<?php
/**
 * VORTEX AI Engine - Log Integration
 * 
 * Main integration file that initializes the real-time logging system
 * Connects WordPress events with GitHub synchronization
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 * @since 2024-01-01
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log Integration Class
 * 
 * Main class that initializes and coordinates the logging system
 */
class VORTEX_Log_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the logging system
     */
    private function init() {
        // Create database tables on plugin activation
        register_activation_hook(VORTEX_AI_ENGINE_FILE, array($this, 'activate'));
        
        // Initialize components
        add_action('init', array($this, 'init_components'));
        
        // Setup cron jobs
        add_action('init', array($this, 'setup_cron_jobs'));
        
        // Add admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        VORTEX_Log_Database::create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Initialize the real-time logger
        VORTEX_Realtime_Logger::get_instance();
        
        // Initialize admin interface if in admin
        if (is_admin()) {
            new VORTEX_Log_Admin();
            new VORTEX_GitHub_Settings();
        }
    }
    
    /**
     * Setup cron jobs
     */
    public function setup_cron_jobs() {
        // Add custom cron interval
        add_filter('cron_schedules', array($this, 'add_custom_cron_interval'));
        
        // Schedule GitHub sync if enabled
        if (get_option('vortex_github_logging_enabled', false)) {
            if (!wp_next_scheduled('vortex_github_sync')) {
                $interval = get_option('vortex_github_sync_interval', 300);
                wp_schedule_event(time(), 'vortex_custom_interval', 'vortex_github_sync');
            }
        }
        
        // Schedule log cleanup
        if (!wp_next_scheduled('vortex_log_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_log_cleanup');
        }
        
        // Schedule statistics update
        if (!wp_next_scheduled('vortex_log_statistics')) {
            wp_schedule_event(time(), 'hourly', 'vortex_log_statistics');
        }
    }
    
    /**
     * Add custom cron interval
     */
    public function add_custom_cron_interval($schedules) {
        $interval = get_option('vortex_github_sync_interval', 300);
        
        $schedules['vortex_custom_interval'] = array(
            'interval' => $interval,
            'display' => sprintf('Every %d seconds', $interval)
        );
        
        return $schedules;
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        // GitHub sync
        if (get_option('vortex_github_logging_enabled', false)) {
            $interval = get_option('vortex_github_sync_interval', 300);
            wp_schedule_event(time(), 'vortex_custom_interval', 'vortex_github_sync');
        }
        
        // Log cleanup
        wp_schedule_event(time(), 'daily', 'vortex_log_cleanup');
        
        // Statistics update
        wp_schedule_event(time(), 'hourly', 'vortex_log_statistics');
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'vortex_github_logging_enabled' => false,
            'vortex_github_repository' => '',
            'vortex_github_token' => '',
            'vortex_github_branch' => 'main',
            'vortex_github_sync_interval' => 300,
            'vortex_github_encrypt_sensitive' => true,
            'vortex_github_exclude_patterns' => "password\ntoken\nkey\nsecret\nauth\ncredential\nprivate",
            'vortex_log_retention_days' => 30,
            'vortex_log_max_entries' => 10000
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(VORTEX_AI_ENGINE_FILE), array($this, 'add_settings_link'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=vortex-github-settings') . '">' . __('GitHub Settings', 'vortex-ai-engine') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if GitHub integration is enabled but not configured
        if (get_option('vortex_github_logging_enabled', false)) {
            $repository = get_option('vortex_github_repository');
            $token = get_option('vortex_github_token');
            
            if (!$repository || !$token) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong>VORTEX AI Engine:</strong> 
                        GitHub integration is enabled but not fully configured. 
                        <a href="<?php echo admin_url('admin.php?page=vortex-github-settings'); ?>">Configure now</a>
                    </p>
                </div>
                <?php
            }
        }
        
        // Check for failed syncs
        $failed_syncs = $this->get_recent_failed_syncs();
        if ($failed_syncs > 0) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>VORTEX AI Engine:</strong> 
                    <?php echo $failed_syncs; ?> GitHub sync(s) failed recently. 
                    <a href="<?php echo admin_url('admin.php?page=vortex-github-settings'); ?>">Check settings</a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Get recent failed syncs
     */
    private function get_recent_failed_syncs() {
        global $wpdb;
        
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sync_table WHERE success = 0 AND sync_date >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
            24
        ));
    }
    
    /**
     * Get system health status
     */
    public function get_system_health() {
        $health = array(
            'database' => $this->check_database_health(),
            'github' => $this->check_github_health(),
            'storage' => $this->check_storage_health(),
            'performance' => $this->check_performance_health()
        );
        
        return $health;
    }
    
    /**
     * Check database health
     */
    private function check_database_health() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'vortex_logs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") === $logs_table;
        
        if (!$table_exists) {
            return array('status' => 'error', 'message' => 'Logs table does not exist');
        }
        
        // Check table size
        $table_size = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
        
        if ($table_size > 50000) {
            return array('status' => 'warning', 'message' => 'Large number of log entries: ' . number_format($table_size));
        }
        
        return array('status' => 'good', 'message' => 'Database healthy');
    }
    
    /**
     * Check GitHub health
     */
    private function check_github_health() {
        if (!get_option('vortex_github_logging_enabled', false)) {
            return array('status' => 'disabled', 'message' => 'GitHub integration disabled');
        }
        
        $repository = get_option('vortex_github_repository');
        $token = get_option('vortex_github_token');
        
        if (!$repository || !$token) {
            return array('status' => 'error', 'message' => 'GitHub not configured');
        }
        
        // Check last sync
        $last_sync = $this->get_last_sync_status();
        
        if ($last_sync && !$last_sync->success) {
            return array('status' => 'error', 'message' => 'Last sync failed: ' . $last_sync->error_message);
        }
        
        return array('status' => 'good', 'message' => 'GitHub integration healthy');
    }
    
    /**
     * Check storage health
     */
    private function check_storage_health() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/vortex-logs';
        
        if (!is_dir($log_dir)) {
            return array('status' => 'error', 'message' => 'Log directory does not exist');
        }
        
        if (!is_writable($log_dir)) {
            return array('status' => 'error', 'message' => 'Log directory not writable');
        }
        
        // Check disk space
        $free_space = disk_free_space($log_dir);
        $total_space = disk_total_space($log_dir);
        $usage_percent = (($total_space - $free_space) / $total_space) * 100;
        
        if ($usage_percent > 90) {
            return array('status' => 'warning', 'message' => 'Low disk space: ' . round($usage_percent, 1) . '% used');
        }
        
        return array('status' => 'good', 'message' => 'Storage healthy');
    }
    
    /**
     * Check performance health
     */
    private function check_performance_health() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'vortex_logs';
        
        // Check query performance
        $start_time = microtime(true);
        $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
        $query_time = microtime(true) - $start_time;
        
        if ($query_time > 1.0) {
            return array('status' => 'warning', 'message' => 'Slow database queries: ' . round($query_time, 2) . 's');
        }
        
        return array('status' => 'good', 'message' => 'Performance healthy');
    }
    
    /**
     * Get last sync status
     */
    private function get_last_sync_status() {
        global $wpdb;
        
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        
        return $wpdb->get_row(
            "SELECT * FROM $sync_table ORDER BY sync_date DESC LIMIT 1"
        );
    }
    
    /**
     * Get system statistics
     */
    public function get_system_statistics() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'vortex_logs';
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        
        $stats = array(
            'total_logs' => $wpdb->get_var("SELECT COUNT(*) FROM $logs_table"),
            'logs_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $logs_table WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            )),
            'logs_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $logs_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                7
            )),
            'total_syncs' => $wpdb->get_var("SELECT COUNT(*) FROM $sync_table"),
            'successful_syncs' => $wpdb->get_var("SELECT COUNT(*) FROM $sync_table WHERE success = 1"),
            'failed_syncs' => $wpdb->get_var("SELECT COUNT(*) FROM $sync_table WHERE success = 0"),
            'last_sync' => $wpdb->get_var("SELECT sync_date FROM $sync_table ORDER BY sync_date DESC LIMIT 1")
        );
        
        return $stats;
    }
    
    /**
     * Export system report
     */
    public function export_system_report() {
        $health = $this->get_system_health();
        $stats = $this->get_system_statistics();
        
        $report = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'health' => $health,
            'statistics' => $stats,
            'settings' => array(
                'github_enabled' => get_option('vortex_github_logging_enabled'),
                'github_repository' => get_option('vortex_github_repository'),
                'sync_interval' => get_option('vortex_github_sync_interval'),
                'encrypt_sensitive' => get_option('vortex_github_encrypt_sensitive'),
                'retention_days' => get_option('vortex_log_retention_days')
            )
        );
        
        return $report;
    }
}

// Initialize the log integration
new VORTEX_Log_Integration();

// Hook for GitHub sync
add_action('vortex_github_sync', function() {
    if (get_option('vortex_github_logging_enabled', false)) {
        $logger = VORTEX_Realtime_Logger::get_instance();
        $logger->sync_logs_to_github();
    }
});

// Hook for log cleanup
add_action('vortex_log_cleanup', function() {
    $retention_days = get_option('vortex_log_retention_days', 30);
    VORTEX_Log_Database::clean_old_data($retention_days);
});

// Hook for statistics update
add_action('vortex_log_statistics', function() {
    VORTEX_Log_Database::update_statistics();
}); 