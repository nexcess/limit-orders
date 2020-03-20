<?php
/**
 * Tests for the order limiting functionality.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Tests;

use Nexcess\WooCommerceLimitOrders\OrderLimiter;
use WP_UnitTestCase as TestCase;

/**
 * @covers Nexcess\WooCommerceLimitOrders\OrderLimiter
 * @group Limiting
 */
class OrderLimiterTest extends TestCase {

	/**
	 * @test
	 * @testdox get_limit() should return the set limit
	 */
	public function get_limit_should_return_the_set_limit() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 7,
		] );

		$this->assertSame( 7, ( new OrderLimiter )->get_limit() );
	}

	/**
	 * @test
	 * @testdox get_limit() should return -1 if limiting is disabled
	 */
	public function get_limit_should_return_negative_one_if_limiting_is_disabled() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => false,
			'limit'   => 7,
		] );

		$this->assertSame( -1, ( new OrderLimiter )->get_limit() );
	}

	/**
	 * @test
	 * @testdox get_limit() should return -1 for any value that is not a zero or a positive integer
	 * @testWith [-1]
	 *           [-500]
	 *           ["one"]
	 *           ["some value"]
	 *           [["array", "value"]]
	 */
	public function get_limit_should_return_negative_one_for_any_non_positive_int_values( $value ) {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => $value,
		] );

		$this->assertSame( -1, ( new OrderLimiter() )->get_limit() );
	}

	/**
	 * @test
	 * @testdox get_remaining_orders() should return the number of orders left for the interval
	 */
	public function get_remaining_orders_should_return_the_number_of_orders_left_for_the_interval() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		set_site_transient( OrderLimiter::TRANSIENT_NAME, 2 );

		$this->assertSame( 3, ( new OrderLimiter() )->get_remaining_orders() );
	}

	/**
	 * @test
	 * @testdox get_remaining_orders() should return -1 if limiting is disabled
	 */
	public function get_remaining_orders_should_return_negative_one_if_limiting_is_disabled() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => false,
		] );

		$this->assertSame( -1, ( new OrderLimiter() )->get_remaining_orders() );
	}

	/**
	 * @test
	 * @testdox get_remaining_orders() should return 0 if the limits are met or exceeded
	 */
	public function get_remaining_orders_should_return_zero_if_limits_are_met_or_exceeded() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		set_site_transient( OrderLimiter::TRANSIENT_NAME, 10 );

		$this->assertSame( 0, ( new OrderLimiter() )->get_remaining_orders() );
	}

	/**
	 * @test
	 */
	public function get_seconds_until_next_interval() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @testdox has_reached_limit() should return false if the order count meets the limit
	 */
	public function has_reached_limit_should_return_true_if_orders_are_under_the_limit() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		set_site_transient( OrderLimiter::TRANSIENT_NAME, 2 );

		$this->assertFalse( ( new OrderLimiter() )->has_reached_limit() );
	}

	/**
	 * @test
	 * @testdox has_reached_limit() should return true if the order count meets the limit
	 */
	public function has_reached_limit_should_return_true_if_orders_meet_the_limit() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		set_site_transient( OrderLimiter::TRANSIENT_NAME, 5 );

		$this->assertTrue( ( new OrderLimiter() )->has_reached_limit() );
	}

	/**
	 * @test
	 * @testdox has_reached_limit() should return true if the order count exceeds the limit
	 */
	public function has_reached_limit_should_return_true_if_orders_exceed_the_limit() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		set_site_transient( OrderLimiter::TRANSIENT_NAME, 6 );

		$this->assertTrue( ( new OrderLimiter() )->has_reached_limit() );
	}
}
