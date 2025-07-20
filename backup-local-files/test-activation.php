<?php
/**
 * VORTEX AI Engine - Activation Test Script
 * This script will help identify what's preventing the plugin from activating
 */

// Simulate WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

if (!defined('VORTEX_AI_ENGINE_VERSION')) {
    define('VORTEX_AI_ENGINE_VERSION', '2.1.0');
}

if (!defined('VORTEX_AI_ENGINE_PLUGIN_FILE')) {
    define('VORTEX_AI_ENGINE_PLUGIN_FILE', __FILE__);
    define('VORTEX_AI_ENGINE_PLUGIN_DIR', dirname(__FILE__) . '/');
    define('VORTEX_AI_ENGINE_PLUGIN_URL', 'http://localhost/wp-content/plugins/vortex-ai-engine/');
    define('VORTEX_AI_ENGINE_PLUGIN_BASENAME', 'vortex-ai-engine/vortex-ai-engine.php');
}

// Mock WordPress functions
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/vortex-ai-engine/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return 'vortex-ai-engine/vortex-ai-engine.php';
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        echo "✅ update_option called: $option = $value\n";
        return true;
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "📝 LOG: $message\n";
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        echo "✅ add_action called: $hook\n";
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        echo "✅ register_activation_hook called\n";
        try {
            $callback();
        } catch (Exception $e) {
            echo "❌ Activation hook error: " . $e->getMessage() . "\n";
        }
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        echo "✅ register_deactivation_hook called\n";
    }
}

if (!function_exists('register_uninstall_hook')) {
    function register_uninstall_hook($file, $callback) {
        echo "✅ register_uninstall_hook called\n";
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir) {
        echo "✅ wp_mkdir_p called: $dir\n";
        return true;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return [
            'basedir' => dirname(__FILE__) . '/uploads',
            'baseurl' => 'http://localhost/wp-content/uploads'
        ];
    }
}

if (!function_exists('current_time')) {
    function current_time($type = 'mysql') {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        return '5.0';
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('file_exists')) {
    function file_exists($path) {
        return \file_exists($path);
    }
}

if (!function_exists('file_put_contents')) {
    function file_put_contents($path, $data, $flags = 0) {
        return \file_put_contents($path, $data, $flags);
    }
}

if (!function_exists('memory_get_usage')) {
    function memory_get_usage($real_usage = false) {
        return 1024 * 1024; // 1MB
    }
}

if (!function_exists('memory_get_peak_usage')) {
    function memory_get_peak_usage($real_usage = false) {
        return 2 * 1024 * 1024; // 2MB
    }
}

if (!function_exists('microtime')) {
    function microtime($as_float = false) {
        return $as_float ? \microtime(true) : \microtime();
    }
}

if (!function_exists('debug_backtrace')) {
    function debug_backtrace($options = DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit = 0) {
        return [];
    }
}

if (!defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
    define('DEBUG_BACKTRACE_IGNORE_ARGS', 1);
}

// Mock global variables
$GLOBALS['wpdb'] = new stdClass();
$GLOBALS['wpdb']->prefix = 'wp_';
$GLOBALS['wpdb']->suppress_errors = function($suppress = null) {
    return true;
};
$GLOBALS['wpdb']->get_var = function($query) {
    return '1';
};
$GLOBALS['wpdb']->get_charset_collate = function() {
    return 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
};
$GLOBALS['wpdb']->insert = function($table, $data, $format = null) {
    echo "✅ Database insert: $table\n";
    return true;
};
$GLOBALS['wpdb']->query = function($query) {
    echo "✅ Database query: " . substr($query, 0, 50) . "...\n";
    return true;
};
$GLOBALS['wpdb']->prepare = function($query, ...$args) {
    return $query;
};

// Test database connection function
function vortex_ai_engine_check_database_connection() {
    global $wpdb;
    $wpdb->suppress_errors();
    $result = $wpdb->get_var("SELECT 1");
    $wpdb->suppress_errors(false);
    return $result === '1';
}

echo "🚀 Starting VORTEX AI Engine Activation Test\n";
echo "============================================\n\n";

// Test 1: Check if main plugin file exists
echo "1. Testing main plugin file...\n";
if (file_exists('vortex-ai-engine.php')) {
    echo "✅ Main plugin file exists\n";
} else {
    echo "❌ Main plugin file not found\n";
    exit(1);
}

// Test 2: Check required files
echo "\n2. Testing required files...\n";
$required_files = [
    'includes/class-vortex-db-setup.php',
    'includes/class-vortex-logger.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

// Test 3: Test database connection
echo "\n3. Testing database connection...\n";
if (vortex_ai_engine_check_database_connection()) {
    echo "✅ Database connection successful\n";
} else {
    echo "❌ Database connection failed\n";
}

// Test 4: Load and test logger
echo "\n4. Testing logger initialization...\n";
try {
    require_once 'includes/class-vortex-db-setup.php';
    require_once 'includes/class-vortex-logger.php';
    
    if (class_exists('VortexAIEngine_Logger')) {
        echo "✅ Logger class loaded successfully\n";
        $logger = VortexAIEngine_Logger::getInstance();
        echo "✅ Logger instance created\n";
    } else {
        echo "❌ Logger class not found\n";
    }
} catch (Exception $e) {
    echo "❌ Logger error: " . $e->getMessage() . "\n";
}

// Test 5: Test activation hook
echo "\n5. Testing activation hook...\n";
try {
    // Include the main plugin file
    ob_start();
    include 'vortex-ai-engine.php';
    $output = ob_get_clean();
    
    if (strpos($output, 'error') !== false) {
        echo "❌ Activation hook produced errors\n";
        echo $output;
    } else {
        echo "✅ Activation hook completed successfully\n";
    }
} catch (Exception $e) {
    echo "❌ Activation hook exception: " . $e->getMessage() . "\n";
}

// Test 6: Check for syntax errors
echo "\n6. Testing for syntax errors...\n";
$files_to_check = [
    'vortex-ai-engine.php',
    'includes/class-vortex-db-setup.php',
    'includes/class-vortex-logger.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $file - No syntax errors\n";
        } else {
            echo "❌ $file - Syntax errors found:\n";
            echo $output . "\n";
        }
    }
}

echo "\n🎯 Activation Test Complete!\n";
echo "Check the output above for any ❌ errors that might be preventing activation.\n"; 