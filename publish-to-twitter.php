<?php
/**
 * Plugin Name: Publish to Twitter
 * Plugin URI:
 * Description: Allows for publishing posts to Twitter based on category.
 * Author:      10up, Zack Tollman, Helen Hou-Sandi, Jeremy Felt, Eric Mann
 * Version:     0.1
 * Author URI:  http://10up.com
 */

/**
 * Set constants
 */
define( 'PTT_ROOT' , __DIR__ );
define( 'PTT_URL' , plugins_url( '/', __FILE__ ) );
if ( ! defined( 'PTT_CONSUMER_KEY' ) ) {
	define( 'PTT_CONSUMER_KEY', 'is9GkL8t2goIpSARSTYwXQ' ); // Should be overridden in your theme
}
if ( ! defined( 'PTT_CONSUMER_SECRET' ) ) {
	define( 'PTT_CONSUMER_SECRET', base64_decode( 'algzdzJjbWdRY3hyWmpuQXpHVzNFcGEzYjhRaTJKRDFJcUlmS1ZR' ) ); // Should be overridden in your theme
}

/**
 * Wrapper to initiate plugin functionality.
 */
class pttPublishToTwitter {

	public function __construct() {

		// @todo: think about adding these files only when necessary if possible

		// All functionality related to the ptt-twitter-account CPT.
		require_once( PTT_ROOT . '/includes/cpt-ptt-twitter-account.php' );

		// Handles publishing the post to Twitter.
		require_once( PTT_ROOT . '/includes/publish-tweet.php' );

		// Controls the admin page for the plugin.
		require_once( PTT_ROOT . '/includes/settings-page.php' );
	}
}
$pttPublishToTwitter = new pttPublishToTwitter();