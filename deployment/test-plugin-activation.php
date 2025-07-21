<?php
/**
 * VORTEX AI Engine - Plugin Activation Test
 * 
 * Simple test to check if the plugin can activate after configuration fixes
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
        echo "❌ WordPress configuration not found.\n";
        exit;
    }
}

/**
 * Plugin Activation Test Class
 */
class VORTEX_Plugin_Activation_Test {
    
    private $results = array();
    private $errors = array();
    
    public function run_test() {
        echo "🧪 VORTEX AI Engine - Plugin Activation Test\n";
        echo "===========================================\n\n";
        
        // Test WordPress environment
        $this->test_wordpress_environment();
        
        // Test plugin files
        $this->test_plugin_files();
        
        // Test required dependencies
        $this->test_required_dependencies();
        
        // Test database connectivity
        $this->test_database_connectivity();
        
        // Test plugin loading
        $this->test_plugin_loading();
        
        // Generate test report
        $this->generate_test_report();
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
        
        // Check PHP version
        $php_version = PHP_VERSION;
        $this->add_success("PHP version: $php_version");
        
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        $this->add_success("Memory limit: $memory_limit");
        
        // Check if we're in admin
        if (is_admin()) {
            $this->add_success('Running in admin context');
        } else {
            $this->add_warning('Not running in admin context');
        }
        
        echo "✅ WordPress Environment Test Complete\n\n";
    }
    
    /**
     * Test plugin files
     */
    private function test_plugin_files() {
        echo "📁 Testing Plugin Files...\n";
        
        $plugin_path = ABSPATH . 'vortex-ai-engine/';
        
        // Check main plugin file
        $main_file = $plugin_path . 'vortex-ai-engine.php';
        if (file_exists($main_file)) {
            $this->add_success('Main plugin file exists');
        } else {
            $this->add_error('Main plugin file missing: ' . $main_file);
        }
        
        // Check includes directory
        $includes_dir = $plugin_path . 'includes/';
        if (is_dir($includes_dir)) {
            $this->add_success('Includes directory exists');
        } else {
            $this->add_error('Includes directory missing: ' . $includes_dir);
        }
        
        // Check AI agents
        $ai_agents_dir = $includes_dir . 'ai-agents/';
        if (is_dir($ai_agents_dir)) {
            $this->add_success('AI agents directory exists');
            
            // Check individual agent files
            $agents = array(
                'class-vortex-archer-orchestrator.php',
                'class-vortex-huraii-agent.php',
                'class-vortex-cloe-agent.php',
                'class-vortex-horace-agent.php',
                'class-vortex-thorius-agent.php'
            );
            
            foreach ($agents as $agent) {
                $agent_file = $ai_agents_dir . $agent;
                if (file_exists($agent_file)) {
                    $this->add_success("AI agent exists: $agent");
                } else {
                    $this->add_error("AI agent missing: $agent");
                }
            }
        } else {
            $this->add_error('AI agents directory missing: ' . $ai_agents_dir);
        }
        
        // Check database directory
        $database_dir = $includes_dir . 'database/';
        if (is_dir($database_dir)) {
            $this->add_success('Database directory exists');
            
            $db_files = array(
                'class-vortex-database-manager.php',
                'class-vortex-artist-journey-database.php'
            );
            
            foreach ($db_files as $file) {
                $db_file = $database_dir . $file;
                if (file_exists($db_file)) {
                    $this->add_success("Database file exists: $file");
                } else {
                    $this->add_error("Database file missing: $file");
                }
            }
        } else {
            $this->add_error('Database directory missing: ' . $database_dir);
        }
        
        echo "✅ Plugin Files Test Complete\n\n";
    }
    
    /**
     * Test required dependencies
     */
    private function test_required_dependencies() {
        echo "🔧 Testing Required Dependencies...\n";
        
        // Check required PHP extensions
        $required_extensions = array('curl', 'json', 'openssl', 'mbstring');
        foreach ($required_extensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->add_result("PHP Extension: $ext", $loaded ? 'Loaded' : 'Missing', $loaded);
        }
        
        // Check WordPress functions
        $required_functions = array(
            'get_bloginfo',
            'is_admin',
            'wp_upload_dir',
            'wp_is_writable'
        );
        
        foreach ($required_functions as $function) {
            $exists = function_exists($function);
            $this->add_result("WordPress Function: $function", $exists ? 'Exists' : 'Missing', $exists);
        }
        
        echo "✅ Required Dependencies Test Complete\n\n";
    }
    
    /**
     * Test database connectivity
     */
    private function test_database_connectivity() {
        echo "🗄️ Testing Database Connectivity...\n";
        
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
        
        // Test table creation permissions
        $test_table = $wpdb->prefix . 'vortex_test_table';
        $create_result = $wpdb->query("CREATE TABLE IF NOT EXISTS $test_table (id INT PRIMARY KEY)");
        
        if ($create_result !== false) {
            $this->add_success('Database write permissions: OK');
            // Clean up test table
            $wpdb->query("DROP TABLE IF EXISTS $test_table");
        } else {
            $this->add_error('Database write permissions: Failed - ' . $wpdb->last_error);
        }
        
        echo "✅ Database Connectivity Test Complete\n\n";
    }
    
    /**
     * Test plugin loading
     */
    private function test_plugin_loading() {
        echo "🚀 Testing Plugin Loading...\n";
        
        $plugin_path = ABSPATH . 'vortex-ai-engine/vortex-ai-engine.php';
        
        if (!file_exists($plugin_path)) {
            $this->add_error('Plugin file not found');
            return;
        }
        
        try {
            // Get plugin data
            $plugin_data = get_plugin_data($plugin_path);
            if ($plugin_data) {
                $this->add_success('Plugin data loaded: ' . $plugin_data['Name'] . ' v' . $plugin_data['Version']);
            } else {
                $this->add_error('Failed to load plugin data');
            }
        } catch (Exception $e) {
            $this->add_error('Error loading plugin data: ' . $e->getMessage());
        }
        
        // Test if plugin is already active
        if (function_exists('is_plugin_active')) {
            if (is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
                $this->add_success('Plugin is already active');
            } else {
                $this->add_warning('Plugin is not active');
            }
        } else {
            $this->add_warning('Cannot check plugin activation status');
        }
        
        echo "✅ Plugin Loading Test Complete\n\n";
    }
    
    /**
     * Add test result
     */
    private function add_result($test, $result, $passed) {
        $this->results[] = array(
            'test' => $test,
            'result' => $result,
            'passed' => $passed
        );
        
        if (!$passed) {
            $this->errors[] = "$test: $result";
        }
    }
    
    /**
     * Add success message
     */
    private function add_success($message) {
        $this->add_result($message, 'Success', true);
    }
    
    /**
     * Add warning message
     */
    private function add_warning($message) {
        $this->add_result($message, 'Warning', true);
    }
    
    /**
     * Add error message
     */
    private function add_error($message) {
        $this->add_result($message, 'Error', false);
    }
    
    /**
     * Generate test report
     */
    private function generate_test_report() {
        $total_tests = count($this->results);
        $passed_tests = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failed_tests = $total_tests - $passed_tests;
        
        echo "📊 Plugin Activation Test Report\n";
        echo "================================\n\n";
        
        echo "Total Tests: $total_tests\n";
        echo "Passed: $passed_tests\n";
        echo "Failed: $failed_tests\n\n";
        
        if (!empty($this->errors)) {
            echo "❌ Issues Found:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
            echo "\n";
        }
        
        echo "📋 Detailed Results:\n";
        echo "===================\n";
        
        foreach ($this->results as $result) {
            $status = $result['passed'] ? '✅' : '❌';
            echo "$status {$result['test']}: {$result['result']}\n";
        }
        
        echo "\n";
        
        if ($failed_tests === 0) {
            echo "🎉 All tests passed! VORTEX AI Engine should activate successfully.\n";
            echo "   You can now go to WordPress Admin → Plugins and activate the plugin.\n";
        } else {
            echo "⚠️ Some tests failed. Please review and fix the issues before activation.\n";
        }
        
        // Save report to file
        $report_file = ABSPATH . 'wp-content/uploads/vortex-activation-test-' . date('Y-m-d-H-i-s') . '.txt';
        $report_content = ob_get_contents();
        file_put_contents($report_file, $report_content);
        
        echo "\n📄 Test report saved to: $report_file\n";
    }
}

// Run test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new VORTEX_Plugin_Activation_Test();
    $test->run_test();
} 