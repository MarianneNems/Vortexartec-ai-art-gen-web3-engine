<?php
/**
 * VORTEX AI Engine - Activation Debug Script
 * 
 * This script helps identify what's preventing the plugin from activating
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
        echo "❌ WordPress not found. Please run this from within WordPress.\n";
        exit;
    }
}

/**
 * VORTEX Activation Debug Class
 */
class VORTEX_Activation_Debug {
    
    private $errors = array();
    private $warnings = array();
    private $success = array();
    
    public function run_debug() {
        echo "🔍 VORTEX AI Engine - Activation Debug\n";
        echo "=====================================\n\n";
        
        // Test WordPress environment
        $this->test_wordpress_environment();
        
        // Test file structure
        $this->test_file_structure();
        
        // Test required classes
        $this->test_required_classes();
        
        // Test database connection
        $this->test_database_connection();
        
        // Test plugin loading
        $this->test_plugin_loading();
        
        // Generate report
        $this->generate_report();
    }
    
    /**
     * Test WordPress environment
     */
    private function test_wordpress_environment() {
        echo "📋 Testing WordPress Environment...\n";
        
        // Check if WordPress is loaded
        if (function_exists('get_bloginfo')) {
            $this->add_success('WordPress loaded successfully');
        } else {
            $this->add_error('WordPress not loaded');
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
     * Test file structure
     */
    private function test_file_structure() {
        echo "📁 Testing File Structure...\n";
        
        $plugin_path = ABSPATH . 'wp-content/plugins/vortex-ai-engine/';
        
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
        
        echo "✅ File Structure Test Complete\n\n";
    }
    
    /**
     * Test required classes
     */
    private function test_required_classes() {
        echo "🔧 Testing Required Classes...\n";
        
        $plugin_path = ABSPATH . 'wp-content/plugins/vortex-ai-engine/';
        
        // Test loading main plugin file
        $main_file = $plugin_path . 'vortex-ai-engine.php';
        if (file_exists($main_file)) {
            try {
                // Include the main plugin file
                include_once $main_file;
                
                // Check if main class exists
                if (class_exists('Vortex_AI_Engine')) {
                    $this->add_success('Main plugin class loaded successfully');
                } else {
                    $this->add_error('Main plugin class not found after loading');
                }
                
                // Check if global function exists
                if (function_exists('vortex_ai_engine_init')) {
                    $this->add_success('Plugin init function exists');
                } else {
                    $this->add_error('Plugin init function not found');
                }
                
            } catch (Exception $e) {
                $this->add_error('Error loading main plugin file: ' . $e->getMessage());
            } catch (Error $e) {
                $this->add_error('Fatal error loading main plugin file: ' . $e->getMessage());
            }
        }
        
        // Test individual class files
        $classes_to_test = array(
            'includes/ai-agents/class-vortex-archer-orchestrator.php' => 'VORTEX_ARCHER_Orchestrator',
            'includes/ai-agents/class-vortex-huraii-agent.php' => 'Vortex_Huraii_Agent',
            'includes/ai-agents/class-vortex-cloe-agent.php' => 'Vortex_Cloe_Agent',
            'includes/ai-agents/class-vortex-horace-agent.php' => 'Vortex_Horace_Agent',
            'includes/ai-agents/class-vortex-thorius-agent.php' => 'Vortex_Thorius_Agent',
            'includes/database/class-vortex-database-manager.php' => 'Vortex_Database_Manager'
        );
        
        foreach ($classes_to_test as $file => $class_name) {
            $full_path = $plugin_path . $file;
            if (file_exists($full_path)) {
                try {
                    include_once $full_path;
                    if (class_exists($class_name)) {
                        $this->add_success("Class loaded: $class_name");
                    } else {
                        $this->add_error("Class not found after loading: $class_name");
                    }
                } catch (Exception $e) {
                    $this->add_error("Error loading $file: " . $e->getMessage());
                } catch (Error $e) {
                    $this->add_error("Fatal error loading $file: " . $e->getMessage());
                }
            } else {
                $this->add_error("File not found: $file");
            }
        }
        
        echo "✅ Required Classes Test Complete\n\n";
    }
    
    /**
     * Test database connection
     */
    private function test_database_connection() {
        echo "🗄️ Testing Database Connection...\n";
        
        global $wpdb;
        
        if (!$wpdb) {
            $this->add_error('WordPress database object not available');
            return;
        }
        
        // Test basic connection
        $test_query = $wpdb->get_var("SELECT 1");
        if ($test_query) {
            $this->add_success('Database connection successful');
        } else {
            $this->add_error('Database connection failed');
            return;
        }
        
        // Test if we can create tables
        $test_table = $wpdb->prefix . 'vortex_test_table';
        $create_result = $wpdb->query("CREATE TABLE IF NOT EXISTS $test_table (id INT PRIMARY KEY)");
        
        if ($create_result !== false) {
            $this->add_success('Database table creation successful');
            // Clean up test table
            $wpdb->query("DROP TABLE IF EXISTS $test_table");
        } else {
            $this->add_error('Database table creation failed: ' . $wpdb->last_error);
        }
        
        echo "✅ Database Connection Test Complete\n\n";
    }
    
    /**
     * Test plugin loading
     */
    private function test_plugin_loading() {
        echo "🚀 Testing Plugin Loading...\n";
        
        // Check if plugin is already active
        if (is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
            $this->add_success('Plugin is already active');
        } else {
            $this->add_warning('Plugin is not active');
        }
        
        // Test activation function
        $plugin_path = ABSPATH . 'wp-content/plugins/vortex-ai-engine/vortex-ai-engine.php';
        if (file_exists($plugin_path)) {
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
        }
        
        echo "✅ Plugin Loading Test Complete\n\n";
    }
    
    /**
     * Add success message
     */
    private function add_success($message) {
        $this->success[] = $message;
    }
    
    /**
     * Add warning message
     */
    private function add_warning($message) {
        $this->warnings[] = $message;
    }
    
    /**
     * Add error message
     */
    private function add_error($message) {
        $this->errors[] = $message;
    }
    
    /**
     * Generate debug report
     */
    private function generate_report() {
        echo "📊 Debug Report\n";
        echo "===============\n\n";
        
        echo "✅ Successes (" . count($this->success) . "):\n";
        foreach ($this->success as $success) {
            echo "  ✓ $success\n";
        }
        echo "\n";
        
        if (!empty($this->warnings)) {
            echo "⚠️ Warnings (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ $warning\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "❌ Errors (" . count($this->errors) . "):\n";
            foreach ($this->errors as $error) {
                echo "  ✗ $error\n";
            }
            echo "\n";
        }
        
        // Summary
        $total_tests = count($this->success) + count($this->warnings) + count($this->errors);
        $success_rate = $total_tests > 0 ? round((count($this->success) / $total_tests) * 100, 1) : 0;
        
        echo "📈 Summary:\n";
        echo "  Total Tests: $total_tests\n";
        echo "  Success Rate: $success_rate%\n";
        echo "  Errors: " . count($this->errors) . "\n";
        echo "  Warnings: " . count($this->warnings) . "\n\n";
        
        if (empty($this->errors)) {
            echo "🎉 All critical tests passed! Plugin should activate successfully.\n";
        } else {
            echo "🔧 Fix the errors above before attempting to activate the plugin.\n";
        }
        
        // Save report to file
        $report_file = ABSPATH . 'wp-content/uploads/vortex-activation-debug-' . date('Y-m-d-H-i-s') . '.txt';
        $report_content = ob_get_contents();
        file_put_contents($report_file, $report_content);
        
        echo "\n📄 Debug report saved to: $report_file\n";
    }
}

// Run debug if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $debug = new VORTEX_Activation_Debug();
    $debug->run_debug();
} 