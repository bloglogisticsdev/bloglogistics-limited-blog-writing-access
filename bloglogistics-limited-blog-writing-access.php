<?php
/**
 * Plugin Name:       BlogLogistics Limited Blog Writing Access
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access
 * Description:       Allows Editors, Authors, and Contributors to create blog posts, but prevents media access, uploads, and publishing. Subscribers and other non-writing users are redirected away from wp-admin.
 * Version:           1.0.1
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

define( 'BLOGLOGISTICS_LBWA_VERSION', '1.0.1' );
define( 'BLOGLOGISTICS_LBWA_SLUG', 'bloglogistics-limited-blog-writing-access' );
define( 'BLOGLOGISTICS_LBWA_FILE', __FILE__ );
define( 'BLOGLOGISTICS_LBWA_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_LBWA_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-limited-blog-writing-access/' );

$bloglogistics_lbwa_puc = BLOGLOGISTICS_LBWA_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $bloglogistics_lbwa_puc ) ) {
    require_once $bloglogistics_lbwa_puc;
    require_once BLOGLOGISTICS_LBWA_DIR . 'includes/class-bloglogistics-github-plugin-updater.php';

    BlogLogistics_GitHub_Plugin_Updater::init( [
        'repo_url'    => BLOGLOGISTICS_LBWA_REPO_URL,
        'plugin_file' => BLOGLOGISTICS_LBWA_FILE,
        'slug'        => BLOGLOGISTICS_LBWA_SLUG,
    ] );
}

/**
 * Roles that are allowed to access wp-admin for writing posts.
 *
 * @return array<int, string>
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
 * Allow admins full wp-admin access.
 * Allow Editors, Authors, and Contributors into wp-admin so they can write posts.
 * Redirect everyone else, such as Subscribers, to the home page.
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

    wp_redirect( home_url() );
    exit;
}
add_action( 'admin_init', 'bloglogistics_lbwa_restrict_wp_admin_access' );

/**
 * Remove upload, media, and publishing capabilities from Editors, Authors, and Contributors.
 *
 * This runs on activation, but the enforcement filters below also protect against
 * capabilities being restored later by another plugin or role editor.
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

        // Make sure they can still create and edit draft or pending posts.
        $role->add_cap( 'read' );
        $role->add_cap( 'edit_posts' );
    }
}
register_activation_hook( __FILE__, 'bloglogistics_lbwa_set_limited_writer_capabilities' );

/**
 * Runtime enforcement, prevents media upload and publishing even if another plugin
 * grants the capabilities back later.
 *
 * @param array<string, bool> $allcaps All capabilities for the user.
 * @param array<int, string>  $caps    Primitive capabilities being checked.
 * @param array<int, mixed>   $args    Capability check arguments.
 * @param WP_User             $user    User object.
 *
 * @return array<string, bool>
 */
function bloglogistics_lbwa_block_limited_writer_caps( $allcaps, $caps, $args, $user ) {
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
        wp_redirect( admin_url( 'edit.php' ) );
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
 * Force posts from limited writers to stay as Draft or Pending Review.
 * If they somehow attempt to publish, the status is changed to Pending Review.
 *
 * @param array<string, mixed> $data    Sanitized post data.
 * @param array<string, mixed> $postarr Raw post data.
 *
 * @return array<string, mixed>
 */
function bloglogistics_lbwa_force_pending_review_for_limited_writers( $data, $postarr ) {
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

    return $data;
}
add_filter( 'wp_insert_post_data', 'bloglogistics_lbwa_force_pending_review_for_limited_writers', 10, 2 );
