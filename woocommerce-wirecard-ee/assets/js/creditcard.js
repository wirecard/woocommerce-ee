var token         = null;
var checkout_form = jQuery( 'form.checkout' );

/**
 * Submit the seamless form before order is placed
 *
 * @since 1.0.0
 */
checkout_form.on( 'checkout_place_order', function() {
	if ( token != null ) {
		return true;
	} else {
		WirecardPaymentPage.seamlessSubmitForm({
			onSuccess: formSubmitSuccessHandler,
			onError: logCallback,
			wrappingDivId: "wc_payment_method_wirecard_creditcard_form"
		});
		return false;
	}
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

jQuery( document ).ajaxComplete(function() {
	if ( jQuery( "#payment_method_woocommerce_wirecard_creditcard" ).checked == true &&
		jQuery( '#wc_payment_method_wirecard_creditcard_form' )[0].hasChildNodes() == false ) {
		renderForm();
	}
	jQuery( ".wc_payment_methods" ).on( "click", ".payment_method_woocommerce_wirecard_creditcard", function() {
		if ( jQuery( '#wc_payment_method_wirecard_creditcard_form' )[0].hasChildNodes() == false) {
			renderForm();
		}
	});

	/**
	 * Render the credit card form
	 *
	 * @since 1.0.0
	 */
	function renderForm() {
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
