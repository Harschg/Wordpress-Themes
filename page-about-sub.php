<?php
/**
 * About subpage — same contents list as About.
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
		$toc = stillframe_about_toc_items( get_the_ID() );
		?>
		<div class="page-glass">
			<article <?php post_class( 'about about-subpage' ); ?>>
				<header class="about__top page-masthead reveal" data-reveal>
					<?php
					get_template_part(
						'template-parts/page-masthead',
						'',
						array(
							'kicker'     => __( 'About', 'stillframe' ),
							'kicker_url' => stillframe_page_url( 'about' ),
						)
					);
					?>
				</header>
				<?php
				get_template_part(
					'template-parts/about-toc',
					'',
					array(
						'items' => $toc,
					)
				);
				?>
				<div class="about__body">
					<div class="about__copy">
						<div class="prose is-awaiting-reveal">
							<?php
							echo stillframe_wrap_content_sections( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content
								apply_filters( 'the_content', get_post()->post_content ),
								2,
								3,
								get_the_ID()
							);
							?>
						</div>
					</div>
				</div>
			</article>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer();
