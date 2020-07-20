<?php

namespace Stackonet\WP\Framework\Abstracts;

use Stackonet\WP\Framework\Interfaces\DataStoreInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class DatabaseModel
 * A thin layer using wpdb database class form rapid development
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
abstract class DatabaseModel extends Data implements DataStoreInterface {

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * The type of the primary key
	 * '%s' for string and '%d' for integer
	 *
	 * @var string
	 */
	protected $primaryKeyType = '%d';

	/**
	 * Column name for holding author id
	 *
	 * @var string
	 */
	protected $created_by = 'created_by';

	/**
	 * Column name for holding date time when creating record
	 *
	 * @var string
	 */
	protected $created_at = 'created_at';

	/**
	 * Column name for holding date time when updating record
	 *
	 * @var string
	 */
	protected $updated_at = 'updated_at';

	/**
	 * Column name for holding date time when updating record
	 *
	 * @var string
	 */
	protected $deleted_at = 'deleted_at';

	/**
	 * The number of models to return for pagination.
	 *
	 * @var int
	 */
	protected $perPage = 20;

	/**
	 * Cache group
	 *
	 * @var string
	 */
	protected $cache_group = 'stackonet';

	/**
	 * Table column info
	 *
	 * @var array
	 */
	protected static $columns = [];

	/**
	 * Model constructor.
	 *
	 * @param mixed $data
	 */
	public function __construct( $data = [] ) {
		if ( $data ) {
			$this->data = $this->read( $data );
		}
	}

	/**
	 * Find multiple records from database
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function find( $args = [] ) {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_collection( $args );
		$items     = $this->get_cache( $cache_key );
		if ( false === $items ) {
			list( $per_page, $offset ) = $this->get_pagination_and_order_data( $args );
			$order_by = $this->get_order_by( $args );

			$query = "SELECT * FROM {$table} WHERE 1=1";

			if ( isset( $args[ $this->created_by ] ) && is_numeric( $args[ $this->created_by ] ) ) {
				$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $args[ $this->created_by ] ) );
			}

			$query .= " ORDER BY {$order_by}";
			if ( $per_page > 0 ) {
				$query .= $wpdb->prepare( " LIMIT %d", $per_page );
			}
			if ( $offset >= 0 ) {
				$query .= $wpdb->prepare( " OFFSET %d", $offset );
			}
			$items = $wpdb->get_results( $query, ARRAY_A );

			// Set cache for one day
			$this->set_cache( $cache_key, $items, DAY_IN_SECONDS );
		}

		return $items;
	}

	/**
	 * Find record by id
	 *
	 * @param int $id
	 *
	 * @return array|self
	 */
	public function find_by_id( $id ) {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_single_item( $id );
		$item      = $this->get_cache( $cache_key );
		if ( false === $item ) {
			$sql  = "SELECT * FROM {$table} WHERE {$this->primaryKey} = {$this->primaryKeyType}";
			$item = $wpdb->get_row( $wpdb->prepare( $sql, $id ), ARRAY_A );

			// Set cache
			$this->set_cache( $cache_key, $item );
		}

		return $item;
	}

	/**
	 * Create data
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public function create( array $data ) {
		global $wpdb;
		$table        = $wpdb->prefix . $this->table;
		$current_time = current_time( 'mysql' );

		$tableColumns = $this->get_column_info();

		$_data = [];
		foreach ( $tableColumns as $key => $tableColumn ) {
			$temp_data     = isset( $data[ $key ] ) ? $data[ $key ] : $tableColumn['default'];
			$_data[ $key ] = $this->serialize( $temp_data );
		}

		if ( array_key_exists( $this->primaryKey, $_data ) ) {
			unset( $_data[ $this->primaryKey ] );
		}

		// Update Author ID
		if ( array_key_exists( $this->created_by, $tableColumns ) ) {
			if ( isset( $data[ $this->created_by ] ) && is_numeric( $data[ $this->created_by ] ) ) {
				$_data[ $this->created_by ] = intval( $data[ $this->created_by ] );
			} else {
				$_data[ $this->created_by ] = get_current_user_id();
			}
		}

		// Update created time
		if ( array_key_exists( $this->created_at, $tableColumns ) ) {
			$_data[ $this->created_at ] = $current_time;
		}

		// Update updated time
		if ( array_key_exists( $this->updated_at, $tableColumns ) ) {
			$_data[ $this->updated_at ] = $current_time;
		}

		// Set deleted at time as null
		if ( array_key_exists( $this->deleted_at, $tableColumns ) ) {
			$_data[ $this->deleted_at ] = null;
		}

		$format = $this->get_data_format_for_db( array_keys( $_data ) );

		$wpdb->insert( $table, $_data, array_values( $format ) );

		// Update cache change
		$this->set_cache_last_changed();

		return $wpdb->insert_id;
	}

	/**
	 * Method to read a record.
	 *
	 * @param mixed $data
	 *
	 * @return array|self
	 */
	public function read( $data ) {
		if ( $data instanceof Data ) {
			return $data->data;
		}

		if ( is_numeric( $data ) ) {
			$item = $this->find_by_id( $data );
			if ( $item instanceof Data ) {
				return $item->data;
			}

			if ( is_array( $item ) ) {
				$data = $item;
			}
		}

		$default = $this->get_default_data();

		if ( is_array( $data ) ) {
			$item = [];
			foreach ( $default as $columnName => $default_value ) {
				$temp_data           = isset( $data[ $columnName ] ) ? $data[ $columnName ] : $default_value;
				$item[ $columnName ] = $this->unserialize( $temp_data );
			}

			return $item;
		}

		return $default;
	}

	/**
	 * Update data
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update( array $data ) {
		global $wpdb;
		$table        = $this->get_table_name();
		$id           = isset( $data[ $this->primaryKey ] ) ? intval( $data[ $this->primaryKey ] ) : 0;
		$current_time = current_time( 'mysql' );

		$item = $this->find_by_id( $id );
		if ( empty( $item ) ) {
			return false;
		}

		// Database table columns
		$columnsNames = $this->get_columns_names();

		$_data = [];
		foreach ( $data as $columnName => $nawValue ) {
			if ( ! in_array( $columnName, $columnsNames ) ) {
				continue;
			}
			$current_data = isset( $item[ $columnName ] ) ? $item[ $columnName ] : null;
			$temp_data    = isset( $data[ $columnName ] ) ? $data[ $columnName ] : $current_data;
			if ( $temp_data == $current_data ) {
				continue;
			}
			$_data[ $columnName ] = $this->serialize( $temp_data );
		}
		$_data[ $this->primaryKey ] = $id;

		// Update updated time
		if ( in_array( $this->updated_at, $columnsNames ) ) {
			$_data[ $this->updated_at ] = $current_time;
		}

		// Update deleted time
		if ( in_array( $this->deleted_at, $columnsNames ) ) {
			$_data[ $this->deleted_at ] = null;
		}

		$dataFormat = $this->get_data_format_for_db( array_keys( $_data ) );

		if ( $wpdb->update( $table, $_data, [ $this->primaryKey => $id ], array_values( $dataFormat ), $this->primaryKeyType ) ) {
			return true;
		}

		// Delete cache
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return false;
	}

	/**
	 * Delete data
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete( $id = 0 ) {
		global $wpdb;
		$table = $this->get_table_name();

		$query = $wpdb->delete( $table, [ $this->primaryKey => $id ], $this->primaryKeyType );

		// Delete cache
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Delete multiple records
	 *
	 * @param array $ids
	 *
	 * @return bool
	 */
	public function batch_delete( array $ids = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$ids   = array_map( 'absint', $ids );
		$sql   = "DELETE FROM `{$table}` WHERE {$this->primaryKey} IN(" . implode( ',', $ids ) . ")";

		$query = $wpdb->query( $sql );

		// Delete cache
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Send an item to trash
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function trash( $id ) {
		global $wpdb;
		$table = $this->get_table_name();
		$query = $wpdb->update( $table, [ $this->deleted_at => current_time( 'mysql' ) ],
			[ $this->primaryKey => $id ]
		);

		// Delete cache
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Trash multiple records
	 *
	 * @param array $ids
	 *
	 * @return bool
	 */
	public function batch_trash( array $ids = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$ids   = array_map( 'absint', $ids );
		$sql   = $wpdb->prepare( "UPDATE `{$table}` SET `{$this->deleted_at}` = %s", current_time( 'mysql' ) );
		$sql   .= " WHERE {$this->primaryKey} IN(" . implode( ',', $ids ) . ")";

		$query = $wpdb->query( $sql );

		// Delete cache
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Restore an item from trash
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function restore( $id ) {
		global $wpdb;
		$table = $this->get_table_name();
		$query = $wpdb->update( $table, [ $this->deleted_at => null ], [ $this->primaryKey => $id ] );

		// Delete cache
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return ( false !== $query );
	}

	/**
	 * Restore multiple records
	 *
	 * @param array $ids
	 *
	 * @return bool
	 */
	public function batch_restore( array $ids = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$ids   = array_map( 'absint', $ids );
		$sql   = "UPDATE `{$table}` SET `{$this->deleted_at}` = NULL";
		$sql   .= " WHERE {$this->primaryKey} IN(" . implode( ',', $ids ) . ")";

		$query = $wpdb->query( $sql );

		// Delete cache
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
	}

	/**
	 * Get pagination data
	 *
	 * @param int $total_items
	 * @param int $per_page
	 * @param int $current_page
	 *
	 * @return array
	 */
	public static function get_pagination( $total_items, $per_page = 10, $current_page = 1 ) {
		return array(
			"total_items"  => $total_items,
			"per_page"     => $per_page,
			"current_page" => $current_page,
			"total_pages"  => ceil( $total_items / $per_page ),
		);
	}

	/**
	 * Generate pagination metadata
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getPaginationMetadata( array $args ) {
		_deprecated_function( __METHOD__, '1.1.5', __CLASS__ . '::get_pagination()' );
		$data = wp_parse_args( $args, array(
			"totalCount"     => 0,
			"limit"          => 10,
			"currentPage"    => 1,
			"offset"         => 0,
			"previousOffset" => null,
			"nextOffset"     => null,
			"pageCount"      => 0,
		) );
		if ( ! isset( $args['currentPage'] ) && isset( $args['offset'] ) ) {
			$data['currentPage'] = ( $args['offset'] / $data['limit'] ) + 1;
		}
		if ( ! isset( $args['offset'] ) && isset( $args['currentPage'] ) ) {
			$offset         = ( $data['currentPage'] - 1 ) * $data['limit'];
			$data['offset'] = max( $offset, 0 );
		}
		$previousOffset         = ( $data['currentPage'] - 2 ) * $data['limit'];
		$nextOffset             = $data['currentPage'] * $data['limit'];
		$data['previousOffset'] = ( $previousOffset < 0 || $previousOffset > $data['totalCount'] ) ? null : $previousOffset;
		$data['nextOffset']     = ( $nextOffset < 0 || $nextOffset > $data['totalCount'] ) ? null : $nextOffset;
		$data['pageCount']      = ceil( $data['totalCount'] / $data['limit'] );

		return $data;
	}

	/**
	 * Serialize array and object data
	 *
	 * @param mixed $data
	 *
	 * @return string
	 */
	protected function serialize( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			return serialize( $data );
		}

		return $data;
	}

	/**
	 * Unserialize value only if it was serialized.
	 *
	 * @param string $data Maybe unserialized original, if is needed.
	 *
	 * @return mixed Unserialized data can be any type.
	 */
	protected function unserialize( $data ) {
		if ( is_serialized( $data ) ) {
			return @unserialize( $data );
		}

		return $data;
	}

	/**
	 * Get pagination and order data
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	protected function get_pagination_and_order_data( array $args ) {
		$paged        = isset( $args['paged'] ) ? absint( $args['paged'] ) : 1;
		$current_page = isset( $args['page'] ) ? absint( $args['page'] ) : $paged;

		$per_page = isset( $args['per_page'] ) ? intval( $args['per_page'] ) : $this->perPage;
		$offset   = $this->calculate_offset( $current_page, $per_page );

		$orderby = isset( $args['orderby'] ) && in_array( $args['orderby'], $this->get_columns_names() )
			? $args['orderby'] : $this->primaryKey;
		$order   = isset( $args['order'] ) && 'ASC' == $args['order'] ? 'ASC' : 'DESC';

		return array( $per_page, $offset, $orderby, $order );
	}

	/**
	 * Calculate offset
	 *
	 * @param int $current_page
	 * @param int $per_page
	 *
	 * @return int
	 */
	protected function calculate_offset( $current_page = 1, $per_page = 0 ) {
		if ( empty( $per_page ) ) {
			$per_page = $this->perPage;
		}

		$page = max( 1, $current_page );

		return (int) ( $page - 1 ) * $per_page;
	}

	/**
	 * Get order_by data
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_order_by( array $args ) {
		$columnsNames = $this->get_columns_names();
		$orders_by    = isset( $args['order_by'] ) ? $args['order_by'] : [];
		$orders_by    = is_string( $orders_by ) ? explode( ",", $orders_by ) : $orders_by;
		$valid_orders = [ 'ASC', 'DESC' ];

		if ( count( $orders_by ) < 1 ) {
			// For backward compatibility
			$column_name = isset( $args['orderby'] ) && in_array( $args['orderby'], $columnsNames ) ? $args['orderby'] : $this->primaryKey;
			$order       = isset( $args['order'] ) && 'ASC' == strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
			$orders_by[] = $column_name . ' ' . $order;
		}

		$final_order_by = [];
		foreach ( $orders_by as $order_by ) {
			$_order      = explode( " ", trim( $order_by ) );
			$column_name = ( isset( $_order[0] ) && in_array( $_order[0], $columnsNames ) ) ? $_order[0] : '';
			$order       = ( isset( $_order[1] ) && in_array( strtoupper( $_order[1] ), $valid_orders ) ) ? $_order[1] : '';

			if ( empty( $column_name ) || empty( $order ) ) {
				continue;
			}
			$final_order_by[] = $column_name . ' ' . $order;
		}

		return implode( ", ", $final_order_by );
	}

	/**
	 * Get integer data type
	 *
	 * @return array
	 */
	public static function get_integer_data_type() {
		return [ 'bit', 'int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'bool', 'boolean' ];
	}

	/**
	 * get float data type
	 *
	 * @return array
	 */
	public static function get_float_data_type() {
		return [ 'float', 'double', 'decimal', 'dec' ];
	}

	/**
	 * Get column name
	 *
	 * @return array
	 */
	public function get_columns_names() {
		return array_keys( $this->get_column_info() );
	}

	/**
	 * Get default data
	 *
	 * @return array
	 */
	public function get_default_data() {
		$columns = $this->get_column_info();
		$data    = [];
		foreach ( $columns as $columnName => $info ) {
			if ( $info['nullable'] ) {
				$default = null;
			} else {
				$default = isset( $info['default'] ) ? $info['default'] : '';
			}

			if ( in_array( $columnName, static::get_integer_data_type() ) ) {
				$default = 0;
			}

			if ( in_array( $columnName, static::get_float_data_type() ) ) {
				$default = 0;
			}

			$data[ $columnName ] = $default;
		}

		return $data;
	}

	/**
	 * Get data format for db
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function get_data_format_for_db( array $fields = [] ) {
		$columns = $this->get_column_info();
		if ( empty( $fields ) ) {
			return wp_list_pluck( $columns, 'data_format' );
		}

		$formats = [];
		foreach ( $fields as $field ) {
			if ( isset( $columns[ $field ] ) ) {
				$formats[ $field ] = $columns[ $field ]['data_format'];
			}
		}

		return $formats;
	}

	/**
	 * Get column info
	 *
	 * @return array
	 */
	public function get_column_info() {
		$table = $this->get_table_name( $this->table );

		if ( ! empty( static::$columns[ $table ] ) ) {
			return static::$columns[ $table ];
		}

		$cache_key   = $table . ':column_info';
		$column_info = $this->get_cache( $cache_key );
		if ( $column_info === false ) {
			global $wpdb;
			$results = $wpdb->get_results( "SHOW COLUMNS FROM $table", ARRAY_A );

			foreach ( $results as $column ) {
				$length = static::get_type_and_length( $column );

				static::$columns[ $table ][ $column['Field'] ] = [
					'field'       => $column['Field'],
					'default'     => $column['Default'],
					'type'        => $length['type'],
					'length'      => $length['length'],
					'nullable'    => strtolower( $column['Null'] ) == 'yes',
					'data_format' => $this->get_data_format_for_type( $length['type'] ),
				];
			}

			$this->set_cache( $cache_key, static::$columns[ $table ] );
			$column_info = static::$columns[ $table ];
		}

		return $column_info;
	}

	/**
	 * Get table name
	 *
	 * @param string $table
	 *
	 * @return string
	 */
	public function get_table_name( $table = null ) {
		if ( empty( $table ) ) {
			$table = $this->table;
		}
		global $wpdb;
		if ( false !== strpos( $table, $wpdb->prefix ) ) {
			return $table;
		}

		return $wpdb->prefix . $table;
	}

	/**
	 * Get type and max length
	 *
	 * @param array $column
	 *
	 * @return array|bool
	 */
	public static function get_type_and_length( array $column ) {
		$typeinfo = explode( '(', $column['Type'] );
		$type     = strtolower( $typeinfo[0] );
		$length   = false;
		if ( ! empty( $typeinfo[1] ) ) {
			$length = trim( $typeinfo[1], ')' );
		}

		switch ( $type ) {
			case 'char':
			case 'varchar':
				return array( 'type' => 'char', 'length' => (int) $length, );

			case 'binary':
			case 'varbinary':
				return array( 'type' => 'byte', 'length' => (int) $length, );

			case 'tinyblob':
			case 'tinytext':
				return array( 'type' => 'byte', 'length' => 255, ); // 2^8 - 1

			case 'blob':
			case 'text':
				return array( 'type' => 'byte', 'length' => 65535, ); // 2^16 - 1

			case 'mediumblob':
			case 'mediumtext':
				return array( 'type' => 'byte', 'length' => 16777215, ); // 2^24 - 1

			case 'longblob':
			case 'longtext':
				return array( 'type' => 'byte', 'length' => 4294967295, ); // 2^32 - 1

			default:
				return array( 'type' => $type, 'length' => $length, );
		}
	}

	/**
	 * Get data format for db
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_data_format_for_type( $type ) {
		if ( in_array( $type, static::get_integer_data_type() ) ) {
			return '%d';
		}
		if ( in_array( $type, static::get_float_data_type() ) ) {
			return '%f';
		}

		return '%s';
	}

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 *
	 * @param int|string $key The key under which the cache contents are stored.
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
	 * @see WP_Object_Cache::get()
	 */
	public function get_cache( $key ) {
		return wp_cache_get( $key, $this->cache_group );
	}

	/**
	 * Saves the data to the cache.
	 *
	 * @param int|string $key The cache key to use for retrieval later.
	 * @param mixed $data The contents to store in the cache.
	 * @param int $expire Optional. When to expire the cache contents, in seconds. Default 0 (no expiration).
	 */
	public function set_cache( $key, $data, $expire = 0 ) {
		if ( empty( $expire ) ) {
			$expire = MONTH_IN_SECONDS;
		}
		wp_cache_set( $key, $data, $this->cache_group, $expire );
	}

	/**
	 * Removes the cache contents matching key and group.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 */
	public function delete_cache( $key ) {
		wp_cache_delete( $key, $this->cache_group );
		$this->set_cache_last_changed();
	}

	/**
	 * Set cache last changed
	 * Use this method when you create, update or delete item
	 */
	public function set_cache_last_changed() {
		wp_cache_set( 'last_changed', microtime(), $this->cache_group );
	}

	/**
	 * Get cache key for collection
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_cache_key_for_collection( array $args = [] ) {
		$last_changed = wp_cache_get_last_changed( $this->cache_group );
		$hash         = md5( serialize( $args ) );
		$table        = $this->get_table_name();

		return "$table:$hash:$last_changed";
	}

	/**
	 * Get cache key for single item
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function get_cache_key_for_single_item( $id ) {
		$table = $this->get_table_name();

		return "$table:$id";
	}
}
