<?php


class KiotvietWcProduct
{
    private $wpdb;
    private $retailer;

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
        $this->retailer = get_option('kiotviet_sync_retailer', "");
    }

    public function check_update_product_by_config(&$product)
    {
        $configProductSync = get_option('kiotviet_sync_product_sync', []);
        if (in_array($this->mapConfigProductSync["name"], $configProductSync)) {
            unset($product['name']);
        }

        if (in_array($this->mapConfigProductSync["category"], $configProductSync)) {
            unset($product['category_ids']);
        }

        if (in_array($this->mapConfigProductSync["images"], $configProductSync)) {
            unset($product['raw_gallery_image_ids'], $product['raw_image_id']);
        }

        if (in_array($this->mapConfigProductSync["description"], $configProductSync)) {
            unset($product['description']);
        }

        if (in_array($this->mapConfigProductSync["regular_price"], $configProductSync)) {
            unset($product['regular_price']);
        }

        if (in_array($this->mapConfigProductSync["sale_price"], $configProductSync)) {
            unset($product['sale_price']);
        }

        if (in_array($this->mapConfigProductSync["stock_quantity"], $configProductSync)) {
            unset($product['stock_quantity']);
        }
    }

    public function import_product($data)
    {
        try {
            // Get product ID from SKU if created during the importation.
            if (empty($data['id']) && !empty($data['sku'])) {
                /*$product_id = $this->get_product_by_sku($data['sku']);*/
                $product_id = wc_get_product_id_by_sku($data['sku']);
                $this->check_update_product_by_config($data);
                if ($product_id) {
                    $data['id'] = $product_id;
                }
            }

            $object = $this->get_product_object($data);

            if ($object->get_status() == "trash") {
                $data['status'] = "trash";
            }

            if (is_wp_error($object)) {
                return $object;
            }

            if ('external' === $object->get_type()) {
                unset($data['manage_stock'], $data['stock_status'], $data['backorders'], $data['low_stock_amount']);
            }

            if ('variation' === $object->get_type()) {
                if (isset($data['status']) && -1 === $data['status']) {
                    $data['status'] = 0; // Variations cannot be drafts - set to private.
                }
            }

            if ('importing' === $object->get_status()) {
                $object->set_status('publish');
                $object->set_slug('');
            }

            $result = $object->set_props(array_diff_key($data, array_flip(array('meta_data', 'raw_image_id', 'raw_gallery_image_ids', 'raw_attributes'))));

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            if ('variation' === $object->get_type()) {
                $this->set_variation_data($object, $data);
            } else {
                $this->set_product_data($object, $data);
            }

            $this->set_image_data($object, $data);
            $this->set_meta_data($object, $data);

            $object = apply_filters('woocommerce_product_import_pre_insert_product_object', $object, $data);

            $object->save();

            // change status when update product trash
            do_action('woocommerce_product_import_inserted_product_object', $object, $data);

            wc_delete_product_transients( $object->get_id() ); // Clear/refresh the variation cache
            return $object->get_id();
        } catch (Exception $e) {
            return new WP_Error('woocommerce_product_importer_error', $e->getMessage(), array('status' => $e->getCode()));
        }
    }

    //    public function get_product_by_sku($sku)
    //    {
    //        global $wpdb;
    //        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='_sku' AND `meta_value`='%s' LIMIT 1", $sku));
    //        if ($product_id) {
    //            return $product_id;
    //        }
    //
    //        return null;
    //    }

    public function parse_categories_field($value)
    {
        if (is_string($value)) {
            $value = array($value);
        }

        $categories = array();

        foreach ($value as $_term) {
            // Check if category exists. Parent must be empty string or null if doesn't exists.
            $term = term_exists($_term, 'product_cat', '');

            if (is_array($term)) {
                $term_id = $term['term_id'];
                // Don't allow users without capabilities to create new categories.
            } elseif (!current_user_can('manage_product_terms')) {
                break;
            } else {
                $term = wp_insert_term($_term, 'product_cat', array());

                if (is_wp_error($term)) {
                    break; // We cannot continue if the term cannot be inserted.
                }

                $term_id = $term['term_id'];
            }
            $categories[] = $term_id;
        }

        return $categories;
    }

    public function parse_tags_field($names)
    {
        if (is_string($names)) {
            $names = array($names);
        }

        $tags = array();

        foreach ($names as $name) {
            $term = get_term_by('name', $name, 'product_tag');

            if (!$term || is_wp_error($term)) {
                $term = (object)wp_insert_term($name, 'product_tag');
            }

            if (!is_wp_error($term)) {
                $tags[] = $term->term_id;
            }
        }

        return $tags;
    }

    protected function get_product_object($data)
    {
        $id = isset($data['id']) ? absint($data['id']) : 0;

        // Type is the most important part here because we need to be using the correct class and methods.
        if (isset($data['type'])) {
            $types = array_keys(wc_get_product_types());
            $types[] = 'variation';

            if (!in_array($data['type'], $types, true)) {
                return new WP_Error('woocommerce_product_importer_invalid_type', __('Invalid product type.', 'woocommerce'), array('status' => 401));
            }

            $classname = WC_Product_Factory::get_classname_from_product_type($data['type']);

            if (!class_exists($classname)) {
                $classname = 'WC_Product_Simple';
            }

            $product = new $classname($id);
        } elseif (!empty($data['id'])) {
            $product = wc_get_product($id);

            if (!$product) {
                return new WP_Error(
                    'woocommerce_product_csv_importer_invalid_id',
                    /* translators: %d: product ID */
                    sprintf(__('Invalid product ID %d.', 'woocommerce'), $id),
                    array(
                        'id' => $id,
                        'status' => 401,
                    )
                );
            }
        } else {
            $product = new WC_Product_Simple($id);
        }

        return apply_filters('woocommerce_product_import_get_product_object', $product, $data);
    }

    public function set_variation_data(&$variation, $data)
    {
        $parent = false;

        // Check if parent exist.
        if (isset($data['parent_id'])) {
            $parent = wc_get_product($data['parent_id']);
            if ($parent) {
                $variation->set_parent_id($parent->get_id());
            }
        }

        // Stop if parent does not exists.
        if (!$parent) {
            return new WP_Error('woocommerce_product_importer_missing_variation_parent_id', __('Variation cannot be imported: Missing parent ID or parent does not exist yet.', 'woocommerce'), array('status' => 401));
        }
        // Stop if parent is a product variation.
        if ($parent->is_type('variation')) {
            return new WP_Error('woocommerce_product_importer_parent_set_as_variation', __('Variation cannot be imported: Parent product cannot be a product variation', 'woocommerce'), array('status' => 401));
        }

        if (isset($data['raw_attributes'])) {
            $attributes = array();
            $parent_attributes = $this->get_variation_parent_attributes($data['raw_attributes'], $parent);

            foreach ($data['raw_attributes'] as $attribute) {
                $attribute_id = 0;

                // Get ID if is a global attribute.
                if (!empty($attribute['taxonomy'])) {
                    $attribute_id = $this->get_attribute_taxonomy_id($attribute['name']);
                }

                if ($attribute_id) {
                    $attribute_name = wc_attribute_taxonomy_name_by_id($attribute_id);
                } else {
                    $attribute_name = sanitize_title($attribute['name']);
                }

                if (!isset($parent_attributes[$attribute_name]) || !$parent_attributes[$attribute_name]->get_variation()) {
                    continue;
                }

                $attribute_key = sanitize_title($parent_attributes[$attribute_name]->get_name());
                $attribute_value = isset($attribute['value']) ? current($attribute['value']) : '';

                if ($parent_attributes[$attribute_name]->is_taxonomy()) {
                    // If dealing with a taxonomy, we need to get the slug from the name posted to the API.
                    $term = get_term_by('name', $attribute_value, $attribute_name);

                    if ($term && !is_wp_error($term)) {
                        $attribute_value = $term->slug;
                    } else {
                        $attribute_value = sanitize_title($attribute_value);
                    }
                }

                $attributes[$attribute_key] = $attribute_value;
            }
            $variation->set_attributes($attributes);
            $variation->set_description(null);
        }
    }

    protected function get_variation_parent_attributes($attributes, $parent)
    {
        $parent_attributes = $parent->get_attributes();
        $require_save = false;

        foreach ($attributes as $attribute) {
            $attribute_id = 0;

            // Get ID if is a global attribute.
            if (!empty($attribute['taxonomy'])) {
                $attribute_id = $this->get_attribute_taxonomy_id($attribute['name']);
            }

            if ($attribute_id) {
                $attribute_name = wc_attribute_taxonomy_name_by_id($attribute_id);
            } else {
                $attribute_name = sanitize_title($attribute['name']);
            }

            // Check if attribute handle variations.
            if (isset($parent_attributes[$attribute_name]) && !$parent_attributes[$attribute_name]->get_variation()) {
                // Re-create the attribute to CRUD save and generate again.
                $parent_attributes[$attribute_name] = clone $parent_attributes[$attribute_name];
                $parent_attributes[$attribute_name]->set_variation(1);

                $require_save = true;
            }
        }

        // Save variation attributes.
        if ($require_save) {
            $parent->set_attributes(array_values($parent_attributes));
            $parent->save();
        }

        return $parent_attributes;
    }

    protected function get_attribute_taxonomy_id($raw_name)
    {
        global $wpdb, $wc_product_attributes;

        // These are exported as labels, so convert the label to a name if possible first.
        $attribute_labels = wp_list_pluck(wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name');
        $attribute_name = array_search($raw_name, $attribute_labels, true);

        if (!$attribute_name) {
            $attribute_name = wc_sanitize_taxonomy_name($raw_name);
        }

        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);

        // Get the ID from the name.
        if ($attribute_id) {
            return $attribute_id;
        }

        // If the attribute does not exist, create it.
        $attribute_id = wc_create_attribute(
            array(
                'name' => $raw_name,
                'slug' => $attribute_name,
                'type' => 'select',
                'order_by' => 'menu_order',
                'has_archives' => false,
            )
        );

        if (is_wp_error($attribute_id)) {
            throw new Exception($attribute_id->get_error_message(), 400);
        }

        // Register as taxonomy while importing.
        $taxonomy_name = wc_attribute_taxonomy_name($attribute_name);
        register_taxonomy(
            $taxonomy_name,
            apply_filters('woocommerce_taxonomy_objects_' . $taxonomy_name, array('product')),
            apply_filters(
                'woocommerce_taxonomy_args_' . $taxonomy_name,
                array(
                    'labels' => array(
                        'name' => $raw_name,
                    ),
                    'hierarchical' => true,
                    'show_ui' => false,
                    'query_var' => true,
                    'rewrite' => false,
                )
            )
        );

        // Set product attributes global.
        $wc_product_attributes = array();

        foreach (wc_get_attribute_taxonomies() as $taxonomy) {
            $wc_product_attributes[wc_attribute_taxonomy_name($taxonomy->attribute_name)] = $taxonomy;
        }

        return $attribute_id;
    }

    public function set_product_data(&$product, $data)
    {
        if (isset($data['raw_attributes'])) {
            $attributes = array();
            $default_attributes = array();
            $existing_attributes = $product->get_attributes();

            foreach ($data['raw_attributes'] as $position => $attribute) {
                $attribute_id = 0;

                // Get ID if is a global attribute.
                if (!empty($attribute['taxonomy'])) {
                    $attribute_id = $this->get_attribute_taxonomy_id($attribute['name']);
                }

                // Set attribute visibility.
                if (isset($attribute['visible'])) {
                    $is_visible = $attribute['visible'];
                } else {
                    $is_visible = 1;
                }

                // Get name.
                $attribute_name = $attribute_id ? wc_attribute_taxonomy_name_by_id($attribute_id) : $attribute['name'];

                // Set if is a variation attribute based on existing attributes if possible so updates via CSV do not change this.
                $is_variation = 0;

                if ($existing_attributes) {
                    foreach ($existing_attributes as $existing_attribute) {
                        if ($existing_attribute->get_name() === $attribute_name) {
                            $is_variation = $existing_attribute->get_variation();
                            break;
                        }
                    }
                }

                if ($attribute_id) {
                    if (isset($attribute['value'])) {
                        $options = array_map('wc_sanitize_term_text_based', $attribute['value']);
                        $options = array_filter($options, 'strlen');
                    } else {
                        $options = array();
                    }

                    // Check for default attributes and set "is_variation".
                    if (!empty($attribute['default']) && in_array($attribute['default'], $options, true)) {
                        $default_term = get_term_by('name', $attribute['default'], $attribute_name);

                        if ($default_term && !is_wp_error($default_term)) {
                            $default = $default_term->slug;
                        } else {
                            $default = sanitize_title($attribute['default']);
                        }

                        $default_attributes[$attribute_name] = $default;
                        $is_variation = 1;
                    }

                    if (!empty($options)) {
                        $attribute_object = new WC_Product_Attribute();
                        $attribute_object->set_id($attribute_id);
                        $attribute_object->set_name($attribute_name);
                        $attribute_object->set_options($options);
                        $attribute_object->set_position($position);
                        $attribute_object->set_visible($is_visible);
                        $attribute_object->set_variation($is_variation);
                        $attributes[] = $attribute_object;
                    }
                } elseif (isset($attribute['value'])) {
                    // Check for default attributes and set "is_variation".
                    if (!empty($attribute['default']) && in_array($attribute['default'], $attribute['value'], true)) {
                        $default_attributes[sanitize_title($attribute['name'])] = $attribute['default'];
                        $is_variation = 1;
                    }

                    $attribute_object = new WC_Product_Attribute();
                    $attribute_object->set_name($attribute['name']);
                    $attribute_object->set_options($attribute['value']);
                    $attribute_object->set_position($position);
                    $attribute_object->set_visible($is_visible);
                    $attribute_object->set_variation($is_variation);
                    $attributes[] = $attribute_object;
                }
            }

            $configProductSync = get_option('kiotviet_sync_product_sync', []);
            if (!in_array($this->mapConfigProductSync["attributes"], $configProductSync)) {
                $product->set_attributes($attributes);
            }

            // Set variable default attributes.
            if ($product->is_type('variable')) {
                $product->set_default_attributes($default_attributes);
            }
        }
    }

    //  Tuyenvv 06/08/2020
    protected function getImageFromUrl($url)
    {
        if (ini_get('allow_url_fopen')) {
            return @file_get_contents($url);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);
        curl_close($ch);

        return $raw;
    }

    protected function create_media($url, $key = 0)
    {
        if ($url) {
            $wordpress_upload_dir = wp_upload_dir();
            $filename_from_url = parse_url($url);
            $tmp = pathinfo($filename_from_url['path']);
            $image_type = array_key_exists('extension', $tmp) ? $tmp['extension'] : 'jpg';
            $file_name = 'kiotviet_' . md5($url) . '.' . $image_type;

            $new_file_mime = 'image/jpeg';
            $new_file_path = $wordpress_upload_dir['path'] . '/' . $file_name;

            if (!file_exists($new_file_path)) {
                $data = $this->getImageFromUrl($url);
                if (!$data) {
                    return '';
                }
                file_put_contents($new_file_path, $data);
                $upload_id = wp_insert_attachment(array(
                    'guid' => $new_file_path,
                    'post_mime_type' => $new_file_mime,
                    'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                    'post_content' => '',
                    'post_status' => 'inherit',
                ), $new_file_path);

                if (!function_exists('wp_generate_attachment_metadata')) {
                    // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                }

                // Generate the metadata for the attachment, and update the database record.
                $attach_data = wp_generate_attachment_metadata($upload_id, $new_file_path);
                wp_update_attachment_metadata($upload_id, $attach_data);
            }

            return $file_name;
        }

        return '';
    }

    protected function set_image_data(&$product, $data)
    {
        if (!in_array($this->mapConfigProductSync['images'], get_option('kiotviet_sync_product_sync', []))) {
            $product->set_image_id(0);
            $product->set_gallery_image_ids(array());
        }
        // Image URLs need converting to IDs before inserting.
        if (isset($data['raw_image_id'])) {
            $data['raw_image_id'] = $this->create_media($data['raw_image_id']);
            $product->set_image_id($this->get_attachment_id_from_url($data['raw_image_id'], $product->get_id()));
        }

        // Gallery image URLs need converting to IDs before inserting.
        if (isset($data['raw_gallery_image_ids'])) {
            $gallery_image_ids = array();

            foreach ($data['raw_gallery_image_ids'] as $key => $image_id) {
                $image_id = $this->create_media($image_id, $key + 1);
                $gallery_image_ids[] = $this->get_attachment_id_from_url($image_id, $product->get_id());
            }
            $product->set_gallery_image_ids($gallery_image_ids);
        }
    }

    protected function set_meta_data(&$product, $data)
    {
        if (isset($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                $product->update_meta_data($meta['key'], $meta['value']);
            }
        }
    }

    protected function get_attachment_id_from_url($url, $product_id)
    {
        if (empty($url)) {
            return 0;
        }

        $id = 0;
        $upload_dir = wp_upload_dir(null, false);
        $base_url = $upload_dir['baseurl'] . '/';

        // Check first if attachment is inside the WordPress uploads directory, or we're given a filename only.
        if (false !== strpos($url, $base_url) || false === strpos($url, '://')) {
            // Search for yyyy/mm/slug.extension or slug.extension - remove the base URL.
            $file = str_replace($base_url, '', $url);
            $explodeFile = explode('.',$file);

            $args = array(
                'post_type' => 'attachment',
                'post_status' => 'any',
                'fields' => 'ids',
                'meta_query' => array( // @codingStandardsIgnoreLine.
                    'relation' => 'OR',
                    array(
                        'key' => '_wp_attached_file',
                        'value' => '^' . isset($explodeFile[0]) ? $explodeFile[0] : $file,
                        'compare' => 'REGEXP',
                    ),
                    array(
                        'key' => '_wp_attached_file',
                        'value' => '/' . isset($explodeFile[0]) ? $explodeFile[0] : $file,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => '_wc_attachment_source',
                        'value' => '/' . isset($explodeFile[0]) ? $explodeFile[0] : $file,
                        'compare' => 'LIKE',
                    ),
                ),
            );
        } else {
            // This is an external URL, so compare to source.
            $args = array(
                'post_type' => 'attachment',
                'post_status' => 'any',
                'fields' => 'ids',
                'meta_query' => array( // @codingStandardsIgnoreLine.
                    array(
                        'value' => $url,
                        'key' => '_wc_attachment_source',
                    ),
                ),
            );
        }

        $ids = get_posts($args); // @codingStandardsIgnoreLine.

        if ($ids) {
            $id = current($ids);
        }
        $testUpload = false;
        // Upload if attachment does not exists.
        if (!$id && stristr($url, '://')) {
            $testUpload = true;
            $upload = wc_rest_upload_image_from_url($url);

            if (is_wp_error($upload)) {
                throw new Exception($upload->get_error_message(), 400);
            }

            $id = wc_rest_set_uploaded_image_as_attachment($upload, $product_id);

            if (!wp_attachment_is_image($id)) {
                /* translators: %s: image URL */
                throw new Exception(sprintf(__('Not able to attach "%s".', 'woocommerce'), $url), 400);
            }

            // Save attachment source for future reference.
            update_post_meta($id, '_wc_attachment_source', $url);
        }

        if (!$id) {
            /* translators: %s: image URL */
            throw new Exception(sprintf(__('Unable to use image "%s".', 'woocommerce'), $url), 400);
        }

        return $id;
    }

    protected function delete_product($id, $force = false)
    {
        $product = wc_get_product($id);

        // If we're forcing, then delete permanently.
        if ($force) {
            if ($product->is_type('variable')) {
                foreach ($product->get_children() as $child_id) {
                    $child = wc_get_product($child_id);
                    if (!empty($child)) {
                        $child->delete(true);
                    }
                }
            } else {
                // For other product types, if the product has children, remove the relationship.
                foreach ($product->get_children() as $child_id) {
                    $child = wc_get_product($child_id);
                    if (!empty($child)) {
                        $child->set_parent_id(0);
                        $child->save();
                    }
                }
            }

            $product->delete(true);
            $result = !($product->get_id() > 0);
        } else {
            $product->delete();
            $result = 'trash' === $product->get_status();
        }

        if (!$result) {
            return new WP_Error('woocommerce_api_cannot_delete_product', sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'), array('status' => 500));
        }

        // Delete parent product transients.
        if ($parent_id = wp_get_post_parent_id($id)) {
            wc_delete_product_transients($parent_id); // Clear/refresh the variation cache
        }

        if ($force) {
            return array('message' => sprintf(__('Permanently deleted %s', 'woocommerce'), 'product'));
        } else {
            return array('message' => sprintf(__('Deleted %s', 'woocommerce'), 'product'));
        }
    }

    public function check_attribute_parent($wcParentProduct, $attributes)
    {
        $attributesParent = [];
        foreach ($wcParentProduct->get_attributes() as $item) {
            // attribute taxonomy
            if (preg_match("/^pa_*/", $item['name'], $matches)) {
                $values = [];
                foreach ($item['options'] as $option) {
                    $values[] = get_term_by('id', $option, $item['name'])->name;
                }
                $attributesParent[] = [
                    'name' => wc_attribute_label($item['name']),
                    'value' => $values,
                    'visible' => true,
                    'taxonomy' => true,
                ];
            } else {
                $attributesParent[] = [
                    'name' => $item['name'],
                    'value' => $item['options'],
                    'visible' => true,
                    'taxonomy' => true,
                ];
            }
        }

        foreach ($attributesParent as $key => $attributeParent) {
            $checkExits = false;
            foreach ($attributes as $attribute) {
                if (strcasecmp($attributeParent['name'], $attribute['name']) == 0) {
                    //value attribute not exits in value attribute parent
                    if (!in_array($attribute['value'][0], $attributeParent['value'])) {
                        $attributesParent[$key]['value'][] = $attribute['value'][0];
                    }
                    $checkExits = true;
                }
            }
            if (!$checkExits) {
                // Remove attribute
                unset($attributesParent[$key]);
            }
        }

        // add new attribute
        $attributesAdd = [];
        foreach ($attributes as $attribute) {
            $checkExits = false;
            foreach ($attributesParent as $key => $attributeParent) {
                if (strcasecmp($attributeParent['name'], $attribute['name']) == 0) {
                    $checkExits = true;
                }
            }

            if (!$checkExits) {
                $attributesAdd[] = [
                    'name' => $attribute['name'],
                    'value' => [$attribute['value'][0]],
                    'visible' => true,
                    'taxonomy' => true,
                ];
            }
        }

        $attributesParent = array_merge($attributesParent, $attributesAdd);

        $this->set_product_data($wcParentProduct, [
            'raw_attributes' => $attributesParent
        ]);

        $wcParentProduct->save();
    }

    public function transformProductMaster($product)
    {
        $product['id'] = 0;
        $product['type'] = "variable";
        $product['sku'] = $product['sku'] . "Master";
        $product['name'] = $product['nameMaster'];
        $product['sale_price'] = "";
        $product['regular_price'] = "";
        $product['master_product_id'] = $product['kv_id'];
        return $product;
    }

    public function transformProduct($value, $type, $id = 0, $regularPrice = 0, $salePrice = 0, $branchStock = 0)
    {
        $regular_price = $this->getRegularPrice($value, $regularPrice);
        $sale_price = $this->getSalePrice($value, $salePrice);
        $stock = $this->getStock($value, $branchStock);
        $low_stock = $this->getLowStock($value, $branchStock);
        $data = [
            'id' => $id,
            'master_product_id' => !empty($value->MasterProductId) ? $value->MasterProductId : 0,
            'kv_id' => $value->Id,
            'data_raw' => json_encode($value),
            'type' => $type,
            'sku' => $value->Code,
            'nameMaster' => $value->Name,
            'name' => $value->Name,
            'description' => !empty($value->Description) ? $value->Description : "",
            'stock_quantity' => $stock,
            'sale_price' => $sale_price,
            'regular_price' => $regular_price,
            'category_kv' => $value->CategoryId,
            'manage_stock' => $value->Type == 3 ? false : true,
            'weight' => !empty($value->Weight) ? $value->Weight : 0,
            'low_stock_amount' => $low_stock,
        ];

        // set image
        $this->getImages($data, $value->Images);

        // set attribute
        if (!empty($value->Attributes)) {
            $data['raw_attributes'] = $this->getAttributes($value);
        } else {
            $data['raw_attributes'] = [];
        }

        // // set time sale
        if ($sale_price !== "") {
            $priceBookSales = [];
            foreach ($value->PriceBooks as $item) {
                if ($value->PriceBookId == $salePrice) {
                    $priceBookSales = $item;
                }
            }

            if (count($priceBookSales)) {
                $startDate = strtotime($priceBookSales->StartDate);
                $endDate = strtotime($priceBookSales->EndDate);
                $data['date_on_sale_from'] = $startDate;
                $data['date_on_sale_to'] = $endDate;
            }
        }

        return $data;
    }

    public function getAttributes($product)
    {
        $attributes = [];
        if (!empty($product->Attributes)) {
            foreach ($product->Attributes as $item) {
                $attributes[] = [
                    "name" => $item->AttributeName,
                    "value" => [$item->AttributeValue],
                    "visible" => true,
                    "taxonomy" => true,
                ];
            }
        }

        return $attributes;
    }

    public function getRegularPrice($data, $regularPrice)
    {
        $regular_price = 0;
        if (!$regularPrice || $regularPrice === -1) {
            $regular_price = $data->BasePrice;
        } else {
            if (!empty($data->PriceBooks)) {
                foreach ($data->PriceBooks as $item) {
                    if ($item->PriceBookId == $regularPrice) {
                        $regular_price = $item;
                    }
                }

                $regular_price = !empty($regular_price) ? $regular_price->Price : $data->BasePrice;
            } else {
                $regular_price = $data->BasePrice;
            }
        }

        if(!$regular_price) {
            $regular_price = $data->BasePrice;
        }

        return $regular_price;
    }

    public function getSalePrice($data, $salePrice)
    {
        $sale_price = "";
        if (!$salePrice) {
            $sale_price = "";
        } elseif ($salePrice === -1) {
            $sale_price = $data->BasePrice;
        } else {
            if (!empty($data->PriceBooks)) {
                foreach ($data->PriceBooks as $item) {
                    if ($item->PriceBookId == $salePrice) {
                        $sale_price = $item;
                    }
                };

                $sale_price = !empty($sale_price) ? $sale_price->Price : "";
            } else {
                $sale_price = "";
            }
        }

        if(!$sale_price) {
            $sale_price = $data->BasePrice;
        }


        return $sale_price;
    }

    public function getStock($data, $branchStock)
    {
        $stock = 0;
        if (!$branchStock) {
            $stock = 0;
        } else {
            if (!empty($data->Inventories)) {
                foreach ($data->Inventories as $item) {
                    if ($item->BranchId == $branchStock) {
                        $stock = $item;
                    }
                };
                $stock = !empty($stock) ? $stock->OnHand - $stock->Reserved : 0;
            } else {
                $stock = 0;
            }
        }

        return $stock;
    }

    public function getLowStock($data, $branchStock)
    {
        $stock = 0;
        if (!$branchStock) {
            $stock = 0;
        } else {
            if (!empty($data->Inventories)) {
                foreach ($data->Inventories as $item) {
                    if ($item->BranchId == $branchStock) {
                        $stock = $item;
                    }
                };
                $stock = !empty($stock) ? $stock->MinQuantity : 0;
            } else {
                $stock = 0;
            }
        }

        return $stock;
    }

    public function getImages(&$data, $images)
    {
        if (!empty($images)) {
            if (count($images) == 1) {
                $data['raw_image_id'] = $images[0];
            }

            if (count($images) > 1) {
                $data['raw_image_id'] = $images[0];
                $rawGalleryImageIds = [];
                foreach ($images as $key => $item) {
                    if ($key > 0) {
                        $rawGalleryImageIds[] = $item;
                    }
                }
                $data['raw_gallery_image_ids'] = $rawGalleryImageIds;
            }
        } else {
            $data['raw_image_id'] = "";
            $data['raw_gallery_image_ids'] = [];
        }
    }

    public function updateStockProductParent($wcParentProduct)
    {
        $productChilds = $wcParentProduct->get_children();
        $stock = 0;
        foreach ($productChilds as $productChild) {
            $productChild = wc_get_product($productChild);
            $stock += $productChild->get_stock_quantity();
        }
        $stock = $stock < 0 ? 0 : floor($stock);
        $wcParentProduct->set_stock_quantity($stock);
        $wcParentProduct->save();
    }

    public function insertProductMap($product)
    {
        $productSync = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `product_id` = " . $product['product_id'] . " AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        if (!$productSync) {
            $this->wpdb->insert($this->wpdb->prefix . "kiotviet_sync_products", $product);
        } else {
            $kv_updatebysku = get_option('kv_updatebysku');
            if($kv_updatebysku == '1') {
                $update = [
                    'product_kv_id' => $product['product_kv_id'],
                    'status' => 1
                ];
            } else {
                $update = [
                    'status' => 1
                ];
            }

            $this->wpdb->update($this->wpdb->prefix . "kiotviet_sync_products", $update, array("id" => $productSync['id']));
        }
    }

    public function productSimple($product)
    {
        $productSimple = $this->import_product($product);
        if (!is_wp_error($productSimple)) {
            kv_sync_log('KiotViet', 'Website', 'Tạo sản phẩm thành công' . ' Mã sản phẩm: #' . $productSimple, json_encode($product), 2, $productSimple);
            $this->insertProductMap([
                'product_id' => $productSimple,
                'product_kv_id' => $product['kv_id'],
                'data_raw' => $product['data_raw'],
                'parent' => 0,
                'retailer' => $this->retailer,
                'created_at' => kiotviet_sync_get_current_time(),
                'status' => 1
            ]);
        } else {
            kv_sync_log('KiotViet', 'WebSite', "productSimple: {$productSimple->get_error_message()}", json_encode($product), 2, 0);
        }

        return $productSimple;
    }

    public function productVariable($product)
    {
        $parentId = $this->import_product($product);
        if (!is_wp_error($parentId)) {
            kv_sync_log('KiotViet', 'Website', 'Tạo sản phẩm thành công' . ' Mã sản phẩm: #' . $parentId, json_encode($product), 2, $parentId);
            $this->insertProductMap([
                'product_id' => $parentId,
                'product_kv_id' => 0,
                'data_raw' => $product["data_raw"],
                'parent' => $product['master_product_id'],
                'retailer' => $this->retailer,
                'created_at' => kiotviet_sync_get_current_time(),
                'status' => 1
            ]);
        } else {
            kv_sync_log('KiotViet', 'WebSite', "productVariable: {$parentId->get_error_message()}", json_encode($product), 2, 0);
        }

        return $parentId;
    }

    public function productVariation($product)
    {
        $productSync = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `parent` = " . $product['master_product_id'] . " AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        $productVariant = [];
        if ($productSync) {
            $product['parent_id'] = $productSync['product_id'];
            $productVariant = $this->import_product($product);
            if (!is_wp_error($productVariant)) {
                $this->insertProductMap([
                    'product_id' => $productVariant,
                    'product_kv_id' => $product['kv_id'],
                    'data_raw' => $product['data_raw'],
                    'parent' => 0,
                    'retailer' => $this->retailer,
                    'status' => $productSync['status'],
                    'created_at' => kiotviet_sync_get_current_time(),
                ]);
            }
        }

        return $productVariant;
    }

    public function getProductParentIdByKv($masterProductId)
    {
        $parentId = 0;
        $productSync = $this->wpdb->get_row("SELECT `product_id` FROM {$this->wpdb->prefix}kiotviet_sync_products WHERE `parent` = " . $masterProductId . " AND `retailer` = '" . $this->retailer . "'", ARRAY_A);
        if ($productSync) {
            $parentId = $productSync['product_id'];
        }
        return $parentId;
    }
}
