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

/* ==========================================================================
 * Tunet Core · Demo wizard — stepper (Plugins → Import).
 * ========================================================================== */
( function () {
	'use strict';
	var root = document.getElementById( 'tunet-wizard' );
	if ( ! root || ! window.tunetCoreAdmin ) { return; }
	var cfg = window.tunetCoreAdmin, i18n = cfg.i18n || {};

	function $( sel ) { return root.querySelector( sel ); }
	function $all( sel, ctx ) { return Array.prototype.slice.call( ( ctx || root ).querySelectorAll( sel ) ); }

	function post( action, data ) {
		var body = new URLSearchParams();
		body.set( 'action', action );
		body.set( 'nonce', cfg.nonce );
		Object.keys( data || {} ).forEach( function ( k ) { body.set( k, data[ k ] ); } );
		return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) { return r.json(); } );
	}

	/* ---- Step navigation ---- */
	var stepEls = $all( '.tunet-stepper__item' );
	var stepPanels = $all( '.tunet-step' );
	function goStep( name ) {
		stepPanels.forEach( function ( p ) { p.hidden = ( p.getAttribute( 'data-panel' ) !== name ); } );
		var idx = ( name === 'import' ) ? 1 : 0;
		stepEls.forEach( function ( el, i ) {
			el.classList.toggle( 'is-current', i === idx );
			el.classList.toggle( 'is-done', i < idx );
		} );
	}

	/* ---- Step 1: plugins ---- */
	var listEl = $( '#tunet-plugins-list' );
	var msgEl = $( '.tunet-plugins__msg' );
	var installBtn = $( '#tunet-plugins-install' );
	var continueBtn = $( '#tunet-plugins-continue' );
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
		if ( p.state === 'active' ) { return '<span class="tunet-plugin__badge is-ok">' + ( i18n.active || 'Active' ) + '</span>'; }
		return p.optional
			? '<span class="tunet-plugin__badge is-opt">' + ( i18n.optional || 'Optional' ) + '</span>'
			: '<span class="tunet-plugin__badge is-req">' + ( i18n.required || 'Required' ) + '</span>';
	}
	function renderPlugins() {
		listEl.innerHTML = '';
		plugins.forEach( function ( p, i ) {
			var active = p.state === 'active';
			// Required → always on. Optional → on if active or user opted in.
			p._on = active ? true : ( p.optional ? !! p._on : true );
			var locked = active || ! p.optional;
			var li = document.createElement( 'li' );
			li.className = 'tunet-plugin' + ( active ? ' is-active' : '' );
			li.innerHTML =
				'<label class="tunet-switch' + ( locked ? ' is-locked' : '' ) + '">' +
					'<input type="checkbox" data-i="' + i + '"' + ( p._on ? ' checked' : '' ) + ( locked ? ' disabled' : '' ) + '>' +
					'<span class="tunet-switch__track"><span class="tunet-switch__dot"></span></span>' +
				'</label>' +
				'<span class="tunet-plugin__name">' + p.label + '</span>' +
				badge( p ) +
				'<span class="tunet-plugin__state" data-i="' + i + '"></span>';
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
		post( 'tunet_demo_plugins', {} ).then( function ( res ) {
			if ( ! res || ! res.success ) { goStep( 'import' ); return; }
			plugins = res.data.plugins || [];
			renderPlugins();
			// Theme declares no third-party plugins → nothing to do here.
			if ( ! plugins.length && msgEl ) {
				msgEl.textContent = i18n.noPlugins || 'No extra plugins needed for this demo — continue to the import.';
			}
		} );
	}

	function installSelected() {
		var queue = plugins.filter( function ( p ) { return p._on && p.state !== 'active'; } );
		if ( ! queue.length ) { return; }
		installBtn.disabled = true;
		installBtn.setAttribute( 'aria-busy', 'true' );
		var installLabel = installBtn.innerHTML;
		installBtn.innerHTML = '<span class="tunet-spin" aria-hidden="true"></span> ' + ( i18n.installing || 'Installing…' );
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
			var stateEl = $( '.tunet-plugin__state[data-i="' + plugins.indexOf( p ) + '"]' );
			// Spinner de carga mientras instala/activa (más agradable que "…").
			if ( stateEl ) { stateEl.innerHTML = '<span class="tunet-spin" aria-hidden="true"></span>'; }
			post( 'tunet_demo_install', { slug: p.slug } ).then( function ( res ) {
				if ( res && res.success ) {
					p.state = 'active';
					if ( stateEl ) { stateEl.innerHTML = '<span class="dashicons dashicons-yes"></span>'; }
				} else {
					var url = res && res.data && res.data.install_url;
					if ( stateEl ) {
						stateEl.innerHTML = '<span class="tunet-err">' + ( ( res && res.data && res.data.message ) || i18n.error ) +
							( url ? ' <a href="' + url + '" target="_blank" rel="noopener">' + i18n.installManually + '</a>' : '' ) + '</span>';
					}
				}
				next();
			} );
		}
		next();
	}

	/* ---- Step 2: import ---- */
	var importBtn = $( '#tunet-demo-import' );
	var undoBtn = $( '#tunet-demo-rollback' );
	var progress = $( '.tunet-progress' );
	var bar = $( '.tunet-progress__bar' );
	var statusEl = $( '.tunet-progress__status' );
	var doneEl = $( '.tunet-done' );
	function setStatus( t ) { if ( statusEl ) { statusEl.textContent = t || ''; } }
	function setBar( pct ) { if ( progress ) { progress.hidden = false; } if ( bar ) { bar.style.width = pct + '%'; } }

	function runImport() {
		importBtn.disabled = true;
		if ( doneEl ) { doneEl.hidden = true; }
		setStatus( i18n.importing ); setBar( 0 );
		function step( n ) {
			post( 'tunet_demo_step', { step: n } ).then( function ( res ) {
				if ( ! res || ! res.success ) { setStatus( i18n.error ); importBtn.disabled = false; return; }
				setBar( res.data.progress );
				if ( res.data.label ) { setStatus( res.data.label ); }
				if ( res.data.done ) { finishImport(); } else { step( res.data.next ); }
			} );
		}
		step( 0 );
	}
	function finishImport() {
		setStatus( i18n.done );
		if ( undoBtn ) { undoBtn.disabled = false; }
		if ( doneEl ) { doneEl.hidden = false; }
	}

	/* ---- Wire ---- */
	if ( installBtn ) { installBtn.addEventListener( 'click', installSelected ); }
	if ( continueBtn ) { continueBtn.addEventListener( 'click', function () { goStep( 'import' ); } ); }
	$all( '.tunet-back' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () { goStep( b.getAttribute( 'data-to' ) || 'plugins' ); } );
	} );
	if ( importBtn ) { importBtn.addEventListener( 'click', runImport ); }
	if ( undoBtn ) {
		undoBtn.addEventListener( 'click', function () {
			undoBtn.disabled = true; setStatus( i18n.importing );
			post( 'tunet_demo_rollback', {} ).then( function () {
				setStatus( i18n.rollback ); setBar( 0 );
				if ( importBtn ) { importBtn.disabled = false; }
				if ( doneEl ) { doneEl.hidden = true; }
			} );
		} );
	}

	loadPlugins();
}() );
