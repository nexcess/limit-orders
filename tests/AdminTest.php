<?php
/**
 * Tests for the admin UI.
 *
 * @package Nexcess\LimitOrders
 */

namespace Tests;

use Nexcess\LimitOrders\Admin;
use Nexcess\LimitOrders\OrderLimiter;
use WC_Admin;
use WC_Settings_General;
use WP_UnitTestCase as TestCase;

/**
 * @covers Nexcess\LimitOrders\Admin
 * @group Admin
 */
class AdminTest extends TestCase {

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

		$this->assertContains( esc_attr( admin_url( 'admin.php?page=wc-settings&tab=limit-orders' ) ), $output );
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

		$this->assertNotContains( admin_url( 'admin.php?page=wc-settings' ), $output );
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

		$next    = ( new \DateTime( 'now' ) )->setTime( 7, 0, 0 );
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

		$this->assertContains( $next->format( get_option( 'time_format' ) ), $output );
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function admin_notices_should_use_midnight_instead_of_dates_for_daily_interval() {
		wp_set_current_user( $this->factory->user->create( [
			'role' => 'editor',
		] ) );

		$next    = ( new \DateTime( 'now' ) )->setTime( 24, 0, 0 ); // Midnight.
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

		$this->assertContains( __( 'midnight' ), $output );
	}
}
