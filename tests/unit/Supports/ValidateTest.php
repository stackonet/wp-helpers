<?php

namespace Supports;

use Stackonet\WP\Framework\Supports\Validate;

class ValidateTest extends \WP_UnitTestCase {
	public function test_validate_url() {
		$this->assertTrue( Validate::url( 'https://example.com' ) );
		$this->assertTrue( Validate::url( 'https://example' ) );
		$this->assertFalse( Validate::url( 'example.com' ) );
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

	public function test_it_validate_phone_e164() {
		$this->assertTrue( Validate::phone( '+14155552671' ) );
		$this->assertTrue( Validate::phone( '+8801701309039' ) );
		$this->assertTrue( Validate::phone( '+8801701309039', 13 ) );
		$this->assertFalse( Validate::phone( '88017013090' ) );
	}
}
