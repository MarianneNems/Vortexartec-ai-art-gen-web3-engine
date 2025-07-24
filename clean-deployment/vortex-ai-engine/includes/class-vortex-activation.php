<?php
/**
 * Vortex AI Engine - Activation Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Activation Class
 *
 * Handles plugin activation and deactivation.
 */
class Vortex_Activation {

    /**
     * Activate plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create required directories
        self::create_directories();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Incentives table
        $incentives_table = $wpdb->prefix . 'vortex_incentives';
        $incentives_sql = "CREATE TABLE $incentives_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            tola_amount decimal(18,8) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            distributed_at datetime NULL,
            transaction_hash varchar(255) NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY status (status)
        ) $charset_collate;";

        // Wallets table
        $wallets_table = $wpdb->prefix . 'vortex_wallets';
        $wallets_sql = "CREATE TABLE $wallets_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tola_balance decimal(18,8) DEFAULT 0.00000000,
            usd_balance decimal(10,2) DEFAULT 0.00,
            wallet_address varchar(255) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY wallet_address (wallet_address)
        ) $charset_collate;";

        // Accounting table
        $accounting_table = $wpdb->prefix . 'vortex_accounting';
        $accounting_sql = "CREATE TABLE $accounting_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            tola_amount decimal(18,8) NOT NULL,
            usd_amount decimal(10,2) NOT NULL,
            conversion_rate decimal(10,8) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            transaction_hash varchar(255) NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($incentives_sql);
        dbDelta($wallets_sql);
        dbDelta($accounting_sql);
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_options = array(
            'vortex_version' => '3.0.0',
            'vortex_installation_date' => current_time('mysql'),
            'vortex_tola_conversion_rate' => 0.50,
            'vortex_minimum_conversion' => 100,
            'vortex_artist_threshold' => 1000,
            'vortex_enable_incentives' => true,
            'vortex_enable_conversions' => false
        );

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    /**
     * Create required directories
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $vortex_dir = $upload_dir['basedir'] . '/vortex-ai-engine';

        if (!file_exists($vortex_dir)) {
            wp_mkdir_p($vortex_dir);
        }

        // Create subdirectories
        $subdirs = array('logs', 'cache', 'backups', 'temp');
        foreach ($subdirs as $subdir) {
            $dir = $vortex_dir . '/' . $subdir;
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
} 