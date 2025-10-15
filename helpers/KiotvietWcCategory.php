<?php
class KiotvietWcCategory
{
    private $wpdb, $retailer;
    protected $kiotvietApi;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->retailer = get_option('kiotviet_sync_retailer', "");
        $this->kiotvietApi = new Kiotviet_Sync_Service_Auth();
    }

    public function add_category($data)
    {
        // Check if category exists. Parent must be empty string or null if doesn't exists.
        $args = [];
        if (!empty($data['parentId'])) {
            $categoryKvId = $this->wpdb->get_row("SELECT `category_id` FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `category_kv_id` = " . $data['parentId'] . " AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
            if ($categoryKvId) {
                $args["parent"] = $categoryKvId["category_id"];
            }
        }
        $term = wp_insert_term(htmlspecialchars_decode($data['name']), 'product_cat', $args);
        if (is_wp_error($term)) {
            return new WP_Error('kiotviet_api_user_cannot_insert_product_category', __('Không thể tạo nhóm hàng', 'kiotviet'), 401);
        }
        $term_id = $term['term_id'];
        return $term_id;
    }

    public function edit_category($data)
    {
        $categoryKv = json_decode(html_entity_decode(stripslashes($data['categoryKv'])), true);
        // Check permissions.
        if (!current_user_can('manage_product_terms')) {
            return new WP_Error('kiotviet_api_user_cannot_edit_product_category', __('Bạn không có quyền tạo nhóm hàng', 'kiotviet'), 401);
        }

        $term = get_term($data['id'], 'product_cat');

        if (is_wp_error($term)) {
            return new WP_Error('term_not_found', __('Không tìm thấy nhóm hàng', 'kiotviet'), 401);
        }

        if (!empty($categoryKv['parentId'])) {
            $categorySync = $this->wpdb->get_row("SELECT `category_id` FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `category_kv_id` = " . $categoryKv['parentId'] . " AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
            if ($categorySync) {
                $data['args']['parent'] = $categorySync["category_id"];
            } else {
                unset($data['args']['parent']);
            }
        }

        $data['args']['slug'] = wc_sanitize_taxonomy_name($data['args']['name']);

        $update = wp_update_term($data['id'], 'product_cat', $data['args']);
        if (is_wp_error($update)) {
            return new WP_Error('cannot_edit_product_catgory', __('Không thể update nhóm hàng', 'kiotviet'), 400);
        }

        return $data['id'];
    }

    public function getUncategorizedId()
    {
        $term = term_exists('Uncategorized', 'product_cat', '');
        if ($term) {
            return $term['term_id'];
        }
        return 0;
    }

    public function getDetailCategoryKiotviet($categoryId){
        $response = $this->kiotvietApi->request('GET', 'https://public.kiotapi.com/categories/'.$categoryId, []);
        if(!empty($response['data'])){
            return $response['data'];
        }

        return [];
    }
}
