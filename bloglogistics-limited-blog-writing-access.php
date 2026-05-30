<?php
/**
 * Plugin Name:       BlogLogistics Limited Blog Writing Access
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access
 * Description:       Allows selected writing roles to create blog posts while preventing media access, uploads, publishing, and broader wp-admin access.
 * Version:           1.1.3
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            BlogLogistics
 * Author URI:        https://www.bloglogistics.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access
 * Text Domain:       bloglogistics-limited-blog-writing-access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOGLOGISTICS_LBWA_VERSION', '1.1.3' );
define( 'BLOGLOGISTICS_LBWA_SLUG', 'bloglogistics-limited-blog-writing-access' );
define( 'BLOGLOGISTICS_LBWA_FILE', __FILE__ );
define( 'BLOGLOGISTICS_LBWA_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_LBWA_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access/' );
define( 'BLOGLOGISTICS_LBWA_UPDATE_MANIFEST_URL', 'https://updates.bloglogistics.com/plugins/bloglogistics-limited-blog-writing-access.json' );
define( 'BLOGLOGISTICS_LBWA_SETTINGS_OPTION', 'bloglogistics_lbwa_settings' );
define( 'BLOGLOGISTICS_LBWA_VERSION_OPTION', 'bloglogistics_lbwa_version' );

$bloglogistics_lbwa_puc = BLOGLOGISTICS_LBWA_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $bloglogistics_lbwa_puc ) ) {
	if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory', false ) ) {
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

/**
 * Get recommended default settings.
 *
 * @return array<string, bool>
 */
function bloglogistics_lbwa_default_settings(): array {
	return array(
		'limit_admin_access' => true,
		'limit_media_tools'    => true,
		'limit_comment_tools'  => true,
	);
}

/**
 * Get saved settings merged with defaults.
 *
 * @return array<string, bool>
 */
function bloglogistics_lbwa_get_settings(): array {
	$saved = get_option( BLOGLOGISTICS_LBWA_SETTINGS_OPTION, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return array_merge( bloglogistics_lbwa_default_settings(), array_map( 'boolval', $saved ) );
}

/**
 * Check whether a specific setting is enabled.
 */
function bloglogistics_lbwa_setting_enabled( string $key ): bool {
	$settings = bloglogistics_lbwa_get_settings();
	return ! empty( $settings[ $key ] );
}

/**
 * Add default settings and keep existing behaviour active on first install.
 */
function bloglogistics_lbwa_activate(): void {
	if ( false === get_option( BLOGLOGISTICS_LBWA_SETTINGS_OPTION, false ) ) {
		add_option( BLOGLOGISTICS_LBWA_SETTINGS_OPTION, bloglogistics_lbwa_default_settings(), '', false );
	}

	update_option( BLOGLOGISTICS_LBWA_VERSION_OPTION, BLOGLOGISTICS_LBWA_VERSION, false );

	if ( bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) || bloglogistics_lbwa_setting_enabled( 'limit_comment_tools' ) ) {
		bloglogistics_lbwa_apply_limited_writer_capabilities();
	}
}
register_activation_hook( __FILE__, 'bloglogistics_lbwa_activate' );

/**
 * Ensure settings exist after updates from older versions.
 */
function bloglogistics_lbwa_maybe_upgrade(): void {
	if ( false === get_option( BLOGLOGISTICS_LBWA_SETTINGS_OPTION, false ) ) {
		add_option( BLOGLOGISTICS_LBWA_SETTINGS_OPTION, bloglogistics_lbwa_default_settings(), '', false );
	}

	$stored_version = (string) get_option( BLOGLOGISTICS_LBWA_VERSION_OPTION, '' );

	if ( BLOGLOGISTICS_LBWA_VERSION !== $stored_version ) {
		update_option( BLOGLOGISTICS_LBWA_VERSION_OPTION, BLOGLOGISTICS_LBWA_VERSION, false );
	}
}
add_action( 'plugins_loaded', 'bloglogistics_lbwa_maybe_upgrade' );

/**
 * Roles that are allowed to access wp-admin for writing posts.
 *
 * @return string[]
 */
function bloglogistics_lbwa_limited_writer_roles(): array {
	return array( 'editor', 'author', 'contributor' );
}

/**
 * Check whether the current user is one of the approved writing roles.
 */
function bloglogistics_lbwa_is_limited_writer(): bool {
	$user = wp_get_current_user();

	if ( empty( $user->roles ) ) {
		return false;
	}

	return (bool) array_intersect( bloglogistics_lbwa_limited_writer_roles(), (array) $user->roles );
}

/**
 * Hide the admin bar for everyone except administrators when enabled.
 */
function bloglogistics_lbwa_disable_admin_bar_for_non_admins(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_admin_access' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}
}
add_action( 'after_setup_theme', 'bloglogistics_lbwa_disable_admin_bar_for_non_admins' );

/**
 * Allow admins full wp-admin access, allow limited writers to write posts,
 * and redirect everyone else away from wp-admin when enabled.
 */
function bloglogistics_lbwa_restrict_wp_admin_access(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_admin_access' ) ) {
		return;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( bloglogistics_lbwa_is_limited_writer() && current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_safe_redirect( home_url() );
	exit;
}
add_action( 'admin_init', 'bloglogistics_lbwa_restrict_wp_admin_access' );

/**
 * Remove upload, media, and publishing capabilities from Editors, Authors,
 * and Contributors.
 */
function bloglogistics_lbwa_apply_limited_writer_capabilities(): void {
	bloglogistics_lbwa_restore_builtin_writer_capabilities();

	$caps_to_remove = array();

	if ( bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		$caps_to_remove = array_merge(
			$caps_to_remove,
			array(
				'upload_files',
				'publish_posts',
				'publish_pages',
			)
		);
	}

	if ( bloglogistics_lbwa_setting_enabled( 'limit_comment_tools' ) ) {
		$caps_to_remove[] = 'moderate_comments';
	}

	$caps_to_remove = array_unique( $caps_to_remove );

	foreach ( bloglogistics_lbwa_limited_writer_roles() as $role_name ) {
		$role = get_role( $role_name );

		if ( ! $role ) {
			continue;
		}

		foreach ( $caps_to_remove as $cap ) {
			$role->remove_cap( $cap );
		}

		$role->add_cap( 'read' );
		$role->add_cap( 'edit_posts' );
	}
}

/**
 * Restore normal built-in role capabilities that older versions may have removed.
 */
function bloglogistics_lbwa_restore_builtin_writer_capabilities(): void {
	$editor = get_role( 'editor' );
	if ( $editor ) {
		foreach ( array( 'upload_files', 'publish_posts', 'publish_pages', 'edit_published_posts', 'edit_published_pages', 'delete_published_posts', 'delete_published_pages', 'moderate_comments' ) as $cap ) {
			$editor->add_cap( $cap );
		}
	}

	$author = get_role( 'author' );
	if ( $author ) {
		foreach ( array( 'upload_files', 'publish_posts', 'edit_published_posts', 'delete_published_posts' ) as $cap ) {
			$author->add_cap( $cap );
		}
	}

	$contributor = get_role( 'contributor' );
	if ( $contributor ) {
		$contributor->add_cap( 'read' );
		$contributor->add_cap( 'edit_posts' );
	}
}

/**
 * Runtime enforcement, prevents media upload and publishing even if another
 * plugin grants the capabilities back later.
 *
 * @param array<string, bool> $allcaps All capabilities.
 * @param string[]            $caps    Required capabilities.
 * @param mixed[]             $args    Capability check arguments.
 * @param WP_User             $user    User object.
 * @return array<string, bool>
 */
function bloglogistics_lbwa_block_limited_writer_caps( array $allcaps, array $caps, array $args, WP_User $user ): array {
	unset( $caps, $args );

	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) && ! bloglogistics_lbwa_setting_enabled( 'limit_comment_tools' ) ) {
		return $allcaps;
	}

	if ( empty( $user->roles ) ) {
		return $allcaps;
	}

	$is_limited_writer = (bool) array_intersect( bloglogistics_lbwa_limited_writer_roles(), (array) $user->roles );

	if ( ! $is_limited_writer ) {
		return $allcaps;
	}

	$caps_to_remove = array();

	if ( bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		$caps_to_remove = array_merge(
			$caps_to_remove,
			array(
				'upload_files',
				'publish_posts',
				'publish_pages',
			)
		);
	}

	if ( bloglogistics_lbwa_setting_enabled( 'limit_comment_tools' ) ) {
		$caps_to_remove[] = 'moderate_comments';
	}

	foreach ( array_unique( $caps_to_remove ) as $cap ) {
		$allcaps[ $cap ] = false;
	}

	return $allcaps;
}
add_filter( 'user_has_cap', 'bloglogistics_lbwa_block_limited_writer_caps', 10, 4 );

/**
 * Block direct access to Media Library, upload, customizer, and related screens.
 */
function bloglogistics_lbwa_block_media_admin_pages(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! bloglogistics_lbwa_is_limited_writer() ) {
		return;
	}

	global $pagenow;

	$blocked_pages = array(
		'upload.php',
		'media-new.php',
		'async-upload.php',
		'customize.php',
	);

	if ( in_array( $pagenow, $blocked_pages, true ) ) {
		wp_safe_redirect( admin_url( 'edit.php' ) );
		exit;
	}
}
add_action( 'admin_init', 'bloglogistics_lbwa_block_media_admin_pages', 20 );

/**
 * Remove Media menu from wp-admin.
 */
function bloglogistics_lbwa_remove_media_menu_for_limited_writers(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( bloglogistics_lbwa_is_limited_writer() ) {
		remove_menu_page( 'upload.php' );
	}
}
add_action( 'admin_menu', 'bloglogistics_lbwa_remove_media_menu_for_limited_writers', 999 );

/**
 * Remove Add Media button from the post editor.
 */
function bloglogistics_lbwa_remove_add_media_button_for_limited_writers(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( bloglogistics_lbwa_is_limited_writer() ) {
		remove_action( 'media_buttons', 'media_buttons' );
	}
}
add_action( 'admin_head', 'bloglogistics_lbwa_remove_add_media_button_for_limited_writers' );

/**
 * Remove Featured Image box from editors for limited writers.
 */
function bloglogistics_lbwa_remove_featured_image_box(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) || ! bloglogistics_lbwa_is_limited_writer() ) {
		return;
	}

	foreach ( array( 'post', 'page' ) as $post_type ) {
		remove_meta_box( 'postimagediv', $post_type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'bloglogistics_lbwa_remove_featured_image_box', 99 );

/**
 * Add editor-side safeguards to hide image/media insertion controls.
 */
function bloglogistics_lbwa_admin_editor_safeguards(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) || ! bloglogistics_lbwa_is_limited_writer() ) {
		return;
	}
	?>
	<style>
		.editor-post-featured-image,
		.editor-post-featured-image__container,
		.editor-post-featured-image__preview,
		.components-button.editor-post-featured-image__toggle,
		.components-button.editor-post-featured-image__preview,
		.components-panel__body.edit-post-post-featured-image,
		.components-panel__body:has(.editor-post-featured-image),
		button[aria-label="Set featured image"],
		button[aria-label="Featured image"],
		button[aria-label*="Featured image"],
		button[aria-label*="Set featured image"],
		button[aria-label*="Icon"],
		[aria-label*="Featured Image"],
		[aria-label*="Featured image"],
		[aria-label*="Site Logo"],
		[aria-label*="Icon"],
		.block-editor-media-placeholder,
		.block-editor-media-replace-flow,
		.block-editor-block-types-list__item[aria-label*="Image"],
		.block-editor-block-types-list__item[aria-label*="Gallery"],
		.block-editor-block-types-list__item[aria-label*="Audio"],
		.block-editor-block-types-list__item[aria-label*="Video"],
		.block-editor-block-types-list__item[aria-label*="Media"],
		.block-editor-block-types-list__item[aria-label*="Cover"],
		.block-editor-block-types-list__item[aria-label*="Featured Image"],
		.block-editor-block-types-list__item[aria-label*="Featured image"],
		.block-editor-block-types-list__item[aria-label*="Site Logo"],
		.block-editor-block-types-list__item[aria-label*="Logo"],
		.block-editor-block-types-list__item[aria-label*="Icon"],
		.block-editor-block-types-list__item[title*="Icon"],
		.block-editor-block-types-list__item[title*="Featured Image"],
		.block-editor-block-types-list__item[title*="Featured image"] {
			display: none !important;
		}
	</style>
	<script>
		(function() {
			var blockedBlocks = [
				'core/image',
				'core/gallery',
				'core/audio',
				'core/video',
				'core/file',
				'core/media-text',
				'core/embed',
				'core/html',
				'core/cover',
				'core/site-logo',
				'core/post-featured-image',
				'core/avatar'
			];

			function removeMediaUi() {
				var selectors = [
					'.editor-post-featured-image',
					'.editor-post-featured-image__container',
					'.components-panel__body.edit-post-post-featured-image',
					'button[aria-label="Set featured image"]',
					'button[aria-label="Featured image"]',
					'button[aria-label*="Featured image"]',
					'button[aria-label*="Set featured image"]',
					'button[aria-label*="Icon"]',
					'[aria-label*="Icon"]',
					'[aria-label*="Site Logo"]',
					'.block-editor-media-placeholder',
					'.block-editor-media-replace-flow'
				];

				selectors.forEach(function(selector) {
					document.querySelectorAll(selector).forEach(function(element) {
						element.style.display = 'none';
					});
				});

				document.querySelectorAll('.components-panel__body, .interface-complementary-area .components-panel__body, .block-editor-block-types-list__item').forEach(function(element) {
					var text = String(element.textContent || '').toLowerCase();
					var label = String(element.getAttribute('aria-label') || '').toLowerCase();
					if (text.indexOf('featured image') !== -1 || text.indexOf('site logo') !== -1 || text === 'icon' || label.indexOf('featured image') !== -1 || label.indexOf('site logo') !== -1 || label.indexOf('icon') !== -1) {
						element.style.display = 'none';
					}
				});
			}

			function unregisterBlockedBlocks() {
				if (!window.wp || !window.wp.blocks || !window.wp.blocks.unregisterBlockType) {
					return;
				}

				blockedBlocks.forEach(function(blockName) {
					if (window.wp.blocks.getBlockType(blockName)) {
						window.wp.blocks.unregisterBlockType(blockName);
					}
				});

				if (window.wp.blocks.getBlockTypes) {
					window.wp.blocks.getBlockTypes().forEach(function(blockType) {
						var title = String(blockType.title || '').toLowerCase();
						var name = String(blockType.name || '').toLowerCase();
						var shouldBlock = title.indexOf('featured image') !== -1 || title.indexOf('site logo') !== -1 || title.indexOf('icon') !== -1 || name.indexOf('featured-image') !== -1 || name.indexOf('site-logo') !== -1 || name.indexOf('icon') !== -1;
						if (shouldBlock && window.wp.blocks.getBlockType(blockType.name)) {
							window.wp.blocks.unregisterBlockType(blockType.name);
						}
					});
				}
			}

			if (window.wp && window.wp.domReady) {
				window.wp.domReady(function() {
					unregisterBlockedBlocks();
					removeMediaUi();
					window.setInterval(removeMediaUi, 1000);
				});
			} else {
				document.addEventListener('DOMContentLoaded', function() {
					removeMediaUi();
					window.setInterval(removeMediaUi, 1000);
				});
			}
		}());
	</script>
	<?php
}
add_action( 'admin_head-post.php', 'bloglogistics_lbwa_admin_editor_safeguards' );
add_action( 'admin_head-post-new.php', 'bloglogistics_lbwa_admin_editor_safeguards' );

/**
 * Remove image/media block types from the block editor for limited writers.
 *
 * @param array<string, mixed>|bool $allowed_block_types Allowed block types.
 * @return array<string, mixed>|bool
 */
function bloglogistics_lbwa_allowed_block_types( $allowed_block_types ) {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return $allowed_block_types;
	}

	if ( current_user_can( 'manage_options' ) || ! bloglogistics_lbwa_is_limited_writer() ) {
		return $allowed_block_types;
	}

	$blocked = array(
		'core/image',
		'core/gallery',
		'core/audio',
		'core/video',
		'core/file',
		'core/media-text',
		'core/embed',
		'core/html',
		'core/cover',
		'core/site-logo',
		'core/post-featured-image',
		'core/avatar',
	);

	$registered = WP_Block_Type_Registry::get_instance()->get_all_registered();

	if ( true === $allowed_block_types || ! is_array( $allowed_block_types ) ) {
		$allowed_block_types = array_keys( $registered );
	}

	$blocked_by_title = array();
	foreach ( $registered as $block_name => $block_type ) {
		$title = isset( $block_type->title ) ? strtolower( (string) $block_type->title ) : '';
		$name  = strtolower( (string) $block_name );

		if ( str_contains( $title, 'featured image' ) || str_contains( $title, 'site logo' ) || 'icon' === $title || str_contains( $name, 'featured-image' ) || str_contains( $name, 'site-logo' ) || str_contains( $name, 'icon' ) ) {
			$blocked_by_title[] = $block_name;
		}
	}

	return array_values( array_diff( $allowed_block_types, array_merge( $blocked, $blocked_by_title ) ) );
}
add_filter( 'allowed_block_types_all', 'bloglogistics_lbwa_allowed_block_types' );

/**
 * Remove media HTML and common media blocks from post content.
 */
function bloglogistics_lbwa_strip_media_from_content( string $content ): string {
	$content = preg_replace( '#<!--\s+wp:(image|gallery|audio|video|file|media-text|embed|cover|site-logo|post-featured-image|avatar)[\s\S]*?\/wp:\1\s+-->#i', '', $content ) ?? $content;
	$content = preg_replace( '#<!--\s+wp:(image|gallery|audio|video|file|media-text|embed|cover|site-logo|post-featured-image|avatar)[^>]*?\/\s+-->#i', '', $content ) ?? $content;
	$content = preg_replace( '#<(img|picture|source|video|audio|iframe|embed|object)\b[^>]*>.*?</\1>#is', '', $content ) ?? $content;
	$content = preg_replace( '#<(img|source|embed)\b[^>]*\/?>#is', '', $content ) ?? $content;

	return $content;
}

/**
 * Force posts from limited writers to stay as Draft or Pending Review and strip media workarounds.
 *
 * @param array<string, mixed> $data    Slashed post data.
 * @param array<string, mixed> $postarr Raw post array.
 * @return array<string, mixed>
 */
function bloglogistics_lbwa_force_pending_review_for_limited_writers( array $data, array $postarr ): array {
	unset( $postarr );

	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return $data;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return $data;
	}

	if ( ! bloglogistics_lbwa_is_limited_writer() ) {
		return $data;
	}

	if ( isset( $data['post_type'] ) && 'post' !== $data['post_type'] ) {
		return $data;
	}

	if ( isset( $data['post_status'] ) && 'publish' === $data['post_status'] ) {
		$data['post_status'] = 'pending';
	}

	if ( isset( $data['post_content'] ) && is_string( $data['post_content'] ) ) {
		$data['post_content'] = bloglogistics_lbwa_strip_media_from_content( $data['post_content'] );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'bloglogistics_lbwa_force_pending_review_for_limited_writers', 10, 2 );

/**
 * Remove featured images saved by workarounds.
 */
function bloglogistics_lbwa_remove_featured_image_workarounds( int $post_id ): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_media_tools' ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || current_user_can( 'manage_options' ) || ! bloglogistics_lbwa_is_limited_writer() ) {
		return;
	}

	delete_post_thumbnail( $post_id );
}
add_action( 'save_post', 'bloglogistics_lbwa_remove_featured_image_workarounds', 20 );


/**
 * Remove Comments menu from wp-admin for limited writers.
 */
function bloglogistics_lbwa_remove_comments_menu_for_limited_writers(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_comment_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) || ! bloglogistics_lbwa_is_limited_writer() ) {
		return;
	}

	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'bloglogistics_lbwa_remove_comments_menu_for_limited_writers', 999 );

/**
 * Remove Comments node from the admin bar for limited writers.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 */
function bloglogistics_lbwa_remove_comments_admin_bar_node( WP_Admin_Bar $wp_admin_bar ): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_comment_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) || ! bloglogistics_lbwa_is_limited_writer() ) {
		return;
	}

	$wp_admin_bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'bloglogistics_lbwa_remove_comments_admin_bar_node', 999 );

/**
 * Block direct access to comment moderation screens for limited writers.
 */
function bloglogistics_lbwa_block_comment_admin_pages(): void {
	if ( ! bloglogistics_lbwa_setting_enabled( 'limit_comment_tools' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) || ! bloglogistics_lbwa_is_limited_writer() ) {
		return;
	}

	global $pagenow;

	$blocked_pages = array(
		'edit-comments.php',
		'comment.php',
	);

	if ( in_array( $pagenow, $blocked_pages, true ) ) {
		wp_safe_redirect( admin_url( 'edit.php' ) );
		exit;
	}
}
add_action( 'admin_init', 'bloglogistics_lbwa_block_comment_admin_pages', 25 );

/**
 * Check whether the shared BlogLogistics parent menu already exists.
 */
function bloglogistics_lbwa_bloglogistics_menu_exists(): bool {
	global $menu;

	if ( ! is_array( $menu ) ) {
		return false;
	}

	foreach ( $menu as $item ) {
		if ( isset( $item[2] ) && 'bloglogistics' === $item[2] ) {
			return true;
		}
	}

	return false;
}

/**
 * Render the shared BlogLogistics parent page.
 */
function bloglogistics_lbwa_render_bloglogistics_parent_page(): void {
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'BlogLogistics', 'bloglogistics-limited-blog-writing-access' ); ?></h1>
		<p><?php echo esc_html__( 'Manage BlogLogistics plugin settings from the left sidebar.', 'bloglogistics-limited-blog-writing-access' ); ?></p>
	</div>
	<?php
}

/**
 * Register the shared parent menu if another BlogLogistics plugin has not already registered it.
 */
function bloglogistics_lbwa_register_bloglogistics_parent_menu(): void {
	if ( bloglogistics_lbwa_bloglogistics_menu_exists() ) {
		return;
	}

	add_menu_page(
		__( 'BlogLogistics', 'bloglogistics-limited-blog-writing-access' ),
		__( 'BlogLogistics', 'bloglogistics-limited-blog-writing-access' ),
		'manage_options',
		'bloglogistics',
		'bloglogistics_lbwa_render_bloglogistics_parent_page',
		'dashicons-rss',
		58
	);
}
add_action( 'admin_menu', 'bloglogistics_lbwa_register_bloglogistics_parent_menu', 9 );

/**
 * Register this plugin's settings submenu.
 */
function bloglogistics_lbwa_register_settings_page(): void {
	add_submenu_page(
		'bloglogistics',
		__( 'Limited Blog Writing Access', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Limited Blog Writing Access', 'bloglogistics-limited-blog-writing-access' ),
		'manage_options',
		'bloglogistics-limited-blog-writing-access',
		'bloglogistics_lbwa_render_settings_page'
	);
}
add_action( 'admin_menu', 'bloglogistics_lbwa_register_settings_page', 20 );

/**
 * Render settings page.
 */
function bloglogistics_lbwa_render_settings_page(): void {
	$settings = bloglogistics_lbwa_get_settings();
	$message  = isset( $_GET['bloglogistics_lbwa_message'] ) ? sanitize_key( wp_unslash( $_GET['bloglogistics_lbwa_message'] ) ) : '';
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Limited Blog Writing Access', 'bloglogistics-limited-blog-writing-access' ); ?></h1>

		<?php if ( 'saved' === $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Settings saved.', 'bloglogistics-limited-blog-writing-access' ); ?></p></div>
		<?php elseif ( 'defaults' === $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Recommended defaults restored.', 'bloglogistics-limited-blog-writing-access' ); ?></p></div>
		<?php endif; ?>

		<p><?php echo esc_html__( 'These settings control what non-administrators and limited writing roles can do in wp-admin.', 'bloglogistics-limited-blog-writing-access' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bloglogistics_lbwa_save_settings' ); ?>
			<input type="hidden" name="action" value="bloglogistics_lbwa_save_settings" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html__( 'Limit wp-admin access for non-administrators', 'bloglogistics-limited-blog-writing-access' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="limit_admin_access" value="1" <?php checked( $settings['limit_admin_access'] ); ?> />
							<?php echo esc_html__( 'Redirect other non-administrator users away from wp-admin and hide the admin bar for them.', 'bloglogistics-limited-blog-writing-access' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Keep limited writers away from media and publishing tools', 'bloglogistics-limited-blog-writing-access' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="limit_media_tools" value="1" <?php checked( $settings['limit_media_tools'] ); ?> />
							<?php echo esc_html__( 'Allow limited writers to write posts, but block media access, image insertion, publishing, and related workarounds.', 'bloglogistics-limited-blog-writing-access' ); ?>
						</label>
						<p class="description"><?php echo esc_html__( 'This includes Media Library access, media uploads, the Media menu, Add Media, Featured Image, Site Logo, Insert from URL, media blocks, embeds, direct media HTML, and publishing attempts.', 'bloglogistics-limited-blog-writing-access' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Keep limited writers away from comment moderation', 'bloglogistics-limited-blog-writing-access' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="limit_comment_tools" value="1" <?php checked( $settings['limit_comment_tools'] ); ?> />
							<?php echo esc_html__( 'Remove Comments from wp-admin for limited writers and block direct access to comment moderation screens.', 'bloglogistics-limited-blog-writing-access' ); ?>
						</label>
						<p class="description"><?php echo esc_html__( 'If someone manually enters a comments URL, such as edit-comments.php, they are redirected back to the posts screen instead of seeing or moderating comments.', 'bloglogistics-limited-blog-writing-access' ); ?></p>
					</td>
				</tr>
			</table>

			<p><strong><?php echo esc_html__( 'Recommended defaults:', 'bloglogistics-limited-blog-writing-access' ); ?></strong></p>
			<ul style="list-style: disc; margin-left: 1.5em;">
				<li><?php echo esc_html__( 'Limit wp-admin access: On', 'bloglogistics-limited-blog-writing-access' ); ?></li>
				<li><?php echo esc_html__( 'Keep limited writers away from media and publishing tools: On', 'bloglogistics-limited-blog-writing-access' ); ?></li>
				<li><?php echo esc_html__( 'Keep limited writers away from comment moderation: On', 'bloglogistics-limited-blog-writing-access' ); ?></li>
			</ul>

			<?php submit_button( __( 'Save Settings', 'bloglogistics-limited-blog-writing-access' ) ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bloglogistics_lbwa_restore_defaults' ); ?>
			<input type="hidden" name="action" value="bloglogistics_lbwa_restore_defaults" />
			<?php submit_button( __( 'Restore recommended defaults', 'bloglogistics-limited-blog-writing-access' ), 'secondary', 'submit', false ); ?>
			<p class="description"><?php echo esc_html__( 'This turns both protections back on.', 'bloglogistics-limited-blog-writing-access' ); ?></p>
		</form>
	</div>
	<?php
}

/**
 * Save settings.
 */
function bloglogistics_lbwa_handle_save_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage these settings.', 'bloglogistics-limited-blog-writing-access' ) );
	}

	check_admin_referer( 'bloglogistics_lbwa_save_settings' );

	$settings = array(
		'limit_admin_access' => isset( $_POST['limit_admin_access'] ),
		'limit_media_tools'    => isset( $_POST['limit_media_tools'] ),
		'limit_comment_tools'  => isset( $_POST['limit_comment_tools'] ),
	);

	update_option( BLOGLOGISTICS_LBWA_SETTINGS_OPTION, $settings, false );
	update_option( BLOGLOGISTICS_LBWA_VERSION_OPTION, BLOGLOGISTICS_LBWA_VERSION, false );

	if ( $settings['limit_media_tools'] || $settings['limit_comment_tools'] ) {
		bloglogistics_lbwa_apply_limited_writer_capabilities();
	} else {
		bloglogistics_lbwa_restore_builtin_writer_capabilities();
	}

	wp_safe_redirect( admin_url( 'admin.php?page=bloglogistics-limited-blog-writing-access&bloglogistics_lbwa_message=saved' ) );
	exit;
}
add_action( 'admin_post_bloglogistics_lbwa_save_settings', 'bloglogistics_lbwa_handle_save_settings' );

/**
 * Restore recommended defaults.
 */
function bloglogistics_lbwa_handle_restore_defaults(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage these settings.', 'bloglogistics-limited-blog-writing-access' ) );
	}

	check_admin_referer( 'bloglogistics_lbwa_restore_defaults' );

	update_option( BLOGLOGISTICS_LBWA_SETTINGS_OPTION, bloglogistics_lbwa_default_settings(), false );
	update_option( BLOGLOGISTICS_LBWA_VERSION_OPTION, BLOGLOGISTICS_LBWA_VERSION, false );
	bloglogistics_lbwa_apply_limited_writer_capabilities();

	wp_safe_redirect( admin_url( 'admin.php?page=bloglogistics-limited-blog-writing-access&bloglogistics_lbwa_message=defaults' ) );
	exit;
}
add_action( 'admin_post_bloglogistics_lbwa_restore_defaults', 'bloglogistics_lbwa_handle_restore_defaults' );
