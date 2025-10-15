<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       opss.com.vn
 * @since      1.0.0
 *
 * @package    Kiotviet_Sync
 * @subpackage Kiotviet_Sync/includes
 */

class Kiotviet_Sync
{
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct()
    {
        if (defined('KIOTVIET_PLUGIN_VERSION')) {
            $this->version = KIOTVIET_PLUGIN_VERSION;
        } else {
            $this->version = '1.8.4';
        }

        $this->plugin_name = 'kiotviet-sync';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();

        // refactor code
        $this->queryControllerAdmin();

        $this->define_public_hooks();



        add_filter('parent_file', 'active_order_menu');
        function active_order_menu($file)
        {
            global $plugin_page, $submenu_file;
            if (array_key_exists('plugin', $_GET) && $_GET['plugin'] == 'kiotviet-sync-order') {
                $plugin_page = 'plugin-kiotviet-sync-order';
                $submenu_file = $plugin_page;
            } elseif (array_key_exists('plugin', $_GET) && $_GET['plugin'] == 'kiotviet-sync-product') {
                $plugin_page = 'plugin-kiotviet-sync-order';
                $submenu_file = $plugin_page;
            }
            return $file;
        }
    }

    private function load_dependencies()
    {
        // Admin
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-kiotviet-sync-admin.php';

        // Include
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-auth.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-categories.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-products.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-pricebooks.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-branchs.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-config.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-webhook.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-log.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-kiotviet-sync-services-order.php';

        //  Load public hook actions
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/public_actions/WebHookAction.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/public_actions/OrderHookAction.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-tgm-plugin-activation.php';

        //  Load helper
        require_once plugin_dir_path(dirname(__FILE__)) . 'helpers/KiotvietSyncHelper.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'helpers/KiotvietWcProduct.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'helpers/KiotvietWcCategory.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'helpers/KiotvietWcAttribute.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'helpers/functions.php';

        // refactor code
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/public_actions/admin/controllers/QueryControllerAdmin.php';
        
    }

    private function set_locale()
    {
        add_action('plugins_loaded', function () {
            load_plugin_textdomain('kiotviet-sync', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/');
        });
    }

    private function hook_auth()
    {
        $auth_service = new Kiotviet_Sync_Service_Auth;
        add_action('wp_ajax_kiotviet_sync_service_auth', array($auth_service, 'doRequest'));
        add_action('wp_ajax_kiotviet_sync_get_token', array($auth_service, 'getAccessToken'));
        add_action('wp_ajax_kiotviet_sync_save_config_retailer', array($auth_service, 'saveConfigRetailer'));
    }

    private function hook_config()
    {
        $config_service = new Kiotviet_Sync_Service_Config;
        add_action('wp_ajax_kiotviet_sync_get_config', array($config_service, 'getConfig'));
        add_action('wp_ajax_kiotviet_sync_save_config', array($config_service, 'saveConfig'));
        add_action('wp_ajax_kiotviet_sync_remove_config', array($config_service, 'removeConfig'));

        
    }

    private function hook_web_hook()
    {
        $webhook_service = new Kiotviet_Sync_Service_Webhook;
        add_action('wp_ajax_kiotviet_sync_remove_webhook', array($webhook_service, 'removeWebhook'));
        add_action('wp_ajax_kiotviet_sync_register_webhook', array($webhook_service, 'registerWebhook'));
    }

    private function hook_log()
    {
        $log_service = new Kiotviet_Sync_Service_Log;
        add_action('wp_ajax_kiotviet_sync_remove_log', array($log_service, 'removeLog'));
    }

    private function hook_order()
    {
        $order_service = new Kiotviet_Sync_Service_Order;
        add_action('wp_ajax_kiotviet_re_sync_order', array($order_service, 'reSyncOrder'));

        $kv_autosyncorder = get_option('kv_autosyncorder');
        if($kv_autosyncorder == '1') {
            add_filter( 'cron_schedules', function ( $schedules ) {
                if(!empty(get_option('kv_timeautosyncorder'))) {
                    $timeSync = (int)get_option('kv_timeautosyncorder');
                } else {
                    $timeSync = 3600;
                }
                $schedules['every_five_minutes'] = array(
                    'interval'  => $timeSync,
                    'display'   => __( 'Every 5 Minutes', 'textdomain' )
                );
                return $schedules;
            } );

            // Schedule an action if it's not already scheduled
            if ( ! wp_next_scheduled( 'isa_add_every_five_minutes' ) ) {
                wp_schedule_event( time(), 'every_five_minutes', 'isa_add_every_five_minutes' );
            }

            // Hook into that action that'll fire every five minutes
            add_action( 'isa_add_every_five_minutes', array($order_service, 'autoSyncOrder'));
        }
    }

    private function hook_product()
    {
        $product_service = new Kiotviet_Sync_Service_Product;
        add_action('wp_ajax_kiotviet_sync_add_product', array($product_service, 'add'));
        add_action('wp_ajax_kiotviet_sync_update_product', array($product_service, 'update'));
        add_action('wp_ajax_kiotviet_sync_get_product_map', array($product_service, 'getProductMap'));
        add_action('wp_ajax_kiotviet_sync_get_product_synced', array($product_service, 'getProductSynced'));
        add_action('wp_ajax_kiotviet_sync_delete_product', array($product_service, 'delete'));
        add_action('wp_ajax_kiotviet_sync_update_status', array($product_service, 'updateStatus'));
        add_action('wp_ajax_kiotviet_sync_update_product_price', array($product_service, 'updatePrice'));
        add_action('wp_ajax_kiotviet_sync_update_product_stock', array($product_service, 'updateStock'));
        add_action('wp_ajax_kiotviet_sync_delete_product_map', array($product_service, 'deleteProductMap'));
    }

    private function hook_category()
    {
        $category_service = new Kiotviet_Sync_Service_Category;
        add_action('wp_ajax_kiotviet_sync_add_category', array($category_service, 'add'));
        add_action('wp_ajax_kiotviet_sync_delete_sync_category', array($category_service, 'deleteSync'));
        add_action('wp_ajax_kiotviet_sync_delete_category', array($category_service, 'delete'));
        add_action('wp_ajax_kiotviet_sync_update_category', array($category_service, 'update'));
    }

    private function hook_price_book()
    {
        $pricebook_service = new Kiotviet_Sync_Service_PriceBook;
        add_action('wp_ajax_kiotviet_sync_get_pricebook', array($pricebook_service, 'get'));
        add_action('wp_ajax_kiotviet_sync_save_pricebook', array($pricebook_service, 'save'));
    }

    private function hook_branch()
    {
        $branch_service = new Kiotviet_Sync_Service_Branch;
        add_action('wp_ajax_kiotviet_sync_get_branch', array($branch_service, 'get'));
        add_action('wp_ajax_kiotviet_sync_save_branch', array($branch_service, 'save'));
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new Kiotviet_Sync_Admin($this->plugin_name, $this->version);
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));

        // Add menu item
        add_action('admin_menu', array($plugin_admin, 'add_plugin_admin_menu'));

        $this->hook_auth();
        $this->hook_config();
        $this->hook_web_hook();
        $this->hook_log();
        $this->hook_order();
        $this->hook_product();
        $this->hook_category();
        $this->hook_price_book();
        $this->hook_branch();

        // Add hook delete product
        add_action('before_delete_post', array($this, 'delete_product'));
        add_action('woocommerce_before_delete_product_variation', array($this, 'delete_product_variation'));

        // Add hook delete category
        add_action('delete_term_taxonomy', array($this, 'delete_category'));

        // Add Settings link to the plugin
        $plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $this->plugin_name . '.php');
        add_filter('plugin_action_links_' . $plugin_basename, array($plugin_admin, 'add_action_links'));

        // Require plugin
        add_action('tgmpa_register', array($this, 'kiotviet_register_required_plugins'));
        add_action('admin_notices', array($this, 'checking_php_requirement_admin_notice__warning'));
    }

    public function checking_php_requirement_admin_notice__warning()
    {
        // check allow_url_fopen
        if (!ini_get('allow_url_fopen')) {
?>
            <div class="notice notice-warning is-dismissible">
                <p>Cấu hình <i><strong>allow_url_fopen</strong></i> đang ở trạng thái tắt, điều này có thể ảnh hưởng đến quá trình đồng bộ hình ảnh từ KiotViet, vui lòng bật cấu hình <i><strong>allow_url_fopen</strong></i> cho website của bạn để có thể đồng bộ hình ảnh từ KiotViet
                </p>
            </div>
        <?php
        }

        if (function_exists('curl_init') === false) {
        ?>
            <div class="notice notice-warning is-dismissible">
                <p>Cấu hình <i><strong>curl</strong></i> đang ở trạng thái tắt, điều này có thể ảnh hưởng đến quá trình đồng bộ hình ảnh từ KiotViet, vui lòng bật cấu hình <i><strong>curl</strong></i> cho website của bạn để có thể đồng bộ hình ảnh từ KiotViet
                </p>
            </div>
<?php
        }
    }

    public function delete_product($post_id)
    {
        global $wpdb;
        $productDeletes = [];
        $retailer = get_option('kiotviet_sync_retailer', "");

        if (WC_Product_Factory::get_product_type($post_id) == "simple") {
            $productDeletes[] = $post_id;
        }

        if (WC_Product_Factory::get_product_type($post_id) == 'variable') {
            $productDeletes[] = $post_id;
            $args = array(
                'post_parent' => $post_id,
                'post_type' => 'product_variation',
                'post_status' => 'any, trash, auto-draft',
                'orderby' => array('menu_order' => 'ASC', 'ID' => 'ASC'),
                'numberposts' => -1,
            );
            $productChildren = get_posts($args);
            $productchildrenIds = [];
            foreach ($productChildren as $product) {
                $productDeletes[] = $product->ID;
            }
        }

        foreach ($productDeletes as $productDelete) {
            $delete = [
                "product_id" => $productDelete,
                "retailer" => $retailer
            ];
            $wpdb->delete($wpdb->prefix . "kiotviet_sync_products", $delete);
        }
    }

    function delete_product_variation($variation_id)
    {
        global $wpdb;
        $productDeletes = [];
        $retailer = get_option('kiotviet_sync_retailer', "");
        $delete = [
            "product_id" => $variation_id,
            "retailer" => $retailer
        ];
        $wpdb->delete($wpdb->prefix . "kiotviet_sync_products", $delete);
    }

    public function delete_category($termId)
    {
        global $wpdb;
        $retailer = get_option('kiotviet_sync_retailer', "");

        $delete = [
            "category_id" => $termId,
            "retailer" => $retailer
        ];
        $wpdb->delete($wpdb->prefix . "kiotviet_sync_categories", $delete);
    }

    public function kiotviet_register_required_plugins()
    {
        $plugins = array(
            array(
                'name' => 'WooCommerce',
                'slug' => 'woocommerce',
                'required' => true,
            ),
        );

        $config = array(
            'id' => 'kiotviet',
            'default_path' => '',
            'menu' => 'tgmpa-install-plugins',
            'parent_slug' => 'plugins.php',
            'capability' => 'manage_options',
            'has_notices' => true,
            'dismissable' => false,
            'dismiss_msg' => '',
            'is_automatic' => false,
            'message' => '',
            'strings' => array(
                'page_title' => __('Các plugin yêu cầu', 'kiotviet'),
                'menu_title' => __('Cài đặt plugin', 'kiotviet'),
                'notice_can_activate_required' => _n_noop(
                    'Bạn cần kích hoạt các plugin sau để sử dụng chức năng đồng bộ sản phẩm của KiotViet: %1$s.',
                    'Bạn cần kích hoạt các plugin sau để sử dụng chức năng đồng bộ sản phẩm của KiotViet: %1$s.',
                    'kiotviet'
                ),
                'notice_can_install_required' => _n_noop(
                    'Bạn cần cài đặt các plugin sau để có thể sử dụng chức năng đồng bộ của KiotViet: %1$s.',
                    'Bạn cần cài đặt các plugin sau để có thể sử dụng chức năng đồng bộ của KiotViet: %1$s.',
                    'kiotviet'
                ),
            )
        );

        tgmpa($plugins, $config);
    }

    private function define_public_hooks()
    {
        // Register route for webhook
        $publicApi = new WebHookAction();
        add_action('rest_api_init', array($publicApi, 'register_api_route'));

        $orderHookAction = new OrderHookAction();
        add_action('woocommerce_checkout_order_processed', array($orderHookAction, 'order_processed'));
        add_action('woocommerce_thankyou', array($orderHookAction, 'update_stock_order'));
    }


    private function queryControllerAdmin()
    {
        // Register route for query
        $publicApi = new QueryControllerAdmin;
        add_action('rest_api_init', array($publicApi, 'register'));

    }
}
