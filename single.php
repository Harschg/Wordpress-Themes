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
		<div class="page-glass">
		<article <?php post_class( 'page-shell page-article' ); ?>>
			<header class="page-header reveal" data-reveal>
				<h1 class="page-header__title"><?php the_title(); ?></h1>
				<p class="page-header__date"><?php echo esc_html( get_the_date() ); ?></p>
			</header>
			<?php
			$hero = stillframe_post_img( get_the_ID(), 'page-article__image' );
			if ( $hero ) :
				?>
				<figure class="page-article__hero reveal" data-reveal>
					<?php echo $hero; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- stillframe_post_img() ?>
				</figure>
			<?php endif; ?>
			<div class="prose reveal" data-reveal>
				<?php the_content(); ?>
			</div>
		</article>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer();
