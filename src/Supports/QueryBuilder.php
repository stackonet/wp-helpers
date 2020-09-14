<?php

namespace Stackonet\WP\Framework\Supports;

use Stackonet\WP\Framework\Traits\TableInfo;

class QueryBuilder {

	use TableInfo;

	/**
	 * @var array
	 */
	protected $query = [
		'table'    => '',
		'select'   => '*',
		'limit'    => - 1,
		'offset'   => 0,
		'order_by' => [],
		'join'     => [],
		'where'    => [],
	];

	/**
	 * Dump data for debug
	 *
	 * @return array
	 */
	public function dump() {
		return [
			'sql'   => $this->get_query_sql(),
			'query' => $this->query,
		];
	}

	/**
	 * Get compare operators
	 *
	 * @return string[]
	 */
	public function get_compare_operators(): array {
		return [ '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ];
	}

	/**
	 * Sanitize where args
	 * ==============================================================
	 * 0 -- column, 1 -- value, 2 -- compare operator, 3 -- data type
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	private function sanitize_where_args( array $args ): array {
		$column = $args[0];
		$value  = $args[1];

		if ( isset( $args[2] ) && in_array( $args[2], $this->get_compare_operators() ) ) {
			$compare = $args[2];
		} else {
			$compare = is_array( $value ) ? 'IN' : '=';
		}

		$table_info = static::get_table_info( $this->query['table'] );
		if ( ! isset( $table_info[ $column ] ) ) {
			return [];
		}
		$column_info = $table_info[ $column ];
		$data_format = $column_info['data_format'];

		if ( $data_format == '%d' ) {
			$type              = 'integer';
			$sanitize_callback = 'intval';
		} elseif ( $data_format == '%f' ) {
			$type              = 'float';
			$sanitize_callback = 'floatval';
		} else {
			$type              = 'string';
			$sanitize_callback = 'esc_sql';
		}

		return [
			'column'            => $column,
			'compare'           => $compare,
			'type'              => $type,
			'data_format'       => $data_format,
			'sanitize_callback' => $sanitize_callback,
			'nullable'          => $column_info['nullable'],
			'value'             => $value,
		];
	}

	/**
	 * Get SQL for where
	 *
	 * @param $item
	 *
	 * @return string
	 */
	private function _get_sql_for_where( $item ): string {
		global $wpdb;

		if ( $item['nullable'] && is_null( $item['value'] ) ) {
			$item['value'] = 'NULL';
		}

		if ( is_array( $item['value'] ) ) {
			$value = array_map( $item['sanitize_callback'], $item['value'] );
		} else {
			$value = call_user_func( $item['sanitize_callback'], $item['value'] );
		}

		$operator = $item['compare'];
		$sql      = '';
		if ( is_array( $value ) ) {
			if ( in_array( $operator, [ 'BETWEEN', 'NOT BETWEEN' ] ) ) {
				$sql = $wpdb->prepare(
					"{$item['column']} {$operator} {$item['data_format']} AND {$item['data_format']}",
					$value[0], $value[1]
				);
			}
			if ( in_array( $operator, [ 'IN', 'NOT IN', ] ) ) {
				if ( 'string' == $item['type'] ) {
					$sql = "{$item['column']} {$operator}('" . implode( ",'", $value ) . "')";
				} else {
					$sql = "{$item['column']} {$operator}(" . implode( ", ", $value ) . ")";
				}
			}
		} else {
			if ( is_null( $value ) || 'NULL' == $value ) {
				$sql = "{$item['column']} IS NULL";
			} elseif ( 'NOT NULL' == strtoupper( $value ) ) {
				$sql = "{$item['column']} IS NOT NULL";
			} else {
				$sql = $wpdb->prepare( "{$item['column']} {$operator} {$item['data_format']}", $value );
			}
		}

		return $sql;
	}

	/**
	 * Get table name
	 *
	 * @param string|null $table
	 *
	 * @return string
	 */
	public function get_table_name( string $table ) {
		global $wpdb;
		if ( false !== strpos( $table, $wpdb->prefix ) ) {
			return $table;
		}

		return $wpdb->prefix . $table;
	}

	/**
	 * Get query builder
	 *
	 * @param string $table
	 *
	 * @return static
	 */
	public static function table( string $table ) {
		$query_builder = new static;

		$query_builder->query['table'] = $query_builder->get_table_name( $table );

		return $query_builder;
	}

	/**
	 * Determine whether a query clause is first-order.
	 *
	 * @param array $query Meta query arguments.
	 *
	 * @return bool Whether the query clause is a first-order clause.
	 */
	protected function is_first_order_clause( array $query ) {
		return isset( $query['column'] ) || isset( $query['value'] );
	}

	/**
	 * Get query SQL
	 *
	 * @return string
	 */
	public function get_query_sql() {
		$where = [];
		foreach ( array_filter( $this->query['where'] ) as $item ) {
			if ( $this->is_first_order_clause( $item ) ) {
				$where[] = $this->_get_sql_for_where( $item );
			} else {
				$relation = $item['relation'];
				unset( $item['relation'] );
				$_where = '(';
				foreach ( $item as $index => $_item ) {
					if ( $index > 0 ) {
						$_where .= " {$relation} ";
					}

					$_where .= $this->_get_sql_for_where( $_item );
				}
				$_where  .= ')';
				$where[] = $_where;
			}
		}

		$sql = "SELECT {$this->query['select']} FROM {$this->query['table']}";
		$sql .= " WHERE " . join( ' AND ', $where );

		return $sql;
	}

	/**
	 * Set order by
	 *
	 * @param string $column
	 * @param string $order
	 *
	 * @return static
	 */
	public function order_by( string $column, string $order = 'DESC' ) {
		$this->query['order_by'][] = [ 'column' => $column, 'order' => $order ];

		return $this;
	}

	/**
	 * Build where query
	 * Example users
	 * =================================================
	 * where( 'post_type', 'page' )
	 * where( 'post_type', 'post', '!=' )
	 * where( 'post_type', [ 'post', 'page' ], 'IN' )
	 * where( [ ['post_type', 'post'], ['post_type', 'page'] ], 'OR' )
	 * where( 'deleted_at', 'NULL' );
	 * where( 'updated_at', 'NOT NULL' );
	 *
	 * @param array|string $column
	 * @param string|array $value
	 * @param string       $compare
	 * @param string       $relation
	 *
	 * @return $this
	 */
	public function where( $column, $value = '', $compare = '=', $relation = 'AND' ) {
		$args  = func_get_args();
		$where = [];
		if ( is_array( $args[0] ) ) {
			$where['relation'] = isset( $args[1] ) && in_array( $args[1], [ 'AND', 'OR' ] ) ? $args[1] : 'AND';;
			foreach ( $args[0] as $item ) {
				if ( count( $item ) < 2 ) {
					continue;
				}
				$where[] = $this->sanitize_where_args( $item );
			}
		} else {
			$where = $this->sanitize_where_args( $args );
		}

		$this->query['where'][] = $where;

		return $this;
	}

	public function orWhere() {
	}

	public function whereBetween() {
	}

	public function whereIn( string $column, array $data ) {
	}

	public function whereNotIn( string $column, array $data ) {
	}

	public function orWhereIn( string $column, array $data ) {
	}

	public function orWhereNotIn( string $column, array $data ) {
	}

	public function whereNull( string $column ) {
	}

	public function orWhereNull( string $column ) {
	}

	public function get() {
	}

	/**
	 * Get a single row
	 */
	public function first() {
	}

	public function value() {
	}

	public function find() {
	}

	public function pluck() {
	}

	public function count() {
	}

	public function max() {
	}

	public function avg() {
	}

	public function exists() {
	}

	public function doesntExist() {
	}

	public function select() {
	}

	public function distinct() {
	}

	public function join( string $reference_table, string $reference_column, string $table_column, string $type = 'INNER' ) {
	}

	public function leftJoin() {
	}

	public function rightJoin() {
	}
}
