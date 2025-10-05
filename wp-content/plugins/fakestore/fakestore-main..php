<?php
/**
 * Plugin Name: Fakestore
 * Plugin URI: #
 * Description: WordPress plugin that synchronises products between FakeStoreAPI and WooCommerce.
 * Version: 1.0.3
 * Author: Ravindu
 * Text Domain: Fakestore
 */

if (!defined('ABSPATH'))
    exit;

// Plugin constants
define('FSYNC_PATH', plugin_dir_path(__FILE__));
define('FSYNC_URL', plugin_dir_url(__FILE__));

// Activation Hook
register_activation_hook(__FILE__, function () {
    add_option('fsync_api_url', 'https://fakestoreapi.com');
    add_option('fsync_last_sync', '');
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function () {
    delete_option('fsync_api_url');
    delete_option('fsync_last_sync');
});

// Include required files
require_once FSYNC_PATH . 'includes/class-admin.php';
require_once FSYNC_PATH . 'includes/class-sync.php';

// Initialize plugin
add_action('plugins_loaded', function () {
    new FSYNC_Admin();
});
