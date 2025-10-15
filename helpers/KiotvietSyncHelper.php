<?php
class KiotvietSyncHelper
{
    public static function registerWebhook()
    {
        $randomStr = get_option('webhook_key');
        if (empty($randomStr)) {
            //  Create webhook key
            $randomStr = substr(md5(time()), 2, 10);
            add_option('webhook_key', $randomStr, '', 'yes');
        }

        $prefixEndPoint = 'kiotviet-sync/v1/' . $randomStr;

        //  Build url to register webhook kiotviet.
        $kiotvietApi = new Kiotviet_Sync_Service_Auth();
        $webhookUrl = get_rest_url(null, $prefixEndPoint . '/webhook/') . '?noecho';

        $types = ['product.update', 'stock.update', 'order.update', 'product.delete'];

        $webhooks = $kiotvietApi->request('get', 'https://public.kiotapi.com/webhooks', []);
        $webhooks = $webhooks['data']['data'];

        //  Delete all old webhooks
        foreach ($webhooks as $webhook) {
            $type = $webhook['type'];
            if (in_array($type, $types)) {
                $kiotvietApi->request('delete', 'https://public.kiotapi.com/webhooks/' . $webhook['id'], []);
            }
        }

        foreach ($types as $type) {
            if($type === "product.update") {
                $description = 'Webhook for update product';
            } elseif ($type === "stock.update") {
                $description = "Webhook for update stock";
            } elseif ($type === "order.update") {
                $description = "Webhook for update order";
            } elseif ($type === "product.delete") {
                $description = "Webhook for delete update";
            } else {
                $description = "Webhook for update";
            }
            $payload = [
                'Webhook' => [
                    'Type' => $type,
                    'Url' => $webhookUrl,
                    'IsActive' => true,
                    'Description' => $description
                ]
            ];
            $kiotvietApi->request('post', 'https://public.kiotapi.com/webhooks', $payload, 'json');
        }
    }

    public static function removeWebhook()
    {
        $kiotvietApi = new Kiotviet_Sync_Service_Auth();
        $webhooks = $kiotvietApi->request('get', 'https://public.kiotapi.com/webhooks', []);
        $webhooks = $webhooks['data']['data'];

        $removeTypes = ['product.delete', 'product.update', 'stock.update', 'order.update'];

        foreach ($webhooks as $webhook) {
            $type = $webhook['type'];
            if (in_array($type, $removeTypes)) {
                $kiotvietApi->request('delete', 'https://public.kiotapi.com/webhooks/' . $webhook['id'], []);
            }
        }
        delete_option('webhook_key');
    }
}
