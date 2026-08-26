<?php
/**
 * Page banner with optional picture background.
 *
 * @package Stillframe
 *
 * @var array $args {
 *     @type string $section home|about|gallery|projects|contact.
 *     @type string $title   Heading text.
 *     @type string $lede    Optional line under the title.
 *     @type string $image   Optional image URL. Overrides the section default.
 * }
 */

defined( 'ABSPATH' ) || exit;

$section = isset( $args['section'] ) ? sanitize_key( $args['section'] ) : '';
$title   = isset( $args['title'] ) ? (string) $args['title'] : '';
$lede    = isset( $args['lede'] ) ? (string) $args['lede'] : '';
$image   = isset( $args['image'] ) ? (string) $args['image'] : '';

if ( '' === $image && $section ) {
	$image = stillframe_section_hero_url( $section );
}

if ( '' === $title ) {
	return;
}
?>
<figure class="section-hero<?php echo $image ? '' : ' section-hero--plain'; ?>">
	<?php if ( $image ) : ?>
		<img
			src="<?php echo esc_url( $image ); ?>"
			alt=""
			width="1536"
			height="1024"
			decoding="async"
			fetchpriority="high"
		/>
	<?php endif; ?>
	<figcaption class="section-hero__caption">
		<h1 class="archive-header__title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $lede ) : ?>
			<p class="archive-header__lede"><?php echo esc_html( $lede ); ?></p>
		<?php endif; ?>
	</figcaption>
</figure>
