<?php
/**
 * Native meta boxes so photographs and projects can store extra fields
 * without a plugin.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register meta boxes.
 */
function stillframe_add_meta_boxes( $post_type, $post ) {
	add_meta_box(
		'stillframe_photograph_details',
		__( 'Photograph details', 'stillframe' ),
		'stillframe_render_photograph_meta_box',
		'photograph',
		'side',
		'high'
	);

	add_meta_box(
		'stillframe_project_details',
		__( 'Project details', 'stillframe' ),
		'stillframe_render_project_meta_box',
		'project',
		'side',
		'high'
	);

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( 'page' === $post_type || 'project' === $post_type ) {
		add_meta_box(
			'stillframe_page_banner',
			__( 'Background image', 'stillframe' ),
			'stillframe_render_banner_meta_box',
			$post_type,
			'side',
			'high'
		);
	}

	if ( 'page' !== $post_type ) {
		return;
	}

	if ( stillframe_is_home_page( $post->ID ) ) {
		add_meta_box(
			'stillframe_home_details',
			__( 'Home', 'stillframe' ),
			'stillframe_render_home_meta_box',
			'page',
			'side',
			'high'
		);
	}

	if ( stillframe_is_about_page( $post->ID ) ) {
		add_meta_box(
			'stillframe_resume',
			__( 'Resume', 'stillframe' ),
			'stillframe_render_resume_meta_box',
			'page',
			'side',
			'high'
		);
	}

	if ( stillframe_is_contact_page( $post->ID ) ) {
		add_meta_box(
			'stillframe_contact_details',
			__( 'Contact', 'stillframe' ),
			'stillframe_render_contact_meta_box',
			'page',
			'side',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'stillframe_add_meta_boxes', 10, 2 );

/**
 * Photograph meta box markup.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_photograph_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_photograph_meta', 'stillframe_photograph_nonce' );

	$location = get_post_meta( $post->ID, 'stillframe_location', true );
	$camera   = get_post_meta( $post->ID, 'stillframe_camera', true );
	$year     = get_post_meta( $post->ID, 'stillframe_year', true );
	?>
	<p>
		<label for="stillframe_location"><?php esc_html_e( 'Location', 'stillframe' ); ?></label>
		<input type="text" class="widefat" id="stillframe_location" name="stillframe_location" value="<?php echo esc_attr( $location ); ?>" />
	</p>
	<p>
		<label for="stillframe_camera"><?php esc_html_e( 'Camera', 'stillframe' ); ?></label>
		<input type="text" class="widefat" id="stillframe_camera" name="stillframe_camera" value="<?php echo esc_attr( $camera ); ?>" />
	</p>
	<p>
		<label for="stillframe_year"><?php esc_html_e( 'Year', 'stillframe' ); ?></label>
		<input type="text" class="widefat" id="stillframe_year" name="stillframe_year" value="<?php echo esc_attr( $year ); ?>" />
	</p>
	<?php
}

/**
 * Project meta box markup.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_project_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_project_meta', 'stillframe_project_nonce' );

	$stack  = get_post_meta( $post->ID, 'stillframe_stack', true );
	$github = get_post_meta( $post->ID, 'stillframe_github', true );
	$live   = get_post_meta( $post->ID, 'stillframe_live_url', true );
	?>
	<p><?php esc_html_e( 'Featured image is the card picture. Upload a background image if you want a different photo behind the page.', 'stillframe' ); ?></p>
	<p>
		<label for="stillframe_stack"><?php esc_html_e( 'Stack (comma separated)', 'stillframe' ); ?></label>
		<input type="text" class="widefat" id="stillframe_stack" name="stillframe_stack" value="<?php echo esc_attr( $stack ); ?>" placeholder="PHP, WordPress, CSS" />
	</p>
	<p>
		<label for="stillframe_github"><?php esc_html_e( 'GitHub URL', 'stillframe' ); ?></label>
		<input type="url" class="widefat" id="stillframe_github" name="stillframe_github" value="<?php echo esc_attr( $github ); ?>" />
	</p>
	<p>
		<label for="stillframe_live_url"><?php esc_html_e( 'Live URL', 'stillframe' ); ?></label>
		<input type="url" class="widefat" id="stillframe_live_url" name="stillframe_live_url" value="<?php echo esc_attr( $live ); ?>" />
	</p>
	<?php
}

/**
 * Save photograph meta.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_photograph_meta( $post_id ) {
	if ( ! isset( $_POST['stillframe_photograph_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_photograph_nonce'] ) ), 'stillframe_save_photograph_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array( 'stillframe_location', 'stillframe_camera', 'stillframe_year' );

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
}
add_action( 'save_post_photograph', 'stillframe_save_photograph_meta' );

/**
 * Save project meta.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_project_meta( $post_id ) {
	if ( ! isset( $_POST['stillframe_project_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_project_nonce'] ) ), 'stillframe_save_project_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['stillframe_stack'] ) ) {
		update_post_meta( $post_id, 'stillframe_stack', sanitize_text_field( wp_unslash( $_POST['stillframe_stack'] ) ) );
	}

	foreach ( array( 'stillframe_github', 'stillframe_live_url' ) as $url_field ) {
		if ( isset( $_POST[ $url_field ] ) ) {
			update_post_meta( $post_id, $url_field, esc_url_raw( wp_unslash( $_POST[ $url_field ] ) ) );
		}
	}
}
add_action( 'save_post_project', 'stillframe_save_project_meta' );

/**
 * Resume upload on pages (used on About).
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_resume_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_resume', 'stillframe_resume_nonce' );

	$resume_id = (int) get_post_meta( $post->ID, 'stillframe_resume_id', true );
	$file      = $resume_id ? get_post( $resume_id ) : null;
	$filename  = ( $file instanceof WP_Post ) ? $file->post_title : '';
	?>
	<p><?php esc_html_e( 'PDF or a photo of the resume. It displays on the About page. Choosing a file saves it right away.', 'stillframe' ); ?></p>
	<input type="hidden" id="stillframe_resume_id" name="stillframe_resume_id" value="<?php echo esc_attr( (string) $resume_id ); ?>" />
	<p data-resume-filename><?php echo $filename ? esc_html( $filename ) : esc_html__( 'No file yet.', 'stillframe' ); ?></p>
	<p data-resume-status class="description"></p>
	<p>
		<button type="button" class="button" data-resume-upload><?php esc_html_e( 'Choose file', 'stillframe' ); ?></button>
		<button type="button" class="button" data-resume-remove <?php echo $resume_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'stillframe' ); ?></button>
	</p>
	<?php
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
			esc_html_e( 'Full-screen photo behind this project. If you skip this, the featured image is used. Choosing a file saves it right away.', 'stillframe' );
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
 * Home subtitle. The description is the page content.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_home_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_home', 'stillframe_home_nonce' );

	$subtitle = get_post_meta( $post->ID, 'stillframe_subtitle', true );
	?>
	<p><?php esc_html_e( 'Write the site description in the page editor. It shows on the left, beside the page buttons.', 'stillframe' ); ?></p>
	<p>
		<label for="stillframe_subtitle"><?php esc_html_e( 'Subtitle', 'stillframe' ); ?></label>
		<input type="text" class="widefat" id="stillframe_subtitle" name="stillframe_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" />
	</p>
	<?php
}

/**
 * Contact email and profile links.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_contact_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_contact', 'stillframe_contact_page_nonce' );

	$email     = get_post_meta( $post->ID, 'stillframe_contact_email', true );
	$linkedin  = get_post_meta( $post->ID, 'stillframe_linkedin', true );
	$instagram = get_post_meta( $post->ID, 'stillframe_instagram', true );
	$github    = get_post_meta( $post->ID, 'stillframe_github', true );
	?>
	<p><?php esc_html_e( 'Used on the Contact page and for form messages. LinkedIn also appears in the footer.', 'stillframe' ); ?></p>
	<p>
		<label for="stillframe_contact_email"><?php esc_html_e( 'Email', 'stillframe' ); ?></label>
		<input type="email" class="widefat" id="stillframe_contact_email" name="stillframe_contact_email" value="<?php echo esc_attr( $email ); ?>" />
	</p>
	<p>
		<label for="stillframe_linkedin"><?php esc_html_e( 'LinkedIn URL', 'stillframe' ); ?></label>
		<input type="url" class="widefat" id="stillframe_linkedin" name="stillframe_linkedin" value="<?php echo esc_attr( $linkedin ); ?>" />
	</p>
	<p>
		<label for="stillframe_instagram"><?php esc_html_e( 'Instagram URL', 'stillframe' ); ?></label>
		<input type="url" class="widefat" id="stillframe_instagram" name="stillframe_instagram" value="<?php echo esc_attr( $instagram ); ?>" />
	</p>
	<p>
		<label for="stillframe_github"><?php esc_html_e( 'GitHub URL', 'stillframe' ); ?></label>
		<input type="url" class="widefat" id="stillframe_github" name="stillframe_github" value="<?php echo esc_attr( $github ); ?>" />
	</p>
	<?php
}

/**
 * Save resume attachment ID.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_resume_meta( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_resume_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_resume_nonce'] ) ), 'stillframe_save_resume' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_resume_id'] ) ) {
		return;
	}

	$resume_id = absint( wp_unslash( $_POST['stillframe_resume_id'] ) );

	if ( $resume_id && ! stillframe_is_resume_file( $resume_id ) ) {
		$resume_id = 0;
	}

	if ( $resume_id ) {
		update_post_meta( $post_id, 'stillframe_resume_id', $resume_id );
	} else {
		delete_post_meta( $post_id, 'stillframe_resume_id' );
	}
}
add_action( 'save_post', 'stillframe_save_resume_meta' );

/**
 * Save resume as soon as a file is chosen (block editor often skips classic meta boxes).
 */
function stillframe_ajax_save_resume() {
	check_ajax_referer( 'stillframe_save_resume', 'nonce' );

	$post_id   = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$resume_id = isset( $_POST['resume_id'] ) ? absint( wp_unslash( $_POST['resume_id'] ) ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error();
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		wp_send_json_error();
	}

	if ( $resume_id && ! stillframe_is_resume_file( $resume_id ) ) {
		$resume_id = 0;
	}

	if ( $resume_id ) {
		update_post_meta( $post_id, 'stillframe_resume_id', $resume_id );
	} else {
		delete_post_meta( $post_id, 'stillframe_resume_id' );
	}

	wp_send_json_success(
		array(
			'url' => $resume_id ? wp_get_attachment_url( $resume_id ) : '',
		)
	);
}
add_action( 'wp_ajax_stillframe_save_resume', 'stillframe_ajax_save_resume' );

/**
 * Media picker for the resume meta box.
 *
 * @param string $hook Current admin page.
 */
function stillframe_resume_admin_assets( $hook ) {
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

	if ( 'page' === $screen->post_type ) {
		wp_enqueue_script(
			'stillframe-admin-resume',
			get_template_directory_uri() . '/assets/js/admin-resume.js',
			array( 'jquery' ),
			STILLFRAME_VERSION,
			true
		);

		wp_localize_script(
			'stillframe-admin-resume',
			'stillframeResume',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'stillframe_save_resume' ),
				'postId'  => $post_id,
			)
		);
	}

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
add_action( 'admin_enqueue_scripts', 'stillframe_resume_admin_assets' );

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
 * Save Home subtitle.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_home_meta( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_home_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_home_nonce'] ) ), 'stillframe_save_home' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_subtitle'] ) ) {
		return;
	}

	$subtitle = sanitize_text_field( wp_unslash( $_POST['stillframe_subtitle'] ) );

	if ( '' === $subtitle ) {
		delete_post_meta( $post_id, 'stillframe_subtitle' );
	} else {
		update_post_meta( $post_id, 'stillframe_subtitle', $subtitle );
	}
}
add_action( 'save_post', 'stillframe_save_home_meta' );

/**
 * Save Contact page details.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_contact_page_meta( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_contact_page_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_contact_page_nonce'] ) ), 'stillframe_save_contact' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['stillframe_contact_email'] ) ) {
		$email = sanitize_email( wp_unslash( $_POST['stillframe_contact_email'] ) );
		if ( $email ) {
			update_post_meta( $post_id, 'stillframe_contact_email', $email );
		} else {
			delete_post_meta( $post_id, 'stillframe_contact_email' );
		}
	}

	foreach ( array( 'stillframe_linkedin', 'stillframe_instagram', 'stillframe_github' ) as $url_field ) {
		if ( ! isset( $_POST[ $url_field ] ) ) {
			continue;
		}

		$url = esc_url_raw( wp_unslash( $_POST[ $url_field ] ) );
		if ( $url ) {
			update_post_meta( $post_id, $url_field, $url );
		} else {
			delete_post_meta( $post_id, $url_field );
		}
	}
}
add_action( 'save_post', 'stillframe_save_contact_page_meta' );
