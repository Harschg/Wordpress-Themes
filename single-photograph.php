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
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'stillframe-hero', array( 'class' => 'photo-single__image' ) ); ?>
				<?php endif; ?>

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
				<header class="reveal" data-reveal>
					<h1 class="page-header__title"><?php the_title(); ?></h1>
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
