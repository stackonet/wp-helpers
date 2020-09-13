<?php

namespace Stackonet\WP\Framework\Supports;

class QueryBuilder {
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

	public function get_query_sql() {
		$where = [];
		foreach ( $this->query['where'] as $item ) {
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
	 *
	 * Example users
	 * =================================================
	 * where( 'post_type', 'post' )
	 * where( 'post_type', 'post', '!=' )
	 * where( 'post_type', [ 'post', 'page' ], 'IN' )
	 * where( [ ['post_type', 'post'], ['post_type', 'page'] ], 'OR' )
	 *
	 * @param array|string $column
	 * @param string|array $value
	 * @param string $compare
	 * @param string $relation
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
				if ( isset( $item[2] ) && in_array( $item[2], $this->get_compare_operators() ) ) {
					$_compare = $item[2];
				} else {
					$_compare = is_array( $item[1] ) ? 'IN' : '=';
				}
				$where[] = [
					'column'  => $item[0],
					'value'   => $item[1],
					'compare' => $_compare,
					'type'    => isset( $item[3] ) ? $item['3'] : 'string',
				];
			}
		} else {
			$where = [ 'column' => $column, 'value' => $value, 'compare' => $compare, 'type' => 'string' ];
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

	/**
	 * Get compare operators
	 *
	 * @return string[]
	 */
	public function get_compare_operators(): array {
		return [
			'=',
			'!=',
			'>',
			'>=',
			'<',
			'<=',
			'LIKE',
			'NOT LIKE',
			'NOT IN',
			'BETWEEN',
			'NOT BETWEEN',
		];
	}

	/**
	 * Get SQL for where
	 *
	 * @param $item
	 *
	 * @return mixed|string
	 */
	private function _get_sql_for_where( $item ) {
		if ( is_null( $item['value'] ) ) {
			$item['value'] = 'NULL';
		}
		$sanitize_callback = 'esc_sql';
		if ( 'integer' == $item['type'] ) {
			$sanitize_callback = 'intval';
		} elseif ( 'float' == $item['type'] ) {
			$sanitize_callback = 'floatval';
		}
		if ( is_array( $item['value'] ) ) {
			$value = array_map( $sanitize_callback, $item['value'] );
			if ( in_array( $item['compare'], [ 'BETWEEN', 'NOT BETWEEN' ] ) ) {
				if ( in_array( $sanitize_callback, [ 'intval', 'floatval' ] ) ) {
					$value = "{$value[0]} AND {$value[1]}";
				} else {
					$value = "'{$value[0]}' AND '{$value[1]}'";
				}
			} else if ( 'string' == $item['type'] ) {
				$value = "'" . implode( ",'", $value ) . "'";
			} else {
				$value = implode( ",'", $value );
			}
		} else {
			$value = call_user_func( $sanitize_callback, $item['value'] );
		}
		$operator = $item['compare'];
		$sql      = "{$item['column']} {$operator} {$value}";
		if ( is_null( $value ) || 'NULL' == $value ) {
			$sql = "{$item['column']} IS NULL";
		} elseif ( 'NOT NULL' == strtoupper( $value ) ) {
			$sql = "{$item['column']} IS NOT NULL";
		}

		return $sql;
	}
}
