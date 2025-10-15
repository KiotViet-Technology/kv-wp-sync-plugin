<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_PriceBook
{
    public function __construct()
    {
        $this->HttpClient = new HttpClient();
    }

    public function save()
    {
        $data = kiotviet_sync_get_request('data', []);
        if (!empty($data['regularPrice'])) {
            update_option('kiotviet_sync_regular_price', $data['regularPrice']);
        }

        if (!empty($data['salePrice'])) {
            update_option('kiotviet_sync_sale_price', $data['salePrice']);
        }

        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function get()
    {
        $regularPrice = get_option('kiotviet_sync_regular_price');
        $salePrice = get_option('kiotviet_sync_sale_price');

        wp_send_json($this->HttpClient->responseSuccess([
            "regular_price" => json_decode(html_entity_decode(stripslashes($regularPrice)), true),
            "sale_price" => json_decode(html_entity_decode(stripslashes($salePrice)), true),
        ]));
    }
}
