<?php
/**
 * Plugin Name: xdebug
 */

add_action('admin_menu', 'add_php_info_page');

function add_php_info_page() {
    add_submenu_page('tools.php', 'xdebug info', 'xdebug info', 'manage_options', 'xdebug-info', 'enable_xdebug');
}

function enable_xdebug() {
    if (function_exists('xdebug_info')) {
        xdebug_info();
    } else {
        echo 'enable xdebug';
    }
}
