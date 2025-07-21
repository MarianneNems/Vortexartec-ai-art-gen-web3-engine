<?php
/**
 * VORTEX AI Engine - Quick Log Checker
 * 
 * Simple command-line script to quickly check for errors
 * Run this from your browser or command line
 */

echo "🔍 VORTEX AI Engine - Quick Log Checker\n";
echo "=====================================\n\n";

// Check if we're in WordPress
if (!defined('ABSPATH')) {
    echo "❌ This script must be run from within WordPress\n";
    echo "Access via: yoursite.com/wp-content/plugins/vortex-ai-engine/QUICK-LOG-CHECK.php\n";
    exit;
}

$errors_found = 0;
$warnings_found = 0;

// 1. Check WordPress Debug Settings
echo "📋 WordPress Configuration:\n";
$wp_debug = defined('WP_DEBUG') ? WP_DEBUG : false;
$wp_debug_display = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : true;

if (!$wp_debug) {
    echo "  ✅ WP_DEBUG: Disabled (Good)\n";
} else {
    echo "  ⚠️ WP_DEBUG: Enabled (Development mode)\n";
    $warnings_found++;
}

if (!$wp_debug_display) {
    echo "  ✅ WP_DEBUG_DISPLAY: Disabled (Good)\n";
} else {
    echo "  ⚠️ WP_DEBUG_DISPLAY: Enabled (Shows errors)\n";
    $warnings_found++;
}

// 2. Check WooCommerce Debug Settings
echo "\n🛒 WooCommerce Configuration:\n";
$wc_debug = defined('WC_DEBUG') ? WC_DEBUG : false;
$wc_debug_display = defined('WC_DEBUG_DISPLAY') ? WC_DEBUG_DISPLAY : false;

if (!$wc_debug) {
    echo "  ✅ WC_DEBUG: Disabled (WooCommerce notices suppressed)\n";
} else {
    echo "  ❌ WC_DEBUG: Enabled (WooCommerce notices active)\n";
    $errors_found++;
}

if (!$wc_debug_display) {
    echo "  ✅ WC_DEBUG_DISPLAY: Disabled (WooCommerce errors hidden)\n";
} else {
    echo "  ❌ WC_DEBUG_DISPLAY: Enabled (WooCommerce errors visible)\n";
    $errors_found++;
}

// 3. Check Redis Configuration
echo "\n🔴 Redis Configuration:\n";
$redis_host = defined('WP_REDIS_HOST') ? WP_REDIS_HOST : null;
$redis_port = defined('WP_REDIS_PORT') ? WP_REDIS_PORT : null;
$redis_disabled = defined('WP_REDIS_DISABLED') ? WP_REDIS_DISABLED : true;

if ($redis_host && $redis_port && !$redis_disabled) {
    echo "  ✅ Redis: Configured ({$redis_host}:{$redis_port})\n";
} else {
    echo "  ℹ️ Redis: Not configured (Optional)\n";
}

// 4. Check VORTEX AI Engine Plugin
echo "\n🌀 VORTEX AI Engine Plugin:\n";
if (class_exists('Vortex_AI_Engine')) {
    echo "  ✅ Main class: Loaded\n";
} else {
    echo "  ❌ Main class: Not found\n";
    $errors_found++;
}

if (is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
    echo "  ✅ Plugin: Active\n";
} else {
    echo "  ❌ Plugin: Not active\n";
    $errors_found++;
}

// 5. Check Error Logs
echo "\n📝 Error Logs:\n";
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);
    $recent_errors = array_slice($log_lines, -10); // Last 10 lines
    
    echo "  ℹ️ Found debug.log file\n";
    echo "  Recent entries:\n";
    
    foreach ($recent_errors as $line) {
        if (trim($line)) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'Fatal') !== false) {
                echo "    ❌ " . htmlspecialchars($line) . "\n";
                $errors_found++;
            } elseif (strpos($line, 'WARNING') !== false) {
                echo "    ⚠️ " . htmlspecialchars($line) . "\n";
                $warnings_found++;
            } else {
                echo "    ℹ️ " . htmlspecialchars($line) . "\n";
            }
        }
    }
} else {
    echo "  ✅ No debug.log file found (Good - no errors)\n";
}

// 6. Check PHP Configuration
echo "\n🐘 PHP Configuration:\n";
$php_version = PHP_VERSION;
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');

if (version_compare($php_version, '7.4', '>=')) {
    echo "  ✅ PHP Version: {$php_version}\n";
} else {
    echo "  ❌ PHP Version: {$php_version} (Need 7.4+)\n";
    $errors_found++;
}

if (intval($memory_limit) >= 256) {
    echo "  ✅ Memory Limit: {$memory_limit}\n";
} else {
    echo "  ⚠️ Memory Limit: {$memory_limit} (Low)\n";
    $warnings_found++;
}

if ($max_execution_time >= 300 || $max_execution_time == 0) {
    echo "  ✅ Max Execution Time: {$max_execution_time}s\n";
} else {
    echo "  ⚠️ Max Execution Time: {$max_execution_time}s (Low)\n";
    $warnings_found++;
}

// 7. Summary
echo "\n📊 SUMMARY:\n";
echo "=====================================\n";
echo "Errors found: {$errors_found}\n";
echo "Warnings found: {$warnings_found}\n";

if ($errors_found == 0) {
    echo "\n🎉 SUCCESS: All critical errors are fixed!\n";
    echo "Your VORTEX AI Engine is running properly.\n";
} else {
    echo "\n❌ ISSUES: There are {$errors_found} critical errors to fix.\n";
}

if ($warnings_found > 0) {
    echo "\n⚠️ WARNINGS: There are {$warnings_found} warnings to consider.\n";
}

echo "\nFor detailed analysis, run: LOG-CHECKER.php\n";
?> 