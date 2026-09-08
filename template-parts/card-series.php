<?php
/**
 * Series button / card for the gallery directory.
 *
 * @package Stillframe
 *
 * @var array $args {
 *     @type WP_Term $term  Series term.
 *     @type int     $index Card index for stagger.
 * }
 */

defined( 'ABSPATH' ) || exit;

$term  = isset( $args['term'] ) ? $args['term'] : null;
$index = isset( $args['index'] ) ? (int) $args['index'] : 0;

if ( ! $term instanceof WP_Term ) {
	return;
}

$photos = stillframe_series_preview_photos( $term->term_id, 3 );
$count  = (int) $term->count;
$url    = get_term_link( $term );
?>

<a
	class="series-card reveal"
	data-reveal
	data-stagger="<?php echo esc_attr( (string) min( $index, 8 ) ); ?>"
	href="<?php echo esc_url( $url ); ?>"
>
	<div class="series-card__frames" aria-hidden="true">
		<?php
		$frames = 0;
		foreach ( $photos as $photo ) {
			$frame = stillframe_get_card_image( $photo->ID, 'stillframe-card', 'series-card__image' );
			if ( ! $frame ) {
				continue;
			}
			echo $frame; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image()
			++$frames;
		}
		if ( ! $frames ) :
			?>
			<span class="series-card__empty"></span>
		<?php endif; ?>
	</div>
	<div class="series-card__body">
		<span class="series-card__title"><?php echo esc_html( $term->name ); ?></span>
		<span class="series-card__meta">
			<?php
			printf(
				/* translators: %s: number of photographs */
				esc_html( _n( '%s photo', '%s photos', $count, 'stillframe' ) ),
				esc_html( number_format_i18n( $count ) )
			);
			?>
		</span>
		<span class="series-card__arrow" aria-hidden="true">→</span>
	</div>
</a>
