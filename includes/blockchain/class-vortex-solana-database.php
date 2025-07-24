<?php
/**
 * VORTEX AI Engine - Solana Database Manager
 * 
 * Database management for Solana blockchain data
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Solana Database Manager Class
 * 
 * Handles database operations for Solana blockchain data
 */
class Vortex_Solana_Database_Manager {
    
    /**
     * Initialize database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Solana metrics table
        $table_metrics = $wpdb->prefix . 'vortex_solana_metrics';
        $sql_metrics = "CREATE TABLE $table_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            network varchar(20) NOT NULL,
            slot bigint(20) NOT NULL DEFAULT 0,
            block_height bigint(20) NOT NULL DEFAULT 0,
            transaction_count bigint(20) NOT NULL DEFAULT 0,
            validator_count int(11) NOT NULL DEFAULT 0,
            supply bigint(20) NOT NULL DEFAULT 0,
            cluster_nodes int(11) NOT NULL DEFAULT 0,
            performance_data longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY network (network),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Solana programs table
        $table_programs = $wpdb->prefix . 'vortex_solana_programs';
        $sql_programs = "CREATE TABLE $table_programs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            program_id varchar(44) NOT NULL,
            network varchar(20) NOT NULL,
            program_name varchar(255),
            program_type varchar(50),
            deployed_at datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            metadata longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY program_id_network (program_id, network),
            KEY network (network),
            KEY status (status)
        ) $charset_collate;";
        
        // Solana health checks table
        $table_health = $wpdb->prefix . 'vortex_solana_health';
        $sql_health = "CREATE TABLE $table_health (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            network varchar(20) NOT NULL,
            rpc_status tinyint(1) NOT NULL DEFAULT 0,
            metrics_status tinyint(1) NOT NULL DEFAULT 0,
            validator_status tinyint(1) NOT NULL DEFAULT 0,
            timestamp datetime NOT NULL,
            PRIMARY KEY (id),
            KEY network (network),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Solana transactions table
        $table_transactions = $wpdb->prefix . 'vortex_solana_transactions';
        $sql_transactions = "CREATE TABLE $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            signature varchar(88) NOT NULL,
            network varchar(20) NOT NULL,
            from_address varchar(44),
            to_address varchar(44),
            amount bigint(20),
            program_id varchar(44),
            status varchar(20) NOT NULL DEFAULT 'pending',
            block_time bigint(20),
            slot bigint(20),
            fee bigint(20),
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY signature_network (signature, network),
            KEY network (network),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY program_id (program_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Solana accounts table
        $table_accounts = $wpdb->prefix . 'vortex_solana_accounts';
        $sql_accounts = "CREATE TABLE $table_accounts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            public_key varchar(44) NOT NULL,
            network varchar(20) NOT NULL,
            balance bigint(20) NOT NULL DEFAULT 0,
            owner varchar(44),
            executable tinyint(1) NOT NULL DEFAULT 0,
            lamports bigint(20) NOT NULL DEFAULT 0,
            rent_epoch bigint(20) NOT NULL DEFAULT 0,
            data longtext,
            last_updated datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY public_key_network (public_key, network),
            KEY network (network),
            KEY owner (owner)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_metrics);
        dbDelta($sql_programs);
        dbDelta($sql_health);
        dbDelta($sql_transactions);
        dbDelta($accounts);
        
        error_log('VORTEX AI Engine: Solana database tables created');
    }
    
    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'vortex_solana_metrics',
            $wpdb->prefix . 'vortex_solana_programs',
            $wpdb->prefix . 'vortex_solana_health',
            $wpdb->prefix . 'vortex_solana_transactions',
            $wpdb->prefix . 'vortex_solana_accounts'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        error_log('VORTEX AI Engine: Solana database tables dropped');
    }
    
    /**
     * Insert metrics data
     */
    public static function insert_metrics($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'vortex_solana_metrics',
            $data
        );
    }
    
    /**
     * Get metrics data
     */
    public static function get_metrics($network = null, $limit = 100) {
        global $wpdb;
        
        $where = '';
        if ($network) {
            $where = $wpdb->prepare("WHERE network = %s", $network);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_solana_metrics {$where} ORDER BY created_at DESC LIMIT {$limit}",
            ARRAY_A
        );
    }
    
    /**
     * Insert program data
     */
    public static function insert_program($data) {
        global $wpdb;
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->insert(
            $wpdb->prefix . 'vortex_solana_programs',
            $data
        );
    }
    
    /**
     * Get program data
     */
    public static function get_program($program_id, $network) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_solana_programs WHERE program_id = %s AND network = %s",
                $program_id,
                $network
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get all programs
     */
    public static function get_programs($network = null, $status = null) {
        global $wpdb;
        
        $where_conditions = [];
        $where_values = [];
        
        if ($network) {
            $where_conditions[] = 'network = %s';
            $where_values[] = $network;
        }
        
        if ($status) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $status;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}vortex_solana_programs {$where_clause} ORDER BY deployed_at DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Insert health check data
     */
    public static function insert_health_check($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'vortex_solana_health',
            $data
        );
    }
    
    /**
     * Get health check data
     */
    public static function get_health_checks($network = null, $limit = 100) {
        global $wpdb;
        
        $where = '';
        if ($network) {
            $where = $wpdb->prepare("WHERE network = %s", $network);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_solana_health {$where} ORDER BY timestamp DESC LIMIT {$limit}",
            ARRAY_A
        );
    }
    
    /**
     * Insert transaction data
     */
    public static function insert_transaction($data) {
        global $wpdb;
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->insert(
            $wpdb->prefix . 'vortex_solana_transactions',
            $data
        );
    }
    
    /**
     * Update transaction status
     */
    public static function update_transaction_status($signature, $network, $status, $block_time = null, $slot = null) {
        global $wpdb;
        
        $update_data = [
            'status' => $status,
            'updated_at' => current_time('mysql')
        ];
        
        if ($block_time) {
            $update_data['block_time'] = $block_time;
        }
        
        if ($slot) {
            $update_data['slot'] = $slot;
        }
        
        return $wpdb->update(
            $wpdb->prefix . 'vortex_solana_transactions',
            $update_data,
            [
                'signature' => $signature,
                'network' => $network
            ]
        );
    }
    
    /**
     * Get transaction data
     */
    public static function get_transaction($signature, $network) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_solana_transactions WHERE signature = %s AND network = %s",
                $signature,
                $network
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get transactions
     */
    public static function get_transactions($network = null, $status = null, $limit = 100) {
        global $wpdb;
        
        $where_conditions = [];
        $where_values = [];
        
        if ($network) {
            $where_conditions[] = 'network = %s';
            $where_values[] = $network;
        }
        
        if ($status) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $status;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}vortex_solana_transactions {$where_clause} ORDER BY created_at DESC LIMIT {$limit}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Insert account data
     */
    public static function insert_account($data) {
        global $wpdb;
        
        $data['created_at'] = current_time('mysql');
        $data['last_updated'] = current_time('mysql');
        
        return $wpdb->insert(
            $wpdb->prefix . 'vortex_solana_accounts',
            $data,
            ['%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s']
        );
    }
    
    /**
     * Update account data
     */
    public static function update_account($public_key, $network, $data) {
        global $wpdb;
        
        $data['last_updated'] = current_time('mysql');
        
        return $wpdb->update(
            $wpdb->prefix . 'vortex_solana_accounts',
            $data,
            [
                'public_key' => $public_key,
                'network' => $network
            ]
        );
    }
    
    /**
     * Get account data
     */
    public static function get_account($public_key, $network) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_solana_accounts WHERE public_key = %s AND network = %s",
                $public_key,
                $network
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get accounts
     */
    public static function get_accounts($network = null, $owner = null, $limit = 100) {
        global $wpdb;
        
        $where_conditions = [];
        $where_values = [];
        
        if ($network) {
            $where_conditions[] = 'network = %s';
            $where_values[] = $network;
        }
        
        if ($owner) {
            $where_conditions[] = 'owner = %s';
            $where_values[] = $owner;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}vortex_solana_accounts {$where_clause} ORDER BY last_updated DESC LIMIT {$limit}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get database statistics
     */
    public static function get_statistics() {
        global $wpdb;
        
        $stats = [];
        
        // Metrics count
        $stats['metrics_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_solana_metrics");
        
        // Programs count
        $stats['programs_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_solana_programs");
        
        // Health checks count
        $stats['health_checks_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_solana_health");
        
        // Transactions count
        $stats['transactions_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_solana_transactions");
        
        // Accounts count
        $stats['accounts_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_solana_accounts");
        
        // Recent activity
        $stats['recent_transactions'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_solana_transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        $stats['recent_metrics'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_solana_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        return $stats;
    }
    
    /**
     * Clean up old data
     */
    public static function cleanup_old_data($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Clean up old metrics
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vortex_solana_metrics WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        // Clean up old health checks
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vortex_solana_health WHERE timestamp < %s",
                $cutoff_date
            )
        );
        
        // Clean up old transactions (keep successful ones longer)
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vortex_solana_transactions WHERE created_at < %s AND status != 'confirmed'",
                $cutoff_date
            )
        );
        
        error_log("VORTEX AI Engine: Cleaned up Solana data older than {$days} days");
    }
} 