<?php
class GS_Stock_Controller {

  public $stock_increases_table = 'wp_goosesoft_wc_stock_increases';
  public $stock_reductions_table = 'wp_goosesoft_wc_stock_reductions';
//  public $supplier_meta_key = '_gs_wc_stock_by_suppliers';

  function __construct() {

    //  add_action( 'woocommerce_product_set_stock', array(&$this, 'reconcile_with_suppliers' ), 10, 1);
    //add_filter('woocommerce_get_stock_quantity', array(&$this, 'get_stock_for_woocommerce'), 10, 2);
    add_action( 'woocommerce_process_product_meta', array(&$this, 'save_supplier_stock_meta'),11,2 );
    //    add_action('edit_form_top', array(&$this, 'check_wc_stock'),10,1);

    add_action('woocommerce_reduce_order_stock', array(&$this, 'reduce_stock_by_order'), 10, 1);
  }


  // function get_stock_for_woocommerce($wc_stock, $product){
  //   return $this->get_stock($product->id);
  // }

  function get_stock($product_id){
    global $wpdb;

    $totalStock = $wpdb->get_results(
    $wpdb->prepare(
    "SELECT (SELECT SUM(quantity) FROM {$this->stock_increases_table} WHERE product_id = %d) +
    IFNULL((SELECT SUM(reductions.quantity)
    FROM {$this->stock_reductions_table} AS reductions
    LEFT JOIN {$this->stock_increases_table} AS increases
    ON increases.id = reductions.stock_increases_id
    WHERE increases.product_id = %d),0) as in_stock"
    , $product_id, $product_id)
  );

  return $totalStock[0]->in_stock;
}

//This will update the built in woocommerce stock to reflect gs_wc_stock numbers.
function update_wc_stock($product_id){

  $stock = $this->get_stock($product_id);
  $product = wc_get_product($product_id);

  if ($stock != $product->stock) {
    wc_update_product_stock($product_id, $stock);
    /*
    * This would automatically set a product to "In Stock" if quantity was set to greater than zero.
    * This would probably go against Woocomerce design.
    *$product = wc_get_product($product_id); //Refresh info for product.
    if ($product->stock > 0) {
      $product->set_stock_status( 'instock' );
    } */
  }


}

// function check_wc_stock($post){
//   if (get_post_type($post) == "product"){
//     $this->update_wc_stock(get_the_ID());
//   }
// }

public function increaseStock($product_id,$supplier_id, $ammount, $cost, $note){
  global $wpdb;

  $wpdb->insert(
  $this->stock_increases_table,
  array(
    'supplier_id' => $supplier_id,
    'product_id' => $product_id,
    'quantity' => $ammount,
    'cost' => $cost,
    'date' => current_time( 'mysql' ),
    'stock_left' => $ammount,
    'note' => $note,
    )
  );

  $this->update_wc_stock($product_id);
}

//Set if it is a sale.  If $order_id == NULL, it will be treated as a back end adjustment.
//Set fifo.  If $fifo = true, it will be treated as First In First Out, otherwise LIFO(Last In First Out)
public function reduceStock($product_id, $ammount, $order_id, $optionals = array()){
  global $wpdb;

  $defaults = array(
        'note'   => "",
        'single_price' => null,
        'fifo'  => true
    );
  $optionals = array_merge($defaults, $optionals);

  global $wpdb;

  $order = $optionals['fifo']?'ASC':'DESC';

  $availableStock = $wpdb->get_results(
  $wpdb->prepare("SELECT increases.id AS increase_id, increases.quantity+IFNULL(reductions.quantity, 0) as stock_left
  FROM {$this->stock_increases_table} as increases
  LEFT JOIN (SELECT red.stock_increases_id, SUM(red.quantity) as quantity
  FROM {$this->stock_reductions_table} AS red
  LEFT JOIN {$this->stock_increases_table} AS inc
  ON inc.id = red.stock_increases_id
  WHERE inc.product_id = %d
  GROUP BY inc.id) as reductions
  ON increases.id = reductions.stock_increases_id
  WHERE product_id = %d
  HAVING stock_left > 0
  ORDER BY increase_id {$order}",$product_id, $product_id));


  $entries_with_stock = array();

  foreach($availableStock as $entry){

    $amount_left_to_remove = $ammount - $entry->stock_left;


    if ($amount_left_to_remove > 0){
      $wpdb->insert(
      $this->stock_reductions_table,
      array(
        'stock_increases_id' => $entry->increase_id,
        'quantity' => -$entry->stock_left,
        'date' => current_time( 'mysql' ),
        'order_id' => $order_id,
        'single_price'=>$optionals['single_price'],
        'note' => $optionals['note']
        )
      );
    }
    else {
      $wpdb->insert(
      $this->stock_reductions_table,
      array(
        'stock_increases_id' => $entry->increase_id,
        'quantity' => -$ammount,
        'date' => current_time( 'mysql' ),
        'order_id' => $order_id,
        'single_price'=>$optionals['single_price'],
        'note' => $optionals['note']
        )
      );
      break;
    }

    $ammount = $amount_left_to_remove;
  }

  $this->update_wc_stock($product_id);

}


function reduce_stock_by_order($order){

  foreach ( $order->get_items() as $item ) {
    if ( $item['type'] == 'line_item' && ( $product = wc_get_product($item['item_meta']['_product_id'][0]) ) && $product->managing_stock() ) {
      $qty = $item['qty'];
    //  $item_price = $item['line_total'];
      $postLink = $this->get_edit_post_link($order->id)?"<a href='".$this->get_edit_post_link($order->id)."'>View Order</a>":"";
      $this->reduceStock($product->id, $qty, $order->id, array('note'=>$postLink, 'single_price'=>$item['line_total']/$qty));
    }
  }
}

function save_supplier_stock_meta($product_id, $post){
  if ($_POST['gs_wc_supplier_stock_qty'] == 0)
  return;

  if ($_POST['gs_wc_supplier_stock_qty'] < 0){
    $this->reduceStock($product_id, abs($_POST['gs_wc_supplier_stock_qty']), '', array('note'=>$_POST['gs_stock_change_note']));
    return;
  }
  //  return;

  $product = wc_get_product( $product_id );
  if (!isset($product)) //If there's no product, return;
  return;
  //if $_POST['gs_wc_supplier_stock_qty']

  if (isset($_POST['gs_wc_add_supplier_id']) && isset($_POST['gs_wc_supplier_stock_qty']) && isset($_POST['gs_wc_supplier_cost'])) {

    if(is_numeric($_POST['gs_wc_add_supplier_id']) && is_numeric($_POST['gs_wc_supplier_stock_qty']) && is_numeric($_POST['gs_wc_supplier_cost'])) {

      $this->increaseStock(
      $_POST['post_ID'],
      $_POST['gs_wc_add_supplier_id'],
      $_POST['gs_wc_supplier_stock_qty'],
      $_POST['gs_wc_supplier_cost'],
      $_POST['gs_stock_change_note']
    );

  }
}
}

public function stockHistoryTable($product_id){
  global $wpdb;

  //  $this->update_wc_stock($product_id);

  //  $product = wc_get_product( get_the_ID() );
  $stockIncreaseHistory = $wpdb->get_results(
  $wpdb->prepare("SELECT * FROM {$this->stock_increases_table} WHERE product_id = %d", $product_id), ARRAY_A
);

if (count($stockIncreaseHistory) == 0)
return;


$increaseIds = array_column($stockIncreaseHistory, 'id');


$stockReductionHistory = $wpdb->get_results(
"SELECT reductions.id, increases.supplier_id, increases.product_id, reductions.quantity, increases.cost, reductions.date, reductions.order_id, reductions.note
FROM {$this->stock_reductions_table} AS reductions
LEFT JOIN {$this->stock_increases_table} AS increases
ON reductions.stock_increases_id = increases.id
WHERE reductions.stock_increases_id IN (".implode(',', $increaseIds).")", ARRAY_A
);

$stockHistory = array_merge($stockIncreaseHistory, $stockReductionHistory);

usort($stockHistory, function($a, $b) {
  $t1 = strtotime($a['date']);
  $t2 = strtotime($b['date']);
  return $t1-$t2==0?$a['id']-$b['id']:$t1-$t2;
});

if (count($stockHistory)> 0) {
  $formHtml = '<div class="form-field">
  <label for="supplier_ids">'
  .__( 'Stock history', 'gs_wc_suppliers' )
  .'<div class="legend sold"></div>
  <div class="legend increased"></div>
  <div class="legend reduced"></div>
  </label>
  <table>
  <tr>
  <th>Date</th>
  <th>Supplier</th>
  <th>Cost</th>
  <th>Quantity</th>
  <th>Running Total</th>
  <th>Note</th>
  </tr>';


  $table_rows = array();
  $runningTotal = 0;

  foreach($stockHistory as $entry){

    if ($entry['quantity'] == 0)
    continue;


    if (!isset($entry['order_id'])){
      $class = "increased";
    }
    else{
      if ($entry['order_id']) {
        $class = "sold";
      }
      else{
        $class = "reduced";
      }

      //  $entry->quantity = -$entry->quantity;
    }
    $note = "";
    if(isset($entry['note'])){
      $note = $entry['note'];
    }


    $runningTotal += $entry['quantity'];

    $row = '<tr class="'.$class .'">
    <td style="width:20%">'.$entry['date'].'</td>
    <td style="width:25%">'.get_the_title($entry['supplier_id']).'</td>
    <td style="width:8%">'.wc_price($entry['cost']).'</td>
    <td style="width:8%">'.$entry['quantity'].'</td>
    <td style="width:8%">'.$runningTotal.'</td>
    <td>'.$note.'</td>
    </tr>';

    array_push($table_rows, $row);

  }
  $formHtml .= implode('',array_reverse($table_rows));

  $formHtml .= '</table></div>';

  return $formHtml;
}
}

function get_edit_post_link( $id = 0) {
  if ( ! $post = get_post( $id ) )
  return;

  $action = '&amp;action=edit';

  $post_type_object = get_post_type_object( $post->post_type );
  if ( !$post_type_object )
  return;

  if ( $post_type_object->_edit_link ) {
    $link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
  } else {
    return;
  }

  return $link;
}
}
?>
