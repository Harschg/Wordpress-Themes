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
		?>
		<article <?php post_class( 'about' ); ?>>
			<div class="about__grid">
				<div class="about__copy">
					<h1 class="page-header__title reveal" data-reveal><?php the_title(); ?></h1>
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
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
