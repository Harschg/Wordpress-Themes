<?php
/**
 * Single project — case study layout.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$github = get_post_meta( get_the_ID(), 'stillframe_github', true );
$live   = get_post_meta( get_the_ID(), 'stillframe_live_url', true );
$stack  = stillframe_project_stack( get_the_ID() );
$face = get_the_post_thumbnail_url( get_the_ID(), 'stillframe-hero' );
if ( ! $face ) {
	$face = get_the_post_thumbnail_url( get_the_ID(), 'full' );
}
?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();

		get_template_part(
			'template-parts/section-hero',
			null,
			array(
				'title' => get_the_title(),
				'image' => $face ? $face : '',
			)
		);
		?>
		<article <?php post_class( 'project-single page-shell' ); ?>>
			<header class="project-single__header">
				<?php if ( has_excerpt() ) : ?>
					<p class="project-single__lede reveal" data-reveal><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<?php if ( $stack ) : ?>
					<ul class="stack-list reveal" data-reveal>
						<?php foreach ( $stack as $item ) : ?>
							<li><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="project-single__actions reveal" data-reveal>
					<?php if ( $live ) : ?>
						<a class="btn" href="<?php echo esc_url( $live ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Live site', 'stillframe' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $github ) : ?>
						<a class="btn btn--ghost" href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'GitHub', 'stillframe' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</header>

			<div class="prose reveal" data-reveal>
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
