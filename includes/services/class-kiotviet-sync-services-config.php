<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Config
{
    public function __construct()
    {
        $this->HttpClient = new HttpClient();
    }

    public function getConfig()
    {
        $clientId = get_option('kiotviet_sync_client_id', "");
        $clientSecret = get_option('kiotviet_sync_client_secret', "");
        $retailer = get_option('kiotviet_sync_retailer', "");
        $autoSyncOrder = get_option('kiotviet_sync_auto_sync_order', "");
        $productSync = get_option('kiotviet_sync_product_sync', []);
        return wp_send_json($this->HttpClient->responseSuccess(array(
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'retailer' => $retailer,
            'auto_sync_order' => $autoSyncOrder === "true" ? true : false,
            'product_sync' => $productSync
        )));
    }

    public function saveConfig()
    {
        $request = kiotviet_sync_get_request('data', []);
        $productSync = !empty($request['product_sync']) ? $request['product_sync'] : [];
        if (!empty($request['auto_sync_order'])) {
            update_option('kiotviet_sync_auto_sync_order', $request['auto_sync_order']);
        }
        if (!empty($productSync)) {
            update_option('kiotviet_sync_product_sync', $productSync);
        }
        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function removeConfig()
    {
        delete_option("kiotviet_sync_client_id");
        delete_option("kiotviet_sync_client_secret");
        delete_option("kiotviet_sync_retailer");
        delete_option("kiotviet_sync_auto_sync_order");
        return wp_send_json($this->HttpClient->responseSuccess(true));
    }
}
