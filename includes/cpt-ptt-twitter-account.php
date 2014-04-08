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
				'name'               => __( 'Twitter Accounts',                   'tweetpublish' ),
				'singular_name'      => __( 'Twitter Account',                    'tweetpublish' ),
				'add_new'            => _x( 'Add New',                            'Label for "add new twitter account"', 'tweetpublish' ),
				'add_new_item'       => __( 'Add New Twitter Account',            'tweetpublish' ),
				'edit_item'          => __( 'Edit Twitter Account',               'tweetpublish' ),
				'new_item'           => __( 'New Twitter Account',                'tweetpublish' ),
				'all_items'          => __( 'All Twitter Accounts',               'tweetpublish' ),
				'view_item'          => __( 'View Twitter Account',               'tweetpublish' ),
				'search_items'       => __( 'Search Twitter Accounts',            'tweetpublish' ),
				'not_found'          => __( 'No Twitter accounts found',          'tweetpublish' ),
				'not_found_in_trash' => __( 'No Twitter accounts found in Trash', 'tweetpublish' ),
				'menu_name'          => __( 'Twitter Accounts',                   'tweetpublish' ),
			),
			'description'         => __( 'Twitter accounts associated with the site.', 'tweetpublish' ),
			'public'              => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'comments' ),
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
