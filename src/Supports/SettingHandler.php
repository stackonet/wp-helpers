<?php

namespace Stackonet\WP\Framework\Supports;

use Stackonet\WP\Framework\SettingApi\SettingApi;

defined( 'ABSPATH' ) || exit;

class SettingHandler extends SettingApi {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		_deprecated_function( __CLASS__, '1.1.6', SettingApi::class );
	}
}
