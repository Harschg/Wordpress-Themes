<?php
/**
 * Theme supports, menus, and asset loading.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports and menus.
 */
function stillframe_setup() {
	load_theme_textdomain( 'stillframe', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
		'navigation-widgets',
	) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );

	add_image_size( 'stillframe-gallery', 900, 1200, false );
	add_image_size( 'stillframe-hero', 1920, 1280, false );
	add_image_size( 'stillframe-card', 800, 600, true );

	register_nav_menus( array(
		'primary' => __( 'Primary', 'stillframe' ),
	) );
}
add_action( 'after_setup_theme', 'stillframe_setup' );

/**
 * Enqueue fonts, CSS, and motion scripts.
 */
function stillframe_enqueue_assets() {
	wp_enqueue_style(
		'stillframe-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,500;9..144,700&family=Outfit:wght@300;400;500&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'stillframe-style',
		get_stylesheet_uri(),
		array(),
		STILLFRAME_VERSION
	);

	wp_enqueue_style(
		'stillframe-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array( 'stillframe-fonts', 'stillframe-style' ),
		STILLFRAME_VERSION
	);

	wp_enqueue_script(
		'stillframe-theme',
		get_template_directory_uri() . '/assets/js/theme.js',
		array(),
		STILLFRAME_VERSION,
		true
	);

	wp_localize_script(
		'stillframe-theme',
		'stillframeTheme',
		array(
			'homeUrl' => home_url( '/' ),
		)
	);

	stillframe_enqueue_resume_pdf();
}
add_action( 'wp_enqueue_scripts', 'stillframe_enqueue_assets' );

/**
 * PDF.js for the on-page resume (avoids the browser's dark PDF viewer).
 */
function stillframe_enqueue_resume_pdf() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	$attachment_id = stillframe_resume_attachment_id( get_queried_object_id() );
	if ( ! $attachment_id ) {
		return;
	}

	$mime = (string) get_post_mime_type( $attachment_id );
	$file = (string) get_attached_file( $attachment_id );
	if ( false === strpos( $mime, 'pdf' ) && ! preg_match( '/\.pdf$/i', $file ) ) {
		return;
	}

	$pdfjs = get_template_directory_uri() . '/assets/js/pdfjs';

	wp_enqueue_script(
		'pdfjs',
		$pdfjs . '/pdf.min.js',
		array(),
		'3.11.174',
		true
	);

	wp_enqueue_script(
		'stillframe-resume-pdf',
		get_template_directory_uri() . '/assets/js/resume-pdf.js',
		array( 'pdfjs' ),
		STILLFRAME_VERSION,
		true
	);

	$projects = array();
	foreach (
		get_posts(
			array(
				'post_type'      => 'project',
				'post_status'    => 'publish',
				'posts_per_page' => 30,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		) as $project
	) {
		if ( ! $project instanceof WP_Post ) {
			continue;
		}

		$projects[] = array(
			'label' => $project->post_title,
			'url'   => get_permalink( $project ),
		);
	}

	wp_localize_script(
		'stillframe-resume-pdf',
		'stillframeResumePdf',
		array(
			'workerSrc' => $pdfjs . '/pdf.worker.min.js',
			'about'     => stillframe_page_url( 'about' ),
			'projects'  => get_post_type_archive_link( 'project' ),
			'gallery'   => get_post_type_archive_link( 'photograph' ),
			'contact'   => stillframe_page_url( 'contact' ),
			'github'    => stillframe_contact_setting( 'stillframe_github' ),
			'linkedin'  => stillframe_contact_setting( 'stillframe_linkedin', 'https://www.linkedin.com/in/grant-harsch' ),
			'projectItems' => $projects,
		)
	);
}

/**
 * Preconnect to Google Fonts.
 *
 * @param array  $urls          URLs to print.
 * @param string $relation_type Relation type.
 * @return array
 */
function stillframe_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href'        => 'https://fonts.googleapis.com',
			'crossorigin' => false,
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'stillframe_resource_hints', 10, 2 );

/**
 * Flush rewrite rules once after the theme is activated.
 */
function stillframe_after_switch_theme() {
	stillframe_register_post_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'stillframe_after_switch_theme' );

/**
 * Create Gallery / Projects pages so every banner lives in the page editor.
 */
function stillframe_ensure_section_pages() {
	if ( ! current_user_can( 'publish_pages' ) ) {
		return;
	}

	$created = false;
	$pages   = array(
		'gallery'  => __( 'Gallery', 'stillframe' ),
		'projects' => __( 'Projects', 'stillframe' ),
	);

	foreach ( $pages as $slug => $title ) {
		if ( stillframe_get_section_page( $slug ) instanceof WP_Post ) {
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_type'   => 'page',
				'post_content' => '',
			),
			true
		);

		if ( ! is_wp_error( $page_id ) && $page_id ) {
			$created = true;
		}
	}

	if ( $created ) {
		flush_rewrite_rules( false );
	}
}

/**
 * Move Customizer banner picks onto the matching pages, once.
 */
function stillframe_migrate_hero_mods_to_pages() {
	$sections = array( 'home', 'about', 'gallery', 'projects', 'contact' );

	foreach ( $sections as $section ) {
		$mod_id  = (int) get_theme_mod( 'stillframe_hero_' . $section, 0 );
		$page_id = stillframe_section_banner_page_id( $section );

		if ( ! $mod_id || ! $page_id ) {
			continue;
		}

		if ( (int) get_post_meta( $page_id, 'stillframe_banner_id', true ) ) {
			continue;
		}

		update_post_meta( $page_id, 'stillframe_banner_id', $mod_id );
	}
}

/**
 * Move Customizer page settings onto the matching pages, once.
 */
function stillframe_migrate_customizer_to_pages() {
	$home_id = (int) get_option( 'page_on_front' );

	if ( $home_id ) {
		$subtitle = (string) get_theme_mod( 'stillframe_vibe_line', '' );
		if ( $subtitle && ! get_post_meta( $home_id, 'stillframe_subtitle', true ) ) {
			update_post_meta( $home_id, 'stillframe_subtitle', $subtitle );
		}

		$intro = trim( (string) get_theme_mod( 'stillframe_home_intro', '' ) );
		$home  = get_post( $home_id );
		if ( $intro && $home instanceof WP_Post && '' === trim( wp_strip_all_tags( (string) $home->post_content ) ) ) {
			wp_update_post(
				array(
					'ID'           => $home_id,
					'post_content' => $intro,
				)
			);
		}
	}

	$contact = stillframe_get_section_page( 'contact' );
	if ( $contact instanceof WP_Post ) {
		$fields = array(
			'stillframe_contact_email' => get_theme_mod( 'stillframe_contact_email', '' ),
			'stillframe_linkedin'      => get_theme_mod( 'stillframe_linkedin', 'https://www.linkedin.com/in/grant-harsch' ),
			'stillframe_instagram'     => get_theme_mod( 'stillframe_instagram', '' ),
			'stillframe_github'        => get_theme_mod( 'stillframe_github', '' ),
		);

		foreach ( $fields as $key => $value ) {
			if ( ! $value || get_post_meta( $contact->ID, $key, true ) ) {
				continue;
			}

			update_post_meta( $contact->ID, $key, $value );
		}
	}

	$about  = stillframe_get_section_page( 'about' );
	$resume = (int) get_theme_mod( 'stillframe_resume_id', 0 );
	if ( $about instanceof WP_Post && $resume && ! get_post_meta( $about->ID, 'stillframe_resume_id', true ) ) {
		update_post_meta( $about->ID, 'stillframe_resume_id', $resume );
	}
}

/**
 * One-time page setup after this theme is already running.
 */
function stillframe_maybe_setup_banner_pages() {
	if ( '1.0.20' === get_option( 'stillframe_banner_pages' ) ) {
		return;
	}

	stillframe_ensure_section_pages();
	stillframe_migrate_hero_mods_to_pages();
	update_option( 'stillframe_banner_pages', '1.0.20' );
}
add_action( 'admin_init', 'stillframe_maybe_setup_banner_pages' );

/**
 * One-time copy of Customizer page fields onto Home / About / Contact.
 */
function stillframe_maybe_migrate_page_settings() {
	if ( '1.0.21' === get_option( 'stillframe_page_settings' ) ) {
		return;
	}

	stillframe_ensure_section_pages();
	stillframe_migrate_hero_mods_to_pages();
	stillframe_migrate_customizer_to_pages();
	update_option( 'stillframe_page_settings', '1.0.21' );
}
add_action( 'admin_init', 'stillframe_maybe_migrate_page_settings' );

/**
 * Always use the About / Contact templates when those pages are detected.
 */
function stillframe_template_include( $template ) {
	if ( ! is_singular( 'page' ) ) {
		return $template;
	}

	$page_id = (int) get_queried_object_id();

	if ( stillframe_is_about_page( $page_id ) ) {
		$found = locate_template( 'page-about.php' );
		return $found ? $found : $template;
	}

	if ( stillframe_is_contact_page( $page_id ) ) {
		$found = locate_template( 'page-contact.php' );
		return $found ? $found : $template;
	}

	return $template;
}
add_filter( 'template_include', 'stillframe_template_include' );
