<?php
/**
 * Tests for the admin UI.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Tests;

use Nexcess\WooCommerceLimitOrders\OrderLimiter;
use Nexcess\WooCommerceLimitOrders\UI;
use WC_Admin;
use WC_Settings_General;
use WP_UnitTestCase as TestCase;

/**
 * @covers Nexcess\WooCommerceLimitOrders\UI
 * @group UI
 */
class UITest extends TestCase {

	/**
	 * @test
	 */
	public function the_options_should_be_added_to_the_general_WooCommerce_settings() {
		( new UI( new OrderLimiter() ) )->init();

		$settings = apply_filters( 'woocommerce_get_settings_general', [] );

		// The first entry should be the title.
		$opening = array_shift( $settings );

		$this->assertSame( 'title', $opening['type'] );
		$this->assertSame( 'woocommerce-limit-orders', $opening['id'] );
	}

	/**
	 * @test
	 */
	public function available_intervals_should_be_filterable() {
		( new UI( new OrderLimiter() ) )->init();

		$intervals = [
			YEAR_IN_SECONDS => uniqid(),
		];

		add_filter( 'woocommerce_limit_orders_interval_select', function () use ( $intervals ) {
			return $intervals;
		} );

		$settings = apply_filters( 'woocommerce_get_settings_general', [] );

		foreach ( $settings as $setting ) {
			if ( OrderLimiter::OPTION_KEY . '[interval]' !== $setting['id'] ) {
				continue;
			}

			$this->assertSame( $intervals, $setting['options'] );
			return;
		}

		$this->fail( 'Did not find setting with ID "'. OrderLimiter::OPTION_KEY . '[interval]".' );
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
		( new UI( $limiter ) )->admin_notice();
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
		( new UI( $limiter ) )->admin_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( admin_url( 'admin.php?page=wc-settings' ), $output );
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
		( new UI( $limiter ) )->admin_notice();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( admin_url( 'admin.php?page=wc-settings' ), $output );
	}
}
