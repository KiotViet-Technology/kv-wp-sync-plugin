<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Order
{
    public function __construct()
    {
        $this->HttpClient = new HttpClient();
    }

    public function reSyncOrder()
    {
        $orderId = sanitize_key(kiotviet_sync_get_request('order'));
        if (!class_exists('OrderHookAction')) {
            require_once KIOTVIET_PLUGIN_PATH . 'includes/public_actions/OrderHookAction.php';
        }

        $orderHookAction = new OrderHookAction();
        $clientId = get_option('kiotviet_sync_client_id', "");
        $clientSecret = get_option('kiotviet_sync_client_secret', "");
        $retailer = get_option('kiotviet_sync_retailer', "");

        if ($clientId && $clientSecret && $retailer) {
            $response = $orderHookAction->order_processed($orderId, true);
        } else {
            $response = [
                "msg" => "Website không có kết nối với gian hàng KiotViet",
                "status" => "error",
            ];
        }

        wp_send_json($response);
    }

    public function autoSyncOrder()
    {
        if (!class_exists('OrderHookAction')) {
            require_once KIOTVIET_PLUGIN_PATH . 'includes/public_actions/OrderHookAction.php';
        }

        $orderHookAction = new OrderHookAction();
        $clientId = get_option('kiotviet_sync_client_id');
        $clientSecret = get_option('kiotviet_sync_client_secret');
        $retailer = get_option('kiotviet_sync_retailer');

        $kv_autosyncorder = get_option('kv_autosyncorder');
        if ($clientId && $clientSecret && $retailer && $kv_autosyncorder == '1') {
            global $wpdb;
            $orderCheck = [];

            if(!empty(get_option('kv_timeautosyncorder'))) {
                $limitSync = (int)get_option('kv_limitautosyncorder');
            } else {
                $limitSync = 1000;
            }

            // get all order id
            /*$orders_statuses = "'wc-completed', 'wc-processing', 'wc-on-hold'";
            $orderCheck = $wpdb->get_col( "
                SELECT DISTINCT woi.order_id
                FROM {$wpdb->prefix}woocommerce_order_itemmeta as woim, 
                     {$wpdb->prefix}woocommerce_order_items as woi, 
                     {$wpdb->prefix}posts as p
                WHERE woi.order_item_id = woim.order_item_id
                AND woi.order_id = p.ID
                AND p.post_status IN ( $orders_statuses )
                AND woim.meta_key IN ( '_product_id', '_variation_id' )
                ORDER BY woi.order_item_id DESC LIMIT $limitSync"
            );*/
            $query = new WC_Order_Query( array(
                'status' => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
                'return' => 'ids',
            ) );
            $orderCheck = $query->get_orders();
            if(!empty($orderCheck)) {
                $orderSync = [];
                $tableSync = $wpdb->get_results("SELECT order_id FROM `{$wpdb->prefix}kiotviet_sync_orders` WHERE order_id IN (" . implode(',', $orderCheck) . ")");
                foreach ($tableSync as $kvOrder) {
                    $orderSync[] = $kvOrder->order_id;
                }

                $orderUnSync = [];
                foreach ($orderCheck as $item) {
                    if(!in_array($item, $orderSync)) {
                        $orderUnSync[] = $item;
                    }
                }

                foreach ($orderUnSync as $item) {
                    $orderHookAction->order_processed($item, true);
                }
            }
        }
    }
}
