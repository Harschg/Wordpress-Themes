<?php
/**
 * 404.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main">
	<div class="lost page-shell">
		<p class="page-header__kicker reveal" data-reveal><?php esc_html_e( '404', 'stillframe' ); ?></p>
		<h1 class="hero__title reveal" data-reveal><?php esc_html_e( 'This frame is empty.', 'stillframe' ); ?></h1>
		<p class="hero__vibe reveal" data-reveal><?php esc_html_e( 'That page drifted off somewhere. Head home and start again.', 'stillframe' ); ?></p>
		<p class="reveal" data-reveal>
			<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'stillframe' ); ?></a>
		</p>
	</div>
</main>

<?php
get_footer();
