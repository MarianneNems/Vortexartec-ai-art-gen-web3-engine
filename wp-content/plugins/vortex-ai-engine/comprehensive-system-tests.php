<?php
/**
 * VORTEX AI Engine - Comprehensive System Tests
 * 
 * This file contains comprehensive tests for the VORTEX AI Engine plugin
 * Used by GitHub Actions CI/CD pipeline to validate the system
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    // For CLI testing, define ABSPATH if not set
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__FILE__) . '/../');
    }
}

class VORTEX_Comprehensive_System_Tests {
    
    private $test_results = [];
    private $errors = [];
    private $warnings = [];
    private $success_count = 0;
    private $total_tests = 0;
    
    public function __construct() {
        $this->init_tests();
    }
    
    /**
     * Initialize and run all tests
     */
    public function run_all_tests() {
        echo "🚀 VORTEX AI Engine - Comprehensive System Tests\n";
        echo "================================================\n\n";
        
        $this->test_php_syntax();
        $this->test_file_structure();
        $this->test_security_measures();
        $this->test_required_classes();
        $this->test_shortcode_registration();
        $this->test_database_tables();
        $this->test_ai_agents();
        $this->test_blockchain_integration();
        $this->test_cloud_services();
        $this->test_admin_interface();
        $this->test_public_interface();
        $this->test_audit_system();
        $this->test_performance();
        $this->test_documentation();
        
        $this->generate_report();
        
        return $this->errors === [];
    }
    
    /**
     * Test PHP syntax on all files
     */
    private function test_php_syntax() {
        $this->start_test_section("PHP Syntax Validation");
        
        $php_files = $this->get_php_files();
        foreach ($php_files as $file) {
            $this->total_tests++;
            $output = [];
            $return_var = 0;
            
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                $this->success_count++;
                $this->test_results[] = "✅ $file - Syntax OK";
            } else {
                $this->errors[] = "❌ $file - Syntax Error: " . implode("\n", $output);
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test file structure and organization
     */
    private function test_file_structure() {
        $this->start_test_section("File Structure Validation");
        
        $required_directories = [
            'admin',
            'includes',
            'public',
            'contracts',
            'audit-system'
        ];
        
        foreach ($required_directories as $dir) {
            $this->total_tests++;
            if (is_dir($dir)) {
                $this->success_count++;
                $this->test_results[] = "✅ Directory $dir exists";
            } else {
                $this->errors[] = "❌ Directory $dir missing";
            }
        }
        
        // Test index.php files in directories
        foreach ($required_directories as $dir) {
            $this->total_tests++;
            $index_file = $dir . '/index.php';
            if (file_exists($index_file)) {
                $this->success_count++;
                $this->test_results[] = "✅ $index_file exists";
            } else {
                $this->warnings[] = "⚠️ $index_file missing (security stub)";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test security measures
     */
    private function test_security_measures() {
        $this->start_test_section("Security Measures Validation");
        
        $php_files = $this->get_php_files();
        foreach ($php_files as $file) {
            $this->total_tests++;
            $content = file_get_contents($file);
            
            if (strpos($content, 'if (!defined(\'ABSPATH\'))') !== false) {
                $this->success_count++;
                $this->test_results[] = "✅ $file - ABSPATH guard present";
            } else {
                $this->warnings[] = "⚠️ $file - ABSPATH guard missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test required classes exist
     */
    private function test_required_classes() {
        $this->start_test_section("Required Classes Validation");
        
        $required_classes = [
            'VORTEX_ARCHER_Orchestrator',
            'Vortex_Huraii_Agent',
            'Vortex_Cloe_Agent',
            'Vortex_Horace_Agent',
            'Vortex_Thorius_Agent',
            'Vortex_Artist_Journey',
            'Vortex_Tola_Token_Handler',
            'Vortex_Activity_Logger',
            'Vortex_Database_Manager'
        ];
        
        foreach ($required_classes as $class) {
            $this->total_tests++;
            if (class_exists($class)) {
                $this->success_count++;
                $this->test_results[] = "✅ Class $class exists";
            } else {
                $this->errors[] = "❌ Class $class missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test shortcode registration
     */
    private function test_shortcode_registration() {
        $this->start_test_section("Shortcode Registration Validation");
        
        $required_shortcodes = [
            'vortex_signup',
            'vortex_connect_wallet',
            'vortex_artist_quiz',
            'vortex_horas_quiz',
            'vortex_artist_dashboard',
            'vortex_artwork_generator',
            'vortex_marketplace',
            'vortex_wallet',
            'vortex_metrics'
        ];
        
        foreach ($required_shortcodes as $shortcode) {
            $this->total_tests++;
            if (shortcode_exists($shortcode)) {
                $this->success_count++;
                $this->test_results[] = "✅ Shortcode [$shortcode] registered";
            } else {
                $this->warnings[] = "⚠️ Shortcode [$shortcode] not registered";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test database tables
     */
    private function test_database_tables() {
        $this->start_test_section("Database Tables Validation");
        
        global $wpdb;
        
        $required_tables = [
            $wpdb->prefix . 'vortex_artist_journey_profiles',
            $wpdb->prefix . 'vortex_artist_activities',
            $wpdb->prefix . 'vortex_rl_system',
            $wpdb->prefix . 'vortex_self_improvement'
        ];
        
        foreach ($required_tables as $table) {
            $this->total_tests++;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
            
            if ($table_exists) {
                $this->success_count++;
                $this->test_results[] = "✅ Table $table exists";
            } else {
                $this->warnings[] = "⚠️ Table $table missing (may be created on activation)";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test AI agents
     */
    private function test_ai_agents() {
        $this->start_test_section("AI Agents Validation");
        
        $ai_agents = [
            'ARCHER' => 'VORTEX_ARCHER_Orchestrator',
            'HURAII' => 'Vortex_Huraii_Agent',
            'CLOE' => 'Vortex_Cloe_Agent',
            'HORACE' => 'Vortex_Horace_Agent',
            'THORIUS' => 'Vortex_Thorius_Agent'
        ];
        
        foreach ($ai_agents as $name => $class) {
            $this->total_tests++;
            if (class_exists($class)) {
                $this->success_count++;
                $this->test_results[] = "✅ AI Agent $name ($class) exists";
            } else {
                $this->errors[] = "❌ AI Agent $name ($class) missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test blockchain integration
     */
    private function test_blockchain_integration() {
        $this->start_test_section("Blockchain Integration Validation");
        
        $blockchain_files = [
            'includes/blockchain/class-vortex-tola-token-handler.php',
            'includes/blockchain/class-vortex-smart-contract-manager.php',
            'contracts/TOLAArtDailyRoyalty.sol'
        ];
        
        foreach ($blockchain_files as $file) {
            $this->total_tests++;
            if (file_exists($file)) {
                $this->success_count++;
                $this->test_results[] = "✅ $file exists";
            } else {
                $this->errors[] = "❌ $file missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test cloud services
     */
    private function test_cloud_services() {
        $this->start_test_section("Cloud Services Validation");
        
        $cloud_files = [
            'includes/cloud/class-vortex-runpod-vault.php',
            'includes/cloud/class-vortex-gradio-client.php',
            'includes/storage/class-vortex-storage-router.php'
        ];
        
        foreach ($cloud_files as $file) {
            $this->total_tests++;
            if (file_exists($file)) {
                $this->success_count++;
                $this->test_results[] = "✅ $file exists";
            } else {
                $this->errors[] = "❌ $file missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test admin interface
     */
    private function test_admin_interface() {
        $this->start_test_section("Admin Interface Validation");
        
        $admin_files = [
            'admin/class-vortex-admin-controller.php',
            'admin/class-vortex-admin-dashboard.php',
            'admin/class-vortex-activity-monitor.php',
            'admin/class-vortex-artist-journey-dashboard.php',
            'admin/css/activity-monitor.css',
            'admin/js/activity-monitor.js'
        ];
        
        foreach ($admin_files as $file) {
            $this->total_tests++;
            if (file_exists($file)) {
                $this->success_count++;
                $this->test_results[] = "✅ $file exists";
            } else {
                $this->errors[] = "❌ $file missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test public interface
     */
    private function test_public_interface() {
        $this->start_test_section("Public Interface Validation");
        
        $public_files = [
            'public/class-vortex-public-interface.php',
            'public/class-vortex-marketplace-frontend.php'
        ];
        
        foreach ($public_files as $file) {
            $this->total_tests++;
            if (file_exists($file)) {
                $this->success_count++;
                $this->test_results[] = "✅ $file exists";
            } else {
                $this->errors[] = "❌ $file missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test audit system
     */
    private function test_audit_system() {
        $this->start_test_section("Audit System Validation");
        
        $audit_files = [
            'audit-system/class-vortex-auditor.php',
            'audit-system/class-vortex-self-improvement.php'
        ];
        
        foreach ($audit_files as $file) {
            $this->total_tests++;
            if (file_exists($file)) {
                $this->success_count++;
                $this->test_results[] = "✅ $file exists";
            } else {
                $this->errors[] = "❌ $file missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test performance metrics
     */
    private function test_performance() {
        $this->start_test_section("Performance Validation");
        
        $this->total_tests++;
        $memory_usage = memory_get_usage(true);
        if ($memory_usage < 50 * 1024 * 1024) { // 50MB
            $this->success_count++;
            $this->test_results[] = "✅ Memory usage acceptable: " . number_format($memory_usage / 1024 / 1024, 2) . " MB";
        } else {
            $this->warnings[] = "⚠️ High memory usage: " . number_format($memory_usage / 1024 / 1024, 2) . " MB";
        }
        
        $this->end_test_section();
    }
    
    /**
     * Test documentation
     */
    private function test_documentation() {
        $this->start_test_section("Documentation Validation");
        
        $docs_files = [
            'README.md',
            'readme.txt',
            'GITHUB-SETUP-GUIDE.md',
            'ARTIST-JOURNEY-GUIDE.md',
            'ACTIVITY-LOGGING-GUIDE.md'
        ];
        
        foreach ($docs_files as $file) {
            $this->total_tests++;
            if (file_exists($file)) {
                $this->success_count++;
                $this->test_results[] = "✅ $file exists";
            } else {
                $this->warnings[] = "⚠️ $file missing";
            }
        }
        
        $this->end_test_section();
    }
    
    /**
     * Get all PHP files in the plugin
     */
    private function get_php_files() {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Start a test section
     */
    private function start_test_section($name) {
        echo "🔍 Testing: $name\n";
        echo str_repeat("-", strlen($name) + 10) . "\n";
    }
    
    /**
     * End a test section
     */
    private function end_test_section() {
        echo "\n";
    }
    
    /**
     * Generate final report
     */
    private function generate_report() {
        echo "📊 TEST RESULTS SUMMARY\n";
        echo "========================\n\n";
        
        echo "Total Tests: $this->total_tests\n";
        echo "Successful: $this->success_count\n";
        echo "Errors: " . count($this->errors) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n\n";
        
        if (!empty($this->errors)) {
            echo "❌ ERRORS:\n";
            foreach ($this->errors as $error) {
                echo "  $error\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️ WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                echo "  $warning\n";
            }
            echo "\n";
        }
        
        if (empty($this->errors)) {
            echo "🎉 ALL TESTS PASSED! VORTEX AI Engine is ready for production.\n";
        } else {
            echo "❌ SOME TESTS FAILED. Please fix the errors above.\n";
        }
        
        echo "\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $tests = new VORTEX_Comprehensive_System_Tests();
    $success = $tests->run_all_tests();
    exit($success ? 0 : 1);
} 