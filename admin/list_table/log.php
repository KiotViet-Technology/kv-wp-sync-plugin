<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kv_Logs_List extends WP_List_Table
{
    public function __construct()
    {

        parent::__construct([
            'singular' => __('Log'), //singular name of the listed records
            'plural' => __('Logs'), //plural name of the listed records
            'ajax' => true //should this table support ajax?
        ]);
    }

    public static function get_logs($per_page, $current_page)
    {
        global $wpdb;
        $list = [];

        $start = ($current_page - 1) * $per_page;

        $logs = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}kiotviet_sync_logs` ORDER BY id DESC limit " . $start . ", " . $per_page, ARRAY_A);
        foreach ($logs as $log) {
            $list[] = $log;
        }


        return $list;
    }

    public static function get_logs_count()
    {
        global $wpdb;
        $cnt = $wpdb->get_var("SELECT COUNT(*) as cnt FROM `{$wpdb->prefix}kiotviet_sync_logs`");
        return $cnt;
    }

    function no_items()
    {
        _e('No log found, dude.');
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
            'total_items' => self::get_logs_count(), //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ]);


        $this->items = self::get_logs($per_page, $current_page);
    }


    public function column_default($item, $column_name)
    {
        $typeMap = [
            1 => 'Webhook',
            2 => 'Tạo',
            3 => 'Map'
        ];
        if ($column_name == 'created_at') {
            return date('d/m/Y H:i:s', strtotime($item[$column_name]));
        } elseif (in_array($column_name, ['from', 'to'])) {
            return '<strong>' . $item[$column_name] . '</strong>';
        } elseif ($column_name == 'data') {
            if($item['data']){
                return "<input type='button' class='button button-primary view_log' data-value='".$item['data']."' value='Xem chi tiết'>";
            }
        } elseif ($column_name == 'type') {
            if($item['type']){
                return $typeMap[$item['type']];
            }
        }
        return $item[$column_name];
    }

    function column_cb($item)
    {
        return '';
    }

    function get_columns()
    {
        $columns = [
            'type' => 'Kiểu',
            'refer_id' => 'Mã',
            'created_at' => 'Ngày tạo',
            'from' => 'Từ',
            'to' => 'Đến',
            'body' => 'Nội dung',
            'data' => 'Dữ liệu'
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