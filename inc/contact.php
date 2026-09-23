<?php
/**
 * Contact form mail handler.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Process the contact form POST.
 */
function stillframe_handle_contact() {
	$redirect = wp_get_referer();

	if ( ! $redirect ) {
		$redirect = stillframe_page_url( 'contact' );
	}

	$redirect = remove_query_arg( 'contact', $redirect );

	if ( ! isset( $_POST['stillframe_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_contact_nonce'] ) ), 'stillframe_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
		exit;
	}

	if ( ! empty( $_POST['stillframe_company'] ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'sent', $redirect ) );
		exit;
	}

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'stillframe_contact_' . md5( $ip );

	if ( get_transient( $key ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
		exit;
	}

	$name    = isset( $_POST['stillframe_name'] ) ? sanitize_text_field( wp_unslash( $_POST['stillframe_name'] ) ) : '';
	$email   = isset( $_POST['stillframe_email'] ) ? sanitize_email( wp_unslash( $_POST['stillframe_email'] ) ) : '';
	$message = isset( $_POST['stillframe_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['stillframe_message'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'contact', 'invalid', $redirect ) );
		exit;
	}

	$to = stillframe_contact_setting( 'stillframe_contact_email', get_option( 'admin_email' ) );

	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$subject = sprintf(
		/* translators: 1: site name, 2: sender name */
		__( '[%1$s] Message from %2$s', 'stillframe' ),
		wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		$name
	);

	$body  = sprintf( "Name: %s\n", $name );
	$body .= sprintf( "Email: %s\n\n", $email );
	$body .= $message;

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$sent = wp_mail( $to, $subject, $body, $headers );

	if ( $sent ) {
		set_transient( $key, 1, MINUTE_IN_SECONDS );
	}

	wp_safe_redirect( add_query_arg( 'contact', $sent ? 'sent' : 'error', $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_stillframe_contact', 'stillframe_handle_contact' );
add_action( 'admin_post_stillframe_contact', 'stillframe_handle_contact' );

/**
 * A Contact page setting, with Customizer leftover as fallback.
 *
 * @param string $key     Meta / theme_mod key.
 * @param string $default Fallback if both are empty.
 * @return string
 */
function stillframe_contact_setting( $key, $default = '' ) {
	$page = stillframe_get_section_page( 'contact' );

	if ( $page instanceof WP_Post ) {
		$value = get_post_meta( $page->ID, $key, true );
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	$mod = get_theme_mod( $key, $default );

	return is_string( $mod ) ? $mod : $default;
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
 * Register Contact meta box.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_contact_meta_box( $post_type, $post ) {
	if ( 'page' !== $post_type || ! $post instanceof WP_Post ) {
		return;
	}

	if ( ! stillframe_is_contact_page( $post->ID ) ) {
		return;
	}

	add_meta_box(
		'stillframe_contact_details',
		__( 'Contact', 'stillframe' ),
		'stillframe_render_contact_meta_box',
		'page',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_contact_meta_box', 10, 2 );
