<?php
/**
 * Fired during plugin activation
 */

class Nivoda_Activator {

    public static function activate() {
        // Set default options
        if (!get_option('nivoda_environment')) {
            add_option('nivoda_environment', 'staging');
        }

        if (!get_option('nivoda_username')) {
            add_option('nivoda_username', '');
        }

        if (!get_option('nivoda_password')) {
            add_option('nivoda_password', '');
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
