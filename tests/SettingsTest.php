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
	 * @ticket https://github.com/nexcess/limit-orders/issues/18
	 */
	public function it_should_include_default_intervals() {
		$method = new \ReflectionMethod( Settings::class, 'get_intervals' );
		$method->setAccessible( true );

		$intervals = $method->invoke( new Settings( new OrderLimiter() ) );

		$this->assertArrayHasKey( 'daily', $intervals );
		$this->assertArrayHasKey( 'weekly', $intervals );
		$this->assertArrayHasKey( 'monthly', $intervals );
		$this->assertArrayHasKey( 'hourly', $intervals, 'Hourly was added in https://github.com/nexcess/limit-orders/issues/18' );
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

	/**
	 * @test
	 * @group Placeholders
	 */
	public function available_placeholders_should_be_shown_in_the_messages_section() {
		$limiter  = new OrderLimiter();
		$sections = array_filter( ( new Settings( new OrderLimiter() ) )->get_settings(), function ( $section ) {
			return 'limit-orders-messaging' === $section['id'] && 'title' === $section['type'];
		} );

		$this->assertCount( 1, $sections, 'Expected to see only one instance of "limit-orders-messaging".' );

		$description = current( $sections )['desc'];

		foreach ( $limiter->get_placeholders() as $placeholder => $value ) {
			$this->assertContains( '<var>' . $placeholder . '</var>', $description );
		}
	}
}
