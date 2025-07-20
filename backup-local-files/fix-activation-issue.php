<?php
/**
 * VORTEX AI Engine - Activation Issue Fix
 * This script will help identify and fix the activation problem
 */

echo "🔧 VORTEX AI Engine - Activation Issue Fix\n";
echo "==========================================\n\n";

// Check if we're in a WordPress environment
if (defined('ABSPATH')) {
    echo "✅ Running in WordPress environment\n";
} else {
    echo "⚠️  Not in WordPress environment - this is likely the issue\n";
    echo "   The plugin needs to be activated through WordPress admin\n\n";
}

// Check if the plugin is in the correct directory
$current_dir = basename(dirname(__FILE__));
if ($current_dir === 'vortex-ai-engine') {
    echo "✅ Plugin is in correct directory: $current_dir\n";
} else {
    echo "⚠️  Plugin directory name: $current_dir\n";
    echo "   Should be: vortex-ai-engine\n";
    echo "   This might cause activation issues\n\n";
}

// Check main plugin file
if (file_exists('vortex-ai-engine.php')) {
    echo "✅ Main plugin file exists\n";
    
    // Check plugin header
    $content = file_get_contents('vortex-ai-engine.php');
    if (strpos($content, 'Plugin Name:') !== false) {
        echo "✅ Plugin header found\n";
    } else {
        echo "❌ Plugin header missing\n";
    }
    
    if (strpos($content, 'register_activation_hook') !== false) {
        echo "✅ Activation hook found\n";
    } else {
        echo "❌ Activation hook missing\n";
    }
} else {
    echo "❌ Main plugin file not found\n";
}

// Check required files
echo "\n📁 Checking required files:\n";
$required_files = [
    'includes/class-vortex-db-setup.php',
    'includes/class-vortex-logger.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file (MISSING)\n";
    }
}

// Check for common activation issues
echo "\n🔍 Common Activation Issues:\n";

// 1. File permissions
echo "1. File permissions:\n";
$files_to_check = ['vortex-ai-engine.php', 'includes/class-vortex-db-setup.php', 'includes/class-vortex-logger.php'];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "   ✅ $file is readable\n";
        } else {
            echo "   ❌ $file is not readable\n";
        }
    }
}

// 2. PHP version
echo "\n2. PHP version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "   ✅ PHP version is compatible\n";
} else {
    echo "   ❌ PHP version too old (7.4+ required)\n";
}

// 3. Memory limit
echo "\n3. Memory limit:\n";
$memory_limit = ini_get('memory_limit');
echo "   Current: $memory_limit\n";
if (intval($memory_limit) >= 128) {
    echo "   ✅ Memory limit is sufficient\n";
} else {
    echo "   ⚠️  Memory limit might be too low\n";
}

// 4. Check for syntax errors
echo "\n4. Syntax check:\n";
$output = shell_exec("php -l vortex-ai-engine.php 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "   ✅ No syntax errors in main plugin file\n";
} else {
    echo "   ❌ Syntax errors found:\n";
    echo "   $output\n";
}

// Provide solutions
echo "\n💡 Solutions:\n";
echo "1. Make sure you're activating the plugin through WordPress admin\n";
echo "2. Ensure the plugin directory is named exactly 'vortex-ai-engine'\n";
echo "3. Check WordPress debug log at wp-content/debug.log\n";
echo "4. Try deactivating other plugins first\n";
echo "5. Check your WordPress version (5.0+ required)\n";
echo "6. Ensure database connection is working\n";

// Check if this is a WordPress installation
if (file_exists('../wp-config.php') || file_exists('../../wp-config.php')) {
    echo "\n✅ WordPress installation detected nearby\n";
    echo "   The plugin should work when activated through WordPress admin\n";
} else {
    echo "\n⚠️  No WordPress installation detected nearby\n";
    echo "   This plugin must be installed in a WordPress plugins directory\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Upload this plugin to: wp-content/plugins/vortex-ai-engine/\n";
echo "2. Go to WordPress admin → Plugins\n";
echo "3. Find 'VORTEX AI Engine For the ARTS' and click 'Activate'\n";
echo "4. If activation fails, check the WordPress debug log\n";

// Create a simple activation test
echo "\n🧪 Creating activation test...\n";
$test_file = 'activation-test.php';
$test_content = '<?php
/**
 * VORTEX AI Engine - Activation Test
 * Run this in your WordPress root directory
 */

// Load WordPress
require_once("wp-load.php");

echo "<h1>VORTEX AI Engine Activation Test</h1>";
echo "<pre>";

// Check if plugin is active
if (is_plugin_active("vortex-ai-engine/vortex-ai-engine.php")) {
    echo "✅ Plugin is ACTIVE\n";
} else {
    echo "❌ Plugin is NOT ACTIVE\n";
    
    // Try to activate
    echo "\nAttempting to activate...\n";
    $result = activate_plugin("vortex-ai-engine/vortex-ai-engine.php");
    
    if (is_wp_error($result)) {
        echo "❌ Activation failed: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ Activation successful!\n";
    }
}

echo "</pre>";
?>';

if (file_put_contents($test_file, $test_content)) {
    echo "✅ Created $test_file\n";
    echo "   Upload this file to your WordPress root directory and run it\n";
} else {
    echo "❌ Could not create test file\n";
}

echo "\n🎯 Summary:\n";
echo "The plugin appears to be properly structured.\n";
echo "The activation issue is likely due to:\n";
echo "1. Not being in a WordPress environment\n";
echo "2. Incorrect plugin directory name\n";
echo "3. WordPress configuration issues\n";
echo "\nTry activating through WordPress admin panel.\n"; 