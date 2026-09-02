<?php
/**
 * Single photo series — all photographs in that series.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$term = get_queried_object();
?>

<main id="content" class="site-main">
	<div class="page-glass">
	<div class="page-shell page-shell--wide">
		<header class="archive-header reveal" data-reveal>
			<h1 class="archive-header__title"><?php echo esc_html( $term instanceof WP_Term ? $term->name : get_the_archive_title() ); ?></h1>
			<?php if ( $term instanceof WP_Term && $term->description ) : ?>
				<p class="archive-header__lede"><?php echo esc_html( $term->description ); ?></p>
			<?php endif; ?>
			<p class="archive-header__back">
				<a class="text-link" href="<?php echo esc_url( get_post_type_archive_link( 'photograph' ) ); ?>">
					← <?php esc_html_e( 'All photographs', 'stillframe' ); ?>
				</a>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>
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
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
	</div>
</main>

<?php
get_footer();
