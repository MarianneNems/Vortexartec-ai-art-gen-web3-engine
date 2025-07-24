<?php
/**
 * VORTEX AI ENGINE - PRE-ACTIVATION CHECKLIST
 * 
 * Comprehensive verification before plugin activation
 * Run this to ensure everything is properly configured
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
    <title>VORTEX AI Engine - Pre-Activation Checklist</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; margin: -30px -30px 30px -30px; border-radius: 10px 10px 0 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .checklist-item { background: #f8f9fa; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0; border-radius: 5px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #218838; }
        .button.warning { background: #ffc107; color: #000; }
        .button.danger { background: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .metric { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .metric-label { font-weight: bold; }
        .metric-value { font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ VORTEX AI Engine - Pre-Activation Checklist</h1>
            <p>Comprehensive verification before plugin activation</p>
        </div>

        <?php
        $checklist_results = [];
        $all_passed = true;
        
        // Function to check and log results
        function check_item($name, $condition, $description, $critical = false) {
            global $checklist_results, $all_passed;
            $passed = $condition;
            if (!$passed && $critical) {
                $all_passed = false;
            }
            $checklist_results[] = [
                'name' => $name,
                'passed' => $passed,
                'description' => $description,
                'critical' => $critical
            ];
            return $passed;
        }
        
        // 1. WordPress Configuration Check
        ?>
        <div class="checklist-item">
            <h3>🔧 WordPress Configuration</h3>
            
            <?php
            // Check wp-config.php settings
            check_item(
                'WP_DEBUG Enabled',
                defined('WP_DEBUG') && WP_DEBUG,
                'Debug mode is enabled for troubleshooting',
                true
            );
            
            check_item(
                'WP_DEBUG_LOG Enabled',
                defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
                'Debug logging is enabled',
                true
            );
            
            check_item(
                'Memory Limit',
                defined('WP_MEMORY_LIMIT') && (int)WP_MEMORY_LIMIT >= 256,
                'Memory limit is sufficient (512M recommended)',
                true
            );
            
            check_item(
                'SSL Admin',
                defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN,
                'SSL is enforced for admin access',
                false
            );
            
            check_item(
                'File Editing Disabled',
                defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
                'File editing is disabled for security',
                false
            );
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th>Status</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>WP_DEBUG</td>
                        <td><span class="status-<?php echo defined('WP_DEBUG') && WP_DEBUG ? 'ok' : 'error'; ?>">
                            <?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled'; ?>
                        </span></td>
                        <td><?php echo defined('WP_DEBUG') ? (WP_DEBUG ? 'true' : 'false') : 'Not defined'; ?></td>
                    </tr>
                    <tr>
                        <td>WP_DEBUG_LOG</td>
                        <td><span class="status-<?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ok' : 'error'; ?>">
                            <?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ Enabled' : '❌ Disabled'; ?>
                        </span></td>
                        <td><?php echo defined('WP_DEBUG_LOG') ? (WP_DEBUG_LOG ? 'true' : 'false') : 'Not defined'; ?></td>
                    </tr>
                    <tr>
                        <td>WP_MEMORY_LIMIT</td>
                        <td><span class="status-<?php echo defined('WP_MEMORY_LIMIT') && (int)WP_MEMORY_LIMIT >= 256 ? 'ok' : 'warning'; ?>">
                            <?php echo defined('WP_MEMORY_LIMIT') && (int)WP_MEMORY_LIMIT >= 256 ? '✅ Sufficient' : '⚠️ Low'; ?>
                        </span></td>
                        <td><?php echo defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : 'Not defined'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 2. Plugin Dependencies Check -->
        <div class="checklist-item">
            <h3>🔌 Plugin Dependencies</h3>
            
            <?php
            // Check required plugins
            check_item(
                'WooCommerce Active',
                is_plugin_active('woocommerce/woocommerce.php'),
                'WooCommerce is required for full functionality',
                false
            );
            
            check_item(
                'WooCommerce Blocks Active',
                is_plugin_active('woocommerce-blocks/woocommerce-blocks.php'),
                'WooCommerce Blocks integration',
                false
            );
            
            check_item(
                'Vortex AI Engine Not Active',
                !is_plugin_active('vortex-ai-engine/vortex-ai-engine.php'),
                'Plugin should not be active before this check',
                true
            );
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Plugin</th>
                        <th>Status</th>
                        <th>Version</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>WooCommerce</td>
                        <td><span class="status-<?php echo is_plugin_active('woocommerce/woocommerce.php') ? 'ok' : 'warning'; ?>">
                            <?php echo is_plugin_active('woocommerce/woocommerce.php') ? '✅ Active' : '⚠️ Inactive'; ?>
                        </span></td>
                        <td><?php echo is_plugin_active('woocommerce/woocommerce.php') ? get_plugin_data(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')['Version'] : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td>WooCommerce Blocks</td>
                        <td><span class="status-<?php echo is_plugin_active('woocommerce-blocks/woocommerce-blocks.php') ? 'ok' : 'warning'; ?>">
                            <?php echo is_plugin_active('woocommerce-blocks/woocommerce-blocks.php') ? '✅ Active' : '⚠️ Inactive'; ?>
                        </span></td>
                        <td><?php echo is_plugin_active('woocommerce-blocks/woocommerce-blocks.php') ? get_plugin_data(WP_PLUGIN_DIR . '/woocommerce-blocks/woocommerce-blocks.php')['Version'] : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td>Vortex AI Engine</td>
                        <td><span class="status-<?php echo !is_plugin_active('vortex-ai-engine/vortex-ai-engine.php') ? 'ok' : 'warning'; ?>">
                            <?php echo !is_plugin_active('vortex-ai-engine/vortex-ai-engine.php') ? '✅ Ready' : '⚠️ Already Active'; ?>
                        </span></td>
                        <td><?php echo file_exists(WP_PLUGIN_DIR . '/vortex-ai-engine/vortex-ai-engine.php') ? get_plugin_data(WP_PLUGIN_DIR . '/vortex-ai-engine/vortex-ai-engine.php')['Version'] : 'Not Found'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 3. File System Check -->
        <div class="checklist-item">
            <h3>📁 File System Check</h3>
            
            <?php
            // Check required files
            $required_files = [
                'vortex-ai-engine/vortex-ai-engine.php' => 'Main Plugin File',
                'vortex-ai-engine/includes/blockchain/class-vortex-solana-integration.php' => 'Solana Integration',
                'vortex-ai-engine/includes/blockchain/class-vortex-solana-database.php' => 'Solana Database',
                'vortex-ai-engine/admin/class-vortex-solana-dashboard.php' => 'Solana Dashboard',
                'vortex-ai-engine/assets/css/solana-dashboard.css' => 'Dashboard CSS',
                'vortex-ai-engine/assets/js/solana-dashboard.js' => 'Dashboard JS'
            ];
            
            foreach ($required_files as $file => $description) {
                $full_path = WP_PLUGIN_DIR . '/' . $file;
                check_item(
                    $description,
                    file_exists($full_path) && is_readable($full_path),
                    "File exists and is readable: $file",
                    true
                );
            }
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Status</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($required_files as $file => $description): ?>
                        <?php $full_path = WP_PLUGIN_DIR . '/' . $file; ?>
                        <tr>
                            <td><?php echo esc_html($description); ?></td>
                            <td><span class="status-<?php echo file_exists($full_path) && is_readable($full_path) ? 'ok' : 'error'; ?>">
                                <?php echo file_exists($full_path) && is_readable($full_path) ? '✅ Found' : '❌ Missing'; ?>
                            </span></td>
                            <td><?php echo file_exists($full_path) ? number_format(filesize($full_path)) . ' bytes' : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Database Check -->
        <div class="checklist-item">
            <h3>🗄️ Database Check</h3>
            
            <?php
            global $wpdb;
            
            // Check database connection
            check_item(
                'Database Connection',
                $wpdb->query("SELECT 1") !== false,
                'Database connection is working',
                true
            );
            
            // Check if tables exist (they shouldn't before activation)
            $tables_to_check = [
                $wpdb->prefix . 'vortex_solana_metrics',
                $wpdb->prefix . 'vortex_solana_programs',
                $wpdb->prefix . 'vortex_solana_health',
                $wpdb->prefix . 'vortex_tola_balances',
                $wpdb->prefix . 'vortex_token_rewards'
            ];
            
            foreach ($tables_to_check as $table) {
                check_item(
                    "Table $table",
                    $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table,
                    "Table should not exist before activation",
                    false
                );
            }
            ?>
            
            <div class="metric">
                <span class="metric-label">Database Connection</span>
                <span class="metric-value status-<?php echo $wpdb->query("SELECT 1") !== false ? 'ok' : 'error'; ?>">
                    <?php echo $wpdb->query("SELECT 1") !== false ? '✅ Working' : '❌ Failed'; ?>
                </span>
            </div>
            <div class="metric">
                <span class="metric-label">Database Name</span>
                <span class="metric-value"><?php echo DB_NAME; ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Table Prefix</span>
                <span class="metric-value"><?php echo $wpdb->prefix; ?></span>
            </div>
        </div>

        <!-- 5. System Requirements Check -->
        <div class="checklist-item">
            <h3>💻 System Requirements</h3>
            
            <?php
            // Check PHP version
            check_item(
                'PHP Version',
                version_compare(PHP_VERSION, '7.4', '>='),
                'PHP 7.4 or higher required',
                true
            );
            
            // Check WordPress version
            check_item(
                'WordPress Version',
                version_compare(get_bloginfo('version'), '5.0', '>='),
                'WordPress 5.0 or higher required',
                true
            );
            
            // Check memory limit
            $memory_limit = ini_get('memory_limit');
            $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
            check_item(
                'Memory Limit',
                $memory_limit_bytes >= 256 * 1024 * 1024, // 256MB
                'At least 256MB memory required',
                true
            );
            
            // Check max execution time
            $max_execution_time = ini_get('max_execution_time');
            check_item(
                'Max Execution Time',
                $max_execution_time >= 30 || $max_execution_time == 0,
                'At least 30 seconds or unlimited',
                false
            );
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Required</th>
                        <th>Current</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PHP Version</td>
                        <td>7.4+</td>
                        <td><?php echo PHP_VERSION; ?></td>
                        <td><span class="status-<?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'ok' : 'error'; ?>">
                            <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅ OK' : '❌ Too Low'; ?>
                        </span></td>
                    </tr>
                    <tr>
                        <td>WordPress Version</td>
                        <td>5.0+</td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                        <td><span class="status-<?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? 'ok' : 'error'; ?>">
                            <?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? '✅ OK' : '❌ Too Low'; ?>
                        </span></td>
                    </tr>
                    <tr>
                        <td>Memory Limit</td>
                        <td>256MB+</td>
                        <td><?php echo $memory_limit; ?></td>
                        <td><span class="status-<?php echo $memory_limit_bytes >= 256 * 1024 * 1024 ? 'ok' : 'warning'; ?>">
                            <?php echo $memory_limit_bytes >= 256 * 1024 * 1024 ? '✅ OK' : '⚠️ Low'; ?>
                        </span></td>
                    </tr>
                    <tr>
                        <td>Max Execution Time</td>
                        <td>30s+</td>
                        <td><?php echo $max_execution_time == 0 ? 'Unlimited' : $max_execution_time . 's'; ?></td>
                        <td><span class="status-<?php echo $max_execution_time >= 30 || $max_execution_time == 0 ? 'ok' : 'warning'; ?>">
                            <?php echo $max_execution_time >= 30 || $max_execution_time == 0 ? '✅ OK' : '⚠️ Low'; ?>
                        </span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 6. Security Check -->
        <div class="checklist-item">
            <h3>🔒 Security Check</h3>
            
            <?php
            // Check SSL
            check_item(
                'SSL Available',
                is_ssl() || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN),
                'SSL is available or enforced',
                false
            );
            
            // Check file permissions
            $plugin_dir = WP_PLUGIN_DIR . '/vortex-ai-engine';
            check_item(
                'Plugin Directory Permissions',
                is_dir($plugin_dir) && (fileperms($plugin_dir) & 0777) == 0755,
                'Plugin directory has correct permissions (755)',
                false
            );
            
            // Check if debug display is disabled
            check_item(
                'Debug Display Disabled',
                !defined('WP_DEBUG_DISPLAY') || !WP_DEBUG_DISPLAY,
                'Debug display is disabled for security',
                false
            );
            ?>
            
            <div class="metric">
                <span class="metric-label">SSL Status</span>
                <span class="metric-value status-<?php echo is_ssl() || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN) ? 'ok' : 'warning'; ?>">
                    <?php echo is_ssl() || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN) ? '✅ Secure' : '⚠️ HTTP'; ?>
                </span>
            </div>
            <div class="metric">
                <span class="metric-label">Plugin Directory Permissions</span>
                <span class="metric-value"><?php echo is_dir($plugin_dir) ? substr(sprintf('%o', fileperms($plugin_dir)), -4) : 'N/A'; ?></span>
            </div>
        </div>

        <!-- Summary -->
        <div class="<?php echo $all_passed ? 'success' : 'error'; ?>">
            <h3><?php echo $all_passed ? '🎉 All Critical Checks Passed!' : '❌ Critical Issues Found'; ?></h3>
            <p>
                <?php
                $passed_count = count(array_filter($checklist_results, function($item) { return $item['passed']; }));
                $total_count = count($checklist_results);
                $critical_passed = count(array_filter($checklist_results, function($item) { return $item['passed'] && $item['critical']; }));
                $critical_total = count(array_filter($checklist_results, function($item) { return $item['critical']; }));
                ?>
                <strong>Overall:</strong> <?php echo $passed_count; ?>/<?php echo $total_count; ?> checks passed<br>
                <strong>Critical:</strong> <?php echo $critical_passed; ?>/<?php echo $critical_total; ?> critical checks passed
            </p>
            
            <?php if ($all_passed): ?>
                <p>✅ Your system is ready for Vortex AI Engine activation!</p>
            <?php else: ?>
                <p>❌ Please fix the critical issues before activating the plugin.</p>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div style="text-align: center; margin-top: 30px;">
            <?php if ($all_passed): ?>
                <a href="<?php echo admin_url('plugins.php'); ?>" class="button">🚀 Activate Vortex AI Engine</a>
            <?php else: ?>
                <a href="<?php echo admin_url('admin.php?page=vortex-ai-engine'); ?>" class="button warning">🔧 Fix Issues</a>
            <?php endif; ?>
            <a href="<?php echo admin_url(); ?>" class="button">🏠 Go to WordPress Admin</a>
            <button onclick="location.reload();" class="button">🔄 Refresh Checklist</button>
        </div>

        <div style="margin-top: 20px; font-size: 12px; color: #666; text-align: center;">
            <p>Pre-activation checklist completed at: <?php echo current_time('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html> 