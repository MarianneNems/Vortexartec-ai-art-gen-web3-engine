<?php
/**
 * VORTEX AI Engine - Real-Time Logger
 * 
 * Secure real-time logging system for WordPress with GitHub integration
 * Maintains complete privacy and security for all communications
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
 * Real-Time Logger Class
 * 
 * Handles secure logging of all WordPress activities with GitHub integration
 * Ensures complete privacy and security of sensitive data
 */
class VORTEX_Realtime_Logger {
    
    /**
     * Logger instance
     */
    private static $instance = null;
    
    /**
     * Log file path
     */
    private $log_file;
    
    /**
     * GitHub integration settings
     */
    private $github_settings;
    
    /**
     * Encryption key for sensitive data
     */
    private $encryption_key;
    
    /**
     * Log levels
     */
    const LOG_LEVEL_DEBUG = 'DEBUG';
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_CRITICAL = 'CRITICAL';
    const LOG_LEVEL_SECURITY = 'SECURITY';
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_logger();
        $this->setup_github_integration();
        $this->setup_encryption();
        $this->setup_hooks();
    }
    
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
     * Initialize logger
     */
    private function init_logger() {
        // Create secure log directory
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/vortex-logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Create .htaccess to protect logs
            $htaccess_content = "Order Deny,Allow\nDeny from all\n";
            file_put_contents($log_dir . '/.htaccess', $htaccess_content);
            
            // Create index.php to prevent directory listing
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        }
        
        $this->log_file = $log_dir . '/vortex-realtime-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Setup GitHub integration
     */
    private function setup_github_integration() {
        $this->github_settings = array(
            'enabled' => get_option('vortex_github_logging_enabled', false),
            'repository' => get_option('vortex_github_repository', ''),
            'token' => get_option('vortex_github_token', ''),
            'branch' => get_option('vortex_github_branch', 'main'),
            'sync_interval' => get_option('vortex_github_sync_interval', 300), // 5 minutes
            'encrypt_sensitive' => get_option('vortex_github_encrypt_sensitive', true),
            'exclude_patterns' => array(
                'password',
                'token',
                'key',
                'secret',
                'auth',
                'credential',
                'private'
            )
        );
    }
    
    /**
     * Setup encryption
     */
    private function setup_encryption() {
        $this->encryption_key = get_option('vortex_log_encryption_key');
        
        if (!$this->encryption_key) {
            $this->encryption_key = wp_generate_password(32, true, true);
            update_option('vortex_log_encryption_key', $this->encryption_key);
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // WordPress core events
        add_action('init', array($this, 'log_wordpress_init'));
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        add_action('user_register', array($this, 'log_user_register'));
        add_action('profile_update', array($this, 'log_user_update'));
        add_action('delete_user', array($this, 'log_user_delete'));
        
        // Plugin events
        add_action('activated_plugin', array($this, 'log_plugin_activated'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivated'));
        add_action('upgrader_process_complete', array($this, 'log_plugin_update'));
        
        // Theme events
        add_action('switch_theme', array($this, 'log_theme_switch'));
        add_action('upgrader_process_complete', array($this, 'log_theme_update'));
        
        // Content events
        add_action('wp_insert_post', array($this, 'log_post_created'), 10, 3);
        add_action('post_updated', array($this, 'log_post_updated'), 10, 3);
        add_action('before_delete_post', array($this, 'log_post_deleted'));
        add_action('wp_insert_comment', array($this, 'log_comment_created'));
        add_action('comment_post', array($this, 'log_comment_posted'));
        
        // Media events
        add_action('add_attachment', array($this, 'log_media_uploaded'));
        add_action('delete_attachment', array($this, 'log_media_deleted'));
        
        // Settings events
        add_action('update_option', array($this, 'log_option_updated'), 10, 3);
        add_action('add_option', array($this, 'log_option_added'), 10, 2);
        add_action('delete_option', array($this, 'log_option_deleted'));
        
        // Security events
        add_action('wp_login_failed', array($this, 'log_login_failed'));
        add_action('wp_authenticate_user', array($this, 'log_authentication_attempt'));
        
        // VORTEX specific events
        add_action('vortex_ai_agent_activated', array($this, 'log_ai_agent_activated'));
        add_action('vortex_ai_agent_deactivated', array($this, 'log_ai_agent_deactivated'));
        add_action('vortex_blockchain_transaction', array($this, 'log_blockchain_transaction'));
        add_action('vortex_artwork_created', array($this, 'log_artwork_created'));
        add_action('vortex_nft_minted', array($this, 'log_nft_minted'));
        
        // GitHub sync
        if ($this->github_settings['enabled']) {
            add_action('vortex_github_sync', array($this, 'sync_logs_to_github'));
            if (!wp_next_scheduled('vortex_github_sync')) {
                wp_schedule_event(time(), 'vortex_custom_interval', 'vortex_github_sync');
            }
        }
        
        // Custom cron interval
        add_filter('cron_schedules', array($this, 'add_custom_cron_interval'));
    }
    
    /**
     * Add custom cron interval
     */
    public function add_custom_cron_interval($schedules) {
        $schedules['vortex_custom_interval'] = array(
            'interval' => $this->github_settings['sync_interval'],
            'display' => 'VORTEX Custom Interval'
        );
        return $schedules;
    }
    
    /**
     * Log entry with security and privacy protection
     */
    public function log($level, $message, $context = array(), $sensitive = false) {
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $user_info = $user_id ? get_userdata($user_id) : null;
        
        // Sanitize and encrypt sensitive data
        if ($sensitive || $this->contains_sensitive_data($message, $context)) {
            $message = $this->encrypt_sensitive_data($message);
            $context = $this->encrypt_sensitive_data($context);
        }
        
        $log_entry = array(
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $user_id,
            'user_agent' => $this->sanitize_user_agent(),
            'ip_address' => $this->get_client_ip(),
            'request_uri' => $this->sanitize_request_uri(),
            'session_id' => $this->get_session_id(),
            'encrypted' => $sensitive || $this->contains_sensitive_data($message, $context)
        );
        
        // Write to local log file
        $this->write_to_file($log_entry);
        
        // Store in database for real-time access
        $this->store_in_database($log_entry);
        
        // Trigger real-time notifications
        $this->trigger_realtime_notification($log_entry);
        
        return $log_entry;
    }
    
    /**
     * Check if data contains sensitive information
     */
    private function contains_sensitive_data($message, $context) {
        $sensitive_patterns = $this->github_settings['exclude_patterns'];
        $data_string = is_array($message) ? json_encode($message) : $message;
        $data_string .= is_array($context) ? json_encode($context) : json_encode($context);
        
        foreach ($sensitive_patterns as $pattern) {
            if (stripos($data_string, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Encrypt sensitive data
     */
    private function encrypt_sensitive_data($data) {
        if (empty($data)) {
            return $data;
        }
        
        $data_string = is_array($data) ? json_encode($data) : $data;
        $encrypted = openssl_encrypt(
            $data_string,
            'AES-256-CBC',
            $this->encryption_key,
            0,
            substr(hash('sha256', $this->encryption_key), 0, 16)
        );
        
        return '[ENCRYPTED:' . base64_encode($encrypted) . ']';
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt_sensitive_data($encrypted_data) {
        if (empty($encrypted_data) || !preg_match('/\[ENCRYPTED:(.+?)\]/', $encrypted_data, $matches)) {
            return $encrypted_data;
        }
        
        $encrypted = base64_decode($matches[1]);
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $this->encryption_key,
            0,
            substr(hash('sha256', $this->encryption_key), 0, 16)
        );
        
        return $decrypted;
    }
    
    /**
     * Write log entry to file
     */
    private function write_to_file($log_entry) {
        $log_line = json_encode($log_entry) . "\n";
        
        // Use file locking for thread safety
        $handle = fopen($this->log_file, 'a');
        if ($handle && flock($handle, LOCK_EX)) {
            fwrite($handle, $log_line);
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
    
    /**
     * Store log entry in database
     */
    private function store_in_database($log_entry) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $log_entry['timestamp'],
                'level' => $log_entry['level'],
                'message' => $log_entry['message'],
                'context' => json_encode($log_entry['context']),
                'user_id' => $log_entry['user_id'],
                'ip_address' => $log_entry['ip_address'],
                'request_uri' => $log_entry['request_uri'],
                'encrypted' => $log_entry['encrypted'] ? 1 : 0,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Trigger real-time notification
     */
    private function trigger_realtime_notification($log_entry) {
        // WebSocket notification for real-time dashboard
        do_action('vortex_log_entry_created', $log_entry);
        
        // Email notification for critical events
        if (in_array($log_entry['level'], array(self::LOG_LEVEL_CRITICAL, self::LOG_LEVEL_SECURITY))) {
            $this->send_critical_notification($log_entry);
        }
    }
    
    /**
     * Send critical notification
     */
    private function send_critical_notification($log_entry) {
        $admin_email = get_option('admin_email');
        $subject = 'VORTEX AI Engine - Critical Log Entry';
        
        $message = "Critical log entry detected:\n\n";
        $message .= "Level: " . $log_entry['level'] . "\n";
        $message .= "Timestamp: " . $log_entry['timestamp'] . "\n";
        $message .= "Message: " . $log_entry['message'] . "\n";
        $message .= "User ID: " . $log_entry['user_id'] . "\n";
        $message .= "IP Address: " . $log_entry['ip_address'] . "\n";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * WordPress specific logging methods
     */
    public function log_wordpress_init() {
        $this->log(self::LOG_LEVEL_INFO, 'WordPress initialized', array(
            'version' => get_bloginfo('version'),
            'theme' => get_template(),
            'plugins_count' => count(get_option('active_plugins'))
        ));
    }
    
    public function log_user_login($user_login, $user) {
        $this->log(self::LOG_LEVEL_INFO, 'User login successful', array(
            'user_login' => $user_login,
            'user_id' => $user->ID,
            'user_email' => $user->user_email,
            'user_roles' => $user->roles
        ), true);
    }
    
    public function log_user_logout() {
        $user = wp_get_current_user();
        $this->log(self::LOG_LEVEL_INFO, 'User logout', array(
            'user_id' => $user->ID,
            'user_login' => $user->user_login
        ));
    }
    
    public function log_login_failed($username) {
        $this->log(self::LOG_LEVEL_SECURITY, 'Login failed', array(
            'username' => $username,
            'ip_address' => $this->get_client_ip()
        ), true);
    }
    
    public function log_plugin_activated($plugin) {
        $this->log(self::LOG_LEVEL_INFO, 'Plugin activated', array(
            'plugin' => $plugin,
            'plugin_data' => get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin)
        ));
    }
    
    public function log_plugin_deactivated($plugin) {
        $this->log(self::LOG_LEVEL_INFO, 'Plugin deactivated', array(
            'plugin' => $plugin
        ));
    }
    
    public function log_post_created($post_id, $post, $update) {
        if (!$update) {
            $this->log(self::LOG_LEVEL_INFO, 'Post created', array(
                'post_id' => $post_id,
                'post_type' => $post->post_type,
                'post_title' => $post->post_title,
                'post_status' => $post->post_status
            ));
        }
    }
    
    public function log_post_updated($post_id, $post_after, $post_before) {
        $this->log(self::LOG_LEVEL_INFO, 'Post updated', array(
            'post_id' => $post_id,
            'post_type' => $post_after->post_type,
            'post_title' => $post_after->post_title,
            'changes' => array(
                'status' => $post_before->post_status !== $post_after->post_status ? 
                    $post_before->post_status . ' -> ' . $post_after->post_status : null,
                'title' => $post_before->post_title !== $post_after->post_title ? 
                    $post_before->post_title . ' -> ' . $post_after->post_title : null
            )
        ));
    }
    
    public function log_option_updated($option, $old_value, $value) {
        // Skip sensitive options
        $sensitive_options = array('vortex_github_token', 'vortex_log_encryption_key');
        if (in_array($option, $sensitive_options)) {
            $this->log(self::LOG_LEVEL_SECURITY, 'Sensitive option updated', array(
                'option' => $option,
                'encrypted' => true
            ), true);
        } else {
            $this->log(self::LOG_LEVEL_INFO, 'Option updated', array(
                'option' => $option,
                'old_value' => $old_value,
                'new_value' => $value
            ));
        }
    }
    
    /**
     * VORTEX specific logging methods
     */
    public function log_ai_agent_activated($agent_name) {
        $this->log(self::LOG_LEVEL_INFO, 'AI Agent activated', array(
            'agent' => $agent_name,
            'timestamp' => current_time('mysql')
        ));
    }
    
    public function log_ai_agent_deactivated($agent_name) {
        $this->log(self::LOG_LEVEL_INFO, 'AI Agent deactivated', array(
            'agent' => $agent_name,
            'timestamp' => current_time('mysql')
        ));
    }
    
    public function log_blockchain_transaction($transaction_data) {
        $this->log(self::LOG_LEVEL_INFO, 'Blockchain transaction', array(
            'transaction_hash' => $transaction_data['hash'],
            'blockchain' => $transaction_data['blockchain'],
            'amount' => $transaction_data['amount'],
            'from_address' => $this->mask_address($transaction_data['from']),
            'to_address' => $this->mask_address($transaction_data['to'])
        ), true);
    }
    
    public function log_artwork_created($artwork_data) {
        $this->log(self::LOG_LEVEL_INFO, 'Artwork created', array(
            'artwork_id' => $artwork_data['id'],
            'artist_id' => $artwork_data['artist_id'],
            'title' => $artwork_data['title'],
            'medium' => $artwork_data['medium'],
            'price' => $artwork_data['price']
        ));
    }
    
    public function log_nft_minted($nft_data) {
        $this->log(self::LOG_LEVEL_INFO, 'NFT minted', array(
            'nft_id' => $nft_data['id'],
            'artwork_id' => $nft_data['artwork_id'],
            'token_id' => $nft_data['token_id'],
            'blockchain' => $nft_data['blockchain'],
            'contract_address' => $this->mask_address($nft_data['contract_address'])
        ), true);
    }
    
    /**
     * GitHub integration methods
     */
    public function sync_logs_to_github() {
        if (!$this->github_settings['enabled']) {
            return;
        }
        
        try {
            $logs = $this->get_recent_logs();
            $log_content = $this->format_logs_for_github($logs);
            
            $this->push_to_github($log_content);
            
            $this->log(self::LOG_LEVEL_INFO, 'Logs synced to GitHub', array(
                'logs_count' => count($logs),
                'repository' => $this->github_settings['repository']
            ));
            
        } catch (Exception $e) {
            $this->log(self::LOG_LEVEL_ERROR, 'GitHub sync failed', array(
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Get recent logs from database
     */
    private function get_recent_logs($limit = 100) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_logs';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Format logs for GitHub
     */
    private function format_logs_for_github($logs) {
        $content = "# VORTEX AI Engine - Real-Time Logs\n\n";
        $content .= "Generated: " . current_time('Y-m-d H:i:s') . "\n";
        $content .= "Total Entries: " . count($logs) . "\n\n";
        
        foreach ($logs as $log) {
            $content .= "## " . $log['timestamp'] . " - " . $log['level'] . "\n";
            $content .= "**Message:** " . $log['message'] . "\n";
            
            if (!empty($log['context'])) {
                $context = json_decode($log['context'], true);
                if ($context) {
                    $content .= "**Context:**\n";
                    $content .= "```json\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n```\n";
                }
            }
            
            $content .= "**User ID:** " . $log['user_id'] . "\n";
            $content .= "**IP:** " . $this->mask_ip($log['ip_address']) . "\n";
            $content .= "**Encrypted:** " . ($log['encrypted'] ? 'Yes' : 'No') . "\n\n";
        }
        
        return $content;
    }
    
    /**
     * Push logs to GitHub
     */
    private function push_to_github($content) {
        $url = "https://api.github.com/repos/{$this->github_settings['repository']}/contents/logs/vortex-logs-" . date('Y-m-d') . ".md";
        
        $headers = array(
            'Authorization' => 'token ' . $this->github_settings['token'],
            'Content-Type' => 'application/json',
            'User-Agent' => 'VORTEX-AI-Engine/2.2.0'
        );
        
        $data = array(
            'message' => 'VORTEX AI Engine - Real-time logs update',
            'content' => base64_encode($content),
            'branch' => $this->github_settings['branch']
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('GitHub API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['message']) && $body['message'] !== 'OK') {
            throw new Exception('GitHub API error: ' . $body['message']);
        }
    }
    
    /**
     * Utility methods
     */
    private function sanitize_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? 
            substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    }
    
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
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function sanitize_request_uri() {
        return isset($_SERVER['REQUEST_URI']) ? 
            substr($_SERVER['REQUEST_URI'], 0, 255) : '';
    }
    
    private function get_session_id() {
        return session_id() ?: 'no-session';
    }
    
    private function mask_ip($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\d+$/', '***', $ip);
        }
        return $ip;
    }
    
    private function mask_address($address) {
        if (strlen($address) > 10) {
            return substr($address, 0, 6) . '...' . substr($address, -4);
        }
        return $address;
    }
    
    /**
     * Get logs for admin interface
     */
    public function get_logs($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_logs';
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($filters['level'])) {
            $where_clauses[] = 'level = %s';
            $where_values[] = $filters['level'];
        }
        
        if (!empty($filters['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT * FROM {$table_name} {$where_sql} ORDER BY created_at DESC LIMIT 1000";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Clear old logs
     */
    public function clear_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_logs';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        $this->log(self::LOG_LEVEL_INFO, 'Old logs cleared', array(
            'days' => $days,
            'deleted_count' => $deleted
        ));
        
        return $deleted;
    }
}

// Initialize the logger
VORTEX_Realtime_Logger::get_instance(); 