<?php

namespace Stackonet\WP\Framework\Abstracts;

use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use Stackonet\WP\Framework\Traits\Cacheable;
use Stackonet\WP\Framework\Traits\TableInfo;

defined( 'ABSPATH' ) || exit;

/**
 * Class DatabaseModel
 * A thin layer using wpdb database class form rapid development
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
abstract class DatabaseModel extends Data implements DataStoreInterface {

	use Cacheable, TableInfo;

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
	 * Model constructor.
	 *
	 * @param mixed $data
	 */
	public function __construct( $data = [] ) {
		if ( $data ) {
			$this->data = $this->read( $data );
		}
		$this->primaryKey     = static::get_primary_key( $this->get_table_name() );
		$this->primaryKeyType = static::get_primary_key_data_format( $this->get_table_name() );
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
			$status   = isset( $args['status'] ) ? $args['status'] : null;

			$query = "SELECT * FROM {$table} WHERE 1=1";

			if ( isset( $args[ $this->created_by ] ) && is_numeric( $args[ $this->created_by ] ) ) {
				$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $args[ $this->created_by ] ) );
			}

			if ( isset( $args[ $this->primaryKey . '__in' ] ) && is_array( $args[ $this->primaryKey . '__in' ] ) ) {
				if ( $this->primaryKeyType == '%d' ) {
					$ids__in = array_map( 'intval', $args[ $this->primaryKey . '__in' ] );
					$query   .= " AND {$this->primaryKey} IN(" . implode( ",", $ids__in ) . ")";
				} else {
					$ids__in = array_map( 'esc_sql', $args[ $this->primaryKey . '__in' ] );
					$query   .= " AND {$this->primaryKey} IN('" . implode( "', '", $ids__in ) . "')";
				}
			}

			if ( in_array( $this->deleted_at, static::get_columns_names( $table ) ) ) {
				if ( 'trash' == $status ) {
					$query .= " AND {$this->deleted_at} IS NOT NULL";
				} else {
					$query .= " AND {$this->deleted_at} IS NULL";
				}
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
		$table = $this->get_table_name();

		list( $_data, $_format ) = $this->format_item_for_db( $data, static::get_default_data( $table ) );

		$wpdb->insert( $table, $_data, $_format );

		// Update cache change
		$this->set_cache_last_changed();

		return $wpdb->insert_id;
	}

	/**
	 * Create multiple record
	 *
	 * @param array $data
	 *
	 * @return int[]
	 */
	public function create_multiple( array $data ) {
		global $wpdb;
		$table         = $this->get_table_name();
		$current_time  = current_time( 'mysql', true );
		$columns_names = static::get_columns_names( $table );
		$default       = static::get_default_data( $table );
		$primary_key   = static::get_primary_key( $table );

		$last_row    = $wpdb->get_row(
			"SELECT {$primary_key} FROM {$table} ORDER BY {$primary_key} DESC LIMIT 1;",
			ARRAY_A
		);
		$last_row_id = isset( $last_row[ $primary_key ] ) ? intval( $last_row[ $primary_key ] ) : 0;

		$values = [];
		foreach ( $data as $index => $item ) {
			list( $_data, $_format ) = $this->format_item_for_db( $item, $default, $current_time );

			$sanitize_data = [];
			foreach ( $_data as $column_name => $column_value ) {
				if ( is_null( $column_value ) ) {
					continue;
				}
				$sanitize_data[ $column_name ] = $column_value;
			}

			$values[] = $wpdb->prepare( "(" . implode( ", ", $_format ) . ")", $sanitize_data );
		}

		if ( in_array( $this->primaryKey, $columns_names ) ) {
			$index = array_search( $this->primaryKey, $columns_names );
			unset( $columns_names[ $index ] );
		}

		$sql   = "INSERT INTO `{$table}` (" . implode( ", ", $columns_names ) . ") VALUES \n" . implode( ",\n", $values ) . ";";
		$query = $wpdb->query( $sql );

		// Update cache change
		$this->set_cache_last_changed();

		$ids = [];
		if ( $query ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$primary_key} FROM {$table} WHERE {$primary_key} > %s ORDER BY {$primary_key} ASC;",
					$last_row_id
				),
				ARRAY_A
			);
			$ids     = array_map( 'intval', wp_list_pluck( $results, $primary_key ) );
		}

		return $ids;
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
		$current_time = current_time( 'mysql', true );

		$item = $this->find_by_id( $id );
		if ( empty( $item ) ) {
			return false;
		}

		// Database table columns
		$columnsNames = static::get_columns_names( $table );

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

		$dataFormat = static::get_data_format_for_db( $table, $_data );

		if ( $wpdb->update( $table, $_data, [ $this->primaryKey => $id ], $dataFormat, $this->primaryKeyType ) ) {
			return true;
		}

		// Delete cache
		$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );

		return false;
	}

	/**
	 * Update multiple record
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update_multiple( array $data ) {
		$ids           = wp_list_pluck( $data, $this->primaryKey );
		$default       = $this->find( [ $this->primaryKey . '__in' => $ids, 'per_page' => count( $ids ) ] );
		$default_items = [];
		foreach ( $default as $item ) {
			$default_items[ $item[ $this->primaryKey ] ] = $item;
		}

		global $wpdb;
		$table         = $this->get_table_name();
		$current_time  = current_time( 'mysql', true );
		$columns_names = static::get_columns_names( $table );

		$values = [];
		foreach ( $data as $index => $item ) {
			// Continue if primary key is not set
			if ( ! isset( $item[ $this->primaryKey ] ) ) {
				continue;
			}
			// Continue if record is not found on database
			$default = isset( $default_items[ $item[ $this->primaryKey ] ] ) ? $default_items[ $item[ $this->primaryKey ] ] : [];
			if ( empty( $default ) ) {
				continue;
			}

			$default = $default instanceof Data ? $default->data : $default;
			list( $_data, $_format ) = $this->format_item_for_db( $item, $default, $current_time );

			$sanitize_data = [];
			foreach ( $_data as $column_name => $column_value ) {
				if ( is_null( $column_value ) ) {
					continue;
				}
				$sanitize_data[ $column_name ] = $column_value;
			}
			$values[ $index ] = $wpdb->prepare( "(" . implode( ", ", $_format ) . ")", $sanitize_data );
		}

		$update_columns = [];
		foreach ( $columns_names as $columns_name ) {
			if ( $columns_name == $this->primaryKey ) {
				continue;
			}
			$update_columns[] = "{$columns_name}=VALUES({$columns_name})";
		}

		$sql = "INSERT INTO `{$table}` (" . implode( ", ", $columns_names ) . ") VALUES \n" . implode( ",\n", $values );
		$sql .= "ON DUPLICATE KEY UPDATE \n" . implode( ", ", $update_columns );

		$query = $wpdb->query( $sql );

		// Delete cache
		foreach ( $ids as $id ) {
			$this->delete_cache( $this->get_cache_key_for_single_item( $id ) );
		}

		return (bool) $query;
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
		$query = $wpdb->update( $table, [ $this->deleted_at => current_time( 'mysql', true ) ],
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
		$sql   = $wpdb->prepare( "UPDATE `{$table}` SET `{$this->deleted_at}` = %s", current_time( 'mysql', true ) );
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
	 * Method to read a record.
	 *
	 * @param mixed $data
	 *
	 * @return array
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

		$table_name = $this->get_table_name();
		$default    = static::get_default_data( $table_name );

		if ( is_array( $data ) ) {
			$item = [];
			foreach ( $default as $columnName => $default_value ) {
				$temp_data           = isset( $data[ $columnName ] ) ? $data[ $columnName ] : $default_value;
				$item[ $columnName ] = $this->unserialize( $temp_data );
			}

			return static::format_data_by_type( $table_name, $item );
		}

		return $default;
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
	public static function get_pagination( $total_items = 0, $per_page = 10, $current_page = 1 ) {
		$per_page = max( intval( $per_page ), 1 );

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
	 * @param mixed $data Maybe unserialized original, if is needed.
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

		$orderby = isset( $args['orderby'] ) && in_array( $args['orderby'], static::get_columns_names( $this->get_table_name() ) )
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
		$columnsNames = static::get_columns_names( $this->get_table_name() );
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
	 * Get table name
	 *
	 * @param string|null $table
	 *
	 * @return string
	 */
	public function get_table_name( ?string $table = null ) {
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
	 * Get foreign key constant name
	 *
	 * @param string $table1
	 * @param string $table2
	 *
	 * @return string
	 */
	public function get_foreign_key_constant_name( string $table1, string $table2 ): string {
		global $wpdb;
		$t1 = str_replace( $wpdb->prefix, '', $table1 );
		$t2 = str_replace( $wpdb->prefix, '', $table2 );

		return substr( sprintf( "fk_%s__%s", $t1, $t2 ), 0, 64 );
	}

	/**
	 * Format item for database
	 *
	 * @param array $data User provided data
	 * @param array $default_data Default data. Previous data for existing record
	 * @param string|null $current_time Current datetime
	 *
	 * @return array
	 */
	protected function format_item_for_db( array $data, array $default_data, $current_time = null ) {
		if ( empty( $current_time ) ) {
			$current_time = current_time( 'mysql', true );
		}

		$mode = ! empty( $data[ $this->primaryKey ] ) ? 'update' : 'create';

		$_data = [];
		foreach ( $default_data as $key => $value ) {
			$temp_data     = isset( $data[ $key ] ) ? $data[ $key ] : $value;
			$_data[ $key ] = $this->serialize( $temp_data );
		}

		// Update updated time
		if ( array_key_exists( $this->updated_at, $default_data ) ) {
			$_data[ $this->updated_at ] = $current_time;
		}

		if ( 'create' == $mode ) {
			// Update Author ID
			if ( array_key_exists( $this->created_by, $default_data ) && ! isset( $data[ $this->created_by ] ) ) {
				$_data[ $this->created_by ] = get_current_user_id();
			}

			// Update created time
			if ( array_key_exists( $this->created_at, $default_data ) ) {
				$_data[ $this->created_at ] = $current_time;
			}

			// Set deleted at time as null
			if ( array_key_exists( $this->deleted_at, $default_data ) ) {
				$_data[ $this->deleted_at ] = null;
			}

			// Remove primary key
			if ( array_key_exists( $this->primaryKey, $_data ) ) {
				unset( $_data[ $this->primaryKey ] );
			}
		}

		$_format = static::get_data_format_for_db( $this->get_table_name(), $_data );

		return array( $_data, $_format );
	}
}
