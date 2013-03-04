<?php
/**
 * This is a forked version of the MIT licensed TwitterOAuth library
 * provided by Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * Abraham's TwitterOAuth is the long standing defacto library for handling
 * Twitter API requests in PHP. The purpose of this fork is to standardize
 * the code to be more readable per current WordPress core standards, as well
 * as to translate methods (i.e. cURL) into their corresponding WordPress
 * counterparts (i.e. wp_remote_request)
 *
 * TwitterOAuth also requires the use of the MIT licensed PHP OAuth library from
 * Andy Smith found at http://oauth.googlecode.com/svn/code/php/
 */

if ( ! class_exists( 'OAuthConsumer' ) ) {
	require_once('OAuth.php');
}

class TwitterOAuth {
	/* Contains the last HTTP status code returned. */
	public $http_code;
	/* Contains the last API call. */
	public $url;
	/* Set up the API root URL. */
	public $host = 'https://api.twitter.com/1/';
	/* Set timeout default. */
	public $timeout = 30;
	/* Set connect timeout. */
	public $connecttimeout = 30;
	/* Verify SSL Cert. */
	public $ssl_verifypeer = FALSE;
	/* Respons format. */
	public $format = 'json';
	/* Decode returned json data. */
	public $decode_json = TRUE;
	/* Contains the last HTTP headers returned. */
	public $http_info;
	/* Set the useragnet. */
	public $useragent = 'TwitterOAuth v0.2.0-beta2';
	/* Immediately retry the API call if the response was not successful. */
	//public $retry = TRUE;

	/**
	* Set API URLS
	*/
	function accessTokenURL()  { return 'https://api.twitter.com/oauth/access_token'; }
	function authenticateURL() { return 'https://api.twitter.com/oauth/authenticate'; }
	function authorizeURL()    { return 'https://api.twitter.com/oauth/authorize'; }
	function requestTokenURL() { return 'https://api.twitter.com/oauth/request_token'; }

	/**
	* Debug helpers
	*/
	function lastStatusCode() { return $this->http_status; }
	function lastAPICall() { return $this->last_api_call; }

	/**
	 * Construct the TwitterOAuth object
	 *
	 * Creates a consumer and a token from the key and secret information provided for the application and current
	 * user the application is attempting to act on behalf of.
	 *
	 * @param $consumer_key string The application's consumer key provided via Twitter's developer registration
	 * @param $consumer_secret string The application's consumer secret provided via Twitter's developer registration
	 * @param null $oauth_token string The end user's OAuth token captured during authentication and authorization
	 * @param null $oauth_token_secret The end user's OAuth token secret captured during authentication and authorization
	 */
	function __construct( $consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL ) {
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer( $consumer_key, $consumer_secret );
		if ( ! empty( $oauth_token ) && ! empty( $oauth_token_secret ) )
			$this->token = new OAuthConsumer( $oauth_token, $oauth_token_secret );
		else
	        $this->token = NULL;
	}

	/**
	 * Get a request token from Twitter's API
	 *
	 * @param null $oauth_callback string Callback URL for Twitter to send a response to when the token is created
	 * @return array Contains OAuth token and OAuth token secret information
	 */
	function getRequestToken( $oauth_callback = NULL ) {
		$parameters = array();
		if ( ! empty( $oauth_callback ) )
			$parameters['oauth_callback'] = $oauth_callback;

		$request = $this->oAuthRequest( $this->requestTokenURL(), 'GET', $parameters );
		$token = OAuthUtil::parse_parameters( $request );
		$this->token = new OAuthConsumer( $token['oauth_token'], $token['oauth_token_secret'] );
		return $token;
	}

	/**
	 * Get the proper URL to use for authorization or authentication depending on whether a full
	 * sign in request is being issued via Twitter.
	 *
	 * @param $token string Contains the end user's OAuth token
	 * @param bool $sign_in_with_twitter
	 * @return string URL to redirect the end user to for authentication or authorization
	 */
	function getAuthorizeURL( $token, $sign_in_with_twitter = true ) {
		if ( is_array( $token ) )
			$token = $token['oauth_token'];

		if ( empty( $sign_in_with_twitter ) )
			return $this->authorizeURL() . "?oauth_token={$token}";
		else
			return $this->authenticateURL() . "?oauth_token={$token}";
	}

	/**
	* Exchange request token and secret for an access token and
	* secret, to sign API calls.
	*
	* @returns array("oauth_token" => "the-access-token",
	*                "oauth_token_secret" => "the-access-secret",
	*                "user_id" => "9436992",
	*                "screen_name" => "abraham")
	*/

	/**
	 * Use the OAuth verifier parameter returned by Twitter to the registered callback to
	 * obtain a new access token and token secret that can be used to sign future API calls
	 * by the application on behalf of the user.
	 *
	 * The token returned by getAccessToken is an array containing Twitter user information:
	 *
	 *  array( 'oauth_token' => 'the-access-token',
	 *         'oauth_token_secret' => 'the-access-secret',
	 *         'user_id' => '9436992',
	 *         'screen_name' => 'abraham' )
	 *
	 * @param mixed $oauth_verifier string parameter returned by Twitter to verify connection
	 * @return array containing oauth_token, oauth_token_secret, user_id, and screen_name for future API calls
	 */
	function getAccessToken( $oauth_verifier = false ) {
		$parameters = array();
		if ( ! empty( $oauth_verifier ) )
			$parameters['oauth_verifier'] = $oauth_verifier;

		$request = $this->oAuthRequest( $this->accessTokenURL(), 'GET', $parameters );
		$token = OAuthUtil::parse_parameters( $request );
		$this->token = new OAuthConsumer( $token['oauth_token'], $token['oauth_token_secret'] );
		return $token;
	}

	/**
	 * A GET wrapper for oAuthRequest
	 * @param $url string containing the intended API URL to request against
	 * @param array $parameters relevant to the API call
	 * @return API|mixed The returned response from the Twitter API
	 */
	function get( $url, $parameters = array() ) {
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		if ( 'json' === $this->format && $this->decode_json )
			return json_decode( $response );

		return $response;
	}

	/**
	 * A POST wrapper for oAuthRequest
	 * @param $url string containing the intended API URL to request against
	 * @param array $parameters relevant to the API call
	 * @return API|mixed The returned response from the Twitter API
	 */
	function post( $url, $parameters = array() ) {
		$response = $this->oAuthRequest( $url, 'POST', $parameters );
		if ( 'json' === $this->format && $this->decode_json )
			return json_decode($response);

		return $response;
	}

	/**
	 * A DELETE wrapper for oAuthRequest
	 * @param $url string containing the intended API URL to request against
	 * @param array $parameters relevant to the API call
	 * @return API|mixed The returned response from the Twitter API
	 */
	function delete( $url, $parameters = array() ) {
		$response = $this->oAuthRequest( $url, 'DELETE', $parameters );
		if ( 'json' === $this->format && $this->decode_json )
			return json_decode( $response );

		return $response;
	}

	/**
	 * Format and sign an OAuth / Twitter API request
	 *
	 * @param $url string containing the command to be issued to the API
	 * @param $method HTTP method to be used in the request
	 * @param $parameters Additional parameters to be sent with the command to the API
	 * @return API The returned response from the Twitter API
	 */
	function oAuthRequest( $url, $method, $parameters ) {
		if ( 0 !== strrpos( $url, 'https://' ) && 0 !== strrpos( $url, 'http://' ) )
			$url = "{$this->host}{$url}.{$this->format}";

		$request = OAuthRequest::from_consumer_and_token( $this->consumer, $this->token, $method, $url, $parameters );
		$request->sign_request( $this->sha1_method, $this->consumer, $this->token );

		if ( 'GET' == $method )
			return $this->http( $request->to_url(), 'GET' );
		else
			return $this->http( $request->get_normalized_http_url(), $method, $request->to_postdata() );
	}

	/**
	 * Make the actual HTTP request
	 *
	 * @param $url string containing the final URL to make a request to
	 * @param $method HTTP method to be used in the request
	 * @param null $postfields string of parameters to send with a POST request
	 * @return mixed response from the Twitter API
	 */
	function http( $url, $method, $postfields = NULL ) {
		$this->http_info = array();
		$ci = curl_init();
		/* Curl settings */
		curl_setopt( $ci, CURLOPT_USERAGENT, $this->useragent );
		curl_setopt( $ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout );
		curl_setopt( $ci, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $ci, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ci, CURLOPT_HTTPHEADER, array('Expect:') );
		curl_setopt( $ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer );
		curl_setopt( $ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader') );
		curl_setopt( $ci, CURLOPT_HEADER, FALSE );

		if ( 'POST' == $method ) {
			curl_setopt( $ci, CURLOPT_POST, TRUE );
			if ( ! empty( $postfields ) )
				curl_setopt( $ci, CURLOPT_POSTFIELDS, $postfields );
		} elseif ( 'DELETE' == $method ) {
			curl_setopt( $ci, CURLOPT_CUSTOMREQUEST, 'DELETE' );
			if ( ! empty( $postfields ) )
				$url = "{$url}?{$postfields}";
		}

		curl_setopt( $ci, CURLOPT_URL, $url );
		$response = curl_exec( $ci );
		$this->http_code = curl_getinfo( $ci, CURLINFO_HTTP_CODE );
		$this->http_info = array_merge( $this->http_info, curl_getinfo( $ci ) );
		$this->url = $url;
		curl_close ( $ci );
		return $response;
	}

	/**
	 * Callback function for cURL that stores the response headers.
	 *
	 * Note: Not entirely sure this is necessary.
	 *
	 * @param $ch resource cURL resource
	 * @param $header string Response headers from an HTTP request
	 * @return int length of the returned header
	 */
	function getHeader( $ch, $header ) {
		$i = strpos( $header, ':' );
		if ( ! empty( $i ) ) {
			$key = str_replace( '-', '_', strtolower( substr( $header, 0, $i ) ) );
			$value = trim( substr( $header, $i + 2 ) );
			$this->http_header[$key] = $value;
		}
		return strlen( $header );
	}
}