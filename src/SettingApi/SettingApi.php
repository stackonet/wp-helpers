<?php

namespace Stackonet\WP\Framework\SettingApi;

use Stackonet\WP\Framework\Supports\Sanitize;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Very simple WordPress Settings API wrapper class
 *
 * WordPress Option Page Wrapper class that implements WordPress Settings API and
 * give you easy way to create multi tabs admin menu and
 * add setting fields with build in validation.
 *
 * @author  Sayful Islam <sayful.islam001@gmail.com>
 * @link    https://sayfulislam.com
 */
class SettingApi {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Settings options array
	 */
	protected $options = [];

	/**
	 * Settings menu fields array
	 */
	protected $menu_fields = [];

	/**
	 * Settings fields array
	 */
	protected $fields = [];

	/**
	 * Settings tabs array
	 */
	protected $panels = [];

	/**
	 * @var array
	 */
	protected $sections = [];

	/**
	 * Option name
	 *
	 * @var string
	 */
	protected $option_name = null;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add new admin menu
	 *
	 * This method is accessible outside the class for creating menu
	 *
	 * @param array $menu_fields
	 *
	 * @return WP_Error|SettingApi
	 */
	public function add_menu( array $menu_fields ) {
		if ( ! isset( $menu_fields['page_title'], $menu_fields['menu_title'], $menu_fields['menu_slug'] ) ) {
			return new WP_Error( 'field_not_set', 'Required key is not set properly for creating menu.' );
		}

		$this->menu_fields = $menu_fields;

		if ( ! empty( $menu_fields['option_name'] ) ) {
			$this->set_option_name( $menu_fields['option_name'] );
		}

		return $this;
	}

	/**
	 * @param array $input
	 *
	 * @return array
	 */
	public function sanitize_options( array $input ): array {
		$output_array = array();
		$fields       = $this->get_fields();
		$options      = $this->get_options();
		foreach ( $fields as $field ) {
			$key     = $field['id'] ?? null;
			$default = $field['default'] ?? null;
			$type    = $field['type'] ?? 'text';
			$value   = $input[ $field['id'] ] ?? $options[ $field['id'] ];

			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				$output_array[ $key ] = in_array( $value, array_keys( $field['options'] ) ) ? $value : $default;
				continue;
			}

			if ( 'checkbox' == $type ) {
				$output_array[ $key ] = Validate::checked( $value ) ? 1 : 0;
				continue;
			}

			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$output_array[ $key ] = call_user_func( $field['sanitize_callback'], $value );
				continue;
			}

			$output_array[ $key ] = $this->sanitize_by_input_type( $value, $field['type'] );
		}

		return $output_array;
	}

	/**
	 * Validate the option's value
	 *
	 * @param mixed $value
	 * @param string $type
	 *
	 * @return string|numeric
	 */
	private function sanitize_by_input_type( $value, string $type = 'text' ) {
		switch ( $type ) {
			case 'number':
				return Sanitize::number( $value );

			case 'url':
				return Sanitize::url( $value );

			case 'email':
				return Sanitize::email( $value );

			case 'date':
				return Sanitize::date( $value );

			case 'textarea':
				return Sanitize::textarea( $value );

			case 'text':
			default:
				return Sanitize::text( $value );
		}
	}

	/**
	 * Get fields default values
	 *
	 * @return array
	 */
	public function get_default_options(): array {
		$defaults = array();

		foreach ( $this->get_fields() as $field ) {
			$defaults[ $field['id'] ] = $field['default'] ?? '';
		}

		return $defaults;
	}

	/**
	 * Get options parsed with default value
	 *
	 * @return array
	 */
	public function get_options(): array {
		if ( empty( $this->options ) ) {
			$defaults      = $this->get_default_options();
			$options       = get_option( $this->get_option_name() );
			$this->options = wp_parse_args( $options, $defaults );
		}

		return $this->options;
	}

	/**
	 * Update options
	 *
	 * @param array $options
	 * @param bool $sanitize
	 */
	public function update_options( array $options, bool $sanitize = true ) {
		if ( $sanitize ) {
			$options = $this->sanitize_options( $options );
		}
		update_option( $this->get_option_name(), $options );
	}

	/**
	 * Get settings panels
	 *
	 * @return array
	 */
	public function get_panels(): array {
		$panels = apply_filters( 'stackonet/settings/panels', $this->panels );

		// Sort by priority
		usort( $panels, [ $this, 'sort_by_priority' ] );

		return $panels;
	}

	/**
	 * Set panels
	 *
	 * @param array $panels
	 *
	 * @return self
	 */
	public function set_panels( array $panels ): SettingApi {
		foreach ( $panels as $panel ) {
			$this->set_panel( $panel );
		}

		return $this;
	}

	/**
	 * Get settings sections
	 *
	 * @return array
	 */
	public function get_sections(): array {
		$sections = apply_filters( 'stackonet/settings/sections', $this->sections );

		// Sort by priority
		usort( $sections, [ $this, 'sort_by_priority' ] );

		return $sections;
	}

	/**
	 * Set sections
	 *
	 * @param array $sections
	 *
	 * @return self
	 */
	public function set_sections( array $sections ): SettingApi {
		foreach ( $sections as $section ) {
			$this->set_section( $section );
		}

		return $this;
	}

	/**
	 * Get settings fields
	 *
	 * @return array
	 */
	public function get_fields(): array {
		$fields = apply_filters( 'stackonet/settings/fields', $this->fields );

		// Sort by priority
		usort( $fields, [ $this, 'sort_by_priority' ] );

		return $fields;
	}

	/**
	 * Set fields
	 *
	 * @param array $fields
	 *
	 * @return self
	 */
	public function set_fields( array $fields ): SettingApi {
		foreach ( $fields as $field ) {
			$this->set_field( $field );
		}

		return $this;
	}

	/**
	 * Add setting page tab
	 *
	 * This method is accessible outside the class for creating page tab
	 *
	 * @param array $panel
	 *
	 * @return self
	 */
	public function set_panel( array $panel ): SettingApi {
		$panel = wp_parse_args( $panel, array(
			'id'          => '',
			'title'       => '',
			'description' => '',
			'priority'    => 200,
		) );

		$this->panels[] = $panel;

		return $this;
	}

	/**
	 * Add Setting page section
	 *
	 * @param array $section
	 *
	 * @return self
	 */
	public function set_section( array $section ): SettingApi {
		$section = wp_parse_args( $section, array(
			'id'          => 'general',
			'panel'       => '',
			'title'       => '',
			'description' => '',
			'priority'    => 200,
		) );

		$this->sections[] = $section;

		return $this;
	}

	/**
	 * Add new settings field
	 * This method is accessible outside the class for creating settings field
	 *
	 * @param array $field
	 *
	 * @return self
	 */
	public function set_field( array $field ): SettingApi {
		$field = wp_parse_args( $field, array(
			'type'        => 'text',
			'section'     => 'general',
			'id'          => '',
			'title'       => '',
			'description' => '',
			'priority'    => 200,
		) );

		$this->fields[ $field['id'] ] = $field;

		return $this;
	}

	/**
	 * Sort array by its priority field
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return mixed
	 */
	public function sort_by_priority( array $array1, array $array2 ) {
		return $array1['priority'] - $array2['priority'];
	}

	/**
	 * Get option name
	 *
	 * @return string
	 */
	public function get_option_name(): ?string {
		if ( ! empty( $this->menu_fields['option_name'] ) ) {
			return $this->menu_fields['option_name'];
		}

		return $this->option_name;
	}

	/**
	 * @param string $option_name
	 *
	 * @return SettingApi
	 */
	public function set_option_name( string $option_name ): SettingApi {
		$this->option_name = $option_name;

		return $this;
	}
}
