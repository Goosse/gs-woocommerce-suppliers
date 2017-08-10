<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * WC_Report_Taxes_By_Date
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */
class GS_WC_Report_Sales_By_Supplier extends WC_Admin_Report {
	/**
	 * Get the legend for the main chart sidebar.
	 * @return array
	 */
	public function get_chart_legend() {
		return array();
	}

	/**
	 * Get chart widgets.
	 *
	 * @return array
	 */
	public function get_chart_widgets() {

		$widgets = array();
		if ( ! empty( $_GET['supplier_id'] ) ) {
			$widgets[] = array(
				'title'    => __( 'Showing reports for:', 'woocommerce' ),
				'callback' => array( $this, 'current_filters' ),
			);
		}
		$widgets[] = array(
			'title'    => 'Supplier Search',
			'callback' => array( $this, 'supplier_widget' ),
		);
		return $widgets;
	}

	/**
	 * Output current filters.
	 */
	public function current_filters() {

			$supplier_id = get_post( $_GET['supplier_id'] );
			if ( $supplier_id ) {
				$supplier_id_title = get_the_title($supplier_id);
			} else {
				$supplier_id_title = '#' . $supplier_id;
			}
		echo '<p>' . ' <strong>' . $supplier_id_title . '</strong></p>';
		echo '<p><a class="button" href="' . esc_url( remove_query_arg( 'supplier_id' ) ) . '">' . __( 'Reset', 'woocommerce' ) . '</a></p>';
	}
	/**
	 * Output category widget.
	 */
	public function supplier_widget() {
		global $wpdb;
		?>
			<form method="GET">
				<div>
					<select type="hidden"
		      class="gs-wc-supplier-search"
		      style="width: 203px;"
		      id="gs_wc_add_supplier_id"
		      name="supplier_id"
		      data-placeholder="<?php esc_attr_e( 'Search for a supplier&hellip;', 'gs_wc_suppliers' ); ?>"
		      data-allow_clear="true"
		      data-action="gs_wc_json_search_suppliers"
		      data-multiple="false"
		      value="" /></select>
					<input type="submit" class="submit button" value="<?php esc_attr_e( 'Show', 'woocommerce' ); ?>" />
					<input type="hidden" name="range" value="<?php if ( ! empty( $_GET['range'] ) ) echo esc_attr( $_GET['range'] ) ?>" />
					<input type="hidden" name="start_date" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ) ?>" />
					<input type="hidden" name="end_date" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ) ?>" />
					<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
					<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
					<input type="hidden" name="report" value="<?php if ( ! empty( $_GET['report'] ) ) echo esc_attr( $_GET['report'] ) ?>" />
				</div>
			</form>
		<?php
	}
	/**
	 * Output an export link.
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : 'last_month';
		if ( ! empty( $_GET['supplier_id'] ) ) {
			$supplier_id = get_post( $_GET['supplier_id'] );
			$supplier_id_title = get_the_title($supplier_id);
		} else {
			$supplier_id_title = '';
		}

		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time( 'timestamp' ) ); ?>_<?=$supplier_id_title ?>.csv"
			class="export_csv"
			data-export="table"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}
	/**
	 * Output the report.
	 */
	public function output_report() {
		$ranges = array(
			'year'         => __( 'Year', 'woocommerce' ),
			'last_month'   => __( 'Last month', 'woocommerce' ),
			'month'        => __( 'This month', 'woocommerce' ),
		);
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : 'last_month';
		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
			$current_range = 'last_month';
		}
		$this->calculate_current_range( $current_range );
		$hide_sidebar = false;
		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php' );
	}
	/**
	 * Get the main chart.
	 *
	 * @return string
	 */
	public function get_main_chart() {

		$supplier_sales_data = $this->get_supplier_sales_report_data();
		?>
		<table class="widefat" style="display: inline-table;">
			<thead>
				<tr>
					<th><?php _e( 'Sale Date', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Product SKU', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Product Name', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the name of the product sold", 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Quantity', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is how many of the this product were sold at this time.", 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Sell Price', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the sell price for one unit.', 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Total Sell', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the total sell price of all units (Quantity x Sell Price)", 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Total Cost', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the purchase cost of this item.", 'woocommerce' ) ); ?></th>
				</tr>
			</thead>
			<?php if ( ! empty( $supplier_sales_data ) ) : ?>
				<tbody>
					<?php
					foreach ( $supplier_sales_data as $row ) {

							$total_sell    = array_sum( wp_list_pluck(  $supplier_sales_data, 'total_sell' ));
							if (get_post_meta($_GET['supplier_id'], 'consignor',true) == 'yes'){
								$commission = get_post_meta($_GET['supplier_id'], 'commission',true);
								if (empty($commission)){
									$commission = 25;
								}

								$cost = (100 - $commission) * $row->total_sell / 100;

							}
							else{
								$cost = $row->cost;
							}
						?>
						<tr>
							<th scope="row">
								<?php echo $row->sale_date; ?>
							</th>
							<td class="total_row"><?php echo $row->product_sku; ?></td>
							<td class="total_row"><?php echo $row->product_name; ?></td>
							<td class="total_row"><?php echo $row->quantity; ?></td>
							<td class="total_row"><?php echo wc_price($row->sell_price); ?></td>
							<td class="total_row"><?php echo wc_price($row->total_sell); ?></td>
							<td class="total_row"><?php echo wc_price($cost); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
				<tfoot>
					<?php
						$total_sell    = array_sum( wp_list_pluck(  $supplier_sales_data, 'total_sell' ));
					//	$cost = get_post_meta($_GET['supplier_id'], 'consignor',true) == 'yes' ? :
					?>
					<tr>
						<th scope="row"><?php _e( 'Totals', 'woocommerce' ); ?></th>
						<th class="total_row"></th>
						<th class="total_row"></th>
						<th class="total_row"><?php echo array_sum( wp_list_pluck(  $supplier_sales_data, 'quantity' )); ?></th>
						<th class="total_row"><?php echo wc_price( array_sum( wp_list_pluck(  $supplier_sales_data, 'sell_price' )) ); ?></th>
						<th class="total_row"><?php echo wc_price( array_sum( wp_list_pluck(  $supplier_sales_data, 'total_sell' )) ); ?></th>
						<th class="total_row"><?php echo wc_price( array_sum( wp_list_pluck(  $supplier_sales_data, 'cost' )) ); ?></th>

					</tr>
				</tfoot>
			<?php else : ?>
				<tbody>
					<tr>
						<td><?php _e( 'No sales found for this supplier in this period', 'woocommerce' ); ?></td>
					</tr>
				</tbody>
			<?php endif; ?>
		</table>
		<?php
	}

	function get_supplier_sales_report_data(){
		global $wpdb;

		if (empty($_GET['supplier_id'])){
			return '';
		}
		$supplier_sales_data = $wpdb->get_results(
		$wpdb->prepare(
									"SELECT
									product_sku.meta_value as product_sku,
									CAST(reductions.date AS DATE) as sale_date,
									order_items.order_item_name as product_name,
									-reductions.quantity as quantity,
									reductions.single_price as sell_price,
									-reductions.quantity*reductions.single_price as total_sell,
									increases.cost as cost
									FROM wp_woocommerce_order_items as order_items
										join wp_woocommerce_order_itemmeta as product_id on product_id.order_item_id=order_items.order_item_id and product_id.meta_key='_product_id'
										join wp_postmeta as product_sku on product_sku.post_id=product_id.meta_value and product_sku.meta_key='_sku'
										join wp_goosesoft_wc_stock_increases as increases on increases.product_id=product_id.meta_value and increases.supplier_id=%s
										join wp_goosesoft_wc_stock_reductions as reductions on reductions.stock_increases_id=increases.id and reductions.order_id=order_items.order_id
										join wp_posts as orders on orders.id = order_items.order_id and CAST(orders.post_date AS DATE) BETWEEN %s and %s
									order by sale_date asc",
		$_GET['supplier_id'],
		date( 'Y-m-d', strtotime($_GET['start_date'] )),
		date( 'Y-m-d', strtotime( '+1 DAY', strtotime($_GET['end_date'] )) ))
		);

		return $supplier_sales_data;

	}
}
