<?php

use Stackonet\WP\Framework\Traits\TableInfo;

class TableInfoTest extends \WP_UnitTestCase {
	protected $instance;

	public function setUp() {
		parent::setUp();
		$this->instance = new class {
			use TableInfo;
		};
	}

	public function test_table_column_info() {
		$info        = $this->instance::get_table_info( 'wp_posts' );
		$column_info = $info['ID'];

		$this->assertEquals( 'ID', $column_info['field'] );
		$this->assertEquals( 'bigint', $column_info['type'] );
		$this->assertEquals( '%d', $column_info['data_format'] );
		$this->assertTrue( $column_info['primary'] );
		$this->assertTrue( $column_info['auto_increment'] );
		$this->assertTrue( $column_info['unsigned'] );

		$columns = $this->instance::get_columns_names( 'wp_posts' );
		$this->assertEquals( 23, count( $columns ) );
	}

	public function test_table_primary_column() {
		$this->assertEquals( 'ID', $this->instance::get_primary_key( 'wp_posts' ) );
		$this->assertEquals( '%d', $this->instance::get_primary_key_data_format( 'wp_posts' ) );
	}

	public function test_table_default_data() {
		$defaults = $this->instance::get_default_data( 'wp_posts' );

		$this->assertEquals( 'publish', $defaults['post_status'] );
		$this->assertEquals( 'open', $defaults['comment_status'] );
		$this->assertEquals( 'open', $defaults['ping_status'] );
		$this->assertEquals( '0', $defaults['post_parent'] );
	}

	public function test_format_data_by_type() {
		$data           = [ 'post_id' => '123', 'meta_key' => null, 'meta_value' => 'Some value', 'invalid_key' => 0 ];
		$formatted_data = $this->instance::format_data_by_type( 'wp_postmeta', $data );
		$this->assertEquals( 123, $formatted_data['post_id'] );
		$this->assertNull( $formatted_data['meta_key'] );
		$this->assertArrayNotHasKey( 'invalid_key', $formatted_data );
	}
}
