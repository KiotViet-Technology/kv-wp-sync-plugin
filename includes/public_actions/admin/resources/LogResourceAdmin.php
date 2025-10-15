<?php

use Kiotviet\Kiotviet\HttpClient;

class LogResourceAdmin
{
    private $wpdb, $HttpClient, $table;
    
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->HttpClient = new HttpClient();

        // get table
        $this->table = "{$wpdb->prefix}kiotviet_sync_logs";
    }

    public function create(
        $refId = "",
        $fields = []
    ) {

        return wp_send_json($this->HttpClient->responseSuccess([
            "results" => []
        ]));
    }

    public function update(
        $refId = "",
        $fields = []
    ) {

        return wp_send_json($this->HttpClient->responseSuccess([
            "results" => []
        ]));
    }

    public function read(
        $refId = "",
        $fields = []
    ) {

        $from = isset($fields['from'])? $fields['from']: 0;
        $limit = isset($fields['limit'])? $fields['limit']: 10;

        // get result
        $results = $this->wpdb->get_results("SELECT * FROM {$this->table} ORDER BY id DESC limit " . $from . ", " . $limit, ARRAY_A);
        
        // get total
        $total = $this->wpdb->get_var("SELECT COUNT(*) as total FROM {$this->table}");

        return wp_send_json($this->HttpClient->responseSuccess([
            "total" => $total,
            "logs" => $results,
        ]));
    }
    public function delete(
        $refId = "",
        $fields = []
    ) {

        return wp_send_json($this->HttpClient->responseSuccess([
            "results" => []
        ]));
    }
}