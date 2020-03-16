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
		update_option( 'woocommerce-limit-orders', [
			'limit' => 7,
		] );

		$this->assertSame( 7, (new OrderLimiter )->get_limit() );
	}

	/**
	 * @test
	 * @testdox get_limit() should -1 for any value that is not a zero or a positive integer
	 * @testWith [-1]
	 *           [-500]
	 *           ["one"]
	 *           ["some value"]
	 *           [["array", "value"]]
	 */
	public function get_limit_should_return_negative_one_for_any_non_positive_int_values( $value ) {
		update_option( 'woocommerce-limit-orders', [
			'limit' => $value,
		] );

		$this->assertSame( -1, (new OrderLimiter )->get_limit() );
	}

	/**
	 * @test
	 * @testdox get_interval() should return the interval in seconds
	 */
	public function get_interval_should_return_the_interval_in_seconds() {
		update_option( 'woocommerce-limit-orders', [
			'interval' => WEEK_IN_SECONDS,
		] );

		$this->assertSame( WEEK_IN_SECONDS, (new OrderLimiter )->get_interval() );
	}

	/**
	 * @test
	 * @testdox get_interval() should default to one month if the value is not a positive integer.
	 * @testWith [0]
	 *           [-1]
	 *           ["some interval"]
	 *           [""]
	 */
	public function get_interval_should_default_to_one_month_if_the_value_is_not_a_positive_integer( $interval ) {
		update_option( 'woocommerce-limit-orders', [
			'interval' => $interval,
		] );

		$this->assertSame( MONTH_IN_SECONDS, (new OrderLimiter )->get_interval() );
	}
}
