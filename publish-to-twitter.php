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
class PTTPublishToTwitter {

	public function __construct() {
		// Controls the admin page for the plugin.
		require_once( __DIR__ . '/includes/settings-page.php' );

		// Handles publishing the post to Twitter.
		require_once( __DIR__ . '/includes/publish-tweet.php' );

		// Get the basics setup
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'associate_taxonomies' ) );
	}

	public function register_post_type() {

	}

	public function associate_taxonomies() {

	}
}
$PTTPublishToTwitter = new PTTPublishToTwitter();