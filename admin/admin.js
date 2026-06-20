/* ==========================================================================
 * Tunet Core · Admin — tabs, selector de logos, demo importer y colores.
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

	/* --- Demo importer (solo en Herramientas) --- */
	var importBtn = document.getElementById( 'tunet-demo-import' );
	var rollbackBtn = document.getElementById( 'tunet-demo-rollback' );
	var progress = document.querySelector( '.tunet-progress' );
	var bar = document.querySelector( '.tunet-progress__bar' );
	var status = document.querySelector( '.tunet-progress__status' );

	function post( action, extra ) {
		var body = 'action=' + encodeURIComponent( action ) + '&nonce=' + encodeURIComponent( cfg.nonce );
		if ( extra ) { body += extra; }
		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		} ).then( function ( r ) { return r.json(); } );
	}

	function setProgress( pct, label ) {
		if ( progress ) { progress.hidden = false; }
		if ( bar ) { bar.style.width = pct + '%'; }
		if ( status ) { status.textContent = label || ''; }
	}

	function runStep( step ) {
		var label = ( cfg.labels && cfg.labels[ step ] ) || ( cfg.i18n && cfg.i18n.importing );
		setProgress( Math.round( ( step / cfg.steps ) * 100 ), label );
		return post( 'tunet_demo_step', '&step=' + step ).then( function ( res ) {
			if ( ! res || ! res.success ) { throw new Error( 'step failed' ); }
			setProgress( res.data.progress, label );
			return res.data.done ? true : runStep( res.data.next );
		} );
	}

	if ( importBtn ) {
		importBtn.addEventListener( 'click', function () {
			importBtn.disabled = true;
			runStep( 0 ).then( function () {
				setProgress( 100, cfg.i18n.done );
				if ( rollbackBtn ) { rollbackBtn.disabled = false; }
				importBtn.disabled = false;
			} ).catch( function () {
				if ( status ) { status.textContent = cfg.i18n.error; }
				importBtn.disabled = false;
			} );
		} );
	}
	if ( rollbackBtn ) {
		rollbackBtn.addEventListener( 'click', function () {
			rollbackBtn.disabled = true;
			post( 'tunet_demo_rollback' ).then( function ( res ) {
				if ( res && res.success ) {
					setProgress( 0, cfg.i18n.rollback );
					if ( progress ) { progress.hidden = true; }
				} else { rollbackBtn.disabled = false; }
			} ).catch( function () { rollbackBtn.disabled = false; } );
		} );
	}
} )();
