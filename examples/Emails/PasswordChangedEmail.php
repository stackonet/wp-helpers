<?php

namespace Stackonet\WP\Examples\Emails;

use Stackonet\WP\Framework\Emails\ActionEmailTemplate;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class PasswordChangedEmail {
	/**
	 * @return string
	 */
	public static function get_content_html() {
		$mailer = new ActionEmailTemplate();
		$mailer->set_logo( 'https://www.bigbasket.com/static/v2267/custPage/build/content/img/bb_logo.png' );
		$mailer->set_box_mode( false );

		$mailer->set_intro_lines( 'This notice confirms that your password was changed.' );
		$mailer->set_intro_lines( 'If you did not change your password, reset your password immediately.' );
		$mailer->set_action( 'Reset Password', $mailer->get_home_url(), 'success' );

		return $mailer->get_content_html();
	}
}
