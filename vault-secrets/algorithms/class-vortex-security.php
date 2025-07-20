<?php
/**
 * Security Class: API Key Encryption, Input Validation & Threat Detection
 *
 * SECURITY FEATURES:
 * ✅ AES-256-CBC API Key Encryption
 * ✅ Advanced Input Validation & Sanitization
 * ✅ File Upload Security with Magic Byte Validation
 * ✅ SQL Injection & XSS Attack Prevention
 * ✅ Security Event Logging & Monitoring
 * ✅ IP-based Rate Limiting & Threat Detection
 * ✅ Emergency Security Response
 *
 * @package VortexAIEngine
 * @version 2.1.1 - Security Hardened
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VortexAIEngine_Security')) {
class VortexAIEngine_Security {
    private static $instance = null;
    private static $encryption_key = null;
    private $security_log_table;
    
    // Security configuration
    private $config = [
        'encryption_enabled' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 3600, // 1 hour
        'security_logging' => true,
        'file_scan_enabled' => true,
        'ip_whitelist' => [],
        'threat_detection' => true
    ];

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->security_log_table = $wpdb->prefix . 'vortex_security_log';
        $this->init_security();
    }

    private function init_security() {
        // Initialize security measures
        add_action( 'init', [ $this, 'setup_security_headers' ] );
        add_action( 'wp_login_failed', [ $this, 'handle_failed_login' ] );
        add_filter( 'authenticate', [ $this, 'check_login_lockout' ], 30, 3 );
        
        // Create security log table if needed
        $this->create_security_log_table();
        
        // Load configuration
        $this->load_security_config();
    }

    /**
     * Encrypt API key using AES-256-CBC
     */
    public static function encrypt_api_key( $api_key ) {
        if ( empty( $api_key ) ) {
            return '';
        }

        try {
            $encryption_key = self::get_encryption_key();
            $iv = openssl_random_pseudo_bytes( 16 );
            $encrypted = openssl_encrypt( $api_key, 'AES-256-CBC', $encryption_key, 0, $iv );
            
            if ( $encrypted === false ) {
                error_log( '[VortexAI Security] API key encryption failed' );
                return $api_key; // Fallback to unencrypted
            }
            
            return base64_encode( $iv . $encrypted );
            
        } catch ( Exception $e ) {
            error_log( '[VortexAI Security] Encryption error: ' . $e->getMessage() );
            return $api_key; // Fallback to unencrypted
        }
    }

    /**
     * Decrypt API key
     */
    public static function decrypt_api_key( $encrypted_data ) {
        if ( empty( $encrypted_data ) ) {
            return '';
        }

        // Check if data is actually encrypted (base64 encoded)
        if ( ! preg_match( '/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $encrypted_data ) ) {
            // Not encrypted, return as-is (backward compatibility)
            return $encrypted_data;
        }

        try {
            $data = base64_decode( $encrypted_data, true );
            if ( $data === false || strlen( $data ) < 16 ) {
                // Not valid encrypted data, return as-is
                return $encrypted_data;
            }
            
            $iv = substr( $data, 0, 16 );
            $encrypted = substr( $data, 16 );
            
            $encryption_key = self::get_encryption_key();
            $decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $encryption_key, 0, $iv );
            
            if ( $decrypted === false ) {
                error_log( '[VortexAI Security] API key decryption failed' );
                return $encrypted_data; // Return encrypted data if can't decrypt
            }
            
            return $decrypted;
            
        } catch ( Exception $e ) {
            error_log( '[VortexAI Security] Decryption error: ' . $e->getMessage() );
            return $encrypted_data; // Return original if decryption fails
        }
    }

    /**
     * Get or generate encryption key
     */
    private static function get_encryption_key() {
        if ( empty( self::$encryption_key ) ) {
            // Try to get existing key
            $key = get_option( 'vortex_encryption_key' );
            
            if ( empty( $key ) ) {
                // Generate new key
                $key = bin2hex( random_bytes( 32 ) );
                update_option( 'vortex_encryption_key', $key );
                
                // Log key generation for security audit
                error_log( '[VortexAI Security] New encryption key generated' );
            }
            
            self::$encryption_key = hex2bin( $key );
        }
        
        return self::$encryption_key;
    }

    /**
     * Validate API key format and security
     */
    public static function validate_api_key( $api_key ) {
        if ( empty( $api_key ) ) {
            return false;
        }

        // Check basic format
        if ( ! preg_match( '/^sk-[a-zA-Z0-9]{20,}$/', $api_key ) ) {
            self::log_security_event( 'invalid_api_key_format', [
                'api_key_prefix' => substr( $api_key, 0, 10 ) . '...',
                'length' => strlen( $api_key )
            ] );
            return false;
        }

        // Check for dangerous patterns
        $dangerous_patterns = [
            '/\.\./i',              // Directory traversal
            '/<script/i',           // XSS attempts
            '/union.*select/i',     // SQL injection
            '/base64_decode/i',     // Code injection
            '/eval\s*\(/i',         // Code execution
            '/exec\s*\(/i',         // Command execution
            '/system\s*\(/i',       // System commands
            '/file_get_contents/i', // File access
            '/curl_exec/i',         // External requests
        ];

        foreach ( $dangerous_patterns as $pattern ) {
            if ( preg_match( $pattern, $api_key ) ) {
                self::log_security_event( 'dangerous_pattern_in_api_key', [
                    'pattern' => $pattern,
                    'api_key_prefix' => substr( $api_key, 0, 10 ) . '...'
                ] );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate file upload with magic byte checking
     */
    public static function validate_file_upload( $file ) {
        if ( ! is_array( $file ) || empty( $file['tmp_name'] ) ) {
            return false;
        }

        // Check file size (max 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ( $file['size'] > $max_size ) {
            self::log_security_event( 'file_upload_too_large', [
                'file_size' => $file['size'],
                'max_size' => $max_size,
                'filename' => $file['name']
            ] );
            return false;
        }

        // MIME type whitelist
        $allowed_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/tiff'
        ];

        // Check MIME type
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            self::log_security_event( 'file_upload_invalid_mime', [
                'mime_type' => $file['type'],
                'filename' => $file['name']
            ] );
            return false;
        }

        // Check file signature (magic bytes)
        if ( ! file_exists( $file['tmp_name'] ) ) {
            return false;
        }

        $file_content = file_get_contents( $file['tmp_name'], false, null, 0, 12 );
        if ( $file_content === false ) {
            return false;
        }

        $file_signature = bin2hex( $file_content );

        $valid_signatures = [
            'ffd8ff',       // JPEG
            '89504e47',     // PNG
            '47494638',     // GIF87a
            '474946384139', // GIF89a
            '52494646',     // WEBP (RIFF)
            '424d',         // BMP
            '49492a00',     // TIFF (little-endian)
            '4d4d002a',     // TIFF (big-endian)
        ];

        $is_valid = false;
        foreach ( $valid_signatures as $signature ) {
            if ( substr( $file_signature, 0, strlen( $signature ) ) === $signature ) {
                $is_valid = true;
                break;
            }
        }

        if ( ! $is_valid ) {
            self::log_security_event( 'file_upload_invalid_signature', [
                'file_signature' => substr( $file_signature, 0, 20 ),
                'filename' => $file['name'],
                'mime_type' => $file['type']
            ] );
            return false;
        }

        // Additional security: scan for embedded scripts
        $file_full_content = file_get_contents( $file['tmp_name'] );
        $script_patterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/<iframe/i',
            '/<embed/i',
            '/<object/i'
        ];

        foreach ( $script_patterns as $pattern ) {
            if ( preg_match( $pattern, $file_full_content ) ) {
                self::log_security_event( 'file_upload_embedded_script', [
                    'pattern' => $pattern,
                    'filename' => $file['name']
                ] );
                return false;
            }
        }

        return true;
    }

    /**
     * Enhanced input sanitization
     */
    public static function sanitize_input( $input, $type = 'text' ) {
        if ( is_array( $input ) ) {
            return array_map( [ self::class, 'sanitize_input' ], $input );
        }

        switch ( $type ) {
            case 'email':
                return sanitize_email( $input );
            
            case 'url':
                return esc_url_raw( $input );
            
            case 'number':
                return is_numeric( $input ) ? floatval( $input ) : 0;
            
            case 'textarea':
                return sanitize_textarea_field( $input );
            
            case 'key':
                // For API keys and similar sensitive data
                return preg_replace( '/[^a-zA-Z0-9\-_]/', '', $input );
            
            case 'text':
            default:
                return sanitize_text_field( $input );
        }
    }

    /**
     * Security event logging
     */
    public static function log_security_event( $event_type, $details = [] ) {
        $instance = self::getInstance();
        
        if ( ! $instance->config['security_logging'] ) {
            return;
        }

        $log_entry = [
            'timestamp' => current_time( 'mysql' ),
            'event_type' => $event_type,
            'user_id' => get_current_user_id(),
            'ip_address' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'details' => json_encode( $details )
        ];

        // Log to WordPress error log
        error_log( '[VortexAI Security] ' . json_encode( $log_entry ) );

        // Store in database
        global $wpdb;
        $wpdb->insert(
            $instance->security_log_table,
            $log_entry,
            [ '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
        );

        // Send alerts for critical events
        $critical_events = [
            'api_key_theft',
            'injection_attempt',
            'file_upload_malware',
            'brute_force_attempt',
            'admin_intrusion',
            'data_exfiltration'
        ];

        if ( in_array( $event_type, $critical_events ) ) {
            $instance->send_security_alert( $log_entry );
        }
    }

    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', $_SERVER[ $key ] )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Setup security headers
     */
    public function setup_security_headers() {
        if ( ! headers_sent() ) {
            // Prevent XSS
            header( 'X-XSS-Protection: 1; mode=block' );
            
            // Prevent MIME sniffing
            header( 'X-Content-Type-Options: nosniff' );
            
            // Prevent clickjacking
            header( 'X-Frame-Options: SAMEORIGIN' );
            
            // Content Security Policy
            $csp = "default-src 'self'; "
                 . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
                 . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
                 . "img-src 'self' data: https: blob:; "
                 . "font-src 'self' https://fonts.gstatic.com; "
                 . "connect-src 'self' https://api.openai.com https://*.amazonaws.com; "
                 . "media-src 'self' blob:; "
                 . "object-src 'none'; "
                 . "frame-ancestors 'self';";
            
            header( "Content-Security-Policy: {$csp}" );
            
            // Referrer Policy
            header( 'Referrer-Policy: strict-origin-when-cross-origin' );
            
            // HSTS (if HTTPS)
            if ( is_ssl() ) {
                header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
            }
        }
    }

    /**
     * Handle failed login attempts
     */
    public function handle_failed_login( $username ) {
        $ip = self::get_client_ip();
        $attempts_key = "vortex_login_attempts_{$ip}";
        
        $attempts = get_transient( $attempts_key );
        $attempts = $attempts ? $attempts + 1 : 1;
        
        set_transient( $attempts_key, $attempts, $this->config['lockout_duration'] );
        
        self::log_security_event( 'login_failed', [
            'username' => $username,
            'ip_address' => $ip,
            'attempt_count' => $attempts
        ] );
        
        if ( $attempts >= $this->config['max_login_attempts'] ) {
            self::log_security_event( 'brute_force_attempt', [
                'username' => $username,
                'ip_address' => $ip,
                'total_attempts' => $attempts
            ] );
        }
    }

    /**
     * Check for login lockout
     */
    public function check_login_lockout( $user, $username, $password ) {
        if ( empty( $username ) && empty( $password ) ) {
            return $user;
        }
        
        $ip = self::get_client_ip();
        $attempts = get_transient( "vortex_login_attempts_{$ip}" );
        
        if ( $attempts >= $this->config['max_login_attempts'] ) {
            return new WP_Error( 'too_many_attempts', 
                sprintf( 
                    __( 'Too many failed login attempts. Please try again in %d minutes.', 'vortex-ai-engine' ),
                    ceil( $this->config['lockout_duration'] / 60 )
                )
            );
        }
        
        return $user;
    }

    /**
     * Create security log table
     */
    private function create_security_log_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->security_log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            event_type varchar(100) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            request_uri text,
            details longtext,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY timestamp (timestamp),
            KEY ip_address (ip_address),
            KEY user_id (user_id)
        ) {$charset_collate};";
        
        if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    /**
     * Load security configuration
     */
    private function load_security_config() {
        $saved_config = get_option( 'vortex_security_config', [] );
        $this->config = array_merge( $this->config, $saved_config );
    }

    /**
     * Send security alert
     */
    private function send_security_alert( $log_entry ) {
        $admin_email = get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );
        
        $subject = "[{$site_name}] Security Alert: {$log_entry['event_type']}";
        
        $message = "A security event has been detected on your website:\n\n";
        $message .= "Event Type: {$log_entry['event_type']}\n";
        $message .= "Timestamp: {$log_entry['timestamp']}\n";
        $message .= "IP Address: {$log_entry['ip_address']}\n";
        $message .= "User Agent: {$log_entry['user_agent']}\n";
        $message .= "Request URI: {$log_entry['request_uri']}\n";
        $message .= "Details: {$log_entry['details']}\n\n";
        $message .= "Please review your security logs and take appropriate action if necessary.\n";
        $message .= "Website: " . home_url() . "\n";
        
        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Get security statistics
     */
    public function get_security_stats( $days = 7 ) {
        global $wpdb;
        
        $since_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        
        $stats = [
            'total_events' => 0,
            'critical_events' => 0,
            'unique_ips' => 0,
            'top_events' => [],
            'recent_events' => []
        ];
        
        // Total events
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->security_log_table} WHERE timestamp >= %s",
            $since_date
        ) );
        $stats['total_events'] = (int) $total;
        
        // Critical events
        $critical_types = "'brute_force_attempt','injection_attempt','file_upload_malware','admin_intrusion'";
        $critical = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->security_log_table} 
             WHERE timestamp >= %s AND event_type IN ({$critical_types})",
            $since_date
        ) );
        $stats['critical_events'] = (int) $critical;
        
        // Unique IPs
        $unique_ips = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_address) FROM {$this->security_log_table} WHERE timestamp >= %s",
            $since_date
        ) );
        $stats['unique_ips'] = (int) $unique_ips;
        
        // Top events
        $top_events = $wpdb->get_results( $wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
             FROM {$this->security_log_table} 
             WHERE timestamp >= %s 
             GROUP BY event_type 
             ORDER BY count DESC 
             LIMIT 10",
            $since_date
        ) );
        $stats['top_events'] = $top_events;
        
        // Recent events
        $recent_events = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->security_log_table} 
             WHERE timestamp >= %s 
             ORDER BY timestamp DESC 
             LIMIT 20",
            $since_date
        ) );
        $stats['recent_events'] = $recent_events;
        
        return $stats;
    }

    /**
     * Emergency security lockdown
     */
    public function emergency_lockdown() {
        // Disable all AJAX endpoints
        remove_all_actions( 'wp_ajax_huraii_generate' );
        remove_all_actions( 'wp_ajax_huraii_describe' );
        remove_all_actions( 'wp_ajax_nopriv_huraii_generate' );
        remove_all_actions( 'wp_ajax_nopriv_huraii_describe' );
        
        // Log the lockdown
        self::log_security_event( 'emergency_lockdown', [
            'triggered_by' => get_current_user_id(),
            'timestamp' => current_time( 'mysql' )
        ] );
        
        // Send notification
        $this->send_security_alert( [
            'event_type' => 'emergency_lockdown',
            'timestamp' => current_time( 'mysql' ),
            'ip_address' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'details' => 'Emergency security lockdown activated'
        ] );
        
        return true;
    }
}

// Initialize security
VortexAIEngine_Security::getInstance();
} 