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
		$timeline = stillframe_about_timeline_data( get_the_ID() );
		$events   = $timeline['events'];
		$intro    = $timeline['intro'];
		$toc      = array();
		if ( $events ) {
			foreach ( $events as $event ) {
				$toc[] = array(
					'id'    => $event['id'],
					'title' => $event['title'],
					'level' => 2,
				);
			}
		} else {
			$toc = stillframe_about_toc_items( get_the_ID() );
		}
		$portrait = stillframe_attachment_img(
			(int) get_post_thumbnail_id(),
			array(
				'class'         => 'about__image',
				'alt'           => get_the_title(),
				'fetchpriority' => 'high',
			)
		);
		?>
		<div class="page-glass">
			<article <?php post_class( $events ? 'about about--timeline' : 'about' ); ?>>
				<header class="about__top">
					<h1 class="archive-header__title reveal" data-reveal><?php the_title(); ?></h1>
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
				<div class="about__body">
					<?php if ( $portrait ) : ?>
						<figure class="about__portrait reveal" data-reveal>
							<?php echo $portrait; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- stillframe_attachment_img() ?>
						</figure>
					<?php endif; ?>
					<?php if ( $events ) : ?>
						<?php if ( $intro ) : ?>
							<div class="about__intro prose reveal" data-reveal>
								<?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content ?>
							</div>
						<?php endif; ?>
						<div class="about-timeline" data-about-timeline>
							<div class="about-timeline__rail" aria-hidden="true">
								<span class="about-timeline__fill"></span>
							</div>
							<?php foreach ( $events as $event ) : ?>
								<article
									id="<?php echo esc_attr( $event['id'] ); ?>"
									class="about-timeline__event about-timeline__event--<?php echo esc_attr( $event['side'] ); ?> reveal reveal--from-<?php echo esc_attr( $event['side'] ); ?>"
									data-reveal
								>
									<span class="about-timeline__dot" aria-hidden="true"></span>
									<div class="about-timeline__side">
										<h2 class="about-timeline__heading">
											<button
												type="button"
												class="about-timeline__topic"
												aria-expanded="false"
												aria-controls="<?php echo esc_attr( $event['id'] ); ?>-card"
											>
												<?php if ( 'left' === $event['side'] ) : ?>
													<span class="about-timeline__chevron" aria-hidden="true">
														<svg viewBox="0 0 16 16" width="16" height="16" focusable="false">
															<path d="M3.2 5.6 8 10.4l4.8-4.8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
														</svg>
													</span>
												<?php endif; ?>
												<span class="about-timeline__topic-label"><?php echo esc_html( $event['title'] ); ?></span>
												<?php if ( 'left' !== $event['side'] ) : ?>
													<span class="about-timeline__chevron" aria-hidden="true">
														<svg viewBox="0 0 16 16" width="16" height="16" focusable="false">
															<path d="M3.2 5.6 8 10.4l4.8-4.8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
														</svg>
													</span>
												<?php endif; ?>
											</button>
										</h2>
										<a
											id="<?php echo esc_attr( $event['id'] ); ?>-card"
											class="about-timeline__card"
											href="<?php echo esc_url( $event['url'] ); ?>"
											inert
										>
											<div class="about-timeline__card-clip">
												<div class="about-timeline__card-body">
													<?php echo wp_kses_post( $event['html'] ); ?>
												</div>
												<div class="about-timeline__card-cta"><?php esc_html_e( 'Open page', 'stillframe' ); ?></div>
											</div>
										</a>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="about__copy">
							<div class="prose is-awaiting-reveal">
								<?php the_content(); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</article>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer();
