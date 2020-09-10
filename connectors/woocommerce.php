<?php
require_once(dirname(dirname(__FILE__)).'/api.php');

class WooCommerceConnector {

  public function __construct() {
    // init hooks etc
    $this->wc = WooCommerce::instance();

    add_action('woocommerce_checkout_order_processed', array($this, 'order_completed'), 10);
    add_action('woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 3);
    add_action('profile_update', array($this, 'customer_updated'));
    add_action('deleted_user', array($this, 'customer_deleted'));

    add_filter('woocommerce_rest_orders_prepare_object_query', array($this, 'api_order_search_custom_fields'), 20, 2);
  }

  public function connect($shop_id, $secret) {
    return Belco_API::post('/shops/connect', array(
      'id' => $shop_id,
      'type' => 'woocommerce',
      'url' => get_site_url()
    ), array('secret' => $secret, 'blocking' => true));
  }

  public function get_identify_data($secret) {
    $data = $this->wc->session->get( 'belco_identify_data' );
    if(!is_null($data)) {
      return $data;
    } else {
      return array();
    }
  }

  public function clear_session_data(){
    $this->wc->session->set('belco_event_data', json_encode(array()));
    $this->wc->session->set('belco_identify_data', json_encode(array()));
  }

  public function clear_event_data(){
    $this->wc->session->set('belco_event_data', json_encode(array()));
  }

  public function track_event($data){
    $events = $this->get_event_data();
    if(empty($events)) $events = array();
    array_push($events, $data);
    $this->wc->session->set('belco_event_data', json_encode($events));
  }

  public function get_event_data(){
    $events = array();
    $data = $this->wc->session->get('belco_event_data');
    if(!empty($data)){
      if(get_magic_quotes_gpc()) $data = stripslashes($data);
      $events = json_decode($data, true);
    }
    return $events;
  }

  public function order_completed($id) {
    $order = new WC_Order($id);
    $customer = $this->get_customer_from_order($order);
    if ($customer) {
      $this->wc->session->set( 'belco_identify_data' , $customer );
    }

    $this->track_event(array(
      'type' => 'track',
      'event' => 'Order Completed',
      'properties' => array(
        'orderId' => $order->get_order_number(),
        'date' => $order->get_date_created()->getTimestamp(),
        'total' => $order->get_total(),
        'status' => $order->get_status()
      )
    ));
  }

  public function order_status_changed($id, $old_status = false, $new_status = false) {
    $order = new WC_Order($id);
    $customer = $this->get_customer_from_order($order);

    $eventName = null;
    $event = null;
    switch ($new_status) {
      case 'completed':
        $eventName = 'Order Completed';
      case 'cancelled':
      case 'failed':
        $eventName = 'Order Cancelled';
      case 'refunded':
        $eventName = 'Order Refunded';
    }

    if ($eventName) {
      Belco_API::post('/v1/t', array(
        'type' => 'track',
        'event' => $eventName,
        'identity' => $customer,
        'sentAt' => $order->get_date_created()->getTimestamp(),
        'properties' => array(
          'orderId' => $order->get_order_number(),
          'date' => $order->get_date_created()->getTimestamp(),
          'total' => $order->get_total(),
          'status' => $new_status
        )
      ));
    }
  }

  public function customer_updated($id) {
    $customer = $this->get_customer($id);
    if ($customer) {
      Belco_API::post('/sync/customer', $customer);
    }
  }

  public function customer_deleted($id) {
    if ($id) {
      Belco_API::post('/sync/customer/delete', array('id' => $id));
    }
  }

  public function api_order_search_custom_fields($args, $request) {
    global $wpdb;

    $email = $request->get_param('email');

		if ( !$email ) {
			return $args;
		}

		// Search orders
		$post_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT DISTINCT p1.post_id
				FROM {$wpdb->postmeta} p1
				INNER JOIN {$wpdb->postmeta} p2 ON p1.post_id = p2.post_id
				WHERE
					( p1.meta_key = '_billing_email' AND p1.meta_value LIKE '%%%s%%' )
				",
				esc_attr( $email )
			)
		);

    if ( !empty ($args['post__in']) ) {
      $args['post__in'] = array_unique( array_merge( $args['post__in'], $post_ids ) );
    } else {
  		$args['post__in'] = $post_ids;
    }

    return $args;
  }

  public function get_customer($id) {
    $user = get_userdata($id);

    if (!$user) {
      return null;
    }

    $customer = array(
      'id' => $user->ID,
      'email' => $user->user_email,
      'firstName' => get_user_meta($user->ID, 'billing_first_name', true),
      'lastName' => get_user_meta($user->ID, 'billing_last_name', true),
      'signedUp' => strtotime($user->user_registered),
      'phoneNumber' => get_user_meta($user->ID, 'billing_phone', true),
      'country' => get_user_meta($user->ID, 'billing_country', true),
      'city' => get_user_meta($user->ID, 'billing_city', true)
    );

    return $customer;
  }

  public function get_cart() {
    $cart = null;
    $items = array();

    foreach ( $this->wc->cart->get_cart() as $cart_item_key => $cart_item ) {
      $product = $cart_item['data'];

      if ( $product && $product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
        $items[] = array(
          'id' => $cart_item['product_id'],
          'name' => $product->get_title(),
          'price' => $cart_item['line_total'],
          'url' => $product->get_permalink(),
          'quantity' => $cart_item['quantity']
        );
      }
    }

    if (count($items)) {
      $cart = array(
        'currency' => get_woocommerce_currency(),
        'total' => $this->wc->cart->total,
        'subtotal' => $this->wc->cart->subtotal,
        'items' => $items
      );
    }

    return $cart;
  }

  public function get_customer_from_order($order) {
    if (!$order) {
      return null;
    }

    if ($order->get_user_id()) {
      $customer = $this->get_customer($order->get_user());
    } else {
      $customer = array(
        'email' => $order->get_billing_email(),
        'phoneNumber' => $order->get_billing_phone(),
        'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'country' => $order->get_billing_country(),
        'city' => $order->get_billing_city(),
      );
    }

    return $customer;
  }

}
