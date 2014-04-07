/*global jQuery */
(function ( window, $, undefined ) {
	var document = window.document;

	// Adds another row to list of category/twitter account pairings
	$( '.ptt-add-another' ).on( 'click', function () {
		var $new_row = $( '#ptt-twitter-category-pairing-clone' ).clone(),
			$div = $( '#ptt-twitter-category-pairings' );

		$new_row.css( {
			visibility : 'visible',
			height     : ''
		} ).appendTo( $div );
	} );

	// Removes a pairing row
	$( '.ptt-delete' ).on( 'click', function () {
		var $this = $( this ),
			$parent_div = $this.parents( '.ptt-twitter-category-pairing' );

		$parent_div.fadeOut( 'fast', function () {
			$parent_div.remove();
		} );
		return false;
	} );

	// Setup Chosen for Terms
	$( '.ptt-chosen-terms' ).chosen( {
		no_results_text : 'No terms match'
	} );

	// Setup Chosen for Accounts
	$( '.ptt-chosen-accounts' ).chosen( {
		no_results_text : 'No accounts match'
	} );
})( this, jQuery );