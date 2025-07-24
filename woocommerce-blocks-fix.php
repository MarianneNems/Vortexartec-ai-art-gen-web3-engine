<?php
/**
 * WooCommerce Blocks IntegrationRegistry Fix
 * 
 * Fixes "IntegrationRegistry::register was called incorrectly" notices
 * by clearing empty and duplicate entries from the registry.
 * 
 * @package Vortex-AI-Engine
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', function() {
    // Ensure we only run once per request
    static $fix_applied = false;
    if ( $fix_applied ) {
        return;
    }
    
    try {
        // Check if WooCommerce Blocks IntegrationRegistry exists
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            return;
        }
        
        // Get the registry instance
        $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
        if ( ! $registry ) {
            return;
        }
        
        // Use reflection to access the private integrations property
        $reflection = new ReflectionClass( $registry );
        $integrations_property = $reflection->getProperty( 'integrations' );
        $integrations_property->setAccessible( true );
        
        $integrations = $integrations_property->getValue( $registry );
        
        if ( ! is_array( $integrations ) ) {
            return;
        }
        
        $original_count = count( $integrations );
        $cleaned_integrations = array();
        $removed_count = 0;
        $empty_keys_removed = 0;
        $duplicates_removed = 0;
        $invalid_objects_removed = 0;
        
        // Clean out empty keys and duplicates
        foreach ( $integrations as $key => $integration ) {
            // Skip empty keys
            if ( empty( $key ) || $key === '' ) {
                $removed_count++;
                $empty_keys_removed++;
                continue;
            }
            
            // Skip if we already have this key (prevent duplicates)
            if ( isset( $cleaned_integrations[ $key ] ) ) {
                $removed_count++;
                $duplicates_removed++;
                continue;
            }
            
            // Skip null or invalid integrations
            if ( ! $integration || ! is_object( $integration ) ) {
                $removed_count++;
                $invalid_objects_removed++;
                continue;
            }
            
            $cleaned_integrations[ $key ] = $integration;
        }
        
        // Only proceed if we actually cleaned something
        if ( $removed_count > 0 ) {
            // Set the cleaned integrations back to the registry
            $integrations_property->setValue( $registry, $cleaned_integrations );
            
            // Log the fix with detailed information
            error_log( sprintf( 
                'WooCommerce Blocks IntegrationRegistry Fix: Removed %d invalid entries (empty keys: %d, duplicates: %d, invalid objects: %d)',
                $removed_count,
                $empty_keys_removed,
                $duplicates_removed,
                $invalid_objects_removed
            ) );
            
            // Show admin notice
            add_action( 'admin_notices', function() use ( $removed_count, $empty_keys_removed, $duplicates_removed, $invalid_objects_removed ) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>WooCommerce Blocks IntegrationRegistry Fixed:</strong> ';
                echo sprintf( 'Removed %d invalid integration entries that were causing PHP notices.', $removed_count );
                if ( $empty_keys_removed > 0 ) {
                    echo sprintf( ' (%d empty keys, %d duplicates, %d invalid objects)', $empty_keys_removed, $duplicates_removed, $invalid_objects_removed );
                }
                echo '</p>';
                echo '</div>';
            } );
        }
        
        $fix_applied = true;
        
    } catch ( Exception $e ) {
        error_log( 'WooCommerce Blocks IntegrationRegistry Fix Error: ' . $e->getMessage() );
    } catch ( Error $e ) {
        error_log( 'WooCommerce Blocks IntegrationRegistry Fix Fatal Error: ' . $e->getMessage() );
    }
    
}, 5 ); 