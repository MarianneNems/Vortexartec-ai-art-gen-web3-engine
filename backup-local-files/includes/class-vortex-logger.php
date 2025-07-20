<?php
/**
 * VORTEX AI Engine - Comprehensive Logging System
 * 
 * This class provides enterprise-grade logging for all VORTEX AI Engine operations.
 * It logs every action, error, and interaction between all components.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VortexAIEngine_Logger')) {
class VortexAIEngine_Logger {
    
    private static $instance = null;
    private $log_file;
    private $log_level;
    private $max_file_size = 10485760; // 10MB
    private $max_files = 10;
    private $session_id;
    private $request_id;
    private $user_id;
    private $start_time;
    
    // Log levels
    const LOG_LEVEL_DEBUG = 0;
    const LOG_LEVEL_INFO = 1;
    const LOG_LEVEL_WARNING = 2;
    const LOG_LEVEL_ERROR = 3;
    const LOG_LEVEL_CRITICAL = 4;
    
    // Log categories
    const CATEGORY_SYSTEM = 'SYSTEM';
    const CATEGORY_ACTIVATION = 'ACTIVATION';
    const CATEGORY_DATABASE = 'DATABASE';
    const CATEGORY_AI_ORCHESTRATION = 'AI_ORCHESTRATION';
    const CATEGORY_AGENT_INTERACTION = 'AGENT_INTERACTION';
    const CATEGORY_VAULT_OPERATIONS = 'VAULT_OPERATIONS';
    const CATEGORY_SECURITY = 'SECURITY';
    const CATEGORY_TIER_MANAGEMENT = 'TIER_MANAGEMENT';
    const CATEGORY_SHORTCODES = 'SHORTCODES';
    const CATEGORY_REST_API = 'REST_API';
    const CATEGORY_AJAX = 'AJAX';
    const CATEGORY_ASSETS = 'ASSETS';
    const CATEGORY_PERFORMANCE = 'PERFORMANCE';
    const CATEGORY_ERROR = 'ERROR';
    const CATEGORY_USER_ACTION = 'USER_ACTION';
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initialize_logger();
        $this->log_system_start();
    }
    
    /**
     * Initialize the logging system
     */
    private function initialize_logger() {
        // Create logs directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/vortex-ai-logs';
        
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
            
            // Create .htaccess to protect logs
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($logs_dir . '/.htaccess', $htaccess_content);
            
            // Create index.php to prevent directory listing
            file_put_contents($logs_dir . '/index.php', '<?php // Silence is golden');
        }
        
        $this->log_file = $logs_dir . '/vortex-ai-engine.log';
        $this->log_level = get_option('vortex_log_level', self::LOG_LEVEL_INFO);
        $this->session_id = $this->generate_session_id();
        $this->request_id = $this->generate_request_id();
        $this->user_id = get_current_user_id();
        $this->start_time = microtime(true);
        
        // Rotate logs if needed
        $this->rotate_logs_if_needed();
    }
    
    /**
     * Log system startup
     */
    private function log_system_start() {
        $this->log(
            'VORTEX AI Engine Logger initialized',
            self::LOG_LEVEL_INFO,
            self::CATEGORY_SYSTEM,
            [
                'session_id' => $this->session_id,
                'request_id' => $this->request_id,
                'user_id' => $this->user_id,
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'plugin_version' => VORTEX_AI_ENGINE_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ]
        );
    }
    
    /**
     * Main logging method
     */
    public function log($message, $level = self::LOG_LEVEL_INFO, $category = self::CATEGORY_SYSTEM, $context = []) {
        // Check if we should log this level
        if ($level < $this->log_level) {
            return;
        }
        
        $log_entry = $this->format_log_entry($message, $level, $category, $context);
        
        // Write to file
        $this->write_to_file($log_entry);
        
        // Also log to WordPress error log for critical errors
        if ($level >= self::LOG_LEVEL_ERROR) {
            error_log("[VORTEX AI] {$category}: {$message}");
        }
        
        // Store in database for web interface
        $this->store_in_database($message, $level, $category, $context);
    }
    
    /**
     * Format log entry
     */
    private function format_log_entry($message, $level, $category, $context) {
        $timestamp = current_time('Y-m-d H:i:s');
        $level_name = $this->get_level_name($level);
        $memory_usage = memory_get_usage(true);
        $peak_memory = memory_get_peak_usage(true);
        $execution_time = microtime(true) - $this->start_time;
        
        $log_entry = [
            'timestamp' => $timestamp,
            'level' => $level_name,
            'category' => $category,
            'message' => $message,
            'session_id' => $this->session_id,
            'request_id' => $this->request_id,
            'user_id' => $this->user_id,
            'memory_usage' => $this->format_bytes($memory_usage),
            'peak_memory' => $this->format_bytes($peak_memory),
            'execution_time' => round($execution_time, 4),
            'context' => $context,
            'backtrace' => $this->get_backtrace(),
            'request_data' => $this->get_request_data()
        ];
        
        return json_encode($log_entry) . "\n";
    }
    
    /**
     * Write log entry to file
     */
    private function write_to_file($log_entry) {
        try {
            file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("[VORTEX AI Logger] Failed to write to log file: " . $e->getMessage());
        }
    }
    
    /**
     * Store log entry in database
     */
    private function store_in_database($message, $level, $category, $context) {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'vortex_ai_logs';
            
            $wpdb->insert(
                $table_name,
                [
                    'timestamp' => current_time('mysql'),
                    'level' => $this->get_level_name($level),
                    'category' => $category,
                    'message' => $message,
                    'session_id' => $this->session_id,
                    'request_id' => $this->request_id,
                    'user_id' => $this->user_id,
                    'context' => json_encode($context),
                    'backtrace' => json_encode($this->get_backtrace()),
                    'request_data' => json_encode($this->get_request_data())
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
            );
            
            // Clean old logs (keep last 1000 entries)
            $this->clean_old_database_logs();
            
        } catch (Exception $e) {
            error_log("[VORTEX AI Logger] Failed to store log in database: " . $e->getMessage());
        }
    }
    
    /**
     * Get level name
     */
    private function get_level_name($level) {
        $levels = [
            self::LOG_LEVEL_DEBUG => 'DEBUG',
            self::LOG_LEVEL_INFO => 'INFO',
            self::LOG_LEVEL_WARNING => 'WARNING',
            self::LOG_LEVEL_ERROR => 'ERROR',
            self::LOG_LEVEL_CRITICAL => 'CRITICAL'
        ];
        
        return $levels[$level] ?? 'UNKNOWN';
    }
    
    /**
     * Get backtrace for debugging
     */
    private function get_backtrace() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $formatted = [];
        
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'vortex-ai-engine') !== false) {
                $formatted[] = [
                    'file' => basename($trace['file']),
                    'line' => $trace['line'],
                    'function' => $trace['function'] ?? '',
                    'class' => $trace['class'] ?? ''
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Get request data
     */
    private function get_request_data() {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'ip' => $this->get_client_ip(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'post_data' => $this->sanitize_post_data($_POST),
            'get_data' => $this->sanitize_get_data($_GET)
        ];
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
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
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Sanitize POST data
     */
    private function sanitize_post_data($post_data) {
        $sanitized = [];
        $sensitive_keys = ['password', 'api_key', 'token', 'secret', 'key'];
        
        foreach ($post_data as $key => $value) {
            if (in_array(strtolower($key), $sensitive_keys)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = is_string($value) ? substr($value, 0, 100) : $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize GET data
     */
    private function sanitize_get_data($get_data) {
        $sanitized = [];
        $sensitive_keys = ['password', 'api_key', 'token', 'secret', 'key'];
        
        foreach ($get_data as $key => $value) {
            if (in_array(strtolower($key), $sensitive_keys)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = is_string($value) ? substr($value, 0, 100) : $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Generate session ID
     */
    private function generate_session_id() {
        return uniqid('vortex_', true);
    }
    
    /**
     * Generate request ID
     */
    private function generate_request_id() {
        return uniqid('req_', true);
    }
    
    /**
     * Format bytes to human readable
     */
    private function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
    
    /**
     * Rotate logs if file is too large
     */
    private function rotate_logs_if_needed() {
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_file_size) {
            $this->rotate_log_file();
        }
    }
    
    /**
     * Rotate log file
     */
    private function rotate_log_file() {
        $log_dir = dirname($this->log_file);
        $base_name = basename($this->log_file, '.log');
        
        // Remove oldest log file if we have too many
        $oldest_log = $log_dir . '/' . $base_name . '.' . $this->max_files . '.log';
        if (file_exists($oldest_log)) {
            unlink($oldest_log);
        }
        
        // Shift existing log files
        for ($i = $this->max_files - 1; $i >= 1; $i--) {
            $old_file = $log_dir . '/' . $base_name . '.' . $i . '.log';
            $new_file = $log_dir . '/' . $base_name . '.' . ($i + 1) . '.log';
            
            if (file_exists($old_file)) {
                rename($old_file, $new_file);
            }
        }
        
        // Rename current log file
        $new_current = $log_dir . '/' . $base_name . '.1.log';
        rename($this->log_file, $new_current);
        
        // Create new log file
        touch($this->log_file);
    }
    
    /**
     * Clean old database logs
     */
    private function clean_old_database_logs() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'vortex_ai_logs';
            
            // Keep only the last 1000 entries
            $wpdb->query("
                DELETE FROM {$table_name} 
                WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM {$table_name} 
                        ORDER BY timestamp DESC 
                        LIMIT 1000
                    ) as temp
                )
            ");
            
        } catch (Exception $e) {
            error_log("[VORTEX AI Logger] Failed to clean old database logs: " . $e->getMessage());
        }
    }
    
    /**
     * Convenience methods for different log levels
     */
    public function debug($message, $category = self::CATEGORY_SYSTEM, $context = []) {
        $this->log($message, self::LOG_LEVEL_DEBUG, $category, $context);
    }
    
    public function info($message, $category = self::CATEGORY_SYSTEM, $context = []) {
        $this->log($message, self::LOG_LEVEL_INFO, $category, $context);
    }
    
    public function warning($message, $category = self::CATEGORY_SYSTEM, $context = []) {
        $this->log($message, self::LOG_LEVEL_WARNING, $category, $context);
    }
    
    public function error($message, $category = self::CATEGORY_ERROR, $context = []) {
        $this->log($message, self::LOG_LEVEL_ERROR, $category, $context);
    }
    
    public function critical($message, $category = self::CATEGORY_ERROR, $context = []) {
        $this->log($message, self::LOG_LEVEL_CRITICAL, $category, $context);
    }
    
    /**
     * Log AI orchestration events
     */
    public function log_ai_orchestration($action, $query, $agents, $result, $duration, $context = []) {
        $this->info(
            "AI Orchestration: {$action}",
            self::CATEGORY_AI_ORCHESTRATION,
            array_merge($context, [
                'query' => substr($query, 0, 200),
                'agents' => $agents,
                'result_status' => $result['status'] ?? 'unknown',
                'duration' => $duration,
                'memory_usage' => memory_get_usage(true)
            ])
        );
    }
    
    /**
     * Log agent interactions
     */
    public function log_agent_interaction($agent_id, $action, $input, $output, $duration, $context = []) {
        $this->info(
            "Agent Interaction: {$agent_id} - {$action}",
            self::CATEGORY_AGENT_INTERACTION,
            array_merge($context, [
                'agent_id' => $agent_id,
                'action' => $action,
                'input_length' => strlen($input),
                'output_length' => strlen($output),
                'duration' => $duration,
                'success' => !empty($output)
            ])
        );
    }
    
    /**
     * Log vault operations
     */
    public function log_vault_operation($operation, $key, $success, $context = []) {
        $this->info(
            "Vault Operation: {$operation}",
            self::CATEGORY_VAULT_OPERATIONS,
            array_merge($context, [
                'operation' => $operation,
                'key' => $key,
                'success' => $success
            ])
        );
    }
    
    /**
     * Log security events
     */
    public function log_security_event($event_type, $details, $severity = self::LOG_LEVEL_WARNING) {
        $this->log(
            "Security Event: {$event_type}",
            $severity,
            self::CATEGORY_SECURITY,
            $details
        );
    }
    
    /**
     * Log user actions
     */
    public function log_user_action($action, $user_id, $details = []) {
        $this->info(
            "User Action: {$action}",
            self::CATEGORY_USER_ACTION,
            array_merge($details, [
                'action' => $action,
                'user_id' => $user_id,
                'ip' => $this->get_client_ip()
            ])
        );
    }
    
    /**
     * Log performance metrics
     */
    public function log_performance($operation, $duration, $memory_usage, $context = []) {
        $this->info(
            "Performance: {$operation}",
            self::CATEGORY_PERFORMANCE,
            array_merge($context, [
                'operation' => $operation,
                'duration' => $duration,
                'memory_usage' => $memory_usage,
                'peak_memory' => memory_get_peak_usage(true)
            ])
        );
    }
    
    /**
     * Get logs for web interface
     */
    public function get_logs($limit = 100, $level = null, $category = null, $user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_logs';
        $where_conditions = [];
        $where_values = [];
        
        if ($level) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $level;
        }
        
        if ($category) {
            $where_conditions[] = 'category = %s';
            $where_values[] = $category;
        }
        
        if ($user_id) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $user_id;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT * FROM {$table_name} {$where_clause} ORDER BY timestamp DESC LIMIT %d";
        $where_values[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * Create database table for logs
     */
    public static function create_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            level varchar(20) NOT NULL,
            category varchar(50) NOT NULL,
            message text NOT NULL,
            session_id varchar(50) NOT NULL,
            request_id varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            context longtext,
            backtrace longtext,
            request_data longtext,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY level (level),
            KEY category (category),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
} 