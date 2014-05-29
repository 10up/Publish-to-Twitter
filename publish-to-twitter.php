<?php
/**
 * Plugin Name: Publish to Twitter
 * Plugin URI:  http://10up.com
 * Description: Allows for publishing posts to Twitter based on category.
 * Author:      10up, Zack Tollman, Helen Hou-Sandi, Jeremy Felt, Eric Mann
 * Version:     1.1.3
 * Author URI:  http://10up.com
 * Text Domain: tweetpublish
 * Domain Path: /lang
 */

/**
 * Set constants
 */
define( 'PTT_ROOT',    __DIR__ );
define( 'PTT_URL',     plugins_url( '/', __FILE__ ) );
define( 'PTT_VERSION', '1.1.3' );

/**
 * Wrapper to initiate plugin functionality.
 */
class pttPublishToTwitter {

	public function __construct() {

		// All functionality related to the ptt-twitter-account CPT.
		require_once( PTT_ROOT . '/includes/cpt-ptt-twitter-account.php' );

		// Handles publishing the post to Twitter.
		require_once( PTT_ROOT . '/includes/publish-tweet.php' );

		// Controls the admin page for the plugin.
		require_once( PTT_ROOT . '/includes/settings-page.php' );
	}

	/**
	 * Initialization routine for adding text domains and such.
	 */
	public function initialize() {
		load_plugin_textdomain( 'tweetpublish', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
	}
}
$pttPublishToTwitter = new pttPublishToTwitter();

add_action( 'init', array( $pttPublishToTwitter, 'initialize' ) );