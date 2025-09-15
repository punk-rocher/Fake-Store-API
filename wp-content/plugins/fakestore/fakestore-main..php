<?php
/**
 * Plugin Name: Fakestore 
 * Plugin URI: #
 * Description: Build a WordPress plugin that synchronises products between FakeStoreAP.
 * Version: 1.0.0
 * Author: Ravindu
 * Text Domain: Fakestore
 */


if (!defined('ABSPATH')) exit; // Prevent direct access

// Plugin constants
define('FSYNC_PATH', plugin_dir_path(__FILE__));
define('FSYNC_URL', plugin_dir_url(__FILE__));


// Activation Hook
register_activation_hook(__FILE__, function () {
    add_option('fsync_api_url', 'https://fakestoreapi.com'); // default API URL
    add_option('fsync_last_sync', ''); // default last sync
});


// Deactivation Hook
register_deactivation_hook(__FILE__, function () {
    // Optional cleanup here
});


// Include required files
require_once FSYNC_PATH . 'includes/class-admin.php';
require_once FSYNC_PATH . 'includes/class-sync.php';


// Initialize plugin
add_action('plugins_loaded', function () {
    new FSYNC_Admin(); // initialize admin page
});
