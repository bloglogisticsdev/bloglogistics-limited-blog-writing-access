=== BlogLogistics Limited Blog Writing Access ===
Contributors: bloglogistics
Tags: roles, permissions, writing, contributors, admin access
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.4
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
* Forces attempted published posts from limited writers to Pending Review.
* Includes runtime capability enforcement in case another plugin restores restricted capabilities.
* Includes GitHub release-based update support.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. Confirm that Editors, Authors, and Contributors can access post writing screens.
4. Confirm that limited writers cannot access Media Library, upload files, or publish posts.

== Usage ==

After activation, Editors, Authors, and Contributors can access wp-admin to create and edit posts, but cannot upload media or publish content.

Administrators keep full access.

Subscribers and other non-writing users are redirected away from wp-admin.

== Frequently Asked Questions ==

= Which roles are treated as limited writers? =

Editors, Authors, and Contributors.

= Can administrators still access everything? =

Yes. Users with the manage_options capability keep full wp-admin access.

= Can limited writers upload media? =

No. The plugin removes upload capabilities, blocks media screens, removes the Media menu, and removes the Add Media button.

= Can limited writers publish posts? =

No. If a limited writer somehow attempts to publish a post, the plugin forces the post to Pending Review.

= Does this plugin modify role capabilities? =

Yes. On activation, it removes selected publishing and media capabilities from Editors, Authors, and Contributors. It also enforces these restrictions at runtime.

== Changelog ==

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

= 1.0.4 =
Switches update checks to the BlogLogistics update manifest endpoint.

== License ==

This plugin is licensed under GPL-3.0-or-later.
See https://www.gnu.org/licenses/gpl-3.0.html.
