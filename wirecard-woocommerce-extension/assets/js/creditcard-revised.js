/**
 * Resize the credit card form when loaded
 *
 * @since 1.0.0
 */
function onFormRendered() {
	jQuery( '#checkout-submit' ).removeAttr( 'disabled' );
	jQuery( '#wc_payment_method_wirecard_creditcard_form > iframe' ).height( 300 );
}

/**
 * Log any error that has occurred.
 * 
 * @param data
 * @since 1.7.0
 */
function logError(data) {
	console.error('An error occurred: ', data);
}

/**
 * Gets the request data from the server.
 * 
 * @returns mixed
 * @since 1.7.0
 */
function getCreditCardData() {
	return jQuery.ajax({
		type: 'POST',
		url: php_vars.ajax_url,
		cache: false,
		data: {'action': 'get_credit_card_request_data'},
		dataType: 'json',
	});
}

/**
 * @param {Object} response
 * @returns mixed
 * @since 1.7.0
 */
function submitCreditCardResponse(response) {
	return jQuery.ajax({
		type: 'POST',
		url: php_vars.submit_url,
		cache: false,
		data: response,
		dataType: 'json',
	});
}

/**
 * Renders the actual seamless form
 * 
 * @since 1.7.0
 */
function renderForm(response) {
	var requestData = JSON.parse( response.data );
	
	WirecardPaymentPage.seamlessRenderForm( {
		requestData: requestData,
		wrappingDivId: 'wc_payment_method_wirecard_creditcard_form',
		onSuccess: onFormRendered,
		onError: logError,
	} );
}

/**
 * Handle the results of the form submission.
 * 
 * @since 1.7.0
 */
function handleSubmitResult(response) {
	var data = response.data;
	console.log(response.data);
		
	if ( "error" === data.result ) {
		// document.location.href = php_vars.base_url;
	} else {
		console.log("Holy shit it worked!");
	}
}

/**
 * Submit the data so we can do a proper transaction
 * 
 * @param response
 * @since 1.7.0
 */
function onFormSubmitSuccess(response) {
	var nonce = jQuery('#wc_payment_method_wirecard_creditcard_response_form input[name="cc_nonce"]');
	
	response['action'] = 'submit_creditcard_response';
	response['cc_nonce'] = nonce.val();
	
	console.log(response);
	
	submitCreditCardResponse(response)
		.done(handleSubmitResult)
		.fail(logError)
}

/**
 * Coordinates the necessary calls for making a successful credit card payment.
 */
function initializeForm() {
	getCreditCardData()
		.done(renderForm)
		.fail(logError)
		.always(function() {
			jQuery( '.show-spinner' ).hide()	
		})
}

function submitForm() {
	WirecardPaymentPage.seamlessSubmitForm( {
		wrappingDivId: "wc_payment_method_wirecard_creditcard_form",
		onSuccess: onFormSubmitSuccess,
		onError: logError
	} );
}

jQuery(document).ready(initializeForm);
jQuery('#checkout-submit').click(submitForm);
