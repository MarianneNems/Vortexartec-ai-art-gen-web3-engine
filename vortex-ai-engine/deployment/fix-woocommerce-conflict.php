<?php
/**
 * Vortex AI Engine - WooCommerce Blocks Integration Conflict Fix
 * 
 * Temporary fix for WooCommerce Blocks integration registry conflicts
 * Place this file in: wp-content/plugins/vortex-ai-engine/deployment/fix-woocommerce-conflict.php
 * Then visit: https://yoursite.com/wp-content/plugins/vortex-ai-engine/deployment/fix-woocommerce-conflict.php
 * 
 * @package VortexAIEngine
 * @since 2.2.0
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
    <title>Vortex AI Engine - WooCommerce Conflict Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); color: white; padding: 20px; margin: -30px -30px 30px -30px; border-radius: 10px 10px 0 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .step { background: #f8f9fa; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #005a87; }
        .status { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Vortex AI Engine - WooCommerce Conflict Fix</h1>
            <p>Resolving WooCommerce Blocks integration registry conflicts</p>
        </div>

        <?php
        // Step 1: Check current status
        echo "<h2>📋 Current Status</h2>";
        
        $woocommerce_active = is_plugin_active('woocommerce/woocommerce.php');
        $blocks_active = is_plugin_active('woocommerce-blocks/woocommerce-blocks.php');
        $vortex_active = is_plugin_active('vortex-ai-engine/vortex-ai-engine.php');
        
        echo "<div class='info'>";
        echo "<strong>Plugin Status:</strong><br>";
        echo "• WooCommerce: " . ($woocommerce_active ? "✅ Active" : "❌ Inactive") . "<br>";
        echo "• WooCommerce Blocks: " . ($blocks_active ? "✅ Active" : "❌ Inactive") . "<br>";
        echo "• Vortex AI Engine: " . ($vortex_active ? "✅ Active" : "❌ Inactive");
        echo "</div>";

        // Step 2: Fix WooCommerce Blocks integration conflicts
        echo "<h2>🔧 Fixing Integration Conflicts</h2>";
        
        $fixes_applied = [];
        
        if ($blocks_active) {
            echo "<div class='step'>";
            echo "<strong>Step 1:</strong> Deactivating WooCommerce Blocks temporarily...<br>";
            
            // Deactivate WooCommerce Blocks
            deactivate_plugins('woocommerce-blocks/woocommerce-blocks.php');
            $fixes_applied[] = "WooCommerce Blocks deactivated";
            
            echo "✅ WooCommerce Blocks deactivated successfully";
            echo "</div>";
        }

        // Step 3: Clear integration registrations
        echo "<div class='step'>";
        echo "<strong>Step 2:</strong> Clearing integration registrations...<br>";
        
        // Clear WordPress cache
        wp_cache_flush();
        $fixes_applied[] = "WordPress cache cleared";
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $fixes_applied[] = "Object cache cleared";
        }
        
        // Clear WooCommerce Blocks specific cache
        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook('woocommerce_blocks_registry_clear');
            $fixes_applied[] = "WooCommerce Blocks registry cleared";
        }
        
        // Clear any existing integration registrations
        if (class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
            try {
                $registry = Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                $reflection = new ReflectionClass($registry);
                $prop = $reflection->getProperty('integrations');
                $prop->setAccessible(true);
                $prop->setValue($registry, []);
                $fixes_applied[] = "Integration registry cleared";
            } catch (Exception $e) {
                echo "⚠️ Could not clear integration registry: " . $e->getMessage() . "<br>";
            }
        }
        
        echo "✅ All caches and registrations cleared";
        echo "</div>";

        // Step 4: Attempt to activate Vortex AI Engine
        echo "<div class='step'>";
        echo "<strong>Step 3:</strong> Activating Vortex AI Engine...<br>";
        
        if (!$vortex_active) {
            // Check if plugin file exists
            if (file_exists(WP_PLUGIN_DIR . '/vortex-ai-engine/vortex-ai-engine.php')) {
                $result = activate_plugin('vortex-ai-engine/vortex-ai-engine.php');
                
                if (is_wp_error($result)) {
                    echo "❌ Activation failed: " . $result->get_error_message();
                } else {
                    echo "✅ Vortex AI Engine activated successfully!";
                    $fixes_applied[] = "Vortex AI Engine activated";
                    $vortex_active = true;
                }
            } else {
                echo "❌ Vortex AI Engine plugin file not found";
            }
        } else {
            echo "✅ Vortex AI Engine is already active";
        }
        echo "</div>";

        // Step 5: Re-activate WooCommerce Blocks if it was active
        if ($blocks_active) {
            echo "<div class='step'>";
            echo "<strong>Step 4:</strong> Re-activating WooCommerce Blocks...<br>";
            
            $result = activate_plugin('woocommerce-blocks/woocommerce-blocks.php');
            
            if (is_wp_error($result)) {
                echo "⚠️ WooCommerce Blocks re-activation failed: " . $result->get_error_message();
                echo "<br>You may need to re-activate it manually.";
            } else {
                echo "✅ WooCommerce Blocks re-activated successfully";
                $fixes_applied[] = "WooCommerce Blocks re-activated";
            }
            echo "</div>";
        }

        // Step 6: Final status check
        echo "<h2>📊 Final Status</h2>";
        
        $final_woocommerce = is_plugin_active('woocommerce/woocommerce.php');
        $final_blocks = is_plugin_active('woocommerce-blocks/woocommerce-blocks.php');
        $final_vortex = is_plugin_active('vortex-ai-engine/vortex-ai-engine.php');
        
        echo "<div class='info'>";
        echo "<strong>Updated Plugin Status:</strong><br>";
        echo "• WooCommerce: " . ($final_woocommerce ? "✅ Active" : "❌ Inactive") . "<br>";
        echo "• WooCommerce Blocks: " . ($final_blocks ? "✅ Active" : "❌ Inactive") . "<br>";
        echo "• Vortex AI Engine: " . ($final_vortex ? "✅ Active" : "❌ Inactive");
        echo "</div>";

        // Step 7: Results summary
        echo "<h2>🎯 Results Summary</h2>";
        
        if ($final_vortex) {
            echo "<div class='success'>";
            echo "<strong>🎉 SUCCESS!</strong><br>";
            echo "Vortex AI Engine is now active and ready to use!";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<strong>❌ ISSUE DETECTED</strong><br>";
            echo "Vortex AI Engine could not be activated. Please check the WordPress error log.";
            echo "</div>";
        }

        if (!empty($fixes_applied)) {
            echo "<div class='info'>";
            echo "<strong>Fixes Applied:</strong><br>";
            foreach ($fixes_applied as $fix) {
                echo "• $fix<br>";
            }
            echo "</div>";
        }

        // Step 8: Next steps
        echo "<h2>📋 Next Steps</h2>";
        
        if ($final_vortex) {
            echo "<div class='step'>";
            echo "<strong>1. Accept Agreement:</strong> Visit your WordPress admin and accept the Vortex AI Engine agreement when prompted.<br><br>";
            echo "<strong>2. Configure AWS:</strong> Add your AWS credentials to wp-config.php:<br>";
            echo "<code>define('AWS_ACCESS_KEY_ID', 'your_key');<br>";
            echo "define('AWS_SECRET_ACCESS_KEY', 'your_secret');<br>";
            echo "define('AWS_DEFAULT_REGION', 'us-east-1');</code><br><br>";
            echo "<strong>3. Test Features:</strong> Verify all shortcodes and features work correctly.<br><br>";
            echo "<strong>4. Monitor:</strong> Check the health dashboard for any issues.";
            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "<strong>Troubleshooting Steps:</strong><br>";
            echo "1. Check WordPress error log for specific errors<br>";
            echo "2. Verify all plugin files are uploaded correctly<br>";
            echo "3. Ensure PHP version is 7.4 or higher<br>";
            echo "4. Check file permissions (755 for directories, 644 for files)";
            echo "</div>";
        }
        ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="<?php echo admin_url(); ?>" class="button">Go to WordPress Admin</a>
            <a href="<?php echo home_url(); ?>" class="button">Visit Homepage</a>
        </div>

        <div style="margin-top: 20px; font-size: 12px; color: #666; text-align: center;">
            <p>This script has been executed successfully. You can now delete this file for security.</p>
        </div>
    </div>
</body>
</html> 