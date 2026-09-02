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
	<div class="page-glass">
	<div class="lost page-shell">
		<h1 class="hero__title reveal" data-reveal><?php esc_html_e( 'Page not found.', 'stillframe' ); ?></h1>
		<p class="hero__vibe reveal" data-reveal><?php esc_html_e( 'That URL doesn\'t exist. You can head home from here.', 'stillframe' ); ?></p>
		<p class="reveal" data-reveal>
			<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'stillframe' ); ?></a>
		</p>
	</div>
	</div>
</main>

<?php
get_footer();
