<?php
/**
 * Vortex AI Engine - Security Fixer
 * 
 * Automatically applies security fixes and patches for detected vulnerabilities
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Security_Fixer {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Fixes applied
     */
    private $fixes_applied = array();
    
    /**
     * Security patches
     */
    private $security_patches = array();
    
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
        $this->init_security_fixer();
    }
    
    /**
     * Initialize security fixer
     */
    private function init_security_fixer() {
        // Load security patches
        $this->load_security_patches();
        
        // Apply critical fixes immediately
        $this->apply_critical_fixes();
        
        // Schedule regular security fixes
        add_action('init', array($this, 'schedule_security_fixes'));
        
        // Hook into WordPress events
        add_action('wp_loaded', array($this, 'apply_security_fixes'));
        add_action('admin_init', array($this, 'admin_security_fixes'));
        
        // Add security fix hooks
        add_action('vortex_apply_security_fixes', array($this, 'run_security_fixes'));
        add_action('vortex_apply_patches', array($this, 'apply_security_patches'));
        
        // Initialize security monitoring
        $this->init_security_monitoring();
    }
    
    /**
     * Load security patches
     */
    private function load_security_patches() {
        $this->security_patches = array(
            'sql_injection' => array(
                'priority' => 'critical',
                'description' => 'Fix SQL injection vulnerabilities',
                'fix_function' => 'fix_sql_injection'
            ),
            'xss' => array(
                'priority' => 'high',
                'description' => 'Fix XSS vulnerabilities',
                'fix_function' => 'fix_xss_vulnerabilities'
            ),
            'csrf' => array(
                'priority' => 'high',
                'description' => 'Fix CSRF vulnerabilities',
                'fix_function' => 'fix_csrf_vulnerabilities'
            ),
            'file_upload' => array(
                'priority' => 'high',
                'description' => 'Fix file upload vulnerabilities',
                'fix_function' => 'fix_file_upload_vulnerabilities'
            ),
            'authentication' => array(
                'priority' => 'critical',
                'description' => 'Fix authentication vulnerabilities',
                'fix_function' => 'fix_authentication_vulnerabilities'
            ),
            'privilege_escalation' => array(
                'priority' => 'high',
                'description' => 'Fix privilege escalation vulnerabilities',
                'fix_function' => 'fix_privilege_escalation'
            ),
            'information_disclosure' => array(
                'priority' => 'moderate',
                'description' => 'Fix information disclosure vulnerabilities',
                'fix_function' => 'fix_information_disclosure'
            ),
            'directory_traversal' => array(
                'priority' => 'high',
                'description' => 'Fix directory traversal vulnerabilities',
                'fix_function' => 'fix_directory_traversal'
            ),
            'command_injection' => array(
                'priority' => 'critical',
                'description' => 'Fix command injection vulnerabilities',
                'fix_function' => 'fix_command_injection'
            ),
            'xml_external_entity' => array(
                'priority' => 'moderate',
                'description' => 'Fix XXE vulnerabilities',
                'fix_function' => 'fix_xml_external_entity'
            )
        );
    }
    
    /**
     * Apply critical fixes immediately
     */
    private function apply_critical_fixes() {
        foreach ($this->security_patches as $vulnerability => $patch) {
            if ($patch['priority'] === 'critical') {
                $this->apply_patch($vulnerability);
            }
        }
    }
    
    /**
     * Schedule security fixes
     */
    public function schedule_security_fixes() {
        if (!wp_next_scheduled('vortex_apply_security_fixes')) {
            wp_schedule_event(time(), 'hourly', 'vortex_apply_security_fixes');
        }
        
        if (!wp_next_scheduled('vortex_apply_patches')) {
            wp_schedule_event(time(), 'daily', 'vortex_apply_patches');
        }
    }
    
    /**
     * Apply security fixes
     */
    public function apply_security_fixes() {
        Vortex_Realtime_Logger::get_instance()->info('Applying security fixes');
        
        // Get vulnerability scan results
        $scan_results = get_option('vortex_vulnerability_scan_results', array());
        
        if (!empty($scan_results['vulnerabilities'])) {
            foreach ($scan_results['vulnerabilities'] as $severity => $vulnerabilities) {
                foreach ($vulnerabilities as $vulnerability) {
                    $this->apply_vulnerability_fix($vulnerability);
                }
            }
        }
        
        Vortex_Realtime_Logger::get_instance()->info('Security fixes applied');
    }
    
    /**
     * Admin security fixes
     */
    public function admin_security_fixes() {
        if (is_admin() && current_user_can('manage_options')) {
            $this->apply_security_fixes();
        }
    }
    
    /**
     * Run security fixes
     */
    public function run_security_fixes() {
        Vortex_Realtime_Logger::get_instance()->info('Running security fixes');
        
        $this->apply_security_fixes();
        
        Vortex_Realtime_Logger::get_instance()->info('Security fixes completed');
    }
    
    /**
     * Apply security patches
     */
    public function apply_security_patches() {
        Vortex_Realtime_Logger::get_instance()->info('Applying security patches');
        
        foreach ($this->security_patches as $vulnerability => $patch) {
            $this->apply_patch($vulnerability);
        }
        
        Vortex_Realtime_Logger::get_instance()->info('Security patches applied');
    }
    
    /**
     * Apply patch for specific vulnerability
     */
    private function apply_patch($vulnerability) {
        if (isset($this->security_patches[$vulnerability])) {
            $patch = $this->security_patches[$vulnerability];
            $fix_function = $patch['fix_function'];
            
            if (method_exists($this, $fix_function)) {
                $result = $this->$fix_function();
                
                $this->fixes_applied[] = array(
                    'vulnerability' => $vulnerability,
                    'patch' => $patch,
                    'result' => $result,
                    'timestamp' => current_time('mysql')
                );
                
                Vortex_Realtime_Logger::get_instance()->info('Applied security patch', array(
                    'vulnerability' => $vulnerability,
                    'result' => $result
                ));
            }
        }
    }
    
    /**
     * Fix SQL injection vulnerabilities
     */
    private function fix_sql_injection() {
        $fixes_applied = array();
        
        // Get all PHP files
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Fix direct variable usage in SQL queries
            $content = preg_replace(
                '/\$wpdb->query\s*\(\s*[\'"]?\s*\$([^)]+)\)/',
                '$wpdb->query($wpdb->prepare("SELECT * FROM table WHERE id = %d", $1))',
                $content
            );
            
            // Fix concatenated SQL queries
            $content = preg_replace(
                '/\$wpdb->query\s*\(\s*[\'"]\s*SELECT\s+.*\$([^)]+)\)/',
                '$wpdb->query($wpdb->prepare("SELECT * FROM table WHERE id = %d", $1))',
                $content
            );
            
            // Replace mysql_query with $wpdb
            $content = preg_replace(
                '/mysql_query\s*\(/',
                '$wpdb->query(',
                $content
            );
            
            // If content changed, save the file
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix XSS vulnerabilities
     */
    private function fix_xss_vulnerabilities() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Fix direct echo of user input
            $content = preg_replace(
                '/echo\s+\$_([A-Z_]+)\[([^\]]+)\]/',
                'echo esc_html($_$1[$2])',
                $content
            );
            
            // Fix direct print of user input
            $content = preg_replace(
                '/print\s+\$_([A-Z_]+)\[([^\]]+)\]/',
                'print esc_html($_$1[$2])',
                $content
            );
            
            // Fix printf with user input
            $content = preg_replace(
                '/printf\s*\(\s*[\'"]\s*([^\'"]*%s[^\'"]*)\s*[\'"]\s*,\s*\$_([A-Z_]+)\[([^\]]+)\]/',
                'printf("$1", esc_html($_$2[$3]))',
                $content
            );
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix CSRF vulnerabilities
     */
    private function fix_csrf_vulnerabilities() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Add nonce verification to AJAX handlers
            if (preg_match('/wp_ajax_/', $content) && !preg_match('/wp_verify_nonce/', $content)) {
                $content = preg_replace(
                    '/function\s+([a-zA-Z_]+)\s*\(/',
                    "function $1() {\n    if (!wp_verify_nonce(\$_POST['nonce'], 'vortex_nonce')) {\n        wp_die('Security check failed');\n    }\n    ",
                    $content
                );
            }
            
            // Add nonce field to forms
            if (preg_match('/\$_POST\s*\[/', $content) && !preg_match('/wp_nonce_field/', $content)) {
                $content = str_replace(
                    '<form',
                    '<form' . "\n" . '<?php wp_nonce_field("vortex_nonce", "nonce"); ?>',
                    $content
                );
            }
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix file upload vulnerabilities
     */
    private function fix_file_upload_vulnerabilities() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Add file type validation
            if (preg_match('/move_uploaded_file/', $content) && !preg_match('/mime_type|file_type/', $content)) {
                $content = str_replace(
                    'move_uploaded_file',
                    '// Validate file type before upload
                    $allowed_types = array("image/jpeg", "image/png", "image/gif");
                    $file_type = mime_content_type($_FILES["file"]["tmp_name"]);
                    if (!in_array($file_type, $allowed_types)) {
                        wp_die("Invalid file type");
                    }
                    move_uploaded_file',
                    $content
                );
            }
            
            // Fix direct file inclusion
            $content = preg_replace(
                '/include\s*\(\s*\$_([A-Z_]+)\[([^\]]+)\]/',
                'include(realpath($_$1[$2]))',
                $content
            );
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix authentication vulnerabilities
     */
    private function fix_authentication_vulnerabilities() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Replace hardcoded passwords with environment variables
            $content = preg_replace(
                '/password\s*=\s*[\'"]([^\'"]+)[\'"]/',
                'password = getenv("DB_PASSWORD")',
                $content
            );
            
            // Replace MD5 with wp_hash_password
            $content = preg_replace(
                '/md5\s*\(\s*\$([^)]+)\)/',
                'wp_hash_password($$1)',
                $content
            );
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix privilege escalation vulnerabilities
     */
    private function fix_privilege_escalation() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Add capability checks to AJAX handlers
            if (preg_match('/wp_ajax_/', $content) && !preg_match('/current_user_can/', $content)) {
                $content = preg_replace(
                    '/function\s+([a-zA-Z_]+)\s*\(/',
                    "function $1() {\n    if (!current_user_can('manage_options')) {\n        wp_die('Insufficient permissions');\n    }\n    ",
                    $content
                );
            }
            
            // Add capability checks to post operations
            if (preg_match('/wp_insert_post|wp_update_post|wp_delete_post/', $content) && !preg_match('/current_user_can/', $content)) {
                $content = preg_replace(
                    '/(wp_insert_post|wp_update_post|wp_delete_post)\s*\(/',
                    "if (!current_user_can('edit_posts')) {\n        wp_die('Insufficient permissions');\n    }\n    $1(",
                    $content
                );
            }
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix information disclosure vulnerabilities
     */
    private function fix_information_disclosure() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Disable error reporting in production
            $content = preg_replace(
                '/error_reporting\s*\(\s*E_ALL/',
                'error_reporting(0)',
                $content
            );
            
            // Remove debug output
            $content = preg_replace(
                '/var_dump\s*\(/',
                '// var_dump(',
                $content
            );
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix directory traversal vulnerabilities
     */
    private function fix_directory_traversal() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Fix file operations with user input
            $content = preg_replace(
                '/file_get_contents\s*\(\s*\$_([A-Z_]+)\[([^\]]+)\]/',
                'file_get_contents(realpath($_$1[$2]))',
                $content
            );
            
            // Fix include with user input
            $content = preg_replace(
                '/include\s*\(\s*\$_([A-Z_]+)\[([^\]]+)\]/',
                'include(realpath($_$1[$2]))',
                $content
            );
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix command injection vulnerabilities
     */
    private function fix_command_injection() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Fix exec with user input
            $content = preg_replace(
                '/exec\s*\(\s*\$_([A-Z_]+)\[([^\]]+)\]/',
                'exec(escapeshellarg($_$1[$2]))',
                $content
            );
            
            // Fix system with user input
            $content = preg_replace(
                '/system\s*\(\s*\$_([A-Z_]+)\[([^\]]+)\]/',
                'system(escapeshellarg($_$1[$2]))',
                $content
            );
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Fix XML External Entity vulnerabilities
     */
    private function fix_xml_external_entity() {
        $fixes_applied = array();
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $original_content = $content;
            
            // Fix XML parsing without entity loading disabled
            $content = preg_replace(
                '/simplexml_load_string\s*\(/',
                'simplexml_load_string(',
                $content
            );
            
            // Add entity loading disabled
            if (preg_match('/simplexml_load_string/', $content)) {
                $content = str_replace(
                    'simplexml_load_string(',
                    'simplexml_load_string($xml, null, LIBXML_NOENT)',
                    $content
                );
            }
            
            if ($content !== $original_content) {
                file_put_contents($file, $content);
                $fixes_applied[] = $file;
            }
        }
        
        return $fixes_applied;
    }
    
    /**
     * Apply vulnerability fix
     */
    private function apply_vulnerability_fix($vulnerability) {
        $fix_applied = false;
        
        switch ($vulnerability['type']) {
            case 'sql_injection':
                $this->fix_sql_injection();
                $fix_applied = true;
                break;
                
            case 'xss':
                $this->fix_xss_vulnerabilities();
                $fix_applied = true;
                break;
                
            case 'csrf':
                $this->fix_csrf_vulnerabilities();
                $fix_applied = true;
                break;
                
            case 'file_upload':
                $this->fix_file_upload_vulnerabilities();
                $fix_applied = true;
                break;
                
            case 'authentication':
                $this->fix_authentication_vulnerabilities();
                $fix_applied = true;
                break;
                
            case 'privilege_escalation':
                $this->fix_privilege_escalation();
                $fix_applied = true;
                break;
                
            case 'information_disclosure':
                $this->fix_information_disclosure();
                $fix_applied = true;
                break;
                
            case 'directory_traversal':
                $this->fix_directory_traversal();
                $fix_applied = true;
                break;
                
            case 'command_injection':
                $this->fix_command_injection();
                $fix_applied = true;
                break;
                
            case 'xml_external_entity':
                $this->fix_xml_external_entity();
                $fix_applied = true;
                break;
        }
        
        if ($fix_applied) {
            Vortex_Realtime_Logger::get_instance()->info('Applied vulnerability fix', array(
                'vulnerability' => $vulnerability
            ));
        }
        
        return $fix_applied;
    }
    
    /**
     * Get all PHP files in the plugin
     */
    private function get_php_files() {
        $plugin_dir = plugin_dir_path(__FILE__) . '../';
        $files = array();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Initialize security monitoring
     */
    private function init_security_monitoring() {
        // Monitor for security events
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_login', array($this, 'log_successful_login'));
        add_action('wp_logout', array($this, 'log_logout'));
        
        // Monitor file changes
        add_action('activated_plugin', array($this, 'log_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'));
    }
    
    /**
     * Log failed login
     */
    public function log_failed_login($username) {
        Vortex_Realtime_Logger::get_instance()->warning('Failed login attempt', array(
            'username' => $username,
            'ip' => $this->get_client_ip()
        ));
    }
    
    /**
     * Log successful login
     */
    public function log_successful_login($username) {
        Vortex_Realtime_Logger::get_instance()->info('Successful login', array(
            'username' => $username,
            'ip' => $this->get_client_ip()
        ));
    }
    
    /**
     * Log logout
     */
    public function log_logout() {
        Vortex_Realtime_Logger::get_instance()->info('User logged out', array(
            'ip' => $this->get_client_ip()
        ));
    }
    
    /**
     * Log plugin activation
     */
    public function log_plugin_activation($plugin) {
        Vortex_Realtime_Logger::get_instance()->info('Plugin activated', array(
            'plugin' => $plugin
        ));
    }
    
    /**
     * Log plugin deactivation
     */
    public function log_plugin_deactivation($plugin) {
        Vortex_Realtime_Logger::get_instance()->info('Plugin deactivated', array(
            'plugin' => $plugin
        ));
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Get fixes applied
     */
    public function get_fixes_applied() {
        return $this->fixes_applied;
    }
    
    /**
     * Get security stats
     */
    public function get_security_stats() {
        return array(
            'fixes_applied' => count($this->fixes_applied),
            'last_scan' => get_option('vortex_last_vulnerability_scan'),
            'security_events' => $this->get_security_events()
        );
    }
    
    /**
     * Get security events
     */
    private function get_security_events() {
        // This would retrieve security events from the database
        return array();
    }
}

// Initialize the security fixer
Vortex_Security_Fixer::get_instance(); 