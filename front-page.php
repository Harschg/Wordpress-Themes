<?php
/**
 * Home: welcome copy and portrait.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$vibe     = stillframe_home_subtitle();
$intro    = stillframe_home_intro_html();
$home_id  = (int) get_option( 'page_on_front' );
$portrait = $home_id ? stillframe_attachment_img(
	(int) get_post_thumbnail_id( $home_id ),
	array(
		'class'         => 'home-intro__image',
		'alt'           => get_bloginfo( 'name' ),
		'fetchpriority' => 'high',
		'sizes'         => stillframe_feature_image_sizes(),
	)
) : '';
?>

<main id="content" class="site-main">
	<div class="page-glass">
		<section class="home-intro" aria-label="<?php esc_attr_e( 'Welcome', 'stillframe' ); ?>">
			<div class="home-intro__copy prose reveal" data-reveal>
				<header class="page-masthead">
					<?php
					get_template_part(
						'template-parts/page-masthead',
						'',
						array(
							'title' => __( 'Welcome', 'stillframe' ),
							'lede'  => $vibe,
						)
					);
					?>
				</header>
				<?php if ( $intro ) : ?>
					<?php echo wp_kses_post( $intro ); ?>
				<?php endif; ?>
			</div>

			<figure
				class="home-intro__portrait reveal<?php echo $portrait ? '' : ' home-intro__portrait--empty'; ?>"
				data-reveal
				<?php echo $portrait ? '' : ' aria-hidden="true"'; ?>
			>
				<?php
				if ( $portrait ) {
					echo $portrait; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- stillframe_attachment_img()
				}
				?>
			</figure>
		</section>
	</div>
</main>

<?php
get_footer();
