( function ( $ ) {
	'use strict';

	var $list = $( '#hsc-order-list' );

	$( '#hsc-order-term' ).on( 'change', function () {
		this.form.submit();
	} );

	$list.sortable( {
		handle: '.hsc-order-handle',
		placeholder: 'hsc-order-placeholder',
		axis: 'y'
	} );

	// Keyboard-friendly alternative to dragging.
	$list.on( 'click', '.hsc-order-up', function () {
		var $item = $( this ).closest( 'li' );
		$item.prev().before( $item );
	} );
	$list.on( 'click', '.hsc-order-down', function () {
		var $item = $( this ).closest( 'li' );
		$item.next().after( $item );
	} );
}( jQuery ) );
