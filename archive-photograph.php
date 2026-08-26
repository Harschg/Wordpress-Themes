<?php
/**
 * Photograph archive — gallery, with a series directory when series exist.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$series = stillframe_photo_series_terms();
?>

<main id="content" class="site-main">
	<?php
	get_template_part(
		'template-parts/section-hero',
		null,
		array(
			'section' => 'gallery',
			'title'   => __( 'Gallery', 'stillframe' ),
		)
	);
	?>
	<div class="page-shell page-shell--wide">

		<?php if ( $series ) : ?>
			<section class="series-directory" aria-labelledby="series-heading">
				<h2 id="series-heading" class="section-title reveal" data-reveal><?php esc_html_e( 'Series', 'stillframe' ); ?></h2>
				<div class="series-grid">
					<?php foreach ( $series as $index => $term ) : ?>
						<?php
						get_template_part(
							'template-parts/card',
							'series',
							array(
								'term'  => $term,
								'index' => $index,
							)
						);
						?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<section class="gallery-loose" aria-labelledby="frames-heading">
				<?php if ( $series ) : ?>
					<h2 id="frames-heading" class="section-title reveal" data-reveal><?php esc_html_e( 'Other photographs', 'stillframe' ); ?></h2>
				<?php else : ?>
					<h2 id="frames-heading" class="screen-reader-text"><?php esc_html_e( 'Photographs', 'stillframe' ); ?></h2>
				<?php endif; ?>
				<div class="gallery-grid">
					<?php
					$i = 0;
					while ( have_posts() ) :
						the_post();
						get_template_part(
							'template-parts/card',
							'photo',
							array(
								'index' => $i,
							)
						);
						++$i;
					endwhile;
					?>
				</div>
				<div class="pagination-wrap">
					<?php the_posts_pagination(); ?>
				</div>
			</section>
		<?php elseif ( ! $series ) : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
