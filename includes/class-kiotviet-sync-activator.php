<?php
/**
 * Fired during plugin activation
 *
 * @link       opss.com.vn
 * @since      1.0.0
 *
 * @package    Kiotviet_Sync
 * @subpackage Kiotviet_Sync/includes
 */

class Kiotviet_Sync_Activator
{
    public static function activate()
    {
        // create the tables kiotviet sync.
        self::createTable();
    }

    public static function migration()
    {
        global $wpdb;
        $sql = "ALTER TABLE `{$wpdb->prefix}kiotviet_sync_categories` MODIFY COLUMN `data_raw` longtext NOT NULL;";
        $wpdb->query($sql);

        $sql = "ALTER TABLE `{$wpdb->prefix}kiotviet_sync_products` MODIFY COLUMN `data_raw` longtext NOT NULL;";
        $wpdb->query($sql);

        $sql = "ALTER TABLE `{$wpdb->prefix}kiotviet_sync_orders` MODIFY COLUMN `data_raw` longtext NOT NULL;";
        $wpdb->query($sql);

        $sql = "ALTER TABLE `{$wpdb->prefix}kiotviet_sync_logs` MODIFY COLUMN `data` longtext NOT NULL;";
        $wpdb->query($sql);
    }

    public static function createTable()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        global $wpdb;

        $wpdb->hide_errors();

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kiotviet_sync_categories` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`category_id` int NOT NULL,
			`category_kv_id` int NOT NULL,
            `data_raw` longtext NOT NULL,
			`created_at` timestamp NOT NULL,
            `retailer` varchar(255) NOT NULL,
			PRIMARY KEY  (id),
			INDEX `category_id` (`category_id`),
            INDEX `category_kv_id` (`category_kv_id`)
            ) $charset_collate
            ;";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kiotviet_sync_products` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`product_id` int NOT NULL,
			`product_kv_id` int NOT NULL,
            `data_raw` longtext NOT NULL,
			`created_at` timestamp NOT NULL,
            `parent` int DEFAULT 0,
            `retailer` varchar(255) NOT NULL,
            `status` tinyint DEFAULT 1,
			PRIMARY KEY  (id),
            INDEX `product_id` (`product_id`),
            INDEX `product_kv_id` (`product_kv_id`),
            INDEX `parent` (`parent`)
			) $charset_collate;";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kiotviet_sync_orders` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`order_id` int NOT NULL,
			`order_kv_id` int NOT NULL,
            `data_raw` longtext NOT NULL,
			`created_at` timestamp NOT NULL,
			PRIMARY KEY  (id),
            INDEX `order_id` (`order_id`),
            INDEX `order_kv_id` (`order_kv_id`)
			) $charset_collate;";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kiotviet_sync_logs` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `from`  varchar(255) NULL,
                `to`  varchar(255) NULL,
                `body`  varchar(255) NULL,
                `data`  longtext NULL,
                `created_at`  datetime NULL ON UPDATE CURRENT_TIMESTAMP,
                `refer_id` int NOT NULL,
                `type` int NULL,
                PRIMARY KEY (id),
                INDEX `refer_id` (`refer_id`)
                ) $charset_collate;";
                
        dbDelta($sql);

        self::migration();
    }
}
