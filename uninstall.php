<?php
/**
 * Nivoda API Integration Uninstall
 *
 * @package NivodaAPI
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin options from database
 */
function nivoda_api_uninstall_cleanup() {
    // Remove plugin options
    delete_option('nivoda_environment');
    delete_option('nivoda_username'); 
    delete_option('nivoda_password');
    delete_option('nivoda_last_order_customer');
    delete_option('nivoda_api_version');

    // Remove any transients
    delete_transient('nivoda_api_token');
    delete_transient('nivoda_connection_test');

    // Remove user meta if any
    delete_metadata('user', 0, 'nivoda_api_preferences', '', true);

    // Clear any cached data
    wp_cache_flush();
}

// Run cleanup
nivoda_api_uninstall_cleanup();
