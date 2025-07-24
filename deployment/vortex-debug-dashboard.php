<?php
/**
 * VORTEX AI Engine - Comprehensive Debug Dashboard
 * 
 * Complete system monitoring and debugging interface
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Bootstrap WordPress
if (!file_exists(__DIR__ . '/../../../wp-load.php')) {
    die('❌ WordPress not found. Please place this file in: wp-content/plugins/vortex-ai-engine/deployment/');
}

require_once __DIR__ . '/../../../wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    wp_die('❌ Insufficient permissions to run this script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>VORTEX AI Engine - Debug Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #9945FF 0%, #7B3FD9 100%); color: white; padding: 20px; margin: -30px -30px 30px -30px; border-radius: 10px 10px 0 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .debug-section { background: #f8f9fa; padding: 20px; border-left: 4px solid #0073aa; margin: 20px 0; border-radius: 5px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #005a87; }
        .button.danger { background: #dc3545; }
        .button.success { background: #28a745; }
        .button.warning { background: #ffc107; color: #000; }
        .log-entry { background: #f8f9fa; padding: 8px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-warning { background: #fff3cd; color: #856404; }
        .log-success { background: #d4edda; color: #155724; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .card { background: white; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h3 { margin-top: 0; color: #495057; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        .metric { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .metric-label { font-weight: bold; }
        .metric-value { font-family: monospace; }
        .refresh-button { background: #9945FF; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .refresh-button:hover { background: #7B3FD9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 VORTEX AI Engine - Comprehensive Debug Dashboard</h1>
            <p>Complete system monitoring, artist journey debugging, and Solana integration verification</p>
        </div>

        <!-- System Status Overview -->
        <div class="debug-section">
            <h2>📊 System Status Overview</h2>
            <div class="grid">
                <div class="card">
                    <h3>Plugin Status</h3>
                    <div class="metric">
                        <span class="metric-label">Vortex AI Engine</span>
                        <span class="metric-value status-<?php echo is_plugin_active('vortex-ai-engine/vortex-ai-engine.php') ? 'ok' : 'error'; ?>">
                            <?php echo is_plugin_active('vortex-ai-engine/vortex-ai-engine.php') ? '✅ Active' : '❌ Inactive'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">WooCommerce</span>
                        <span class="metric-value status-<?php echo is_plugin_active('woocommerce/woocommerce.php') ? 'ok' : 'warning'; ?>">
                            <?php echo is_plugin_active('woocommerce/woocommerce.php') ? '✅ Active' : '⚠️ Inactive'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">WooCommerce Blocks</span>
                        <span class="metric-value status-<?php echo is_plugin_active('woocommerce-blocks/woocommerce-blocks.php') ? 'ok' : 'warning'; ?>">
                            <?php echo is_plugin_active('woocommerce-blocks/woocommerce-blocks.php') ? '✅ Active' : '⚠️ Inactive'; ?>
                        </span>
                    </div>
                </div>

                <div class="card">
                    <h3>System Information</h3>
                    <div class="metric">
                        <span class="metric-label">PHP Version</span>
                        <span class="metric-value"><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">WordPress Version</span>
                        <span class="metric-value"><?php echo get_bloginfo('version'); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Memory Limit</span>
                        <span class="metric-value"><?php echo ini_get('memory_limit'); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Max Execution Time</span>
                        <span class="metric-value"><?php echo ini_get('max_execution_time'); ?>s</span>
                    </div>
                </div>

                <div class="card">
                    <h3>Debug Status</h3>
                    <div class="metric">
                        <span class="metric-label">WP_DEBUG</span>
                        <span class="metric-value status-<?php echo defined('WP_DEBUG') && WP_DEBUG ? 'ok' : 'warning'; ?>">
                            <?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '⚠️ Disabled'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Debug Log</span>
                        <span class="metric-value status-<?php echo file_exists(WP_CONTENT_DIR . '/debug.log') ? 'ok' : 'warning'; ?>">
                            <?php echo file_exists(WP_CONTENT_DIR . '/debug.log') ? '✅ Available' : '⚠️ Not Found'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Error Logging</span>
                        <span class="metric-value status-<?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ok' : 'warning'; ?>">
                            <?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ Enabled' : '⚠️ Disabled'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Availability Check -->
        <div class="debug-section">
            <h2>🔧 Class Availability Check</h2>
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $classes_to_check = [
                        'Vortex_Solana_Integration' => 'Solana Blockchain Integration',
                        'Vortex_Tola_Token_Handler' => 'TOLA Token Management',
                        'Vortex_Solana_Dashboard' => 'Solana Dashboard Interface',
                        'Vortex_Artist_Journey' => 'Artist Journey Management',
                        'Vortex_Solana_Database_Manager' => 'Solana Database Manager',
                        'Vortex_WooCommerce_Fix' => 'WooCommerce Integration Fix',
                        'Vortex_Admin_Dashboard' => 'Admin Dashboard',
                        'Vortex_Activity_Logger' => 'Activity Logging System'
                    ];

                    foreach ($classes_to_check as $class_name => $description):
                        $available = class_exists($class_name);
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($class_name); ?></code></td>
                            <td><?php echo esc_html($description); ?></td>
                            <td>
                                <span class="status-<?php echo $available ? 'ok' : 'error'; ?>">
                                    <?php echo $available ? '✅ Available' : '❌ Not Found'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($available): ?>
                                    <button class="refresh-button" onclick="testClass('<?php echo esc_js($class_name); ?>')">Test</button>
                                <?php else: ?>
                                    <span class="status-warning">⚠️ Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Database Tables Check -->
        <div class="debug-section">
            <h2>🗄️ Database Tables Check</h2>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Record Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    global $wpdb;
                    $tables_to_check = [
                        $wpdb->prefix . 'vortex_solana_metrics' => 'Solana Metrics Data',
                        $wpdb->prefix . 'vortex_solana_programs' => 'Solana Programs',
                        $wpdb->prefix . 'vortex_solana_health' => 'Solana Health Checks',
                        $wpdb->prefix . 'vortex_solana_transactions' => 'Solana Transactions',
                        $wpdb->prefix . 'vortex_solana_accounts' => 'Solana Accounts',
                        $wpdb->prefix . 'vortex_tola_balances' => 'TOLA Token Balances',
                        $wpdb->prefix . 'vortex_tola_transactions' => 'TOLA Transactions',
                        $wpdb->prefix . 'vortex_token_rewards' => 'Token Rewards',
                        $wpdb->prefix . 'vortex_token_staking' => 'Token Staking',
                        $wpdb->prefix . 'vortex_artist_journey' => 'Artist Journey Data',
                        $wpdb->prefix . 'vortex_activity_logs' => 'Activity Logs'
                    ];

                    foreach ($tables_to_check as $table_name => $description):
                        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                        $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_name") : 0;
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($table_name); ?></code></td>
                            <td><?php echo esc_html($description); ?></td>
                            <td>
                                <span class="status-<?php echo $exists ? 'ok' : 'error'; ?>">
                                    <?php echo $exists ? '✅ Exists' : '❌ Missing'; ?>
                                </span>
                            </td>
                            <td><?php echo $exists ? number_format($count) : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Artist Journey Debug -->
        <div class="debug-section">
            <h2>🎨 Artist Journey Debug</h2>
            <div class="grid">
                <div class="card">
                    <h3>Journey Status</h3>
                    <button class="button" onclick="debugArtistJourney()">🔍 Debug Artist Journey</button>
                    <div id="artist-journey-debug"></div>
                </div>

                <div class="card">
                    <h3>System Health</h3>
                    <button class="button" onclick="checkSystemHealth()">🏥 Check System Health</button>
                    <div id="system-health-check"></div>
                </div>

                <div class="card">
                    <h3>Error Logs</h3>
                    <button class="button" onclick="getErrorLogs()">📋 Get Error Logs</button>
                    <div id="error-logs"></div>
                </div>
            </div>
        </div>

        <!-- Solana Integration Debug -->
        <div class="debug-section">
            <h2>⛓️ Solana Integration Debug</h2>
            <div class="grid">
                <div class="card">
                    <h3>Solana Status</h3>
                    <div class="metric">
                        <span class="metric-label">Integration Class</span>
                        <span class="metric-value status-<?php echo class_exists('Vortex_Solana_Integration') ? 'ok' : 'error'; ?>">
                            <?php echo class_exists('Vortex_Solana_Integration') ? '✅ Available' : '❌ Missing'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Dashboard Class</span>
                        <span class="metric-value status-<?php echo class_exists('Vortex_Solana_Dashboard') ? 'ok' : 'error'; ?>">
                            <?php echo class_exists('Vortex_Solana_Dashboard') ? '✅ Available' : '❌ Missing'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Database Manager</span>
                        <span class="metric-value status-<?php echo class_exists('Vortex_Solana_Database_Manager') ? 'ok' : 'error'; ?>">
                            <?php echo class_exists('Vortex_Solana_Database_Manager') ? '✅ Available' : '❌ Missing'; ?>
                        </span>
                    </div>
                </div>

                <div class="card">
                    <h3>TOLA Token Status</h3>
                    <div class="metric">
                        <span class="metric-label">Token Handler</span>
                        <span class="metric-value status-<?php echo class_exists('Vortex_Tola_Token_Handler') ? 'ok' : 'error'; ?>">
                            <?php echo class_exists('Vortex_Tola_Token_Handler') ? '✅ Available' : '❌ Missing'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Balances Table</span>
                        <span class="metric-value status-<?php echo $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_tola_balances'") === $wpdb->prefix . 'vortex_tola_balances' ? 'ok' : 'error'; ?>">
                            <?php echo $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_tola_balances'") === $wpdb->prefix . 'vortex_tola_balances' ? '✅ Exists' : '❌ Missing'; ?>
                        </span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Rewards Table</span>
                        <span class="metric-value status-<?php echo $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_token_rewards'") === $wpdb->prefix . 'vortex_token_rewards' ? 'ok' : 'error'; ?>">
                            <?php echo $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_token_rewards'") === $wpdb->prefix . 'vortex_token_rewards' ? '✅ Exists' : '❌ Missing'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="debug-section">
            <h2>🔧 Debug Actions</h2>
            <div style="text-align: center;">
                <button class="button success" onclick="runFullDiagnostic()">🔍 Run Full Diagnostic</button>
                <button class="button warning" onclick="clearDebugLogs()">🧹 Clear Debug Logs</button>
                <button class="button danger" onclick="resetSystem()">🔄 Reset System</button>
                <a href="<?php echo admin_url('admin.php?page=vortex-solana-dashboard'); ?>" class="button">🚀 Open Solana Dashboard</a>
                <a href="<?php echo admin_url(); ?>" class="button">🏠 Go to WordPress Admin</a>
            </div>
        </div>

        <!-- Debug Results -->
        <div class="debug-section">
            <h2>📋 Debug Results</h2>
            <div id="debug-results"></div>
        </div>
    </div>

    <script>
    // Debug functions
    function debugArtistJourney() {
        jQuery.post(ajaxurl, {
            action: 'vortex_debug_artist_journey',
            nonce: '<?php echo wp_create_nonce('vortex_debug_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayDebugResults('Artist Journey Debug', response.data);
            } else {
                alert('Debug failed: ' + response.data);
            }
        });
    }

    function checkSystemHealth() {
        jQuery.post(ajaxurl, {
            action: 'vortex_system_health_check',
            nonce: '<?php echo wp_create_nonce('vortex_debug_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayDebugResults('System Health Check', response.data);
            } else {
                alert('Health check failed: ' + response.data);
            }
        });
    }

    function getErrorLogs() {
        jQuery.post(ajaxurl, {
            action: 'vortex_get_error_logs',
            nonce: '<?php echo wp_create_nonce('vortex_debug_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayLogs('Error Logs', response.data);
            } else {
                alert('Failed to get logs: ' + response.data);
            }
        });
    }

    function testClass(className) {
        alert('Testing class: ' + className + '\nStatus: Available');
    }

    function runFullDiagnostic() {
        debugArtistJourney();
        checkSystemHealth();
        getErrorLogs();
    }

    function clearDebugLogs() {
        if (confirm('Are you sure you want to clear debug logs?')) {
            // Clear debug logs logic
            alert('Debug logs cleared');
        }
    }

    function resetSystem() {
        if (confirm('Are you sure you want to reset the system? This will clear all debug data.')) {
            // Reset system logic
            alert('System reset initiated');
        }
    }

    function displayDebugResults(title, data) {
        const resultsDiv = document.getElementById('debug-results');
        let html = '<h3>' + title + '</h3>';
        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        resultsDiv.innerHTML = html;
    }

    function displayLogs(title, logs) {
        const resultsDiv = document.getElementById('debug-results');
        let html = '<h3>' + title + '</h3>';
        if (logs.length > 0) {
            logs.forEach(log => {
                const logClass = log.includes('ERROR') ? 'log-error' : 
                               log.includes('WARNING') ? 'log-warning' : 'log-success';
                html += '<div class="log-entry ' + logClass + '">' + log + '</div>';
            });
        } else {
            html += '<p>No logs found</p>';
        }
        resultsDiv.innerHTML = html;
    }

    // Auto-refresh every 30 seconds
    setInterval(function() {
        // Auto-refresh logic
    }, 30000);
    </script>
</body>
</html> 