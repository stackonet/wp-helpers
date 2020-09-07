<?php

namespace Stackonet\WP\Framework\Traits;

defined( 'ABSPATH' ) || exit;

trait TableInfo {

	/**
	 * @var array
	 */
	protected static $__table_info = [];

	/**
	 * Get column info
	 *
	 * @param string $table
	 *
	 * @return array|false
	 */
	public static function __get_column_info( string $table ) {
		if ( ! empty( static::$__table_info[ $table ] ) ) {
			return static::$__table_info[ $table ];
		}

		$info = wp_cache_get( $table, 'table-column-info' );
		if ( ! is_array( $info ) ) {
			$info = static::$__table_info[ $table ] = static::__get_formatted_info( $table );
			wp_cache_set( $table, $info, 'table-column-info', WEEK_IN_SECONDS );
		}

		return $info;
	}

	/**
	 * Get primary key
	 *
	 * @param string $table
	 *
	 * @return string
	 */
	public static function __get_primary_key( string $table ): string {
		$primary_key = 'id';
		foreach ( static::__get_column_info( $table ) as $release ) {
			if ( isset( $release['primary'] ) ) {
				$primary_key = $release['field'];
			}
		}

		return $primary_key;
	}

	/**
	 * Get primary key data format
	 *
	 * @param string $table
	 *
	 * @return string
	 */
	public static function __get_primary_key_data_format( string $table ): string {
		$data_format = '%d';
		foreach ( static::__get_column_info( $table ) as $release ) {
			if ( isset( $release['primary'] ) ) {
				$data_format = $release['data_format'];
			}
		}

		return $data_format;
	}

	/**
	 * Get column name
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	public function __get_columns_names( string $table ): array {
		return array_keys( static::__get_column_info( $table ) );
	}


	/**
	 * Format data by type
	 *
	 * @param string $table
	 * @param array  $data
	 *
	 * @return array
	 */
	public static function __format_data_by_type( string $table, array $data ): array {
		$column_info    = static::__get_column_info( $table );
		$formatted_data = [];
		foreach ( $data as $key => $value ) {
			if ( ! array_key_exists( $key, $column_info ) ) {
				continue;
			}
			$data_format = $column_info[ $key ]['data_format'];
			if ( '%d' == $data_format ) {
				$formatted_data[ $key ] = intval( $value );
			} elseif ( '%f' == $data_format ) {
				$formatted_data[ $key ] = floatval( $value );
			} else {
				$formatted_data[ $key ] = $value;
			}
		}

		return $formatted_data;
	}

	/**
	 * Get data format for db
	 *
	 * @param string $table
	 * @param array  $data
	 *
	 * @return array
	 */
	public static function __get_data_format_for_db( string $table, array $data = [] ): array {
		$columns = static::__get_column_info( $table );

		if ( empty( $data ) ) {
			return wp_list_pluck( $columns, 'data_format' );
		}

		$formats = [];
		foreach ( $data as $column_name => $value ) {
			if ( is_string( $column_name ) && isset( $columns[ $column_name ] ) ) {
				$formats[ $column_name ] = is_null( $value ) ? 'NULL' : $columns[ $column_name ]['data_format'];
				continue;
			}
			if ( isset( $columns[ $value ] ) ) {
				$formats[ $value ] = $columns[ $value ]['data_format'];
			}
		}

		return $formats;
	}

	/**
	 * Get default data
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	public static function __get_default_data( string $table ): array {
		$columns = static::__get_column_info( $table );
		$data    = [];
		foreach ( $columns as $columnName => $info ) {
			if ( $info['nullable'] ) {
				$default = null;
			} else {
				$default = isset( $info['default'] ) ? $info['default'] : '';
			}

			if ( in_array( $columnName, static::__get_integer_data_type() ) ) {
				$default = 0;
			}

			if ( in_array( $columnName, static::__get_float_data_type() ) ) {
				$default = 0;
			}

			$data[ $columnName ] = $default;
		}

		return $data;
	}

	/**
	 * Get formatted table info
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	private static function __get_formatted_info( string $table ): array {
		$results = static::__get_info( $table );
		$info    = [];
		foreach ( $results as $column ) {
			$length = static::__get_type_and_length( $column['Type'] );

			$column_info = [
				'field'       => $column['Field'],
				'default'     => $column['Default'],
				'type'        => $length['type'],
				'length'      => $length['length'],
				'nullable'    => strtolower( $column['Null'] ) == 'yes',
				'data_format' => static::__get_data_format_for_type( $length['type'] ),
			];

			if ( isset( $column['Key'] ) && $column['Key'] == 'PRI' ) {
				$column_info['primary'] = true;
			}

			if ( isset( $column['Extra'] ) && $column['Extra'] == 'auto_increment' ) {
				$column_info['auto_increment'] = true;
			}

			if ( strpos( $column['Type'], 'unsigned' ) !== false ) {
				$column_info['unsigned'] = true;
			}

			$info[ $column['Field'] ] = $column_info;
		}

		return $info;
	}

	/**
	 * Get table info
	 *
	 * @param string $table
	 *
	 * @return array|null
	 */
	public static function __get_info( string $table ): ?array {
		global $wpdb;
		$sql = "SHOW COLUMNS FROM `{$table}`";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $results ) ? $results : null;
	}

	/**
	 * Get type and max length
	 *
	 * @param string $typeInfo
	 *
	 * @return array
	 */
	public static function __get_type_and_length( string $typeInfo ): array {
		$type_info = explode( '(', $typeInfo );
		$type      = strtolower( $type_info[0] );
		$length    = false;
		if ( ! empty( $type_info[1] ) ) {
			$length_info = explode( ')', $type_info[1] );
			$length      = intval( $length_info[0] );
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
	public static function __get_data_format_for_type( string $type ): string {
		if ( in_array( $type, static::__get_integer_data_type() ) ) {
			return '%d';
		}
		if ( in_array( $type, static::__get_float_data_type() ) ) {
			return '%f';
		}

		return '%s';
	}


	/**
	 * Get integer data type
	 *
	 * @return array
	 */
	public static function __get_integer_data_type(): array {
		return [ 'bit', 'int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'bool', 'boolean' ];
	}

	/**
	 * get float data type
	 *
	 * @return array
	 */
	public static function __get_float_data_type(): array {
		return [ 'float', 'double', 'decimal', 'dec' ];
	}
}
