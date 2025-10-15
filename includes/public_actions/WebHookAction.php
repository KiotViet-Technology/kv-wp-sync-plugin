<?php
require_once KIOTVIET_PLUGIN_PATH . '/helpers/KiotvietWcProduct.php';

class WebHookAction extends KiotvietWcProduct
{
    protected $stockBranch, $regularPrice, $salePrice, $KiotvietWcCategory;
    private $wpdb, $retailer, $configProductSync;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->KiotvietWcCategory = new KiotvietWcCategory();
        $this->getConfig();
        $this->retailer = get_option('kiotviet_sync_retailer', "");
        $this->configProductSync = get_option('kiotviet_sync_product_sync', []);
        parent::__construct();
    }

    public function register_api_route()
    {
        $webhookKey = get_option('webhook_key');

        register_rest_route('kiotviet-sync/v1', $webhookKey . '/webhook/', array(
            'methods' => 'POST',
            'callback' => [$this, 'product'],
            'permission_callback' => '__return_true'
        ));
    }

    public function product($req)
    {
        try {
            $req->set_headers([ 'Cache-Control' => 'must-revalidate, no-cache, no-store, private' ]);
            $data = $req->get_body();
    
            if (!is_dir('wp-content/uploads/kiotviet-log')) {
                wp_mkdir_p('wp-content/uploads/kiotviet-log');
            }
            file_put_contents('wp-content/uploads/kiotviet-log/log-webhook-kiotviet.txt', $data);
            file_put_contents('wp-content/uploads/kiotviet-log/log-webhook-kiotviet.txt', "\n\nThời gian: " . date("Y-m-d H:i:s", time()), FILE_APPEND);
    
            $response = [
                'code' => 0,
                'msg' => 'No action',
            ];
    
    
            if ($this->isJson($data)) {
                $data = json_decode($data);
    
                $action = $data->Notifications[0]->Action;
    
                list($entity, $method, $id) = explode('.', $action);
    
                if ($entity == 'stock' && $method == 'update') {
                    $this->updateStock($data);
                    $response = [
                        'code' => 1,
                        'msg' => 'Update stock',
                    ];
                } elseif ($entity == 'product' && $method == 'update') {
                    $this->updateProduct($data);
                    $response = [
                        'code' => 1,
                        'msg' => 'Update product',
                    ];
                } elseif ($entity == 'product' && $method == 'delete') {
                    $this->deleteProduct($data);
                    $response = [
                        'code' => 1,
                        'msg' => 'Delete product',
                    ];
                } elseif ($entity == 'order' && $method == 'update') {
                    $this->updateOrder($data);
                    $response = [
                        'code' => 1,
                        'msg' => 'Update order',
                    ];
                }
            }
    
            return $response;
        } catch (\Exception $exception) {

            kv_sync_log('KiotViet', 'Website', 'Lỗi đồng bộ sản phẩm: #product ', json_encode([
                'mess' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]));

            return [
                'code' => 0,
                'msg' => $exception->getMessage(),
            ];
        }

        return [
            'code' => 0,
            'msg' => 'No action',
        ];
       
    }

    protected function updateOrder($data)
    {
        $kvOrderId = $data->Notifications[0]->Data[0]->Id;
        $kvOrderStatus = $data->Notifications[0]->Data[0]->StatusValue;
        $kvOrderCode = $data->Notifications[0]->Data[0]->StatusValue;
        $orderId = $this->getOrderIdFromKvOrderId($kvOrderId);

        if ($orderId != -1) {
            $orderObj = wc_get_order($orderId);
            // handle status cancelled reduce stock
            if ($kvOrderStatus == "Đã hủy") {
                $update = [
                    'post_status' => 'wc-cancelled',
                ];
                $orderObj->add_order_note("Cập nhật từ KiotViet: <strong>{$kvOrderStatus}</strong>\n\n<hr />");
                $orderObj->save();
                $this->wpdb->update($this->wpdb->prefix . "posts", $update, array("ID" => $orderId));
            } else {
                $statusMappig = [
                    'Phiếu tạm' => 'processing',
                    'Đang xử lý' => 'processing',
                    'Hoàn thành' => 'completed',
                    'Đang giao hàng' => 'processing',
                ];
                $wcStatus = array_key_exists($kvOrderStatus, $statusMappig) ? $statusMappig[$kvOrderStatus] : '';
                $orderObj->set_status($wcStatus, "Cập nhật từ KiotViet: <strong>{$kvOrderStatus}</strong>\n\n<hr />");
                $orderObj->save();
            }

            kv_sync_log('KiotViet', 'Website', 'Cập nhật trạng thái đơn hàng thành công. Mã đơn hàng: #' . $kvOrderCode, json_encode($data), 1, $orderId);
        } else {
            kv_sync_log('KiotViet', 'WebSite', "updateOrder: getOrderIdFromKvOrderId {$orderId}", json_encode($data), 1, 0);
        }
    }

    public function getOrderIdFromKvOrderId($kvOrderId)
    {
        global $wpdb;
        $order = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}kiotviet_sync_orders` WHERE `order_kv_id` = $kvOrderId", ARRAY_A);
        if (is_array($order)) {
            return $order['order_id'];
        }
        return -1;
    }

    protected function updateStock($data)
    {
        $inventories = $data->Notifications[0]->Data;

        foreach ($inventories as $inventory) {
            //  Todo: Will be check the branch in setting
            if ($inventory->BranchId == $this->stockBranch) {
                $productSync = $this->wpdb->get_row("SELECT `product_id` FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` = " . $inventory->ProductId . " AND `retailer` = '" . $this->retailer . "' AND `status` = 1", ARRAY_A);
                if ($productSync) {
                    $wcProduct = wc_get_product($productSync['product_id']);
                    if ($wcProduct) {
                        $oldStock = $wcProduct->get_stock_quantity();
                        $stock = $inventory->OnHand - $inventory->Reserved;
                        if ($oldStock != $stock) {
                            $stock = $stock < 0 ? 0 : floor($stock);
                            $wcProduct->set_stock_quantity($stock);
                            $wcProduct->save();
                            kv_sync_log('KiotViet', 'Website', 'Cập nhật thông tin tồn kho thành công. Mã sản phẩm: #' . $productSync['product_id'] . "\n Tồn kho " . $oldStock . " -> " . $stock, json_encode($data), 1, $wcProduct->get_id());
                        }

                        // update stock parent when product variant
                        if ($wcProduct->get_type() == 'variation') {
                            $parentId = $wcProduct->get_parent_id();
                            $wcParentProduct = wc_get_product($parentId);
                            $this->updateStockProductParent($wcParentProduct);
                        }
                    }

                    wc_delete_product_transients( $productSync['product_id'] ); // Clear/refresh the variation cache
                }
            }
        }
    }

    protected function updateProduct($data)
    {
        try {
            $kvProducts = $data->Notifications[0]->Data;
            foreach ($kvProducts as $kvProduct) {
                $productSync = $this->wpdb->get_row("SELECT `product_id` FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` = " . $kvProduct->Id . " AND `retailer` = '" . $this->retailer . "' AND `status` = 1", ARRAY_A);

                if ($productSync) {
                    $wcProductId = $productSync['product_id'];
                    $this->configProductSync = get_option('kiotviet_sync_product_sync', []);
                    // remove - Website by Product name KV
                    $productName = preg_replace("/\s-\sWebsite$/", "", $kvProduct->Name);
                    $description = !empty($kvProduct->Description) ? $kvProduct->Description : "";
                    $weight = !empty($kvProduct->Weight) ? $kvProduct->Weight : 0;
                    $attributes = $this->getAttributes($kvProduct);
                    $productType = "";

                    $isProductMaster = !empty($kvProduct->Attributes) && empty($kvProduct->MasterProductId);
                    $isProductSimple = empty($kvProduct->Attributes);
                    $isProductVariation = !empty($kvProduct->Attributes);

                    $wcProduct = wc_get_product($wcProductId);
                    //$parentId = $wcProduct->get_parent_id();

                    // Product Variable
                    if ($isProductMaster) {
                        if ($wcProduct->get_type() === 'variation') {
                            $parentId = $wcProduct->get_parent_id();
                            if ($parentId) {
                                $wcParentProduct = wc_get_product($parentId);
                                $this->updateName($wcParentProduct, $productName);
                                $this->updateDescription($wcParentProduct, $description);
                                $wcParentProduct->set_weight($weight);
                                $this->updateCategory($wcParentProduct, $kvProduct);
                                $this->updateImage($wcParentProduct, $kvProduct);
                                $this->updateLowStockProduct($wcParentProduct, $kvProduct);
                                $wcParentProduct->save();
                            }
                        }

                        if ($wcProduct->get_type() === 'simple') {
                            $productVariable = $this->addProductVariable($kvProduct);
                            $wcParentProduct = wc_get_product($productVariable);
                            if ($wcParentProduct->get_status() == 'trash') {
                                $this->wpdb->update($this->wpdb->prefix . "posts", array('post_status' => 'publish'), array("ID" => $productVariable));
                            }

                            $product = $this->transformProduct($kvProduct, "variation", $wcProductId, $this->regularPrice, $this->salePrice, $this->stockBranch);
                            $product['parent_id'] = $productVariable;
                            $parentId = $this->import_product($product);
                            continue;
                        }
                    }

                    if ($isProductVariation) {
                        if ($wcProduct->get_type() === 'simple') {
                            $parentId = $this->getProductParentIdByKv($kvProduct->MasterProductId);
                            $wcParentProduct = wc_get_product($parentId);
                            $this->check_attribute_parent($wcParentProduct, $attributes);
                            $product = $this->transformProduct($kvProduct, "variation", $wcProductId, $this->regularPrice, $this->salePrice, $this->stockBranch);
                            $product['parent_id'] = $parentId;
                            $this->import_product($product);
                            $wcParentProduct = wc_get_product($parentId);
                            $this->updateStockProductParent($wcParentProduct);

                            continue;
                        }
                    }

                    // Product Variation
                    if ($wcProduct->get_type() === 'variation') {
                        $parentId = $wcProduct->get_parent_id();
                        // set attributes product variable
                        $wcParentProduct = wc_get_product($parentId);
                        $this->check_attribute_parent($wcParentProduct, $attributes);
                        // update attributes
                        $this->set_variation_data($wcProduct, [
                            'raw_attributes' => $attributes,
                            'parent_id' => $parentId
                        ]);

                        $wcParentProduct = wc_get_product($parentId);
                        $this->updateStockProductParent($wcParentProduct);
                    }

                    $this->updateCategory($wcProduct, $kvProduct);
                    $this->updateName($wcProduct, $productName);
                    $this->updateDescription($wcProduct, $description);
                    // Update images
                    $this->updateImage($wcProduct, $kvProduct);

                    $wcProduct->save();

                    $wcProduct->set_weight($weight);
                    // update sku
                    if ($wcProduct->get_sku() !== $kvProduct->Code) {
                        // remove Website by sku Kv
                        $sku = preg_replace("/^Website/", "", $kvProduct->Code);
                        $wcProduct->set_sku($sku);
                    }

                    if (!$kvProduct->isActive) {
                        $this->delete_product($wcProductId);
                        continue;
                    } else {
                        $wcProduct->set_status('publish');
                    }

                    // Update stock
                    $this->updateStockProduct($wcProduct, $kvProduct);

                    // Update low stock
                    $this->updateLowStockProduct($wcProduct, $kvProduct->Inventories);

                    // Update priceBook
                    $this->updatePrice($wcProduct, $kvProduct);

                    $wcProduct->save();

                    if ($isProductSimple) {
                        $wcProduct = wc_get_product($wcProductId);
                        wp_set_object_terms($wcProductId, 'simple', 'product_type');
                        $this->wpdb->update($this->wpdb->prefix . "posts", array('post_type' => 'product', 'post_parent' => 0), array("ID" => $wcProductId));
                        //$this->wpdb->update($this->wpdb->prefix . "posts", array('post_status' => 'trash'), array("ID" => $wcProductId));
                    }

                    kv_sync_log('KiotViet', 'Website', 'Cập nhật thông tin sản phẩm thành công. Mã sản phẩm: #' . $kvProduct->Code, json_encode($data), 1, $wcProductId);

                    wc_delete_product_transients( $productSync['product_id'] ); // Clear/refresh the variation cache
                } else {
                    // add product variant same type
                    $this->addProductVariant($kvProduct);
                }
            }
        } catch (Exception $exception) {
            update_option('shop_debug_log',[
                'mess' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]);

            kv_sync_log('KiotViet', 'Website', 'Lỗi đồng bộ sản phẩm: #updateProduct ', json_encode([
                'mess' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]));

            return $exception->getMessage();
        }
    }

    private function addProductVariable($kvProduct)
    {
        $productTransform = $this->transformProduct($kvProduct, "variation", 0, $this->regularPrice, $this->salePrice, $this->stockBranch);
        $productVariable = $this->transformProductMaster($productTransform);
        $parentId = $this->productVariable($productVariable);
        return $parentId;
    }

    private function addProductVariant($kvProduct)
    {
        if (!empty($kvProduct->Attributes) && !empty($kvProduct->MasterProductId) && empty($kvProduct->MasterUnitId)) {
            $parentId = $this->getProductParentIdByKv($kvProduct->MasterProductId);
            if ($parentId) {
                $wcParentProduct = wc_get_product($parentId);
                $attributes = $this->getAttributes($kvProduct);
                $this->check_attribute_parent($wcParentProduct, $attributes);
                // update attributes
                $product = $this->transformProduct($kvProduct, "variation", 0, $this->regularPrice, $this->salePrice, $this->stockBranch);
                $product['parent_id'] = $parentId;
                $this->import_product($product);
                // update stock product parent
                $wcParentProduct = wc_get_product($parentId);
                $this->updateStockProductParent($wcParentProduct);

                wc_delete_product_transients( $parentId ); // Clear/refresh the variation cache
            }
        }
    }

    protected function deleteProduct($data)
    {
        $productIds = $data->Notifications[0]->Data;
        foreach ($productIds as $productId) {
            $productSync = $this->wpdb->get_row("SELECT `product_id` FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_kv_id` = " . $productId . " AND `retailer` = '" . $this->retailer . "' AND `status` = 1", ARRAY_A);
            if ($productSync) {
                $wcProductId = $productSync['product_id'];
                $this->delete_product($wcProductId, true);
                // delete product map sync
                $delete = [
                    "id" => $productSync['id'],
                ];

                $this->wpdb->delete($this->wpdb->prefix . "kiotviet_sync_products", $delete);
            }
        }
    }

    protected function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function getConfig()
    {
        $branchStock = get_option('kiotviet_sync_config_branch_stock');
        $regularPrice = get_option('kiotviet_sync_regular_price');
        $salePrice = get_option('kiotviet_sync_sale_price');

        $branchStock = json_decode(html_entity_decode(stripslashes($branchStock)), true);
        if (is_array($branchStock) && array_key_exists('id', $branchStock)) {
            $this->stockBranch = $branchStock['id'];
        }

        $regularPrice = json_decode(html_entity_decode(stripslashes($regularPrice)), true);
        if (is_array($regularPrice) && array_key_exists('id', $regularPrice)) {
            $this->regularPrice = $regularPrice['id'];
        }

        $salePrice = json_decode(html_entity_decode(stripslashes($salePrice)), true);
        if (is_array($salePrice) && array_key_exists('id', $salePrice)) {
            $this->salePrice = $salePrice['id'];
        }
    }

    private function updateName(&$productObject, $productName)
    {
        if (!in_array($this->mapConfigProductSync["name"], $this->configProductSync)) {
            $productObject->set_name($productName);
        }
    }

    private function updateDescription(&$productObject, $productDescription)
    {
        if (!in_array($this->mapConfigProductSync["description"], $this->configProductSync)) {
            $productObject->set_description($productDescription);
        }
    }

    private function updateImage(&$productObject, $kvProduct)
    {
        $images = $this->getImagesProduct($kvProduct->Images);
        if ($images) {
            if (!in_array($this->mapConfigProductSync["images"], $this->configProductSync)) {
                $this->set_image_data($productObject, $images);
            }
        }
    }

    private function updateCategory(&$productObject, $kvProduct)
    {
        if (!in_array($this->mapConfigProductSync["category"], $this->configProductSync)) {
            $categorySync = $this->wpdb->get_row("SELECT `category_id` FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `retailer` = '" . $this->retailer . "' AND `category_kv_id` = " . $kvProduct->CategoryId . "", ARRAY_A);
            if ($categorySync) {
                $productObject->set_category_ids(array($categorySync['category_id']));
            } else {
                $cateogryId = $this->insertCategory($kvProduct);
                if ($cateogryId) {
                    $productObject->set_category_ids(array($cateogryId));
                }
            }
        }
    }

    private function insertCategory($kvProduct)
    {
        $category_id = 0;
        $categoryDetailKiotviet = $this->KiotvietWcCategory->getDetailCategoryKiotviet($kvProduct->CategoryId);
        if ($categoryDetailKiotviet) {
            $category = [
                'name' => $categoryDetailKiotviet['categoryName'],
                'parentId' => !empty($categoryDetailKiotviet['parentId']) ? $categoryDetailKiotviet['parentId'] : 0,
                'categoryKvId' => $categoryDetailKiotviet['categoryId'],
                'dataRaw' => json_encode($categoryDetailKiotviet)
            ];

            $category_id = $this->KiotvietWcCategory->add_category($category);
            if (!is_wp_error($category_id)) {
                $insert = [
                    'category_id' => $category_id,
                    'category_kv_id' => $category["categoryKvId"],
                    'data_raw' => $category["dataRaw"],
                    'retailer' => $this->retailer,
                    'created_at' => kiotviet_sync_get_current_time(),
                ];

                $this->wpdb->insert($this->wpdb->prefix . "kiotviet_sync_categories", $insert);
            }
        }

        return $category_id;
    }

    private function getImagesProduct($images)
    {
        $data = [];
        if (!$images) {
            $data = [
                'raw_gallery_image_ids' => [],
                'raw_image_id' => [],
            ];
        } else {
            if (count($images) == 1) {
                $data = [
                    'raw_gallery_image_ids' => [],
                    'raw_image_id' => $images[0],
                ];
            } else if (count($images) > 1) {
                $raw_gallery_image_ids = [];
                foreach ($images as $key => $item) {
                    if ($key > 0) {
                        $raw_gallery_image_ids[] = $item;
                    }
                }

                $data = [
                    'raw_gallery_image_ids' => $raw_gallery_image_ids,
                    'raw_image_id' => $images[0],
                ];
            }
        }

        return $data;
    }

    private function updatePrice(&$product, $kvProduct)
    {
        if (!in_array($this->mapConfigProductSync["regular_price"], $this->configProductSync)) {
            $product->set_regular_price($this->getRegularPrice($kvProduct, $this->regularPrice));
        }

        if (!empty($kvProduct->PriceBooks)) {
            foreach ($kvProduct->PriceBooks as $priceBook) {
                if ($priceBook->PriceBookId == $this->salePrice) {
                    if (!in_array($this->mapConfigProductSync["sale_price"], $this->configProductSync)) {
                        $product->set_sale_price($priceBook->Price);
                        $product->set_date_on_sale_from(strtotime($priceBook->StartDate));
                        $product->set_date_on_sale_to(strtotime($priceBook->EndDate));
                    }
                }
            }
        }
    }

    private function updateStockProduct(&$wcProduct, $kvProduct)
    {
        $wcProduct->set_stock_quantity($this->getStock($kvProduct, $this->stockBranch));
    }

    private function updateLowStockProduct(&$wcProduct, $kvProduct)
    {
        $wcProduct->set_low_stock_amount($this->getLowStock($kvProduct, $this->stockBranch));
    }
}
