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
	$page = get_page_by_path( $slug );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return home_url( '/' . trim( $slug, '/' ) . '/' );
}

/**
 * Fallback menu when no menu is assigned in Appearance → Menus.
 */
function stillframe_fallback_menu() {
	$items = array(
		array(
			'url'   => home_url( '/' ),
			'label' => __( 'Home', 'stillframe' ),
		),
		array(
			'url'   => stillframe_page_url( 'about' ),
			'label' => __( 'About', 'stillframe' ),
		),
		array(
			'url'   => get_post_type_archive_link( 'photograph' ),
			'label' => __( 'Gallery', 'stillframe' ),
		),
		array(
			'url'   => get_post_type_archive_link( 'project' ),
			'label' => __( 'Projects', 'stillframe' ),
		),
		array(
			'url'   => stillframe_page_url( 'contact' ),
			'label' => __( 'Contact', 'stillframe' ),
		),
	);

	echo '<ul class="nav-list">';

	foreach ( $items as $item ) {
		if ( empty( $item['url'] ) ) {
			continue;
		}

		printf(
			'<li><a class="nav-link" href="%1$s">%2$s</a></li>',
			esc_url( $item['url'] ),
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
 * Recent photographs in a series, for card previews.
 *
 * @param int $term_id Series term ID.
 * @param int $count   How many to fetch.
 * @return WP_Post[]
 */
function stillframe_series_preview_photos( $term_id, $count = 3 ) {
	$posts = get_posts(
		array(
			'post_type'      => 'photograph',
			'posts_per_page' => (int) $count,
			'orderby'        => 'title',
			'order'          => 'ASC',
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

	return $posts;
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

	$slug = get_post_field( 'post_name', $page_id );
	if ( 'about' === $slug ) {
		return true;
	}

	return 'template-about.php' === get_page_template_slug( $page_id );
}

/**
 * Attachment ID for the uploaded resume.
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return int
 */
function stillframe_resume_attachment_id( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	$ids     = array();

	if ( $page_id ) {
		$ids[] = (int) get_post_meta( $page_id, 'stillframe_resume_id', true );
	}

	if ( stillframe_is_about_page( $page_id ) ) {
		$about = get_page_by_path( 'about' );
		if ( $about instanceof WP_Post ) {
			$ids[] = (int) get_post_meta( $about->ID, 'stillframe_resume_id', true );
		}

		$ids[] = (int) get_theme_mod( 'stillframe_resume_id', 0 );
	}

	$ids = array_unique( array_filter( $ids ) );

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

	if ( is_front_page() ) {
		$classes[] = 'vibe-home';
	} elseif ( is_page( 'about' ) || is_page_template( 'template-about.php' ) ) {
		$classes[] = 'vibe-about';
	} elseif ( is_page( 'contact' ) || is_page_template( 'template-contact.php' ) ) {
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
