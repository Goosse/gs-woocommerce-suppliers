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
class GS_WC_Report_Stock_By_Supplier extends WC_Admin_Report {
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
					<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
					<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
					<input type="hidden" name="report" value="stock_by_supplier" />
				</div>
			</form>
		<?php
	}
	/**
	 * Output an export link.
	 */
	public function get_export_button() {
		if ( ! empty( $_GET['supplier_id'] ) ) {
			$supplier_id = get_post( $_GET['supplier_id'] );
			$supplier_id_title = get_the_title($supplier_id);
		} else {
			$supplier_id_title = '';
		}

		?>
		<a
			href="#"
			download="report-current_stock-<?php echo date_i18n( 'Y-m-d', current_time( 'timestamp' ) ); ?>_<?=$supplier_id_title ?>.csv"
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
		$hide_sidebar = false;
		include( 'html-stock-report.php' );
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
					<th><?php _e( 'Product SKU', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Product Name', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the name of the product", 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Quantity In Stock', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the quantity of this product that is currently in stock.", 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Cost', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the purchase cost of this item.", 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Regular Price', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the regular sell price for one unit.', 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Sale Price', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the sale price for one unit.', 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Total Cost', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the total purchase cost of this item for all units from this supplier.", 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Total Sell', 'woocommerce' ); ?> <?php echo wc_help_tip( __( "This is the total sell price of all units (Quantity In Stock x Price) from this supplier", 'woocommerce' ) ); ?></th>
				</tr>
			</thead>
			<?php if ( ! empty( $supplier_sales_data ) ) : ?>
				<tbody>
					<?php
					$total_sell = 0;
					$total_cost = 0;
					foreach ( $supplier_sales_data as $row ) {

							$total_sell  += $row->current_stock*($row->sale_price?$row->sale_price:$row->price); //   = array_sum( wp_list_pluck(  $supplier_sales_data, 'total_sell' ));
							if (get_post_meta($_GET['supplier_id'], 'consignor',true) == 'yes'){
								$commission = get_post_meta($_GET['supplier_id'], 'commission',true);
								if (empty($commission)){
									$commission = 25;
								}

								$row_total_cost = (100 - $commission) * $row->current_stock*($row->sale_price?$row->sale_price:$row->price) / 100;
								// if(!empty($row->sale_price))
								// 	$total_cost = (100 - $commission) * $row->current_stock*$row->sale_price  / 100;

							}
							else{
								$cost = $row->cost;
								$row_total_cost = $row->current_stock*($row->sale_price?$row->sale_price:$row->price);
								// if(!empty($row->sale_price))
								// 	$total_cost = $row->current_stock*$row->sale_price;
							}
							$total_cost += $row_total_cost;
						?>
						<tr>
							<th scope="row">
								<?php echo $row->product_sku; ?>
							</th>
							<td class="total_row"><?php echo $row->product_name; ?></td>
							<td class="total_row"><?php echo $row->current_stock; ?></td>
							<td class="total_row"><?php echo wc_price($row->cost); ?></td>
							<td class="total_row"><?php echo wc_price($row->price); ?></td>
							<td class="total_row"><?php echo $row->sale_price ? wc_price($row->sale_price): ''; ?></td>
							<td class="total_row"><?php echo wc_price($row_total_cost); ?></td>
							<td class="total_row"><?php echo wc_price($row->current_stock*($row->sale_price?$row->sale_price:$row->price)); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
				<tfoot>
					<?php
						//$total_sell    = array_sum( wp_list_pluck(  $supplier_sales_data, 'total_sell' ));
					//	$cost = get_post_meta($_GET['supplier_id'], 'consignor',true) == 'yes' ? :
					?>
					<tr>
						<th scope="row"><?php _e( 'Totals', 'woocommerce' ); ?></th>
						<th class="total_row"></th>
						<th class="total_row"><?php echo array_sum( wp_list_pluck(  $supplier_sales_data, 'current_stock' )); ?></th>
						<th class="total_row"></th>
						<th class="total_row"></th>
						<th class="total_row"></th>
						<th class="total_row"><?php echo wc_price( $total_cost ); ?></th>
						<th class="total_row"><?php echo wc_price( $total_sell ); ?></th>

					</tr>
				</tfoot>
			<?php else : ?>
				<tbody>
					<tr>
						<td><?php _e( 'There are no products in stock for this supplier', 'woocommerce' ); ?></td>
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
									(select meta_value FROM wp_postmeta where meta_key='_sku' and post_id = product.id) as product_sku,
									product.post_title as product_name,
									cost,
									(select meta_value FROM wp_postmeta where meta_key='_regular_price' and post_id = product.id) as price,
									(select meta_value FROM wp_postmeta where meta_key='_sale_price' and post_id = product.id) as sale_price,
									(SELECT (SELECT SUM(quantity) FROM wp_goosesoft_wc_stock_increases WHERE product_id = product.id) +
							    	IFNULL((SELECT SUM(reductions.quantity)
							    	FROM wp_goosesoft_wc_stock_reductions AS reductions
							    	LEFT JOIN wp_goosesoft_wc_stock_increases AS increases
							    	ON increases.id = reductions.stock_increases_id
							    	WHERE increases.product_id = product.id),0) as in_stock) as current_stock
							from wp_posts as product
							join wp_goosesoft_wc_stock_increases as inc on product.id = inc.product_id
							join  wp_posts as supplier on supplier.id = inc.supplier_id
							where supplier.id = %s
							order by current_stock desc",
					$_GET['supplier_id']
		));

		return $supplier_sales_data;

	}
}
