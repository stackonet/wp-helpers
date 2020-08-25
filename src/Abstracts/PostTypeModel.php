<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayObject;
use JsonSerializable;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

abstract class PostTypeModel implements JsonSerializable {

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected static $post_type = 'post';

	/**
	 * WP_Post object
	 *
	 * @var WP_Post
	 */
	protected $post;

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
	 *      'meta_key_name'     => '_image_id',
	 *      'post_key_name'     => '_image_id',
	 *      'rest_param_name'   => 'image_id',
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
	 * @param null|int|WP_Post $post
	 */
	public function __construct( $post = null ) {
		$post = get_post( $post );

		if ( $post->post_type == static::$post_type ) {
			$this->post = $post;
			$this->read_meta_data();
		}
	}

	/**
	 * Get array representation of the class
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'id'      => $this->get_id(),
			'title'   => $this->get_title(),
			'content' => $this->get_content(),
			'created' => mysql_to_rfc3339( $this->get_created_at() ),
			'updated' => mysql_to_rfc3339( $this->get_updated_at() ),
		];
	}

	/**
	 * Get post id
	 *
	 * @return int
	 */
	public function get_id() {
		return intval( $this->post->ID );
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function get_title() {
		return get_the_title( $this->post->ID );
	}

	/**
	 * Get content
	 *
	 * @return string
	 */
	public function get_content() {
		return apply_filters( 'the_content', $this->post->post_content );
	}

	/**
	 * Get thumbnail src
	 *
	 * @param string $size
	 *
	 * @return ArrayObject|array
	 */
	public function get_thumbnail_image( $size = 'thumbnail' ) {
		$thumbnail_id = (int) get_post_thumbnail_id( $this->post );

		return self::get_image_data( $thumbnail_id, $size );
	}

	/**
	 * Created time
	 *
	 * @return string
	 */
	public function get_created_at() {
		return $this->post->post_date;
	}

	/**
	 * Updated time
	 *
	 * @return string
	 */
	public function get_updated_at() {
		return $this->post->post_modified;
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

		$value = get_post_meta( $this->post->ID, $key, true );

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

		return [ 'id' => $image_id, 'url' => $src[0], 'width' => $src[1], 'height' => $src[2], ];
	}

	/**
	 * Get query
	 *
	 * @param array $args
	 *
	 * @return WP_Query
	 */
	public static function query( array $args = [] ) {
		$args = wp_parse_args( $args, array(
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'order'          => 'ASC',
			'orderby'        => 'date',
		) );

		$args['post_type'] = static::$post_type;

		return new WP_Query( $args );
	}

	/**
	 * Method to create a new record
	 *
	 * @param array $data
	 *
	 * @return int|WP_Error The post ID on success. The value 0 or WP_Error on failure.
	 */
	public static function create( array $data ) {
		$data['post_type'] = static::$post_type;

		return wp_insert_post( $data );
	}

	/**
	 * Method to create a new record
	 *
	 * @param array $data
	 *
	 * @return int|WP_Error The post ID on success. The value 0 or WP_Error on failure.
	 */
	public static function update( array $data ) {
		$data['post_type'] = static::$post_type;

		return wp_update_post( $data );
	}

	/**
	 * Delete data
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public static function delete( $id = 0 ) {
		return (bool) wp_delete_post( $id, true );
	}

	/**
	 * Send an item to trash
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public static function trash( $id ) {
		return (bool) wp_trash_post( $id );
	}

	/**
	 * Restore an item from trash
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public static function restore( $id ) {
		return (bool) wp_untrash_post( $id );
	}

	/**
	 * Get post type args
	 *
	 * @param string $menu_name
	 * @param array  $args
	 *
	 * @return array
	 */
	public static function get_post_type_args( $menu_name = 'Posts', array $args = [] ) {
		$labels       = array(
			'name'                  => 'Posts',
			'singular_name'         => 'Post',
			'menu_name'             => $menu_name,
			'name_admin_bar'        => $menu_name,
			'archives'              => 'Post Archives',
			'attributes'            => 'Post Attributes',
			'parent_item_colon'     => 'Parent Post:',
			'all_items'             => 'All Posts',
			'add_new_item'          => 'Add New Post',
			'add_new'               => 'Add New',
			'new_item'              => 'New Post',
			'edit_item'             => 'Edit Post',
			'update_item'           => 'Update Post',
			'view_item'             => 'View Post',
			'view_items'            => 'View Posts',
			'search_items'          => 'Search Post',
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into post',
			'uploaded_to_this_item' => 'Uploaded to this post',
			'items_list'            => 'Posts list',
			'items_list_navigation' => 'Posts list navigation',
			'filter_items_list'     => 'Filter posts list',
		);
		$default_args = array(
			'label'               => 'Post',
			'description'         => 'A list of posts',
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5.5565,
			'menu_icon'           => 'dashicons-media-document',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'rewrite'             => false,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Save meta data
	 *
	 * @param WP_Post|int $post
	 * @param string      $source
	 */
	public static function save_meta_data( $post, $source = 'admin-ui' ) {
		$post = get_post( $post );

		if ( $post->post_type != static::$post_type ) {
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

			update_post_meta( $post->ID, $meta_key, $value );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
