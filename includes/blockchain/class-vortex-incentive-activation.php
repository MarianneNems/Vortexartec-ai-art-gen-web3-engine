<?php
/**
 * VORTEX AI Engine - Incentive System Activation
 * 
 * Handles activation and setup of the incentive system
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Incentive System Activation Class
 * 
 * Handles activation and setup of the incentive system
 */
class Vortex_Incentive_Activation {
    
    /**
     * Activate the incentive system
     */
    public static function activate() {
        try {
            // Create database tables
            self::create_database_tables();
            
            // Set default options
            self::set_default_options();
            
            // Create default roles
            self::create_default_roles();
            
            // Initialize components
            self::initialize_components();
            
            // Schedule events
            self::schedule_events();
            
            // Log activation
            self::log_activation();
            
            error_log('VORTEX AI Engine: Incentive system activated successfully');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Incentive system activation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create database tables
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // User wallets table
        $table_name = $wpdb->prefix . 'vortex_user_wallets';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(255) NOT NULL,
            wallet_type varchar(50) DEFAULT 'solana',
            balance decimal(20,8) DEFAULT 0.00000000,
            platform_credits decimal(20,8) DEFAULT 0.00000000,
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY wallet_address (wallet_address),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Token transactions table
        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_hash varchar(255) NOT NULL,
            from_address varchar(255),
            to_address varchar(255) NOT NULL,
            amount decimal(20,8) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_hash (transaction_hash),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY transaction_type (transaction_type),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Financial transactions table
        $table_name = $wpdb->prefix . 'vortex_financial_transactions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            currency varchar(10) DEFAULT 'TOLA',
            usdc_equivalent decimal(20,8) DEFAULT 0.00000000,
            transaction_category varchar(50) NOT NULL,
            description text,
            metadata longtext,
            status varchar(50) DEFAULT 'completed',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_id (transaction_id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY transaction_category (transaction_category),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Incentive distributions table
        $table_name = $wpdb->prefix . 'vortex_incentive_distributions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            incentive_type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            transaction_hash varchar(255),
            context_data longtext,
            platform_credit_only tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY incentive_type (incentive_type),
            KEY platform_credit_only (platform_credit_only),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Platform credits table
        $table_name = $wpdb->prefix . 'vortex_platform_credits';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            credit_type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            balance_before decimal(20,8) NOT NULL,
            balance_after decimal(20,8) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY credit_type (credit_type),
            KEY transaction_type (transaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Conversion requests table
        $table_name = $wpdb->prefix . 'vortex_conversion_requests';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            request_id varchar(255) NOT NULL,
            tola_amount decimal(20,8) NOT NULL,
            usdc_amount decimal(20,8) NOT NULL,
            conversion_fee decimal(20,8) DEFAULT 0.00000000,
            wallet_address varchar(255) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            transaction_hash varchar(255),
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY request_id (request_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Conversion limits table
        $table_name = $wpdb->prefix . 'vortex_conversion_limits';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            limit_type varchar(50) NOT NULL,
            amount_used decimal(20,8) DEFAULT 0.00000000,
            limit_amount decimal(20,8) NOT NULL,
            reset_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_limit (user_id, limit_type, reset_date),
            KEY user_id (user_id),
            KEY reset_date (reset_date)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Financial reports table
        $table_name = $wpdb->prefix . 'vortex_financial_reports';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            report_period varchar(50) NOT NULL,
            report_data longtext NOT NULL,
            total_tola_distributed decimal(20,8) DEFAULT 0.00000000,
            total_usdc_equivalent decimal(20,8) DEFAULT 0.00000000,
            total_transactions int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY report_type (report_type),
            KEY report_period (report_period),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Integration logs table
        $table_name = $wpdb->prefix . 'vortex_integration_logs';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            component varchar(50) NOT NULL,
            action varchar(50) NOT NULL,
            user_id bigint(20),
            data longtext,
            status varchar(50) DEFAULT 'success',
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY component (component),
            KEY action (action),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // System health table
        $table_name = $wpdb->prefix . 'vortex_system_health';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            component varchar(50) NOT NULL,
            status varchar(50) DEFAULT 'healthy',
            last_check datetime DEFAULT CURRENT_TIMESTAMP,
            health_data longtext,
            PRIMARY KEY (id),
            UNIQUE KEY component (component)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        // Incentive system options
        add_option('vortex_conversion_enabled', false);
        add_option('vortex_platform_credit_restriction', true);
        add_option('vortex_milestone_artist_count', 1000);
        add_option('vortex_min_conversion_amount', 10);
        add_option('vortex_max_conversion_amount', 10000);
        add_option('vortex_conversion_fee', 0.01);
        
        // TOLA token options
        add_option('vortex_tola_usdc_rate', 1.0);
        add_option('vortex_decimal_places', 8);
        add_option('vortex_tola_contract_address', '');
        add_option('vortex_treasury_wallet', '');
        add_option('vortex_blockchain_network', 'solana');
        
        // System options
        add_option('vortex_incentive_system_version', '3.0.0');
        add_option('vortex_incentive_system_activated', current_time('mysql'));
    }
    
    /**
     * Create default roles
     */
    private static function create_default_roles() {
        // Create artist role if it doesn't exist
        if (!get_role('artist')) {
            add_role('artist', 'Artist', [
                'read' => true,
                'upload_files' => true,
                'edit_posts' => true,
                'publish_posts' => true,
                'vortex_upload_artwork' => true,
                'vortex_claim_incentives' => true,
                'vortex_connect_wallet' => true
            ]);
        }
        
        // Create collector role if it doesn't exist
        if (!get_role('collector')) {
            add_role('collector', 'Collector', [
                'read' => true,
                'vortex_purchase_artwork' => true,
                'vortex_claim_incentives' => true,
                'vortex_connect_wallet' => true
            ]);
        }
    }
    
    /**
     * Initialize components
     */
    private static function initialize_components() {
        // Load and initialize incentive system components
        require_once plugin_dir_path(__FILE__) . 'class-vortex-incentive-loader.php';
        
        $loader = new Vortex_Incentive_Loader();
        $loader->init();
    }
    
    /**
     * Schedule events
     */
    private static function schedule_events() {
        // Clear existing schedules
        wp_clear_scheduled_hook('vortex_daily_incentive_audit');
        wp_clear_scheduled_hook('vortex_daily_accounting_report');
        wp_clear_scheduled_hook('vortex_weekly_incentive_report');
        wp_clear_scheduled_hook('vortex_monthly_accounting_report');
        wp_clear_scheduled_hook('vortex_fraud_detection');
        
        // Schedule new events
        if (!wp_next_scheduled('vortex_daily_incentive_audit')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_incentive_audit');
        }
        
        if (!wp_next_scheduled('vortex_daily_accounting_report')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_accounting_report');
        }
        
        if (!wp_next_scheduled('vortex_weekly_incentive_report')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_incentive_report');
        }
        
        if (!wp_next_scheduled('vortex_monthly_accounting_report')) {
            wp_schedule_event(time(), 'monthly', 'vortex_monthly_accounting_report');
        }
        
        if (!wp_next_scheduled('vortex_fraud_detection')) {
            wp_schedule_event(time(), 'six_hours', 'vortex_fraud_detection');
        }
    }
    
    /**
     * Log activation
     */
    private static function log_activation() {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'vortex_integration_logs', [
            'component' => 'activation',
            'action' => 'system_activated',
            'user_id' => get_current_user_id(),
            'data' => json_encode([
                'version' => '3.0.0',
                'timestamp' => current_time('mysql'),
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version')
            ]),
            'status' => 'success'
        ]);
    }
    
    /**
     * Deactivate the incentive system
     */
    public static function deactivate() {
        try {
            // Clear scheduled events
            wp_clear_scheduled_hook('vortex_daily_incentive_audit');
            wp_clear_scheduled_hook('vortex_daily_accounting_report');
            wp_clear_scheduled_hook('vortex_weekly_incentive_report');
            wp_clear_scheduled_hook('vortex_monthly_accounting_report');
            wp_clear_scheduled_hook('vortex_fraud_detection');
            
            // Log deactivation
            self::log_deactivation();
            
            error_log('VORTEX AI Engine: Incentive system deactivated');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Incentive system deactivation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Log deactivation
     */
    private static function log_deactivation() {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'vortex_integration_logs', [
            'component' => 'activation',
            'action' => 'system_deactivated',
            'user_id' => get_current_user_id(),
            'data' => json_encode([
                'timestamp' => current_time('mysql')
            ]),
            'status' => 'success'
        ]);
    }
    
    /**
     * Check if incentive system is active
     */
    public static function is_active() {
        return get_option('vortex_incentive_system_activated') !== false;
    }
    
    /**
     * Get activation status
     */
    public static function get_activation_status() {
        return [
            'active' => self::is_active(),
            'version' => get_option('vortex_incentive_system_version', 'unknown'),
            'activated_at' => get_option('vortex_incentive_system_activated', 'unknown'),
            'tables_exist' => self::check_tables_exist(),
            'options_set' => self::check_options_set(),
            'roles_created' => self::check_roles_created()
        ];
    }
    
    /**
     * Check if tables exist
     */
    private static function check_tables_exist() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'vortex_user_wallets',
            $wpdb->prefix . 'vortex_token_transactions',
            $wpdb->prefix . 'vortex_financial_transactions',
            $wpdb->prefix . 'vortex_incentive_distributions',
            $wpdb->prefix . 'vortex_platform_credits',
            $wpdb->prefix . 'vortex_conversion_requests',
            $wpdb->prefix . 'vortex_conversion_limits',
            $wpdb->prefix . 'vortex_financial_reports',
            $wpdb->prefix . 'vortex_integration_logs',
            $wpdb->prefix . 'vortex_system_health'
        ];
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if options are set
     */
    private static function check_options_set() {
        $required_options = [
            'vortex_conversion_enabled',
            'vortex_platform_credit_restriction',
            'vortex_milestone_artist_count',
            'vortex_incentive_system_version'
        ];
        
        foreach ($required_options as $option) {
            if (get_option($option) === false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if roles are created
     */
    private static function check_roles_created() {
        return get_role('artist') !== null && get_role('collector') !== null;
    }
} 