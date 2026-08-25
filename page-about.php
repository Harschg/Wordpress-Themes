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

			<?php
			$resume_url = stillframe_resume_url( get_the_ID() );
			if ( $resume_url ) :
				?>
				<a class="resume-scroll reveal" data-reveal href="<?php echo esc_url( $resume_url ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="resume-scroll__roll resume-scroll__roll--top" aria-hidden="true"></span>
					<span class="resume-scroll__sheet">
						<span class="resume-scroll__seal" aria-hidden="true"></span>
						<span class="resume-scroll__title"><?php esc_html_e( 'Resume', 'stillframe' ); ?></span>
						<span class="resume-scroll__hint"><?php esc_html_e( 'Open PDF', 'stillframe' ); ?></span>
					</span>
					<span class="resume-scroll__roll resume-scroll__roll--bottom" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
