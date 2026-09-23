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
		<header class="page-masthead reveal" data-reveal>
			<?php
			get_template_part(
				'template-parts/page-masthead',
				'',
				array(
					'title' => __( 'Page not found.', 'stillframe' ),
					'lede'  => __( 'That URL doesn\'t exist. You can head home from here.', 'stillframe' ),
				)
			);
			?>
		</header>
		<p class="reveal" data-reveal>
			<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'stillframe' ); ?></a>
		</p>
	</div>
	</div>
</main>

<?php
get_footer();
