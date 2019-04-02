/*
 * Helper functions
 */

var nonce                  = jQuery( '#wc_payment_method_wirecard_upi_response_form input[name="cc_nonce"]' );
var card_content_area  	   = jQuery( '#wc_payment_method_wirecard_upi' );
var seamless_submit_button = jQuery( '#seamless-submit' );


/**
 * Log any error that has occurred.
 *
 * @param data
 * @since 1.7.0
 */
function log_error( data ) {
	console.error( 'An error occurred: ', data );
}

/*
 * AJAX-based functions
 */

/**
 * Gets the request data from the server.
 *
 * @returns mixed
 * @since 1.7.0
 */
function get_credit_card_data() {
	return jQuery.ajax(
		{
			type: 'POST',
			url: upi_vars.ajax_url,
			cache: false,
			data: {'action': 'get_upi_request_data'},
			dataType: 'json',
		}
	);
}

/**
 * Submits the seamless response to the server
 *
 * @param {Object} response
 * @returns mixed
 * @since 1.7.0
 */
function submit_credit_card_response( response ) {
	return jQuery.ajax(
		{
			type: 'POST',
			url: upi_vars.submit_url,
			cache: false,
			data: response,
			dataType: 'json',
		}
	);
}

/*
 * Seamless related functions
 */

/**
 * Handle the results of the form submission.
 *
 * @since 1.7.0
 */
function handle_submit_result( response ) {
	var data = response.data;

	if ( "error" === data.result ) {
		document.location.reload();
		return;
	}

	document.location = data.redirect;

}

/**
 * Submit the data so we can do a proper transaction
 *
 * @param response
 * @since 1.7.0
 */
function on_form_submitted( response ) {
	response['action']   = 'submit_upi_response';
	response['cc_nonce'] = nonce.val();

	submit_credit_card_response( response )
		.then( handle_submit_result )
		.fail( log_error );
}

/**
 * Renders the actual seamless form
 *
 * @since 1.7.0
 */
function render_form( response ) {
	var request_data = JSON.parse( response.data );

	WirecardPaymentPage.seamlessRenderForm(
		{
			requestData: request_data,
			wrappingDivId: 'wc_payment_method_wirecard_upi_form',
			onSuccess: on_form_rendered,
			onError: log_error,
		}
	);
}

/**
 * Resize the credit card form when loaded
 *
 * @since 1.0.0
 */
function on_form_rendered() {
	seamless_submit_button.removeAttr( 'disabled' );
	card_content_area.find( 'iframe' ).height( 470 );
}

/**
 * Coordinates the necessary calls for making a successful credit card payment.
 *
 * @since 1.7.0
 */
function initialize_form() {
	get_credit_card_data()
		.then( render_form )
		.fail( log_error )
		.always(
			function() {
				jQuery( '.show-spinner' ).hide()
			}
		)
}

/**
 * Submit the seamless form or token and handle the results.
 *
 * @since 1.7.0
 */
function submit_seamless_form() {
	jQuery( this ).after( upi_vars.spinner );
	jQuery( '.spinner' ).addClass( 'spinner-submit' );

	WirecardPaymentPage.seamlessSubmitForm(
		{
			wrappingDivId: "wc_payment_method_wirecard_upi_form",
			onSuccess: on_form_submitted,
			onError: log_error
		}
	);
}

/*
 * Integration code
 */

jQuery( document ).ready( initialize_form );
seamless_submit_button.click( submit_seamless_form );
