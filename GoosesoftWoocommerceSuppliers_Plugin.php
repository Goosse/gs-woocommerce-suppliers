<?php


include_once('GoosesoftWoocommerceSuppliers_LifeCycle.php');

class GoosesoftWoocommerceSuppliers_Plugin extends GoosesoftWoocommerceSuppliers_LifeCycle {

  /**
  * See: http://plugin.michael-simpson.com/?page_id=31
  * @return array of option meta data.
  */
  public function getOptionMetaData() {
    //  http://plugin.michael-simpson.com/?page_id=31
    return array(
      //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
      'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
      'AmAwesome' => array(__('I like this awesome plugin', 'my-awesome-plugin'), 'false', 'true'),
      'CanDoSomething' => array(__('Which user role can do something', 'my-awesome-plugin'),
      'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
    );
  }

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
    //        global $wpdb;
    //        $tableName = $this->prefixTableName('mytable');
    //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
    //            `id` INTEGER NOT NULL");
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

    //Supplier Actions.
    add_action('init', array(&$this, 'create_gs_wc_suppliers_post_type'));
    add_filter( 'enter_title_here', array(&$this, 'change_gs_suppliers_default_title' ));
    add_action( 'admin_init', array(&$this, 'add_gs_suppliers_meta_boxes' ));
    add_action( 'save_post', array(&$this, 'save_gs_suppliers_custom_fields' ));


    //Stock actions.
    add_action( 'woocommerce_product_options_stock_fields', array(&$this, 'add_gs_supplier_stockFields' ));
    add_action( 'wp_ajax_gs_wc_json_search_suppliers', array(&$this, 'json_search_suppliers' ));
    add_action( 'woocommerce_process_product_meta', array(&$this, 'gs_wc_save_supplier_stock_meta' ));



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
    wp_register_script( 'gs_wc_enhanced_supplier_select', plugins_url('/gs-wc-suppliers/js/gs-wc-enhanced-supplier-select.js'), array( 'jquery', 'select2', 'wc-enhanced-select' ));
    wp_localize_script( 'gs_wc_enhanced_supplier_select', 'wc_enhanced_supplier_select_params', array(
      'search_suppliers_nonce'     => wp_create_nonce( 'gs-wc-search-suppliers' ),
    ));

    if  ( $screen->post_type == 'product' )
      wp_enqueue_script( 'gs_wc_enhanced_supplier_select' );
  }

  //Create Suppliers Taxonomy.
  public function create_gs_wc_suppliers_post_type() {

    register_post_type( 'gs_wc_suppliers',
    array(
      'labels' => array(
        'name' => __( 'Suppliers' ),
        'singular_name' => __( 'Supplier' ),
        'add_new_item' => __( 'Add New Supplier' ),
        'edit_item' => __( 'Edit Supplier' ),
        'new_item' => __( 'New Supplier' ),
        'view_item' => __( 'View Supplier' ),
        'search_items' => __( 'Search Suppliers' ),
        'not_found' => __( 'No suppliers found' ),
        'not_found_in_trash' => __( 'No suppliers found in trash' ),
        'all_items' => __( 'All Suppliers' )
      ),
      'description' => 'Suppliers for WooCommerce products.',
      'public' => false,
      'show_ui' => true,
      'show_in_menu' => true,
      'menu_position' => 56,
      'supports' => array('title')
      )
    );
    // register_taxonomy( 'suppliers' , 'product' , array(
    // 	'hierarchical' => false,
    // 	'labels'       => array(
    // 		'name'                           => 'Suppliers',
    // 		'singular_name'                  => 'Supplier',
    // 		'search_items'                   => 'Search Suppliers',
    // 		'all_items'                      => 'All Suppliers',
    // 		'edit_item'                      => 'Edit Supplier',
    // 		'update_item'                    => 'Update Supplier',
    // 		'add_new_item'                   => 'Add New Supplier',
    // 		'new_item_name'                  => 'New Supplier Name',
    // 		'menu_name'                      => 'Suppliers',
    //     'view_item'                      => 'View Supplier',
    //     'popular_items'                  => 'Popular Suppliers',
    //     'separate_items_with_commas'     => 'Separate suppliers with commas',
    //     'add_or_remove_items'            => 'Add or remove suppliers',
    //     'choose_from_most_used'          => 'Choose from the most used suppliers',
    //     'not_found'                      => 'No suppliers found'
    // 	),
    // 	'show_ui'               => true,
    // 	'show_in_nav_menus'     => true,
    // 	'public'                => false,
    // 	'rewrite'               => array(
    // 	'slug' => 'suppliers'
    // )
    // ));
  }

  function add_gs_suppliers_meta_boxes() {
    add_meta_box("gs_suppliers_contact_meta", "Contact Details", array(&$this, "add_contact_details_gs_suppliers_meta_box"), "gs_wc_suppliers", "normal", "low");
  }
  function add_contact_details_gs_suppliers_meta_box()
  {
    global $post;
    $custom = get_post_custom( $post->ID );

    ?>
    <div class="gscol50left">
      <fieldset>
        <legend>Address:</legend>
        <label for="street-address">Street:</label>
        <input type="text" name="street-address" value="<?= @$custom["street-address"][0] ?>" class="fullWidth" />
        <label for="street-address">City:</label>
        <input type="text" name="city" value="<?= @$custom["city"][0] ?>" class="fullWidth" />
        <div class="gscol50left">
          <label for="street-address">State:</label>
          <input type="text" name="state" value="<?= @$custom["state"][0] ?>" class="fullWidth" />
        </div>
        <div class="gscol50right">
          <label for="street-address">Zip:</label>
          <input type="text" name="zip" value="<?= @$custom["zip"][0] ?>" class="fullWidth" />
        </div>
      </fieldset>
    </div>
    <div class="gscol50right">
      <p>
        <label>Phone:</label><br />
        <input type="text" name="phone" value="<?= @$custom["phone"][0] ?>" class="fullWidth" />
      </p>
      <p>
        <label>Email:</label><br />
        <input type="text" name="email" value="<?= @$custom["email"][0] ?>" class="fullWidth" />
      </p>
      <p>
        <label>Website:</label><br />
        <input type="text" name="website" value="<?= @$custom["website"][0] ?>" class="fullWidth" />
      </p>
      <p>
        <label><input type="checkbox" name="consignor" <?= @$custom["consignor"][0]=='on'?'checked':''?>/> Consignor</label>
      </p>
      <p id="commission" class="<?= @$custom["consignor"][0]=='on'?'':'hidden'?>">
        <label>Commission:</label><br />
        <input id="comissionAmmount" type="number" style="width: 50px" name="commission" value="<?= @$custom["commission"][0] ?>"/>%
      </p>
    </div>
    <div class="clear"></div>
    <script>
    jQuery('input[type="checkbox"][name="consignor"]').change(function() {
     if(this.checked) {
         jQuery('#commission').removeClass('hidden');
     }
     else{
       jQuery('#commission').addClass('hidden');
       jQuery('#comissionAmmount').removeAttr('value');;
     }
 });
    </script>
    <?php
  }
  /**
  * Save custom field data when creating/updating posts
  */
  function save_gs_suppliers_custom_fields(){
    global $post;

    if ( $post && $post->post_type == 'gs_wc_suppliers')
    {
      update_post_meta($post->ID, "street-address", @$_POST["street-address"]);
      update_post_meta($post->ID, "city", @$_POST["city"]);
      update_post_meta($post->ID, "state", @$_POST["state"]);
      update_post_meta($post->ID, "zip", @$_POST["zip"]);
      update_post_meta($post->ID, "address", @$_POST["address"]);
      update_post_meta($post->ID, "phone", @$_POST["phone"]);
      update_post_meta($post->ID, "email", @$_POST["email"]);
      update_post_meta($post->ID, "website", @$_POST["website"]);
      update_post_meta($post->ID, "consignor", @$_POST["consignor"]);
      update_post_meta($post->ID, "commission", @$_POST["commission"]);
    }
  }

  function change_gs_suppliers_default_title( $title ){
    $screen = get_current_screen();
    if  ( $screen->post_type == 'gs_wc_suppliers' ) {
      return 'Enter supplier name here';
    } else {
      return 'Enter title here';
    }
  }


  //Stock functions
  function add_gs_supplier_stockFields(){
    global $post;
    ?>

    <p class="form-field">
      <label for="supplier_ids"><?php _e( 'Change inventory from supplier', 'gs_wc_suppliers' ); ?></label>
    <input type="hidden"
    class="gs-wc-supplier-search"
    style="width: 35%;"
    id="supplier_id"
    name="supplier_id"
    data-placeholder="<?php esc_attr_e( 'Search for a supplier&hellip;', 'gs_wc_suppliers' ); ?>"
    data-allow_clear="true"
    data-action="gs_wc_json_search_suppliers"
    data-multiple="false"
    data-exclude="<?php echo intval( $post->ID ); ?>"
    data-selected="<?php
                            $product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_upsell_ids', true ) ) );
                            $json_ids    = array();

                            foreach ( $product_ids as $product_id ) {
                                $product = wc_get_product( $product_id );
                                if ( is_object( $product ) ) {
                                    $json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                                }
                            }

                            echo esc_attr( json_encode( $json_ids ) );
                        ?>"
    value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
    <?php echo wc_help_tip( __( 'Up-sells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woocommerce' ) );?>
    </p>
    <?php
  }

  function gs_wc_save_supplier_stock_meta(){
    $supplier = isset( $_POST['supplier_id'] ) ? array_filter( array_map( 'intval', explode( ',', $_POST['supplier_id'] ) ) ) : array();
    update_post_meta( $_POST['post_ID'], '_gs_wc_prod_supplier_entry', $supplier );
    error_log("save_suppliers");
    error_log(print_r($supplier, true));
  }


  /**
	 * Search for suppliers and echo json.
	 *
	 * @param string $term (default: '')
	 * @param string $post_types (default: array('gs_wc_suppliers'))
	 */
	public static function json_search_suppliers( $term = '', $post_types = array( 'gs_wc_suppliers' ) ) {
		global $wpdb;
		ob_start();
		check_ajax_referer( 'gs-wc-search-suppliers', 'security' );
		if ( empty( $term ) ) {
			$term = wc_clean( stripslashes( $_GET['term'] ) );
		} else {
			$term = wc_clean( $term );
		}
		if ( empty( $term ) ) {
			die();
		}
		$like_term = '%' . $wpdb->esc_like( $term ) . '%';
		if ( is_numeric( $term ) ) {
			$query = $wpdb->prepare( "
				SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				WHERE posts.post_status = 'publish'
				AND (
					posts.post_parent = %s
					OR posts.ID = %s
					OR posts.post_title LIKE %s
					OR (
						postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
				)
			", $term, $term, $term, $like_term );
		} else {
			$query = $wpdb->prepare( "
				SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				WHERE posts.post_status = 'publish'
				AND (
					posts.post_title LIKE %s
					or posts.post_content LIKE %s
					OR (
						postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
				)
			", $like_term, $like_term, $like_term );
		}
		$query .= " AND posts.post_type IN ('" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "')";
		if ( ! empty( $_GET['exclude'] ) ) {
			$query .= " AND posts.ID NOT IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['exclude'] ) ) ) . ")";
		}
		if ( ! empty( $_GET['include'] ) ) {
			$query .= " AND posts.ID IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['include'] ) ) ) . ")";
		}
		if ( ! empty( $_GET['limit'] ) ) {
			$query .= " LIMIT " . intval( $_GET['limit'] );
		}
		$posts          = array_unique( $wpdb->get_col( $query ) );
		$found_suppliers = array();
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$supplierName =  get_post( $post ) -> post_title;
				// if ( ! current_user_can( 'read_product', $post ) ) {
				// 	continue;
				// }

				$found_suppliers[ $post ] = rawurldecode( $supplierName );
			}
		}
		$found_suppliers = apply_filters( 'gs_wc_json_search_found_suppliers', $found_suppliers );
		wp_send_json( $found_suppliers );
	}
}
