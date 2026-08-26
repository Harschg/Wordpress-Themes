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
