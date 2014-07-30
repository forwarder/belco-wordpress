<?php
/**
 * @package Belco
 * @version 0.1
 *
 */
/* 
Plugin Name: Belco - Let's Talk Shop
Plugin URI: http://www.belco.io
Description: Increase conversion through a decent interaction with your customers
Version: 0.1
Author: Forwarder B.V.
Author URI: http://www.forwarder.nl
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


if(!class_exists('WP_Belco'))
{

  class WP_Belco
  {
    
    /**
     * Construct Belco
     */
    public function __construct()
    {
      
      $this->plugin_path = plugin_dir_path(__FILE__);
      
      // Check for WooCommerce
      if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        //wp_die( __( 'WooCommerce is not installed on this systeem.' ) );
      }

      // register filters
      
      add_filter('query_vars', array(&$this, 'query_vars'));
      
      // register actions
      
      add_action('admin_init', array(&$this, 'admin_init'));
      
      add_action('admin_menu', array(&$this, 'add_menu'));
      
      // Register toolbar menu item
      
      add_action('wp_before_admin_bar_render', array(&$this, 'belco_toolbar_menu'), 1 );
      
      add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widget'));
      
      // register parse request
      
      add_action('parse_request', array(&$this, 'belco_parse_request'));
      
      // register menu styles
      
      wp_enqueue_style( 'belco_admin_menu_styles', plugins_url('menu.css', __FILE__));
      
      // Enqueue Angular

      wp_enqueue_script('angular-core', '//ajax.googleapis.com/ajax/libs/angularjs/1.2.20/angular.js', array('jquery'), null, false);

      // Enqueue Belco
      
      wp_enqueue_script('belco', plugins_url('js/belco.js', __FILE__), array('angular-core'), null, false);
    }
    
    /**
     * Activate Belco
     */
    public static function  activate()
    {
      
      // Create data/tables for Belco
      
      //add_rewrite_rule('belco/(\d*)$','index.php?belco_cid=$matches[1]', 'top');
      
      add_rewrite_rule('belco/([^/]*)$','index.php?belco_cid=$matches[1]', 'top');
      
      
      flush_rewrite_rules();
      
    }
    
    /**
     * Deactive Belco
     */
     
     public static function deactivate()
     {
       
       // Delete custom Belco data/tables
       
       flush_rewrite_rules();
       
     }
     
     /**
      * hook into WP's admin_init action hook
      */
     
     public function admin_init()
     {
        $this->init_settings();
     }
     
     /**
      * Initialize some custom settings
      */ 
     
     public function init_settings()
     {
       register_setting('wp_belco', 'setting_api');
     }
     
     
    /**
     * Register Toolbar Menu
     */
     
      public function belco_toolbar_menu() {

        global $wp_admin_bar;

        $args = array(
          'id'     => 'belco-status',
          'parent' => 'top-secondary',
          'href'   => false,
          'meta'   => array(
            'html' => '<div ng-app="belco.status"><span class="belco-logo"></span> <span class="belco-status" ng-controller="StatusController as status"><span class="belco-status-connecting" ng-if="!ready">Connecting...</span><span class="belco-status-{{user.status.id}}" ng-if="user.status.online">{{status.show()}}</span></span></div>'
          )
        );
        $wp_admin_bar->add_menu( $args );

      } 
     
     
    /**
     * Register Belco query var
     */ 
     
     public function query_vars( $query_vars )
     {
       
       $query_vars[] = 'belco';
       
       $query_vars[] = 'belco_api';
       
       $query_vars[] = 'belco_id';
       
       $query_vars[] = 'belco_cid';
       
       return $query_vars;
       
     }
     
     
    /**
     * Handle parse request
     */
     
     public function belco_parse_request( &$wp )
     {
       
       if ( array_key_exists( 'belco_cid', $wp->query_vars ) ){
            include $this->plugin_path . 'test.php';
            exit();
        }
        return;
       
     }
     
     /**
      * Create the widget
      */
      
     public function add_dashboard_widget()
     {
       wp_add_dashboard_widget(
                 'belco_dashboard_widget',
                 'Belco - Let\'s Talk Shop',
                 array(&$this, 'belco_dashboard_widget_function')
        );
        
        global $wp_meta_boxes;
 	
        $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
 	
        $example_widget_backup = array( 'belco_dashboard_widget' => $normal_dashboard['belco_dashboard_widget'] );
        
        unset( $normal_dashboard['belco_dashboard_widget'] );
 
        $sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );
 
        $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
        
     }
     
     /**
      * Display the widget
      */
     
    function belco_dashboard_widget_function()
      {
        $searchterm = isset($_GET["belco_cid"]) ? $_GET["belco_cid"] : "0614269740";
        
        global $wp;
       
        global $woocommerce;        
        
        preg_match("/(00|\+)?([0-9]{1,2})?(0)?(\d{9,10})/", $searchterm, $matches);
        
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
        
        /*while ( $loop->have_posts() ) : $loop->the_post();
        
        	$order_id = $loop->post->ID;
        	
        	$order = new WC_Order($order_id);
        	
        	echo "<div>";
        	
        	echo "<h4>Order: ".$order_id."</h4>";
        	
          echo "<div>Email: ".$order->billing_email."</div>";
          
          echo "<div>Phone: ".$order->billing_phone."</div>";
          
          preg_match("/(00|\+)?([0-9]{1,2})?(0)?(\d{9,10})/", $order->billing_phone, $matches);
          
          echo "<div><strong>Searchkey:</strong> ".end($matches)."</div>";
          
          echo "<div>Products: ".$order->get_item_count()."</div>";
          
          echo "</div>";
       
        endwhile;
        */
        
        echo '<div id="belco-widget" ng-app="belco.widget">';
/*        

*/
        

        
echo '<div ng-if="!connected">';
echo '  Connecting...';
echo '</div>';       
        
echo '<div ng-controller="LoginController as login" ng-if="!user && ready">';
echo '        <form ng-submit="login.submit()">';
echo '    <p>';
echo '      <label for="username">Username</label><input type="text" name="belco_username" ng-model="login.username">';
echo '    </p>';
echo '    <p>';
echo '      <label for="password">Password</label><input type="password" name="belco_password" ng-model="login.password">';
echo '    </p>';
echo '    <button type="submit">Log in</button>';
echo '  </form>';
echo '</div>';

echo '<div ng-if="user && ready">';
echo '  <div class="belco-utilities" ng-controller="StatusController as status" ng-show="connected">';
echo '    <p>Welkom {{status.displayName()}}</p>';
echo '    <p>Status: {{status.status.id}}</p>';
echo '  </div>';

echo '<div ng-controller="LogoutController as logout">';
echo '  <form ng-submit="logout.submit()">';
echo '    <button type="submit">Log out</button>';
echo '  </form>';
echo '</div>';

echo '  <div ng-controller="OrdersController">';
echo '  <table>';
echo '    <thead>';
echo '      <tr><th colspan="3">Orders</th></tr>';
echo '      <tr><th colspan="3"><input type="text" ng-model="phoneFilter" placeholder="Search..."/></th></tr>';
echo '    </thead>';
echo '    <tbody>';
echo '      <tr ng-repeat="order in orderList | filter: searchFilter">';
echo '        <td>{{order.id}}</td>';
echo '        <td>';
echo '          {{order.status}}';
echo '        </td>';
echo '        <td>{{order.phone}}</td>';
echo '      </tr>';
echo '    </tbody>';
echo '  </table>';
echo '  </div>';

echo '</div>';
      
echo "</div>";       
     }
      
     
     /**
      * Create a menu
      */ 
     
     public function add_menu()
     {
       add_menu_page('Belco Options','Belco','manage_options','belco',array(&$this, 'belco_dashboard_page'),null,'2.5');
       
       add_submenu_page( 'belco', 'Dashboard', 'Dashboard', 'manage_options', 'belco',array(&$this, 'belco_dashboard_page'));
       
       add_submenu_page( 'belco', 'Settings', 'Options', 'manage_options', 'belco-options',array(&$this, 'belco_options_page'));
       
     }
     
     public function belco_dashboard_page()
     {
       if ( !current_user_can( 'manage_options' ) )  {
         wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
       }
       
       // Render the settings template
       include(sprintf("%s/templates/dashboard.php", dirname(__FILE__)));
       
     }
     
     public function belco_options_page()
     {
       if ( !current_user_can( 'manage_options' ) )  {
         wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
       }
       
       // Render the settings template
       include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
       
     }
     
    
  }

}

if(class_exists('WP_Belco'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_Belco', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_Belco', 'deactivate'));

    // instantiate the plugin class
    $wp_belco = new WP_Belco();
}

?>