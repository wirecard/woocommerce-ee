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

let Url = {
	SAVE_TOKEN_TO_VAULT: phpVars.vault_url,
	DELETE_TOKEN_FROM_VAULT: phpVars.vault_delete_url,
	GET_TOKEN_FORMATTED_VIEW: phpVars.vault_get_url,
	PROCESS_PAYMENT: phpVars.submit_url,
	GET_CREDIT_CARD_REQUEST_DATA: phpVars.ajax_url,
};

let Spinner = {
	FORM_SPINNER: ".show-spinner",
	PAY_BUTTON_SPINNER: "#wd-cc-submit-spinner",
	STATE_ON: "display",
	STATE_OFF: "none",
	DELETE_TOKEN_SPINNER: phpVars.spinner
};

let DELETE_BUTTON = {
	FROM_VAULT: "delete-from-vault",
	FROM_VAULT_DISABLED: "delete-from-vault-disabled",
	WD_BUTTON: "wd-card-delete",
	WD_BUTTON_DISABLED: "wd-card-delete-disabled",
};

let Constants = {
	IFRAME_HEIGHT: "270",
	USE_CARD_ID: "input[data-token]",
	DELETE_CARD_BUTTON_ID: ".wd-card-delete",
	SAVE_CARD_CHECKMARK_ID: "#wirecard-store-card",
	VAULT_CONTENT_CONTAINER: "#wc_payment_method_wirecard_creditcard_vault .cards",
	VAULT_CONTAINER: "#wc_payment_method_wirecard_creditcard_vault",
	VAULT_TABLE_ID: "#vault-table",
	NEW_CARD_CONTENT_AREA: "#wc_payment_method_wirecard_new_credit_card",
	NEW_CARD_CONTENT_AREA_IFRAME: "#wc_payment_method_wirecard_new_credit_card iframe",
	SEAMLESS_SUBMIT_BUTTON: "#seamless-submit",
	SEAMLESS_FORM_CONTAINER: "wc_payment_method_wirecard_creditcard_form",
	NONCE_SELECTOR: "#wc_payment_method_wirecard_creditcard_response_form input[name='cc_nonce']",
	MESSAGE_CONTAINER: "#wd-creditcard-messagecontainer",
	WD_TOKEN_ID_PREFIX: "wd-token-",
};

Object.freeze( Actions );
Object.freeze( Url );
Object.freeze( Spinner );
Object.freeze( DELETE_BUTTON );
Object.freeze( Constants );

/**
 * Log any error that has occurred.
 *
 * @param {*} data
 * @since 1.7.0
 */
function logError( data ) {
	console.error( "An error occurred: ", data );
}

/**
 * jQuery ajax wrapper
 *
 * @param {string} request_url
 * @param {string} method
 * @param {*} request_data
 * @returns {jQuery}
 * @since 3.1.0
 */
function callAjax(request_url, method, request_data) {
	return jQuery.ajax(
		{
			type: method,
			url: request_url,
			data: request_data,
			dataType: "json",
		}
	);
}

/**
 * Set states for spinner object
 *
 * @param {string} state
 * @param {string} selector
 * @since 3.1.0
 */
function setSpinnerState(state, selector) {
	switch (state) {
		case Spinner.STATE_OFF:
			document.querySelector( selector ).style.display = "none";
			break;
		case Spinner.STATE_ON:
			document.querySelector( selector ).style.display = "block";
			break;
	}
}

/**
 * Fade out form spinner
 *
 * @since 3.1.0
 */
function turnOffFormSpinner() {
	setSpinnerState( Spinner.STATE_OFF, Spinner.FORM_SPINNER );
}

/**
 * Process error message from WPP
 *
 * @param {Object} response
 * @since 3.1.0
 */
function showErrorMessageFromResponse( response ) {
	// Invalid configuration
	if (response.hasOwnProperty( "error_1" )) {
		return;
	}

	let errorMessage = "";
	response.errors.forEach(
		function ( item ) {
			errorMessage += "<li>" + item.error.description + "</li>";
		}
	);

	if ( errorMessage ) {
		errorMessage              = "<ul class='woocommerce-error' role='alert'>" + errorMessage + "</ul>";
		let errorMessageContainer = jQuery( Constants.MESSAGE_CONTAINER );
		errorMessageContainer.empty();
		errorMessageContainer.html( errorMessage );
		errorMessageContainer.show();
	}

	logError( response );
}

/**
 * Hide error message container
 *
 * @since 3.1.0
 */
function hideErrorMessage() {
	let errorMessageContainer = jQuery( Constants.MESSAGE_CONTAINER );
	errorMessageContainer.empty();
	errorMessageContainer.hide();
}

/**
 * Disable radio button selection
 *
 * @since 3.1.0
 */
function disableTokenSelection() {
	jQuery( Constants.USE_CARD_ID ).attr( "disabled", "disabled" );
}

/**
 * Enable radio button selection
 *
 * @since 3.1.0
 */
function enableTokenSelection() {
	jQuery( Constants.USE_CARD_ID ).removeAttr( "disabled" );
}

/**
 * Get saved token from vault
 *
 * @since 3.1.0
 */
function getFormattedTokenViewFromVault() {
	return callAjax(
		Url.GET_TOKEN_FORMATTED_VIEW,
		"GET",
		{ action : Actions.GET_CREDIT_CARD_FROM_VAULT }
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
 * Delete a saved credit card from the vault
 *
 * @param {number} id
 * @since 3.1.0
 */
function deleteTokenFromVault( id ) {
	return callAjax(
		Url.DELETE_TOKEN_FROM_VAULT,
		"POST",
		{
			action : Actions.REMOVE_CREDIT_CARD_FROM_VAULT,
			vault_id: id
		}
	);
}

/**
 * Deleting token from view and vault
 *
 * @since 3.1.0
 */
function onTokenDeleted() {
	let self = this;
	jQuery( self ).find( "." + DELETE_BUTTON.FROM_VAULT ).append( Spinner.DELETE_TOKEN_SPINNER );
	let vault_id = jQuery( self ) .data( "vault-id" );

	if ( vault_id ) {
		deleteTokenFromVault( vault_id )
			.done(
				function () {
					self.closest( "tr" ).remove();
					if ( ! jQuery( Constants.DELETE_CARD_BUTTON_ID ).length ) {
						jQuery( Constants.VAULT_CONTAINER ).remove();
					}
				}
			)
			.fail( logError );
	}
}

/**
 * Replace class for chosen selector
 *
 * @param {jQuery} $selector
 * @param {string} needle
 * @param {string} replacement
 * @since 3.1.0
 */
function replaceClassForSelector( $selector, needle, replacement ) {
	$selector.removeClass( needle ).addClass( replacement );
}

/**
 * Enable all delete buttons on select credit card
 *
 * @since 3.1.0
 */
function enableDeleteButtons() {
	jQuery( Constants.DELETE_CARD_BUTTON_ID ).off( "click" );
	jQuery( "[id^=" + Constants.WD_TOKEN_ID_PREFIX + "]" ).filter(
		function() {
			let $selector = jQuery( this );
			replaceClassForSelector(
				$selector.find(
					"." + DELETE_BUTTON.FROM_VAULT_DISABLED
				),
				DELETE_BUTTON.FROM_VAULT_DISABLED,
				DELETE_BUTTON.FROM_VAULT
			);
			replaceClassForSelector(
				$selector,
				DELETE_BUTTON.WD_BUTTON_DISABLED,
				DELETE_BUTTON.WD_BUTTON
			);
			$selector.on( "click", onTokenDeleted );
		}
	);
}

/**
 * Disable delete button for selected credit card
 *
 * @param {string} token
 * @since 3.1.0
 */
function disableDeleteButtonByToken( token ) {
	let $selector = jQuery( "#" + Constants.WD_TOKEN_ID_PREFIX + token );

	replaceClassForSelector(
		$selector,
		DELETE_BUTTON.WD_BUTTON,
		DELETE_BUTTON.WD_BUTTON_DISABLED
	);

	replaceClassForSelector(
		$selector.find( "." + DELETE_BUTTON.FROM_VAULT ),
		DELETE_BUTTON.FROM_VAULT,
		DELETE_BUTTON.FROM_VAULT_DISABLED
	);

	$selector.off( "click" );
}

/**
 * Gets the request data from the server.
 *
 * @param {number|null} selectedToken
 * @returns {jQuery}
 * @since 3.1.0
 */
function getCreditCardData( selectedToken ) {
	selectedToken = (typeof selectedToken !== "undefined") ? selectedToken : null;
	return callAjax(
		Url.GET_CREDIT_CARD_REQUEST_DATA,
		"POST",
		{
			action: Actions.GET_CREDIT_CARD_REQUEST_DATA,
			vault_token: selectedToken
		}
	);
}

/**
 * Configure iframe view
 *
 * @since 3.1.0
 */
function configureSeamlessIframe() {
	jQuery( Constants.NEW_CARD_CONTENT_AREA_IFRAME ).height( Constants.IFRAME_HEIGHT );
}

/**
 * Enable submit button
 *
 * @since 3.1.0
 */
function enableSubmitButton() {
	jQuery( Constants.SEAMLESS_SUBMIT_BUTTON ).removeAttr( "disabled" );
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
 * Renders the actual seamless form
 *
 * @param {Object} response
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
			onError: showErrorMessageFromResponse,
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
		Url.PROCESS_PAYMENT,
		"POST",
		response
	);
}

/**
 * Handle the results of the form submission.
 *
 * @param {Object} response
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
	let deferred      = jQuery.Deferred(); // eslint-disable-line new-cap
	let vaultCheckbox = jQuery( Constants.SAVE_CARD_CHECKMARK_ID );
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
		Url.SAVE_TOKEN_TO_VAULT,
		"POST",
		request
	);
}

/**
 * Submit the data so we can do a proper transaction
 *
 * @param {Object} response
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
 * Display error message after failure submit and hide processing spinner
 *
 * @param {Object} data
 * @since 3.1.0
 */
function onSubmitError( data ) {
	setSpinnerState( Spinner.STATE_OFF, Spinner.PAY_BUTTON_SPINNER );
	if ( "transaction_state" in data ) {
		getCreditCardData()
			.then( renderSeamlessForm )
			.fail( logError )
			.always( turnOffFormSpinner );
		jQuery( Constants.MESSAGE_CONTAINER ).css( "display", "block" );
	}
	logError( data );
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
 * Loads the card list for one-click and renders the seamless form
 *
 * @param {number|null} tokenId
 * @since 3.1.0
 */
function initializeForm( tokenId )
{
	tokenId = (typeof tokenId !== "undefined") ? tokenId : null;
	disableTokenSelection();
	getCreditCardData( tokenId )
		.then( renderSeamlessForm )
		.fail( logError )
		.always( turnOffFormSpinner )
		.always( enableTokenSelection );
}

/**
 * Trigger event that load filled credit card form
 *
 * @since 3.1.0
 */
function onTokenSelected() {
	let token = jQuery( this ).data( "token" );
	hideErrorMessage();
	enableDeleteButtons();
	disableDeleteButtonByToken( token );
	setSpinnerState( Spinner.STATE_ON, Spinner.FORM_SPINNER );
	initializeForm( token );
}

/**
 * Initializes token event handlers for the interface
 *
 * @since 3.1.0
 */
function initializeTokenEventHandlers() {
	jQuery( Constants.USE_CARD_ID ).on( "change", onTokenSelected );
	jQuery( Constants.DELETE_CARD_BUTTON_ID ).on( "click", onTokenDeleted );
}

/**
 * Initializes the vault interface as required.
 *
 * @since 3.1.0
 */
function initializeVault() {
	getFormattedTokenViewFromVault()
		.then( loadTokenTable )
		.then( initializeTokenEventHandlers )
		.fail( logError )
		.always( turnOffFormSpinner );
}

/**
 * Get saved tokens and init view
 *
 * @since 3.1.0
 */
function initializeTokenList() {
	let hasSavedTokens = jQuery( Constants.VAULT_TABLE_ID );
	if ( typeof(hasSavedTokens) === "undefined" || ! hasSavedTokens.length ) {
		initializeVault();
	}
}

/**
 * Initializes general event handlers for the interface
 *
 * @since 2.4.0
 */
function initializeEventHandlers()
{
	jQuery( Constants.SEAMLESS_SUBMIT_BUTTON ).on( "click", submitSeamlessForm );
}

jQuery( document ).ready(
	function() {
		initializeEventHandlers();
		initializeTokenList();
		initializeForm();
	}
);
