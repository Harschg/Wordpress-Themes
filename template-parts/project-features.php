<?php
/**
 * Feature sections on a single project: copy beside its photo.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

$post_id  = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
$features = stillframe_project_features( $post_id );

if ( ! $features ) {
	return;
}

$manifest = array();
foreach ( $features as $feature ) {
	if ( empty( $feature['image_id'] ) ) {
		continue;
	}
	$url = stillframe_largest_attachment_url( $feature['image_id'] );
	if ( ! $url ) {
		continue;
	}
	$alt = get_post_meta( $feature['image_id'], '_wp_attachment_image_alt', true );
	if ( '' === $alt ) {
		$alt = $feature['title'];
	}
	$manifest[] = array(
		'src' => $url,
		'alt' => (string) $alt,
	);
}

$shot_index = 0;
?>

<div class="project-features" data-project-features>
	<?php foreach ( $features as $index => $feature ) : ?>
		<?php
		$image_id = (int) $feature['image_id'];
		$img      = $image_id ? stillframe_attachment_img(
			$image_id,
			array(
				'class'   => 'project-feature__image',
				'alt'     => $feature['title'] ? $feature['title'] : '',
				'loading' => 'eager',
				'sizes'   => stillframe_feature_image_sizes(),
			)
		) : '';
		$classes  = 'project-feature glass-lift';
		if ( $img && 1 === $index % 2 ) {
			$classes .= ' project-feature--flip';
		}
		if ( ! $img ) {
			$classes .= ' project-feature--text';
		}
		?>
		<section class="<?php echo esc_attr( $classes ); ?>">
			<?php if ( $img ) : ?>
				<button
					type="button"
					class="project-feature__media"
					data-shot-index="<?php echo esc_attr( (string) $shot_index ); ?>"
				>
					<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<?php ++$shot_index; ?>
			<?php endif; ?>

			<div class="project-feature__copy">
				<?php if ( $feature['title'] ) : ?>
					<h2 class="project-feature__title"><?php echo esc_html( $feature['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( $feature['text'] ) : ?>
					<div class="project-feature__text">
						<?php echo wpautop( esc_html( $feature['text'] ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endforeach; ?>

	<script type="application/json" data-gallery-manifest><?php echo wp_json_encode( array_values( $manifest ) ); ?></script>
</div>
