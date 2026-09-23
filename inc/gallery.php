<?php
/**
 * Photograph gallery queries, neighbors, and photo meta.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


/**
 * Series terms that currently have photographs.
 *
 * @return WP_Term[]
 */
function stillframe_photo_series_terms() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'photo_series',
			'hide_empty' => true,
			'parent'     => 0,
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Recent photographs in a series, for card previews.
 *
 * @param int $term_id Series term ID.
 * @param int $count   How many to fetch.
 * @return WP_Post[]
 */
function stillframe_series_preview_photos( $term_id, $count = 3 ) {
	$count = max( 1, (int) $count );
	$ids   = get_posts(
		array(
			'post_type'      => 'photograph',
			'posts_per_page' => 24,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'photo_series',
					'field'    => 'term_id',
					'terms'    => (int) $term_id,
				),
			),
		)
	);

	$with_image = array();

	foreach ( $ids as $post_id ) {
		$post_id = (int) $post_id;
		if ( ! stillframe_card_thumbnail_id( $post_id ) ) {
			continue;
		}

		$post = get_post( $post_id );
		if ( $post instanceof WP_Post ) {
			$with_image[] = $post;
		}

		if ( count( $with_image ) >= $count ) {
			break;
		}
	}

	return $with_image;
}

/**
 * Previous and next photograph IDs, in title order, within the same series
 * or among ungrouped gallery photos.
 *
 * @param int $post_id Current photograph ID.
 * @return array{prev: int|null, next: int|null}
 */
function stillframe_photograph_neighbors( $post_id ) {
	$post_id = (int) $post_id;
	$args    = array(
		'post_type'      => 'photograph',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);

	$terms = get_the_terms( $post_id, 'photo_series' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'photo_series',
				'field'    => 'term_id',
				'terms'    => (int) $terms[0]->term_id,
			),
		);
	} else {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'photo_series',
				'operator' => 'NOT EXISTS',
			),
		);
	}

	$ids   = array_map( 'intval', get_posts( $args ) );
	$index = array_search( $post_id, $ids, true );

	if ( false === $index ) {
		return array(
			'prev' => null,
			'next' => null,
		);
	}

	return array(
		'prev' => $index > 0 ? $ids[ $index - 1 ] : null,
		'next' => ( $index < count( $ids ) - 1 ) ? $ids[ $index + 1 ] : null,
	);
}

/**
 * Keep series photographs off the main gallery grid when series exist.
 *
 * @param WP_Query $query Query.
 */
function stillframe_gallery_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'photograph' ) && stillframe_photo_series_terms() ) {
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'photo_series',
					'operator' => 'NOT EXISTS',
				),
			)
		);
	}

	if ( $query->is_post_type_archive( 'photograph' ) || $query->is_tax( 'photo_series' ) || $query->is_post_type_archive( 'project' ) || $query->is_tax( 'project_type' ) ) {
		$query->set( 'posts_per_page', 24 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'stillframe_gallery_query' );

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
 * Register photograph meta boxes.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_photograph_meta_boxes( $post_type, $post ) {
	if ( 'photograph' !== $post_type ) {
		return;
	}

	add_meta_box(
		'stillframe_photograph_details',
		__( 'Photograph details', 'stillframe' ),
		'stillframe_render_photograph_meta_box',
		'photograph',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_photograph_meta_boxes', 10, 2 );
