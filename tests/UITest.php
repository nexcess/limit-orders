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
	public function saved_values_should_be_set_as_the_values_for_the_inputs() {
		$values = [
			'limit'    => 5,
			'interval' => HOUR_IN_SECONDS,
		];

		update_option( OrderLimiter::OPTION_KEY, $values );

		$settings = apply_filters( 'woocommerce_get_settings_general', [] );

		// Loop through the $settings and find those that correspond to our values.
		foreach ( $settings as $setting ) {
			if ( ! isset( $setting['id'] ) ) {
				continue;
			}

			// Look for IDs that match "woocommerce-limit-orders[$KEY]".
			if ( ! preg_match( '/^' . preg_quote( OrderLimiter::OPTION_KEY ) . '\[([^\]]+)\]$/', $setting['id'], $match ) ) {
				continue;
			}

			$this->assertSame( $values[ $match[1] ], $setting['value'] );

			// Remove the key from $values.
			unset( $values[ $match[1] ] );
		}

		// Sanity check: we should have hit each and every $values.
		$this->assertEmpty( $values, 'Not all $values were found in $settings.' );
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
