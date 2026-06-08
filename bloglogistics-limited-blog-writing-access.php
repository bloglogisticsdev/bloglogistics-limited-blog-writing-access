<?php
/**
 * Plugin Name:       BlogLogistics Limited Blog Writing Access
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access
 * Description:       Allows selected writing roles to create blog posts while preventing media access, uploads, publishing, and broader wp-admin access.
 * Version:           1.2.1
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            BlogLogistics
 * Author URI:        https://www.bloglogistics.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access
 * Text Domain:       bloglogistics-limited-blog-writing-access
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOGLOGISTICS_LBWA_VERSION', '1.2.1' );
define( 'BLOGLOGISTICS_LBWA_SLUG', 'bloglogistics-limited-blog-writing-access' );
define( 'BLOGLOGISTICS_LBWA_FILE', __FILE__ );
define( 'BLOGLOGISTICS_LBWA_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_LBWA_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access/' );
define( 'BLOGLOGISTICS_LBWA_UPDATE_MANIFEST_URL', 'https://updates.bloglogistics.com/plugins/bloglogistics-limited-blog-writing-access.json' );
define( 'BLOGLOGISTICS_LBWA_SETTINGS_OPTION', 'bloglogistics_lbwa_settings' );
define( 'BLOGLOGISTICS_LBWA_VERSION_OPTION', 'bloglogistics_lbwa_version' );

$bloglogistics_lbwa_puc = BLOGLOGISTICS_LBWA_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $bloglogistics_lbwa_puc ) ) {
	if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory', false ) ) {
		require_once $bloglogistics_lbwa_puc;
	}

	require_once BLOGLOGISTICS_LBWA_DIR . 'includes/class-bloglogistics-limited-blog-writing-access-updater.php';

	if ( class_exists( 'BlogLogistics_Limited_Blog_Writing_Access_Updater', false ) ) {
		BlogLogistics_Limited_Blog_Writing_Access_Updater::init(
			array(
				'repo_url'    => BLOGLOGISTICS_LBWA_UPDATE_MANIFEST_URL,
				'plugin_file' => BLOGLOGISTICS_LBWA_FILE,
				'slug'        => BLOGLOGISTICS_LBWA_SLUG,
			)
		);
	}
}

require_once BLOGLOGISTICS_LBWA_DIR . 'includes/class-bloglogistics-limited-blog-writing-access.php';

register_activation_hook( __FILE__, array( 'BlogLogistics_Limited_Blog_Writing_Access', 'activate' ) );

BlogLogistics_Limited_Blog_Writing_Access::init();
