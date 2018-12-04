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

var token              = null;
var processing         = false;
var saved_credit_cards = jQuery( "#wc_payment_method_wirecard_creditcard_vault" );
var checkout_form      = jQuery( "form.checkout" );
var new_credit_card    = jQuery( "#wc_payment_method_wirecard_new_credit_card" );

/**
 * Add token to submit form
 *
 * @since 1.0.0
 */
function setToken() {
	token = jQuery( "input[name='token']:checked" ).data( "token" );
	jQuery( "<input>" ).attr(
		{
			type: "hidden",
			name: "tokenId",
			id: "tokenId",
			value: token
		}
	).appendTo( checkout_form );
}

/**
 * Append cc to frontend
 *
 * @param array data
 * @since 1.1.0
 */
function addVaultData( data, saved_credit_cards ) {
	jQuery( ".cards", saved_credit_cards ).html( data );
	jQuery( ".show-spinner", saved_credit_cards ).hide();
	jQuery( "#wc_payment_method_wirecard_creditcard_vault" ).slideDown();
}

/**
 * Get stored cc from Vault
 *
 * @since 1.1.0
 */
function getVaultData( saved_credit_cards ) {
	var new_credit_card = jQuery( "#wc_payment_method_wirecard_new_credit_card" );
	jQuery( ".show-spinner", saved_credit_cards ).show();
	jQuery.ajax(
		{
			type: "GET",
			/* global php_vars b:true */
			url: php_vars.vault_get_url,
			data: { "action" : "get_cc_from_vault" },
			dataType: "json",
			success: function ( data ) {
				if ( false !== data.data) {
					addVaultData( data.data, saved_credit_cards );
				} else {
					jQuery( ".cards", saved_credit_cards ).empty();
					jQuery( ".show-spinner", saved_credit_cards ).hide();
					new_credit_card.trigger( "click" );
				}
			},
			error: function (data) {
				console.log( data );
			}
		}
	);
}

function loadWirecardEEScripts() {
	/**
	 * Click on stored credit card
	 *
	 * @since 1.1.0
	 */
	jQuery( "#open-vault-popup" ).unbind( "click" ).on(
		"click",
		function () {
			jQuery( "#wc_payment_method_wirecard_creditcard_vault" ).slideToggle();
			jQuery( "#wc_payment_method_wirecard_new_credit_card" ).slideUp();
			jQuery( "span", "#open-new-card" ).removeClass( "dashicons-arrow-up" ).addClass( "dashicons-arrow-down" );
			jQuery( "span", jQuery( this ) ).toggleClass( "dashicons-arrow-down" ).toggleClass( "dashicons-arrow-up" );
		}
	);

	/**
	 * Click on new credit card
	 *
	 * @since 1.1.0
	 */
	jQuery( "#open-new-card" ).unbind( "click" ).on(
		"click",
		function () {
			token = null;
			jQuery( "#wc_payment_method_wirecard_new_credit_card" ).slideToggle();
			jQuery( "#wc_payment_method_wirecard_creditcard_vault" ).slideUp();
			jQuery( "input", saved_credit_cards ).prop( "checked", false );
			jQuery( "span", "#open-vault-popup" ).removeClass( "dashicons-arrow-up" ).addClass( "dashicons-arrow-down" );
			jQuery( "span", jQuery( this ) ).toggleClass( "dashicons-arrow-down" ).toggleClass( "dashicons-arrow-up" );
		}
	);
}

/**
 * Delete cc from Vault
 *
 * @param int id
 * @since 1.1.0
 */
function deleteCard( id ) {
	var saved_credit_cards = jQuery( "#wc_payment_method_wirecard_creditcard_vault" );

	token = null;
	jQuery( ".show-spinner", saved_credit_cards ).show();
	jQuery( ".cards", saved_credit_cards ).empty();
	jQuery.ajax(
		{
			type: "POST",
			/* global php_vars b:true */
			url: php_vars.vault_delete_url,
			data: { "action" : "remove_cc_from_vault", "vault_id": id },
			dataType: "json",
			success: function () {
				getVaultData( saved_credit_cards );
			},
			error: function (data) {
				console.log( data );
			}
		}
	);
}

/**
 * Resize the credit card form when loaded
 *
 * @since 1.0.0
 */
function resizeIframe() {
	jQuery( ".show-spinner" ).hide();
	jQuery( ".save-later" ).show();
	jQuery( "#wc_payment_method_wirecard_creditcard_form > iframe" ).height( 550 );
}

/**
 * Display error massages
 *
 * @since 1.0.0
 */
function logCreditCardCallback( response ) {
	console.error( response );
	processing = false;
	token      = null;
}

/**
 * Render the credit card form
 *
 * @since 1.0.0
 */
function renderForm( request_data ) {
	/* global WirecardPaymentPage b:true */
	WirecardPaymentPage.seamlessRenderForm(
		{
			requestData: request_data,
			wrappingDivId: "wc_payment_method_wirecard_creditcard_form",
			onSuccess: resizeIframe,
			onError: logCreditCardCallback
		}
	);
}

/**
 * Get data rquired to render the form
 *
 * @since 1.0.0
 */
function getRequestData( success, error ) {
	jQuery( "#wc_payment_method_wirecard_creditcard_form" ).empty();
	jQuery( ".show-spinner" ).show();
	jQuery.ajax(
		{
			type: "POST",
			url: php_vars.ajax_url,
			cache: false,
			data: {"action": "get_credit_card_request_data"},
			dataType: "json",
			success: function ( data ) {
				jQuery( ".show-spinner" ).hide();
				success( JSON.parse( data.data ) );
			},
			error: function ( data ) {
				jQuery( ".show-spinner" ).hide();
				error( data );
			}
		}
	);
}

function loadCreditCardData() {
	getRequestData( renderForm, logCreditCardCallback );
	getVaultData();
	return false;
}

/**
 * this method gets called whenever the payment selection changes or checkout data was updated
 *
 * @since 1.4.3
 */
function paymentMethodChangeAndCheckoutUpdateEvent() {
	var paymentMethod = jQuery( 'li.wc_payment_method > input[name=payment_method]:checked' ).val();

	if ( paymentMethod === "wirecard_ee_creditcard" ) {
		loadCreditCardData();
		loadWirecardEEScripts();
	}

	if ( false === processing ) {
		saved_credit_cards = jQuery( "#wc_payment_method_wirecard_creditcard_vault" );
		checkout_form      = jQuery( "form.checkout" );
		new_credit_card    = jQuery( "#wc_payment_method_wirecard_new_credit_card" );
		new_credit_card.hide();
		loadWirecardEEScripts();
	}

	if ( jQuery( ".cards" ).html() === "" &&
		paymentMethod === "wirecard_ee_creditcard" ) {
		getVaultData( saved_credit_cards );
	}
}

/**
 * this function gets called whenever place order button is pressed
 *
 * @since 1.4.3
 */
function placeOrderEvent() {
	/**
	 * Add the tokenId to the submited form
	 *
	 * @since 1.0.0
	 */
	function formSubmitSuccessHandler( response ) {
		if ( response.hasOwnProperty( "token_id" ) ) {
			token = response.token_id;
		} else if ( response.hasOwnProperty( "card_token" ) && response.card_token.hasOwnProperty( "token" )) {
			token = response.card_token.token;

			var fields = ["expiration_month", "expiration_year"];

			for ( var el in  fields ) {
				if ( ! fields.hasOwnProperty( el ) ) {
					break;
				}
				el          = fields[el];
				var element = jQuery( "#" + el );
				if ( element.length > 0 ) {
					element.remove();
				} else {
					if ( response.card.hasOwnProperty( el ) ) {
						jQuery( "<input>" ).attr(
							{
								type: "hidden",
								name: el,
								id: "#" + el,
								value: response.card[el]
							}
						).appendTo( checkout_form );
					}
				}
			}
		}

		if ( jQuery( "#wirecard-store-card" ).is( ":checked" ) && response.transaction_state === "success" ) {
			jQuery.ajax(
				{
					type: "POST",
					url: php_vars.vault_url,
					data: {
						"action": "save_cc_to_vault",
						"token": response.token_id,
						"mask_pan": response.masked_account_number
					},
					dataType: "json",
					error: function (data) {
						console.log( data );
					}
				}
			);
		}

		if ( jQuery( "#tokenId" ).length > 0 ) {
			jQuery( "#tokenId" ).remove();
		}

		jQuery( "<input>" ).attr(
			{
				type: "hidden",
				name: "tokenId",
				id: "tokenId",
				value: token
			}
		).appendTo( checkout_form );

		checkout_form.submit();
	}

	/**
	 * Submit Payment page seamless form
	 * @since 1.1.0
	 */
	function submitForm() {
		WirecardPaymentPage.seamlessSubmitForm(
			{
				onSuccess: formSubmitSuccessHandler,
				onError: logCreditCardCallback
			}
		);
	}

	if ( jQuery( 'li.wc_payment_method > input[name=payment_method]:checked' ).val() === 'wirecard_ee_creditcard' &&
		processing === false ) {
		processing = true;
		if ( token ) {
			return true;
		} else {
			submitForm();
			return false;
		}
	}
}

jQuery( document ).ready(
	function() {
		checkout_form.on(
			'change', // when payment selection changes
			'input[name^="payment_method"]',
			paymentMethodChangeAndCheckoutUpdateEvent
		).on(
			'checkout_place_order', // when order is placed
			placeOrderEvent
		);

		jQuery( document.body ).on(
			'updated_checkout', // when checkout data gets updated so that we have the correct user data
			paymentMethodChangeAndCheckoutUpdateEvent
		)
	}
);
