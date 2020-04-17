# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
[#5]: https://github.com/nexcess/limit-orders/pull/5
[#6]: https://github.com/nexcess/limit-orders/pull/6
[#8]: https://github.com/nexcess/limit-orders/pull/8
[#10]: https://github.com/nexcess/limit-orders/pull/10
[#13]: https://github.com/nexcess/limit-orders/pull/13
