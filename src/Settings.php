<?php
/**
 * Define the WooCommerce settings page for the plugin.
 *
 * @package Nexcess\LimitOrders
 */

namespace Nexcess\LimitOrders;

use WC_Settings_Page;

class Settings extends WC_Settings_Page {

	/**
	 * The current limiter instance.
	 *
	 * @var \Nexcess\LimitOrders\OrderLimiter
	 */
	private $limiter;

	/**
	 * Construct the settings page.
	 */
	public function __construct( OrderLimiter $limiter ) {
		$this->id      = 'limit-orders';
		$this->label   = __( 'Order Limiting', 'limit-orders' );
		$this->limiter = $limiter;

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$placeholders           = (array) $this->limiter->get_placeholders();
		$update_warning         = '';
		$available_placeholders = '';

		// Warn users if changes will impact current limits.
		if ( $this->limiter->has_orders_in_current_interval() ) {
			$update_warning  = '<div class="notice notice-info"><p>';
			$update_warning .= __( 'Please be aware that making changes to these settings will recalculate limits for the current interval.', 'limit-orders' );
			$update_warning .= '</p></div>';
		}

		// Build a list of available placeholders.
		if ( ! empty( $placeholders ) ) {
			$available_placeholders  = __( 'Available placeholders:', 'limit-orders' ) . ' <var>';
			$available_placeholders .= implode( '</var>, <var>', array_keys( $placeholders ) );
			$available_placeholders .= '</var>';
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, [
			[
				'id'   => 'limit-orders-general',
				'type' => 'title',
				'name' => _x( 'Order Limiting', 'settings section title', 'limit-orders' ),
				'desc' => __( 'Automatically turn off new orders once the store\'s limit has been met.', 'limit-orders' ) . $update_warning,
			],
			[
				'id'      => OrderLimiter::OPTION_KEY . '[enabled]',
				'name'    => __( 'Enable Order Limiting', 'limit-orders' ),
				'desc'    => __( 'Prevent new orders once the specified threshold has been met.', 'limit-orders' ),
				'type'    => 'checkbox',
				'default' => false,
			],
			[
				'id'                => OrderLimiter::OPTION_KEY . '[limit]',
				'name'              => __( 'Maximum # of orders', 'limit-orders' ),
				'desc_tip'          => __( 'Customers will be unable to checkout after this number of orders are made.', 'limit-orders' ),
				'type'              => 'number',
				'css'               => 'width: 150px;',
				'custom_attributes' => [
					'min'  => 0,
					'step' => 1,
				],
			],
			[
				'id'       => OrderLimiter::OPTION_KEY . '[interval]',
				'name'     => __( 'Interval', 'limit-orders' ),
				'desc_tip' => __( 'How frequently the limit will be reset.', 'limit-orders' ),
				'type'     => 'select',
				'options'  => $this->get_intervals(),
			],
			[
				'id'   => 'limit-orders-general',
				'type' => 'sectionend',
			],
			[
				'id'   => 'limit-orders-messaging',
				'type' => 'title',
				'name' => _x( 'Customer messaging', 'settings section title', 'limit-orders' ),
				'desc' => '<p>' . __( 'Customize the messages shown to customers once ordering is disabled.', 'limit-orders' ) . '</p>' . $available_placeholders ? '<p>' . $available_placeholders . '</p>' : '',
			],
			[
				'id'       => OrderLimiter::OPTION_KEY . '[customer_notice]',
				'name'     => __( 'Customer notice', 'limit-orders' ),
				'desc_tip' => __( 'This message will appear on shop pages on the front-end of your site.', 'limit-orders' ),
				'type'     => 'text',
				'default'  => __( 'Due to increased demand, new orders will be temporarily suspended until {next_interval}.', 'limit-orders' ),
			],
			[
				'id'       => OrderLimiter::OPTION_KEY . '[order_button]',
				'name'     => __( '"Place Order" button', 'limit-orders' ),
				'desc_tip' => __( 'This text will replace the "Place Order" button on the checkout screen.', 'limit-orders' ),
				'type'     => 'text',
				'default'  => __( 'Ordering is temporarily disabled for this store.', 'limit-orders' ),
			],
			[
				'id'       => OrderLimiter::OPTION_KEY . '[checkout_error]',
				'name'     => __( 'Checkout error message', 'limit-orders' ),
				'desc_tip' => __( 'This error message will be displayed if a customer attempts to checkout once ordering is disabled.', 'limit-orders' ),
				'type'     => 'text',
				'default'  => __( 'Ordering is temporarily disabled for this store.', 'limit-orders' ),
			],
			[
				'id'   => 'limit-orders-messaging',
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
	protected function get_intervals(): array {
		global $wp_locale;

		$intervals = [
			'hourly'  => _x( 'Hourly (resets at the top of every hour)', 'order threshold interval', 'limit-orders' ),
			'daily'   => _x( 'Daily (resets every day)', 'order threshold interval', 'limit-orders' ),
			'weekly'  => sprintf(
				/* Translators: %1$s is the first day of the week, based on site configuration. */
				_x( 'Weekly (resets every %1$s)', 'order threshold interval', 'limit-orders' ),
				$wp_locale->get_weekday( get_option( 'start_of_week' ) )
			),
			'monthly' => _x( 'Monthly (resets on the first of the month)', 'order threshold interval', 'limit-orders' ),
		];

		/**
		 * Filter the available intervals.
		 *
		 * @param array $intervals Available time intervals.
		 */
		return apply_filters( 'limit_orders_interval_select', $intervals );
	}
}
