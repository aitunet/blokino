/* ==========================================================================
 * Blokino · Admin — tabs, selector de logos y colores.
 * ========================================================================== */
( function () {
	'use strict';

	var cfg = window.blokinoAdmin || {};

	/* --- Selector de logos (wp.media) --- */
	function previewFor( targetId ) {
		return document.querySelector( '.blokino-logo-preview[data-for="' + targetId + '"]' );
	}
	Array.prototype.forEach.call( document.querySelectorAll( '.blokino-logo-pick' ), function ( btn ) {
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
	Array.prototype.forEach.call( document.querySelectorAll( '.blokino-logo-remove' ), function ( btn ) {
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
		return document.querySelector( '.blokino-color-swatch[data-target="' + id + '"]' );
	}
	Array.prototype.forEach.call( document.querySelectorAll( '.blokino-color-swatch' ), function ( sw ) {
		var target = document.getElementById( sw.getAttribute( 'data-target' ) );
		if ( target ) { sw.addEventListener( 'input', function () { target.value = sw.value; } ); }
	} );
	Array.prototype.forEach.call( document.querySelectorAll( '.blokino-color-text' ), function ( txt ) {
		txt.addEventListener( 'input', function () {
			var sw = swatchFor( txt.id );
			if ( sw && /^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test( txt.value ) ) {
				sw.value = txt.value.charAt( 0 ) === '#' ? txt.value : '#' + txt.value;
			}
		} );
	} );
	Array.prototype.forEach.call( document.querySelectorAll( '.blokino-color-clear' ), function ( btn ) {
		btn.addEventListener( 'click', function () {
			var t = document.getElementById( btn.getAttribute( 'data-target' ) );
			if ( t ) { t.value = ''; }
		} );
	} );

	/* Vaciar TODOS los brand colors de golpe (aviso de override activo). No
	   guarda: deja el formulario listo y el usuario pulsa "Save changes". */
	var clearAll = document.getElementById( 'blokino-brand-clear-all' );
	if ( clearAll ) {
		clearAll.addEventListener( 'click', function () {
			// Both brand tables: site colors and the dark-band pair.
			Array.prototype.forEach.call( document.querySelectorAll( '.blokino-brand-colors .blokino-color-text' ), function ( t ) {
				t.value = '';
			} );
			clearAll.disabled = true;
		} );
	}

} )();

/* ==========================================================================
 * Blokino · Demo wizard — stepper (Plugins → Import).
 * ========================================================================== */
( function () {
	'use strict';
	var root = document.getElementById( 'blokino-wizard' );
	if ( ! root || ! window.blokinoAdmin ) { return; }
	var cfg = window.blokinoAdmin, i18n = cfg.i18n || {};

	function $( sel ) { return root.querySelector( sel ); }
	function $all( sel, ctx ) { return Array.prototype.slice.call( ( ctx || root ).querySelectorAll( sel ) ); }

	/**
	 * POST to admin-ajax and resolve with the parsed JSON.
	 *
	 * Never `r.json()` blindly: a request the host kills (PHP time limit, proxy
	 * timeout, WAF) comes back as an HTML error page, and parsing it threw
	 * "Unexpected token '<'" with the wizard frozen and no message. Now a
	 * non-JSON body rejects with an Error carrying the HTTP status and a snippet,
	 * so the caller can show it and retry.
	 */
	function post( action, data ) {
		var body = new URLSearchParams();
		body.set( 'action', action );
		body.set( 'nonce', cfg.nonce );
		Object.keys( data || {} ).forEach( function ( k ) { body.set( k, data[ k ] ); } );
		return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) {
			return r.text().then( function ( text ) {
				try {
					return JSON.parse( text );
				} catch ( e ) {
					var err = new Error( 'Non-JSON response' );
					err.status  = r.status;
					err.snippet = String( text ).replace( /<[^>]*>/g, ' ' ).replace( /\s+/g, ' ' ).trim().slice( 0, 120 );
					throw err;
				}
			} );
		} );
	}

	/* ---- Step navigation ---- */
	var stepEls = $all( '.blokino-stepper__item' );
	var stepPanels = $all( '.blokino-step' );
	function goStep( name ) {
		stepPanels.forEach( function ( p ) { p.hidden = ( p.getAttribute( 'data-panel' ) !== name ); } );
		var idx = ( name === 'import' ) ? 1 : 0;
		stepEls.forEach( function ( el, i ) {
			el.classList.toggle( 'is-current', i === idx );
			el.classList.toggle( 'is-done', i < idx );
		} );
	}

	/* ---- Step 1: plugins ---- */
	var listEl = $( '#blokino-plugins-list' );
	var msgEl = $( '.blokino-plugins__msg' );
	var installBtn = $( '#blokino-plugins-install' );
	var continueBtn = $( '#blokino-plugins-continue' );
	var plugins = [];

	function requiredSatisfied() {
		return plugins.every( function ( p ) { return p.optional || p.state === 'active'; } );
	}
	function syncUI() {
		var pending = plugins.some( function ( p ) { return p._on && p.state !== 'active'; } );
		if ( continueBtn ) { continueBtn.disabled = ! requiredSatisfied(); }
		if ( installBtn ) { installBtn.disabled = ! pending; installBtn.hidden = ! pending; }
	}
	function badge( p ) {
		if ( p.state === 'active' ) { return '<span class="blokino-plugin__badge is-ok">' + ( i18n.active || 'Active' ) + '</span>'; }
		return p.optional
			? '<span class="blokino-plugin__badge is-opt">' + ( i18n.optional || 'Optional' ) + '</span>'
			: '<span class="blokino-plugin__badge is-req">' + ( i18n.required || 'Required' ) + '</span>';
	}
	function renderPlugins() {
		listEl.innerHTML = '';
		plugins.forEach( function ( p, i ) {
			var active = p.state === 'active';
			// Required → always on. Optional → on if active or user opted in.
			p._on = active ? true : ( p.optional ? !! p._on : true );
			var locked = active || ! p.optional;
			var li = document.createElement( 'li' );
			li.className = 'blokino-plugin' + ( active ? ' is-active' : '' );
			li.innerHTML =
				'<label class="blokino-switch' + ( locked ? ' is-locked' : '' ) + '">' +
					'<input type="checkbox" data-i="' + i + '"' + ( p._on ? ' checked' : '' ) + ( locked ? ' disabled' : '' ) + '>' +
					'<span class="blokino-switch__track"><span class="blokino-switch__dot"></span></span>' +
				'</label>' +
				'<span class="blokino-plugin__name">' + p.label + '</span>' +
				badge( p ) +
				'<span class="blokino-plugin__state" data-i="' + i + '"></span>';
			listEl.appendChild( li );
		} );
		$all( 'input[type=checkbox]', listEl ).forEach( function ( cb ) {
			cb.addEventListener( 'change', function () {
				plugins[ cb.getAttribute( 'data-i' ) ]._on = cb.checked;
				syncUI();
			} );
		} );
		syncUI();
	}

	function loadPlugins() {
		post( 'blokino_demo_plugins', {} ).then( function ( res ) {
			if ( ! res || ! res.success ) { goStep( 'import' ); return; }
			plugins = res.data.plugins || [];
			renderPlugins();
			// Theme declares no third-party plugins → nothing to do here.
			if ( ! plugins.length && msgEl ) {
				msgEl.textContent = i18n.noPlugins || 'No extra plugins needed for this demo — continue to the import.';
			}
		} ).catch( function ( err ) {
			// Could not even list the plugins: say so, and let the import proceed.
			if ( msgEl ) { msgEl.textContent = describe( err ); }
			plugins = [];
			syncUI();
		} );
	}

	function installSelected() {
		var queue = plugins.filter( function ( p ) { return p._on && p.state !== 'active'; } );
		if ( ! queue.length ) { return; }
		installBtn.disabled = true;
		installBtn.setAttribute( 'aria-busy', 'true' );
		var installLabel = installBtn.innerHTML;
		installBtn.innerHTML = '<span class="blokino-spin" aria-hidden="true"></span> ' + ( i18n.installing || 'Installing…' );
		msgEl.textContent = '';
		var i = 0;
		function next() {
			if ( i >= queue.length ) {
				installBtn.innerHTML = installLabel;
				installBtn.removeAttribute( 'aria-busy' );
				renderPlugins();
				if ( requiredSatisfied() ) { msgEl.textContent = i18n.pluginsReady || ''; }
				return;
			}
			var p = queue[ i++ ];
			var stateEl = $( '.blokino-plugin__state[data-i="' + plugins.indexOf( p ) + '"]' );
			// Spinner de carga mientras instala/activa (más agradable que "…").
			if ( stateEl ) { stateEl.innerHTML = '<span class="blokino-spin" aria-hidden="true"></span>'; }
			post( 'blokino_demo_install', { slug: p.slug } ).then( function ( res ) {
				if ( res && res.success ) {
					p.state = 'active';
					if ( stateEl ) { stateEl.innerHTML = '<span class="dashicons dashicons-yes"></span>'; }
				} else {
					var url = res && res.data && res.data.install_url;
					if ( stateEl ) {
						stateEl.innerHTML = '<span class="blokino-err">' + ( ( res && res.data && res.data.message ) || i18n.error ) +
							( url ? ' <a href="' + url + '" target="_blank" rel="noopener">' + i18n.installManually + '</a>' : '' ) + '</span>';
					}
				}
				next();
			} ).catch( function ( err ) {
				// Installing/activating a plugin can also outlive a tight host limit.
				if ( stateEl ) { stateEl.innerHTML = '<span class="blokino-err">' + describe( err ) + '</span>'; }
				next();
			} );
		}
		next();
	}

	/* ---- Step 2: import ---- */
	var importBtn = $( '#blokino-demo-import' );
	var undoBtn = $( '#blokino-demo-rollback' );
	var progress = $( '.blokino-progress' );
	var bar = $( '.blokino-progress__bar' );
	var statusEl = $( '.blokino-progress__status' );
	var doneEl = $( '.blokino-done' );
	function setStatus( t ) { if ( statusEl ) { statusEl.textContent = t || ''; } }
	function setBar( pct ) { if ( progress ) { progress.hidden = false; } if ( bar ) { bar.style.width = pct + '%'; } }

	// Step to resume from after a failure (null = start over). Every step is
	// safe to re-run: media resumes from its cursor, content dedupes by slug.
	var resumeStep = null;
	var importLabel = importBtn ? importBtn.innerHTML : '';
	var RETRIES = 3;

	function describe( err ) {
		var what = err && err.status ? ( 'HTTP ' + err.status ) : ( ( err && err.message ) || 'network' );
		var msg  = ( i18n.serverError || 'The server did not answer with JSON (%s). The request was probably cut short by a time limit.' ).replace( '%s', what );
		if ( err && err.snippet ) { msg += ' — “' + err.snippet + '”'; }
		return msg;
	}

	function failImport( n, err ) {
		resumeStep = n;
		setStatus( describe( err ) + ' ' + ( i18n.retryHint || 'Click Retry to resume from this step.' ) );
		if ( progress ) { progress.classList.add( 'is-error' ); }
		importBtn.innerHTML = i18n.retry || 'Retry';
		importBtn.disabled  = false;
	}

	function runImport() {
		var from = resumeStep || 0;
		resumeStep = null;
		importBtn.disabled  = true;
		importBtn.innerHTML = importLabel;
		if ( progress ) { progress.classList.remove( 'is-error' ); }
		if ( doneEl ) { doneEl.hidden = true; }
		setStatus( i18n.importing );
		if ( ! from ) { setBar( 0 ); }
		// fails: consecutive dead requests on this step (reset on success).
		// level: how much the server should shrink the step's per-request work —
		// kept while the same step continues, so a host with a tight limit does
		// not pay a kill + retry for every chunk.
		function step( n, fails, level ) {
			post( 'blokino_demo_step', { step: n, retry: level } ).then( function ( res ) {
				if ( ! res || ! res.success ) {
					setStatus( ( res && res.data && res.data.message ) || i18n.error );
					importBtn.disabled = false;
					return;
				}
				setBar( res.data.progress );
				if ( res.data.label ) { setStatus( res.data.label ); }
				if ( res.data.done ) { finishImport(); return; }
				step( res.data.next, 0, res.data.next === n ? level : 0 );
			} ).catch( function ( err ) {
				// The request died (non-JSON body or network). Retry the SAME step a
				// few times with a pause — a resumable step picks up where it stopped.
				if ( fails < RETRIES ) {
					setStatus( ( i18n.retrying || 'Connection hiccup — retrying…' ) + ' (' + ( fails + 1 ) + '/' + RETRIES + ')' );
					window.setTimeout( function () { step( n, fails + 1, Math.min( 3, level + 1 ) ); }, 1500 * ( fails + 1 ) );
					return;
				}
				failImport( n, err );
			} );
		}
		step( from, 0, 0 );
	}
	function finishImport() {
		resumeStep = null;
		setStatus( i18n.done );
		if ( undoBtn ) { undoBtn.disabled = false; }
		if ( doneEl ) { doneEl.hidden = false; }
	}

	/* ---- Wire ---- */
	if ( installBtn ) { installBtn.addEventListener( 'click', installSelected ); }
	if ( continueBtn ) { continueBtn.addEventListener( 'click', function () { goStep( 'import' ); } ); }
	$all( '.blokino-back' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () { goStep( b.getAttribute( 'data-to' ) || 'plugins' ); } );
	} );
	if ( importBtn ) { importBtn.addEventListener( 'click', runImport ); }
	if ( undoBtn ) {
		undoBtn.addEventListener( 'click', function () {
			undoBtn.disabled = true; setStatus( i18n.importing );
			post( 'blokino_demo_rollback', {} ).then( function () {
				resumeStep = null;
				setStatus( i18n.rollback ); setBar( 0 );
				if ( importBtn ) { importBtn.disabled = false; importBtn.innerHTML = importLabel; }
				if ( doneEl ) { doneEl.hidden = true; }
			} ).catch( function ( err ) {
				setStatus( describe( err ) );
				undoBtn.disabled = false;
			} );
		} );
	}

	loadPlugins();
}() );
