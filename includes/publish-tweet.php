<?php
/**
 * Handle actually publishing posts to Twitter.
 */
class pttPublishTweet {
	/**
	 * Wireup functionality.
	 */
	public function __construct() {
		add_action( 'ptt_publish_tweet',      array( $this, 'push_post' ) );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
	}

	/**
	 * When a post transitions from any other status to "publish," fire the appropriate action.
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'post' !== $post->post_type ) {
			return;
		}

		do_action( 'ptt_publish_tweet', $post );
	}

	/**
	 * Actually push a post through to the Twitter API.
	 *
	 * @param WP_Post $post
	 */
	public function push_post( $post ) {

	}
}
$pttPublishTweet = new pttPublishTweet();