<?php
/**
 * Project helpers, details meta, and feature rows.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


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
 * Register project meta boxes.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_project_meta_boxes( $post_type, $post ) {
	if ( 'project' !== $post_type ) {
		return;
	}

	add_meta_box(
		'stillframe_project_details',
		__( 'Project details', 'stillframe' ),
		'stillframe_render_project_meta_box',
		'project',
		'side',
		'high'
	);

	add_meta_box(
		'stillframe_project_features',
		__( 'Project features', 'stillframe' ),
		'stillframe_render_project_features_meta_box',
		'project',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_project_meta_boxes', 10, 2 );

/**
 * Admin assets for project feature rows.
 *
 * @param string $hook Current admin page.
 */
function stillframe_project_features_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'project' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

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
add_action( 'admin_enqueue_scripts', 'stillframe_project_features_admin_assets' );
