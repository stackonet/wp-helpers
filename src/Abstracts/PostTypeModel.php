<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayObject;
use JsonSerializable;
use Stackonet\WP\Framework\Supports\Sanitize;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * PostTypeModel class
 */
abstract class PostTypeModel implements JsonSerializable {

	/**
	 * Post type name
	 */
	const POST_TYPE = 'post';

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
	 * Meta fields
	 *
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
	 * Check if metadata read
	 *
	 * @var bool
	 */
	protected $meta_data_read = false;

	/**
	 * Class constructor.
	 *
	 * @param null|int|WP_Post $post The post object or post id or null.
	 */
	public function __construct( $post = null ) {
		$post = get_post( $post );

		if ( static::POST_TYPE === $post->post_type ) {
			$this->post = $post;
			$this->read_meta_data();
		}
	}

	/**
	 * Get WP_Post
	 *
	 * @return WP_Post|null
	 */
	public function get_post(): ?WP_Post {
		return $this->post;
	}

	/**
	 * Check if Post type is valid
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->get_post() instanceof WP_Post;
	}

	/**
	 * Get array representation of the class
	 *
	 * @return array
	 */
	public function to_array(): array {
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
	public function get_id(): int {
		return $this->post->ID;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return get_the_title( $this->post->ID );
	}

	/**
	 * Get post status
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->post->post_status;
	}

	/**
	 * Get content
	 *
	 * @return string
	 */
	public function get_content(): string {
		return apply_filters( 'the_content', $this->post->post_content );
	}

	/**
	 * Get summery
	 *
	 * @return string
	 */
	public function get_excerpt(): string {
		return apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $this->post->post_excerpt, $this->post ) );
	}

	/**
	 * Get thumbnail src
	 *
	 * @param string $size The image size.
	 *
	 * @return ArrayObject|array
	 */
	public function get_thumbnail_image( string $size = 'thumbnail' ) {
		$thumbnail_id = (int) get_post_thumbnail_id( $this->post );

		return self::get_image_data( $thumbnail_id, $size );
	}

	/**
	 * Created time
	 *
	 * @return string
	 */
	public function get_created_at(): string {
		return $this->post->post_date_gmt;
	}

	/**
	 * Updated time
	 *
	 * @return string
	 */
	public function get_updated_at(): string {
		return $this->post->post_modified_gmt;
	}

	/**
	 * Get meta data
	 *
	 * @param string $key The meta key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get_meta( string $key, $default = '' ) {
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
		foreach ( static::$meta_fields as $field ) {
			$this->meta_data[ $field['meta_key'] ] = get_post_meta( $this->get_id(), $field['meta_key'], true );
		}
		$this->meta_data_read = true;
	}

	/**
	 * Get image data
	 *
	 * @param int    $image_id The image id.
	 * @param string $size The image size.
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
			'alt_text' => $alt_text,
		];
	}

	/**
	 * Get query
	 *
	 * @param array $args array of query arguments.
	 *
	 * @return WP_Query
	 */
	public static function query( array $args = [] ): WP_Query {
		$args = wp_parse_args(
			$args,
			[
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'orderby'        => [
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				],
			]
		);

		$args['post_type'] = static::POST_TYPE;

		return new WP_Query( $args );
	}


	/**
	 * Find data
	 *
	 * @param array $args array of query arguments.
	 *
	 * @return static[]|array
	 */
	public static function find( array $args = [] ): array {
		$query = static::query( $args );
		$posts = $query->get_posts();
		$items = [];
		foreach ( $posts as $post ) {
			$items[] = new static( $post );
		}

		return $items;
	}

	/**
	 * Method to create a new record
	 *
	 * @param array $data An array of elements that make up a post to insert.
	 *
	 * @return int|WP_Error The post ID on success. The value 0 or WP_Error on failure.
	 */
	public static function create( array $data ) {
		$data['post_type'] = static::POST_TYPE;

		return wp_insert_post( $data );
	}

	/**
	 * Method to create a new record
	 *
	 * @param array $data An array of elements that make up a post to update.
	 *
	 * @return int|WP_Error The post ID on success. The value 0 or WP_Error on failure.
	 */
	public static function update( array $data ) {
		$data['post_type'] = static::POST_TYPE;

		return wp_update_post( $data );
	}

	/**
	 * Delete data
	 *
	 * @param int $id The post id.
	 *
	 * @return bool
	 */
	public static function delete( int $id = 0 ): bool {
		return (bool) wp_delete_post( $id, true );
	}

	/**
	 * Send an item to trash
	 *
	 * @param int $id The post id.
	 *
	 * @return bool
	 */
	public static function trash( int $id = 0 ): bool {
		return (bool) wp_trash_post( $id );
	}

	/**
	 * Restore an item from trash
	 *
	 * @param int $id The post id.
	 *
	 * @return bool
	 */
	public static function restore( int $id = 0 ): bool {
		return (bool) wp_untrash_post( $id );
	}

	/**
	 * Get post type args
	 *
	 * @param string $menu_name The menu name.
	 * @param string $name The name in plural form.
	 * @param string $singular_name The name in singular form.
	 * @param array  $args Additional arguments.
	 *
	 * @return array
	 */
	public static function get_post_type_args(
		string $menu_name = 'Posts',
		string $name = 'Posts',
		string $singular_name = 'Post',
		array $args = []
	): array {
		$l_name          = strtolower( $name );
		$l_singular_name = strtolower( $singular_name );

		$labels       = array(
			'name'                  => $name,
			'singular_name'         => $singular_name,
			'menu_name'             => $menu_name,
			'name_admin_bar'        => $menu_name,
			'archives'              => $singular_name . ' Archives',
			'attributes'            => $singular_name . ' Attributes',
			'parent_item_colon'     => 'Parent ' . $singular_name . ':',
			'all_items'             => 'All ' . $name,
			'add_new_item'          => 'Add New ' . $singular_name,
			'add_new'               => 'Add New',
			'new_item'              => 'New ' . $singular_name,
			'edit_item'             => 'Edit ' . $singular_name,
			'update_item'           => 'Update ' . $singular_name,
			'view_item'             => 'View ' . $singular_name,
			'view_items'            => 'View ' . $name,
			'search_items'          => 'Search ' . $singular_name,
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into ' . $l_singular_name,
			'uploaded_to_this_item' => 'Uploaded to this ' . $l_singular_name,
			'items_list'            => $name . ' list',
			'items_list_navigation' => $name . ' list navigation',
			'filter_items_list'     => 'Filter ' . $l_name . ' list',
		);
		$default_args = array(
			'label'               => $name,
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
	 * @param WP_Post|int $post The post id or post object.
	 * @param string      $source Data source. admin-ui or rest-api.
	 * @param array       $values The values to be saved.
	 */
	public static function save_meta_data( $post, string $source = 'admin-ui', array $values = [] ) {
		$post = get_post( $post );

		if ( static::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( empty( $values ) ) {
			$values = $_REQUEST;
		}

		$default = [
			'meta_key'          => '',
			'post_key'          => '',
			'rest_param'        => '',
			'sanitize_callback' => [ Sanitize::class, 'deep' ],
		];

		foreach ( static::$meta_fields as $meta_field ) {
			$field     = wp_parse_args( $meta_field, $default );
			$post_key  = ! empty( $field['post_key'] ) ? $field['post_key'] : $field['meta_key'];
			$field_key = 'rest' === $source ? $field['rest_param'] : $post_key;
			$meta_key  = $field['meta_key'];
			if ( empty( $meta_key ) || empty( $field_key ) ) {
				continue;
			}
			$value = $values[ $field_key ] ?? '';
			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$value = call_user_func( $field['sanitize_callback'], $value );
			}

			update_post_meta( $post->ID, $meta_key, $value );
		}
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
