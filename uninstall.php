<?php
/**
 * Uninstall cleanup for BlogLogistics Limited Blog Writing Access.
 *
 * @package BlogLogistics_Limited_Blog_Writing_Access
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Restore normal built-in writing role capabilities.
 */
function bloglogistics_lbwa_uninstall_restore_builtin_writer_capabilities(): void {
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

bloglogistics_lbwa_uninstall_restore_builtin_writer_capabilities();

delete_option( 'bloglogistics_lbwa_settings' );
delete_option( 'bloglogistics_lbwa_version' );
