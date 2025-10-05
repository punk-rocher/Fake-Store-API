<?php
if (!defined('ABSPATH'))
    exit;

class FSYNC_Sync
{

    public function import_products($limit = 200)
    {
        //API endpoint
        $base_url = get_option('fsync_api_url', 'https://fakestoreapi.com');
        $api_url = rtrim($base_url, '/') . '/products?limit=' . intval($limit);

        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            return ['error' => 'API request failed: ' . $response->get_error_message()];
        }

        $products = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($products))
            return ['error' => 'API did not return a valid product array'];

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $table_data = [];

        foreach ($products as $p) {
            $res = $this->create_or_update_product($p);

            // Count totals
            if ($res['action'] === 'created')
                $imported++;
            elseif ($res['action'] === 'updated')
                $updated++;
            else
                $skipped++;

            // Add to table
            $table_data[] = [
                'id' => $p['id'],
                'title' => $p['title'],
                'image' => $p['image'],
                'category' => $p['category'],
                'description' => $p['description'], // add full description
                'old_price' => $res['old_price'] ?? $p['price'],
                'new_price' => $p['price'],
                'action' => ucfirst($res['action']),
                'status' => ($res['action'] === 'created') ? 'Created successfully' : (($res['action'] === 'updated') ? 'Updated successfully' : 'Already up to date'),
            ];
        }


        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'table' => $table_data
        ];
    }

    public function create_or_update_product($p)
    {
        $fakestore_id = intval($p['id']);
        $title = sanitize_text_field($p['title']);
        $price = floatval($p['price']);
        $desc = wp_kses_post($p['description']);
        $category_name = sanitize_text_field($p['category']);
        $image_url = esc_url_raw($p['image']);

        // Check existing product by fakestore_id
        $existing = get_posts([
            'post_type' => 'product',
            'meta_key' => '_fakestore_id',
            'meta_value' => $fakestore_id,
            'numberposts' => 1
        ]);

        $action = 'created';
        $post_id = 0;
        $old_price = 0;

        if (!empty($existing)) {
            $post_id = $existing[0]->ID;
            $product = wc_get_product($post_id);
            if (!$product)
                return ['action' => 'skipped', 'status' => 'Invalid product', 'old_price' => 0];

            $old_price = floatval($product->get_regular_price());
            $changes_detected = false;

            if ($old_price !== $price || $product->get_name() !== $title || $product->get_description() !== $desc) {
                $changes_detected = true;
            }

            // Category check
            $terms = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'names']);
            if (!in_array($category_name, $terms))
                $changes_detected = true;

            if ($changes_detected) {
                wp_update_post(['ID' => $post_id, 'post_title' => $title, 'post_content' => $desc]);
                $product->set_regular_price($price);
                $product->set_price($price);
                $product->save();
                $action = 'updated';
            } else
                $action = 'skipped';
        } else {
            $post_id = wp_insert_post([
                'post_title' => $title,
                'post_content' => $desc,
                'post_status' => 'publish',
                'post_type' => 'product'
            ]);

            if (!$post_id || is_wp_error($post_id)) {
                return ['action' => 'skipped', 'status' => 'Failed to create product', 'old_price' => 0];
            }

            update_post_meta($post_id, '_fakestore_id', $fakestore_id);
            $product = wc_get_product($post_id);
            $product->set_regular_price($price);
            $product->set_price($price);
            $product->save();
        }

        // Category
        if ($category_name) {
            $term = get_term_by('name', $category_name, 'product_cat');
            if (!$term || is_wp_error($term)) {
                $term_id = wp_insert_term($category_name, 'product_cat');
                if (!is_wp_error($term_id))
                    $term = get_term($term_id['term_id'], 'product_cat');
            }
            if ($term && !is_wp_error($term))
                wp_set_object_terms($post_id, intval($term->term_id), 'product_cat');
        }

        // Image
        if ($image_url)
            $this->set_product_image($post_id, $image_url);

        return ['action' => $action, 'post_id' => $post_id, 'old_price' => $old_price, 'status' => ucfirst($action)];
    }


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
