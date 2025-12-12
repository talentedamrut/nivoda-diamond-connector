<?php
/**
 * Core plugin class
 */

class Nivoda_API {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = NIVODA_API_VERSION;
        $this->plugin_name = 'nivoda-api-integration';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once NIVODA_API_PLUGIN_DIR . 'includes/class-nivoda-loader.php';
        require_once NIVODA_API_PLUGIN_DIR . 'includes/class-nivoda-api-client.php';
        require_once NIVODA_API_PLUGIN_DIR . 'admin/class-nivoda-admin.php';
        require_once NIVODA_API_PLUGIN_DIR . 'public/class-nivoda-public.php';

        $this->loader = new Nivoda_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Nivoda_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    private function define_public_hooks() {
        $plugin_public = new Nivoda_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('rest_api_init', $plugin_public, 'register_rest_routes');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
