<?php
class KiotvietWcAttribute
{
    private $wpdb;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function add_attribute($data)
    {
        try {
            // Check permissions.
            if (!current_user_can('manage_product_terms')) {
                return new WP_Error('user_cannot_create_product_attribute', __('Bạn không có quyền tạo thuộc tính sản phẩm', 'woocommerce'), 401);
            }

            if (!isset($data['name'])) {
                $data['name'] = '';
            }

            $data['slug'] = wc_sanitize_taxonomy_name(stripslashes($data['name']));
            // Set attribute type when not sent.
            if (!isset($data['type'])) {
                $data['type'] = 'select';
            }

            // Set order by when not sent.
            if (!isset($data['order_by'])) {
                $data['order_by'] = 'menu_order';
            }

            $validate = $this->validate_attribute_data($data['name'], $data['slug'], $data['type'], $data['order_by'], true);
            if(is_wp_error($validate)){
                return $validate;
            }

            $insert = $this->wpdb->insert(
                $this->wpdb->prefix . 'woocommerce_attribute_taxonomies',
                array(
                    'attribute_label' => $data['name'],
                    'attribute_name' => $data['slug'],
                    'attribute_type' => $data['type'],
                    'attribute_orderby' => $data['order_by'],
                    'attribute_public' => isset($data['has_archives']) && true === $data['has_archives'] ? 1 : 0,
                ),
                array('%s', '%s', '%s', '%s', '%d')
            );

            // Checks for an error in the product creation.
            if (is_wp_error($insert)) {
                return new WP_Error('cannot_create_product_attribute', $insert->get_error_message(), 400);
            }

            $attribute_id = $this->wpdb->insert_id;
            return $attribute_id;
        } catch (WP_Error $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), array('status' => $e->getCode()));
        }
    }

    public function edit_attribute($data)
    {
        try {
            // Check permissions.
            if (!current_user_can('manage_product_terms')) {
                return new WP_Error('user_cannot_edit_product_attribute', __('You do not have permission to edit product attributes', 'woocommerce'), 401);
            }

            if (!isset($data['args']['name'])) {
                $data['args']['name'] = '';
            }

            $data['args']['slug'] = wc_sanitize_taxonomy_name(stripslashes($data['args']['name']));
            // Set attribute type when not sent.
            if (!isset($data['args']['type'])) {
                $data['args']['type'] = 'select';
            }

            // Set order by when not sent.
            if (!isset($data['args']['order_by'])) {
                $data['args']['order_by'] = 'menu_order';
            }

            $validate = $this->validate_attribute_data($data['args']['name'], $data['args']['slug'], $data['args']['type'], $data['args']['order_by'], true);
            if(is_wp_error($validate)){
                return $validate;
            }
            $update = $this->wpdb->update(
                $this->wpdb->prefix . 'woocommerce_attribute_taxonomies',
                array(
                    'attribute_label' => $data['args']['name'],
                    'attribute_name' => $data['args']['slug'],
                ), ['attribute_id' => $data['id']]);

            if (is_wp_error($update)) {
                return new WP_Error('cannot_edit_product_attribute', __('Could not edit the attribute', 'woocommerce'), 400);
            }
            // Checks for an error in the product creation.
            return $update;
        } catch (Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), array('status' => $e->getCode()));
        }
    }

    protected function validate_attribute_data($name, $slug, $type, $order_by, $new_data = true)
    {
        if (empty($name)) {
            return new WP_Error('woocommerce_api_missing_product_attribute_name', sprintf(__('Missing parameter %s', 'woocommerce'), 'name'), 400);
        }

        if (strlen($slug) >= 28) {
            return new WP_Error('woocommerce_api_invalid_product_attribute_slug_too_long', sprintf(__('Slug "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce'), $slug), 400);
        } elseif (wc_check_if_attribute_name_is_reserved($slug)) {
            return new WP_Error('woocommerce_api_invalid_product_attribute_slug_reserved_name', sprintf(__('Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce'), $slug), 400);
        } elseif ($new_data && taxonomy_exists(wc_attribute_taxonomy_name($slug))) {
            return new WP_Error('product_attribute_slug_exits', sprintf(__('Slug "%s" is already in use. Change it, please.', 'woocommerce'), $slug), 400);
        }

        // Validate the attribute type
        if (!in_array(wc_clean($type), array_keys(wc_get_attribute_types()))) {
            return new WP_Error('woocommerce_api_invalid_product_attribute_type', sprintf(__('Invalid product attribute type - the product attribute type must be any of these: %s', 'woocommerce'), implode(', ', array_keys(wc_get_attribute_types()))), 400);
        }

        // Validate the attribute order by
        if (!in_array(wc_clean($order_by), array('menu_order', 'name', 'name_num', 'id'))) {
            return new WP_Error('woocommerce_api_invalid_product_attribute_order_by', sprintf(__('Invalid product attribute order_by type - the product attribute order_by type must be any of these: %s', 'woocommerce'), implode(', ', array('menu_order', 'name', 'name_num', 'id'))), 400);
        }

        return true;
    }
}
