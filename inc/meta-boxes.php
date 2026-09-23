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

	if ( 'project' === $post_type ) {
		add_meta_box(
			'stillframe_project_features',
			__( 'Project features', 'stillframe' ),
			'stillframe_render_project_features_meta_box',
			'project',
			'normal',
			'high'
		);
	}

	if ( stillframe_page_uses_section_wrap( $post->ID ) ) {
		add_meta_box(
			'stillframe_join_sections',
			__( 'Join sections', 'stillframe' ),
			'stillframe_render_join_sections_meta_box',
			$post_type,
			'normal',
			'default'
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
			'stillframe_about_dropdown',
			__( 'About dropdown', 'stillframe' ),
			'stillframe_render_about_dropdown_meta_box',
			'page',
			'side',
			'default'
		);
	}

	if ( stillframe_is_resume_page( $post->ID ) ) {
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
	<p><?php esc_html_e( 'Featured image is the card and the photo behind the page. Write the intro in the editor. Add each feature below with its own photo and description.', 'stillframe' ); ?></p>
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
 * Pages that appear when hovering About in the header.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_about_dropdown_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_about_dropdown', 'stillframe_about_dropdown_nonce' );

	$selected = stillframe_about_dropdown_page_ids( (int) $post->ID );
	$pages    = get_pages(
		array(
			'sort_column' => 'menu_order,post_title',
			'exclude'     => array( (int) $post->ID, (int) get_option( 'page_on_front' ) ),
		)
	);
	?>
	<p><?php esc_html_e( 'These pages appear when someone hovers About in the header, in the same order as the links in this page. Leave them unchecked to use the linked pages automatically.', 'stillframe' ); ?></p>
	<?php if ( $pages ) : ?>
		<ul style="margin:0;padding:0;list-style:none;max-height:14rem;overflow:auto;">
			<?php foreach ( $pages as $page ) : ?>
				<li style="margin:0 0 0.35rem;">
					<label>
						<input
							type="checkbox"
							name="stillframe_about_dropdown_ids[]"
							value="<?php echo esc_attr( (string) $page->ID ); ?>"
							<?php checked( in_array( (int) $page->ID, $selected, true ) ); ?>
						/>
						<?php echo esc_html( get_the_title( $page ) ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Create other pages first, then they will show up here.', 'stillframe' ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Resume upload on the Resume page.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_resume_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_resume', 'stillframe_resume_nonce' );

	$resume_id = (int) get_post_meta( $post->ID, 'stillframe_resume_id', true );
	$file      = $resume_id ? get_post( $resume_id ) : null;
	$filename  = ( $file instanceof WP_Post ) ? $file->post_title : '';
	?>
	<p><?php esc_html_e( 'PDF or a photo of the resume. It displays on this page. Choosing a file saves it right away.', 'stillframe' ); ?></p>
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
 * Feature rows: heading, description, and a photo for that part of the write-up.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_project_features_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_features', 'stillframe_features_nonce' );

	$features = stillframe_project_features( $post->ID );
	if ( ! $features ) {
		$features = array(
			array(
				'title'    => '',
				'text'     => '',
				'image_id' => 0,
			),
		);
	}
	?>
	<p><?php esc_html_e( 'Each row is one part of the project: a heading, the description, and the photo that belongs with it. They sit side by side on the page. Leave a photo empty if that section is text only.', 'stillframe' ); ?></p>
	<div data-feature-list>
		<?php
		foreach ( $features as $index => $feature ) {
			stillframe_render_project_feature_row( $index, $feature );
		}
		?>
	</div>
	<p>
		<button type="button" class="button" data-feature-add><?php esc_html_e( 'Add feature', 'stillframe' ); ?></button>
	</p>
	<template data-feature-template>
		<?php
		stillframe_render_project_feature_row(
			'__i__',
			array(
				'title'    => '',
				'text'     => '',
				'image_id' => 0,
			)
		);
		?>
	</template>
	<?php
}

/**
 * One feature row in the project editor.
 *
 * @param int|string $index   Row index.
 * @param array      $feature {
 *     @type string $title
 *     @type string $text
 *     @type int    $image_id
 * }
 */
function stillframe_render_project_feature_row( $index, $feature ) {
	$title    = isset( $feature['title'] ) ? $feature['title'] : '';
	$text     = isset( $feature['text'] ) ? $feature['text'] : '';
	$image_id = isset( $feature['image_id'] ) ? (int) $feature['image_id'] : 0;
	$thumb    = $image_id ? wp_get_attachment_image(
		$image_id,
		'medium',
		false,
		array(
			'style' => 'display:block;max-width:180px;height:auto;margin:0 0 8px;',
		)
	) : '';
	?>
	<div class="stillframe-feature-row" data-feature-row>
		<p>
			<label><?php esc_html_e( 'Heading', 'stillframe' ); ?></label>
			<input type="text" class="widefat" name="stillframe_feature_title[]" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label><?php esc_html_e( 'Description', 'stillframe' ); ?></label>
			<textarea class="widefat" name="stillframe_feature_text[]" rows="5"><?php echo esc_textarea( $text ); ?></textarea>
		</p>
		<div>
			<label><?php esc_html_e( 'Photo for this section', 'stillframe' ); ?></label>
			<input type="hidden" name="stillframe_feature_image[]" value="<?php echo esc_attr( (string) $image_id ); ?>" data-feature-image-id />
			<div data-feature-preview><?php echo $thumb ? $thumb : ''; ?></div>
			<p>
				<button type="button" class="button" data-feature-upload><?php esc_html_e( 'Choose image', 'stillframe' ); ?></button>
				<button type="button" class="button" data-feature-image-remove <?php echo $image_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove photo', 'stillframe' ); ?></button>
				<button type="button" class="button-link" data-feature-remove><?php esc_html_e( 'Remove this feature', 'stillframe' ); ?></button>
			</p>
		</div>
	</div>
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
	<p><?php esc_html_e( 'Write the site description in the page editor. It shows on the left. Set a Featured image for the portrait on the right.', 'stillframe' ); ?></p>
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

	if ( 'page' === $screen->post_type && stillframe_is_resume_page( $post_id ) ) {
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

	if ( 'project' === $screen->post_type ) {
		wp_enqueue_script(
			'stillframe-admin-features',
			get_template_directory_uri() . '/assets/js/admin-project-features.js',
			array( 'jquery' ),
			STILLFRAME_VERSION,
			true
		);

		wp_add_inline_style(
			'common',
			'.stillframe-feature-row{display:grid;grid-template-columns:1fr 1fr 180px;gap:12px;align-items:start;margin:0 0 16px;padding:12px;border:1px solid #dcdcde;background:#fff;}
			.stillframe-feature-row p{margin:0 0 8px;}
			@media (max-width:782px){.stillframe-feature-row{grid-template-columns:1fr;}}'
		);
	}
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
 * Save project feature sections.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_project_features_meta( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'project' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_features_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_features_nonce'] ) ), 'stillframe_save_features' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$titles = isset( $_POST['stillframe_feature_title'] ) ? (array) wp_unslash( $_POST['stillframe_feature_title'] ) : array();
	$texts  = isset( $_POST['stillframe_feature_text'] ) ? (array) wp_unslash( $_POST['stillframe_feature_text'] ) : array();
	$images = isset( $_POST['stillframe_feature_image'] ) ? (array) wp_unslash( $_POST['stillframe_feature_image'] ) : array();
	$count  = max( count( $titles ), count( $texts ), count( $images ) );
	$rows   = array();

	for ( $i = 0; $i < $count; $i++ ) {
		$title    = isset( $titles[ $i ] ) ? sanitize_text_field( $titles[ $i ] ) : '';
		$text     = isset( $texts[ $i ] ) ? sanitize_textarea_field( $texts[ $i ] ) : '';
		$image_id = isset( $images[ $i ] ) ? absint( $images[ $i ] ) : 0;

		if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}

		if ( '' === $title && '' === $text && ! $image_id ) {
			continue;
		}

		$rows[] = array(
			'title'    => $title,
			'text'     => $text,
			'image_id' => $image_id,
		);
	}

	if ( $rows ) {
		update_post_meta( $post_id, 'stillframe_project_features', $rows );
	} else {
		delete_post_meta( $post_id, 'stillframe_project_features' );
	}
}
add_action( 'save_post_project', 'stillframe_save_project_features_meta' );

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

/**
 * Save About header dropdown page IDs.
 *
 * @param int $post_id Page ID.
 */
function stillframe_save_about_dropdown_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! stillframe_is_about_page( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_about_dropdown_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_about_dropdown_nonce'] ) ), 'stillframe_save_about_dropdown' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$ids = array();
	if ( isset( $_POST['stillframe_about_dropdown_ids'] ) && is_array( $_POST['stillframe_about_dropdown_ids'] ) ) {
		foreach ( wp_unslash( $_POST['stillframe_about_dropdown_ids'] ) as $id ) {
			$id = absint( $id );
			if ( $id && $id !== (int) $post_id && 'page' === get_post_type( $id ) ) {
				$ids[] = $id;
			}
		}
	}

	$ids = array_values( array_unique( $ids ) );

	if ( $ids ) {
		update_post_meta( $post_id, 'stillframe_about_dropdown_ids', $ids );
	} else {
		delete_post_meta( $post_id, 'stillframe_about_dropdown_ids' );
	}
}
add_action( 'save_post', 'stillframe_save_about_dropdown_meta' );

/**
 * Checkboxes to keep selected headings in the same lifted card.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_join_sections_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_section_joins', 'stillframe_section_joins_nonce' );

	$levels   = stillframe_section_wrap_levels( $post->ID );
	$headings = stillframe_joinable_headings( $post->ID, $levels[0], $levels[1] );
	$joined   = stillframe_joined_section_ids( $post->ID );
	?>
	<input type="hidden" name="stillframe_joined_sections_present" value="1" />
	<p><?php esc_html_e( 'Checked headings stay in the same card as the one above. Nested headings already stay with their parent.', 'stillframe' ); ?></p>
	<?php if ( count( $headings ) < 2 ) : ?>
		<p class="description"><?php esc_html_e( 'Add at least two headings that start their own cards, then update the page to join them here.', 'stillframe' ); ?></p>
		<?php
		return;
	endif;
	?>
	<ul style="margin:0;padding:0;list-style:none;">
		<?php foreach ( $headings as $index => $heading ) : ?>
			<li style="margin:0 0 0.5rem;">
				<?php if ( 0 === $index ) : ?>
					<strong><?php echo esc_html( $heading['title'] ); ?></strong>
				<?php else : ?>
					<label>
						<input
							type="checkbox"
							name="stillframe_joined_sections[]"
							value="<?php echo esc_attr( $heading['id'] ); ?>"
							<?php checked( in_array( $heading['id'], $joined, true ) ); ?>
						/>
						<?php echo esc_html( $heading['title'] ); ?>
						<span class="description"><?php esc_html_e( 'Keep with the section above', 'stillframe' ); ?></span>
					</label>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Save joined section heading ids.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_section_joins_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! stillframe_page_uses_section_wrap( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_section_joins_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_section_joins_nonce'] ) ), 'stillframe_save_section_joins' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_joined_sections_present'] ) ) {
		return;
	}

	$levels   = stillframe_section_wrap_levels( $post_id );
	$headings = stillframe_joinable_headings( $post_id, $levels[0], $levels[1] );
	$allowed  = array();
	foreach ( $headings as $index => $heading ) {
		if ( 0 === $index ) {
			continue;
		}
		$allowed[] = $heading['id'];
	}

	$ids = array();
	if ( isset( $_POST['stillframe_joined_sections'] ) && is_array( $_POST['stillframe_joined_sections'] ) ) {
		foreach ( wp_unslash( $_POST['stillframe_joined_sections'] ) as $id ) {
			$id = sanitize_title( (string) $id );
			if ( $id && in_array( $id, $allowed, true ) ) {
				$ids[] = $id;
			}
		}
	}

	$ids = array_values( array_unique( $ids ) );

	if ( $ids ) {
		update_post_meta( $post_id, 'stillframe_joined_sections', $ids );
	} else {
		delete_post_meta( $post_id, 'stillframe_joined_sections' );
	}
}
add_action( 'save_post', 'stillframe_save_section_joins_meta' );
