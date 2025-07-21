<?php
/**
 * WordPress Configuration Audit Script
 * 
 * Comprehensive audit of WordPress configuration before VORTEX AI Engine activation
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, simulate basic environment
    define('ABSPATH', dirname(__FILE__) . '/../../');
    if (file_exists(ABSPATH . 'wp-config.php')) {
        require_once ABSPATH . 'wp-config.php';
    } else {
        echo "❌ WordPress configuration not found. Please run this from within WordPress.\n";
        exit;
    }
}

/**
 * WordPress Configuration Audit Class
 */
class WordPress_Config_Audit {
    
    private $results = array();
    private $errors = array();
    private $warnings = array();
    private $recommendations = array();
    
    public function run_audit() {
        echo "🔍 WordPress Configuration Audit\n";
        echo "================================\n\n";
        
        // Test WordPress environment
        $this->test_wordpress_environment();
        
        // Test database configuration
        $this->test_database_configuration();
        
        // Test file permissions
        $this->test_file_permissions();
        
        // Test PHP configuration
        $this->test_php_configuration();
        
        // Test WordPress settings
        $this->test_wordpress_settings();
        
        // Test security configuration
        $this->test_security_configuration();
        
        // Test performance settings
        $this->test_performance_settings();
        
        // Generate audit report
        $this->generate_audit_report();
    }
    
    /**
     * Test WordPress environment
     */
    private function test_wordpress_environment() {
        echo "📋 Testing WordPress Environment...\n";
        
        // Check if WordPress is loaded
        if (function_exists('get_bloginfo')) {
            $this->add_success('WordPress core loaded successfully');
        } else {
            $this->add_error('WordPress core not loaded');
            return;
        }
        
        // Check WordPress version
        $wp_version = get_bloginfo('version');
        $this->add_success("WordPress version: $wp_version");
        
        // Check if we're in admin
        if (is_admin()) {
            $this->add_success('Running in admin context');
        } else {
            $this->add_warning('Not running in admin context');
        }
        
        // Check ABSPATH
        if (defined('ABSPATH')) {
            $this->add_success('ABSPATH defined: ' . ABSPATH);
        } else {
            $this->add_error('ABSPATH not defined');
        }
        
        echo "✅ WordPress Environment Test Complete\n\n";
    }
    
    /**
     * Test database configuration
     */
    private function test_database_configuration() {
        echo "🗄️ Testing Database Configuration...\n";
        
        global $wpdb;
        
        if (!$wpdb) {
            $this->add_error('WordPress database object not available');
            return;
        }
        
        // Test database connection
        $test_query = $wpdb->get_var("SELECT 1");
        if ($test_query) {
            $this->add_success('Database connection successful');
        } else {
            $this->add_error('Database connection failed: ' . $wpdb->last_error);
            return;
        }
        
        // Check database constants
        $db_constants = array(
            'DB_NAME' => 'Database name',
            'DB_USER' => 'Database user',
            'DB_HOST' => 'Database host',
            'DB_CHARSET' => 'Database charset',
            'DB_COLLATE' => 'Database collation'
        );
        
        foreach ($db_constants as $constant => $description) {
            if (defined($constant)) {
                $value = constant($constant);
                if (!empty($value)) {
                    $this->add_success("$description: $value");
                } else {
                    $this->add_warning("$description: Empty value");
                }
            } else {
                $this->add_error("$description: Not defined");
            }
        }
        
        // Check table prefix
        if (isset($wpdb->prefix)) {
            $this->add_success('Table prefix: ' . $wpdb->prefix);
        } else {
            $this->add_error('Table prefix not set');
        }
        
        // Test database permissions
        $test_table = $wpdb->prefix . 'vortex_test_table';
        $create_result = $wpdb->query("CREATE TABLE IF NOT EXISTS $test_table (id INT PRIMARY KEY)");
        
        if ($create_result !== false) {
            $this->add_success('Database write permissions: OK');
            // Clean up test table
            $wpdb->query("DROP TABLE IF EXISTS $test_table");
        } else {
            $this->add_error('Database write permissions: Failed - ' . $wpdb->last_error);
        }
        
        echo "✅ Database Configuration Test Complete\n\n";
    }
    
    /**
     * Test file permissions
     */
    private function test_file_permissions() {
        echo "📁 Testing File Permissions...\n";
        
        $paths_to_check = array(
            ABSPATH => 'WordPress root directory',
            ABSPATH . 'wp-content' => 'wp-content directory',
            ABSPATH . 'wp-content/plugins' => 'plugins directory',
            ABSPATH . 'wp-content/uploads' => 'uploads directory',
            ABSPATH . 'wp-config.php' => 'wp-config.php file'
        );
        
        foreach ($paths_to_check as $path => $description) {
            if (file_exists($path)) {
                $permissions = substr(sprintf('%o', fileperms($path)), -4);
                
                if (is_dir($path)) {
                    // Directory permissions
                    if ($permissions === '0755' || $permissions === '0750') {
                        $this->add_success("$description permissions: $permissions (Good)");
                    } elseif ($permissions === '0777') {
                        $this->add_warning("$description permissions: $permissions (Too permissive)");
                    } else {
                        $this->add_warning("$description permissions: $permissions (Check required)");
                    }
                } else {
                    // File permissions
                    if ($permissions === '0644' || $permissions === '0640') {
                        $this->add_success("$description permissions: $permissions (Good)");
                    } elseif ($permissions === '0666') {
                        $this->add_warning("$description permissions: $permissions (Too permissive)");
                    } else {
                        $this->add_warning("$description permissions: $permissions (Check required)");
                    }
                }
            } else {
                $this->add_error("$description: Not found");
            }
        }
        
        // Check if wp-content is writable
        if (wp_is_writable(ABSPATH . 'wp-content')) {
            $this->add_success('wp-content directory is writable');
        } else {
            $this->add_error('wp-content directory is not writable');
        }
        
        // Check if plugins directory is writable
        if (wp_is_writable(ABSPATH . 'wp-content/plugins')) {
            $this->add_success('plugins directory is writable');
        } else {
            $this->add_error('plugins directory is not writable');
        }
        
        echo "✅ File Permissions Test Complete\n\n";
    }
    
    /**
     * Test PHP configuration
     */
    private function test_php_configuration() {
        echo "🐘 Testing PHP Configuration...\n";
        
        // Check PHP version
        $php_version = PHP_VERSION;
        $this->add_success("PHP version: $php_version");
        
        if (version_compare($php_version, '7.4', '<')) {
            $this->add_error('PHP version below 7.4 - VORTEX AI Engine requires PHP 7.4+');
        } elseif (version_compare($php_version, '8.0', '<')) {
            $this->add_warning('PHP version below 8.0 - Consider upgrading for better performance');
        }
        
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->parse_memory_limit($memory_limit);
        $this->add_success("Memory limit: $memory_limit");
        
        if ($memory_bytes < 256 * 1024 * 1024) { // 256MB
            $this->add_warning('Memory limit below 256MB - VORTEX AI Engine recommends 256MB+');
            $this->add_recommendation('Increase memory_limit in php.ini to 256M or higher');
        }
        
        // Check required extensions
        $required_extensions = array(
            'curl' => 'cURL (for API calls)',
            'json' => 'JSON (for data processing)',
            'openssl' => 'OpenSSL (for encryption)',
            'mbstring' => 'mbstring (for text processing)',
            'zip' => 'ZIP (for file operations)',
            'gd' => 'GD (for image processing)'
        );
        
        foreach ($required_extensions as $extension => $description) {
            if (extension_loaded($extension)) {
                $this->add_success("$description: Loaded");
            } else {
                $this->add_error("$description: Not loaded");
            }
        }
        
        // Check optional extensions
        $optional_extensions = array(
            'redis' => 'Redis (for caching)',
            'memcached' => 'Memcached (for caching)',
            'imagick' => 'ImageMagick (for advanced image processing)'
        );
        
        foreach ($optional_extensions as $extension => $description) {
            if (extension_loaded($extension)) {
                $this->add_success("$description: Available");
            } else {
                $this->add_warning("$description: Not available (optional)");
            }
        }
        
        // Check execution time
        $max_execution_time = ini_get('max_execution_time');
        $this->add_success("Max execution time: $max_execution_time seconds");
        
        if ($max_execution_time > 0 && $max_execution_time < 300) {
            $this->add_warning('Max execution time below 300 seconds - AI processing may timeout');
        }
        
        // Check upload limits
        $upload_max_filesize = ini_get('upload_max_filesize');
        $post_max_size = ini_get('post_max_size');
        $this->add_success("Upload max filesize: $upload_max_filesize");
        $this->add_success("Post max size: $post_max_size");
        
        echo "✅ PHP Configuration Test Complete\n\n";
    }
    
    /**
     * Test WordPress settings
     */
    private function test_wordpress_settings() {
        echo "⚙️ Testing WordPress Settings...\n";
        
        // Check debug settings
        if (defined('WP_DEBUG')) {
            if (WP_DEBUG) {
                $this->add_warning('WP_DEBUG is enabled (development mode)');
            } else {
                $this->add_success('WP_DEBUG is disabled (production mode)');
            }
        } else {
            $this->add_warning('WP_DEBUG not defined');
        }
        
        if (defined('WP_DEBUG_LOG')) {
            if (WP_DEBUG_LOG) {
                $this->add_success('WP_DEBUG_LOG is enabled');
            } else {
                $this->add_warning('WP_DEBUG_LOG is disabled');
            }
        } else {
            $this->add_warning('WP_DEBUG_LOG not defined');
        }
        
        if (defined('WP_DEBUG_DISPLAY')) {
            if (WP_DEBUG_DISPLAY) {
                $this->add_warning('WP_DEBUG_DISPLAY is enabled (shows errors)');
            } else {
                $this->add_success('WP_DEBUG_DISPLAY is disabled (hides errors)');
            }
        } else {
            $this->add_warning('WP_DEBUG_DISPLAY not defined');
        }
        
        // Check file system method
        if (defined('FS_METHOD')) {
            $this->add_success('FS_METHOD: ' . FS_METHOD);
        } else {
            $this->add_warning('FS_METHOD not defined');
        }
        
        // Check cron settings
        if (defined('DISABLE_WP_CRON')) {
            if (DISABLE_WP_CRON) {
                $this->add_warning('WP_CRON is disabled - VORTEX AI Engine may not function properly');
            } else {
                $this->add_success('WP_CRON is enabled');
            }
        } else {
            $this->add_success('WP_CRON is enabled (default)');
        }
        
        // Check cache settings
        if (defined('WP_CACHE')) {
            if (WP_CACHE) {
                $this->add_success('WP_CACHE is enabled');
            } else {
                $this->add_warning('WP_CACHE is disabled');
            }
        } else {
            $this->add_warning('WP_CACHE not defined');
        }
        
        // Check site URL
        $site_url = get_option('siteurl');
        $home_url = get_option('home');
        $this->add_success("Site URL: $site_url");
        $this->add_success("Home URL: $home_url");
        
        if ($site_url !== $home_url) {
            $this->add_warning('Site URL and Home URL are different');
        }
        
        echo "✅ WordPress Settings Test Complete\n\n";
    }
    
    /**
     * Test security configuration
     */
    private function test_security_configuration() {
        echo "🔒 Testing Security Configuration...\n";
        
        // Check authentication keys
        $auth_keys = array(
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT'
        );
        
        foreach ($auth_keys as $key) {
            if (defined($key)) {
                $value = constant($key);
                if ($value && $value !== 'put your unique phrase here') {
                    $this->add_success("$key: Set");
                } else {
                    $this->add_error("$key: Not properly configured");
                }
            } else {
                $this->add_error("$key: Not defined");
            }
        }
        
        // Check SSL
        if (is_ssl()) {
            $this->add_success('SSL is enabled');
        } else {
            $this->add_warning('SSL is not enabled - recommended for production');
        }
        
        // Check file editing
        if (defined('DISALLOW_FILE_EDIT')) {
            if (DISALLOW_FILE_EDIT) {
                $this->add_success('File editing is disabled (secure)');
            } else {
                $this->add_warning('File editing is enabled (insecure)');
            }
        } else {
            $this->add_warning('DISALLOW_FILE_EDIT not defined');
        }
        
        // Check file modifications
        if (defined('DISALLOW_FILE_MODS')) {
            if (DISALLOW_FILE_MODS) {
                $this->add_success('File modifications are disabled (secure)');
            } else {
                $this->add_warning('File modifications are enabled (insecure)');
            }
        } else {
            $this->add_warning('DISALLOW_FILE_MODS not defined');
        }
        
        // Check automatic updates
        if (defined('AUTOMATIC_UPDATER_DISABLED')) {
            if (AUTOMATIC_UPDATER_DISABLED) {
                $this->add_warning('Automatic updates are disabled');
            } else {
                $this->add_success('Automatic updates are enabled');
            }
        } else {
            $this->add_success('Automatic updates are enabled (default)');
        }
        
        echo "✅ Security Configuration Test Complete\n\n";
    }
    
    /**
     * Test performance settings
     */
    private function test_performance_settings() {
        echo "⚡ Testing Performance Settings...\n";
        
        // Check object cache
        if (defined('WP_CACHE') && WP_CACHE) {
            $this->add_success('Object caching is enabled');
        } else {
            $this->add_warning('Object caching is disabled - consider enabling for better performance');
        }
        
        // Check Redis
        if (defined('WP_REDIS_HOST')) {
            $this->add_success('Redis is configured');
        } else {
            $this->add_warning('Redis not configured (optional for performance)');
        }
        
        // Check database optimization
        global $wpdb;
        $query_count = $wpdb->num_queries;
        $this->add_success("Database queries so far: $query_count");
        
        // Check memory usage
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        $this->add_success("Current memory usage: " . $this->format_bytes($memory_usage));
        $this->add_success("Peak memory usage: " . $this->format_bytes($memory_peak));
        
        // Check load time
        $load_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $this->add_success("Page load time: " . round($load_time, 3) . " seconds");
        
        if ($load_time > 2.0) {
            $this->add_warning('Page load time is slow - consider optimization');
        }
        
        echo "✅ Performance Settings Test Complete\n\n";
    }
    
    /**
     * Add success message
     */
    private function add_success($message) {
        $this->results[] = array('type' => 'success', 'message' => $message);
    }
    
    /**
     * Add warning message
     */
    private function add_warning($message) {
        $this->warnings[] = $message;
        $this->results[] = array('type' => 'warning', 'message' => $message);
    }
    
    /**
     * Add error message
     */
    private function add_error($message) {
        $this->errors[] = $message;
        $this->results[] = array('type' => 'error', 'message' => $message);
    }
    
    /**
     * Add recommendation
     */
    private function add_recommendation($message) {
        $this->recommendations[] = $message;
    }
    
    /**
     * Generate audit report
     */
    private function generate_audit_report() {
        echo "📊 WordPress Configuration Audit Report\n";
        echo "=======================================\n\n";
        
        $total_tests = count($this->results);
        $success_count = count(array_filter($this->results, function($r) { return $r['type'] === 'success'; }));
        $warning_count = count($this->warnings);
        $error_count = count($this->errors);
        
        echo "📈 Summary:\n";
        echo "  Total Tests: $total_tests\n";
        echo "  Passed: $success_count\n";
        echo "  Warnings: $warning_count\n";
        echo "  Errors: $error_count\n\n";
        
        if (!empty($this->errors)) {
            echo "❌ Critical Issues:\n";
            foreach ($this->errors as $error) {
                echo "  ✗ $error\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️ Warnings:\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ $warning\n";
            }
            echo "\n";
        }
        
        if (!empty($this->recommendations)) {
            echo "💡 Recommendations:\n";
            foreach ($this->recommendations as $recommendation) {
                echo "  💡 $recommendation\n";
            }
            echo "\n";
        }
        
        // Overall assessment
        if ($error_count === 0) {
            if ($warning_count === 0) {
                echo "🎉 WordPress configuration is optimal for VORTEX AI Engine!\n";
            } else {
                echo "✅ WordPress configuration is acceptable for VORTEX AI Engine.\n";
                echo "   Consider addressing the warnings for better performance.\n";
            }
        } else {
            echo "🔧 WordPress configuration needs attention before VORTEX AI Engine activation.\n";
            echo "   Please fix the critical issues above.\n";
        }
        
        // Save report to file
        $report_file = ABSPATH . 'wp-content/uploads/wordpress-config-audit-' . date('Y-m-d-H-i-s') . '.txt';
        $report_content = ob_get_contents();
        file_put_contents($report_file, $report_content);
        
        echo "\n📄 Audit report saved to: $report_file\n";
    }
    
    /**
     * Parse memory limit
     */
    private function parse_memory_limit($memory_limit) {
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) substr($memory_limit, 0, -1);
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
    
    /**
     * Format bytes
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Run audit if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $audit = new WordPress_Config_Audit();
    $audit->run_audit();
} 