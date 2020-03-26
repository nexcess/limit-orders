<?php
/**
 * Define the WP Admin integration.
 *
 * @package Nexcess\LimitOrders
 */

namespace Nexcess\LimitOrders;

class Admin {

	/**
	 * @var \Nexcess\LimitOrders\OrderLimiter
	 */
	protected $limiter;

	/**
	 * Create a new instance of the UI, built around the passed $limiter.
	 *
	 * @param \Nexcess\LimitOrders\OrderLimiter $limiter
	 */
	public function __construct( OrderLimiter $limiter ) {
		$this->limiter = $limiter;
	}

	/**
	 * Add the necessary hooks.
	 */
	public function init() {
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'register_settings_page' ] );
		add_filter( 'admin_notices', [ $this, 'admin_notice' ] );
	}

	/**
	 * Register our custom WooCommerce Admin settings page.
	 *
	 * @param array $pages Registered settings pages.
	 *
	 * @return array The filtered $pages array.
	 */
	public function register_settings_page( $pages ) {
		$pages[] = new Settings( $this->limiter );

		return $pages;
	}

	/**
	 * Display an admin notice when ordering is disabled.
	 */
	public function admin_notice() {
		if ( ! $this->limiter->has_reached_limit() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';

		if ( current_user_can( 'manage_options' ) ) {
			echo wp_kses_post( sprintf(
				/* Translators: %1$s is the settings page URL, %2$s is the reset date for order limiting. */
				__( '<a href="%1$s">Based on your store\'s configuration</a>, new orders have been put on hold until %2$s.', 'limit-orders' ),
				admin_url( 'admin.php?page=wc-settings&tab=limit-orders' ),
				$this->limiter->get_next_interval_start()->format( get_option( 'date_format' ) )
			) );
		} else {
			echo esc_html( sprintf(
				/* Translators: %1$s is the reset date for order limiting. */
				__( 'Based on your store\'s configuration, new orders have been put on hold until %1$s.', 'limit-orders' ),
				$this->limiter->get_next_interval_start()->format( get_option( 'date_format' ) )
			) );
		}
		echo '</p></div>';
	}
}
