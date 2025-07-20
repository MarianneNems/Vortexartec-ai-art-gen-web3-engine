<?php
/**
 * VORTEX AI Engine - Plugin Health Check
 * Comprehensive diagnostic tool for the VORTEX AI Engine plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

class VortexHealthCheck {
    
    private $plugin_dir;
    private $issues = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct() {
        $this->plugin_dir = dirname(__FILE__);
    }
    
    public function run_full_check() {
        echo "<h1>🔍 VORTEX AI Engine - Health Check Report</h1>\n";
        echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto;'>\n";
        
        $this->check_environment();
        $this->check_database();
        $this->check_plugin_files();
        $this->check_dependencies();
        $this->check_permissions();
        $this->check_configuration();
        $this->check_integrations();
        
        $this->generate_report();
        
        echo "</div>\n";
    }
    
    private function check_environment() {
        echo "<h2>🌍 Environment Check</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        // PHP Version
        $php_version = PHP_VERSION;
        if (version_compare($php_version, '7.4', '>=')) {
            $this->success[] = "PHP Version: $php_version (✅ Compatible)";
        } else {
            $this->issues[] = "PHP Version: $php_version (❌ Requires 7.4+)";
        }
        
        // Required Extensions
        $required_extensions = [
            'mysqli' => 'Database connectivity',
            'json' => 'JSON processing',
            'curl' => 'API communications',
            'openssl' => 'Security features',
            'mbstring' => 'String handling',
            'xml' => 'XML processing'
        ];
        
        foreach ($required_extensions as $ext => $purpose) {
            if (extension_loaded($ext)) {
                $this->success[] = "Extension $ext: ✅ Available ($purpose)";
            } else {
                $this->issues[] = "Extension $ext: ❌ Missing ($purpose)";
            }
        }
        
        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->return_bytes($memory_limit);
        if ($memory_bytes >= 256 * 1024 * 1024) { // 256MB
            $this->success[] = "Memory Limit: $memory_limit (✅ Sufficient)";
        } else {
            $this->warnings[] = "Memory Limit: $memory_limit (⚠️ Consider increasing to 256M+)";
        }
        
        // Max Execution Time
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time >= 300 || $max_execution_time == 0) {
            $this->success[] = "Max Execution Time: $max_execution_time seconds (✅ Sufficient)";
        } else {
            $this->warnings[] = "Max Execution Time: $max_execution_time seconds (⚠️ Consider increasing to 300+)";
        }
        
        $this->display_results();
        echo "</div>\n";
    }
    
    private function check_database() {
        echo "<h2>🗄️ Database Check</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        // Try to load WordPress
        $wp_load_path = $this->plugin_dir . '/wp-load.php';
        if (file_exists($wp_load_path)) {
            require_once($wp_load_path);
            
            global $wpdb;
            if (isset($wpdb) && $wpdb instanceof wpdb) {
                // Test connection
                $wpdb->suppress_errors();
                $result = $wpdb->get_var("SELECT 1");
                $wpdb->suppress_errors(false);
                
                if ($result === '1') {
                    $this->success[] = "WordPress Database: ✅ Connected";
                    
                    // Check VORTEX tables
                    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}vortex_%'");
                    if (!empty($tables)) {
                        $this->success[] = "VORTEX Tables: ✅ Found " . count($tables) . " tables";
                        foreach ($tables as $table) {
                            $table_name = array_values((array)$table)[0];
                            $this->success[] = "  - $table_name";
                        }
                    } else {
                        $this->warnings[] = "VORTEX Tables: ⚠️ No tables found (normal for first install)";
                    }
                    
                    // Check database version
                    $db_version = $wpdb->get_var("SELECT VERSION()");
                    $this->success[] = "Database Version: $db_version";
                    
                } else {
                    $this->issues[] = "WordPress Database: ❌ Connection failed";
                }
            } else {
                $this->issues[] = "WordPress Database: ❌ wpdb object not available";
            }
        } else {
            $this->issues[] = "WordPress: ❌ wp-load.php not found";
        }
        
        $this->display_results();
        echo "</div>\n";
    }
    
    private function check_plugin_files() {
        echo "<h2>📁 Plugin Files Check</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        $required_files = [
            'vortex-ai-engine.php' => 'Main plugin file',
            'includes/class-vortex-db-setup.php' => 'Database setup',
            'includes/class-vortex-logger.php' => 'Logging system',
            'admin/class-vortex-admin.php' => 'Admin interface',
            'vault-secrets/algorithms/' => 'Algorithm directory',
            'vendor/autoload.php' => 'Composer autoloader'
        ];
        
        foreach ($required_files as $file => $description) {
            $file_path = $this->plugin_dir . '/' . $file;
            if (file_exists($file_path)) {
                if (is_readable($file_path)) {
                    $this->success[] = "$description: ✅ Found and readable";
                } else {
                    $this->issues[] = "$description: ❌ Found but not readable";
                }
            } else {
                $this->issues[] = "$description: ❌ Missing";
            }
        }
        
        // Check algorithm files
        $algorithm_dir = $this->plugin_dir . '/vault-secrets/algorithms/';
        if (is_dir($algorithm_dir)) {
            $algorithm_files = glob($algorithm_dir . '*.php');
            if (!empty($algorithm_files)) {
                $this->success[] = "Algorithm Files: ✅ Found " . count($algorithm_files) . " files";
            } else {
                $this->warnings[] = "Algorithm Files: ⚠️ Directory exists but no PHP files found";
            }
        } else {
            $this->issues[] = "Algorithm Files: ❌ Directory missing";
        }
        
        $this->display_results();
        echo "</div>\n";
    }
    
    private function check_dependencies() {
        echo "<h2>📦 Dependencies Check</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        // Check Composer
        $composer_json = $this->plugin_dir . '/composer.json';
        if (file_exists($composer_json)) {
            $this->success[] = "Composer: ✅ composer.json found";
            
            $composer_lock = $this->plugin_dir . '/composer.lock';
            if (file_exists($composer_lock)) {
                $this->success[] = "Composer: ✅ composer.lock found (dependencies locked)";
            } else {
                $this->warnings[] = "Composer: ⚠️ composer.lock missing (run composer install)";
            }
        } else {
            $this->issues[] = "Composer: ❌ composer.json missing";
        }
        
        // Check vendor directory
        $vendor_dir = $this->plugin_dir . '/vendor/';
        if (is_dir($vendor_dir)) {
            $this->success[] = "Vendor: ✅ vendor directory exists";
            
            // Check key dependencies
            $key_deps = [
                'aws/aws-sdk-php' => 'AWS SDK',
                'guzzlehttp/guzzle' => 'HTTP client',
                'psr/http-client' => 'PSR HTTP client'
            ];
            
            foreach ($key_deps as $dep => $name) {
                $dep_path = $vendor_dir . $dep;
                if (is_dir($dep_path)) {
                    $this->success[] = "  - $name: ✅ Available";
                } else {
                    $this->issues[] = "  - $name: ❌ Missing";
                }
            }
        } else {
            $this->issues[] = "Vendor: ❌ vendor directory missing (run composer install)";
        }
        
        $this->display_results();
        echo "</div>\n";
    }
    
    private function check_permissions() {
        echo "<h2>🔐 Permissions Check</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        $directories_to_check = [
            'vault-secrets/' => 'Secrets storage',
            'logs/' => 'Log files',
            'uploads/' => 'File uploads',
            'cache/' => 'Cache directory'
        ];
        
        foreach ($directories_to_check as $dir => $purpose) {
            $dir_path = $this->plugin_dir . '/' . $dir;
            if (is_dir($dir_path)) {
                if (is_writable($dir_path)) {
                    $this->success[] = "$purpose: ✅ Writable";
                } else {
                    $this->issues[] = "$purpose: ❌ Directory exists but not writable";
                }
            } else {
                $this->warnings[] = "$purpose: ⚠️ Directory doesn't exist (will be created if needed)";
            }
        }
        
        // Check main plugin file permissions
        $main_file = $this->plugin_dir . '/vortex-ai-engine.php';
        if (file_exists($main_file)) {
            if (is_readable($main_file)) {
                $this->success[] = "Main Plugin File: ✅ Readable";
            } else {
                $this->issues[] = "Main Plugin File: ❌ Not readable";
            }
        }
        
        $this->display_results();
        echo "</div>\n";
    }
    
    private function check_configuration() {
        echo "<h2>⚙️ Configuration Check</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        // Check if WordPress is loaded
        if (defined('ABSPATH') && function_exists('get_option')) {
            $this->success[] = "WordPress: ✅ Loaded";
            
            // Check VORTEX options
            $vortex_version = get_option('vortex_ai_engine_version');
            if ($vortex_version) {
                $this->success[] = "Plugin Version: $vortex_version";
            } else {
                $this->warnings[] = "Plugin Version: ⚠️ Not set (plugin may not be activated)";
            }
            
            // Check for required WordPress constants
            $required_constants = ['WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY'];
            foreach ($required_constants as $constant) {
                if (defined($constant)) {
                    $value = constant($constant) ? 'Enabled' : 'Disabled';
                    $this->success[] = "$constant: $value";
                } else {
                    $this->warnings[] = "$constant: ⚠️ Not defined";
                }
            }
            
        } else {
            $this->issues[] = "WordPress: ❌ Not loaded";
        }
        
        $this->display_results();
        echo "</div>\n";
    }
    
    private function check_integrations() {
        echo "<h2>🔗 Integrations Check</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        // Check AWS SDK
        if (class_exists('Aws\S3\S3Client')) {
            $this->success[] = "AWS SDK: ✅ Available";
        } else {
            $this->warnings[] = "AWS SDK: ⚠️ Not loaded (S3 features may not work)";
        }
        
        // Check for VORTEX classes
        $vortex_classes = [
            'VortexAIEngine_Logger' => 'Logging system',
            'VortexAIEngine_DBSetup' => 'Database setup',
            'VortexAIEngine_Admin' => 'Admin interface'
        ];
        
        foreach ($vortex_classes as $class => $purpose) {
            if (class_exists($class)) {
                $this->success[] = "$purpose: ✅ Class loaded";
            } else {
                $this->warnings[] = "$purpose: ⚠️ Class not loaded";
            }
        }
        
        $this->display_results();
        echo "</div>\n";
    }
    
    private function display_results() {
        if (!empty($this->success)) {
            echo "<h4 style='color: #28a745;'>✅ Success:</h4>\n";
            echo "<ul style='color: #28a745;'>\n";
            foreach ($this->success as $item) {
                echo "<li>$item</li>\n";
            }
            echo "</ul>\n";
        }
        
        if (!empty($this->warnings)) {
            echo "<h4 style='color: #ffc107;'>⚠️ Warnings:</h4>\n";
            echo "<ul style='color: #ffc107;'>\n";
            foreach ($this->warnings as $item) {
                echo "<li>$item</li>\n";
            }
            echo "</ul>\n";
        }
        
        if (!empty($this->issues)) {
            echo "<h4 style='color: #dc3545;'>❌ Issues:</h4>\n";
            echo "<ul style='color: #dc3545;'>\n";
            foreach ($this->issues as $item) {
                echo "<li>$item</li>\n";
            }
            echo "</ul>\n";
        }
        
        // Clear arrays for next section
        $this->success = [];
        $this->warnings = [];
        $this->issues = [];
    }
    
    private function generate_report() {
        echo "<h2>📊 Summary Report</h2>\n";
        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        $total_issues = count($this->issues);
        $total_warnings = count($this->warnings);
        
        if ($total_issues == 0 && $total_warnings == 0) {
            echo "<h3 style='color: #28a745;'>🎉 All Systems Operational!</h3>\n";
            echo "<p>Your VORTEX AI Engine plugin is ready to use.</p>\n";
        } elseif ($total_issues == 0) {
            echo "<h3 style='color: #ffc107;'>⚠️ Minor Issues Detected</h3>\n";
            echo "<p>Your plugin should work, but consider addressing the warnings for optimal performance.</p>\n";
        } else {
            echo "<h3 style='color: #dc3545;'>❌ Critical Issues Found</h3>\n";
            echo "<p>Please address the issues before using the plugin.</p>\n";
        }
        
        echo "<p><strong>Next Steps:</strong></p>\n";
        echo "<ol>\n";
        echo "<li>Run the database connection test: <code>test-database-connection.php</code></li>\n";
        echo "<li>Check your WordPress configuration</li>\n";
        echo "<li>Verify file permissions</li>\n";
        echo "<li>Run <code>composer install</code> if dependencies are missing</li>\n";
        echo "</ol>\n";
        
        echo "</div>\n";
    }
    
    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
}

// Run the health check
if (php_sapi_name() === 'cli') {
    echo "VORTEX AI Engine - Health Check\n";
    echo "==============================\n\n";
    
    $checker = new VortexHealthCheck();
    $checker->run_full_check();
} else {
    echo "<!DOCTYPE html>\n";
    echo "<html><head><title>VORTEX AI Engine - Health Check</title>";
    echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style></head><body>\n";
    
    $checker = new VortexHealthCheck();
    $checker->run_full_check();
    
    echo "</body></html>\n";
}
?> 