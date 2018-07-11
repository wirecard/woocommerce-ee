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

var token = null;
var processing = false;

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
    ).appendTo( jQuery( 'form.checkout' ) );
}

/**
 * Delete cc from Vault
 *
 * @param int id
 * @since 1.1.0
 */
function deleteCard( id ) {
	var saved_credit_cards = jQuery( '#wc_payment_method_wirecard_creditcard_vault' );

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
                getVaultData(saved_credit_cards);
            },
            error: function (data) {
                console.log( data );
            }
        }
    );
}

/**
 * Get stored cc from Vault
 *
 * @since 1.1.0
 */
function getVaultData(saved_credit_cards) {
    var new_credit_card = $( '#wc_payment_method_wirecard_new_credit_card' );
    jQuery( '.show-spinner', saved_credit_cards ).show();
    jQuery.ajax(
        {
            type: 'GET',
            url: php_vars.vault_get_url,
            data: { 'action' : 'get_cc_from_vault' },
            dataType: 'json',
            success: function ( data ) {
                if ( false != data.data) {
                    addVaultData( data.data, saved_credit_cards );
                } else {
                    jQuery( '.cards', saved_credit_cards ).empty();
                    jQuery( '.show-spinner', saved_credit_cards ).hide();
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
function addVaultData( data, saved_credit_cards ) {
    jQuery( '.cards', saved_credit_cards ).html( data );
    jQuery( '.show-spinner', saved_credit_cards ).hide();
}

jQuery( document ).ajaxComplete( function() {
	var saved_credit_cards = jQuery( '#wc_payment_method_wirecard_creditcard_vault' );
	var checkout_form      = jQuery( 'form.checkout' );

	if ( jQuery( '.cards' ).html() == '' ) {
        getVaultData( saved_credit_cards );
    }

    if ( jQuery( '#wc_payment_method_wirecard_creditcard_form' ).find('iframe').length == 0 ) {
        getRequestData( renderForm, logCallback );
    }

    jQuery( document ).on(
        'checkout_error', 'body',function() {
            getRequestData( renderForm, logCallback );
        }
    );

    jQuery( document).on('change', 'input[name=payment_method]',
        function() {
            if ( jQuery( this ).val() === 'wirecard_ee_creditcard' ) {
                getRequestData( renderForm, logCallback );
                getVaultData();
                return false;
            }
        }
    );

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
     * Get data rquired to render the form
     *
     * @since 1.0.0
     */
    function getRequestData( success, error ) {
    	$( '#wc_payment_method_wirecard_creditcard_form' ).empty();
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
});
