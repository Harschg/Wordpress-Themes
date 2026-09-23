<?php
/**
 * Home page helpers and meta.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


/**
 * Subtitle under the Home banner title.
 *
 * @return string
 */
function stillframe_home_subtitle() {
	$page_id = (int) get_option( 'page_on_front' );
	if ( $page_id ) {
		$value = get_post_meta( $page_id, 'stillframe_subtitle', true );
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	return (string) get_theme_mod( 'stillframe_vibe_line', '' );
}

/**
 * Home description HTML from the front page editor.
 *
 * @return string
 */
function stillframe_home_intro_html() {
	$page_id = (int) get_option( 'page_on_front' );
	if ( $page_id ) {
		$post = get_post( $page_id );
		if ( $post instanceof WP_Post && '' !== trim( wp_strip_all_tags( (string) $post->post_content ) ) ) {
			return apply_filters( 'the_content', $post->post_content );
		}
	}

	$mod = trim( (string) get_theme_mod( 'stillframe_home_intro', '' ) );

	return $mod ? wp_kses_post( wpautop( $mod ) ) : '';
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
 * Register Home meta box.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_home_meta_box( $post_type, $post ) {
	if ( 'page' !== $post_type || ! $post instanceof WP_Post ) {
		return;
	}

	if ( ! stillframe_is_home_page( $post->ID ) ) {
		return;
	}

	add_meta_box(
		'stillframe_home_details',
		__( 'Home', 'stillframe' ),
		'stillframe_render_home_meta_box',
		'page',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_home_meta_box', 10, 2 );
