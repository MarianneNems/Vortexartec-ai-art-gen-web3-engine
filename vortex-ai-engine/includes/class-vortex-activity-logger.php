<?php
/**
 * VORTEX AI Engine - Real-Time Activity Logger
 * 
 * Tracks all plugin activities, AI agent interactions, server connections,
 * and algorithm executions in real-time for monitoring and debugging.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Activity_Logger {
    
    /**
     * Single instance of the logger
     */
    private static $instance = null;
    
    /**
     * Log file path
     */
    private $log_file;
    
    /**
     * Activity buffer for real-time display
     */
    private $activity_buffer = array();
    
    /**
     * Maximum buffer size
     */
    private $max_buffer_size = 1000;
    
    /**
     * Log levels
     */
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_SUCCESS = 'SUCCESS';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_DEBUG = 'DEBUG';
    
    /**
     * Activity types
     */
    const ACTIVITY_AI_AGENT = 'AI_AGENT';
    const ACTIVITY_SERVER = 'SERVER';
    const ACTIVITY_ALGORITHM = 'ALGORITHM';
    const ACTIVITY_DATABASE = 'DATABASE';
    const ACTIVITY_BLOCKCHAIN = 'BLOCKCHAIN';
    const ACTIVITY_CLOUD = 'CLOUD';
    const ACTIVITY_USER = 'USER';
    const ACTIVITY_SYSTEM = 'SYSTEM';
    
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
        $this->log_file = WP_CONTENT_DIR . '/vortex-activity.log';
        $this->init_logger();
    }
    
    /**
     * Initialize logger
     */
    private function init_logger() {
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Create initial log entry
        $this->log_activity(
            self::ACTIVITY_SYSTEM,
            self::LOG_LEVEL_INFO,
            'VORTEX Activity Logger initialized',
            array(
                'timestamp' => current_time('mysql'),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            )
        );
    }
    
    /**
     * Log AI Agent Activity
     */
    public function log_ai_agent_activity($agent_name, $action, $data = array(), $level = self::LOG_LEVEL_INFO) {
        $activity_data = array_merge($data, array(
            'agent' => $agent_name,
            'action' => $action,
            'memory_usage' => memory_get_usage(true),
            'execution_time' => microtime(true)
        ));
        
        $this->log_activity(
            self::ACTIVITY_AI_AGENT,
            $level,
            "AI Agent {$agent_name}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Log Server Connection Activity
     */
    public function log_server_activity($server_name, $action, $url = '', $response_code = null, $data = array()) {
        $activity_data = array_merge($data, array(
            'server' => $server_name,
            'action' => $action,
            'url' => $url,
            'response_code' => $response_code,
            'timestamp' => current_time('mysql')
        ));
        
        $level = ($response_code >= 200 && $response_code < 300) ? self::LOG_LEVEL_SUCCESS : self::LOG_LEVEL_ERROR;
        
        $this->log_activity(
            self::ACTIVITY_SERVER,
            $level,
            "Server {$server_name}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Log Algorithm Execution
     */
    public function log_algorithm_activity($algorithm_name, $action, $parameters = array(), $result = null, $execution_time = null) {
        $activity_data = array(
            'algorithm' => $algorithm_name,
            'action' => $action,
            'parameters' => $parameters,
            'result' => $result,
            'execution_time' => $execution_time,
            'memory_usage' => memory_get_usage(true)
        );
        
        $level = ($result !== null) ? self::LOG_LEVEL_SUCCESS : self::LOG_LEVEL_WARNING;
        
        $this->log_activity(
            self::ACTIVITY_ALGORITHM,
            $level,
            "Algorithm {$algorithm_name}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Log Database Activity
     */
    public function log_database_activity($table, $action, $query = '', $affected_rows = null, $data = array()) {
        $activity_data = array_merge($data, array(
            'table' => $table,
            'action' => $action,
            'query' => $query,
            'affected_rows' => $affected_rows,
            'timestamp' => current_time('mysql')
        ));
        
        $level = ($affected_rows !== null) ? self::LOG_LEVEL_SUCCESS : self::LOG_LEVEL_INFO;
        
        $this->log_activity(
            self::ACTIVITY_DATABASE,
            $level,
            "Database {$table}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Log Blockchain Activity
     */
    public function log_blockchain_activity($network, $action, $transaction_hash = null, $data = array()) {
        $activity_data = array_merge($data, array(
            'network' => $network,
            'action' => $action,
            'transaction_hash' => $transaction_hash,
            'timestamp' => current_time('mysql')
        ));
        
        $level = ($transaction_hash) ? self::LOG_LEVEL_SUCCESS : self::LOG_LEVEL_INFO;
        
        $this->log_activity(
            self::ACTIVITY_BLOCKCHAIN,
            $level,
            "Blockchain {$network}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Log Cloud Service Activity
     */
    public function log_cloud_activity($service, $action, $endpoint = '', $data = array()) {
        $activity_data = array_merge($data, array(
            'service' => $service,
            'action' => $action,
            'endpoint' => $endpoint,
            'timestamp' => current_time('mysql')
        ));
        
        $this->log_activity(
            self::ACTIVITY_CLOUD,
            self::LOG_LEVEL_INFO,
            "Cloud {$service}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Log User Activity
     */
    public function log_user_activity($user_id, $action, $data = array()) {
        $user = get_userdata($user_id);
        $username = $user ? $user->user_login : 'Unknown';
        
        $activity_data = array_merge($data, array(
            'user_id' => $user_id,
            'username' => $username,
            'action' => $action,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ));
        
        $this->log_activity(
            self::ACTIVITY_USER,
            self::LOG_LEVEL_INFO,
            "User {$username}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Log System Activity
     */
    public function log_system_activity($component, $action, $data = array(), $level = self::LOG_LEVEL_INFO) {
        $activity_data = array_merge($data, array(
            'component' => $component,
            'action' => $action,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'load_average' => sys_getloadavg()
        ));
        
        $this->log_activity(
            self::ACTIVITY_SYSTEM,
            $level,
            "System {$component}: {$action}",
            $activity_data
        );
    }
    
    /**
     * Main logging function
     */
    private function log_activity($type, $level, $message, $data = array()) {
        $timestamp = current_time('mysql');
        $log_entry = array(
            'timestamp' => $timestamp,
            'type' => $type,
            'level' => $level,
            'message' => $message,
            'data' => $data,
            'request_id' => $this->get_request_id()
        );
        
        // Add to buffer for real-time display
        $this->add_to_buffer($log_entry);
        
        // Write to file
        $this->write_to_file($log_entry);
        
        // Trigger action for real-time updates
        do_action('vortex_activity_logged', $log_entry);
    }
    
    /**
     * Add entry to activity buffer
     */
    private function add_to_buffer($entry) {
        $this->activity_buffer[] = $entry;
        
        // Maintain buffer size
        if (count($this->activity_buffer) > $this->max_buffer_size) {
            array_shift($this->activity_buffer);
        }
    }
    
    /**
     * Write log entry to file
     */
    private function write_to_file($entry) {
        $log_line = json_encode($entry) . "\n";
        
        // Use file_put_contents with LOCK_EX for thread safety
        file_put_contents($this->log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get current request ID
     */
    private function get_request_id() {
        if (!isset($_SESSION['vortex_request_id'])) {
            $_SESSION['vortex_request_id'] = uniqid('vortex_', true);
        }
        return $_SESSION['vortex_request_id'];
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
     * Get recent activity buffer
     */
    public function get_recent_activity($limit = 100) {
        return array_slice($this->activity_buffer, -$limit);
    }
    
    /**
     * Get activity by type
     */
    public function get_activity_by_type($type, $limit = 100) {
        $filtered = array_filter($this->activity_buffer, function($entry) use ($type) {
            return $entry['type'] === $type;
        });
        return array_slice($filtered, -$limit);
    }
    
    /**
     * Get activity by level
     */
    public function get_activity_by_level($level, $limit = 100) {
        $filtered = array_filter($this->activity_buffer, function($entry) use ($level) {
            return $entry['level'] === $level;
        });
        return array_slice($filtered, -$limit);
    }
    
    /**
     * Clear activity buffer
     */
    public function clear_buffer() {
        $this->activity_buffer = array();
    }
    
    /**
     * Get log file path
     */
    public function get_log_file_path() {
        return $this->log_file;
    }
    
    /**
     * Get log file size
     */
    public function get_log_file_size() {
        return file_exists($this->log_file) ? filesize($this->log_file) : 0;
    }
    
    /**
     * Rotate log file if it's too large
     */
    public function rotate_log_if_needed($max_size = 10485760) { // 10MB
        if ($this->get_log_file_size() > $max_size) {
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s');
            rename($this->log_file, $backup_file);
            
            $this->log_system_activity(
                'Logger',
                'Log file rotated',
                array('backup_file' => $backup_file)
            );
        }
    }
    
    /**
     * Get activity statistics
     */
    public function get_activity_stats() {
        $stats = array(
            'total_entries' => count($this->activity_buffer),
            'by_type' => array(),
            'by_level' => array(),
            'recent_errors' => 0,
            'recent_warnings' => 0
        );
        
        foreach ($this->activity_buffer as $entry) {
            // Count by type
            if (!isset($stats['by_type'][$entry['type']])) {
                $stats['by_type'][$entry['type']] = 0;
            }
            $stats['by_type'][$entry['type']]++;
            
            // Count by level
            if (!isset($stats['by_level'][$entry['level']])) {
                $stats['by_level'][$entry['level']] = 0;
            }
            $stats['by_level'][$entry['level']]++;
            
            // Count recent errors/warnings
            if ($entry['level'] === self::LOG_LEVEL_ERROR) {
                $stats['recent_errors']++;
            } elseif ($entry['level'] === self::LOG_LEVEL_WARNING) {
                $stats['recent_warnings']++;
            }
        }
        
        return $stats;
    }
}

// Initialize the logger
Vortex_Activity_Logger::get_instance(); 