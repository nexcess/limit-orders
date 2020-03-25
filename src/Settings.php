<?php
/**
 * Define the WooCommerce settings page for the plugin.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Nexcess\WooCommerceLimitOrders;

use WC_Settings_Page;

class Settings extends WC_Settings_Page {

	/**
	 * Construct the settings page.
	 */
	public function __construct() {
		$this->id    = 'woocommerce-limit-orders';
		$this->label = __( 'Order Limiting', 'woocommerce' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		return apply_filters( 'woocommerce_get_settings_' . $this->id, [
			[
				'id'   => 'woocommerce-limit-orders-general',
				'type' => 'title',
				'name' => _x( 'Order Limiting', 'settings section title', 'woocommerce-limit-orders' ),
				'desc' => __( 'Automatically turn off new orders once the store\'s limit has been met.', 'woocommerce-limit-orders' ),
			],
			[
				'id'      => OrderLimiter::OPTION_KEY . '[enabled]',
				'name'    => __( 'Enable Order Limiting', 'woocommerce-limit-orders' ),
				'desc'    => __( 'Prevent new orders once the specified threshold has been met.', 'woocommerce-limit-orders' ),
				'type'    => 'checkbox',
				'default' => false,
			],
			[
				'id'                => OrderLimiter::OPTION_KEY . '[limit]',
				'name'              => __( 'Order threshold', 'woocommerce-limit-orders' ),
				'desc'              => __( 'Customers will be unable to checkout after this number of orders are made.', 'woocommerce-limit-orders' ),
				'type'              => 'number',
				'css'               => 'width: 150px;',
				'custom_attributes' => [
					'min'  => 0,
					'step' => 1,
				],
			],
			[
				'id'      => OrderLimiter::OPTION_KEY . '[interval]',
				'name'    => __( 'Reset Limits', 'woocommerce-limit-orders' ),
				'desc'    => __( 'How frequently the limit will be reset.', 'woocommerce-limit-orders' ),
				'type'    => 'select',
				'options' => $this->get_intervals(),
			],
			[
				'id'   => 'woocommerce-limit-orders-general',
				'type' => 'sectionend',
			],
		] );
	}

	/**
	 * Retrieve the available intervals for order limiting.
	 *
	 * @global $wp_locale
	 *
	 * @return array An array of interval names, keyed with their lengths in seconds.
	 */
	protected function get_intervals() {
		global $wp_locale;

		$intervals = [
			'daily'   => _x( 'Every day', 'order threshold interval', 'woocommerce-limit-orders' ),
			'weekly'  => sprintf(
				/* Translators: %1$s is the first day of the week, based on site configuration. */
				_x( 'Every %1$s', 'order threshold interval', 'woocommerce-limit-orders' ),
				$wp_locale->get_weekday( get_option( 'start_of_week' ) )
			),
			'monthly' => _x( 'The first day of each month', 'order threshold interval', 'woocommerce-limit-orders' ),
		];

		/**
		 * Filter the available intervals for WooCommerce Limit Orders.
		 *
		 * @param array $intervals Available intervals for WooCommerce Limit Orders.
		 */
		return apply_filters( 'woocommerce_limit_orders_interval_select', $intervals );
	}
}
