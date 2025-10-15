<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Category
{
    private $KiotvietWcCategory;
    private $wpdb;
    private $retailer;
    public function __construct()
    {
        global $wpdb;
        $this->KiotvietWcCategory = new KiotvietWcCategory();
        $this->wpdb = $wpdb;
        $this->retailer = get_option('kiotviet_sync_retailer', "");
        $this->HttpClient = new HttpClient();
    }

    public function delete()
    {
        $categorySync = $this->wpdb->get_results("SELECT category_id FROM {$this->wpdb->prefix}kiotviet_sync_categories");
        foreach ($categorySync as $item) {
            wp_delete_term($item->category_id, 'product_cat');
        }

        $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}kiotviet_sync_categories");
        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function getCategoryIdMap($categoryKvId)
    {
        $categoryMap = [];
        if ($categoryKvId) {
            $categorySync = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `category_kv_id` IN (" . implode(",", $categoryKvId) . ") AND `retailer` = '" . $this->retailer . "'");
            foreach ($categorySync as $item) {
                $categoryMap[$item->category_kv_id] = $item->category_id;
            }
        }

        return $categoryMap;
    }

    public function add()
    {
        $categories = kiotviet_sync_get_request('data', []);
        $categoryKvId = [];

        foreach ($categories as $category) {
            $categoryKvId[] = intval($category['categoryKvId']);
        }

        $categoryMap = $this->getCategoryIdMap($categoryKvId);
        foreach ($categories as $category) {
            if (empty($categoryMap[intval($category["categoryKvId"])])) {
                $category_id = $this->KiotvietWcCategory->add_category($category);
                if (!is_wp_error($category_id)) {
                    $insert = [
                        'category_id' => $category_id,
                        'category_kv_id' => intval($category["categoryKvId"]),
                        'data_raw' => $category["dataRaw"],
                        'retailer' => $this->retailer,
                        'created_at' => kiotviet_sync_get_current_time(),
                    ];
                    $this->insertCategorySync($insert);
                }
            }
        }

        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function deleteCategorySync($category_kv_id)
    {
        $delete = [
            "category_kv_id" => $category_kv_id,
            "retailer" => $this->retailer,
        ];
        $this->wpdb->delete($this->wpdb->prefix . "kiotviet_sync_categories", $delete);
    }

    public function insertCategorySync($category)
    {
        $categorySync = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `category_kv_id` = " . $category['category_kv_id'] . " AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        if (!$categorySync) {
            $this->wpdb->insert($this->wpdb->prefix . "kiotviet_sync_categories", $category);
        }
    }

    public function deleteSync()
    {
        $ids = kiotviet_sync_get_request('data', []);
        if ($ids) {
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `category_kv_id` IN (" . implode(",", $ids) . ") AND `retailer` = '" . $this->retailer . "'");
        }

        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function update()
    {
        $data = kiotviet_sync_get_request('data', []);
        $categoryKvId = [];
        $response = [];
        foreach ($data as $item) {
            $categoryKv = json_decode(html_entity_decode(stripslashes($item['categoryKv'])), true);
            $categoryKvId[] = $categoryKv['categoryId'];
        }

        $categoryMap = $this->getCategoryIdMap($categoryKvId);

        foreach ($data as $item) {
            $categoryKv = json_decode(html_entity_decode(stripslashes($item['categoryKv'])), true);
            if (!empty($categoryMap[$categoryKv['categoryId']])) {
                $item['id'] = $categoryMap[$categoryKv['categoryId']];
                $result = $this->KiotvietWcCategory->edit_category($item);
                if (!is_wp_error($result)) {
                    $response[] = $categoryKv['categoryId'];
                }
            }
        }

        wp_send_json($this->HttpClient->responseSuccess($response));
    }
}
