/**
 * Blokino · Deactivation feedback dialog (Plugins screen).
 *
 * Intercepts Blokino's own "Deactivate" link and opens the dialog printed by
 * Blokino_Feedback. Whatever the person does — submit, skip, or a failed
 * request — the deactivate URL is followed in the end; only Cancel/Escape
 * keep the plugin active. Without this script the link works as usual.
 */
( function () {
	'use strict';

	var cfg = window.blokinoFeedback;
	var dialog = document.getElementById( 'blokino-feedback' );
	if ( ! cfg || ! dialog || typeof dialog.showModal !== 'function' ) {
		return;
	}
	var row = document.querySelector( 'tr[data-plugin="' + cfg.plugin + '"]' );
	var link = row ? row.querySelector( '.deactivate a' ) : null;
	if ( ! link ) {
		return;
	}

	var form = dialog.querySelector( 'form' );
	var details = dialog.querySelector( '#blokino-fb-details' );
	var contact = dialog.querySelector( 'input[name="contact"]' );
	var emailWrap = dialog.querySelector( '.blokino-fb__email' );
	var email = dialog.querySelector( 'input[name="email"]' );
	var submit = dialog.querySelector( '[data-blokino-fb="submit"]' );
	var buttons = dialog.querySelectorAll( 'button' );
	var leaving = false;

	function deactivate() {
		leaving = true;
		window.location.href = link.href;
	}

	function busy( label ) {
		buttons.forEach( function ( b ) {
			b.disabled = true;
		} );
		submit.textContent = label;
		dialog.setAttribute( 'aria-busy', 'true' );
	}

	link.addEventListener( 'click', function ( e ) {
		e.preventDefault();
		dialog.showModal();
		var first = dialog.querySelector( 'input[name="reason"]' );
		if ( first ) {
			first.focus();
		}
	} );

	dialog.addEventListener( 'change', function ( e ) {
		if ( e.target.name === 'reason' ) {
			submit.disabled = false;
			details.placeholder = e.target.getAttribute( 'data-hint' ) || '';
		} else if ( e.target === contact ) {
			emailWrap.hidden = ! contact.checked;
			if ( contact.checked ) {
				email.focus();
			}
		}
	} );

	dialog.addEventListener( 'click', function ( e ) {
		var action = e.target.closest( '[data-blokino-fb]' );
		if ( action ) {
			var what = action.getAttribute( 'data-blokino-fb' );
			if ( what === 'cancel' ) {
				dialog.close();
			} else if ( what === 'skip' ) {
				busy( submit.textContent );
				deactivate();
			}
			return;
		}
		// Click on the backdrop (outside the form box) closes like Cancel.
		if ( e.target === dialog && ! leaving ) {
			var r = dialog.getBoundingClientRect();
			if ( e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom ) {
				dialog.close();
			}
		}
	} );

	// Escape while sending must not strand the page with a half-done request.
	dialog.addEventListener( 'cancel', function ( e ) {
		if ( leaving ) {
			e.preventDefault();
		}
	} );

	dialog.addEventListener( 'close', function () {
		if ( ! leaving ) {
			link.focus();
		}
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var reason = form.querySelector( 'input[name="reason"]:checked' );
		if ( ! reason ) {
			return;
		}
		leaving = true;
		busy( cfg.sending );

		var data = new FormData();
		data.append( 'action', cfg.action );
		data.append( 'nonce', cfg.nonce );
		data.append( 'reason', reason.value );
		data.append( 'details', details.value );
		if ( contact.checked ) {
			data.append( 'contact', '1' );
			data.append( 'email', email.value );
		}

		var controller = typeof AbortController === 'function' ? new AbortController() : null;
		var timer = setTimeout( function () {
			if ( controller ) {
				controller.abort();
			}
			deactivate();
		}, 8000 );

		fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
			signal: controller ? controller.signal : undefined,
		} )
			.catch( function () {} )
			.then( function () {
				clearTimeout( timer );
				deactivate();
			} );
	} );
} )();
