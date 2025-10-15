<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Branch
{
    public function __construct()
    {
        $this->HttpClient = new HttpClient();
    }

    public function save()
    {
        $data = kiotviet_sync_get_request('data', []);
        if (!empty($data['configBranchStock'])) {
            update_option('kiotviet_sync_config_branch_stock', $data['configBranchStock']);
        }

        if (!empty($data['configBranchOrder'])) {
            update_option('kiotviet_sync_config_branch_order', $data['configBranchOrder']);
        }

        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function get()
    {
        $configBranchStock = get_option('kiotviet_sync_config_branch_stock', null);
        $configBranchOrder = get_option('kiotviet_sync_config_branch_order', null);

        wp_send_json($this->HttpClient->responseSuccess([
            "config_branch_stock" => json_decode(html_entity_decode(stripslashes($configBranchStock)), true),
            "config_branch_order" => json_decode(html_entity_decode(stripslashes($configBranchOrder)), true),
        ]));
    }
}
