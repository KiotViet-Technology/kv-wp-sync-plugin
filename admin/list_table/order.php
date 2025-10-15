<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kv_Orders_List extends WP_List_Table
{
    public function __construct()
    {

        parent::__construct([
            'singular' => __('Order'), //singular name of the listed records
            'plural' => __('Orders'), //plural name of the listed records
            'ajax' => true //should this table support ajax?
        ]);
    }

    public static function get_orders($per_page, $current_page)
    {
        global $wpdb;

        $args = array(
            'limit' => $per_page,
            'paged' => $current_page,
        );

        if (array_key_exists('s', $_REQUEST)) {
            $args['billing_first_name'] = sanitize_text_field($_GET['s']);
        }

        $orders = wc_get_orders($args);

        $list = [];
        $kvOrderId = [];

        foreach ($orders as $order) {
            $order = $order->get_data();
            $kvOrderId[] = $order['id'];
            $list[$order['id']] = [
                'ID' => $order['id'],
                'order_number' => '#' . $order['number'] . ' ' . $order['billing']['first_name'] . $order['billing']['last_name'],
                'kv_order' => 'Chưa có',
                'date' => $order['date_created']->date_i18n('d/m/Y'),
                'order_status' => $order['status'],
                'name' => $order['billing']['first_name'] . ' ' . $order['billing']['last_name'],
                'phone' => $order['billing']['phone'],
                'total' => $order['total'],
                'sync' => false,
            ];
        }

        if (sizeof($kvOrderId) > 0) {
            $tableSync = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}kiotviet_sync_orders` WHERE order_id IN (" . implode(',', $kvOrderId) . ")");
            foreach ($tableSync as $kvOrder) {
                $data = json_decode($kvOrder->data_raw);
                $list[$kvOrder->order_id]['kv_order'] = '#' . $data->code;
                $list[$kvOrder->order_id]['sync'] = true;
            }

        }

        return $list;
    }

    function no_items()
    {
        _e('No orders found, dude.');
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


        $orderStatus = ['pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed'];
        $total_items = 0;
        foreach ($orderStatus as $status) {
            $total_items += wc_orders_count($status);
        }


        $this->set_pagination_args([
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ]);


        $this->items = self::get_orders($per_page, $current_page);
    }


    public function column_default($item, $column_name)
    {

        if ($column_name == 'order_number') {
            return '<a target="_blank" href="post.php?post=' . $item['ID'] . '&action=edit&plugin=kiotviet-sync-order" class="order-view"><strong>' . $item[$column_name] . '</strong></a>';
        } elseif ($column_name == 'sync') {
            if (!$item[$column_name]) {
                return '<input type="button"data-id="' . $item['ID'] . '" name="re-sync" id="re-sync-' . $item['ID'] . '" class="button button-primary regular re-sync-order" value="Đồng bộ lại">';
            } else {
                return '<strong style="color:green">Thành công</strong>';
            }
        } elseif ($column_name == 'total') {
            return number_format($item[$column_name]) . get_woocommerce_currency_symbol();
        } elseif ($column_name == 'order_status') {
            return '<mark class="order-status status-' . $item[$column_name] . ' tips"><span>' . ucfirst($item[$column_name]) . '</span></mark>';
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
            'order_number' => 'Mã ĐH',
            'kv_order' => 'Mã ĐH KiotViet',
            'name' => 'Tên khách hàng',
            'date' => 'Ngày tạo',
            'phone' => 'Số điện thoại',
            'order_status' => 'Trạng thái',
            'total' => 'Thành tiền',
            'sync' => 'Trạng thái đồng bộ',
        ];

        return $columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'status' => array('name', true),
            'phone' => array('city', false)
        );

        return $sortable_columns;
    }

    public function get_bulk_actions()
    {
        $actions = [
        ];

        return $actions;
    }
}