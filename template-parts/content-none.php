<?php
/**
 * Empty state.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="empty-state reveal" data-reveal>
	<h2 class="empty-state__title"><?php esc_html_e( 'Nothing here yet.', 'stillframe' ); ?></h2>
	<p class="empty-state__copy">
		<?php esc_html_e( 'Add photographs or projects in the WordPress admin and they will land here.', 'stillframe' ); ?>
	</p>
	<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'stillframe' ); ?></a>
</div>
