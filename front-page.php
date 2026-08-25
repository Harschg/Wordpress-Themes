<?php
/**
 * Home: directory into the rest of the site.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$vibe = get_theme_mod( 'stillframe_vibe_line', __( 'Laid-back photographs and things I make.', 'stillframe' ) );

$cards = array(
	array(
		'kicker' => __( '01', 'stillframe' ),
		'title'  => __( 'About', 'stillframe' ),
		'copy'   => __( 'Who I am when the camera is down.', 'stillframe' ),
		'url'    => stillframe_page_url( 'about' ),
	),
	array(
		'kicker' => __( '02', 'stillframe' ),
		'title'  => __( 'Gallery', 'stillframe' ),
		'copy'   => __( 'Photographs, unhurried.', 'stillframe' ),
		'url'    => get_post_type_archive_link( 'photograph' ),
	),
	array(
		'kicker' => __( '03', 'stillframe' ),
		'title'  => __( 'Projects', 'stillframe' ),
		'copy'   => __( 'Things I have built and shipped.', 'stillframe' ),
		'url'    => get_post_type_archive_link( 'project' ),
	),
	array(
		'kicker' => __( '04', 'stillframe' ),
		'title'  => __( 'Contact', 'stillframe' ),
		'copy'   => __( 'A note, a collab, a hello.', 'stillframe' ),
		'url'    => stillframe_page_url( 'contact' ),
	),
);
?>

<main id="content" class="site-main">
	<section class="hero">
		<p class="hero__kicker reveal" data-reveal><?php esc_html_e( 'Portfolio', 'stillframe' ); ?></p>
		<h1 class="hero__title reveal" data-reveal>
			<?php bloginfo( 'name' ); ?>
		</h1>
		<?php if ( $vibe ) : ?>
			<p class="hero__vibe reveal" data-reveal><?php echo esc_html( $vibe ); ?></p>
		<?php endif; ?>
	</section>

	<section class="directory" aria-label="<?php esc_attr_e( 'Site directory', 'stillframe' ); ?>">
		<?php foreach ( $cards as $index => $card ) : ?>
			<a
				class="directory-card reveal"
				data-reveal
				data-stagger="<?php echo esc_attr( (string) $index ); ?>"
				href="<?php echo esc_url( $card['url'] ); ?>"
			>
				<span class="directory-card__kicker"><?php echo esc_html( $card['kicker'] ); ?></span>
				<span class="directory-card__title"><?php echo esc_html( $card['title'] ); ?></span>
				<span class="directory-card__copy"><?php echo esc_html( $card['copy'] ); ?></span>
				<span class="directory-card__arrow" aria-hidden="true">→</span>
			</a>
		<?php endforeach; ?>
	</section>
</main>

<?php
get_footer();
