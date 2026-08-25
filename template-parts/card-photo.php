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
$location = get_post_meta( get_the_ID(), 'stillframe_location', true );
$tall     = 0 === $index % 3;
?>

<article <?php post_class( 'photo-card' ); ?>>
	<a class="photo-card__link" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="photo-card__media <?php echo $tall ? 'photo-card__media--tall' : ''; ?>">
				<?php
				the_post_thumbnail(
					'stillframe-gallery',
					array(
						'class'    => 'photo-card__image',
						'loading'  => 'eager',
						'alt'      => the_title_attribute( array( 'echo' => false ) ),
					)
				);
				?>
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
