<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Log
{
    public function __construct()
    {
        $this->HttpClient = new HttpClient();
    }

    public function removeLog()
    {
        global $wpdb;
        $sql = "TRUNCATE TABLE `{$wpdb->prefix}kiotviet_sync_logs`;";
        $wpdb->query($sql);
        return wp_send_json($this->HttpClient->responseSuccess(true));
    }
}
