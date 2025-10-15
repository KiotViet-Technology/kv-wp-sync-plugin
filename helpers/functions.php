<?php
if (!function_exists('kv_sync_log')) {
    function kv_sync_log($from, $to, $body, $request = "", $type = "", $refer_id = 0)
    {
        global $wpdb;
        $typeMap = [
            1 => 'Webhook',
            2 => 'Create',
            3 => 'Map'
        ];

        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $data = [
            'from' => $from,
            'to' => $to,
            'body' => $body,
            'data' => $request,
            'created_at' => date('Y-m-d H:i:s'),
            'type' => $type,
            'refer_id' => $refer_id
        ];
        $format = array('%s', '%s', '%s', '%s', '%s', '%d', '%d');

        $wpdb->insert("{$wpdb->prefix}kiotviet_sync_logs", $data, $format);
    }
}