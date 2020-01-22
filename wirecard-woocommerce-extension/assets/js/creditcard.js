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
	SAVE_CREDIT_CARD_TO_VAULT: "save_cc_to_vault",
	GET_CREDIT_CARD_REQUEST_DATA: "get_credit_card_request_data"
};

let Spinner  = {
	FORM_SPINNER: ".show-spinner",
	PAY_BUTTON_SPINNER: "#wd-cc-submit-spinner",
	STATE_ON: "display",
	STATE_OFF: "none"
};

let DELETE_BUTTON = {
	FROM_VAULT: "delete-from-vault",
	FROM_VAULT_DISABLED: "delete-from-vault-disabled",
	WD_BUTTON: "wd-card-delete",
	WD_BUTTON_DISABLED: "wd-card-delete-disabled",
};

let Constants = {
	IFRAME_HEIGHT: 270,
	USE_CARD_ID: "input[data-token]",
	DELETE_CARD_BUTTON_ID: ".wd-card-delete",
	SAVE_CARD_CHECKMARK_ID: "#wirecard-store-card",
	VAULT_CONTENT_CONTAINER: "#wc_payment_method_wirecard_creditcard_vault .cards",
	VAULT_TABLE_ID: "vault-table",
	NEW_CARD_CONTENT_AREA: "#wc_payment_method_wirecard_new_credit_card",
	NEW_CARD_CONTENT_AREA_IFRAME: "#wc_payment_method_wirecard_new_credit_card iframe",
	SEAMLESS_SUBMIT_BUTTON: "seamless-submit",
	SEAMLESS_FORM_CONTAINER: "wc_payment_method_wirecard_creditcard_form",
	NONCE_SELECTOR: "#wc_payment_method_wirecard_creditcard_response_form input[name='cc_nonce']",
	MESSAGE_CONTAINER: "#wd-creditcard-messagecontainer",
	WD_TOKEN_ID_PREFIX: "wd-token-"
};

/**
 * Initializes all event handlers for the interface
 *
 * @since 2.4.0
 */
function initializeEventHandlers()
{
	let seamlessButtonSubmit = document.getElementById(Constants.SEAMLESS_SUBMIT_BUTTON);
	seamlessButtonSubmit.addEventListener("click", submitSeamlessForm);
}

function initializeVaultHandlers() {
	jQuery( Constants.USE_CARD_ID ).on('change', onCardSelected);
	jQuery( Constants.DELETE_CARD_BUTTON_ID ).on('click', deleteCreditCardFromVaultTab);
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
	getSavedCreditCardList();
	getCreditCardData( tokenId )
		.then( renderSeamlessForm )
		.fail( logError )
		.always( function () {
			setSpinnerState(Spinner.STATE_OFF, Spinner.FORM_SPINNER);
		} );
}

function getSavedCreditCardList() {
	let hasSavedTokens = document.getElementById( Constants.VAULT_TABLE_ID );
	if (typeof(hasSavedTokens) == 'undefined' || hasSavedTokens == null) {
		initializeVault();
	}
}

/**
 * Initializes the vault interface as required.
 */
function initializeVault() {
	callAjax(
		phpVars.vault_get_url,
		"GET",
		{ action : Actions.GET_CREDIT_CARD_FROM_VAULT }
	)
		.then( loadCreditCards )
		.then( initializeVaultHandlers )
		.fail( logError )
		.always(function () {
			setSpinnerState(Spinner.STATE_OFF, Spinner.FORM_SPINNER);
		});
}

function callAjax(url, method, data) {
	return jQuery.ajax(
		{
			type: method,
			url: url,
			data: data,
			dataType: "json",
		}
	);
}

/**
 * @param cardResponse
 */
function loadCreditCards(cardResponse) {
	jQuery( Constants.VAULT_CONTENT_CONTAINER )
		.html( cardResponse.data );
}

function processDeleteButton( selector, sourceClass, targetClass ) {
	selector.removeClass(sourceClass).addClass(targetClass);

}

function reloadDeleteButtons() {
	jQuery( Constants.DELETE_CARD_BUTTON_ID ).off('click');
	jQuery('[id^=' + Constants.WD_TOKEN_ID_PREFIX + ']').filter(
		function(){
			let selector = jQuery( this );
			processDeleteButton(selector.find("." + DELETE_BUTTON.FROM_VAULT_DISABLED), DELETE_BUTTON.FROM_VAULT_DISABLED, DELETE_BUTTON.FROM_VAULT);
			processDeleteButton(
				selector,
				DELETE_BUTTON.WD_BUTTON_DISABLED,
				DELETE_BUTTON.WD_BUTTON
			);
			selector.on('click', deleteCreditCardFromVaultTab);
		});
	
}

function disabledDeleteButtonByToken(token) {
	let selector = jQuery( "#" + Constants.WD_TOKEN_ID_PREFIX + token);

	processDeleteButton(
		selector,
		DELETE_BUTTON.WD_BUTTON,
		DELETE_BUTTON.WD_BUTTON_DISABLED
	);
	
	processDeleteButton(
		selector.find("." + DELETE_BUTTON.FROM_VAULT), 
		DELETE_BUTTON.FROM_VAULT,
		DELETE_BUTTON.FROM_VAULT_DISABLED
	);
	
	selector.off('click');
}

function onCardSelected(event) {
	let radioButton = jQuery( this );
	let token = radioButton.data( "token" );
	reloadDeleteButtons();
	disabledDeleteButtonByToken(token);
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
			action: Actions.GET_CREDIT_CARD_REQUEST_DATA,
			vault_token: selected_token
		}
	);
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
	let responseData = JSON.parse(response.data);
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
	return callAjax(
		phpVars.submit_url,
		"POST",
		response
	);
}


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

	return callAjax(
		phpVars.vault_url,
		"POST",
		request
	);
}

/**
 * Display error message after failure submit and hide processing spinner
 *
 * @param data
 * @since 2.0.3
 */
function onSubmitError( data ) {
	setSpinnerState(Spinner.STATE_OFF, Spinner.PAY_BUTTON_SPINNER);
	if ("transaction_state" in data) {
		getCreditCardData()
			.then( renderForm )
			.fail( logError )
			.always(
				function() {
					setSpinnerState(Spinner.STATE_OFF, Spinner.FORM_SPINNER);
				}
			);
		jQuery( "#wd-creditcard-messagecontainer" ).css( "display","block" );
	}
	logError( data );
}

/**
 * @param deleteTrigger
 * @param id
 */
function deleteCreditCardFromVaultTab() {
	let self = this;
	self.append( phpVars.spinner );
	let vault_id = jQuery(self).data('vault-id');
	
	if (vault_id) {
		deleteCreditCardFromVault( vault_id )
			.then( function () {
				self.closest('tr').remove();
			} )
			.fail( logError );
	}
}

/**
 * Delete a saved credit card from the vault
 *
 * @param vault_id
 * @since 1.1.0
 */
function deleteCreditCardFromVault( vault_id ) {
	return callAjax(
		phpVars.vault_delete_url,
		"POST",
		{ "action" : "remove_cc_from_vault", "vault_id": vault_id }
	);
}

jQuery(document).on("ready", function(){
	initializeEventHandlers();
	initializeForm();
});
