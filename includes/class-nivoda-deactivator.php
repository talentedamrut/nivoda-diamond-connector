<?php
/**
 * Fired during plugin deactivation
 */

class Nivoda_Deactivator {

    public static function deactivate() {
        // Clear cached tokens
        delete_transient('nivoda_api_token');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
