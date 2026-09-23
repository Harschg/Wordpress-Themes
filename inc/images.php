<?php
/**
 * Attachment and card image helpers.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


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
