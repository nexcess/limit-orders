<?php

/**
 * Tests that need to manage symlinks.
 */

namespace Tests\Concerns;

use Tests\Exceptions\MissingDependencyException;

trait ManagesSymlinks {

	/**
	 * @var string[] Symlinks that should only exist for the duration of a test.
	 */
	private static $testSymlinks = [];

	/**
	 * @var string[] Symlinks that should exist for the duration of a test class.
	 */
	private static $classSymlinks = [];

	/**
	 * Clean up per-test symlinks after each test.
	 *
	 * @after
	 */
	protected function cleanUpSymlinksAfterTest() {
		array_map( [ __CLASS__, 'removeSymlink' ], self::$testSymlinks );
	}

	/**
	 * Clean up per-test symlinks after each test.
	 *
	 * @afterClass
	 */
	public static function cleanUpSymlinksAfterTestClass() {
		array_map( [ __CLASS__, 'removeSymlink' ], self::$classSymlinks );
	}

	/**
	 * Create a symlink for dependencies.
	 *
	 * This method behaves like PHP's symlink() function, adding extra checks along the way.
	 *
	 * @param string $target   The absolute path to the target of the symlink.
	 * @param string $link     The absolute path to where the symlink should be created.
	 * @param string $duration Optional. How long the symlink should stick around. Accepted values
	 *                         are "test" (single test) or "class" (single test class).
	 *                         Default is "test".
	 *
	 * @throws MissingDependencyException when something prevents the symlink
	 *                                                      from being created.
	 *
	 * @return bool
	 */
	protected static function symlink( $target, $link, $duration = 'test' ) {
		// Does the target exist?
		if ( ! file_exists( $target ) ) {
			throw new MissingDependencyException(
				sprintf( 'File "%1$s" does not exist.', $target )
			);
		}

		// Does the link already exist?
		if ( is_link( $link ) ) {
			$readlink = readlink( $link );

			// It already points to what we wanted, consider it a success.
			if ( $target === $readlink ) {
				return true;
			}

			throw new MissingDependencyException(
				sprintf( 'Link already exists, but points to %1$s.', $readlink )
			);
		}

		$target_dir = dirname( $link );

		// Attempt to create the target directory if it isn't already there.
		if ( ! file_exists( $target_dir ) ) {
			if ( ! mkdir( $target_dir, 0777, true ) ) {
				throw new MissingDependencyException(
					sprintf( 'Directory "%1$s" cannot be created.', $target_dir )
				);
			}
		}

		// Is the target directory writable?
		if ( ! is_writable( $target_dir ) ) {
			throw new MissingDependencyException(
				sprintf( 'Directory "%1$s" is not writable.', $target_dir )
			);
		}

		$linked = symlink( $target, $link );

		if ( $linked ) {
			$var            = 'class' === $duration ? 'classSymlinks' : 'testSymlinks';
			self::${$var}[] = $link;
		}

		return $linked;
	}

	/**
	 * Delete the given symlink.
	 *
	 * This is a wrapper around unlink(), but does not throw an error if the file does not exist.
	 *
	 * Additionally, non-symlinks will not be removed.
	 *
	 * @param string $symlink
	 */
	protected static function removeSymlink( string $symlink ) {
		if ( is_link( $symlink ) ) {
			unlink( $symlink );
		}
	}
}
