<?php
/**
 * Tests for the settings page.
 *
 * @package Nexcess\LimitOrders
 */

namespace Tests;

use Nexcess\LimitOrders\OrderLimiter;
use Nexcess\LimitOrders\Settings;
use WP_UnitTestCase as TestCase;

/**
 * @covers Nexcess\LimitOrders\Settings
 * @group Admin
 * @group Settings
 */
class SettingsTest extends TestCase {

	/**
	 * @test
	 */
	public function the_options_should_be_added_to_their_own_page() {
		$settings = ( new Settings( new OrderLimiter() ) )->get_settings();

		$this->assertSame( 'title', $settings[0]['type'] );
		$this->assertSame( 'limit-orders-general', $settings[0]['id'] );
	}

	/**
	 * @test
	 * @group Intervals
	 */
	public function available_intervals_should_be_filterable() {
		$intervals = [
			YEAR_IN_SECONDS => uniqid(),
		];

		add_filter( 'limit_orders_interval_select', function () use ( $intervals ) {
			return $intervals;
		} );

		// Find the interval setting and inspect its options.
		foreach ( ( new Settings( new OrderLimiter() ) )->get_settings() as $setting ) {
			if ( OrderLimiter::OPTION_KEY . '[interval]' !== $setting['id'] ) {
				continue;
			}

			$this->assertSame( $intervals, $setting['options'] );
			return;
		}

		$this->fail( 'Did not find setting with ID "'. OrderLimiter::OPTION_KEY . '[interval]".' );
	}
}
