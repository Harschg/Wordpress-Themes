<?php
/**
 * Resume attachment, PDF serving, and admin UI.
 *
 * @package Stillframe
 */

defined( 'ABSPATH' ) || exit;


/**
 * Whether an attachment can be used as the resume.
 *
 * @param int $attachment_id Attachment ID.
 * @return bool
 */
function stillframe_is_resume_file( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id ) {
		return false;
	}

	$mime = (string) get_post_mime_type( $attachment_id );
	$file = (string) get_attached_file( $attachment_id );

	if ( preg_match( '/\.(pdf|docx?|jpe?g|png|webp)$/i', $file ) ) {
		return true;
	}

	if ( false !== strpos( $mime, 'pdf' ) || 0 === strpos( $mime, 'image/' ) ) {
		return true;
	}

	return (bool) wp_get_attachment_url( $attachment_id );
}

/**
 * Attachment ID for the uploaded resume.
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return int
 */
function stillframe_resume_attachment_id( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : (int) get_the_ID();
	if ( ! $page_id || ! stillframe_is_resume_page( $page_id ) ) {
		return 0;
	}

	$ids = array(
		(int) get_post_meta( $page_id, 'stillframe_resume_id', true ),
	);

	$resume_page = stillframe_get_section_page( 'resume' );
	if ( $resume_page instanceof WP_Post ) {
		$ids[] = (int) get_post_meta( $resume_page->ID, 'stillframe_resume_id', true );
	}

	$about = stillframe_get_section_page( 'about' );
	if ( $about instanceof WP_Post ) {
		$ids[] = (int) get_post_meta( $about->ID, 'stillframe_resume_id', true );
	}

	$ids[] = (int) get_theme_mod( 'stillframe_resume_id', 0 );
	$ids   = array_unique( array_filter( $ids ) );

	foreach ( $ids as $attachment_id ) {
		if ( stillframe_is_resume_file( $attachment_id ) && wp_get_attachment_url( $attachment_id ) ) {
			return $attachment_id;
		}
	}

	return 0;
}

/**
 * Public URL for the uploaded resume.
 *
 * @param int $page_id Optional page ID. Defaults to the current post.
 * @return string
 */
function stillframe_resume_url( $page_id = 0 ) {
	$attachment_id = stillframe_resume_attachment_id( $page_id );

	return $attachment_id ? (string) wp_get_attachment_url( $attachment_id ) : '';
}

/**
 * Raw PDF bytes for a page's resume, or empty string.
 *
 * @param int $page_id Page ID.
 * @return string
 */
function stillframe_resume_pdf_bytes( $page_id ) {
	$attachment_id = stillframe_resume_attachment_id( (int) $page_id );
	if ( ! $attachment_id ) {
		return '';
	}

	$path = (string) get_attached_file( $attachment_id );
	if ( $path && is_readable( $path ) ) {
		$bytes = file_get_contents( $path );
		if ( false !== $bytes && '' !== $bytes ) {
			return $bytes;
		}
	}

	$url = (string) wp_get_attachment_url( $attachment_id );
	if ( ! $url ) {
		return '';
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return '';
	}

	return (string) wp_remote_retrieve_body( $response );
}

/**
 * Byte length that stays correct if mbstring overloads strlen().
 *
 * @param string $bytes Binary string.
 * @return int
 */
function stillframe_byte_length( $bytes ) {
	if ( function_exists( 'mb_strlen' ) ) {
		return (int) mb_strlen( $bytes, '8bit' );
	}

	return strlen( $bytes );
}

/**
 * Send a PDF with headers that do not truncate or gzip the file.
 *
 * @param string $bytes PDF contents.
 */
function stillframe_send_pdf_bytes( $bytes ) {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	if ( function_exists( 'apache_setenv' ) ) {
		apache_setenv( 'no-gzip', '1' );
	}
	if ( function_exists( 'ini_set' ) ) {
		ini_set( 'zlib.output_compression', 'Off' );
	}

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="resume.pdf"' );
	header( 'Content-Length: ' . (string) stillframe_byte_length( $bytes ) );
	header( 'Accept-Ranges: none' );
	header( 'Content-Encoding: identity' );
	header( 'X-Content-Type-Options: nosniff' );

	echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * Same-origin PDF so the on-page renderer can read the file.
 */
function stillframe_resume_pdf_file() {
	if ( ! isset( $_GET['stillframe_resume_pdf'] ) ) {
		return;
	}

	$bytes = stillframe_resume_pdf_bytes( absint( wp_unslash( $_GET['stillframe_resume_pdf'] ) ) );
	if ( '' === $bytes ) {
		status_header( 404 );
		exit;
	}

	stillframe_send_pdf_bytes( $bytes );
}
add_action( 'template_redirect', 'stillframe_resume_pdf_file', 0 );

/**
 * REST route for the resume PDF.
 */
function stillframe_register_resume_rest() {
	register_rest_route(
		'stillframe/v1',
		'/resume/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'stillframe_rest_resume_pdf',
			'args'                => array(
				'id' => array(
					'required' => true,
					'type'     => 'integer',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'stillframe_register_resume_rest' );

/**
 * REST callback: stream the resume PDF.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_Error
 */
function stillframe_rest_resume_pdf( $request ) {
	$bytes = stillframe_resume_pdf_bytes( (int) $request['id'] );
	if ( '' === $bytes ) {
		return new WP_Error( 'stillframe_resume_missing', __( 'Resume not found.', 'stillframe' ), array( 'status' => 404 ) );
	}

	stillframe_send_pdf_bytes( $bytes );
}

/**
 * Resume upload on the Resume page.
 *
 * @param WP_Post $post Current post.
 */
function stillframe_render_resume_meta_box( $post ) {
	wp_nonce_field( 'stillframe_save_resume', 'stillframe_resume_nonce' );

	$resume_id = (int) get_post_meta( $post->ID, 'stillframe_resume_id', true );
	$file      = $resume_id ? get_post( $resume_id ) : null;
	$filename  = ( $file instanceof WP_Post ) ? $file->post_title : '';
	?>
	<p><?php esc_html_e( 'PDF or a photo of the resume. It displays on this page. Choosing a file saves it right away.', 'stillframe' ); ?></p>
	<input type="hidden" id="stillframe_resume_id" name="stillframe_resume_id" value="<?php echo esc_attr( (string) $resume_id ); ?>" />
	<p data-resume-filename><?php echo $filename ? esc_html( $filename ) : esc_html__( 'No file yet.', 'stillframe' ); ?></p>
	<p data-resume-status class="description"></p>
	<p>
		<button type="button" class="button" data-resume-upload><?php esc_html_e( 'Choose file', 'stillframe' ); ?></button>
		<button type="button" class="button" data-resume-remove <?php echo $resume_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'stillframe' ); ?></button>
	</p>
	<?php
}

/**
 * Save resume attachment ID.
 *
 * @param int $post_id Post ID.
 */
function stillframe_save_resume_meta( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_resume_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stillframe_resume_nonce'] ) ), 'stillframe_save_resume' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['stillframe_resume_id'] ) ) {
		return;
	}

	$resume_id = absint( wp_unslash( $_POST['stillframe_resume_id'] ) );

	if ( $resume_id && ! stillframe_is_resume_file( $resume_id ) ) {
		$resume_id = 0;
	}

	if ( $resume_id ) {
		update_post_meta( $post_id, 'stillframe_resume_id', $resume_id );
	} else {
		delete_post_meta( $post_id, 'stillframe_resume_id' );
	}
}
add_action( 'save_post', 'stillframe_save_resume_meta' );

/**
 * Save resume as soon as a file is chosen (block editor often skips classic meta boxes).
 */
function stillframe_ajax_save_resume() {
	check_ajax_referer( 'stillframe_save_resume', 'nonce' );

	$post_id   = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$resume_id = isset( $_POST['resume_id'] ) ? absint( wp_unslash( $_POST['resume_id'] ) ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error();
	}

	if ( 'page' !== get_post_type( $post_id ) ) {
		wp_send_json_error();
	}

	if ( $resume_id && ! stillframe_is_resume_file( $resume_id ) ) {
		$resume_id = 0;
	}

	if ( $resume_id ) {
		update_post_meta( $post_id, 'stillframe_resume_id', $resume_id );
	} else {
		delete_post_meta( $post_id, 'stillframe_resume_id' );
	}

	wp_send_json_success(
		array(
			'url' => $resume_id ? wp_get_attachment_url( $resume_id ) : '',
		)
	);
}
add_action( 'wp_ajax_stillframe_save_resume', 'stillframe_ajax_save_resume' );
/**
 * PDF.js for the on-page resume (avoids the browser's dark PDF viewer).
 */
function stillframe_enqueue_resume_pdf() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	$attachment_id = stillframe_resume_attachment_id( get_queried_object_id() );
	if ( ! $attachment_id ) {
		return;
	}

	$mime = (string) get_post_mime_type( $attachment_id );
	$file = (string) get_attached_file( $attachment_id );
	if ( false === strpos( $mime, 'pdf' ) && ! preg_match( '/\.pdf$/i', $file ) ) {
		return;
	}

	$pdfjs = get_template_directory_uri() . '/assets/js/pdfjs';

	wp_enqueue_script(
		'pdfjs',
		$pdfjs . '/pdf.min.js',
		array(),
		'3.11.174',
		true
	);

	wp_enqueue_script(
		'stillframe-resume-pdf',
		get_template_directory_uri() . '/assets/js/resume-pdf.js',
		array( 'pdfjs' ),
		STILLFRAME_VERSION,
		true
	);

	wp_localize_script(
		'stillframe-resume-pdf',
		'stillframeResumePdf',
		array(
			'workerSrc' => $pdfjs . '/pdf.worker.min.js',
		)
	);
}

/**
 * Register Resume meta box.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Current post.
 */
function stillframe_add_resume_meta_box( $post_type, $post ) {
	if ( 'page' !== $post_type || ! $post instanceof WP_Post ) {
		return;
	}

	if ( ! stillframe_is_resume_page( $post->ID ) ) {
		return;
	}

	add_meta_box(
		'stillframe_resume',
		__( 'Resume', 'stillframe' ),
		'stillframe_render_resume_meta_box',
		'page',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'stillframe_add_resume_meta_box', 10, 2 );

/**
 * Media picker for the resume meta box.
 *
 * @param string $hook Current admin page.
 */
function stillframe_resume_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) {
		$post_id = absint( wp_unslash( $_GET['post'] ) );
	} elseif ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
		$post_id = (int) $GLOBALS['post']->ID;
	}

	if ( ! stillframe_is_resume_page( $post_id ) ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'stillframe-admin-resume',
		get_template_directory_uri() . '/assets/js/admin-resume.js',
		array( 'jquery' ),
		STILLFRAME_VERSION,
		true
	);

	wp_localize_script(
		'stillframe-admin-resume',
		'stillframeResume',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'stillframe_save_resume' ),
			'postId'  => $post_id,
		)
	);
}
add_action( 'admin_enqueue_scripts', 'stillframe_resume_admin_assets' );
