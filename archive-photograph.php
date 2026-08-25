<?php
/**
 * Photograph archive — gallery.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main">
	<div class="page-shell page-shell--wide">
		<header class="archive-header reveal" data-reveal>
			<p class="page-header__kicker"><?php esc_html_e( 'Gallery', 'stillframe' ); ?></p>
			<h1 class="archive-header__title"><?php esc_html_e( 'Photographs', 'stillframe' ); ?></h1>
			<p class="archive-header__lede"><?php esc_html_e( 'Click a frame to sit with it for a second.', 'stillframe' ); ?></p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="gallery-grid" data-lightbox-root>
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
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
