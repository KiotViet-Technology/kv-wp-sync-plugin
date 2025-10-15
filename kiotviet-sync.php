<?php
/**
 *
 *
 * @wordpress-plugin
 * Plugin Name:       KiotViet Sync
 * Plugin URI:        https://kiotviet.vn
 * Description:       Plugin hỗ trợ đồng bộ sản phẩm, đơn hàng giữa website Wordpress với KiotViet.
 * Version:           1.8.4
 * Author:            KiotViet
 * Author URI:        https://kiotviet.vn
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kiotviet-sync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// define
define('KIOTVIET_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('KIOTVIET_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('KIOTVIET_PLUGIN_VERSION', '1.8.4');

include_once "bootstrap.php";

// active
function activate_kiotviet_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kiotviet-sync-activator.php';
	Kiotviet_Sync_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_kiotviet_sync' );

// deactive
function deactivate_kiotviet_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kiotviet-sync-deactivator.php';
	Kiotviet_Sync_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_kiotviet_sync' );

// main
require plugin_dir_path( __FILE__ ) . 'includes/class-kiotviet-sync.php';

// begin
$plugin = new Kiotviet_Sync();
