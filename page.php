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
		<article <?php post_class( 'page-shell page-article' ); ?>>
			<header class="page-header reveal" data-reveal>
				<h1 class="page-header__title"><?php the_title(); ?></h1>
			</header>
			<div class="prose reveal" data-reveal>
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
