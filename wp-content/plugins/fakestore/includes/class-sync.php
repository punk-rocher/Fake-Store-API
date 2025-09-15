<?php
if (!defined('ABSPATH'))
    exit;

class FSYNC_Sync
{

    // Import products from FakeStore
    public function import_products($limit = 100)
    {
        $api_url = get_option('fsync_api_url', 'https://fakestoreapi.com/products') . '?limit=' . intval($limit);
        $response = wp_remote_get($api_url);

        if (is_wp_error($response))
            return ['error' => 'API request failed'];

        $products = json_decode(wp_remote_retrieve_body($response), true);

        $imported = 0;
        $updated = 0;

        foreach ($products as $p) {
            $res = $this->create_or_update_product($p);
            if ($res['action'] === 'created')
                $imported++;
            else
                $updated++;
        }

        return [
            'imported' => $imported,
            'updated' => $updated
        ];
    }

    // Create or update WooCommerce product
    public function create_or_update_product($p)
    {
        $fakestore_id = intval($p['id']);
        $title = sanitize_text_field($p['title']);
        $price = floatval($p['price']);
        $desc = wp_kses_post($p['description']);
        $category_name = sanitize_text_field($p['category']);
        $image_url = esc_url_raw($p['image']);

        // Check if product exists
        $existing = get_posts([
            'post_type' => 'product',
            'meta_key' => '_fakestore_id',
            'meta_value' => $fakestore_id,
            'numberposts' => 1
        ]);

        if (!empty($existing)) {
            $post_id = $existing[0]->ID;
            $action = 'updated';
        } else {
            $post_id = wp_insert_post([
                'post_title' => $title,
                'post_content' => $desc,
                'post_status' => 'publish',
                'post_type' => 'product'
            ]);
            $action = 'created';
        }

        // Update price and meta
        update_post_meta($post_id, '_fakestore_id', $fakestore_id);
        update_post_meta($post_id, '_regular_price', $price);
        update_post_meta($post_id, '_price', $price);
        update_post_meta($post_id, '_stock_status', 'instock');

        // Assign category
        if ($category_name) {
            $term = get_term_by('name', $category_name, 'product_cat');
            if (!$term) {
                $term_id = wp_insert_term($category_name, 'product_cat');
                $term = get_term($term_id['term_id'], 'product_cat');
            }
            if ($term)
                wp_set_object_terms($post_id, $term->term_id, 'product_cat');
        }

        // Set featured image
        if ($image_url && !get_post_meta($post_id, '_thumbnail_id', true)) {
            $this->set_product_image($post_id, $image_url);
        }

        return ['action' => $action, 'post_id' => $post_id];
    }

    // Download and set image
    private function set_product_image($post_id, $url)
    {
        if (!function_exists('media_handle_sideload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $tmp = download_url($url);
        if (is_wp_error($tmp))
            return false;

        $file = [
            'name' => basename($url),
            'tmp_name' => $tmp
        ];

        $id = media_handle_sideload($file, $post_id);
        @unlink($tmp);

        if (!is_wp_error($id))
            set_post_thumbnail($post_id, $id);
    }
}
