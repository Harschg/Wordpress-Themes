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
	<figure class="projects-hero">
		<img
			src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/projects-hero.jpg' ); ?>"
			alt=""
			width="1600"
			height="900"
		/>
		<figcaption class="projects-hero__caption">
			<h1 class="archive-header__title"><?php esc_html_e( 'Projects', 'stillframe' ); ?></h1>
		</figcaption>
	</figure>

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
