<?php
/*
Plugin Name: Publish to Twitter
Plugin URI: http://github.com/tollmanz/publish-to-twitter
Description: Allows for publishing posts to Twitter based on category.
Author: Zack Tollman, Helen Hou-Sandi, Jeremy Felt
Version: 0.1
Author URI: http://github.com/tollmanz
*/

/**
 * Wrapper to initiate plugin functionality.
 */
class pttPublishToTwitter {

	public function __construct() {
		// All functionality related to the ptt-twitter-account CPT.
		require_once( __DIR__ . '/includes/cpt-ptt-twitter-account.php' );

		// Handles publishing the post to Twitter.
		require_once( __DIR__ . '/includes/publish-tweet.php' );

		// Controls the admin page for the plugin.
		require_once( __DIR__ . '/includes/settings-page.php' );
	}
}
$pttPublishToTwitter = new pttPublishToTwitter();