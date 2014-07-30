<?
    
    global $wp;
 
    preg_match("/(00|\+)?([0-9]{1,2})?(0)?(\d{9,10})/", $wp->query_vars['belco_cid'], $matches);
    
    $args = array(
       'post_type' => 'shop_order',
       'meta_key' => '_billing_phone',
       'meta_query' => array(
           array(
               'key' => '_billing_phone',
               'value' => end($matches),
               'compare' => 'LIKE',
           )
       )
     );
    
    $loop = new WP_Query( $args );
    
    $results = array();
    
    while ( $loop->have_posts() ) : $loop->the_post();
    
    	$order_id = $loop->post->ID;
    	
    	$order = new WC_Order($order_id);
    	
    	
    	
    	$result["id"] = $order_id;
      
      $result["email"] = $order->billing_email;
      
      $result["phone"] = $order->billing_phone;
      
      $result["items"] = $order->get_item_count();
      
      // $result["items"] = $order->get_items();
      
      $result["total"] = $order->get_total();
      
      $result["currency"] = $order->get_order_currency();
      
      $result["status"] = $order->status;
      
      $result["orderDate"] = $order->order_date;
      
      $result["modifiedData"] = $order->modified_date;
      
      $result["address"] = $order->get_billing_address();
      
      $result["url"] = "/";
      
      $results[] = $result;
   
    endwhile;
    
    $data["customer"]["firstName"] = "Andries";
    
    $data["customer"]["lastName"] = "Reitsma";
    
    $data["customer"]["phone"] = "+31614269740";
    
    $data["customer"]["email"] = "andries@forwarder.nl";
    
    $data["orders"] = $results;

    wp_send_json($data);
 
?>