<?php

class Supplier {

  function __construct() {

          add_action('init', array(&$this, 'create_post_type'));
          add_filter( 'enter_title_here', array(&$this, 'change_title' ));
          add_action( 'admin_init', array(&$this, 'add_meta_boxes' ));
          add_action( 'save_post', array(&$this, 'save_custom_fields' ));
          add_action( 'wp_ajax_gs_wc_json_search_suppliers', array(&$this, 'json_search_suppliers' ));

     }

  public function create_post_type() {

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
  }

  // static function get_supplier($id){
  //   return get_post($id);
  // }

  function add_meta_boxes() {
    add_meta_box("gs_suppliers_contact_meta", "Contact Details", array(&$this, "add_contact_details_meta_box"), "gs_wc_suppliers", "normal", "low");
  }
  function add_contact_details_meta_box()
  {
    global $post;
    $custom = get_post_custom( $post->ID );

    ?>
    <div class="gscol50left">
      <fieldset>
        <legend>Address:</legend>
        <label for="street-address">Street:</label>
        <input type="text" name="street-address" value="<?= @$custom["street-address"][0] ?>" class="fullWidth" />
        <label for="street-address2">Line 2:</label>
        <input type="text" name="street-address2" value="<?= @$custom["street-address2"][0] ?>" class="fullWidth" />
        <label for="city">City:</label>
        <input type="text" name="city" value="<?= @$custom["city"][0] ?>" class="fullWidth" />
        <div class="gscol50left">
          <label for="state">State:</label>
          <input type="text" name="state" value="<?= @$custom["state"][0] ?>" class="fullWidth" />
        </div>
        <div class="gscol50right">
          <label for="zip">Zip:</label>
          <input type="text" name="zip" value="<?= @$custom["zip"][0] ?>" class="fullWidth" />
        </div>
      </fieldset>
    </div>
    <div class="gscol50right">
      <p>
        <div class="gscol50left">
        <label>Phone:</label><br />
        <input type="text" name="phone" value="<?= @$custom["phone"][0] ?>" class="fullWidth" />
      </div>
      <div class="gscol50right">
        <label>Phone 2:</label><br />
        <input type="text" name="phone2" value="<?= @$custom["phone2"][0] ?>" class="fullWidth" />
      </div>
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
        <label><input type="checkbox" name="consignor" <?= @$custom["consignor"][0]=='yes'?'checked':''?>/> Consignor</label>
      </p>
      <p id="commission" class="<?= @$custom["consignor"][0]=='yes'?'':'hidden'?>">
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
  function save_custom_fields(){
    global $post;

    if ( $post && $post->post_type == 'gs_wc_suppliers')
    {
      update_post_meta($post->ID, "street-address", @$_POST["street-address"]);
      update_post_meta($post->ID, "street-address2", @$_POST["street-address2"]);
      update_post_meta($post->ID, "city", @$_POST["city"]);
      update_post_meta($post->ID, "state", @$_POST["state"]);
      update_post_meta($post->ID, "zip", @$_POST["zip"]);
      update_post_meta($post->ID, "phone", @$_POST["phone"]);
      update_post_meta($post->ID, "phone2", @$_POST["phone2"]);
      update_post_meta($post->ID, "email", @$_POST["email"]);
      update_post_meta($post->ID, "website", @$_POST["website"]);
      update_post_meta($post->ID, "consignor", @$_POST["consignor"]=="on"?"yes":NULL);
      update_post_meta($post->ID, "commission", @$_POST["commission"]);
    }
  }

  function change_title( $title ){
    $screen = get_current_screen();
    if  ( $screen->post_type == 'gs_wc_suppliers' ) {
      return 'Enter supplier name here';
    } else {
      return 'Enter title here';
    }
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

 ?>
