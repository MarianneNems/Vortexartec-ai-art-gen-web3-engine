<?php
/**
 * VORTEX AI Engine - Quick Log Checker
 * 
 * Simple command-line script to quickly check for errors
 * Run this from your browser or command line
 */

// Bootstrap WordPress if not already loaded
if (!defined('ABSPATH')) {
    if (file_exists(__DIR__ . '/../../../wp-load.php')) {
        require_once __DIR__ . '/../../../wp-load.php';
    } else {
        echo "❌ WordPress not found. Please place this file in: wp-content/plugins/vortex-ai-engine/\n";
        exit;
    }
}

// Security check
if (!current_user_can('activate_plugins')) {
    echo "❌ Insufficient permissions to run this script.\n";
    exit;
}

echo "🔍 VORTEX AI Engine - Quick Log Checker\n";
echo "=====================================\n\n";

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

// 5. Check Solana Integration
echo "\n⛓️ Solana Integration:\n";
if (class_exists('Vortex_Solana_Integration')) {
    echo "  ✅ Solana Integration: Available\n";
} else {
    echo "  ❌ Solana Integration: Not found\n";
    $errors_found++;
}

if (class_exists('Vortex_Tola_Token_Handler')) {
    echo "  ✅ TOLA Token Handler: Available\n";
} else {
    echo "  ❌ TOLA Token Handler: Not found\n";
    $errors_found++;
}

// 6. Check Database Tables
echo "\n🗄️ Database Tables:\n";
global $wpdb;
$required_tables = [
    $wpdb->prefix . 'vortex_solana_metrics' => 'Solana Metrics',
    $wpdb->prefix . 'vortex_solana_programs' => 'Solana Programs',
    $wpdb->prefix . 'vortex_solana_health' => 'Solana Health',
    $wpdb->prefix . 'vortex_tola_balances' => 'TOLA Balances',
    $wpdb->prefix . 'vortex_token_rewards' => 'Token Rewards'
];

foreach ($required_tables as $table => $description) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if ($exists) {
        echo "  ✅ {$description}: Exists\n";
    } else {
        echo "  ❌ {$description}: Missing\n";
        $errors_found++;
    }
}

// 7. Check Error Logs
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

// 8. Check PHP Configuration
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

// 9. Check WooCommerce Blocks Integration
echo "\n🔗 WooCommerce Blocks Integration:\n";
if (class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
    echo "  ✅ IntegrationRegistry: Available\n";
    
    // Check if there are integration conflicts
    $registry = Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    $reflection = new ReflectionClass($registry);
    
    if ($reflection->hasProperty('integrations')) {
        $integrations_property = $reflection->getProperty('integrations');
        $integrations_property->setAccessible(true);
        $integrations = $integrations_property->getValue($registry);
        
        if (empty($integrations)) {
            echo "  ✅ Integrations: Cleared (Good)\n";
        } else {
            echo "  ⚠️ Integrations: " . count($integrations) . " registered\n";
            $warnings_found++;
        }
    }
} else {
    echo "  ℹ️ WooCommerce Blocks: Not active\n";
}

// 10. Check File Permissions
echo "\n📁 File Permissions:\n";
$plugin_dir = WP_PLUGIN_DIR . '/vortex-ai-engine';
if (is_dir($plugin_dir)) {
    $perms = substr(sprintf('%o', fileperms($plugin_dir)), -4);
    if ($perms == '0755') {
        echo "  ✅ Plugin Directory: {$perms} (Correct)\n";
    } else {
        echo "  ⚠️ Plugin Directory: {$perms} (Should be 0755)\n";
        $warnings_found++;
    }
} else {
    echo "  ❌ Plugin Directory: Not found\n";
    $errors_found++;
}

// 11. Summary
echo "\n📊 SUMMARY:\n";
echo "=====================================\n";
echo "Errors found: {$errors_found}\n";
echo "Warnings found: {$warnings_found}\n";

if ($errors_found == 0) {
    echo "\n🎉 SUCCESS: All critical errors are fixed!\n";
    echo "Your VORTEX AI Engine is running properly.\n";
    
    if ($warnings_found == 0) {
        echo "✅ No warnings - system is optimal!\n";
    } else {
        echo "⚠️ Consider addressing {$warnings_found} warnings for optimal performance.\n";
    }
} else {
    echo "\n❌ ISSUES: There are {$errors_found} critical errors to fix.\n";
    echo "Please run the emergency fix: EMERGENCY-WOOCOMMERCE-FIX.php\n";
}

if ($warnings_found > 0) {
    echo "\n⚠️ WARNINGS: There are {$warnings_found} warnings to consider.\n";
}

echo "\n🔧 Available Tools:\n";
echo "  • EMERGENCY-WOOCOMMERCE-FIX.php - Fix activation issues\n";
echo "  • PRE-ACTIVATION-CHECKLIST.php - Comprehensive system check\n";
echo "  • vortex-debug-dashboard.php - Interactive debugging\n";
echo "  • comprehensive-audit.php - Full system audit\n";

echo "\n📅 Check completed at: " . current_time('Y-m-d H:i:s') . "\n";
?> 