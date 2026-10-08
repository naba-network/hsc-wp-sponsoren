/*
 * [hsc-sponsoren-grid]: fades each item in once it scrolls into view, one after the other.
 * Images are lazy-loaded natively (loading="lazy"). Without JS the items are simply visible.
 */
( function () {
	'use strict';

	var STAGGER_MS = 90;

	/**
	 * Resolves once the item's image (if any) has loaded or failed.
	 *
	 * @param {Element} item Grid item.
	 * @return {Promise<void>}
	 */
	function imageReady( item ) {
		var img = item.querySelector( '.hsc-grid__img' );
		if ( ! img || img.complete ) {
			return Promise.resolve();
		}
		return new Promise( function ( resolve ) {
			img.addEventListener( 'load', resolve, { once: true } );
			img.addEventListener( 'error', resolve, { once: true } );
		} );
	}

	function reveal( item, delay ) {
		item.style.setProperty( '--hsc-delay', delay + 'ms' );
		item.classList.add( 'is-visible' );
	}

	function init( grid ) {
		var items = Array.prototype.slice.call( grid.querySelectorAll( '.hsc-grid__item' ) );
		var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		if ( ! ( 'IntersectionObserver' in window ) || reduced ) {
			items.forEach( function ( item ) {
				item.classList.add( 'is-visible' );
			} );
			return;
		}

		var observer = new IntersectionObserver( function ( entries ) {
			// Items entering together are revealed in DOM order, each a bit later than the previous one.
			var entering = entries
				.filter( function ( entry ) {
					return entry.isIntersecting;
				} )
				.map( function ( entry ) {
					return entry.target;
				} )
				.sort( function ( a, b ) {
					return items.indexOf( a ) - items.indexOf( b );
				} );

			entering.forEach( function ( item, index ) {
				observer.unobserve( item );
				imageReady( item ).then( function () {
					reveal( item, index * STAGGER_MS );
				} );
			} );
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 } );

		items.forEach( function ( item ) {
			observer.observe( item );
		} );
	}

	document.querySelectorAll( '.hsc-grid--reveal' ).forEach( init );
}() );
