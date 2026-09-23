<?php
/**
 * About timeline, dropdown, and related page helpers.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


/**
 * Page IDs chosen to appear under About in the header.
 *
 * @param int $about_id About page ID. Defaults to the About section page.
 * @return int[]
 */
function stillframe_about_dropdown_page_ids( $about_id = 0 ) {
	if ( ! $about_id ) {
		$about    = stillframe_get_section_page( 'about' );
		$about_id = $about instanceof WP_Post ? (int) $about->ID : 0;
	}

	$about_id = (int) $about_id;
	if ( ! $about_id ) {
		return array();
	}

	$raw = get_post_meta( $about_id, 'stillframe_about_dropdown_ids', true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$ids = array();
	foreach ( $raw as $id ) {
		$id = (int) $id;
		if ( $id && $id !== $about_id ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Page ID for an internal URL, or 0.
 *
 * @param string $url Link href.
 * @return int
 */
function stillframe_url_to_page_id( $url ) {
	$url = trim( html_entity_decode( (string) $url, ENT_QUOTES ) );
	if ( '' === $url || ! preg_match( '/^[^#]/', $url ) || preg_match( '#^(mailto:|tel:|javascript:)#i', $url ) ) {
		return 0;
	}

	$home_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$parts     = wp_parse_url( $url );
	$host      = isset( $parts['host'] ) ? (string) $parts['host'] : '';
	if ( $host && $home_host && 0 !== strcasecmp( $host, $home_host ) ) {
		return 0;
	}

	$path = isset( $parts['path'] ) ? (string) $parts['path'] : '';
	$abs  = $host ? preg_replace( '/#.*$/', '', $url ) : home_url( $path ? $path : '/' . ltrim( $url, '/' ) );
	$abs  = preg_replace( '/#.*$/', '', $abs );

	$post_id = url_to_postid( $abs );
	if ( $post_id ) {
		$post = get_post( $post_id );
		return ( $post instanceof WP_Post && 'page' === $post->post_type ) ? (int) $post_id : 0;
	}

	$home_path = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	$rel       = trim( (string) $path, '/' );
	if ( $home_path ) {
		if ( $rel === $home_path ) {
			$rel = '';
		} elseif ( 0 === strpos( $rel, $home_path . '/' ) ) {
			$rel = substr( $rel, strlen( $home_path ) + 1 );
		}
	}

	if ( $rel ) {
		$page = get_page_by_path( $rel );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return (int) $page->ID;
		}
	}

	$slug = $rel ? basename( untrailingslashit( $rel ) ) : '';
	if ( '' === $slug ) {
		return 0;
	}

	$page = get_page_by_path( $slug );
	return $page instanceof WP_Post ? (int) $page->ID : 0;
}

/**
 * Page IDs linked from About copy, in the order they appear.
 *
 * @param int $about_id About page ID.
 * @return int[]
 */
function stillframe_about_content_page_ids( $about_id ) {
	$about_id = (int) $about_id;
	$post     = get_post( $about_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	if ( ! preg_match_all( '/<a\s[^>]*href\s*=\s*([\'"])([^\'"]+)\1/i', (string) $post->post_content, $matches ) ) {
		return array();
	}

	$home_id = (int) get_option( 'page_on_front' );
	$ids     = array();
	foreach ( $matches[2] as $href ) {
		$page_id = stillframe_url_to_page_id( $href );
		if ( ! $page_id || $page_id === $about_id || $page_id === $home_id ) {
			continue;
		}
		$ids[] = $page_id;
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Put page IDs in the same order as links on About.
 *
 * @param int[] $ids      Page IDs.
 * @param int   $about_id About page ID.
 * @return int[]
 */
function stillframe_order_ids_like_about_links( $ids, $about_id ) {
	$ids   = array_values( array_unique( array_map( 'intval', $ids ) ) );
	$order = stillframe_about_content_page_ids( $about_id );
	if ( ! $ids || ! $order ) {
		return $ids;
	}

	$rank = array_flip( $order );
	$head = array();
	$tail = array();

	foreach ( $order as $id ) {
		if ( in_array( $id, $ids, true ) ) {
			$head[] = $id;
		}
	}

	foreach ( $ids as $id ) {
		if ( ! isset( $rank[ $id ] ) ) {
			$tail[] = $id;
		}
	}

	return array_values( array_unique( array_merge( $head, $tail ) ) );
}

/**
 * Page IDs in the same order as About timeline events.
 *
 * @param int $about_id About page ID.
 * @return int[]
 */
function stillframe_about_timeline_page_ids( $about_id ) {
	$about_id = (int) $about_id;
	if ( ! $about_id ) {
		return array();
	}

	$ids = array();
	foreach ( stillframe_about_timeline_data( $about_id )['events'] as $event ) {
		if ( empty( $event['page_id'] ) ) {
			continue;
		}
		$ids[] = (int) $event['page_id'];
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Put page IDs in the same order as About timeline events.
 *
 * @param int[] $ids      Page IDs.
 * @param int   $about_id About page ID.
 * @return int[]
 */
function stillframe_order_ids_like_about_timeline( $ids, $about_id ) {
	$ids   = array_values( array_unique( array_map( 'intval', $ids ) ) );
	$order = stillframe_about_timeline_page_ids( $about_id );
	if ( ! $ids || ! $order ) {
		return $ids;
	}

	$rank = array_flip( $order );
	$head = array();
	$tail = array();

	foreach ( $order as $id ) {
		if ( in_array( $id, $ids, true ) ) {
			$head[] = $id;
		}
	}

	foreach ( $ids as $id ) {
		if ( ! isset( $rank[ $id ] ) ) {
			$tail[] = $id;
		}
	}

	return array_values( array_unique( array_merge( $head, $tail ) ) );
}

/**
 * Whether this page is linked from About (dropdown, child page, or About copy).
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return bool
 */
function stillframe_is_about_subpage( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id ) {
		return false;
	}

	if ( stillframe_is_about_page( $page_id ) || stillframe_is_contact_page( $page_id ) || stillframe_is_home_page( $page_id ) ) {
		return false;
	}

	if ( stillframe_is_resume_page( $page_id ) ) {
		return true;
	}

	$about    = stillframe_get_section_page( 'about' );
	$about_id = $about instanceof WP_Post ? (int) $about->ID : 0;

	if ( $about_id && (int) wp_get_post_parent_id( $page_id ) === $about_id ) {
		return true;
	}

	if ( in_array( $page_id, stillframe_about_dropdown_page_ids( $about_id ), true ) ) {
		return true;
	}

	return $about_id && in_array( $page_id, stillframe_about_content_page_ids( $about_id ), true );
}

/**
 * Strip comments and links from a content fragment so it can sit inside a card link.
 *
 * @param string $html HTML.
 * @return string
 */
function stillframe_about_timeline_plain_html( $html ) {
	$html = preg_replace( '/<!--.*?-->/s', '', (string) $html );
	$html = preg_replace( '/<\/?a\b[^>]*>/i', '', (string) $html );

	return trim( (string) $html );
}

/**
 * Short copy from a page when a timeline section has no body of its own.
 *
 * @param int $page_id Page ID.
 * @return string
 */
function stillframe_about_timeline_page_excerpt( $page_id ) {
	$page_id = (int) $page_id;
	if ( ! $page_id ) {
		return '';
	}

	$excerpt = get_the_excerpt( $page_id );
	if ( is_string( $excerpt ) && '' !== trim( $excerpt ) ) {
		return wpautop( esc_html( trim( $excerpt ) ) );
	}

	$raw = wp_strip_all_tags( (string) get_post_field( 'post_content', $page_id ) );
	$raw = wp_trim_words( $raw, 48 );

	return $raw ? wpautop( esc_html( $raw ) ) : '';
}

/**
 * Pages the About timeline can point at: dropdown, children, and in-copy links.
 *
 * @param int $about_id About page ID.
 * @return int[]
 */
function stillframe_about_related_page_ids( $about_id ) {
	$about_id = (int) $about_id;
	$ids      = stillframe_about_dropdown_page_ids( $about_id );

	if ( $about_id ) {
		$children = get_pages(
			array(
				'parent'      => $about_id,
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
			)
		);
		foreach ( $children as $child ) {
			if ( $child instanceof WP_Post ) {
				$ids[] = (int) $child->ID;
			}
		}
	}

	$ids = array_merge( $ids, stillframe_about_content_page_ids( $about_id ) );
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	return stillframe_order_ids_like_about_links( $ids, $about_id );
}

/**
 * Collapse a title for loose About timeline matching.
 *
 * @param string $title Title.
 * @return string
 */
function stillframe_about_timeline_normalize_title( $title ) {
	$title = strtolower( html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' ) );
	$title = preg_replace( '/[()]/', ' ', $title );
	$title = str_replace( array( '&', '/', '+' ), ' ', $title );
	$title = preg_replace( '/[^a-z0-9]+/', ' ', $title );
	$title = preg_replace( '/\b(and|the|a|an|of|at|for|in|to|my|work)\b/', ' ', $title );

	return trim( preg_replace( '/\s+/', ' ', (string) $title ) );
}

/**
 * Whether two About titles refer to the same section.
 *
 * @param string $a Title.
 * @param string $b Title.
 * @return bool
 */
function stillframe_about_timeline_titles_match( $a, $b ) {
	$a = stillframe_about_timeline_normalize_title( $a );
	$b = stillframe_about_timeline_normalize_title( $b );
	if ( '' === $a || '' === $b ) {
		return false;
	}
	if ( $a === $b ) {
		return true;
	}
	if ( false !== strpos( $a, $b ) || false !== strpos( $b, $a ) ) {
		return true;
	}

	$stem = static function ( $word ) {
		return preg_replace( '/(ing|ment|tion|ence|ance|ed|er|es|s)$/', '', $word );
	};

	$wa      = array_values( array_filter( array_map( $stem, preg_split( '/\s+/', $a ) ) ) );
	$wb      = array_values( array_filter( array_map( $stem, preg_split( '/\s+/', $b ) ) ) );
	$overlap = array_values(
		array_filter(
			array_intersect( $wa, $wb ),
			static function ( $word ) {
				return strlen( (string) $word ) >= 4;
			}
		)
	);

	if ( ! $overlap ) {
		return false;
	}
	if ( count( $overlap ) >= 2 ) {
		return true;
	}

	return strlen( (string) $overlap[0] ) >= 6;
}

/**
 * Timeline card data from a related page.
 *
 * @param int $page_id Page ID.
 * @return array{id:string, title:string, html:string, url:string, page_id:int}|null
 */
function stillframe_about_timeline_event_from_page( $page_id ) {
	$page_id = (int) $page_id;
	$page    = get_post( $page_id );
	if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
		return null;
	}

	$url = get_permalink( $page );
	if ( ! $url ) {
		return null;
	}

	$title = get_the_title( $page );
	$id    = sanitize_title( $title );
	if ( '' === $id ) {
		$id = 'page-' . $page_id;
	}

	return array(
		'id'      => $id,
		'title'   => $title,
		'html'    => stillframe_about_timeline_page_excerpt( $page_id ),
		'url'     => $url,
		'page_id' => $page_id,
	);
}

/**
 * Page a timeline heading should open, from its section links or title.
 *
 * @param string $html     Section HTML, including the heading if it is a link.
 * @param string $title    Heading text.
 * @param int    $about_id About page ID.
 * @return int
 */
function stillframe_about_timeline_event_page_id( $html, $title, $about_id ) {
	$about_id = (int) $about_id;
	$home_id  = (int) get_option( 'page_on_front' );

	if ( preg_match_all( '/<a\s[^>]*href\s*=\s*([\'"])([^\'"]+)\1/i', (string) $html, $matches ) ) {
		foreach ( $matches[2] as $href ) {
			$page_id = stillframe_url_to_page_id( $href );
			if ( $page_id && $page_id !== $about_id && $page_id !== $home_id ) {
				return $page_id;
			}
		}
	}

	$title = trim( (string) $title );
	$slug  = sanitize_title( $title );
	if ( '' === $title ) {
		return 0;
	}

	if ( in_array( $slug, array( 'resume', 'cv' ), true ) ) {
		$resume = stillframe_get_section_page( 'resume' );
		if ( $resume instanceof WP_Post ) {
			return (int) $resume->ID;
		}
	}

	$bare_slug = sanitize_title( stillframe_about_timeline_normalize_title( $title ) );
	$paths     = array_values( array_unique( array_filter( array( $slug, $bare_slug ) ) ) );
	$about     = $about_id ? get_post( $about_id ) : null;
	if ( $about instanceof WP_Post && $about->post_name ) {
		foreach ( $paths as $path_slug ) {
			$paths[] = $about->post_name . '/' . $path_slug;
		}
		$paths = array_values( array_unique( $paths ) );
	}

	foreach ( $paths as $path ) {
		$by_path = get_page_by_path( $path );
		if ( $by_path instanceof WP_Post && (int) $by_path->ID !== $about_id && 'publish' === $by_path->post_status ) {
			return (int) $by_path->ID;
		}
	}

	foreach ( stillframe_about_related_page_ids( $about_id ) as $page_id ) {
		$page = get_post( $page_id );
		if ( ! $page instanceof WP_Post ) {
			continue;
		}

		$page_title = get_the_title( $page );
		if (
			strcasecmp( $page_title, $title ) === 0
			|| sanitize_title( $page_title ) === $slug
			|| sanitize_title( $page->post_name ) === $slug
			|| stillframe_about_timeline_titles_match( $page_title, $title )
			|| stillframe_about_timeline_titles_match( $page->post_name, $title )
		) {
			return (int) $page_id;
		}
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'title'                  => $title,
			'posts_per_page'         => 1,
			'post__not_in'           => array( $about_id ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( ! empty( $query->posts[0] ) && $query->posts[0] instanceof WP_Post ) {
		return (int) $query->posts[0]->ID;
	}

	return 0;
}

/**
 * Centered About timeline: intro copy plus linked heading events.
 *
 * @param int $post_id About page ID.
 * @return array{intro:string, events:array<int, array{id:string, title:string, html:string, url:string, side:string}>}
 */
function stillframe_about_timeline_data( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$empty   = array(
		'intro'  => '',
		'events' => array(),
	);

	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		$cache[ $post_id ] = $empty;
		return $empty;
	}

	$content = (string) $post->post_content;
	if ( '' === trim( $content ) ) {
		$cache[ $post_id ] = $empty;
		return $empty;
	}

	$parts = preg_split( '/(<h[2-4]\b[^>]*>.*?<\/h[2-4]>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) ) {
		$cache[ $post_id ] = $empty;
		return $empty;
	}

	$intro   = '';
	$pending = array();
	$current = null;
	$used    = array();

	foreach ( $parts as $part ) {
		if ( preg_match( '/<h([2-4])(\s[^>]*)?>(.*?)<\/h\1>/is', $part, $match ) ) {
			if ( is_array( $current ) ) {
				$pending[] = $current;
			}

			$title = trim( wp_strip_all_tags( $match[3] ) );
			$attrs = isset( $match[2] ) ? $match[2] : '';
			$id    = '';
			if ( preg_match( '/\sid\s*=\s*([\'"])([^\'"]+)\1/i', $attrs, $id_match ) ) {
				$id = sanitize_title( $id_match[2] );
			}
			if ( '' === $id ) {
				$id = sanitize_title( $title );
			}

			$base = $id;
			$n    = 2;
			while ( $id && isset( $used[ $id ] ) ) {
				$id = $base . '-' . $n;
				++$n;
			}
			if ( $id ) {
				$used[ $id ] = true;
			}

			$current = array(
				'id'      => $id,
				'title'   => $title,
				'html'    => '',
				'heading' => $part,
			);
			continue;
		}

		if ( is_array( $current ) ) {
			$current['html'] .= $part;
		} else {
			$intro .= $part;
		}
	}

	if ( is_array( $current ) ) {
		$pending[] = $current;
	}

	$events = array();
	$index  = 0;
	foreach ( $pending as $item ) {
		if ( '' === $item['title'] ) {
			continue;
		}

		$lookup  = ( isset( $item['heading'] ) ? $item['heading'] : '' ) . $item['html'];
		$page_id = stillframe_about_timeline_event_page_id( $lookup, $item['title'], $post_id );
		if ( ! $page_id ) {
			continue;
		}

		$url  = get_permalink( $page_id );
		$html = stillframe_about_timeline_plain_html( $item['html'] );
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			$html = stillframe_about_timeline_page_excerpt( $page_id );
		}
		if ( ! $url ) {
			continue;
		}

		$events[] = array(
			'id'      => $item['id'],
			'title'   => $item['title'],
			'html'    => $html,
			'url'     => $url,
			'page_id' => $page_id,
			'side'    => 0 === $index % 2 ? 'left' : 'right',
		);
		++$index;
	}

	$used_pages = array();
	foreach ( $events as $event ) {
		if ( ! empty( $event['page_id'] ) ) {
			$used_pages[] = (int) $event['page_id'];
		}
	}

	$missing_ids = stillframe_about_dropdown_page_ids( $post_id );
	if ( $missing_ids ) {
		$missing_ids = stillframe_order_ids_like_about_links( $missing_ids, $post_id );
	} elseif ( ! $events ) {
		$missing_ids = stillframe_about_content_page_ids( $post_id );
	} else {
		$missing_ids = array();
	}

	$link_rank = array_flip( stillframe_about_content_page_ids( $post_id ) );
	foreach ( $missing_ids as $page_id ) {
		$page_id = (int) $page_id;
		if ( ! $page_id || in_array( $page_id, $used_pages, true ) ) {
			continue;
		}

		$extra = stillframe_about_timeline_event_from_page( $page_id );
		if ( ! $extra ) {
			continue;
		}

		$base = $extra['id'];
		$n    = 2;
		while ( isset( $used[ $extra['id'] ] ) ) {
			$extra['id'] = $base . '-' . $n;
			++$n;
		}
		$used[ $extra['id'] ] = true;

		$insert_at = count( $events );
		if ( isset( $link_rank[ $page_id ] ) ) {
			$insert_at = 0;
			foreach ( $events as $i => $event ) {
				$eid = isset( $event['page_id'] ) ? (int) $event['page_id'] : 0;
				if ( isset( $link_rank[ $eid ] ) && $link_rank[ $eid ] < $link_rank[ $page_id ] ) {
					$insert_at = $i + 1;
				}
			}
		}

		array_splice( $events, $insert_at, 0, array( $extra ) );
		$used_pages[] = $page_id;
	}

	foreach ( $events as $i => $event ) {
		$events[ $i ]['side'] = 0 === $i % 2 ? 'left' : 'right';
	}

	$intro_html = stillframe_about_timeline_plain_html( $intro );
	$intro_out  = '';
	if ( '' !== trim( wp_strip_all_tags( $intro_html ) ) ) {
		$intro_out = apply_filters( 'the_content', $intro );
	}

	$result = array(
		'intro'  => $intro_out,
		'events' => $events,
	);
	$cache[ $post_id ] = $result;

	return $result;
}

/**
 * Pages that appear when hovering About in the header.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_about_dropdown_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_about_dropdown', 'stillframe_about_dropdown_nonce' );

	$selected = stillframe_about_dropdown_page_ids( (int) $post->ID );
	$pages    = get_pages(
		array(
			'sort_column' => 'menu_order,post_title',
			'exclude'     => array( (int) $post->ID, (int) get_option( 'page_on_front' ) ),
		)
	);
	?>
	<p><?php esc_html_e( 'These pages appear when someone hovers About in the header, in the same order as the links in this page. Leave them unchecked to use the linked pages automatically.', 'stillframe' ); ?></p>
	<?php if ( $pages ) : ?>
		<ul style="margin:0;padding:0;list-style:none;max-height:14rem;overflow:auto;">
			<?php foreach ( $pages as $page ) : ?>
				<li style="margin:0 0 0.35rem;">
					<label>
						<input
							type="checkbox"
							name="stillframe_about_dropdown_ids[]"
							value="<?php echo esc_attr( (string) $page->ID ); ?>"
							<?php checked( in_array( (int) $page->ID, $selected, true ) ); ?>
						/>
						<?php echo esc_html( get_the_title( $page ) ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Create other pages first, then they will show up here.', 'stillframe' ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Save About header dropdown page IDs.
 *
 * @param int $post_id Page ID.
 */
function stillframe_save_about_dropdown_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! stillframe_is_about_page( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_about_dropdown_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_about_dropdown_nonce'] ) ), 'stillframe_save_about_dropdown' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$ids = array();
	if ( isset( $_POST['stillframe_about_dropdown_ids'] ) && is_array( $_POST['stillframe_about_dropdown_ids'] ) ) {
		foreach ( wp_unslash( $_POST['stillframe_about_dropdown_ids'] ) as $id ) {
			$id = absint( $id );
			if ( $id && $id !== (int) $post_id && 'page' === get_post_type( $id ) ) {
				$ids[] = $id;
			}
		}
	}

	$ids = array_values( array_unique( $ids ) );

	if ( $ids ) {
		update_post_meta( $post_id, 'stillframe_about_dropdown_ids', $ids );
	} else {
		delete_post_meta( $post_id, 'stillframe_about_dropdown_ids' );
	}
}
add_action( 'save_post', 'stillframe_save_about_dropdown_meta' );
/**
 * Register About dropdown meta box.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_about_meta_boxes( $post_type, $post ) {
	if ( 'page' !== $post_type || ! $post instanceof WP_Post ) {
		return;
	}

	if ( ! stillframe_is_about_page( $post->ID ) ) {
		return;
	}

	add_meta_box(
		'stillframe_about_dropdown',
		__( 'About dropdown', 'stillframe' ),
		'stillframe_render_about_dropdown_meta_box',
		'page',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_about_meta_boxes', 10, 2 );
