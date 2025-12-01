<?php
/**
 * Nivoda API Service Wrapper
 * 
 * Handles all API communication with Nivoda GraphQL API
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDC_API {
    
    /**
     * API endpoint
     */
    private $api_url;
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Cache service
     */
    private $cache;
    
    /**
     * Rate limiting
     */
    private $last_request_time = 0;
    private $min_request_interval = 0.1;
    
    /**
     * Constructor
     */
    public function __construct($cache = null) {
        $settings = get_option('ndc_settings', []);
        $this->api_url = $settings['api_url'] ?? 'https://api.nivoda.net/graphql';
        $this->api_key = $settings['api_key'] ?? '';
        $this->cache = $cache;
    }
    
    /**
     * Make GraphQL request
     */
    private function request($query, $variables = [], $use_cache = true) {
        // Validate API key
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Nivoda API key is not configured.', 'nivoda-diamond-connector'));
        }
        
        // Check cache first
        if ($use_cache && $this->cache) {
            $cache_key = 'ndc_' . md5($query . serialize($variables));
            $cached = $this->cache->get($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Rate limiting
        $this->enforce_rate_limit();
        
        // Prepare request
        $body = json_encode([
            'query' => $query,
            'variables' => $variables,
        ]);
        
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => $body,
            'timeout' => 30,
            'sslverify' => true,
        ];
        
        // Make request
        $response = wp_remote_post($this->api_url, $args);
        
        // Handle errors
        if (is_wp_error($response)) {
            error_log('Nivoda API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            error_log('Nivoda API HTTP Error: ' . $status_code . ' - ' . $body);
            return new WP_Error('api_error', sprintf(__('API returned status code %d', 'nivoda-diamond-connector'), $status_code));
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Nivoda API JSON Error: ' . json_last_error_msg());
            return new WP_Error('json_error', __('Invalid JSON response from API', 'nivoda-diamond-connector'));
        }
        
        // Check for GraphQL errors
        if (isset($data['errors']) && !empty($data['errors'])) {
            $error_msg = $data['errors'][0]['message'] ?? 'Unknown GraphQL error';
            error_log('Nivoda GraphQL Error: ' . $error_msg);
            return new WP_Error('graphql_error', $error_msg);
        }
        
        // Cache successful response
        if ($use_cache && $this->cache && isset($data['data'])) {
            $this->cache->set($cache_key, $data, HOUR_IN_SECONDS);
        }
        
        return $data;
    }
    
    /**
     * Enforce rate limiting
     */
    private function enforce_rate_limit() {
        $now = microtime(true);
        $elapsed = $now - $this->last_request_time;
        
        if ($elapsed < $this->min_request_interval) {
            usleep(($this->min_request_interval - $elapsed) * 1000000);
        }
        
        $this->last_request_time = microtime(true);
    }
    
    /**
     * Search diamonds
     */
    public function search_diamonds($filters = [], $page = 1, $limit = 20) {
        $query = '
            query SearchDiamonds($filters: DiamondFilters!, $page: Int!, $limit: Int!) {
                diamonds_by_query(
                    query: $filters
                    offset: $page
                    limit: $limit
                ) {
                    items {
                        id
                        video
                        image
                        availability
                        supplierStockId
                        brown
                        green
                        milky
                        eyeClean
                        blue
                        gray
                        other
                        certificate {
                            id
                            lab
                            shape
                            certNumber
                            cut
                            carats
                            clarity
                            color
                            polish
                            symmetry
                            fluorescence
                            measurements
                        }
                        delivery_time {
                            express_timeline
                            standard_timeline
                        }
                        price
                        discount
                        depth
                        table
                        girdle
                        culet
                        measurements {
                            length
                            width
                            depth
                        }
                    }
                    total_count
                    page_info {
                        has_next_page
                        has_previous_page
                        start_cursor
                        end_cursor
                    }
                }
            }
        ';
        
        $variables = [
            'filters' => $this->prepare_filters($filters),
            'page' => max(0, ($page - 1) * $limit),
            'limit' => $limit,
        ];
        
        return $this->request($query, $variables);
    }
    
    /**
     * Get diamond by ID
     */
    public function get_diamond($diamond_id) {
        $query = '
            query GetDiamond($id: String!) {
                diamond(id: $id) {
                    id
                    video
                    image
                    availability
                    supplierStockId
                    brown
                    green
                    milky
                    eyeClean
                    blue
                    gray
                    other
                    certificate {
                        id
                        lab
                        shape
                        certNumber
                        cut
                        carats
                        clarity
                        color
                        polish
                        symmetry
                        fluorescence
                        measurements
                        date_created
                    }
                    delivery_time {
                        express_timeline
                        standard_timeline
                    }
                    price
                    discount
                    depth
                    table
                    girdle
                    culet
                    measurements {
                        length
                        width
                        depth
                    }
                    mine_of_origin
                    country_of_origin
                }
            }
        ';
        
        $variables = [
            'id' => $diamond_id,
        ];
        
        return $this->request($query, $variables);
    }
    
    /**
     * Get diamond images
     */
    public function get_diamond_images($diamond_id) {
        $query = '
            query GetDiamondMedia($id: String!) {
                diamond(id: $id) {
                    image
                    video
                    certificate {
                        certNumber
                        lab
                    }
                }
            }
        ';
        
        $variables = [
            'id' => $diamond_id,
        ];
        
        return $this->request($query, $variables);
    }
    
    /**
     * Prepare filters for GraphQL query
     */
    private function prepare_filters($filters) {
        $prepared = [];
        
        // Shape filter
        if (!empty($filters['shape'])) {
            $prepared['shapes'] = is_array($filters['shape']) ? $filters['shape'] : [$filters['shape']];
        }
        
        // Carat range
        if (isset($filters['carat_min']) || isset($filters['carat_max'])) {
            $prepared['size_from'] = floatval($filters['carat_min'] ?? 0.3);
            $prepared['size_to'] = floatval($filters['carat_max'] ?? 20.0);
        }
        
        // Color filter
        if (!empty($filters['color'])) {
            $prepared['colors'] = is_array($filters['color']) ? $filters['color'] : [$filters['color']];
        }
        
        // Clarity filter
        if (!empty($filters['clarity'])) {
            $prepared['clarities'] = is_array($filters['clarity']) ? $filters['clarity'] : [$filters['clarity']];
        }
        
        // Cut filter
        if (!empty($filters['cut'])) {
            $prepared['cuts'] = is_array($filters['cut']) ? $filters['cut'] : [$filters['cut']];
        }
        
        // Price range
        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $prepared['price_from'] = floatval($filters['price_min'] ?? 0);
            $prepared['price_to'] = floatval($filters['price_max'] ?? 1000000);
        }
        
        // Lab filter
        if (!empty($filters['lab'])) {
            $prepared['labs'] = is_array($filters['lab']) ? $filters['lab'] : [$filters['lab']];
        }
        
        // Polish
        if (!empty($filters['polish'])) {
            $prepared['polishes'] = is_array($filters['polish']) ? $filters['polish'] : [$filters['polish']];
        }
        
        // Symmetry
        if (!empty($filters['symmetry'])) {
            $prepared['symmetries'] = is_array($filters['symmetry']) ? $filters['symmetry'] : [$filters['symmetry']];
        }
        
        // Fluorescence
        if (!empty($filters['fluorescence'])) {
            $prepared['fluorescences'] = is_array($filters['fluorescence']) ? $filters['fluorescence'] : [$filters['fluorescence']];
        }
        
        // Availability
        $prepared['has_v360'] = $filters['has_video'] ?? null;
        $prepared['has_image'] = $filters['has_image'] ?? null;
        
        return $prepared;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $query = '
            query TestConnection {
                diamonds_by_query(limit: 1) {
                    total_count
                }
            }
        ';
        
        $result = $this->request($query, [], false);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }
        
        if (isset($result['data']['diamonds_by_query'])) {
            return [
                'success' => true,
                'message' => sprintf(
                    __('Connected successfully. %d diamonds available.', 'nivoda-diamond-connector'),
                    $result['data']['diamonds_by_query']['total_count']
                ),
            ];
        }
        
        return [
            'success' => false,
            'message' => __('Unknown error occurred.', 'nivoda-diamond-connector'),
        ];
    }
    
    /**
     * Get filter options (shapes, colors, etc.)
     */
    public function get_filter_options() {
        return [
            'shapes' => ['Round', 'Princess', 'Cushion', 'Emerald', 'Oval', 'Radiant', 'Asscher', 'Marquise', 'Heart', 'Pear'],
            'colors' => ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'],
            'clarities' => ['FL', 'IF', 'VVS1', 'VVS2', 'VS1', 'VS2', 'SI1', 'SI2', 'I1', 'I2'],
            'cuts' => ['Ideal', 'Excellent', 'Very Good', 'Good', 'Fair', 'Poor'],
            'labs' => ['GIA', 'IGI', 'HRD', 'GCAL'],
            'fluorescences' => ['None', 'Faint', 'Medium', 'Strong', 'Very Strong'],
        ];
    }
}
