<?php
/**
 * WooCommerce Blocks IntegrationRegistry Conflict Fix
 * 
 * This file fixes the IntegrationRegistry::register conflicts that occur when
 * WooCommerce Blocks tries to register integrations that are already registered.
 * 
 * @package Vortex-AI-Engine
 * @version 1.0.0
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WooCommerce Blocks Integration Registry Fix
 * 
 * @since 1.0.0
 */
class Vortex_WooCommerce_Blocks_Fix {
    
    /**
     * Singleton instance
     * 
     * @var Vortex_WooCommerce_Blocks_Fix
     */
    private static $instance = null;
    
    /**
     * Registered integrations cache
     * 
     * @var array
     */
    private $registered_integrations = array();
    
    /**
     * Get singleton instance
     * 
     * @return Vortex_WooCommerce_Blocks_Fix
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the fix
     */
    private function init() {
        // Hook early to prevent conflicts
        add_action( 'init', array( $this, 'fix_integration_registry' ), 5 );
        add_action( 'plugins_loaded', array( $this, 'early_integration_fix' ), 1 );
        
        // Hook into WooCommerce Blocks specific actions
        add_action( 'woocommerce_blocks_loaded', array( $this, 'blocks_loaded_fix' ), 1 );
        
        // Fix for specific integration conflicts
        add_filter( 'woocommerce_blocks_integration_registry', array( $this, 'filter_integration_registry' ), 1 );
        
        // Prevent duplicate registrations
        add_action( 'woocommerce_blocks_register_integration', array( $this, 'prevent_duplicate_registration' ), 1, 2 );
        
        // Clean up on deactivation
        register_deactivation_hook( __FILE__, array( $this, 'cleanup' ) );
    }
    
    /**
     * Early integration fix
     */
    public function early_integration_fix() {
        // Check if WooCommerce Blocks is active
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            return;
        }
        
        // Prevent multiple registrations of the same integration
        add_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'check_registration' ), 1, 2 );
    }
    
    /**
     * Fix integration registry conflicts
     */
    public function fix_integration_registry() {
        // Only run if WooCommerce Blocks is active
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            return;
        }
        
        // Clear any existing integration cache
        $this->clear_integration_cache();
        
        // Hook into the registration process
        add_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'safe_register_integration' ), 1, 2 );
        
        // Fix specific known conflicts
        $this->fix_known_conflicts();
    }
    
    /**
     * Blocks loaded fix
     */
    public function blocks_loaded_fix() {
        // Re-register integrations safely
        $this->re_register_integrations();
    }
    
    /**
     * Filter integration registry
     * 
     * @param mixed $registry The integration registry
     * @return mixed
     */
    public function filter_integration_registry( $registry ) {
        if ( is_object( $registry ) && method_exists( $registry, 'get_all_registered' ) ) {
            // Cache current registrations
            $this->registered_integrations = $registry->get_all_registered();
        }
        return $registry;
    }
    
    /**
     * Prevent duplicate registration
     * 
     * @param string $name Integration name
     * @param object $integration Integration object
     */
    public function prevent_duplicate_registration( $name, $integration ) {
        if ( empty( $name ) ) {
            // Log empty integration name
            error_log( 'WooCommerce Blocks: Attempted to register integration with empty name' );
            return false;
        }
        
        if ( isset( $this->registered_integrations[ $name ] ) ) {
            // Integration already registered, skip
            return false;
        }
        
        // Cache the registration
        $this->registered_integrations[ $name ] = $integration;
        return true;
    }
    
    /**
     * Check registration before it happens
     * 
     * @param bool $result Registration result
     * @param array $args Registration arguments
     * @return bool
     */
    public function check_registration( $result, $args ) {
        if ( ! is_array( $args ) || empty( $args['name'] ) ) {
            return false;
        }
        
        $name = sanitize_text_field( $args['name'] );
        
        if ( isset( $this->registered_integrations[ $name ] ) ) {
            // Already registered, prevent duplicate
            return false;
        }
        
        return $result;
    }
    
    /**
     * Safe register integration
     * 
     * @param bool $result Registration result
     * @param array $args Registration arguments
     * @return bool
     */
    public function safe_register_integration( $result, $args ) {
        if ( ! is_array( $args ) ) {
            return false;
        }
        
        $name = isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '';
        $integration = isset( $args['integration'] ) ? $args['integration'] : null;
        
        if ( empty( $name ) ) {
            error_log( 'WooCommerce Blocks: Integration registration attempted with empty name' );
            return false;
        }
        
        if ( ! $integration ) {
            error_log( 'WooCommerce Blocks: Integration registration attempted with null integration object' );
            return false;
        }
        
        if ( isset( $this->registered_integrations[ $name ] ) ) {
            // Already registered, skip
            return false;
        }
        
        // Cache the registration
        $this->registered_integrations[ $name ] = $integration;
        
        return $result;
    }
    
    /**
     * Fix known conflicts
     */
    private function fix_known_conflicts() {
        // Fix for empty integration names
        add_filter( 'woocommerce_blocks_integration_registry_register', function( $result, $args ) {
            if ( is_array( $args ) && empty( $args['name'] ) ) {
                return false;
            }
            return $result;
        }, 1, 2 );
        
        // Fix for duplicate integration registrations
        add_action( 'woocommerce_blocks_register_integration', function( $name, $integration ) {
            static $registered = array();
            
            if ( empty( $name ) ) {
                return false;
            }
            
            if ( isset( $registered[ $name ] ) ) {
                return false;
            }
            
            $registered[ $name ] = $integration;
            return true;
        }, 1, 2 );
    }
    
    /**
     * Re-register integrations safely
     */
    private function re_register_integrations() {
        // Get the integration registry
        $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
        
        if ( ! $registry ) {
            return;
        }
        
        // Get all currently registered integrations
        $current_integrations = $registry->get_all_registered();
        
        if ( ! is_array( $current_integrations ) ) {
            return;
        }
        
        // Clear and re-register each integration safely
        foreach ( $current_integrations as $name => $integration ) {
            if ( ! empty( $name ) && $integration ) {
                // Remove existing registration
                $registry->unregister( $name );
                
                // Re-register safely
                try {
                    $registry->register( $name, $integration );
                } catch ( Exception $e ) {
                    error_log( 'WooCommerce Blocks: Failed to re-register integration ' . $name . ': ' . $e->getMessage() );
                }
            }
        }
    }
    
    /**
     * Clear integration cache
     */
    private function clear_integration_cache() {
        $this->registered_integrations = array();
        
        // Clear any WordPress object cache for integrations
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( 'woocommerce_blocks_integrations', 'woocommerce' );
        }
    }
    
    /**
     * Cleanup on deactivation
     */
    public function cleanup() {
        $this->clear_integration_cache();
        
        // Remove any custom filters and actions
        remove_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'check_registration' ), 1 );
        remove_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'safe_register_integration' ), 1 );
        remove_action( 'woocommerce_blocks_register_integration', array( $this, 'prevent_duplicate_registration' ), 1 );
    }
}

/**
 * Initialize the fix
 */
function vortex_init_woocommerce_blocks_fix() {
    // Only initialize if WooCommerce is active
    if ( class_exists( 'WooCommerce' ) ) {
        Vortex_WooCommerce_Blocks_Fix::get_instance();
    }
}

// Initialize early
add_action( 'plugins_loaded', 'vortex_init_woocommerce_blocks_fix', 1 );

/**
 * Additional fix for specific WooCommerce Blocks versions
 */
function vortex_woocommerce_blocks_version_fix() {
    // Check WooCommerce Blocks version
    if ( ! defined( 'WC_BLOCKS_VERSION' ) ) {
        return;
    }
    
    $blocks_version = WC_BLOCKS_VERSION;
    
    // Fix for specific version issues
    if ( version_compare( $blocks_version, '8.0.0', '<' ) ) {
        // Older version fixes
        add_filter( 'woocommerce_blocks_integration_registry_register', function( $result, $args ) {
            if ( is_array( $args ) && empty( $args['name'] ) ) {
                return false;
            }
            return $result;
        }, 1, 2 );
    }
    
    // Fix for newer versions
    if ( version_compare( $blocks_version, '9.0.0', '>=' ) ) {
        // Newer version specific fixes
        add_action( 'woocommerce_blocks_loaded', function() {
            // Additional fixes for newer versions
        }, 5 );
    }
}

add_action( 'init', 'vortex_woocommerce_blocks_version_fix', 1 );

/**
 * Debug function to log integration registry issues
 */
function vortex_log_integration_registry_issue( $message, $data = array() ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'WooCommerce Blocks Integration Registry Issue: ' . $message );
        if ( ! empty( $data ) ) {
            error_log( 'Data: ' . print_r( $data, true ) );
        }
    }
}

/**
 * Emergency fix for critical integration conflicts
 */
function vortex_emergency_integration_fix() {
    // Only run in emergency situations
    if ( ! defined( 'VORTEX_EMERGENCY_FIX' ) || ! VORTEX_EMERGENCY_FIX ) {
        return;
    }
    
    // Force clear all integration registrations
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
        $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
        
        if ( $registry && method_exists( $registry, 'get_all_registered' ) ) {
            $integrations = $registry->get_all_registered();
            
            foreach ( $integrations as $name => $integration ) {
                if ( method_exists( $registry, 'unregister' ) ) {
                    $registry->unregister( $name );
                }
            }
        }
    }
}

// Emergency fix hook
add_action( 'init', 'vortex_emergency_integration_fix', 1 ); 