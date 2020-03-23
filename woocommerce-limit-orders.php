<?php
/**
 * Plugin Name: WooCommerce Limit Orders
 * Description: Set a maximum number of orders that can be made in a given timeframe for a WooCommerce store.
 * Author:      Nexcess
 * Author URI:  https://nexcess.net
 * Text Domain: woocommerce-limit-orders
 * Domain Path: /languages
 * Version:     0.1.0
 *
 * @package     Nexcess\WooCommerceLimitOrders
 */

namespace Nexcess\WooCommerceLimitOrders;

/**
 * Register a PSR-4 autoloader.
 *
 * @param string $class The classname we're attempting to load.
 */
spl_autoload_register( function ( string $class ) {
	$filepath = str_replace( __NAMESPACE__ . '\\', '', $class );
	$filepath = __DIR__ . '/src/' . str_replace( '\\', '/', $filepath ) . '.php';

	if ( is_readable( $filepath ) ) {
		include_once $filepath;
	}
} );

// Initialize the plugin.
add_action( 'woocommerce_loaded', function () {
	$limiter = new OrderLimiter();
	$ui      = new UI( $limiter );

	// Initialize hooks.
	$limiter->init();
	$ui->init();

	// Turn off ordering if we've reached the defined limits.
	if ( $limiter->has_reached_limit() ) {
		$ui->disable_ordering();
	}
} );
