"use strict";
module.exports = {
	NODE_ENV: '"production"',
	ACTION_WP: '"kiotviet_sync_api"',
	// Auth
	ACTION_WP_KV: '"kiotviet_sync_service_auth"',
	ACTION_WP_GET_TOKEN: '"kiotviet_sync_get_token"',
	ACTION_WP_SAVE_CONFIG_RETAILER: '"kiotviet_sync_save_config_retailer"',

	// Config
	ACTION_WP_GET_CONFIG: '"kiotviet_sync_get_config"',
	ACTION_WP_SAVE_CONFIG: '"kiotviet_sync_save_config"',
	ACTION_WP_REMOVE_CONFIG: '"kiotviet_sync_remove_config"',

	// Webhook
	ACTION_WP_REGISTER_WEBHOOK: '"kiotviet_sync_register_webhook"',
	ACTION_WP_REMOVE_WEBHOOK: '"kiotviet_sync_remove_webhook"',

	// Log
	ACTION_WP_REMOVE_LOG: '"kiotviet_sync_remove_log"',

	// Product
	ACTION_WP_ADD_PRODUCT: '"kiotviet_sync_add_product"',
	ACTION_WP_UPDATE_PRODUCT: '"kiotviet_sync_update_product"',
	ACTION_WP_GET_PRODUCT_MAP: '"kiotviet_sync_get_product_map"',
	ACTION_WP_DELETE_PRODUCT: '"kiotviet_sync_delete_product"',
	ACTION_WP_UPDATE_PRODUCT_PRICE: '"kiotviet_sync_update_product_price"',
	ACTION_WP_UPDATE_PRODUCT_STOCK: '"kiotviet_sync_update_product_stock"',
	ACTION_WP_UPDATE_PRODUCT_STATUS: '"kiotviet_sync_update_status"',

	// Category
	ACTION_WP_ADD_CATEGORY: '"kiotviet_sync_add_category"',
	ACTION_WP_DELETE_SYNC_CATEGORY: '"kiotviet_sync_delete_sync_category"',
	ACTION_WP_DELETE_CATEGORY: '"kiotviet_sync_delete_category"',
	ACTION_WP_UPDATE_CATEGORY: '"kiotviet_sync_update_category"',

	// Price Book
	ACTION_WP_GET_PRICEBOOK: '"kiotviet_sync_get_pricebook"',
	ACTION_WP_SAVE_PRICEBOOK: '"kiotviet_sync_save_pricebook"',

	// Branch
	ACTION_WP_GET_BRANCH: '"kiotviet_sync_get_branch"',
	ACTION_WP_SAVE_BRANCH: '"kiotviet_sync_save_branch"',

	KIOTVIET_ENPOINT: '"https://public.kiotapi.com"',
	VERSION_INDEXDB: 9
};
