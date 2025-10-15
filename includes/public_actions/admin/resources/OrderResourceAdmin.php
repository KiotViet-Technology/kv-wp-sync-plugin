<?php

use Kiotviet\Kiotviet\HttpClient;

class OrderResourceAdmin
{
    private $wpdb, $HttpClient, $table;
    
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->HttpClient = new HttpClient();

        // get table
        $this->table = "{$wpdb->prefix}kiotviet_sync_orders";
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

        return wp_send_json($this->HttpClient->responseSuccess([
            "results" => []
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