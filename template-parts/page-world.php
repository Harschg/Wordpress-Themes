<?php
/**
 * Full-bleed photo behind the glass panel.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

$source = stillframe_page_world_source();
if ( empty( $source['url'] ) ) {
	return;
}

$width  = 1920;
$height = 1280;

if ( ! empty( $source['id'] ) ) {
	$meta = wp_get_attachment_metadata( (int) $source['id'] );
	if ( ! empty( $meta['width'] ) ) {
		$width = (int) $meta['width'];
	}
	if ( ! empty( $meta['height'] ) ) {
		$height = (int) $meta['height'];
	}
}
?>
<div class="page-world" aria-hidden="true">
	<img
		src="<?php echo esc_url( $source['url'] ); ?>"
		alt=""
		width="<?php echo esc_attr( (string) $width ); ?>"
		height="<?php echo esc_attr( (string) $height ); ?>"
		decoding="async"
		fetchpriority="high"
	/>
</div>
