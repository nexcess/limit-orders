# Contribution Guidelines

Thank you for your interest in contributing to [the Limit Orders for WooCommerce plugin](https://wordpress.org/plugins/limit-orders)!

## Getting started

To begin contributing, please clone this repository and install its dependencies via [Composer](https://getcomposer.org):

```sh
# Clone the repository
$ git clone git@github.com:nexcess/limit-orders.git

# Move into the newly-cloned repo
$ cd limit-orders

# Install Composer dependencies
$ composer install
```

## Versioning

This plugin adheres to [Semantic Versioning ("SemVer")](https://semver.org/spec/v2.0.0.html).

## Branching strategy

This plugin uses [the Gitflow branching model](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow):

* `develop` (the default branch) represents the latest development code, and should be the starting point for all new branches.
* `master` represents the current **stable** release of the plugin.
	- The only time anything changes in `master` is when we're tagging a new release.

When submitting changes, please fork the repository and create a new branch that contains your work. Ideally, the branch will follow the `{type}/{description}` pattern, where `{type}` is one of "fix", "feature", "docs", etc. and `{description}` is a brief description of the change. For example:

* `fix/transient-not-clearing`
* `feature/additional-settings`
* `docs/filter-reference`

When your branch is ready, open a pull request against the `develop` branch of the main repository.

### Preparing a new release

When we're ready to prepare a new release, a new `release/vX.X.X` branch should be made off of `develop`, and the following changes made in that branch:

* Bump version numbers (`limit-orders.php`, `readme.txt`)
* Update the `CHANGELOG.md` file with all new changes and the soon-to-be link to the new release.

The release branch should be submitted as a PR against `master`, and given the "release" tag. Once merged into `master`, [a new release should be created within GitHub](https://github.com/nexcess/limit-orders/releases/new) using the tag name of "vX.X.X". The creation of this tag will trigger [the WordPress.org Plugin Deploy GitHub action](https://github.com/marketplace/actions/wordpress-plugin-deploy) to run, pushing the release to WordPress.org.

## Coding standards

This plugin uses [the WordPress coding standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/) with a few modifications:

* Use file and class naming schemes consistent with [PSR-4 autoloading](https://www.php-fig.org/psr/psr-4/)
* Permit (and, in fact, _embrace_) PHP short-array syntax
* Allow multiple arguments on the same line, even when they span multiple lines.

Coding standards can be checked automatically at any time by running:

```sh
$ composer test:standards
```

## Unit tests

Tests for the plugin are written using the [WordPress core test framework](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/), and PHPUnit itself is installed as a Composer dependency.

```sh
$ composer test:unit
```

Please make sure to add/update tests accordingly when making changes.

## Static code analysis

This repository is pre-configured with [PHPStan](https://github.com/phpstan/phpstan) for static code analysis.

```sh
$ composer test:analysis
```
