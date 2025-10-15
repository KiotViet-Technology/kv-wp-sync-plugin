<?php
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Product
{
    private $KiotvietWcProduct;
    private $wpdb;
    private $retailer;
    private $response;
    private $client;

    protected $mapConfigProductSync = [
        "name" => "1",
        "category" => "2",
        "images" => "3",
        "description" => "4",
        "regular_price" => "5",
        "sale_price" => "6",
        "stock_quantity" => "7",
        "attributes" => "8"
    ];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->response = [];
        $this->retailer = get_option('kiotviet_sync_retailer', "");
        $this->KiotvietWcProduct = new KiotvietWcProduct();
        $this->HttpClient = new HttpClient();
        // $this->client = new Client();
    }

    public function getProductMap()
    {
        $product_id = kiotviet_sync_get_request('product_id', []);
        $product = [];
        if ($product_id) {
            $product = $this->wpdb->get_results("SELECT * FROM `{$this->wpdb->prefix}kiotviet_sync_products` WHERE `product_kv_id` IN (" . implode(",", $product_id) . ") AND `status` = 1 AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        }
        wp_send_json($this->HttpClient->responseSuccess($product));
    }

    public function productMap($products)
    {
        $productId = [];
        $productMap = [];
        foreach ($products as $product) {
            $productId[] = $product['kv_id'];
        }

        if ($productId) {
            $productSync = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` IN (" . implode(",", $productId) . ") AND `retailer` = '" . $this->retailer . "'");
            foreach ($productSync as $product) {
                $productMap[$product->product_kv_id] = $product;
            }
        }

        return $productMap;
    }

    public function getProductSynced()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT DISTINCT product_kv_id FROM {$wpdb->prefix}kiotviet_sync_products");
        $productsSynced = [];
        foreach ($results as $item) {
            $productsSynced[] = (int)$item->product_kv_id;
        }
        wp_send_json($this->HttpClient->responseSuccess($productsSynced));
    }

    public function getCategoryIdMap($products)
    {
        $categoryId = [];
        $categoryMap = [];
        foreach ($products as $product) {
            $categoryId[] = $product['category_kv'];
        }

        if ($categoryId) {
            $categorySync = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `category_kv_id` IN (" . implode(",", $categoryId) . ") AND `retailer` = '" . $this->retailer . "'");
            foreach ($categorySync as $category) {
                $categoryMap[$category->category_kv_id] = $category->category_id;
            }
        }

        return $categoryMap;
    }

    private function handleResponse($result)
    {
        if ($result) {
            if (is_wp_error($result)) {
                $this->response['error'][] = $result;
            } else {
                $this->response['data'][] = $result;
            }
        }
    }

    public function addProductWC($products, $fn) {
        $categorySync = $this->getCategoryIdMap($products);

        foreach ($products as $product) {
            $data_raw = json_decode($product['data_raw']);

            if (isset($data_raw->unit) && !empty($data_raw->unit)) {
                $product['name'] = $product['name'] . " - " . $data_raw->unit;
            }

            $result = [];
            $product['category_ids'] = array(!empty($categorySync[$product['category_kv']]) ? $categorySync[$product['category_kv']] : []);
            if ($product['type'] == "simple") {
                $result = $this->KiotvietWcProduct->productSimple($product);
            } elseif ($product['type'] == "variable") {
                $result = $this->KiotvietWcProduct->productVariable($product);
            } elseif ($product['type'] == "variation") {
                $result = $this->KiotvietWcProduct->productVariation($product);
            }

            if($fn) {
                $fn($result);
            }
        }
    }

    public function add()
    {
        try {
            $products = kiotviet_sync_decode_json(kiotviet_sync_get_request('data', []));
            $categorySync = $this->getCategoryIdMap($products);
            foreach ($products as $product) {
                $data_raw = json_decode($product['data_raw']);
    
                if (isset($data_raw->unit) && !empty($data_raw->unit)) {
                    $product['name'] = $product['name'] . " - " . $data_raw->unit;
                }
    
                $result = [];
                $product['category_ids'] = array(!empty($categorySync[$product['category_kv']]) ? $categorySync[$product['category_kv']] : []);
                if ($product['type'] == "simple") {
                    $result = $this->KiotvietWcProduct->productSimple($product);
                } elseif ($product['type'] == "variable") {
                    $result = $this->KiotvietWcProduct->productVariable($product);
                } elseif ($product['type'] == "variation") {
                    $result = $this->KiotvietWcProduct->productVariation($product);
                }
    
                $this->handleResponse($result);
            }
    
        } catch (\Exception $exception) {

            kv_sync_log('KiotViet', 'Website', 'add Lỗi đồng bộ sản phẩm: #product ', json_encode([
                'mess' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]));
        }

       
        wp_send_json($this->HttpClient->responseSuccess($this->response));

        // try {

        //     $options = [];
        //     $options['json'] = [
        //         "client_uri" => get_site_url()."/wp-json/admin/v1/query?client_id="
        //             .get_option('kiotviet_sync_client_id')
        //             ."&client_secret="
        //             .get_option('kiotviet_sync_client_secret'),
        //         "action" => "create",
        //         "table" => "kiotviet_sync_products",
        //         "object_name" => "products",
        //         "object_data" => $products
        //     ];
   
        //     $options['headers'] ['Content-Type'] = 'application/json';
            


        //     $response = $this->client->request("POST", "http://54.251.178.152/wp/income/message", $options);

        // } catch (ClientException $exception){
           
        // } catch (GuzzleException $e) {

        // }

        // // response
        // foreach($products as $product) {
        //     $this->handleResponse($product);
        // }

        // wp_send_json($this->HttpClient->responseSuccess($this->response));
    }

    public function update()
    {
        $products = kiotviet_sync_decode_json(kiotviet_sync_get_request('data', []));
        $productSync = $this->productMap($products);
        $categorySync = $this->getCategoryIdMap($products);
        foreach ($products as $product) {
            $result = [];
            $updateProduct = !empty($productSync[$product['kv_id']]) && $productSync[$product['kv_id']]->status == 1;
            $addProductVariant = $product['type'] == "variation" && empty($productSync[$product['kv_id']]);
            if ($updateProduct || $addProductVariant || $product['type'] == 'variable') {
                $product['category_ids'] = array(!empty($categorySync[$product['category_kv']]) ? $categorySync[$product['category_kv']] : []);
                if ($product['type'] == "simple") {
                    $result = $this->KiotvietWcProduct->productSimple($product);
                } elseif ($product['type'] == "variable") {
                    if (!empty($productSync[$product['kv_id']])) {
                        $result = $this->KiotvietWcProduct->productVariable($product);
                    }
                } elseif ($product['type'] == "variation") {
                    $result = $this->KiotvietWcProduct->productVariation($product);
                }
            }
            $this->handleResponse($result);
        }

        wp_send_json($this->HttpClient->responseSuccess($this->response));
    }

    public function delete()
    {
        $wcProductSync = $this->wpdb->get_results("SELECT product_id FROM {$this->wpdb->prefix}kiotviet_sync_products");
        foreach ($wcProductSync as $item) {
            wp_delete_post($item->product_id);
        }
        $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}kiotviet_sync_products");

        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    public function updateStatus()
    {
        $productId = kiotviet_sync_get_request('product_id', 0);
        $productKvId = kiotviet_sync_get_request('product_kv_id', 0);
        $status = kiotviet_sync_get_request('status', 0);
        if ($productId) {
            $this->updateStatusById($productId, $status);
        }

        if ($productKvId) {
            $this->updateStatusByKvId($productKvId, $status);
        }

        wp_send_json($this->HttpClient->responseSuccess($status));
    }

    public function updateStatusByKvId($productKvId, $status)
    {
        $productSync = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` IN (" . implode(",", $productKvId) . ") AND `retailer` = '" . $this->retailer . "'");
        foreach ($productSync as $product) {
            $update = [
                "status" => $status,
            ];

            $this->wpdb->update($this->wpdb->prefix . "kiotviet_sync_products", $update, array("id" => $product->id));
            $productObj = wc_get_product($product->product_id);

            if (WC_Product_Factory::get_product_type($product->product_id) == 'variation') {
                $parentId = $productObj->get_parent_id();
                // update parent product
                $this->wpdb->update($this->wpdb->prefix . "kiotviet_sync_products", $update, array("product_id" => $parentId));
                $productParent = wc_get_product($parentId);
                if ($productParent) {
                    $productChildId = $productParent->get_children();
                    if ($productChildId) {
                        // update child product
                        $query = "UPDATE {$this->wpdb->prefix}kiotviet_sync_products SET `status` = " . $status . " WHERE `product_id` IN (" . implode(",", $productChildId) . ")";
                        $this->wpdb->query($query);
                    }
                }
            }
        }
    }

    public function updateStatusById($productId, $status)
    {
        $productSync = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_id` = " . $productId . " AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        if ($productSync) {
            $update = [
                "status" => $status,
            ];

            $this->wpdb->update($this->wpdb->prefix . "kiotviet_sync_products", $update, array("id" => $productSync['id']));

            // update product child variant
            if ($productSync['product_kv_id'] == 0) {
                $productParent = wc_get_product($productSync['product_id']);
                if ($productParent) {
                    $productChildId = $productParent->get_children();
                    if ($productChildId) {
                        $query = "UPDATE {$this->wpdb->prefix}kiotviet_sync_products SET `status` = " . $status . " WHERE `product_id` IN (" . implode(",", $productChildId) . ")";
                        $this->wpdb->query($query);
                    }
                }
            }
        }
    }

    public function updatePrice()
    {
        $configProductSync = get_option('kiotviet_sync_product_sync', []);

        $data = kiotviet_sync_get_request('data', []);
        $result = [];
        $productMapIds = [];
        foreach ($data as $item) {
            $productIDs[] = $item['productKvId'];
        }

        $wcProductSync = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` IN (" . implode(",", $productIDs) . ") AND `status` = 1 AND  `retailer` = '" . $this->retailer . "'");
        foreach ($wcProductSync as $item) {
            $productMapIds[$item->product_kv_id] = $item->product_id;
        }

        foreach ($data as $item) {
            $productId = !empty($productMapIds[$item['productKvId']]) ? $productMapIds[$item['productKvId']] : 0;
            if ($productId) {
                $product = wc_get_product($productId);
                if ($product) {
                    if (!in_array($this->mapConfigProductSync["regular_price"], $configProductSync)) {
                        $product->set_regular_price($item['regularPrice']);
                    }

                    if (!in_array($this->mapConfigProductSync["sale_price"], $configProductSync)) {
                        $product->set_sale_price($item['salePrice']);
                    }

                    $product->save();
                    $result[] = $product;
                }
            }
        }
        wp_send_json($this->HttpClient->responseSuccess($result));
    }

    public function updateStock()
    {
        $configProductSync = get_option('kiotviet_sync_product_sync', []);

        $data = kiotviet_sync_get_request('data', []);
        $result = [];
        $productMapIds = [];
        foreach ($data as $item) {
            $productIDs[] = $item['productKvId'];
        }

        $wcProductSync = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` IN (" . implode(",", $productIDs) . ") AND `status` = 1 AND`retailer` = '" . $this->retailer . "'");
        foreach ($wcProductSync as $item) {
            $productMapIds[$item->product_kv_id] = $item->product_id;
        }

        foreach ($data as $item) {
            $productId = !empty($productMapIds[$item['productKvId']]) ? $productMapIds[$item['productKvId']] : 0;
            if ($productId) {
                $product = wc_get_product($productId);
                if ($product && !in_array($this->mapConfigProductSync["stock_quantity"], $configProductSync)) {
                    $product->set_stock_quantity($item['stock']);
                    $product->save();
                    $result[] = $product;

                    // update stock parent
                    if (WC_Product_Factory::get_product_type($productId) == 'variation') {
                        $parentId = $product->get_parent_id();
                        $wcParentProduct = wc_get_product($parentId);
                        if ($wcParentProduct) {
                            $this->KiotvietWcProduct->updateStockProductParent($wcParentProduct);
                        }
                    }
                }
            }
        }

        wp_send_json($this->HttpClient->responseSuccess($result));
    }

    public function getProductParent($productKvId)
    {
        $productSync = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `parent` = " . $productKvId . " AND `status` = 1 AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        $parentId = 0;
        if ($productSync) {
            $parentId = $productSync['product_id'];
        }

        return $parentId;
    }

    public function deleteProductMap()
    {
        $productKvId = kiotviet_sync_get_request('product_id', 0);
        try {
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` = " . $productKvId);
        } catch (\Exception $e) {
            wp_send_json($this->HttpClient->responseError(
                $e->getMessage()
            ));
        }

        wp_send_json($this->HttpClient->responseSuccess([]));
    }
}
