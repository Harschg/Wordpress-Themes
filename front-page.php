<?php
/**
 * Home: directory into the rest of the site.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$vibe = get_theme_mod( 'stillframe_vibe_line', '' );

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
	<section class="hero">
		<h1 class="hero__title reveal" data-reveal>
			<?php bloginfo( 'name' ); ?>
		</h1>
		<?php if ( $vibe ) : ?>
			<p class="hero__vibe reveal" data-reveal><?php echo esc_html( $vibe ); ?></p>
		<?php endif; ?>
	</section>

	<section class="directory" aria-label="<?php esc_attr_e( 'Pages', 'stillframe' ); ?>">
		<?php foreach ( $cards as $index => $card ) : ?>
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
	</section>
</main>

<?php
get_footer();
