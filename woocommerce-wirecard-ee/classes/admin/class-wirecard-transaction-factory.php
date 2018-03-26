<?php
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/handler/class-wirecard-transaction-handler.php' );


use Wirecard\PaymentSdk\Response\SuccessResponse;


/**
 * Class Wirecard_Transaction_Factory
 *
 * Factory for transaction creation and basic views
 *
 * @since 1.0.0
 */
class Wirecard_Transaction_Factory {

	/**
	 * Transaction table name in database
	 *
	 * @since  1.0.0
	 * @access private
	 * @var string
	 */
	private
		$table_name;

	/**
	 * Fields for transaction table view
	 *
	 * @since  1.0.0
	 * @access private
	 * @var array
	 */
	private
		$fields_list;

	/**
	 * Handles back-end operations
	 *
	 * @since  1.0.0
	 * @access private
	 * @var Wirecard_Transaction_Handler
	 */
	private
		$transaction_handler;

	/**
	 * Wirecard_Transaction_Factory constructor.
	 *
	 * @since 1.0.0
	 */
	public
	function __construct() {
		global $wpdb;

		$this->transaction_handler = new Wirecard_Transaction_Handler();
		$this->table_name          = $wpdb->base_prefix . 'wirecard_payment_gateway_tx';
		$this->fields_list         = array(
			'tx_id'                 => array(
				'title' => __( 'Transaction', 'woocommerce-gateway-wirecard' ),
			),
			'transaction_id'        => array(
				'title' => __( 'Transaction ID', 'woocommerce-gateway-wirecard' ),
			),
			'parent_transaction_id' => array(
				'title' => __( 'Parent transaction ID', 'woocommerce-gateway-wirecard' ),
			),
			'payment_method'        => array(
				'title' => __( 'Payment method', 'woocommerce-gateway-wirecard' ),
			),
			'transaction_state'     => array(
				'title' => __( 'Transaction state', 'woocommerce-gateway-wirecard' ),
			),
			'transaction_type'      => array(
				'title' => __( 'Action', 'woocommerce-gateway-wirecard' ),
			),
			'amount'                => array(
				'title' => __( 'Amount', 'woocommerce-gateway-wirecard' ),
			),
			'currency'              => array(
				'title' => __( 'Currency', 'woocommerce-gateway-wirecard' ),
			),
			'order_id'              => array(
				'title' => __( 'Order number', 'woocommerce-gateway-wirecard' ),
			),
		);
	}

	/**
	 * Create new transaction entry in database
	 *
	 * @param WC_Order        $order
	 * @param SuccessResponse $response
	 * @param string          $base_url
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public
	function create_transaction(
		$order, $response, $base_url
	) {
		global $wpdb;

		$parent_transaction_id = '';
		$parent_transaction    = $this->get_transaction( $response->getParentTransactionId() );
		$transaction_state     = 'success';

		if ( $parent_transaction ) {
			$parent_transaction_id = $response->getParentTransactionId();
			// update parent transaction to closed, no back-end ops possible anymore
			$wpdb->update(
				$this->table_name,
				array(
					'closed'            => '1',
					'transaction_state' => 'closed',
				),
				array(
					'transaction_id' => $parent_transaction_id,
				)
			);
		}
		$transaction_link = $this->get_transaction_link( $base_url, $response );
		$wpdb->insert(
			$this->table_name,
			array(
				'transaction_id'        => $response->getTransactionId(),
				'parent_transaction_id' => $parent_transaction_id,
				'payment_method'        => $response->getPaymentMethod(),
				'transaction_state'     => $transaction_state,
				'transaction_type'      => $response->getTransactionType(),
				'amount'                => $order->get_total(),
				'currency'              => $order->get_currency(),
				'order_id'              => $order->get_id(),
				'response'              => wp_json_encode( $response->getData() ),
				'transaction_link'      => $transaction_link,
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get transaction html table for overview beginning from $start to $stop
	 *
	 * @since 1.0.0
	 *
	 * @param int $page
	 *
	 * @return int $row_count
	 */
	public
	function get_rows(
		$page = 1
	) {
		global $wpdb;

		$start = ( $page * 20 ) - 19;

		$start --;
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_tx ORDER BY tx_id DESC LIMIT %d,20", $start ), ARRAY_A );
		$pages = $wpdb->get_row( "SELECT CEILING(COUNT(*)/20) as pages FROM {$wpdb->prefix}wirecard_payment_gateway_tx" );

		if ( is_null( $pages ) ) {
			$pages        = new stdClass();
			$pages->pages = 1;
		}

		echo '<tr>';
		foreach ( $this->fields_list as $field_key => $field_value ) {
			echo '<th>';
			echo $field_value['title'];
			echo '</th>';
		}
		echo '</tr>';

		foreach ( $rows as $row ) {
			echo '<tr>';

			foreach ( $this->fields_list as $field_key => $field_value ) {
				echo '<td>';
				if ( key_exists( $field_key, $row ) ) {
					if ( 'transaction_id' == $field_key || ( 'parent_transaction_id' == $field_key && ! empty( $field_value ) ) ) {
						echo "<a href='?page=wirecardpayment&id={$row[ $field_key ]}'>" . $row[$field_key] . '</a>';
					} else {
						echo $row[$field_key];
					}
				}
				echo '</td>';
			}

			echo '</tr>';
		}

		return $pages->pages;
	}

	/**
	 * Get specific transaction via transaction_id
	 *
	 * @param string $transaction_id
	 *
	 * @return bool|stdClass
	 *
	 * @since 1.0.0
	 */
	public
	function get_transaction(
		$transaction_id
	) {
		global $wpdb;

		$transaction = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_tx WHERE transaction_id = %s", $transaction_id ) );

		if ( empty( $transaction ) ) {
			return false;
		}

		return $transaction;
	}


	/**
	 * Print transaction detail information and possible back-end operations
	 *
	 * @param $transaction_id
	 *
	 * @since 1.0.0
	 */
	public
	function show_transaction(
		$transaction_id
	) {
		$transaction = $this->get_transaction( $transaction_id );
		if ( ! $transaction ) {
			echo __( 'An error occured. The requested transaction could not be found!', 'woocommerce-gateway-wirecard' );

			return;
		}
		/** @var WC_Wirecard_Payment_Gateway $payment */
		$payment = $this->transaction_handler->get_payment_method( $transaction->payment_method );

		$response_data = json_decode( $transaction->response );
		?>
		<link rel='stylesheet' href='<?php echo plugins_url( 'woocommerce-wirecard-ee/assets/styles/admin.css' ); ?>'>
		<div class="wrap">
			<div class="postbox-container">
				<div class="postbox">
					<div class="inside">
						<div class="panel-wrap woocommerce">
							<div class="panel woocommerce-order-data">
								<h2 class="woocommerce-order-data__heading"><?php echo __( 'Transaction', 'woocommerce-gateway-wirecard' ) . $transaction_id; ?></h2>
								<h3>
									<?php echo __( $payment->method_name . ' ' . 'payment', 'woocommerce-gateway-wirecard' ); ?>
								</h3>
								<div><?php echo $transaction->transaction_link; ?></div>
								<br>
								<div class="transaction-type type-authorization"><?php echo $transaction->transaction_type; ?></div>
								<br>
								<div class="wc-order-data-row">
									<?php
									if ( $payment->can_cancel( $transaction->transaction_type ) && ! $transaction->closed ) {
										echo "<a href='?page=cancelpayment&id={$transaction_id}' class='button'>" . __( 'Cancel transaction', 'woocommerce-gateway-wirecard' ) . '</a> ';
									}
									if ( $payment->can_capture( $transaction->transaction_type ) && ! $transaction->closed ) {
										echo "<a href='?page=capturepayment&id={$transaction_id}' class='button'>" . __( 'Capture transaction', 'woocommerce-gateway-wirecard' ) . '</a> ';
									}
									if ( $payment->can_refund( $transaction->transaction_type ) && ! $transaction->closed ) {
										echo "<a href='?page=refundpayment&id={$transaction_id}' class='button'>" . __( 'Refund transaction', 'woocommerce-gateway-wirecard' ) . '</a> ';
									}
									if ( $transaction->closed ) {
										echo "<p class='add-items'>" . __( 'No Back-end operations available for this transaction', 'woocommerce-gateway-wirecard' ) . '</p>';
									}
									?>
									<p class="add-items">
										<a href="?page=wirecardpayment" class="button"><?php echo __( 'Wirecard Payment Gateway', 'woocommerce-gateway-wirecard' ); ?></a> <!---->
									</p>
								</div>
								<hr>
								<h3><?php echo __( 'Response data:', 'woocommerce-gateway-wirecard' ); ?></h3>
								<div class="order_data_column_container">
									<table>
										<tr>
											<td>
												<b><?php echo __( 'Total', 'woocommerce-gateway-wirecard' ); ?></b>
											</td>
											<td>
												<b><?php echo $transaction->amount . ' ' . $transaction->currency; ?></b>
											</td>
										</tr>
										<?php
										foreach ( $response_data as $key => $value ) {
											echo '<tr>';
											echo '<td>' . $key . '</td><td>' . $value . '</td>';
											echo '</tr>';
										}
										?>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles cancel transaction calls
	 *
	 * @param $transaction_id
	 *
	 * @since 1.0.0
	 */
	public
	function handle_cancel(
		$transaction_id
	) {
		/** @var stdClass $transaction */
		$transaction = $this->get_transaction( $transaction_id );
		if ( ! $transaction ) {
			echo __( 'No transaction found', 'woocommerce-gateway-wirecard' );

			return;
		}
		$this->transaction_handler->cancel_transaction( $transaction );
	}

	/**
	 * Handles capture transaction calls
	 *
	 * @param $transaction_id
	 *
	 * @since 1.0.0
	 */
	public
	function handle_capture(
		$transaction_id
	) {
		/** @var stdClass $transaction */
		$transaction = $this->get_transaction( $transaction_id );
		if ( ! $transaction ) {
			echo __( 'No transaction found', 'woocommerce-gateway-wirecard' );

			return;
		}
		$this->transaction_handler->capture_transaction( $transaction );
	}

	/**
	 * Handles refund transaction calls
	 *
	 * @param $transaction_id
	 *
	 * @since 1.0.0
	 */
	public
	function handle_refund(
		$transaction_id
	) {
		/** @var stdClass $transaction */
		$transaction = $this->get_transaction( $transaction_id );
		if ( ! $transaction ) {
			echo __( 'No transaction found', 'woocommerce-gateway-wirecard' );

			return;
		}
		$this->transaction_handler->refund_transaction( $transaction );
	}

	/**
	 * Create transaction link for detailed information
	 *
	 * @param string                                $base_url
	 * @param Wirecard\PaymentSdk\Response\Response $response
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public
	function get_transaction_link(
		$base_url, $response
	) {
		$transaction_id = $response->getTransactionId();
		$output         = sprintf(
			'%s <a target="_blank" href="' . $base_url . '/engine/rest/merchants/%s/payments/%s">%s</a>',
			__( 'For more transaction information click ', 'woocommerce-wirecard-gateway' ),
			$response->findElement( 'merchant-account-id' ),
			$transaction_id,
			__( 'here', 'woocommerce-wirecard-gateway' )
		);

		return $output;
	}
}
