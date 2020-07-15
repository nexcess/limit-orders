# Limit Orders for WooCommerce

[![Build Status](https://travis-ci.org/nexcess/limit-orders.svg?branch=develop)](https://travis-ci.org/nexcess/limit-orders)
[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/limit-orders)](https://wordpress.org/plugins/limit-orders)

While many stores would be thrilled to have a never-ending order queue, some store owners are faced with the opposite problem: how can I make sure I don't get overwhelmed by _too many_ orders?

Limit Orders for WooCommerce lets you limit the number of orders your store will accept per day, week, or month, while giving you full control over the messaging shown to your customers when orders are no longer being accepted. Once limiting is in effect, "Add to Cart" buttons and the checkout screens will automatically be disabled.

![A notice at the top of a WooCommerce catalog with the message "Due to increased demand, new orders will be temporarily suspended until March 27, 2020."](.wordpress-org/screenshot-2.png)

## Requirements

* WordPress 5.3 or newer
* WooCommerce 3.9 or newer
* PHP 7.0 or newer

## Installation

1. [Download and extract a .zip archive of the plugin](https://github.com/nexcess/limit-orders/archive/master.zip) (or clone this repository) into your site's plugins directory (`wp-content/plugins/` by default).
2. Activate the plugin through the Plugins screen in WP Admin.
3. [Configure the plugin](#Configuration).

## Configuration

Configuration for Limit Orders for WooCommerce is available through WooCommerce &rsaquo; Settings &rsaquo; Order Limiting:

![The settings screen for Limit Orders for WooCommerce](.wordpress-org/screenshot-1.png)

⚠️ **Please be aware** that any changes made to the settings will take effect immediately.

For example, if you're using an hourly interval and switch it to daily, the plugin will re-calculate whether or not to disable ordering based on the number of orders received since the start of the current day (midnight, by default).

### General settings

These settings determine how and when order limiting should take effect.

<dl>
	<dt>Enable Order Limiting</dt>
	<dd>Check this box to enable order limiting.</dd>
	<dd>Should you ever want to disable the limiting temporarily, simply uncheck this box.</dd>
	<dt>Maximum # of orders</dt>
	<dd>Customers will be unable to checkout after this number of orders are received.</dd>
	<dd>Shop owners will still be able to create orders via WP Admin, even after the limit has been reached.</dd>
	<dt>Interval</dt>
	<dd>How often the limit is reset. By default, this can be "hourly", daily", "weekly", or "monthly".</dd>
	<dd>When choosing "weekly", the plugin will respect the value of <a href="https://wordpress.org/support/article/settings-general-screen/#week-starts-on">the store's "week starts on" setting</a>.</dd>
</dl>

### Messaging

Limit Orders for WooCommerce lets you customize the messages shown on the front-end of your store:

<dl>
	<dt>Customer notice</dt>
	<dd>This notice will be added to all WooCommerce pages on the front-end of your store once the limit has been reached.</dd>
	<dt>"Place Order" button</dt>
	<dd>If a customer happens to visit the checkout screen after the order limit has been reached, this message will replace the "Place Order" button.</dd>
	<dt>Checkout error message</dt>
	<dd>If a customer submits an order after the order limits have been reached, this text will be used in the resulting error message.</dd>
</dl>

#### Variables

In any of these messages, you may also use the following variables:

<dl>
	<dt>{limit}</dt>
	<dd>The maximum number of orders accepted.</dd>
	<dt>{current_interval}</dt>
	<dd>The date the current interval started.</dd>
	<dt>{current_interval:date}</dt>
	<dd>An alias of <var>{current_interval}</var></dd>
	<dt>{current_interval:time}</dt>
	<dd>The time the current interval started.</dd>
	<dt>{next_interval}</dt>
	<dd>The date the next interval will begin (e.g. when orders will be accepted again).</dd>
	<dt>{next_interval:date}</dt>
	<dd>An alias of <var>{next_interval}</var></dd>
	<dt>{next_interval:time}</dt>
	<dd>The time the next interval will begin.</dd>
	<dt>{timezone}</dt>
	<dd>The store's timezone, e.g. "PST", "EDT", etc. This will automatically update based on Daylight Saving Time.</dd>
</dl>

Dates and times will be formatted [according to the "date format" and "time format" settings for your store](https://wordpress.org/support/article/settings-general-screen/#date-format), respectively.

If you would like to add custom placeholders, see [Adding Custom Placeholders](#adding-custom-placeholders) below.

## Customizing plugin behavior

Limit Orders for WooCommerce includes [a number of actions and filters](https://codex.wordpress.org/Plugin_API) that enable store owners to modify the plugin's behavior.

Examples of common customizations are included below.

### Adding custom intervals

The plugin includes a few intervals by default:

1. Hourly (resets at the top of every hour)
1. Daily (resets every day)
1. Weekly (resets every week, respecting the store's "Week Starts On" setting)
1. Monthly (resets on the first of the month)

If your store needs a custom interval, you may add them using filters built into the plugin.

You may also use these gists, which define custom plugins that can be run alongside Limit Orders:

* [Limit Orders for WooCommerce - 15min Intervals](https://gist.github.com/stevegrunwell/ab8a7a2036f993c3c09c6504acda96eb)
* [Limit Orders for WooCommerce - Never-ending Interval](https://gist.github.com/stevegrunwell/f9cc978cd5c23f744142798b7b772ba6)

#### Example: Reset Limits Annually

Let's say your store can only accept a certain number of orders in a year.

You may accomplish this by adding the following code into your theme's `functions.php` file or (preferably) by saving it as a custom plugin:

```php
<?php
/**
 * Plugin Name: Limit Orders for WooCommerce - Annual Intervals
 * Description: Add a "Annually" option to Limit Orders for WooCommerce.
 * Author:      Nexcess
 * Author URI:  https://nexcess.net
 */

/**
 * Add "Annually" to the list of intervals.
 *
 * @param array $intervals Available time intervals.
 *
 * @return array The filtered array of intervals.
 */
add_filter( 'limit_orders_interval_select', function ( $intervals ) {
	// Return early if it already exists.
	if ( isset( $intervals['annually'] ) ) {
		return $intervals;
	}

	$intervals['annually'] = __( 'Annually (Resets on the first of the year)', 'limit-orders' );

	return $intervals;
} );

/**
 * Get a DateTime object representing the beginning of the current year.
 *
 * @param \DateTime $start    The DateTime representing the start of the current interval.
 * @param string    $interval The type of interval being calculated.
 *
 * @return \DateTime A DateTime object representing the top of the current hour or $start, if the
 *                   current $interval is not "annually".
 */
add_filter( 'limit_orders_interval_start', function ( $start, $interval ) {
	if ( 'annually' !== $interval ) {
		return $start;
	}

	// Happy New Year!
	return ( new \DateTime( 'now' ) )
		->setDate( (int) $start->format( 'Y' ), 1, 1 )
		->setTime( 0, 0, 0 );
}, 10, 2 );

/**
 * Filter the DateTime at which the next interval should begin.
 *
 * @param \DateTime $start    A DateTime representing the start time for the next interval.
 * @param \DateTime $current  A DateTime representing the beginning of the current interval.
 * @param string    $interval The specified interval.
 *
 * @return \DateTime The DateTime at which the next interval should begin, or $start if the
 *                   current $interval is not "annually".
 */
add_filter( 'limit_orders_next_interval', function ( $start, $current, $interval ) {
	if ( 'annually' !== $interval ) {
		return $start;
	}

	return $current->add( new \DateInterval( 'P1Y' ) );
}, 10, 3 );
```

### Adding Custom Placeholders

The placeholders used for customer-facing messaging are editable via the `limit_orders_message_placeholders` filter.

For example, imagine we wanted to add a placeholder for the WooCommerce store name. The code to accomplish this may look like:

```php
/**
 * Append a {store_name} placeholder.
 *
 * @param array $placeholders The currently-defined placeholders.
 *
 * @return array The filtered array of placeholders.
 */
add_filter( 'limit_orders_message_placeholders', function ( $placeholders ) {
	$placeholders['{store_name}'] = get_option( 'blogname' );

	return $placeholders;
} );
```

Now, we can create customer-facing notices like:

> {store_name} is a little overwhelmed right now, but we'll be able to take more orders on {next_interval:date}. Please check back then!

### Dynamically changing limiter behavior

In certain cases, you may want to further customize the logic around _which_ orders count toward the limit or, for example, change the behavior based on time of day. Limit Orders for WooCommerce has you covered:

#### Customize the counting of qualified orders

Sometimes, you only want to limit certain types of orders. Maybe some orders are fulfilled via third parties (e.g. [dropshipping](https://www.liquidweb.com/woocommerce-resource/dropshipping-glossary/)), or perhaps you're willing to bend the limits a bit for orders that contain certain products.

You can customize the logic used to calculate the count via the `limit_orders_pre_count_qualifying_orders` filter:

```php
/**
 * Determine how many orders to count against the current interval.
 *
 * @param bool $preempt         Whether the counting logic should be preempted. Returning
 *                              anything but FALSE will bypass the default logic.
 * @param OrderLimiter $limiter The current OrderLimiter instance.
 *
 * @return int The number of orders that should be counted against the limit.
 */
add_filter( 'limit_orders_pre_count_qualifying_orders', function ( $preempt, $limiter ) {
	/*
	 * Do whatever you need to do here to count how many orders count.
	 *
	 * Pay close attention to date ranges here, and check out the public methods
	 * on the Nexcess\LimitOrders\OrderLimiter class.
	 */
}, 10, 2 );
```

Please note that the `LimitOrders::count_qualifying_orders()` method (where this filter is defined) is only called in two situations:

1. When a new order is created.
2. If the `limit_orders_order_count` transient disappears.

#### Dynamically change the order limit

If, for example, you want to automatically turn off the store overnight, you might do so by setting the limit to `0` only during certain hours.

You can accomplish this using the `limit_orders_pre_get_remaining_orders` filter:

```php
/**
 * Disable the store between 10pm and 8am.
 *
 * This works by setting the limit on Limit Orders for WooCommerce to zero if
 * the current time is between those hours.
 *
 * @param bool $preempt Whether or not the default logic should be preempted.
 *                      Returning anything besides FALSE will be treated as the
 *                      number of remaining orders that can be accepted.
 *
 * @return int|bool Either 0 if the store is closed (meaning zero orders remaining)
 *                  or the value of $preempt if Limit Orders should proceed normally.
 */
add_filter( 'limit_orders_pre_get_remaining_orders', function ( $preempt ) {
	$open  = new \DateTime('08:00', wp_timezone());
	$close = new \DateTime('22:00', wp_timezone());
	$now   = current_datetime();

	// We're currently inside normal business hours.
	if ( $now >= $open && $now < $close ) {
		return $preempt;
	}

	// If we've gotten this far, turn off ordering.
	return 0;
} );
```
