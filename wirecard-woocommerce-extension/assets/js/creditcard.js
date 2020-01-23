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

/**
 * @external WPP - External WPP library
 * @param {string} phpVars.vault_url - endpoint to save token to vault
 * @param {string} phpVars.vault_delete_url - endpoint to delete token from vault
 * @param {string} phpVars.submit_url - endpoint to submit wpp response and process payment
 * @param {string} phpVars.ajax_url - endpoint to get response from payment engine
 * @param {string} phpVars.vault_get_url - endpoint to get tokens from vault
 */

let Actions = {
	GET_CREDIT_CARD_FROM_VAULT: "get_cc_from_vault",
	SUBMIT_CREDIT_CARD_RESPONSE: "submit_creditcard_response",
	SAVE_CREDIT_CARD_TO_VAULT: "save_cc_to_vault",
	GET_CREDIT_CARD_REQUEST_DATA: "get_credit_card_request_data",
	REMOVE_CREDIT_CARD_FROM_VAULT: "remove_cc_from_vault",
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
	IFRAME_HEIGHT: "270px",
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
 * Initializes general event handlers for the interface
 *
 * @since 2.4.0
 */
function initEventHandlers()
{
	let seamlessButtonSubmit = document.getElementById(Constants.SEAMLESS_SUBMIT_BUTTON);
	seamlessButtonSubmit.addEventListener("click", submitSeamlessForm);
}

/**
 * Initializes token event handlers for the interface
 * 
 * @since 3.1.0
 */
function initTokenEventHandlers() {
	jQuery( Constants.USE_CARD_ID ).on('change', onTokenSelected);
	jQuery( Constants.DELETE_CARD_BUTTON_ID ).on('click', onTokenDeleted);
}

/**
 * Log any error that has occurred.
 *
 * @param {*} data
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
 * @param {number|null} tokenId
 * @since 3.1.0
 */
function initializeForm(tokenId = null)
{
	getSavedTokenList();
	getCreditCardData( tokenId )
		.then( renderSeamlessForm )
		.fail( logError )
		.always( function () {
			setSpinnerState(Spinner.STATE_OFF, Spinner.FORM_SPINNER);
		} );
}

function getSavedTokenList() {
	let hasSavedTokens = document.getElementById( Constants.VAULT_TABLE_ID );
	if (typeof(hasSavedTokens) == 'undefined' || hasSavedTokens == null) {
		initializeVault();
	}
}

/**
 * Get saved token from vault
 * 
 * @since 3.1.0
 */
function getFormattedTokenViewFromVault() {
	return callAjax(
		phpVars.vault_get_url,
		"GET",
		{ action : Actions.GET_CREDIT_CARD_FROM_VAULT }
	);
}

/**
 * Initializes the vault interface as required.
 * 
 * @since 3.1.0
 */
function initializeVault() {
	getFormattedTokenViewFromVault()
		.then( loadTokenTable )
		.then( initTokenEventHandlers )
		.fail( logError )
		.always(function () {
			setSpinnerState( Spinner.STATE_OFF, Spinner.FORM_SPINNER );
		});
}

/**
 * jQuery ajax wrapper
 * 
 * @param {string} url
 * @param {string} method
 * @param {*} data
 * @returns {jQuery}
 */
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
 * Load token table
 * @param {*} response
 * 
 * @since 3.1.0
 */
function loadTokenTable( response ) {
	jQuery( Constants.VAULT_CONTENT_CONTAINER )
		.html( response.data );
}

/**
 * Replace class for chosen selector
 * 
 * @param {jQuery} selector
 * @param {string} needle
 * @param {string} replacement
 * @since 3.1.0
 */
function replaceClassForSelector( selector, needle, replacement ) {
	selector.removeClass(needle).addClass(replacement);
}

/**
 * Enable all delete buttons on select credit card
 * 
 * @since 3.1.0
 */
function enableDeleteButtons() {
	jQuery( Constants.DELETE_CARD_BUTTON_ID ).off( 'click' );
	jQuery( '[id^=' + Constants.WD_TOKEN_ID_PREFIX + ']' ).filter(
		function() {
			let selector = jQuery( this );
			replaceClassForSelector( 
				selector.find( 
					"." + DELETE_BUTTON.FROM_VAULT_DISABLED 
				), 
				DELETE_BUTTON.FROM_VAULT_DISABLED,
				DELETE_BUTTON.FROM_VAULT
			);
			replaceClassForSelector(
				selector,
				DELETE_BUTTON.WD_BUTTON_DISABLED,
				DELETE_BUTTON.WD_BUTTON
			);
			selector.on( 'click', onTokenDeleted );
		});
}

/**
 * Disable delete button for selected credit card
 *
 * @param {string} token
 * @since 3.1.0
 */
function disableDeleteButtonByToken( token ) {
	let selector = jQuery( "#" + Constants.WD_TOKEN_ID_PREFIX + token );

	replaceClassForSelector(
		selector,
		DELETE_BUTTON.WD_BUTTON,
		DELETE_BUTTON.WD_BUTTON_DISABLED
	);
	
	replaceClassForSelector(
		selector.find( "." + DELETE_BUTTON.FROM_VAULT ), 
		DELETE_BUTTON.FROM_VAULT,
		DELETE_BUTTON.FROM_VAULT_DISABLED
	);
	
	selector.off( 'click' );
}

/**
 * Trigger event that load filled credit card form 
 * 
 * @since 3.1.0
 */
function onTokenSelected() {
	let radioButton = jQuery( this );
	let token = radioButton.data( "token" );
	enableDeleteButtons();
	disableDeleteButtonByToken( token );
	setSpinnerState( Spinner.STATE_ON, Spinner.FORM_SPINNER );
	initializeForm( token );
}

/**
 * Gets the request data from the server.
 *
 * @returns {jQuery}
 * @since 3.1.0
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
 * @since 3.1.0
 */
function onFormRendered() {
	enableSubmitButton();
	configureSeamlessIframe();
}

/**
 * Configure iframe view
 *
 * @since 3.1.0
 */
function configureSeamlessIframe() {
	document
		.querySelector( Constants.NEW_CARD_CONTENT_AREA_IFRAME ).style.height = Constants.IFRAME_HEIGHT;
}

/**
 * Enable submit button 
 *
 * @since 3.1.0
 */
function enableSubmitButton() {
	document
		.getElementById( Constants.SEAMLESS_SUBMIT_BUTTON )
		.removeAttribute( "disabled" );
}

/**
 * Renders the actual seamless form
 *
 * @since 3.1.0
 */
function renderSeamlessForm( response ) {
	let responseData = JSON.parse( response.data );
	/** @function WPP.seamlessRender */
	WPP.seamlessRender(
		{
			requestData: responseData,
			wrappingDivId: Constants.SEAMLESS_FORM_CONTAINER,
			onSuccess: onFormRendered,
			onError: logError,
		}
	);
}

/**
 * Render seamless form from WPP in iframe
 * 
 * @since 3.1.0
 */
function submitSeamlessForm() {
	setSpinnerState( Spinner.STATE_ON, Spinner.PAY_BUTTON_SPINNER );
	jQuery( this ).blur();
	/** @function WPP.seamlessSubmit */
	WPP.seamlessSubmit(
		{
			wrappingDivId: Constants.SEAMLESS_FORM_CONTAINER,
			onSuccess: onFormSubmitted,
			onError: onSubmitError,
		}
	);
}

/**
 * Submit the data so we can do a proper transaction
 *
 * @param {object} response
 * @since 3.1.0
 */
function onFormSubmitted( response ) {
	response["action"]   = Actions.SUBMIT_CREDIT_CARD_RESPONSE;
	response["cc_nonce"] = document.querySelector( Constants.NONCE_SELECTOR ).value;

	saveTokenToVault( response )
		.then(
			function () {
				submitCreditCardResponse( response )
					.then( handleSubmitResult )
					.fail( logError );
			}
		);
}

/**
 * Submit credit card response from WPP
 * 
 * @param {Object} response
 * @since 3.1.0
 */
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
 * @since 3.1.0
 */
function handleSubmitResult( response ) {
	let data = response.data;

	if ( "error" === data.result ) {
		document.location.reload();
		return;
	}

	document.location = data.redirect;
}

/**
 * Save a new credit card token to our vault.
 *
 * @param {Object} response
 * @returns {jQuery}
 * @param {string} response.token_id - tokenized id of saving credit card
 * @param {string} response.masked_account_number - masked credit card
 * @param {string} response.transaction_state - transaction state
 * @since 3.1.0
 */
function saveTokenToVault(response ) {
	let deferred      = jQuery.Deferred();
	let vaultCheckbox = jQuery( "#wirecard-store-card" );
	let request       = {
		"action": Actions.SAVE_CREDIT_CARD_TO_VAULT,
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
 * @param {Object} data
 * @since 3.1.0
 */
function onSubmitError( data ) {
	setSpinnerState(Spinner.STATE_OFF, Spinner.PAY_BUTTON_SPINNER);
	if ( "transaction_state" in data ) {
		getCreditCardData()
			.then( renderForm )
			.fail( logError )
			.always(
				function() {
					setSpinnerState( Spinner.STATE_OFF, Spinner.FORM_SPINNER );
				}
			);
		jQuery( Constants.MESSAGE_CONTAINER ).css( "display", "block" );
	}
	logError( data );
}

/**
 * Deleting token from view and vault
 * 
 * @since 3.1.0
 */
function onTokenDeleted() {
	let self = this;
	jQuery( self ).append( phpVars.spinner );
	let vault_id = jQuery( self ) .data( 'vault-id' );
	
	if ( vault_id ) {
		deleteTokenFromVault( vault_id )
			.done( function () {
				self.closest( 'tr' ).remove();
			} )
			.fail( logError );
	}
}

/**
 * Delete a saved credit card from the vault
 *
 * @param {number} vault_id
 * @since 3.1.0
 */
function deleteTokenFromVault(vault_id ) {
	return callAjax(
		phpVars.vault_delete_url,
		"POST",
		{ 
			"action" : Actions.REMOVE_CREDIT_CARD_FROM_VAULT,
			"vault_id": vault_id
		}
	);
}

jQuery(document).on("ready", function() {
	initEventHandlers();
	initializeForm();
});
