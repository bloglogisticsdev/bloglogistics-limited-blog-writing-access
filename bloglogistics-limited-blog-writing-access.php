<?php
/**
 * Plugin Name:       BlogLogistics Limited Blog Writing Access
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access
 * Description:       Allows selected writing roles to create blog posts while preventing media access, uploads, publishing, and broader wp-admin access.
 * Version:           1.0.6
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

define( 'BLOGLOGISTICS_LBWA_VERSION', '1.0.6' );
define( 'BLOGLOGISTICS_LBWA_SLUG', 'bloglogistics-limited-blog-writing-access' );
define( 'BLOGLOGISTICS_LBWA_FILE', __FILE__ );
define( 'BLOGLOGISTICS_LBWA_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_LBWA_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access/' );
define( 'BLOGLOGISTICS_LBWA_UPDATE_MANIFEST_URL', 'https://updates.bloglogistics.com/plugins/bloglogistics-limited-blog-writing-access.json' );

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
 * Hide the admin bar for everyone except administrators.
 */
function bloglogistics_lbwa_disable_admin_bar_for_non_admins(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}
}
add_action( 'after_setup_theme', 'bloglogistics_lbwa_disable_admin_bar_for_non_admins' );

/**
 * Allow admins full wp-admin access, allow limited writers to write posts,
 * and redirect everyone else away from wp-admin.
 */
function bloglogistics_lbwa_restrict_wp_admin_access(): void {
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
function bloglogistics_lbwa_set_limited_writer_capabilities(): void {
	$roles = bloglogistics_lbwa_limited_writer_roles();

	$caps_to_remove = array(
		'upload_files',
		'publish_posts',
		'publish_pages',
		'edit_published_posts',
		'edit_published_pages',
		'delete_published_posts',
		'delete_published_pages',
	);

	foreach ( $roles as $role_name ) {
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
register_activation_hook( __FILE__, 'bloglogistics_lbwa_set_limited_writer_capabilities' );

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

	if ( empty( $user->roles ) ) {
		return $allcaps;
	}

	$is_limited_writer = (bool) array_intersect( bloglogistics_lbwa_limited_writer_roles(), (array) $user->roles );

	if ( ! $is_limited_writer ) {
		return $allcaps;
	}

	$blocked_caps = array(
		'upload_files',
		'publish_posts',
		'publish_pages',
		'edit_published_posts',
		'edit_published_pages',
		'delete_published_posts',
		'delete_published_pages',
	);

	foreach ( $blocked_caps as $cap ) {
		$allcaps[ $cap ] = false;
	}

	return $allcaps;
}
add_filter( 'user_has_cap', 'bloglogistics_lbwa_block_limited_writer_caps', 10, 4 );

/**
 * Block direct access to Media Library and media upload screens.
 */
function bloglogistics_lbwa_block_media_admin_pages(): void {
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
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( bloglogistics_lbwa_is_limited_writer() ) {
		remove_action( 'media_buttons', 'media_buttons' );
	}
}
add_action( 'admin_head', 'bloglogistics_lbwa_remove_add_media_button_for_limited_writers' );

/**
 * Block names that limited writers cannot insert or save.
 *
 * @return string[]
 */
function bloglogistics_lbwa_blocked_media_blocks(): array {
	return array(
		'core/audio',
		'core/cover',
		'core/embed',
		'core/file',
		'core/gallery',
		'core/image',
		'core/media-text',
		'core/video',
	);
}

/**
 * Determine whether current restrictions should apply to the active user.
 */
function bloglogistics_lbwa_should_restrict_current_user(): bool {
	if ( current_user_can( 'manage_options' ) ) {
		return false;
	}

	return bloglogistics_lbwa_is_limited_writer();
}

/**
 * Remove media blocks from parsed block content recursively.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @return array<int, array<string, mixed>>
 */
function bloglogistics_lbwa_remove_media_blocks_from_parsed_blocks( array $blocks ): array {
	$blocked_blocks = bloglogistics_lbwa_blocked_media_blocks();
	$clean_blocks    = array();

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : null;

		if ( null !== $block_name && in_array( $block_name, $blocked_blocks, true ) ) {
			continue;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = bloglogistics_lbwa_remove_media_blocks_from_parsed_blocks( $block['innerBlocks'] );
		}

		$clean_blocks[] = $block;
	}

	return $clean_blocks;
}

/**
 * Remove direct media HTML from post content.
 */
function bloglogistics_lbwa_strip_media_html( string $content ): string {
	$patterns = array(
		'#<picture\b[^>]*>.*?</picture>#is',
		'#<video\b[^>]*>.*?</video>#is',
		'#<audio\b[^>]*>.*?</audio>#is',
		'#<iframe\b[^>]*>.*?</iframe>#is',
		'#<object\b[^>]*>.*?</object>#is',
		'#<embed\b[^>]*>.*?</embed>#is',
		'#<embed\b[^>]*?/?>#is',
		'#<img\b[^>]*?/?>#is',
		'#<source\b[^>]*?/?>#is',
		'#<track\b[^>]*?/?>#is',
	);

	$content = preg_replace( $patterns, '', $content );

	return is_string( $content ) ? $content : '';
}

/**
 * Remove media blocks and media HTML from limited writer content.
 */
function bloglogistics_lbwa_sanitize_limited_writer_content( string $content ): string {
	if ( '' === trim( $content ) ) {
		return $content;
	}

	if ( function_exists( 'has_blocks' ) && function_exists( 'parse_blocks' ) && function_exists( 'serialize_blocks' ) && has_blocks( $content ) ) {
		$blocks  = parse_blocks( $content );
		$content = serialize_blocks( bloglogistics_lbwa_remove_media_blocks_from_parsed_blocks( $blocks ) );
	}

	return bloglogistics_lbwa_strip_media_html( $content );
}

/**
 * Force posts from limited writers to stay as Draft or Pending Review, and
 * remove media inserted through workarounds such as Insert from URL.
 *
 * @param array<string, mixed> $data    Slashed post data.
 * @param array<string, mixed> $postarr Raw post array.
 * @return array<string, mixed>
 */
function bloglogistics_lbwa_force_pending_review_for_limited_writers( array $data, array $postarr ): array {
	unset( $postarr );

	if ( ! bloglogistics_lbwa_should_restrict_current_user() ) {
		return $data;
	}

	if ( isset( $data['post_type'] ) && 'post' !== $data['post_type'] ) {
		return $data;
	}

	if ( isset( $data['post_status'] ) && 'publish' === $data['post_status'] ) {
		$data['post_status'] = 'pending';
	}

	if ( isset( $data['post_content'] ) && is_string( $data['post_content'] ) ) {
		$data['post_content'] = bloglogistics_lbwa_sanitize_limited_writer_content( $data['post_content'] );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'bloglogistics_lbwa_force_pending_review_for_limited_writers', 10, 2 );

/**
 * Remove media blocks from the block inserter for limited writers.
 *
 * @param bool|string[] $allowed_block_types Allowed block types.
 * @return bool|string[]
 */
function bloglogistics_lbwa_restrict_allowed_block_types( $allowed_block_types ) {
	if ( ! bloglogistics_lbwa_should_restrict_current_user() ) {
		return $allowed_block_types;
	}

	$blocked_blocks = bloglogistics_lbwa_blocked_media_blocks();

	if ( true === $allowed_block_types ) {
		if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
			return $allowed_block_types;
		}

		$registered_blocks   = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$allowed_block_types = array_keys( $registered_blocks );
	}

	if ( ! is_array( $allowed_block_types ) ) {
		return $allowed_block_types;
	}

	return array_values( array_diff( $allowed_block_types, $blocked_blocks ) );
}
add_filter( 'allowed_block_types_all', 'bloglogistics_lbwa_restrict_allowed_block_types' );

/**
 * Add block editor JavaScript that unregisters media blocks and blocks common
 * Insert from URL image patterns before save.
 */
function bloglogistics_lbwa_enqueue_block_editor_restrictions(): void {
	if ( ! bloglogistics_lbwa_should_restrict_current_user() ) {
		return;
	}

	$blocked_blocks = wp_json_encode( bloglogistics_lbwa_blocked_media_blocks() );

	if ( ! is_string( $blocked_blocks ) ) {
		$blocked_blocks = '[]';
	}

	$script = "
(function( wp ) {
	if ( ! wp || ! wp.domReady || ! wp.blocks ) {
		return;
	}

	wp.domReady( function() {
		var blockedBlocks = {$blocked_blocks};

		blockedBlocks.forEach( function( blockName ) {
			if ( wp.blocks.getBlockType( blockName ) ) {
				wp.blocks.unregisterBlockType( blockName );
			}
		} );
	} );
})( window.wp );
";

	wp_add_inline_script( 'wp-blocks', $script, 'after' );
}
add_action( 'enqueue_block_editor_assets', 'bloglogistics_lbwa_enqueue_block_editor_restrictions' );

/**
 * Display a clear, read-only restrictions page for administrators.
 */
function bloglogistics_lbwa_register_admin_visibility_page(): void {
	add_options_page(
		__( 'Limited Blog Access', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Limited Blog Access', 'bloglogistics-limited-blog-writing-access' ),
		'manage_options',
		BLOGLOGISTICS_LBWA_SLUG,
		'bloglogistics_lbwa_render_admin_visibility_page'
	);
}
add_action( 'admin_menu', 'bloglogistics_lbwa_register_admin_visibility_page' );

/**
 * List the restrictions administrators should know are active.
 *
 * @return string[]
 */
function bloglogistics_lbwa_admin_restriction_summary(): array {
	return array(
		__( 'Editors, Authors, and Contributors can log in and access wp-admin for writing posts.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Editors, Authors, and Contributors can create and edit blog articles only.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Media uploads are blocked for limited writers.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Media Library access is blocked for limited writers.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Image and media insertion through editor blocks, embeds, and Insert from URL workarounds is blocked and stripped on save.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Publishing is blocked for limited writers.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Limited writer content can only remain Draft or Pending Review.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Subscribers and other non-approved users are redirected away from wp-admin.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'Administrators remain unrestricted.', 'bloglogistics-limited-blog-writing-access' ),
		__( 'The admin bar is hidden for non-administrators.', 'bloglogistics-limited-blog-writing-access' ),
	);
}

/**
 * Render the admin visibility page.
 */
function bloglogistics_lbwa_render_admin_visibility_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'bloglogistics-limited-blog-writing-access' ) );
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Limited Blog Access Restrictions', 'bloglogistics-limited-blog-writing-access' ); ?></h1>
		<p><?php esc_html_e( 'This plugin is active and is enforcing the following rules:', 'bloglogistics-limited-blog-writing-access' ); ?></p>
		<ul style="list-style: disc; padding-left: 1.5em;">
			<?php foreach ( bloglogistics_lbwa_admin_restriction_summary() as $item ) : ?>
				<li><?php echo esc_html( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p><strong><?php esc_html_e( 'Note:', 'bloglogistics-limited-blog-writing-access' ); ?></strong> <?php esc_html_e( 'These settings are enforced by the plugin and are not configurable on this screen.', 'bloglogistics-limited-blog-writing-access' ); ?></p>
	</div>
	<?php
}

/**
 * Add a restrictions link on the Plugins screen.
 *
 * @param string[] $links Plugin action links.
 * @return string[]
 */
function bloglogistics_lbwa_plugin_action_links( array $links ): array {
	$restrictions_url = admin_url( 'options-general.php?page=' . BLOGLOGISTICS_LBWA_SLUG );
	$restrictions     = '<a href="' . esc_url( $restrictions_url ) . '">' . esc_html__( 'Restrictions', 'bloglogistics-limited-blog-writing-access' ) . '</a>';

	array_unshift( $links, $restrictions );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bloglogistics_lbwa_plugin_action_links' );

/**
 * Add a dashboard widget so administrators can see what the plugin is doing
 * without relying on a persistent admin notice.
 */
function bloglogistics_lbwa_register_dashboard_widget(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'bloglogistics_lbwa_dashboard_widget',
		__( 'Limited Blog Access', 'bloglogistics-limited-blog-writing-access' ),
		'bloglogistics_lbwa_render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', 'bloglogistics_lbwa_register_dashboard_widget' );

/**
 * Render administrator dashboard widget.
 */
function bloglogistics_lbwa_render_dashboard_widget(): void {
	$restrictions_url = admin_url( 'options-general.php?page=' . BLOGLOGISTICS_LBWA_SLUG );
	?>
	<p><?php esc_html_e( 'This site is limiting blog-writing access for Editors, Authors, and Contributors.', 'bloglogistics-limited-blog-writing-access' ); ?></p>
	<ul style="list-style: disc; padding-left: 1.5em;">
		<li><?php esc_html_e( 'No uploads or Media Library access for limited writers.', 'bloglogistics-limited-blog-writing-access' ); ?></li>
		<li><?php esc_html_e( 'No image or media insertion workarounds, including Insert from URL.', 'bloglogistics-limited-blog-writing-access' ); ?></li>
		<li><?php esc_html_e( 'No publishing, only Draft or Pending Review.', 'bloglogistics-limited-blog-writing-access' ); ?></li>
	</ul>
	<p><a class="button" href="<?php echo esc_url( $restrictions_url ); ?>"><?php esc_html_e( 'View all restrictions', 'bloglogistics-limited-blog-writing-access' ); ?></a></p>
	<?php
}
