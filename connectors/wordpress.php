<?php
require_once(dirname(dirname(__FILE__)).'/api.php');

class WordPressConnector {

  public function __construct() {
    add_action('profile_update', array($this, 'customer_updated'));
    add_action('deleted_user', array($this, 'customer_deleted'));
  }

  public function connect($shop_id, $secret) {
    return Belco_API::post('/shops/connect', array(
      'id' => $shop_id,
      'type' => 'wordpress',
      'url' => get_site_url()
    ), array('secret' => $secret, 'blocking' => true));
  }
  
  public function get_identify_data($secret) {
    return null;
  }

  public function get_event_data($secret) {
    return array();
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

  public function get_customer($id) {
    $user = get_userdata($id);

    if (!$user) {
      return null;
    }

    $customer = array(
      'id' => $user->ID,
      'email' => $user->user_email,
      'firstName' => $user->first_name,
      'lastName' => $user->last_name
    );

    return $customer;
  }

  public function get_cart() {
    return null;
  }

}
