var token         = null;
var checkout_form = jQuery( 'form.checkout' );
var processing    = false;

/**
 * Submit the seamless form before order is placed
 *
 * @since 1.0.0
 */
checkout_form.on( 'checkout_place_order', function() {
	if ( jQuery( '#payment_method_woocommerce_wirecard_creditcard' )[0].checked === true && processing === false ) {
		processing = true;
		if ( token !== null ) {
			return true;
		} else {
			WirecardPaymentPage.seamlessSubmitForm({
				onSuccess: formSubmitSuccessHandler,
				onError: logCallback,
				wrappingDivId: "wc_payment_method_wirecard_creditcard_form"
			});
			return false;
		}
	}
	processing = false;
});

/**
 * Display error massages
 *
 * @since 1.0.0
 */
function logCallback( response ) {
	console.error( response );
}

/**
 * Add the tokenId to the submited form
 *
 * @since 1.0.0
 */
function formSubmitSuccessHandler( response ) {
	token = response.token_id;
	jQuery( '<input>' ).attr({
		type: 'hidden',
		name: 'tokenId',
		id: 'tokenId',
		value: token
	}).appendTo( checkout_form );

	checkout_form.submit();
}
$ = jQuery;

jQuery( document ).ready(function() {

	if ( $( "#wc_payment_method_wirecard_creditcard_form" ).is( ":visible" ) ) {
		getRequestData();
	}

	jQuery( "input[name=payment_method]" ).change(function() {
		if ( $( this ).val() === 'woocommerce_wirecard_creditcard' ) {
			getRequestData();
			return false;
		}
	});

	/**
	 * Get data rquired to render the form
	 *
	 * @since 1.0.0
	 */
	function getRequestData() {
		$.ajax({
			type: 'POST',
			url: ajax_url,
			data: { 'action' : 'get_credit_card_request_data' },
			dataType: 'json',
			success: function (data) {
				renderForm( JSON.parse( data.data ) );
			},
			error: function (data) {
				console.log( data );
			}
		});
	}

	/**
	 * Render the credit card form
	 *
	 * @since 1.0.0
	 */
	function renderForm( request_data ) {
		WirecardPaymentPage.seamlessRenderForm({
			requestData: request_data,
			wrappingDivId: "wc_payment_method_wirecard_creditcard_form",
			onSuccess: resizeIframe,
			onError: logCallback
		});
	}

	/**
	 * Resize the credit card form when loaded
	 *
	 * @since 1.0.0
	 */
	function resizeIframe() {
		jQuery( "#wc_payment_method_wirecard_creditcard_form > iframe" ).height( 550 );
	}
});
