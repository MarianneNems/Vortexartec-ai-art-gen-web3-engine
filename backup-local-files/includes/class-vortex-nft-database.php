<?php
/**
 * VORTEX NFT Database Management
 * Handles database operations for TOLA NFT data
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_NFT_Database {
    
    public function __construct() {
        add_action('init', [$this, 'maybe_create_tables']);
    }
    
    /**
     * Create NFT tables if they don't exist
     */
    public function maybe_create_tables() {
        $version = get_option('vortex_nft_db_version', '0');
        
        if (version_compare($version, '1.0.0', '<')) {
            $this->create_nft_tables();
            update_option('vortex_nft_db_version', '1.0.0');
        }
    }
    
    /**
     * Create NFT tables
     */
    public function create_nft_tables() {
        global $wpdb;
        
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create NFT metadata table
        $table_name = $wpdb->prefix . 'vortex_nft_metadata';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            nft_id varchar(128) NOT NULL,
            metadata text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY nft_id (nft_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Log any errors
        if (!empty($wpdb->last_error)) {
            error_log('[VortexAI NFT Database] Error creating tables: ' . $wpdb->last_error);
        }
    }
    
    /**
     * Get NFT metadata
     */
    public function get_nft_metadata($nft_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_nft_metadata';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE nft_id = %s",
            $nft_id
        ));
        
        return $result;
    }
    
    /**
     * Save NFT metadata
     */
    public function save_nft_metadata($user_id, $nft_id, $metadata) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_nft_metadata';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'nft_id' => $nft_id,
                'metadata' => json_encode($metadata)
            ],
            ['%d', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[VortexAI NFT Database] Error saving metadata: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
} 