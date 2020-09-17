<?php
/**
 * Tests for the order limiting functionality.
 *
 * @package Nexcess\LimitOrders
 */

namespace Tests;

use Nexcess\LimitOrders\OrderLimiter;
use WC_Helper_Product;

/**
 * @covers Nexcess\LimitOrders\OrderLimiter
 * @group Limiting
 */
class OrderLimiterTest extends TestCase {

	/**
	 * @before
	 */
	public function reset_wc_notices() {
		wc_clear_notices();
	}

	/**
	 * @test
	 * @testdox get_interval() should return the interval setting
	 * @group Intervals
	 */
	public function get_interval_should_return_the_interval_setting() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		$this->assertSame( 'weekly', ( new OrderLimiter )->get_interval() );
	}

	/**
	 * @test
	 * @testdox get_interval() should default to "daily"
	 * @group Intervals
	 */
	public function get_interval_should_default_to_daily() {
		$this->assertSame( 'daily', ( new OrderLimiter )->get_interval() );
	}

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
	 * @testWith ["checkout_error"]
	 *           ["customer_notice"]
	 *           ["order_button"]
	 */
	public function get_message_should_return_user_provided_messages( string $key ) {
		$message = 'Some message ' . uniqid();

		update_option( OrderLimiter::OPTION_KEY, [
			$key => $message,
		] );

		$this->assertSame( $message, ( new OrderLimiter() )->get_message( $key ) );
	}

	/**
	 * @test
	 */
	public function get_message_should_not_expose_other_settings() {
		$this->assertEmpty( ( new OrderLimiter() )->get_message( 'interval' ) );
	}

	/**
	 * @test
	 * @testdox get_message() should replace the {current_interval} placeholder
	 * @testWith ["{current_interval}"]
	 *           ["{current_interval:date}"]
	 * @group Placeholders
	 */
	public function get_message_should_replace_current_interval_placeholder( $placeholder ) {
		update_option( 'date_format', 'F j, Y' );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval'        => 'monthly',
			'customer_notice' => "This started on {$placeholder}",
		] );

		$now     = new \DateTimeImmutable( '2020-04-28 00:00:00', wp_timezone() );
		$limiter = new OrderLimiter( $now );

		$this->assertSame(
			'This started on ' . $limiter->get_interval_start()->format( 'F j, Y' ),
			$limiter->get_message( 'customer_notice' )
		);
	}

	/**
	 * @test
	 * @testdox get_message() should replace the {current_interval:time} placeholder
	 * @group Placeholders
	 */
	public function get_message_should_replace_current_interval_time_placeholder() {
		update_option( 'time_format', 'g:ia' );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval'        => 'monthly',
			'customer_notice' => "This started at {current_interval:time}",
		] );

		$now     = new \DateTimeImmutable( '2020-04-28 00:00:00', wp_timezone() );
		$limiter = new OrderLimiter( $now );

		$this->assertSame(
			'This started at ' . $limiter->get_interval_start()->format( 'g:ia' ),
			$limiter->get_message( 'customer_notice' )
		);
	}

	/**
	 * @test
	 * @testdox get_message() should replace the {limit} placeholder
	 * @group Placeholders
	 */
	public function get_message_should_replace_limit_placeholder() {
		update_option( OrderLimiter::OPTION_KEY, [
			'customer_notice' => 'We can accept {limit} orders.',
		] );

		$limiter = new OrderLimiter();

		$this->assertSame(
			'We can accept ' . $limiter->get_limit() . ' orders.',
			$limiter->get_message( 'customer_notice' )
		);
	}

	/**
	 * @test
	 * @testdox get_message() should replace the {next_interval} placeholder
	 * @testWith ["{next_interval}"]
	 *           ["{next_interval:date}"]
	 * @group Placeholders
	 */
	public function get_message_should_replace_next_interval_placeholder( $placeholder ) {
		update_option( 'date_format', 'F j, Y' );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval'        => 'monthly',
			'customer_notice' => "Check back on {$placeholder}",
		] );

		$now     = new \DateTimeImmutable( '2020-04-28 00:00:00', wp_timezone() );
		$limiter = new OrderLimiter( $now );

		$this->assertSame(
			'Check back on ' . $limiter->get_next_interval_start()->format( 'F j, Y' ),
			$limiter->get_message( 'customer_notice' )
		);
	}

	/**
	 * @test
	 * @testdox get_message() should replace the {next_interval:time} placeholder
	 * @group Placeholders
	 */
	public function get_message_should_replace_next_interval_time_placeholder() {
		update_option( 'time_format', 'g:ia' );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval'        => 'monthly',
			'customer_notice' => "Check back at {next_interval:time}",
		] );

		$now     = new \DateTimeImmutable( '2020-04-27 00:00:00', wp_timezone() );
		$limiter = new OrderLimiter( $now );

		$this->assertSame(
			'Check back at ' . $limiter->get_next_interval_start()->format( 'g:ia' ),
			$limiter->get_message( 'customer_notice' )
		);
	}

	/**
	 * @test
	 * @group Placeholders
	 * @ticket https://github.com/nexcess/limit-orders/issues/18
	 * @ticket https://github.com/nexcess/limit-orders/issues/22
	 */
	public function get_placeholders_should_return_an_array_of_default_placeholders() {
		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:ia' );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'hourly',
		] );

		$now          = new \DateTimeImmutable( '2020-04-27 12:15:00', wp_timezone() );
		$current      = new \DateTimeImmutable( '2020-04-27 12:00:00', wp_timezone() );
		$next         = new \DateTimeImmutable( '2020-04-27 13:00:00', wp_timezone() );
		$placeholders = ( new OrderLimiter( $now ) )->get_placeholders();

		$this->assertSame( $current->format( 'F j, Y' ), $placeholders['{current_interval}'] );
		$this->assertSame( $current->format( 'F j, Y' ), $placeholders['{current_interval:date}'] );
		$this->assertSame( $current->format( 'g:ia' ), $placeholders['{current_interval:time}'] );
		$this->assertSame( $next->format( 'F j, Y' ), $placeholders['{next_interval}'] );
		$this->assertSame( $next->format( 'F j, Y' ), $placeholders['{next_interval:date}'] );
		$this->assertSame( $next->format( 'g:ia' ), $placeholders['{next_interval:time}'] );
		$this->assertSame( $next->format( 'T' ), $placeholders['{timezone}'] );
	}

	/**
	 * @test
	 * @group Placeholders
	 */
	public function time_placeholders_should_replace_00_with_midnight() {
		$this->markTestIncomplete( 'https://github.com/nexcess/limit-orders/issues/21' );

		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		$now          = new \DateTimeImmutable( '2020-04-27 12:15:00', wp_timezone() );
		$current      = new \DateTimeImmutable( '2020-04-27 00:00:00', wp_timezone() );
		$next         = new \DateTimeImmutable( '2020-04-28 00:00:00', wp_timezone() );
		$placeholders = ( new OrderLimiter( $now ) )->get_placeholders();

		$this->assertSame( __( 'midnight', 'limit-orders' ), $placeholders['{current_interval:time}'] );
		$this->assertSame( __( 'midnight', 'limit-orders' ), $placeholders['{next_interval:time}'] );
	}

	/**
	 * @test
	 * @group Placeholders
	 */
	public function get_placeholders_should_filter_placeholders() {
		add_filter( 'limit_orders_message_placeholders', function ( $placeholders ) {
			$placeholders['{test}'] = 'Test value';

			return $placeholders;
		} );

		$placeholders = ( new OrderLimiter() )->get_placeholders();

		$this->assertSame( 'Test value', $placeholders['{test}'] );
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

		set_transient( OrderLimiter::TRANSIENT_NAME, 2 );

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

		set_transient( OrderLimiter::TRANSIENT_NAME, 10 );

		$this->assertSame( 0, ( new OrderLimiter() )->get_remaining_orders() );
	}

	/**
	 * @test
	 * @testdox get_remaining_orders() should be filterable
	 */
	public function get_remaining_orders_should_be_filterable() {
		$instance = new OrderLimiter();
		$called   = false;

		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		add_filter( 'limit_orders_pre_get_remaining_orders', function ( $preempt, $limiter ) use ( $instance, &$called ) {
			$this->assertFalse( $preempt, 'The $preempt argument should start as false.' );
			$this->assertSame( $instance, $limiter );
			$called = true;

			return -1;
		}, 10, 2 );

		$this->assertSame( -1, $instance->get_remaining_orders() );
		$this->assertTrue( $called );
	}

	/**
	 * @test
	 * @testdox get_remaining_orders() should be filterable
	 */
	public function get_remaining_orders_should_cast_the_return_values_as_integers() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		add_filter( 'limit_orders_pre_get_remaining_orders', '__return_true' );

		$this->assertSame( 1, ( new OrderLimiter() )->get_remaining_orders(), 'TRUE should be cast as 1.' );
	}

	/**
	 * @test
	 * @group Intervals
	 * @ticket https://github.com/nexcess/limit-orders/issues/18
	 */
	public function get_interval_start_for_hourly() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'hourly',
		] );

		$now   = new \DateTimeImmutable( '2020-04-27 12:05:00', wp_timezone() );
		$start = new \DateTimeImmutable( '2020-04-27 12:00:00', wp_timezone() );

		$this->assertSame(
			$start->format( 'r' ),
			( new OrderLimiter( $now ) )->get_interval_start()->format( 'r' ),
			'Hourly intervals should start at the top of the hour.'
		);
	}

	/**
	 * @test
	 * @depends get_interval_start_for_hourly
	 * @group Intervals
	 * @ticket https://github.com/nexcess/limit-orders/issues/24
	 */
	public function get_interval_start_should_use_24hr_time() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'hourly',
		] );

		$now   = new \DateTimeImmutable( '2020-04-27 18:05:00', wp_timezone() );
		$start = new \DateTimeImmutable( '2020-04-27 18:00:00', wp_timezone() );

		$this->assertSame(
			$start->format( 'r' ),
			( new OrderLimiter( $now ) )->get_interval_start()->format( 'r' )
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_interval_start_for_daily() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		$now   = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$start = new \DateTimeImmutable( '2020-03-03 00:00:00', wp_timezone() );

		$this->assertSame(
			$start->format( 'r' ),
			( new OrderLimiter( $now ) )->get_interval_start()->format( 'r' ),
			'Daily intervals should start at midnight local time.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_interval_start_for_weekly() {
		update_option( 'week_starts_on', 1 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		// Tuesday, March 3 and Monday, March 2.
		$now   = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$start = new \DateTimeImmutable( '2020-03-02 00:00:00', wp_timezone() );

		$this->assertSame(
			$start->format( 'r' ),
			( new OrderLimiter( $now ) )->get_interval_start()->format( 'r' ),
			'Weekly intervals should start at midnight on the first day of the week.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_interval_start_for_weekly_with_a_non_standard_day() {
		update_option( 'week_starts_on', 6 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		// Tuesday, March 10 and Saturday, March 7.
		$now   = new \DateTimeImmutable( '2020-03-10 12:01:00', wp_timezone() );
		$start = new \DateTimeImmutable( '2020-03-07 00:00:00', wp_timezone() );

		$this->assertSame(
			$start->format( 'r' ),
			( new OrderLimiter( $now ) )->get_interval_start()->format( 'r' ),
			'Weekly intervals should adjust to the week_starts_on option.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_interval_start_for_weekly_when_today_is_the_first_day_of_the_week() {
		update_option( 'week_starts_on', 1 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		// Monday, March 2.
		$now   = new \DateTimeImmutable( '2020-03-02 12:01:00', wp_timezone() );
		$start = new \DateTimeImmutable( '2020-03-02 00:00:00', wp_timezone() );

		$this->assertSame(
			$start->format( 'r' ),
			( new OrderLimiter( $now ) )->get_interval_start()->format( 'r' ),
			'If today is the first day of a weekly interval, the time should be this morning at midnight.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_interval_start_for_monthly() {
		$today = new \DateTimeImmutable( 'now', wp_timezone() );

		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'monthly',
		] );

		$this->assertSame(
			( new \DateTimeImmutable( $today->format( 'F' ) . ' 1', wp_timezone() ) )->format( 'r' ),
			( new OrderLimiter() )->get_interval_start()->format( 'r' ),
			'Monthly intervals should start at midnight on the first day of the month.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_interval_start_should_be_idempotent() {
		$now     = new \DateTimeImmutable( '00:00:00', wp_timezone() );
		$limiter = new OrderLimiter( $now );

		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertSame(
				$now->format( 'r' ),
				$limiter->get_interval_start()->format( 'r' )
			);
		}
	}

	/**
	 * @test
	 * @group Intervals
	 * @ticket https://github.com/nexcess/limit-orders/issues/18
	 */
	public function get_next_interval_start_for_hourly() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'hourly',
		] );

		$now  = new \DateTimeImmutable( '2020-04-27 12:05:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-04-27 13:00:00', wp_timezone() );

		$this->assertSame(
			$next->format( 'r' ),
			( new OrderLimiter( $now ) )->get_next_interval_start()->format( 'r' ),
			'The next hourly interval should begin at the top of the next hour.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_next_interval_start_for_daily() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		// Tuesday, March 3 and Wednesday, March 4.
		$now  = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-03-04 00:00:00', wp_timezone() );

		$this->assertSame(
			$next->format( 'r' ),
			( new OrderLimiter( $now ) )->get_next_interval_start()->format( 'r' ),
			'The next daily interval should begin at midnight.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_next_interval_start_for_weekly() {
		update_option( 'week_starts_on', 1 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		// Tuesday, March 3 and Monday, March 9.
		$now  = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-03-09 00:00:00', wp_timezone() );

		$this->assertSame(
			$next->format( 'r' ),
			( new OrderLimiter( $now ) )->get_next_interval_start()->format( 'r' ),
			'The next weekly interval should begin Monday at midnight.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_next_interval_start_for_monthly() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'monthly',
		] );

		// March 3 and April 1.
		$now  = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-04-01 00:00:00', wp_timezone() );

		$this->assertSame(
			$next->format( 'r' ),
			( new OrderLimiter( $now ) )->get_next_interval_start()->format( 'r' ),
			'The next monthly interval should begin at midnight on April 1.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 * @ticket https://github.com/nexcess/limit-orders/issues/18
	 */
	public function get_seconds_until_next_interval_for_hourly() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'hourly',
		] );

		$now  = new \DateTimeImmutable( '2020-04-27 12:05:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-04-27 13:00:00', wp_timezone() );

		$this->assertSame(
			$next->getTimestamp() - $now->getTimestamp(),
			( new OrderLimiter( $now ) )->get_seconds_until_next_interval(),
			'It should return the number of seconds until the next hour begins.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_seconds_until_next_interval_for_daily() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'daily',
		] );

		// Tuesday, March 3 and Wednesday, March 4.
		$now  = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-03-04 00:00:00', wp_timezone() );

		$this->assertSame(
			$next->getTimestamp() - $now->getTimestamp(),
			( new OrderLimiter( $now ) )->get_seconds_until_next_interval(),
			'It should return the number of seconds until midnight.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_seconds_until_next_interval_for_weekly() {
		update_option( 'week_starts_on', 1 );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'weekly',
		] );

		// Tuesday, March 3 and Monday, March 9.
		$now  = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-03-09 00:00:00', wp_timezone() );

		$this->assertSame(
			$next->getTimestamp() - $now->getTimestamp(),
			( new OrderLimiter( $now ) )->get_seconds_until_next_interval(),
			'It should return the number of seconds until midnight next Monday.'
		);
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function get_seconds_until_next_interval_for_monthly() {
		update_option( OrderLimiter::OPTION_KEY, [
			'interval' => 'monthly',
		] );

		// March 3 and April 1.
		$now  = new \DateTimeImmutable( '2020-03-03 12:01:00', wp_timezone() );
		$next = new \DateTimeImmutable( '2020-04-01 00:00:00', wp_timezone() );

		$this->assertSame(
			$next->getTimestamp() - $now->getTimestamp(),
			( new OrderLimiter( $now ) )->get_seconds_until_next_interval(),
			'It should return the number of seconds until midnight on the first of the month.'
		);
	}

	/**
	 * @test
	 */
	public function has_orders_in_current_interval_should_compare_the_limit_to_remaining_order() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 5,
		] );

		$limiter = new OrderLimiter();
		$limiter->init();

		$this->assertFalse( $limiter->has_orders_in_current_interval() );

		$this->generate_order();

		$this->assertTrue( $limiter->has_orders_in_current_interval() );
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

		set_transient( OrderLimiter::TRANSIENT_NAME, 2 );

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

		set_transient( OrderLimiter::TRANSIENT_NAME, 5 );

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

		set_transient( OrderLimiter::TRANSIENT_NAME, 6 );

		$this->assertTrue( ( new OrderLimiter() )->has_reached_limit() );
	}

	/**
	 * @test
	 */
	public function disable_ordering_registers_a_notice_in_the_header() {
		$limiter = new OrderLimiter();
		$limiter->disable_ordering();

		$this->assertSame( 10, has_action( 'wp', [ $limiter, 'customer_notice' ] ) );
	}

	/**
	 * @test
	 */
	public function disable_ordering_prevents_items_from_being_added_to_a_cart() {
		$product = WC_Helper_Product::create_simple_product( true );

		$this->assertTrue( false !== WC()->cart->add_to_cart( $product->get_id(), 1 ) );

		( new OrderLimiter() )->disable_ordering();

		$this->assertFalse(
			WC()->cart->add_to_cart( $product->get_id(), 1 ),
			'Customers should not be able to add items into their carts.'
		);
	}

	/**
	 * @test
	 */
	public function disable_ordering_prevents_customers_from_being_able_to_checkout() {
		$this->assertTrue( is_int( $this->generate_order() ) );

		( new OrderLimiter() )->disable_ordering();

		$this->assertWPError( $this->generate_order() );
	}

	/**
	 * @test
	 */
	public function store_owners_should_still_be_able_to_create_orders_through_WP_Admin() {
		( new OrderLimiter() )->disable_ordering();

		$this->assertGreaterThan( 0, wc_create_order()->get_id() );
	}

	/**
	 * @test
	 */
	public function customer_notice_should_register_a_new_WooCommerce_notice() {
		add_filter( 'is_woocommerce', '__return_true' );

		$this->assertSame( 0, wc_notice_count( 'notice' ) );

		( new OrderLimiter() )->customer_notice();

		$this->assertSame( 1, wc_notice_count( 'notice' ) );
	}

	/**
	 * @test
	 */
	public function customer_notice_should_not_register_duplicate_messages() {
		add_filter( 'is_woocommerce', '__return_true' );

		$limiter = new OrderLimiter();

		$limiter->customer_notice();
		$limiter->customer_notice();
		$limiter->customer_notice();

		$this->assertSame( 1, wc_notice_count( 'notice' ) );
	}

	/**
	 * @test
	 */
	public function customer_notice_should_not_add_a_notice_on_non_WooCommerce_pages() {
		add_filter( 'is_woocommerce', '__return_false' );

		( new OrderLimiter() )->customer_notice();

		$this->assertSame( 0, wc_notice_count( 'notice' ) );
	}

	/**
	 * @test
	 * @ticket https://wordpress.org/support/topic/php-call-to-undefined-function
	 */
	public function customer_notice_should_not_display_in_wp_admin() {
		set_current_screen( 'edit-product' );
		add_filter( 'is_woocommerce', '__return_true' );

		$limiter = $this->getMockBuilder( OrderLimiter::class )
			->setMethods( [ 'get_message' ] )
			->getMock();
		$limiter->expects( $this->never() )
			->method( 'get_message' );

		$this->assertNull( $limiter->customer_notice() );
		$this->assertSame( 0, wc_notice_count( 'notice' ), 'No notices should have been added.' );
	}

	/**
	 * @test
	 */
	public function disable_ordering_makes_items_unpurchasable() {
		$product = WC_Helper_Product::create_simple_product( true );

		$this->assertTrue( $product->is_purchasable() );

		( new OrderLimiter() )->disable_ordering();

		$this->assertFalse( $product->is_purchasable() );
	}

	/**
	 * @test
	 */
	public function regenerate_transient_should_create_the_site_transient() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled'  => true,
			'interval' => 'daily',
			'limit'    => 5,
		] );

		$this->assertFalse( get_transient( OrderLimiter::TRANSIENT_NAME ) );

		$limiter = $this->getMockBuilder( OrderLimiter::class )
			->setMethods( [ 'count_qualifying_orders' ] )
			->getMock();
		$limiter->expects( $this->once() )
			->method( 'count_qualifying_orders' )
			->willReturn( 3 );

		$this->assertSame( 3, $limiter->regenerate_transient() );
		$this->assertSame( 3, get_transient( OrderLimiter::TRANSIENT_NAME ) );
	}

	/**
	 * @test
	 */
	public function reset_should_delete_the_transient_cache() {
		set_transient( OrderLimiter::TRANSIENT_NAME, 5 );

		( new OrderLimiter() )->reset();

		$this->assertFalse( get_transient( OrderLimiter::TRANSIENT_NAME ) );
	}

	/**
	 * @test
	 */
	public function the_transient_should_be_updated_each_time_an_order_is_placed() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled'  => true,
			'interval' => 'daily',
			'limit'    => 5,
		] );

		( new OrderLimiter() )->init();

		$this->assertFalse( get_transient( OrderLimiter::TRANSIENT_NAME ) );

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->generate_order();

			$this->assertSame( $i, get_transient( OrderLimiter::TRANSIENT_NAME ) );
		}
	}

	/**
	 * @test
	 * @ticket https://github.com/nexcess/limit-orders/issues/36
	 */
	public function the_limiter_should_be_reset_when_settings_are_changed() {
		set_transient( OrderLimiter::TRANSIENT_NAME, uniqid() );
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled'  => true,
			'interval' => 'daily',
			'limit'    => 5,
		] );

		( new OrderLimiter() )->init();

		update_option( OrderLimiter::OPTION_KEY, [
			'enabled'  => true,
			'interval' => 'hourly',
			'limit'    => 2,
		] );

		$this->assertFalse( get_transient( OrderLimiter::TRANSIENT_NAME ) );
	}

	/**
	 * @test
	 * @ticket https://github.com/nexcess/limit-orders/issues/36
	 */
	public function the_limiter_should_be_not_reset_unless_settings_have_actually_been_updated() {
		$transient = uniqid();

		set_transient( OrderLimiter::TRANSIENT_NAME, $transient );
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled'  => true,
			'interval' => 'daily',
			'limit'    => 5,
		] );

		( new OrderLimiter() )->init();

		update_option( OrderLimiter::OPTION_KEY, get_option( OrderLimiter::OPTION_KEY ) );

		$this->assertSame( $transient, get_transient( OrderLimiter::TRANSIENT_NAME ) );
	}

	/**
	 * @test
	 * @ticket https://github.com/nexcess/limit-orders/pull/13
	 */
	public function count_qualifying_orders_should_not_limit_results() {
		update_option( 'posts_per_page', 2 ); // Lower the default to improve test performance.
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled'  => true,
			'interval' => 'daily',
			'limit'    => 100,
		] );

		for ( $i = 0; $i < 5; $i++ ) {
			$this->generate_order();
		}

		$instance = new OrderLimiter();
		$method   = new \ReflectionMethod( $instance, 'count_qualifying_orders' );
		$method->setAccessible( true );

		$this->assertSame( 5, $method->invoke( $instance ) );
	}

	/**
	 * @test
	 * @testdox count_qualifying_orders() should be filterable
	 */
	public function count_qualifying_orders_should_be_filterable() {
		$instance = new OrderLimiter();
		$called   = false;
		$method   = new \ReflectionMethod( $instance, 'count_qualifying_orders' );
		$method->setAccessible( true );

		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 1,
		] );

		add_filter( 'limit_orders_pre_count_qualifying_orders', function ( $preempt, $limiter ) use ( $instance, &$called ) {
			$this->assertFalse( $preempt );
			$this->assertSame( $instance, $limiter );
			$called = true;

			return 5;
		}, 10, 2 );

		$this->assertSame( 5, $method->invoke( $instance ) );
		$this->assertTrue( $called );
	}

	/**
	 * @test
	 * @testdox Return values from the limit_orders_pre_count_qualifying_orders filter should be cast as integers
	 */
	public function return_values_from_pre_count_qualifying_orders_should_be_cast_as_int() {
		$instance = new OrderLimiter();
		$method   = new \ReflectionMethod( $instance, 'count_qualifying_orders' );
		$method->setAccessible( true );

		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 1,
		] );

		add_filter( 'limit_orders_pre_count_qualifying_orders', '__return_true' );

		$this->assertSame( 1, $method->invoke( $instance ) );
	}
}
