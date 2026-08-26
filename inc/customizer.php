<?php
/**
 * Theme Customizer: site-wide footer note only.
 *
 * Page settings live on the pages they belong to.
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
		'stillframe_footer_note',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'stillframe_footer_note',
		array(
			'label'       => __( 'Footer note', 'stillframe' ),
			'description' => __( 'Optional site-wide line in the footer. Leave blank to show only the copyright and LinkedIn.', 'stillframe' ),
			'section'     => 'stillframe_vibe',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'stillframe_customize_register' );
