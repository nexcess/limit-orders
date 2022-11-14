<?php
/**
 * Plugin Name:       Limit Orders for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/limit-orders/
 * Description:       Automatically disable WooCommerce's checkout process after reaching a maximum number of orders.
 * Version:           2.0.0
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * License:           MIT
 * License URI:       https://github.com/nexcess/limit-orders/blob/develop/LICENSE.txt
 * Author:            Nexcess
 * Author URI:        https://nexcess.net
 * Text Domain:       limit-orders
 * Domain Path:       /languages
 *
 * WC requires at least: 6.9
 * WC tested up to:      7.1
 *
 * @package Nexcess\LimitOrders
 */

namespace Nexcess\LimitOrders;

/**
 * Register a PSR-4 autoloader.
 *
 * @param string $class The classname we're attempting to load.
 */
spl_autoload_register( function ( $class ) {
	$namespace = __NAMESPACE__ . '\\';
	$class     = (string) $class;

	// Move onto the next registered autoloader if the class is outside of our namespace.
	if ( 0 !== strncmp( $namespace, $class, strlen( $namespace ) ) ) {
		return;
	}

	$filepath = str_replace( $namespace, '', $class );
	$filepath = __DIR__ . '/src/' . str_replace( '\\', '/', $filepath ) . '.php';

	if ( is_readable( $filepath ) ) {
		include_once $filepath;
	}
} );

// Initialize the plugin.
add_action( 'init', function () {

	// Abort if WooCommerce hasn't loaded.
	if ( ! did_action( 'woocommerce_loaded' ) ) {
		return;
	}

	$limiter = new OrderLimiter();
	$admin   = new Admin( $limiter );

	// Initialize hooks.
	$limiter->init();
	$admin->init();

	// Turn off ordering if we've reached the defined limits.
	if ( $limiter->has_reached_limit() ) {
		$limiter->disable_ordering();
	}
} );
