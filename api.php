<?php
class Belco_API {
	
	public $headers = array();
	
	public function __construct($query_vars) {
		
		$this->headers = $this->get_headers($_SERVER);
		
		$this->check_authentication();
		
		return $this->query($query_vars['belco_cid']);
	}
	
	public function check_authentication() {
		$api_key = isset($this->headers['X_API_KEY']) ? $this->headers['X_API_KEY'] : null;

		if (!$api_key || $api_key !== get_option('belco_api_secret')) {
			throw new Exception('Authentication failed');
		}
	}
	
	public function get_headers($server) {
		$headers = array();
		// CONTENT_* headers are not prefixed with HTTP_
		$additional = array('CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true);

		foreach ($server as $key => $value) {
			if ( strpos( $key, 'HTTP_' ) === 0) {
				$headers[ substr( $key, 5 ) ] = $value;
			}
			elseif ( isset( $additional[ $key ] ) ) {
				$headers[ $key ] = $value;
			}
		}

		return $headers;
	}
	
	protected function _parsePhoneNumber($number) {
    if (!preg_match("/(?P<prefix>00|\+)?(?P<country_code>[0-9]{1,2})?(?P<local_prefix>0)?(?P<number>\d{9,})/", $number, $parsed)) {
			throw new Exception('Invalid phone number');
    }

		$variations = array(
			'e164' => $number,
			'international_prefix' => '00' . $parsed['country_code'] . $parsed['number'],
			'international' => $parsed['country_code'] . $parsed['number'],
			'national_prefix' => '0' . $parsed['number'],
			'national' => $parsed['number']
		);
		
		return $variations;
	}
	
	public function query($number = '') {
		$matches = $this->_parsePhoneNumber($number);
		$phone = $matches['national'];
		
		$customer = $this->find_customer_by_phone($phone);
		if ($customer) {
			$orders = $this->find_customer_orders($customer['id']);
		} else {
			$orders = $this->find_orders_by_phone($phone);
		}
		
		wp_send_json(array(
			'customer' => $customer,
			'orders' => $orders
		));
	}
	
	public function find_customer_by_phone($phone) {
		$query = array(
			'role' => 'customer',
			'fields' => array('id'),
			'meta_key' => 'billing_phone',
			'meta_value' => $number,
			'meta_compare' => 'like'
		);

    $users = get_users( $query );
		
		if (!count($users)) {
			return null;
		}

		return $this->get_customer($users[0]->id);
	}
	
	public function get_customer($id) {
		$customer = new WP_User($id);
		
		return array(
			'id'               => $customer->ID,
			'created_at'       => $customer->user_registered,
			'email'            => $customer->user_email,
			'first_name'       => $customer->first_name,
			'last_name'        => $customer->last_name,
			'username'         => $customer->user_login,
			'billing_address'  => array(
				'first_name' => $customer->billing_first_name,
				'last_name'  => $customer->billing_last_name,
				'company'    => $customer->billing_company,
				'address_1'  => $customer->billing_address_1,
				'address_2'  => $customer->billing_address_2,
				'city'       => $customer->billing_city,
				'state'      => $customer->billing_state,
				'postcode'   => $customer->billing_postcode,
				'country'    => $customer->billing_country,
				'email'      => $customer->billing_email,
				'phone'      => $customer->billing_phone,
			),
			'shipping_address' => array(
				'first_name' => $customer->shipping_first_name,
				'last_name'  => $customer->shipping_last_name,
				'company'    => $customer->shipping_company,
				'address_1'  => $customer->shipping_address_1,
				'address_2'  => $customer->shipping_address_2,
				'city'       => $customer->shipping_city,
				'state'      => $customer->shipping_state,
				'postcode'   => $customer->shipping_postcode,
				'country'    => $customer->shipping_country,
			),
		);
	}
	
	public function find_customer_orders($id) {
		$query = array(
			'post_type' => 'shop_order',
			'meta_key' => '_customer_user',
			'meta_value' => $id
		);

    $loop = new WP_Query( $query );
    
    $orders = array();
    
    while ( $loop->have_posts() ) {
			$loop->the_post();
      $orders[] = $this->get_order($loop->post->ID);
    };
		
		return $orders;
	}
	
	public function find_orders_by_phone($phone) {
		$query = array(
			'post_type' => 'shop_order',
			'meta_key' => 'billing_phone',
			'meta_value' => $phone,
			'meta_compare' => 'like'
		);

    $loop = new WP_Query( $query );
    
    $orders = array();
    
    while ( $loop->have_posts() ) {
			$loop->the_post();
      $orders[] = $this->get_order($loop->post->ID);
    };
		
		return $orders;
	}
	
	public function get_order($id) {
  	$order = new WC_Order($id);

		return array(
			'id'                        => $order->id,
			'order_number'              => $order->get_order_number(),
			'created_at'                => $order_post->post_date_gmt,
			'updated_at'                => $order_post->post_modified_gmt,
			'completed_at'              => $order->completed_date,
			'status'                    => $order->status,
			'currency'                  => $order->get_order_currency(),
			'total'                     => wc_format_decimal( $order->get_total(), 2 ),
			'shipping_methods'          => $order->get_shipping_method(),
			'payment_details' => array(
				'method_id'    => $order->payment_method,
				'method_title' => $order->payment_method_title,
				'paid'         => isset( $order->paid_date ),
			),
			'billing_address' => array(
				'first_name' => $order->billing_first_name,
				'last_name'  => $order->billing_last_name,
				'company'    => $order->billing_company,
				'address_1'  => $order->billing_address_1,
				'address_2'  => $order->billing_address_2,
				'city'       => $order->billing_city,
				'state'      => $order->billing_state,
				'postcode'   => $order->billing_postcode,
				'country'    => $order->billing_country,
				'email'      => $order->billing_email,
				'phone'      => $order->billing_phone,
			),
			'shipping_address' => array(
				'first_name' => $order->shipping_first_name,
				'last_name'  => $order->shipping_last_name,
				'company'    => $order->shipping_company,
				'address_1'  => $order->shipping_address_1,
				'address_2'  => $order->shipping_address_2,
				'city'       => $order->shipping_city,
				'state'      => $order->shipping_state,
				'postcode'   => $order->shipping_postcode,
				'country'    => $order->shipping_country,
			),
			'note'         => $order->customer_note,
			'customer_id'  => $order->customer_user
		);
	}
	
}
?>