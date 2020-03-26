=== Limit Orders for WooCommerce ===
Contributors: nexcess, liquidweb, stevegrunwell
Tags: WooCommerce, ordering, limits, throttle
Requires at least: 5.3
Tested up to: 5.4
Requires PHP: 7.0
Stable tag: 0.1.0
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

== Screenshots ==

1. The settings screen for Limit Orders for WooCommerce
2. A notice at the top of a WooCommerce catalog with the message "Due to increased demand, new orders will be temporarily suspended until March 27, 2020."

== Changelog ==

For a complete list of changes, please [see the plugin's changelog on GitHub](https://github.com/nexcess/limit-orders/blob/master/CHANGELOG.md).

= 0.1.0 =
Initial release of the plugin.
