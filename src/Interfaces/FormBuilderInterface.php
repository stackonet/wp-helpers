<?php

namespace Stackonet\WP\Framework\Interfaces;

interface FormBuilderInterface {
	/**
	 * Set fields settings
	 *
	 * @param array $settings
	 */
	public function set_fields_settings( array $settings ): void;

	/**
	 * Set option name
	 *
	 * @param string $option_name
	 */
	public function set_option_name( string $option_name ): void;

	/**
	 * @param array $values
	 */
	public function set_values( array $values ): void;

	/**
	 * Render form
	 *
	 * @return string
	 */
	public function render(): string;
}
