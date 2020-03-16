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
	}

	/**
	 * Append our settings section to WooCommerce > Settings > General.
	 *
	 * @param array $settings The general settings section.
	 *
	 * @return array The filtered array, including our settings.
	 */
	function get_settings( array $settings ) {
		$values = wp_parse_args( get_option( 'woocommerce-limit-orders', [] ), [
			'limit'    => -1,
			'interval' => WEEK_IN_SECONDS,
		] );

		return array_merge( $settings, [
			[
				'id'   => 'woocommerce-limit-orders',
				'type' => 'title',
				'name' => __( 'Limit orders', 'woocommerce-limit-orders' ),
				'desc' => __( 'Automatically turn off new orders once the store\'s limit has been met.', 'woocommerce-limit-orders' ),
			],
			[
				'id'    => 'woocommerce-limit-orders[limit]',
				'name'  => 'Order threshold',
				'desc'  => 'Stop accepting orders once this limit has been reached. -1 will disable limiting.',
				'type'  => 'number',
				'css'   => 'width: 150px;',
				'value' => $values['limit'],
			],
			[
				'id'      => 'woocommerce-limit-orders[interval]',
				'name'    => 'Interval',
				'desc'    => 'How frequently the limit will be reset.',
				'type'    => 'select',
				'options' => $this->get_intervals(),
				'value'   => $values['interval'],
			],
			[
				'id'   => 'woocommerce-limit-orders',
				'type' => 'sectionend',
			],
		] );
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
