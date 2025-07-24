<?php
/**
 * Vortex AI Engine - Real-Time Logging System
 * 
 * Comprehensive logging system with real-time monitoring,
 * debug tracking, and performance analytics.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Realtime_Logger {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Log file path
     */
    private $log_file;
    
    /**
     * Debug file path
     */
    private $debug_file;
    
    /**
     * Performance log file path
     */
    private $performance_file;
    
    /**
     * Error log file path
     */
    private $error_file;
    
    /**
     * Log levels
     */
    const LOG_LEVEL_DEBUG = 'DEBUG';
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_CRITICAL = 'CRITICAL';
    
    /**
     * Get single instance
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
        $this->init_logging_system();
    }
    
    /**
     * Initialize logging system
     */
    private function init_logging_system() {
        // Create logs directory if it doesn't exist
        $logs_dir = VORTEX_AI_ENGINE_PLUGIN_PATH . 'logs';
        if (!is_dir($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        // Set log file paths
        $this->log_file = $logs_dir . '/vortex-ai-engine.log';
        $this->debug_file = $logs_dir . '/vortex-debug.log';
        $this->performance_file = $logs_dir . '/vortex-performance.log';
        $this->error_file = $logs_dir . '/vortex-errors.log';
        
        // Hook into WordPress events for automatic logging
        add_action('wp_loaded', array($this, 'log_system_startup'));
        add_action('wp_footer', array($this, 'log_page_completion'));
        add_action('admin_footer', array($this, 'log_admin_completion'));
        add_action('wp_login', array($this, 'log_user_login'));
        add_action('wp_logout', array($this, 'log_user_logout'));
        
        // Error logging
        add_action('wp_loaded', array($this, 'log_php_errors'));
        
        // Performance logging
        add_action('wp_loaded', array($this, 'log_performance_metrics'));
        
        // Database query logging
        add_action('wp_loaded', array($this, 'log_database_queries'));
        
        // Memory usage logging
        add_action('wp_loaded', array($this, 'log_memory_usage'));
        
        // Plugin activity logging
        add_action('vortex_ai_activity', array($this, 'log_plugin_activity'));
        
        // Security event logging
        add_action('vortex_security_event', array($this, 'log_security_event'));
        
        // AI system logging
        add_action('vortex_ai_event', array($this, 'log_ai_event'));
        
        // Initialize log rotation
        $this->init_log_rotation();
    }
    
    /**
     * Log a message
     */
    public function log($message, $level = self::LOG_LEVEL_INFO, $context = array()) {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = array(
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'memory_usage' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => $this->get_client_ip(),
            'user_id' => get_current_user_id(),
            'session_id' => session_id() ?? 'Unknown'
        );
        
        $log_line = sprintf(
            "[%s] [%s] %s | Memory: %s | Peak: %s | URI: %s | User: %d\n",
            $timestamp,
            $level,
            $message,
            $this->format_bytes($log_entry['memory_usage']),
            $this->format_bytes($log_entry['peak_memory']),
            $log_entry['request_uri'],
            $log_entry['user_id']
        );
        
        // Write to main log file
        file_put_contents($this->log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Write to debug file if debug level
        if ($level === self::LOG_LEVEL_DEBUG) {
            file_put_contents($this->debug_file, $log_line, FILE_APPEND | LOCK_EX);
        }
        
        // Write to error file if error level
        if (in_array($level, array(self::LOG_LEVEL_ERROR, self::LOG_LEVEL_CRITICAL))) {
            file_put_contents($this->error_file, $log_line, FILE_APPEND | LOCK_EX);
        }
        
        // Store in WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Vortex AI Engine [' . $level . ']: ' . $message);
        }
        
        return $log_entry;
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_DEBUG, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_INFO, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_WARNING, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_ERROR, $context);
    }
    
    /**
     * Log critical message
     */
    public function critical($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_CRITICAL, $context);
    }
    
    /**
     * Log system startup
     */
    public function log_system_startup() {
        $this->info('Vortex AI Engine system startup', array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => VORTEX_AI_ENGINE_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ));
    }
    
    /**
     * Log page completion
     */
    public function log_page_completion() {
        if (!is_admin()) {
            $this->info('Frontend page completed', array(
                'page_title' => wp_get_document_title(),
                'template' => get_page_template_slug(),
                'is_home' => is_home(),
                'is_front_page' => is_front_page(),
                'is_single' => is_single(),
                'is_page' => is_page(),
                'is_archive' => is_archive()
            ));
        }
    }
    
    /**
     * Log admin completion
     */
    public function log_admin_completion() {
        if (is_admin()) {
            $this->info('Admin page completed', array(
                'admin_page' => $_GET['page'] ?? 'Unknown',
                'action' => $_GET['action'] ?? 'Unknown',
                'screen_id' => get_current_screen()->id ?? 'Unknown'
            ));
        }
    }
    
    /**
     * Log user login
     */
    public function log_user_login($user_login) {
        $user = get_user_by('login', $user_login);
        $this->info('User login successful', array(
            'user_id' => $user->ID ?? 0,
            'user_login' => $user_login,
            'user_email' => $user->user_email ?? 'Unknown',
            'user_roles' => $user->roles ?? array()
        ));
    }
    
    /**
     * Log user logout
     */
    public function log_user_logout() {
        $this->info('User logout', array(
            'user_id' => get_current_user_id()
        ));
    }
    
    /**
     * Log PHP errors
     */
    public function log_php_errors() {
        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            $this->critical('PHP Error detected', array(
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ));
        }
    }
    
    /**
     * Log performance metrics
     */
    public function log_performance_metrics() {
        $metrics = array(
            'memory_usage' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
            'load_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'database_queries' => get_num_queries(),
            'database_time' => $this->get_database_time()
        );
        
        $log_line = sprintf(
            "[%s] Performance | Memory: %s | Peak: %s | Load: %.4fs | Queries: %d | DB Time: %.4fs\n",
            current_time('Y-m-d H:i:s'),
            $this->format_bytes($metrics['memory_usage']),
            $this->format_bytes($metrics['peak_memory']),
            $metrics['load_time'],
            $metrics['database_queries'],
            $metrics['database_time']
        );
        
        file_put_contents($this->performance_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log database queries
     */
    public function log_database_queries() {
        global $wpdb;
        
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            $queries = $wpdb->queries;
            if (!empty($queries)) {
                foreach ($queries as $query) {
                    $this->debug('Database query executed', array(
                        'query' => $query[0],
                        'time' => $query[1],
                        'backtrace' => $query[2]
                    ));
                }
            }
        }
    }
    
    /**
     * Log memory usage
     */
    public function log_memory_usage() {
        $memory_usage = memory_get_usage();
        $peak_memory = memory_get_peak_usage();
        $memory_limit = ini_get('memory_limit');
        
        $usage_percentage = ($memory_usage / $this->convert_memory_limit($memory_limit)) * 100;
        
        if ($usage_percentage > 80) {
            $this->warning('High memory usage detected', array(
                'usage' => $this->format_bytes($memory_usage),
                'peak' => $this->format_bytes($peak_memory),
                'limit' => $memory_limit,
                'percentage' => round($usage_percentage, 2)
            ));
        }
    }
    
    /**
     * Log plugin activity
     */
    public function log_plugin_activity($activity) {
        $this->info('Plugin activity', $activity);
    }
    
    /**
     * Log security event
     */
    public function log_security_event($event) {
        $this->warning('Security event detected', $event);
    }
    
    /**
     * Log AI event
     */
    public function log_ai_event($event) {
        $this->info('AI system event', $event);
    }
    
    /**
     * Initialize log rotation
     */
    private function init_log_rotation() {
        // Rotate logs daily
        add_action('wp_scheduled_delete', array($this, 'rotate_logs'));
    }
    
    /**
     * Rotate log files
     */
    public function rotate_logs() {
        $log_files = array($this->log_file, $this->debug_file, $this->performance_file, $this->error_file);
        
        foreach ($log_files as $log_file) {
            if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) { // 10MB
                $backup_file = $log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
                rename($log_file, $backup_file);
                
                // Keep only last 5 backup files
                $backup_files = glob($log_file . '.*.bak');
                if (count($backup_files) > 5) {
                    array_map('unlink', array_slice($backup_files, 0, count($backup_files) - 5));
                }
            }
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Format bytes to human readable format
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Convert memory limit to bytes
     */
    private function convert_memory_limit($memory_limit) {
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) $memory_limit;
        
        switch ($unit) {
            case 'k':
                return $value * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'g':
                return $value * 1024 * 1024 * 1024;
            default:
                return $value;
        }
    }
    
    /**
     * Get database time
     */
    private function get_database_time() {
        global $wpdb;
        return $wpdb->time_total ?? 0;
    }
    
    /**
     * Get log statistics
     */
    public function get_log_stats() {
        $stats = array();
        
        $log_files = array(
            'main' => $this->log_file,
            'debug' => $this->debug_file,
            'performance' => $this->performance_file,
            'errors' => $this->error_file
        );
        
        foreach ($log_files as $type => $file) {
            if (file_exists($file)) {
                $stats[$type] = array(
                    'size' => filesize($file),
                    'size_formatted' => $this->format_bytes(filesize($file)),
                    'lines' => count(file($file)),
                    'last_modified' => filemtime($file)
                );
            } else {
                $stats[$type] = array(
                    'size' => 0,
                    'size_formatted' => '0 B',
                    'lines' => 0,
                    'last_modified' => 0
                );
            }
        }
        
        return $stats;
    }
    
    /**
     * Clear log files
     */
    public function clear_logs() {
        $log_files = array($this->log_file, $this->debug_file, $this->performance_file, $this->error_file);
        
        foreach ($log_files as $log_file) {
            if (file_exists($log_file)) {
                file_put_contents($log_file, '');
            }
        }
        
        $this->info('Log files cleared');
    }
    
    /**
     * Get recent log entries
     */
    public function get_recent_logs($lines = 100) {
        if (file_exists($this->log_file)) {
            $log_content = file($this->log_file);
            return array_slice($log_content, -$lines);
        }
        return array();
    }
} 