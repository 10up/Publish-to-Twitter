<?php
/**
 * Contains all functionality related to the ptt-twitter-account CPT.
 */
class pttTwitterAccount {

	/**
	 * Initiate basic actions.
	 */
	public function __construct() {
		// Get the basics setup
		add_action( 'init', array( $this, 'register_post_type' ), 100 );	// Registering late to capture taxonomies

		// Remove the "nav_menu" taxonomy
		add_filter( 'ptt_taxonomies', array( $this, 'ptt_taxonomy_names' ) );
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type() {
		$args = array(
			'labels'              => array(
				'name'               => __( 'Twitter Accounts', 'ptt-publish-to-twitter' ),
				'singular_name'      => __( 'Twitter Account', 'ptt-publish-to-twitter' ),
				'add_new'            => _x( 'Add New', 'Label for "add new twitter account"', 'ptt-publish-to-twitter' ),
				'add_new_item'       => __( 'Add New Twitter Account', 'ptt-publish-to-twitter' ),
				'edit_item'          => __( 'Edit Twitter Account', 'ptt-publish-to-twitter' ),
				'new_item'           => __( 'New Twitter Account', 'ptt-publish-to-twitter' ),
				'all_items'          => __( 'All Twitter Accounts', 'ptt-publish-to-twitter' ),
				'view_item'          => __( 'View Twitter Account', 'ptt-publish-to-twitter' ),
				'search_items'       => __( 'Search Twitter Accounts', 'ptt-publish-to-twitter' ),
				'not_found'          => __( 'No Twitter accounts found', 'ptt-publish-to-twitter' ),
				'not_found_in_trash' => __( 'No Twitter accounts found in Trash', 'ptt-publish-to-twitter' ),
				'parent_item_colon'  => '',
				'menu_name'          => 'Twitter Accounts'
			),
			'description'         => 'Twitter accounts associated with the site.',
			'public'              => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'comments' ), // @todo: adding comments to use as a log of tweets. consider if I really want to do this or not.
			'taxonomies'          => apply_filters( 'ptt_taxonomies', get_taxonomies() )
		);

		register_post_type( 'ptt-twitter-account', apply_filters( 'ptt_register_post_type', $args ) );
	}

	/**
	 * Removes the "nav_menu" taxonomy from the list of taxonomies.
	 *
	 * @param $taxonomy_names
	 * @return array
	 */
	public function ptt_taxonomy_names( $taxonomy_names ) {
		$cleaned_taxonomies = array();

		foreach ( $taxonomy_names as $taxonomy_name ) {
			$taxonomy = get_taxonomy( $taxonomy_name );
			if ( $taxonomy && $taxonomy->show_ui ) {
				$cleaned_taxonomies[] = $taxonomy_name;
			}
		}

		return $cleaned_taxonomies;
	}
}
$pttTwitterAccount = new pttTwitterAccount();
