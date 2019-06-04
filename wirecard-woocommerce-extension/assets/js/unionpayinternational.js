/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

/* globals WPP */

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

	WPP.seamlessRender(
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
	card_content_area.find( 'iframe' ).height( 270 );
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

	WPP.seamlessSubmit(
		{
			wrappingDivId: 'wc_payment_method_wirecard_upi_form',
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
