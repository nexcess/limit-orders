<?php
/**
 * Tests for the admin UI.
 *
 * @package Nexcess\LimitOrders
 */

namespace Tests;

use Nexcess\LimitOrders\Admin;
use Nexcess\LimitOrders\OrderLimiter;
use WC_Admin_Status;
use WC_REST_System_Status_Tools_V2_Controller;

/**
 * @covers Nexcess\LimitOrders\Admin
 * @group Admin
 */
class AdminTest extends TestCase {

	/**
	 * Plugins that should be installed.
	 *
	 * @var array
	 */
	protected static $plugins = [
		'wpackagist-plugin/woocommerce',
	];

	/**
	 * @before
	 */
	public function activateWooCommerce() {
		$this->activatePlugin( 'woocommerce' );
		WC()->init();
	}

	/**
	 * @test
	 */
	public function admin_notices_should_not_be_shown_if_no_limits_have_been_reached() {
		$limiter = $this->getMockBuilder( OrderLimiter::class )
			->setMethods( [ 'has_reached_limit' ] )
			->getMock();
		$limiter->method( 'has_reached_limit' )
			->willReturn( false );

		ob_start();
		( new Admin( $limiter ) )->admin_notice();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * @test
	 */
	public function admin_notices_should_be_shown_once_limits_are_reached() {
		wp_set_current_user( $this->factory->user->create( [
			'role' => 'administrator',
		] ) );

		$limiter = $this->getMockBuilder( OrderLimiter::class )
			->setMethods( [ 'has_reached_limit' ] )
			->getMock();
		$limiter->method( 'has_reached_limit' )
			->willReturn( true );

		ob_start();
		( new Admin( $limiter ) )->admin_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( esc_attr( admin_url( 'admin.php?page=wc-settings&tab=limit-orders' ) ), $output );
	}

	/**
	 * @test
	 * @depends admin_notices_should_be_shown_once_limits_are_reached
	 */
	public function admin_notices_should_not_include_links_to_settings_for_non_admin_users() {
		wp_set_current_user( $this->factory->user->create( [
			'role' => 'author',
		] ) );

		$limiter = $this->getMockBuilder( OrderLimiter::class )
			->setMethods( [ 'has_reached_limit' ] )
			->getMock();
		$limiter->method( 'has_reached_limit' )
			->willReturn( true );

		ob_start();
		( new Admin( $limiter ) )->admin_notice();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( admin_url( 'admin.php?page=wc-settings' ), $output );
	}

	/**
	 * @test
	 * @group Intervals
	 * @ticket https://github.com/nexcess/limit-orders/issues/18
	 */
	public function intervals_of_less_than_a_day_should_use_time_instead_of_date_in_the_admin_notice() {
		wp_set_current_user( $this->factory->user->create( [
			'role' => 'editor',
		] ) );

		$next    = ( current_datetime() )->setTime( 7, 0, 0 );
		$limiter = $this->getMockBuilder( OrderLimiter::class )
			->setMethods( [ 'has_reached_limit', 'get_next_interval_start' ] )
			->getMock();
		$limiter->method( 'has_reached_limit' )
			->willReturn( true );
		$limiter->method( 'get_next_interval_start' )
			->willReturn( $next );

		ob_start();
		( new Admin( $limiter ) )->admin_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( $next->format( get_option( 'time_format' ) ), $output );
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function admin_notices_should_use_midnight_instead_of_dates_for_daily_interval() {
		wp_set_current_user( $this->factory->user->create( [
			'role' => 'editor',
		] ) );

		$next    = ( current_datetime() )->setTime( 24, 0, 0 ); // Midnight.
		$limiter = $this->getMockBuilder( OrderLimiter::class )
			->setMethods( [ 'has_reached_limit', 'get_next_interval_start' ] )
			->getMock();
		$limiter->method( 'has_reached_limit' )
			->willReturn( true );
		$limiter->method( 'get_next_interval_start' )
			->willReturn( $next );

		ob_start();
		( new Admin( $limiter ) )->admin_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( __( 'midnight' ), $output );
	}

	/**
	 * @test
	 */
	public function the_plugin_should_register_a_debug_tool() {
		( new Admin( new OrderLimiter() ) )->init();

		$this->assertArrayHasKey( 'limit_orders', WC_Admin_Status::get_tools() );
	}

	/**
	 * @test
	 */
	public function the_debug_tool_should_clear_the_order_count_transient() {
		set_transient( OrderLimiter::TRANSIENT_NAME, 5 );

		( new Admin( new OrderLimiter() ) )->init();

		$controller = new WC_REST_System_Status_Tools_V2_Controller();
		$controller->execute_tool( 'limit_orders' );

		$this->assertFalse( get_transient( OrderLimiter::TRANSIENT_NAME ) );
	}

	/**
	 * @test
	 */
	public function clearing_order_transients_should_remove_the_order_count() {
		set_transient( OrderLimiter::TRANSIENT_NAME, 5 );

		( new Admin( new OrderLimiter() ) )->init();

		wc_delete_shop_order_transients();

		$this->assertFalse( get_transient( OrderLimiter::TRANSIENT_NAME ) );
	}
}
