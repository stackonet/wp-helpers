<?php

namespace Stackonet\WP\Framework\REST;

use Stackonet\WP\Framework\Traits\ApiResponse;
use Stackonet\WP\Framework\Traits\ApiUtils;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class ApiController
 *
 * @package Stackonet\WP\Framework\REST
 */
class ApiController extends WP_REST_Controller {
	use ApiResponse, ApiUtils;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'stackonet/v1';
}
