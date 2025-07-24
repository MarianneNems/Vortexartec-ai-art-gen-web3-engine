<?php
/**
 * VORTEX AI Engine - Activation Audit Script
 * 
 * Comprehensive audit to ensure all WordPress credentials, files, and functionality are correct
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
 * Vortex Activation Auditor
 */
class Vortex_Activation_Auditor {
    
    private $audit_results = [];
    private $missing_files = [];
    private $errors = [];
    private $warnings = [];
    
    /**
     * Run comprehensive activation audit
     */
    public function run_activation_audit() {
        $this->audit_results = [
            'timestamp' => current_time('mysql'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => VORTEX_AI_ENGINE_VERSION,
            'checks' => []
        ];
        
        // Run all audit checks
        $this->audit_wordpress_credentials();
        $this->audit_file_structure();
        $this->audit_database_tables();
        $this->audit_ai_agents();
        $this->audit_tola_art_system();
        $this->audit_scheduled_tasks();
        $this->audit_huraii_interface();
        $this->audit_registration_agreements();
        $this->audit_recursive_self_improvement();
        $this->audit_marketplace_functionality();
        
        return $this->generate_audit_report();
    }
    
    /**
     * Audit WordPress credentials and permissions
     */
    private function audit_wordpress_credentials() {
        $check = [
            'name' => 'WordPress Credentials & Permissions',
            'status' => 'passed',
            'details' => []
        ];
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $check['status'] = 'failed';
            $check['details'][] = 'WordPress version ' . get_bloginfo('version') . ' is below required 5.0';
        } else {
            $check['details'][] = 'WordPress version ' . get_bloginfo('version') . ' is compatible';
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $check['status'] = 'failed';
            $check['details'][] = 'PHP version ' . PHP_VERSION . ' is below required 7.4';
        } else {
            $check['details'][] = 'PHP version ' . PHP_VERSION . ' is compatible';
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            $check['status'] = 'warning';
            $check['details'][] = 'Current user lacks manage_options capability';
        } else {
            $check['details'][] = 'User has proper administrative capabilities';
        }
        
        // Check upload directory permissions
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            $check['status'] = 'warning';
            $check['details'][] = 'Upload directory is not writable';
        } else {
            $check['details'][] = 'Upload directory is writable';
        }
        
        // Check plugin directory permissions
        $plugin_dir = WP_PLUGIN_DIR . '/vortex-ai-engine/';
        if (!is_readable($plugin_dir)) {
            $check['status'] = 'failed';
            $check['details'][] = 'Plugin directory is not readable';
        } else {
            $check['details'][] = 'Plugin directory is accessible';
        }
        
        $this->audit_results['checks']['wordpress_credentials'] = $check;
    }
    
    /**
     * Audit file structure and missing files
     */
    private function audit_file_structure() {
        $check = [
            'name' => 'File Structure & Missing Files',
            'status' => 'passed',
            'details' => [],
            'missing_files' => []
        ];
        
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
            if (!file_exists($full_path)) {
                $check['status'] = 'failed';
                $check['missing_files'][] = $file;
                $check['details'][] = "Missing file: $file";
            } else {
                $check['details'][] = "✓ File exists: $file";
            }
        }
        
        if (empty($check['missing_files'])) {
            $check['details'][] = 'All required files are present';
        }
        
        $this->audit_results['checks']['file_structure'] = $check;
    }
    
    /**
     * Audit database tables
     */
    private function audit_database_tables() {
        $check = [
            'name' => 'Database Tables',
            'status' => 'passed',
            'details' => [],
            'missing_tables' => []
        ];
        
        global $wpdb;
        
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
            if (!$table_exists) {
                $check['status'] = 'failed';
                $check['missing_tables'][] = $table;
                $check['details'][] = "Missing table: $table";
            } else {
                $check['details'][] = "✓ Table exists: $table";
            }
        }
        
        if (empty($check['missing_tables'])) {
            $check['details'][] = 'All required database tables are present';
        }
        
        $this->audit_results['checks']['database_tables'] = $check;
    }
    
    /**
     * Audit AI agents functionality
     */
    private function audit_ai_agents() {
        $check = [
            'name' => 'AI Agents Functionality',
            'status' => 'passed',
            'details' => []
        ];
        
        $agents = [
            'Vortex_ARCHER_Orchestrator' => 'Master AI coordinator',
            'Vortex_HURAII_Agent' => 'GPU-powered image generation',
            'Vortex_CLOE_Agent' => 'Market analysis and collector matching',
            'Vortex_HORACE_Agent' => 'Content optimization and SEO',
            'Vortex_THORIUS_Agent' => 'Platform guide and security'
        ];
        
        foreach ($agents as $class => $description) {
            if (class_exists($class)) {
                $check['details'][] = "✓ $class ($description) is available";
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
            }
        }
        
        $this->audit_results['checks']['ai_agents'] = $check;
    }
    
    /**
     * Audit TOLA-ART system
     */
    private function audit_tola_art_system() {
        $check = [
            'name' => 'TOLA-ART System',
            'status' => 'passed',
            'details' => []
        ];
        
        // Check TOLA-ART classes
        $tola_classes = [
            'Vortex_TOLA_Art_Daily_Automation' => 'Daily art generation',
            'Vortex_TOLA_Smart_Contract_Automation' => 'Smart contract automation'
        ];
        
        foreach ($tola_classes as $class => $description) {
            if (class_exists($class)) {
                $check['details'][] = "✓ $class ($description) is available";
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
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
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description is not scheduled";
            }
        }
        
        $this->audit_results['checks']['tola_art_system'] = $check;
    }
    
    /**
     * Audit scheduled tasks
     */
    private function audit_scheduled_tasks() {
        $check = [
            'name' => 'Scheduled Tasks & Automation',
            'status' => 'passed',
            'details' => []
        ];
        
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
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description is not scheduled";
            }
        }
        
        // Check custom cron schedules
        $cron_schedules = wp_get_schedules();
        $custom_schedules = ['vortex_five_seconds', 'vortex_five_minutes'];
        
        foreach ($custom_schedules as $schedule) {
            if (isset($cron_schedules[$schedule])) {
                $check['details'][] = "✓ Custom schedule '$schedule' is registered";
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ Custom schedule '$schedule' is not registered";
            }
        }
        
        $this->audit_results['checks']['scheduled_tasks'] = $check;
    }
    
    /**
     * Audit HURAII interface tabs
     */
    private function audit_huraii_interface() {
        $check = [
            'name' => 'HURAII Interface Tabs',
            'status' => 'passed',
            'details' => []
        ];
        
        // Check HURAII agent methods
        if (class_exists('Vortex_HURAII_Agent')) {
            $huraii_methods = [
                'generate_image' => 'Image generation',
                'handle_image_generation' => 'AJAX image generation',
                'generate_daily_art' => 'Daily art generation',
                'optimize_performance' => 'Performance optimization',
                'get_status' => 'Status reporting',
                'get_capabilities' => 'Capabilities reporting'
            ];
            
            $reflection = new ReflectionClass('Vortex_HURAII_Agent');
            
            foreach ($huraii_methods as $method => $description) {
                if ($reflection->hasMethod($method)) {
                    $check['details'][] = "✓ HURAII method '$method' ($description) is available";
                } else {
                    $check['status'] = 'failed';
                    $check['details'][] = "✗ HURAII method '$method' ($description) is missing";
                }
            }
        } else {
            $check['status'] = 'failed';
            $check['details'][] = 'HURAII agent class is not available';
        }
        
        $this->audit_results['checks']['huraii_interface'] = $check;
    }
    
    /**
     * Audit registration agreements
     */
    private function audit_registration_agreements() {
        $check = [
            'name' => 'Registration Agreements & Enqueued Functionality',
            'status' => 'passed',
            'details' => []
        ];
        
        // Check if registration hooks are properly set
        $registration_hooks = [
            'user_register' => 'User registration hook',
            'wp_ajax_vortex_subscribe_user' => 'AJAX subscription',
            'wp_ajax_nopriv_vortex_subscribe_user' => 'Public subscription'
        ];
        
        foreach ($registration_hooks as $hook => $description) {
            if (has_action($hook)) {
                $check['details'][] = "✓ $description hook is registered";
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description hook is not registered";
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
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description is not registered";
            }
        }
        
        $this->audit_results['checks']['registration_agreements'] = $check;
    }
    
    /**
     * Audit recursive self-improvement
     */
    private function audit_recursive_self_improvement() {
        $check = [
            'name' => 'Recursive Self-Improvement System',
            'status' => 'passed',
            'details' => []
        ];
        
        // Check self-improvement class
        if (class_exists('VortexAIEngine_SelfImprovement')) {
            $check['details'][] = '✓ Self-improvement class is available';
            
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
                } else {
                    $check['status'] = 'warning';
                    $check['details'][] = "⚠ Self-improvement method '$method' ($description) is missing";
                }
            }
        } else {
            $check['status'] = 'failed';
            $check['details'][] = 'Self-improvement class is not available';
        }
        
        // Check scheduled self-improvement tasks
        $self_improvement_tasks = [
            'vortex_daily_improvement' => 'Daily improvement',
            'vortex_weekly_optimization' => 'Weekly optimization',
            'vortex_monthly_analysis' => 'Monthly analysis'
        ];
        
        foreach ($self_improvement_tasks as $hook => $description) {
            $next_scheduled = wp_next_scheduled($hook);
            if ($next_scheduled) {
                $check['details'][] = "✓ $description scheduled for " . date('Y-m-d H:i:s', $next_scheduled);
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description is not scheduled";
            }
        }
        
        $this->audit_results['checks']['recursive_self_improvement'] = $check;
    }
    
    /**
     * Audit marketplace functionality
     */
    private function audit_marketplace_functionality() {
        $check = [
            'name' => 'Marketplace Functionality',
            'status' => 'passed',
            'details' => []
        ];
        
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
            } else {
                $check['status'] = 'failed';
                $check['details'][] = "✗ $class ($description) is missing";
            }
        }
        
        // Check marketplace AJAX handlers
        $marketplace_ajax = [
            'vortex_generate_artwork' => 'Artwork generation',
            'vortex_purchase_artwork' => 'Artwork purchase',
            'vortex_get_artwork_details' => 'Artwork details',
            'vortex_subscribe_user' => 'User subscription',
            'vortex_get_artist_profile' => 'Artist profile',
            'vortex_search_artworks' => 'Artwork search',
            'vortex_filter_artworks' => 'Artwork filtering',
            'vortex_add_to_cart' => 'Add to cart',
            'vortex_checkout' => 'Checkout process'
        ];
        
        foreach ($marketplace_ajax as $action => $description) {
            if (has_action("wp_ajax_$action") || has_action("wp_ajax_nopriv_$action")) {
                $check['details'][] = "✓ $description AJAX handler is registered";
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description AJAX handler is not registered";
            }
        }
        
        // Check shortcodes
        $marketplace_shortcodes = [
            'vortex_artwork_generator' => 'Artwork generator',
            'vortex_artwork_gallery' => 'Artwork gallery',
            'vortex_artist_profile' => 'Artist profile',
            'vortex_marketplace' => 'Marketplace',
            'vortex_subscription_form' => 'Subscription form',
            'vortex_marketplace_home' => 'Marketplace home',
            'vortex_artwork_detail' => 'Artwork detail',
            'vortex_artist_marketplace' => 'Artist marketplace',
            'vortex_auction_house' => 'Auction house',
            'vortex_shopping_cart' => 'Shopping cart'
        ];
        
        foreach ($marketplace_shortcodes as $shortcode => $description) {
            if (shortcode_exists($shortcode)) {
                $check['details'][] = "✓ $description shortcode is registered";
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "⚠ $description shortcode is not registered";
            }
        }
        
        $this->audit_results['checks']['marketplace_functionality'] = $check;
    }
    
    /**
     * Generate comprehensive audit report
     */
    private function generate_audit_report() {
        $report = [
            'summary' => [
                'total_checks' => count($this->audit_results['checks']),
                'passed' => 0,
                'warnings' => 0,
                'failed' => 0
            ],
            'details' => $this->audit_results,
            'recommendations' => []
        ];
        
        // Calculate summary
        foreach ($this->audit_results['checks'] as $check) {
            switch ($check['status']) {
                case 'passed':
                    $report['summary']['passed']++;
                    break;
                case 'warning':
                    $report['summary']['warnings']++;
                    break;
                case 'failed':
                    $report['summary']['failed']++;
                    break;
            }
        }
        
        // Generate recommendations
        if ($report['summary']['failed'] > 0) {
            $report['recommendations'][] = 'Fix failed checks before activation';
        }
        
        if ($report['summary']['warnings'] > 0) {
            $report['recommendations'][] = 'Address warnings for optimal functionality';
        }
        
        if ($report['summary']['passed'] === $report['summary']['total_checks']) {
            $report['recommendations'][] = 'All checks passed - plugin is ready for activation';
        }
        
        return $report;
    }
}

/**
 * Run activation audit
 */
function vortex_run_activation_audit() {
    $auditor = new Vortex_Activation_Auditor();
    return $auditor->run_activation_audit();
}

// Run audit if called directly
if (isset($_GET['run_vortex_audit']) && current_user_can('manage_options')) {
    $audit_report = vortex_run_activation_audit();
    echo '<pre>' . print_r($audit_report, true) . '</pre>';
} 