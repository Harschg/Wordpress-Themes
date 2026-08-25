<?php
/**
 * Resume shown on the page (image or PDF), not a download link.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

$attachment_id = stillframe_resume_attachment_id( get_the_ID() );
if ( ! $attachment_id ) {
	return;
}

$url  = (string) wp_get_attachment_url( $attachment_id );
$mime = (string) get_post_mime_type( $attachment_id );
$file = (string) get_attached_file( $attachment_id );
$alt  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
$alt  = $alt ? $alt : __( 'Resume', 'stillframe' );

$is_image = 0 === strpos( $mime, 'image/' ) || preg_match( '/\.(jpe?g|png|webp|gif)$/i', $file );
$is_word  = false !== strpos( $mime, 'word' ) || preg_match( '/\.docx?$/i', $file );

if ( $is_word ) {
	$url = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode( $url );
}
?>
<section class="resume-embed reveal" data-reveal>
	<span class="resume-embed__roll resume-embed__roll--top" aria-hidden="true"></span>
	<div class="resume-embed__sheet">
		<?php if ( $is_image ) : ?>
			<?php
			echo wp_get_attachment_image(
				$attachment_id,
				'full',
				false,
				array(
					'class' => 'resume-embed__image',
					'alt'   => $alt,
				)
			);
			?>
		<?php else : ?>
			<iframe
				class="resume-embed__frame"
				src="<?php echo esc_url( $url ); ?>"
				title="<?php echo esc_attr( $alt ); ?>"
				loading="lazy"
			></iframe>
		<?php endif; ?>
	</div>
	<span class="resume-embed__roll resume-embed__roll--bottom" aria-hidden="true"></span>
</section>
