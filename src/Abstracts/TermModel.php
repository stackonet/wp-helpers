<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayObject;
use JsonSerializable;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_Term;
use WP_Term_Query;

defined( 'ABSPATH' ) || exit;

abstract class TermModel implements JsonSerializable {

	/**
	 * Taxonomy name
	 */
	const TAXONOMY = 'category';

	/**
	 * WP_Term object
	 *
	 * @var WP_Term
	 */
	protected $term;

	/**
	 * Meta data
	 *
	 * @var array
	 */
	protected $meta_data = [];

	/**
	 * @var array
	 * Example
	 * [
	 *  [
	 *      'meta_key'          => '_image_id',
	 *      'post_key'          => '_image_id',
	 *      'rest_param'        => 'image_id',
	 *      'sanitize_callback' => 'absint'
	 *  ]
	 * ]
	 */
	protected static $meta_fields = [];

	/**
	 * Check if meta data read
	 *
	 * @var bool
	 */
	protected $meta_data_read = false;

	/**
	 * Class constructor.
	 *
	 * @param int|WP_Term|null $term
	 */
	public function __construct( $term = null ) {
		if ( is_numeric( $term ) || $term instanceof WP_Term ) {
			$term = get_term( $term, static::TAXONOMY );

			if ( $term->taxonomy == static::TAXONOMY ) {
				$this->term = $term;
				$this->read_meta_data();
			}
		}
	}

	/**
	 * Get array representation of the class
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'id'    => $this->get_id(),
			'name'  => $this->get_name(),
			'slug'  => $this->get_slug(),
			'count' => $this->get_count(),
		];
	}

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->term->term_id;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->term->name;
	}

	/**
	 * Get slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->term->slug;
	}

	/**
	 * Get count
	 *
	 * @return int
	 */
	public function get_count() {
		return $this->term->count;
	}

	/**
	 * Get meta data
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get_meta( $key, $default = '' ) {
		if ( isset( $this->meta_data[ $key ] ) ) {
			return $this->meta_data[ $key ];
		}

		$value = get_term_meta( $this->term->term_id, $key, true );

		return ! empty( $value ) ? $value : $default;
	}

	/**
	 * Read meta data
	 */
	protected function read_meta_data() {
		$this->meta_data_read = true;
	}

	/**
	 * @param int    $image_id
	 * @param string $size
	 *
	 * @return array|ArrayObject
	 */
	public static function get_image_data( int $image_id, string $size = 'thumbnail' ) {
		$image = new ArrayObject();
		$src   = wp_get_attachment_image_src( $image_id, $size );
		if ( ! ( is_array( $src ) && Validate::url( $src[0] ) ) ) {
			return $image;
		}

		$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		return [
			'id'       => $image_id,
			'url'      => $src[0],
			'width'    => $src[1],
			'height'   => $src[2],
			'alt_text' => $alt_text
		];
	}

	/**
	 * Get query
	 *
	 * @param array $args
	 *
	 * @return WP_Term_Query
	 */
	public static function query( array $args = [] ) {
		$args['taxonomy'] = static::TAXONOMY;

		return new WP_Term_Query( $args );
	}

	/**
	 * Method to create a new record
	 *
	 * @param string $term
	 * @param array  $args
	 *
	 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`, WP_Error otherwise.
	 */
	public static function create( $term, array $args = [] ) {
		return wp_insert_term( $term, static::TAXONOMY, $args );
	}

	/**
	 * Method to create a new record
	 *
	 * @param int   $term_id
	 * @param array $args
	 *
	 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`, WP_Error otherwise.
	 */
	public static function update( int $term_id, array $args ) {
		return wp_update_term( $term_id, static::TAXONOMY, $args );
	}

	/**
	 * Delete data
	 *
	 * @param int $term_id
	 *
	 * @return bool
	 */
	public static function delete( $term_id = 0 ) {
		return (bool) wp_delete_term( $term_id, static::TAXONOMY );
	}

	/**
	 * Find for a post
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public static function find_for_post( $post_id ) {
		$terms = wp_get_post_terms( $post_id, static::TAXONOMY );
		$data  = [];
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				$data[] = new static( $term );
			}
		}

		return $data;
	}

	/**
	 * @param string $menu_name
	 * @param string $name
	 * @param string $singular_name
	 * @param array  $args
	 *
	 * @return array
	 */
	public static function get_term_args(
		string $menu_name = 'Categories',
		string $name = 'Categories',
		string $singular_name = 'Category',
		array $args = []
	) {
		$l_name = strtolower( $name );

		$labels       = array(
			'name'                       => $name,
			'singular_name'              => $singular_name,
			'menu_name'                  => $menu_name,
			'all_items'                  => 'All ' . $name,
			'parent_item'                => 'Parent ' . $singular_name,
			'parent_item_colon'          => 'Parent ' . $singular_name . ':',
			'new_item_name'              => 'New ' . $singular_name . ' Name',
			'add_new_item'               => 'Add New ' . $singular_name,
			'edit_item'                  => 'Edit ' . $singular_name,
			'update_item'                => 'Update ' . $singular_name,
			'view_item'                  => 'View ' . $singular_name,
			'separate_items_with_commas' => 'Separate ' . $l_name . ' with commas',
			'add_or_remove_items'        => 'Add or remove ' . $l_name,
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular ' . $name,
			'search_items'               => 'Search ' . $name,
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No ' . $l_name,
			'items_list'                 => $name . ' list',
			'items_list_navigation'      => $name . ' list navigation',
		);
		$default_args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
			'show_in_rest'      => true,
		);

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Save category fields
	 *
	 * @param int    $term_id  Term ID being saved.
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $source
	 */
	public static function save_form_fields( $term_id, $taxonomy, $source = 'admin-ui' ) {
		if ( static::TAXONOMY !== $taxonomy ) {
			return;
		}

		$default = [
			'meta_key'          => '',
			'post_key'          => '',
			'rest_param'        => '',
			'sanitize_callback' => 'sanitize_text_field'
		];

		foreach ( static::$meta_fields as $meta_field ) {
			$field     = wp_parse_args( $meta_field, $default );
			$post_key  = ! empty( $field['post_key'] ) ? $field['post_key'] : $field['meta_key'];
			$field_key = $source == 'rest' ? $field['rest_param'] : $post_key;
			$meta_key  = $field['meta_key'];
			if ( empty( $meta_key ) || empty( $field_key ) ) {
				continue;
			}
			$value = isset( $_REQUEST[ $field_key ] ) ? $_REQUEST[ $field_key ] : '';
			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$value = call_user_func( $field['sanitize_callback'], $value );
			}

			update_term_meta( $term_id, $meta_key, $value );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
