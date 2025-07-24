<?php
/**
 * Vortex AI Engine - Activation Debug Script
 * 
 * Diagnoses and fixes activation issues, especially WooCommerce conflicts
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    if (!file_exists('../../../wp-config.php')) {
        die('❌ WordPress not found. Please run this script from wp-content/plugins/vortex-ai-engine/deployment/');
    }
    require_once '../../../wp-config.php';
}

class Vortex_Activation_Debug {
    
    private $issues = [];
    private $fixes = [];
    
    /**
     * Run activation diagnostics
     */
    public function run_diagnostics() {
        echo "🔍 VORTEX AI ENGINE - ACTIVATION DEBUG\n";
        echo "=====================================\n\n";
        
        $this->check_woocommerce_conflicts();
        $this->check_plugin_structure();
        $this->check_php_compatibility();
        $this->check_wordpress_compatibility();
        $this->check_file_permissions();
        $this->check_database_tables();
        
        $this->display_results();
        $this->suggest_fixes();
    }
    
    /**
     * Check WooCommerce conflicts
     */
    private function check_woocommerce_conflicts() {
        echo "🛒 Checking WooCommerce conflicts...\n";
        
        // Check if WooCommerce is active
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            $this->issues[] = "WooCommerce is active - potential integration conflicts";
            
            // Check WooCommerce Blocks
            if (is_plugin_active('woocommerce-blocks/woocommerce-blocks.php')) {
                $this->issues[] = "WooCommerce Blocks is active - causing integration registry conflicts";
                $this->fixes[] = "Temporarily deactivate WooCommerce Blocks plugin";
            }
            
            // Check WooCommerce version
            if (defined('WC_VERSION')) {
                $wc_version = WC_VERSION;
                if (version_compare($wc_version, '5.0', '<')) {
                    $this->issues[] = "WooCommerce version $wc_version may have compatibility issues";
                    $this->fixes[] = "Update WooCommerce to version 5.0 or higher";
                }
            }
        } else {
            echo "✅ WooCommerce not active - no conflicts detected\n";
        }
        
        echo "   Completed WooCommerce conflict check\n\n";
    }
    
    /**
     * Check plugin structure
     */
    private function check_plugin_structure() {
        echo "📁 Checking plugin structure...\n";
        
        $required_files = [
            'vortex-ai-engine.php',
            'includes/class-vortex-agreement-policy.php',
            'includes/class-vortex-health-check.php',
            'assets/js/agreement.js',
            'assets/css/agreement.css'
        ];
        
        foreach ($required_files as $file) {
            if (!file_exists($file)) {
                $this->issues[] = "Missing required file: $file";
            } else {
                echo "✅ $file exists\n";
            }
        }
        
        // Check main plugin file header
        if (file_exists('vortex-ai-engine.php')) {
            $plugin_data = get_plugin_data('vortex-ai-engine.php');
            if (empty($plugin_data['Plugin Name'])) {
                $this->issues[] = "Invalid plugin header in vortex-ai-engine.php";
            } else {
                echo "✅ Plugin header valid: {$plugin_data['Plugin Name']}\n";
            }
        }
        
        echo "   Completed plugin structure check\n\n";
    }
    
    /**
     * Check PHP compatibility
     */
    private function check_php_compatibility() {
        echo "🐘 Checking PHP compatibility...\n";
        
        $php_version = PHP_VERSION;
        $required_php = '7.4';
        
        if (version_compare($php_version, $required_php, '<')) {
            $this->issues[] = "PHP version $php_version is below required version $required_php";
            $this->fixes[] = "Upgrade PHP to version $required_php or higher";
        } else {
            echo "✅ PHP version $php_version is compatible\n";
        }
        
        // Check required PHP extensions
        $required_extensions = ['json', 'curl', 'mbstring'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->issues[] = "Missing PHP extension: $ext";
                $this->fixes[] = "Install PHP $ext extension";
            } else {
                echo "✅ PHP extension $ext is loaded\n";
            }
        }
        
        echo "   Completed PHP compatibility check\n\n";
    }
    
    /**
     * Check WordPress compatibility
     */
    private function check_wordpress_compatibility() {
        echo "📝 Checking WordPress compatibility...\n";
        
        global $wp_version;
        $required_wp = '5.0';
        
        if (version_compare($wp_version, $required_wp, '<')) {
            $this->issues[] = "WordPress version $wp_version is below required version $required_wp";
            $this->fixes[] = "Upgrade WordPress to version $required_wp or higher";
        } else {
            echo "✅ WordPress version $wp_version is compatible\n";
        }
        
        // Check if REST API is working
        $rest_url = get_rest_url();
        if (empty($rest_url)) {
            $this->issues[] = "WordPress REST API is not available";
            $this->fixes[] = "Enable WordPress REST API";
        } else {
            echo "✅ WordPress REST API is available\n";
        }
        
        echo "   Completed WordPress compatibility check\n\n";
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        echo "🔐 Checking file permissions...\n";
        
        $directories = ['includes', 'assets', 'admin', 'public'];
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                if (!is_readable($dir)) {
                    $this->issues[] = "Directory $dir is not readable";
                    $this->fixes[] = "Set directory $dir permissions to 755";
                } else {
                    echo "✅ Directory $dir is readable\n";
                }
            }
        }
        
        // Check main plugin file
        if (file_exists('vortex-ai-engine.php')) {
            if (!is_readable('vortex-ai-engine.php')) {
                $this->issues[] = "Main plugin file is not readable";
                $this->fixes[] = "Set vortex-ai-engine.php permissions to 644";
            } else {
                echo "✅ Main plugin file is readable\n";
            }
        }
        
        echo "   Completed file permissions check\n\n";
    }
    
    /**
     * Check database tables
     */
    private function check_database_tables() {
        echo "🗄️ Checking database tables...\n";
        
        global $wpdb;
        
        // Check if Vortex tables exist
        $tables = [
            $wpdb->prefix . 'vortex_activity_logs',
            $wpdb->prefix . 'vortex_artist_journey',
            $wpdb->prefix . 'vortex_agreements'
        ];
        
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$exists) {
                echo "⚠️  Table $table does not exist (will be created on activation)\n";
            } else {
                echo "✅ Table $table exists\n";
            }
        }
        
        echo "   Completed database tables check\n\n";
    }
    
    /**
     * Display diagnostic results
     */
    private function display_results() {
        echo "📊 DIAGNOSTIC RESULTS\n";
        echo "====================\n\n";
        
        if (empty($this->issues)) {
            echo "✅ No issues detected! Plugin should activate normally.\n\n";
        } else {
            echo "❌ Issues found:\n";
            foreach ($this->issues as $issue) {
                echo "   • $issue\n";
            }
            echo "\n";
        }
    }
    
    /**
     * Suggest fixes
     */
    private function suggest_fixes() {
        if (!empty($this->fixes)) {
            echo "🔧 SUGGESTED FIXES\n";
            echo "==================\n\n";
            
            foreach ($this->fixes as $fix) {
                echo "   • $fix\n";
            }
            echo "\n";
            
            echo "🚀 QUICK FIX COMMANDS:\n";
            echo "======================\n\n";
            
            if (in_array("Temporarily deactivate WooCommerce Blocks plugin", $this->fixes)) {
                echo "1. Go to WordPress Admin → Plugins\n";
                echo "2. Deactivate 'WooCommerce Blocks'\n";
                echo "3. Try activating Vortex AI Engine\n";
                echo "4. Re-activate WooCommerce Blocks after Vortex is active\n\n";
            }
            
            echo "📞 If issues persist, check the WordPress debug log for more details.\n";
        }
    }
    
    /**
     * Attempt to fix WooCommerce conflicts
     */
    public function fix_woocommerce_conflicts() {
        echo "🔧 Attempting to fix WooCommerce conflicts...\n";
        
        // Deactivate WooCommerce Blocks temporarily
        if (is_plugin_active('woocommerce-blocks/woocommerce-blocks.php')) {
            deactivate_plugins('woocommerce-blocks/woocommerce-blocks.php');
            echo "✅ WooCommerce Blocks deactivated\n";
        }
        
        // Clear any cached integration registries
        if (class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
            // Clear any cached registrations
            wp_cache_flush();
            echo "✅ Cache cleared\n";
        }
        
        echo "🔄 Please try activating Vortex AI Engine now.\n";
        echo "   You can re-activate WooCommerce Blocks after Vortex is active.\n";
    }
}

// Run diagnostics if script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $debug = new Vortex_Activation_Debug();
    $debug->run_diagnostics();
    
    // Ask if user wants to attempt fixes
    echo "Would you like to attempt automatic fixes? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) === 'y') {
        $debug->fix_woocommerce_conflicts();
    }
} 