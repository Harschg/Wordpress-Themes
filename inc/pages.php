<?php
/**
 * Page detectors and section page URLs.
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
