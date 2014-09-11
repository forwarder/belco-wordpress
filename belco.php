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

define('BELCO_HOST', '127.0.0.1:3000');

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
        wp_die( __( 'WooCommerce is not installed on this systeem.' ) );
      }

      // register filters
      
      add_filter( 'query_vars', array(&$this, 'query_vars') );
			add_action( 'init', array(&$this, 'init') );
      add_action( 'admin_init', array(&$this, 'admin_init') );
      add_action( 'admin_menu', array(&$this, 'add_menu') );
			add_action( 'plugins_loaded', array(&$this, 'enqueue_scripts') );
      add_action( 'wp_before_admin_bar_render', array(&$this, 'belco_toolbar_menu'), 1 );
      add_action( 'parse_request', array(&$this, 'belco_parse_request') );
    }

    /**
     * Activate Belco
     */
    public static function activate()
    {

    }
    
    /**
     * Deactive Belco
     */
     
		public static function deactivate()
		{
			flush_rewrite_rules();
			delete_option('belco_api_key');
			delete_option('belco_api_secret');
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
		
		public function init()
		{
			add_rewrite_rule('^belco/([^/]*)/([^/]*)$','index.php?belco-search=$matches[1]&query=$matches[2]', 'top');
      flush_rewrite_rules();
		}
     
     /**
      * hook into WP's admin_init action hook
      */
     
     public function admin_init()
     {
        $this->init_settings();

				add_action( 'admin_notices', array(&$this, 'installation_notice') );
     }
     
     /**
      * Initialize some custom settings
      */ 
     
     public function init_settings()
     {
       register_setting('wp_belco', 'belco_api_key');
			 register_setting('wp_belco', 'belco_api_secret');
     }
		 
		 
		public function enqueue_scripts() {
			if (!is_user_logged_in() || WP_Belco::user_role('customer')) {
				wp_enqueue_style( 'belco-click2call', plugins_url('css/click2call.css', __FILE__));
				wp_enqueue_script('belco-click2call', plugins_url('js/click2call.js', __FILE__), array('jquery'), null, false);
				add_action('wp_footer', array(&$this, 'init_config'));
			} else if(is_admin() && current_user_can('manage_options')){
				wp_enqueue_style( 'belco-admin', plugins_url('css/admin.css', __FILE__));
				wp_enqueue_script('belco-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), null, false);
				add_action('admin_footer', array(&$this, 'init_config'));
			}
			
		}
     
    /**
     * Register Toolbar Menu
     */
     
    public function belco_toolbar_menu() {
      global $wp_admin_bar;
			
			if (WP_Belco::user_role('customer')) {
				return;
			}

      $args = array(
        'id'     => 'belco-status',
        'parent' => 'top-secondary',
        'href'   => false,
        'meta'   => array(
          'html' => '<div id="belco-status" title="Open Belco client"><span class="belco-logo"></span><span class="belco-status belco-status-connecting">Connecting...</span></div>'
        )
      );
      $wp_admin_bar->add_menu( $args );
    } 
     
     
    /**
     * Register Belco query var
     */ 
     
     public function query_vars( $query_vars )
     {
       $query_vars[] = 'belco-search';
			 $query_vars[] = 'query';
       
       return $query_vars;
     }
     
     
    /**
     * Handle parse request
     */
     
     public function belco_parse_request( &$wp )
     {
       if ( isset($wp->query_vars['belco-search']) ){
          include $this->plugin_path . 'api.php';
          return new Belco_API($wp->query_vars);
        }
        return;
     }   
		 
     /**
      * Check if plugin installation is completed
      */
		 
		 public function installation_complete() {
			 $api_key = get_option('belco_api_key');
			 $api_secret = get_option('belco_api_key');
			 
       return !empty($api_key) && !empty($api_secret);
		 } 
     
		/**
		* Create a menu
		*/ 

		public function add_menu()
		{
			add_menu_page('Belco dashboard', 'Belco', 'manage_options', 'belco', array(&$this, 'dashboard_page'), null, 30);
			add_submenu_page( 'belco', 'Belco dashboard', 'Dashboard', 'manage_options', 'belco', array(&$this, 'dashboard_page'));
			add_submenu_page( 'belco', 'Belco settings', 'Settings', 'manage_options', 'belco-settings', array(&$this, 'settings_page'));
		}

		/**
		* Initialize the Belco client config
		*/

		public function init_config()
		{
			$config = array(
				'host' => BELCO_HOST,
				'apiKey' => get_option('belco_api_key')
			);

			if (is_user_logged_in() && WP_Belco::user_role('customer')) {
				$user = wp_get_current_user();
				$config['customer'] = array(
					'id' => $user->ID,
					'name' => sprintf('%s %s', $user->user_firstname, $user->user_lastname)
				);
			}

			include(sprintf("%s/templates/config.php", dirname(__FILE__)));
		}
		
    /**
     * Dashboard page
     */

    public function dashboard_page()
    {
      if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
      }
			
			$installed = $this->installation_complete();
			
      $api_key = get_option('belco_api_key');
      $api_secret = get_option('belco_api_secret');
			
			$page = '';
			if (!$installed) {
				$page = '/install?type=woocommerce';
			}
			
      include(sprintf("%s/templates/dashboard.php", dirname(__FILE__)));
    }
		 
		/**
		* Settings page
		*/

		public function settings_page()
		{
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			
			$installed = $this->installation_complete();

			$api_key = get_option('belco_api_key');
			$api_secret = get_option('belco_api_secret');

			include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
		}

		/**
			* Show installation notice when Belco hasnt been configured yet
			*/
		function installation_notice() {
			if (!$this->installation_complete()) {
				include(sprintf("%s/templates/notice.php", dirname(__FILE__)));
			}
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