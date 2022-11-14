<?php
/**
 * Responsible for limiting orders on a WooCommerce site.
 *
 * @package Nexcess\LimitOrders
 */

namespace Nexcess\LimitOrders;

use DateTimeImmutable;
use Nexcess\LimitOrders\Exceptions\EmptyOrderTypesException;
use Nexcess\LimitOrders\Exceptions\OrdersNotAcceptedException;

class OrderLimiter {

	/**
	 * Holds the current DateTime.
	 *
	 * @var DateTimeImmutable
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
	const OPTION_KEY = 'limit_orders';

	/**
	 * The transient that holds the current order count per period.
	 */
	const TRANSIENT_NAME = 'limit_orders_order_count';

	/**
	 * Create a new instance of the OrderLimiter.
	 *
	 * @param DateTimeImmutable|null $now Optional. A DateTimeImmutable object to use as the basis for
	 *                                all calculations. Default is current_datetime().
	 */
	public function __construct( DateTimeImmutable $now = null ) {
		$this->now = $now ? $now : current_datetime();
	}

	/**
	 * Initialize hooks used by the limiter.
	 */
	public function init() {
		add_action( 'woocommerce_new_order', [ $this, 'regenerate_transient' ] );
		add_action( 'update_option_' . self::OPTION_KEY, [ $this, 'reset_limiter_on_update' ], 10, 2 );
	}

	/**
	 * Is limiting currently enabled for this store?
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) $this->get_setting( 'enabled', false );
	}

	/**
	 * Retrieve the interval setting.
	 *
	 * @return string The order limiter's interval.
	 */
	public function get_interval(): ?string {
		return $this->get_setting( 'interval', 'daily' );
	}

	/**
	 * Retrieve the number of orders permitted per interval.
	 *
	 * @return int The maximum number of orders, or -1 if there is no limit.
	 */
	public function get_limit(): int {
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
	public function get_message( string $setting ): string {
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
			$message = __( 'Ordering is currently disabled for this store.', 'limit-orders' );
		}

		// Perform simple placeholder replacements.
		$placeholders = $this->get_placeholders( $setting, $message );

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $message );
	}

	/**
	 * Retrieve eligible placeholders for front-end messaging.
	 *
	 * Note that the parameters are only included for the sake of the filter.
	 *
	 * @param string $setting Optional. The current setting that's being retrieved. Default is empty.
	 * @param string $message Optional. The current message being constructed. Default is empty.
	 *
	 * @return array An array of placeholder => replacements.
	 */
	public function get_placeholders( string $setting = '', string $message = '' ): array {
		$date_format  = get_option( 'date_format' );
		$time_format  = get_option( 'time_format' );
		$current      = $this->get_interval_start();
		$next         = $this->get_next_interval_start();
		$within24hr   = $next->getTimestamp() - $current->getTimestamp() < DAY_IN_SECONDS;
		$placeholders = [
			'{current_interval}'      => $current->format( $within24hr ? $time_format : $date_format ),
			'{current_interval:date}' => $current->format( $date_format ),
			'{current_interval:time}' => $current->format( $time_format ),
			'{limit}'                 => $this->get_limit(),
			'{next_interval}'         => $next->format( $within24hr ? $time_format : $date_format ),
			'{next_interval:date}'    => $next->format( $date_format ),
			'{next_interval:time}'    => $next->format( $time_format ),
			'{timezone}'              => $next->format( 'T' ),
		];

		/**
		 * Filter the available placeholders for customer-facing messages.
		 *
		 * @param array  $placeholders The currently-defined placeholders.
		 * @param string $setting      The current message's setting key.
		 * @param string $message      The current message to display.
		 */
		return apply_filters( 'limit_orders_message_placeholders', $placeholders, $setting, $message );
	}

	/**
	 * Retrieve the number of remaining for this interval.
	 *
	 * @return int The maximum number of that may still be accepted, or -1 if there is no limit.
	 */
	public function get_remaining_orders(): int {
		$limit = $this->get_limit();

		/**
		 * Filter the number of orders remaining for the current interval.
		 *
		 * @param bool         $preempt Whether the default logic should be preempted.
		 *                              Returning anything besides FALSE will be treated as the
		 *                              number of remaining orders that can be accepted.
		 * @param OrderLimiter $limiter The current OrderLimiter object.
		 */
		$remaining = apply_filters( 'limit_orders_pre_get_remaining_orders', false, $this );

		// Return early if a non-false value was returned from the filter.
		if ( false !== $remaining ) {
			return (int) $remaining;
		}

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
	 * @return DateTimeImmutable
	 */
	public function get_interval_start(): DateTimeImmutable {
		$interval = $this->get_setting( 'interval' );
		$start    = $this->now;

		switch ( $interval ) {
			case 'hourly':
				// Start at the top of the current hour.
				$start = $start->setTime( (int) $start->format( 'G' ), 0, 0 );
				break;

			case 'daily':
				// Start at midnight.
				$start = $start->setTime( 0, 0, 0 );
				break;

			case 'weekly':
				$start_of_week = (int) get_option( 'week_starts_on' );
				$current_dow   = (int) $start->format( 'w' );
				$diff          = $current_dow - $start_of_week;

				// Compensate for values outside of 0-6.
				if ( 0 > $diff ) {
					$diff += 7;
				}

				// A difference of 0 means today is the start; anything else and we need to change $start.
				if ( 0 !== $diff ) {
					$start = $start->sub( new \DateInterval( 'P' . $diff . 'D' ) );
				}

				$start = $start->setTime( 0, 0, 0 );
				break;

			case 'monthly':
				$start = $start->setDate( (int) $start->format( 'Y' ), (int) $start->format( 'm' ), 1 )
					->setTime( 0, 0, 0 );
				break;
		}

		/**
		 * Filter the DateTime object representing the start of the current interval.
		 *
		 * @param DateTimeImmutable $start    The DateTimeImmutable representing the start of the current interval.
		 * @param string             $interval The type of interval being calculated.
		 */
		return apply_filters( 'limit_orders_interval_start', $start, $interval );
	}

	/**
	 * Get a DateTime object representing the start of the next interval.
	 *
	 * @return DateTimeImmutable
	 */
	public function get_next_interval_start(): DateTimeImmutable {
		$interval = $this->get_setting( 'interval' );
		$current  = $this->get_interval_start();
		$start    = clone $current;

		switch ( $interval ) {
			case 'hourly':
				$start = $start->add( new \DateInterval( 'PT1H' ) );
				break;

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
		 * @param DateTimeImmutable $start    A DateTime representing the start time for the next interval.
		 * @param DateTimeImmutable $current  A DateTime representing the beginning of the current interval.
		 * @param string    $interval The specified interval.
		 */
		return apply_filters( 'limit_orders_next_interval', $start, $current, $interval );
	}

	/**
	 * Retrieve the number of seconds until the next interval starts.
	 *
	 * @return int The number of seconds until the limiting interval resets.
	 */
	public function get_seconds_until_next_interval(): int {
		return $this->get_next_interval_start()->getTimestamp() - $this->now->getTimestamp();
	}

	/**
	 * Determine whether the store has any orders in the given interval.
	 *
	 * @return bool
	 */
	public function has_orders_in_current_interval(): bool {
		return $this->get_limit() > $this->get_remaining_orders();
	}

	/**
	 * Determine whether the store has reached its limits.
	 *
	 * @return bool
	 */
	public function has_reached_limit(): bool {
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
	 * @throws OrdersNotAcceptedException
	 */
	public function abort_checkout() {
		throw new OrdersNotAcceptedException( $this->get_message( 'checkout_error' ) );
	}

	/**
	 * Replace the "place order" button on the checkout screen.
	 *
	 * @return string
	 */
	public function order_button_html(): string {
		return '<p>' . wp_kses_post( $this->get_message( 'order_button' ) ) . '</p>';
	}

	/**
	 * Display a notice on the front-end of the site.
	 */
	public function customer_notice() {
		// Only display on WooCommerce pages.
		if ( is_admin() || ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) ) {
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
	public function regenerate_transient(): int {
		try {
			$count = $this->count_qualifying_orders();
		} catch ( EmptyOrderTypesException $e ) {
			// Return 0 for now but try to populate this transient later, after $wc_order_types has been populated.
			add_action( 'init', [ $this, 'regenerate_transient' ] );
			return 0;
		}

		set_transient( self::TRANSIENT_NAME, $count, $this->get_seconds_until_next_interval() );

		return $count;
	}

	/**
	 * Reset the order limiter.
	 */
	public function reset() {
		delete_transient( self::TRANSIENT_NAME );
	}

	/**
	 * Reset the limiter when its configuration changes.
	 *
	 * @param mixed $previous The previous value of the option.
	 * @param mixed $new      The new option value.
	 */
	public function reset_limiter_on_update( $previous, $new ) {
		if ( $previous !== $new ) {
			$this->reset();
		}
	}

	/**
	 * Count the number of qualifying orders.
	 *
	 * @return int The number of orders that have taken place within the defined interval.
	 */
	protected function count_qualifying_orders(): int {
		/**
		 * Replace the logic used to count qualified orders.
		 *
		 * @param bool         $preempt Whether the counting logic should be preempted. Returning
		 *                              anything but FALSE will bypass the default logic.
		 * @param OrderLimiter $limiter The current OrderLimiter instance.
		 */
		$count = apply_filters( 'limit_orders_pre_count_qualifying_orders', false, $this );

		if ( false !== $count ) {
			return (int) $count;
		}

		$order_types = wc_get_order_types( 'order-count' );

		if ( empty( $order_types ) ) {
			throw new EmptyOrderTypesException( 'No order types were found.' );
		}

		$orders = wc_get_orders( [
			'type'         => $order_types,
			'date_created' => '>=' . $this->get_interval_start()->getTimestamp(),
			'return'       => 'ids',
			'limit'        => max( $this->get_limit(), 1000 ),
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
	protected function get_setting( string $setting, $default = null ) {
		if ( null === $this->settings ) {
			$this->settings = get_option( self::OPTION_KEY, [] );
		}

		return $this->settings[ $setting ] ?? $default;
	}
}
