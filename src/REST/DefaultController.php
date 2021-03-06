<?php

namespace Stackonet\WP\Framework\REST;

use Stackonet\WP\Framework\Traits\ApiCrudOperations;

defined( 'ABSPATH' ) || exit;

abstract class DefaultController extends ApiController {
	use ApiCrudOperations;


	public function __construct() {
		_deprecated_function( __CLASS__, '1.3.0', ApiCrudOperations::class );
	}
}
