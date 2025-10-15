<?php
class OrderHookAction
{
    protected $wpdb;
    protected $kiotvietApi;
    protected $orderBranch;
    protected $orderExtra = '';
    private $retailer, $KiotvietWcProduct;

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
        $this->kiotvietApi = new Kiotviet_Sync_Service_Auth();

        $data = get_option('kiotviet_sync_config_branch_order');
        $data = json_decode(html_entity_decode(stripslashes($data)), true);
        if (is_array($data) && array_key_exists('id', $data)) {
            $this->orderBranch = $data['id'];
        }

        $salePrice = get_option('kiotviet_sync_sale_price');
        $salePrice = json_decode(html_entity_decode(stripslashes($salePrice)), true);
        if (is_array($salePrice) && array_key_exists('id', $salePrice) && !empty($salePrice['id'])) {
            $this->orderExtra = json_encode([
                "PriceBookId" => [
                    "Id" => $salePrice['id'],
                    "Name" => $salePrice['name'],
                ]
                ]);
        }

        $this->retailer = get_option('kiotviet_sync_retailer', "");
        $this->KiotvietWcProduct = new KiotvietWcProduct();
    }

    public function order_processed($orderId, $auto = false)
    {
        // NOTE: sync order
        $auto_sync_order = get_option('kiotviet_sync_auto_sync_order', "");

        if ($auto_sync_order == "true" || $auto) {
            try {
                $productAddKv = 0;
                $orderObj = wc_get_order($orderId);

                if (!$orderObj) {
                    return [
                        'status' => 'error',
                        'msg' => 'Không tìm thấy đơn hàng trên website',
                    ];
                }

                $orderData = $orderObj->get_data();

                $surchagesData = [];

                $kv_syncshipping = get_option('kv_syncshipping');
                if($kv_syncshipping == '1') {
                    // lấy danh sách thu khác
                    $surchages = $this->kiotvietApi->request('GET', 'https://public.kiotapi.com/surchages', [
                        "pageSize" => "100",
                    ]);

                    if ($surchages['status'] === 'success') {
                        $surchargeCodeArr = [];
                        foreach ((array)$surchages['data']['data'] as $itemSurchages) {
                            $surchargeCodeArr[] = $itemSurchages['surchargeCode'];
                        }

                        if (!in_array("THKSHIPPINGWEB", $surchargeCodeArr)) {
                            // tạo thu khác shipping
                            $surchargeData = [
                                'name' => 'Phí vận chuyển (Website)',
                                'code' => 'THKSHIPPINGWEB',
                                'value' => 0
                            ];
                            $createSurcharge = $this->kiotvietApi->request('POST', 'https://public.kiotapi.com/surchages', $surchargeData, 'json');

                            if ($createSurcharge['status'] === 'success') {
                                $surchagesDataItem = [
                                    'id' => $createSurcharge['data']['data']['id'],
                                    'code' => $createSurcharge['data']['data']['surchargeCode'],
                                    'price' => $orderData['shipping_total']
                                ];
                            } else {
                                $surchagesDataItem = [];
                                return [
                                    'status' => 'error',
                                    'msg' => 'Mã thu khác shipping đã tồn tại nhưng chưa kích hoạt, vui lòng kích hoạt.',
                                ];
                                die();
                            }
                        } else {
                            // lấy dữ liệu thu khác shipping đã tồn tại
                            foreach ((array)$surchages['data']['data'] as $itemSurchages) {
                                if ($itemSurchages['surchargeCode'] === 'THKSHIPPINGWEB') {
                                    $surchagesDataItem = [
                                        'id' => $itemSurchages['id'],
                                        'code' => $itemSurchages['surchargeCode'],
                                        'price' => $orderData['shipping_total']
                                    ];
                                }
                            }
                        }
                        $surchagesData[] = $surchagesDataItem;
                    }
                }

                $orderItems = $orderObj->get_items();

                $productItems = [];

                foreach ($orderItems as $orderItem) {
                    if ($orderItem) {
                        $orderItemData = $orderItem->get_data();
                        $productId = $orderItemData['product_id'];

                        $productObj = wc_get_product($productId);
                        // product variant
                        if (WC_Product_Factory::get_product_type($productId) == "variable") {
                            $productId = $orderItemData['variation_id'];
                            $productObj = wc_get_product($productId);
                        }

                        if ($productObj) {
                            $product = $productObj->get_data();

                            if(get_option('kv_syncorderbysku') == '1') {
                                $kvProductId = $this->get_kv_product_id_from_wc_product_sku($product['sku']);
                            } else {
                                $kvProductId = $this->get_kv_product_id_from_wc_product_id($productId);
                            }

                            if ($kvProductId == -1) {
                                if($kvProductId == -1) {
                                    $kvProductId = $this->create_kv_product_from_wc_product($productObj);
                                    $productAddKv = $kvProductId;
                                    $product = $productObj->get_data();
                                }
                            }

                            $productItems[] = [
                                'productId' => $kvProductId,
                                'productCode' => $product['sku'],
                                'quantity' => $orderItemData['quantity'],
                                'price' => $product['price'],
                                'discount' => 0,
                                'discountRatio' => 0,
                            ];
                        } else {
                            return [
                                'status' => 'error',
                                'msg' => 'Sản phẩm trong đơn hàng đã bị xóa trên website.',
                            ];
                        }
                    }
                }

                if (!isset($orderData['billing']['phone']) || empty($orderData['billing']['phone']) || $orderData['billing']['phone'] == "") {
                    $customerId = $this->create_kv_customer($orderData['billing']);
                } else {
                    $customerId = $this->get_customer_id_from_contact_number($orderData['billing']['phone']);
                    if ($customerId == -1) {
                        $customerId = $this->create_kv_customer($orderData['billing']);
                    }
                }

                if(!empty($orderData['payment_method_title'])) {
                    $payment_method = $orderData['payment_method_title'] . ".";
                } else {
                    $payment_method = "";
                }

                if (!empty($orderData['customer_note'])) {
                    $orderDescription = $orderData['customer_note'] . ". " . "Đơn hàng từ website, mã đơn hàng #" . $orderObj->get_order_number() . ". " . $payment_method;
                } else {
                    $orderDescription = "Đơn hàng từ website, mã đơn hàng #" . $orderObj->get_order_number() . ". " . $payment_method;
                }

                $kvOrderData = [
                    'branchId' => $this->orderBranch,
                    'customerId' => $customerId,
                    'totalPayment' => 0,
                    'discount' => $orderData['discount_total'],
                    'makeInvoice' => false,
                    'description' => $orderDescription,
                    'method' => 'CASH',
                    'status' => 0,
                    'orderDetails' => $productItems,
                    'orderDelivery' => array(
                        'type' => 1,
                        'price' => 0,
                        'receiver' => $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'],
                        'contactNumber' => $orderData['billing']['phone'],
                        'address' => $orderData['billing']['address_1'] . ', ' . $orderObj->get_billing_city() . ', ' . $orderObj->get_billing_country(),
                    ),
                    'surchages' => $surchagesData,
                    'Extra' => $this->orderExtra,
                ];

                $response = $this->kiotvietApi->request('POST', 'https://public.kiotapi.com/orders', $kvOrderData, 'json', [
                    "Partner" => "KVSync",
                ]);

                if ($response['status'] != 'error') {
                    $data = [
                        'order_id' => $orderId,
                        'order_kv_id' => $response['data']['id'],
                        'data_raw' => json_encode($response['data']),
                        'created_at' => kiotviet_sync_get_current_time(),

                    ];
                    $format = array('%d', '%d', '%s', '%d');

                    $this->wpdb->insert("{$this->wpdb->prefix}kiotviet_sync_orders", $data, $format);
                    kv_sync_log('Website', 'KiotViet', 'Tạo đơn hàng trên KiotViet thành công, mã đơn hàng: #' . $response['data']['code'], json_encode($response['data']), 2, $orderId);
                } else {
                    // $this->remove_kv_product($productAddKv);
                    $msg = 'Có lỗi xảy ra, vui lòng kiểm tra lại';
                    if($response['message']) $msg = $response['message'];
                    if($response['error']['responseStatus']['message']) $msg = $response['error']['responseStatus']['message'];
                    kv_sync_log('WebSite', 'KiotViet', "order_processed error: {$msg}", json_encode($response['data']), 2, 0);

                    return [
                        'status' => 'error',
                        'msg' => $msg,
                    ];
                }
                return $response;
            } catch (Exception $e) {
                var_dump($e);
                kv_sync_log('WebSite', 'KiotViet', "order_processed Exception: {$e->getMessage()}", json_encode($response['data']), 2, 0);
            }
        }
    }

    public function update_stock_order($orderId)
    {
        try {
            $productParents = [];
            $orderObj = wc_get_order($orderId);
            if (!$orderObj) {
                return [
                    'status' => 'error',
                    'msg' => 'Không tìm thấy đơn hàng trên website',
                ];
            }

            $orderItems = $orderObj->get_items();
            foreach ($orderItems as $orderItem) {
                if ($orderItem) {
                    $orderItemData = $orderItem->get_data();
                    $productId = $orderItemData['product_id'];
                    $productObj = wc_get_product($productId);
                    // product variant
                    if (WC_Product_Factory::get_product_type($productId) == "variable") {
                        $productId = $orderItemData['variation_id'];
                        $productObj = wc_get_product($productId);
                        $productParents[] = $productObj->get_parent_id();
                    }
                }
            }

            // update stock product master
            foreach ($productParents as $productParent) {
                $productParentObj = wc_get_product($productParent);
                if ($productParentObj) {
                    $this->KiotvietWcProduct->updateStockProductParent($productParentObj);
                }
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function remove_kv_product($productId)
    {
        $this->kiotvietApi->request('delete', 'https://public.kiotapi.com/products/' . $productId, []);
    }

    public function get_kv_product_id_from_wc_product_id($productId)
    {
        $product = $this->wpdb->get_row("SELECT * FROM `{$this->wpdb->prefix}kiotviet_sync_products` WHERE `product_id` = $productId AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        if (is_array($product)) {
            return $product['product_kv_id'];
        }
        return -1;
    }

    public function get_kv_product_id_from_wc_product_sku($productSku)
    {
        $response = $this->kiotvietApi->request('GET', 'https://public.kiotapi.com/products/code', [
            'Id' => 0,
            'Code' => $productSku
        ]);
        if(!empty($response['data'])){
            $kvProduct = $response['data'];
            return $kvProduct['id'];
        }
        return -1;
    }

    public function create_kv_product_from_wc_product(&$productObj)
    {
        $product = $productObj->get_data();

        $attrs = [];
        $inventories = [];
        $sku = "";

        if ($productObj->get_type() == 'variation') {
            if (!empty($itemMetaData)) {
                foreach ($itemMetaData as $attr) {
                    $attrData = $attr->get_data();
                    $taxonomyData = get_taxonomy($attrData['key']);
                    $attributeName = $taxonomyData->labels->singular_name;
                    $attribute_value_object = get_term_by('slug', $attrData['value'], $attrData['key']);
                    $attrs[] = [
                        'attributeName' => $attributeName,
                        'attributeValue' => $attribute_value_object->name,
                    ];
                }
            }
        }

        $inventories[] = [
            'branchId' => $this->orderBranch,
            'onHand' => $productObj->get_stock_quantity(),
            'minQuantity' => $productObj->get_low_stock_amount(),
            'cost' => (float) $product['price'],
            'reserved' => 0,
        ];

        if (empty($product['sku'])) {
            $sku = 'Website' . strtoupper(time());
            $productObj->set_sku($sku);
            $productObj->save();
        } else {
            $sku = 'Website' . $product['sku'];
        }

        if ($productObj->get_type() == 'variation') {
            $productParent = wc_get_product($productObj->get_parent_id());
            $productName = $productParent->get_name();
        } else {
            $productName = $product['name'];
        }

        // images
        $images = [];
        $imageProducts = $productObj->get_gallery_image_ids();
        if ($productObj->get_image_id()) {
            array_unshift($imageProducts, $productObj->get_image_id());
        }

        foreach ($imageProducts as $attachment_id) {
            // Display the image URL
            $images[] = wp_get_attachment_url($attachment_id);
        }

        $data = [
            'code' => $sku,
            'name' => $productName . " - " . "Website",
            'categoryId' => $this->get_website_category_on_kv($productObj), //?? Don't know how i set the fucking category
            'allowsSale' => true,
            'description' => strip_tags($product['description']),
            'hasVariants' => count($attrs) ? true : false,
            'attributes' => $attrs,
            'inventories' => $inventories,
            'images' => $images,
            'basePrice' => (float) $product['price'],
            'weight' => 5,
        ];

        $response = $this->kiotvietApi->request('POST', 'https://public.kiotapi.com/products', $data, 'json');

        $data = [
            'product_id' => $product['id'],
            'product_kv_id' => $response['data']['id'],
            'data_raw' => json_encode($response['data']),
            'retailer' => $this->retailer,
            'created_at' => kiotviet_sync_get_current_time(),
        ];
        $format = array('%d', '%d', '%s', '%s', '%d');

        $this->wpdb->insert("{$this->wpdb->prefix}kiotviet_sync_products", $data, $format);

        if ($response['status'] != 'error') {
            return $response['data']['id'];
        }
        return -1;
    }

    public function getSettingManagerCustomer()
    {
        $managerCustomerByBranch = true;
        $settings = $this->kiotvietApi->request('GET', 'https://public.kiotapi.com/settings');
        if ($settings['status'] == 'success') {
            $managerCustomerByBranch = $settings['data']['ManagerCustomerByBranch'];
        }

        return $managerCustomerByBranch;
    }

    public function get_customer_id_from_contact_number($contactNumber)
    {
        $contactNumber = str_replace(' ', '', $contactNumber);
        $response = $this->kiotvietApi->request('GET', 'https://public.kiotapi.com/customers', [
            'contactNumber' => $contactNumber,
        ]);

        $managerCustomerByBranch = $this->getSettingManagerCustomer();
        $customers = $response['data']['data'];
        $customerFilter = [];


        $isValidContact = strlen($contactNumber) >= 9;
        if ($isValidContact && is_array($customers) && count($customers) == 1) {
            // Edge case: KV không trả về  contactNumber 
            $customerFilter = $customers;
        } else if ($customers) {
            foreach ((array)$customers as $customer) {
                if (str_replace(' ', '', $customer['contactNumber']) == $contactNumber) {
                    $customerFilter[] = $customer;
                }
            }
        }

        if ($customerFilter) {
            if (!$managerCustomerByBranch) {
                $customer = $customerFilter[0];
                return $customer['id'];
            } else {
                foreach ($customerFilter as $item) {
                    if ($item['branchId'] == $this->orderBranch) {
                        return $item['id'];
                    }
                }
            }
        }

        return -1;
    }

    public function get_website_category_on_kv($productObj)
    {
        $categoryIds = $productObj->get_category_ids();

        if ($categoryIds) {
            $wcCategoryAsync = $this->wpdb->get_row("SELECT `category_kv_id` FROM {$this->wpdb->prefix}kiotviet_sync_categories WHERE `retailer` = '" . $this->retailer . "' AND `category_id` = '" . $categoryIds[count($categoryIds) - 1] . "'", ARRAY_A);

            if ($wcCategoryAsync) {
                return $wcCategoryAsync['category_kv_id'];
            } else {
                return $this->getCategoryOther();
            }
        } else {
            return $this->getCategoryOther();
        }

        return -1;
    }

    public function getCategoryOther()
    {
        $response = $this->kiotvietApi->request('GET', 'https://public.kiotapi.com/categories', ['pageSize' => 100]);

        if (!empty($response['data']['data'])) {
            foreach ((array)$response['data']['data'] as $item) {
                if ($item['categoryName'] == "Khác") {
                    return $item['categoryId'];
                }
            }
        }

        $response = $this->kiotvietApi->request('POST', 'https://public.kiotapi.com/categories', [
            'categoryName' => 'Khác',
        ]);

        if ($response['data']['data']) {
            return $response['data']['data']['categoryId'];
        }
    }

    public function create_kv_customer($billing)
    {
        $data = [
            'code' => 'KH' . strtoupper(substr(md5(time()), rand(0, strlen(md5(time())) - 5), 9)),
            'name' => $billing['first_name'] . ' ' . $billing['last_name'],
            'gender' => true,
            'branchId' => $this->orderBranch,
            'contactNumber' => $billing['phone'],
            'address' => $billing['address_1'],
            'comments' => 'Khách hàng tạo từ website, email: ' . $billing['email'],
        ];

        $response = $this->kiotvietApi->request('POST', 'https://public.kiotapi.com/customers', $data);

        if ($response['status'] != 'error') {
            return $response['data']['data']['id'];
        }
        return -1;
    }
}
