<?php
/**
 * Theme Customizer: vibe line and footer note.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function stillframe_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'stillframe_vibe',
		array(
			'title'    => __( 'Stillframe', 'stillframe' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'stillframe_vibe_line',
		array(
			'default'           => __( 'Laid-back photographs and things I make.', 'stillframe' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'stillframe_vibe_line',
		array(
			'label'   => __( 'Home vibe line', 'stillframe' ),
			'section' => 'stillframe_vibe',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'stillframe_footer_note',
		array(
			'default'           => __( 'Shot slow. Built with care.', 'stillframe' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'stillframe_footer_note',
		array(
			'label'   => __( 'Footer note', 'stillframe' ),
			'section' => 'stillframe_vibe',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'stillframe_contact_email',
		array(
			'default'           => get_option( 'admin_email' ),
			'sanitize_callback' => 'sanitize_email',
		)
	);

	$wp_customize->add_control(
		'stillframe_contact_email',
		array(
			'label'       => __( 'Contact email', 'stillframe' ),
			'description' => __( 'Where contact form messages are sent, and the mailto link on the Contact page.', 'stillframe' ),
			'section'     => 'stillframe_vibe',
			'type'        => 'email',
		)
	);

	$wp_customize->add_setting(
		'stillframe_instagram',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		'stillframe_instagram',
		array(
			'label'   => __( 'Instagram URL', 'stillframe' ),
			'section' => 'stillframe_vibe',
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'stillframe_github',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		'stillframe_github',
		array(
			'label'   => __( 'GitHub URL', 'stillframe' ),
			'section' => 'stillframe_vibe',
			'type'    => 'url',
		)
	);
}
add_action( 'customize_register', 'stillframe_customize_register' );
