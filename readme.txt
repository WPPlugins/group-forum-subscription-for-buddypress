=== Plugin Name ===
Contributors: boonebgorges, cuny-academic-commons
Tags: buddypress, bbpress, groups, forums, subscribe, email, notifications
Requires at least: WPMU 2.8, BP 1.0
Tested up to: WPMU 2.8.6, BP 1.1.3
Stable tag: 1.4.1

** Deprecated in BP 1.2. Use http://wordpress.org/extend/plugins/buddypress-group-email-subscription/ ** Gives users the ability to subscribe to email notifications of discussion activity in their BuddyPress groups

== Description ==

** Use of this plugin is not recommended in versions of BuddyPress 1.2 and higher. Please consider using BuddyPress Group Activity Notifications instead: http://wordpress.org/extend/plugins/buddypress-group-activity-stream-subscription/. A conversion script for converting subscription data from this plugin to that one is coming soon **

Features:
Users can subscribe to individual discussion topics from within BuddyPress;
Users can subscribe to discussions on a group-by-group basis;
Users are automatically unsubscribed from a group's discussions when they leave the group;
Administrators can subscribe all users to the appropriate forums with a single click (potentially handy for first-time setup);
Administrators can toggle whether email notification is turned on by default;
Administrators can toggle whether posters receive notification of their own posts.

Developed for the CUNY Academic Commons. Visit http://commons.gc.cuny.edu to learn more about this neat project.

== Installation ==

* Upload `bp-group-forum-subscription.php` to `/wp-content/plugins` and activate it on your main blog (the same one where BuddyPress runs)
* If you're running bbPress as a separate installtion (probably with BP < 1.1), upload the directory `bb-group-forum-subscription` to your bbPress plugin directory and activate the plugin in the bbPress administration panel

*** Optional setup steps ***

* In the Dashboard of your main WP blog, you'll find Group Forum Subscription under Settings. On that page, you can configure various behaviors of the plugin
* The Setup button on the admin page will subscribe every member of your community to every discussion topic in every group they belong to. Don't press the Setup button unless you are SURE you want this to happen!

== Running BP < 1.1? ==

If you plan on having your members use the bbPress interface in addition to/instead of the BuddyPress forums interface (something you can only do if you are running bbPress in a separate installation, which means you're almost certainly running BP < 1.1) do the following steps as well: 

* Upload `bb-group-forum-subscription` to `[bbpress-directory]/my-plugins`
* Activate the plugin on the bbPress admin page
In `[bbpress-template-directory]/topic.php`, replace the code
`user_favorites_link();`
with
`user_favorites_link( array('mid' => __('Subscribe to this discussion')),array('mid' => __('Unsubscribe from this discussion')));`

== Translation credits ==

Many thanks to the following translators:
* German: Markus Schubert
* French: Daniel H
* Italian: Luca Camellini
* Russian: slaFFik
* Spanish: Admin at dominicana.net.do
* Traditional Chinese: Levin



== Changelog ==

= 0.1 =
* Initial release

= 0.2 =
* Bug fixes

= 1.0 =
* Compatibility with BP 1.1
* Bug fixes

= 1.1 =
* Quite a few bug fixes, especially with BP 1.1 compatibility

= 1.2 =
* Post reply bugfix for IE
* Updated readme information for installation with older versions of BP
* Resolved issues some users were having with PHP errors
* Moved stable tag away from trunk. Use trunk at your own risk!

= 1.3 =
* Localization complete. Shipped with four translations: French, German, Russian, Spanish
* Dashboard menu moved under BuddyPress section
* Forum Subscription notification block added to BP's forum index.php pages
* Forum subscription notification block removed for non-logged-in users
* Fixed bug that subscribed non-confirmed users to forums created in standalone bbPress
* Admins can choose whether posters receive notifications of their own posts (off by default; not togglable in standalone bbPress)

= 1.4 =
* Fixed bug that prevented members of large groups from being subscribed to new topics
* Fixed bug that kept email headers/footers from being translated
* Fixed bug that kept subscribing banned members in bbPress standalone
* Fixed bug that didn't check to see if members wanted automatic subscription for a given group in bbPress standalone
* Added topic title to bbPress standalone email
* Fixed bug that kept admin_menu from hooking correctly for some users installing BP in non-standard location

= 1.4.1 =
* Loader file added to prevent crashes when BP is upgraded
* Duplicate notifications menu functions removed to ensure interoperability with BP 1.2 bp-default
