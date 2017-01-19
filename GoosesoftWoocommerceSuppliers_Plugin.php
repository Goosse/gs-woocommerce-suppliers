<?php


include_once('GoosesoftWoocommerceSuppliers_LifeCycle.php');
include_once('includes/class-supplier.php');
include_once('includes/class-supplier-reports.php');
include_once('includes/class-gs-stock-controller.php');


class GoosesoftWoocommerceSuppliers_Plugin extends GoosesoftWoocommerceSuppliers_LifeCycle {
  /**
  * See: http://plugin.michael-simpson.com/?page_id=31
  * @return array of option meta data.
  */

  public $stockController;

  public function deactivate(){
    $this->markAsUnInstalled();
  }
  // public function getOptionMetaData() {
  //   //  http://plugin.michael-simpson.com/?page_id=31
  //   return array(
  //     //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
  //     'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
  //     'AmAwesome' => array(__('I like this awesome plugin', 'my-awesome-plugin'), 'false', 'true'),
  //     'CanDoSomething' => array(__('Which user role can do something', 'my-awesome-plugin'),
  //     'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
  //   );
  // }

  //    protected function getOptionValueI18nString($optionValue) {
  //        $i18nValue = parent::getOptionValueI18nString($optionValue);
  //        return $i18nValue;
  //    }

  protected function initOptions() {
    $options = $this->getOptionMetaData();
    if (!empty($options)) {
      foreach ($options as $key => $arr) {
        if (is_array($arr) && count($arr > 1)) {
          $this->addOption($key, $arr[1]);
        }
      }
    }
  }

  public function getPluginDisplayName() {
    return 'GooseSoft WooCommerce Suppliers';
  }

  protected function getMainPluginFileName() {
    return 'goosesoft-woocommerce-suppliers.php';
  }

  /**
  * See: http://plugin.michael-simpson.com/?page_id=101
  * Called by install() to create any database tables if needed.
  * Best Practice:
  * (1) Prefix all table names with $wpdb->prefix
  * (2) make table names lower case only
  * @return void
  */
  protected function installDatabaseTables() {
    global $wpdb;
    $stockIncreaseName = $wpdb->prefix.'goosesoft_wc_stock_increases';
    $stockReductionsName = $wpdb->prefix.'goosesoft_wc_stock_reductions';

    $charset_collate = $wpdb->get_charset_collate();

    $stockIncreasesSql = "CREATE TABLE " .$stockIncreaseName ." (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    date datetime NOT NULL,
    supplier_id bigint(20) NOT NULL,
    product_id bigint(20) NOT NULL,
    quantity bigint(20) NOT NULL,
    stock_left bigint(20) NOT NULL,
    cost numeric(15,2) NOT NULL,
    note varchar(255) NOT NULL,
    PRIMARY KEY  (id)
    ) " .$charset_collate.";";

    $stockReductionsSql = "CREATE TABLE " .$stockReductionsName ." (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    date datetime NOT NULL,
    stock_increases_id bigint(20) NOT NULL,
    single_price numeric(15,2) DEFAULT NULL,
    quantity bigint(20) NOT NULL,
    order_id bigint(20) DEFAULT NULL,
    note varchar(255) NOT NULL,
    PRIMARY KEY  (id)
    ) " .$charset_collate.";";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $stockIncreasesSql );
    dbDelta( $stockReductionsSql );
  }

  /**
  * See: http://plugin.michael-simpson.com/?page_id=101
  * Drop plugin-created tables on uninstall.
  * @return void
  */
  protected function unInstallDatabaseTables() {
    //        global $wpdb;
    //        $tableName = $this->prefixTableName('mytable');
    //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
  }


  /**
  * Perform actions when upgrading from version X to version Y
  * See: http://plugin.michael-simpson.com/?page_id=35
  * @return void
  */
  public function upgrade() {
  }

  public function addActionsAndFilters() {

    // Add options administration page
    // http://plugin.michael-simpson.com/?page_id=47
    add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

    //Stock change/update actions/filters are called on __construct.
    $this->stockController = new GS_Stock_Controller;

    //Supplier actions/filters are called on __construct.
    $supplier = new Supplier;

    //Supplier reports actions/filters are called on __construct.
    $supplierReports = new Supplier_Reports;

    //Stock actions.
    add_action( 'woocommerce_product_options_stock_fields', array(&$this, 'add_gs_supplier_stockFields' ));
    //  add_action( 'woocommerce_product_set_stock', array(&$this, 'gs_wc_save_supplier_stock_meta' ));


    add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_gs_wc_suppliers_scripts' ));


    // Example adding a script & style just for the options administration page
    // http://plugin.michael-simpson.com/?page_id=47
    //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
    //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));




    // Add Actions & Filters
    // http://plugin.michael-simpson.com/?page_id=37


    // Adding scripts & styles to all pages
    // Examples:
    //        wp_enqueue_script('jquery');
    //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
    //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


    // Register short codes
    // http://plugin.michael-simpson.com/?page_id=39


    // Register AJAX hooks
    // http://plugin.michael-simpson.com/?page_id=41

  }

  public function enqueue_gs_wc_suppliers_scripts(){
    $screen       = get_current_screen();

    //Styles
    wp_enqueue_style('gs-wc-suppliers-style', plugins_url('/css/gs_wc_suppliers_admin.css', __FILE__));

    //Scripts
    //wp_register_script( 'gs_wc_enhanced_supplier_select', plugins_url('/js/gs-wc-enhanced-supplier-select.js')l array( 'jquery', 'select2' ));
    wp_register_script( 'gs_wc_enhanced_supplier_select', plugins_url('/'.basename(__DIR__).'/js/gs-wc-enhanced-supplier-select.js'), array( 'jquery', 'select2', 'wc-enhanced-select' ));
    wp_localize_script( 'gs_wc_enhanced_supplier_select', 'wc_enhanced_supplier_select_params', array(
      'search_suppliers_nonce'     => wp_create_nonce( 'gs-wc-search-suppliers' ),
    ));

    wp_register_script( 'gs_wc_block_woocommerce_stock_fields', plugins_url('/'.basename(__DIR__).'/js/gs-wc-block-woocommerce-stock-field.js'), array( 'jquery' ));

    $enque_enhanced_select = false;
    if(isset($_GET['page']) && isset($_GET['report'])){
      if ($_GET['page'] == 'wc-reports' && $_GET['report'] == 'sales_by_supplier'){
        $enque_enhanced_select = true;
      }
    }

    if  ( $screen->post_type == 'product' || $enque_enhanced_select == true)
    wp_enqueue_script( 'gs_wc_enhanced_supplier_select' );


    if  ( $screen->post_type == 'product')
    wp_enqueue_script( 'gs_wc_block_woocommerce_stock_fields' );

  }

  function add_gs_supplier_stockFields(){
    global $post;
    ?>

    <p class="form-field">
      <label for="supplier_ids"><?php _e( 'Change stock by supplier', 'gs_wc_suppliers' ); ?></label>
      <!-- Stock -->
      <input type="number" style="width:8%;margin-right: 2%" name="gs_wc_supplier_stock_qty" id="gs_wc_supplier_stock_qty" value="" placeholder="Qty" >

      <input type="hidden"
      class="gs-wc-supplier-search"
      style="width: 30%;"
      id="gs_wc_add_supplier_id"
      name="gs_wc_add_supplier_id"
      data-placeholder="<?php esc_attr_e( 'Search for a supplier&hellip;', 'gs_wc_suppliers' ); ?>"
      data-allow_clear="true"
      data-action="gs_wc_json_search_suppliers"
      data-multiple="false"
      value="" />

      <!-- Cost -->
      <input type="number" style="width:8%;margin-left: 2%" name="gs_wc_supplier_cost" id="gs_wc_supplier_cost" value="" placeholder="Cost" step="any" >

      <?php echo wc_help_tip( __( 'Add inventory to stock that is linked to a supplier at a speficied cost. These costs will be tracked using the FIFO method.', 'gs_wc_suppliers' ) );?>

      <input type="text" class="gs_stock_change_note" style="width:50%;margin: 8px 5px 0 0;" name="gs_stock_change_note" id="gs_stock_change_note" placeholder="Note" >

      </p>
    <?php
    echo $this->stockController->stockHistoryTable(get_the_ID()); ?>
    <?php
  }
}
