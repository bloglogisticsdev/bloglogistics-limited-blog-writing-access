=== BlogLogistics Limited Blog Writing Access ===
Contributors: bloglogistics
Tags: roles, permissions, writing, contributors, admin access
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.7
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Allows selected writing roles to create blog posts while preventing media access, uploads, publishing, and broader wp-admin access.

== Description ==

BlogLogistics Limited Blog Writing Access allows Editors, Authors, and Contributors to access wp-admin for writing posts while preventing media access, media uploads, publishing, and broader administrative access.

The plugin is intended for sites where some users need to draft or submit blog posts, but should not upload media, publish content, or access unrelated admin areas.

== Features ==

* Allows Editors, Authors, and Contributors to access wp-admin for writing posts.
* Redirects other non-administrator users away from wp-admin.
* Hides the admin bar for non-administrators.
* Removes media upload and publishing capabilities from limited writing roles.
* Blocks direct access to Media Library and media upload screens.
* Removes the Media menu from wp-admin for limited writers.
* Removes the Add Media button from the post editor for limited writers.
* Blocks image and media insertion workarounds, including Featured Image, Site Logo, Insert from URL, media blocks, embeds, and direct media HTML.
* Forces attempted published posts from limited writers to Pending Review.
* Includes runtime capability enforcement in case another plugin restores restricted capabilities.
* Adds an administrator-only Settings page, Plugins screen link, and Dashboard widget explaining the active restrictions.
* Includes GitHub release-based update support.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. Confirm that Editors, Authors, and Contributors can access post writing screens.
4. Confirm that limited writers cannot access Media Library, upload files, or publish posts.

== Usage ==

After activation, Editors, Authors, and Contributors can access wp-admin to create and edit posts, but cannot upload media, access the Media Library, set Featured Images, insert Site Logo blocks, insert media through URL workarounds, or publish content.

Administrators keep full access.

Subscribers and other non-writing users are redirected away from wp-admin.

== Frequently Asked Questions ==

= Which roles are treated as limited writers? =

Editors, Authors, and Contributors.

= Can administrators still access everything? =

Yes. Users with the manage_options capability keep full wp-admin access.

= Can limited writers upload media? =

No. The plugin removes upload capabilities, blocks media screens, removes the Media menu, removes the Add Media button, removes Featured Image controls, removes media and Site Logo blocks from the editor, blocks Featured Image metadata, and strips media markup from saved limited-writer content.

= Can limited writers publish posts? =

No. If a limited writer somehow attempts to publish a post, the plugin forces the post to Pending Review.

= Does this plugin modify role capabilities? =

Yes. On activation, it removes selected publishing and media capabilities from Editors, Authors, and Contributors. It also enforces these restrictions at runtime.

== Changelog ==

= 1.0.7 =
* Block Featured Image controls and Featured Image metadata for limited writers.
* Block Site Logo and Post Featured Image blocks for limited writers.
* Update administrator visibility text to include Featured Image and Site Logo restrictions.

= 1.0.6 =
* Block image and media insertion workarounds for limited writers, including Insert from URL, media blocks, embeds, and direct media HTML.
* Add an administrator-only Settings page, Plugins screen link, and Dashboard widget explaining the restrictions currently enforced by the plugin.

= 1.0.5 =
* Automate update manifest generation and upload from GitHub Actions.
* Generate the update manifest changelog from readme.txt so WordPress displays the full changelog.

= 1.0.4 =
* Switch update checks to the BlogLogistics update manifest endpoint.
* Avoid GitHub API update checks to reduce rate-limit errors.
* Rename updater wrapper from GitHub-specific naming to manifest-style naming.

= 1.0.3 =
* Rename updater wrapper class to BlogLogistics_Limited_Blog_Writing_Access_GitHub_Updater.
* Prevent Plugin Update Checker from loading more than once when multiple BlogLogistics plugins are active.
* Prevent fatal errors caused by duplicate updater class or library declarations.
* Standardize function prefixes to reduce conflict risk.

= 1.0.2 =
* Fix GitHub updater loading when multiple BlogLogistics plugins are active.

= 1.0.1 =
* Test GitHub release update detection.

= 1.0.0 =
* Initial GitHub-updatable release.

== Upgrade Notice ==

= 1.0.7 =
Blocks Featured Image and Site Logo image entry points for limited writers.

= 1.0.6 =
Blocks media insertion workarounds and adds administrator visibility for active restrictions.

= 1.0.4 =
Switches update checks to the BlogLogistics update manifest endpoint.

== License ==

This plugin is licensed under GPL-3.0-or-later.
See https://www.gnu.org/licenses/gpl-3.0.html.
