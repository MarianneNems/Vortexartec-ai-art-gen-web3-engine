<?php
/**
 * VORTEX AI ENGINE - EMERGENCY WOOCOMMERCE BLOCKS FIX
 * 
 * Immediate fix for WooCommerce Blocks IntegrationRegistry conflicts
 * Run this file via browser to resolve activation issues
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

// Disable error display for production
error_reporting(E_ALL);
ini_set('display_errors', 0);

?>
<!DOCTYPE html>
<html>
<head>
    <title>VORTEX AI Engine - Emergency WooCommerce Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; margin: -30px -30px 30px -30px; border-radius: 10px 10px 0 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .button { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #c82333; }
        .button.success { background: #28a745; }
        .button.success:hover { background: #218838; }
        .log-entry { background: #f8f9fa; padding: 8px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-success { background: #d4edda; color: #155724; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 VORTEX AI Engine - Emergency WooCommerce Fix</h1>
            <p>Resolving IntegrationRegistry conflicts and activation issues</p>
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
        
        // Step 1: Check current status
        log_action('Starting emergency WooCommerce Blocks fix...', 'info');
        
        $woocommerce_active = is_plugin_active('woocommerce/woocommerce.php');
        $woocommerce_blocks_active = is_plugin_active('woocommerce-blocks/woocommerce-blocks.php');
        $vortex_active = is_plugin_active('vortex-ai-engine/vortex-ai-engine.php');
        
        $fix_results['initial_status'] = [
            'woocommerce' => $woocommerce_active,
            'woocommerce_blocks' => $woocommerce_blocks_active,
            'vortex_ai_engine' => $vortex_active
        ];
        
        log_action("Initial status - WooCommerce: " . ($woocommerce_active ? 'Active' : 'Inactive'), 'info');
        log_action("Initial status - WooCommerce Blocks: " . ($woocommerce_blocks_active ? 'Active' : 'Inactive'), 'info');
        log_action("Initial status - Vortex AI Engine: " . ($vortex_active ? 'Active' : 'Inactive'), 'info');
        ?>
        
        <div class="info">
            <h3>📊 Current Status</h3>
            <p><strong>WooCommerce:</strong> <?php echo $woocommerce_active ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>WooCommerce Blocks:</strong> <?php echo $woocommerce_blocks_active ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>Vortex AI Engine:</strong> <?php echo $vortex_active ? '✅ Active' : '❌ Inactive'; ?></p>
        </div>

        <?php
        // Step 2: Apply aggressive WooCommerce Blocks fix
        log_action('Applying aggressive WooCommerce Blocks fix...', 'info');
        
        try {
            // Force clear all WooCommerce Blocks integrations
            if (class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
                log_action('IntegrationRegistry class found, clearing integrations...', 'info');
                
                // Get the registry instance
                $registry = Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                
                // Use reflection to access private properties
                $reflection = new ReflectionClass($registry);
                
                // Clear integrations property
                if ($reflection->hasProperty('integrations')) {
                    $integrations_property = $reflection->getProperty('integrations');
                    $integrations_property->setAccessible(true);
                    $integrations_property->setValue($registry, []);
                    log_action('Cleared integrations property', 'success');
                }
                
                // Clear registered_integrations property if it exists
                if ($reflection->hasProperty('registered_integrations')) {
                    $registered_property = $reflection->getProperty('registered_integrations');
                    $registered_property->setAccessible(true);
                    $registered_property->setValue($registry, []);
                    log_action('Cleared registered_integrations property', 'success');
                }
                
                // Force reload integrations
                if (class_exists('Automattic\WooCommerce\Blocks\Init')) {
                    Automattic\WooCommerce\Blocks\Init::load_integrations();
                    log_action('Reloaded WooCommerce Blocks integrations', 'success');
                }
                
                $fix_results['registry_fix'] = true;
            } else {
                log_action('IntegrationRegistry class not found', 'warning');
                $fix_results['registry_fix'] = false;
            }
            
        } catch (Exception $e) {
            log_action('Error applying registry fix: ' . $e->getMessage(), 'error');
            $fix_results['registry_fix'] = false;
        }
        
        // Step 3: Clear all caches
        log_action('Clearing all caches...', 'info');
        
        try {
            // Clear WordPress object cache
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
                log_action('Cleared WordPress object cache', 'success');
            }
            
            // Clear WooCommerce cache
            if (function_exists('wc_cache_helper_get_transient_version')) {
                delete_transient('wc_cache_helper_get_transient_version');
                log_action('Cleared WooCommerce cache', 'success');
            }
            
            // Clear any other caches
            if (function_exists('w3tc_flush_all')) {
                w3tc_flush_all();
                log_action('Cleared W3 Total Cache', 'success');
            }
            
            if (function_exists('wp_cache_clear_cache')) {
                wp_cache_clear_cache();
                log_action('Cleared WP Super Cache', 'success');
            }
            
            $fix_results['cache_cleared'] = true;
            
        } catch (Exception $e) {
            log_action('Error clearing caches: ' . $e->getMessage(), 'error');
            $fix_results['cache_cleared'] = false;
        }
        
        // Step 4: Temporarily deactivate WooCommerce Blocks
        log_action('Temporarily deactivating WooCommerce Blocks...', 'info');
        
        try {
            if ($woocommerce_blocks_active) {
                deactivate_plugins('woocommerce-blocks/woocommerce-blocks.php');
                log_action('Deactivated WooCommerce Blocks', 'success');
                $fix_results['blocks_deactivated'] = true;
            } else {
                log_action('WooCommerce Blocks already inactive', 'info');
                $fix_results['blocks_deactivated'] = true;
            }
        } catch (Exception $e) {
            log_action('Error deactivating WooCommerce Blocks: ' . $e->getMessage(), 'error');
            $fix_results['blocks_deactivated'] = false;
        }
        
        // Step 5: Try to activate Vortex AI Engine
        log_action('Attempting to activate Vortex AI Engine...', 'info');
        
        try {
            if (!$vortex_active) {
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
        
        // Step 6: Reactivate WooCommerce Blocks
        log_action('Reactivating WooCommerce Blocks...', 'info');
        
        try {
            activate_plugins('woocommerce-blocks/woocommerce-blocks.php');
            log_action('Reactivated WooCommerce Blocks', 'success');
            $fix_results['blocks_reactivated'] = true;
        } catch (Exception $e) {
            log_action('Error reactivating WooCommerce Blocks: ' . $e->getMessage(), 'error');
            $fix_results['blocks_reactivated'] = false;
        }
        
        // Step 7: Final status check
        log_action('Performing final status check...', 'info');
        
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
        
        // Check for errors in error log
        $error_log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($error_log_file)) {
            $log_content = file_get_contents($error_log_file);
            $recent_errors = array_filter(explode("\n", $log_content), function($line) {
                return strpos($line, 'IntegrationRegistry') !== false || strpos($line, 'VORTEX') !== false;
            });
            $fix_results['recent_errors'] = array_slice($recent_errors, -10);
        }
        ?>

        <div class="info">
            <h3>📊 Final Status</h3>
            <p><strong>WooCommerce:</strong> <?php echo $final_woocommerce_active ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>WooCommerce Blocks:</strong> <?php echo $final_woocommerce_blocks_active ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>Vortex AI Engine:</strong> <?php echo $final_vortex_active ? '✅ Active' : '❌ Inactive'; ?></p>
        </div>

        <div class="success">
            <h3>✅ Fix Results</h3>
            <p><strong>Registry Fix:</strong> <?php echo $fix_results['registry_fix'] ? '✅ Applied' : '❌ Failed'; ?></p>
            <p><strong>Cache Cleared:</strong> <?php echo $fix_results['cache_cleared'] ? '✅ Cleared' : '❌ Failed'; ?></p>
            <p><strong>Blocks Deactivated:</strong> <?php echo $fix_results['blocks_deactivated'] ? '✅ Done' : '❌ Failed'; ?></p>
            <p><strong>Vortex Activated:</strong> <?php echo $fix_results['vortex_activated'] ? '✅ Done' : '❌ Failed'; ?></p>
            <p><strong>Blocks Reactivated:</strong> <?php echo $fix_results['blocks_reactivated'] ? '✅ Done' : '❌ Failed'; ?></p>
        </div>

        <?php if (!empty($fix_results['recent_errors'])): ?>
            <div class="warning">
                <h3>⚠️ Recent Errors</h3>
                <?php foreach ($fix_results['recent_errors'] as $error): ?>
                    <div class="log-entry log-error"><?php echo esc_html($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
            <a href="<?php echo admin_url('admin.php?page=vortex-solana-dashboard'); ?>" class="button success">🚀 Open Solana Dashboard</a>
            <a href="<?php echo admin_url(); ?>" class="button">🏠 Go to WordPress Admin</a>
            <button onclick="location.reload();" class="button">🔄 Refresh Status</button>
        </div>

        <?php if ($final_vortex_active): ?>
            <div class="success">
                <h3>🎉 Success!</h3>
                <p>Vortex AI Engine is now active and ready to use. You can access the Solana dashboard from the WordPress admin menu.</p>
            </div>
        <?php else: ?>
            <div class="error">
                <h3>❌ Activation Failed</h3>
                <p>Vortex AI Engine could not be activated. Please check the error logs and try again.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; font-size: 12px; color: #666; text-align: center;">
            <p>Emergency fix completed at: <?php echo current_time('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html> 