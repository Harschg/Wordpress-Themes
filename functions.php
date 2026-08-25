<?php
/**
 * Stillframe theme bootstrap.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

define( 'STILLFRAME_VERSION', '1.0.4' );

require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/post-types.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/meta-boxes.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/contact.php';
