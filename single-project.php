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
?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		$toc = stillframe_project_toc_items( get_the_ID() );
		?>
		<div class="page-glass">
			<article <?php post_class( 'project-single' ); ?>>
				<header class="project-single__header">
					<h1 class="archive-header__title reveal" data-reveal><?php the_title(); ?></h1>

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

				<?php if ( $toc ) : ?>
					<nav class="about-toc" data-about-toc aria-label="<?php esc_attr_e( 'On this page', 'stillframe' ); ?>">
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

				<?php if ( get_post() && '' !== trim( (string) get_post()->post_content ) ) : ?>
					<div class="prose project-single__intro is-awaiting-reveal">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php
				get_template_part(
					'template-parts/project',
					'features',
					array(
						'post_id' => get_the_ID(),
					)
				);
				?>
			</article>
		</div>

		<div class="project-stage" data-project-stage hidden>
			<button type="button" class="project-stage__close" data-stage-close>
				<?php esc_html_e( 'Close', 'stillframe' ); ?>
			</button>
			<button type="button" class="project-stage__nav project-stage__nav--prev" data-stage-prev aria-label="<?php esc_attr_e( 'Previous photo', 'stillframe' ); ?>">
				<span aria-hidden="true">←</span>
			</button>
			<img class="project-stage__image" data-stage-image alt="" />
			<button type="button" class="project-stage__nav project-stage__nav--next" data-stage-next aria-label="<?php esc_attr_e( 'Next photo', 'stillframe' ); ?>">
				<span aria-hidden="true">→</span>
			</button>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer();
