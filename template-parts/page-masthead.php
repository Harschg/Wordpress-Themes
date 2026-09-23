<?php
/**
 * Editorial title plate used across the site.
 *
 * @package Stillframe
 *
 * @var array $args {
 *     @type string $title      Optional title override.
 *     @type string $kicker     Small label above the title.
 *     @type string $kicker_url Optional link for the kicker.
 *     @type string $lede       Optional line under the gold rule.
 * }
 */

defined( 'ABSPATH' ) || exit;

$title      = isset( $args['title'] ) && '' !== (string) $args['title'] ? (string) $args['title'] : get_the_title();
$kicker     = isset( $args['kicker'] ) ? (string) $args['kicker'] : '';
$kicker_url = isset( $args['kicker_url'] ) ? (string) $args['kicker_url'] : '';
$lede       = isset( $args['lede'] ) ? (string) $args['lede'] : '';
?>
<?php if ( $kicker ) : ?>
	<p class="page-masthead__kicker">
		<?php if ( $kicker_url ) : ?>
			<a href="<?php echo esc_url( $kicker_url ); ?>"><?php echo esc_html( $kicker ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $kicker ); ?>
		<?php endif; ?>
	</p>
<?php endif; ?>
<h1 class="page-masthead__title"><?php echo esc_html( $title ); ?></h1>
<span class="page-masthead__rule" aria-hidden="true"></span>
<?php if ( $lede ) : ?>
	<p class="page-masthead__lede"><?php echo esc_html( $lede ); ?></p>
<?php endif; ?>
