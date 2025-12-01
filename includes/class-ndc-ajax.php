<?php
/**
 * AJAX Handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDC_AJAX {
    
    /**
     * API service
     */
    private $api;
    
    /**
     * Cache service
     */
    private $cache;
    
    /**
     * Constructor
     */
    public function __construct($api, $cache) {
        $this->api = $api;
        $this->cache = $cache;
        
        // Frontend AJAX
        add_action('wp_ajax_ndc_search_diamonds', [$this, 'search_diamonds']);
        add_action('wp_ajax_nopriv_ndc_search_diamonds', [$this, 'search_diamonds']);
        
        add_action('wp_ajax_ndc_get_diamond', [$this, 'get_diamond']);
        add_action('wp_ajax_nopriv_ndc_get_diamond', [$this, 'get_diamond']);
        
        add_action('wp_ajax_ndc_get_images', [$this, 'get_images']);
        add_action('wp_ajax_nopriv_ndc_get_images', [$this, 'get_images']);
        
        // Admin AJAX
        add_action('wp_ajax_ndc_clear_cache', [$this, 'clear_cache']);
        add_action('wp_ajax_ndc_sync_diamonds', [$this, 'sync_diamonds']);
        add_action('wp_ajax_ndc_test_api', [$this, 'test_api']);
    }
    
    /**
     * Search diamonds AJAX handler
     */
    public function search_diamonds() {
        check_ajax_referer('ndc_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        
        $result = $this->api->search_diamonds($filters, $page, $limit);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        if (!isset($result['data']['diamonds_by_query'])) {
            wp_send_json_error([
                'message' => __('Invalid API response', 'nivoda-diamond-connector'),
            ]);
        }
        
        $data = $result['data']['diamonds_by_query'];
        
        // Apply markup
        $markup = $this->get_markup_multiplier();
        foreach ($data['items'] as &$diamond) {
            if (isset($diamond['price'])) {
                $diamond['original_price'] = $diamond['price'];
                $diamond['price'] = round($diamond['price'] * $markup, 2);
            }
        }
        
        wp_send_json_success([
            'diamonds' => $data['items'],
            'total' => $data['total_count'],
            'page_info' => $data['page_info'],
            'current_page' => $page,
        ]);
    }
    
    /**
     * Get single diamond AJAX handler
     */
    public function get_diamond() {
        check_ajax_referer('ndc_nonce', 'nonce');
        
        $diamond_id = isset($_POST['diamond_id']) ? sanitize_text_field($_POST['diamond_id']) : '';
        
        if (empty($diamond_id)) {
            wp_send_json_error([
                'message' => __('Diamond ID is required', 'nivoda-diamond-connector'),
            ]);
        }
        
        $result = $this->api->get_diamond($diamond_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        if (!isset($result['data']['diamond'])) {
            wp_send_json_error([
                'message' => __('Diamond not found', 'nivoda-diamond-connector'),
            ]);
        }
        
        $diamond = $result['data']['diamond'];
        
        // Apply markup
        if (isset($diamond['price'])) {
            $markup = $this->get_markup_multiplier();
            $diamond['original_price'] = $diamond['price'];
            $diamond['price'] = round($diamond['price'] * $markup, 2);
        }
        
        wp_send_json_success($diamond);
    }
    
    /**
     * Get diamond images AJAX handler
     */
    public function get_images() {
        check_ajax_referer('ndc_nonce', 'nonce');
        
        $diamond_id = isset($_POST['diamond_id']) ? sanitize_text_field($_POST['diamond_id']) : '';
        
        if (empty($diamond_id)) {
            wp_send_json_error([
                'message' => __('Diamond ID is required', 'nivoda-diamond-connector'),
            ]);
        }
        
        $result = $this->api->get_diamond_images($diamond_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        wp_send_json_success($result['data']['diamond'] ?? []);
    }
    
    /**
     * Clear cache AJAX handler
     */
    public function clear_cache() {
        check_ajax_referer('ndc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'nivoda-diamond-connector'),
            ]);
        }
        
        $this->cache->clear_all();
        
        wp_send_json_success([
            'message' => __('Cache cleared successfully', 'nivoda-diamond-connector'),
        ]);
    }
    
    /**
     * Sync diamonds AJAX handler
     */
    public function sync_diamonds() {
        check_ajax_referer('ndc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'nivoda-diamond-connector'),
            ]);
        }
        
        // Get recent diamonds
        $result = $this->api->search_diamonds([], 1, 100);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        $diamonds = $result['data']['diamonds_by_query']['items'] ?? [];
        $synced = 0;
        
        foreach ($diamonds as $diamond) {
            $this->create_or_update_diamond_post($diamond);
            $synced++;
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Synced %d diamonds successfully', 'nivoda-diamond-connector'), $synced),
            'count' => $synced,
        ]);
    }
    
    /**
     * Test API AJAX handler
     */
    public function test_api() {
        check_ajax_referer('ndc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'nivoda-diamond-connector'),
            ]);
        }
        
        $result = $this->api->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get markup multiplier
     */
    private function get_markup_multiplier() {
        $settings = get_option('ndc_settings', []);
        $markup_percentage = floatval($settings['markup_percentage'] ?? 10);
        return 1 + ($markup_percentage / 100);
    }
    
    /**
     * Create or update diamond post
     */
    private function create_or_update_diamond_post($diamond) {
        $diamond_id = $diamond['id'];
        
        // Check if post exists
        $existing = get_posts([
            'post_type' => 'ndc_diamond',
            'meta_key' => '_ndc_diamond_id',
            'meta_value' => $diamond_id,
            'posts_per_page' => 1,
        ]);
        
        $cert = $diamond['certificate'] ?? [];
        
        $post_data = [
            'post_title' => sprintf(
                '%s %s %s %s',
                $cert['carats'] ?? '',
                $cert['shape'] ?? '',
                $cert['color'] ?? '',
                $cert['clarity'] ?? ''
            ),
            'post_type' => 'ndc_diamond',
            'post_status' => 'publish',
        ];
        
        if (!empty($existing)) {
            $post_data['ID'] = $existing[0]->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if ($post_id) {
            // Update meta
            update_post_meta($post_id, '_ndc_diamond_id', $diamond_id);
            update_post_meta($post_id, '_ndc_carat', $cert['carats'] ?? '');
            update_post_meta($post_id, '_ndc_cut', $cert['cut'] ?? '');
            update_post_meta($post_id, '_ndc_polish', $cert['polish'] ?? '');
            update_post_meta($post_id, '_ndc_symmetry', $cert['symmetry'] ?? '');
            update_post_meta($post_id, '_ndc_fluorescence', $cert['fluorescence'] ?? '');
            update_post_meta($post_id, '_ndc_cert_number', $cert['certNumber'] ?? '');
            update_post_meta($post_id, '_ndc_cert_lab', $cert['lab'] ?? '');
            update_post_meta($post_id, '_ndc_price', $diamond['price'] ?? 0);
            update_post_meta($post_id, '_ndc_discount', $diamond['discount'] ?? 0);
            
            // Set taxonomies
            if (!empty($cert['shape'])) {
                wp_set_object_terms($post_id, $cert['shape'], 'ndc_shape');
            }
            if (!empty($cert['color'])) {
                wp_set_object_terms($post_id, $cert['color'], 'ndc_color');
            }
            if (!empty($cert['clarity'])) {
                wp_set_object_terms($post_id, $cert['clarity'], 'ndc_clarity');
            }
            
            // Set featured image
            if (!empty($diamond['image'])) {
                $this->set_featured_image_from_url($post_id, $diamond['image']);
            }
        }
        
        return $post_id;
    }
    
    /**
     * Set featured image from URL
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = [
            'name' => basename($image_url),
            'tmp_name' => $tmp,
        ];
        
        $id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        set_post_thumbnail($post_id, $id);
        
        return true;
    }
}
