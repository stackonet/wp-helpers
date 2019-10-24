<?php

namespace Stackonet\WP\Framework\Abstracts;

use Stackonet\WP\Framework\Interfaces\DataStoreInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Model
 * A thin layer using wpdb database class form rapid development
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
	 * Default data
	 * Must contain all table columns name in (key => value) format
	 *
	 * @var array
	 */
	protected $default_data = [];

	/**
	 * Data format
	 *
	 * @var array
	 */
	protected $data_format = [];

	/**
	 * The number of models to return for pagination.
	 *
	 * @var int
	 */
	protected $perPage = 15;

	/**
	 * @var string
	 */
	protected $cache_group;

	/**
	 * @var array
	 */
	protected static $information_schema = [];

	/**
	 * Model constructor.
	 *
	 * @param array $data
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
		list( $per_page, $offset, $orderby, $order ) = $this->get_pagination_and_order_data( $args );

		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$query = "SELECT * FROM {$table} WHERE 1=1";

		if ( isset( $args[ $this->created_by ] ) && is_numeric( $args[ $this->created_by ] ) ) {
			$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $args[ $this->created_by ] ) );
		}

		$query   .= " ORDER BY {$orderby} {$order}";
		$query   .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results;
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
		$table = $wpdb->prefix . $this->table;

		$sql  = "SELECT * FROM {$table} WHERE {$this->primaryKey} = {$this->primaryKeyType}";
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $id ), ARRAY_A );

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

		$_data = [];
		foreach ( $this->default_data as $key => $default ) {
			$temp_data     = isset( $data[ $key ] ) ? $data[ $key ] : $default;
			$_data[ $key ] = $this->serialize( $temp_data );
		}

		if ( isset( $_data[ $this->primaryKey ] ) ) {
			unset( $_data[ $this->primaryKey ] );
		}

		// Update Author ID
		if ( array_key_exists( $this->created_by, $this->default_data ) ) {
			if ( isset( $data[ $this->created_by ] ) && is_numeric( $data[ $this->created_by ] ) ) {
				$_data[ $this->created_by ] = intval( $data[ $this->created_by ] );
			} else {
				$_data[ $this->created_by ] = get_current_user_id();
			}
		}

		// Update created time
		if ( array_key_exists( $this->created_at, $this->default_data ) ) {
			$_data[ $this->created_at ] = $current_time;
		}

		// Update updated time
		if ( array_key_exists( $this->updated_at, $this->default_data ) ) {
			$_data[ $this->updated_at ] = $current_time;
		}

		// Set deleted at time as null
		if ( array_key_exists( $this->deleted_at, $this->default_data ) ) {
			$_data[ $this->deleted_at ] = null;
		}

		$format = $this->data_format;
		unset( $format[0] );
		$wpdb->insert( $table, $_data, $format );

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
		if ( is_array( $data ) ) {
			$item = [];
			foreach ( $this->default_data as $key => $default ) {
				$temp_data    = isset( $data[ $key ] ) ? $data[ $key ] : $default;
				$item[ $key ] = $this->unserialize( $temp_data );
			}

			return $item;
		}

		if ( is_numeric( $data ) ) {
			$data = $this->find_by_id( $data );
		}

		if ( $data instanceof self ) {
			return $data->data;
		}

		return $this->default_data;
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
		$table        = $wpdb->prefix . $this->table;
		$id           = isset( $data[ $this->primaryKey ] ) ? intval( $data[ $this->primaryKey ] ) : 0;
		$current_time = current_time( 'mysql' );

		$item = $this->find_by_id( $id );
		if ( empty( $item ) ) {
			return false;
		}

		$_data = [];
		foreach ( $this->default_data as $key => $default ) {
			$current_data  = isset( $item[ $key ] ) ? $item[ $key ] : null;
			$temp_data     = isset( $data[ $key ] ) ? $data[ $key ] : $current_data;
			$_data[ $key ] = $this->serialize( $temp_data );
		}
		$_data[ $this->primaryKey ] = $id;

		// Update updated time
		if ( array_key_exists( $this->updated_at, $this->default_data ) ) {
			$_data[ $this->updated_at ] = $current_time;
		}

		// Update deleted time
		if ( array_key_exists( $this->deleted_at, $this->default_data ) ) {
			$_data[ $this->deleted_at ] = null;
		}

		if ( $wpdb->update( $table, $_data, [ $this->primaryKey => $id ], $this->data_format, $this->primaryKeyType ) ) {
			return true;
		}

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
		$table = $wpdb->prefix . $this->table;

		$item = $this->find_by_id( $id );
		if ( ! $item instanceof self ) {
			return false;
		}

		return ( false !== $wpdb->delete( $table, [ $this->primaryKey => $id ], $this->primaryKeyType ) );
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
		$table = $wpdb->prefix . $this->table;
		$query = $wpdb->update( $table, [ $this->deleted_at => current_time( 'mysql' ) ],
			[ $this->primaryKey => $id ]
		);

		return ( false !== $query );
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
		$table = $wpdb->prefix . $this->table;
		$query = $wpdb->update( $table, [ $this->deleted_at => null ], [ $this->primaryKey => $id ] );

		return ( false !== $query );
	}

	public function information_schema() {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;
		if ( empty( self::$information_schema[ $table ] ) ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s;",
				DB_NAME,
				$table
			);

			$results = $wpdb->get_results( $sql, ARRAY_A );

			$data = [];
			foreach ( $results as $item ) {
				$data[ $item['COLUMN_NAME'] ] = [
					'datatype'       => $item['DATA_TYPE'],
					'default'        => $item['COLUMN_DEFAULT'],
					'chr_max_length' => is_numeric( $item['CHARACTER_MAXIMUM_LENGTH'] ) ? intval( $item['CHARACTER_MAXIMUM_LENGTH'] ) : null,
					'nullable'       => $item['IS_NULLABLE'] == "YES",
				];
			}

			self::$information_schema[ $table ] = $data;
		}

		return self::$information_schema[ $table ];
	}

	/**
	 * Generate pagination metadata
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getPaginationMetadata( array $args ) {
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
	 * Count total records from the database
	 *
	 * @return array
	 */
	abstract public function count_records();

	/**
	 * Create database table
	 *
	 * @return void
	 */
	abstract public function create_table();

	/**
	 * Handle dynamic static method calls into the method.
	 *
	 * @param string $method
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public static function __callStatic( $method, $parameters ) {
		return ( new static )->$method( ...$parameters );
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	protected function get_pagination_and_order_data( $args ) {
		$per_page     = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : $this->perPage;
		$paged        = isset( $args['paged'] ) ? absint( $args['paged'] ) : 1;
		$current_page = $paged < 1 ? 1 : $paged;
		$offset       = ( $current_page - 1 ) * $per_page;
		$orderby      = $this->primaryKey;
		if ( isset( $args['orderby'] ) && in_array( $args['orderby'], array_keys( $this->default_data ) ) ) {
			$orderby = $args['orderby'];
		}
		$order = isset( $args['order'] ) && 'ASC' == $args['order'] ? 'ASC' : 'DESC';

		return array( $per_page, $offset, $orderby, $order );
	}
}
