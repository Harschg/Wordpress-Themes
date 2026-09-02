<?php
/**
 * About page (used when the page slug is "about").
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
			<article <?php post_class( 'about' ); ?>>
				<header class="about__top">
					<h1 class="archive-header__title reveal" data-reveal><?php the_title(); ?></h1>
				</header>
				<?php if ( $toc ) : ?>
					<nav class="about-toc reveal" data-about-toc data-reveal aria-label="<?php esc_attr_e( 'On this page', 'stillframe' ); ?>">
						<p class="about-toc__label"><?php esc_html_e( 'On this page', 'stillframe' ); ?></p>
						<ol>
							<?php foreach ( $toc as $item ) : ?>
								<li class="about-toc__item about-toc__item--h<?php echo esc_attr( (string) $item['level'] ); ?>">
									<a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ol>
					</nav>
				<?php endif; ?>
				<div class="about__body">
					<div class="about__grid">
						<div class="about__copy">
							<div class="prose reveal" data-reveal>
								<?php the_content(); ?>
							</div>
						</div>
						<?php if ( has_post_thumbnail() ) : ?>
							<figure class="about__portrait reveal" data-reveal>
								<?php the_post_thumbnail( 'stillframe-hero', array( 'class' => 'about__image' ) ); ?>
							</figure>
						<?php endif; ?>
					</div>

					<?php get_template_part( 'template-parts/resume' ); ?>
				</div>
			</article>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer();
