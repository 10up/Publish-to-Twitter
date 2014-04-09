=== Publish to Twitter ===
Contributors:      10up
Donate link:       http://10up.com
Tags:              twitter, social
Requires at least: 3.8.1
Tested up to:      3.9
Stable tag:        1.0.1
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Allows for publishing posts to Twitter based on category.

== Description ==

Allows for publishing posts to Twitter based on category.

== Installation ==

= Manual Installation =

1. Upload the entire `/publish-to-twitter` folder to the `/wp-content/plugins/` directory.
1. Activate Publish to Twitter through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= How do I get a Twitter Consumer Key? =

Log in to https://apps.twitter.com/apps and create a new application.

Once the application is set up, you can find your Consumer and Consumer Secret keys under the API Keys section under the Application settings heading (they are called API key and API secret).

== Screenshots ==

1. Settings configuration page. Add Twitter API keys and authenticate accounts.

== Changelog ==

= DEV =

* Fix: Correct an over-escaped string
* New: Add FAQs and Screenshots
* Dev: Add Readme.md parser
* Dev: Add pre-deploy build script (to clean node modules and dependencies)
* Dev: Add I18N parser

= 1.0.1 =

* Fix: Minor JS bugs limiting things to a staging environment
* Fix: Expand tweet filtering to encompass all taxonomies

= 1.0 =

* New: Add proper localization/internationalization for strings
* New: Repair the per-category Tweet system so categories are lazy-loaded using the auto-complete feature.
* Update: Bump the bundled Chosen library to v1.1.0.
* Update: Rebuild JavaScript files to avoid deprecated jQuery functions.

= 0.1.0 =

* Initial alpha version
