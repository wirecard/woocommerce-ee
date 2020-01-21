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

let Actions = {
	GET_CREDIT_CARD_FROM_VAULT: "get_cc_from_vault",
	SUBMIT_CREDIT_CARD_RESPONSE: "submit_creditcard_response",
	SAVE_CREDIT_CARD_TO_VAULT: "save_cc_to_vault"
};

let Spinner  = {
	FORM_SPINNER: ".show-spinner",
	PAY_BUTTON_SPINNER: "#wd-cc-submit-spinner",
	STATE_ON: "display",
	STATE_OFF: "none"
};

let Constants = {
	IFRAME_HEIGHT: 270,
	USE_CARD_ID: "input[data-token]",
	DELETE_CARD_BUTTON_ID: "button[data-cardid]",
	STORED_CARD_BUTTON_ID: "#stored-card",
	SAVE_CARD_CHECKMARK_ID: "#wirecard-store-card",
	CARD_LIST_ID: "#wd-card-list",
	CARD_SPINNER_ID: ".spinner",
	VAULT_CONTENT_CONTAINER: "#wc_payment_method_wirecard_creditcard_vault .cards",
	VAULT_TABLE_ID: "vault-table",
	NEW_CARD_CONTENT_AREA: "#wc_payment_method_wirecard_new_credit_card",
	NEW_CARD_CONTENT_AREA_IFRAME: "#wc_payment_method_wirecard_new_credit_card iframe",
	SEAMLESS_SUBMIT_BUTTON: "seamless-submit",
	SEAMLESS_FORM_CONTAINER: "wc_payment_method_wirecard_creditcard_form",
	NONCE_SELECTOR: "#wc_payment_method_wirecard_creditcard_response_form input[name='cc_nonce']"
};

/**
 * Initializes all event handlers for the interface
 *
 * @since 2.4.0
 */
function initializeCreditCardEventHandlers()
{
	let useCardElement = document.querySelector(Constants.USE_CARD_ID);
	if (useCardElement) {
		useCardElement.addEventListener("change", onCardSelected);
	}
	
	let seamlessButtonSubmit = document.getElementById(Constants.SEAMLESS_SUBMIT_BUTTON);
	seamlessButtonSubmit.addEventListener("click", submitSeamlessForm);
	
	// $document.on("submit", Constants.PAYMENT_FORM_ID, onPaymentFormSubmit);
}

/**
 * Log any error that has occurred.
 *
 * @param data
 * @since 1.7.0
 */
function logError( data ) {
	console.error( "An error occurred: ", data );
}

function setSpinnerState(state, selector) {
	switch (state) {
		case Spinner.STATE_OFF:
			document.querySelector(selector).style.display = "none";
			break;
		case Spinner.STATE_ON:
			document.querySelector(selector).style.display = "block";
			break;
	} 	
}

/**
 * Loads the card list for one-click and renders the seamless form
 *
 * @param tokenId
 * @since 2.4.0
 */
function initializeForm(tokenId = null)
{
	getCardList();
	getCreditCardData(tokenId).then(function(response) {
		renderSeamlessForm(response);
	}).catch(function(response) {
		logError(response);
	}).then(function () {
		setSpinnerState(Spinner.STATE_OFF, Spinner.FORM_SPINNER);
	});
	
	
	setTimeout(initializeCreditCardEventHandlers, 3000);
}

function getCardList() {
	let hasSavedTokens = document.getElementById(Constants.VAULT_TABLE_ID);
	if (typeof(hasSavedTokens) == 'undefined' || hasSavedTokens == null) {
		initializeVault();
	}
}

/**
 * Initializes the vault interface as required.
 */
function initializeVault() {
	return getCreditCardList();
}

function callAjax(url, method, data) {
	return new Promise(function (resolve, reject) {
		var request = typeof XMLHttpRequest != 'undefined'
			? new XMLHttpRequest()
			: new ActiveXObject('Microsoft.XMLHTTP');
		request.open(method, url, true);
		request.onload = function () {


			if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
				console.log(this.responseText);
				resolve(JSON.parse(this.responseText).data);
			} else {
				reject("OOPS");
			}
		};
		request.onerror = function () {
			reject("OOPS");
		};
		request.send(JSON.stringify(data));
	});
	
	
	
	// var request = typeof XMLHttpRequest != 'undefined'
	// 	? new XMLHttpRequest()
	// 	: new ActiveXObject('Microsoft.XMLHTTP');
	// request.open(method, url, true);
	// //request.setRequestHeader("Cache-Control", "no-cache, no-store, must-revalidate");
	// //request.setRequestHeader( 'CacheControl', false );
	// request.setRequestHeader('Content-Type','application/json;charset=UTF-8');
	// request.onreadystatechange = function() {
	// 	if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
	// 		console.log(this.responseText);
	// 		callback(JSON.parse(this.responseText).data);
	// 	}
	// };
	//
	// request.onerror = function() {
	// 	console.log('something went wrong');
	// };
	//
	// request.send(JSON.stringify(data));
}

/**
 * Get all saved credit cards from the vault
 *
 * @return mixed
 * @since 1.7.0
 */
function getCreditCardList() {
	
	return callAjax(
		phpVars.vault_get_url, 
		"GET", 
		{ action : Actions.GET_CREDIT_CARD_FROM_VAULT }
	).then(function(response) {
		loadCreditCards(response);
	}).catch(function(response) {
		logError(response);
	}).then(function () {
		setSpinnerState(Spinner.STATE_OFF, Spinner.FORM_SPINNER);
	});
}

/**
 * @param cardResponse
 */
function loadCreditCards(cardResponse) {
	jQuery( Constants.VAULT_CONTENT_CONTAINER )
		.html( cardResponse );
}

function onCardSelected(event) {
	let token = event.target.dataset.token;
	setSpinnerState(Spinner.STATE_ON, Spinner.FORM_SPINNER);
	initializeForm(token);
}

/**
 * Gets the request data from the server.
 *
 * @returns mixed
 * @since 1.7.0
 */
function getCreditCardData( selected_token = null ) {
	return callAjax(
		phpVars.ajax_url,
		"POST",
		{
			action: 'get_credit_card_request_data',
			vault_token: selected_token
		}
	);
	// return jQuery.ajax(
	// 	{
	// 		type: "POST",
	// 		url: phpVars.ajax_url,
	// 		cache: false,
	// 		data: {
	// 			"action": "get_credit_card_request_data",
	// 			"vault_token": selected_token
	// 		},
	// 		dataType: "json",
	// 		success:function(data) {
	// 			renderSeamlessForm(data);
	// 		}
	// 	}
	// );
}

/**
 * Resize the credit card form when loaded
 *
 * @since 1.0.0
 */
function onFormRendered() {
	enableSubmitButton();
	configureSeamlessIframe();
}

/**
 * Configure iframe view
 *
 * @since 2.1.0
 */
function configureSeamlessIframe() {
	document.querySelector(Constants.NEW_CARD_CONTENT_AREA_IFRAME).style.height = Constants.IFRAME_HEIGHT + "px";
}

/**
 * Enable submit button 
 *
 * @since 2.1.0
 */
function enableSubmitButton() {
	document.getElementById(Constants.SEAMLESS_SUBMIT_BUTTON).removeAttribute("disabled");
}

/**
 * Renders the actual seamless form
 *
 * @since 1.7.0
 */
function renderSeamlessForm( response ) {
	let responseData = JSON.parse(response);
	WPP.seamlessRender(
		{
			requestData: responseData,
			wrappingDivId: Constants.SEAMLESS_FORM_CONTAINER,
			onSuccess: onFormRendered,
			onError: logError,
		}
	);
}

function submitSeamlessForm() {
	setSpinnerState(Spinner.STATE_ON, Spinner.PAY_BUTTON_SPINNER);
	//jQuery( "#wd-cc-submit-spinner" ).css( "display","block" );
	jQuery( this ).blur();

	WPP.seamlessSubmit(
		{
			wrappingDivId: Constants.SEAMLESS_FORM_CONTAINER,
			onSuccess: onFormSubmitted,
			onError: onSubmitError
		}
	);
	
}

/**
 * Submit the data so we can do a proper transaction
 *
 * @param response
 * @since 1.7.0
 */
function onFormSubmitted( response ) {
	response["action"]   = Actions.SUBMIT_CREDIT_CARD_RESPONSE;
	response["cc_nonce"] = document.querySelector(Constants.NONCE_SELECTOR).value;
	console.log(response);

	saveCreditCardToVault( response )
		.then(
			function () {
				submitCreditCardResponse( response )
					.then( handleSubmitResult )
					.fail( logError );
			}
		);
	
}

function submitCreditCardResponse( response ) {
	// return callAjax(
	// 	phpVars.submit_url,
	// 	"POST",
	// 	response
	// ).then( handleSubmitResult ).catch( logError );
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
 * Handle the results of the form submission.
 *
 * @since 1.7.0
 */
function handleSubmitResult( response ) {
	console.log(response);
	var data = response.data;

	if ( "error" === data.result ) {
		document.location.reload();
		return;
	}

	document.location = data.redirect;

}

// /**
//  * Save a new credit card token to our vault.
//  *
//  * @param response
//  * @returns mixed
//  * @since 1.7.0
//  */
// async function saveCreditCardToVault( response ) {
//	
// 	let vaultCheckbox = document.querySelector(Constants.SAVE_CARD_CHECKMARK_ID);
//	
// 	var request       = {
// 		"action": Actions.SAVE_CREDIT_CARD_TO_VAULT,
// 		"token": response.token_id,
// 		"mask_pan": response.masked_account_number
// 	};
//
// 	if ( "success" !== response.transaction_state ) {
// 		return null;
// 	}
//
// 	if ( ! vaultCheckbox.checked ) {
// 		return null;
// 	}
//
// 	return callAjax(
// 		phpVars.vault_url, 
// 		"POST",
// 		request
// 	);
//	
// 	// return jQuery.ajax(
// 	// 	{
// 	// 		type: "POST",
// 	// 		url: phpVars.vault_url,
// 	// 		data: request,
// 	// 		dataType: "json"
// 	// 	}
// 	// );
// }

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
 * Display error message after failure submit and hide processing spinner
 *
 * @param data
 * @since 2.0.3
 */
function onSubmitError( data ) {
	console.log(data);
	setSpinnerState(Spinner.STATE_OFF, Spinner.PAY_BUTTON_SPINNER);
	//jQuery( "#wd-cc-submit-spinner" ).css( "display","none" );
	if ("transaction_state" in data) {
		console.log("OOps");
		getCreditCardData();
			// .then( renderSeamlessForm )
			// .fail( logError )
			// .always(
			// 	function() {
			// 		jQuery( ".show-spinner" ).hide();
			// 	}
			// )
		//jQuery( "#wd-creditcard-messagecontainer" ).css( "display","block" );
	}
	logError( data );
}

initializeForm();
