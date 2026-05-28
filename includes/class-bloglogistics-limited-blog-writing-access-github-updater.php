<?php
/**
 * GitHub updater for BlogLogistics Limited Blog Writing Access.
 *
 * @package BlogLogistics_Limited_Blog_Writing_Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( ! class_exists( 'BlogLogistics_Limited_Blog_Writing_Access_GitHub_Updater', false ) ) {

	final class BlogLogistics_Limited_Blog_Writing_Access_GitHub_Updater {

		/**
		 * Initialise GitHub-based plugin updates.
		 *
		 * @param array<string, string> $args Updater arguments.
		 */
		public static function init( array $args ): void {
			if (
				empty( $args['repo_url'] ) ||
				empty( $args['plugin_file'] ) ||
				empty( $args['slug'] )
			) {
				return;
			}

			if ( ! class_exists( PucFactory::class ) ) {
				return;
			}

			$update_checker = PucFactory::buildUpdateChecker(
				$args['repo_url'],
				$args['plugin_file'],
				$args['slug']
			);

			$update_checker->getVcsApi()->enableReleaseAssets( '/\.zip($|[?&#])/i' );
		}
	}
}
