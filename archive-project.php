<?php
/**
 * Project archive.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main">
	<?php
	get_template_part(
		'template-parts/section-hero',
		null,
		array(
			'section' => 'projects',
			'title'   => __( 'Projects', 'stillframe' ),
		)
	);
	?>

	<div class="page-shell">
		<?php if ( have_posts() ) : ?>
			<div class="project-grid">
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					get_template_part(
						'template-parts/card',
						'project',
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
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
