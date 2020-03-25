<?php
/**
 * Tests for the admin UI.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Tests;

use Nexcess\WooCommerceLimitOrders\Admin;
use Nexcess\WooCommerceLimitOrders\OrderLimiter;
use WC_Admin;
use WC_Settings_General;
use WP_UnitTestCase as TestCase;

/**
 * @covers Nexcess\WooCommerceLimitOrders\Admin
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

		$this->assertStringContainsString( esc_attr( admin_url( 'admin.php?page=wc-settings&tab=woocommerce-limit-orders' ) ), $output );
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
}
