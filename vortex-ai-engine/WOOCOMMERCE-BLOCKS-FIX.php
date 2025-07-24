<?php
/**
 * WooCommerce Blocks Integration Fix
 * 
 * Add this code to your theme's functions.php or at the top of your plugin file
 * to resolve IntegrationRegistry conflicts with Vortex AI Engine
 * 
 * Remove this code after successful plugin activation
 */

add_action('plugins_loaded', function() {
    // Check if WooCommerce Blocks is active
    if (!class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
        return;
    }
    
    try {
        // Get the integration registry instance
        $registry = Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
        
        // Use reflection to access the private integrations property
        $reflection = new ReflectionClass($registry);
        $integrations_property = $reflection->getProperty('integrations');
        $integrations_property->setAccessible(true);
        
        // Clear existing integrations
        $integrations_property->setValue($registry, []);
        
        // Reload integrations safely
        if (class_exists('Automattic\WooCommerce\Blocks\Init')) {
            Automattic\WooCommerce\Blocks\Init::load_integrations();
        }
        
        // Log successful fix
        error_log('VORTEX AI Engine: WooCommerce Blocks integration fix applied successfully');
        
    } catch (Exception $e) {
        error_log('VORTEX AI Engine: WooCommerce Blocks integration fix failed: ' . $e->getMessage());
    }
}, 5); 