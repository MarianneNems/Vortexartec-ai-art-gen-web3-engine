<?php
/**
 * VORTEX AI Engine - Log Database Setup
 * 
 * Database table creation and management for real-time logging
 * Ensures secure storage and efficient querying of log data
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
 * Log Database Class
 * 
 * Handles database table creation and management for logging system
 */
class VORTEX_Log_Database {
    
    /**
     * Create log tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main logs table
        $table_name = $wpdb->prefix . 'vortex_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp varchar(20) NOT NULL,
            level varchar(20) NOT NULL,
            message longtext NOT NULL,
            context longtext,
            user_id bigint(20) DEFAULT 0,
            ip_address varchar(45) DEFAULT '',
            request_uri varchar(255) DEFAULT '',
            session_id varchar(255) DEFAULT '',
            encrypted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY encrypted (encrypted)
        ) $charset_collate;";
        
        // Log statistics table
        $stats_table = $wpdb->prefix . 'vortex_log_stats';
        
        $stats_sql = "CREATE TABLE $stats_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            level varchar(20) NOT NULL,
            count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_level (date, level),
            KEY date (date),
            KEY level (level)
        ) $charset_collate;";
        
        // Log alerts table
        $alerts_table = $wpdb->prefix . 'vortex_log_alerts';
        
        $alerts_sql = "CREATE TABLE $alerts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            alert_type varchar(50) NOT NULL,
            alert_message text NOT NULL,
            alert_data longtext,
            triggered_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY alert_type (alert_type),
            KEY status (status),
            KEY triggered_at (triggered_at)
        ) $charset_collate;";
        
        // GitHub sync history table
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        
        $sync_sql = "CREATE TABLE $sync_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sync_date datetime DEFAULT CURRENT_TIMESTAMP,
            logs_count int(11) DEFAULT 0,
            success tinyint(1) DEFAULT 0,
            error_message text,
            repository varchar(255) DEFAULT '',
            branch varchar(100) DEFAULT 'main',
            PRIMARY KEY (id),
            KEY sync_date (sync_date),
            KEY success (success)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql);
        dbDelta($stats_sql);
        dbDelta($alerts_sql);
        dbDelta($sync_sql);
        
        // Add version option
        update_option('vortex_log_db_version', '2.2.0');
    }
    
    /**
     * Update log statistics
     */
    public static function update_statistics($date = null) {
        global $wpdb;
        
        if (!$date) {
            $date = current_time('Y-m-d');
        }
        
        $table_name = $wpdb->prefix . 'vortex_logs';
        $stats_table = $wpdb->prefix . 'vortex_log_stats';
        
        // Get counts by level for the date
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT level, COUNT(*) as count 
             FROM $table_name 
             WHERE DATE(created_at) = %s 
             GROUP BY level",
            $date
        ));
        
        foreach ($results as $result) {
            $wpdb->replace(
                $stats_table,
                array(
                    'date' => $date,
                    'level' => $result->level,
                    'count' => $result->count
                ),
                array('%s', '%s', '%d')
            );
        }
    }
    
    /**
     * Get log statistics
     */
    public static function get_statistics($days = 7) {
        global $wpdb;
        
        $stats_table = $wpdb->prefix . 'vortex_log_stats';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT date, level, count 
             FROM $stats_table 
             WHERE date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             ORDER BY date DESC, level",
            $days
        ));
    }
    
    /**
     * Create alert
     */
    public static function create_alert($type, $message, $data = null) {
        global $wpdb;
        
        $alerts_table = $wpdb->prefix . 'vortex_log_alerts';
        
        return $wpdb->insert(
            $alerts_table,
            array(
                'alert_type' => $type,
                'alert_message' => $message,
                'alert_data' => $data ? json_encode($data) : null
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Get active alerts
     */
    public static function get_active_alerts() {
        global $wpdb;
        
        $alerts_table = $wpdb->prefix . 'vortex_log_alerts';
        
        return $wpdb->get_results(
            "SELECT * FROM $alerts_table WHERE status = 'active' ORDER BY triggered_at DESC"
        );
    }
    
    /**
     * Resolve alert
     */
    public static function resolve_alert($alert_id) {
        global $wpdb;
        
        $alerts_table = $wpdb->prefix . 'vortex_log_alerts';
        
        return $wpdb->update(
            $alerts_table,
            array(
                'status' => 'resolved',
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $alert_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Record GitHub sync
     */
    public static function record_github_sync($logs_count, $success, $error_message = null) {
        global $wpdb;
        
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        $repository = get_option('vortex_github_repository', '');
        $branch = get_option('vortex_github_branch', 'main');
        
        return $wpdb->insert(
            $sync_table,
            array(
                'logs_count' => $logs_count,
                'success' => $success ? 1 : 0,
                'error_message' => $error_message,
                'repository' => $repository,
                'branch' => $branch
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get GitHub sync history
     */
    public static function get_github_sync_history($limit = 50) {
        global $wpdb;
        
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $sync_table ORDER BY sync_date DESC LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Clean old data
     */
    public static function clean_old_data($days = 90) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Clean old logs
        $logs_table = $wpdb->prefix . 'vortex_logs';
        $deleted_logs = $wpdb->query($wpdb->prepare(
            "DELETE FROM $logs_table WHERE created_at < %s",
            $cutoff_date
        ));
        
        // Clean old sync history
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        $deleted_sync = $wpdb->query($wpdb->prepare(
            "DELETE FROM $sync_table WHERE sync_date < %s",
            $cutoff_date
        ));
        
        // Clean old alerts
        $alerts_table = $wpdb->prefix . 'vortex_log_alerts';
        $deleted_alerts = $wpdb->query($wpdb->prepare(
            "DELETE FROM $alerts_table WHERE triggered_at < %s AND status = 'resolved'",
            $cutoff_date
        ));
        
        return array(
            'logs' => $deleted_logs,
            'sync' => $deleted_sync,
            'alerts' => $deleted_alerts
        );
    }
    
    /**
     * Get database size
     */
    public static function get_database_size() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'vortex_logs';
        $stats_table = $wpdb->prefix . 'vortex_log_stats';
        $alerts_table = $wpdb->prefix . 'vortex_log_alerts';
        $sync_table = $wpdb->prefix . 'vortex_github_sync';
        
        $result = $wpdb->get_results("
            SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb',
                table_rows
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            AND table_name IN ('$logs_table', '$stats_table', '$alerts_table', '$sync_table')
        ");
        
        return $result;
    }
    
    /**
     * Optimize tables
     */
    public static function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'vortex_logs',
            $wpdb->prefix . 'vortex_log_stats',
            $wpdb->prefix . 'vortex_log_alerts',
            $wpdb->prefix . 'vortex_github_sync'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
    }
} 