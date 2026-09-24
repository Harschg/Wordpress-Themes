<?php
/**
 * Individual project — case study layout.
 *
 * Loaded via single-project.php so WordPress hierarchy still resolves
 * the `project` post type to this template.
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
				<header class="project-single__header page-masthead reveal" data-reveal>
					<?php
					get_template_part(
						'template-parts/page-masthead',
						'',
						array(
							'kicker'     => __( 'Projects', 'stillframe' ),
							'kicker_url' => get_post_type_archive_link( 'project' ),
						)
					);
					?>

					<?php if ( has_excerpt() ) : ?>
						<p class="page-masthead__lede"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>

					<?php if ( $stack ) : ?>
						<ul class="stack-list">
							<?php foreach ( $stack as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( $live || $github ) : ?>
						<div class="project-single__actions">
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
					<?php endif; ?>
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
						<?php
						echo stillframe_wrap_content_sections( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content
							apply_filters( 'the_content', get_post()->post_content ),
							3,
							3,
							get_the_ID()
						);
						?>
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
