<?php
/**
 * Define the UI for configuring order limits.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Nexcess\WooCommerceLimitOrders;

class UI {

	/**
	 * Add the necessary hooks.
	 */
	public function init() {
		add_filter( 'woocommerce_get_settings_general', [ $this, 'get_settings' ] );
		add_filter(
			'woocommerce_admin_settings_sanitize_option_' . OrderLimiter::OPTION_KEY,
			[ $this, 'convert_interval_to_unix_timestamp' ],
			10,
			2
		);
	}

	/**
	 * Append our settings section to WooCommerce > Settings > General.
	 *
	 * @param array $settings The general settings section.
	 *
	 * @return array The filtered array, including our settings.
	 *
	 * @todo Don't rely on datetime-local in browsers that don't support it (Firefox, Safari); see
	 *       https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local#Browser_compatibility
	 */
	public function get_settings( array $settings ) {
		return array_merge( $settings, [
			[
				'id'   => 'woocommerce-limit-orders',
				'type' => 'title',
				'name' => __( 'Limit orders', 'woocommerce-limit-orders' ),
				'desc' => __( 'Automatically turn off new orders once the store\'s limit has been met.', 'woocommerce-limit-orders' ),
			],
			[
				'id'      => OrderLimiter::OPTION_KEY . '[limit]',
				'name'    => 'Order threshold',
				'desc'    => 'Stop accepting orders once this limit has been reached. -1 will disable limiting.',
				'type'    => 'number',
				'css'     => 'width: 150px;',
				'default' => -1,
			],
			[
				'id'      => OrderLimiter::OPTION_KEY . '[interval]',
				'name'    => 'Interval',
				'desc'    => 'How frequently the limit will be reset.',
				'type'    => 'select',
				'options' => $this->get_intervals(),
				'default' => OrderLimiter::DEFAULT_INTERVAL,
			],
			[
				'id'                => OrderLimiter::OPTION_KEY . '[interval_start]',
				'name'              => 'Start Date',
				'desc'              => 'Select the local date/time at which the interval should begin.',
				'type'              => 'datetime-local',
				'placeholder'       => 'YYYY-MM-DD HH:MM',
				'custom_attributes' => [
					'pattern' => '[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}',
				],
			],
			[
				'id'   => 'woocommerce-limit-orders',
				'type' => 'sectionend',
			],
		] );
	}

	/**
	 * Save interval_start as a Unix timestamp.
	 *
	 * @param mixed  $value  The value being saved.
	 * @param string $option The option being saved.
	 *
	 * @return mixed The potentially-filtered $value.
	 */
	public function convert_interval_to_unix_timestamp( $value, array $option ) {
		if ( ! isset( $option['id'] ) || $option['id'] !== OrderLimiter::OPTION_KEY . '[interval_start]' ) {
			return $value;
		}

		$date = new \DateTimeImmutable( $value, wp_timezone() );

		return $date->format( 'U' );
	}

	/**
	 * Retrieve the available intervals for order limiting.
	 *
	 * @return array An array of interval names, keyed with their lengths in seconds.
	 */
	protected function get_intervals() {
		$intervals = [
			DAY_IN_SECONDS   => _x( 'Daily', 'order threshold interval', 'woocommerce-limit-orders' ),
			WEEK_IN_SECONDS  => _x( 'Weekly', 'order threshold interval', 'woocommerce-limit-orders' ),
			MONTH_IN_SECONDS => _x( 'Monthly', 'order threshold interval', 'woocommerce-limit-orders' ),
		];

		/**
		 * Filter the available intervals for WooCommerce Limit Orders.
		 *
		 * @param array $intervals Available intervals for WooCommerce Limit Orders.
		 */
		return apply_filters( 'woocommerce_limit_orders_intervals', $intervals );
	}
}
