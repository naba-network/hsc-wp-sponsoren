( function () {
	'use strict';

	var cfg = window.hscSponsorBulk;
	var drop = document.getElementById( 'hsc-bulk-drop' );
	var input = document.getElementById( 'hsc-bulk-files' );
	var select = document.getElementById( 'hsc-bulk-term' );
	var queueEl = document.getElementById( 'hsc-bulk-queue' );
	var summary = document.getElementById( 'hsc-bulk-summary' );
	var queue = [];
	var running = false;
	var total = 0;
	var created = 0;

	if ( ! cfg || ! drop || ! input || ! select ) {
		return;
	}

	function ready() {
		return '' !== select.value;
	}

	function refresh() {
		drop.classList.toggle( 'is-disabled', ! ready() );
		input.disabled = ! ready();
	}

	function setState( item, cls, text ) {
		item.el.className = cls;
		item.status.textContent = text;
	}

	function addToQueue( files ) {
		Array.prototype.forEach.call( files, function ( file ) {
			var li = document.createElement( 'li' );
			var name = document.createElement( 'span' );
			var status = document.createElement( 'span' );
			var item = { file: file, termId: select.value, el: li, status: status };

			name.textContent = file.name;
			li.appendChild( name );
			li.appendChild( status );
			queueEl.appendChild( li );
			total++;

			if ( 0 !== file.type.indexOf( 'image/' ) ) {
				setState( item, 'is-failed', cfg.i18n.notImage );
				return;
			}
			setState( item, '', cfg.i18n.waiting );
			queue.push( item );
		} );
		next();
	}

	// One file at a time, so sponsors are created (and ordered) in the given sequence.
	function next() {
		var item;
		var body;

		if ( running || 0 === queue.length ) {
			updateSummary();
			return;
		}
		running = true;
		item = queue.shift();
		setState( item, '', cfg.i18n.uploading );

		body = new FormData();
		body.append( 'action', cfg.action );
		body.append( 'nonce', cfg.nonce );
		body.append( 'term_id', item.termId );
		body.append( 'file', item.file );

		fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( res ) {
				return res.json();
			} )
			.then( function ( json ) {
				if ( json && json.success ) {
					created++;
					setState( item, 'is-done', cfg.i18n.done );
				} else {
					setState( item, 'is-failed', cfg.i18n.failed + ': ' + ( json && json.data && json.data.message ? json.data.message : '' ) );
				}
			} )
			.catch( function () {
				setState( item, 'is-failed', cfg.i18n.failed + ': ' + cfg.i18n.network );
			} )
			.then( function () {
				running = false;
				next();
			} );
	}

	function updateSummary() {
		summary.textContent = cfg.i18n.summary.replace( '%1$d', created ).replace( '%2$d', total );
	}

	select.addEventListener( 'change', refresh );
	input.addEventListener( 'change', function () {
		addToQueue( input.files );
		input.value = '';
	} );

	[ 'dragenter', 'dragover' ].forEach( function ( type ) {
		drop.addEventListener( type, function ( e ) {
			e.preventDefault();
			if ( ready() ) {
				drop.classList.add( 'is-over' );
			}
		} );
	} );
	[ 'dragleave', 'drop' ].forEach( function ( type ) {
		drop.addEventListener( type, function ( e ) {
			e.preventDefault();
			drop.classList.remove( 'is-over' );
		} );
	} );
	drop.addEventListener( 'drop', function ( e ) {
		if ( ready() && e.dataTransfer && e.dataTransfer.files ) {
			addToQueue( e.dataTransfer.files );
		}
	} );

	refresh();
}() );
