<?php
if (!defined('ABSPATH'))
    exit;

class FSYNC_Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        add_action('wp_ajax_fsync_sync_products', [$this, 'ajax_sync_products']);
    }

    public function admin_menu()
    {
        add_menu_page(
            'FakeStore Sync',
            'FakeStore Sync',
            'manage_options',
            'fsync',
            [$this, 'admin_page'],
            'dashicons-cart',
            56
        );
    }

    public function admin_scripts($hook)
    {
        if ($hook != 'toplevel_page_fsync')
            return;

        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'fsync-admin',
            FSYNC_URL . 'assets/js/admin.js',
            ['jquery'],
            '1.2',
            true
        );

        // Enqueue plugin styles
        wp_enqueue_style(
            'fsync-admin-style',
            FSYNC_URL . 'assets/css/plugin-styles.css',
            [],
            '1.0'
        );

        wp_localize_script('fsync-admin', 'fsync_vars', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fsync_nonce')
        ]);
    }


    public function admin_page()
    {
        $api_url = esc_attr(get_option('fsync_api_url', 'https://fakestoreapi.com'));
        $last_sync = get_option('fsync_last_sync', 'Never');
        ?>
        <div class="wrap">
            <h1>FakeStore WooCommerce Sync</h1>
            <h2>Settings</h2>
            <p>API URL: <input type="text" value="<?php echo $api_url; ?>" disabled></p>
            <p>Last Sync: <strong id="fsync-last-sync"><?php echo $last_sync ?: 'Never'; ?></strong></p>
            <h2>Manual Sync</h2>
            <button id="fsync-sync-btn" class="button button-primary">Sync Products</button>
            <div id="fsync-sync-log" style="margin-top:10px;"></div>
            <div id="fsync-results" style="margin-top:20px;"></div>
        </div>
        <?php
    }

    public function ajax_sync_products()
    {
        check_ajax_referer('fsync_nonce', 'nonce');

        if (!current_user_can('manage_options'))
            wp_send_json_error('No permission');

        $engine = new FSYNC_Sync();
        $result = $engine->import_products(200);
        update_option('fsync_last_sync', date('Y-m-d H:i:s'));

        // Display results table
        ob_start();
        echo '<h2>Change Summary</h2>';
        echo '<table class="widefat striped">';
        echo '<thead><tr>
            <th>Product ID</th>
            <th>Image</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Description</th>
            <th>Changes</th>
            <th>Action</th>
            <th>Old Price</th>
            <th>New Price</th>
            <th>Status</th>
        </tr></thead><tbody>';

        foreach ($result['table'] as $row) {
            // Get changes
            $changes = [];
            if (isset($row['old_price'], $row['new_price']) && $row['old_price'] != $row['new_price'])
                $changes[] = 'Price';
            if (isset($row['title_changed']) && $row['title_changed'])
                $changes[] = 'Title';
            if (isset($row['category_changed']) && $row['category_changed'])
                $changes[] = 'Category';
            $changes_text = !empty($changes) ? implode(', ', $changes) : '-';

            // Table display
            $desc = !empty($row['description']) ? wp_trim_words($row['description'], 20, '...') : '';

            echo '<tr>';
            echo '<td>' . esc_html($row['id']) . '</td>';
            echo '<td><img src="' . esc_url($row['image']) . '" width="50" height="50" style="object-fit:cover;border-radius:4px;"></td>';
            echo '<td>' . esc_html($row['title']) . '</td>';
            echo '<td>' . esc_html($row['category']) . '</td>';
            echo '<td>' . esc_html($desc) . '</td>';
            echo '<td>' . esc_html($changes_text) . '</td>';
            echo '<td>' . esc_html($row['action']) . '</td>';
            echo '<td>' . esc_html($row['old_price']) . '</td>';
            echo '<td>' . esc_html($row['new_price']) . '</td>';
            echo '<td>' . esc_html($row['status']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        $table_html = ob_get_clean();

        wp_send_json_success([
            'imported' => $result['imported'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'table' => $table_html
        ]);
    }
}
