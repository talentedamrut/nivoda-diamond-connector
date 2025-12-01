<?php
/**
 * WooCommerce Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDC_WooCommerce {
    
    /**
     * API service
     */
    private $api;
    
    /**
     * Constructor
     */
    public function __construct($api) {
        $this->api = $api;
        
        // Add to cart handler
        add_action('wp_ajax_ndc_add_to_cart', [$this, 'add_to_cart']);
        add_action('wp_ajax_nopriv_ndc_add_to_cart', [$this, 'add_to_cart']);
        
        // Cart item display
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);
        
        // Order item meta
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_order_item_meta'], 10, 4);
    }
    
    /**
     * Add diamond to cart
     */
    public function add_to_cart() {
        check_ajax_referer('ndc_nonce', 'nonce');
        
        $diamond_id = isset($_POST['diamond_id']) ? sanitize_text_field($_POST['diamond_id']) : '';
        
        if (empty($diamond_id)) {
            wp_send_json_error([
                'message' => __('Diamond ID is required', 'nivoda-diamond-connector'),
            ]);
        }
        
        // Get diamond details
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
        $cert = $diamond['certificate'] ?? [];
        
        // Create or get product
        $product_id = $this->create_diamond_product($diamond);
        
        if (!$product_id) {
            wp_send_json_error([
                'message' => __('Failed to create product', 'nivoda-diamond-connector'),
            ]);
        }
        
        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            1,
            0,
            [],
            [
                'ndc_diamond_id' => $diamond_id,
                'ndc_diamond_data' => $diamond,
            ]
        );
        
        if ($cart_item_key) {
            wp_send_json_success([
                'message' => __('Diamond added to cart', 'nivoda-diamond-connector'),
                'cart_url' => wc_get_cart_url(),
                'cart_count' => WC()->cart->get_cart_contents_count(),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to add to cart', 'nivoda-diamond-connector'),
            ]);
        }
    }
    
    /**
     * Create WooCommerce product for diamond
     */
    private function create_diamond_product($diamond) {
        $cert = $diamond['certificate'] ?? [];
        
        // Check if product exists
        $existing = get_posts([
            'post_type' => 'product',
            'meta_key' => '_ndc_diamond_id',
            'meta_value' => $diamond['id'],
            'posts_per_page' => 1,
        ]);
        
        if (!empty($existing)) {
            return $existing[0]->ID;
        }
        
        // Calculate price with markup
        $settings = get_option('ndc_settings', []);
        $markup_percentage = floatval($settings['markup_percentage'] ?? 10);
        $markup_multiplier = 1 + ($markup_percentage / 100);
        $price = round(($diamond['price'] ?? 0) * $markup_multiplier, 2);
        
        // Create product
        $product = new WC_Product_Simple();
        
        $title = sprintf(
            '%s ct %s %s %s Diamond',
            $cert['carats'] ?? '',
            $cert['shape'] ?? '',
            $cert['color'] ?? '',
            $cert['clarity'] ?? ''
        );
        
        $product->set_name($title);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_price($price);
        $product->set_regular_price($price);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        // Description
        $description = sprintf(
            'Certificate: %s %s<br>',
            $cert['lab'] ?? '',
            $cert['certNumber'] ?? ''
        );
        $description .= sprintf('Cut: %s<br>', $cert['cut'] ?? '');
        $description .= sprintf('Polish: %s<br>', $cert['polish'] ?? '');
        $description .= sprintf('Symmetry: %s<br>', $cert['symmetry'] ?? '');
        $description .= sprintf('Fluorescence: %s<br>', $cert['fluorescence'] ?? '');
        
        if (isset($cert['measurements'])) {
            $description .= sprintf('Measurements: %s<br>', $cert['measurements']);
        }
        
        $product->set_description($description);
        $product->set_short_description($description);
        
        $product_id = $product->save();
        
        if ($product_id) {
            // Save diamond metadata
            update_post_meta($product_id, '_ndc_diamond_id', $diamond['id']);
            update_post_meta($product_id, '_ndc_diamond_data', $diamond);
            
            // Set image
            if (!empty($diamond['image'])) {
                $this->set_product_image($product_id, $diamond['image']);
            }
        }
        
        return $product_id;
    }
    
    /**
     * Set product image from URL
     */
    private function set_product_image($product_id, $image_url) {
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
        
        $id = media_handle_sideload($file_array, $product_id);
        
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        set_post_thumbnail($product_id, $id);
        
        return true;
    }
    
    /**
     * Display cart item data
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['ndc_diamond_data'])) {
            $diamond = $cart_item['ndc_diamond_data'];
            $cert = $diamond['certificate'] ?? [];
            
            $item_data[] = [
                'key' => __('Diamond ID', 'nivoda-diamond-connector'),
                'value' => $diamond['id'],
            ];
            
            if (!empty($cert['certNumber'])) {
                $item_data[] = [
                    'key' => __('Certificate', 'nivoda-diamond-connector'),
                    'value' => sprintf('%s %s', $cert['lab'] ?? '', $cert['certNumber']),
                ];
            }
        }
        
        return $item_data;
    }
    
    /**
     * Add order item meta
     */
    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['ndc_diamond_id'])) {
            $item->add_meta_data('_ndc_diamond_id', $values['ndc_diamond_id']);
        }
        
        if (isset($values['ndc_diamond_data'])) {
            $item->add_meta_data('_ndc_diamond_data', $values['ndc_diamond_data']);
            
            $diamond = $values['ndc_diamond_data'];
            $cert = $diamond['certificate'] ?? [];
            
            if (!empty($cert['certNumber'])) {
                $item->add_meta_data(
                    __('Certificate', 'nivoda-diamond-connector'),
                    sprintf('%s %s', $cert['lab'] ?? '', $cert['certNumber']),
                    true
                );
            }
        }
    }
}
