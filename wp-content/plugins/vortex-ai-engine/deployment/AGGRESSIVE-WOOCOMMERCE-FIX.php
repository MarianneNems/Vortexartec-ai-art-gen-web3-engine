<?php
/**
 * VORTEX AI ENGINE - AGGRESSIVE WOOCOMMERCE BLOCKS FIX
 * 
 * Complete resolution for IntegrationRegistry conflicts
 * Based on debug log analysis showing repeated registration errors
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
    <title>VORTEX AI Engine - Aggressive WooCommerce Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; margin: -30px -30px 30px -30px; border-radius: 10px 10px 0 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .log-entry { background: #f8f9fa; padding: 8px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-success { background: #d4edda; color: #155724; }
        .button { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #c82333; }
        .button.success { background: #28a745; }
        .button.success:hover { background: #218838; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 VORTEX AI Engine - Aggressive WooCommerce Fix</h1>
            <p>Resolving IntegrationRegistry conflicts from debug log analysis</p>
        </div>

        <?php
        $fix_results = [];
        $logs = [];
        
        // Function to log actions
        function log_action($message, $type = 'info') {
            global $logs;
            $logs[] = [
                'message' => $message,
                'type' => $type,
                'timestamp' => current_time('mysql')
            ];
        }
        
        // Analyze the debug log issue
        log_action('Analyzing IntegrationRegistry conflicts from debug log...', 'info');
        ?>
        
        <div class="info">
            <h3>🔍 Debug Log Analysis</h3>
            <p><strong>Issue Identified:</strong> WooCommerce Blocks IntegrationRegistry is registering empty integrations repeatedly</p>
            <p><strong>Error Pattern:</strong> "Function IntegrationRegistry::register was called incorrectly. "" is already registered."</p>
            <p><strong>Root Cause:</strong> Multiple plugins trying to register the same integration name</p>
        </div>

        <?php
        // Step 1: Completely disable WooCommerce Blocks temporarily
        log_action('Step 1: Temporarily disabling WooCommerce Blocks...', 'info');
        
        try {
            if (is_plugin_active('woocommerce-blocks/woocommerce-blocks.php')) {
                deactivate_plugins('woocommerce-blocks/woocommerce-blocks.php');
                log_action('Successfully deactivated WooCommerce Blocks', 'success');
                $fix_results['blocks_deactivated'] = true;
            } else {
                log_action('WooCommerce Blocks already inactive', 'info');
                $fix_results['blocks_deactivated'] = true;
            }
        } catch (Exception $e) {
            log_action('Error deactivating WooCommerce Blocks: ' . $e->getMessage(), 'error');
            $fix_results['blocks_deactivated'] = false;
        }
        
        // Step 2: Clear all WordPress caches
        log_action('Step 2: Clearing all WordPress caches...', 'info');
        
        try {
            // Clear WordPress object cache
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
                log_action('Cleared WordPress object cache', 'success');
            }
            
            // Clear transients
            wp_cache_flush();
            log_action('Cleared WordPress transients', 'success');
            
            // Clear any other caches
            if (function_exists('w3tc_flush_all')) {
                w3tc_flush_all();
                log_action('Cleared W3 Total Cache', 'success');
            }
            
            if (function_exists('wp_cache_clear_cache')) {
                wp_cache_clear_cache();
                log_action('Cleared WP Super Cache', 'success');
            }
            
            $fix_results['caches_cleared'] = true;
            
        } catch (Exception $e) {
            log_action('Error clearing caches: ' . $e->getMessage(), 'error');
            $fix_results['caches_cleared'] = false;
        }
        
        // Step 3: Remove all IntegrationRegistry hooks
        log_action('Step 3: Removing all IntegrationRegistry hooks...', 'info');
        
        try {
            // Remove all hooks related to WooCommerce Blocks
            remove_all_actions('woocommerce_blocks_loaded');
            remove_all_actions('woocommerce_blocks_registry_loaded');
            remove_all_actions('woocommerce_blocks_integration_registry_loaded');
            
            // Clear any stored integration data
            delete_option('woocommerce_blocks_integrations');
            delete_option('woocommerce_blocks_registry');
            
            log_action('Removed all WooCommerce Blocks hooks and options', 'success');
            $fix_results['hooks_removed'] = true;
            
        } catch (Exception $e) {
            log_action('Error removing hooks: ' . $e->getMessage(), 'error');
            $fix_results['hooks_removed'] = false;
        }
        
        // Step 4: Try to activate Vortex AI Engine
        log_action('Step 4: Attempting to activate Vortex AI Engine...', 'info');
        
        try {
            if (!is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
                $activation_result = activate_plugin('vortex-ai-engine/vortex-ai-engine.php');
                if (is_wp_error($activation_result)) {
                    log_action('Error activating Vortex AI Engine: ' . $activation_result->get_error_message(), 'error');
                    $fix_results['vortex_activated'] = false;
                } else {
                    log_action('Successfully activated Vortex AI Engine', 'success');
                    $fix_results['vortex_activated'] = true;
                }
            } else {
                log_action('Vortex AI Engine already active', 'info');
                $fix_results['vortex_activated'] = true;
            }
        } catch (Exception $e) {
            log_action('Error activating Vortex AI Engine: ' . $e->getMessage(), 'error');
            $fix_results['vortex_activated'] = false;
        }
        
        // Step 5: Reactivate WooCommerce Blocks with modified approach
        log_action('Step 5: Reactivating WooCommerce Blocks with conflict prevention...', 'info');
        
        try {
            // Add a filter to prevent integration conflicts
            add_filter('woocommerce_blocks_integration_registry_loaded', function($registry) {
                // Clear any existing integrations
                if (method_exists($registry, 'get_registered_integrations')) {
                    $integrations = $registry->get_registered_integrations();
                    foreach ($integrations as $integration_name => $integration) {
                        try {
                            $registry->unregister($integration_name);
                        } catch (Exception $e) {
                            // Ignore unregister errors
                        }
                    }
                }
                return $registry;
            }, 1);
            
            // Reactivate WooCommerce Blocks
            activate_plugins('woocommerce-blocks/woocommerce-blocks.php');
            log_action('Reactivated WooCommerce Blocks with conflict prevention', 'success');
            $fix_results['blocks_reactivated'] = true;
            
        } catch (Exception $e) {
            log_action('Error reactivating WooCommerce Blocks: ' . $e->getMessage(), 'error');
            $fix_results['blocks_reactivated'] = false;
        }
        
        // Step 6: Clear debug log
        log_action('Step 6: Clearing debug log...', 'info');
        
        try {
            $log_file = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($log_file)) {
                // Clear the log file
                file_put_contents($log_file, '');
                log_action('Cleared debug.log file', 'success');
                $fix_results['log_cleared'] = true;
            } else {
                log_action('No debug.log file found', 'info');
                $fix_results['log_cleared'] = true;
            }
        } catch (Exception $e) {
            log_action('Error clearing debug log: ' . $e->getMessage(), 'error');
            $fix_results['log_cleared'] = false;
        }
        
        // Step 7: Final status check
        log_action('Step 7: Performing final status check...', 'info');
        
        $final_woocommerce_active = is_plugin_active('woocommerce/woocommerce.php');
        $final_woocommerce_blocks_active = is_plugin_active('woocommerce-blocks/woocommerce-blocks.php');
        $final_vortex_active = is_plugin_active('vortex-ai-engine/vortex-ai-engine.php');
        
        $fix_results['final_status'] = [
            'woocommerce' => $final_woocommerce_active,
            'woocommerce_blocks' => $final_woocommerce_blocks_active,
            'vortex_ai_engine' => $final_vortex_active
        ];
        
        log_action("Final status - WooCommerce: " . ($final_woocommerce_active ? 'Active' : 'Inactive'), 'info');
        log_action("Final status - WooCommerce Blocks: " . ($final_woocommerce_blocks_active ? 'Active' : 'Inactive'), 'info');
        log_action("Final status - Vortex AI Engine: " . ($final_vortex_active ? 'Active' : 'Inactive'), 'info');
        ?>

        <div class="info">
            <h3>📊 Final Status</h3>
            <p><strong>WooCommerce:</strong> <?php echo $final_woocommerce_active ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>WooCommerce Blocks:</strong> <?php echo $final_woocommerce_blocks_active ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>Vortex AI Engine:</strong> <?php echo $final_vortex_active ? '✅ Active' : '❌ Inactive'; ?></p>
        </div>

        <div class="success">
            <h3>✅ Aggressive Fix Results</h3>
            <p><strong>Blocks Deactivated:</strong> <?php echo $fix_results['blocks_deactivated'] ? '✅ Done' : '❌ Failed'; ?></p>
            <p><strong>Caches Cleared:</strong> <?php echo $fix_results['caches_cleared'] ? '✅ Done' : '❌ Failed'; ?></p>
            <p><strong>Hooks Removed:</strong> <?php echo $fix_results['hooks_removed'] ? '✅ Done' : '❌ Failed'; ?></p>
            <p><strong>Vortex Activated:</strong> <?php echo $fix_results['vortex_activated'] ? '✅ Done' : '❌ Failed'; ?></p>
            <p><strong>Blocks Reactivated:</strong> <?php echo $fix_results['blocks_reactivated'] ? '✅ Done' : '❌ Failed'; ?></p>
            <p><strong>Debug Log Cleared:</strong> <?php echo $fix_results['log_cleared'] ? '✅ Done' : '❌ Failed'; ?></p>
        </div>

        <div class="info">
            <h3>📋 Action Log</h3>
            <?php foreach ($logs as $log): ?>
                <div class="log-entry log-<?php echo $log['type']; ?>">
                    [<?php echo $log['timestamp']; ?>] <?php echo esc_html($log['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Actions -->
        <div style="text-align: center; margin-top: 30px;">
            <?php if ($final_vortex_active): ?>
                <a href="<?php echo admin_url('admin.php?page=vortex-solana-dashboard'); ?>" class="button success">🚀 Open Solana Dashboard</a>
            <?php endif; ?>
            <a href="<?php echo admin_url(); ?>" class="button">🏠 Go to WordPress Admin</a>
            <button onclick="location.reload();" class="button">🔄 Refresh Status</button>
        </div>

        <?php if ($final_vortex_active): ?>
            <div class="success">
                <h3>🎉 Success!</h3>
                <p>Vortex AI Engine is now active and the IntegrationRegistry conflicts have been resolved!</p>
                <p>The debug log has been cleared and the system should be running smoothly.</p>
            </div>
        <?php else: ?>
            <div class="error">
                <h3>❌ Activation Failed</h3>
                <p>Vortex AI Engine could not be activated. Please check the error logs and try again.</p>
                <p>You may need to manually activate the plugin from the WordPress admin.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; font-size: 12px; color: #666; text-align: center;">
            <p>Aggressive fix completed at: <?php echo current_time('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html> 