<?php
/**
 * Project card.
 *
 * @package Stillframe
 *
 * @var array $args {
 *     @type int $index Card index for stagger.
 * }
 */

defined( 'ABSPATH' ) || exit;

$index = isset( $args['index'] ) ? (int) $args['index'] : 0;
$stack = stillframe_project_stack( get_the_ID() );
?>

<article <?php post_class( 'project-card reveal' ); ?> data-reveal data-stagger="<?php echo esc_attr( (string) min( $index, 8 ) ); ?>">
	<a class="project-card__link" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="project-card__media">
				<?php
				the_post_thumbnail(
					'stillframe-card',
					array(
						'class'   => 'project-card__image',
						'loading' => 'lazy',
						'alt'     => the_title_attribute( array( 'echo' => false ) ),
					)
				);
				?>
			</div>
		<?php endif; ?>
		<div class="project-card__body">
			<h2 class="project-card__title"><?php the_title(); ?></h2>
			<?php if ( has_excerpt() ) : ?>
				<p class="project-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<?php if ( $stack ) : ?>
				<ul class="stack-list stack-list--compact">
					<?php foreach ( array_slice( $stack, 0, 4 ) as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<span class="project-card__cta"><?php esc_html_e( 'View project', 'stillframe' ); ?> →</span>
		</div>
	</a>
</article>
