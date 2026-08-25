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

	wp_localize_script(
		'stillframe-resume-pdf',
		'stillframeResumePdf',
		array(
			'workerSrc' => $pdfjs . '/pdf.worker.min.js',
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
