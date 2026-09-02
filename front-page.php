<?php
/**
 * Home: directory into the rest of the site.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$vibe  = stillframe_home_subtitle();
$intro = stillframe_home_intro_html();

$cards = array(
	array(
		'title' => __( 'About', 'stillframe' ),
		'url'   => stillframe_page_url( 'about' ),
	),
	array(
		'title' => __( 'Gallery', 'stillframe' ),
		'url'   => get_post_type_archive_link( 'photograph' ),
	),
	array(
		'title' => __( 'Projects', 'stillframe' ),
		'url'   => get_post_type_archive_link( 'project' ),
	),
	array(
		'title' => __( 'Contact', 'stillframe' ),
		'url'   => stillframe_page_url( 'contact' ),
	),
);
?>

<main id="content" class="site-main">
	<div class="page-glass">
		<section class="home-intro" aria-label="<?php esc_attr_e( 'Welcome', 'stillframe' ); ?>">
			<div class="home-intro__copy prose reveal" data-reveal>
				<h1 class="archive-header__title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
				<?php if ( $vibe ) : ?>
					<p class="archive-header__lede"><?php echo esc_html( $vibe ); ?></p>
				<?php endif; ?>
				<?php if ( $intro ) : ?>
					<?php echo wp_kses_post( $intro ); ?>
				<?php endif; ?>
			</div>

			<div class="directory" aria-label="<?php esc_attr_e( 'Pages', 'stillframe' ); ?>">
				<?php foreach ( $cards as $index => $card ) : ?>
					<?php if ( empty( $card['url'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<a
						class="directory-card reveal"
						data-reveal
						data-stagger="<?php echo esc_attr( (string) $index ); ?>"
						href="<?php echo esc_url( $card['url'] ); ?>"
					>
						<span class="directory-card__title"><?php echo esc_html( $card['title'] ); ?></span>
						<span class="directory-card__arrow" aria-hidden="true">→</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	</div>
</main>

<?php
get_footer();
