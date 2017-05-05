<?php

class Supplier_Reports {

  function __construct() {

    add_filter( 'woocommerce_admin_reports', array(&$this, 'add_reports' ), 10, 1);
    //  add_filter( 'wc_admin_reports_path', array(&$this, 'get_report' ), 10, 3);
  }


  public static function add_reports($reports){

    // $suppliers = array(
    //   'suppliers'     => array(
    //     'title'  => __( 'Suppliers', 'gs_wc_suppliers' ),
    //     'reports' => array(
    //       "sales_by_supplier" => array(
    //         'title'       => __( 'Sales by supplier', 'woocommerce' ),
    //         'description' => '',
    //         'hide_title'  => true,
    //         'callback'    => array( __CLASS__, 'get_report' ),
    //       ),
    //     )
    //     )
    //   );

      $reports['orders']['reports']['sales_by_supplier'] = array(
        'title'       => __( 'Sales by supplier', 'woocommerce' ),
        'description' => '',
        'hide_title'  => true,
        'callback'    => array( __CLASS__, 'get_report' ),
      );

      return $reports;
    }

    /**
    * Get a report from our reports subfolder.
    */
    public static function get_report( $name ) {
      $name  = sanitize_title( str_replace( '_', '-', $name ) );
      $class = 'GS_WC_Report_' . str_replace( '-', '_', $name );
      include_once('reports/class-gs-wc-report-' . $name . '.php');
      if ( ! class_exists( $class ) )
      return;
      $report = new $class();
      $report->output_report();
    }
    // public static function get_report( $path, $name='', $class='' ) {
    //
    //   return $path;
    //   $newPath = plugins_url($path, __FILE__);
    //
    //   include_once($path);
    //   if ( ! class_exists( $class ) )
    //     return $path;
    //
    //   return $newPath;
    // }
  }
