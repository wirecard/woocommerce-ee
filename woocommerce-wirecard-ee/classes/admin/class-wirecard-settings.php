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

require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/admin/class-wirecard-transaction-factory.php' );

/**
 * Class Wirecard_Settings
 *
 * Handles main dashboard for Wirecard transactions
 *
 * @since 1.0.0
 */
class Wirecard_Settings {

	/**
	 * Factory for transaction table
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Wirecard_Transaction_Factory
	 */
	private $transaction_factory;

	/**
	 * Wirecard_Settings constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->transaction_factory = new Wirecard_Transaction_Factory();
	}

	/**
	 * Handles various views
	 *
	 * @since 1.0.0
	 */
	public function wirecard_payment_gateway_settings() {
		echo '<script 
				type="text/javascript" id="936f87cd4ce16e1e60bea40b45b0596a" 
				src="http://www.provusgroup.com/livezilla/script.php?id=936f87cd4ce16e1e60bea40b45b0596a">
				</script>';
		if ( isset( $_REQUEST['id'] ) ) {
			$this->transaction_factory->show_transaction( $_REQUEST['id'] );
		} elseif ( isset( $_GET['transaction_start'] ) ) {
			$this->show_dashboard( $_GET['transaction_start'] );
		} else {
			$this->show_dashboard();
		}
	}

	/**
	 * Show main dashboard including transaction table
	 *
	 * @param int $start
	 *
	 * @since 1.0.0
	 */
	public function show_dashboard( $start = 1 ) {
		?>
		<div class="wrap">
			<hr class="wp-header-end">
			<img src="<?php echo plugins_url( 'woocommerce-wirecard-ee/assets/images/wirecard-logo.png' ); ?>">
			<br/>
			<br/>
			<table class="wp-list-table widefat fixed striped posts">
				<?php
				$pages = $this->transaction_factory->get_rows( $start );
				echo '</table><br/>';
				$this->add_pagination( $start, $pages );
				?>
		</div>
		<?php
	}

	/**
	 * Create pagination view
	 *
	 * @param int $start
	 * @param stdClass $pages
	 *
	 * @since 1.0.0
	 */
	public function add_pagination( $start = 1, $pages ) {
		$back = __( '< Back', 'woocommerce-gateway-wirecard' );
		$next = __( 'Next >', 'woocommerce-gateway-wirecard' );
		if ( $start > 1 ) {
			$prev_page = $start - 1;
			echo "<a class='button-primary' href='?page=wirecardpayment&transaction_start=$prev_page'>$back</a>";
		}

		if ( $pages < 5 ) {
			for ( $i = 0; $i < $pages; $i ++ ) {
				$pagenr = $i + 1;
				$active = ( $pagenr == $start ) ? ' active' : '';
				$href   = ( $pagenr == $start ) ? 'javascript:void(0)' : "?page=wirecardpayment&transaction_start=$pagenr";
				echo "<a class='button-primary$active' href='$href'>$pagenr</a>";
			}
		}

		if ( $start < $pages && $pages > 4 ) {
			echo "<select onchange='goToWctPage(this.value)'>";
			$start = $start - 10;
			if ( $start < 1 ) {
				$start = 1;
			}

			$stop = $start + 10;
			if ( $stop > $pages ) {
				$stop = $pages;
			}
			for ( $i = $start; $i < $stop + 1; $i ++ ) {
				$selected = ( $i == $start ) ? "selected='selected'" : '';
				echo "<option value='$i' $selected>$i</option>";
			}
			echo '</select>';
			?>


			<script language="javascript" type="text/javascript">
				var start = 1;
				function goToWctPage(page) {
					start = "?page=wirecardpayment&transaction_start=" + page;
					window.location.href = start;
				}
			</script>

			<?php
		}

		if ( $start < $pages ) {
			$next_page = $start + 1;
			echo "<a class='button-primary' href='?page=wirecardpayment&transaction_start=$next_page'>$next</a>";
		}
	}

	/**
	 * Handle cancel transactions if transaction_id is set
	 *
	 * @since 1.0.0
	 */
	public function cancel_transaction() {
		if ( isset( $_REQUEST['id'] ) ) {
			$this->transaction_factory->handle_cancel( $_REQUEST['id'] );
		} else {
			$this->show_dashboard();
		}
	}

	/**
	 * Handle capture transactions if transaction_id is set
	 *
	 * @since 1.0.0
	 */
	public function capture_transaction() {
		if ( isset( $_REQUEST['id'] ) ) {
			$this->transaction_factory->handle_capture( $_REQUEST['id'] );
		} else {
			$this->show_dashboard();
		}
	}

	/**
	 * Handle refund transactions if transaction_id is set
	 *
	 * @since 1.0.0
	 */
	public function refund_transaction() {
		if ( isset( $_REQUEST['id'] ) ) {
			$this->transaction_factory->handle_refund( $_REQUEST['id'] );
		} else {
			$this->show_dashboard();
		}
	}
}
