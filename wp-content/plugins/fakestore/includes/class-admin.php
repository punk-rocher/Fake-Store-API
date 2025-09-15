<?php
if (!defined('ABSPATH'))
    exit;

class FSYNC_Admin
{

    public function __construct()
    {
        // Add menu page
        add_action('admin_menu', [$this, 'admin_menu']);
        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        // AJAX handlers
        add_action('wp_ajax_fsync_sync_products', [$this, 'ajax_sync_products']);
    }

    // Add menu
    public function admin_menu()
    {
        add_menu_page(
            'FakeStore Sync',          // Page title
            'FakeStore Sync',          // Menu title
            'manage_options',          // Capability
            'fsync',                   // Menu slug
            [$this, 'admin_page'],     // Callback
            'dashicons-cart',          // Icon
            56                         // Position
        );
    }

    // Enqueue JS
    public function admin_scripts($hook)
    {
        if ($hook != 'toplevel_page_fsync')
            return;

        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'fsync-admin',
            FSYNC_URL . 'assets/js/admin.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('fsync-admin', 'fsync_vars', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fsync_nonce')
        ]);
    }

    // Admin page HTML
    public function admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sorry, you are not allowed to access this page.');
        }

        $api_url = esc_attr(get_option('fsync_api_url', 'https://fakestoreapi.com'));
        $last_sync = get_option('fsync_last_sync', 'Never');
        ?>
        <div class="wrap">
            <h1>FakeStore WooCommerce Sync</h1>

            <h2>Settings</h2>
            <p>API URL: <input type="text" value="<?php echo $api_url; ?>" disabled></p>
            <p>Last Sync: <strong id="fsync-last-sync"><?php echo $last_sync ? $last_sync : 'Never'; ?></strong></p>

            <h2>Manual Sync</h2>
            <button id="fsync-sync-btn" class="button button-primary">Sync Products</button>
            <div id="fsync-sync-log" style="margin-top:10px;"></div>
        </div>
        <?php
    }

    // AJAX: Sync products
    public function ajax_sync_products()
    {
        check_ajax_referer('fsync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }

        $engine = new FSYNC_Sync();
        $result = $engine->import_products(200); // import max 200 products

        // Update last sync timestamp
        update_option('fsync_last_sync', date('Y-m-d H:i:s'));

        wp_send_json_success($result);
    }
}
