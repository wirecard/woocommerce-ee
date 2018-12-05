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

$                 = jQuery;
var popup         = $( "#dialog" );
var checkout_form = $( "form.checkout" );
var sepa_check    = false;

/**
 * Validate if inputs are set
 */
function validate_inputs() {
	var validation = true;
	$( ".wc-sepa-input" ).each(
		function () {
			if ( ! $( this ).val() ) {
				$( this ) .focus();
				validation = false;
				return;
			}
		}
	);
	return validation;
}

function process_order() {
	if ( document.getElementById( "sepa-check" ).checked ) {
		sepa_check = true;
		checkout_form.submit();
	} else {
		popup.dialog( "close" );
	}
}

function check_change() {
	if ( document.getElementById( "sepa-check" ).checked ) {
		/* global sepa_var b:true */
		$( "#sepa-button" ).text( sepa_var.sepa_process_text );
	} else {
		/* global sepa_var b:true */
		$( "#sepa-button" ).text( sepa_var.sepa_cancel_text );
	}
}

/**
 * Process data and open popup
 *
 * @param content
 * @returns {boolean}
 */
function openPopup( content ) {
	popup.html( content );
	popup.find( ".first_last_name" ).text( $( "#sepa_firstname" ).val() + " " + $( "#sepa_lastname" ).val() );
	popup.find( ".bank_iban" ).text( $( "#sepa_iban" ).val() );
	popup.find( ".bank_bic" ).text( $( "#sepa_bic" ).val() );
	var screen_height    = window.screen.height;
	var adjust_to_screen = screen_height * 0.8;

	if ( screen_height > 1000 ) {
		adjust_to_screen = 800;
	}

	popup.dialog(
		{
			height: adjust_to_screen,
			width: "auto"
		}
	);
	popup.dialog( "open" );
	$( "body" ).css( "overflow", "hidden" );

	var button = document.getElementById( "sepa-button" );
	button.addEventListener( "click", process_order, false );

	var check_box = document.getElementById( "sepa-check" );
	check_box.addEventListener( "change", check_change, false );

	return false;
}

/**
 * Get SEPA mandate template
 */
function get_sepa_mandate_data() {
	$.ajax(
		{
			type: "GET",
			url: sepa_var.ajax_url,
			data: { "action" : "get_sepa_mandate" },
			dataType: "json",
			success: function ( response ) {
				openPopup( response.data );
			},
			error: function ( response ) {
				console.log( response );
			}
		}
	);
}

$( document ).off( "checkout_error" ).on(
	"checkout_error",
	"body",
	function () {
		popup.dialog( "close" );
	}
);

$( document.body ).on(
	"updated_checkout",
	function() {
			/**
			* Create popup window
			*/
			popup.dialog(
				{
					autoOpen :false,
					modal: true,
					show: "blind",
					hide: "blind"
				}
			);
	}
);

$( document.body ).on(
	"dialogclose",
	popup,
	function() {
		$( "body" ).css( "overflow", "auto" );
	}
);

/**
 * Submit the seamless form before order is placed
 * @since 1.0.0
 */
checkout_form.on(
	"checkout_place_order",
	function() {
		if ( $( "#payment_method_wirecard_ee_sepadirectdebit" ).is( ":checked" ) ) {
			if ( ! sepa_check ) {
				if ( validate_inputs() === false ) {
					return false;
				}
				get_sepa_mandate_data();
				return false;
			} else {
				sepa_check = false;
				popup.dialog( "close" );
				return true;
			}
		}
	}
);
