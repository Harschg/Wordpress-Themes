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
		<header class="page-masthead reveal" data-reveal>
			<?php
			get_template_part(
				'template-parts/page-masthead',
				'',
				array(
					'title'      => $term instanceof WP_Term ? $term->name : get_the_archive_title(),
					'kicker'     => __( 'Gallery', 'stillframe' ),
					'kicker_url' => get_post_type_archive_link( 'photograph' ),
					'lede'       => $term instanceof WP_Term ? (string) $term->description : '',
				)
			);
			?>
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
							'arch'  => true,
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
