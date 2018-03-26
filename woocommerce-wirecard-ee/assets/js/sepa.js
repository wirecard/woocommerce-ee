$                 = jQuery;
var popup         = $( '#dialog' );
var checkout_form = $( 'form.checkout' );
var sepa_check    = false;

$( document ).ready( function() {
	/**
	 * Create popup window
	 */
	popup.dialog({
		autoOpen :false,
		modal: true,
		show: "blind",
		hide: "blind"
	});

	/**
	 * Submit the seamless form before order is placed
	 *
	 * @since 1.0.0
	 */
	checkout_form.on( 'checkout_place_order', function() {
		if ( window.sepaplaceorderchecked ) {
			window.sepaplaceorderchecked = false;
			return;
		} else {
			window.sepaplaceorderchecked = true;
		}
		if ( $( '#payment_method_wirecard_ee_sepa' ).is( ':checked' ) && ! sepa_check ) {
			if ( validate_inputs() === false ) {
				return false;
			}
			get_sepa_mandate_data();
			return false;
		} else {
			sepa_check = false;
			popup.dialog( 'close' );
			$( 'body' ).css( 'overflow', 'auto' );
			return true;
		}
	});

	/**
	 * Validate if inputs are set
	 */
	function validate_inputs() {
		var validation = true;
		$( '.wc-sepa-input' ).each(function () {
			if ( ! $( this ).val() ) {
				$( this ) .focus();
				validation = false;
				return;
			}
		});
		return validation;
	}

	/**
	 * Get SEPA mandate template
	 */
	function get_sepa_mandate_data() {
		$.ajax({
			type: 'GET',
			url: sepa_url,
			data: { 'action' : 'get_sepa_mandate' },
			dataType: 'json',
			success: function ( response ) {
				openPopup( response.data );
			},
			error: function ( response ) {
				console.log( response );
			}
		});
	}

	/**
	 * Process data and open popup
	 *
	 * @param content
	 * @returns {boolean}
	 */
	function openPopup( content ) {
		popup.html( content );
		popup.find( '.first_last_name' ).text( $( '#sepa_firstname' ).val() + ' ' + $( '#sepa_lastname' ).val() );
		popup.find( '.bank_iban' ).text( $( '#sepa_iban' ).val() );
		popup.find( '.bank_bic' ).text( $( '#sepa_bic' ).val() );
		var screen_height    = window.screen.height;
		var adjust_to_screen = screen_height * 0.8;

		if ( screen_height > 1000 ) {
			adjust_to_screen = 800;
		}

		popup.dialog({
			height: adjust_to_screen,
			width: 'auto'
		});
		popup.dialog( 'open' );
		$( 'body' ).css( 'overflow', 'hidden' );

		var button = document.getElementById( 'sepa-button' );
		button.addEventListener( 'click', process_order, false );

		var check_box = document.getElementById( 'sepa-check' );
		check_box.addEventListener( 'change', check_change, false );

		return false;
	}

	function process_order() {
		if ( document.getElementById( 'sepa-check' ).checked ) {
			sepa_check = true;
			checkout_form.submit();
		} else {
			popup.dialog( 'close' );
			$( 'body' ).css( 'overflow', 'auto' );
		}
	}

	function check_change() {
		if ( document.getElementById( 'sepa-check' ).checked ) {
			$( '#sepa-button' ).text( 'Process' );
		} else {
			$( '#sepa-button' ).text( 'Cancel' );
		}
	}
});
