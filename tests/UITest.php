<?php
/**
 * Tests for the admin UI.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Tests;

use Nexcess\WooCommerceLimitOrders\OrderLimiter;
use Nexcess\WooCommerceLimitOrders\UI;
use WP_UnitTestCase as TestCase;

/**
 * @covers Nexcess\WooCommerceLimitOrders\UI
 * @group UI
 */
class UITest extends TestCase {

	/**
	 * @before
	 */
	public function init() {
		remove_all_filters( 'woocommerce_get_settings_general' );

		( new UI() )->init();
	}

	/**
	 * @test
	 */
	public function the_options_should_be_added_to_the_general_WooCommerce_settings() {
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
		$intervals = [
			YEAR_IN_SECONDS => uniqid(),
		];

		add_filter( 'woocommerce_limit_orders_intervals', function () use ( $intervals ) {
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
}
