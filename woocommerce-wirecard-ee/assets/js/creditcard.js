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
var checkout_form      = null;
var processing         = false;
$                      = jQuery;
var saved_credit_cards = null;
var new_credit_card    = null;

/**
 * Add token to submit form
 *
 * @since 1.0.0
 */
function setToken() {
	token = $( "input[name='token']:checked" ).data( 'token' );
	jQuery( '<input>' ).attr(
		{
			type: 'hidden',
			name: 'tokenId',
			id: 'tokenId',
			value: token
		}
	).appendTo( checkout_form );
}

/**
 * Get stored cc from Vault
 *
 * @since 1.1.0
 */
function getVaultData() {
	$( '.show-spinner', saved_credit_cards ).show();
	$.ajax(
		{
			type: 'GET',
			url: php_vars.vault_get_url,
			data: { 'action' : 'get_cc_from_vault' },
			dataType: 'json',
			success: function ( data ) {
				if ( false != data.data) {
					addVaultData( data.data );
				} else {
					$( '.cards', saved_credit_cards ).empty();
					$( '.show-spinner', saved_credit_cards ).hide();
					new_credit_card.trigger( 'click' );
				}
			},
			error: function (data) {
				console.log( data );
			}
		}
	);
}

/**
 * Append cc to frontend
 *
 * @param array data
 * @since 1.1.0
 */
function addVaultData( data ) {
	$( '.cards', saved_credit_cards ).html( data );
	$( '.show-spinner', saved_credit_cards ).hide();
}

/**
 * Delete cc from Vault
 *
 * @param int id
 * @since 1.1.0
 */
function deleteCard( id ) {
	token = null;
	$( '.show-spinner', saved_credit_cards ).show();
	$( '.cards', saved_credit_cards ).empty();
	$.ajax(
		{
			type: 'POST',
			url: php_vars.vault_delete_url,
			data: { 'action' : 'remove_cc_from_vault', 'vault_id': id },
			dataType: 'json',
			success: function () {
				getVaultData();
			},
			error: function (data) {
				console.log( data );
			}
		}
	);
}

$( document ).ready(
	function() {
		$( document.body ).on(
			'checkout_error', function() {
				getRequestData( renderForm, logCallback );
			}
		);

		checkout_form      = $( 'form.checkout' );
		saved_credit_cards = $( '#wc_payment_method_wirecard_creditcard_vault' );
		new_credit_card    = $( '#wc_payment_method_wirecard_new_credit_card' );
		new_credit_card.hide();

		getVaultData();
		getRequestData( renderForm, logCallback );

		$( "input[name=payment_method]" ).change(
			function() {
				if ( $( this ).val() === 'wirecard_ee_creditcard' ) {
					getRequestData( renderForm, logCallback );
					getVaultData();
					return false;
				}
			}
		);

		/**
		 * Click on stored credit card
		 *
		 * @since 1.1.0
		 */
		$( '#open-vault-popup' ).on(
			'click', function () {
				saved_credit_cards.slideToggle();
				new_credit_card.slideUp();
				$( 'span', '#open-new-card' ).removeClass( 'dashicons-arrow-up' ).addClass( 'dashicons-arrow-down' );
				$( 'span', $( this ) ).toggleClass( 'dashicons-arrow-down' ).toggleClass( 'dashicons-arrow-up' );
			}
		);

		/**
		 * Click on new credit card
		 *
		 * @since 1.1.0
		 */
		$( '#open-new-card' ).on(
			'click', function () {
				token = null;
				new_credit_card.slideToggle();
				saved_credit_cards.slideUp();
				$( 'input', saved_credit_cards ).prop( 'checked', false );
				$( 'span', '#open-vault-popup' ).removeClass( 'dashicons-arrow-up' ).addClass( 'dashicons-arrow-down' );
				$( 'span', $( this ) ).toggleClass( 'dashicons-arrow-down' ).toggleClass( 'dashicons-arrow-up' );
			}
		);

		/**
		* Submit the seamless form before order is placed
		*
		* @since 1.0.0
		*/
		checkout_form.on(
			'checkout_place_order', function() {
				if ( $( '#payment_method_wirecard_ee_creditcard' )[0].checked === true && processing === false ) {
					processing = true;
					if ( token !== null ) {
						return true;
					} else {
						getRequestData( submitForm, logCallback );
						return false;
					}
				}
			}
		);

		/**
		 * Submit Payment page seamless form
		 *
		 * @param request_data
		 * @since 1.1.0
		 */
		function submitForm( request_data ) {
			WirecardPaymentPage.seamlessSubmitForm(
				{
					onSuccess: formSubmitSuccessHandler,
					onError: logCallback,
					requestData: request_data,
					wrappingDivId: "wc_payment_method_wirecard_creditcard_form"
				}
			);
		}

		/**
		* Display error massages
		*
		* @since 1.0.0
		*/
		function logCallback( response ) {
			console.error( response );
			processing = false;
			token      = null;
		}

		/**
		* Add the tokenId to the submited form
		*
		* @since 1.0.0
		*/
		function formSubmitSuccessHandler( response ) {
			if ( response.hasOwnProperty( 'token_id' ) ) {
				token = response.token_id;
			} else if ( response.hasOwnProperty( 'card_token' ) && response.card_token.hasOwnProperty( 'token' )) {
				token = response.card_token.token;

				var fields = [ "expiration_month", "expiration_year" ];

				for ( var el in  fields ) {
					el          = fields[el];
					var element = $( "#" + el );
					if ( element.length > 0 ) {
						element.remove();
					} else {
						jQuery( '<input>' ).attr(
							{
								type: 'hidden',
								name: el,
								id: '#' + el,
								value: response.card[el]
							}
						).appendTo( checkout_form );
					}
				}
			}

			if ( $( "#wirecard-store-card" ).is( ":checked" ) && response.transaction_state === 'success' ) {
				$.ajax(
					{
						type: 'POST',
						url: php_vars.vault_url,
						data: { 'action' : 'save_cc_to_vault', 'token' : response.token_id, 'mask_pan' : response.masked_account_number },
						dataType: 'json',
						error: function (data) {
							console.log( data );
						}
					}
				);
			}

			if ( jQuery( "#tokenId" ).length > 0 ) {
				jQuery( "#tokenId" ).remove();
			}

			jQuery( '<input>' ).attr(
				{
					type: 'hidden',
					name: 'tokenId',
					id: 'tokenId',
					value: token
				}
			).appendTo( checkout_form );

			checkout_form.submit();
		}

		/**
		 * Get data rquired to render the form
		 *
		 * @since 1.0.0
		 */
		function getRequestData( success, error ) {
			$( '.show-spinner' ).show();
			$.ajax(
				{
					type: 'POST',
					url: php_vars.ajax_url,
					cache: false,
					data: { 'action' : 'get_credit_card_request_data' },
					dataType: 'json',
					success: function (data) {
						$( '.show-spinner' ).hide();
						success( JSON.parse( data.data ) );
					},
					error: function (data) {
						$( '.show-spinner' ).hide();
						error( data );
					}
				}
			);
		}

		/**
	 * Render the credit card form
	 *
	 * @since 1.0.0
	 */
		function renderForm( request_data ) {
			WirecardPaymentPage.seamlessRenderForm(
				{
					requestData: request_data,
					wrappingDivId: "wc_payment_method_wirecard_creditcard_form",
					onSuccess: resizeIframe,
					onError: logCallback
				}
			);
		}

		/**
	 * Resize the credit card form when loaded
	 *
	 * @since 1.0.0
	 */
		function resizeIframe() {
			$( '.show-spinner' ).hide();
			$( '.save-later' ).show();
			$( "#wc_payment_method_wirecard_creditcard_form > iframe" ).height( 550 );
		}
	}
);
