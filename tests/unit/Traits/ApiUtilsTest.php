<?php

use Stackonet\WP\Framework\Traits\ApiUtils;

class ApiUtilsTest extends \WP_UnitTestCase {
	protected $instance;

	public function setUp() {
		parent::setUp();
		$this->instance = new class {
			use ApiUtils;
		};
	}

	public function test_it_format_date() {
		$this->assertEquals( '2022-02-01T00:00:00', $this->instance::format_date( 'Feb 1, 2022' ) );
		$this->assertEquals( '2022-02-01T11:22:33', $this->instance::format_date( '2022-02-01 11:22:33' ) );
		$this->assertEquals( '0000-00-00Y00:00:00', $this->instance::format_date( 'invalid date' ) );
	}

	public function test_it_generate_pagination_data() {
		$pagination = $this->instance::get_pagination_data( 103, 50, 2 );
		$this->assertEquals( 3, $pagination['total_pages'] );
		$this->assertEquals( 2, $pagination['current_page'] );
	}

	public function test_it_sanitize_sorting_data() {
		$sorting_array = $this->instance::sanitize_sorting_data( 'title+DESC,author+ASC,date_created' );
		$this->assertEquals( 2, count( $sorting_array ) );
		$this->assertEquals( 'title', $sorting_array[0]['field'] );
		$this->assertEquals( 'DESC', $sorting_array[0]['order'] );
		$this->assertEquals( 'author', $sorting_array[1]['field'] );
		$this->assertEquals( 'ASC', $sorting_array[1]['order'] );
		$sorting_array2 = $this->instance::sanitize_sorting_data( [ 'title+DESC,author+ASC,date_created' ] );
		$this->assertEquals( 0, count( $sorting_array2 ) );
	}
}
