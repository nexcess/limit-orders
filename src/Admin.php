<?php
/**
 * Define the WP Admin integration.
 *
 * @package Nexcess\LimitOrders
 */

namespace Nexcess\LimitOrders;

class Admin {

	/**
	 * @var OrderLimiter
	 */
	protected $limiter;

	/**
	 * Create a new instance of the UI, built around the passed $limiter.
	 *
	 * @param OrderLimiter $limiter
	 */
	public function __construct( OrderLimiter $limiter ) {
		$this->limiter = $limiter;
	}

	/**
	 * Add the necessary hooks.
	 */
	public function init() {
		$basename = plugin_basename( dirname( __DIR__ ) . '/limit-orders.php' );

		add_action( 'admin_notices', [ $this, 'admin_notice' ] );

		add_filter( 'woocommerce_get_settings_pages', [ $this, 'register_settings_page' ] );
		add_filter( sprintf( 'plugin_action_links_%s', $basename ), [ $this, 'action_links' ] );
		add_filter( 'woocommerce_debug_tools', [ $this, 'debug_tools' ] );
		add_action( 'woocommerce_delete_shop_order_transients', [ $this, 'reset_limiter' ] );
		add_action( 'woocommerce_system_status_report', [ $this, 'system_status_report' ] );
	}

	/**
	 * Inject a "Settings" link to the plugin's action links.
	 *
	 * @param array $actions A link of available actions.
	 */
	public function action_links( array $actions ): array {
		array_unshift( $actions, sprintf(
			'<a href="%s">%s</a>',
			$this->get_settings_url(),
			_x( 'Settings', 'plugin action link', 'limit-orders' )
		) );

		return $actions;
	}

	/**
	 * Register our custom WooCommerce Admin settings page.
	 *
	 * @param array $pages Registered settings pages.
	 *
	 * @return array The filtered $pages array.
	 */
	public function register_settings_page( array $pages ): array {
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

		// Change what text we show based on how far off it is.
		$next_interval = $this->limiter->get_next_interval_start();
		$midnight      = current_datetime()->setTime( 24, 0, 0 );

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( $next_interval == $midnight ) {
			$next = _x( 'midnight', 'beginning of the next day/interval', 'limit-orders' );
		} elseif ( $next_interval < $midnight ) {
			$next = $next_interval->format( get_option( 'time_format' ) );
		} else {
			$next = $next_interval->format( get_option( 'date_format' ) );
		}

		echo '<div class="notice notice-warning"><p>';

		if ( current_user_can( 'manage_options' ) ) {
			echo wp_kses_post( sprintf(
				/* Translators: %1$s is the settings page URL, %2$s is the reset date for order limiting. */
				__( '<a href="%1$s">Based on your store\'s configuration</a>, new orders have been put on hold until %2$s.', 'limit-orders' ),
				$this->get_settings_url(),
				$next
			) );
		} else {
			echo esc_html( sprintf(
				/* Translators: %1$s is the reset date for order limiting. */
				__( 'Based on your store\'s configuration, new orders have been put on hold until %1$s.', 'limit-orders' ),
				$next
			) );
		}
		echo '</p></div>';
	}

	/**
	 * Render the system status report.
	 */
	public function system_status_report() {
		$this->render_view( 'SystemStatusReport', [
			'limiter' => $this->limiter,
		] );
	}

	/**
	 * Add additional debugging tools.
	 *
	 * @param array $tools Currently-registered tools.
	 *
	 * @return array The $tools array with our button included.
	 */
	public function debug_tools( array $tools ): array {
		$tools['limit_orders'] = [
			'name'     => __( 'Reset order limiting', 'limit-orders' ),
			'button'   => __( 'Reset limiter', 'limit-orders' ),
			'desc'     => __( 'Clear the cached order count. This may be needed if you\'ve changed your order limiting settings.', 'limit-orders' ),
			'callback' => [ $this, 'reset_limiter' ],
		];

		return $tools;
	}

	/**
	 * Delete the order limiter transient.
	 */
	public function reset_limiter() {
		$this->limiter->reset();
	}

	/**
	 * Retrieve a link to the plugin's settings page.
	 *
	 * @return string An absolute URL to the settings page.
	 */
	protected function get_settings_url(): string {
		return admin_url( 'admin.php?page=wc-settings&tab=limit-orders' );
	}

	/**
	 * Render a view from within the Views/ directory.
	 *
	 * @param string $view The view name.
	 * @param array $vars Variables that should be exposed to the view.
	 */
	protected function render_view( string $view, array $vars = [] ) {
		$template = sprintf( '%1$s/Views/%2$s.php', untrailingslashit( __DIR__ ), $view );

		// We don't have enough of these yet to justify needing heavy error handling.
		if ( ! file_exists( $template ) ) {
			return;
		}

		// Extract the $vars so they're available within the template.
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		// Finally, include the template.
		include $template;
	}
}
