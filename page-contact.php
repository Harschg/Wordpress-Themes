<?php
/**
 * Contact page (used when the page slug is "contact").
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$status   = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : '';
$email     = stillframe_contact_setting( 'stillframe_contact_email', get_option( 'admin_email' ) );
$linkedin  = stillframe_contact_setting( 'stillframe_linkedin', 'https://www.linkedin.com/in/grant-harsch' );
$instagram = stillframe_contact_setting( 'stillframe_instagram' );
$github    = stillframe_contact_setting( 'stillframe_github' );
?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'contact' ); ?>>
			<?php
			get_template_part(
				'template-parts/section-hero',
				null,
				array(
					'section' => 'contact',
					'title'   => get_the_title(),
				)
			);
			?>
			<div class="contact__grid">
				<div class="contact__intro">
					<?php if ( get_the_content() ) : ?>
						<div class="prose reveal" data-reveal>
							<?php the_content(); ?>
						</div>
					<?php endif; ?>

					<ul class="contact-links reveal" data-reveal>
						<?php if ( is_email( $email ) ) : ?>
							<li>
								<span><?php esc_html_e( 'Email', 'stillframe' ); ?></span>
								<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( $linkedin ) : ?>
							<li>
								<span><?php esc_html_e( 'LinkedIn', 'stillframe' ); ?></span>
								<a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#i', '', $linkedin ) ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( $instagram ) : ?>
							<li>
								<span><?php esc_html_e( 'Instagram', 'stillframe' ); ?></span>
								<a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#i', '', $instagram ) ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( $github ) : ?>
							<li>
								<span><?php esc_html_e( 'GitHub', 'stillframe' ); ?></span>
								<a href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#i', '', $github ) ); ?></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>

				<div class="contact__form-wrap reveal" data-reveal>
					<?php if ( 'sent' === $status ) : ?>
						<p class="contact-banner contact-banner--ok" role="status">
							<?php esc_html_e( 'Thanks — I\'ll get back to you.', 'stillframe' ); ?>
						</p>
					<?php elseif ( 'invalid' === $status ) : ?>
						<p class="contact-banner contact-banner--err" role="alert">
							<?php esc_html_e( 'Name, email, and a message are required.', 'stillframe' ); ?>
						</p>
					<?php elseif ( 'error' === $status ) : ?>
						<p class="contact-banner contact-banner--err" role="alert">
							<?php esc_html_e( 'Couldn\'t send that. Try again, or email me.', 'stillframe' ); ?>
						</p>
					<?php endif; ?>

					<form class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="stillframe_contact" />
						<?php wp_nonce_field( 'stillframe_contact', 'stillframe_contact_nonce' ); ?>

						<p class="contact-honeypot" aria-hidden="true">
							<label for="stillframe_company"><?php esc_html_e( 'Company', 'stillframe' ); ?></label>
							<input type="text" id="stillframe_company" name="stillframe_company" tabindex="-1" autocomplete="off" />
						</p>

						<p class="contact-field">
							<label for="stillframe_name"><?php esc_html_e( 'Name', 'stillframe' ); ?></label>
							<input type="text" id="stillframe_name" name="stillframe_name" required autocomplete="name" />
						</p>
						<p class="contact-field">
							<label for="stillframe_email"><?php esc_html_e( 'Email', 'stillframe' ); ?></label>
							<input type="email" id="stillframe_email" name="stillframe_email" required autocomplete="email" />
						</p>
						<p class="contact-field">
							<label for="stillframe_message"><?php esc_html_e( 'Message', 'stillframe' ); ?></label>
							<textarea id="stillframe_message" name="stillframe_message" rows="6" required></textarea>
						</p>
						<p class="contact-field">
							<button class="btn" type="submit"><?php esc_html_e( 'Send', 'stillframe' ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
