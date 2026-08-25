<?php
/**
 * Single photograph.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$location = get_post_meta( get_the_ID(), 'stillframe_location', true );
$camera   = get_post_meta( get_the_ID(), 'stillframe_camera', true );
$year     = get_post_meta( get_the_ID(), 'stillframe_year', true );
$series   = get_the_terms( get_the_ID(), 'photo_series' );
$series   = ( ! is_wp_error( $series ) && $series ) ? $series : array();
?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'photo-single' ); ?>>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="photo-single__frame reveal" data-reveal>
					<?php the_post_thumbnail( 'stillframe-hero', array( 'class' => 'photo-single__image' ) ); ?>
				</figure>
			<?php endif; ?>

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

				<nav class="photo-nav reveal" data-reveal>
					<?php
					the_post_navigation(
						array(
							'prev_text' => '<span class="photo-nav__label">' . esc_html__( 'Earlier', 'stillframe' ) . '</span><span class="photo-nav__title">%title</span>',
							'next_text' => '<span class="photo-nav__label">' . esc_html__( 'Later', 'stillframe' ) . '</span><span class="photo-nav__title">%title</span>',
						)
					);
					?>
				</nav>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
