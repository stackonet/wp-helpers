<?php

namespace Stackonet\WP\Examples\Emails;

use Stackonet\WP\Framework\Emails\ActionEmailTemplate;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class PasswordChangeRequestEmail {
	/**
	 * @return string
	 */
	public static function get_content_html() {
		$mailer = new ActionEmailTemplate();
		$mailer->set_logo( 'https://www.bigbasket.com/static/v2267/custPage/build/content/img/bb_logo.png' );
		$mailer->set_box_mode( false );

		$mailer->set_intro_lines( 'You are receiving this email because we received a password reset request for your account.' );
		$mailer->set_action( 'Reset Password', $mailer->get_home_url(), 'success' );
		$mailer->set_outro_lines( 'If you did not request a password reset, no further action is required.' );

		return $mailer->get_content_html();
	}
}
