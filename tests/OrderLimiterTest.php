<?php
/**
 * Tests for the order limiting functionality.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Tests;

use Nexcess\WooCommerceLimitOrders\OrderLimiter;
use WC_Checkout;
use WC_Form_Handler;
use WC_Helper_Product;
use WP_UnitTestCase as TestCase;

/**
 * @covers Nexcess\WooCommerceLimitOrders\OrderLimiter
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
	 */
	public function get_message_should_replace_placeholders() {
		update_option( 'date_format', 'F j, Y' );
		update_option( OrderLimiter::OPTION_KEY, [
			'interval'        => 'weekly',
			'customer_notice' => 'Check back on %NEXT_INTERVAL%',
		] );

		$now     = new \DateTimeImmutable( 'now', wp_timezone() );
		$limiter = new OrderLimiter( $now );

		$this->assertSame(
			'Check back on ' . $limiter->get_next_interval_start()->format( 'F j, Y' ),
			$limiter->get_message( 'customer_notice' )
		);
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
		$this->assertIsNumeric( $this->generate_order() );

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
	 * Create a new order by emulating the checkout process.
	 */
	protected function generate_order() {
		$product = WC_Helper_Product::create_simple_product( true );

		WC()->cart->add_to_cart( $product->get_id(), 1 );

		return WC_Checkout::instance()->create_order( [
			'billing_email'  => 'test_customer@example.com',
			'payment_method' => 'dummy_payment_gateway',
		] );
	}
}
