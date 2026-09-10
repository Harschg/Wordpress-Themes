<?php
/**
 * Photograph card for the gallery grid.
 *
 * @package Stillframe
 *
 * @var array $args {
 *     @type int $index Card index for stagger.
 * }
 */

defined( 'ABSPATH' ) || exit;

$index    = isset( $args['index'] ) ? (int) $args['index'] : 0;
$arch     = ! empty( $args['arch'] );
$location = get_post_meta( get_the_ID(), 'stillframe_location', true );
$tall     = 0 === $index % 3;
$classes  = 'photo-card';
if ( $arch ) {
	$classes .= ' photo-card--arch reveal';
}
?>

<article <?php post_class( $classes ); ?><?php echo $arch ? ' data-reveal' : ''; ?>>
	<a class="photo-card__link" href="<?php the_permalink(); ?>">
		<?php
		$thumb = stillframe_get_card_image( get_the_ID(), 'stillframe-gallery', 'photo-card__image' );
		if ( $thumb ) :
			?>
			<div class="photo-card__media <?php echo $tall ? 'photo-card__media--tall' : ''; ?>">
				<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() ?>
			</div>
		<?php else : ?>
			<div class="photo-card__media photo-card__media--empty"></div>
		<?php endif; ?>
		<div class="photo-card__caption">
			<h2 class="photo-card__title"><?php the_title(); ?></h2>
			<?php if ( $location ) : ?>
				<p class="photo-card__meta"><?php echo esc_html( $location ); ?></p>
			<?php endif; ?>
		</div>
	</a>
</article>
