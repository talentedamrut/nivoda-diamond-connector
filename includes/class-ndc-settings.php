<?php
/**
 * Admin Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDC_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_menu_page(
            __('Nivoda Diamonds', 'nivoda-diamond-connector'),
            __('Nivoda Diamonds', 'nivoda-diamond-connector'),
            'manage_options',
            'nivoda-diamond-connector',
            [$this, 'render_settings_page'],
            'dashicons-money-alt',
            56
        );
        
        add_submenu_page(
            'nivoda-diamond-connector',
            __('Settings', 'nivoda-diamond-connector'),
            __('Settings', 'nivoda-diamond-connector'),
            'manage_options',
            'nivoda-diamond-connector',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ndc_settings_group', 'ndc_settings', [$this, 'sanitize_settings']);
        
        // API Settings Section
        add_settings_section(
            'ndc_api_section',
            __('API Configuration', 'nivoda-diamond-connector'),
            [$this, 'render_api_section_desc'],
            'nivoda-diamond-connector'
        );
        
        add_settings_field(
            'api_key',
            __('API Key', 'nivoda-diamond-connector'),
            [$this, 'render_api_key_field'],
            'nivoda-diamond-connector',
            'ndc_api_section'
        );
        
        add_settings_field(
            'api_url',
            __('API URL', 'nivoda-diamond-connector'),
            [$this, 'render_api_url_field'],
            'nivoda-diamond-connector',
            'ndc_api_section'
        );
        
        // Pricing Settings Section
        add_settings_section(
            'ndc_pricing_section',
            __('Pricing Configuration', 'nivoda-diamond-connector'),
            [$this, 'render_pricing_section_desc'],
            'nivoda-diamond-connector'
        );
        
        add_settings_field(
            'markup_percentage',
            __('Markup Percentage', 'nivoda-diamond-connector'),
            [$this, 'render_markup_field'],
            'nivoda-diamond-connector',
            'ndc_pricing_section'
        );
        
        // Cache Settings Section
        add_settings_section(
            'ndc_cache_section',
            __('Cache Configuration', 'nivoda-diamond-connector'),
            [$this, 'render_cache_section_desc'],
            'nivoda-diamond-connector'
        );
        
        add_settings_field(
            'enable_caching',
            __('Enable Caching', 'nivoda-diamond-connector'),
            [$this, 'render_enable_caching_field'],
            'nivoda-diamond-connector',
            'ndc_cache_section'
        );
        
        add_settings_field(
            'cache_ttl',
            __('Cache Duration (seconds)', 'nivoda-diamond-connector'),
            [$this, 'render_cache_ttl_field'],
            'nivoda-diamond-connector',
            'ndc_cache_section'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $sanitized['api_url'] = esc_url_raw($input['api_url'] ?? 'https://api.nivoda.net/graphql');
        $sanitized['markup_percentage'] = floatval($input['markup_percentage'] ?? 10);
        $sanitized['enable_caching'] = ($input['enable_caching'] ?? 'yes') === 'yes' ? 'yes' : 'no';
        $sanitized['cache_ttl'] = absint($input['cache_ttl'] ?? 3600);
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle test connection
        if (isset($_POST['test_connection']) && check_admin_referer('ndc_test_connection')) {
            $api = new NDC_API();
            $result = $api->test_connection();
            
            if ($result['success']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ndc_settings_group');
                do_settings_sections('nivoda-diamond-connector');
                submit_button(__('Save Settings', 'nivoda-diamond-connector'));
                ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Test API Connection', 'nivoda-diamond-connector'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('ndc_test_connection'); ?>
                <p>
                    <button type="submit" name="test_connection" class="button button-secondary">
                        <?php _e('Test Connection', 'nivoda-diamond-connector'); ?>
                    </button>
                </p>
            </form>
            
            <hr>
            
            <h2><?php _e('Shortcodes', 'nivoda-diamond-connector'); ?></h2>
            <p><strong><?php _e('Diamond Search:', 'nivoda-diamond-connector'); ?></strong></p>
            <code>[nivoda_search]</code>
            
            <p><strong><?php _e('Single Diamond:', 'nivoda-diamond-connector'); ?></strong></p>
            <code>[nivoda_diamond id="DIAMOND_ID"]</code>
        </div>
        <?php
    }
    
    // Section descriptions
    public function render_api_section_desc() {
        echo '<p>' . __('Configure your Nivoda API credentials. You can obtain an API key from your Nivoda account dashboard.', 'nivoda-diamond-connector') . '</p>';
    }
    
    public function render_pricing_section_desc() {
        echo '<p>' . __('Configure pricing markup for diamonds displayed on your site.', 'nivoda-diamond-connector') . '</p>';
    }
    
    public function render_cache_section_desc() {
        echo '<p>' . __('Configure caching to improve performance and reduce API calls.', 'nivoda-diamond-connector') . '</p>';
    }
    
    // Field renderers
    public function render_api_key_field() {
        $settings = get_option('ndc_settings', []);
        $value = $settings['api_key'] ?? '';
        ?>
        <input type="text" 
               name="ndc_settings[api_key]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="<?php esc_attr_e('Enter your Nivoda API key', 'nivoda-diamond-connector'); ?>">
        <p class="description">
            <?php _e('Your Nivoda API authentication key.', 'nivoda-diamond-connector'); ?>
        </p>
        <?php
    }
    
    public function render_api_url_field() {
        $settings = get_option('ndc_settings', []);
        $value = $settings['api_url'] ?? 'https://api.nivoda.net/graphql';
        ?>
        <input type="url" 
               name="ndc_settings[api_url]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description">
            <?php _e('Nivoda GraphQL API endpoint. Default: https://api.nivoda.net/graphql', 'nivoda-diamond-connector'); ?>
        </p>
        <?php
    }
    
    public function render_markup_field() {
        $settings = get_option('ndc_settings', []);
        $value = $settings['markup_percentage'] ?? 10;
        ?>
        <input type="number" 
               name="ndc_settings[markup_percentage]" 
               value="<?php echo esc_attr($value); ?>" 
               min="0" 
               max="100" 
               step="0.01"
               class="small-text">
        <span>%</span>
        <p class="description">
            <?php _e('Percentage markup to add to diamond prices (e.g., 10 for 10% markup).', 'nivoda-diamond-connector'); ?>
        </p>
        <?php
    }
    
    public function render_enable_caching_field() {
        $settings = get_option('ndc_settings', []);
        $value = $settings['enable_caching'] ?? 'yes';
        ?>
        <label>
            <input type="checkbox" 
                   name="ndc_settings[enable_caching]" 
                   value="yes"
                   <?php checked($value, 'yes'); ?>>
            <?php _e('Enable caching for API responses', 'nivoda-diamond-connector'); ?>
        </label>
        <p class="description">
            <?php _e('Caching improves performance and reduces API calls. Recommended: enabled.', 'nivoda-diamond-connector'); ?>
        </p>
        <?php
    }
    
    public function render_cache_ttl_field() {
        $settings = get_option('ndc_settings', []);
        $value = $settings['cache_ttl'] ?? 3600;
        ?>
        <input type="number" 
               name="ndc_settings[cache_ttl]" 
               value="<?php echo esc_attr($value); ?>" 
               min="60" 
               step="60"
               class="small-text">
        <span><?php _e('seconds', 'nivoda-diamond-connector'); ?></span>
        <p class="description">
            <?php _e('How long to cache API responses. Recommended: 3600 (1 hour).', 'nivoda-diamond-connector'); ?>
        </p>
        <?php
    }
}
