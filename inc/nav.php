<?php
/**
 * Primary navigation and dropdown menus.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


/**
 * Primary navigation items.
 *
 * @return array<int, array{key: string, url: string, label: string}>
 */
function stillframe_nav_items() {
	return array(
		array(
			'key'   => 'about',
			'url'   => stillframe_page_url( 'about' ),
			'label' => __( 'About', 'stillframe' ),
		),
		array(
			'key'   => 'gallery',
			'url'   => get_post_type_archive_link( 'photograph' ),
			'label' => __( 'Gallery', 'stillframe' ),
		),
		array(
			'key'   => 'projects',
			'url'   => get_post_type_archive_link( 'project' ),
			'label' => __( 'Projects', 'stillframe' ),
		),
		array(
			'key'   => 'contact',
			'url'   => stillframe_page_url( 'contact' ),
			'label' => __( 'Contact', 'stillframe' ),
		),
	);
}

/**
 * Whether a primary nav item matches the current request.
 *
 * @param string $key Nav item key.
 * @return bool
 */
function stillframe_nav_item_is_current( $key ) {
	switch ( $key ) {
		case 'home':
			return is_front_page();
		case 'about':
			if ( is_front_page() || ! is_page() ) {
				return false;
			}

			$page_id = (int) get_queried_object_id();
			if ( stillframe_is_about_page( $page_id ) ) {
				return true;
			}

			$about = stillframe_get_section_page( 'about' );
			if ( $about instanceof WP_Post && $page_id === (int) $about->ID ) {
				return true;
			}

			if ( $about instanceof WP_Post && wp_get_post_parent_id( $page_id ) === (int) $about->ID ) {
				return true;
			}

			return in_array( $page_id, stillframe_about_dropdown_page_ids(), true );
		case 'gallery':
			return is_post_type_archive( 'photograph' ) || is_tax( 'photo_series' ) || is_singular( 'photograph' );
		case 'projects':
			return is_post_type_archive( 'project' ) || is_tax( 'project_type' ) || is_singular( 'project' );
		case 'contact':
			if ( is_front_page() || ! is_page() ) {
				return false;
			}

			return stillframe_is_contact_page( (int) get_queried_object_id() );
		default:
			return false;
	}
}

/**
 * Links in the About header dropdown.
 *
 * Uses pages picked on the About screen, then child pages, then on-page headings.
 * Chosen pages follow the order of events on the About timeline.
 *
 * @return array<int, array{url:string, label:string, current:bool}>
 */
function stillframe_about_dropdown_items() {
	$about = stillframe_get_section_page( 'about' );
	if ( ! $about instanceof WP_Post ) {
		return array();
	}

	$about_id = (int) $about->ID;
	$items    = array();
	$ids      = stillframe_about_dropdown_page_ids( $about_id );
	$linked   = stillframe_about_content_page_ids( $about_id );
	$timeline = stillframe_about_timeline_page_ids( $about_id );

	if ( $ids && $timeline ) {
		$ids = stillframe_order_ids_like_about_timeline( $ids, $about_id );
	} elseif ( $ids && $linked ) {
		$ids = stillframe_order_ids_like_about_links( $ids, $about_id );
	} elseif ( ! $ids && $timeline ) {
		$ids = $timeline;
	} elseif ( ! $ids && $linked ) {
		$ids = $linked;
	}

	if ( $ids ) {
		foreach ( $ids as $page_id ) {
			$page = get_post( $page_id );
			if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
				continue;
			}

			$url = get_permalink( $page );
			if ( ! $url ) {
				continue;
			}

			$items[] = array(
				'url'     => $url,
				'label'   => get_the_title( $page ),
				'current' => is_page( $page_id ),
			);
		}

		return $items;
	}

	$children = get_pages(
		array(
			'parent'      => $about_id,
			'sort_column' => 'menu_order,post_title',
		)
	);

	if ( $children ) {
		foreach ( $children as $page ) {
			$url = get_permalink( $page );
			if ( ! $url ) {
				continue;
			}

			$items[] = array(
				'url'     => $url,
				'label'   => get_the_title( $page ),
				'current' => is_page( (int) $page->ID ),
			);
		}

		if ( $items ) {
			return $items;
		}
	}

	$about_url = (string) get_permalink( $about );
	foreach ( stillframe_about_toc_items( $about_id ) as $heading ) {
		$items[] = array(
			'url'     => $about_url . '#' . $heading['id'],
			'label'   => $heading['title'],
			'current' => false,
		);
	}

	return $items;
}

/**
 * Links in the Projects header dropdown.
 *
 * @return array<int, array{url:string, label:string, current:bool}>
 */
function stillframe_projects_dropdown_items() {
	$query = new WP_Query(
		array(
			'post_type'              => 'project',
			'post_status'            => 'publish',
			'posts_per_page'         => 24,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$items     = array();
	$current_id = is_singular( 'project' ) ? (int) get_queried_object_id() : 0;

	foreach ( $query->posts as $project ) {
		$url = get_permalink( $project );
		if ( ! $url ) {
			continue;
		}

		$items[] = array(
			'url'     => $url,
			'label'   => get_the_title( $project ),
			'current' => $current_id === (int) $project->ID,
		);
	}

	return $items;
}

/**
 * Dropdown links for a primary nav item, if any.
 *
 * @param string $key Nav item key.
 * @return array<int, array{url:string, label:string, current:bool}>
 */
function stillframe_nav_dropdown_items( $key ) {
	if ( 'about' === $key ) {
		return stillframe_about_dropdown_items();
	}

	if ( 'projects' === $key ) {
		return stillframe_projects_dropdown_items();
	}

	return array();
}

/**
 * Fallback menu when no menu is assigned in Appearance → Menus.
 */
function stillframe_fallback_menu() {
	echo '<ul class="nav-list">';

	foreach ( stillframe_nav_items() as $item ) {
		if ( empty( $item['url'] ) ) {
			continue;
		}

		$current  = stillframe_nav_item_is_current( $item['key'] );
		$children = stillframe_nav_dropdown_items( $item['key'] );

		if ( $children ) {
			$child_current = false;
			foreach ( $children as $child ) {
				if ( ! empty( $child['current'] ) ) {
					$child_current = true;
					break;
				}
			}

			$li_class = 'nav-item nav-item--drop';
			if ( $current || $child_current ) {
				$li_class .= ' is-current';
			}

			$toggle_label = sprintf(
				/* translators: %s: nav item label, e.g. About or Projects. */
				__( '%s menu', 'stillframe' ),
				$item['label']
			);

			echo '<li class="' . esc_attr( $li_class ) . '">';
			echo '<div class="nav-item__hit">';
			printf(
				'<a class="nav-link" href="%1$s"%2$s>%3$s</a>',
				esc_url( $item['url'] ),
				$current && ! $child_current ? ' aria-current="page"' : '',
				esc_html( $item['label'] )
			);
			echo '<button type="button" class="nav-drop__toggle" aria-expanded="false" aria-label="' . esc_attr( $toggle_label ) . '"><span aria-hidden="true"></span></button>';
			echo '</div>';
			echo '<ul class="nav-sub">';

			foreach ( $children as $child ) {
				printf(
					'<li class="%1$s"><a href="%2$s"%3$s>%4$s</a></li>',
					! empty( $child['current'] ) ? 'is-current' : '',
					esc_url( $child['url'] ),
					! empty( $child['current'] ) ? ' aria-current="page"' : '',
					esc_html( $child['label'] )
				);
			}

			echo '</ul>';
			echo '</li>';
			continue;
		}

		printf(
			'<li class="%1$s"><a class="nav-link" href="%2$s"%3$s>%4$s</a></li>',
			$current ? 'is-current' : '',
			esc_url( $item['url'] ),
			$current ? ' aria-current="page"' : '',
			esc_html( $item['label'] )
		);
	}

	echo '</ul>';
}
