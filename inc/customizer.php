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
}
add_action( 'customize_register', 'stillframe_customize_register' );
