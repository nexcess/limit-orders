<?php
/**
 * Define the UI for configuring order limits.
 *
 * @package Nexcess\WooCommerceLimitOrders
 */

namespace Nexcess\WooCommerceLimitOrders;

class UI {

	/**
	 * @var \Nexcess\WooCommerceLimitOrders\OrderLimiter
	 */
	protected $limiter;

	/**
	 * Create a new instance of the UI, built around the passed $limiter.
	 *
	 * @param \Nexcess\WooCommerceLimitOrders\OrderLimiter $limiter
	 */
	public function __construct( OrderLimiter $limiter ) {
		$this->limiter = $limiter;
	}

	/**
	 * Add the necessary hooks.
	 */
	public function init() {
		add_filter( 'woocommerce_get_settings_general', [ $this, 'get_settings' ] );
		add_filter( 'admin_notices', [ $this, 'admin_notice' ] );
	}

	/**
	 * Append our settings section to WooCommerce > Settings > General.
	 *
	 * @param array $settings The general settings section.
	 *
	 * @return array The filtered array, including our settings.
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
				'id'   => 'woocommerce-limit-orders',
				'type' => 'sectionend',
			],
		] );
	}

	/**
	 * Display an admin notice when ordering is disabled.
	 *
	 * @todo Get an actual value for $restart.
	 */
	public function admin_notice() {
		if ( ! $this->limiter->has_reached_limit() ) {
			return;
		}

		$restart = 'reactivated';

		echo '<div class="notice notice-warning"><p>';

		if ( current_user_can( 'manage_options' ) ) {
			echo wp_kses_post( sprintf(
				/* Translators: %1$s is the settings page URL, %2$s is the reset date for order limiting. */
				__( '<a href="%1$s">Based on your store\'s configuration</a>, new orders have been put on hold until %2%s.', 'woocommerce-limit-orders' ),
				admin_url( 'admin.php?page=wc-settings&tab=general' ),
				$restart
			) );
		} else {
			echo esc_html( sprintf(
				/* Translators: %1$s is the reset date for order limiting. */
				__( 'Based on your store\'s configuration, new orders have been put on hold until %1%s.', 'woocommerce-limit-orders' ),
				$restart
			) );
		}
		echo '</p></div>';
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
		return apply_filters( 'woocommerce_limit_orders_intervals', $intervals );
	}
}
