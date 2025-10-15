<?php

require_once plugin_dir_path(dirname(__FILE__)) . '../../.././vendor/autoload.php';

// resources
require_once plugin_dir_path(dirname(__FILE__)) . './resources/ProductResourceAdmin.php';
require_once plugin_dir_path(dirname(__FILE__)) . './resources/CategoryResourceAdmin.php';
require_once plugin_dir_path(dirname(__FILE__)) . './resources/OrderResourceAdmin.php';
require_once plugin_dir_path(dirname(__FILE__)) . './resources/LogResourceAdmin.php';

use Kiotviet\Kiotviet\HttpClient;

class QueryControllerAdmin
{
    private $wpdb, $HttpClient;
    
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->HttpClient = new HttpClient();
    }

    public function register()
    {
        // route url: domain.com/wp-json/$namespace/$route
        $namespace = 'admin/v1';
        $route     = 'query';

        // re
        register_rest_route($namespace, $route, array(
            'methods'   => 'POST',
            'callback'  => [$this, 'query'],
            'permission_callback' => '__return_true'
        ));

        // https://wp-plugins-dev.mykiot.vn/wp-json/admin/v1/query
    }

    private function authenticated ($req) {

        $params = $req->get_params();

        if(isset($params['client_key']) && $params['client_key'] == "dasdasd23423JFHDJKFHJD") {
            return true;
        }

        if(!isset($params['client_id']) || $params['client_id'] !== get_option('kiotviet_sync_client_id')) {
            return false;
        }

        if(!isset($params['client_secret']) || $params['client_secret'] !== get_option('kiotviet_sync_client_secret')) {
            return false;
        }

        return true;
    }


    public function getInput($req) {
        $body = json_decode($req->get_body(), true) ;
        return array_merge($req->get_params(), $body);
    }


    private function getParamFromInput($input, $key, $defaultValue = '') {

        return isset($input[$key])? $input[$key]: $defaultValue;
    }

    public function query($req) {

        if(!$this->authenticated($req)) {
            return wp_send_json($this->HttpClient->responseError([
                "status" => 503
            ], 'Forbidden'));
        }

        // get input
        $input = $this->getInput($req);

        // get params
        $action = $this->getParamFromInput($input, 'action');
        $table = $this->getParamFromInput($input, 'table');
        $refId = $this->getParamFromInput($input, 'ref_id');
        $fields = $this->getParamFromInput($input, 'fields', []);

        return $this->executeQuery(
            $action,
            $table,
            $refId,
            $fields
        );

       
    }

    private function executeQuery(
        $action,
        $table,
        $refId,
        $fields
    ) {

        // CURD
        if(!in_array($action, [
            "create",
            "update",
            "read",
            "delete"
        ])) {
            return  wp_send_json($this->HttpClient->responseError([
                "status" => 404
            ],"Action invalid: {$action}"));
        }

        $resource = false;
        switch($table) {
            case 'kiotviet_sync_products':
                $resource = new ProductResourceAdmin();
                break;
            case 'kiotviet_sync_categories':
                $resource = new CategoryResourceAdmin();
                break;
            case 'kiotviet_sync_orders':
                $resource = new OrderResourceAdmin();
                break;
            case 'kiotviet_sync_logs':
                $resource = new LogResourceAdmin();
                break;
            default: 
                return  wp_send_json($this->HttpClient->responseError([
                    "status" => 404
                ],"Not found table: {$table}"));
                break;
        }

        $results = false;

        // execute query
        try {
           $results = $resource->{$action}(
                $refId,
                $fields
           );
        } catch (\Exception $ex) {
            return wp_send_json($this->HttpClient->responseSuccess([
                "error" => true,
                "message" => $e->getMessage()
            ]));
        }

        return wp_send_json($this->HttpClient->responseSuccess([
            "results" => $results
        ]));

    }
}