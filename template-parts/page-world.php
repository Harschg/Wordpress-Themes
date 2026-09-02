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

$srcset = '';
$width  = 2560;
$height = 1707;

if ( ! empty( $source['id'] ) ) {
	$srcset = (string) wp_get_attachment_image_srcset( (int) $source['id'], 'full' );

	if ( function_exists( 'wp_get_original_image_path' ) && function_exists( 'wp_get_original_image_url' ) ) {
		$original_path = wp_get_original_image_path( (int) $source['id'] );
		$original_url  = wp_get_original_image_url( (int) $source['id'] );
		if ( $original_path && $original_url && is_readable( $original_path ) ) {
			$info = wp_getimagesize( $original_path );
			if ( $info ) {
				$width  = (int) $info[0];
				$height = (int) $info[1];
				if ( $srcset && false === strpos( $srcset, $original_url ) && $width ) {
					$srcset .= ', ' . $original_url . ' ' . $width . 'w';
				}
			}
		}
	}

	if ( ! $width || ! $height ) {
		$meta = wp_get_attachment_metadata( (int) $source['id'] );
		if ( ! empty( $meta['width'] ) ) {
			$width = (int) $meta['width'];
		}
		if ( ! empty( $meta['height'] ) ) {
			$height = (int) $meta['height'];
		}
	}
}
?>
<div class="page-world" aria-hidden="true">
	<img
		src="<?php echo esc_url( $source['url'] ); ?>"
		<?php if ( $srcset ) : ?>
			srcset="<?php echo esc_attr( $srcset ); ?>"
			sizes="100vw"
		<?php endif; ?>
		alt=""
		width="<?php echo esc_attr( (string) $width ); ?>"
		height="<?php echo esc_attr( (string) $height ); ?>"
		decoding="async"
		fetchpriority="high"
	/>
</div>
