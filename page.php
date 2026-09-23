<?php
/**
 * Generic page.
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
		<article <?php post_class( 'page-shell page-article' ); ?>>
			<header class="page-masthead reveal" data-reveal>
				<?php
				get_template_part(
					'template-parts/page-masthead',
					'',
					array(
						'title' => get_the_title(),
					)
				);
				?>
			</header>
			<div class="prose reveal" data-reveal>
				<?php the_content(); ?>
			</div>
		</article>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer();
