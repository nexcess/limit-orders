<?php

/**
 * Enable symlinking of desired dependencies into place in the WordPress test environment.
 */

namespace Tests\Concerns;

use Tests\Exceptions\DependencyActivationException;
use Tests\Exceptions\MissingDependencyException;

trait InstallsDependencies {
	use ManagesSymlinks;

	/**
	 * Plugins that should be installed.
	 *
	 * @var string[]
	 */
	protected static $plugins = [];

	/**
	 * A cache of plugins that have already caused activation issues.
	 *
	 * @var string[]
	 */
	protected static $skippedPlugins = [];

	/**
	 * Plugins that have been activated via activatePlugin().
	 *
	 * @var array
	 */
	private $activatedPlugins = [];

	/**
	 * Cached plugin data from WordPress' get_plugins() function.
	 *
	 * @var array
	 */
	private $pluginData;

	/**
	 * Install the plugins into the test environment.
	 *
	 * Plugins are "installed" by symlinking the Composer-installed versions into the WordPress
	 * test environment.
	 *
	 * @beforeClass
	 */
	public static function installPluginsBeforeTestClass() {
		$plugin_root   = PROJECT_ROOT . '/vendor/';
		$wp_plugin_dir = WP_CONTENT_DIR . '/plugins/';

		foreach ( static::$plugins as $plugin ) {
			$target = $plugin_root . $plugin;
			$link   = $wp_plugin_dir . basename( $plugin );

			try {
				if ( ! self::symlink( $target, $link, 'class' ) ) {
					throw new MissingDependencyException( 'PHP symlink() call failed.' );
				}
			} catch ( MissingDependencyException $e ) {
				throw new MissingDependencyException( sprintf(
					'Unable to symlink %1$s to %2$s: %3$s',
					$link,
					$target,
					$e->getMessage()
				), $e->getCode(), $e );
			}
		}
	}

	/**
	 * Reset the $activatedPlugins array between tests.
	 *
	 * @beforeClass
	 */
	protected function resetActivatedPluginsBeforeTestClass() {
		$this->activatedPlugins = [];
	}

	/**
	 * Deactivate any plugins that were activated as part of a test.
	 *
	 * @afterClass
	 */
	protected function deactivatePluginsAfterTestClass() {
		if ( ! empty( $this->activatedPlugins ) ) {
			deactivate_plugins( $this->activatedPlugins );
		}
	}

	/**
	 * Retrieve and cache WordPress' get_plugins() call.
	 *
	 * @return array
	 */
	protected function getPlugins(): array {
		if ( null === $this->pluginData ) {
			wp_cache_flush();
			$this->pluginData = get_plugins();
		}

		return $this->pluginData;
	}

	/**
	 * Activate a plugin within WordPress by the directory slug.
	 *
	 * @param string $plugin The plugin to activate.
	 * @param bool $silent Optional. Whether to silently activate, thereby skipping
	 *                       activation hooks. Default is false.
	 *
	 * @throws DependencyActivationException if unable to activate the plugin.
	 */
	protected function activatePlugin( string $plugin, bool $silent = false ) {
		// If we already know this will fail, skip early.
		if ( isset( self::$skippedPlugins[ $plugin ] ) ) {
			$this->markTestSkipped( self::$skippedPlugins[ $plugin ] );
		}

		if ( 'woocommerce' === $plugin ) {
			/*
			 * Delete the wc_installing transient or risk tables not being created.
			 *
			 * @see WC_Install::install()
			 */
			delete_transient( 'wc_installing' );

			/*
			 * WooCommerce uses a Singleton (`WooCommerce::$_instance), which needs to be reset
			 * between tests or WooCommerce won't be fully loaded on subsequent activations.
			 *
			 * Additionally, since `activate_plugin()` uses `include_once`, we need to explicitly
			 * call the `WC()` function in order to bootstrap WooCommerce.
			 */
			add_action( 'activate_woocommerce/woocommerce.php', function () {
				$prop = new \ReflectionProperty( 'WooCommerce', '_instance' );
				$prop->setAccessible( true );
				$prop->setValue( null, null );

				if ( class_exists( 'Automattic\WooCommerce\Packages' ) ) {
					\Automattic\WooCommerce\Packages::init();
				}

				if ( class_exists( '\Automattic\WooCommerce\Container' ) ) {
					$GLOBALS['wc_container'] = new \Automattic\WooCommerce\Container();
				}

				WC();
			}, 1 );

			/*
			 * Don't make an HTTP request to /wp-content/uploads/woocommerce_uploads/ to see if
			 * it can list directory contents.
			 *
			 * @see WC_Admin_Notices::is_uploads_directory_protected()
			 */
			add_filter( 'pre_transient__woocommerce_upload_directory_status', function () {
				return 'protected';
			} );

			/**
			 * The WordPress core test suite uses MySQL temporary tables, but WooCommerce's
			 * `WC_Install::create_tables()` method tries to perform an "ALTER TABLE" query to add
			 * a foreign key, which is not supported by temporary tables.
			 *
			 * In the test suite, we'll simply replace this query with something innocuous.
			 *
			 * @see WC_Install::create_tables()
			 * @link https://dev.mysql.com/doc/refman/8.0/en/create-table-foreign-keys.html#foreign-key-restrictions
			 */
			add_filter( 'query', function ( $query ) {
				global $wp_version, $wpdb;

				// The ALTER TABLE query from create_tables().
				$search = "ALTER TABLE `{$wpdb->prefix}wc_download_log`
					ADD CONSTRAINT `fk_{$wpdb->prefix}wc_download_log_permission_id`
					FOREIGN KEY (`permission_id`)
					REFERENCES `{$wpdb->prefix}woocommerce_downloadable_product_permissions` (`permission_id`) ON DELETE CASCADE;";

				if ( $query !== $search ) {
					return $query;
				}

				/*
				 * WordPress 5.6+ will simply return false if given an empty query. For older
				 * releases, we'll use something quick and non-destructive.
				 */
				return version_compare( $wp_version, '5.6', '>=' )
					? ''
					: 'SELECT DATABASE();';
			} );
		}

		$plugin_file = $this->getPluginFile( $plugin );
		$activated   = activate_plugin( $plugin_file, '', false, $silent );

		// Unexpected plugin output detected.
		if ( is_wp_error( $activated ) ) {
			switch ( $activated->get_error_code() ) {
				case 'plugin_wp_incompatible':
					self::$skippedPlugins[ $plugin ] = $activated->get_error_message();
					return $this->markTestSkipped( $activated->get_error_message() );
				case 'unexpected_output':
					$message = sprintf(
						"Unable to activate %1\$s due to unexpected output:\n%2\$s",
						$plugin,
						$activated->get_error_data( 'unexpected_output' )
					);
					break;
				default:
					$message = sprintf(
						"Unable to activate %1\$s:\n%2\$s",
						$plugin,
						strip_tags( $activated->get_error_message() )
					);
			}

			self::$skippedPlugins[ $plugin ] = sprintf(
				'Skipping activation of %1$s due to previous activation error: %2$s',
				$plugin,
				$message
			);

			throw new DependencyActivationException( $message );
		}

		// Track the plugin, so we can deactivate it at the end of the test.
		$this->activatedPlugins[] = $plugin_file;
	}

	/**
	 * Deactivate a plugin within WordPress by the directory slug.
	 *
	 * @param string $plugin The plugin to deactivate.
	 */
	protected function deactivatePlugin( string $plugin ) {
		$file = $this->getPluginFile( $plugin );

		if ( ! is_plugin_active( $file ) ) {
			throw new MissingDependencyException( sprintf(
				'Plugin %1$s (%2$s) cannot be deactivated, as it is not currently active.',
				$plugin,
				$file
			) );
		}

		deactivate_plugins( $this->getPluginFile( $plugin ) );
	}

	/**
	 * Retrieve the plugin file for a given slug.
	 *
	 * For example, calling the method on "jetpack" should return "jetpack/jetpack.php" if Jetpack
	 * is installed on the site.
	 *
	 * @param string $plugin The plugin slug.
	 *
	 * @throws MissingDependencyException if no matching dependency was found.
	 *
	 * @return string The plugin filename.
	 */
	protected function getPluginFile( string $plugin ): string {
		$plugins = array_filter( $this->getPlugins(), function ( $file ) use ( $plugin ) {
			return 0 === strpos( $file, $plugin . '/' );
		}, ARRAY_FILTER_USE_KEY );

		if ( empty( $plugins ) ) {
			throw new MissingDependencyException( sprintf(
				'Unable to find a plugin matching "%s" to activate.',
				$plugin
			) );
		}

		return key( $plugins );
	}
}
