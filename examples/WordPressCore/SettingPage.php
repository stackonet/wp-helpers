<?php

namespace Stackonet\WP\Examples\WordPressCore;

use Stackonet\WP\Framework\SettingApi\DefaultSettingApi;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class SettingPage {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'plugins_loaded', [ self::$instance, 'add_settings_page' ] );
		}

		return self::$instance;
	}

	public function add_settings_page() {
		$setting = new DefaultSettingApi();
		$setting->set_option_name( '_wp_helper_setting_example' );
		$setting->add_menu( [
			'parent_slug' => 'options-general.php',
			'menu_title'  => 'Stackonet WP Helper',
			'page_title'  => 'Stackonet WP Helper',
			'menu_slug'   => 'wp-helper-setting-example',
		] );

		$panels = [
			[ 'id' => 'general', 'title' => 'General', 'priority' => 10, ],
			[ 'id' => 'messages', 'title' => 'Messages', 'priority' => 20, ],
			[ 'id' => 'integrations', 'title' => 'Integrations', 'priority' => 30, ],
		];
		$setting->set_panels( $panels );

		$sections = [
			[
				'id'          => 'google_map',
				'title'       => __( 'Google Map', 'dialog-contact-form' ),
				'description' => __( 'Plugin general options.', 'dialog-contact-form' ),
				'panel'       => 'integrations',
				'priority'    => 10,
			]
		];
		$setting->set_sections( $sections );

		$setting->add_field( array(
			'id'          => 'mailer',
			'type'        => 'checkbox',
			'title'       => __( 'Use SMTP', 'dialog-contact-form' ),
			'description' => __( 'Check to send all emails via SMTP', 'dialog-contact-form' ),
			'default'     => '',
			// 'section'  => 'general',
			'panel'       => 'general',
			'priority'    => 10,
		) );

		$setting->add_field( array(
			'id'                => 'smpt_host',
			'type'              => 'text',
			'title'             => __( 'SMTP Host', 'dialog-contact-form' ),
			'description'       => __( 'Specify your SMTP server hostname', 'dialog-contact-form' ),
			'default'           => '',
//			'section'           => 'dcf_smpt_server_section',
			'priority'          => 20,
			'sanitize_callback' => 'sanitize_text_field',
			'panel'             => 'general',
		) );

		$setting->add_field( array(
			'id'       => 'spam_message',
			'type'     => 'textarea',
			'rows'     => 2,
			'title'    => __( 'Submission filtered as spam', 'dialog-contact-form' ),
			'default'  => '',
			'section'  => 'dcf_message_section',
			'priority' => 10,
			'panel'    => 'messages',
		) );

		$setting->add_field( array(
			'id'                => 'recaptcha_site_key',
			'type'              => 'text',
			'name'              => __( 'Site key', 'dialog-contact-form' ),
			'desc'              => __( 'Enter google reCAPTCHA API site key', 'dialog-contact-form' ),
			'std'               => '',
			'priority'          => 10,
			'section'           => 'google_map',
			'sanitize_callback' => 'sanitize_text_field',
		) );
	}
}
