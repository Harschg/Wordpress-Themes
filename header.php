<?php
/**
 * Theme header.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<script>document.documentElement.classList.add("has-fade-in");</script>
	<style>
		html.has-fade-in .page-loader {
			position: fixed;
			inset: 0;
			z-index: 10000;
			background: var(--bg, #141210);
		}
		html.has-fade-in .reveal:not(.is-visible),
		html.has-fade-in .is-awaiting-reveal,
		html.has-fade-in img:not(.is-in) {
			opacity: 0;
		}
		html.has-fade-in .page-world img {
			opacity: 1;
		}
		@media (prefers-reduced-motion: reduce) {
			html.has-fade-in .page-loader {
				display: none;
			}
			html.has-fade-in .reveal,
			html.has-fade-in .is-awaiting-reveal,
			html.has-fade-in img {
				opacity: 1 !important;
			}
		}
	</style>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="page-loader" role="status" data-page-loader>
	<span class="page-loader__spinner" aria-hidden="true"></span>
	<span class="screen-reader-text"><?php esc_html_e( 'Loading', 'stillframe' ); ?></span>
</div>
<noscript>
	<style>
		.page-loader { display: none !important; }
		.site-header, .site-main, .site-footer, img { opacity: 1 !important; }
	</style>
</noscript>

<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'stillframe' ); ?></a>

<header class="site-header">
	<div class="site-header__inner">
		<div class="site-brand">
			<a class="site-brand__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php
				$logo = stillframe_site_logo_img();
				if ( $logo ) {
					echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- stillframe_attachment_img()
				}
				?>
				<span class="site-brand__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
		</div>

		<nav id="site-nav" class="site-nav" data-nav>
			<?php stillframe_fallback_menu(); ?>
		</nav>
	</div>
</header>

<?php get_template_part( 'template-parts/page-world' ); ?>
