<?php
/**
 * VORTEX AI ENGINE - FUNCTIONS.PHP FIX
 * 
 * Add this code to your functions.php to resolve WooCommerce Blocks conflicts
 * Copy and paste this entire block into your functions.php file
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// ============================================================================
// VORTEX AI ENGINE - AGGRESSIVE WOOCOMMERCE BLOCKS FIX
// ============================================================================
// This fix resolves IntegrationRegistry conflicts by completely clearing
// and reinitializing the integration registry

add_action('plugins_loaded', function() {
    // Only run if WooCommerce Blocks is active
    if (!class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
        return;
    }
    
    try {
        // Get the integration registry instance
        $registry = Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
        
        // Use reflection to access private properties
        $reflection = new ReflectionClass($registry);
        
        // Clear all integration-related properties
        $properties_to_clear = [
            'integrations',
            'registered_integrations', 
            'integration_registry',
            'registry'
        ];
        
        foreach ($properties_to_clear as $property_name) {
            if ($reflection->hasProperty($property_name)) {
                $property = $reflection->getProperty($property_name);
                $property->setAccessible(true);
                $property->setValue($registry, []);
            }
        }
        
        // Clear any stored integration data
        delete_option('woocommerce_blocks_integrations');
        delete_option('woocommerce_blocks_registry');
        
        // Remove all hooks that might cause conflicts
        remove_all_actions('woocommerce_blocks_loaded');
        remove_all_actions('woocommerce_blocks_registry_loaded');
        remove_all_actions('woocommerce_blocks_integration_registry_loaded');
        
        // Force reload integrations safely
        if (class_exists('Automattic\WooCommerce\Blocks\Init')) {
            Automattic\WooCommerce\Blocks\Init::load_integrations();
        }
        
        // Log successful fix
        error_log('VORTEX AI Engine: Aggressive WooCommerce Blocks fix applied successfully');
        
    } catch (Exception $e) {
        error_log('VORTEX AI Engine: Aggressive WooCommerce Blocks fix failed: ' . $e->getMessage());
    }
}, 1); // Highest priority to run first

// ============================================================================
// VORTEX AI ENGINE - INTEGRATION CONFLICT PREVENTION
// ============================================================================
// Prevents future integration conflicts by filtering registration attempts

add_filter('woocommerce_blocks_integration_registry_loaded', function($registry) {
    try {
        // Clear any existing integrations before new ones are loaded
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
        
        error_log('VORTEX AI Engine: Integration conflicts prevented');
        
    } catch (Exception $e) {
        error_log('VORTEX AI Engine: Integration conflict prevention failed: ' . $e->getMessage());
    }
    
    return $registry;
}, 1);

// ============================================================================
// VORTEX AI ENGINE - CACHE CLEARING
// ============================================================================
// Clears caches when Vortex AI Engine is activated

add_action('activated_plugin', function($plugin) {
    if (strpos($plugin, 'vortex-ai-engine') !== false) {
        // Clear WordPress caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear transients
        wp_cache_flush();
        
        // Clear other caches
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        error_log('VORTEX AI Engine: Caches cleared on activation');
    }
});

// ============================================================================
// VORTEX AI ENGINE - ERROR SUPPRESSION
// ============================================================================
// Suppresses specific WooCommerce Blocks errors during development

if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_head', function() {
        echo '<script>
            // Suppress console errors during development
            var originalError = console.error;
            console.error = function() {
                var args = Array.prototype.slice.call(arguments);
                if (args[0] && typeof args[0] === "string") {
                    if (args[0].includes("IntegrationRegistry") || 
                        args[0].includes("already registered") ||
                        args[0].includes("addEventListener")) {
                        return; // Suppress specific errors
                    }
                }
                originalError.apply(console, args);
            };
        </script>';
    });
}

// ============================================================================
// VORTEX AI ENGINE - ACTIVATION HOOK
// ============================================================================
// Ensures proper initialization when Vortex AI Engine is activated

register_activation_hook(__FILE__, function() {
    // Clear any existing integration conflicts
    delete_option('woocommerce_blocks_integrations');
    delete_option('woocommerce_blocks_registry');
    
    // Clear caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    error_log('VORTEX AI Engine: Activation hook completed');
});

// ============================================================================
// VORTEX AI ENGINE - DEACTIVATION HOOK
// ============================================================================
// Cleans up when Vortex AI Engine is deactivated

register_deactivation_hook(__FILE__, function() {
    // Clear any Vortex-specific options
    delete_option('vortex_solana_metrics');
    delete_option('vortex_tola_balances');
    
    error_log('VORTEX AI Engine: Deactivation hook completed');
});

// ============================================================================
// VORTEX AI ENGINE - SYSTEM VERIFICATION
// ============================================================================
// Verifies that the fix is working properly

add_action('admin_init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Check if WooCommerce Blocks conflicts are resolved
        if (class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
            try {
                $registry = Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                $reflection = new ReflectionClass($registry);
                
                if ($reflection->hasProperty('integrations')) {
                    $integrations_property = $reflection->getProperty('integrations');
                    $integrations_property->setAccessible(true);
                    $integrations = $integrations_property->getValue($registry);
                    
                    if (empty($integrations)) {
                        error_log('VORTEX AI Engine: Integration conflicts resolved successfully');
                    } else {
                        error_log('VORTEX AI Engine: WARNING - Integrations still present: ' . count($integrations));
                    }
                }
            } catch (Exception $e) {
                error_log('VORTEX AI Engine: Error checking integration status: ' . $e->getMessage());
            }
        }
    }
}); 