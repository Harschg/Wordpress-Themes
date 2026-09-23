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
	add_image_size( 'stillframe-world', 2560, 1707, false );

	register_nav_menus( array(
		'primary' => __( 'Primary', 'stillframe' ),
	) );
}
add_action( 'after_setup_theme', 'stillframe_setup' );

/**
 * Per-image motion choices for About subpage photos.
 *
 * In the editor, select an image and use Styles: Tilt in, Slide in, or Fade in.
 * Images with no style keep the tilt-in motion.
 */
function stillframe_register_image_motion_styles() {
	$styles = array(
		array(
			'name'  => 'tilt-in',
			'label' => __( 'Tilt in', 'stillframe' ),
		),
		array(
			'name'  => 'slide-in',
			'label' => __( 'Slide in', 'stillframe' ),
		),
		array(
			'name'  => 'fade-in',
			'label' => __( 'Fade in', 'stillframe' ),
		),
	);

	foreach ( array( 'core/image', 'core/media-text' ) as $block ) {
		foreach ( $styles as $style ) {
			register_block_style( $block, $style );
		}
	}
}
add_action( 'init', 'stillframe_register_image_motion_styles' );

/**
 * Keep more pixels on large background uploads.
 *
 * @return int
 */
function stillframe_big_image_threshold() {
	return 3840;
}
add_filter( 'big_image_size_threshold', 'stillframe_big_image_threshold' );

/**
 * Sharper JPEGs for generated sizes.
 *
 * @param int $quality Current quality.
 * @return int
 */
function stillframe_jpeg_quality( $quality ) {
	return max( (int) $quality, 90 );
}
add_filter( 'jpeg_quality', 'stillframe_jpeg_quality' );
add_filter( 'wp_editor_set_quality', 'stillframe_jpeg_quality' );

/**
 * Do not emit srcset on the front end.
 *
 * WordPress lists every registered size. Missing stillframe-gallery
 * files 404 and the browser shows a grey box instead of the photo. Theme img tags
 * pick a single existing file instead.
 *
 * @param array|false $sources Srcset candidates.
 * @return array|false
 */
function stillframe_front_srcset( $sources ) {
	if ( is_admin() ) {
		return $sources;
	}

	return false;
}
add_filter( 'wp_calculate_image_srcset', 'stillframe_front_srcset' );

/**
 * Strip srcset/sizes WordPress adds to img tags in content.
 *
 * @param array $attr Image attributes.
 * @return array
 */
function stillframe_front_image_attributes( $attr ) {
	if ( is_admin() ) {
		return $attr;
	}

	unset( $attr['srcset'], $attr['sizes'] );

	if ( empty( $attr['loading'] ) ) {
		$attr['loading'] = 'eager';
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'stillframe_front_image_attributes' );

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

	stillframe_enqueue_resume_pdf();
}
add_action( 'wp_enqueue_scripts', 'stillframe_enqueue_assets' );

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
		'resume'   => __( 'Resume', 'stillframe' ),
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
 * Move the uploaded resume off About and onto the Resume page, once.
 */
function stillframe_maybe_move_resume_to_resume_page() {
	if ( '1.0.85' === get_option( 'stillframe_resume_on_resume_page' ) ) {
		return;
	}

	if ( current_user_can( 'publish_pages' ) ) {
		stillframe_ensure_section_pages();
	}

	$resume_page = stillframe_get_section_page( 'resume' );
	$about       = stillframe_get_section_page( 'about' );
	$from_about  = ( $about instanceof WP_Post ) ? (int) get_post_meta( $about->ID, 'stillframe_resume_id', true ) : 0;
	$from_mod    = (int) get_theme_mod( 'stillframe_resume_id', 0 );
	$source      = $from_about ? $from_about : $from_mod;

	if ( $resume_page instanceof WP_Post && $source && ! get_post_meta( $resume_page->ID, 'stillframe_resume_id', true ) ) {
		update_post_meta( $resume_page->ID, 'stillframe_resume_id', $source );
	}

	if ( $resume_page instanceof WP_Post || ! $source ) {
		update_option( 'stillframe_resume_on_resume_page', '1.0.85' );
	}
}
add_action( 'init', 'stillframe_maybe_move_resume_to_resume_page' );

/**
 * Always use the About / Contact templates when those pages are detected.
 */
function stillframe_template_include( $template ) {
	if ( ! is_singular( 'page' ) ) {
		return $template;
	}

	$page_id = (int) get_queried_object_id();

	if ( stillframe_is_resume_page( $page_id ) ) {
		$found = locate_template( 'page-resume.php' );
		return $found ? $found : $template;
	}

	if ( stillframe_is_about_page( $page_id ) ) {
		$found = locate_template( 'page-about.php' );
		return $found ? $found : $template;
	}

	if ( stillframe_is_about_subpage( $page_id ) ) {
		$found = locate_template( 'page-about-sub.php' );
		return $found ? $found : $template;
	}

	if ( stillframe_is_contact_page( $page_id ) ) {
		$found = locate_template( 'page-contact.php' );
		return $found ? $found : $template;
	}

	return $template;
}
add_filter( 'template_include', 'stillframe_template_include' );
