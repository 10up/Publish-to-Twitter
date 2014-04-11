/*global jQuery */
(function ( window, $, undefined ) {
	var document = window.document;

	// Adds another row to list of category/twitter account pairings
	$( '.ptt-add-another' ).on( 'click', function () {
		var $new_row = $( '#ptt-twitter-category-pairing-clone' ).clone(),
			$div = $( '#ptt-twitter-category-pairings' );

		$new_row.css( {
			visibility: 'visible',
			height    : ''
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
	$( '.ptt-chosen-terms' ).select2( {
		multiple          : true,
		minimumInputLength: 2,
		ajax              : {
			url     : window.ajaxurl,
			dataType: 'json',
			data    : function ( term, page ) {
				return {
					q     : term,
					action: 'ptt-select',
					limit : 5
				};
			},
			results : function ( data, page ) {
				return data;
			}
		},
		initSelection: function( element, callback ) {
			var data = [];
			$( element.val().split( ',' ) ).each( function() {
				var parts = this.split( ':' );
				data.push( { id: parts[0] + ':' + parts[1], text: parts[2] } );
			} );
			callback( data );
		}
	} );

	// Setup Chosen for Accounts
	$( '.ptt-chosen-accounts' ).select2( {
		formatNoMatches: 'No accounts match'
	} );
})( this, jQuery );