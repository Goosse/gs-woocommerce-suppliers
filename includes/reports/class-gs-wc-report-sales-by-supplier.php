<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * WC_Report_Sales_By_Supplier
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */

//include_once('../class-gs-stock-controller.php');

class GS_WC_Report_Sales_By_Supplier extends WC_Admin_Report {

  public $stockController;
	/**
	 * Chart colours.
	 *
	 * @var array
	 */
	public $chart_colours      = array();
	/**
	 * Product ids.
	 *
	 * @var array
	 */
	public $supplier_ids        = array();
	/**
	 * Product ids with titles.
	 *
	 * @var array
	 */
	 public $sale_counts;
	 public $sale_ammounts;
	public $supplier_ids_titles = array();
	/**
	 * Constructor.
	 */

	public function __construct() {
    $this->stockController = new GS_Stock_Controller;
		if ( isset( $_GET['supplier_ids'] ) && is_array( $_GET['supplier_ids'] ) ) {
			$this->supplier_ids = array_filter( array_map( 'absint', $_GET['supplier_ids'] ) );
		} elseif ( isset( $_GET['supplier_ids'] ) ) {
			$this->supplier_ids = array_filter( array( absint( $_GET['supplier_ids'] ) ) );
		}
	}
	/**
	 * Get the legend for the main chart sidebar.
	 * @return array
	 */
	public function get_chart_legend() {
		global $wpdb;

		if ( empty( $this->supplier_ids ) ) {
			return array();
		}
		$legend   = array();
		$total_sales = $wpdb->get_results(
		$wpdb->prepare(
		"SELECT SUM(reductions.single_price*reductions.quantity*-1) AS sales_total
		FROM {$this->stockController->stock_reductions_table} AS reductions
		LEFT JOIN {$this->stockController->stock_increases_table} AS increases
		ON reductions.stock_increases_id = increases.id
		WHERE reductions.is_sale = 1
		AND reductions.date >= %s AND reductions.date < %s
		AND increases.supplier_id IN (".implode(",", $this->supplier_ids).")",
		date( 'Y-m-d', $this->start_date ),
		date( 'Y-m-d', strtotime( '+1 DAY', $this->end_date ) ))
		);

		$total_sales = $total_sales[0]->sales_total;

		$total_items = $wpdb->get_results(
		$wpdb->prepare(
		"SELECT SUM(reductions.quantity *-1) AS sales_count
		FROM {$this->stockController->stock_reductions_table} AS reductions
		LEFT JOIN {$this->stockController->stock_increases_table} AS increases
		ON reductions.stock_increases_id = increases.id
		WHERE reductions.is_sale = 1
		AND reductions.date >= %s AND reductions.date < %s
		AND increases.supplier_id IN (".implode(",", $this->supplier_ids).")",
		date( 'Y-m-d', $this->start_date ),
		date( 'Y-m-d', strtotime( '+1 DAY', $this->end_date ) ))
		);

		$total_items = $total_items[0]->sales_count;

		$legend[] = array(
			/* translators: %s: total items sold */
			'title' => sprintf( __( '%s sales for the selected supplier', 'woocommerce' ), '<strong>' . wc_price( $total_sales ) . '</strong>' ),
			'color' => $this->chart_colours['sales_amount'],
			'highlight_series' => 1,
		);
		$legend[] = array(
			/* translators: %s: total items purchased */
			'title' => sprintf( __( '%s sales for selected supplier', 'gs_wc_suppliers' ), '<strong>' . ( $total_items ) . '</strong>' ),
			'color' => $this->chart_colours['item_count'],
			'highlight_series' => 0,
		);
		return $legend;
	}
	/**
	 * Output the report.
	 */
	public function output_report() {
		$ranges = array(
			'year'         => __( 'Year', 'woocommerce' ),
			'last_month'   => __( 'Last month', 'woocommerce' ),
			'month'        => __( 'This month', 'woocommerce' ),
			'7day'         => __( 'Last 7 days', 'woocommerce' ),
		);
		$this->chart_colours = array(
			'sales_amount' => '#3498db',
			'item_count'   => '#d4d9dc',
		);
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) )
			$current_range = '7day';
		$this->calculate_current_range( $current_range );
		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php' );
	}
	/**
	 * Get chart widgets.
	 *
	 * @return array
	 */
	public function get_chart_widgets() {
		$widgets = array();
		if ( ! empty( $this->supplier_ids ) ) {
			$widgets[] = array(
				'title'    => __( 'Showing reports for:', 'woocommerce' ),
				'callback' => array( $this, 'current_filters' ),
			);
		}
		$widgets[] = array(
			'title'    => '',
			'callback' => array( $this, 'products_widget' ),
		);
		return $widgets;
	}
	/**
	 * Output current filters.
	 */
	public function current_filters() {
		$this->supplier_ids_titles = array();
		foreach ( $this->supplier_ids as $supplier_id ) {
			$supplier = get_post( $supplier_id );
			if ( $supplier ) {
				$this->supplier_ids_titles[] = get_the_title($supplier_id);
			} else {
				$this->supplier_ids_titles[] = '#' . $supplier_id;
			}
		}
		echo '<p>' . ' <strong>' . implode( ', ', $this->supplier_ids_titles ) . '</strong></p>';
		echo '<p><a class="button" href="' . esc_url( remove_query_arg( 'supplier_ids' ) ) . '">' . __( 'Reset', 'woocommerce' ) . '</a></p>';
	}
	/**
	 * Output products widget.
	 */
	public function products_widget() {
    global $wpdb;
		?>
		<h4 class="section_title"><span><?php _e( 'Supplier search', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<form method="GET">
				<div>
					<input type="hidden" class="gs-wc-supplier-search" style="width:203px;" name="supplier_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a suppliers&hellip;', 'woocommerce' ); ?>" data-action="gs_wc_json_search_suppliers" />
					<input type="submit" class="submit button" value="<?php esc_attr_e( 'Show', 'woocommerce' ); ?>" />
					<input type="hidden" name="range" value="<?php if ( ! empty( $_GET['range'] ) ) echo esc_attr( $_GET['range'] ) ?>" />
					<input type="hidden" name="start_date" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ) ?>" />
					<input type="hidden" name="end_date" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ) ?>" />
					<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
					<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
					<input type="hidden" name="report" value="<?php if ( ! empty( $_GET['report'] ) ) echo esc_attr( $_GET['report'] ) ?>" />
				</div>
			</form>
		</div>
		<h4 class="section_title"><span><?php _e( 'Top sellers', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php

        $top_sellers = $wpdb->get_results(
        $wpdb->prepare(
        "SELECT increases.supplier_id, SUM(reductions.quantity *-1) AS sales_count
        FROM {$this->stockController->stock_reductions_table} AS reductions
        LEFT JOIN {$this->stockController->stock_increases_table} AS increases
        ON reductions.stock_increases_id = increases.id
        WHERE reductions.is_sale = 1
        AND reductions.date >= %s AND reductions.date < %s
        GROUP BY increases.supplier_id",
        date( 'Y-m-d', $this->start_date ),
        date( 'Y-m-d', strtotime( '+1 DAY', $this->end_date ) ))
        );

				if ( $top_sellers ) {
					foreach ( $top_sellers as $supplier ) {
						echo '<tr class="' . ( in_array( $supplier->supplier_id, $this->supplier_ids ) ? 'active' : '' ) . '">
							<td class="count">' . $supplier->sales_count . '</td>
							<td class="name"><a href="' . esc_url( add_query_arg( 'supplier_ids', $supplier->supplier_id ) ) . '">' . get_the_title( $supplier->supplier_id ) . '</a></td>
						</tr>';
            //<td class="sparkline">' . $this->sales_sparkline( $supplier->supplier_id, 7, 'count' ) . '</td>
					}
				} else {
					echo '<tr><td colspan="3">' . __( 'No suppliers found in range', 'woocommerce' ) . '</td></tr>';
				}
				?>
			</table>
		</div>
		<h4 class="section_title"><span><?php _e( 'Top earners', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php
				$top_earners = $wpdb->get_results(
        $wpdb->prepare(
        "SELECT increases.supplier_id, SUM(reductions.single_price*reductions.quantity*-1) AS order_total
        FROM {$this->stockController->stock_reductions_table} AS reductions
        LEFT JOIN {$this->stockController->stock_increases_table} AS increases
        ON reductions.stock_increases_id = increases.id
        WHERE reductions.is_sale = 1
        AND reductions.date >= %s AND reductions.date < %s
        GROUP BY increases.supplier_id",
        date( 'Y-m-d', $this->start_date ),
        date( 'Y-m-d', strtotime( '+1 DAY', $this->end_date ) ))
        );

				if ( $top_earners ) {
					foreach ( $top_earners as $supplier ) {
						echo '<tr class="' . ( in_array( $supplier->supplier_id, $this->supplier_ids ) ? 'active' : '' ) . '">
							<td class="count">' . wc_price( $supplier->order_total ) . '</td>
							<td class="name"><a href="' . esc_url( add_query_arg( 'supplier_ids', $supplier->supplier_id ) ) . '">' . get_the_title( $supplier->supplier_id ) . '</a></td>
						</tr>';
						//<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 7, 'sales' ) . '</td>
					}
				} else {
					echo '<tr><td colspan="3">' . __( 'No products found in range', 'woocommerce' ) . '</td></tr>';
				}
				?>
			</table>
		</div>
		<script type="text/javascript">
			jQuery('.section_title').click(function(){
				var next_section = jQuery(this).next('.section');
				if ( jQuery(next_section).is(':visible') )
					return false;
				jQuery('.section:visible').slideUp();
				jQuery('.section_title').removeClass('open');
				jQuery(this).addClass('open').next('.section').slideDown();
				return false;
			});
			jQuery('.section').slideUp( 100, function() {
				<?php if ( empty( $this->supplier_ids ) ) : ?>
					jQuery('.section_title:eq(1)').click();
				<?php endif; ?>
			});
		</script>
		<?php
	}
	/**
	 * Output an export link.
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time( 'timestamp' ) ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>"
			data-groupby="<?php echo $this->chart_groupby; ?>"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}
	/**
	 * Get the main chart.
	 *
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;
		global $wpdb;

		if ( empty( $this->supplier_ids ) ) {
			?>
			<div class="chart-container">
				<p class="chart-prompt"><?php _e( 'Choose a supplier to view stats', 'woocommerce' ); ?></p>
			</div>
			<?php
		} else {
			// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
			$order_item_counts	 = $wpdb->get_results("SELECT reductions.date as post_date, SUM(reductions.quantity *-1) AS order_item_count
        FROM wp_goosesoft_wc_stock_reductions AS reductions
        LEFT JOIN wp_goosesoft_wc_stock_increases AS increases
        ON reductions.stock_increases_id = increases.id
        WHERE reductions.is_sale = 1
        AND increases.supplier_id IN (".implode(",",$this->supplier_ids).")");

			$order_item_amounts	 = $wpdb->get_results("SELECT reductions.date as post_date, reductions.single_price*reductions.quantity*-1 AS order_item_amount
        FROM wp_goosesoft_wc_stock_reductions AS reductions
        LEFT JOIN wp_goosesoft_wc_stock_increases AS increases
        ON reductions.stock_increases_id = increases.id
        WHERE reductions.is_sale = 1
        AND increases.supplier_id IN (".implode(",",$this->supplier_ids).")");

			// Prepare data for report
			$order_item_counts  = $this->prepare_chart_data( $order_item_counts, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
			$order_item_amounts = $this->prepare_chart_data( $order_item_amounts, 'post_date', 'order_item_amount', $this->chart_interval, $this->start_date, $this->chart_groupby );
			// Encode in json format
			$chart_data = json_encode( array(
				'order_item_counts'  => array_values( $order_item_counts ),
				'order_item_amounts' => array_values( $order_item_amounts ),
			) );
			?>
			<div class="chart-container">
				<div class="chart-placeholder main"></div>
			</div>
			<script type="text/javascript">
				var main_chart;
				jQuery(function(){
					var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );
					var drawGraph = function( highlight ) {
						var series = [
							{
								label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
								data: order_data.order_item_counts,
								color: '<?php echo $this->chart_colours['item_count']; ?>',
								bars: { fillColor: '<?php echo $this->chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
								shadowSize: 0,
								hoverable: false
							},
							{
								label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
								data: order_data.order_item_amounts,
								yaxis: 2,
								color: '<?php echo $this->chart_colours['sales_amount']; ?>',
								points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
								lines: { show: true, lineWidth: 4, fill: false },
								shadowSize: 0,
								<?php echo $this->get_currency_tooltip(); ?>
							}
						];
						if ( highlight !== 'undefined' && series[ highlight ] ) {
							highlight_series = series[ highlight ];
							highlight_series.color = '#9c5d90';
							if ( highlight_series.bars )
								highlight_series.bars.fillColor = '#9c5d90';
							if ( highlight_series.lines ) {
								highlight_series.lines.lineWidth = 5;
							}
						}
						main_chart = jQuery.plot(
							jQuery('.chart-placeholder.main'),
							series,
							{
								legend: {
									show: false
								},
								grid: {
									color: '#aaa',
									borderColor: 'transparent',
									borderWidth: 0,
									hoverable: true
								},
								xaxes: [ {
									color: '#aaa',
									position: "bottom",
									tickColor: 'transparent',
									mode: "time",
									timeformat: "<?php echo ( 'day' === $this->chart_groupby ) ? '%d %b' : '%b'; ?>",
									monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
									tickLength: 1,
									minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
									font: {
										color: "#aaa"
									}
								} ],
								yaxes: [
									{
										min: 0,
										minTickSize: 1,
										tickDecimals: 0,
										color: '#ecf0f1',
										font: { color: "#aaa" }
									},
									{
										position: "right",
										min: 0,
										tickDecimals: 2,
										alignTicksWithAxis: 1,
										color: 'transparent',
										font: { color: "#aaa" }
									}
								],
							}
						);
						jQuery('.chart-placeholder').resize();
					}
					drawGraph();
					jQuery('.highlight_series').hover(
						function() {
							drawGraph( jQuery(this).data('series') );
						},
						function() {
							drawGraph();
						}
					);
				});
			</script>
			<?php
		}
	}
}
