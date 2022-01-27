<?php

namespace Supports;

use Stackonet\WP\Framework\Supports\Validate;

class ValidateTest extends \WP_UnitTestCase {
	public function test_validate_required() {
		$this->assertTrue( Validate::required( 'has some value' ) );
		$this->assertTrue( Validate::required( true ) );
		$this->assertTrue( Validate::required( [ '', false, 0, null ] ) );
		$this->assertFalse( Validate::required( false ) );
		$this->assertFalse( Validate::required( null ) );
		$this->assertFalse( Validate::required( 0 ) );
		$this->assertFalse( Validate::required( '' ) );
		$this->assertFalse( Validate::required( [] ) );
	}

	public function test_validate_int() {
		$this->assertTrue( Validate::int( 0 ) );
		$this->assertTrue( Validate::int( '1' ) );
		$this->assertFalse( Validate::int( 'string' ) );
	}

	public function test_validate_number() {
		$this->assertTrue( Validate::number( + 0123.45e6 ) );
		$this->assertTrue( Validate::number( 0xf4c3b00c ) );
		$this->assertTrue( Validate::number( 0b10100111001 ) );
		$this->assertTrue( Validate::number( 0777 ) );
		$this->assertFalse( Validate::number( 'string' ) );
	}

	public function test_validate_url() {
		$this->assertTrue( Validate::url( 'https://example.com' ) );
		$this->assertTrue( Validate::url( 'https://example' ) );
		$this->assertFalse( Validate::url( 'example.com' ) );
	}

	public function test_validate_alpha() {
		$this->assertTrue( Validate::alpha( 'KjgWZC' ) );
		$this->assertFalse( Validate::alpha( 'arf12' ) );
		$this->assertFalse( Validate::alpha( 123456 ) );
	}

	public function test_validate_alnum() {
		$this->assertTrue( Validate::alnum( 'KjgWZC' ) );
		$this->assertTrue( Validate::alnum( 'arf12' ) );
		$this->assertTrue( Validate::alnum( 123456 ) );
		$this->assertFalse( Validate::alnum( 'sayful_bd' ) );
		$this->assertFalse( Validate::alnum( 'foo!#$bar' ) );
	}

	public function test_validate_alnumdash() {
		$this->assertTrue( Validate::alnumdash( 'KjgWZC' ) );
		$this->assertTrue( Validate::alnumdash( 'arf12' ) );
		$this->assertTrue( Validate::alnumdash( 123456 ) );
		$this->assertTrue( Validate::alnumdash( 'sayful_bd' ) );
		$this->assertFalse( Validate::alnumdash( [ 'KjgWZC' ] ) );
		$this->assertFalse( Validate::alnumdash( 'foo!#$bar' ) );
	}

	public function test_validate_email() {
		$this->assertTrue( Validate::email( 'mail@example.com' ) );
		$this->assertFalse( Validate::email( 'mail@example' ) );
	}

	public function test_validate_array() {
		$this->assertTrue( Validate::array( [ 'mail@example.com' ] ) );
		$this->assertFalse( Validate::array( 'mail@example' ) );
	}

	public function test_validate_min() {
		$this->assertTrue( Validate::min( 3, 2, true ) );
		$this->assertFalse( Validate::min( 1, 2, true ) );
		$this->assertFalse( Validate::min( 12345, 6 ) );
		$this->assertTrue( Validate::min( 'mail@example', 3 ) );
		$this->assertFalse( Validate::min( '12345', 6 ) );
		$this->assertFalse( Validate::min( [ '12345' ], 6 ) );
	}

	public function test_validate_max() {
		$this->assertTrue( Validate::max( 3, 5, true ) );
		$this->assertFalse( Validate::max( 10, 2, true ) );
		$this->assertFalse( Validate::max( 12345, 4 ) );
		$this->assertTrue( Validate::max( 'mail@example', 20 ) );
		$this->assertFalse( Validate::max( [ '12345' ], 3 ) );
	}

	public function test_validate_checked() {
		$this->assertTrue( Validate::checked( 'yes' ) );
		$this->assertTrue( Validate::checked( 'on' ) );
		$this->assertTrue( Validate::checked( 'true' ) );
		$this->assertTrue( Validate::checked( true ) );
		$this->assertTrue( Validate::checked( 1 ) );
		$this->assertTrue( Validate::checked( '1' ) );
	}

	public function test_it_validate_json() {
		$this->assertTrue( Validate::json( wp_json_encode( [ 'key' => 'value' ] ) ) );
		$this->assertFalse( Validate::json( [ 'key' => 'value' ] ) );
		$this->assertFalse( Validate::json( 'String' ) );
	}

	public function test_it_validate_date() {
		$this->assertTrue( Validate::date( '1989-02-04' ) );
		$this->assertTrue( Validate::date( '2022-01-31' ) );
		$this->assertFalse( Validate::date( '11/12/10' ) ); // MM/DD/YY
		$this->assertFalse( Validate::date( 'Jan 31, 2022' ) );
		$this->assertFalse( Validate::date( '31 Jan 2022' ) );
		$this->assertFalse( Validate::date( [ 'key' => 'value' ] ) );
	}

	public function test_it_validate_time() {
		$this->assertTrue( Validate::time( '10:20' ) );
		$this->assertTrue( Validate::time( '23:20' ) );
		$this->assertTrue( Validate::time( '10:20 AM' ) );
		$this->assertFalse( Validate::time( [ 'key' => 'value' ] ) );
		$this->assertFalse( Validate::time( '10:20:30' ) );
		$this->assertFalse( Validate::time( '10:20:30 AM' ) );
	}

	public function test_it_validate_phone_e164() {
		$this->assertTrue( Validate::phone( '+14155552671' ) );
		$this->assertTrue( Validate::phone( '+8801701309039' ) );
		$this->assertTrue( Validate::phone( '+8801701309039', 13 ) );
		$this->assertFalse( Validate::phone( '88017013090' ) );
	}
}
