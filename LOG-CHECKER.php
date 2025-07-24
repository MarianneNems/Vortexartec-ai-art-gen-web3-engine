<?php
/**
 * VORTEX AI Engine - Log Checker & Error Verification
 * 
 * This script checks for common WordPress, WooCommerce, and VORTEX AI Engine errors
 * and provides a comprehensive status report.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, simulate basic checks
    echo "<h1>VORTEX AI Engine - Log Checker</h1>";
    echo "<p>This script should be run from within WordPress.</p>";
    echo "<p>Please access it through: yoursite.com/wp-content/plugins/vortex-ai-engine/LOG-CHECKER.php</p>";
    exit;
}

// Only allow admin access
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>VORTEX AI Engine - Log Checker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #0073aa; color: white; padding: 20px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .log-entry { background: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 4px solid #0073aa; font-family: monospace; font-size: 12px; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .button:hover { background: #005a87; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 VORTEX AI Engine - Log Checker & Error Verification</h1>
            <p>Comprehensive system status and error checking</p>
        </div>

        <?php
        // Initialize results array
        $results = array();
        $errors = array();
        $warnings = array();
        $successes = array();

        // 1. Check WordPress Configuration
        echo "<div class='section'>";
        echo "<h2>📋 WordPress Configuration Check</h2>";
        
        // Check wp-config.php settings
        $wp_debug = defined('WP_DEBUG') ? WP_DEBUG : false;
        $wp_debug_display = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : true;
        $wp_debug_log = defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false;
        
        if (!$wp_debug) {
            echo "<div class='status success'>✅ WP_DEBUG is disabled (good for production)</div>";
            $successes[] = "WP_DEBUG disabled";
        } else {
            echo "<div class='status warning'>⚠️ WP_DEBUG is enabled (development mode)</div>";
            $warnings[] = "WP_DEBUG enabled";
        }
        
        if (!$wp_debug_display) {
            echo "<div class='status success'>✅ WP_DEBUG_DISPLAY is disabled (good for production)</div>";
            $successes[] = "WP_DEBUG_DISPLAY disabled";
        } else {
            echo "<div class='status warning'>⚠️ WP_DEBUG_DISPLAY is enabled (shows errors on screen)</div>";
            $warnings[] = "WP_DEBUG_DISPLAY enabled";
        }
        
        echo "</div>";

        // 2. Check WooCommerce Configuration
        echo "<div class='section'>";
        echo "<h2>🛒 WooCommerce Configuration Check</h2>";
        
        $wc_debug = defined('WC_DEBUG') ? WC_DEBUG : false;
        $wc_debug_log = defined('WC_DEBUG_LOG') ? WC_DEBUG_LOG : false;
        $wc_debug_display = defined('WC_DEBUG_DISPLAY') ? WC_DEBUG_DISPLAY : false;
        
        if (!$wc_debug) {
            echo "<div class='status success'>✅ WC_DEBUG is disabled (WooCommerce notices suppressed)</div>";
            $successes[] = "WC_DEBUG disabled";
        } else {
            echo "<div class='status error'>❌ WC_DEBUG is enabled (WooCommerce notices active)</div>";
            $errors[] = "WC_DEBUG enabled";
        }
        
        if (!$wc_debug_display) {
            echo "<div class='status success'>✅ WC_DEBUG_DISPLAY is disabled (WooCommerce errors hidden)</div>";
            $successes[] = "WC_DEBUG_DISPLAY disabled";
        } else {
            echo "<div class='status error'>❌ WC_DEBUG_DISPLAY is enabled (WooCommerce errors visible)</div>";
            $errors[] = "WC_DEBUG_DISPLAY enabled";
        }
        
        echo "</div>";

        // 3. Check Redis Configuration
        echo "<div class='section'>";
        echo "<h2>🔴 Redis Configuration Check</h2>";
        
        $redis_host = defined('WP_REDIS_HOST') ? WP_REDIS_HOST : null;
        $redis_port = defined('WP_REDIS_PORT') ? WP_REDIS_PORT : null;
        $redis_disabled = defined('WP_REDIS_DISABLED') ? WP_REDIS_DISABLED : true;
        
        if ($redis_host && $redis_port && !$redis_disabled) {
            echo "<div class='status success'>✅ Redis configuration found: {$redis_host}:{$redis_port}</div>";
            $successes[] = "Redis configured";
        } else {
            echo "<div class='status info'>ℹ️ Redis not configured (optional for performance)</div>";
            $warnings[] = "Redis not configured";
        }
        
        echo "</div>";

        // 4. Check VORTEX AI Engine Plugin
        echo "<div class='section'>";
        echo "<h2>🌀 VORTEX AI Engine Plugin Check</h2>";
        
        if (class_exists('Vortex_AI_Engine')) {
            echo "<div class='status success'>✅ VORTEX AI Engine main class loaded</div>";
            $successes[] = "Vortex_AI_Engine class loaded";
        } else {
            echo "<div class='status error'>❌ VORTEX AI Engine main class not found</div>";
            $errors[] = "Vortex_AI_Engine class missing";
        }
        
        // Check if plugin is active
        if (is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
            echo "<div class='status success'>✅ VORTEX AI Engine plugin is active</div>";
            $successes[] = "Plugin active";
        } else {
            echo "<div class='status error'>❌ VORTEX AI Engine plugin is not active</div>";
            $errors[] = "Plugin not active";
        }
        
        echo "</div>";

        // 5. Check Error Logs
        echo "<div class='section'>";
        echo "<h2>📝 Error Log Check</h2>";
        
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            $log_lines = explode("\n", $log_content);
            $recent_errors = array_slice($log_lines, -20); // Last 20 lines
            
            echo "<div class='status info'>ℹ️ Found debug.log file</div>";
            echo "<h3>Recent Log Entries (Last 20):</h3>";
            
            foreach ($recent_errors as $line) {
                if (trim($line)) {
                    $class = 'log-entry';
                    if (strpos($line, 'ERROR') !== false || strpos($line, 'Fatal') !== false) {
                        $class = 'log-entry error';
                    } elseif (strpos($line, 'WARNING') !== false) {
                        $class = 'log-entry warning';
                    }
                    echo "<div class='{$class}'>" . htmlspecialchars($line) . "</div>";
                }
            }
        } else {
            echo "<div class='status success'>✅ No debug.log file found (good - no errors logged)</div>";
            $successes[] = "No debug log file";
        }
        
        echo "</div>";

        // 6. Check PHP Errors
        echo "<div class='section'>";
        echo "<h2>🐘 PHP Configuration Check</h2>";
        
        $php_version = PHP_VERSION;
        $memory_limit = ini_get('memory_limit');
        $max_execution_time = ini_get('max_execution_time');
        $upload_max_filesize = ini_get('upload_max_filesize');
        
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
        
        // PHP Version
        if (version_compare($php_version, '7.4', '>=')) {
            echo "<tr><td>PHP Version</td><td>{$php_version}</td><td>✅ Good</td></tr>";
            $successes[] = "PHP version {$php_version}";
        } else {
            echo "<tr><td>PHP Version</td><td>{$php_version}</td><td>❌ Too old (need 7.4+)</td></tr>";
            $errors[] = "PHP version too old";
        }
        
        // Memory Limit
        if (intval($memory_limit) >= 256) {
            echo "<tr><td>Memory Limit</td><td>{$memory_limit}</td><td>✅ Good</td></tr>";
            $successes[] = "Memory limit {$memory_limit}";
        } else {
            echo "<tr><td>Memory Limit</td><td>{$memory_limit}</td><td>⚠️ Low</td></tr>";
            $warnings[] = "Memory limit low";
        }
        
        // Max Execution Time
        if ($max_execution_time >= 300 || $max_execution_time == 0) {
            echo "<tr><td>Max Execution Time</td><td>{$max_execution_time}s</td><td>✅ Good</td></tr>";
            $successes[] = "Execution time {$max_execution_time}s";
        } else {
            echo "<tr><td>Max Execution Time</td><td>{$max_execution_time}s</td><td>⚠️ Low</td></tr>";
            $warnings[] = "Execution time low";
        }
        
        echo "</table>";
        echo "</div>";

        // 7. Summary
        echo "<div class='section'>";
        echo "<h2>📊 Summary</h2>";
        
        $total_checks = count($successes) + count($warnings) + count($errors);
        
        echo "<table>";
        echo "<tr><th>Status</th><th>Count</th><th>Percentage</th></tr>";
        echo "<tr><td>✅ Success</td><td>" . count($successes) . "</td><td>" . round((count($successes) / $total_checks) * 100, 1) . "%</td></tr>";
        echo "<tr><td>⚠️ Warnings</td><td>" . count($warnings) . "</td><td>" . round((count($warnings) / $total_checks) * 100, 1) . "%</td></tr>";
        echo "<tr><td>❌ Errors</td><td>" . count($errors) . "</td><td>" . round((count($errors) / $total_checks) * 100, 1) . "%</td></tr>";
        echo "</table>";
        
        if (empty($errors)) {
            echo "<div class='status success'>🎉 All critical errors are fixed! Your system is running properly.</div>";
        } else {
            echo "<div class='status error'>⚠️ There are " . count($errors) . " critical errors that need attention.</div>";
        }
        
        if (!empty($warnings)) {
            echo "<div class='status warning'>⚠️ There are " . count($warnings) . " warnings to consider.</div>";
        }
        
        echo "</div>";

        // 8. Action Buttons
        echo "<div class='section'>";
        echo "<h2>🔧 Actions</h2>";
        echo "<button class='button' onclick='location.reload()'>🔄 Refresh Check</button>";
        echo "<button class='button' onclick='window.print()'>🖨️ Print Report</button>";
        echo "<a href='" . admin_url('plugins.php') . "' class='button'>⚙️ Plugin Management</a>";
        echo "<a href='" . admin_url('admin.php?page=vortex-ai-engine') . "' class='button'>🌀 VORTEX Dashboard</a>";
        echo "</div>";
        ?>

    </div>
</body>
</html> 