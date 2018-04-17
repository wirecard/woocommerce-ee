var token         = null;
var checkout_form = jQuery( 'form.checkout' );
var processing    = false;
$                 = jQuery;

$( document ).ready(
	function() {
		if ( $( "#wc_payment_method_wirecard_unionpayinternational_form" ).is( ":visible" ) ) {
			getUpiRequestData();
		}

		$( "input[name=payment_method]" ).change(
			function() {
				if ( $( this ).val() === 'wirecard_ee_unionpayinternational' ) {
					getUpiRequestData();
					return false;
				}
			}
		);

		/**
	 * Submit the seamless form before order is placed
	 *
	 * @since 1.1.0
	 */
		checkout_form.on(
			'checkout_place_order', function() {
				if ( $( '#payment_method_wirecard_ee_unionpayinternational' )[0].checked === true && processing === false ) {
					processing = true;
					if ( token !== null ) {
						return true;
					} else {
						WirecardPaymentPage.seamlessSubmitForm(
							{
								onSuccess: formSubmitUpiSuccessHandler,
								onError: logCallback,
								wrappingDivId: "wc_payment_method_wirecard_unionpayinternational_form"
							}
						);
						return false;
					}
				}
				processing = false;
			}
		);

		/**
	 * Display error massages
	 *
	 * @since 1.1.0
	 */
		function logCallback( response ) {
			console.error( response );
		}

		/**
	 * Add the tokenId to the submited form
	 *
	 * @since 1.1.0
	 */
		function formSubmitUpiSuccessHandler( response ) {
			token = response.token_id;
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
	 * Get data required to render the form
	 *
	 * @since 1.1.0
	 */
		function getUpiRequestData() {
			$.ajax(
				{
					type: 'POST',
					url: ajax_url,
					data: { 'action' : 'get_upi_request_data' },
					dataType: 'json',
					success: function (data) {
						renderUpiForm( JSON.parse( data.data ) );
					},
					error: function (data) {
						console.log( data );
					}
				}
			);
		}

		/**
	 * Render the unionpayinternational form
	 *
	 * @since 1.1.0
	 */
		function renderUpiForm( request_data ) {
			WirecardPaymentPage.seamlessRenderForm(
				{
					requestData: request_data,
					wrappingDivId: "wc_payment_method_wirecard_unionpayinternational_form",
					onSuccess: resizeUpiIframe,
					onError: logCallback
				}
			);
		}

		/**
	 * Resize the unionpayinternational form when loaded
	 *
	 * @since 1.1.0
	 */
		function resizeUpiIframe() {
			$( "#wc_payment_method_wirecard_unionpayinternational_form > iframe" ).height( 550 );
		}
	}
);
