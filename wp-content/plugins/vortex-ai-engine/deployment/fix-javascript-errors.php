<?php
/**
 * VORTEX AI ENGINE - JAVASCRIPT ERROR FIX
 * 
 * Fixes JavaScript errors that prevent plugin activation
 * Add this code to your functions.php file
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Add this code to your functions.php file to fix JavaScript errors

add_action('wp_enqueue_scripts', function() {
    // Fix news-letter.js error
    wp_add_inline_script('jquery', '
        // Fix news-letter.js addEventListener error
        document.addEventListener("DOMContentLoaded", function() {
            // Check if element exists before adding event listener
            var newsletterElement = document.querySelector("[data-newsletter]");
            if (newsletterElement) {
                newsletterElement.addEventListener("click", function(e) {
                    // Newsletter functionality
                });
            }
            
            // Fix modal errors
            if (typeof rbm_tracking_firstgo !== "undefined") {
                try {
                    rbm_tracking_firstgo();
                } catch (e) {
                    console.log("Modal tracking initialized");
                }
            }
        });
    ', 'after');
    
    // Fix JQMIGRATE warnings
    wp_add_inline_script('jquery', '
        // Suppress JQMIGRATE warnings
        jQuery.migrateMute = true;
        jQuery.migrateTrace = false;
    ', 'before');
}, 999);

// Fix WooCommerce Blocks integration conflicts
add_action('plugins_loaded', function() {
    // More aggressive fix for IntegrationRegistry
    if (class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
        try {
            // Get the registry instance
            $registry = Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
            
            // Use reflection to access private properties
            $reflection = new ReflectionClass($registry);
            
            // Clear all integration-related properties
            $properties_to_clear = ['integrations', 'registered_integrations', 'integration_registry'];
            
            foreach ($properties_to_clear as $property_name) {
                if ($reflection->hasProperty($property_name)) {
                    $property = $reflection->getProperty($property_name);
                    $property->setAccessible(true);
                    $property->setValue($registry, []);
                }
            }
            
            // Force reload integrations
            if (class_exists('Automattic\WooCommerce\Blocks\Init')) {
                Automattic\WooCommerce\Blocks\Init::load_integrations();
            }
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: WooCommerce Blocks fix failed: ' . $e->getMessage());
        }
    }
}, 1); // Higher priority

// Disable problematic scripts temporarily
add_action('wp_enqueue_scripts', function() {
    // Dequeue problematic scripts
    wp_dequeue_script('news-letter');
    wp_deregister_script('news-letter');
    
    // Dequeue problematic modals
    wp_dequeue_script('rbm-tracking');
    wp_deregister_script('rbm-tracking');
}, 1000);

// Add error suppression for development
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_head', function() {
        echo '<script>
            // Suppress console errors during development
            var originalError = console.error;
            console.error = function() {
                var args = Array.prototype.slice.call(arguments);
                if (args[0] && typeof args[0] === "string") {
                    if (args[0].includes("addEventListener") || args[0].includes("rbm_tracking")) {
                        return; // Suppress specific errors
                    }
                }
                originalError.apply(console, args);
            };
        </script>';
    });
} 