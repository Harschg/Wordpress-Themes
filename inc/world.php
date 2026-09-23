<?php
/**
 * Page-world backgrounds and banner meta.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


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
 * Page background image.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_banner_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_banner', 'stillframe_banner_nonce' );

	$banner_id = (int) get_post_meta( $post->ID, 'stillframe_banner_id', true );
	$thumb     = $banner_id ? wp_get_attachment_image(
		$banner_id,
		'medium',
		false,
		array(
			'style' => 'display:block;max-width:100%;height:auto;margin:0 0 8px;',
		)
	) : '';
	?>
	<p>
		<?php
		if ( 'project' === $post->post_type ) {
			esc_html_e( 'Optional. A different full-screen photo behind this project. Leave it empty to use the featured image. Choosing a file saves it right away.', 'stillframe' );
		} elseif ( stillframe_is_about_page( (int) $post->ID ) ) {
			esc_html_e( 'Full-screen photo behind About. Featured image stays the portrait beside your bio. Choosing a file saves it right away.', 'stillframe' );
		} elseif ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
			esc_html_e( 'Full-screen photo behind Home. Choosing a file saves it right away.', 'stillframe' );
		} elseif ( stillframe_is_contact_page( (int) $post->ID ) ) {
			esc_html_e( 'Full-screen photo behind Contact. Choosing a file saves it right away.', 'stillframe' );
		} elseif ( 'gallery' === $post->post_name || 0 === strpos( (string) $post->post_name, 'gallery-' ) ) {
			esc_html_e( 'Full-screen photo behind Gallery. Choosing a file saves it right away.', 'stillframe' );
		} elseif ( 'projects' === $post->post_name || 0 === strpos( (string) $post->post_name, 'projects-' ) ) {
			esc_html_e( 'Full-screen photo behind Projects. Choosing a file saves it right away.', 'stillframe' );
		} else {
			esc_html_e( 'Full-screen photo behind this page. Choosing a file saves it right away.', 'stillframe' );
		}
		?>
	</p>
	<input type="hidden" id="stillframe_banner_id" name="stillframe_banner_id" value="<?php echo esc_attr( (string) $banner_id ); ?>" />
	<div data-banner-preview><?php echo $thumb ? $thumb : ''; ?></div>
	<p data-banner-status class="description"></p>
	<p>
		<button type="button" class="button" data-banner-upload><?php esc_html_e( 'Choose image', 'stillframe' ); ?></button>
		<button type="button" class="button" data-banner-remove <?php echo $banner_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'stillframe' ); ?></button>
	</p>
	<?php
}

/**
 * Save banner attachment ID.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_banner_meta( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! in_array( get_post_type( $post_id ), array( 'page', 'project' ), true ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_banner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_banner_nonce'] ) ), 'stillframe_save_banner' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_banner_id'] ) ) {
		return;
	}

	$banner_id = absint( wp_unslash( $_POST['stillframe_banner_id'] ) );

	if ( $banner_id && ! wp_attachment_is_image( $banner_id ) ) {
		$banner_id = 0;
	}

	if ( $banner_id ) {
		update_post_meta( $post_id, 'stillframe_banner_id', $banner_id );
	} else {
		delete_post_meta( $post_id, 'stillframe_banner_id' );
	}
}
add_action( 'save_post', 'stillframe_save_banner_meta' );

/**
 * Save banner as soon as an image is chosen.
 */
function stillframe_ajax_save_banner() {
	check_ajax_referer( 'stillframe_save_banner', 'nonce' );

	$post_id   = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$banner_id = isset( $_POST['banner_id'] ) ? absint( wp_unslash( $_POST['banner_id'] ) ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error();
	}

	if ( ! in_array( get_post_type( $post_id ), array( 'page', 'project' ), true ) ) {
		wp_send_json_error();
	}

	if ( $banner_id && ! wp_attachment_is_image( $banner_id ) ) {
		$banner_id = 0;
	}

	if ( $banner_id ) {
		update_post_meta( $post_id, 'stillframe_banner_id', $banner_id );
	} else {
		delete_post_meta( $post_id, 'stillframe_banner_id' );
	}

	wp_send_json_success(
		array(
			'url'   => $banner_id ? wp_get_attachment_image_url( $banner_id, 'full' ) : '',
			'thumb' => $banner_id ? wp_get_attachment_image(
				$banner_id,
				'medium',
				false,
				array(
					'style' => 'display:block;max-width:100%;height:auto;margin:0 0 8px;',
				)
			) : '',
		)
	);
}
add_action( 'wp_ajax_stillframe_save_banner', 'stillframe_ajax_save_banner' );
/**
 * Register the background image meta box.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_banner_meta_box( $post_type, $post ) {
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( 'page' !== $post_type && 'project' !== $post_type ) {
		return;
	}

	add_meta_box(
		'stillframe_page_banner',
		__( 'Background image', 'stillframe' ),
		'stillframe_render_banner_meta_box',
		$post_type,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_banner_meta_box', 10, 2 );

/**
 * Media picker for the banner meta box.
 *
 * @param string $hook Current admin page.
 */
function stillframe_banner_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'page', 'project' ), true ) ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) {
		$post_id = absint( wp_unslash( $_GET['post'] ) );
	} elseif ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
		$post_id = (int) $GLOBALS['post']->ID;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'stillframe-admin-banner',
		get_template_directory_uri() . '/assets/js/admin-banner.js',
		array( 'jquery' ),
		STILLFRAME_VERSION,
		true
	);

	wp_localize_script(
		'stillframe-admin-banner',
		'stillframeBanner',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'stillframe_save_banner' ),
			'postId'  => $post_id,
		)
	);
}
add_action( 'admin_enqueue_scripts', 'stillframe_banner_admin_assets' );
