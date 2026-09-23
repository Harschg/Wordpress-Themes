<?php
/**
 * Single photograph.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$location  = get_post_meta( get_the_ID(), 'stillframe_location', true );
$camera    = get_post_meta( get_the_ID(), 'stillframe_camera', true );
$year      = get_post_meta( get_the_ID(), 'stillframe_year', true );
$series    = get_the_terms( get_the_ID(), 'photo_series' );
$series    = ( ! is_wp_error( $series ) && $series ) ? $series : array();
$neighbors = stillframe_photograph_neighbors( get_the_ID() );
?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'photo-single' ); ?>>
			<figure class="photo-single__frame">
				<?php
				$photo = stillframe_post_img(
					get_the_ID(),
					'photo-single__image',
					array(
						'fetchpriority' => 'high',
					)
				);
				if ( $photo ) :
					echo $photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- stillframe_post_img()
				endif;
				?>

				<?php if ( $neighbors['prev'] || $neighbors['next'] ) : ?>
					<nav class="photo-arrows" aria-label="<?php esc_attr_e( 'Photos', 'stillframe' ); ?>">
						<?php if ( $neighbors['prev'] ) : ?>
							<a class="photo-arrow photo-arrow--prev" href="<?php echo esc_url( get_permalink( $neighbors['prev'] ) ); ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Previous photo', 'stillframe' ); ?></span>
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
									<path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</a>
						<?php endif; ?>
						<?php if ( $neighbors['next'] ) : ?>
							<a class="photo-arrow photo-arrow--next" href="<?php echo esc_url( get_permalink( $neighbors['next'] ) ); ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Next photo', 'stillframe' ); ?></span>
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
									<path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			</figure>

			<div class="photo-single__meta page-shell">
				<header class="page-masthead reveal" data-reveal>
					<?php
					$photo_kicker     = __( 'Gallery', 'stillframe' );
					$photo_kicker_url = get_post_type_archive_link( 'photograph' );
					if ( $series && ! is_wp_error( $series ) && isset( $series[0] ) && $series[0] instanceof WP_Term ) {
						$photo_kicker     = $series[0]->name;
						$photo_kicker_url = get_term_link( $series[0] );
						if ( is_wp_error( $photo_kicker_url ) ) {
							$photo_kicker_url = get_post_type_archive_link( 'photograph' );
						}
					}
					get_template_part(
						'template-parts/page-masthead',
						'',
						array(
							'title'      => get_the_title(),
							'kicker'     => $photo_kicker,
							'kicker_url' => $photo_kicker_url,
						)
					);
					?>
				</header>

				<dl class="meta-list reveal" data-reveal>
					<?php if ( $location ) : ?>
						<div>
							<dt><?php esc_html_e( 'Location', 'stillframe' ); ?></dt>
							<dd><?php echo esc_html( $location ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $camera ) : ?>
						<div>
							<dt><?php esc_html_e( 'Camera', 'stillframe' ); ?></dt>
							<dd><?php echo esc_html( $camera ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $year ) : ?>
						<div>
							<dt><?php esc_html_e( 'Year', 'stillframe' ); ?></dt>
							<dd><?php echo esc_html( $year ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $series ) : ?>
						<div>
							<dt><?php esc_html_e( 'Series', 'stillframe' ); ?></dt>
							<dd>
								<?php
								$links = array();
								foreach ( $series as $term ) {
									$links[] = sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( get_term_link( $term ) ),
										esc_html( $term->name )
									);
								}
								echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
								?>
							</dd>
						</div>
					<?php endif; ?>
				</dl>

				<?php if ( get_the_content() ) : ?>
					<div class="prose reveal" data-reveal>
						<?php the_content(); ?>
					</div>
				<?php endif; ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
