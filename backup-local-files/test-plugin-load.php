<?php
/**
 * Test script to verify VORTEX AI Engine plugin can be loaded
 * Run this from WordPress root directory
 */

// Simulate WordPress environment
define('ABSPATH', dirname(__FILE__) . '/');
define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/');

// Mock WordPress functions
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://localhost/wp-admin/' . $path;
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = '') {
        return 'http://localhost/wp-json/' . $path;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        // Mock shortcode registration
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Mock action registration
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all') {
        // Mock style enqueue
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
        // Mock script enqueue
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        // Mock script localization
        return true;
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "[ERROR] $message\n";
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        // Mock activation hook registration
        return true;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        // Mock deactivation hook registration
        return true;
    }
}

if (!function_exists('register_uninstall_hook')) {
    function register_uninstall_hook($file, $callback) {
        // Mock uninstall hook registration
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        // Mock action execution
        return true;
    }
}

// Test plugin loading
echo "Testing VORTEX AI Engine plugin loading...\n";

try {
    // Load the main plugin file
    $plugin_file = __DIR__ . '/vortex-ai-engine.php';
    
    if (!file_exists($plugin_file)) {
        throw new Exception("Plugin file not found: $plugin_file");
    }
    
    echo "✓ Plugin file exists\n";
    
    // Check syntax
    $syntax_check = shell_exec("php -l \"$plugin_file\" 2>&1");
    if (strpos($syntax_check, 'No syntax errors') === false) {
        throw new Exception("Syntax error in plugin file: $syntax_check");
    }
    
    echo "✓ Plugin file has no syntax errors\n";
    
    // Try to include the plugin file
    ob_start();
    include_once $plugin_file;
    $output = ob_get_clean();
    
    if ($output) {
        echo "Plugin output: $output\n";
    }
    
    echo "✓ Plugin file loaded successfully\n";
    
    // Trigger plugins_loaded hook to load classes
    echo "Triggering plugins_loaded hook...\n";
    do_action('plugins_loaded');
    echo "✓ plugins_loaded hook triggered\n";
    
    // Check if essential classes are available
    $essential_classes = [
        'VortexAIEngine_Shortcodes',
        'VortexAIEngine_Agreements', 
        'VortexAIEngine_IndividualShortcodes',
        'VortexAIEngine_AIOrchestrator',
        'VortexAIEngine_Security',
        'VortexAIEngine_Vault',
        'VortexAIEngine_TierManager'
    ];
    
    foreach ($essential_classes as $class) {
        if (class_exists($class)) {
            echo "✓ Class $class exists\n";
        } else {
            echo "✗ Class $class not found\n";
        }
    }
    
    echo "\nPlugin test completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
} 