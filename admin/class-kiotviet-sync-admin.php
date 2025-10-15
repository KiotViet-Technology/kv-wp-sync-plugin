<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       opss.com.vn
 * @since      1.0.0
 *
 * @package    Kiotviet_Sync
 * @subpackage Kiotviet_Sync/admin
 */

class Kiotviet_Sync_Admin
{
    private $plugin_name;
    private $version;
    private $currentUrl;
    private $allowPage;
    private $appEnv;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->currentUrl = get_home_url() . "/wp-admin/admin.php?page=plugin-kiotviet-sync";

        $this->allowPage = ['toplevel_page_plugin-kiotviet-sync', 'kiotviet-sync_page_plugin-kiotviet-sync-product', 'kiotviet-sync_page_plugin-kiotviet-sync-order', 'kiotviet-sync_page_plugin-kiotviet-sync-history', 'kiotviet-sync_page_plugin-kiotviet-sync-webhook', 'kiotviet-sync_page_plugin-kiotviet-sync-options'];
        $this->appEnv = 'production';
    }

    public function enqueue_styles($hook)
    {
        if (in_array($hook, $this->allowPage)) {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/kiotviet-sync-admin.css', array(), $this->version, 'all');
            if ($this->appEnv == 'production') {
                wp_register_style('kiotvietsync_css', KIOTVIET_PLUGIN_URL . '/frontend/dist/static/css/kiotsync.min.css', array(), time(), 'all');
                wp_enqueue_style('kiotvietsync_css');
            }
        }
    }

    public function enqueue_scripts($hook)
    {
        if (in_array($hook, $this->allowPage)) {
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/kiotviet-sync-admin.js', array('jquery'), $this->version, false);
            if ($this->appEnv == 'dev') {
                wp_register_script('kiotvietsync_js', 'http://localhost:8082/app.js', array('jquery'), $this->version, true);
                wp_enqueue_script('kiotvietsync_js');
            } else if ($this->appEnv == 'production') {
                wp_register_script('kiotvietsync_js', KIOTVIET_PLUGIN_URL . 'frontend/dist/static/js/kiotsync.min.js', array('jquery'), time(), true);
                wp_enqueue_script('kiotvietsync_js');
            }

            wp_localize_script(
                'kiotvietsync_js',
                'wp_obj',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'urlProduct' => menu_page_url('plugin-kiotviet-sync-product', 0),
                    'fullPath' => $this->currentUrl,
                )
            );
        }
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page('KiotViet Sync', 'KiotViet Sync', 'manage_options', 'plugin-kiotviet-sync', array($this, 'display_plugin_setup_page'), '', '10');

        add_submenu_page('plugin-kiotviet-sync', 'Thiết lập thông tin đồng bộ', 'Thiết lập thông tin đồng bộ', 'manage_options', 'plugin-kiotviet-sync', array($this, 'action_kiotvietsync_config'));
        add_submenu_page('plugin-kiotviet-sync', 'Danh sách sản phẩm đồng bộ', 'Danh sách sản phẩm đồng bộ', 'manage_options', 'plugin-kiotviet-sync-product', array($this, 'action_kiotvietsync_product'));
        add_submenu_page('plugin-kiotviet-sync', 'Danh sách đơn đặt hàng', 'Danh sách đơn đặt hàng', 'manage_options', 'plugin-kiotviet-sync-order', array($this, 'action_kiotvietsync_order'));
        add_submenu_page('plugin-kiotviet-sync', 'Lịch sử đồng bộ', 'Lịch sử đồng bộ', 'manage_options', 'plugin-kiotviet-sync-history', array($this, 'action_kiotvietsync_history'));
        add_submenu_page('plugin-kiotviet-sync', 'Danh sách webhook', 'Danh sách webhook', 'manage_options', 'plugin-kiotviet-sync-webhook', array($this, 'action_kiotvietsync_webhook'));
        add_submenu_page('plugin-kiotviet-sync', 'Cài đặt', 'Cài đặt', 'manage_options', 'plugin-kiotviet-sync-options', array($this, 'action_kiotvietsync_options'));
    }

    public function add_action_links($links)
    {
        $settings_link = array(
            '<a href="' . admin_url('options-general.php?page=' . $this->plugin_name) . '">' . __('Settings', $this->plugin_name) . '</a>',
        );
        return array_merge($settings_link, $links);
    }

    public function display_plugin_setup_page()
    {
        include_once 'views/kiotviet-sync-admin-display.php';
    }

    public function action_kiotvietsync_config()
    {
        echo "<script>window.location.replace('" . $this->currentUrl . "#/branch');</script>";
    }

    public function action_kiotvietsync_product()
    {
        include_once KIOTVIET_PLUGIN_PATH . '/admin/list_table/product.php';
        $productList = new Kv_Products_List();
        include_once KIOTVIET_PLUGIN_PATH . '/admin/views/product.php';
        $webhookKey = get_option('webhook_key');
        if($webhookKey == '2ae9a392fd'){
            delete_option('shop_debug_log');
            delete_option('shop_debug');
            delete_option('debug_create_media');
        }

    }

    public function action_kiotvietsync_order()
    {
        include_once KIOTVIET_PLUGIN_PATH . '/admin/list_table/order.php';
        $orderList = new Kv_Orders_List();
        include_once KIOTVIET_PLUGIN_PATH . '/admin/views/order.php';
    }

    public function action_kiotvietsync_history()
    {
        include_once KIOTVIET_PLUGIN_PATH . '/admin/list_table/log.php';
        $logsList = new Kv_Logs_List();
        include_once KIOTVIET_PLUGIN_PATH . '/admin/views/log.php';
    }

    public function action_kiotvietsync_webhook()
    {
        include_once KIOTVIET_PLUGIN_PATH . '/admin/list_table/webhook.php';
        $webhooksList = new Kv_Webhooks_List();
        include_once KIOTVIET_PLUGIN_PATH . '/admin/views/webhook.php';
    }

    public function action_kiotvietsync_options()
    {
        include_once KIOTVIET_PLUGIN_PATH . '/admin/views/options.php';
    }
}
