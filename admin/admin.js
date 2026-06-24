/* ==========================================================================
 * Tunet Core · Admin — tabs, selector de logos y colores.
 * ========================================================================== */
( function () {
	'use strict';

	var cfg = window.tunetCoreAdmin || {};

	/* --- Tabs (Ajustes) --- */
	var tabs = document.querySelectorAll( '.tunet-tabs .nav-tab' );
	var panels = document.querySelectorAll( '.tunet-tab-panel' );
	if ( tabs.length ) {
		Array.prototype.forEach.call( tabs, function ( tab ) {
			tab.addEventListener( 'click', function () {
				var name = tab.getAttribute( 'data-tab' );
				Array.prototype.forEach.call( tabs, function ( t ) { t.classList.toggle( 'nav-tab-active', t === tab ); } );
				Array.prototype.forEach.call( panels, function ( p ) { p.hidden = ( p.getAttribute( 'data-tab' ) !== name ); } );
			} );
		} );
	}

	/* --- Selector de logos (wp.media) --- */
	function previewFor( targetId ) {
		return document.querySelector( '.tunet-logo-preview[data-for="' + targetId + '"]' );
	}
	Array.prototype.forEach.call( document.querySelectorAll( '.tunet-logo-pick' ), function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			var frame = window.wp.media( {
				title: ( cfg.i18n && cfg.i18n.chooseLogo ) || 'Logo',
				button: { text: ( cfg.i18n && cfg.i18n.useLogo ) || 'Use' },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				var targetId = btn.getAttribute( 'data-target' );
				var input = document.getElementById( targetId );
				if ( input ) { input.value = att.id; }
				var prev = previewFor( targetId );
				if ( prev ) {
					var url = ( att.sizes && att.sizes.medium ) ? att.sizes.medium.url : att.url;
					prev.innerHTML = '';
					var img = document.createElement( 'img' );
					img.src = url;
					img.alt = '';
					prev.appendChild( img );
				}
			} );
			frame.open();
		} );
	} );
	Array.prototype.forEach.call( document.querySelectorAll( '.tunet-logo-remove' ), function ( btn ) {
		btn.addEventListener( 'click', function () {
			var targetId = btn.getAttribute( 'data-target' );
			var input = document.getElementById( targetId );
			if ( input ) { input.value = ''; }
			var prev = previewFor( targetId );
			if ( prev ) { prev.innerHTML = ''; }
		} );
	} );

	/* --- Color rows --- */
	function swatchFor( id ) {
		return document.querySelector( '.tunet-color-swatch[data-target="' + id + '"]' );
	}
	Array.prototype.forEach.call( document.querySelectorAll( '.tunet-color-swatch' ), function ( sw ) {
		var target = document.getElementById( sw.getAttribute( 'data-target' ) );
		if ( target ) { sw.addEventListener( 'input', function () { target.value = sw.value; } ); }
	} );
	Array.prototype.forEach.call( document.querySelectorAll( '.tunet-color-text' ), function ( txt ) {
		txt.addEventListener( 'input', function () {
			var sw = swatchFor( txt.id );
			if ( sw && /^#?[0-9a-fA-F]{6}$/.test( txt.value ) ) {
				sw.value = txt.value.charAt( 0 ) === '#' ? txt.value : '#' + txt.value;
			}
		} );
	} );
	Array.prototype.forEach.call( document.querySelectorAll( '.tunet-color-clear' ), function ( btn ) {
		btn.addEventListener( 'click', function () {
			var t = document.getElementById( btn.getAttribute( 'data-target' ) );
			if ( t ) { t.value = ''; }
		} );
	} );

} )();

( function () {
	'use strict';
	var root = document.getElementById( 'tunet-wizard' );
	if ( ! root || ! window.tunetCoreAdmin ) { return; }
	var cfg = window.tunetCoreAdmin, i18n = cfg.i18n;

	var statusEl = root.querySelector( '.tunet-progress__status' );
	var progress = root.querySelector( '.tunet-progress' );
	var bar      = root.querySelector( '.tunet-progress__bar' );
	var startBtn = root.querySelector( '#tunet-demo-import' );
	var undoBtn  = root.querySelector( '#tunet-demo-rollback' );
	var panel    = document.createElement( 'div' );
	panel.className = 'tunet-wizard__panel';
	root.insertBefore( panel, progress );

	function post( action, data ) {
		var body = new URLSearchParams();
		body.set( 'action', action );
		body.set( 'nonce', cfg.nonce );
		Object.keys( data || {} ).forEach( function ( k ) { body.set( k, data[ k ] ); } );
		return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( r ) { return r.json(); } );
	}

	function setStatus( t ) { statusEl.textContent = t || ''; }
	function setBar( pct ) { progress.hidden = false; bar.style.width = pct + '%'; }

	// --- Plugins screen ---
	function showPlugins() {
		post( 'tunet_demo_plugins', {} ).then( function ( res ) {
			if ( ! res || ! res.success ) { return runImport(); }
			if ( res.data.all_satisfied ) { return runImport(); }
			panel.innerHTML = '<h2>' + i18n.pluginsTitle + '</h2>';
			var list = document.createElement( 'ul' );
			list.className = 'tunet-plugins';
			res.data.plugins.forEach( function ( p ) {
				var li = document.createElement( 'li' );
				li.dataset.slug = p.slug;
				renderPluginRow( li, p );
				list.appendChild( li );
			} );
			panel.appendChild( list );
			var cont = document.createElement( 'button' );
			cont.className = 'button button-primary';
			cont.textContent = i18n.continue;
			cont.addEventListener( 'click', runImport );
			panel.appendChild( cont );
		} );
	}

	function renderPluginRow( li, p ) {
		var name = '<strong>' + p.label + '</strong>' + ( p.optional ? ' <em>(' + i18n.optional + ')</em>' : '' );
		if ( p.state === 'active' ) {
			li.innerHTML = name + ' <span class="tunet-pill tunet-pill--ok">' + i18n.active + '</span>';
			return;
		}
		var label = ( p.state === 'missing' ) ? i18n.installAct : i18n.activate;
		li.innerHTML = name + ' ';
		var btn = document.createElement( 'button' );
		btn.className = 'button';
		btn.textContent = label;
		btn.addEventListener( 'click', function () {
			btn.disabled = true; btn.textContent = '…';
			post( 'tunet_demo_install', { slug: p.slug } ).then( function ( res ) {
				if ( res && res.success ) {
					p.state = 'active'; renderPluginRow( li, p );
				} else {
					btn.disabled = false; btn.textContent = label;
					var url = res && res.data && res.data.install_url;
					li.querySelector( '.tunet-err' ) || li.insertAdjacentHTML( 'beforeend',
						' <span class="tunet-err">' + ( ( res && res.data && res.data.message ) || i18n.error ) +
						( url ? ' <a href="' + url + '" target="_blank">' + i18n.installManually + '</a>' : '' ) + '</span>' );
				}
			} );
		} );
		li.appendChild( btn );
	}

	// --- Import (stepped) ---
	function runImport() {
		panel.innerHTML = '';
		setStatus( i18n.importing ); setBar( 0 );
		function step( n ) {
			post( 'tunet_demo_step', { step: n } ).then( function ( res ) {
				if ( ! res || ! res.success ) { setStatus( i18n.error ); return; }
				setBar( res.data.progress );
				if ( res.data.label ) { setStatus( res.data.label ); }
				if ( res.data.done ) { finish(); } else { step( res.data.next ); }
			} );
		}
		step( 0 );
	}

	function finish() {
		setStatus( i18n.done );
		undoBtn.disabled = false;
		panel.innerHTML = '<p><a class="button button-primary" href="' + ( cfg.homeUrl || ( location.origin + '/' ) ) + '">' + i18n.viewSite + '</a></p>';
	}

	var intro = document.getElementById( 'tunet-demo-intro' );

	startBtn.addEventListener( 'click', function () {
		startBtn.disabled = true;
		if ( intro ) { intro.hidden = true; }
		showPlugins();
	} );
	undoBtn.addEventListener( 'click', function () {
		undoBtn.disabled = true; setStatus( i18n.importing );
		post( 'tunet_demo_rollback', {} ).then( function () {
			setStatus( i18n.rollback ); setBar( 0 ); startBtn.disabled = false;
			panel.innerHTML = '';
			if ( intro ) { intro.hidden = false; }
		} );
	} );
}() );
