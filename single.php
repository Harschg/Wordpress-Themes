<?php
/**
 * Single blog post fallback.
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
		<article <?php post_class( 'page-shell page-article' ); ?>>
			<header class="page-header reveal" data-reveal>
				<p class="page-header__kicker"><?php echo esc_html( get_the_date() ); ?></p>
				<h1 class="page-header__title"><?php the_title(); ?></h1>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="page-article__hero reveal" data-reveal>
					<?php the_post_thumbnail( 'stillframe-hero' ); ?>
				</figure>
			<?php endif; ?>
			<div class="prose reveal" data-reveal>
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
