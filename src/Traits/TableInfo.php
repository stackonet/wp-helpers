<?php

namespace Stackonet\WP\Framework\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * TableInfo trait class
 */
trait TableInfo {

	/**
	 * Collection of table information.
	 *
	 * @var array
	 */
	protected static $table_info = [];

	/**
	 * Get table info
	 *
	 * @param string $table The table name.
	 *
	 * @return array
	 */
	public static function show_columns_from( string $table ): array {
		global $wpdb;
		$results = $wpdb->get_results( "SHOW COLUMNS FROM $table", ARRAY_A ); // phpcs:ignore

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Get column info
	 *
	 * @param string $table The table name.
	 *
	 * @return array|false
	 */
	public static function get_table_info( string $table ) {
		if ( ! empty( static::$table_info[ $table ] ) ) {
			return static::$table_info[ $table ];
		}

		$info = wp_cache_get( $table, 'table-column-info' );
		if ( ! is_array( $info ) ) {
			$info = static::get_formatted_info( $table );
			wp_cache_set( $table, $info, 'table-column-info', WEEK_IN_SECONDS );
			static::$table_info[ $table ] = $info;
		}

		return $info;
	}

	/**
	 * Get primary key
	 *
	 * @param string $table The table name.
	 *
	 * @return string
	 */
	public static function get_primary_key( string $table ): string {
		$primary_key = 'id';
		foreach ( static::get_table_info( $table ) as $release ) {
			if ( isset( $release['primary'] ) ) {
				$primary_key = $release['field'];
			}
		}

		return $primary_key;
	}

	/**
	 * Get primary key data format
	 *
	 * @param string $table The table name.
	 *
	 * @return string
	 */
	public static function get_primary_key_data_format( string $table ): string {
		$data_format = '%d';
		foreach ( static::get_table_info( $table ) as $release ) {
			if ( isset( $release['primary'] ) ) {
				$data_format = $release['data_format'];
			}
		}

		return $data_format;
	}

	/**
	 * Get column name
	 *
	 * @param string $table The table name.
	 *
	 * @return array
	 */
	public static function get_columns_names( string $table ): array {
		return array_keys( static::get_table_info( $table ) );
	}


	/**
	 * Format data by type
	 *
	 * @param string $table The table name.
	 * @param array  $data The data that is going to insert into database.
	 *
	 * @return array
	 */
	public static function format_data_by_type( string $table, array $data ): array {
		$column_info    = static::get_table_info( $table );
		$formatted_data = [];
		foreach ( $data as $key => $value ) {
			if ( ! array_key_exists( $key, $column_info ) ) {
				continue;
			}
			$data_format = $column_info[ $key ]['data_format'];
			if ( '%d' === $data_format ) {
				$formatted_data[ $key ] = intval( $value );
			} elseif ( '%f' === $data_format ) {
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
	 * @param string $table The table name.
	 * @param array  $data The data that is going to insert into database.
	 *
	 * @return array
	 */
	public static function get_data_format_for_db( string $table, array $data = [] ): array {
		$columns = static::get_table_info( $table );

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
	 * @param string $table The table name.
	 *
	 * @return array
	 */
	public static function get_default_data( string $table ): array {
		$columns = static::get_table_info( $table );
		$data    = [];
		foreach ( $columns as $column_name => $info ) {
			if ( $info['nullable'] ) {
				$default = null;
			} else {
				$default = $info['default'] ?? '';
			}

			$words = str_word_count( $info['type'], 1 );
			if ( count( $words ) > 1 ) {
				$info['type'] = $words[0];
			}

			if ( in_array( $info['type'], static::get_integer_data_type(), true ) ) {
				$default = 0;
			}

			if ( in_array( $info['type'], static::get_float_data_type(), true ) ) {
				$default = 0;
			}

			$data[ $column_name ] = $default;
		}

		return $data;
	}

	/**
	 * Get formatted table info
	 *
	 * @param string $table The table name.
	 *
	 * @return array
	 */
	private static function get_formatted_info( string $table ): array {
		$results = static::show_columns_from( $table );
		$info    = [];
		foreach ( $results as $column ) {
			$length = static::get_type_and_length( $column['Type'] );

			$column_info = [
				'field'       => $column['Field'],
				'default'     => $column['Default'],
				'type'        => $length['type'],
				'length'      => $length['length'],
				'nullable'    => strtolower( $column['Null'] ) === 'yes',
				'data_format' => static::get_data_format_for_type( $length['type'] ),
			];

			if ( isset( $column['Key'] ) && 'PRI' === $column['Key'] ) {
				$column_info['primary'] = true;
			}

			if ( isset( $column['Extra'] ) && 'auto_increment' === $column['Extra'] ) {
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
	 * Get type and max length
	 *
	 * @param string $type_info Table column type.
	 *
	 * @return array
	 */
	private static function get_type_and_length( string $type_info ): array {
		$types  = explode( '(', $type_info );
		$type   = strtolower( $types[0] );
		$length = false;
		if ( ! empty( $types[1] ) ) {
			$length_info = explode( ')', $types[1] );
			$length      = intval( $length_info[0] );
		}

		$words = str_word_count( $type, 1 );
		if ( count( $words ) > 1 ) {
			$type = $words[0];
		}

		switch ( $type ) {
			case 'char':
			case 'varchar':
				return [
					'type'   => 'char',
					'length' => (int) $length,
				];

			case 'binary':
			case 'varbinary':
				return [
					'type'   => 'byte',
					'length' => (int) $length,
				];

			case 'tinyblob':
			case 'tinytext':
				return [
					'type'   => 'byte',
					'length' => 255,
				]; // 2^8 - 1

			case 'blob':
			case 'text':
				return [
					'type'   => 'byte',
					'length' => 65535,
				]; // 2^16 - 1

			case 'mediumblob':
			case 'mediumtext':
				return [
					'type'   => 'byte',
					'length' => 16777215,
				]; // 2^24 - 1

			case 'longblob':
			case 'longtext':
				return [
					'type'   => 'byte',
					'length' => 4294967295,
				]; // 2^32 - 1

			default:
				return [
					'type'   => $type,
					'length' => $length,
				];
		}
	}

	/**
	 * Get data format for db
	 *
	 * @param string $type Table column type.
	 *
	 * @return string
	 */
	private static function get_data_format_for_type( string $type ): string {
		$words = str_word_count( $type, 1 );
		if ( count( $words ) > 1 ) {
			$type = $words[0];
		}
		if ( in_array( $type, static::get_integer_data_type(), true ) ) {
			return '%d';
		}
		if ( in_array( $type, static::get_float_data_type(), true ) ) {
			return '%f';
		}

		return '%s';
	}


	/**
	 * Get integer data type
	 *
	 * @return array
	 */
	private static function get_integer_data_type(): array {
		return [ 'bit', 'int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'bool', 'boolean' ];
	}

	/**
	 * Get float data type
	 *
	 * @return array
	 */
	private static function get_float_data_type(): array {
		return [ 'float', 'double', 'decimal', 'dec' ];
	}
}
