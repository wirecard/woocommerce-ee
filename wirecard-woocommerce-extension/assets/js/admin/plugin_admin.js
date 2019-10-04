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

$ = jQuery;
$( document ).ready(
	function () {
			var button = $( ".wc_wirecard_test_credentials_button" );

			/* global admin_vars b:true */
			button.removeClass( "regular-input" ).val( admin_vars.test_credentials_button );
			button.on(
				"click",
				function () {
					var base_id = $( this ).attr( "id" ).replace( "_test_button", "" );

					var base_url  = $( "#" + base_id + "_base_url" ).val();
					var http_user = $( "#" + base_id + "_http_user" ).val();
					var http_pass = $( "#" + base_id + "_http_pass" ).val();

					$.ajax(
						{
							type: "POST",
							/* global admin_vars b:true */
							url: admin_vars.admin_url,
							data: { "action" : "test_payment_method_config", "base_url" : base_url, "http_user" : http_user, "http_pass" : http_pass, "admin_nonce" : admin_vars.admin_nonce },
							dataType: "json",
							success: function (data) {
								alert( data.data );
							},
							error: function (data) {
								// This occurs if the PHP script dies. So the error message is hardcoded
								// Usually it is an invalid url that gets called
								let msg = "An undefined error occured!";
								if ( data.status === 500 ) {
									msg = base_url + " is invalid!";
								}
								alert( msg + " Test failed, please check your credentials." );
							}
						}
					);

				}
			);
	}
);
