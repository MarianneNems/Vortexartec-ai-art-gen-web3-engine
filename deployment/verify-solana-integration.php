<?php
/**
 * VORTEX AI Engine - Solana Integration Verification
 * 
 * Verifies that all Solana integration files are properly located and accessible
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
if (!current_user_can('activate_plugins')) {
    wp_die('❌ Insufficient permissions to run this script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>VORTEX AI Engine - Solana Integration Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #9945FF 0%, #7B3FD9 100%); color: white; padding: 20px; margin: -30px -30px 30px -30px; border-radius: 10px 10px 0 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .file-check { background: #f8f9fa; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0; }
        .file-path { font-family: monospace; background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 VORTEX AI Engine - Solana Integration Verification</h1>
            <p>Comprehensive verification of Solana blockchain integration files</p>
        </div>

        <?php
        $plugin_path = VORTEX_AI_ENGINE_PLUGIN_PATH;
        $verification_results = [];
        
        // Define required files and their expected locations
        $required_files = [
            // Core Solana integration files
            'includes/blockchain/class-vortex-solana-integration.php' => 'Solana Integration Class',
            'includes/blockchain/class-vortex-solana-database.php' => 'Solana Database Manager',
            'admin/class-vortex-solana-dashboard.php' => 'Solana Dashboard',
            
            // Assets
            'assets/css/solana-dashboard.css' => 'Solana Dashboard CSS',
            'assets/js/solana-dashboard.js' => 'Solana Dashboard JavaScript',
            
            // Deployment and setup
            'deployment/setup-solana-devnet.ps1' => 'Solana Devnet Setup Script',
            'deployment/fix-woocommerce-conflict.php' => 'WooCommerce Conflict Fix',
            'deployment/verify-solana-integration.php' => 'This Verification Script',
            
            // Documentation
            'SOLANA-INTEGRATION-GUIDE.md' => 'Solana Integration Guide',
            
            // WooCommerce fix
            'includes/class-vortex-woocommerce-fix.php' => 'WooCommerce Integration Fix'
        ];
        
        // Check each required file
        foreach ($required_files as $file_path => $description) {
            $full_path = $plugin_path . $file_path;
            $exists = file_exists($full_path);
            $readable = $exists ? is_readable($full_path) : false;
            $size = $exists ? filesize($full_path) : 0;
            
            $verification_results[] = [
                'file' => $file_path,
                'description' => $description,
                'exists' => $exists,
                'readable' => $readable,
                'size' => $size,
                'status' => $exists && $readable ? 'OK' : ($exists ? 'WARNING' : 'ERROR')
            ];
        }
        
        // Check class availability
        $class_checks = [
            'Vortex_Solana_Integration' => 'Solana Integration Class',
            'Vortex_Solana_Database_Manager' => 'Solana Database Manager',
            'Vortex_Solana_Dashboard' => 'Solana Dashboard Class',
            'Vortex_WooCommerce_Fix' => 'WooCommerce Fix Class'
        ];
        
        $class_results = [];
        foreach ($class_checks as $class_name => $description) {
            $available = class_exists($class_name);
            $class_results[] = [
                'class' => $class_name,
                'description' => $description,
                'available' => $available,
                'status' => $available ? 'OK' : 'ERROR'
            ];
        }
        
        // Check database tables
        global $wpdb;
        $table_checks = [
            $wpdb->prefix . 'vortex_solana_metrics' => 'Solana Metrics Table',
            $wpdb->prefix . 'vortex_solana_programs' => 'Solana Programs Table',
            $wpdb->prefix . 'vortex_solana_health' => 'Solana Health Table',
            $wpdb->prefix . 'vortex_solana_transactions' => 'Solana Transactions Table',
            $wpdb->prefix . 'vortex_solana_accounts' => 'Solana Accounts Table',
            $wpdb->prefix . 'vortex_tola_balances' => 'TOLA Balances Table',
            $wpdb->prefix . 'vortex_tola_transactions' => 'TOLA Transactions Table',
            $wpdb->prefix . 'vortex_token_rewards' => 'Token Rewards Table',
            $wpdb->prefix . 'vortex_token_staking' => 'Token Staking Table'
        ];
        
        $table_results = [];
        foreach ($table_checks as $table_name => $description) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            $table_results[] = [
                'table' => $table_name,
                'description' => $description,
                'exists' => $exists,
                'status' => $exists ? 'OK' : 'ERROR'
            ];
        }
        
        // Check WordPress integration
        $wp_integration_checks = [
            'Vortex AI Engine plugin active' => is_plugin_active('vortex-ai-engine/vortex-ai-engine.php'),
            'Solana dashboard menu accessible' => current_user_can('manage_options'),
            'AJAX endpoints registered' => has_action('wp_ajax_vortex_solana_refresh_metrics'),
            'Cron jobs scheduled' => wp_next_scheduled('vortex_solana_collect_metrics') !== false
        ];
        
        $wp_results = [];
        foreach ($wp_integration_checks as $check => $result) {
            $wp_results[] = [
                'check' => $check,
                'result' => $result,
                'status' => $result ? 'OK' : 'WARNING'
            ];
        }
        ?>

        <!-- File Verification Results -->
        <h2>📁 File Verification Results</h2>
        <table>
            <thead>
                <tr>
                    <th>File</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($verification_results as $result): ?>
                    <tr>
                        <td><span class="file-path"><?php echo esc_html($result['file']); ?></span></td>
                        <td><?php echo esc_html($result['description']); ?></td>
                        <td>
                            <span class="status-<?php echo strtolower($result['status']); ?>">
                                <?php echo $result['status']; ?>
                            </span>
                        </td>
                        <td><?php echo $result['size'] > 0 ? number_format($result['size']) . ' bytes' : 'N/A'; ?></td>
                        <td>
                            <?php if ($result['exists'] && $result['readable']): ?>
                                ✅ File exists and is readable
                            <?php elseif ($result['exists']): ?>
                                ⚠️ File exists but not readable
                            <?php else: ?>
                                ❌ File not found
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Class Availability Results -->
        <h2>🔧 Class Availability Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Description</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($class_results as $result): ?>
                    <tr>
                        <td><span class="file-path"><?php echo esc_html($result['class']); ?></span></td>
                        <td><?php echo esc_html($result['description']); ?></td>
                        <td>
                            <span class="status-<?php echo strtolower($result['status']); ?>">
                                <?php echo $result['available'] ? '✅ Available' : '❌ Not Found'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Database Table Results -->
        <h2>🗄️ Database Table Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Description</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table_results as $result): ?>
                    <tr>
                        <td><span class="file-path"><?php echo esc_html($result['table']); ?></span></td>
                        <td><?php echo esc_html($result['description']); ?></td>
                        <td>
                            <span class="status-<?php echo strtolower($result['status']); ?>">
                                <?php echo $result['exists'] ? '✅ Exists' : '❌ Missing'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- WordPress Integration Results -->
        <h2>🔗 WordPress Integration Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Integration Check</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wp_results as $result): ?>
                    <tr>
                        <td><?php echo esc_html($result['check']); ?></td>
                        <td>
                            <span class="status-<?php echo strtolower($result['status']); ?>">
                                <?php echo $result['result'] ? '✅ Working' : '⚠️ Not Working'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary -->
        <h2>📊 Verification Summary</h2>
        <?php
        $total_files = count($verification_results);
        $ok_files = count(array_filter($verification_results, function($r) { return $r['status'] === 'OK'; }));
        $error_files = count(array_filter($verification_results, function($r) { return $r['status'] === 'ERROR'; }));
        $warning_files = count(array_filter($verification_results, function($r) { return $r['status'] === 'WARNING'; }));
        
        $total_classes = count($class_results);
        $ok_classes = count(array_filter($class_results, function($r) { return $r['status'] === 'OK'; }));
        
        $total_tables = count($table_results);
        $ok_tables = count(array_filter($table_results, function($r) { return $r['status'] === 'OK'; }));
        
        $total_wp_checks = count($wp_results);
        $ok_wp_checks = count(array_filter($wp_results, function($r) { return $r['status'] === 'OK'; }));
        ?>
        
        <div class="info">
            <h3>Overall Status</h3>
            <p><strong>Files:</strong> <?php echo $ok_files; ?>/<?php echo $total_files; ?> OK, <?php echo $error_files; ?> errors, <?php echo $warning_files; ?> warnings</p>
            <p><strong>Classes:</strong> <?php echo $ok_classes; ?>/<?php echo $total_classes; ?> available</p>
            <p><strong>Database Tables:</strong> <?php echo $ok_tables; ?>/<?php echo $total_tables; ?> exist</p>
            <p><strong>WordPress Integration:</strong> <?php echo $ok_wp_checks; ?>/<?php echo $total_wp_checks; ?> working</p>
        </div>

        <?php if ($error_files > 0): ?>
            <div class="error">
                <h3>❌ Issues Found</h3>
                <p>Some files are missing or inaccessible. Please check the file paths and permissions.</p>
            </div>
        <?php endif; ?>

        <?php if ($warning_files > 0): ?>
            <div class="warning">
                <h3>⚠️ Warnings</h3>
                <p>Some files exist but may have permission issues.</p>
            </div>
        <?php endif; ?>

        <?php if ($error_files === 0 && $warning_files === 0): ?>
            <div class="success">
                <h3>✅ All Systems Operational</h3>
                <p>Solana integration is properly configured and ready to use!</p>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="<?php echo admin_url('admin.php?page=vortex-solana-dashboard'); ?>" class="button">🚀 Open Solana Dashboard</a>
            <a href="<?php echo admin_url(); ?>" class="button">🏠 Go to WordPress Admin</a>
            <button onclick="location.reload();" class="button">🔄 Refresh Verification</button>
        </div>

        <div style="margin-top: 20px; font-size: 12px; color: #666; text-align: center;">
            <p>Verification completed at: <?php echo current_time('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html> 