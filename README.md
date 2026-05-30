=== BlogLogistics Limited Blog Writing Access ===
Contributors: bloglogistics
Tags: roles, permissions, writing, contributors, admin access
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.1.3
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Allows selected writing roles to create blog posts while preventing media access, uploads, publishing, and broader wp-admin access.

== Description ==

BlogLogistics Limited Blog Writing Access allows selected writing roles to access wp-admin for writing posts while preventing media access, media uploads, publishing, and broader administrative access.

The plugin is intended for sites where some users need to draft or submit blog posts, but should not upload media, publish content, or access unrelated admin areas.

Version 1.1.3 adds a configurable comment moderation protection for limited writers.

== Features ==

* Adds settings under BlogLogistics > Limited Blog Writing Access.
* Redirects other non-administrator users away from wp-admin when enabled.
* Hides the admin bar for non-administrators when enabled.
* Removes media upload and publishing capabilities from limited writing roles when enabled.
* Blocks direct access to Media Library and media upload screens when enabled.
* Removes the Media menu from wp-admin for limited writers when enabled.
* Removes the Add Media button from the post editor for limited writers when enabled.
* Blocks common image and media insertion workarounds when enabled.
* Removes Comments from wp-admin and blocks direct access to comment moderation screens when enabled.
* Forces attempted published posts from limited writers to Pending Review when enabled.
* Includes runtime capability enforcement in case another plugin restores restricted capabilities.
* Cleans up plugin settings on uninstall.
* Uses the BlogLogistics manifest-based update system.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. Go to BlogLogistics > Limited Blog Writing Access to review the settings.
4. Confirm that Editors, Authors, and Contributors can access post writing screens.
5. Confirm that limited writers cannot access Media Library, upload files, or publish posts when the media and publishing protection is enabled.

== Usage ==

After activation, the recommended defaults are turned on automatically.

Administrators can manage the settings at:

BlogLogistics > Limited Blog Writing Access

The main settings are:

* Limit wp-admin access for non-administrators.
* Keep limited writers away from media and publishing tools.
* Keep limited writers away from comment moderation.

Administrators keep full access.

== Frequently Asked Questions ==

= Which roles are treated as limited writers? =

Editors, Authors, and Contributors.

= Can administrators still access everything? =

Yes. Users with the manage_options capability keep full wp-admin access.

= Are the protections turned on by default? =

Yes. On first activation, the recommended defaults are turned on automatically.

= Can I turn the protections off? =

Yes. Go to BlogLogistics > Limited Blog Writing Access and turn off the setting you no longer want to apply.

= What does “Limit wp-admin access for non-administrators” do? =

It redirects other non-administrator users away from wp-admin and hides the admin bar for them.

= What does “Keep limited writers away from media and publishing tools” do? =

It allows limited writers to write posts, but blocks media access, image insertion, publishing, and related workarounds.

= Does this plugin modify role capabilities? =

When the media and publishing protection is turned on, the plugin removes selected media and publishing capabilities from the built-in writing roles and also enforces the same restrictions at runtime. When that protection is turned off, the plugin restores the normal built-in Editor and Author capabilities it previously restricted.

= What is removed when the plugin is deleted? =

The plugin removes its saved settings and version option. It does not delete users, posts, pages, or site content.

== Changelog ==

= 1.1.3 =
* Add a setting to keep limited writers away from comment moderation.
* Remove Comments from the wp-admin menu when comment moderation protection is enabled.
* Redirect direct comment moderation screen access back to the posts screen.
* Remove the Comments admin bar node for limited writers when enabled.

= 1.1.2 =
* Tighten block-editor restrictions for Featured Image and Icon controls.
* Block the Post Featured Image block and icon-labelled media insertion controls.
* Fix a capability-filter variable issue while preserving normal edit access for limited writers.

= 1.1.1 =
* Fix settings save and restore redirects so the settings page does not go blank after saving.
* Keep normal edit links available for limited writers while media and publishing restrictions are enabled.
* Improve hiding and blocking of Featured Image, Site Logo, Cover, and other media insertion controls.

= 1.1.0 =
* Add settings under BlogLogistics > Limited Blog Writing Access.
* Add toggles for wp-admin access protection and media/publishing restrictions.
* Keep both protections turned on by default for existing behaviour.
* Add restore recommended defaults action.
* Restore normal built-in writing role capabilities when the media and publishing protection is turned off.
* Add uninstall cleanup for plugin settings and version data.

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

= 1.1.3 =
* Add a setting to keep limited writers away from comment moderation.
* Remove Comments from the wp-admin menu when comment moderation protection is enabled.
* Redirect direct comment moderation screen access back to the posts screen.
* Remove the Comments admin bar node for limited writers when enabled.

= 1.1.2 =
Tightens Featured Image and Icon blocking for limited writers.

= 1.1.1 =
Fixes settings page redirects, keeps normal edit links available for limited writers, and improves media insertion blocking.

= 1.1.0 =
Adds settings under BlogLogistics > Limited Blog Writing Access while keeping the existing protections turned on by default.

== License ==

This plugin is licensed under GPL-3.0-or-later.
See https://www.gnu.org/licenses/gpl-3.0.html.
