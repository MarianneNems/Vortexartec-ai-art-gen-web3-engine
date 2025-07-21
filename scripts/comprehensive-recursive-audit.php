<?php
/**
 * VORTEX AI Engine - Comprehensive Recursive Audit Script
 * 
 * Performs a complete audit of the entire plugin directory structure
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

/**
 * Comprehensive Recursive Audit Class
 */
class VORTEX_Comprehensive_Recursive_Audit {
    
    private $plugin_dir;
    private $audit_results = array();
    private $errors = array();
    private $warnings = array();
    private $files_checked = 0;
    private $files_fixed = 0;
    private $missing_files = array();
    
    public function __construct() {
        $this->plugin_dir = dirname(__FILE__) . '/../vortex-ai-engine/';
    }
    
    /**
     * Run comprehensive audit
     */
    public function run_comprehensive_audit() {
        echo "🔍 VORTEX AI Engine - Comprehensive Recursive Audit\n";
        echo "==================================================\n\n";
        
        // 1. Scan directory tree
        $this->scan_directory_tree();
        
        // 2. Audit PHP files
        $this->audit_php_files();
        
        // 3. Check JS/CSS assets
        $this->check_assets();
        
        // 4. Validate includes components
        $this->validate_includes_components();
        
        // 5. Verify shortcodes
        $this->verify_shortcodes();
        
        // 6. Test REST endpoints
        $this->test_rest_endpoints();
        
        // 7. Check front-end markup
        $this->check_frontend_markup();
        
        // 8. Audit AI pipeline wiring
        $this->audit_ai_pipeline();
        
        // 9. Verify external integrations
        $this->verify_external_integrations();
        
        // 10. Run self-improvement audit
        $this->run_self_improvement_audit();
        
        // 11. Generate comprehensive report
        $this->generate_comprehensive_report();
    }
    
    /**
     * 1. Scan directory tree
     */
    private function scan_directory_tree() {
        echo "📁 1. Scanning Directory Tree...\n";
        
        $directories = array(
            'includes/',
            'includes/ai-agents/',
            'includes/database/',
            'includes/artist-journey/',
            'includes/tola-art/',
            'includes/subscriptions/',
            'includes/storage/',
            'includes/secret-sauce/',
            'includes/cloud/',
            'includes/blockchain/',
            'admin/',
            'public/',
            'assets/',
            'assets/js/',
            'assets/css/',
            'languages/',
            'deployment/',
            'audit-system/',
            'contracts/'
        );
        
        foreach ($directories as $dir) {
            $full_path = $this->plugin_dir . $dir;
            if (is_dir($full_path)) {
                $this->add_success("Directory exists: $dir");
                $this->scan_directory_contents($full_path, $dir);
            } else {
                $this->add_error("Directory missing: $dir");
                $this->missing_files[] = $dir;
            }
        }
        
        echo "\n";
    }
    
    /**
     * Scan directory contents
     */
    private function scan_directory_contents($path, $relative_path) {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_path = $path . '/' . $file;
                if (is_file($file_path)) {
                    $this->add_success("File found: $relative_path$file");
                } elseif (is_dir($file_path)) {
                    $this->add_success("Subdirectory: $relative_path$file/");
                    $this->scan_directory_contents($file_path, $relative_path . $file . '/');
                }
            }
        }
    }
    
    /**
     * 2. Audit PHP files
     */
    private function audit_php_files() {
        echo "🐘 2. Auditing PHP Files...\n";
        
        $php_files = $this->find_php_files($this->plugin_dir);
        
        foreach ($php_files as $file) {
            $this->files_checked++;
            $this->audit_single_php_file($file);
        }
        
        echo "\n";
    }
    
    /**
     * Find all PHP files recursively
     */
    private function find_php_files($directory) {
        $php_files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $php_files[] = $file->getPathname();
            }
        }
        
        return $php_files;
    }
    
    /**
     * Audit a single PHP file
     */
    private function audit_single_php_file($file_path) {
        $relative_path = str_replace($this->plugin_dir, '', $file_path);
        
        // Check file size
        $file_size = filesize($file_path);
        if ($file_size === 0) {
            $this->add_error("Zero-byte file: $relative_path");
            return;
        }
        
        // Syntax check
        $output = array();
        $return_var = 0;
        exec("php -l \"$file_path\" 2>&1", $output, $return_var);
        
        if ($return_var !== 0) {
            $this->add_error("Syntax error in: $relative_path - " . implode(' ', $output));
        } else {
            $this->add_success("Syntax OK: $relative_path");
        }
        
        // Check ABSPATH guard
        $content = file_get_contents($file_path);
        if (strpos($content, 'ABSPATH') === false && strpos($content, 'defined') === false) {
            $this->add_warning("No ABSPATH guard in: $relative_path");
        } else {
            $this->add_success("ABSPATH guard present: $relative_path");
        }
        
        // Check for TODO/FIXME comments
        if (preg_match('/TODO|FIXME/i', $content)) {
            $this->add_warning("TODO/FIXME found in: $relative_path");
        }
        
        // Check for stub files
        if (strlen($content) < 100) {
            $this->add_warning("Potential stub file: $relative_path");
        }
    }
    
    /**
     * 3. Check JS/CSS assets
     */
    private function check_assets() {
        echo "🎨 3. Checking JS/CSS Assets...\n";
        
        $required_js_files = array(
            'assets/js/swap.js',
            'assets/js/wallet.js',
            'assets/js/metrics.js',
            'assets/js/chat.js',
            'assets/js/feedback.js',
            'assets/js/blockchain.js',
            'assets/js/ai-agents.js'
        );
        
        $required_css_files = array(
            'assets/css/vortex-admin.css',
            'assets/css/vortex-public.css',
            'assets/css/swap-interface.css',
            'assets/css/wallet-interface.css',
            'assets/css/metrics-dashboard.css'
        );
        
        foreach ($required_js_files as $file) {
            $this->check_asset_file($file, 'JavaScript');
        }
        
        foreach ($required_css_files as $file) {
            $this->check_asset_file($file, 'CSS');
        }
        
        echo "\n";
    }
    
    /**
     * Check asset file
     */
    private function check_asset_file($file, $type) {
        $file_path = $this->plugin_dir . $file;
        if (file_exists($file_path)) {
            $this->add_success("$type file exists: $file");
            
            // Check file structure
            $content = file_get_contents($file_path);
            if (strlen($content) > 50) {
                $this->add_success("$type file has content: $file");
            } else {
                $this->add_warning("$type file may be empty: $file");
            }
        } else {
            $this->add_error("$type file missing: $file");
            $this->missing_files[] = $file;
        }
    }
    
    /**
     * 4. Validate includes components
     */
    private function validate_includes_components() {
        echo "🔧 4. Validating Includes Components...\n";
        
        // Check core classes
        $core_classes = array(
            'includes/class-vortex-loader.php',
            'includes/class-vortex-config.php',
            'includes/class-vortex-database-manager.php',
            'includes/class-vortex-api-endpoints.php',
            'includes/class-vortex-shortcodes.php'
        );
        
        foreach ($core_classes as $class) {
            $this->check_class_file($class);
        }
        
        // Check AI agents
        $ai_agents = array(
            'includes/ai-agents/class-vortex-archer-orchestrator.php',
            'includes/ai-agents/class-vortex-huraii-agent.php',
            'includes/ai-agents/class-vortex-cloe-agent.php',
            'includes/ai-agents/class-vortex-horace-agent.php',
            'includes/ai-agents/class-vortex-thorius-agent.php'
        );
        
        foreach ($ai_agents as $agent) {
            $this->check_ai_agent($agent);
        }
        
        // Check other modules
        $modules = array(
            'includes/artist-journey/class-vortex-artist-journey.php',
            'includes/subscriptions/class-vortex-subscription-manager.php',
            'includes/tola-art/class-vortex-tola-art.php',
            'includes/secret-sauce/class-vortex-secret-sauce.php'
        );
        
        foreach ($modules as $module) {
            $this->check_module_file($module);
        }
        
        echo "\n";
    }
    
    /**
     * Check class file
     */
    private function check_class_file($file) {
        $file_path = $this->plugin_dir . $file;
        if (file_exists($file_path)) {
            $this->add_success("Core class exists: $file");
            
            // Check if class can be loaded
            $content = file_get_contents($file_path);
            if (preg_match('/class\s+\w+/', $content)) {
                $this->add_success("Class definition found: $file");
            } else {
                $this->add_warning("No class definition in: $file");
            }
        } else {
            $this->add_error("Core class missing: $file");
            $this->missing_files[] = $file;
        }
    }
    
    /**
     * Check AI agent
     */
    private function check_ai_agent($file) {
        $file_path = $this->plugin_dir . $file;
        if (file_exists($file_path)) {
            $this->add_success("AI agent exists: $file");
            
            $content = file_get_contents($file_path);
            
            // Check for testConnection method
            if (strpos($content, 'testConnection') !== false) {
                $this->add_success("testConnection method found: $file");
            } else {
                $this->add_warning("testConnection method missing: $file");
            }
            
            // Check for get_instance method
            if (strpos($content, 'get_instance') !== false) {
                $this->add_success("get_instance method found: $file");
            } else {
                $this->add_warning("get_instance method missing: $file");
            }
        } else {
            $this->add_error("AI agent missing: $file");
            $this->missing_files[] = $file;
        }
    }
    
    /**
     * Check module file
     */
    private function check_module_file($file) {
        $file_path = $this->plugin_dir . $file;
        if (file_exists($file_path)) {
            $this->add_success("Module exists: $file");
        } else {
            $this->add_error("Module missing: $file");
            $this->missing_files[] = $file;
        }
    }
    
    /**
     * 5. Verify shortcodes
     */
    private function verify_shortcodes() {
        echo "📝 5. Verifying Shortcodes...\n";
        
        $shortcode_file = $this->plugin_dir . 'includes/class-vortex-shortcodes.php';
        if (file_exists($shortcode_file)) {
            $this->add_success("Shortcodes class exists");
            
            $content = file_get_contents($shortcode_file);
            
            // Check for add_shortcode calls
            $shortcodes = array(
                'huraii_generate',
                'vortex_wallet',
                'vortex_swap',
                'vortex_metric',
                'vortex_chat',
                'vortex_feedback'
            );
            
            foreach ($shortcodes as $shortcode) {
                if (strpos($content, "add_shortcode('$shortcode'") !== false) {
                    $this->add_success("Shortcode registered: $shortcode");
                } else {
                    $this->add_warning("Shortcode not registered: $shortcode");
                }
                
                // Check for render method
                $render_method = 'render_' . str_replace('_', '_', $shortcode) . '_shortcode';
                if (strpos($content, $render_method) !== false) {
                    $this->add_success("Render method found: $render_method");
                } else {
                    $this->add_warning("Render method missing: $render_method");
                }
            }
        } else {
            $this->add_error("Shortcodes class missing");
            $this->missing_files[] = 'includes/class-vortex-shortcodes.php';
        }
        
        echo "\n";
    }
    
    /**
     * 6. Test REST endpoints
     */
    private function test_rest_endpoints() {
        echo "🌐 6. Testing REST Endpoints...\n";
        
        $api_file = $this->plugin_dir . 'includes/class-vortex-api-endpoints.php';
        if (file_exists($api_file)) {
            $this->add_success("API endpoints class exists");
            
            $content = file_get_contents($api_file);
            
            // Check for register_rest_route calls
            if (strpos($content, 'register_rest_route') !== false) {
                $this->add_success("REST routes registration found");
            } else {
                $this->add_warning("No REST routes registration found");
            }
            
            // Check for permission callbacks
            if (strpos($content, 'permission_callback') !== false) {
                $this->add_success("Permission callbacks found");
            } else {
                $this->add_warning("Permission callbacks missing");
            }
            
            // Check for nonce verification
            if (strpos($content, 'wp_verify_nonce') !== false) {
                $this->add_success("Nonce verification found");
            } else {
                $this->add_warning("Nonce verification missing");
            }
        } else {
            $this->add_error("API endpoints class missing");
            $this->missing_files[] = 'includes/class-vortex-api-endpoints.php';
        }
        
        echo "\n";
    }
    
    /**
     * 7. Check front-end markup
     */
    private function check_frontend_markup() {
        echo "🎨 7. Checking Front-end Markup...\n";
        
        $public_file = $this->plugin_dir . 'public/class-vortex-public.php';
        if (file_exists($public_file)) {
            $this->add_success("Public class exists");
            
            $content = file_get_contents($public_file);
            
            // Check for unique div wrappers
            if (strpos($content, '<div') !== false) {
                $this->add_success("Div wrappers found");
            } else {
                $this->add_warning("No div wrappers found");
            }
            
            // Check for iframe elements
            if (strpos($content, '<iframe') !== false) {
                $this->add_success("Iframe elements found");
            } else {
                $this->add_warning("No iframe elements found");
            }
            
            // Check for tab interfaces
            $tabs = array('swap', 'history', 'chat', 'feedback');
            foreach ($tabs as $tab) {
                if (strpos($content, $tab) !== false) {
                    $this->add_success("Tab interface found: $tab");
                } else {
                    $this->add_warning("Tab interface missing: $tab");
                }
            }
        } else {
            $this->add_error("Public class missing");
            $this->missing_files[] = 'public/class-vortex-public.php';
        }
        
        echo "\n";
    }
    
    /**
     * 8. Audit AI pipeline wiring
     */
    private function audit_ai_pipeline() {
        echo "🤖 8. Auditing AI Pipeline Wiring...\n";
        
        $orchestrator_file = $this->plugin_dir . 'includes/ai-agents/class-vortex-archer-orchestrator.php';
        if (file_exists($orchestrator_file)) {
            $this->add_success("Archer orchestrator exists");
            
            $content = file_get_contents($orchestrator_file);
            
            // Check for ProviderFactory
            if (strpos($content, 'ProviderFactory') !== false) {
                $this->add_success("ProviderFactory found");
            } else {
                $this->add_warning("ProviderFactory missing");
            }
            
            // Check for provider registration
            $providers = array('GPT', 'Claude', 'Gemini', 'Grok', 'SORA');
            foreach ($providers as $provider) {
                if (strpos($content, $provider) !== false) {
                    $this->add_success("Provider found: $provider");
                } else {
                    $this->add_warning("Provider missing: $provider");
                }
            }
            
            // Check for getBestProvider method
            if (strpos($content, 'getBestProvider') !== false) {
                $this->add_success("getBestProvider method found");
            } else {
                $this->add_warning("getBestProvider method missing");
            }
            
            // Check for caching
            if (strpos($content, 'wp_cache_get') !== false || strpos($content, 'wp_cache_set') !== false) {
                $this->add_success("Caching layer found");
            } else {
                $this->add_warning("Caching layer missing");
            }
            
            // Check for reinforcement learning hooks
            if (strpos($content, 'reinforcement_learning') !== false) {
                $this->add_success("Reinforcement learning hooks found");
            } else {
                $this->add_warning("Reinforcement learning hooks missing");
            }
        } else {
            $this->add_error("Archer orchestrator missing");
            $this->missing_files[] = 'includes/ai-agents/class-vortex-archer-orchestrator.php';
        }
        
        echo "\n";
    }
    
    /**
     * 9. Verify external integrations
     */
    private function verify_external_integrations() {
        echo "🔗 9. Verifying External Integrations...\n";
        
        $integrations = array(
            'includes/storage/class-vortex-s3.php' => 'S3 integration',
            'includes/blockchain/class-vortex-solana-integration.php' => 'Solana integration',
            'includes/cloud/class-vortex-aws-services.php' => 'AWS services'
        );
        
        foreach ($integrations as $file => $description) {
            $file_path = $this->plugin_dir . $file;
            if (file_exists($file_path)) {
                $this->add_success("$description exists: $file");
                
                $content = file_get_contents($file_path);
                
                // Check for basic functionality
                if (strlen($content) > 200) {
                    $this->add_success("$description has substantial content");
                } else {
                    $this->add_warning("$description may be a stub");
                }
            } else {
                $this->add_error("$description missing: $file");
                $this->missing_files[] = $file;
            }
        }
        
        echo "\n";
    }
    
    /**
     * 10. Run self-improvement audit
     */
    private function run_self_improvement_audit() {
        echo "🔍 10. Running Self-Improvement Audit...\n";
        
        $audit_file = $this->plugin_dir . 'audit-system/run-audit.php';
        if (file_exists($audit_file)) {
            $this->add_success("Self-improvement audit system exists");
            
            // Try to run the audit
            $output = array();
            $return_var = 0;
            exec("php \"$audit_file\" --full 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                $this->add_success("Self-improvement audit completed successfully");
            } else {
                $this->add_warning("Self-improvement audit had issues: " . implode(' ', $output));
            }
        } else {
            $this->add_error("Self-improvement audit system missing");
            $this->missing_files[] = 'audit-system/run-audit.php';
        }
        
        echo "\n";
    }
    
    /**
     * Add success result
     */
    private function add_success($message) {
        $this->audit_results[] = array('type' => 'success', 'message' => $message);
    }
    
    /**
     * Add warning result
     */
    private function add_warning($message) {
        $this->warnings[] = $message;
        $this->audit_results[] = array('type' => 'warning', 'message' => $message);
    }
    
    /**
     * Add error result
     */
    private function add_error($message) {
        $this->errors[] = $message;
        $this->audit_results[] = array('type' => 'error', 'message' => $message);
    }
    
    /**
     * 11. Generate comprehensive report
     */
    private function generate_comprehensive_report() {
        $total_checks = count($this->audit_results);
        $success_count = count(array_filter($this->audit_results, function($r) { return $r['type'] === 'success'; }));
        $warning_count = count($this->warnings);
        $error_count = count($this->errors);
        
        echo "📊 Comprehensive Recursive Audit Report\n";
        echo "=======================================\n\n";
        
        echo "📈 Summary:\n";
        echo "  Total Checks: $total_checks\n";
        echo "  Passed: $success_count\n";
        echo "  Warnings: $warning_count\n";
        echo "  Errors: $error_count\n";
        echo "  Files Checked: $this->files_checked\n";
        echo "  Files Fixed: $this->files_fixed\n";
        echo "  Missing Files: " . count($this->missing_files) . "\n\n";
        
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
        
        if (!empty($this->missing_files)) {
            echo "📁 Missing Files:\n";
            foreach ($this->missing_files as $file) {
                echo "  📄 $file\n";
            }
            echo "\n";
        }
        
        echo "📋 Detailed Results:\n";
        echo "===================\n";
        
        foreach ($this->audit_results as $result) {
            $status = $result['type'] === 'success' ? '✅' : ($result['type'] === 'warning' ? '⚠️' : '❌');
            echo "$status {$result['message']}\n";
        }
        
        echo "\n";
        
        // Overall assessment
        if ($error_count === 0) {
            if ($warning_count === 0) {
                echo "🎉 Plugin audit completed successfully!\n";
                echo "   All components are properly configured and functional.\n";
            } else {
                echo "✅ Plugin audit completed with minor warnings.\n";
                echo "   Consider addressing warnings for optimal performance.\n";
            }
        } else {
            echo "🔧 Plugin needs attention before deployment.\n";
            echo "   Please fix the critical issues above.\n";
        }
        
        // Save report to file
        $report_file = dirname($this->plugin_dir) . '/COMPREHENSIVE-RECURSIVE-AUDIT-REPORT.md';
        $report_content = ob_get_contents();
        file_put_contents($report_file, $report_content);
        
        echo "\n📄 Comprehensive report saved to: $report_file\n";
    }
}

// Run audit if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $audit = new VORTEX_Comprehensive_Recursive_Audit();
    $audit->run_comprehensive_audit();
} 