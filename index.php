<?php
/**
 * Fallback template.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main">
	<div class="page-shell">
		<?php if ( have_posts() ) : ?>
			<header class="archive-header reveal" data-reveal>
				<h1 class="archive-header__title"><?php esc_html_e( 'Journal', 'stillframe' ); ?></h1>
			</header>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'post-tease reveal' ); ?> data-reveal>
						<h2 class="post-tease__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<p class="post-tease__meta"><?php echo esc_html( get_the_date() ); ?></p>
						<div class="post-tease__excerpt"><?php the_excerpt(); ?></div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
