<?php
/**
 * Responsible for limiting orders on a WooCommerce site.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Nexcess\WooCommerceLimitOrders;

use Nexcess\WooCommerceLimitOrders\Exceptions\OrdersNotAcceptedException;

class OrderLimiter {

	/**
	 * Holds the current DateTime.
	 *
	 * @var \DateTimeImmutable
	 */
	private $now;

	/**
	 * The cached value of the wp_options array.
	 *
	 * @var array
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
	 * Create a new instance of the OrderLimiter.
	 *
	 * @param \DateTimeImmutable $now Optional. A DateTimeImmutable object to use as the basis for
	 *                                all calculations. Default is current_datetime().
	 */
	public function __construct( \DateTimeImmutable $now = null ) {
		$this->now = $now ? $now : current_datetime();
	}

	/**
	 * Initialize hooks used by the limiter.
	 */
	public function init() {
		add_action( 'woocommerce_new_order', [ $this, 'regenerate_transient' ] );
	}

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

		return $this->is_enabled() && is_numeric( $limit ) && 0 <= $limit ? (int) $limit : -1;
	}

	/**
	 * Retrieve a user-provided string and apply any transformations.
	 *
	 * @param string $setting The message's setting key. Must be one of "checkout_error",
	 *                        "customer_notice", or "order_button".
	 *
	 * @return string The user-provided setting, or a generic default if nothing is available.
	 */
	public function get_message( string $setting ) {
		$settings = [
			'checkout_error',
			'customer_notice',
			'order_button',
		];

		// Don't risk exposing any/all settings.
		if ( ! in_array( $setting, $settings, true ) ) {
			return '';
		}

		$message = $this->get_setting( $setting );

		if ( null === $message ) {
			$message = __( 'Ordering is currently disabled for this store.', 'woocommerce-limit-orders' );
		}

		// Perform simple placeholder replacements.
		$date_format  = get_option( 'date_format' );
		$placeholders = [
			'{current_interval}' => $this->get_interval_start()->format( $date_format ),
			'{limit}'            => $this->get_limit(),
			'{next_interval}'    => $this->get_next_interval_start()->format( $date_format ),
		];

		/**
		 * Filter the available placeholders for customer-facing messages.
		 *
		 * @param array  $placeholders The currently-defined placeholders.
		 * @param string $setting      The current message's setting key.
		 * @param string $message      The current message to display.
		 */
		$placeholders = apply_filters( 'woocommerce_limit_orders_message_placeholders', $placeholders, $setting, $message );

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $message );
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

		$orders = get_transient( self::TRANSIENT_NAME );

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
		$start    = $this->now;

		switch ( $interval ) {
			case 'weekly':
				$start_of_week = (int) get_option( 'week_starts_on' );
				$current_dow   = (int) $start->format( 'w' );

				// If today isn't the start of the week, get a DateTime representing that day.
				if ( $current_dow !== $start_of_week ) {
					if ( $current_dow > $start_of_week ) {
						$diff = $current_dow - $start_of_week;
					} elseif ( $current_dow < $start_of_week ) {
						$diff = $current_dow + 7 - $start_of_week;
					}

					$start = $start->sub( new \DateInterval( 'P' . $diff . 'D' ) );
				}
				break;

			case 'monthly':
				$start = $start->setDate( (int) $start->format( 'Y' ), (int) $start->format( 'm' ), 1 );
				break;
		}

		// Start everything at midnight.
		$start = $start->setTime( 0, 0, 0 );

		/**
		 * Filter the DateTime object representing the start of the current interval.
		 *
		 * @param \DateTime $start    The DateTime representing the start of the current interval.
		 * @param string    $interval The type of interval being calculated.
		 */
		return apply_filters( 'woocommerce_limit_orders_interval_start', $start, $interval );
	}

	/**
	 * Get a DateTime object representing the start of the next interval.
	 *
	 * @return \DateTime
	 */
	public function get_next_interval_start() {
		$interval = $this->get_setting( 'interval' );
		$current  = $this->get_interval_start();
		$start    = clone $current;

		switch ( $interval ) {
			case 'daily':
				$start = $start->add( new \DateInterval( 'P1D' ) );
				break;

			case 'weekly':
				$start = $start->add( new \DateInterval( 'P7D' ) );
				break;

			case 'monthly':
				$start = $start->add( new \DateInterval( 'P1M' ) );
				break;
		}

		/**
		 * Filter the DateTime at which the next interval should begin.
		 *
		 * @param \DateTime $start    A DateTime representing the start time for the next interval.
		 * @param \DateTime $current  A DateTime representing the beginning of the current interval.
		 * @param string    $interval The specified interval.
		 */
		return apply_filters( 'woocommerce_limit_orders_next_interval', $start, $current, $interval );
	}

	/**
	 * Retrieve the number of seconds until the next interval starts.
	 *
	 * @return int The number of seconds until the limiting interval resets.
	 */
	public function get_seconds_until_next_interval() {
		return $this->get_next_interval_start()->getTimestamp() - $this->now->getTimestamp();
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
	 */
	public function disable_ordering() {
		add_action( 'wp', [ $this, 'customer_notice' ] );

		// Prevent items from being added to the cart.
		add_filter( 'woocommerce_is_purchasable', '__return_false' );

		// Cause checkouts to fail upon submission.
		add_action( 'woocommerce_checkout_create_order', [ $this, 'abort_checkout' ] );

		// Replace the "place order" button on the checkout screen.
		add_filter( 'woocommerce_order_button_html', [ $this, 'order_button_html' ] );
	}

	/**
	 * Abort the checkout process.
	 *
	 * @throws \Nexcess\WooCommerceLimitOrders\Exceptions\OrdersNotAcceptedException
	 */
	public function abort_checkout() {
		throw new OrdersNotAcceptedException( $this->get_message( 'checkout_error' ) );
	}

	/**
	 * Replace the "place order" button on the checkout screen.
	 *
	 * @return string
	 */
	public function order_button_html() {
		return '<p>' . wp_kses_post( $this->get_message( 'order_button' ) ) . '</p>';
	}

	/**
	 * Display a notice on the front-end of the site.
	 */
	public function customer_notice() {
		// Only display on WooCommerce pages.
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
			return;
		}

		$message = $this->get_message( 'customer_notice' );

		// Prevent the same message from appearing multiple times.
		if ( ! wc_has_notice( $message, 'notice' ) ) {
			wc_add_notice( $message, 'notice' );
		}
	}

	/**
	 * Regenerate the site transient.
	 *
	 * Rather than simply incrementing, we'll explicitly count qualifying orders as they roll in.
	 * This guarantees that we'll have accurate numbers and handle race conditions.
	 *
	 * @return int The number of qualifying orders.
	 */
	public function regenerate_transient() {
		$count = $this->count_qualifying_orders();

		set_transient( self::TRANSIENT_NAME, $count, $this->get_seconds_until_next_interval() );

		return $count;
	}

	/**
	 * Count the number of qualifying orders.
	 *
	 * @return int The number of orders that have taken place within the defined interval.
	 */
	protected function count_qualifying_orders() {
		$orders = wc_get_orders( [
			'type'         => wc_get_order_types( 'order-count' ),
			'date_created' => '>=' . $this->get_interval_start()->getTimestamp(),
			'return'       => 'ids',
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
