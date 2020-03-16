<?php
/**
 * Responsible for limiting orders on a WooCommerce site.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Nexcess\WooCommerceLimitOrders;

class OrderLimiter {

	/**
	 * The cached value of the wp_options array.
	 *
	 * @param array
	 */
	private $settings;

	/**
	 * The key used for the settings stored in wp_options.
	 */
	const OPTION_KEY = 'woocommerce-limit-orders';

	/**
	 * Add the necessary hooks.
	 */
	public function init() {

	}

	/**
	 * Retrieve the number of orders permitted per interval.
	 *
	 * @return int The maximum number of orders, or -1 if there is no limit.
	 */
	public function get_limit() {
		$limit = $this->get_setting( 'limit' );

		return is_int( $limit ) && 0 <= $limit ? $limit : -1;
	}

	/**
	 * Retrieve the number of seconds per interval.
	 *
	 * @return int The number of seconds in each interval.
	 */
	public function get_interval() {
		$interval = $this->get_setting( 'interval' );

		return is_int( $interval ) && 0 < $interval ? $interval : MONTH_IN_SECONDS;
	}

	/**
	 * Retrieve the number of remaining for this interval.
	 *
	 * @return int The maximum number of that may still be accepted, or -1 if there is no limit.
	 */
	public function get_remaining_orders() {

	}

	/**
	 * Retrieve the given key from the cached options array.
	 *
	 * If the key isn't set, return $default instead.
	 *
	 * @param string $setting The setting key to retrieve.
	 *
	 * @return mixed The value of $setting, or null $setting is undefined.
	 */
	protected function get_setting( string $setting ) {
		if ( null === $this->settings ) {
			$this->settings = get_option( self::OPTION_KEY, [] );
		}

		return isset( $this->settings[ $setting ] ) ? $this->settings[ $setting ] : null;
	}
}
