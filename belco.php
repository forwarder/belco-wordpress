<?php
/**
 * @package Belco
 * @version 0.8.2
 *
 */
/*
Plugin Name: Belco.io
Plugin URI: http://www.belco.io
Description: All-in-one customer service software for e-commerce
Version: 0.8.2
Author: Belco B.V.
Author URI: http://www.belco.io
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define('BELCO_HOST', 'app.belco.io');
define('BELCO_API_HOST', 'api.belco.io');
define('BELCO_USE_SSL', true);

if(!class_exists('WP_Belco')) {

  class WP_Belco {

    /**
     * Construct Belco
     */
    public function __construct() {
      $this->plugin_path = plugin_dir_path(__FILE__);

      // register filters
      add_action( 'init', array(&$this, 'init') );
      add_action( 'admin_init', array(&$this, 'admin_init') );
      add_action( 'admin_menu', array(&$this, 'add_menu') );
      add_action( 'plugins_loaded', array(&$this, 'enqueue_scripts') );
      add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts') );
    }

    /**
     * Activate Belco
     */
    public static function activate() {

    }

    /**
     * Deactive Belco
     */
    public static function deactivate() {
      flush_rewrite_rules();
      delete_option('belco_shop_id');
      delete_option('belco_secret');
    }

    public static function user_role($role, $user_id = null) {
      if ( is_numeric( $user_id ) )
        $user = get_userdata( $user_id );
      else
        $user = wp_get_current_user();

      if ( empty( $user ) )
        return false;

      return in_array( $role, (array) $user->roles );
    }

    public function init() {
      if ($this->woocommerce_active()) {
        require('connectors/woocommerce.php');
        $this->connector = new WooCommerceConnector();
      } else {
        require('connectors/wordpress.php');
        $this->connector = new WordPressConnector();
      }
    }

    /**
     * hook into WP's admin_init action hook
     */
    public function admin_init() {
      $this->init_settings();
      add_action( 'admin_notices', array(&$this, 'installation_notice') );
    }

    /**
     * Initialize some custom settings
     */

    public function init_settings() {
      register_setting('wp_belco', 'belco_shop_id');
      register_setting('wp_belco', 'belco_secret');

      add_filter('pre_update_option_belco_secret', array(&$this, 'connect'), 10 , 2);
    }

    public function enqueue_scripts() {
      if (!is_user_logged_in() || WP_Belco::user_role('customer')) {
        add_action('wp_footer', array(&$this, 'init_widget'));
      }
    }

    public function admin_enqueue_scripts() {
      wp_enqueue_style( 'belco-admin', plugins_url('css/admin.css', __FILE__));
    }

    /**
     * Check if plugin installation is completed
     */

    public function installation_complete() {
      $shop_id = get_option('belco_shop_id');
      $secret = get_option('belco_secret');

      return !empty($shop_id) && !empty($secret);
    }

    /**
     * Create a menu
     */

    public function add_menu()
    {
      add_menu_page('Belco settings', 'Belco', 'manage_options', 'belco', array(&$this, 'settings_page'), null, 58);
    }

    /**
     * Initialize the Belco client widget
     */

    public function init_widget() {
      // Don't show if Woocommerce isn't activated.
      if (!$this->connector) {
        return;
      }

      $shopId = get_option('belco_shop_id');

      if (!$shopId) {
        return;
      }

      $secret = get_option('belco_secret');
      $config = array(
        'shopId' => $shopId
      );

      if (!is_user_logged_in()) {
        $data = $this->connector->get_identify_data($secret);
        if (!empty($data)) {
          if ($secret) {
            $config['hash'] = hash_hmac("sha256", $data['email'], $secret);
          }
          $config = array_merge($config, $data);
        }
      } elseif (WP_Belco::user_role('customer')) {
        $user = wp_get_current_user();
        if ($secret) {
          $config['hash'] = hash_hmac("sha256", $user->user_email, $secret);
        }
        $config = array_merge($config, $this->connector->get_customer($user->ID));
      }

      if ($cart = $this->connector->get_cart()) {
        $config['cart'] = $cart;
      }

      $events = $this->connector->get_event_data();

      $this->connector->clear_event_data();

      include(sprintf("%s/templates/widget.php", dirname(__FILE__)));
    }

    /**
     * Settings page
     */

    public function settings_page()
    {
      if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
      }

      $woocommerce = $this->woocommerce_active();
      $installed = $this->installation_complete();

      $shop_id = get_option('belco_shop_id');
      $secret = get_option('belco_secret');

      include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
    }

    /**
     * Show installation notice when Belco hasnt been configured yet
     */
    public function installation_notice() {
      if (!$this->installation_complete()) {
        include(sprintf("%s/templates/notice.php", dirname(__FILE__)));
      }
    }

    /**
     * Check if WooCommerce is active
     */
    public function woocommerce_active() {
      return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    /**
     * Connects the shop to Belco
     */
    public function connect($secret, $oldSecret) {
      $result = $this->connector->connect(get_option('belco_shop_id'), $secret);

      if ($result !== true) {
        add_settings_error('belco_shop_id', 'shop-id', $result);
        return $oldSecret;
      }

      return $secret;
    }
  }

}

if (class_exists('WP_Belco'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_Belco', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_Belco', 'deactivate'));

    // instantiate the plugin class
    $wp_belco = new WP_Belco();
}
?>
