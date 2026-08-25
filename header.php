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
		.site-header, .site-main, .site-footer { opacity: 1 !important; }
	</style>
</noscript>

<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'stillframe' ); ?></a>

<header class="site-header">
	<div class="site-header__inner">
		<div class="site-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
			<span class="nav-toggle__bars" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'stillframe' ); ?></span>
		</button>

		<nav id="site-nav" class="site-nav" data-nav>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'nav-list',
					'fallback_cb'    => 'stillframe_fallback_menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
</header>
