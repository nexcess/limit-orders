<?php
/**
 * Bootstrap the PHPUnit test suite(s).
 *
 * @package Nexcess\LimitOrders
 */

define( 'PROJECT_ROOT', dirname( __DIR__ ) );

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run WP_Filesystem_Mock.phpvendor/bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Finally, Start up the WP testing environment.
require_once $autoloader;
require_once $_tests_dir . '/includes/bootstrap.php';

// Load our helpers
require_once PROJECT_ROOT . '/tests/Support/WC_Helper_Product.php';
