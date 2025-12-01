<?php
/**
 * Plugin Name: Nivoda Diamond Connector
 * Plugin URI: https://github.com/yourusername/nivoda-diamond-connector
 * Description: Professional WordPress plugin for integrating Nivoda diamond API with WooCommerce. Includes advanced search, caching, and direct checkout capabilities.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nivoda-diamond-connector
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NDC_VERSION', '1.0.0');
define('NDC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NDC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NDC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NDC_INCLUDES_DIR', NDC_PLUGIN_DIR . 'includes/');
define('NDC_ASSETS_URL', NDC_PLUGIN_URL . 'assets/');
define('NDC_TEMPLATES_DIR', NDC_PLUGIN_DIR . 'templates/');

/**
 * Main plugin class
 */
class Nivoda_Diamond_Connector {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * API Service instance
     */
    public $api;
    
    /**
     * Cache service instance
     */
    public $cache;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once NDC_INCLUDES_DIR . 'class-ndc-api.php';
        require_once NDC_INCLUDES_DIR . 'class-ndc-cache.php';
        require_once NDC_INCLUDES_DIR . 'class-ndc-settings.php';
        require_once NDC_INCLUDES_DIR . 'class-ndc-cpt.php';
        require_once NDC_INCLUDES_DIR . 'class-ndc-ajax.php';
        require_once NDC_INCLUDES_DIR . 'class-ndc-woocommerce.php';
        require_once NDC_INCLUDES_DIR . 'class-ndc-admin-tools.php';
        
        // Initialize services
        $this->cache = new NDC_Cache();
        $this->api = new NDC_API($this->cache);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Initialize components
        add_action('plugins_loaded', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Load text domain
        add_action('init', [$this, 'load_textdomain']);
        
        // Shortcodes
        add_shortcode('nivoda_search', [$this, 'render_search_shortcode']);
        add_shortcode('nivoda_diamond', [$this, 'render_diamond_shortcode']);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        flush_rewrite_rules();
        
        // Set default options
        if (!get_option('ndc_settings')) {
            add_option('ndc_settings', [
                'api_key' => '',
                'api_url' => 'https://api.nivoda.net/graphql',
                'cache_ttl' => 3600,
                'markup_percentage' => 10,
                'enable_caching' => 'yes',
            ]);
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Check WooCommerce dependency
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }
        
        // Initialize components
        new NDC_Settings();
        new NDC_CPT();
        new NDC_AJAX($this->api, $this->cache);
        new NDC_WooCommerce($this->api);
        new NDC_Admin_Tools($this->api, $this->cache);
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('Nivoda Diamond Connector requires WooCommerce to be installed and active.', 'nivoda-diamond-connector'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('nivoda-diamond-connector', false, dirname(NDC_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'ndc-frontend',
            NDC_ASSETS_URL . 'css/frontend.css',
            [],
            NDC_VERSION
        );
        
        wp_enqueue_style(
            'ndc-slick',
            'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
            [],
            '1.8.1'
        );
        
        // JavaScript
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'ndc-slick',
            'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
            ['jquery'],
            '1.8.1',
            true
        );
        
        wp_enqueue_script(
            'ndc-nouislider',
            'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js',
            [],
            '15.7.0',
            true
        );
        
        wp_enqueue_style(
            'ndc-nouislider',
            'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css',
            [],
            '15.7.0'
        );
        
        wp_enqueue_script(
            'ndc-frontend',
            NDC_ASSETS_URL . 'js/frontend.js',
            ['jquery', 'ndc-slick', 'ndc-nouislider'],
            NDC_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('ndc-frontend', 'ndcData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ndc_nonce'),
            'i18n' => [
                'loading' => __('Loading...', 'nivoda-diamond-connector'),
                'error' => __('An error occurred. Please try again.', 'nivoda-diamond-connector'),
                'noResults' => __('No diamonds found matching your criteria.', 'nivoda-diamond-connector'),
            ],
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin settings page
        if (strpos($hook, 'nivoda-diamond') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ndc-admin',
            NDC_ASSETS_URL . 'css/admin.css',
            [],
            NDC_VERSION
        );
        
        wp_enqueue_script(
            'ndc-admin',
            NDC_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            NDC_VERSION,
            true
        );
        
        wp_localize_script('ndc-admin', 'ndcAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ndc_admin_nonce'),
        ]);
    }
    
    /**
     * Render search shortcode
     */
    public function render_search_shortcode($atts) {
        $atts = shortcode_atts([
            'results_per_page' => 20,
            'show_filters' => 'yes',
        ], $atts);
        
        ob_start();
        include NDC_TEMPLATES_DIR . 'search.php';
        return ob_get_clean();
    }
    
    /**
     * Render single diamond shortcode
     */
    public function render_diamond_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => '',
        ], $atts);
        
        if (empty($atts['id'])) {
            return '<p>' . __('Diamond ID is required.', 'nivoda-diamond-connector') . '</p>';
        }
        
        ob_start();
        include NDC_TEMPLATES_DIR . 'single-diamond.php';
        return ob_get_clean();
    }
}

/**
 * Get main plugin instance
 */
function NDC() {
    return Nivoda_Diamond_Connector::instance();
}

// Initialize plugin
NDC();
