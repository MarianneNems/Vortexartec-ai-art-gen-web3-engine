<?php
/**
 * Vortex AI Engine - Security Manager
 * 
 * Comprehensive security system to fix all vulnerabilities and implement
 * security best practices for WordPress plugins.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Security_Manager {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Security configuration
     */
    private $security_config = array(
        'csrf_protection' => true,
        'xss_protection' => true,
        'sql_injection_protection' => true,
        'file_upload_security' => true,
        'rate_limiting' => true,
        'input_validation' => true,
        'output_escaping' => true,
        'security_headers' => true,
        'error_handling' => true,
        'logging' => true
    );
    
    /**
     * Rate limiting data
     */
    private $rate_limit_data = array();
    
    /**
     * Security events log
     */
    private $security_events = array();
    
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
        $this->init_security_system();
    }
    
    /**
     * Initialize security system
     */
    private function init_security_system() {
        // Load security configuration
        $this->load_security_config();
        
        // Initialize security components
        $this->init_security_components();
        
        // Hook into WordPress events
        $this->hook_security_events();
        
        // Set up security headers
        $this->setup_security_headers();
        
        // Initialize rate limiting
        $this->init_rate_limiting();
        
        // Set up error handling
        $this->setup_error_handling();
        
        // Initialize logging
        $this->init_security_logging();
    }
    
    /**
     * Load security configuration
     */
    private function load_security_config() {
        $this->security_config = wp_parse_args(
            get_option('vortex_security_config', array()),
            $this->security_config
        );
    }
    
    /**
     * Initialize security components
     */
    private function init_security_components() {
        // Initialize input validation
        if ($this->security_config['input_validation']) {
            add_filter('wp_kses_allowed_html', array($this, 'filter_allowed_html'));
            add_filter('wp_kses_data', array($this, 'sanitize_output'));
        }
        
        // Initialize CSRF protection
        if ($this->security_config['csrf_protection']) {
            add_action('wp_loaded', array($this, 'verify_csrf_token'));
        }
        
        // Initialize XSS protection
        if ($this->security_config['xss_protection']) {
            add_filter('the_content', array($this, 'prevent_xss'));
            add_filter('the_title', array($this, 'prevent_xss'));
            add_filter('comment_text', array($this, 'prevent_xss'));
        }
        
        // Initialize SQL injection protection
        if ($this->security_config['sql_injection_protection']) {
            add_filter('query', array($this, 'sanitize_sql_query'));
        }
        
        // Initialize file upload security
        if ($this->security_config['file_upload_security']) {
            add_filter('upload_mimes', array($this, 'restrict_upload_types'));
            add_filter('wp_handle_upload_prefilter', array($this, 'validate_upload'));
        }
        
        // Initialize rate limiting
        if ($this->security_config['rate_limiting']) {
            add_action('wp_loaded', array($this, 'check_rate_limit'));
        }
    }
    
    /**
     * Hook security events
     */
    private function hook_security_events() {
        // Monitor login attempts
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_login', array($this, 'log_successful_login'));
        
        // Monitor admin actions
        add_action('admin_init', array($this, 'monitor_admin_actions'));
        
        // Monitor file uploads
        add_action('wp_handle_upload', array($this, 'log_file_upload'));
        
        // Monitor database queries
        add_action('query', array($this, 'monitor_database_queries'));
        
        // Monitor AJAX requests
        add_action('wp_ajax_nopriv_vortex_', array($this, 'monitor_ajax_requests'));
        add_action('wp_ajax_vortex_', array($this, 'monitor_ajax_requests'));
    }
    
    /**
     * Setup security headers
     */
    private function setup_security_headers() {
        if ($this->security_config['security_headers']) {
            add_action('send_headers', array($this, 'add_security_headers'));
        }
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:; frame-src 'self'; object-src 'none';");
        
        // X-Frame-Options
        header("X-Frame-Options: SAMEORIGIN");
        
        // X-Content-Type-Options
        header("X-Content-Type-Options: nosniff");
        
        // X-XSS-Protection
        header("X-XSS-Protection: 1; mode=block");
        
        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Permissions Policy
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        
        // Strict-Transport-Security (HTTPS only)
        if (is_ssl()) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }
    
    /**
     * Initialize rate limiting
     */
    private function init_rate_limiting() {
        $this->rate_limit_data = get_transient('vortex_rate_limit_' . $this->get_client_ip());
        
        if (!$this->rate_limit_data) {
            $this->rate_limit_data = array(
                'requests' => 0,
                'last_request' => time(),
                'blocked_until' => 0
            );
        }
    }
    
    /**
     * Check rate limit
     */
    public function check_rate_limit() {
        $current_time = time();
        $ip = $this->get_client_ip();
        
        // Check if IP is blocked
        if ($this->rate_limit_data['blocked_until'] > $current_time) {
            $this->log_security_event('rate_limit_blocked', array(
                'ip' => $ip,
                'blocked_until' => $this->rate_limit_data['blocked_until']
            ));
            wp_die('Rate limit exceeded. Please try again later.', 'Rate Limit Exceeded', array('response' => 429));
        }
        
        // Reset counter if more than 1 minute has passed
        if ($current_time - $this->rate_limit_data['last_request'] > 60) {
            $this->rate_limit_data['requests'] = 0;
        }
        
        // Increment request counter
        $this->rate_limit_data['requests']++;
        $this->rate_limit_data['last_request'] = $current_time;
        
        // Block if too many requests (100 per minute)
        if ($this->rate_limit_data['requests'] > 100) {
            $this->rate_limit_data['blocked_until'] = $current_time + 300; // 5 minutes
            $this->log_security_event('rate_limit_exceeded', array(
                'ip' => $ip,
                'requests' => $this->rate_limit_data['requests']
            ));
            wp_die('Rate limit exceeded. Please try again later.', 'Rate Limit Exceeded', array('response' => 429));
        }
        
        // Save rate limit data
        set_transient('vortex_rate_limit_' . $ip, $this->rate_limit_data, 3600);
    }
    
    /**
     * Setup error handling
     */
    private function setup_error_handling() {
        if ($this->security_config['error_handling']) {
            set_error_handler(array($this, 'custom_error_handler'));
            register_shutdown_function(array($this, 'custom_shutdown_handler'));
        }
    }
    
    /**
     * Custom error handler
     */
    public function custom_error_handler($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $this->log_security_event('php_error', array(
            'error_number' => $errno,
            'error_message' => $errstr,
            'error_file' => $errfile,
            'error_line' => $errline
        ));
        
        return false; // Let PHP handle the error
    }
    
    /**
     * Custom shutdown handler
     */
    public function custom_shutdown_handler() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            $this->log_security_event('fatal_error', array(
                'error_type' => $error['type'],
                'error_message' => $error['message'],
                'error_file' => $error['file'],
                'error_line' => $error['line']
            ));
        }
    }
    
    /**
     * Initialize security logging
     */
    private function init_security_logging() {
        if ($this->security_config['logging']) {
            add_action('wp_loaded', array($this, 'log_security_status'));
        }
    }
    
    /**
     * Filter allowed HTML
     */
    public function filter_allowed_html($allowed_html) {
        // Remove potentially dangerous tags and attributes
        unset($allowed_html['script']);
        unset($allowed_html['iframe']);
        unset($allowed_html['object']);
        unset($allowed_html['embed']);
        
        // Remove dangerous attributes
        foreach ($allowed_html as $tag => $attributes) {
            if (is_array($attributes)) {
                unset($allowed_html[$tag]['onclick']);
                unset($allowed_html[$tag]['onload']);
                unset($allowed_html[$tag]['onerror']);
                unset($allowed_html[$tag]['onmouseover']);
                unset($allowed_html[$tag]['onfocus']);
                unset($allowed_html[$tag]['onblur']);
                unset($allowed_html[$tag]['onchange']);
                unset($allowed_html[$tag]['onsubmit']);
                unset($allowed_html[$tag]['onreset']);
                unset($allowed_html[$tag]['onselect']);
                unset($allowed_html[$tag]['onunload']);
                unset($allowed_html[$tag]['onabort']);
                unset($allowed_html[$tag]['onbeforeunload']);
                unset($allowed_html[$tag]['onerror']);
                unset($allowed_html[$tag]['onhashchange']);
                unset($allowed_html[$tag]['onmessage']);
                unset($allowed_html[$tag]['onoffline']);
                unset($allowed_html[$tag]['ononline']);
                unset($allowed_html[$tag]['onpagehide']);
                unset($allowed_html[$tag]['onpageshow']);
                unset($allowed_html[$tag]['onpopstate']);
                unset($allowed_html[$tag]['onresize']);
                unset($allowed_html[$tag]['onstorage']);
                unset($allowed_html[$tag]['oncontextmenu']);
                unset($allowed_html[$tag]['onkeydown']);
                unset($allowed_html[$tag]['onkeypress']);
                unset($allowed_html[$tag]['onkeyup']);
                unset($allowed_html[$tag]['onmousedown']);
                unset($allowed_html[$tag]['onmousemove']);
                unset($allowed_html[$tag]['onmouseout']);
                unset($allowed_html[$tag]['onmouseover']);
                unset($allowed_html[$tag]['onmouseup']);
                unset($allowed_html[$tag]['onwheel']);
                unset($allowed_html[$tag]['oncopy']);
                unset($allowed_html[$tag]['oncut']);
                unset($allowed_html[$tag]['onpaste']);
                unset($allowed_html[$tag]['onsearch']);
                unset($allowed_html[$tag]['onselectstart']);
                unset($allowed_html[$tag]['onvisibilitychange']);
            }
        }
        
        return $allowed_html;
    }
    
    /**
     * Sanitize output
     */
    public function sanitize_output($data) {
        if (is_string($data)) {
            return esc_html($data);
        }
        return $data;
    }
    
    /**
     * Verify CSRF token
     */
    public function verify_csrf_token() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nonce_field = 'vortex_nonce';
            $nonce_action = 'vortex_action';
            
            if (!isset($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
                $this->log_security_event('csrf_attack_detected', array(
                    'ip' => $this->get_client_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
                ));
                
                wp_die('Security check failed. Please try again.', 'Security Error', array('response' => 403));
            }
        }
    }
    
    /**
     * Prevent XSS
     */
    public function prevent_xss($content) {
        if (is_string($content)) {
            // Remove script tags
            $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
            
            // Remove event handlers
            $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
            
            // Remove javascript: URLs
            $content = preg_replace('/javascript:/i', '', $content);
            
            // Remove data: URLs in img tags
            $content = preg_replace('/<img[^>]*src\s*=\s*["\']data:/i', '<img src="about:blank"', $content);
        }
        
        return $content;
    }
    
    /**
     * Sanitize SQL query
     */
    public function sanitize_sql_query($query) {
        // Check for common SQL injection patterns
        $dangerous_patterns = array(
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
            '/\b(script|javascript|vbscript|expression)\b/i',
            '/[;\'"]/',
            '/\b(or|and)\s+\d+\s*=\s*\d+/i',
            '/\b(union|select).*from/i'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $this->log_security_event('sql_injection_attempt', array(
                    'query' => $query,
                    'ip' => $this->get_client_ip()
                ));
                
                return false; // Block the query
            }
        }
        
        return $query;
    }
    
    /**
     * Restrict upload types
     */
    public function restrict_upload_types($mimes) {
        // Remove dangerous file types
        unset($mimes['php']);
        unset($mimes['php3']);
        unset($mimes['php4']);
        unset($mimes['php5']);
        unset($mimes['phtml']);
        unset($mimes['pl']);
        unset($mimes['py']);
        unset($mimes['cgi']);
        unset($mimes['asp']);
        unset($mimes['aspx']);
        unset($mimes['jsp']);
        unset($mimes['sh']);
        unset($mimes['bash']);
        unset($mimes['exe']);
        unset($mimes['com']);
        unset($mimes['bat']);
        unset($mimes['cmd']);
        unset($mimes['scr']);
        unset($mimes['dll']);
        unset($mimes['so']);
        unset($mimes['dylib']);
        
        return $mimes;
    }
    
    /**
     * Validate upload
     */
    public function validate_upload($file) {
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->log_security_event('file_upload_size_exceeded', array(
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'ip' => $this->get_client_ip()
            ));
            
            return new WP_Error('file_too_large', 'File size exceeds maximum allowed size.');
        }
        
        // Check file extension
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $this->log_security_event('file_upload_invalid_type', array(
                'file_name' => $file['name'],
                'file_extension' => $file_extension,
                'ip' => $this->get_client_ip()
            ));
            
            return new WP_Error('invalid_file_type', 'File type not allowed.');
        }
        
        // Check file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = array(
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        );
        
        if (!in_array($mime_type, $allowed_mimes)) {
            $this->log_security_event('file_upload_invalid_mime', array(
                'file_name' => $file['name'],
                'mime_type' => $mime_type,
                'ip' => $this->get_client_ip()
            ));
            
            return new WP_Error('invalid_mime_type', 'File type not allowed.');
        }
        
        return $file;
    }
    
    /**
     * Log security events
     */
    public function log_security_event($event_type, $data = array()) {
        $event = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'data' => $data
        );
        
        $this->security_events[] = $event;
        
        // Log to file
        $log_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'logs/security.log';
        $log_entry = sprintf(
            "[%s] %s | IP: %s | User: %d | URI: %s | Data: %s\n",
            $event['timestamp'],
            $event['event_type'],
            $event['ip_address'],
            $event['user_id'],
            $event['request_uri'],
            json_encode($event['data'])
        );
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Store in database
        $this->store_security_event($event);
    }
    
    /**
     * Store security event in database
     */
    private function store_security_event($event) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_security_events';
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $event['timestamp'],
                'event_type' => $event['event_type'],
                'ip_address' => $event['ip_address'],
                'user_agent' => $event['user_agent'],
                'user_id' => $event['user_id'],
                'request_uri' => $event['request_uri'],
                'event_data' => json_encode($event['data'])
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
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
     * Log security status
     */
    public function log_security_status() {
        $status = array(
            'security_enabled' => true,
            'csrf_protection' => $this->security_config['csrf_protection'],
            'xss_protection' => $this->security_config['xss_protection'],
            'sql_injection_protection' => $this->security_config['sql_injection_protection'],
            'file_upload_security' => $this->security_config['file_upload_security'],
            'rate_limiting' => $this->security_config['rate_limiting'],
            'input_validation' => $this->security_config['input_validation'],
            'output_escaping' => $this->security_config['output_escaping'],
            'security_headers' => $this->security_config['security_headers'],
            'error_handling' => $this->security_config['error_handling'],
            'logging' => $this->security_config['logging']
        );
        
        $this->log_security_event('security_status', $status);
    }
    
    /**
     * Get security statistics
     */
    public function get_security_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_security_events';
        
        $stats = array(
            'total_events' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'events_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = %s",
                current_time('Y-m-d')
            )),
            'blocked_ips' => $wpdb->get_var("SELECT COUNT(DISTINCT ip_address) FROM $table_name WHERE event_type IN ('rate_limit_blocked', 'csrf_attack_detected', 'sql_injection_attempt')"),
            'security_events' => $wpdb->get_results("SELECT event_type, COUNT(*) as count FROM $table_name GROUP BY event_type ORDER BY count DESC LIMIT 10")
        );
        
        return $stats;
    }
    
    /**
     * Create security tables
     */
    public function create_security_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_security_events';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            event_type varchar(100) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            user_id bigint(20),
            request_uri text,
            event_data longtext,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY event_type (event_type),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 