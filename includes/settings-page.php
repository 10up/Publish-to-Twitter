<?php

class pttSettingsPage {

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
	 * Flag whether or not consumer keys are hardcoded into the application.
	 *
	 * @var bool
	 */
	private $_keys_hardcoded = false;

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
		add_action( 'admin_menu',         array( $this, 'add_submenu_page'  ) );
		add_action( 'admin_init',         array( $this, 'handle_settings'   ) );
		add_action( 'wp_ajax_ptt-select', array( $this, 'ajax_autocomplete' ) );

		// Twitter specific actions
		add_action( 'admin_init',    array( $this, 'get_authorization'      ) );
		add_action( 'admin_init',    array( $this, 'process_twitter_tokens' ) );
		add_action( 'admin_init',    array( $this, 'remove_twitter_account' ) );
		add_action( 'admin_notices', array( $this, 'display_twitter_errors' ) );

		// Styles and JS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_print_styles',    array( $this, 'admin_print_styles'    ) );

		// Set instance vars
		$this->_oauth_callback    = admin_url( '/options.php?ptt-action=ptt-publish-to-twitter-auth' );
		$this->_settings_page_url = admin_url( '/options-general.php?page=ptt-publish-to-twitter' );

		if ( defined( 'PTT_CONSUMER_KEY' ) ) {
			$this->_consumer_key = PTT_CONSUMER_KEY;
			$this->_keys_hardcoded = true;
		} else {
			$this->_consumer_key = get_option( '_ptt_consumer_key' );
		}

		if ( defined( 'PTT_CONSUMER_SECRET' ) ) {
			$this->_consumer_secret = PTT_CONSUMER_SECRET;
			$this->_keys_hardcoded = true;
		} else {
			$this->_consumer_secret = get_option( '_ptt_consumer_secret' );
		}
	}

	/**
	 * Add the submenu under Settings.
	 */
	public function add_submenu_page() {
		$this->_hook = add_submenu_page(
			'options-general.php',
			esc_html__( 'Publish to Twitter Settings', 'tweetpublish' ),
			esc_html__( 'Publish to Twitter', 'tweetpublish' ),
			'manage_options',
			'ptt-publish-to-twitter',
			array( $this, 'submenu_page' )
		);
	}

	/**
	 * Write the submenu page.
	 */
	public function submenu_page() {
		?>
    <div class="wrap">
		<?php screen_icon(); ?>
        <h2><?php esc_html_e( 'Publish to Twitter', 'tweetpublish' ); ?></h2>

        <form action="options.php" method="post">

			<?php settings_fields( 'ptt-publish-to-twitter-settings' ); ?>
			<?php do_settings_sections( 'ptt-publish-to-twitter' ); ?>

			<?php wp_nonce_field( 'ptt-save-associations', 'ptt-save-associations-nonce' ); ?>
            <br/><input type="submit" value="<?php esc_attr_e( 'Save Settings', 'tweetpublish' ); ?>" name="submit" class="button-primary"/>
        </form>
    </div>
	<?php
	}

	/**
	 * Register the settings.
	 */
	public function handle_settings() {
		if ( false === $this->_keys_hardcoded ) {
			add_settings_section( 'ptt-publish-to-twitter-keys', esc_html__( 'Application Keys', 'tweetpublish' ), array( $this, 'add_keys_text' ), 'ptt-publish-to-twitter' );

			add_settings_field( 'ptt-publish-to-twitter-consumer-key',    esc_html__( 'API Key',    'tweetpublish' ), array( $this, 'add_consumer_key' ),    'ptt-publish-to-twitter', 'ptt-publish-to-twitter-keys' );
			add_settings_field( 'ptt-publish-to-twitter-consumer-secret', esc_html__( 'API Secret', 'tweetpublish' ), array( $this, 'add_consumer_secret' ), 'ptt-publish-to-twitter', 'ptt-publish-to-twitter-keys' );
		}

		add_settings_section( 'ptt-publish-to-twitter-main-settings', esc_html__( 'Account/Category Associations', 'tweetpublish' ), array( $this, 'add_accounts_text' ), 'ptt-publish-to-twitter' );

		register_setting( 'ptt-publish-to-twitter-settings', 'ptt-publish-to-twitter-settings', array( $this, 'validate_settings' ) );
		add_settings_field( 'ptt-publish-to-twitter-main-settings-associations', esc_html__( 'Accounts and Associated Categories', 'tweetpublish' ), array( $this, 'add_associations_input' ), 'ptt-publish-to-twitter', 'ptt-publish-to-twitter-main-settings' );

		add_settings_field( 'ptt-publish-to-twitter-main-settings-accounts', esc_html__( 'Available Twitter Accounts', 'tweetpublish' ), array( $this, 'add_accounts_input' ), 'ptt-publish-to-twitter', 'ptt-publish-to-twitter-main-settings' );
	}

	/**
	 * Generic text.
	 */
	public function add_accounts_text() {
		?>
    <p><?php esc_html_e( 'General Settings', 'tweetpublish' ); ?></p>
	<?php
	}

	/**
	 * Generic text.
	 */
	public function add_keys_text() {
		?>
        <p><?php esc_html_e( 'OAuth Settings', 'tweetpublish' ); ?></p>
	    <p class="description"><?php echo sprintf( esc_html__( 'You will need to create %san application on Twitter%s to retrieve your API keys.', 'tweetpublish' ), '<a href="https://dev.twitter.com/apps/">', '</a>' ); ?></p>
	<?php
	}


	/**
	 * Settings inputs.
	 *
	 * If there are authorized Twitter accounts, a category and Twitter account dropdown
	 * is displayed to allow the user to select a category to associate with a Twitter account.
	 * The user is also given buttons to add more pairings, as well as delete current pairings.
	 *
	 * @return void;
	 */
	public function add_associations_input() {
		$twitter_accounts = $this->_retrieve_twitter_accounts_query();

		if ( $twitter_accounts->have_posts() ) : ?>
			<div id="ptt-twitter-category-pairings">
				<?php while ( $twitter_accounts->have_posts() ) : $twitter_accounts->the_post(); ?>
				<?php $this->_account_category_association_selects( get_the_ID() ); ?>
				<?php endwhile; ?>



			<?php /*if ( $associations = get_option( 'ptt-publish-to-twitter-settings' ) ) : ?>
				<?php foreach ( $associations as $key => $pairing ) : ?>
					<?php $this->_account_category_association_selects( $twitter_accounts, $pairing['category_id'], $pairing['twitter_account_id'] ); ?>
					<?php endforeach; ?>
				<?php else : ?>
				<?php $this->_account_category_association_selects( $twitter_accounts ); ?>
				<?php endif; ?>
			</div>
			<a href="#add" class="ptt-add-another button">Add Association</a>
			<?php $this->_account_category_association_selects( $twitter_accounts, 0, 0, true ); */
			?>
			<?php else : ?>
        <p><em><?php esc_html_e( 'You must authenticate one Twitter account in order to begin associating accounts with categories.', 'tweetpublish' ); ?></em>
        </p>
			<?php endif;
	}

	/**
	 * Print the consumer key field.
 	 */
	public function add_consumer_key() {
		?>
		<input name="_ptt_consumer_key" id="_ptt_consumer_key" type="text" class="regular-text" value="<?php echo esc_attr( false === $this->_consumer_key ? '' : $this->_consumer_key ); ?>" />
	<?php
	}

	/**
	 * Print the consumer secret field.
	 */
	public function add_consumer_secret() {
		?>
        <input name="_ptt_consumer_secret" id="_ptt_consumer_secret" type="text" class="regular-text" value="<?php echo esc_attr( false === $this->_consumer_secret ? '' : $this->_consumer_secret ); ?>" />
	<?php
	}

	/**
	 * Generates the category/Twitter account pairing interface.
	 *
	 * This function will generate two select elements: one for categories, and one for Twitter accounts.
	 * The function can also create a "dummy" version of the HTML that is cloned and inserted when a
	 * user chooses to add another pairing.
	 *
	 * @param int $twitter_account
	 */
	private function _account_category_association_selects( $twitter_account ) {
		$taxonomies       = get_object_taxonomies( 'ptt-twitter-account' );
		$associated_terms = wp_get_object_terms( $twitter_account, $taxonomies );

		$values = array();
		foreach( $associated_terms as $term ) {
			if ( empty( $term->term_id ) || empty( $term->name ) ) {
				continue;
			}
			$values[] = $term->taxonomy . ':' . $term->term_id . ':' . $term->name;
		}
		$values = array_map( 'esc_attr', $values );
		?>
    <div class="ptt-twitter-category-pairing"><p>
        <em><?php esc_html_e( 'Posts in:', 'tweetpublish' ); ?></em>&nbsp;

        <input type="hidden" class="ptt-chosen-terms" name="ptt-associations[terms][<?php echo absint( $twitter_account ); ?>]"
                multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select some terms', 'tweetpublish' ); ?>" value="<?php echo implode( ',', $values ) ?>"/>

        &nbsp;<em><?php esc_html_e( 'automatically Tweet to:', 'tweetpublish' ); ?></em>&nbsp;
        <select multiple class="ptt-chosen-accounts" name="ptt-associations[accounts][0][]" data-placeholder="<?php esc_attr_e( 'Select an account', 'tweetpublish' ); ?>">
            <option value="-99"></option>
			<?php $ids = wp_list_pluck( $this->_retrieve_twitter_accounts_query()->posts, 'ID' ); $titles = wp_list_pluck( $this->_retrieve_twitter_accounts_query()->posts, 'post_title' ); ?>
			<?php for ( $i = 0; $i < count( $ids ); $i ++ ) : ?>
            <option value="<?php echo absint( $ids[$i] ); ?>" <?php selected( $ids[$i], $twitter_account ); ?>>
                @<?php echo wp_strip_all_tags( $titles[$i] ); ?></option>
			<?php endfor; ?>
        </select>
    </p></div>
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

		if ( $twitter_accounts->have_posts() ) : while ( $twitter_accounts->have_posts() ) : $twitter_accounts->the_post(); ?>
        <p>
            <strong>@<?php the_title(); ?></strong>
            &nbsp;&nbsp;<a
                href="<?php echo add_query_arg( array( 'ptt-twitter' => wp_create_nonce( 'ptt-delete-account' ), 'ptt-twitter-id' => get_the_ID() ), admin_url( '/options.php' ) ); ?>"
                class="button"><?php esc_html_e( 'Remove Account', 'tweetpublish' ); ?></a>
        </p>
			<?php endwhile; endif; ?>
    <a href="<?php echo add_query_arg( array( 'ptt-twitter' => wp_create_nonce( 'ptt-authenticate' ), 'action' => 'update' ), admin_url( '/options.php' ) ); ?>">
	    <?php esc_html_e( 'Authorize Twitter Account', 'tweetpublish' ); ?>
    </a>
	<p class="description">
		<?php esc_html_e( 'If you are currently logged in to Twitter, you will be authorizing your current account.', 'tweetpublish' ); ?>
	</p>
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

		$this->_twitter_accounts = new WP_Query(
			array(
			     'post_type'      => 'ptt-twitter-account',
			     'posts_per_page' => apply_filters( 'ptt_add_accounts_input_ppp', 100 ),
			     'no_found_rows'  => false
			)
		);

		return $this->_twitter_accounts;
	}

	/**
	 * Setting validation.
	 *
	 * @param $input
	 *
	 * @return array
	 */
	public function validate_settings( $input ) {
		$sanitized = array();

		$this->save_oauth();
		$this->save_associations();

		// Verify that we have the same number of inputs for category and twitter; if not, there is a major issue
		$number_of_inputs = ( isset( $input['category'] ) && isset( $input['twitter'] ) && count( $input['category'] ) == count( $input['twitter'] ) ) ? count( $input['category'] ) : 0;
		for ( $i = 0; $i < $number_of_inputs; $i ++ ) {
			// Only accept valid categories and twitter account ids
			if ( '-1' != $input['category'][$i] && '-99' != $input['twitter'][$i] && is_object( get_term( $input['category'][$i], 'category' ) ) && array_key_exists( $input['twitter'][$i], get_option( 'ptt_twitter_accounts' ) ) ) {
				$sanitized[] = array(
					'category_id'        => absint( $input['category'][$i] ),
					'twitter_account_id' => absint( $input['twitter'][$i] )
				);
			}
		}

		return false;
	}

	public function save_associations() {
		if ( ! isset( $_POST['ptt-save-associations-nonce'] ) || ! wp_verify_nonce( $_POST['ptt-save-associations-nonce'], 'ptt-save-associations' ) ) {
			return false;
		}

		if ( ! isset( $_POST['ptt-associations'] ) || ! isset( $_POST['ptt-associations']['terms'] ) || ! isset( $_POST['ptt-associations']['accounts'] ) ) {
			return false;
		}

		$associations_to_save = array();
		$non_hierarchical_terms = array();

		/**
		 * Sort input into array of the following format:
		 *
		 * array
		 *     {$post_id} =>
		 *         array
		 *             {$taxonomy} =>
		 *                 array
		 *                     0 => {$term_id}
		 *                     1 => {$term_id}
		 *             {$taxonomy} =>
		 *                 array
		 *                     0 => {$term_id}
		 *                     1 => {$term_id}
		 */
		foreach ( $_POST['ptt-associations']['accounts'] as $a_key => $a_value ) {
			foreach ( $a_value as $a_sub_key => $twitter_account_id ) {
				$terms = explode( ',', $_POST['ptt-associations']['terms'][$twitter_account_id] );

				foreach ( $terms as $term ) {
					$term_pieces = explode( ':', $term );

					if ( ! isset( $term_pieces[1] ) ) {
						continue;
					}

					if ( ! isset( $non_hierarchical_terms[ $term_pieces[0] ] ) ) {
						$non_hierarchical_terms[ $term_pieces[0] ] = array();
					}

					$associations_to_save[$twitter_account_id][$term_pieces[0]][] = $term_pieces[1];
					$non_hierarchical_terms[ $term_pieces[0] ][ $term_pieces[1] ] = $term_pieces[2];
				}
			}
		}

		/**
		 * Validate the post IDs, term IDs and associate terms with posts (i.e., Twitter Accounts)
		 */
		foreach ( $associations_to_save as $post_id => $associations ) {

			// Verify post (Twitter Account)
			if ( get_post( $post_id ) ) {
				foreach ( $associations as $taxonomy => $term_ids ) {
					$taxonomy_obj = get_taxonomy( $taxonomy );

					// Remove existing relationships so we can reset things.
					wp_delete_object_term_relationships( $post_id, $taxonomy );

					if ( $taxonomy_obj->hierarchical ) {
						$term_ids = array_map( 'intval', $term_ids );
						wp_set_object_terms( $post_id, $term_ids, $taxonomy );
					} else {
						// For non-hierarchical taxonomies, we need to grab the term names
						$terms = array();

						foreach( $term_ids as $term_id ) {
							$terms[] = $non_hierarchical_terms[ $taxonomy ][ $term_id ];
						}

						wp_set_object_terms( $post_id, $terms, $taxonomy );
					}
				}
			}
		}

		unset( $_POST['ptt-associations'] );
		unset( $_POST['ptt-associations-nonce'] );

		return $_POST;
	}

	private function save_oauth() {
		if ( ! isset( $_POST['_ptt_consumer_key'] ) || ! isset( $_POST['_ptt_consumer_secret'] ) ) {
			return false;
		}

		update_option( '_ptt_consumer_key', sanitize_text_field( $_POST['_ptt_consumer_key'] ) );
		update_option( '_ptt_consumer_secret', sanitize_text_field( $_POST['_ptt_consumer_secret'] ) );
	}

	/**
	 * Add JS.
	 */
	public function admin_enqueue_scripts() {
		if ( 'settings_page_ptt-publish-to-twitter' !== get_current_screen()->base ) {
			return;
		}

		// Denqueue select2 so we don't run into conflicts
		wp_dequeue_script( 'select2' );

		wp_register_script( 'ptt-select', PTT_URL . 'js/select2/select2.js', array( 'jquery' ), '3.4.6', true );

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'ptt-twitter-settings-page', PTT_URL . 'js/ptt-settings-page.dev.js', array( 'jquery', 'ptt-select' ), PTT_VERSION, true );
		} else {
			wp_enqueue_script( 'ptt-twitter-settings-page', PTT_URL . 'js/ptt-settings-page.js', array( 'jquery', 'ptt-select' ), PTT_VERSION, true );
		}
	}

	/**
	 * Add CSS.
	 */
	public function admin_print_styles() {
		wp_enqueue_style( 'ptt-select', PTT_URL . 'js/select2/select2.css', array(), '3.4.6' );
		?>
    <style type="text/css">
        .ptt-twitter-category-pairing {
            margin-bottom: 5px;
        }

        .ptt-chosen-terms {
            width: 200px;
        }

        .ptt-chosen-accounts {
            width: 200px;
        }
        .ptt-twitter-category-pairing em,
        .ptt-twitter-category-pairing .chzn-container {
	        vertical-align: text-bottom;
        }
        .ptt-twitter-category-pairing .chzn-container-multi .chzn-choices .search-field input {
		    height: 26px;
	    }
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
		if ( ! isset( $_GET['ptt-twitter'] ) || ! wp_verify_nonce( $_GET['ptt-twitter'], 'ptt-authenticate' ) ) {
			return false;
		}

		// Load library
		$this->_include_twitteroauth();

		// Build TwitterOAuth object with client credentials
		$connection = new pttTwitterOAuth( $this->_consumer_key, $this->_consumer_secret );

		// Get temporary credentials
		$request_token = $connection->getRequestToken( $this->_oauth_callback );

		// Get request tokens to save temporarily
		if ( ctype_alnum( $request_token['oauth_token'] ) && ctype_alnum( $request_token['oauth_token_secret'] ) ) {
			$temp_token = array(
				'oauth_token'        => $request_token['oauth_token'],
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
				$this->_set_message_and_redirect( 'ptt-twitter', '102', esc_html__( 'There was an error connecting with Twitter. Please try again.', 'tweetpublish' ), 'error' );
			}
		} else {
			$this->_set_message_and_redirect( 'ptt-twitter', '101', esc_html__( 'There was an error connecting with Twitter. Please try again.', 'tweetpublish' ), 'error' );
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
				$connection   = new pttTwitterOAuth( $this->_consumer_key, $this->_consumer_secret, $temp_token['oauth_token'], $temp_token['oauth_token_secret'] );
				$access_token = $connection->getAccessToken( $_GET['oauth_verifier'] );
				$user         = $connection->get( 'account/verify_credentials' );

				// Grab the user info and dump it in the DB
				if ( is_object( $user ) && is_array( $access_token ) ) {
					// Note that because Twitter's ID number can be so big, I'm not using absint in order to avoid a potential issue with 32-bit systems max integer range
					$user_id            = isset( $access_token['user_id'] ) && is_numeric( $access_token['user_id'] ) && $access_token['user_id'] > 0 ? $access_token['user_id'] : 0;
					$screen_name        = isset( $access_token['screen_name'] ) ? sanitize_title( $access_token['screen_name'] ) : false;
					$oauth_token        = isset( $access_token['oauth_token'] ) ? $this->_validate_twitter_oauth_token( $access_token['oauth_token'], $user_id ) : false;
					$oauth_token_secret = isset( $access_token['oauth_token_secret'] ) && ctype_alnum( $access_token['oauth_token_secret'] ) ? $access_token['oauth_token_secret'] : false;

					// If everything is properly set, update the option
					if ( $user_id && $screen_name && $oauth_token && $oauth_token_secret ) {

						// Since we have authenticated, clean up the temp token
						delete_option( 'ptt_twitter_temp_token' );

						$post_data = array(
							'post_status' => 'publish',
							'post_type'   => 'ptt-twitter-account',
							'post_title'  => $screen_name
						);

						if ( $post_id = wp_insert_post( $post_data ) ) {
							$updated_oauth        = update_post_meta( $post_id, '_ptt_oauth_token', $oauth_token ) ? true : false;
							$updated_oauth_secret = update_post_meta( $post_id, '_ptt_oauth_token_secret', $oauth_token_secret ) ? true : false;
							$updated_user_id      = update_post_meta( $post_id, '_ptt_user_id', $user_id ) ? true : false;

							// Since the tokens are the most important part of this process, we need to verify that they saved
							if ( $updated_oauth && $updated_oauth_secret && $updated_user_id ) {
								$this->_set_message_and_redirect( 'ptt-twitter', '400', sprintf( esc_html__( 'The user @%s has been authorized to use with this site!', 'tweetpublish' ), $screen_name ), 'updated' );
							} else {
								// Something went wrong; clean up the post and print error message
								wp_delete_post( $post_id, true );
								$this->_set_message_and_redirect( 'ptt-twitter', '205', esc_html__( 'There was an saving information about the Twitter Account to the database. Please try again.', 'tweetpublish' ), 'error' );
							}
						} else {
							$this->_set_message_and_redirect( 'ptt-twitter', '204', esc_html__( 'The Twitter Account could not be saved at this time. Please try again.', 'tweetpublish' ), 'error' );
						}
					} else {
						$this->_set_message_and_redirect( 'ptt-twitter', '201', esc_html__( 'There was an error authenticating with Twitter. Please try again.', 'tweetpublish' ), 'error' );
					}
				} else {
					$this->_set_message_and_redirect( 'ptt-twitter', '202', esc_html__( 'There was an error authenticating with Twitter. Please try again.', 'tweetpublish' ), 'error' );
				}
			} else {
				$this->_set_message_and_redirect( 'ptt-twitter', '203', esc_html__( 'There was an error authenticating with Twitter. Please try again.', 'tweetpublish' ), 'error' );
			}
		}
	}

	/**
	 * Removes an authorized Twitter account from the option, as well as its association with any categories.
	 *
	 * @return bool
	 */
	public function remove_twitter_account() {
		if ( ! isset( $_GET['ptt-twitter'] ) || ! wp_verify_nonce( $_GET['ptt-twitter'], 'ptt-delete-account' ) ) {
			return false;
		}

		if ( ! isset( $_GET['ptt-twitter-id'] ) && ! is_numeric( $_GET['ptt-twitter-id'] ) ) {
			return false;
		}

		$id = absint( $_GET['ptt-twitter-id'] );

		if ( ! $id )
			return false;

		// Delete the post
		if ( wp_delete_post( $id ) ) {
			$this->_set_message_and_redirect( 'ptt-twitter', '400', esc_html__( 'The user has been unauthorized and associations have been removed.', 'tweetpublish' ), 'updated' );
		} else {
			$this->_set_message_and_redirect( 'ptt-twitter', '301', esc_html__( 'The user was unable to be removed. Please try again.', 'tweetpublish' ), 'error' );
		}
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
		if ( ! class_exists( 'pttTwitterOAuth' ) ) {
			require_once( __DIR__ . '/library/ptt-twitter-oauth.php' );
		}
	}

	/**
	 * Validates the oauth_token recieved from Twitter.
	 *
	 * The pattern is {id}-{alphanum}.
	 *
	 * @param $oauth_token
	 * @param $id
	 *
	 * @return bool
	 */
	private function _validate_twitter_oauth_token( $oauth_token, $id ) {
		// Make sure the id is in the token; note that this will return false if the $id is not cast to string
		if ( false === strpos( $oauth_token, ( string ) $id ) ) {
			return false;
		}

		// The next character after the id should be a "-"
		$sans_id = str_replace( $id, '', $oauth_token );
		if ( '-' != substr( $sans_id, 0, 1 ) ) {
			return false;
		}

		// The rest of the token must be alphanumeric
		$sans_id_and_dash = substr( $sans_id, 1 );
		if ( ! ctype_alnum( $sans_id_and_dash ) ) {
			return false;
		}

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
			'code'    => $code,
			'message' => $message,
			'type'    => $type,
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

		if ( ! is_array( $settings_errors ) ) {
			return;
		}

		delete_transient( 'ptt-twitter-error-' . get_current_user_id() );
		$output = '';
		foreach ( $settings_errors as $key => $details ) {
			$css_id    = 'setting-error-' . absint( $details['code'] );
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

	/**
	 * AJAX Autocomplete
	 */
	public function ajax_autocomplete() {
		$search_term = sanitize_text_field( $_REQUEST['q'] );
		$results = array();

		foreach ( get_object_taxonomies( 'ptt-twitter-account' ) as $taxonomy ) {
			$children = array();

			$terms = get_terms(
				$taxonomy,
				array(
					'hide_empty' => false,
				    'name__like' => $search_term,
				    'number'     => absint( $_REQUEST['limit'] ),
				)
			);

			foreach( $terms as $term ) {
				$children[] = array( 'id' => $taxonomy . ':' . $term->term_id . ':' . $term->name, 'text' => $term->name, );
			}

			$results[] = array( 'text' => $taxonomy, 'children' => $children );
		}

		wp_send_json( array( 'results' => $results ) );
	}
}

$pttSettingsPage = new pttSettingsPage();