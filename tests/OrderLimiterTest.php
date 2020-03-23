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
	public function get_interval_start_for_daily() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		$today = new \DateTime( 'now', wp_timezone() );

		$this->assertSame(
			$today->setTime( 0, 0, 0 )->format( 'r' ),
			( new OrderLimiter() )->get_interval_start()->format( 'r' ),
			'Daily intervals should start at midnight local time.'
		);
	}

	/**
	 * @test
	 */
	public function get_interval_start_for_weekly() {
		update_option( 'week_starts_on', 1 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		$this->assertSame(
			( new \DateTime( 'Monday', wp_timezone() ) )->format( 'r' ),
			( new OrderLimiter() )->get_interval_start()->format( 'r' ),
			'Weekly intervals should start at midnight on the first day of the week.'
		);
	}

	/**
	 * @test
	 * @depends get_interval_start_for_weekly
	 */
	public function get_interval_start_for_weekly_with_a_non_standard_day() {
		update_option( 'week_starts_on', 3 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		$this->assertSame(
			( new \DateTime( 'last Wednesday' ) )->format( 'r' ),
			( new OrderLimiter() )->get_interval_start()->format( 'r' ),
			'Weekly intervals should adjust to the week_starts_on option.'
		);
	}

	/**
	 * @test
	 * @depends get_interval_start_for_weekly
	 */
	public function get_interval_start_for_weekly_when_today_is_the_first_day_of_the_week() {
		$today = new \DateTime( 'now', wp_timezone() );

		update_option( 'week_starts_on', $today->format( 'w' ) );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		$this->assertSame(
			$today->setTime( 0, 0, 0 )->format( 'r' ),
			( new OrderLimiter() )->get_interval_start()->format( 'r' ),
			'If today is the first day of a weekly interval, the time should be this morning at midnight.'
		);
	}

	/**
	 * @test
	 */
	public function get_interval_start_for_monthly() {
		$today = new \DateTime( 'now', wp_timezone() );

		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'monthly',
		] );

		$this->assertSame(
			( new \DateTime( $today->format( 'F' ) . ' 1', wp_timezone() ) )->format( 'r' ),
			( new OrderLimiter() )->get_interval_start()->format( 'r' ),
			'Monthly intervals should start at midnight on the first day of the month.'
		);
	}

	/**
	 * @test
	 */
	public function get_seconds_until_next_interval_for_daily() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		$current = new \DateTime( 'now', wp_timezone() );
		$next    = new \DateTime( 'tomorrow', wp_timezone() );

		$this->assertSame(
			$next->getTimestamp() - $current->getTimestamp(),
			( new OrderLimiter() )->get_seconds_until_next_interval(),
			'It should return the number of seconds until midnight.'
		);
	}

	/**
	 * @test
	 */
	public function get_seconds_until_next_interval_for_weekly() {
		update_option( 'week_starts_on', 2 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		$current = new \DateTime( 'now', wp_timezone() );
		$next    = new \DateTime( 'Next Tuesday', wp_timezone() );

		$this->assertSame(
			$next->getTimestamp() - $current->getTimestamp(),
			( new OrderLimiter() )->get_seconds_until_next_interval(),
			'It should return the number of seconds until midnight next Monday.'
		);
	}

	/**
	 * @test
	 */
	public function get_seconds_until_next_interval_for_monthly() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'monthly',
		] );

		$current = new \DateTime( 'now', wp_timezone() );
		$next    = ( new \DateTime( 'First day of next Month', wp_timezone() ) )->setTime( 0, 0, 0 );

		$this->assertSame(
			$next->getTimestamp() - $current->getTimestamp(),
			( new OrderLimiter() )->get_seconds_until_next_interval(),
			'It should return the number of seconds until midnight on the first of the month.'
		);
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
