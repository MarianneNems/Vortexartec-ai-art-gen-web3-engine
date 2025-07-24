<?php
/**
 * Vortex AI Engine WooCommerce Blocks Integration Fix
 * 
 * This fix specifically addresses IntegrationRegistry conflicts caused by
 * the Vortex AI Engine plugin when it interacts with WooCommerce Blocks.
 * 
 * @package Vortex-AI-Engine
 * @version 3.0.0
 * @since 3.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Vortex WooCommerce Integration Fix Class
 * 
 * @since 3.0.0
 */
class Vortex_WooCommerce_Integration_Fix {
    
    /**
     * Singleton instance
     * 
     * @var Vortex_WooCommerce_Integration_Fix
     */
    private static $instance = null;
    
    /**
     * Registered integrations cache
     * 
     * @var array
     */
    private $registered_integrations = array();
    
    /**
     * Vortex-specific integrations
     * 
     * @var array
     */
    private $vortex_integrations = array();
    
    /**
     * Get singleton instance
     * 
     * @return Vortex_WooCommerce_Integration_Fix
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
        add_action( 'plugins_loaded', array( $this, 'early_fix' ), 1 );
        add_action( 'init', array( $this, 'init_fix' ), 1 );
        
        // Hook into WooCommerce Blocks
        add_action( 'woocommerce_blocks_loaded', array( $this, 'blocks_loaded_fix' ), 1 );
        
        // Hook into Vortex AI Engine
        add_action( 'vortex_ai_engine_loaded', array( $this, 'vortex_loaded_fix' ), 1 );
        
        // Prevent duplicate registrations
        add_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'prevent_duplicate_registration' ), 1, 2 );
        
        // Fix Vortex-specific integrations
        add_action( 'vortex_register_woocommerce_integration', array( $this, 'register_vortex_integration' ), 1, 2 );
        
        // Clean up on deactivation
        register_deactivation_hook( __FILE__, array( $this, 'cleanup' ) );
    }
    
    /**
     * Early fix for plugin conflicts
     */
    public function early_fix() {
        // Check if both Vortex AI Engine and WooCommerce Blocks are active
        if ( ! class_exists( 'Vortex_AI_Engine' ) || ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            return;
        }
        
        // Log the conflict detection
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Vortex WooCommerce Fix: Detected Vortex AI Engine and WooCommerce Blocks conflict' );
        }
        
        // Prevent early integration conflicts
        add_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'validate_integration_registration' ), 1, 2 );
    }
    
    /**
     * Initialize fix after WordPress is fully loaded
     */
    public function init_fix() {
        // Only run if both plugins are active
        if ( ! $this->are_plugins_active() ) {
            return;
        }
        
        // Clear any existing integration cache
        $this->clear_integration_cache();
        
        // Hook into the registration process
        add_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'safe_register_integration' ), 1, 2 );
        
        // Fix specific Vortex integration issues
        $this->fix_vortex_integrations();
    }
    
    /**
     * WooCommerce Blocks loaded fix
     */
    public function blocks_loaded_fix() {
        if ( ! $this->are_plugins_active() ) {
            return;
        }
        
        // Re-register integrations safely
        $this->re_register_integrations();
    }
    
    /**
     * Vortex AI Engine loaded fix
     */
    public function vortex_loaded_fix() {
        if ( ! $this->are_plugins_active() ) {
            return;
        }
        
        // Ensure Vortex integrations are properly registered
        $this->register_vortex_integrations();
    }
    
    /**
     * Register Vortex-specific integration
     * 
     * @param string $name Integration name
     * @param object $integration Integration object
     */
    public function register_vortex_integration( $name, $integration ) {
        if ( empty( $name ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Attempted to register Vortex integration with empty name' );
            }
            return false;
        }
        
        // Add Vortex prefix to avoid conflicts
        $vortex_name = 'vortex_' . sanitize_key( $name );
        
        // Store Vortex integration
        $this->vortex_integrations[ $vortex_name ] = array(
            'original_name' => $name,
            'integration' => $integration,
            'timestamp' => current_time( 'mysql' )
        );
        
        // Register with WooCommerce Blocks
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            try {
                $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                $registry->register( $vortex_name, $integration );
                
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( "Vortex WooCommerce Fix: Registered Vortex integration '$vortex_name'" );
                }
                
                return true;
            } catch ( Exception $e ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Vortex WooCommerce Fix: Failed to register Vortex integration: ' . $e->getMessage() );
                }
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Register all Vortex integrations
     */
    private function register_vortex_integrations() {
        // Get Vortex AI Engine instance
        if ( ! class_exists( 'Vortex_AI_Engine' ) ) {
            return;
        }
        
        // Register Vortex-specific integrations
        $vortex_integrations = array(
            'tola_token' => array(
                'name' => 'TOLA Token Integration',
                'class' => 'Vortex_TOLA_Token_Integration'
            ),
            'wallet_system' => array(
                'name' => 'Wallet System Integration',
                'class' => 'Vortex_Wallet_Integration'
            ),
            'incentive_system' => array(
                'name' => 'Incentive System Integration',
                'class' => 'Vortex_Incentive_Integration'
            ),
            'accounting_system' => array(
                'name' => 'Accounting System Integration',
                'class' => 'Vortex_Accounting_Integration'
            )
        );
        
        foreach ( $vortex_integrations as $key => $integration_data ) {
            if ( class_exists( $integration_data['class'] ) ) {
                $integration = new $integration_data['class']();
                $this->register_vortex_integration( $key, $integration );
            }
        }
    }
    
    /**
     * Validate integration registration
     * 
     * @param bool $result Registration result
     * @param array $args Registration arguments
     * @return bool
     */
    public function validate_integration_registration( $result, $args ) {
        if ( ! is_array( $args ) ) {
            return false;
        }
        
        $name = isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '';
        $integration = isset( $args['integration'] ) ? $args['integration'] : null;
        
        // Check for empty name
        if ( empty( $name ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Integration registration attempted with empty name' );
            }
            return false;
        }
        
        // Check for null integration
        if ( ! $integration ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Integration registration attempted with null integration' );
            }
            return false;
        }
        
        // Check for Vortex-specific conflicts
        if ( strpos( strtolower( $name ), 'vortex' ) !== false ) {
            // Ensure Vortex integrations are properly prefixed
            if ( strpos( $name, 'vortex_' ) !== 0 ) {
                $name = 'vortex_' . $name;
                $args['name'] = $name;
            }
        }
        
        return $result;
    }
    
    /**
     * Prevent duplicate registration
     * 
     * @param bool $result Registration result
     * @param array $args Registration arguments
     * @return bool
     */
    public function prevent_duplicate_registration( $result, $args ) {
        if ( ! is_array( $args ) ) {
            return false;
        }
        
        $name = isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '';
        
        if ( empty( $name ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Duplicate registration attempted with empty name' );
            }
            return false;
        }
        
        if ( isset( $this->registered_integrations[ $name ] ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( "Vortex WooCommerce Fix: Duplicate registration prevented for '$name'" );
            }
            return false;
        }
        
        // Cache the registration
        $this->registered_integrations[ $name ] = array(
            'integration' => $args['integration'] ?? null,
            'timestamp' => current_time( 'mysql' )
        );
        
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
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Safe registration attempted with empty name' );
            }
            return false;
        }
        
        if ( ! $integration ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Safe registration attempted with null integration' );
            }
            return false;
        }
        
        if ( isset( $this->registered_integrations[ $name ] ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( "Vortex WooCommerce Fix: Integration '$name' already registered" );
            }
            return false;
        }
        
        // Cache the registration
        $this->registered_integrations[ $name ] = array(
            'integration' => $integration,
            'timestamp' => current_time( 'mysql' )
        );
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( "Vortex WooCommerce Fix: Successfully registered integration '$name'" );
        }
        
        return $result;
    }
    
    /**
     * Fix Vortex-specific integrations
     */
    private function fix_vortex_integrations() {
        // Fix known Vortex integration issues
        add_filter( 'woocommerce_blocks_integration_registry_register', function( $result, $args ) {
            if ( is_array( $args ) && ! empty( $args['name'] ) ) {
                $name = $args['name'];
                
                // Fix Vortex integration names
                if ( strpos( strtolower( $name ), 'vortex' ) !== false && strpos( $name, 'vortex_' ) !== 0 ) {
                    $args['name'] = 'vortex_' . $name;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "Vortex WooCommerce Fix: Fixed integration name from '$name' to '{$args['name']}'" );
                    }
                }
            }
            return $result;
        }, 1, 2 );
    }
    
    /**
     * Re-register integrations safely
     */
    private function re_register_integrations() {
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            return;
        }
        
        try {
            $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
            $current_integrations = $registry->get_all_registered();
            
            if ( ! is_array( $current_integrations ) ) {
                return;
            }
            
            // Clear and re-register each integration safely
            foreach ( $current_integrations as $name => $integration ) {
                if ( ! empty( $name ) && $integration ) {
                    // Remove existing registration
                    if ( method_exists( $registry, 'unregister' ) ) {
                        $registry->unregister( $name );
                    }
                    
                    // Re-register safely
                    try {
                        $registry->register( $name, $integration );
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( "Vortex WooCommerce Fix: Re-registered integration '$name'" );
                        }
                    } catch ( Exception $e ) {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'Vortex WooCommerce Fix: Failed to re-register integration ' . $name . ': ' . $e->getMessage() );
                        }
                    }
                }
            }
        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Error during re-registration: ' . $e->getMessage() );
            }
        }
    }
    
    /**
     * Check if required plugins are active
     * 
     * @return bool
     */
    private function are_plugins_active() {
        return class_exists( 'Vortex_AI_Engine' ) && 
               class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' );
    }
    
    /**
     * Clear integration cache
     */
    private function clear_integration_cache() {
        $this->registered_integrations = array();
        $this->vortex_integrations = array();
        
        // Clear any WordPress object cache for integrations
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( 'woocommerce_blocks_integrations', 'woocommerce' );
            wp_cache_delete( 'vortex_integrations', 'vortex' );
        }
    }
    
    /**
     * Cleanup on deactivation
     */
    public function cleanup() {
        $this->clear_integration_cache();
        
        // Remove any custom filters and actions
        remove_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'validate_integration_registration' ), 1 );
        remove_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'prevent_duplicate_registration' ), 1 );
        remove_filter( 'woocommerce_blocks_integration_registry_register', array( $this, 'safe_register_integration' ), 1 );
        remove_action( 'vortex_register_woocommerce_integration', array( $this, 'register_vortex_integration' ), 1 );
    }
}

/**
 * Initialize the Vortex WooCommerce integration fix
 */
function vortex_init_woocommerce_integration_fix() {
    // Only initialize if both Vortex AI Engine and WooCommerce are active
    if ( class_exists( 'Vortex_AI_Engine' ) && class_exists( 'WooCommerce' ) ) {
        Vortex_WooCommerce_Integration_Fix::get_instance();
    }
}

// Initialize early
add_action( 'plugins_loaded', 'vortex_init_woocommerce_integration_fix', 1 );

/**
 * Emergency fix for critical Vortex integration conflicts
 */
function vortex_emergency_integration_fix() {
    // Only run in emergency situations
    if ( ! defined( 'VORTEX_EMERGENCY_FIX' ) || ! VORTEX_EMERGENCY_FIX ) {
        return;
    }
    
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
        try {
            $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
            $integrations = $registry->get_all_registered();
            
            foreach ( $integrations as $name => $integration ) {
                if ( method_exists( $registry, 'unregister' ) ) {
                    $registry->unregister( $name );
                }
            }
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Emergency fix cleared all integrations' );
            }
        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Vortex WooCommerce Fix: Emergency fix error: ' . $e->getMessage() );
            }
        }
    }
}

// Emergency fix hook
add_action( 'init', 'vortex_emergency_integration_fix', 1 ); 