<?php
require_once plugin_dir_path(dirname(__FILE__)) . '../includes/services/class-kiotviet-sync-services-auth.php';

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kv_Webhooks_List extends WP_List_Table
{
    public function __construct()
    {

        parent::__construct([
            'singular' => __('Webhook'), //singular name of the listed records
            'plural' => __('Webhooks'), //plural name of the listed records
            'ajax' => true //should this table support ajax?
        ]);
    }

    public static function get_webhooks($per_page, $current_page)
    {
        $servicesAuth = new Kiotviet_Sync_Service_Auth();
        $data = $servicesAuth->request("get", "https://public.kiotapi.com/webhooks");
        return $data["data"]["data"];
    }

    public static function get_webhooks_count()
    {
        $servicesAuth = new Kiotviet_Sync_Service_Auth();
        $data = $servicesAuth->request("get", "https://public.kiotapi.com/webhooks");
        return $data["data"]["total"];
    }

    function no_items()
    {
        _e('No webhook found, dude.');
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = $this->get_items_per_page('customers_per_page', 25);
        $current_page = $this->get_pagenum();

        $this->set_pagination_args([
            'total_items' => self::get_webhooks_count(), //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ]);

        $this->items = self::get_webhooks($per_page, $current_page);
    }

    public function column_default($item, $column_name)
    {
        switch ( $column_name ) {
            case 'id':
                return $item['id'];
            case 'type':
                return $item['type'];
            case 'url':
                return $item['url'];
            case 'isActive':
                if($item['isActive'] === true) {
                    return '<span class="active">Đã kích hoạt</span>';
                } else {
                    return '<span class="no-active">Chưa kích hoạt</span>';
                }
            case 'mapStatus':
                $homeUrl = get_home_url();
                if(strpos($item['url'], $homeUrl) !== false) {
                    return '<span class="active">Đã trỏ về website</span>';
                } elseif(strpos($item['url'], 'https://webhook.mykiot.vn') !== false) {
                    return '<span class="active-mykiot">Đã trỏ về Mykiot</span>';
                } else {
                    return '<span class="no-active">Chưa trỏ về website</span>';
                }
            case 'retailerId':
                return $item['retailerId'];
            case 'description':
                return $item['description'];
            case 'modifiedDate':
                $newDate = new DateTime($item['modifiedDate']);
                return $newDate->format("d-m-Y H:i:s");
            case 'curlExample':
                return "curl -X POST -d ".'"Testing '.$item['type'].' by cUrl" '.$item['url'];
            default:
                return print_r( $item, true );
        }
    }

    function column_cb($item)
    {
        return '';
    }

    function get_columns()
    {
        $columns = [
            'id' => 'ID',
            'type' => 'Loại',
            'url' => 'URL',
            'isActive' => 'Trạng thái',
            'mapStatus' => 'Trạng thái trỏ về',
            'retailerId' => 'Retailer ID',
            'description' => 'Mô tả',
            'modifiedDate' => 'Ngày tạo',
            'curlExample' => 'cUrl Example'
        ];

        return $columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array();
        return $sortable_columns;
    }

    public function get_bulk_actions()
    {
        $actions = [
        ];
        return $actions;
    }
}
