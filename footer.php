<?php
/**
 * Theme footer.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

$footer_note = get_theme_mod( 'stillframe_footer_note', '' );
?>

<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="site-footer__brand">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
			<?php bloginfo( 'name' ); ?>
		</p>
		<?php if ( $footer_note ) : ?>
			<p class="site-footer__note"><?php echo esc_html( $footer_note ); ?></p>
		<?php endif; ?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
