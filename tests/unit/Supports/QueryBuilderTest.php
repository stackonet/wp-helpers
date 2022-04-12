<?php

namespace Supports;

use Stackonet\WP\Framework\Supports\QueryBuilder;

class QueryBuilderTest extends \WP_UnitTestCase {
	protected $query_builder;

	public function setUp() {
		$this->query_builder = QueryBuilder::table( 'posts' );
	}

	public function test_select_single_data() {
		$this->assertEquals( "SELECT * FROM wp_posts", $this->query_builder->get_query_sql() );

		$this->query_builder->where( 'post_status', 'publish' );
		$this->assertEquals(
			"SELECT * FROM wp_posts WHERE post_status = 'publish'",
			$this->query_builder->get_query_sql()
		);

		$this->query_builder->where( 'post_type', [ 'post', 'page' ], 'IN' );
		$this->assertEquals(
			"SELECT * FROM wp_posts WHERE post_status = 'publish' AND post_type IN('post','page')",
			$this->query_builder->get_query_sql()
		);
	}
}
