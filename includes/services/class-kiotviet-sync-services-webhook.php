<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Webhook
{
    public function __construct()
    {
        $this->HttpClient = new HttpClient();
    }

    public function registerWebhook()
    {
        // NOTE: registerWebhook
        KiotvietSyncHelper::registerWebhook();
        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function removeWebhook()
    {
        // NOTE: removeWebhook
        KiotvietSyncHelper::removeWebhook();
        wp_send_json($this->HttpClient->responseSuccess(true));
    }
}
