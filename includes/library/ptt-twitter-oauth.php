<?php
if ( ! class_exists( 'TwitterOAuth' ) )
	require_once( __DIR__ . '/twitteroauth.php' );

/**
 * Extending the TwitterOAuth class to utilize the WP HTTP API.
 */
class pttTwitterOAuth extends TwitterOAuth {

	public $response_body;

	/**
	 * Call the parent constructor.
	 *
	 * @param $consumer_key
	 * @param $consumer_secret
	 * @param null $oauth_token
	 * @param null $oauth_token_secret
	 */
	public function __construct( $consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL ) {
		parent::__construct( $consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret );
	}

	/**
	 * Make HTTP request using the WP HTTP API.
	 *
	 * @param $url
	 * @param $method
	 * @param null $postfields
	 * @return mixed
	 */
	public function http( $url, $method, $postfields = NULL ) {
		$this->http_info = array();

		$args = array(
			'method' => $method,
			'user-agent' => $this->useragent,
			'timeout' => $this->timeout,		// This sets both CONNECTTIMEOUT and TIMEOUT
			'headers' => array( 'Expect:' ),
			'sslverify' => $this->ssl_verifypeer,
			'postfields' => ! empty( $postfields ) ? $postfields : ''
		);

		$response = wp_remote_request( $url, $args );
		$this->http_code = wp_remote_retrieve_response_code( $response );
		$this->response_body = wp_remote_retrieve_body( $response );
		$this->url = $url;

		return $this->response_body;
	}
}