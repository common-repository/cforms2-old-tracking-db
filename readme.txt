=== Old Tracking DB for cformsII ===
Contributors: bgermann
Donate link: https://www.betterplace.org/projects/11633/donations/new
Tags: tracking, database, db, cforms, cforms2
Tested up to: 6.5
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0

== Description ==

Beginning with version 15 [cformsII](https://wordpress.org/plugins/cforms2) does not have built-in Tracking Database support anymore. However it allows for arbitrary plugins to process the validated form submissions. This plugin implements the old tracking database submission feature via this mechanism, but it will not have any new features. E.g. there will not be any user interface.

You will have to rely on direct database access via any MySQL software to view the data or you can use the [Unofficial CForms II table display](https://wordpress.org/plugins/cformstable) to view the tracking database on any page via Shortcodes.

There are two tables involved in the Tracking DB: "cformssubmissions" and "cformsdata", which are prefixed by the wordpress installation prefix that is "wp_" by default.


== Installation ==

The minimum required cformsII version for this plugin is 15.0.


== Changelog ==

= 0.3 =
* enhanced: depend on \Cforms2\FormSettings instead of the removed cformsII database entry

= 0.2 =
* added:    get_cforms_entries API call
* other:    plugin's entry file is now Plugin.php

= 0.1 =
* added:    compatibility layer to support the old Tracking DB for new form submissions
