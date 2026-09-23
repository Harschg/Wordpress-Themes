<?php
/**
 * Glass section lifts, TOC, and join-sections meta.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


/**
 * Headings in HTML content, with ids matching stillframe_about_heading_ids().
 *
 * @param string $content HTML.
 * @param int    $min_level 2–4.
 * @param int    $max_level 2–4.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_content_headings( $content, $min_level = 2, $max_level = 3 ) {
	$items = array();
	if ( ! $content ) {
		return $items;
	}

	$min_level = max( 2, min( 4, (int) $min_level ) );
	$max_level = max( $min_level, min( 4, (int) $max_level ) );
	$used      = array();

	if ( ! preg_match_all( '/<h([2-4])(\s[^>]*)?>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $items;
	}

	$count = count( $matches );
	for ( $i = 0; $i < $count; $i++ ) {
		$match = $matches[ $i ];
		$level = (int) $match[1][0];
		if ( $level < $min_level || $level > $max_level ) {
			continue;
		}

		$title = trim( wp_strip_all_tags( $match[3][0] ) );
		if ( '' === $title ) {
			continue;
		}

		$heading_end = $match[0][1] + strlen( $match[0][0] );
		$next_start  = strlen( $content );
		for ( $j = $i + 1; $j < $count; $j++ ) {
			if ( (int) $matches[ $j ][1][0] <= $level ) {
				$next_start = (int) $matches[ $j ][0][1];
				break;
			}
		}
		$following = substr( $content, $heading_end, max( 0, $next_start - $heading_end ) );
		if ( ! stillframe_section_has_body( $following ) ) {
			continue;
		}

		$attrs = isset( $match[2][0] ) ? $match[2][0] : '';
		$id    = '';
		if ( preg_match( '/\sid\s*=\s*([\'"])([^\'"]+)\1/i', $attrs, $id_match ) ) {
			$id = sanitize_title( $id_match[2] );
		}
		if ( '' === $id ) {
			$id = sanitize_title( $title );
		}
		if ( '' === $id ) {
			continue;
		}

		$base = $id;
		$n    = 2;
		while ( isset( $used[ $id ] ) ) {
			$id = $base . '-' . $n;
			++$n;
		}
		$used[ $id ] = true;

		$items[] = array(
			'id'    => $id,
			'title' => $title,
			'level' => $level,
		);
	}

	return $items;
}

/**
 * Whether a content fragment has anything besides an empty heading.
 *
 * @param string $html HTML.
 * @return bool
 */
function stillframe_section_has_body( $html ) {
	$html = preg_replace( '/<!--.*?-->/s', '', (string) $html );
	if ( preg_match( '/<(img|figure|iframe|video|audio|svg|object|embed|ul|ol|blockquote|table|hr|pre|canvas)\b/i', $html ) ) {
		return true;
	}

	$html = preg_replace( '/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', '', $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( '/\x{00A0}/u', ' ', $text );

	return '' !== trim( (string) $text );
}

/**
 * Wrap a content fragment in a lifted glass section.
 *
 * @param string $inner HTML.
 * @return string
 */
function stillframe_glass_lift_html( $inner ) {
	$inner = trim( (string) $inner );
	if ( '' === $inner || ! stillframe_section_has_body( $inner ) ) {
		return '';
	}

	return '<section class="glass-lift">' . $inner . '</section>';
}

/**
 * Heading id from a single heading tag.
 *
 * @param string $html Heading markup.
 * @return string
 */
function stillframe_heading_id_from_markup( $html ) {
	$html = (string) $html;
	if ( preg_match( '/\sid\s*=\s*([\'"])([^\'"]+)\1/i', $html, $match ) ) {
		return sanitize_title( $match[2] );
	}

	if ( preg_match( '/<h[1-6]\b[^>]*>(.*?)<\/h[1-6]>/is', $html, $match ) ) {
		return sanitize_title( wp_strip_all_tags( $match[1] ) );
	}

	return '';
}

/**
 * Pages and projects that wrap headings in glass lifts.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function stillframe_page_uses_section_wrap( $post_id ) {
	$post_id = (int) $post_id;
	if ( 'project' === get_post_type( $post_id ) ) {
		return true;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return false;
	}

	if ( stillframe_is_about_page( $post_id ) || stillframe_is_home_page( $post_id ) || stillframe_is_contact_page( $post_id ) ) {
		return false;
	}

	return stillframe_is_about_subpage( $post_id ) || stillframe_is_resume_page( $post_id );
}

/**
 * Heading range used when wrapping this post into lifts.
 *
 * @param int $post_id Post ID.
 * @return array{0:int,1:int}
 */
function stillframe_section_wrap_levels( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( 'project' === get_post_type( $post_id ) ) {
		return array( 3, 3 );
	}

	return array( 2, 3 );
}

/**
 * Saved heading ids that should stay in the previous lift.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function stillframe_joined_section_ids( $post_id ) {
	$raw = get_post_meta( (int) $post_id, 'stillframe_joined_sections', true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$ids = array();
	foreach ( $raw as $id ) {
		$id = sanitize_title( (string) $id );
		if ( '' !== $id ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Headings that start a new lift and can be joined to the one above.
 *
 * @param int $post_id    Post ID.
 * @param int $min_level  2–4.
 * @param int $max_level  2–4.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_joinable_headings( $post_id, $min_level = 2, $max_level = 3 ) {
	$post = get_post( (int) $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$content   = (string) $post->post_content;
	$min_level = max( 2, min( 4, (int) $min_level ) );
	$max_level = max( $min_level, min( 4, (int) $max_level ) );

	$split_level = 0;
	for ( $level = $min_level; $level <= $max_level; $level++ ) {
		if ( preg_match( '/<h' . $level . '\b/i', $content ) ) {
			$split_level = $level;
			break;
		}
	}

	if ( ! $split_level ) {
		return array();
	}

	$items = stillframe_content_headings( $content, $min_level, $max_level );
	if ( ! $items ) {
		$items = stillframe_content_headings( $content, 2, 4 );
	}

	$joinable = array();
	foreach ( $items as $item ) {
		if ( (int) $item['level'] === $split_level ) {
			$joinable[] = $item;
		}
	}

	return $joinable;
}

/**
 * Split HTML on top-level headings so each major section sits on its own panel.
 *
 * Nested headings (H3 under H2, and so on) stay inside the same lift.
 * Headings marked “join with previous” in the editor stay in the previous lift.
 *
 * @param string $html      Filtered post content.
 * @param int    $min_level 2–4.
 * @param int    $max_level 2–4.
 * @param int    $post_id   Post whose join list to use.
 * @return string
 */
function stillframe_wrap_content_sections( $html, $min_level = 2, $max_level = 3, $post_id = 0 ) {
	$html = trim( (string) $html );
	if ( '' === $html ) {
		return '';
	}

	$min_level = max( 2, min( 4, (int) $min_level ) );
	$max_level = max( $min_level, min( 4, (int) $max_level ) );
	$post_id   = $post_id ? (int) $post_id : (int) get_the_ID();
	$joined    = $post_id ? stillframe_joined_section_ids( $post_id ) : array();

	$split_level = 0;
	for ( $level = $min_level; $level <= $max_level; $level++ ) {
		if ( preg_match( '/<h' . $level . '\b/i', $html ) ) {
			$split_level = $level;
			break;
		}
	}

	if ( ! $split_level ) {
		return stillframe_glass_lift_html( $html );
	}

	$pattern = '/(<h' . $split_level . '\b[^>]*>.*?<\/h' . $split_level . '>)/is';
	$parts   = preg_split( $pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE );

	if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
		return stillframe_glass_lift_html( $html );
	}

	$out    = '';
	$buffer = '';

	foreach ( $parts as $part ) {
		if ( preg_match( '/^<h' . $split_level . '\b/i', $part ) ) {
			$id = stillframe_heading_id_from_markup( $part );
			if ( $id && in_array( $id, $joined, true ) && '' !== trim( $buffer ) ) {
				$buffer .= $part;
				continue;
			}

			$out   .= stillframe_glass_lift_html( $buffer );
			$buffer = $part;
			continue;
		}

		$buffer .= $part;
	}

	$out .= stillframe_glass_lift_html( $buffer );

	return $out ? $out : stillframe_glass_lift_html( $html );
}

/**
 * Jump links for the About table of contents.
 *
 * @param int $post_id Page ID.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_about_toc_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$post    = get_post( $post_id );
	$items   = array();

	if ( $post instanceof WP_Post ) {
		$items = stillframe_content_headings( (string) $post->post_content, 2, 3 );
		if ( ! $items ) {
			$items = stillframe_content_headings( (string) $post->post_content, 2, 4 );
		}
	}

	return $items;
}

/**
 * Jump links for the project table of contents. H3s only.
 *
 * @param int $post_id Project ID.
 * @return array<int, array{id:string, title:string, level:int}>
 */
function stillframe_project_toc_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	return stillframe_content_headings( (string) $post->post_content, 3, 3 );
}

/**
 * Give About / project headings ids so the contents list can jump to them.
 *
 * @param string $content Page content.
 * @return string
 */
function stillframe_about_heading_ids( $content ) {
	if ( is_admin() ) {
		return $content;
	}

	$levels = '';
	if ( is_singular( 'page' ) && ( stillframe_is_about_page() || stillframe_is_about_subpage() || stillframe_is_resume_page() ) ) {
		$levels = '2-4';
	} elseif ( is_singular( 'project' ) ) {
		$levels = '3';
	} else {
		return $content;
	}

	$used = array();

	return preg_replace_callback(
		'/<h([' . $levels . '])(\s[^>]*)?>(.*?)<\/h\1>/is',
		function ( $match ) use ( &$used ) {
			$attrs = isset( $match[2] ) ? $match[2] : '';
			if ( preg_match( '/\sid\s*=/', $attrs ) ) {
				return $match[0];
			}

			$id = sanitize_title( wp_strip_all_tags( $match[3] ) );
			if ( '' === $id ) {
				return $match[0];
			}

			$base = $id;
			$n    = 2;
			while ( isset( $used[ $id ] ) ) {
				$id = $base . '-' . $n;
				++$n;
			}
			$used[ $id ] = true;

			return '<h' . $match[1] . $attrs . ' id="' . esc_attr( $id ) . '">' . $match[3] . '</h' . $match[1] . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'stillframe_about_heading_ids', 12 );

/**
 * Checkboxes to keep selected headings in the same lifted card.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_join_sections_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_section_joins', 'stillframe_section_joins_nonce' );

	$levels   = stillframe_section_wrap_levels( $post->ID );
	$headings = stillframe_joinable_headings( $post->ID, $levels[0], $levels[1] );
	$joined   = stillframe_joined_section_ids( $post->ID );
	?>
	<input type="hidden" name="stillframe_joined_sections_present" value="1" />
	<p><?php esc_html_e( 'Checked headings stay in the same card as the one above. Nested headings already stay with their parent.', 'stillframe' ); ?></p>
	<?php if ( count( $headings ) < 2 ) : ?>
		<p class="description"><?php esc_html_e( 'Add at least two headings that start their own cards, then update the page to join them here.', 'stillframe' ); ?></p>
		<?php
		return;
	endif;
	?>
	<ul style="margin:0;padding:0;list-style:none;">
		<?php foreach ( $headings as $index => $heading ) : ?>
			<li style="margin:0 0 0.5rem;">
				<?php if ( 0 === $index ) : ?>
					<strong><?php echo esc_html( $heading['title'] ); ?></strong>
				<?php else : ?>
					<label>
						<input
							type="checkbox"
							name="stillframe_joined_sections[]"
							value="<?php echo esc_attr( $heading['id'] ); ?>"
							<?php checked( in_array( $heading['id'], $joined, true ) ); ?>
						/>
						<?php echo esc_html( $heading['title'] ); ?>
						<span class="description"><?php esc_html_e( 'Keep with the section above', 'stillframe' ); ?></span>
					</label>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Save joined section heading ids.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_section_joins_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! stillframe_page_uses_section_wrap( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_section_joins_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_section_joins_nonce'] ) ), 'stillframe_save_section_joins' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_joined_sections_present'] ) ) {
		return;
	}

	$levels   = stillframe_section_wrap_levels( $post_id );
	$headings = stillframe_joinable_headings( $post_id, $levels[0], $levels[1] );
	$allowed  = array();
	foreach ( $headings as $index => $heading ) {
		if ( 0 === $index ) {
			continue;
		}
		$allowed[] = $heading['id'];
	}

	$ids = array();
	if ( isset( $_POST['stillframe_joined_sections'] ) && is_array( $_POST['stillframe_joined_sections'] ) ) {
		foreach ( wp_unslash( $_POST['stillframe_joined_sections'] ) as $id ) {
			$id = sanitize_title( (string) $id );
			if ( $id && in_array( $id, $allowed, true ) ) {
				$ids[] = $id;
			}
		}
	}

	$ids = array_values( array_unique( $ids ) );

	if ( $ids ) {
		update_post_meta( $post_id, 'stillframe_joined_sections', $ids );
	} else {
		delete_post_meta( $post_id, 'stillframe_joined_sections' );
	}
}
add_action( 'save_post', 'stillframe_save_section_joins_meta' );
/**
 * Register join-sections meta box on pages and projects that use glass lifts.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_join_sections_meta_box( $post_type, $post ) {
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( ! stillframe_page_uses_section_wrap( $post->ID ) ) {
		return;
	}

	add_meta_box(
		'stillframe_join_sections',
		__( 'Join sections', 'stillframe' ),
		'stillframe_render_join_sections_meta_box',
		$post_type,
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_join_sections_meta_box', 10, 2 );
