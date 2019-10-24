<?php

namespace Stackonet\WP\Framework\Abstracts;

defined( 'ABSPATH' ) || exit;

class AbstractModel extends Data {

	/**
	 * Default data
	 * Must contain all table columns name in (key => value) format
	 *
	 * @var array
	 */
	protected $default_data = [];

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
}
