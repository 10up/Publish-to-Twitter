<?php

class pttSettingsPage {

	/**
	 * Consumer key for the Twitter App.
	 *
	 * @var string
	 */
	private $_consumer_key = PTT_CONSUMER_KEY;

	/**
	 * Consumer secret for the Twitter App.
	 *
	 * @var string
	 */
	private $_consumer_secret = PTT_CONSUMER_SECRET;

	/**
	 * Holds the oauth_callback URL, which is just the settings page.
	 *
	 * @var string
	 */
	private $_oauth_callback;

	/**
	 * The settings page url.
	 *
	 * @var string
	 */
	private $_settings_page_url;

	/**
	 * WP_Query object containing the Twitter Account posts.
	 *
	 * @var object
	 */
	private $_twitter_accounts;
	
	/**
	 * Holds errors related to Twitter interaction.
	 *
	 * These errors are special in that they cannot use the error reporting
	 * provided by the Settings API. Due to the multiple requests between the Application
	 * and Twitter, the normal Settings API is not sufficient and the errors are recorded
	 * here before being saved to a transient.
	 *
	 * @var array
	 */
	private $_twitter_errors = array();

	private $_hook;

	/**
	 * Get the party started.
	 */
	public function __construct() {
		// Menu item and settings
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_settings' ) );

		// Twitter specific actions
		add_action( 'admin_init', array( $this, 'get_authorization' ) );
		add_action( 'admin_init', array( $this, 'process_twitter_tokens' ) );
		add_action( 'admin_init', array( $this, 'remove_twitter_account' ) );
		add_action( 'admin_notices', array( $this, 'display_twitter_errors' ) );

		// Styles and JS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );

		// Set instance vars
		$this->_oauth_callback = admin_url( '/options.php?ptt-action=ptt-publish-to-twitter-auth' );
		$this->_settings_page_url = admin_url( '/options-general.php?page=ptt-publish-to-twitter' );
	}

	/**
	 * Add the submenu under Settings.
	 */
	public function add_submenu_page() {
		$this->_hook = add_submenu_page( 'options-general.php', 'Publish to Twitter Settings', 'Publish to Twitter', 'manage_options', 'ptt-publish-to-twitter', array( $this, 'submenu_page' ) );
	}

	/**
	 * Write the submenu page.
	 */
	public function submenu_page() {
	?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Publish to Twitter</h2>

			<form action="options.php" method="post">

				<?php settings_fields( 'ptt-publish-to-twitter-settings' ); ?>
				<?php do_settings_sections( 'ptt-publish-to-twitter' ); ?>

				<br /><input type="submit" value="Save Settings" name="submit" class="button-primary" />
			</form>
		</div>
	<?php
	}

	/**
	 * Register the settings.
	 */
	public function handle_settings() {
		add_settings_section( 'ptt-publish-to-twitter-main-settings', 'Account/Category Associations', array( $this, 'add_accounts_text' ), 'ptt-publish-to-twitter' );

		register_setting( 'ptt-publish-to-twitter-settings', 'ptt-publish-to-twitter-settings', array( $this, 'validate_settings' ) );
		add_settings_field( 'ptt-publish-to-twitter-main-settings-associations', 'Accounts and Associated Categories', array( $this, 'add_associations_input' ), 'ptt-publish-to-twitter', 'ptt-publish-to-twitter-main-settings' );

		add_settings_field( 'ptt-publish-to-twitter-main-settings-accounts', 'Available Twitter Accounts', array( $this, 'add_accounts_input' ), 'ptt-publish-to-twitter', 'ptt-publish-to-twitter-main-settings' );
	}

	/**
	 * Generic text.
	 */
	public function add_accounts_text() {
	?>
		<p>General Settings</p>
	<?php
	}


	/**
	 * Settings inputs.
	 *
	 * If there are authorized Twitter accounts, a category and Twitter account dropdown
	 * is displayed to allow the user to select a category to associate with a Twitter account.
	 * The user is also given buttons to add more pairings, as well as delete current pairings.
	 */
	public function add_associations_input() {
		if ( $this->_retrieve_twitter_accounts_query()->have_posts() ) : ?>
			<div id="ptt-twitter-category-pairings">

				<?php $this->_account_category_association_selects( $this->_retrieve_twitter_accounts_query() ); ?>



				<?php /*if ( $associations = get_option( 'ptt-publish-to-twitter-settings' ) ) : ?>
				<?php foreach ( $associations as $key => $pairing ) : ?>
					<?php $this->_account_category_association_selects( $twitter_accounts, $pairing['category_id'], $pairing['twitter_account_id'] ); ?>
					<?php endforeach; ?>
				<?php else : ?>
				<?php $this->_account_category_association_selects( $twitter_accounts ); ?>
				<?php endif; ?>
			</div>
			<a href="#add" class="ptt-add-another button">Add Association</a>
			<?php $this->_account_category_association_selects( $twitter_accounts, 0, 0, true ); */?>
		<?php else : ?>
			<p><em>You must authenticate one Twitter account in order to begin associating accounts with categories.</em></p>
		<?php endif;
	}
	
	/**
	 * Generates the category/Twitter account pairing interface.
	 *
	 * This function will generate two select elements: one for categories, and one for Twitter accounts.
	 * The function can also create a "dummy" version of the HTML that is cloned and inserted when a
	 * user chooses to add another pairing.
	 *
	 * @param array $twitter_accounts
	 * @param int $category_id
	 * @param int $twitter_account_id
	 * @param bool $dummy
	 */
	private function _account_category_association_selects( $twitter_accounts, $category_id = 0, $twitter_account_id = 0, $dummy = false ) {
	?>
		<div class="ptt-twitter-category-pairing"<?php if ( $dummy ) : ?> style="visibility:hidden;height:0;" id="ptt-twitter-category-pairing-clone"<?php endif; ?>>
			<em>Posts in:</em>&nbsp;

			<select class="ptt-chosen-terms" name="ptt-publish-to-twitter-settings[category][]" multiple="multiple" data-placeholder="Select some terms">
				<option value="-99"></option>

				<?php foreach ( get_object_taxonomies( 'ptt-twitter-account' ) as $taxonomy ) : ?>
					<optgroup label="Taxonomy : <?php echo esc_attr( $taxonomy ); ?>">
						<?php foreach ( get_terms( $taxonomy ) as $term ) : ?>
							<option value="<?php echo esc_attr( $taxonomy ) . ':' . absint( $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>

			</select>

			&nbsp;<em>automatically Tweet to:</em>&nbsp;
			<select class="ptt-chosen-accounts" name="ptt-publish-to-twitter-settings[twitter][]" multiple="multiple" data-placeholder="Select some accounts">
				<option value="-99"></option>
				<?php while( $twitter_accounts->have_posts() ) : $twitter_accounts->the_post(); ?>
					<option value="<?php echo absint( get_post_meta( get_the_ID(), '_ptt_user_id', true ) ); ?>" <?php selected( get_post_meta( get_the_ID(), '_ptt_user_id', true ), $twitter_account_id ); ?>>@<?php the_title(); ?></option>
				<?php endwhile; ?>
			</select>
			<a href="#delete" class="ptt-delete button">Delete</a>
		</div>
	<?php
	}


	/**
	 * Displays controls for the Twitter accounts.
	 *
	 * Lists all authorized Twitter accounts and gives the user a control to delete the accounts.
	 * The user can also click a link to authorize additional accounts.
	 */
	public function add_accounts_input() {
		$twitter_accounts = $this->_retrieve_twitter_accounts_query();

		if ( $twitter_accounts->have_posts() ) : while( $twitter_accounts->have_posts() ) : $twitter_accounts->the_post(); ?>
			<p>
				<strong>@<?php the_title(); ?></strong>
				&nbsp;&nbsp;<a href="<?php echo add_query_arg( array( 'ptt-twitter' => wp_create_nonce( 'ptt-delete-account' ), 'ptt-twitter-id' => get_the_ID() ), admin_url( '/options.php' ) ); ?>" class="button">Remove Account</a>
			</p>
		<?php endwhile; endif; ?>
		<a href="<?php echo add_query_arg( array( 'ptt-twitter' => wp_create_nonce( 'ptt-authenticate' ), 'action' => 'update' ),  admin_url( '/options.php' ) ); ?>">Authorize Twitter Account</a>
	<?php
	}

	/**
	 * Gets the WP_Query object containing Twitter Accounts.
	 *
	 * Since this is used in multiple places, it is stored as an instance variable to avoid unnecessary queries.
	 * If the object has yet to be obtained, it is generated and set to the instance var.
	 *
	 * @return object
	 */
	private function _retrieve_twitter_accounts_query() {
		if ( is_object( $this->_twitter_accounts ) )
			return $this->_twitter_accounts;

		$this->_twitter_accounts = new WP_Query( array(
			'post_type' => 'ptt-twitter-account',
			'posts_per_page' => apply_filters( 'ptt_add_accounts_input_ppp', 100 ),
			'no_found_rows' => false
		) );

		return $this->_twitter_accounts;
	}

	/**
	 * Setting validation.
	 *
	 * @param $input
	 * @return array
	 */
	public function validate_settings( $input ) {
		$sanitized = array();

		// Verify that we have the same number of inputs for category and twitter; if not, there is a major issue
		$number_of_inputs = ( isset( $input['category'] ) && isset( $input['twitter'] ) && count( $input['category'] ) == count( $input['twitter'] ) ) ? count( $input['category'] ) : 0;
		for ( $i = 0; $i < $number_of_inputs; $i++ ) {
			// Only accept valid categories and twitter account ids
			if ( '-1' != $input['category'][$i] && '-99' != $input['twitter'][$i] && is_object( get_term( $input['category'][$i], 'category' ) ) && array_key_exists( $input['twitter'][$i], get_option( 'ptt_twitter_accounts' ) ) ) {
				$sanitized[] = array(
					'category_id' => absint( $input['category'][$i] ),
					'twitter_account_id' => absint( $input['twitter'][$i] )
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Add JS.
	 */
	public function admin_enqueue_scripts() {
		wp_register_script( 'ptt-chosen', PTT_URL . 'js/chosen.jquery.min.js', array( 'jquery' ), '0.9.8', true );
		wp_enqueue_script( 'ptt-twitter-settings-page', PTT_URL . 'js/ptt-settings-page.js', array( 'jquery', 'ptt-chosen' ), '0.1', true );
	}

	/**
	 * Add CSS.
	 */
	public function admin_print_styles() {
		wp_enqueue_style( 'ptt-chosen', PTT_URL . 'css/chosen.css', array(), '0.9.8' );
	?>
		<style type="text/css">
			.ptt-twitter-category-pairing { margin-bottom: 5px; }
			.ptt-chosen-terms { width: 200px; }
			.ptt-chosen-accounts { width: 200px; }
		</style>
	<?php
	}

	/**
	 * Handles redirection to Twitter to authorize an account.
	 *
	 * Assuming
	 *
	 * @return bool
	 */
	public function get_authorization() {
		if ( ! isset( $_GET['ptt-twitter'] ) || ! wp_verify_nonce( $_GET['ptt-twitter'], 'ptt-authenticate' ) )
			return false;

		// Load library
		$this->_include_twitteroauth();

		// Build TwitterOAuth object with client credentials
		$connection = new pttTwitterOAuth( $this->_consumer_key, $this->_consumer_secret );

		// Get temporary credentials
		$request_token = $connection->getRequestToken( $this->_oauth_callback );

		// Get request tokens to save temporarily
		if ( ctype_alnum( $request_token['oauth_token'] ) && ctype_alnum( $request_token['oauth_token_secret'] ) ) {
			$temp_token = array(
				'oauth_token' => $request_token['oauth_token'],
				'oauth_token_secret' => $request_token['oauth_token_secret']
			);
			update_option( 'ptt_twitter_temp_token', $temp_token );
			// Redirect to Twitter only if the last request was successful
			if ( 200 == $connection->http_code ) {
				/// Build authorize URL and redirect user to Twitter
				$url = $connection->getAuthorizeURL( $temp_token['oauth_token'] );
				wp_redirect( $url );
				exit();
			} else {
				$this->_set_message_and_redirect( 'ptt-twitter', '102', 'There was an error connecting with Twitter. Please try again.', 'error' );
			}
		} else {
			$this->_set_message_and_redirect( 'ptt-twitter', '101', 'There was an error connecting with Twitter. Please try again.', 'error' );
		}
	}

	/**
	 * Save data when user is authenticated.
	 *
	 * The Twitter application is given the settings page as a callback URL. On "admin_init", this request is checked for.
	 * Assuming that the proper tokens are present, another request is made to get the final user details to save.
	 */
	public function process_twitter_tokens() {
		if ( isset( $_GET['ptt-action'] ) && 'ptt-publish-to-twitter-auth' == $_GET['ptt-action'] && isset( $_GET['oauth_token'] ) && ctype_alnum( $_GET['oauth_token'] ) && isset( $_GET['oauth_verifier'] ) && ctype_alnum( $_GET['oauth_verifier'] ) ) {
			$temp_token = get_option( 'ptt_twitter_temp_token' );
			if ( isset( $temp_token['oauth_token'] ) && ctype_alnum( $temp_token['oauth_token'] ) && isset( $temp_token['oauth_token_secret'] ) && ctype_alnum( $temp_token['oauth_token_secret'] ) ) {

				// We have all of the information needed to make the request to get the user details, so make it
				$this->_include_twitteroauth();
				$connection = new TwitterOAuth( $this->_consumer_key, $this->_consumer_secret, $temp_token['oauth_token'], $temp_token['oauth_token_secret'] );
				$access_token = $connection->getAccessToken( $_GET['oauth_verifier'] );
				$user = $connection->get( 'account/verify_credentials' );

				// Grab the user info and dump it in the DB
				if ( is_object( $user ) && is_array( $access_token ) ) {
					// Note that because Twitter's ID number can be so big, I'm not using absint in order to avoid a potential issue with 32-bit systems max integer range
					$user_id = isset( $access_token['user_id'] ) && is_numeric( $access_token['user_id'] ) && $access_token['user_id'] > 0 ? $access_token['user_id'] : 0;
					$screen_name = isset( $access_token['screen_name'] ) ? sanitize_title( $access_token['screen_name'] ) : false;
					$oauth_token = isset( $access_token['oauth_token'] ) ? $this->_validate_twitter_oauth_token( $access_token['oauth_token'], $user_id ) : false;
					$oauth_token_secret = isset( $access_token['oauth_token_secret'] ) && ctype_alnum( $access_token['oauth_token_secret'] ) ? $access_token['oauth_token_secret'] : false;

					// If everything is properly set, update the option
					if ( $user_id && $screen_name && $oauth_token && $oauth_token_secret ) {

						// Since we have authenticated, clean up the temp token
						delete_option( 'ptt_twitter_temp_token' );

						$post_data = array(
							'post_status' => 'publish',
							'post_type' => 'ptt-twitter-account',
							'post_title' => $screen_name
						);

						if ( $post_id = wp_insert_post( $post_data ) ) {
							$updated_oauth = update_post_meta( $post_id, '_ptt_oauth_token', $oauth_token ) ? true : false;
							$updated_oauth_secret = update_post_meta( $post_id, '_ptt_oauth_token_secret', $oauth_token_secret ) ? true : false;
							$updated_user_id = update_post_meta( $post_id, '_ptt_user_id', $user_id ) ? true : false;

							// Since the tokens are the most important part of this process, we need to verify that they saved
							if ( $updated_oauth && $updated_oauth_secret && $updated_user_id ) {
								$this->_set_message_and_redirect( 'ptt-twitter', '400', 'The user @' . $screen_name. ' has been authorized to use with this site!', 'updated' );
							} else {
								// Something went wrong; clean up the post and print error message
								wp_delete_post( $post_id, true );
								$this->_set_message_and_redirect( 'ptt-twitter', '205', 'There was an saving information about the Twitter Account to the database. Please try again.', 'error' );
							}
						} else {
							$this->_set_message_and_redirect( 'ptt-twitter', '204', 'The Twitter Account could not be saved at this time. Please try again.', 'error' );
						}
					} else {
						$this->_set_message_and_redirect( 'ptt-twitter', '201', 'There was an error authenticating with Twitter. Please try again.', 'error' );
					}
				} else {
					$this->_set_message_and_redirect( 'ptt-twitter', '202', 'There was an error authenticating with Twitter. Please try again.', 'error' );
				}
			} else {
				$this->_set_message_and_redirect( 'ptt-twitter', '203', 'There was an error authenticating with Twitter. Please try again.', 'error' );
			}
		}
	}

	/**
	 * Removes an authorized Twitter account from the option, as well as its association with any categories.
	 *
	 * @return bool
	 */
	public function remove_twitter_account() {
		if ( ! isset( $_GET['ptt-twitter'] ) || ! wp_verify_nonce( $_GET['ptt-twitter'], 'ptt-delete-account' ) )
			return false;

		if ( ! isset( $_GET['ptt-twitter-id'] ) && ! is_numeric( $_GET['ptt-twitter-id'] ) )
			return false;

		$id = absint( $_GET['ptt-twitter-id'] );

		if ( ! $id )
			return false;

		// Delete the post
		if ( wp_delete_post( $id ) )
			$this->_set_message_and_redirect( 'ptt-twitter', '400', 'The user has been unauthorized and associations have been removed.', 'updated' );
		else
			$this->_set_message_and_redirect( 'ptt-twitter', '301', 'The user was unable to be removed. Please try again.', 'error' );
	}

	/**
	 * Includes the twitteroauth library.
	 *
	 * Note that I have attempted to avoid potential conflicts by wrapping this in "class_exists". Since this is
	 * a popular library, I'm concerned others may be using it in VIP code or VIP plugins (e.g., Publicize). I have
	 * also wrapped the includes OAuth.php file call in the "class_exists" function. I would appreciate careful attention
	 * paid to how I'm including this as I cannot account for things I cannot mimick in dev.
	 */
	private function _include_twitteroauth() {
		if ( ! class_exists( 'TwitterOAuth' ) )
			require_once( __DIR__ . '/library/ptt-twitter-oauth.php' );
	}

	/**
	 * Validates the oauth_token recieved from Twitter.
	 *
	 * The pattern is {id}-{alphanum}.
	 *
	 * @param $oauth_token
	 * @param $id
	 * @return bool
	 */
	private function _validate_twitter_oauth_token( $oauth_token, $id ) {
		// Make sure the id is in the token; note that this will return false if the $id is not cast to string
		if ( false === strpos( $oauth_token, ( string ) $id ) )
			return false;

		// The next character after the id should be a "-"
		$sans_id = str_replace( $id, '', $oauth_token );
		if ( '-' != substr( $sans_id, 0, 1 ) )
			return false;

		// The rest of the token must be alphanumeric
		$sans_id_and_dash = substr( $sans_id, 1 );
		if ( ! ctype_alnum( $sans_id_and_dash ) )
			return false;

		return $oauth_token;
	}

	/**
	 * Adds an error to the twitter errors array.
	 *
	 * @param $setting
	 * @param $code
	 * @param $message
	 * @param $type
	 */
	private function _add_twitter_error( $setting, $code, $message, $type ) {
		$this->_twitter_errors[] = array(
			'setting' => $setting,
			'code' => $code,
			'message' => $message,
			'type' => $type
		);
	}

	/**
	 * Displays Twitter errors on settings page.
	 *
	 * This function is modelled after "settings_errors" (but properly escapes data!). Twitter
	 * errors, which are recorded as a transient, are obtained and displayed here. Once the error
	 * is displayed, the transient is deleted. Since the oAuth flow for the Twitter authentication requires
	 * movement between WordPress and Twitter, this mechanism allows for errors to be recorded and displayed across
	 * requests.
	 *
	 * @return mixed
	 */
	public function display_twitter_errors() {
		$settings_errors = get_transient( 'ptt-twitter-error-' . get_current_user_id() );

		if ( ! is_array( $settings_errors ) ) return;

		delete_transient( 'ptt-twitter-error-' . get_current_user_id() );
		$output = '';
		foreach ( $settings_errors as $key => $details ) {
			$css_id = 'setting-error-' . absint( $details['code'] );
			$css_class = esc_html( $details['type'] ) . ' settings-error';
			$output .= "<div id='$css_id' class='$css_class'> \n";
			$output .= "<p><strong>" . wp_kses_data( $details['message'] ) . "</strong></p>";
			$output .= "</div> \n";
		}
		echo $output;
	}

	/**
	 * Records error and redirects to the main settings page.
	 *
	 * @param $setting
	 * @param $code
	 * @param $message
	 * @param $type
	 */
	private function _set_message_and_redirect( $setting, $code, $message, $type ) {
		$this->_add_twitter_error( $setting, $code, $message, $type );

		// Appending the user_id to make the error messages unique to the user.
		set_transient( 'ptt-twitter-error-' . get_current_user_id(), $this->_twitter_errors, 300 );
		wp_redirect( $this->_settings_page_url );
		exit();
	}
}
$pttSettingsPage = new pttSettingsPage();