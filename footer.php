<?php
/**
 * Theme footer.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

$footer_note = get_theme_mod( 'stillframe_footer_note', '' );
$linkedin    = stillframe_contact_setting( 'stillframe_linkedin', 'https://www.linkedin.com/in/grant-harsch' );
?>

<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="site-footer__brand">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
			<?php bloginfo( 'name' ); ?>
		</p>
		<p class="site-footer__note">
			<?php if ( $linkedin ) : ?>
				<a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LinkedIn', 'stillframe' ); ?></a>
			<?php endif; ?>
			<?php if ( $linkedin && $footer_note ) : ?>
				<span aria-hidden="true"> · </span>
			<?php endif; ?>
			<?php if ( $footer_note ) : ?>
				<?php echo esc_html( $footer_note ); ?>
			<?php endif; ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
