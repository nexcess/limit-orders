=== Limit Orders for WooCommerce ===
Contributors: nexcess, liquidweb, stevegrunwell, bswatson
Tags: WooCommerce, ordering, limits, throttle
Requires at least: 5.7
Tested up to: 6.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: MIT
License URI: https://github.com/nexcess/limit-orders/blob/master/LICENSE.txt

Automatically disable WooCommerce's checkout process after reaching a maximum number of orders.

== Description ==

While many stores would be thrilled to have a never-ending order queue, some store owners are faced with the opposite problem: how can I make sure I don't get overwhelmed by _too many_ orders?

Limit Orders for WooCommerce lets you limit the number of orders your store will accept per day, week, or month, while giving you full control over the messaging shown to your customers when orders are no longer being accepted. Once limiting is in effect, "Add to Cart" buttons and the checkout screens will automatically be disabled.

For full documentation on configuration options and available filters, please [visit the plugin's GitHub repository](https://github.com/nexcess/limit-orders).

== Installation ==

1. Upload the `limit-orders/` directory to `/wp-content/plugins/`
2. Activate the plugin through the "Plugins" menu in WordPress
3. Visit "WooCommerce &rsaquo; Settings &rsaquo; Order Limiting" to configure limits and messaging

== Frequently Asked Questions ==

= What happens when the order limit is reached? =

Once the maximum number of orders has been placed, Limit Orders for WooCommerce will use hooks within WooCommerce itself to temporarily mark all products as unpurchasable, remove the "Place Order" button, and disable the checkout form.

Meanwhile, a fully-customizable message will be displayed on all WooCommerce shop pages, informing customers that ordering has been paused.

= Can store owners still create orders manually? =

Yes, the order creation process through WP Admin is unaffected.

= Can the plugin limit orders based on some custom time interval? =

The base plugin defines several common intervals: hourly, daily, weekly, and monthly.

In the event that you require a custom interval, they may be registered with a few filters. [Several examples are available in the plugin's GitHub repository](https://github.com/nexcess/limit-orders#adding-custom-intervals).

= Can the plugin limit orders based on category/amount/items/etc.? =

The plugin is designed to work based on the total number of orders, but as of version 1.3.0 filters have been introduced that enable developers to specify which orders should be counted against the limit.

[Documentation for these filters is available in the plugin's GitHub repository](https://github.com/nexcess/limit-orders#customizing-plugin-behavior).

== Screenshots ==

1. The settings screen for Limit Orders for WooCommerce
2. A notice at the top of a WooCommerce catalog with the message "Due to increased demand, new orders will be temporarily suspended until March 27, 2020."

== Changelog ==

For a complete list of changes, please [see the plugin's changelog on GitHub](https://github.com/nexcess/limit-orders/blob/master/CHANGELOG.md).

= 2.0.0 (2022-11-14) =
* Verified compatibility with WooCommerce 7.1. ([#70])

= 1.3.1 (2020-09-17) =
* Fixed issue where clearing transients would prevent the order limiting from working.
* Clarify the behavior of the {current_interval} and {next_interval} placeholders.

= 1.3.0 (2020-07-16) =
* Added new "Reset order limiting" WooCommerce tool.
* Introduce new filters for customizing order counting logic.
* Automatically clear the cached order count when settings are updated or WooCommerce order transients are cleared.
* Verify compatibility with WooCommerce 4.3.

= 1.2.1 (2020-05-08) =
* Verify compatibility with WooCommerce 4.1.

= 1.2.0 (2020-04-27) =
* Add a new "hourly" interval, enabling store owners to limit the number of orders per hour.
* Added new placeholders for customer-facing messaging.

= 1.1.2 (2020-04-17) =
* Override WordPress' default "LIMIT" on queries, which was preventing stores with limits > 10 from stopping orders

= 1.1.1 (2020-04-16) =
* Prevent errors from occurring in WP Admin due to the customer-facing notice

= 1.1.0 (2020-04-15) =
* Include a "Limit Orders" section in the WooCommerce System Status Report

= 1.0.0 (2020-03-27) =
Initial release of the plugin.

== Upgrade Notice ==

= 1.3.1 =
Fixes issue where limiting would periodically stop working

= 1.2.0 =
Added the ability to limit the number of orders per hour a store can receive.

= 1.1.2 =
Fixes error that was preventing order limiting from working on stores with limits higher than 10.

= 1.1.1 =
Fixes errors in WP Admin after a store's order limit has been reached.
