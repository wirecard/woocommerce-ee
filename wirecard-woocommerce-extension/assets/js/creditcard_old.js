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
/* globals phpVars */

/*
 * Helper functions
 */

var token                = null;
var nonce                = jQuery( "#wc_payment_method_wirecard_creditcard_response_form input[name='cc_nonce']" );
var newCardContentArea   = jQuery( "#wc_payment_method_wirecard_new_credit_card" );
var vaultContentArea     = jQuery( "#wc_payment_method_wirecard_creditcard_vault" );
var seamlessSubmitButton = jQuery( "#seamless-submit" );


var Constants = {
	IFRAME_HEIGHT_DESKTOP: 410,
	IFRAME_HEIGHT_MOBILE: 390,
	IFRAME_HEIGHT_CUTOFF: 992,

	MODAL_ID: "#wirecard-ccvault-modal",
	IFRAME_ID: "#wirecard-integrated-payment-page-frame",
	CONTAINER_ID: "payment-processing-gateway-credit-card-form",
	PAYMENT_FORM_ID: "form[action*=\"creditcard\"]",
	CREDITCARD_RADIO_ID: "input[name=\"payment-option\"][data-module-name=\"wd-creditcard\"]",
	USE_CARD_BUTTON_ID: "button[data-tokenid]",
	DELETE_CARD_BUTTON_ID: "button[data-cardid]",
	STORED_CARD_BUTTON_ID: "#stored-card",
	SAVE_CARD_CHECKMARK_ID: "#wirecard-store-card",
	CARD_LIST_ID: "#wd-card-list",
	CARD_SPINNER_ID: "#card-spinner"
};


/**
 * Log any error that has occurred.
 *
 * @param data
 * @since 1.7.0
 */
function logError( data ) {
	console.error( "An error occurred: ", data );
}

/**
 * Gets the request data from the server.
 *
 * @returns mixed
 * @since 1.7.0
 */
function getCreditCardData( selected_token = null ) {
	return jQuery.ajax(
		{
			type: "POST",
			url: phpVars.ajax_url,
			cache: false,
			data: {
				"action": "get_credit_card_request_data",
				"vault_token": selected_token
			},
			dataType: "json",
		}
	);
}

/**
 * Resize the credit card form when loaded
 *
 * @since 1.0.0
 */
function onFormRendered() {
	seamlessSubmitButton.removeAttr( "disabled" );
	newCardContentArea.find( "iframe" ).height( 270 );
}

/**
 * Renders the actual seamless form
 *
 * @since 1.7.0
 */
function renderForm( response ) {
	WPP.seamlessRender(
		{
			requestData: JSON.parse( response.data ),
			wrappingDivId: "wc_payment_method_wirecard_creditcard_form",
			onSuccess: onFormRendered,
			onError: logError,
		}
	);

}

/**
 * Display error message after failure submit and hide processing spinner
 *
 * @param data
 * @since 2.0.3
 */
function onSubmitError( data ) {
	jQuery( "#wd-cc-submit-spinner" ).css( "display","none" );
	if ("transaction_state" in data) {
		getCreditCardData()
			.then( renderForm )
			.fail( logError )
			.always(
				function() {
					jQuery( ".show-spinner" ).hide();
				}
			)
		jQuery( "#wd-creditcard-messagecontainer" ).css( "display","block" );
	}
	logError( data );
}


/*
 * AJAX-based functions
 */

/**
 * Save a new credit card token to our vault.
 *
 * @param response
 * @returns mixed
 * @since 1.7.0
 */
function saveCreditCardToVault( response ) {
	var deferred      = jQuery.Deferred();
	var vaultCheckbox = jQuery( "#wirecard-store-card" );
	var request       = {
		"action": "save_cc_to_vault",
		"token": response.token_id,
		"mask_pan": response.masked_account_number
	};

	if ( "success" !== response.transaction_state ) {
		return deferred.resolve();
	}

	if ( ! vaultCheckbox.is( ":checked" ) ) {
		return deferred.resolve();
	}

	return jQuery.ajax(
		{
			type: "POST",
			url: phpVars.vault_url,
			data: request,
			dataType: "json"
		}
	);
}

/**
 * Get all saved credit cards from the vault
 *
 * @return mixed
 * @since 1.7.0
 */
function getCreditCardsFromVault() {
	return jQuery.ajax(
		{
			type: "GET",
			url: phpVars.vault_get_url,
			data: { "action" : "get_cc_from_vault" },
			dataType: "json",
		}
	);
}

/**
 * Delete a saved credit card from the vault
 *
 * @param id
 * @since 1.1.0
 */
function deleteCreditCardFromVault( id ) {
	return jQuery.ajax(
		{
			type: "POST",
			url: phpVars.vault_delete_url,
			data: { "action" : "remove_cc_from_vault", "vault_id": id },
			dataType: "json",
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
function submitCreditCardResponse( response ) {
	return jQuery.ajax(
		{
			type: "POST",
			url: phpVars.submit_url,
			cache: false,
			data: response,
			dataType: "json",
		}
	);
}

/**
 * Submits a vault-based payment to the server
 *
 * @returns mixed
 * @since 1.7.0
 */
function submitVault() {
	getCreditCardData( token )
		.then( renderForm )
		.fail( logError )
		.always(
			function() {
				jQuery( ".spinner" ).hide();
			}
		);
}

/*
 * User interface-related functions
 */

/**
 * @param cardResponse
 */
function addCreditCardsToVaultTab(cardResponse) {
	var cards = cardResponse.data;

	vaultContentArea
		.find( ".cards" )
		.html( cards )
}

/**
 * @param deleteTrigger
 * @param id
 */
function deleteCreditCardFromVaultTab( deleteTrigger, id ) {
	token = null;
	jQuery( deleteTrigger ).append( phpVars.spinner );

	deleteCreditCardFromVault( id )
		.then( addCreditCardsToVaultTab )
		.fail( logError );
}

function onTokenSelected( tokenField ) {
	var selectedToken = jQuery( tokenField ).data( "token" );

	if ( selectedToken ) {
		token = selectedToken;
	}
}

/*
 * Seamless related functions
 */

/**
 * Handle the results of the form submission.
 *
 * @since 1.7.0
 */
function handleSubmitResult( response ) {
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
function onFormSubmitted( response ) {
	response["action"]   = "submit_creditcard_response";
	response["cc_nonce"] = nonce.val();

	saveCreditCardToVault( response )
		.then(
			function () {
				submitCreditCardResponse( response )
					.then( handleSubmitResult )
					.fail( logError );
			}
		);
}

/**
 * Initializes the vault interface as required.
 */
function initializeVault() {
	getCreditCardsFromVault()
		.then( addCreditCardsToVaultTab )
		.fail( logError );
}

/**
 * Coordinates the necessary calls for making a successful credit card payment.
 *
 * @since 1.7.0
 */
function initializeForm() {
	let hasSavedTokens = document.getElementById("wc_payment_method_wirecard_creditcard_vault");
	if ( hasSavedTokens ) {
		initializeVault();
	}

	getCreditCardData()
		.then( renderForm )
		.fail( logError )
		.always(
			function() {
				jQuery( ".show-spinner" ).hide();
			}
		)
}

/**
 * Submit the seamless form or token and handle the results.
 *
 * @since 1.7.0
 */
function submitSeamlessForm() {
	jQuery( "#wd-cc-submit-spinner" ).css( "display","block" );
	jQuery( this ).blur();

	WPP.seamlessSubmit(
		{
			wrappingDivId: "wc_payment_method_wirecard_creditcard_form",
			onSuccess: onFormSubmitted,
			onError: onSubmitError
		}
	);
}

/**
 * Submit the token and handle the results.
 *
 * @since 1.7.0
 */
function submitVaultForm() {
	jQuery( this ).after( phpVars.spinner );
	jQuery( ".spinner" ).addClass( "spinner-submit" );

	submitVault()
		.then( handleSubmitResult )
		.fail( logError );
}

/*
 * Integration code
 */

jQuery( document ).ready( initializeForm );
seamlessSubmitButton.click( submitSeamlessForm );
