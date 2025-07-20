<?php
/**
 * VORTEX AI Engine - Database Setup
 * Handles creation and management of database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_DBSetup {
    /**
     * Create all required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create feedback table
        $table_name = $wpdb->prefix . 'vortex_feedback';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            action varchar(64) NOT NULL,
            request_id varchar(128) NOT NULL,
            liked tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $result = dbDelta($sql);
        
        // Log any errors
        if (!empty($wpdb->last_error)) {
            error_log('[VortexAI DB Setup] Database error: ' . $wpdb->last_error);
        }
        
        // Update database version
        update_option('vortex_ai_db_version', '1.0');
        
        return $result;
    }
    
    /**
     * Drop database tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'vortex_feedback',
            $wpdb->prefix . 'vortex_tier_api_keys',
            $wpdb->prefix . 'vortex_tier_usage_log'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove database version
        delete_option('vortex_ai_db_version');
    }
    
    /**
     * Check if tables exist and are up to date
     */
    public static function check_tables() {
        global $wpdb;
        
        $current_version = get_option('vortex_ai_db_version', '0');
        
        if (version_compare($current_version, '1.0', '<')) {
            self::create_tables();
        }
        
        return true;
    }
} 