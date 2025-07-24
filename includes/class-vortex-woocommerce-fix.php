<?php
/**
 * VORTEX AI Engine - WooCommerce Blocks Integration Fix
 * 
 * Temporary fix for WooCommerce Blocks IntegrationRegistry conflicts
 * This file should be removed after successful plugin activation
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Blocks Integration Fix
 * 
 * Resolves IntegrationRegistry conflicts by clearing existing integrations
 * and reloading them safely before Vortex AI Engine activation
 */
class Vortex_WooCommerce_Fix {
    
    /**
     * Fix applied flag
     */
    private static $fix_applied = false;
    
    /**
     * Initialize the fix
     */
    public static function init() {
        if (self::$fix_applied) {
            return;
        }
        
        add_action('plugins_loaded', [__CLASS__, 'apply_integration_fix'], 5);
        self::$fix_applied = true;
    }
    
    /**
     * Apply the integration fix
     */
    public static function apply_integration_fix() {
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
    }
    
    /**
     * Remove the fix (call this after successful activation)
     */
    public static function remove_fix() {
        // Remove the action hook
        remove_action('plugins_loaded', [__CLASS__, 'apply_integration_fix'], 5);
        
        // Log removal
        error_log('VORTEX AI Engine: WooCommerce Blocks integration fix removed');
    }
}

// Initialize the fix
Vortex_WooCommerce_Fix::init(); 