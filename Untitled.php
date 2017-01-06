Array
(
[select] => SELECT SUM( order_item_meta__line_total.meta_value) as order_item_amount, posts.post_date as post_date, order_item_meta__product_id.meta_value as product_id
[from] => FROM wp_posts AS posts
[join] => INNER JOIN wp_woocommerce_order_items AS order_items ON posts.ID = order_items.order_id INNER JOIN wp_woocommerce_order_itemmeta AS order_item_meta__line_total ON (order_items.order_item_id = order_item_meta__line_total.order_item_id)  AND (order_item_meta__line_total.meta_key = '_line_total') INNER JOIN wp_woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') INNER JOIN wp_woocommerce_order_itemmeta AS order_item_meta__product_id_array ON order_items.order_item_id = order_item_meta__product_id_array.order_item_id
[where] =>
  WHERE 	posts.post_type 	IN ( 'shop_order','shop_order_refund' )

    AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold')

    AND 	posts.post_date >= '2016-10-27'
    AND 	posts.post_date < '2016-11-03'
   AND ( ( order_item_meta__product_id_array.meta_key   IN ('_product_id','_variation_id') AND order_item_meta__product_id_array.meta_value IN ('45') ))
[group_by] => GROUP BY product_id, YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)
[order_by] => ORDER BY post_date ASC
)
