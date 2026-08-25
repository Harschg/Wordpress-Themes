<?php
/**
 * Template helper functions.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Permalink for a page slug, with a sensible fallback.
 *
 * @param string $slug Page slug.
 * @return string
 */
function stillframe_page_url( $slug ) {
	$page = get_page_by_path( $slug );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return home_url( '/' . trim( $slug, '/' ) . '/' );
}

/**
 * Fallback menu when no menu is assigned in Appearance → Menus.
 */
function stillframe_fallback_menu() {
	$items = array(
		array(
			'url'   => home_url( '/' ),
			'label' => __( 'Home', 'stillframe' ),
		),
		array(
			'url'   => stillframe_page_url( 'about' ),
			'label' => __( 'About', 'stillframe' ),
		),
		array(
			'url'   => get_post_type_archive_link( 'photograph' ),
			'label' => __( 'Gallery', 'stillframe' ),
		),
		array(
			'url'   => get_post_type_archive_link( 'project' ),
			'label' => __( 'Projects', 'stillframe' ),
		),
	);

	echo '<ul class="nav-list">';

	foreach ( $items as $item ) {
		if ( empty( $item['url'] ) ) {
			continue;
		}

		printf(
			'<li><a class="nav-link" href="%1$s">%2$s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}

	echo '</ul>';
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
 * Body class for the loading curtain.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function stillframe_body_class( $classes ) {
	$classes[] = 'has-page-motion';

	return $classes;
}
add_filter( 'body_class', 'stillframe_body_class' );
