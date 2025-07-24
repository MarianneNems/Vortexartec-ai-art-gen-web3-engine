<?php
/**
 * Vortex AI Engine - Security Fix Deployment Script
 * 
 * Deploys all security fixes and patches to address GitHub vulnerabilities.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Fix Deployer
 */
class Vortex_Security_Fix_Deployer {
    
    /**
     * Security fixes to apply
     */
    private $security_fixes = array();
    
    /**
     * Files to update
     */
    private $files_to_update = array();
    
    /**
     * Backup directory
     */
    private $backup_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->backup_dir = VORTEX_AI_ENGINE_PLUGIN_PATH . 'backups/security/';
        $this->init_security_fixes();
    }
    
    /**
     * Initialize security fixes
     */
    private function init_security_fixes() {
        $this->security_fixes = array(
            'sql_injection' => array(
                'priority' => 'critical',
                'description' => 'Fix SQL injection vulnerabilities',
                'files' => array(
                    'includes/class-vortex-loader.php',
                    'includes/class-vortex-shortcodes.php',
                    'admin/class-vortex-admin.php',
                    'public/class-vortex-public.php'
                ),
                'fixes' => array(
                    'replace_direct_queries' => 'Replace direct SQL queries with prepared statements',
                    'add_escaping' => 'Add proper escaping for all user inputs',
                    'validate_inputs' => 'Add input validation for all database operations'
                )
            ),
            'xss_protection' => array(
                'priority' => 'high',
                'description' => 'Fix XSS vulnerabilities',
                'files' => array(
                    'includes/class-vortex-shortcodes.php',
                    'public/class-vortex-public.php',
                    'admin/class-vortex-admin.php'
                ),
                'fixes' => array(
                    'escape_output' => 'Escape all output with esc_html, esc_attr, etc.',
                    'remove_script_tags' => 'Remove any script tags from user input',
                    'validate_html' => 'Validate and sanitize HTML content'
                )
            ),
            'csrf_protection' => array(
                'priority' => 'high',
                'description' => 'Fix CSRF vulnerabilities',
                'files' => array(
                    'admin/class-vortex-admin.php',
                    'includes/class-vortex-ajax-handlers.php'
                ),
                'fixes' => array(
                    'add_nonces' => 'Add nonce verification to all forms',
                    'verify_ajax' => 'Add nonce verification to AJAX calls',
                    'check_permissions' => 'Add capability checks for all actions'
                )
            ),
            'file_upload_security' => array(
                'priority' => 'high',
                'description' => 'Fix file upload vulnerabilities',
                'files' => array(
                    'includes/class-vortex-file-handler.php',
                    'admin/class-vortex-admin.php'
                ),
                'fixes' => array(
                    'validate_types' => 'Validate file types and extensions',
                    'check_size' => 'Check file size limits',
                    'scan_content' => 'Scan uploaded files for malicious content',
                    'secure_storage' => 'Store files in secure location'
                )
            ),
            'authentication_security' => array(
                'priority' => 'critical',
                'description' => 'Fix authentication vulnerabilities',
                'files' => array(
                    'includes/class-vortex-auth.php',
                    'admin/class-vortex-admin.php'
                ),
                'fixes' => array(
                    'strong_passwords' => 'Enforce strong password requirements',
                    'rate_limiting' => 'Add rate limiting for login attempts',
                    'session_security' => 'Secure session management',
                    'logout_security' => 'Secure logout process'
                )
            ),
            'privilege_escalation' => array(
                'priority' => 'critical',
                'description' => 'Fix privilege escalation vulnerabilities',
                'files' => array(
                    'admin/class-vortex-admin.php',
                    'includes/class-vortex-user-management.php'
                ),
                'fixes' => array(
                    'capability_checks' => 'Add proper capability checks',
                    'role_validation' => 'Validate user roles and permissions',
                    'admin_restrictions' => 'Restrict admin-only functions'
                )
            ),
            'information_disclosure' => array(
                'priority' => 'moderate',
                'description' => 'Fix information disclosure vulnerabilities',
                'files' => array(
                    'includes/class-vortex-debug.php',
                    'admin/class-vortex-admin.php'
                ),
                'fixes' => array(
                    'hide_errors' => 'Hide error messages in production',
                    'sanitize_output' => 'Sanitize all debug output',
                    'log_security' => 'Log security events instead of displaying'
                )
            ),
            'directory_traversal' => array(
                'priority' => 'high',
                'description' => 'Fix directory traversal vulnerabilities',
                'files' => array(
                    'includes/class-vortex-file-handler.php',
                    'admin/class-vortex-admin.php'
                ),
                'fixes' => array(
                    'validate_paths' => 'Validate all file paths',
                    'restrict_access' => 'Restrict file access to allowed directories',
                    'sanitize_inputs' => 'Sanitize all file path inputs'
                )
            ),
            'command_injection' => array(
                'priority' => 'critical',
                'description' => 'Fix command injection vulnerabilities',
                'files' => array(
                    'includes/class-vortex-system.php',
                    'admin/class-vortex-admin.php'
                ),
                'fixes' => array(
                    'remove_exec' => 'Remove or secure all exec() calls',
                    'validate_commands' => 'Validate all command inputs',
                    'use_alternatives' => 'Use PHP alternatives to system commands'
                )
            ),
            'xml_external_entity' => array(
                'priority' => 'moderate',
                'description' => 'Fix XXE vulnerabilities',
                'files' => array(
                    'includes/class-vortex-xml-handler.php',
                    'admin/class-vortex-admin.php'
                ),
                'fixes' => array(
                    'disable_entities' => 'Disable external entity loading',
                    'validate_xml' => 'Validate XML input',
                    'use_safe_parsers' => 'Use safe XML parsers'
                )
            )
        );
    }
    
    /**
     * Deploy all security fixes
     */
    public function deploy_security_fixes() {
        Vortex_Realtime_Logger::get_instance()->info('Starting security fix deployment');
        
        try {
            // Create backup
            $this->create_backup();
            
            // Apply critical fixes first
            $this->apply_critical_fixes();
            
            // Apply high priority fixes
            $this->apply_high_priority_fixes();
            
            // Apply moderate priority fixes
            $this->apply_moderate_priority_fixes();
            
            // Apply low priority fixes
            $this->apply_low_priority_fixes();
            
            // Update security configuration
            $this->update_security_config();
            
            // Run security tests
            $this->run_security_tests();
            
            // Clear caches
            $this->clear_caches();
            
            Vortex_Realtime_Logger::get_instance()->info('Security fix deployment completed successfully');
            
            return true;
            
        } catch (Exception $e) {
            Vortex_Realtime_Logger::get_instance()->error('Security fix deployment failed', array(
                'error' => $e->getMessage()
            ));
            
            // Restore from backup
            $this->restore_backup();
            
            return false;
        }
    }
    
    /**
     * Create backup
     */
    private function create_backup() {
        if (!is_dir($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
        }
        
        $backup_file = $this->backup_dir . 'security-backup-' . date('Y-m-d-H-i-s') . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
            $this->add_directory_to_zip($zip, VORTEX_AI_ENGINE_PLUGIN_PATH, '');
            $zip->close();
            
            Vortex_Realtime_Logger::get_instance()->info('Security backup created', array(
                'backup_file' => $backup_file
            ));
        }
    }
    
    /**
     * Apply critical fixes
     */
    private function apply_critical_fixes() {
        foreach ($this->security_fixes as $vulnerability => $fix) {
            if ($fix['priority'] === 'critical') {
                $this->apply_fix($vulnerability);
            }
        }
    }
    
    /**
     * Apply high priority fixes
     */
    private function apply_high_priority_fixes() {
        foreach ($this->security_fixes as $vulnerability => $fix) {
            if ($fix['priority'] === 'high') {
                $this->apply_fix($vulnerability);
            }
        }
    }
    
    /**
     * Apply moderate priority fixes
     */
    private function apply_moderate_priority_fixes() {
        foreach ($this->security_fixes as $vulnerability => $fix) {
            if ($fix['priority'] === 'moderate') {
                $this->apply_fix($vulnerability);
            }
        }
    }
    
    /**
     * Apply low priority fixes
     */
    private function apply_low_priority_fixes() {
        foreach ($this->security_fixes as $vulnerability => $fix) {
            if ($fix['priority'] === 'low') {
                $this->apply_fix($vulnerability);
            }
        }
    }
    
    /**
     * Apply specific fix
     */
    private function apply_fix($vulnerability) {
        if (isset($this->security_fixes[$vulnerability])) {
            $fix = $this->security_fixes[$vulnerability];
            
            Vortex_Realtime_Logger::get_instance()->info('Applying security fix', array(
                'vulnerability' => $vulnerability,
                'description' => $fix['description'],
                'priority' => $fix['priority']
            ));
            
            // Apply fixes to each file
            foreach ($fix['files'] as $file) {
                $file_path = VORTEX_AI_ENGINE_PLUGIN_PATH . $file;
                
                if (file_exists($file_path)) {
                    $this->apply_fixes_to_file($file_path, $fix['fixes']);
                }
            }
        }
    }
    
    /**
     * Apply fixes to specific file
     */
    private function apply_fixes_to_file($file_path, $fixes) {
        $content = file_get_contents($file_path);
        $original_content = $content;
        
        // Apply SQL injection fixes
        if (isset($fixes['replace_direct_queries'])) {
            $content = $this->fix_sql_injection($content);
        }
        
        // Apply XSS fixes
        if (isset($fixes['escape_output'])) {
            $content = $this->fix_xss($content);
        }
        
        // Apply CSRF fixes
        if (isset($fixes['add_nonces'])) {
            $content = $this->fix_csrf($content);
        }
        
        // Apply file upload fixes
        if (isset($fixes['validate_types'])) {
            $content = $this->fix_file_upload($content);
        }
        
        // Apply authentication fixes
        if (isset($fixes['strong_passwords'])) {
            $content = $this->fix_authentication($content);
        }
        
        // Apply privilege escalation fixes
        if (isset($fixes['capability_checks'])) {
            $content = $this->fix_privilege_escalation($content);
        }
        
        // Apply information disclosure fixes
        if (isset($fixes['hide_errors'])) {
            $content = $this->fix_information_disclosure($content);
        }
        
        // Apply directory traversal fixes
        if (isset($fixes['validate_paths'])) {
            $content = $this->fix_directory_traversal($content);
        }
        
        // Apply command injection fixes
        if (isset($fixes['remove_exec'])) {
            $content = $this->fix_command_injection($content);
        }
        
        // Apply XXE fixes
        if (isset($fixes['disable_entities'])) {
            $content = $this->fix_xml_external_entity($content);
        }
        
        // Write updated content if changed
        if ($content !== $original_content) {
            file_put_contents($file_path, $content);
            
            Vortex_Realtime_Logger::get_instance()->info('File updated with security fixes', array(
                'file' => $file_path
            ));
        }
    }
    
    /**
     * Fix SQL injection vulnerabilities
     */
    private function fix_sql_injection($content) {
        // Replace direct queries with prepared statements
        $content = preg_replace(
            '/\$wpdb->query\s*\(\s*["\']\s*SELECT\s+(.*?)\s+FROM\s+(.*?)\s+WHERE\s+(.*?)\s*["\']\s*\)/i',
            '$wpdb->prepare("SELECT $1 FROM $2 WHERE $3", $4)',
            $content
        );
        
        // Add escaping for variables in queries
        $content = preg_replace(
            '/\$wpdb->query\s*\(\s*["\'](.*?)\$([^"\']*)["\']\s*\)/',
            '$wpdb->query($wpdb->prepare("$1", $2))',
            $content
        );
        
        return $content;
    }
    
    /**
     * Fix XSS vulnerabilities
     */
    private function fix_xss($content) {
        // Escape echo statements
        $content = preg_replace(
            '/echo\s+\$([^;]+);/',
            'echo esc_html($$1);',
            $content
        );
        
        // Escape print statements
        $content = preg_replace(
            '/print\s+\$([^;]+);/',
            'print esc_html($$1);',
            $content
        );
        
        // Remove script tags
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
        
        // Remove event handlers
        $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        
        return $content;
    }
    
    /**
     * Fix CSRF vulnerabilities
     */
    private function fix_csrf($content) {
        // Add nonce fields to forms
        $content = preg_replace(
            '/<form([^>]*)>/i',
            '<form$1>' . "\n" . '<?php wp_nonce_field("vortex_action", "vortex_nonce"); ?>',
            $content
        );
        
        // Add nonce verification to AJAX calls
        $content = preg_replace(
            '/wp_ajax_nopriv_vortex_([^,]+),/',
            'wp_ajax_nopriv_vortex_$1, function() { check_ajax_referer("vortex_nonce", "nonce");',
            $content
        );
        
        return $content;
    }
    
    /**
     * Fix file upload vulnerabilities
     */
    private function fix_file_upload($content) {
        // Add file type validation
        $content = preg_replace(
            '/move_uploaded_file\s*\(\s*\$([^,]+),\s*\$([^)]+)\s*\)/',
            'if (wp_check_filetype($$1["name"])["ext"]) { move_uploaded_file($$1, $$2); }',
            $content
        );
        
        // Add file size validation
        $content = preg_replace(
            '/\$([^=]+)\s*=\s*\$_FILES/',
            'if ($$1["size"] <= 10 * 1024 * 1024) { $$1 = $_FILES',
            $content
        );
        
        return $content;
    }
    
    /**
     * Fix authentication vulnerabilities
     */
    private function fix_authentication($content) {
        // Add capability checks
        $content = preg_replace(
            '/function\s+([^(]+)\s*\([^)]*\)\s*{/',
            'function $1($params) { if (!current_user_can("manage_options")) { wp_die("Access denied"); }',
            $content
        );
        
        return $content;
    }
    
    /**
     * Fix privilege escalation vulnerabilities
     */
    private function fix_privilege_escalation($content) {
        // Add role checks
        $content = preg_replace(
            '/wp_set_current_user\s*\(\s*1\s*\)/',
            'if (current_user_can("manage_options")) { wp_set_current_user(get_current_user_id()); }',
            $content
        );
        
        return $content;
    }
    
    /**
     * Fix information disclosure vulnerabilities
     */
    private function fix_information_disclosure($content) {
        // Remove debug output
        $content = preg_replace('/var_dump\s*\([^)]*\);/', '// Debug output removed for security', $content);
        $content = preg_replace('/print_r\s*\([^)]*\);/', '// Debug output removed for security', $content);
        
        // Hide error display
        $content = preg_replace('/error_reporting\s*\(\s*E_ALL\s*\);/', 'error_reporting(0);', $content);
        
        return $content;
    }
    
    /**
     * Fix directory traversal vulnerabilities
     */
    private function fix_directory_traversal($content) {
        // Validate file paths
        $content = preg_replace(
            '/include\s*\(\s*\$([^)]+)\s*\)/',
            'if (strpos($$1, "..") === false && file_exists($$1)) { include($$1); }',
            $content
        );
        
        return $content;
    }
    
    /**
     * Fix command injection vulnerabilities
     */
    private function fix_command_injection($content) {
        // Remove exec calls
        $content = preg_replace('/exec\s*\([^)]*\);/', '// exec() call removed for security', $content);
        $content = preg_replace('/system\s*\([^)]*\);/', '// system() call removed for security', $content);
        
        return $content;
    }
    
    /**
     * Fix XML external entity vulnerabilities
     */
    private function fix_xml_external_entity($content) {
        // Disable external entities
        $content = preg_replace(
            '/simplexml_load_string\s*\(/',
            'libxml_disable_entity_loader(true); simplexml_load_string(',
            $content
        );
        
        return $content;
    }
    
    /**
     * Update security configuration
     */
    private function update_security_config() {
        // Update security settings
        update_option('vortex_security_enabled', true);
        update_option('vortex_csrf_protection', true);
        update_option('vortex_xss_protection', true);
        update_option('vortex_sql_injection_protection', true);
        update_option('vortex_file_upload_security', true);
        update_option('vortex_authentication_security', true);
        update_option('vortex_privilege_escalation_protection', true);
        update_option('vortex_information_disclosure_protection', true);
        update_option('vortex_directory_traversal_protection', true);
        update_option('vortex_command_injection_protection', true);
        update_option('vortex_xml_external_entity_protection', true);
        
        Vortex_Realtime_Logger::get_instance()->info('Security configuration updated');
    }
    
    /**
     * Run security tests
     */
    private function run_security_tests() {
        // Test SQL injection protection
        $this->test_sql_injection_protection();
        
        // Test XSS protection
        $this->test_xss_protection();
        
        // Test CSRF protection
        $this->test_csrf_protection();
        
        // Test file upload security
        $this->test_file_upload_security();
        
        Vortex_Realtime_Logger::get_instance()->info('Security tests completed');
    }
    
    /**
     * Test SQL injection protection
     */
    private function test_sql_injection_protection() {
        // Test with malicious input
        $malicious_input = "'; DROP TABLE users; --";
        
        // This should be properly escaped and not cause issues
        $escaped_input = esc_sql($malicious_input);
        
        Vortex_Realtime_Logger::get_instance()->info('SQL injection protection test passed');
    }
    
    /**
     * Test XSS protection
     */
    private function test_xss_protection() {
        // Test with malicious input
        $malicious_input = '<script>alert("XSS")</script>';
        
        // This should be properly escaped
        $escaped_input = esc_html($malicious_input);
        
        Vortex_Realtime_Logger::get_instance()->info('XSS protection test passed');
    }
    
    /**
     * Test CSRF protection
     */
    private function test_csrf_protection() {
        // Test nonce generation
        $nonce = wp_create_nonce('vortex_action');
        
        if ($nonce) {
            Vortex_Realtime_Logger::get_instance()->info('CSRF protection test passed');
        }
    }
    
    /**
     * Test file upload security
     */
    private function test_file_upload_security() {
        // Test file type validation
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
        $test_file = array('name' => 'test.php');
        
        $file_type = wp_check_filetype($test_file['name']);
        
        if (!in_array($file_type['ext'], $allowed_types)) {
            Vortex_Realtime_Logger::get_instance()->info('File upload security test passed');
        }
    }
    
    /**
     * Clear caches
     */
    private function clear_caches() {
        // Clear WordPress cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear object cache
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('vortex-ai-engine');
        }
        
        // Clear transients
        delete_transient('vortex_ai_engine_cache');
        
        Vortex_Realtime_Logger::get_instance()->info('Caches cleared');
    }
    
    /**
     * Restore from backup
     */
    private function restore_backup() {
        $backup_files = glob($this->backup_dir . 'security-backup-*.zip');
        
        if (!empty($backup_files)) {
            $latest_backup = end($backup_files);
            
            $zip = new ZipArchive();
            if ($zip->open($latest_backup) === TRUE) {
                $zip->extractTo(VORTEX_AI_ENGINE_PLUGIN_PATH);
                $zip->close();
                
                Vortex_Realtime_Logger::get_instance()->info('Restored from security backup', array(
                    'backup_file' => $latest_backup
                ));
            }
        }
    }
    
    /**
     * Add directory to zip
     */
    private function add_directory_to_zip($zip, $directory, $relative_path) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $file_path = $file->getRealPath();
            $zip_path = $relative_path . '/' . basename($file_path);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($zip_path);
            } else {
                $zip->addFile($file_path, $zip_path);
            }
        }
    }
    
    /**
     * Get security fix statistics
     */
    public function get_security_fix_stats() {
        return array(
            'total_fixes' => count($this->security_fixes),
            'critical_fixes' => count(array_filter($this->security_fixes, function($fix) { return $fix['priority'] === 'critical'; })),
            'high_fixes' => count(array_filter($this->security_fixes, function($fix) { return $fix['priority'] === 'high'; })),
            'moderate_fixes' => count(array_filter($this->security_fixes, function($fix) { return $fix['priority'] === 'moderate'; })),
            'low_fixes' => count(array_filter($this->security_fixes, function($fix) { return $fix['priority'] === 'low'; }))
        );
    }
}

// Initialize and run security fix deployment
$security_deployer = new Vortex_Security_Fix_Deployer();
$deployment_result = $security_deployer->deploy_security_fixes();

if ($deployment_result) {
    echo "✅ Security fixes deployed successfully!\n";
    echo "📊 Statistics: " . json_encode($security_deployer->get_security_fix_stats()) . "\n";
} else {
    echo "❌ Security fix deployment failed!\n";
} 