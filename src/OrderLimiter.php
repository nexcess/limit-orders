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
	const OPTION_KEY = 'woocommerce_limit_orders';

	/**
	 * The transient that holds the current order count per period.
	 */
	const TRANSIENT_NAME = 'woocommerce_limit_orders_order_count';

	/**
	 * Is limiting currently enabled for this store?
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) $this->get_setting( 'enabled' );
	}

	/**
	 * Retrieve the number of orders permitted per interval.
	 *
	 * @return int The maximum number of orders, or -1 if there is no limit.
	 */
	public function get_limit() {
		$limit = $this->get_setting( 'limit' );

		return $this->is_enabled() && is_int( $limit ) && 0 <= $limit ? $limit : -1;
	}

	/**
	 * Retrieve the number of remaining for this interval.
	 *
	 * @return int The maximum number of that may still be accepted, or -1 if there is no limit.
	 */
	public function get_remaining_orders() {
		$limit = $this->get_limit();

		// If there are no limits set, return -1.
		if ( ! $this->is_enabled() || -1 === $limit ) {
			return -1;
		}

		$orders = get_site_transient( self::TRANSIENT_NAME );

		// The transient has been cleared, so re-generate it.
		if ( false === $orders ) {
			$orders = $this->regenerate_transient();
		}

		// Never return less than zero.
		return max( $limit - $orders, 0 );
	}

	/**
	 * Get a DateTime object representing the start of the current interval.
	 *
	 * @return \DateTime
	 */
	public function get_interval_start() {
		$interval = $this->get_setting( 'interval' );
		$start    = new \DateTime( 'now', wp_timezone() );

		switch ( $interval ) {
			case 'weekly':
				$start_of_week = (int) get_option( 'week_starts_on' );
				$current_dow   = (int) $start->format( 'w' );

				// If today isn't the start of the week, get a DateTime representing that day.
				if ( $current_dow !== $start_of_week ) {
					$days  = [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ];
					$start = new \DateTime( 'last ' . $days[ $start_of_week ], wp_timezone() );
				}
				break;

			case 'monthly':
				$start->setDate( $start->format( 'Y' ), $start->format( 'm' ), 1 );
				break;
		}

		// Start everything at midnight.
		$start->setTime( 0, 0, 0 );

		/**
		 * Filter the DateTime object representing the start of the current interval.
		 *
		 * @param \DateTime $start    The DateTime representing the start of the current interval.
		 * @param string    $interval The type of interval being calculated.
		 */
		return apply_filters( 'woocommerce_limit_orders_interval_start', $start, $interval );
	}

	/**
	 * Retrieve the number of seconds until the next interval starts.
	 *
	 * @return int The number of seconds until the limiting interval resets.
	 *
	 * @todo Calculate the beginning of this interval, then add one interval to it.
	 */
	public function get_seconds_until_next_interval() {
		return 0;
	}

	/**
	 * Determine whether or not the given store has reached its limits.
	 *
	 * @return bool
	 */
	public function has_reached_limit() {
		return 0 === $this->get_remaining_orders();
	}

	/**
	 * Disable ordering for a WooCommerce store.
	 *
	 * @todo Make the magic happen!
	 */
	public function disable_ordering() {

	}

	/**
	 * Regenerate the site transient.
	 *
	 * Rather than simply incrementing, we'll explicitly count qualifying orders as they roll in.
	 * This guarantees that we'll have accurate numbers and handle race conditions.
	 *
	 * @return int The number of qualifying orders.
	 *
	 * @todo Hook this into new order creation.
	 */
	public function regenerate_transient() {
		$count = $this->count_qualifying_orders();

		set_site_transient( self::TRANSIENT_NAME, $count, $this->get_seconds_until_next_interval() );
	}

	/**
	 * Count the number of qualifying orders.
	 *
	 * @return int The number of orders that have taken place within the defined interval.
	 */
	protected function count_qualifying_orders() {
		$orders = wc_get_orders( [
			'type'       => wc_get_order_types( 'order-count' ),
			'date_after' => $this->get_interval_start(),
			'return'     => 'ids',
		] );

		return count( $orders );
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
