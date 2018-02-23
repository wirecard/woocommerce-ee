$ = jQuery;

$( document ).ready(function() {
	/**
	 * Submit the seamless form before order is placed
	 *
	 * @since 1.0.0
	 */
	checkout_form.on( 'checkout_place_order', function() {
		if ( $( '#payment_method_woocommerce_wirecard_sepa' )[0].checked === true ) {
			console.log("stop");
			render_sepa_mandate();
			return false;
		}
	});
	
	function render_sepa_mandate() {
		$data = get_sepa_mandate_data();
	}
	
	function get_sepa_mandate_data() {
		$.ajax({
			type: 'POST',
			url: ajax_url,
			data: { 'action' : 'get_sepa_mandate' },
			dataType: 'json',
			success: function (data) {

			},
			error: function (data) {
				console.log( data );
			}
		});
	}
});