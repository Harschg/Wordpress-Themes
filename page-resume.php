<?php
/**
 * Resume page — uploaded PDF or photo of the resume.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="page-glass">
			<article <?php post_class( 'resume-page' ); ?>>
				<h1 class="archive-header__title reveal" data-reveal><?php the_title(); ?></h1>
				<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
					<div class="prose reveal" data-reveal>
						<?php the_content(); ?>
					</div>
				<?php endif; ?>
				<?php get_template_part( 'template-parts/resume' ); ?>
			</article>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer();
