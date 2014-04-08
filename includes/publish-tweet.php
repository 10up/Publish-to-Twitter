<?php
/**
 * Handle actually publishing posts to Twitter.
 */
class pttPublishTweet {

	/**
	 * Consumer key for the Twitter App.
	 *
	 * @var string
	 */
	private $_consumer_key;

	/**
	 * Consumer secret for the Twitter App.
	 *
	 * @var string
	 */
	private $_consumer_secret;

	/**
	 * Wireup functionality.
	 */
	public function __construct() {
		add_action( 'ptt_publish_tweet',      array( $this, 'push_post' ) );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );

		if ( defined( 'PTT_CONSUMER_KEY' ) ) {
			$this->_consumer_key = PTT_CONSUMER_KEY;
		} else {
			$this->_consumer_key = get_option( '_ptt_consumer_key' );
		}

		if ( defined( 'PTT_CONSUMER_SECRET' ) ) {
			$this->_consumer_secret = PTT_CONSUMER_SECRET;
		} else {
			$this->_consumer_secret = get_option( '_ptt_consumer_secret' );
		}
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
		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return;
		}

		$has_been_twittered = get_post_meta( $post->ID, 'has_been_twittered', true );
		if ( 'yes' === $has_been_twittered ) {
			return;
		}

		add_post_meta( $post->ID, 'has_been_twittered', 'yes' );

		if ( 'yes' !== $has_been_twittered ) { // Redundant check
			setup_postdata( $post );

			// Default Twitter message
			$message = sprintf( _x( '%1$s %2$s by %3$s', '[post title] [post link] by [twitter username]', 'ptt-publish-to-twitter' ), '[title]', '[link]', '[twitname]' );
			$message = apply_filters( 'ptt_message', $message );

			$message = apply_filters( 'ptt_pre_proc_message', $message, $post->ID );

			// Get the post tile and apply it to the message
			$post_title = apply_filters( 'ptt_title', $post->post_title, $message, $post );

			// Get the post URL and apply it to the message
			$post_url = apply_filters( 'ptt_url', wp_get_shortlink( $post->ID ), $post );

			// Get any hashtags that need to be applied
			$hashtags = apply_filters( 'ptt_hashtags', '', $post );

			// Get all of the taxonomies available
			$taxonomies = get_object_taxonomies( 'ptt-twitter-account' );

			$taxonomy_args = array(
				'relation' => 'OR'
			);

			foreach( $taxonomies as $taxonomy ) {
				$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

				$taxonomy_args[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'id',
				    'terms'    => $terms,
				);
			}

			$search_args = array(
				'post_type'   => 'ptt-twitter-account',
				'post_status' => 'publish',
				'numberposts' => -1,
			    'tax_query'   => $taxonomy_args
			);

			$accounts = get_posts(
				$search_args
			);
			$accounts = wp_list_pluck( $accounts, 'post_title' );

			foreach( $accounts as $account ) {
				$twitname = apply_filters( 'ptt_twitname', $account, $post );

				// Link and Twitter handle MUST be full text. Title, however, can be shortened
				$twitmessage = str_replace( '[link]', $post_url, $message );
				$twitmessage = str_replace( '[twitname]', $twitname, $twitmessage );

				if ( strlen( $post_title ) + strlen( $twitmessage ) + strlen( $hashtags ) > 147 ) { // 147 since we're replacing [title]
					$post_title = substr( $post_title, 0, 146 - strlen( $twitmessage ) - strlen ( $hashtags ) ) . "…"; // 146 to make room for ellipses
				}

				$twitmessage = str_replace( '[title]', $post_title, $twitmessage ) . $hashtags;

				$twitmessage = apply_filters( 'ptt_post_proc_message', $twitmessage, $post->ID );

				$update_status = $this->send_message( $twitmessage, $account );

				if ( ! $update_status ) {
					update_post_meta( $post->ID, 'ptt_fail', "api-fail_{$account}" );
				}
			}

			wp_reset_postdata();
		}
	}

	/**
	 * Connect to Twitter and push out a message.
	 *
	 * @param string $message         Status update to post.
	 * @param string $twitter_account Twitter handle to send from.
	 *
	 * @return bool
	 */
	private function send_message( $message, $twitter_account ) {
		$account = get_page_by_title( $twitter_account, OBJECT, 'ptt-twitter-account' );

		if ( null === $account ) {
			return false;
		}

		$oauth = get_post_meta( $account->ID, '_ptt_oauth_token', true );
		$oauth_secret = get_post_meta( $account->ID, '_ptt_oauth_token_secret', true );

		if ( empty( $oauth ) || empty( $oauth_secret ) ) {
			return false;
		}

		if ( ! class_exists( 'pttTwitterOAuth' ) ) {
			require_once( __DIR__ . '/library/ptt-twitter-oauth.php' );
		}

		$connection = new pttTwitterOAuth( $this->_consumer_key, $this->_consumer_secret, $oauth, $oauth_secret );
		$content = $connection->get( 'account/verify_credentials' );

		if ( is_object( $content ) && ! empty( $content->screen_name ) ) {
			$result = $connection->post( 'statuses/update', array( 'status' => $message ) );
			if ( is_object( $result ) && ! empty( $result->id ) ) {
				return true;
			}
		}

		return false;
	}
}
$GLOBALS['pttPublishTweet'] = new pttPublishTweet();
