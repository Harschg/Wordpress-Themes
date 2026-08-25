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
function stillframe_add_meta_boxes() {
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
}
add_action( 'add_meta_boxes', 'stillframe_add_meta_boxes' );

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
