<?php
/**
 * Template helper functions.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Permalink for a page slug, with a sensible fallback.
 *
 * @param string $slug Page slug.
 * @return string
 */
function stillframe_page_url( $slug ) {
	$page = stillframe_get_section_page( $slug );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return '';
}

/**
 * Attachment ID for the header logo (custom logo, then site icon).
 *
 * @return int
 */
function stillframe_site_logo_id() {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		return $logo_id;
	}

	return (int) get_option( 'site_icon' );
}

/**
 * Header logo img tag, using a URL WordPress can actually serve.
 *
 * @return string
 */
function stillframe_site_logo_img() {
	$logo_id = stillframe_site_logo_id();
	if ( ! $logo_id ) {
		return '';
	}

	return stillframe_attachment_img(
		$logo_id,
		array(
			'class'    => 'custom-logo',
			'alt'      => '',
			'loading'  => 'eager',
			'decoding' => 'async',
		)
	);
}

/**
 * Find the WordPress page used for a section slug.
 *
 * Matches nested pages (not only /about/) and titles like "About Grant Harsch".
 *
 * @param string $slug about|contact or another page path.
 * @return WP_Post|null
 */
function stillframe_get_section_page( $slug ) {
	$slug = sanitize_title( $slug );

	if ( '' === $slug ) {
		return null;
	}

	$by_path = get_page_by_path( $slug );
	if ( $by_path instanceof WP_Post ) {
		return $by_path;
	}

	$by_name = new WP_Query(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'name'                   => $slug,
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( ! empty( $by_name->posts[0] ) && $by_name->posts[0] instanceof WP_Post ) {
		return $by_name->posts[0];
	}

	$template = '';
	if ( 'about' === $slug ) {
		$template = 'template-about.php';
	} elseif ( 'contact' === $slug ) {
		$template = 'template-contact.php';
	}

	$pages = get_pages(
		array(
			'number'      => 100,
			'sort_column' => 'menu_order,post_title',
		)
	);

	if ( empty( $pages ) ) {
		return null;
	}

	if ( $template ) {
		foreach ( $pages as $candidate ) {
			if ( $candidate instanceof WP_Post && $template === get_page_template_slug( $candidate->ID ) ) {
				return $candidate;
			}
		}
	}

	foreach ( $pages as $candidate ) {
		if ( ! $candidate instanceof WP_Post ) {
			continue;
		}

		$name = (string) $candidate->post_name;
		if ( $slug === $name || 0 === strpos( $name, $slug . '-' ) ) {
			return $candidate;
		}
	}

	foreach ( $pages as $candidate ) {
		if ( $candidate instanceof WP_Post && 0 === stripos( (string) $candidate->post_title, $slug ) ) {
			return $candidate;
		}
	}

	return null;
}

/**
 * Primary navigation items.
 *
 * @return array<int, array{key: string, url: string, label: string}>
 */
function stillframe_nav_items() {
	return array(
		array(
			'key'   => 'about',
			'url'   => stillframe_page_url( 'about' ),
			'label' => __( 'About', 'stillframe' ),
		),
		array(
			'key'   => 'gallery',
			'url'   => get_post_type_archive_link( 'photograph' ),
			'label' => __( 'Gallery', 'stillframe' ),
		),
		array(
			'key'   => 'projects',
			'url'   => get_post_type_archive_link( 'project' ),
			'label' => __( 'Projects', 'stillframe' ),
		),
		array(
			'key'   => 'contact',
			'url'   => stillframe_page_url( 'contact' ),
			'label' => __( 'Contact', 'stillframe' ),
		),
	);
}

/**
 * Whether a primary nav item matches the current request.
 *
 * @param string $key Nav item key.
 * @return bool
 */
function stillframe_nav_item_is_current( $key ) {
	switch ( $key ) {
		case 'home':
			return is_front_page();
		case 'about':
			if ( is_front_page() || ! is_page() ) {
				return false;
			}

			$page_id = (int) get_queried_object_id();
			if ( stillframe_is_about_page( $page_id ) ) {
				return true;
			}

			$about = stillframe_get_section_page( 'about' );
			if ( $about instanceof WP_Post && $page_id === (int) $about->ID ) {
				return true;
			}

			if ( $about instanceof WP_Post && wp_get_post_parent_id( $page_id ) === (int) $about->ID ) {
				return true;
			}

			return in_array( $page_id, stillframe_about_dropdown_page_ids(), true );
		case 'gallery':
			return is_post_type_archive( 'photograph' ) || is_tax( 'photo_series' ) || is_singular( 'photograph' );
		case 'projects':
			return is_post_type_archive( 'project' ) || is_tax( 'project_type' ) || is_singular( 'project' );
		case 'contact':
			if ( is_front_page() || ! is_page() ) {
				return false;
			}

			return stillframe_is_contact_page( (int) get_queried_object_id() );
		default:
			return false;
	}
}

/**
 * Public URL for an attachment WordPress can actually serve.
 *
 * Uses the media library "full" file (often the -scaled.jpg), not the
 * original camera file, which is sometimes not web-accessible.
 *
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function stillframe_largest_attachment_url( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $attachment_id, 'full' );
	if ( $url ) {
		return $url;
	}

	$url = wp_get_attachment_url( $attachment_id );

	return $url ? $url : '';
}

/**
 * Whether a registered size crops the original instead of scaling it.
 *
 * Cropped files would change CSS object-fit framing, so display code skips them.
 *
 * @param string $size Image size name.
 * @return bool
 */
function stillframe_image_size_is_cropped( $size ) {
	$size = (string) $size;

	if ( 'full' === $size || 'medium' === $size || 'medium_large' === $size || 'large' === $size ) {
		return false;
	}

	if ( 'thumbnail' === $size ) {
		return true;
	}

	$sizes = wp_get_additional_image_sizes();
	if ( isset( $sizes[ $size ]['crop'] ) ) {
		return (bool) $sizes[ $size ]['crop'];
	}

	return false;
}

/**
 * Uncropped sizes for gallery / project / series cards.
 *
 * @return string[]
 */
function stillframe_card_image_sizes() {
	return array( 'stillframe-hero', 'large', 'stillframe-gallery', 'medium_large', 'full' );
}

/**
 * Uncropped sizes for full-bleed page backgrounds.
 *
 * @return string[]
 */
function stillframe_world_image_sizes() {
	return array( 'stillframe-world', 'stillframe-hero', 'full' );
}

/**
 * Uncropped sizes for portraits and in-page feature photos.
 *
 * @return string[]
 */
function stillframe_feature_image_sizes() {
	return array( 'stillframe-hero', 'full' );
}

/**
 * URL for one image size, only if that file exists on disk.
 *
 * Missing generated sizes 404 and show a grey box, so this never trusts
 * a size name that WordPress registered but did not write.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Size name, including full.
 * @return string
 */
function stillframe_attachment_size_file_url( $attachment_id, $size ) {
	$attachment_id = (int) $attachment_id;
	$size          = (string) $size;

	if ( ! $attachment_id || '' === $size ) {
		return '';
	}

	if ( 'full' === $size ) {
		return stillframe_largest_attachment_url( $attachment_id );
	}

	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( empty( $meta['sizes'][ $size ]['file'] ) ) {
		return '';
	}

	$attached = get_attached_file( $attachment_id );
	if ( ! $attached ) {
		return '';
	}

	$path = path_join( dirname( $attached ), $meta['sizes'][ $size ]['file'] );
	if ( ! is_readable( $path ) ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $attachment_id, $size );

	return $url ? $url : '';
}

/**
 * Width and height for a chosen size, falling back to the full file.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Size name.
 * @return array{0:int,1:int}
 */
function stillframe_attachment_dimensions( $attachment_id, $size ) {
	$meta = wp_get_attachment_metadata( (int) $attachment_id );
	if ( ! is_array( $meta ) ) {
		return array( 1600, 1067 );
	}

	if ( 'full' !== $size && ! empty( $meta['sizes'][ $size ]['width'] ) && ! empty( $meta['sizes'][ $size ]['height'] ) ) {
		return array(
			(int) $meta['sizes'][ $size ]['width'],
			(int) $meta['sizes'][ $size ]['height'],
		);
	}

	$width  = ! empty( $meta['width'] ) ? (int) $meta['width'] : 1600;
	$height = ! empty( $meta['height'] ) ? (int) $meta['height'] : 1067;

	return array( $width, $height );
}

/**
 * First uncropped size whose file exists, then the full file.
 *
 * @param int      $attachment_id Attachment ID.
 * @param string[] $sizes         Size names to try.
 * @return array{url:string,size:string}
 */
function stillframe_pick_attachment( $attachment_id, $sizes ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return array(
			'url'  => '',
			'size' => 'full',
		);
	}

	foreach ( (array) $sizes as $size ) {
		$size = (string) $size;
		if ( '' === $size || stillframe_image_size_is_cropped( $size ) ) {
			continue;
		}

		$url = stillframe_attachment_size_file_url( $attachment_id, $size );
		if ( $url ) {
			return array(
				'url'  => $url,
				'size' => $size,
			);
		}
	}

	return array(
		'url'  => stillframe_largest_attachment_url( $attachment_id ),
		'size' => 'full',
	);
}

/**
 * Public URL for an attachment at the first usable size in $sizes.
 *
 * @param int      $attachment_id Attachment ID.
 * @param string[] $sizes         Size names to try.
 * @return string
 */
function stillframe_attachment_url( $attachment_id, $sizes = array( 'full' ) ) {
	$picked = stillframe_pick_attachment( $attachment_id, $sizes );

	return $picked['url'];
}

/**
 * Full-bleed background URL, preferring the theme world/hero sizes when present.
 *
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function stillframe_world_attachment_url( $attachment_id ) {
	return stillframe_attachment_url( $attachment_id, stillframe_world_image_sizes() );
}

/**
 * Front-end img tag with a single src WordPress can serve. No srcset.
 *
 * @param int   $attachment_id Attachment ID.
 * @param array $args {
 *     @type string   $class         Class attribute.
 *     @type string   $alt           Alt text.
 *     @type string   $loading       lazy|eager.
 *     @type string   $decoding      async|auto|sync.
 *     @type string   $fetchpriority high|low|auto.
 *     @type string   $size          Single size to try before full.
 *     @type string[] $sizes         Size names to try, uncropped only.
 * }
 * @return string
 */
function stillframe_attachment_img( $attachment_id, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class'         => '',
			'alt'           => '',
			'loading'       => 'eager',
			'decoding'      => 'async',
			'fetchpriority' => '',
			'size'          => 'full',
			'sizes'         => array(),
		)
	);

	$sizes  = ! empty( $args['sizes'] ) ? (array) $args['sizes'] : array( (string) $args['size'] );
	$picked = stillframe_pick_attachment( $attachment_id, $sizes );
	if ( ! $picked['url'] ) {
		return '';
	}

	$dimensions = stillframe_attachment_dimensions( $attachment_id, $picked['size'] );

	$atts = array(
		'src'      => $picked['url'],
		'alt'      => (string) $args['alt'],
		'width'    => (string) $dimensions[0],
		'height'   => (string) $dimensions[1],
		'decoding' => (string) $args['decoding'],
		'loading'  => (string) $args['loading'],
	);

	if ( $args['class'] ) {
		$atts['class'] = (string) $args['class'];
	}

	if ( $args['fetchpriority'] ) {
		$atts['fetchpriority'] = (string) $args['fetchpriority'];
	}

	$html = '<img';
	foreach ( $atts as $name => $value ) {
		$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
	$html .= ' />';

	return $html;
}

/**
 * Img tag for a post card or featured photo.
 *
 * @param int    $post_id Post ID.
 * @param string $class   Image class.
 * @param array  $args    Extra stillframe_attachment_img() args.
 * @return string
 */
function stillframe_post_img( $post_id, $class, $args = array() ) {
	$attachment_id = stillframe_card_thumbnail_id( $post_id );
	if ( ! $attachment_id ) {
		$attachment_id = (int) get_post_thumbnail_id( $post_id );
	}

	if ( ! $attachment_id ) {
		return '';
	}

	$args            = wp_parse_args( $args, array() );
	$args['class']   = $class;
	$args['alt']     = isset( $args['alt'] ) ? $args['alt'] : get_the_title( $post_id );
	$args['loading'] = isset( $args['loading'] ) ? $args['loading'] : 'eager';

	return stillframe_attachment_img( $attachment_id, $args );
}

/**
 * Background source for a site section.
 *
 * @param string $section home|about|gallery|projects|contact.
 * @return array{id:int, url:string}
 */
function stillframe_section_world_source( $section ) {
	$section = sanitize_key( $section );
	$page_id = stillframe_section_banner_page_id( $section );

	if ( $page_id ) {
		$banner_id = (int) get_post_meta( $page_id, 'stillframe_banner_id', true );
		$url       = stillframe_world_attachment_url( $banner_id );
		if ( $url ) {
			return array(
				'id'  => $banner_id,
				'url' => $url,
			);
		}
	}

	$mod_id = (int) get_theme_mod( 'stillframe_hero_' . $section, 0 );
	$url    = stillframe_world_attachment_url( $mod_id );
	if ( $url ) {
		return array(
			'id'  => $mod_id,
			'url' => $url,
		);
	}

	$relative = 'assets/images/' . $section . '-hero.jpg';
	$path     = get_template_directory() . '/' . $relative;

	if ( is_readable( $path ) ) {
		return array(
			'id'  => 0,
			'url' => get_template_directory_uri() . '/' . $relative . '?ver=' . STILLFRAME_VERSION,
		);
	}

	return array(
		'id'  => 0,
		'url' => '',
	);
}

/**
 * Feature sections for a project: heading, copy, and optional photo.
 *
 * @param int $post_id Project ID.
 * @return array<int, array{title:string, text:string, image_id:int}>
 */
function stillframe_project_features( $post_id ) {
	$raw = get_post_meta( (int) $post_id, 'stillframe_project_features', true );

	if ( is_string( $raw ) && '' !== $raw ) {
		$decoded = json_decode( $raw, true );
		$raw     = is_array( $decoded ) ? $decoded : maybe_unserialize( $raw );
	}

	if ( ! is_array( $raw ) ) {
		return array();
	}

	$features = array();

	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$title    = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
		$text     = isset( $row['text'] ) ? sanitize_textarea_field( $row['text'] ) : '';
		$image_id = isset( $row['image_id'] ) ? absint( $row['image_id'] ) : 0;

		if ( $image_id && ! stillframe_largest_attachment_url( $image_id ) ) {
			$image_id = 0;
		}

		if ( '' === $title && '' === $text && ! $image_id ) {
			continue;
		}

		$features[] = array(
			'title'    => $title,
			'text'     => $text,
			'image_id' => $image_id,
		);
	}

	return $features;
}

/**
 * Full-bleed photo for a single project. Only images uploaded on that project.
 *
 * @param int $post_id Project ID.
 * @return array{id:int, url:string}
 */
function stillframe_project_world_source( $post_id ) {
	$post_id = (int) $post_id;
	$ids     = array(
		(int) get_post_meta( $post_id, 'stillframe_banner_id', true ),
		(int) get_post_thumbnail_id( $post_id ),
	);

	foreach ( $ids as $attachment_id ) {
		$url = stillframe_world_attachment_url( $attachment_id );
		if ( $url ) {
			return array(
				'id'  => $attachment_id,
				'url' => $url,
			);
		}
	}

	return array(
		'id'  => 0,
		'url' => '',
	);
}

/**
 * Attachment and URL for the full-bleed page background.
 *
 * @return array{id:int, url:string}
 */
function stillframe_page_world_source() {
	$empty = array(
		'id'  => 0,
		'url' => '',
	);

	if ( is_singular( 'photograph' ) ) {
		return $empty;
	}

	if ( is_singular( 'project' ) ) {
		return stillframe_project_world_source( (int) get_queried_object_id() );
	}

	if ( is_singular( 'page' ) ) {
		$banner_id = (int) get_post_meta( (int) get_queried_object_id(), 'stillframe_banner_id', true );
		$url       = stillframe_world_attachment_url( $banner_id );
		if ( $url ) {
			return array(
				'id'  => $banner_id,
				'url' => $url,
			);
		}
	}

	if ( is_front_page() ) {
		return stillframe_section_world_source( 'home' );
	}

	if ( is_singular( 'page' ) && stillframe_is_about_page( (int) get_queried_object_id() ) ) {
		return stillframe_section_world_source( 'about' );
	}

	if ( is_singular( 'page' ) && stillframe_is_contact_page( (int) get_queried_object_id() ) ) {
		return stillframe_section_world_source( 'contact' );
	}

	if ( is_post_type_archive( 'photograph' ) || is_tax( 'photo_series' ) ) {
		return stillframe_section_world_source( 'gallery' );
	}

	if ( is_post_type_archive( 'project' ) ) {
		return stillframe_section_world_source( 'projects' );
	}

	return stillframe_section_world_source( 'home' );
}

/**
 * Full-bleed photo behind the page panel.
 *
 * @return string
 */
function stillframe_page_world_url() {
	$source = stillframe_page_world_source();

	return $source['url'];
}

/**
 * Page whose banner meta belongs to a site section.
 *
 * @param string $section home|about|gallery|projects|contact.
 * @return int
 */
function stillframe_section_banner_page_id( $section ) {
	if ( 'home' === $section ) {
		return (int) get_option( 'page_on_front' );
	}

	if ( 'about' === $section ) {
		$current = (int) get_queried_object_id();
		if ( $current && stillframe_is_about_page( $current ) ) {
			return $current;
		}

		$about = stillframe_get_section_page( 'about' );
		return $about instanceof WP_Post ? (int) $about->ID : 0;
	}

	if ( 'contact' === $section ) {
		$current = (int) get_queried_object_id();
		if ( $current && stillframe_is_contact_page( $current ) ) {
			return $current;
		}

		$contact = stillframe_get_section_page( 'contact' );
		return $contact instanceof WP_Post ? (int) $contact->ID : 0;
	}

	if ( 'gallery' === $section || 'projects' === $section ) {
		$page = stillframe_get_section_page( $section );
		return $page instanceof WP_Post ? (int) $page->ID : 0;
	}

	return 0;
}

/**
 * Page IDs chosen to appear under About in the header.
 *
 * @param int $about_id About page ID. Defaults to the About section page.
 * @return int[]
 */
function stillframe_about_dropdown_page_ids( $about_id = 0 ) {
	if ( ! $about_id ) {
		$about    = stillframe_get_section_page( 'about' );
		$about_id = $about instanceof WP_Post ? (int) $about->ID : 0;
	}

	$about_id = (int) $about_id;
	if ( ! $about_id ) {
		return array();
	}

	$raw = get_post_meta( $about_id, 'stillframe_about_dropdown_ids', true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$ids = array();
	foreach ( $raw as $id ) {
		$id = (int) $id;
		if ( $id && $id !== $about_id ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Page ID for an internal URL, or 0.
 *
 * @param string $url Link href.
 * @return int
 */
function stillframe_url_to_page_id( $url ) {
	$url = trim( html_entity_decode( (string) $url, ENT_QUOTES ) );
	if ( '' === $url || ! preg_match( '/^[^#]/', $url ) || preg_match( '#^(mailto:|tel:|javascript:)#i', $url ) ) {
		return 0;
	}

	$home_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$parts     = wp_parse_url( $url );
	$host      = isset( $parts['host'] ) ? (string) $parts['host'] : '';
	if ( $host && $home_host && 0 !== strcasecmp( $host, $home_host ) ) {
		return 0;
	}

	$path = isset( $parts['path'] ) ? (string) $parts['path'] : '';
	$abs  = $host ? preg_replace( '/#.*$/', '', $url ) : home_url( $path ? $path : '/' . ltrim( $url, '/' ) );
	$abs  = preg_replace( '/#.*$/', '', $abs );

	$post_id = url_to_postid( $abs );
	if ( $post_id ) {
		$post = get_post( $post_id );
		return ( $post instanceof WP_Post && 'page' === $post->post_type ) ? (int) $post_id : 0;
	}

	$home_path = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	$rel       = trim( (string) $path, '/' );
	if ( $home_path ) {
		if ( $rel === $home_path ) {
			$rel = '';
		} elseif ( 0 === strpos( $rel, $home_path . '/' ) ) {
			$rel = substr( $rel, strlen( $home_path ) + 1 );
		}
	}

	if ( $rel ) {
		$page = get_page_by_path( $rel );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return (int) $page->ID;
		}
	}

	$slug = $rel ? basename( untrailingslashit( $rel ) ) : '';
	if ( '' === $slug ) {
		return 0;
	}

	$page = get_page_by_path( $slug );
	return $page instanceof WP_Post ? (int) $page->ID : 0;
}

/**
 * Page IDs linked from About copy, in the order they appear.
 *
 * @param int $about_id About page ID.
 * @return int[]
 */
function stillframe_about_content_page_ids( $about_id ) {
	$about_id = (int) $about_id;
	$post     = get_post( $about_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	if ( ! preg_match_all( '/<a\s[^>]*href\s*=\s*([\'"])([^\'"]+)\1/i', (string) $post->post_content, $matches ) ) {
		return array();
	}

	$home_id = (int) get_option( 'page_on_front' );
	$ids     = array();
	foreach ( $matches[2] as $href ) {
		$page_id = stillframe_url_to_page_id( $href );
		if ( ! $page_id || $page_id === $about_id || $page_id === $home_id ) {
			continue;
		}
		$ids[] = $page_id;
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Put page IDs in the same order as links on About.
 *
 * @param int[] $ids      Page IDs.
 * @param int   $about_id About page ID.
 * @return int[]
 */
function stillframe_order_ids_like_about_links( $ids, $about_id ) {
	$ids   = array_values( array_unique( array_map( 'intval', $ids ) ) );
	$order = stillframe_about_content_page_ids( $about_id );
	if ( ! $ids || ! $order ) {
		return $ids;
	}

	$rank = array_flip( $order );
	$head = array();
	$tail = array();

	foreach ( $order as $id ) {
		if ( in_array( $id, $ids, true ) ) {
			$head[] = $id;
		}
	}

	foreach ( $ids as $id ) {
		if ( ! isset( $rank[ $id ] ) ) {
			$tail[] = $id;
		}
	}

	return array_values( array_unique( array_merge( $head, $tail ) ) );
}

/**
 * Page IDs in the same order as About timeline events.
 *
 * @param int $about_id About page ID.
 * @return int[]
 */
function stillframe_about_timeline_page_ids( $about_id ) {
	$about_id = (int) $about_id;
	if ( ! $about_id ) {
		return array();
	}

	$ids = array();
	foreach ( stillframe_about_timeline_data( $about_id )['events'] as $event ) {
		if ( empty( $event['page_id'] ) ) {
			continue;
		}
		$ids[] = (int) $event['page_id'];
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Put page IDs in the same order as About timeline events.
 *
 * @param int[] $ids      Page IDs.
 * @param int   $about_id About page ID.
 * @return int[]
 */
function stillframe_order_ids_like_about_timeline( $ids, $about_id ) {
	$ids   = array_values( array_unique( array_map( 'intval', $ids ) ) );
	$order = stillframe_about_timeline_page_ids( $about_id );
	if ( ! $ids || ! $order ) {
		return $ids;
	}

	$rank = array_flip( $order );
	$head = array();
	$tail = array();

	foreach ( $order as $id ) {
		if ( in_array( $id, $ids, true ) ) {
			$head[] = $id;
		}
	}

	foreach ( $ids as $id ) {
		if ( ! isset( $rank[ $id ] ) ) {
			$tail[] = $id;
		}
	}

	return array_values( array_unique( array_merge( $head, $tail ) ) );
}

/**
 * Links in the About header dropdown.
 *
 * Uses pages picked on the About screen, then child pages, then on-page headings.
 * Chosen pages follow the order of events on the About timeline.
 *
 * @return array<int, array{url:string, label:string, current:bool}>
 */
function stillframe_about_dropdown_items() {
	$about = stillframe_get_section_page( 'about' );
	if ( ! $about instanceof WP_Post ) {
		return array();
	}

	$about_id = (int) $about->ID;
	$items    = array();
	$ids      = stillframe_about_dropdown_page_ids( $about_id );
	$linked   = stillframe_about_content_page_ids( $about_id );
	$timeline = stillframe_about_timeline_page_ids( $about_id );

	if ( $ids && $timeline ) {
		$ids = stillframe_order_ids_like_about_timeline( $ids, $about_id );
	} elseif ( $ids && $linked ) {
		$ids = stillframe_order_ids_like_about_links( $ids, $about_id );
	} elseif ( ! $ids && $timeline ) {
		$ids = $timeline;
	} elseif ( ! $ids && $linked ) {
		$ids = $linked;
	}

	if ( $ids ) {
		foreach ( $ids as $page_id ) {
			$page = get_post( $page_id );
			if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
				continue;
			}

			$url = get_permalink( $page );
			if ( ! $url ) {
				continue;
			}

			$items[] = array(
				'url'     => $url,
				'label'   => get_the_title( $page ),
				'current' => is_page( $page_id ),
			);
		}

		return $items;
	}

	$children = get_pages(
		array(
			'parent'      => $about_id,
			'sort_column' => 'menu_order,post_title',
		)
	);

	if ( $children ) {
		foreach ( $children as $page ) {
			$url = get_permalink( $page );
			if ( ! $url ) {
				continue;
			}

			$items[] = array(
				'url'     => $url,
				'label'   => get_the_title( $page ),
				'current' => is_page( (int) $page->ID ),
			);
		}

		if ( $items ) {
			return $items;
		}
	}

	$about_url = (string) get_permalink( $about );
	foreach ( stillframe_about_toc_items( $about_id ) as $heading ) {
		$items[] = array(
			'url'     => $about_url . '#' . $heading['id'],
			'label'   => $heading['title'],
			'current' => false,
		);
	}

	return $items;
}

/**
 * Links in the Projects header dropdown.
 *
 * @return array<int, array{url:string, label:string, current:bool}>
 */
function stillframe_projects_dropdown_items() {
	$query = new WP_Query(
		array(
			'post_type'              => 'project',
			'post_status'            => 'publish',
			'posts_per_page'         => 24,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$items     = array();
	$current_id = is_singular( 'project' ) ? (int) get_queried_object_id() : 0;

	foreach ( $query->posts as $project ) {
		$url = get_permalink( $project );
		if ( ! $url ) {
			continue;
		}

		$items[] = array(
			'url'     => $url,
			'label'   => get_the_title( $project ),
			'current' => $current_id === (int) $project->ID,
		);
	}

	return $items;
}

/**
 * Dropdown links for a primary nav item, if any.
 *
 * @param string $key Nav item key.
 * @return array<int, array{url:string, label:string, current:bool}>
 */
function stillframe_nav_dropdown_items( $key ) {
	if ( 'about' === $key ) {
		return stillframe_about_dropdown_items();
	}

	if ( 'projects' === $key ) {
		return stillframe_projects_dropdown_items();
	}

	return array();
}

/**
 * Fallback menu when no menu is assigned in Appearance → Menus.
 */
function stillframe_fallback_menu() {
	echo '<ul class="nav-list">';

	foreach ( stillframe_nav_items() as $item ) {
		if ( empty( $item['url'] ) ) {
			continue;
		}

		$current  = stillframe_nav_item_is_current( $item['key'] );
		$children = stillframe_nav_dropdown_items( $item['key'] );

		if ( $children ) {
			$child_current = false;
			foreach ( $children as $child ) {
				if ( ! empty( $child['current'] ) ) {
					$child_current = true;
					break;
				}
			}

			$li_class = 'nav-item nav-item--drop';
			if ( $current || $child_current ) {
				$li_class .= ' is-current';
			}

			$toggle_label = sprintf(
				/* translators: %s: nav item label, e.g. About or Projects. */
				__( '%s menu', 'stillframe' ),
				$item['label']
			);

			echo '<li class="' . esc_attr( $li_class ) . '">';
			echo '<div class="nav-item__hit">';
			printf(
				'<a class="nav-link" href="%1$s"%2$s>%3$s</a>',
				esc_url( $item['url'] ),
				$current && ! $child_current ? ' aria-current="page"' : '',
				esc_html( $item['label'] )
			);
			echo '<button type="button" class="nav-drop__toggle" aria-expanded="false" aria-label="' . esc_attr( $toggle_label ) . '"><span aria-hidden="true"></span></button>';
			echo '</div>';
			echo '<ul class="nav-sub">';

			foreach ( $children as $child ) {
				printf(
					'<li class="%1$s"><a href="%2$s"%3$s>%4$s</a></li>',
					! empty( $child['current'] ) ? 'is-current' : '',
					esc_url( $child['url'] ),
					! empty( $child['current'] ) ? ' aria-current="page"' : '',
					esc_html( $child['label'] )
				);
			}

			echo '</ul>';
			echo '</li>';
			continue;
		}

		printf(
			'<li class="%1$s"><a class="nav-link" href="%2$s"%3$s>%4$s</a></li>',
			$current ? 'is-current' : '',
			esc_url( $item['url'] ),
			$current ? ' aria-current="page"' : '',
			esc_html( $item['label'] )
		);
	}

	echo '</ul>';
}

/**
 * Split a comma-separated stack string into tags.
 *
 * @param int $post_id Project ID.
 * @return string[]
 */
function stillframe_project_stack( $post_id ) {
	$raw = (string) get_post_meta( $post_id, 'stillframe_stack', true );

	if ( '' === $raw ) {
		return array();
	}

	$parts = array_map( 'trim', explode( ',', $raw ) );

	return array_values( array_filter( $parts ) );
}

/**
 * Series terms that currently have photographs.
 *
 * @return WP_Term[]
 */
function stillframe_photo_series_terms() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'photo_series',
			'hide_empty' => true,
			'parent'     => 0,
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Image to show on a gallery or project card.
 *
 * Featured image first, then the page/project background upload, then an
 * image attached to the post or placed in the editor.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function stillframe_card_thumbnail_id( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return 0;
	}

	$candidates = array(
		(int) get_post_thumbnail_id( $post_id ),
		(int) get_post_meta( $post_id, 'stillframe_banner_id', true ),
	);

	foreach ( $candidates as $attachment_id ) {
		if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
			return $attachment_id;
		}
	}

	$attached = get_children(
		array(
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => 1,
			'orderby'        => 'menu_order ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	if ( $attached ) {
		$attachment_id = (int) reset( $attached );
		if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
			return $attachment_id;
		}
	}

	$post = get_post( $post_id );
	if ( $post instanceof WP_Post && preg_match( '/wp-image-(\d+)/', (string) $post->post_content, $match ) ) {
		$attachment_id = (int) $match[1];
		if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
			return $attachment_id;
		}
	}

	return 0;
}

/**
 * HTML for a card thumbnail.
 *
 * Prefers a smaller uncropped derivative when that file exists, so cards
 * do not download the full photo. Framing stays the same under object-fit.
 *
 * @param int    $post_id Post ID.
 * @param string $class   Image class.
 * @return string
 */
function stillframe_get_card_image( $post_id, $class ) {
	return stillframe_post_img(
		$post_id,
		$class,
		array(
			'sizes' => stillframe_card_image_sizes(),
		)
	);
}

/**
 * Recent photographs in a series, for card previews.
 *
 * @param int $term_id Series term ID.
 * @param int $count   How many to fetch.
 * @return WP_Post[]
 */
function stillframe_series_preview_photos( $term_id, $count = 3 ) {
	$count = max( 1, (int) $count );
	$ids   = get_posts(
		array(
			'post_type'      => 'photograph',
			'posts_per_page' => 24,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'photo_series',
					'field'    => 'term_id',
					'terms'    => (int) $term_id,
				),
			),
		)
	);

	$with_image = array();

	foreach ( $ids as $post_id ) {
		$post_id = (int) $post_id;
		if ( ! stillframe_card_thumbnail_id( $post_id ) ) {
			continue;
		}

		$post = get_post( $post_id );
		if ( $post instanceof WP_Post ) {
			$with_image[] = $post;
		}

		if ( count( $with_image ) >= $count ) {
			break;
		}
	}

	return $with_image;
}

/**
 * Previous and next photograph IDs, in title order, within the same series
 * or among ungrouped gallery photos.
 *
 * @param int $post_id Current photograph ID.
 * @return array{prev: int|null, next: int|null}
 */
function stillframe_photograph_neighbors( $post_id ) {
	$post_id = (int) $post_id;
	$args    = array(
		'post_type'      => 'photograph',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);

	$terms = get_the_terms( $post_id, 'photo_series' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'photo_series',
				'field'    => 'term_id',
				'terms'    => (int) $terms[0]->term_id,
			),
		);
	} else {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'photo_series',
				'operator' => 'NOT EXISTS',
			),
		);
	}

	$ids   = array_map( 'intval', get_posts( $args ) );
	$index = array_search( $post_id, $ids, true );

	if ( false === $index ) {
		return array(
			'prev' => null,
			'next' => null,
		);
	}

	return array(
		'prev' => $index > 0 ? $ids[ $index - 1 ] : null,
		'next' => ( $index < count( $ids ) - 1 ) ? $ids[ $index + 1 ] : null,
	);
}

/**
 * Whether an attachment can be used as the resume.
 *
 * @param int $attachment_id Attachment ID.
 * @return bool
 */
function stillframe_is_resume_file( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id ) {
		return false;
	}

	$mime = (string) get_post_mime_type( $attachment_id );
	$file = (string) get_attached_file( $attachment_id );

	if ( preg_match( '/\.(pdf|docx?|jpe?g|png|webp)$/i', $file ) ) {
		return true;
	}

	if ( false !== strpos( $mime, 'pdf' ) || 0 === strpos( $mime, 'image/' ) ) {
		return true;
	}

	return (bool) wp_get_attachment_url( $attachment_id );
}

/**
 * Whether this page is the About screen (slug or page template).
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return bool
 */
function stillframe_is_about_page( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id ) {
		return false;
	}

	if ( stillframe_is_resume_page( $page_id ) ) {
		return false;
	}

	$slug = (string) get_post_field( 'post_name', $page_id );
	if ( 'about' === $slug || 0 === strpos( $slug, 'about' ) ) {
		return true;
	}

	return 'template-about.php' === get_page_template_slug( $page_id );
}

/**
 * Whether this page is linked from About (dropdown, child page, or About copy).
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return bool
 */
function stillframe_is_about_subpage( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id ) {
		return false;
	}

	if ( stillframe_is_about_page( $page_id ) || stillframe_is_contact_page( $page_id ) || stillframe_is_home_page( $page_id ) ) {
		return false;
	}

	if ( stillframe_is_resume_page( $page_id ) ) {
		return true;
	}

	$about    = stillframe_get_section_page( 'about' );
	$about_id = $about instanceof WP_Post ? (int) $about->ID : 0;

	if ( $about_id && (int) wp_get_post_parent_id( $page_id ) === $about_id ) {
		return true;
	}

	if ( in_array( $page_id, stillframe_about_dropdown_page_ids( $about_id ), true ) ) {
		return true;
	}

	return $about_id && in_array( $page_id, stillframe_about_content_page_ids( $about_id ), true );
}

/**
 * Whether this page is the Resume screen.
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return bool
 */
function stillframe_is_resume_page( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id ) {
		return false;
	}

	$slug  = strtolower( (string) get_post_field( 'post_name', $page_id ) );
	$title = strtolower( trim( (string) get_post_field( 'post_title', $page_id ) ) );

	if ( in_array( $slug, array( 'resume', 'cv' ), true ) || 0 === strpos( $slug, 'resume-' ) || 0 === strpos( $slug, 'cv-' ) ) {
		return true;
	}

	if ( in_array( $title, array( 'resume', 'cv' ), true ) ) {
		return true;
	}

	$resume = stillframe_get_section_page( 'resume' );
	if ( $resume instanceof WP_Post && $page_id === (int) $resume->ID ) {
		return true;
	}

	return false;
}

/**
 * Whether this page is the Contact screen (slug or page template).
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return bool
 */
function stillframe_is_contact_page( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id ) {
		return false;
	}

	$slug = (string) get_post_field( 'post_name', $page_id );
	if ( 'contact' === $slug || 0 === strpos( $slug, 'contact' ) ) {
		return true;
	}

	return 'template-contact.php' === get_page_template_slug( $page_id );
}

/**
 * Whether this page is the Home screen used by front-page.php.
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return bool
 */
function stillframe_is_home_page( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id ) {
		return false;
	}

	if ( (int) get_option( 'page_on_front' ) === $page_id ) {
		return true;
	}

	return 'home' === (string) get_post_field( 'post_name', $page_id );
}

/**
 * Subtitle under the Home banner title.
 *
 * @return string
 */
function stillframe_home_subtitle() {
	$page_id = (int) get_option( 'page_on_front' );
	if ( $page_id ) {
		$value = get_post_meta( $page_id, 'stillframe_subtitle', true );
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	return (string) get_theme_mod( 'stillframe_vibe_line', '' );
}

/**
 * Home description HTML from the front page editor.
 *
 * @return string
 */
function stillframe_home_intro_html() {
	$page_id = (int) get_option( 'page_on_front' );
	if ( $page_id ) {
		$post = get_post( $page_id );
		if ( $post instanceof WP_Post && '' !== trim( wp_strip_all_tags( (string) $post->post_content ) ) ) {
			return apply_filters( 'the_content', $post->post_content );
		}
	}

	$mod = trim( (string) get_theme_mod( 'stillframe_home_intro', '' ) );

	return $mod ? wp_kses_post( wpautop( $mod ) ) : '';
}

/**
 * A Contact page setting, with Customizer leftover as fallback.
 *
 * @param string $key     Meta / theme_mod key.
 * @param string $default Fallback if both are empty.
 * @return string
 */
function stillframe_contact_setting( $key, $default = '' ) {
	$page = stillframe_get_section_page( 'contact' );

	if ( $page instanceof WP_Post ) {
		$value = get_post_meta( $page->ID, $key, true );
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	$mod = get_theme_mod( $key, $default );

	return is_string( $mod ) ? $mod : $default;
}

/**
 * Attachment ID for the uploaded resume.
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return int
 */
function stillframe_resume_attachment_id( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id || ! stillframe_is_resume_page( $page_id ) ) {
		return 0;
	}

	$ids = array(
		(int) get_post_meta( $page_id, 'stillframe_resume_id', true ),
	);

	$resume_page = stillframe_get_section_page( 'resume' );
	if ( $resume_page instanceof WP_Post ) {
		$ids[] = (int) get_post_meta( $resume_page->ID, 'stillframe_resume_id', true );
	}

	$about = stillframe_get_section_page( 'about' );
	if ( $about instanceof WP_Post ) {
		$ids[] = (int) get_post_meta( $about->ID, 'stillframe_resume_id', true );
	}

	$ids[] = (int) get_theme_mod( 'stillframe_resume_id', 0 );
	$ids   = array_unique( array_filter( $ids ) );

	foreach ( $ids as $attachment_id ) {
		if ( stillframe_is_resume_file( $attachment_id ) && wp_get_attachment_url( $attachment_id ) ) {
			return $attachment_id;
		}
	}

	return 0;
}

/**
 * Public URL for the uploaded resume.
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return string
 */
function stillframe_resume_url( $page_id = 0 ) {
	$attachment_id = stillframe_resume_attachment_id( $page_id );

	return $attachment_id ? (string) wp_get_attachment_url( $attachment_id ) : '';
}

/**
 * Raw PDF bytes for a page's resume, or empty string.
 *
 * @param int $page_id Page ID.
 * @return string
 */
function stillframe_resume_pdf_bytes( $page_id ) {
	$attachment_id = stillframe_resume_attachment_id( (int) $page_id );
	if ( ! $attachment_id ) {
		return '';
	}

	$path = (string) get_attached_file( $attachment_id );
	if ( $path && is_readable( $path ) ) {
		$bytes = file_get_contents( $path );
		if ( false !== $bytes && '' !== $bytes ) {
			return $bytes;
		}
	}

	$url = (string) wp_get_attachment_url( $attachment_id );
	if ( ! $url ) {
		return '';
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return '';
	}

	return (string) wp_remote_retrieve_body( $response );
}

/**
 * Byte length that stays correct if mbstring overloads strlen().
 *
 * @param string $bytes Binary string.
 * @return int
 */
function stillframe_byte_length( $bytes ) {
	if ( function_exists( 'mb_strlen' ) ) {
		return (int) mb_strlen( $bytes, '8bit' );
	}

	return strlen( $bytes );
}

/**
 * Send a PDF with headers that do not truncate or gzip the file.
 *
 * @param string $bytes PDF contents.
 */
function stillframe_send_pdf_bytes( $bytes ) {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	if ( function_exists( 'apache_setenv' ) ) {
		apache_setenv( 'no-gzip', '1' );
	}
	if ( function_exists( 'ini_set' ) ) {
		ini_set( 'zlib.output_compression', 'Off' );
	}

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="resume.pdf"' );
	header( 'Content-Length: ' . (string) stillframe_byte_length( $bytes ) );
	header( 'Accept-Ranges: none' );
	header( 'Content-Encoding: identity' );
	header( 'X-Content-Type-Options: nosniff' );

	echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * Same-origin PDF so the on-page renderer can read the file.
 */
function stillframe_resume_pdf_file() {
	if ( ! isset( $_GET['stillframe_resume_pdf'] ) ) {
		return;
	}

	$bytes = stillframe_resume_pdf_bytes( absint( wp_unslash( $_GET['stillframe_resume_pdf'] ) ) );
	if ( '' === $bytes ) {
		status_header( 404 );
		exit;
	}

	stillframe_send_pdf_bytes( $bytes );
}
add_action( 'template_redirect', 'stillframe_resume_pdf_file', 0 );

/**
 * REST route for the resume PDF.
 */
function stillframe_register_resume_rest() {
	register_rest_route(
		'stillframe/v1',
		'/resume/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'stillframe_rest_resume_pdf',
			'args'                => array(
				'id' => array(
					'required' => true,
					'type'     => 'integer',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'stillframe_register_resume_rest' );

/**
 * REST callback: stream the resume PDF.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_Error
 */
function stillframe_rest_resume_pdf( $request ) {
	$bytes = stillframe_resume_pdf_bytes( (int) $request['id'] );
	if ( '' === $bytes ) {
		return new WP_Error( 'stillframe_resume_missing', __( 'Resume not found.', 'stillframe' ), array( 'status' => 404 ) );
	}

	stillframe_send_pdf_bytes( $bytes );
}

/**
 * Keep series photographs off the main gallery grid when series exist.
 *
 * @param WP_Query $query Query.
 */
function stillframe_gallery_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'photograph' ) && stillframe_photo_series_terms() ) {
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'photo_series',
					'operator' => 'NOT EXISTS',
				),
			)
		);
	}

	if ( $query->is_post_type_archive( 'photograph' ) || $query->is_tax( 'photo_series' ) || $query->is_post_type_archive( 'project' ) || $query->is_tax( 'project_type' ) ) {
		$query->set( 'posts_per_page', 24 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'stillframe_gallery_query' );

/**
 * Extra body class for motion styles.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function stillframe_body_class( $classes ) {
	$classes[] = 'has-page-motion';

	if ( stillframe_page_world_url() ) {
		$classes[] = 'has-page-world';
	}

	if ( is_front_page() ) {
		$classes[] = 'vibe-home';
	} elseif ( is_singular( 'page' ) && ( stillframe_is_about_page( get_queried_object_id() ) || stillframe_is_about_subpage( get_queried_object_id() ) ) ) {
		$classes[] = 'vibe-about';
	} elseif ( is_page( 'contact' ) || is_page_template( 'template-contact.php' ) || ( is_singular( 'page' ) && stillframe_is_contact_page( get_queried_object_id() ) ) ) {
		$classes[] = 'vibe-contact';
	} elseif ( is_post_type_archive( 'photograph' ) || is_tax( 'photo_series' ) || is_singular( 'photograph' ) ) {
		$classes[] = 'vibe-gallery';
	} elseif ( is_post_type_archive( 'project' ) || is_singular( 'project' ) ) {
		$classes[] = 'vibe-projects';
	} elseif ( is_404() ) {
		$classes[] = 'vibe-lost';
	} else {
		$classes[] = 'vibe-home';
	}

	return $classes;
}
add_filter( 'body_class', 'stillframe_body_class' );

/**
 * Headings in HTML content, with ids matching stillframe_about_heading_ids().
 *
 * @param string $content HTML.
 * @param int    $min_level 2–4.
 * @param int    $max_level 2–4.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_content_headings( $content, $min_level = 2, $max_level = 3 ) {
	$items = array();
	if ( ! $content ) {
		return $items;
	}

	$min_level = max( 2, min( 4, (int) $min_level ) );
	$max_level = max( $min_level, min( 4, (int) $max_level ) );
	$used      = array();

	if ( ! preg_match_all( '/<h([2-4])(\s[^>]*)?>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $items;
	}

	$count = count( $matches );
	for ( $i = 0; $i < $count; $i++ ) {
		$match = $matches[ $i ];
		$level = (int) $match[1][0];
		if ( $level < $min_level || $level > $max_level ) {
			continue;
		}

		$title = trim( wp_strip_all_tags( $match[3][0] ) );
		if ( '' === $title ) {
			continue;
		}

		$heading_end = $match[0][1] + strlen( $match[0][0] );
		$next_start  = strlen( $content );
		for ( $j = $i + 1; $j < $count; $j++ ) {
			if ( (int) $matches[ $j ][1][0] <= $level ) {
				$next_start = (int) $matches[ $j ][0][1];
				break;
			}
		}
		$following = substr( $content, $heading_end, max( 0, $next_start - $heading_end ) );
		if ( ! stillframe_section_has_body( $following ) ) {
			continue;
		}

		$attrs = isset( $match[2][0] ) ? $match[2][0] : '';
		$id    = '';
		if ( preg_match( '/\sid\s*=\s*([\'"])([^\'"]+)\1/i', $attrs, $id_match ) ) {
			$id = sanitize_title( $id_match[2] );
		}
		if ( '' === $id ) {
			$id = sanitize_title( $title );
		}
		if ( '' === $id ) {
			continue;
		}

		$base = $id;
		$n    = 2;
		while ( isset( $used[ $id ] ) ) {
			$id = $base . '-' . $n;
			++$n;
		}
		$used[ $id ] = true;

		$items[] = array(
			'id'    => $id,
			'title' => $title,
			'level' => $level,
		);
	}

	return $items;
}

/**
 * Strip comments and links from a content fragment so it can sit inside a card link.
 *
 * @param string $html HTML.
 * @return string
 */
function stillframe_about_timeline_plain_html( $html ) {
	$html = preg_replace( '/<!--.*?-->/s', '', (string) $html );
	$html = preg_replace( '/<\/?a\b[^>]*>/i', '', (string) $html );

	return trim( (string) $html );
}

/**
 * Short copy from a page when a timeline section has no body of its own.
 *
 * @param int $page_id Page ID.
 * @return string
 */
function stillframe_about_timeline_page_excerpt( $page_id ) {
	$page_id = (int) $page_id;
	if ( ! $page_id ) {
		return '';
	}

	$excerpt = get_the_excerpt( $page_id );
	if ( is_string( $excerpt ) && '' !== trim( $excerpt ) ) {
		return wpautop( esc_html( trim( $excerpt ) ) );
	}

	$raw = wp_strip_all_tags( (string) get_post_field( 'post_content', $page_id ) );
	$raw = wp_trim_words( $raw, 48 );

	return $raw ? wpautop( esc_html( $raw ) ) : '';
}

/**
 * Pages the About timeline can point at: dropdown, children, and in-copy links.
 *
 * @param int $about_id About page ID.
 * @return int[]
 */
function stillframe_about_related_page_ids( $about_id ) {
	$about_id = (int) $about_id;
	$ids      = stillframe_about_dropdown_page_ids( $about_id );

	if ( $about_id ) {
		$children = get_pages(
			array(
				'parent'      => $about_id,
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
			)
		);
		foreach ( $children as $child ) {
			if ( $child instanceof WP_Post ) {
				$ids[] = (int) $child->ID;
			}
		}
	}

	$ids = array_merge( $ids, stillframe_about_content_page_ids( $about_id ) );
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	return stillframe_order_ids_like_about_links( $ids, $about_id );
}

/**
 * Collapse a title for loose About timeline matching.
 *
 * @param string $title Title.
 * @return string
 */
function stillframe_about_timeline_normalize_title( $title ) {
	$title = strtolower( html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' ) );
	$title = preg_replace( '/[()]/', ' ', $title );
	$title = str_replace( array( '&', '/', '+' ), ' ', $title );
	$title = preg_replace( '/[^a-z0-9]+/', ' ', $title );
	$title = preg_replace( '/\b(and|the|a|an|of|at|for|in|to|my|work)\b/', ' ', $title );

	return trim( preg_replace( '/\s+/', ' ', (string) $title ) );
}

/**
 * Whether two About titles refer to the same section.
 *
 * @param string $a Title.
 * @param string $b Title.
 * @return bool
 */
function stillframe_about_timeline_titles_match( $a, $b ) {
	$a = stillframe_about_timeline_normalize_title( $a );
	$b = stillframe_about_timeline_normalize_title( $b );
	if ( '' === $a || '' === $b ) {
		return false;
	}
	if ( $a === $b ) {
		return true;
	}
	if ( false !== strpos( $a, $b ) || false !== strpos( $b, $a ) ) {
		return true;
	}

	$stem = static function ( $word ) {
		return preg_replace( '/(ing|ment|tion|ence|ance|ed|er|es|s)$/', '', $word );
	};

	$wa      = array_values( array_filter( array_map( $stem, preg_split( '/\s+/', $a ) ) ) );
	$wb      = array_values( array_filter( array_map( $stem, preg_split( '/\s+/', $b ) ) ) );
	$overlap = array_values(
		array_filter(
			array_intersect( $wa, $wb ),
			static function ( $word ) {
				return strlen( (string) $word ) >= 4;
			}
		)
	);

	if ( ! $overlap ) {
		return false;
	}
	if ( count( $overlap ) >= 2 ) {
		return true;
	}

	return strlen( (string) $overlap[0] ) >= 6;
}

/**
 * Timeline card data from a related page.
 *
 * @param int $page_id Page ID.
 * @return array{id:string, title:string, html:string, url:string, page_id:int}|null
 */
function stillframe_about_timeline_event_from_page( $page_id ) {
	$page_id = (int) $page_id;
	$page    = get_post( $page_id );
	if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
		return null;
	}

	$url = get_permalink( $page );
	if ( ! $url ) {
		return null;
	}

	$title = get_the_title( $page );
	$id    = sanitize_title( $title );
	if ( '' === $id ) {
		$id = 'page-' . $page_id;
	}

	return array(
		'id'      => $id,
		'title'   => $title,
		'html'    => stillframe_about_timeline_page_excerpt( $page_id ),
		'url'     => $url,
		'page_id' => $page_id,
	);
}

/**
 * Page a timeline heading should open, from its section links or title.
 *
 * @param string $html     Section HTML, including the heading if it is a link.
 * @param string $title    Heading text.
 * @param int    $about_id About page ID.
 * @return int
 */
function stillframe_about_timeline_event_page_id( $html, $title, $about_id ) {
	$about_id = (int) $about_id;
	$home_id  = (int) get_option( 'page_on_front' );

	if ( preg_match_all( '/<a\s[^>]*href\s*=\s*([\'"])([^\'"]+)\1/i', (string) $html, $matches ) ) {
		foreach ( $matches[2] as $href ) {
			$page_id = stillframe_url_to_page_id( $href );
			if ( $page_id && $page_id !== $about_id && $page_id !== $home_id ) {
				return $page_id;
			}
		}
	}

	$title = trim( (string) $title );
	$slug  = sanitize_title( $title );
	if ( '' === $title ) {
		return 0;
	}

	if ( in_array( $slug, array( 'resume', 'cv' ), true ) ) {
		$resume = stillframe_get_section_page( 'resume' );
		if ( $resume instanceof WP_Post ) {
			return (int) $resume->ID;
		}
	}

	$bare_slug = sanitize_title( stillframe_about_timeline_normalize_title( $title ) );
	$paths     = array_values( array_unique( array_filter( array( $slug, $bare_slug ) ) ) );
	$about     = $about_id ? get_post( $about_id ) : null;
	if ( $about instanceof WP_Post && $about->post_name ) {
		foreach ( $paths as $path_slug ) {
			$paths[] = $about->post_name . '/' . $path_slug;
		}
		$paths = array_values( array_unique( $paths ) );
	}

	foreach ( $paths as $path ) {
		$by_path = get_page_by_path( $path );
		if ( $by_path instanceof WP_Post && (int) $by_path->ID !== $about_id && 'publish' === $by_path->post_status ) {
			return (int) $by_path->ID;
		}
	}

	foreach ( stillframe_about_related_page_ids( $about_id ) as $page_id ) {
		$page = get_post( $page_id );
		if ( ! $page instanceof WP_Post ) {
			continue;
		}

		$page_title = get_the_title( $page );
		if (
			strcasecmp( $page_title, $title ) === 0
			|| sanitize_title( $page_title ) === $slug
			|| sanitize_title( $page->post_name ) === $slug
			|| stillframe_about_timeline_titles_match( $page_title, $title )
			|| stillframe_about_timeline_titles_match( $page->post_name, $title )
		) {
			return (int) $page_id;
		}
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'title'                  => $title,
			'posts_per_page'         => 1,
			'post__not_in'           => array( $about_id ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( ! empty( $query->posts[0] ) && $query->posts[0] instanceof WP_Post ) {
		return (int) $query->posts[0]->ID;
	}

	return 0;
}

/**
 * Centered About timeline: intro copy plus linked heading events.
 *
 * @param int $post_id About page ID.
 * @return array{intro:string, events:array<int, array{id:string, title:string, html:string, url:string, side:string}>}
 */
function stillframe_about_timeline_data( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$empty   = array(
		'intro'  => '',
		'events' => array(),
	);

	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		$cache[ $post_id ] = $empty;
		return $empty;
	}

	$content = (string) $post->post_content;
	if ( '' === trim( $content ) ) {
		$cache[ $post_id ] = $empty;
		return $empty;
	}

	$parts = preg_split( '/(<h[2-4]\b[^>]*>.*?<\/h[2-4]>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) ) {
		$cache[ $post_id ] = $empty;
		return $empty;
	}

	$intro   = '';
	$pending = array();
	$current = null;
	$used    = array();

	foreach ( $parts as $part ) {
		if ( preg_match( '/<h([2-4])(\s[^>]*)?>(.*?)<\/h\1>/is', $part, $match ) ) {
			if ( is_array( $current ) ) {
				$pending[] = $current;
			}

			$title = trim( wp_strip_all_tags( $match[3] ) );
			$attrs = isset( $match[2] ) ? $match[2] : '';
			$id    = '';
			if ( preg_match( '/\sid\s*=\s*([\'"])([^\'"]+)\1/i', $attrs, $id_match ) ) {
				$id = sanitize_title( $id_match[2] );
			}
			if ( '' === $id ) {
				$id = sanitize_title( $title );
			}

			$base = $id;
			$n    = 2;
			while ( $id && isset( $used[ $id ] ) ) {
				$id = $base . '-' . $n;
				++$n;
			}
			if ( $id ) {
				$used[ $id ] = true;
			}

			$current = array(
				'id'      => $id,
				'title'   => $title,
				'html'    => '',
				'heading' => $part,
			);
			continue;
		}

		if ( is_array( $current ) ) {
			$current['html'] .= $part;
		} else {
			$intro .= $part;
		}
	}

	if ( is_array( $current ) ) {
		$pending[] = $current;
	}

	$events = array();
	$index  = 0;
	foreach ( $pending as $item ) {
		if ( '' === $item['title'] ) {
			continue;
		}

		$lookup  = ( isset( $item['heading'] ) ? $item['heading'] : '' ) . $item['html'];
		$page_id = stillframe_about_timeline_event_page_id( $lookup, $item['title'], $post_id );
		if ( ! $page_id ) {
			continue;
		}

		$url  = get_permalink( $page_id );
		$html = stillframe_about_timeline_plain_html( $item['html'] );
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			$html = stillframe_about_timeline_page_excerpt( $page_id );
		}
		if ( ! $url ) {
			continue;
		}

		$events[] = array(
			'id'      => $item['id'],
			'title'   => $item['title'],
			'html'    => $html,
			'url'     => $url,
			'page_id' => $page_id,
			'side'    => 0 === $index % 2 ? 'left' : 'right',
		);
		++$index;
	}

	$used_pages = array();
	foreach ( $events as $event ) {
		if ( ! empty( $event['page_id'] ) ) {
			$used_pages[] = (int) $event['page_id'];
		}
	}

	$missing_ids = stillframe_about_dropdown_page_ids( $post_id );
	if ( $missing_ids ) {
		$missing_ids = stillframe_order_ids_like_about_links( $missing_ids, $post_id );
	} elseif ( ! $events ) {
		$missing_ids = stillframe_about_content_page_ids( $post_id );
	} else {
		$missing_ids = array();
	}

	$link_rank = array_flip( stillframe_about_content_page_ids( $post_id ) );
	foreach ( $missing_ids as $page_id ) {
		$page_id = (int) $page_id;
		if ( ! $page_id || in_array( $page_id, $used_pages, true ) ) {
			continue;
		}

		$extra = stillframe_about_timeline_event_from_page( $page_id );
		if ( ! $extra ) {
			continue;
		}

		$base = $extra['id'];
		$n    = 2;
		while ( isset( $used[ $extra['id'] ] ) ) {
			$extra['id'] = $base . '-' . $n;
			++$n;
		}
		$used[ $extra['id'] ] = true;

		$insert_at = count( $events );
		if ( isset( $link_rank[ $page_id ] ) ) {
			$insert_at = 0;
			foreach ( $events as $i => $event ) {
				$eid = isset( $event['page_id'] ) ? (int) $event['page_id'] : 0;
				if ( isset( $link_rank[ $eid ] ) && $link_rank[ $eid ] < $link_rank[ $page_id ] ) {
					$insert_at = $i + 1;
				}
			}
		}

		array_splice( $events, $insert_at, 0, array( $extra ) );
		$used_pages[] = $page_id;
	}

	foreach ( $events as $i => $event ) {
		$events[ $i ]['side'] = 0 === $i % 2 ? 'left' : 'right';
	}

	$intro_html = stillframe_about_timeline_plain_html( $intro );
	$intro_out  = '';
	if ( '' !== trim( wp_strip_all_tags( $intro_html ) ) ) {
		$intro_out = apply_filters( 'the_content', $intro );
	}

	$result = array(
		'intro'  => $intro_out,
		'events' => $events,
	);
	$cache[ $post_id ] = $result;

	return $result;
}

/**
 * Whether a content fragment has anything besides an empty heading.
 *
 * @param string $html HTML.
 * @return bool
 */
function stillframe_section_has_body( $html ) {
	$html = preg_replace( '/<!--.*?-->/s', '', (string) $html );
	if ( preg_match( '/<(img|figure|iframe|video|audio|svg|object|embed|ul|ol|blockquote|table|hr|pre|canvas)\b/i', $html ) ) {
		return true;
	}

	$html = preg_replace( '/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', '', $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( '/\x{00A0}/u', ' ', $text );

	return '' !== trim( (string) $text );
}

/**
 * Wrap a content fragment in a lifted glass section.
 *
 * @param string $inner HTML.
 * @return string
 */
function stillframe_glass_lift_html( $inner ) {
	$inner = trim( (string) $inner );
	if ( '' === $inner || ! stillframe_section_has_body( $inner ) ) {
		return '';
	}

	return '<section class="glass-lift">' . $inner . '</section>';
}

/**
 * Heading id from a single heading tag.
 *
 * @param string $html Heading markup.
 * @return string
 */
function stillframe_heading_id_from_markup( $html ) {
	$html = (string) $html;
	if ( preg_match( '/\sid\s*=\s*([\'"])([^\'"]+)\1/i', $html, $match ) ) {
		return sanitize_title( $match[2] );
	}

	if ( preg_match( '/<h[1-6]\b[^>]*>(.*?)<\/h[1-6]>/is', $html, $match ) ) {
		return sanitize_title( wp_strip_all_tags( $match[1] ) );
	}

	return '';
}

/**
 * Pages and projects that wrap headings in glass lifts.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function stillframe_page_uses_section_wrap( $post_id ) {
	$post_id = (int) $post_id;
	if ( 'project' === get_post_type( $post_id ) ) {
		return true;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return false;
	}

	if ( stillframe_is_about_page( $post_id ) || stillframe_is_home_page( $post_id ) || stillframe_is_contact_page( $post_id ) ) {
		return false;
	}

	return stillframe_is_about_subpage( $post_id ) || stillframe_is_resume_page( $post_id );
}

/**
 * Heading range used when wrapping this post into lifts.
 *
 * @param int $post_id Post ID.
 * @return array{0:int,1:int}
 */
function stillframe_section_wrap_levels( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( 'project' === get_post_type( $post_id ) ) {
		return array( 3, 3 );
	}

	return array( 2, 3 );
}

/**
 * Saved heading ids that should stay in the previous lift.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function stillframe_joined_section_ids( $post_id ) {
	$raw = get_post_meta( (int) $post_id, 'stillframe_joined_sections', true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$ids = array();
	foreach ( $raw as $id ) {
		$id = sanitize_title( (string) $id );
		if ( '' !== $id ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Headings that start a new lift and can be joined to the one above.
 *
 * @param int $post_id    Post ID.
 * @param int $min_level  2–4.
 * @param int $max_level  2–4.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_joinable_headings( $post_id, $min_level = 2, $max_level = 3 ) {
	$post = get_post( (int) $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$content   = (string) $post->post_content;
	$min_level = max( 2, min( 4, (int) $min_level ) );
	$max_level = max( $min_level, min( 4, (int) $max_level ) );

	$split_level = 0;
	for ( $level = $min_level; $level <= $max_level; $level++ ) {
		if ( preg_match( '/<h' . $level . '\b/i', $content ) ) {
			$split_level = $level;
			break;
		}
	}

	if ( ! $split_level ) {
		return array();
	}

	$items = stillframe_content_headings( $content, $min_level, $max_level );
	if ( ! $items ) {
		$items = stillframe_content_headings( $content, 2, 4 );
	}

	$joinable = array();
	foreach ( $items as $item ) {
		if ( (int) $item['level'] === $split_level ) {
			$joinable[] = $item;
		}
	}

	return $joinable;
}

/**
 * Split HTML on top-level headings so each major section sits on its own panel.
 *
 * Nested headings (H3 under H2, and so on) stay inside the same lift.
 * Headings marked “join with previous” in the editor stay in the previous lift.
 *
 * @param string $html      Filtered post content.
 * @param int    $min_level 2–4.
 * @param int    $max_level 2–4.
 * @param int    $post_id   Post whose join list to use.
 * @return string
 */
function stillframe_wrap_content_sections( $html, $min_level = 2, $max_level = 3, $post_id = 0 ) {
	$html = trim( (string) $html );
	if ( '' === $html ) {
		return '';
	}

	$min_level = max( 2, min( 4, (int) $min_level ) );
	$max_level = max( $min_level, min( 4, (int) $max_level ) );
	$post_id   = $post_id ? (int) $post_id : (int) get_the_ID();
	$joined    = $post_id ? stillframe_joined_section_ids( $post_id ) : array();

	$split_level = 0;
	for ( $level = $min_level; $level <= $max_level; $level++ ) {
		if ( preg_match( '/<h' . $level . '\b/i', $html ) ) {
			$split_level = $level;
			break;
		}
	}

	if ( ! $split_level ) {
		return stillframe_glass_lift_html( $html );
	}

	$pattern = '/(<h' . $split_level . '\b[^>]*>.*?<\/h' . $split_level . '>)/is';
	$parts   = preg_split( $pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE );

	if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
		return stillframe_glass_lift_html( $html );
	}

	$out    = '';
	$buffer = '';

	foreach ( $parts as $part ) {
		if ( preg_match( '/^<h' . $split_level . '\b/i', $part ) ) {
			$id = stillframe_heading_id_from_markup( $part );
			if ( $id && in_array( $id, $joined, true ) && '' !== trim( $buffer ) ) {
				$buffer .= $part;
				continue;
			}

			$out   .= stillframe_glass_lift_html( $buffer );
			$buffer = $part;
			continue;
		}

		$buffer .= $part;
	}

	$out .= stillframe_glass_lift_html( $buffer );

	return $out ? $out : stillframe_glass_lift_html( $html );
}

/**
 * Jump links for the About table of contents.
 *
 * @param int $post_id Page ID.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_about_toc_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$post    = get_post( $post_id );
	$items   = array();

	if ( $post instanceof WP_Post ) {
		$items = stillframe_content_headings( (string) $post->post_content, 2, 3 );
		if ( ! $items ) {
			$items = stillframe_content_headings( (string) $post->post_content, 2, 4 );
		}
	}

	return $items;
}

/**
 * Jump links for the project table of contents. H3s only.
 *
 * @param int $post_id Project ID.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_project_toc_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	return stillframe_content_headings( (string) $post->post_content, 3, 3 );
}

/**
 * Give About / project headings ids so the contents list can jump to them.
 *
 * @param string $content Page content.
 * @return string
 */
function stillframe_about_heading_ids( $content ) {
	if ( is_admin() ) {
		return $content;
	}

	$levels = '';
	if ( is_singular( 'page' ) && ( stillframe_is_about_page() || stillframe_is_about_subpage() || stillframe_is_resume_page() ) ) {
		$levels = '2-4';
	} elseif ( is_singular( 'project' ) ) {
		$levels = '3';
	} else {
		return $content;
	}

	$used = array();

	return preg_replace_callback(
		'/<h([' . $levels . '])(\s[^>]*)?>(.*?)<\/h\1>/is',
		function ( $match ) use ( &$used ) {
			$attrs = isset( $match[2] ) ? $match[2] : '';
			if ( preg_match( '/\sid\s*=/', $attrs ) ) {
				return $match[0];
			}

			$id = sanitize_title( wp_strip_all_tags( $match[3] ) );
			if ( '' === $id ) {
				return $match[0];
			}

			$base = $id;
			$n    = 2;
			while ( isset( $used[ $id ] ) ) {
				$id = $base . '-' . $n;
				++$n;
			}
			$used[ $id ] = true;

			return '<h' . $match[1] . $attrs . ' id="' . esc_attr( $id ) . '">' . $match[3] . '</h' . $match[1] . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'stillframe_about_heading_ids', 12 );
