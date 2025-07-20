<?php
/**
 * Vortex Database Manager
 * 
 * Handles all database operations for the VORTEX AI Engine plugin
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Database Manager Class
 */
class Vortex_Database_Manager {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * WordPress database object
     */
    private $wpdb;
    
    /**
     * Database tables
     */
    private $tables = array();
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->init_tables();
    }
    
    /**
     * Initialize table names
     */
    private function init_tables() {
        $this->tables = array(
            'artworks' => $this->wpdb->prefix . 'vortex_artworks',
            'artists' => $this->wpdb->prefix . 'vortex_artists',
            'transactions' => $this->wpdb->prefix . 'vortex_transactions',
            'ai_generations' => $this->wpdb->prefix . 'vortex_ai_generations',
            'smart_contracts' => $this->wpdb->prefix . 'vortex_smart_contracts',
            'subscriptions' => $this->wpdb->prefix . 'vortex_subscriptions',
            'artist_journey' => $this->wpdb->prefix . 'vortex_artist_journey',
            'market_analysis' => $this->wpdb->prefix . 'vortex_market_analysis',
            'system_logs' => $this->wpdb->prefix . 'vortex_system_logs',
            'ai_agents_status' => $this->wpdb->prefix . 'vortex_ai_agents_status'
        );
    }
    
    /**
     * Create all database tables
     */
    public function create_tables() {
        $this->create_artworks_table();
        $this->create_artists_table();
        $this->create_transactions_table();
        $this->create_ai_generations_table();
        $this->create_smart_contracts_table();
        $this->create_subscriptions_table();
        $this->create_artist_journey_table();
        $this->create_market_analysis_table();
        $this->create_system_logs_table();
        $this->create_ai_agents_status_table();
    }
    
    /**
     * Create artworks table
     */
    private function create_artworks_table() {
        $table_name = $this->tables['artworks'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            artist_id bigint(20) NOT NULL,
            ai_agent varchar(50) DEFAULT 'HURAII',
            prompt text,
            image_url varchar(500),
            metadata longtext,
            price decimal(10,2) DEFAULT 0.00,
            status varchar(50) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY artist_id (artist_id),
            KEY status (status),
            KEY ai_agent (ai_agent)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create artists table
     */
    private function create_artists_table() {
        $table_name = $this->tables['artists'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            artist_name varchar(255) NOT NULL,
            bio text,
            avatar_url varchar(500),
            wallet_address varchar(255),
            subscription_tier varchar(50) DEFAULT 'starter',
            total_sales decimal(10,2) DEFAULT 0.00,
            total_artworks int(11) DEFAULT 0,
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY subscription_tier (subscription_tier),
            KEY status (status)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create transactions table
     */
    private function create_transactions_table() {
        $table_name = $this->tables['transactions'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_hash varchar(255) NOT NULL,
            artwork_id bigint(20) NOT NULL,
            seller_id bigint(20) NOT NULL,
            buyer_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            marketplace_fee decimal(10,2) DEFAULT 0.00,
            creator_royalty decimal(10,2) DEFAULT 0.00,
            artist_royalty decimal(10,2) DEFAULT 0.00,
            transaction_type varchar(50) DEFAULT 'sale',
            blockchain_network varchar(50) DEFAULT 'solana',
            status varchar(50) DEFAULT 'pending',
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_hash (transaction_hash),
            KEY artwork_id (artwork_id),
            KEY seller_id (seller_id),
            KEY buyer_id (buyer_id),
            KEY status (status)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create AI generations table
     */
    private function create_ai_generations_table() {
        $table_name = $this->tables['ai_generations'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_name varchar(50) NOT NULL,
            prompt text NOT NULL,
            result_data longtext,
            status varchar(50) DEFAULT 'processing',
            processing_time int(11) DEFAULT 0,
            gpu_used varchar(100),
            cost decimal(10,4) DEFAULT 0.0000,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            KEY agent_name (agent_name),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create smart contracts table
     */
    private function create_smart_contracts_table() {
        $table_name = $this->tables['smart_contracts'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            contract_address varchar(255) NOT NULL,
            contract_type varchar(50) NOT NULL,
            artwork_id bigint(20),
            artist_id bigint(20),
            contract_data longtext,
            deployment_hash varchar(255),
            network varchar(50) DEFAULT 'solana',
            status varchar(50) DEFAULT 'deployed',
            gas_used bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            deployed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY contract_address (contract_address),
            KEY contract_type (contract_type),
            KEY artwork_id (artwork_id),
            KEY artist_id (artist_id),
            KEY status (status)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create subscriptions table
     */
    private function create_subscriptions_table() {
        $table_name = $this->tables['subscriptions'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            subscription_tier varchar(50) NOT NULL,
            payment_provider varchar(50) DEFAULT 'stripe',
            subscription_id varchar(255),
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            status varchar(50) DEFAULT 'active',
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            auto_renew boolean DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY subscription_tier (subscription_tier),
            KEY status (status),
            KEY end_date (end_date)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create artist journey table
     */
    private function create_artist_journey_table() {
        $table_name = $this->tables['artist_journey'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            artist_id bigint(20) NOT NULL,
            milestone_type varchar(50) NOT NULL,
            milestone_data longtext,
            completed boolean DEFAULT 0,
            completed_at datetime NULL,
            points_earned int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY artist_id (artist_id),
            KEY milestone_type (milestone_type),
            KEY completed (completed)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create market analysis table
     */
    private function create_market_analysis_table() {
        $table_name = $this->tables['market_analysis'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            analysis_type varchar(50) NOT NULL,
            data_source varchar(100),
            analysis_data longtext,
            confidence_score decimal(3,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY analysis_type (analysis_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create system logs table
     */
    private function create_system_logs_table() {
        $table_name = $this->tables['system_logs'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_level varchar(20) NOT NULL,
            component varchar(100) NOT NULL,
            message text NOT NULL,
            context longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY log_level (log_level),
            KEY component (component),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create AI agents status table
     */
    private function create_ai_agents_status_table() {
        $table_name = $this->tables['ai_agents_status'];
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_name varchar(50) NOT NULL,
            status varchar(50) DEFAULT 'active',
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            performance_metrics longtext,
            error_count int(11) DEFAULT 0,
            success_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY agent_name (agent_name),
            KEY status (status),
            KEY last_activity (last_activity)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Execute SQL with error handling
     */
    private function execute_sql($sql) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if ($this->wpdb->last_error) {
            error_log('VORTEX Database Error: ' . $this->wpdb->last_error);
        }
    }
    
    /**
     * Get table name
     */
    public function get_table($table_name) {
        return isset($this->tables[$table_name]) ? $this->tables[$table_name] : false;
    }
    
    /**
     * Insert record
     */
    public function insert($table_name, $data) {
        $table = $this->get_table($table_name);
        if (!$table) {
            return false;
        }
        
        $result = $this->wpdb->insert($table, $data);
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Update record
     */
    public function update($table_name, $data, $where) {
        $table = $this->get_table($table_name);
        if (!$table) {
            return false;
        }
        
        return $this->wpdb->update($table, $data, $where);
    }
    
    /**
     * Delete record
     */
    public function delete($table_name, $where) {
        $table = $this->get_table($table_name);
        if (!$table) {
            return false;
        }
        
        return $this->wpdb->delete($table, $where);
    }
    
    /**
     * Get single record
     */
    public function get_row($table_name, $where = array(), $order_by = '') {
        $table = $this->get_table($table_name);
        if (!$table) {
            return false;
        }
        
        $sql = "SELECT * FROM $table";
        
        if (!empty($where)) {
            $where_clause = array();
            foreach ($where as $key => $value) {
                $where_clause[] = $this->wpdb->prepare("$key = %s", $value);
            }
            $sql .= " WHERE " . implode(' AND ', $where_clause);
        }
        
        if ($order_by) {
            $sql .= " ORDER BY $order_by";
        }
        
        $sql .= " LIMIT 1";
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * Get multiple records
     */
    public function get_results($table_name, $where = array(), $order_by = '', $limit = '') {
        $table = $this->get_table($table_name);
        if (!$table) {
            return false;
        }
        
        $sql = "SELECT * FROM $table";
        
        if (!empty($where)) {
            $where_clause = array();
            foreach ($where as $key => $value) {
                $where_clause[] = $this->wpdb->prepare("$key = %s", $value);
            }
            $sql .= " WHERE " . implode(' AND ', $where_clause);
        }
        
        if ($order_by) {
            $sql .= " ORDER BY $order_by";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Log system message
     */
    public function log($level, $component, $message, $context = array()) {
        $data = array(
            'log_level' => $level,
            'component' => $component,
            'message' => $message,
            'context' => json_encode($context)
        );
        
        return $this->insert('system_logs', $data);
    }
    
    /**
     * Clean old logs
     */
    public function clean_old_logs($days = 30) {
        $table = $this->get_table('system_logs');
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $sql = $this->wpdb->prepare(
            "DELETE FROM $table WHERE created_at < %s",
            $cutoff_date
        );
        
        return $this->wpdb->query($sql);
    }
    
    /**
     * Get database statistics
     */
    public function get_stats() {
        $stats = array();
        
        foreach ($this->tables as $name => $table) {
            $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table");
            $stats[$name] = $count;
        }
        
        return $stats;
    }
    
    /**
     * Optimize tables
     */
    public function optimize_tables() {
        foreach ($this->tables as $name => $table) {
            $this->wpdb->query("OPTIMIZE TABLE $table");
        }
    }
} 