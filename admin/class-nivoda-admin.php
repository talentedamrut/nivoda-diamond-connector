<?php
/**
 * Admin-specific functionality
 */

class Nivoda_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            NIVODA_API_PLUGIN_URL . 'admin/css/nivoda-admin.css',
            [],
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            NIVODA_API_PLUGIN_URL . 'admin/js/nivoda-admin.js',
            ['jquery'],
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'nivodaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nivoda_admin_nonce')
        ]);
    }

    public function add_admin_menu() {
        add_menu_page(
            esc_html__('Nivoda API Settings', 'nivoda-api-integration'),
            esc_html__('Nivoda API', 'nivoda-api-integration'),
            'manage_options',
            'nivoda-api-settings',
            [$this, 'display_settings_page'],
            'dashicons-networking',
            80
        );

        add_submenu_page(
            'nivoda-api-settings',
            esc_html__('Search Diamonds', 'nivoda-api-integration'),
            esc_html__('Search Diamonds', 'nivoda-api-integration'),
            'manage_options',
            'nivoda-search',
            [$this, 'display_search_page']
        );

        add_submenu_page(
            'nivoda-api-settings',
            esc_html__('Orders', 'nivoda-api-integration'),
            esc_html__('Orders', 'nivoda-api-integration'),
            'manage_options',
            'nivoda-orders',
            [$this, 'display_orders_page']
        );
    }

    public function register_settings() {
        register_setting('nivoda_api_settings', 'nivoda_environment', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('nivoda_api_settings', 'nivoda_username', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('nivoda_api_settings', 'nivoda_password', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);

        add_settings_section(
            'nivoda_api_main_section',
            esc_html__('API Configuration', 'nivoda-api-integration'),
            [$this, 'settings_section_callback'],
            'nivoda-api-settings'
        );

        add_settings_field(
            'nivoda_environment',
            esc_html__('Environment', 'nivoda-api-integration'),
            [$this, 'environment_field_callback'],
            'nivoda-api-settings',
            'nivoda_api_main_section'
        );

        add_settings_field(
            'nivoda_username',
            esc_html__('Username', 'nivoda-api-integration'),
            [$this, 'username_field_callback'],
            'nivoda-api-settings',
            'nivoda_api_main_section'
        );

        add_settings_field(
            'nivoda_password',
            esc_html__('Password', 'nivoda-api-integration'),
            [$this, 'password_field_callback'],
            'nivoda-api-settings',
            'nivoda_api_main_section'
        );
    }

    public function settings_section_callback() {
        echo '<p>' . esc_html__('Configure your Nivoda API credentials. For production, use your Nivoda platform credentials. For staging, request credentials from tech@nivoda.net', 'nivoda-api-integration') . '</p>';
    }

    public function environment_field_callback() {
        $environment = get_option('nivoda_environment', 'staging');
        ?>
        <select name="nivoda_environment" id="nivoda_environment">
            <option value="staging" <?php selected($environment, 'staging'); ?>><?php esc_html_e('Staging', 'nivoda-api-integration'); ?></option>
            <option value="production" <?php selected($environment, 'production'); ?>><?php esc_html_e('Production', 'nivoda-api-integration'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Select the API environment', 'nivoda-api-integration'); ?></p>
        <?php
    }

    public function username_field_callback() {
        $username = get_option('nivoda_username', '');
        ?>
        <input type="text" name="nivoda_username" id="nivoda_username" value="<?php echo esc_attr($username); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Your Nivoda API username', 'nivoda-api-integration'); ?></p>
        <?php
    }

    public function password_field_callback() {
        $password = get_option('nivoda_password', '');
        ?>
        <input type="password" name="nivoda_password" id="nivoda_password" value="<?php echo esc_attr($password); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Your Nivoda API password', 'nivoda-api-integration'); ?></p>
        <?php
    }

    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle test connection
        if (isset($_POST['test_connection']) && check_admin_referer('nivoda_test_connection')) {
            $client = new Nivoda_API_Client();
            $result = $client->test_connection();

            if (is_wp_error($result)) {
                add_settings_error(
                    'nivoda_api_messages',
                    'nivoda_api_error',
                    $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'nivoda_api_messages',
                    'nivoda_api_success',
                    esc_html__('Connection successful!', 'nivoda-api-integration'),
                    'success'
                );
            }
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'nivoda_api_messages',
                'nivoda_api_message',
                esc_html__('Settings Saved', 'nivoda-api-integration'),
                'success'
            );
        }

        settings_errors('nivoda_api_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="nivoda-settings-wrapper">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('nivoda_api_settings');
                    do_settings_sections('nivoda-api-settings');
                    submit_button(__('Save Settings', 'nivoda-api-integration'));
                    ?>
                </form>

                <hr>

                <form method="post">
                    <?php wp_nonce_field('nivoda_test_connection'); ?>
                    <h2><?php esc_html_e('Test Connection', 'nivoda-api-integration'); ?></h2>
                    <p><?php esc_html_e('Click the button below to test your API credentials.', 'nivoda-api-integration'); ?></p>
                    <?php submit_button(__('Test Connection', 'nivoda-api-integration'), 'secondary', 'test_connection'); ?>
                </form>

                <hr>

                <div class="nivoda-info-box">
                    <h2><?php esc_html_e('Usage Instructions', 'nivoda-api-integration'); ?></h2>
                    <h3><?php esc_html_e('Shortcodes', 'nivoda-api-integration'); ?></h3>
                    <ul>
                        <li><code>[nivoda_search]</code> - <?php esc_html_e('Display diamond search interface', 'nivoda-api-integration'); ?></li>
                        <li><code>[nivoda_search shapes="ROUND,PRINCESS" labgrown="false" limit="20"]</code> - <?php esc_html_e('Display filtered search', 'nivoda-api-integration'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('REST API Endpoints', 'nivoda-api-integration'); ?></h3>
                    <ul>
                        <li><code>GET /wp-json/nivoda/v1/search</code> - <?php esc_html_e('Search diamonds', 'nivoda-api-integration'); ?></li>
                        <li><code>POST /wp-json/nivoda/v1/order</code> - <?php esc_html_e('Create order', 'nivoda-api-integration'); ?></li>
                        <li><code>POST /wp-json/nivoda/v1/hold</code> - <?php esc_html_e('Create hold', 'nivoda-api-integration'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('Documentation', 'nivoda-api-integration'); ?></h3>
                    <p><?php esc_html_e('For full API documentation, visit:', 'nivoda-api-integration'); ?> <a href="https://bitbucket.org/nivoda/nivoda-api/" target="_blank">https://bitbucket.org/nivoda/nivoda-api/</a></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function display_search_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Search Diamonds', 'nivoda-api-integration'); ?></h1>
            
            <div id="nivoda-admin-search">
                <div class="nivoda-search-filters">
                    <h2><?php esc_html_e('Search Filters', 'nivoda-api-integration'); ?></h2>
                    
                    <div class="filter-group">
                        <label><?php esc_html_e('Lab Grown', 'nivoda-api-integration'); ?></label>
                        <select id="filter-labgrown">
                            <option value=""><?php esc_html_e('All', 'nivoda-api-integration'); ?></option>
                            <option value="true"><?php esc_html_e('Yes', 'nivoda-api-integration'); ?></option>
                            <option value="false"><?php esc_html_e('No', 'nivoda-api-integration'); ?></option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><?php esc_html_e('Shape', 'nivoda-api-integration'); ?></label>
                        <select id="filter-shape" multiple>
                            <option value="ROUND">Round</option>
                            <option value="PRINCESS">Princess</option>
                            <option value="EMERALD">Emerald</option>
                            <option value="ASSCHER">Asscher</option>
                            <option value="CUSHION">Cushion</option>
                            <option value="OVAL">Oval</option>
                            <option value="RADIANT">Radiant</option>
                            <option value="PEAR">Pear</option>
                            <option value="HEART">Heart</option>
                            <option value="MARQUISE">Marquise</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><?php esc_html_e('Size (Carats)', 'nivoda-api-integration'); ?></label>
                        <input type="number" id="filter-size-from" placeholder="From" step="0.01" min="0">
                        <input type="number" id="filter-size-to" placeholder="To" step="0.01" min="0">
                    </div>

                    <div class="filter-group">
                        <label><?php esc_html_e('Price', 'nivoda-api-integration'); ?></label>
                        <input type="number" id="filter-price-from" placeholder="From" step="1" min="0">
                        <input type="number" id="filter-price-to" placeholder="To" step="1" min="0">
                    </div>

                    <button type="button" id="nivoda-search-btn" class="button button-primary"><?php esc_html_e('Search', 'nivoda-api-integration'); ?></button>
                </div>

                <div id="nivoda-search-results">
                    <p><?php esc_html_e('Enter search criteria and click Search to view results.', 'nivoda-api-integration'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function display_orders_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get stored orders (from WordPress options)
        $orders = get_option('nivoda_orders', []);
        $last_customer = get_option('nivoda_last_order_customer', []);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Recent Orders', 'nivoda-api-integration'); ?></h1>
            
            <?php if (!empty($last_customer)): ?>
                <div class="nivoda-order-card">
                    <h2>Last Order Customer Details</h2>
                    <table class="form-table">
                        <tr>
                            <th>Name:</th>
                            <td><?php echo esc_html($last_customer['name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo esc_html($last_customer['email'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo esc_html($last_customer['phone'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td><?php echo esc_html($last_customer['address'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>City:</th>
                            <td><?php echo esc_html($last_customer['city'] ?? 'N/A'); ?>, <?php echo esc_html($last_customer['state'] ?? ''); ?> <?php echo esc_html($last_customer['zip'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Country:</th>
                            <td><?php echo esc_html($last_customer['country'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php if (!empty($last_customer['comments'])): ?>
                        <tr>
                            <th>Comments:</th>
                            <td><?php echo esc_html($last_customer['comments']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            <?php else: ?>
                <p><?php esc_html_e('No orders yet. Orders will appear here once customers place them through the checkout page.', 'nivoda-api-integration'); ?></p>
            <?php endif; ?>
            
            <div class="nivoda-info-box" style="margin-top: 30px;">
                <h3><?php esc_html_e('How to Set Up Your Store', 'nivoda-api-integration'); ?></h3>
                <ol>
                    <li>Create a page with shortcode: <code>[nivoda_search]</code> - For displaying diamonds</li>
                    <li>Create a page with shortcode: <code>[nivoda_cart]</code> - For the cart (set permalink to /cart)</li>
                    <li>Create a page with shortcode: <code>[nivoda_checkout]</code> - For checkout (set permalink to /checkout)</li>
                    <li>Customers can now browse, add to cart, and place orders!</li>
                </ol>
                
                <h3><?php esc_html_e('Order Processing', 'nivoda-api-integration'); ?></h3>
                <p>When a customer places an order:</p>
                <ul>
                    <li>✅ Order is submitted to Nivoda API</li>
                    <li>✅ Customer receives confirmation email</li>
                    <li>✅ Admin receives notification email</li>
                    <li>✅ Customer details are stored and displayed here</li>
                </ul>
            </div>
        </div>
        <?php
    }
}
