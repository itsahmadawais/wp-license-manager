<?php
/**
 * Plugin Name:       License Manager
 * Description:       This plugin helps you manage licenses and provide integration with ClickFunnels. It also offers REST API endpoints.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Cawoy Services | Awais Ahmad
 * Author URI:        https://cawoy.com/
 * Text Domain:       license-manager
 */

if(!defined("ABSPATH"))
{
	die("You cannot access this directory!");
}
/*
 *
 * @ Plugin Directory Path
 * @ Plugin URL
 *
*/
if(!defined('CLM_DIR'))
{
	define('CLM_DIR',plugin_dir_path(__FILE__));
}
if(!defined('CLM_URL'))
{
	define('CLM_URL',plugin_dir_url(__FILE__));
}

if(file_exists(dirname(__FILE__)."/vendor/autoload.php"))
{
	require_once(dirname(__FILE__)."/vendor/autoload.php");
}

if(file_exists(dirname(__FILE__)."/plugins/meta-box/meta-box.php"))
{
	require_once(dirname(__FILE__)."/plugins/meta-box/meta-box.php");
}

use Inc\Setup;
use Inc\Packages;
use Inc\LicenseManager;
use Inc\RestAPI;

/**
 * Activation Hook
 */
function activate_my_plugin() {
	Setup::activate();
 }
 /**
  * Deactivation Hook
  */
 function deactivate_my_plugin() {

 }
register_activation_hook( __FILE__, 'activate_my_plugin' );
register_deactivation_hook( __FILE__, 'deactivate_my_plugin' );

$package = new Packages();
$package->init();

$license = new LicenseManager(); $license->init();
$restAPI = new RestAPI();
$restAPI->init();

function my_cpt_column( $colname, $cptid ) {
     if ( $colname == 'metabox')
          echo get_post_meta( $cptid, '_my_meta_value_key', true );
}
add_action('manage_portfolio_posts_custom_column', 'my_cpt_column', 10, 2);
//the actual column data is output
