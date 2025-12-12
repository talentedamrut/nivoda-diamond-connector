<?php
/**
 * Nivoda API Client Class
 *
 * Handles all API communication with Nivoda GraphQL API
 */

class Nivoda_API_Client {

    private $api_url;
    private $username;
    private $password;
    private $token;
    private $environment;

    /**
     * Constructor
     */
    public function __construct() {
        $this->environment = get_option('nivoda_environment', 'staging');
        $this->username = get_option('nivoda_username', '');
        $this->password = get_option('nivoda_password', '');
        
        // Set API URL based on environment
        if ($this->environment === 'production') {
            $this->api_url = 'https://integrations.nivoda.net/api/diamonds';
        } else {
            $this->api_url = 'https://intg-customer-staging.nivodaapi.net/api/diamonds';
        }
    }

    /**
     * Authenticate and get token
     *
     * @return string|WP_Error Token or error
     */
    public function authenticate() {
        if (empty($this->username) || empty($this->password)) {
            return new WP_Error('no_credentials', __('Nivoda API credentials not configured', 'nivoda-api-integration'));
        }

        $query = sprintf(
            '{authenticate{username_and_password(username:"%s",password:"%s"){token}}}',
            $this->username,
            $this->password
        );

        $response = $this->make_request($query, false);

        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($response['data']['authenticate']['username_and_password']['token'])) {
            $this->token = $response['data']['authenticate']['username_and_password']['token'];
            
            // Cache token for 30 minutes
            set_transient('nivoda_api_token', $this->token, 1800);
            
            return $this->token;
        }

        return new WP_Error('auth_failed', __('Authentication failed', 'nivoda-api-integration'));
    }

    /**
     * Get cached token or authenticate
     *
     * @return string|WP_Error
     */
    private function get_token() {
        // Check for cached token
        $cached_token = get_transient('nivoda_api_token');
        
        if ($cached_token) {
            $this->token = $cached_token;
            return $this->token;
        }

        return $this->authenticate();
    }

    /**
     * Search diamonds
     *
     * @param array $params Search parameters
     * @return array|WP_Error
     */
    public function search_diamonds($params = []) {
        $token = $this->get_token();
        
        if (is_wp_error($token)) {
            return $token;
        }

        // Build query parameters
        $query_params = $this->build_query_params($params);
        
        $query = sprintf('
            query {
                diamonds_by_query(
                    query: %s,
                    offset: %d,
                    limit: %d,
                    order: { type: %s, direction: %s }
                ) {
                    items {
                        id
                        diamond {
                            id
                            video
                            image
                            availability
                            supplierStockId
                            brown
                            green
                            milky
                            eyeClean
                            mine_of_origin
                            certificate {
                                id
                                lab
                                shape
                                certNumber
                                cut
                                carats
                                clarity
                                polish
                                symmetry
                                color
                                width
                                length
                                depth
                                girdle
                                floInt
                                floCol
                                depthPercentage
                                table
                            }
                        }
                        price
                        discount
                    }
                    total_count
                }
            }
        ',
            $query_params,
            isset($params['offset']) ? intval($params['offset']) : 0,
            isset($params['limit']) ? intval($params['limit']) : 50,
            isset($params['order_type']) ? $params['order_type'] : 'price',
            isset($params['order_direction']) ? $params['order_direction'] : 'ASC'
        );

        return $this->make_request($query);
    }

    /**
     * Build query parameters from search array
     *
     * @param array $params
     * @return string
     */
    private function build_query_params($params) {
        $query_parts = [];

        // Lab grown
        if (isset($params['labgrown'])) {
            $query_parts[] = sprintf('labgrown: %s', $params['labgrown'] ? 'true' : 'false');
        }

        // Shapes
        if (!empty($params['shapes']) && is_array($params['shapes'])) {
            $shapes = array_map(function($shape) {
                return '"' . strtoupper($shape) . '"';
            }, $params['shapes']);
            $query_parts[] = 'shapes: [' . implode(',', $shapes) . ']';
        }

        // Size range
        if (!empty($params['size_from']) || !empty($params['size_to'])) {
            $from = isset($params['size_from']) ? floatval($params['size_from']) : 0;
            $to = isset($params['size_to']) ? floatval($params['size_to']) : 10;
            $query_parts[] = sprintf('sizes: [{ from: %s, to: %s }]', $from, $to);
        }

        // Color
        if (!empty($params['color']) && is_array($params['color'])) {
            $query_parts[] = 'color: [' . implode(',', $params['color']) . ']';
        }

        // Clarity
        if (!empty($params['clarity']) && is_array($params['clarity'])) {
            $query_parts[] = 'clarity: [' . implode(',', $params['clarity']) . ']';
        }

        // Cut
        if (!empty($params['cut']) && is_array($params['cut'])) {
            $query_parts[] = 'cut: [' . implode(',', $params['cut']) . ']';
        }

        // Has video
        if (isset($params['has_v360'])) {
            $query_parts[] = sprintf('has_v360: %s', $params['has_v360'] ? 'true' : 'false');
        }

        // Has image
        if (isset($params['has_image'])) {
            $query_parts[] = sprintf('has_image: %s', $params['has_image'] ? 'true' : 'false');
        }

        // Price range
        if (!empty($params['price_from']) || !empty($params['price_to'])) {
            $from = isset($params['price_from']) ? floatval($params['price_from']) : 0;
            $to = isset($params['price_to']) ? floatval($params['price_to']) : 1000000;
            $query_parts[] = sprintf('price_total: { from: %s, to: %s }', $from, $to);
        }

        return '{' . implode(', ', $query_parts) . '}';
    }

    /**
     * Create an order
     *
     * @param array $items Order items
     * @return array|WP_Error
     */
    public function create_order($items) {
        $token = $this->get_token();
        
        if (is_wp_error($token)) {
            return $token;
        }

        $items_string = $this->build_order_items($items);

        $query = sprintf('
            mutation {
                create_order(
                    items: %s
                )
            }
        ', $items_string);

        return $this->make_request($query);
    }

    /**
     * Build order items string
     *
     * @param array $items
     * @return string
     */
    private function build_order_items($items) {
        $formatted_items = [];

        foreach ($items as $item) {
            $item_parts = [sprintf('offerId: "%s"', $item['offerId'])];

            if (!empty($item['customer_comment'])) {
                $item_parts[] = sprintf('customer_comment: "%s"', addslashes($item['customer_comment']));
            }

            if (!empty($item['customer_order_number'])) {
                $item_parts[] = sprintf('customer_order_number: "%s"', addslashes($item['customer_order_number']));
            }

            if (isset($item['return_option'])) {
                $item_parts[] = sprintf('return_option: %s', $item['return_option'] ? 'true' : 'false');
            }

            if (!empty($item['destinationId'])) {
                $item_parts[] = sprintf('destinationId: %s', $item['destinationId']);
            }

            $formatted_items[] = '{' . implode(', ', $item_parts) . '}';
        }

        return '[' . implode(', ', $formatted_items) . ']';
    }

    /**
     * Create a hold
     *
     * @param string $product_id Product ID
     * @param string $product_type Product type (default: Diamond)
     * @return array|WP_Error
     */
    public function create_hold($product_id, $product_type = 'Diamond') {
        $token = $this->get_token();
        
        if (is_wp_error($token)) {
            return $token;
        }

        $query = sprintf('
            mutation {
                create_hold(
                    ProductId: "%s",
                    ProductType: %s
                ) {
                    id
                    denied
                    until
                }
            }
        ', $product_id, $product_type);

        return $this->make_request($query);
    }

    /**
     * Make API request
     *
     * @param string $query GraphQL query
     * @param bool $use_auth Whether to use authentication
     * @return array|WP_Error
     */
    private function make_request($query, $use_auth = true) {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($use_auth && !empty($this->token)) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        $body = json_encode(['query' => $query]);

        $response = wp_remote_post($this->api_url, [
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Failed to parse API response', 'nivoda-api-integration'));
        }

        // Check for GraphQL errors
        if (isset($data['errors'])) {
            $error_messages = array_map(function($error) {
                return $error['message'] ?? 'Unknown error';
            }, $data['errors']);
            
            return new WP_Error('graphql_error', implode(', ', $error_messages));
        }

        return $data;
    }

    /**
     * Test connection
     *
     * @return bool|WP_Error
     */
    public function test_connection() {
        $result = $this->authenticate();
        
        if (is_wp_error($result)) {
            return $result;
        }

        return true;
    }
}
