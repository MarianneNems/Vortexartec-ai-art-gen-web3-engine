<?php
/**
 * VORTEX AI Engine - Deployment Verification Script
 * 
 * Comprehensive verification to ensure all systems are working before deployment
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
 * Vortex Deployment Verifier
 */
class Vortex_Deployment_Verifier {
    
    private $verification_results = [];
    private $errors = [];
    private $warnings = [];
    private $success_count = 0;
    private $total_checks = 0;
    
    /**
     * Run comprehensive deployment verification
     */
    public function run_deployment_verification() {
        $this->verification_results = [
            'timestamp' => current_time('mysql'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => VORTEX_AI_ENGINE_VERSION,
            'checks' => []
        ];
        
        // Run all verification checks
        $this->verify_wordpress_credentials();
        $this->verify_file_integrity();
        $this->verify_database_connectivity();
        $this->verify_ai_agents_functionality();
        $this->verify_tola_art_system();
        $this->verify_scheduled_tasks();
        $this->verify_huraii_interface_tabs();
        $this->verify_registration_agreements();
        $this->verify_recursive_self_improvement();
        $this->verify_marketplace_functionality();
        $this->verify_blockchain_integration();
        $this->verify_cloud_integration();
        
        return $this->generate_verification_report();
    }
    
    /**
     * Verify WordPress credentials and permissions
     */
    private function verify_wordpress_credentials() {
        $check = [
            'name' => 'WordPress Credentials & Permissions',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '>=')) {
            $check['details'][] = "✓ WordPress version " . get_bloginfo('version') . " is compatible";
            $this->success_count++;
        } else {
            $check['status'] = 'failed';
            $check['details'][] = "✗ WordPress version " . get_bloginfo('version') . " is below required 5.0";
            $this->errors[] = 'WordPress version incompatible';
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '>=')) {
            $check['details'][] = "✓ PHP version " . PHP_VERSION . " is compatible";
            $this->success_count++;
        } else {
            $check['status'] = 'failed';
            $check['details'][] = "✗ PHP version " . PHP_VERSION . " is below required 7.4";
            $this->errors[] = 'PHP version incompatible';
        }
        
        // Check user capabilities
        if (current_user_can('manage_options')) {
            $check['details'][] = "✓ User has proper administrative capabilities";
            $this->success_count++;
        } else {
            $check['status'] = 'warning';
            $check['details'][] = "⚠ Current user lacks manage_options capability";
            $this->warnings[] = 'User capability warning';
        }
        
        // Check upload directory permissions
        $upload_dir = wp_upload_dir();
        if (is_writable($upload_dir['basedir'])) {
            $check['details'][] = "✓ Upload directory is writable";
            $this->success_count++;
        } else {
            $check['status'] = 'warning';
            $check['details'][] = "⚠ Upload directory is not writable";
            $this->warnings[] = 'Upload directory not writable';
        }
        
        $this->verification_results['checks']['wordpress_credentials'] = $check;
    }
    
    /**
     * Verify file integrity
     */
    private function verify_file_integrity() {
        $check = [
            'name' => 'File Integrity & Structure',
            'status' => 'passed',
            'details' => [],
            'missing_files' => []
        ];
        
        $this->total_checks++;
        
        $required_files = [
            // Core files
            'vortex-ai-engine.php',
            'readme.txt',
            
            // Admin files
            'admin/class-vortex-admin-controller.php',
            'admin/class-vortex-admin-dashboard.php',
            'admin/tola-art-admin-page.php',
            
            // Public files
            'public/class-vortex-public-interface.php',
            'public/class-vortex-marketplace-frontend.php',
            
            // Audit system
            'audit-system/class-vortex-auditor.php',
            'audit-system/class-vortex-self-improvement.php',
            
            // AI Agents
            'includes/ai-agents/class-vortex-archer-orchestrator.php',
            'includes/ai-agents/class-vortex-huraii-agent.php',
            'includes/ai-agents/class-vortex-cloe-agent.php',
            'includes/ai-agents/class-vortex-horace-agent.php',
            'includes/ai-agents/class-vortex-thorius-agent.php',
            
            // TOLA-ART
            'includes/tola-art/class-vortex-tola-art-daily-automation.php',
            'includes/tola-art/class-vortex-tola-smart-contract-automation.php',
            
            // Secret Sauce
            'includes/secret-sauce/class-vortex-secret-sauce.php',
            'includes/secret-sauce/class-vortex-zodiac-intelligence.php',
            
            // Artist Journey
            'includes/artist-journey/class-vortex-artist-journey.php',
            
            // Subscriptions
            'includes/subscriptions/class-vortex-subscription-manager.php',
            
            // Cloud
            'includes/cloud/class-vortex-runpod-vault.php',
            'includes/cloud/class-vortex-gradio-client.php',
            
            // Blockchain
            'includes/blockchain/class-vortex-smart-contract-manager.php',
            'includes/blockchain/class-vortex-tola-token-handler.php',
            
            // Database & Storage
            'includes/database/class-vortex-database-manager.php',
            'includes/storage/class-vortex-storage-router.php'
        ];
        
        $plugin_path = VORTEX_AI_ENGINE_PLUGIN_PATH;
        
        foreach ($required_files as $file) {
            $full_path = $plugin_path . $file;
            if (file_exists($full_path)) {
                $check['details'][] = "✓ File exists: $file";
                $this->success_count++;
            } else {
                $check['status'] = 'failed';
                $check['missing_files'][] = $file;
                $check['details'][] = "✗ Missing file: $file";
                $this->errors[] = "Missing file: $file";
            }
        }
        
        if (empty($check['missing_files'])) {
            $check['details'][] = "✓ All required files are present";
        }
        
        $this->verification_results['checks']['file_integrity'] = $check;
    }
    
    /**
     * Verify database connectivity
     */
    private function verify_database_connectivity() {
        $check = [
            'name' => 'Database Connectivity & Tables',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        global $wpdb;
        
        // Test database connection
        $test_query = $wpdb->get_var("SELECT 1");
        if ($test_query === '1') {
            $check['details'][] = "✓ Database connection successful";
            $this->success_count++;
        } else {
            $check['status'] = 'failed';
            $check['details'][] = "✗ Database connection failed";
            $this->errors[] = 'Database connection failed';
        }
        
        // Check required tables
        $required_tables = [
            $wpdb->prefix . 'vortex_artworks',
            $wpdb->prefix . 'vortex_artists',
            $wpdb->prefix . 'vortex_transactions',
            $wpdb->prefix . 'vortex_ai_generations',
            $wpdb->prefix . 'vortex_smart_contracts',
            $wpdb->prefix . 'vortex_subscriptions',
            $wpdb->prefix . 'vortex_artist_journey',
            $wpdb->prefix . 'vortex_market_analysis',
            $wpdb->prefix . 'vortex_system_logs',
            $wpdb->prefix . 'vortex_ai_agents_status'
        ];
        
        foreach ($required_tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            if ($table_exists) {
                $check['details'][] = "✓ Table exists: $table";
                $this->success_count++;
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ Table missing: $table (will be created on activation)";
                $this->warnings[] = "Table missing: $table";
            }
        }
        
        $this->verification_results['checks']['database_connectivity'] = $check;
    }
    
    /**
     * Verify AI agents functionality
     */
    private function verify_ai_agents_functionality() {
        $check = [
            'name' => 'AI Agents Functionality',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        $agents = [
            'VORTEX_ARCHER_Orchestrator' => 'Master AI coordinator',
            'Vortex_Huraii_Agent' => 'GPU-powered image generation',
            'Vortex_Cloe_Agent' => 'Market analysis and collector matching',
            'Vortex_Horace_Agent' => 'Content optimization and SEO',
            'Vortex_Thorius_Agent' => 'Platform guide and security'
        ];
        
        foreach ($agents as $class => $description) {
            if (class_exists($class)) {
                $check['details'][] = "✓ $class ($description) is available";
                $this->success_count++;
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
                $this->errors[] = "Missing AI agent: $class";
            }
        }
        
        $this->verification_results['checks']['ai_agents_functionality'] = $check;
    }
    
    /**
     * Verify TOLA-ART system
     */
    private function verify_tola_art_system() {
        $check = [
            'name' => 'TOLA-ART System & Daily Generation',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check TOLA-ART classes
        $tola_classes = [
            'Vortex_Tola_Art_Daily_Automation' => 'Daily art generation',
            'Vortex_Tola_Smart_Contract_Automation' => 'Smart contract automation'
        ];
        
        foreach ($tola_classes as $class => $description) {
            if (class_exists($class)) {
                $check['details'][] = "✓ $class ($description) is available";
                $this->success_count++;
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
                $this->errors[] = "Missing TOLA-ART class: $class";
            }
        }
        
        // Check scheduled tasks
        $scheduled_tasks = [
            'vortex_daily_art_generation' => 'Daily art generation (00:00)',
            'vortex_daily_art_curation' => 'Daily art curation',
            'vortex_daily_art_distribution' => 'Daily art distribution'
        ];
        
        foreach ($scheduled_tasks as $hook => $description) {
            $next_scheduled = wp_next_scheduled($hook);
            if ($next_scheduled) {
                $check['details'][] = "✓ $description scheduled for " . date('Y-m-d H:i:s', $next_scheduled);
                $this->success_count++;
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description is not scheduled (will be scheduled on activation)";
                $this->warnings[] = "Task not scheduled: $hook";
            }
        }
        
        $this->verification_results['checks']['tola_art_system'] = $check;
    }
    
    /**
     * Verify scheduled tasks
     */
    private function verify_scheduled_tasks() {
        $check = [
            'name' => 'Scheduled Tasks & Automation',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        $required_tasks = [
            'vortex_daily_art_generation' => 'Daily art generation',
            'vortex_archer_orchestration' => 'AI orchestration',
            'vortex_ai_health_check' => 'AI health check',
            'vortex_secret_sauce_optimization' => 'Secret sauce optimization',
            'vortex_daily_audit' => 'Daily system audit'
        ];
        
        foreach ($required_tasks as $hook => $description) {
            $next_scheduled = wp_next_scheduled($hook);
            if ($next_scheduled) {
                $check['details'][] = "✓ $description scheduled for " . date('Y-m-d H:i:s', $next_scheduled);
                $this->success_count++;
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description is not scheduled (will be scheduled on activation)";
                $this->warnings[] = "Task not scheduled: $hook";
            }
        }
        
        // Check custom cron schedules
        $cron_schedules = wp_get_schedules();
        $custom_schedules = ['vortex_five_seconds', 'vortex_five_minutes'];
        
        foreach ($custom_schedules as $schedule) {
            if (isset($cron_schedules[$schedule])) {
                $check['details'][] = "✓ Custom schedule '$schedule' is registered";
                $this->success_count++;
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ Custom schedule '$schedule' is not registered (will be registered on activation)";
                $this->warnings[] = "Custom schedule not registered: $schedule";
            }
        }
        
        $this->verification_results['checks']['scheduled_tasks'] = $check;
    }
    
    /**
     * Verify HURAII interface tabs
     */
    private function verify_huraii_interface_tabs() {
        $check = [
            'name' => 'HURAII Interface Tabs & Functionality',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check HURAII agent methods
        if (class_exists('Vortex_Huraii_Agent')) {
            $huraii_methods = [
                'generate_image' => 'Image generation',
                'handle_image_generation' => 'AJAX image generation',
                'generate_daily_art' => 'Daily art generation',
                'optimize_performance' => 'Performance optimization',
                'get_status' => 'Status reporting',
                'get_capabilities' => 'Capabilities reporting'
            ];
            
            $reflection = new ReflectionClass('Vortex_Huraii_Agent');
            
            foreach ($huraii_methods as $method => $description) {
                if ($reflection->hasMethod($method)) {
                    $check['details'][] = "✓ HURAII method '$method' ($description) is available";
                    $this->success_count++;
                } else {
                    $check['status'] = 'failed';
                    $check['details'][] = "✗ HURAII method '$method' ($description) is missing";
                    $this->errors[] = "Missing HURAII method: $method";
                }
            }
        } else {
            $check['status'] = 'failed';
            $check['details'][] = 'HURAII agent class is not available';
            $this->errors[] = 'HURAII agent class missing';
        }
        
        $this->verification_results['checks']['huraii_interface_tabs'] = $check;
    }
    
    /**
     * Verify registration agreements
     */
    private function verify_registration_agreements() {
        $check = [
            'name' => 'Registration Agreements & Enqueued Functionality',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check if registration hooks are properly set
        $registration_hooks = [
            'user_register' => 'User registration hook',
            'wp_ajax_vortex_subscribe_user' => 'AJAX subscription',
            'wp_ajax_nopriv_vortex_subscribe_user' => 'Public subscription'
        ];
        
        foreach ($registration_hooks as $hook => $description) {
            if (has_action($hook)) {
                $check['details'][] = "✓ $description hook is registered";
                $this->success_count++;
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description hook is not registered (will be registered on activation)";
                $this->warnings[] = "Hook not registered: $hook";
            }
        }
        
        // Check enqueued scripts and styles
        $enqueued_assets = [
            'vortex-admin-js' => 'Admin JavaScript',
            'vortex-admin-css' => 'Admin CSS',
            'vortex-public-js' => 'Public JavaScript',
            'vortex-public-css' => 'Public CSS'
        ];
        
        foreach ($enqueued_assets as $handle => $description) {
            if (wp_script_is($handle, 'registered')) {
                $check['details'][] = "✓ $description is registered";
                $this->success_count++;
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description is not registered (will be registered on activation)";
                $this->warnings[] = "Asset not registered: $handle";
            }
        }
        
        $this->verification_results['checks']['registration_agreements'] = $check;
    }
    
    /**
     * Verify recursive self-improvement
     */
    private function verify_recursive_self_improvement() {
        $check = [
            'name' => 'Recursive Self-Improvement System',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check self-improvement class
        if (class_exists('VortexAIEngine_SelfImprovement')) {
            $check['details'][] = '✓ Self-improvement class is available';
            $this->success_count++;
            
            $self_improvement_methods = [
                'run_daily_improvement' => 'Daily improvement cycle',
                'run_weekly_optimization' => 'Weekly optimization',
                'run_monthly_analysis' => 'Monthly analysis',
                'optimize_ai_agents' => 'AI agent optimization',
                'optimize_performance' => 'Performance optimization',
                'optimize_user_experience' => 'UX optimization',
                'enhance_security' => 'Security enhancement',
                'improve_content_quality' => 'Content quality improvement'
            ];
            
            $reflection = new ReflectionClass('VortexAIEngine_SelfImprovement');
            
            foreach ($self_improvement_methods as $method => $description) {
                if ($reflection->hasMethod($method)) {
                    $check['details'][] = "✓ Self-improvement method '$method' ($description) is available";
                    $this->success_count++;
                } else {
                    $check['status'] = 'warning';
                    $check['details'][] = "⚠ Self-improvement method '$method' ($description) is missing";
                    $this->warnings[] = "Missing self-improvement method: $method";
                }
            }
        } else {
            $check['status'] = 'failed';
            $check['details'][] = 'Self-improvement class is not available';
            $this->errors[] = 'Self-improvement class missing';
        }
        
        $this->verification_results['checks']['recursive_self_improvement'] = $check;
    }
    
    /**
     * Verify marketplace functionality
     */
    private function verify_marketplace_functionality() {
        $check = [
            'name' => 'Marketplace Functionality',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check marketplace classes
        $marketplace_classes = [
            'Vortex_Public_Interface' => 'Public interface',
            'Vortex_Marketplace_Frontend' => 'Marketplace frontend',
            'Vortex_Subscription_Manager' => 'Subscription management',
            'Vortex_Smart_Contract_Manager' => 'Smart contract management'
        ];
        
        foreach ($marketplace_classes as $class => $description) {
            if (class_exists($class)) {
                $check['details'][] = "✓ $class ($description) is available";
                $this->success_count++;
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
                $this->errors[] = "Missing marketplace class: $class";
            }
        }
        
        $this->verification_results['checks']['marketplace_functionality'] = $check;
    }
    
    /**
     * Verify blockchain integration
     */
    private function verify_blockchain_integration() {
        $check = [
            'name' => 'Blockchain Integration',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check blockchain classes
        $blockchain_classes = [
            'Vortex_Smart_Contract_Manager' => 'Smart contract management',
            'Vortex_Tola_Token_Handler' => 'TOLA token handling'
        ];
        
        foreach ($blockchain_classes as $class => $description) {
            if (class_exists($class)) {
                $check['details'][] = "✓ $class ($description) is available";
                $this->success_count++;
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
                $this->errors[] = "Missing blockchain class: $class";
            }
        }
        
        $this->verification_results['checks']['blockchain_integration'] = $check;
    }
    
    /**
     * Verify cloud integration
     */
    private function verify_cloud_integration() {
        $check = [
            'name' => 'Cloud Integration',
            'status' => 'passed',
            'details' => []
        ];
        
        $this->total_checks++;
        
        // Check cloud classes
        $cloud_classes = [
            'Vortex_Runpod_Vault' => 'RunPod vault integration',
            'Vortex_Gradio_Client' => 'Gradio client integration'
        ];
        
        foreach ($cloud_classes as $class => $description) {
            if (class_exists($class)) {
                $check['details'][] = "✓ $class ($description) is available";
                $this->success_count++;
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
                $this->errors[] = "Missing cloud class: $class";
            }
        }
        
        $this->verification_results['checks']['cloud_integration'] = $check;
    }
    
    /**
     * Generate comprehensive verification report
     */
    private function generate_verification_report() {
        $report = [
            'summary' => [
                'total_checks' => $this->total_checks,
                'successful_checks' => $this->success_count,
                'failed_checks' => count($this->errors),
                'warning_checks' => count($this->warnings),
                'success_rate' => round(($this->success_count / $this->total_checks) * 100, 2)
            ],
            'details' => $this->verification_results,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'recommendations' => []
        ];
        
        // Generate recommendations
        if (count($this->errors) > 0) {
            $report['recommendations'][] = 'Fix errors before deployment';
        }
        
        if (count($this->warnings) > 0) {
            $report['recommendations'][] = 'Address warnings for optimal functionality';
        }
        
        if ($report['summary']['success_rate'] >= 95) {
            $report['recommendations'][] = 'Plugin is ready for deployment';
        } elseif ($report['summary']['success_rate'] >= 80) {
            $report['recommendations'][] = 'Plugin is mostly ready but review warnings';
        } else {
            $report['recommendations'][] = 'Plugin needs fixes before deployment';
        }
        
        return $report;
    }
}

/**
 * Run deployment verification
 */
function vortex_run_deployment_verification() {
    $verifier = new Vortex_Deployment_Verifier();
    return $verifier->run_deployment_verification();
}

// Run verification if called directly
if (isset($_GET['run_vortex_verification']) && current_user_can('manage_options')) {
    $verification_report = vortex_run_deployment_verification();
    echo '<pre>' . print_r($verification_report, true) . '</pre>';
} 