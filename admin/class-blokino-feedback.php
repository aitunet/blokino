<?php
/**
 * Blokino · Deactivation feedback — an optional "why are you leaving?" dialog.
 *
 * On the Plugins screen, clicking Blokino's "Deactivate" link opens a small
 * dialog with a few reasons and a text box. Built to the wordpress.org
 * guidelines (no tracking without consent):
 *
 * - Nothing is sent unless the person presses "Submit & deactivate". "Skip &
 *   deactivate" is always there and deactivates right away.
 * - What is sent is listed in the dialog itself: the reason, the text, the
 *   Blokino / WordPress / PHP versions, the admin language and the active
 *   theme's name. The email address and the site URL go only when the person
 *   ticks "You may contact me".
 * - The request is made from the server (admin-ajax → wp_remote_post) with a
 *   neutral user agent, so the browser never talks to tunetdesign.com, and a
 *   failure never blocks the deactivation.
 * - If JavaScript fails, the link keeps working as a plain deactivate link.
 *
 * @package Blokino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation feedback.
 */
class Blokino_Feedback {

	const ENDPOINT    = 'https://tunetdesign.com/wp-json/tunet-store/v1/feedback';
	const PRIVACY_URL = 'https://tunetdesign.com/privacy/';
	const ACTION      = 'blokino_deactivation_feedback';
	const MAX_DETAILS = 2000;

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		// admin_footer, not admin_footer-plugins.php: that one fires AFTER the footer scripts
		// are printed, so feedback.js would run before the dialog exists.
		add_action( 'admin_footer', array( $this, 'render_dialog' ) );
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Endpoint. Filterable so a local store can be pointed at
	 * (`add_filter( 'blokino_feedback_endpoint', fn() => 'http://tunet.local/wp-json/tunet-store/v1/feedback' )`).
	 * Returning an empty string turns the dialog off.
	 *
	 * @return string
	 */
	public static function endpoint() {
		return (string) apply_filters( 'blokino_feedback_endpoint', self::ENDPOINT );
	}

	/**
	 * The dialog only exists on the single-site Plugins screen, for people who
	 * can deactivate plugins, and while the endpoint is set.
	 *
	 * @return bool
	 */
	private static function enabled() {
		return ! is_network_admin() && current_user_can( 'activate_plugins' ) && '' !== self::endpoint();
	}

	/**
	 * Reasons, in the order shown. Each has a label and the hint used as the
	 * text box placeholder once it is picked.
	 *
	 * @return array<string, array{label: string, hint: string}>
	 */
	public static function reasons() {
		return array(
			'no_longer_needed' => array(
				'label' => __( 'I no longer need it', 'blokino' ),
				'hint'  => __( 'Anything we could have done better?', 'blokino' ),
			),
			'temporary'        => array(
				'label' => __( "It's temporary — I'm troubleshooting", 'blokino' ),
				'hint'  => __( 'If you found a problem, what was it?', 'blokino' ),
			),
			'broke_site'       => array(
				'label' => __( 'It broke my site or conflicts with something', 'blokino' ),
				'hint'  => __( 'What broke? Which plugin or theme does it conflict with?', 'blokino' ),
			),
			'missing_feature'  => array(
				'label' => __( "It's missing a feature I need", 'blokino' ),
				'hint'  => __( 'Which feature?', 'blokino' ),
			),
			'better_plugin'    => array(
				'label' => __( 'I found a better plugin', 'blokino' ),
				'hint'  => __( 'Which one, and what does it do better?', 'blokino' ),
			),
			'hard_to_use'      => array(
				'label' => __( "It's hard to use or I couldn't make it work", 'blokino' ),
				'hint'  => __( 'What were you trying to do?', 'blokino' ),
			),
			'other'            => array(
				'label' => __( 'Other', 'blokino' ),
				'hint'  => __( 'Tell us why, please.', 'blokino' ),
			),
		);
	}

	/**
	 * Assets, only on the Plugins screen.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue( $hook ) {
		if ( 'plugins.php' !== $hook || ! self::enabled() ) {
			return;
		}
		wp_enqueue_style( 'blokino-feedback', BLOKINO_URL . 'admin/feedback.css', array(), (string) filemtime( BLOKINO_PATH . 'admin/feedback.css' ) );
		wp_enqueue_script( 'blokino-feedback', BLOKINO_URL . 'admin/feedback.js', array(), (string) filemtime( BLOKINO_PATH . 'admin/feedback.js' ), true );
		wp_localize_script(
			'blokino-feedback',
			'blokinoFeedback',
			array(
				'plugin'  => plugin_basename( BLOKINO_PATH . 'blokino.php' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION,
				'nonce'   => wp_create_nonce( self::ACTION ),
				'sending' => __( 'Sending…', 'blokino' ),
			)
		);
	}

	/**
	 * The dialog markup, printed once in the Plugins screen footer.
	 */
	public function render_dialog() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id || ! self::enabled() ) {
			return;
		}
		$user  = wp_get_current_user();
		$email = $user && $user->exists() ? $user->user_email : '';
		?>
		<dialog id="blokino-feedback" class="blokino-fb" aria-labelledby="blokino-fb-title" aria-describedby="blokino-fb-intro">
			<form class="blokino-fb__form" method="dialog">
				<h2 id="blokino-fb-title" class="blokino-fb__title"><?php esc_html_e( 'Before you go — why are you deactivating Blokino?', 'blokino' ); ?></h2>
				<p id="blokino-fb-intro" class="blokino-fb__intro"><?php esc_html_e( 'It takes ten seconds and tells us what to fix. Answering is optional.', 'blokino' ); ?></p>

				<fieldset class="blokino-fb__reasons">
					<legend class="screen-reader-text"><?php esc_html_e( 'Reason', 'blokino' ); ?></legend>
					<?php foreach ( self::reasons() as $key => $reason ) : ?>
						<label class="blokino-fb__reason">
							<input type="radio" name="reason" value="<?php echo esc_attr( $key ); ?>" data-hint="<?php echo esc_attr( $reason['hint'] ); ?>"/>
							<span><?php echo esc_html( $reason['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>

				<label class="blokino-fb__label" for="blokino-fb-details"><?php esc_html_e( 'Tell us more (optional)', 'blokino' ); ?></label>
				<textarea id="blokino-fb-details" name="details" rows="4" maxlength="<?php echo esc_attr( self::MAX_DETAILS ); ?>"></textarea>

				<label class="blokino-fb__contact">
					<input type="checkbox" name="contact" value="1"/>
					<span><?php esc_html_e( 'You may contact me about this', 'blokino' ); ?></span>
				</label>
				<div class="blokino-fb__email" hidden>
					<label class="screen-reader-text" for="blokino-fb-email"><?php esc_html_e( 'Email', 'blokino' ); ?></label>
					<input id="blokino-fb-email" type="email" name="email" class="regular-text" value="<?php echo esc_attr( $email ); ?>" autocomplete="email"/>
				</div>

				<p class="blokino-fb__privacy">
					<?php
					printf(
						/* translators: %s: privacy policy link. */
						esc_html__( 'Sent to tunetdesign.com only if you press "Submit & deactivate": your answer, the Blokino, WordPress and PHP versions, the admin language and your theme\'s name. Your email and site address go only if you tick the box above. %s', 'blokino' ),
						'<a href="' . esc_url( self::PRIVACY_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy policy', 'blokino' ) . '</a>'
					);
					?>
				</p>

				<div class="blokino-fb__actions">
					<button type="button" class="button-link blokino-fb__skip" data-blokino-fb="skip"><?php esc_html_e( 'Skip & deactivate', 'blokino' ); ?></button>
					<span class="blokino-fb__buttons">
						<button type="button" class="button" data-blokino-fb="cancel"><?php esc_html_e( 'Cancel', 'blokino' ); ?></button>
						<button type="submit" class="button button-primary" data-blokino-fb="submit" disabled><?php esc_html_e( 'Submit & deactivate', 'blokino' ); ?></button>
					</span>
				</div>
			</form>
		</dialog>
		<?php
	}

	/**
	 * AJAX: forward the answer to the store. Always answers success — the
	 * browser deactivates the plugin whatever happens here.
	 */
	public function handle() {
		check_ajax_referer( self::ACTION, 'nonce' );
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( null, 403 );
		}
		$endpoint = self::endpoint();
		$reason   = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
		if ( '' === $endpoint || ! array_key_exists( $reason, self::reasons() ) ) {
			wp_send_json_success( array( 'sent' => false ) );
		}
		$details = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		$theme   = wp_get_theme();
		$body    = array(
			'product' => 'blokino',
			'reason'  => $reason,
			'details' => mb_substr( $details, 0, self::MAX_DETAILS ),
			'version' => BLOKINO_VERSION,
			'wp'      => get_bloginfo( 'version' ),
			'php'     => PHP_VERSION,
			'locale'  => get_user_locale(),
			'theme'   => trim( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ),
		);
		// Contact details only with the explicit opt-in.
		if ( ! empty( $_POST['contact'] ) ) {
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( is_email( $email ) ) {
				$body['email'] = $email;
				$body['site']  = home_url( '/' );
			}
		}
		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'    => 5,
				'user-agent' => 'Blokino/' . BLOKINO_VERSION, // Neutral: WP's default UA carries the site URL.
				'body'       => $body,
			)
		);
		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		wp_send_json_success( array( 'sent' => $code >= 200 && $code < 300 ) );
	}
}
