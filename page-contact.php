<?php
/**
 * Contact page (used when the page slug is "contact").
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;

get_header();

$status   = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : '';
$email    = get_theme_mod( 'stillframe_contact_email', get_option( 'admin_email' ) );
$instagram = get_theme_mod( 'stillframe_instagram', '' );
$github    = get_theme_mod( 'stillframe_github', '' );
?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'contact' ); ?>>
			<div class="contact__grid">
				<div class="contact__intro">
					<p class="page-header__kicker reveal" data-reveal><?php esc_html_e( 'Contact', 'stillframe' ); ?></p>
					<h1 class="page-header__title reveal" data-reveal><?php the_title(); ?></h1>
					<div class="prose reveal" data-reveal>
						<?php if ( get_the_content() ) : ?>
							<?php the_content(); ?>
						<?php else : ?>
							<p><?php esc_html_e( 'Say hello. Commissions, collaborations, or just a note — I read everything.', 'stillframe' ); ?></p>
						<?php endif; ?>
					</div>

					<ul class="contact-links reveal" data-reveal>
						<?php if ( is_email( $email ) ) : ?>
							<li>
								<span><?php esc_html_e( 'Email', 'stillframe' ); ?></span>
								<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( $instagram ) : ?>
							<li>
								<span><?php esc_html_e( 'Instagram', 'stillframe' ); ?></span>
								<a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open profile', 'stillframe' ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( $github ) : ?>
							<li>
								<span><?php esc_html_e( 'GitHub', 'stillframe' ); ?></span>
								<a href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open profile', 'stillframe' ); ?></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>

				<div class="contact__form-wrap reveal" data-reveal>
					<?php if ( 'sent' === $status ) : ?>
						<p class="contact-banner contact-banner--ok" role="status">
							<?php esc_html_e( 'Sent. I will get back to you when I can.', 'stillframe' ); ?>
						</p>
					<?php elseif ( 'invalid' === $status ) : ?>
						<p class="contact-banner contact-banner--err" role="alert">
							<?php esc_html_e( 'Need a name, a real email, and a message.', 'stillframe' ); ?>
						</p>
					<?php elseif ( 'error' === $status ) : ?>
						<p class="contact-banner contact-banner--err" role="alert">
							<?php esc_html_e( 'Something stalled. Try again in a minute, or email me directly.', 'stillframe' ); ?>
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
