<?php
/**
 * Sticky "On this page" contents list.
 *
 * @package Stillframe
 *
 * @var array $args {
 *     @type array<int, array{id:string, title:string, level:int}> $items TOC entries.
 * }
 */

defined( 'ABSPATH' ) || exit;

$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();
if ( ! $items ) {
	return;
}
?>
<nav class="about-toc" data-about-toc aria-label="<?php esc_attr_e( 'On this page', 'stillframe' ); ?>">
	<p class="about-toc__label"><?php esc_html_e( 'On this page', 'stillframe' ); ?></p>
	<ol>
		<?php foreach ( $items as $item ) : ?>
			<li class="about-toc__item about-toc__item--h<?php echo esc_attr( (string) $item['level'] ); ?>">
				<a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
