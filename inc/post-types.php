<?php
/**
 * Custom post types and taxonomies.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register photograph and project post types.
 */
function stillframe_register_post_types() {
	register_post_type(
		'photograph',
		array(
			'labels'              => array(
				'name'          => __( 'Photographs', 'stillframe' ),
				'singular_name' => __( 'Photograph', 'stillframe' ),
				'add_new_item'  => __( 'Add Photograph', 'stillframe' ),
				'edit_item'     => __( 'Edit Photograph', 'stillframe' ),
				'view_item'     => __( 'View Photograph', 'stillframe' ),
				'search_items'  => __( 'Search Photographs', 'stillframe' ),
				'not_found'     => __( 'No photographs yet.', 'stillframe' ),
			),
			'public'              => true,
			'has_archive'         => 'gallery',
			'rewrite'             => array(
				'slug'       => 'photo',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-camera',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest'        => true,
			'exclude_from_search' => false,
		)
	);

	register_post_type(
		'project',
		array(
			'labels'       => array(
				'name'          => __( 'Projects', 'stillframe' ),
				'singular_name' => __( 'Project', 'stillframe' ),
				'add_new_item'  => __( 'Add Project', 'stillframe' ),
				'edit_item'     => __( 'Edit Project', 'stillframe' ),
				'view_item'     => __( 'View Project', 'stillframe' ),
				'search_items'  => __( 'Search Projects', 'stillframe' ),
				'not_found'     => __( 'No projects yet.', 'stillframe' ),
			),
			'public'       => true,
			'has_archive'  => 'projects',
			'rewrite'      => array(
				'slug'       => 'work',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-portfolio',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest' => true,
		)
	);

	register_taxonomy(
		'photo_series',
		'photograph',
		array(
			'labels'            => array(
				'name'          => __( 'Series', 'stillframe' ),
				'singular_name' => __( 'Series', 'stillframe' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'series' ),
		)
	);

	register_taxonomy(
		'project_type',
		'project',
		array(
			'labels'            => array(
				'name'          => __( 'Project types', 'stillframe' ),
				'singular_name' => __( 'Project type', 'stillframe' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'type' ),
		)
	);
}
add_action( 'init', 'stillframe_register_post_types' );

/**
 * Register post meta used by the front-end templates.
 */
function stillframe_register_meta() {
	$photo_fields = array(
		'stillframe_location' => __( 'Location', 'stillframe' ),
		'stillframe_camera'   => __( 'Camera', 'stillframe' ),
		'stillframe_year'     => __( 'Year', 'stillframe' ),
	);

	foreach ( $photo_fields as $key => $label ) {
		unset( $label );
		register_post_meta(
			'photograph',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'project',
		'stillframe_stack',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'single'            => true,
			'show_in_rest'      => true,
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	foreach ( array( 'stillframe_github', 'stillframe_live_url' ) as $url_key ) {
		register_post_meta(
			'project',
			$url_key,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'page',
		'stillframe_resume_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => function () {
				return current_user_can( 'edit_pages' );
			},
		)
	);
}
add_action( 'init', 'stillframe_register_meta' );
