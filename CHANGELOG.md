# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Version 1.3.1] — 2020-09-17

### Fixed

* Prevent the order count transient from being set before $wc_order_types is populated ([#52])

### Changed

* Move the limiter initialization from "woocommerce_loaded" to "init" ([#52])
* Change the format of the `{current_interval}` and `{next_interval}` placeholders if the interval is less than 24 hours ([#53])


## [Version 1.3.0] — 2020-07-16

### Added

* Add a new "Reset order limiting" tool to WooCommerce &rsaquo; Status &rsaquo; Tools ([#42])
* Introduce new filters — `limit_orders_pre_count_qualifying_orders` and `limit_orders_pre_get_remaining_orders` — for customizing the logic around counting qualifying and remaining orders, respectively ([#41])
* Automatically clear the cached order count when settings are changed or when WooCommerce order transients are cleared ([#37], [#42])

### Updated

* Bump "WC tested up to" to 4.3 ([#43]).

### Fixed

* Added missing plugin headers ([#32])


## [Version 1.2.1] — 2020-05-08

### Updated

* Bump "WC tested up to" to 4.1 ([#28]).


## [Version 1.2.0] — 2020-04-27

### Added

* Added "hourly" as a default interval for stores ([#20]).
* Added new placeholders to user-facing messaging ([#20], [#26]):
	- `{current_interval:date}` (alias of `{current_interval}`)
	- `{current_interval:time}`
	- `{next_interval:date}` (alias of `{next_interval}`)
	- `{next_interval:time}`
	- `{timezone}`
* Added documentation for adding custom intervals, placeholders ([#23]).

### Updated

* The settings screen will now show custom placeholders that have been registered via the "limit_orders_message_placeholders" filter ([#20]).
* Improve autoloader performance and remove type-hint from PSR-4 autoloader ([#17]).


## [Version 1.1.2] - 2020-04-17

### Fixed

* Override WordPress' default `LIMIT` on queries, which was preventing stores with limits > 10 from stopping orders ([#13]).


## [Version 1.1.1] — 2020-04-16

### Fixed

* Don't attempt to display customer-facing notices in WP Admin ([#10]).

### Updated

* Updated PHPUnit to 7.x for all but PHP 7.0


## [Version 1.1.0] — 2020-04-15

### Added

* Include a "Limit Orders" section in the WooCommerce System Status Report ([#8]).
* Add GitHub issue templates and contributing documentation ([#5], [#6]).


## [Version 1.0.0] — 2020-03-27

Initial plugin release.


[Unreleased]: https://github.com/nexcess/limit-orders/compare/master...develop
[Version 1.0.0]: https://github.com/nexcess/limit-orders/releases/tag/v1.0.0
[Version 1.1.0]: https://github.com/nexcess/limit-orders/releases/tag/v1.1.0
[Version 1.1.1]: https://github.com/nexcess/limit-orders/releases/tag/v1.1.1
[Version 1.1.2]: https://github.com/nexcess/limit-orders/releases/tag/v1.1.2
[Version 1.2.0]: https://github.com/nexcess/limit-orders/releases/tag/v1.2.0
[Version 1.2.1]: https://github.com/nexcess/limit-orders/releases/tag/v1.2.1
[Version 1.3.0]: https://github.com/nexcess/limit-orders/releases/tag/v1.3.0
[Version 1.3.1]: https://github.com/nexcess/limit-orders/releases/tag/v1.3.1
[#5]: https://github.com/nexcess/limit-orders/pull/5
[#6]: https://github.com/nexcess/limit-orders/pull/6
[#8]: https://github.com/nexcess/limit-orders/pull/8
[#10]: https://github.com/nexcess/limit-orders/pull/10
[#13]: https://github.com/nexcess/limit-orders/pull/13
[#17]: https://github.com/nexcess/limit-orders/pull/17
[#20]: https://github.com/nexcess/limit-orders/pull/20
[#23]: https://github.com/nexcess/limit-orders/pull/23
[#26]: https://github.com/nexcess/limit-orders/pull/26
[#28]: https://github.com/nexcess/limit-orders/issues/28
[#32]: https://github.com/nexcess/limit-orders/pull/32
[#37]: https://github.com/nexcess/limit-orders/pull/37
[#41]: https://github.com/nexcess/limit-orders/pull/41
[#42]: https://github.com/nexcess/limit-orders/pull/42
[#43]: https://github.com/nexcess/limit-orders/pull/43
[#52]: https://github.com/nexcess/limit-orders/pull/52
[#53]: https://github.com/nexcess/limit-orders/pull/53
