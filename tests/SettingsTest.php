<?php
/**
 * Tests for the settings page.
 *
 * @package Nexcess\LimitOrders
 */

namespace Tests;

use Nexcess\LimitOrders\OrderLimiter;
use Nexcess\LimitOrders\Settings;

/**
 * @covers Nexcess\LimitOrders\Settings
 * @group Admin
 * @group Settings
 */
class SettingsTest extends TestCase {

	/**
	 * @test
	 */
	public function the_settings_should_be_added_to_their_own_page() {
		$instance = new Settings( new OrderLimiter() );

		$this->assertSame( 'limit-orders', $instance->get_id() );
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

		$setting = $this->get_setting_by_id( OrderLimiter::OPTION_KEY . '[interval]' );

		$this->assertArrayHasKey( 'options', $setting );
		$this->assertSame( $intervals, $setting['options'] );
	}

	/**
	 * @test
	 * @group Placeholders
	 */
	public function available_placeholders_should_be_shown_in_the_messages_section() {
		$limiter     = new OrderLimiter();
		$instance    = new Settings( $limiter );
		$description = $this->get_setting_by_id( 'limit-orders-messaging', $instance )['desc'];

		foreach ( $limiter->get_placeholders() as $placeholder => $value ) {
			$this->assertContains( '<var>' . $placeholder . '</var>', $description );
		}
	}

	/**
	 * @test
	 * @ticket https://github.com/nexcess/limit-orders/issues/36
	 */
	public function a_notice_should_be_shown_if_there_have_been_orders_in_the_current_interval() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 10,
		] );

		$limiter = new OrderLimiter();
		$limiter->init();
		$this->set_current_order_count( 1 );

		$this->assertContains(
			'<div class="notice notice-info">',
			$this->get_setting_by_id( 'limit-orders-general', new Settings( $limiter ) )['desc'],
			'Expected to see a notice about limits being recalculated.'
		);
	}

	/**
	 * @test
	 * @ticket https://github.com/nexcess/limit-orders/issues/36
	 */
	public function a_notice_should_not_be_shown_if_there_have_not_been_any_orders_in_the_current_interval() {
		update_option( OrderLimiter::OPTION_KEY, [
			'enabled' => true,
			'limit'   => 10,
		] );

		$limiter = new OrderLimiter();
		$limiter->init();
		$this->set_current_order_count( 0 );

		$this->assertNotContains(
			'<div class="notice notice-info">',
			$this->get_setting_by_id( 'limit-orders-general', new Settings( $limiter ) )['desc'],
			'Did not expect to see a notice about limits being recalculated.'
		);
	}

	/**
	 * Retrieve the given setting by ID.
	 *
	 * If more than one setting matches, the first result will be returned.
	 *
	 * @param string $section_id The setting ID.
	 * @param Settings $instance Optional. An instantiated Settings object, if available.
	 *
	 * @return array|null Either the first matching setting or null if no matches were found.
	 */
	protected function get_setting_by_id( $setting_id, Settings $instance = null ) {
		$instance = $instance ?: new Settings( new OrderLimiter() );
		$settings = array_filter( $instance->get_settings(), function ( $setting ) use ( $setting_id ) {
			return $setting_id === $setting['id'];
		} );

		return current( $settings );
	}
}
