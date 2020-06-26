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

require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/handler/class-wirecard-transaction-handler.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/helper/class-transaction-translate-helper.php' );


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
	 * @const string TRANSACTION_CLOSED
	 */
	const TRANSACTION_CLOSED = 'closed';

	/**
	 * @const string TRANSACTION_SUCCESS
	 */
	const TRANSACTION_SUCCESS = 'success';

	/**
	 * @const string TRANSACTION_STATE_SUCCESS
	 */
	const TRANSACTION_STATE_SUCCESS = '0';

	/**
	 * @const string TRANSACTION_STATE_CLOSED
	 */
	const TRANSACTION_STATE_CLOSED = '1';

	/**
	 * Transaction table name in database
	 *
	 * @since  1.0.0
	 * @access private
	 * @var string
	 */
	private $table_name;

	/**
	 * Handles back-end operations
	 *
	 * @since  1.0.0
	 * @access private
	 * @var Wirecard_Transaction_Handler
	 */
	private $transaction_handler;

	/**
	 * Transactiontypes for stock reduction
	 *
	 * @since  1.3.1
	 * @access private
	 * @var array
	 */
	private $stock_reduction_types;

	/**
	 * Translation of transaction types and states
	 *
	 * @since  3.3.0
	 * @access private
	 * @var Transaction_Translate_Helper $transaction_translate_helper
	 */
	private $transaction_translate_helper;

	/**
	 * Wirecard_Transaction_Factory constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->transaction_handler          = new Wirecard_Transaction_Handler();
		$this->table_name                   = $wpdb->prefix . 'wirecard_payment_gateway_tx';
		$this->stock_reduction_types        = array( 'authorization', 'purchase', 'debit', 'deposit' );
		$this->transaction_translate_helper = new Transaction_Translate_Helper();
	}

	/**
	 * Create new transaction entry in database
	 *
	 * @param WC_Order $order
	 * @param SuccessResponse $response
	 * @param string $base_url
	 * @param string $transaction_state
	 * @param string $payment_method
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function create_transaction( $order, $response, $base_url, $transaction_state, $payment_method ) {
		global $wpdb;
		$requested_amount      = $response->getData()['requested-amount'];
		$parent_transaction_id = $this->update_parent_transaction( $response, $order );
		$transaction_link      = $this->get_transaction_link( $base_url, $response );
		$transaction           = $this->get_transaction( $response->getTransactionId() );
		$parameters            = $this->set_transaction_parameters(
			$response,
			$parent_transaction_id,
			$payment_method,
			$transaction_state,
			$order,
			$transaction_link,
			$requested_amount
		);

		if ( $transaction && ( 'awaiting' === $transaction->transaction_state ) ) {
			$wpdb->update(
				$this->table_name,
				$parameters,
				array(
					'transaction_id' => $response->getTransactionId(),
				)
			);
		} elseif ( ! $transaction ) {
			$wpdb->insert(
				$this->table_name,
				$parameters
			);
			$this->reduce_stock( $response, $order );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get transaction html table for overview beginning from $start to $stop
	 *
	 * @param int $page
	 *
	 * @return int $row_count
	 * @since 1.0.0
	 *
	 */
	public function get_rows( $page = 1 ) {
		global $wpdb;

		$transaction_table_title_list = $this->transaction_translate_helper->get_table_header_translations();
		$payment_methods              = $this->get_payment_methods();
		$start                        = ( $page * 20 ) - 19;

		$start --;
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_tx ORDER BY tx_id DESC LIMIT %d,20", $start ), ARRAY_A );
		$pages = $wpdb->get_row( "SELECT CEILING(COUNT(*)/20) as pages FROM {$wpdb->prefix}wirecard_payment_gateway_tx" );

		if ( is_null( $pages ) ) {
			$pages        = new stdClass();
			$pages->pages = 1;
		}

		echo '<tr>';
		foreach ( $transaction_table_title_list as $table_title_key => $table_title ) {
			echo '<th>';
			echo $table_title;
			echo '</th>';
		}
		echo '</tr>';

		foreach ( $rows as $row ) {
			echo '<tr>';

			foreach ( $transaction_table_title_list as $table_title_key => $table_title ) {
				echo '<td>';
				if ( key_exists( $table_title_key, $row ) ) {
					if ( 'transaction_id' === $table_title_key || ( 'parent_transaction_id' === $table_title_key && ! empty( $table_title ) ) ) {
						echo "<a href='?page=wirecardpayment&id={$row[ $table_title_key ]}'>" . $row[ $table_title_key ] . '</a>';
					} else {
						if ( 'payment_method' === $table_title_key ) {
							echo $this->get_payment_method_display_text( $payment_methods, $row[ $table_title_key ] );
						} else {
							echo $this->transaction_translate_helper->translate( $row[ $table_title_key ] );
						}
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
	public function get_transaction( $transaction_id ) {
		global $wpdb;

		$transaction = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_tx WHERE transaction_id = %s", $transaction_id ) );

		if ( empty( $transaction ) ) {
			return false;
		}

		return $transaction;
	}

	/**
	 * Get rest amount of parent transaction
	 *
	 * @param string $parent_transaction_id
	 * @param string $action
	 *
	 * @return float
	 *
	 * @since 1.1.2
	 */
	public function get_parent_rest_amount( $parent_transaction_id, $action ) {
		global $wpdb;

		$transactions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_tx WHERE parent_transaction_id = %s AND transaction_type = %s", $parent_transaction_id, $action ) );
		$parent       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_tx WHERE transaction_id = %s", $parent_transaction_id ) );
		$rest         = $parent->amount;

		if ( ! empty( $transactions ) ) {
			foreach ( $transactions as $transaction ) {
				$rest -= $transaction->amount;
			}
		}

		return $rest;
	}

	/**
	 * Handling of post-processing actions
	 *
	 * @param int $transaction_id
	 * @param null|string $action
	 *
	 * @since 1.6.1
	 */
	public function show_post_processing_info( $transaction_id, $action = null ) {
		$message  = null;
		$severity = 'error';

		$transaction = $this->get_transaction( $transaction_id );
		$order       = wc_get_order( $transaction->order_id );
		if ( ! $transaction ) {
			$message = __( 'error_no_transaction', 'wirecard-woocommerce-extension' );
			$this->print_admin_notice( $message, $severity );

			return;
		}

		if ( isset( $action ) ) {
			switch ( $action ) {
				case 'refund':
					$message = $this->transaction_handler->refund_transaction( $transaction );
					break;
				case 'cancel':
					$message = $this->transaction_handler->cancel_transaction( $transaction );
					break;
				case 'capture':
					$message = $this->transaction_handler->capture_transaction( $transaction );
					break;
			}
		}

		if ( $message instanceof SuccessResponse ) {
			$this->update_parent_transaction( $message, $order );
			$severity    = 'updated';
			$message     = __( 'success_new_transaction', 'wirecard-woocommerce-extension' ) . ' <a href="?page=wirecardpayment&id=' . $message->getTransactionId() . '">' . $message->getTransactionId() . '</a>';
			$transaction = $this->get_transaction( $transaction_id );
		}
		$this->show_transaction( $transaction, $message, $severity );
	}

	/**
	 * Print transaction detail information and possible back-end operations
	 *
	 * @param stdClass $transaction
	 * @param null|string $message
	 * @param string $severity
	 *
	 * @since 1.0.0
	 */
	public function show_transaction( $transaction, $message, $severity ) {
		/** @var WC_Wirecard_Payment_Gateway $payment */
		$payment = $this->transaction_handler->get_payment_method( $transaction->payment_method );

		$response_data = json_decode( $transaction->response );
		?>
		<link rel='stylesheet' href='<?php echo plugins_url( 'wirecard-woocommerce-extension/assets/styles/admin.css' ); ?>'>
		<div class="wrap">
			<?php
			if ( isset( $message ) ) {
				$this->print_admin_notice( $message, $severity );
			}
			?>
			<div class="postbox-container">
				<div class="postbox">
					<div class="inside">
						<div class="panel-wrap woocommerce">
							<div class="panel woocommerce-order-data">
								<h2 class="woocommerce-order-data__heading"><?php echo __( 'text_transaction', 'wirecard-woocommerce-extension' ) . ': ' . $transaction->transaction_id; ?></h2>
								<h3>
									<?php echo $payment->method_name . ' ' . __( 'payment_suffix', 'wirecard-woocommerce-extension' ); ?>
								</h3>
								<div><?php echo $transaction->transaction_link; ?></div>
								<br>
								<div
									class="transaction-type type-authorization"><?php echo $transaction->transaction_type; ?></div>
								<br>
								<div class="wc-order-data-row">
									<?php
									if ( $payment->can_cancel( $transaction->transaction_type ) && ! $transaction->closed && 'awaiting' !== $transaction->transaction_state ) {
										echo "<a href='?page=wirecardpayment&id={$transaction->transaction_id}&action=cancel' class='button'>" . __( 'text_cancel_transaction', 'wirecard-woocommerce-extension' ) . '</a> ';
									}
									if ( $payment->can_capture( $transaction->transaction_type ) && ! $transaction->closed && 'awaiting' !== $transaction->transaction_state ) {
										echo "<a href='?page=wirecardpayment&id={$transaction->transaction_id}&action=capture' class='button'>" . __( 'text_capture_transaction', 'wirecard-woocommerce-extension' ) . '</a> ';
									}
									if ( $payment->can_refund( $transaction->transaction_type ) && ! $transaction->closed && 'awaiting' !== $transaction->transaction_state ) {
										echo "<a href='?page=wirecardpayment&id={$transaction->transaction_id}&action=refund' class='button'>" . __( 'text_refund_transaction', 'wirecard-woocommerce-extension' ) . '</a> ';
									}
									if ( $transaction->closed ) {
										echo "<p class='add-items'>" . __( 'error_no_post_processing_operations', 'wirecard-woocommerce-extension' ) . '</p>';
									}
									if ( 'awaiting' === $transaction->transaction_state ) {
										echo "<p class='add-items'>" . __( 'error_no_post_processing_operations_unconfirmed', 'wirecard-woocommerce-extension' ) . '</p>';
									}
									?>
									<p class="add-items">
										<a href="?page=wirecardpayment"><?php echo __( 'title_payment_gateway', 'wirecard-woocommerce-extension' ); ?></a>
										<!---->
									</p>
								</div>
								<hr>
								<h3><?php echo __( 'text_response_data', 'wirecard-woocommerce-extension' ); ?></h3>
								<div class="order_data_column_container">
									<table>
										<tr>
											<td>
												<b><?php echo __( 'text_total', 'wirecard-woocommerce-extension' ); ?></b>
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
	 * Create notice, success, error box in admin interface
	 *
	 * @param string $message
	 * @param string $severity
	 *
	 * @since 1.6.1
	 */
	public function print_admin_notice( $message, $severity = 'update-nag' ) {
		$notice = '<div class="' . $severity . ' notice">' . $message . '</div>';
		echo $notice;
	}

	/**
	 * Create transaction link for detailed information
	 *
	 * @param string $base_url
	 * @param Wirecard\PaymentSdk\Response\Response $response
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_transaction_link( $base_url, $response ) {
		$transaction_id = $response->getTransactionId();
		$output         = sprintf(
			'%s <a target="_blank" href="' . $base_url . '/engine/rest/merchants/%s/payments/%s">%s</a>',
			__( 'text_transaction_details_1', 'wirecard-woocommerce-extension' ),
			$response->findElement( 'merchant-account-id' ),
			$transaction_id,
			__( 'text_transaction_details_2', 'wirecard-woocommerce-extension' )
		);

		return $output;
	}

	/**
	 * Set parameters for the transaction
	 *
	 * @param SuccessResponse $response
	 * @param string $parent_transaction_id
	 * @param string $payment_method
	 * @param string $transaction_state
	 * @param WC_Order $order
	 * @param string $transaction_link
	 * @param float $amount
	 *
	 * @return array
	 *
	 * @since 1.1.0
	 */
	private function set_transaction_parameters(
		$response,
		$parent_transaction_id,
		$payment_method,
		$transaction_state,
		$order,
		$transaction_link,
		$amount
	) {
		return array(
			'transaction_id'        => $response->getTransactionId(),
			'parent_transaction_id' => $parent_transaction_id,
			'payment_method'        => $payment_method,
			'transaction_state'     => $transaction_state,
			'transaction_type'      => $response->getTransactionType(),
			'amount'                => $amount,
			'currency'              => $order->get_currency(),
			'order_id'              => $order->get_id(),
			'response'              => wp_json_encode( $response->getData() ),
			'transaction_link'      => $transaction_link,
		);
	}

	/**
	 * Set the tansaction state based on the state value
	 *
	 * @param string $state
	 *
	 * @return string
	 *
	 * @throws WC_Data_Exception
	 *
	 * @since 3.1.0
	 */
	private function set_transaction_state( $state ) {
		$transaction_state = self::TRANSACTION_CLOSED;
		if ( self::TRANSACTION_STATE_SUCCESS === $state ) {
			$transaction_state = self::TRANSACTION_SUCCESS;
		}
		return $transaction_state;
	}

	/**
	 * Update parent transaction state in database
	 *
	 * @param SuccessResponse $response
	 * @param WC_Order $order
	 * @param string $state
	 *
	 * @return string
	 *
	 * @throws WC_Data_Exception
	 *
	 * @since 3.1.0
	 */
	private function update_parent_transaction_state( $response, $order, $state ) {
		global $wpdb;
		$requested_amount      = $response->getData()['requested-amount'];
		$action                = $response->getTransactionType();
		$parent_transaction_id = $response->getParentTransactionId();
		$rest_amount           = $this->get_parent_rest_amount( $parent_transaction_id, $action );
		$transaction_state     = $this->set_transaction_state( $state );
		if ( ( $rest_amount === $requested_amount ) || ( 0 === $rest_amount ) ) {
			$order->set_transaction_id( $response->getTransactionId() );
			$wpdb->update(
				$this->table_name,
				array(
					'closed'            => $state,
					'transaction_state' => $transaction_state,
				),
				array(
					'transaction_id' => $parent_transaction_id,
				)
			);
		}

		return $parent_transaction_id;
	}

	/**
	 * Update parent transaction
	 *
	 * @param SuccessResponse $response
	 * @param WC_Order $order
	 *
	 * @return string
	 *
	 * @throws WC_Data_Exception
	 * @since 3.0.0
	 */
	private function update_parent_transaction( $response, $order ) {
		$parent_transaction = $this->get_transaction( $response->getParentTransactionId() );
		if ( $parent_transaction ) {
			if ( $response instanceof SuccessResponse ) {
				return $this->update_parent_transaction_state( $response, $order, self::TRANSACTION_STATE_CLOSED );
			} else {
				// Update parent transaction state from closed to success if notification response is error
				return $this->update_parent_transaction_state( $response, $order, self::TRANSACTION_STATE_SUCCESS );
			}
		} else {
			$order->set_transaction_id( $response->getTransactionId() );
		}

		return '';
	}

	/**
	 * Reduce stock
	 *
	 * @param SuccessResponse $response
	 * @param WC_Order $order
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	private function reduce_stock( $response, $order ) {
		// Do not reduce stock for follow-up transactions
		if ( in_array( $response->getTransactionType(), $this->stock_reduction_types, true ) && ! $this->active_germanized() ) {
			// Reduce stock after successful transaction creation to avoid duplicated reduction
			wc_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Returns true if WooCommerce Germanized exists and is activated
	 *
	 * @return bool
	 *
	 * @since 1.3.1
	 */
	private function active_germanized() {
		if ( ! class_exists( 'WooCommerce_Germanized' ) ) {
			return false;
		}
		if ( is_plugin_active( 'woocommerce-germanized/woocommerce-germanized.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get payment_method display text
	 *
	 * @param WC_Wirecard_Payment_Gateway[] $payment_methods
	 * @param string $type
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	private function get_payment_method_display_text( $payment_methods, $type ) {
		if ( ! isset( $payment_methods[ $type ] ) ) {
			return $type;
		}

		$payment_method   = $payment_methods[ $type ];
		$database_title   = $payment_method->get_title();
		$translated_title = $payment_method->get_method_title();

		if ( $database_title === $translated_title ) {
			return $translated_title;
		}

		return $translated_title . '<br>(' . $database_title . ')';
	}

	/**
	 * Get payment methods
	 *
	 * @return WC_Wirecard_Payment_Gateway[]
	 *
	 * @since 3.3.0
	 */
	private function get_payment_methods() {
		$payment_methods = array();

		/**
		 * @var WC_Wirecard_Payment_Gateway $payment_method
		 */
		foreach ( wirecard_get_payments() as $payment_method ) {
			$payment_methods[ $payment_method->get_type() ] = $payment_method;
		}

		return $payment_methods;
	}
}
