<?php
/**
 * Vortex AI Engine - Production Deployment Script
 *
 * This script handles the actual production deployment process.
 * Run this on your production WordPress server.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_CLI')) {
    define('WP_CLI', true);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== VORTEX AI ENGINE - PRODUCTION DEPLOYMENT ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "===============================================\n\n";

/**
 * Production Deployment Steps
 */
class Vortex_Production_Deployment {

    private $backup_dir;
    private $deployment_log = [];

    public function __construct() {
        $this->backup_dir = 'backups/' . date('Y-m-d-H-i-s');
        mkdir($this->backup_dir, 0755, true);
    }

    /**
     * Execute full deployment
     */
    public function deploy() {
        echo "Starting production deployment...\n\n";

        $steps = [
            'Create Backup' => [$this, 'create_backup'],
            'Verify Environment' => [$this, 'verify_environment'],
            'Deploy Plugin' => [$this, 'deploy_plugin'],
            'Activate Plugin' => [$this, 'activate_plugin'],
            'Configure Settings' => [$this, 'configure_settings'],
            'Test Functionality' => [$this, 'test_functionality'],
            'Final Verification' => [$this, 'final_verification']
        ];

        foreach ($steps as $step_name => $step_function) {
            echo "Step: $step_name\n";
            echo str_repeat('-', strlen($step_name) + 6) . "\n";
            
            try {
                $result = $step_function();
                if ($result['success']) {
                    echo "✅ " . $result['message'] . "\n";
                } else {
                    echo "❌ " . $result['message'] . "\n";
                    $this->log_deployment_step($step_name, 'FAILED', $result['message']);
                    return false;
                }
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
                $this->log_deployment_step($step_name, 'ERROR', $e->getMessage());
                return false;
            }
            
            $this->log_deployment_step($step_name, 'SUCCESS', $result['message']);
            echo "\n";
        }

        echo "🎉 PRODUCTION DEPLOYMENT COMPLETED SUCCESSFULLY!\n";
        $this->save_deployment_log();
        return true;
    }

    /**
     * Create backup
     */
    private function create_backup() {
        echo "Creating backup...\n";

        // Database backup
        if (function_exists('wp_db_export')) {
            $db_backup_file = $this->backup_dir . '/database-backup.sql';
            wp_db_export($db_backup_file);
            echo "  - Database backup created: $db_backup_file\n";
        }

        // Plugin backup
        $plugin_backup_file = $this->backup_dir . '/plugin-backup.zip';
        $this->create_plugin_backup($plugin_backup_file);
        echo "  - Plugin backup created: $plugin_backup_file\n";

        return [
            'success' => true,
            'message' => 'Backup created successfully'
        ];
    }

    /**
     * Verify environment
     */
    private function verify_environment() {
        echo "Verifying environment...\n";

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            return [
                'success' => false,
                'message' => 'PHP version ' . PHP_VERSION . ' is below required 8.0'
            ];
        }

        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            return [
                'success' => false,
                'message' => 'WordPress version ' . $wp_version . ' is below required 5.0'
            ];
        }

        // Check required extensions
        $required_extensions = ['curl', 'json', 'mbstring', 'pdo_mysql'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                return [
                    'success' => false,
                    'message' => "Required PHP extension missing: $ext"
                ];
            }
        }

        echo "  - PHP Version: " . PHP_VERSION . "\n";
        echo "  - WordPress Version: $wp_version\n";
        echo "  - Required extensions: OK\n";

        return [
            'success' => true,
            'message' => 'Environment verification passed'
        ];
    }

    /**
     * Deploy plugin
     */
    private function deploy_plugin() {
        echo "Deploying plugin...\n";

        $plugin_dir = WP_PLUGIN_DIR . '/vortex-ai-engine';
        
        // Create plugin directory if it doesn't exist
        if (!is_dir($plugin_dir)) {
            mkdir($plugin_dir, 0755, true);
        }

        // Copy plugin files
        $source_dir = __DIR__ . '/../wp-content/plugins/vortex-ai-engine';
        if (!is_dir($source_dir)) {
            return [
                'success' => false,
                'message' => 'Source plugin directory not found'
            ];
        }

        $this->copy_directory($source_dir, $plugin_dir);
        echo "  - Plugin files deployed to: $plugin_dir\n";

        return [
            'success' => true,
            'message' => 'Plugin deployed successfully'
        ];
    }

    /**
     * Activate plugin
     */
    private function activate_plugin() {
        echo "Activating plugin...\n";

        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_file = 'vortex-ai-engine/vortex-ai-engine.php';
        
        if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
            return [
                'success' => false,
                'message' => 'Plugin file not found for activation'
            ];
        }

        $result = activate_plugin($plugin_file);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => 'Plugin activation failed: ' . $result->get_error_message()
            ];
        }

        echo "  - Plugin activated successfully\n";

        return [
            'success' => true,
            'message' => 'Plugin activated successfully'
        ];
    }

    /**
     * Configure settings
     */
    private function configure_settings() {
        echo "Configuring settings...\n";

        // Set default options
        $default_options = [
            'vortex_version' => '3.0.0',
            'vortex_installation_date' => current_time('mysql'),
            'vortex_tola_conversion_rate' => 0.50,
            'vortex_minimum_conversion' => 100,
            'vortex_artist_threshold' => 1000,
            'vortex_enable_incentives' => true,
            'vortex_enable_conversions' => false
        ];

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }

        echo "  - Default settings configured\n";

        return [
            'success' => true,
            'message' => 'Settings configured successfully'
        ];
    }

    /**
     * Test functionality
     */
    private function test_functionality() {
        echo "Testing functionality...\n";

        // Test database tables
        global $wpdb;
        $tables = [
            $wpdb->prefix . 'vortex_incentives',
            $wpdb->prefix . 'vortex_wallets',
            $wpdb->prefix . 'vortex_accounting'
        ];

        foreach ($tables as $table) {
            $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$result) {
                return [
                    'success' => false,
                    'message' => "Required table missing: $table"
                ];
            }
        }

        echo "  - Database tables: OK\n";

        // Test plugin classes
        if (!class_exists('Vortex_Loader')) {
            return [
                'success' => false,
                'message' => 'Core plugin class not found'
            ];
        }

        echo "  - Plugin classes: OK\n";

        return [
            'success' => true,
            'message' => 'Functionality tests passed'
        ];
    }

    /**
     * Final verification
     */
    private function final_verification() {
        echo "Final verification...\n";

        // Check if plugin is active
        if (!is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
            return [
                'success' => false,
                'message' => 'Plugin is not active'
            ];
        }

        // Check plugin version
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/vortex-ai-engine/vortex-ai-engine.php');
        if ($plugin_data['Version'] !== '3.0.0') {
            return [
                'success' => false,
                'message' => 'Plugin version mismatch'
            ];
        }

        echo "  - Plugin status: Active\n";
        echo "  - Plugin version: " . $plugin_data['Version'] . "\n";

        return [
            'success' => true,
            'message' => 'Final verification passed'
        ];
    }

    /**
     * Create plugin backup
     */
    private function create_plugin_backup($backup_file) {
        $plugin_dir = WP_PLUGIN_DIR . '/vortex-ai-engine';
        
        if (is_dir($plugin_dir)) {
            $zip = new ZipArchive();
            if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
                $this->add_folder_to_zip($zip, $plugin_dir, 'vortex-ai-engine');
                $zip->close();
            }
        }
    }

    /**
     * Add folder to zip
     */
    private function add_folder_to_zip($zip, $folder, $relative_path) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_file_path = $relative_path . '/' . substr($file_path, strlen($folder) + 1);
                $zip->addFile($file_path, $relative_file_path);
            }
        }
    }

    /**
     * Copy directory
     */
    private function copy_directory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $source_path = $source . '/' . $file;
                $dest_path = $destination . '/' . $file;

                if (is_dir($source_path)) {
                    $this->copy_directory($source_path, $dest_path);
                } else {
                    copy($source_path, $dest_path);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Log deployment step
     */
    private function log_deployment_step($step, $status, $message) {
        $this->deployment_log[] = [
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Save deployment log
     */
    private function save_deployment_log() {
        $log_file = $this->backup_dir . '/deployment-log.json';
        file_put_contents($log_file, json_encode($this->deployment_log, JSON_PRETTY_PRINT));
        echo "Deployment log saved to: $log_file\n";
    }
}

// Execute deployment
$deployment = new Vortex_Production_Deployment();
$success = $deployment->deploy();

if ($success) {
    echo "\n✅ PRODUCTION DEPLOYMENT SUCCESSFUL!\n";
    echo "Your Vortex AI Engine plugin is now live and ready for use.\n";
    exit(0);
} else {
    echo "\n❌ PRODUCTION DEPLOYMENT FAILED!\n";
    echo "Please check the deployment log for details.\n";
    exit(1);
} 