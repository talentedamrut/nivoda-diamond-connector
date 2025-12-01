<?php
/**
 * Cache Service using WordPress Transients
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDC_Cache {
    
    /**
     * Cache prefix
     */
    private $prefix = 'ndc_cache_';
    
    /**
     * Default TTL
     */
    private $default_ttl;
    
    /**
     * Enable caching
     */
    private $enabled;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('ndc_settings', []);
        $this->default_ttl = intval($settings['cache_ttl'] ?? 3600);
        $this->enabled = ($settings['enable_caching'] ?? 'yes') === 'yes';
    }
    
    /**
     * Get cached value
     */
    public function get($key) {
        if (!$this->enabled) {
            return false;
        }
        
        $cache_key = $this->prefix . $key;
        return get_transient($cache_key);
    }
    
    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $cache_key = $this->prefix . $key;
        $expiration = $ttl ?? $this->default_ttl;
        
        return set_transient($cache_key, $value, $expiration);
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        $cache_key = $this->prefix . $key;
        return delete_transient($cache_key);
    }
    
    /**
     * Clear all plugin cache
     */
    public function clear_all() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $this->prefix) . '%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_timeout_' . $this->prefix) . '%'
            )
        );
        
        return true;
    }
    
    /**
     * Get cache statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $this->prefix) . '%'
            )
        );
        
        $size = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $this->prefix) . '%'
            )
        );
        
        return [
            'count' => intval($count),
            'size' => intval($size),
            'size_formatted' => size_format(intval($size)),
            'enabled' => $this->enabled,
            'ttl' => $this->default_ttl,
        ];
    }
}
