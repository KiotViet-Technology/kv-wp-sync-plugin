<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Kv_Products_List extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Product'), //singular name of the listed records
            'plural' => __('Products'), //plural name of the listed records
            'ajax' => true //should this table support ajax?
        ]);
    }

    public static function get_total()
    {
        global $wpdb;

        $query = "SELECT count(*) as total FROM `{$wpdb->prefix}posts` AS a INNER JOIN `{$wpdb->prefix}kiotviet_sync_products` AS b ON a.ID = b.product_id WHERE a.post_status = 'publish' AND a.post_type = 'product' AND b.retailer = '" . get_option('kiotviet_sync_retailer', "") . "'";
        if (array_key_exists('s', $_REQUEST)) {
            $query .= " AND a.post_title LIKE '%" . sanitize_text_field($_REQUEST['s']) . "%'";
        }

        $count = $wpdb->get_results($query, OBJECT);
        return $count[0]->total;
    }

    public static function get_products($per_page, $current_page)
    {
        global $wpdb;

        $args = array(
            'limit' => $per_page,
            'paged' => 1
        );

        $limit = ($current_page - 1) * 25;
        $query = "SELECT * FROM `{$wpdb->prefix}posts` AS a INNER JOIN `{$wpdb->prefix}kiotviet_sync_products` AS b ON a.ID = b.product_id WHERE a.post_status = 'publish' AND a.post_type = 'product' AND b.retailer = '" . get_option('kiotviet_sync_retailer', "") . "'";
        if (array_key_exists('s', $_REQUEST)) {
            $query .= " AND a.post_title LIKE '%" . sanitize_text_field($_REQUEST['s']) . "%'";
        }

        $query .= " ORDER BY b.id DESC LIMIT " . $limit . ",25";

        $products = $wpdb->get_results($query, ARRAY_A);

        $productIds = [];
        $statusMaps = [];
        foreach ($products as $product) {
            $productIds[] = $product['ID'];
            $statusMaps[$product['ID']] = $product['status'];
        }

        $args['include'] = $productIds;
        if ($productIds) {
            $products = wc_get_products($args);
        }

        $list = [];
        $kvProductId = [];
        foreach ($products as $product) {
            $data = $product->get_data();
            $kvProductId[] = $data['id'];
            $list[$data['id']] = [
                'ID' => $data['id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'stock_quantity' => $data['stock_quantity'],
                'stock_status' => $data['stock_status'],
                'price' => number_format(intval($data['price'])) . get_woocommerce_currency_symbol(),
                'regular_price' => number_format(intval($data['regular_price'])) . get_woocommerce_currency_symbol(),
                'object' => $product,
                'status' => $statusMaps[$data['id']]
            ];

            if ($product->get_type() == "variable") {
                $sale_price     =  $product->get_variation_sale_price('min', true);
                $regular_price  =  $product->get_variation_regular_price('max', true);
                $price = $regular_price !== $sale_price ? number_format(intval($sale_price)) . get_woocommerce_currency_symbol() . " - " . number_format(intval($regular_price)) . get_woocommerce_currency_symbol() : number_format(intval($regular_price)) . get_woocommerce_currency_symbol();
                $list[$data['id']]['regular_price'] = $price;
            }
        }

        return $list;
    }

    function no_items()
    {
        _e('No products found, dude.');
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

        $total_items = self::get_total();

        $this->set_pagination_args([
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ]);

        $this->items = self::get_products($per_page, $current_page);
    }


    public function column_default($item, $column_name)
    {
        if ($column_name == 'name') {
            return '<a target="_blank" href="post.php?post=' . $item['ID'] . '&action=edit&plugin=kiotviet-sync-product" class="order-view"><strong>' . $item[$column_name] . '</strong></a>';
        } elseif ($column_name == 'img') {
            return $item['object']->get_image();
        } elseif ($column_name == 'status') {
            if (!$item[$column_name]) {
                return '<input type="button" data-product-id="' . $item['ID'] . '" data-status="1" name="product_sync" id="remove-map-' . $item['ID'] . '" class="button button-danger regular product_sync" value="Ngừng đồng bộ">';
            } else {
                return '<input type="button" data-product-id="' . $item['ID'] . '" data-status="0" name="maping" id="product_sync-' . $item['ID'] . '" class="button button-primary regular product_sync" value="Đang đồng bộ">';
            }
        } elseif ($column_name == 'regular_price') {
            return $item[$column_name];
        } elseif ($column_name == 'stock_status') {
            return '<strong class="stock_status ' . $item[$column_name] . '">' . ucfirst($item[$column_name]) . '</strong> (' . $item['stock_quantity'] . ')';
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
            'img' => 'Ảnh',
            'name' => 'Tên sản phẩm',
            'sku' => 'SKU',
            'stock_status' => 'Trạng thái kho',
            'regular_price' => 'Giá',
            'status' => 'Đồng bộ',
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
        $actions = [];
        return $actions;
    }
}
