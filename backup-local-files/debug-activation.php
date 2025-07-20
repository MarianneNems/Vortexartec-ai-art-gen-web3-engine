<?php
/**
 * Debug script to identify VORTEX AI Engine activation issues
 */

// Mock WordPress functions for testing
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Mock implementation
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        // Mock implementation
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
        // Mock implementation
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all') {
        // Mock implementation
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        // Mock implementation
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return '/wp-admin/' . $path;
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = '') {
        return '/wp-json/' . $path;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'mock_nonce_' . $action;
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

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "[ERROR] $message\n";
    }
}

// Define constants
define('VORTEX_AI_ENGINE_PLUGIN_DIR', __DIR__ . '/');
define('VORTEX_AI_ENGINE_PLUGIN_URL', 'http://localhost/wp-content/plugins/vortex-ai-engine/');
define('VORTEX_AI_ENGINE_VERSION', '2.1.0');

echo "=== VORTEX AI Engine Activation Debug ===\n\n";

// Test 1: Check if main plugin file loads
echo "1. Testing main plugin file...\n";
try {
    require_once VORTEX_AI_ENGINE_PLUGIN_DIR . 'vortex-ai-engine.php';
    echo "✓ Main plugin file loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Main plugin file failed to load: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check algorithm files
echo "\n2. Testing algorithm files...\n";
$algorithm_files = [
    'vault-secrets/algorithms/class-vortex-shortcodes.php',
    'vault-secrets/algorithms/class-vortex-agreements.php',
    'vault-secrets/algorithms/individual_agent_algorithms.php',
    'vault-secrets/algorithms/base_ai_orchestrator.php',
    'vault-secrets/algorithms/class-vortex-security.php',
    'vault-secrets/algorithms/vault_integration.php',
    'vault-secrets/algorithms/class-vortex-tier-manager.php',
];

foreach ($algorithm_files as $file) {
    $path = VORTEX_AI_ENGINE_PLUGIN_DIR . $file;
    if (file_exists($path)) {
        try {
            require_once $path;
            echo "✓ Loaded: $file\n";
        } catch (Exception $e) {
            echo "✗ Failed to load $file: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Missing: $file\n";
    }
}

// Test 3: Check if classes exist
echo "\n3. Testing class existence...\n";
$classes_to_check = [
    'VortexAIEngine_Shortcodes',
    'VortexAIEngine_Agreements',
    'VortexAIEngine_IndividualShortcodes',
    'VortexAIEngine_AIOrchestrator',
    'VortexAIEngine_Security',
    'VortexAIEngine_Vault',
    'VortexAIEngine_TierManager'
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "✓ Class exists: $class\n";
    } else {
        echo "✗ Class missing: $class\n";
    }
}

// Test 4: Try to instantiate classes
echo "\n4. Testing class instantiation...\n";
foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        try {
            if (method_exists($class, 'getInstance')) {
                $instance = $class::getInstance();
                echo "✓ Instantiated (singleton): $class\n";
            } else {
                $instance = new $class();
                echo "✓ Instantiated (new): $class\n";
            }
        } catch (Exception $e) {
            echo "✗ Failed to instantiate $class: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== Debug Complete ===\n"; 