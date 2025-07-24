<?php
/**
 * WooCommerce Blocks IntegrationRegistry Debug Tool
 * 
 * This script provides comprehensive debugging for IntegrationRegistry conflicts
 * 
 * @package Vortex-AI-Engine
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    // Load WordPress if not already loaded
    if ( ! function_exists( 'wp_loaded' ) ) {
        $wp_load_path = dirname( __FILE__ ) . '/wp-load.php';
        if ( file_exists( $wp_load_path ) ) {
            require_once $wp_load_path;
        } else {
            die( 'WordPress not found. Please run this script from WordPress root directory.' );
        }
    }
}

/**
 * Integration Registry Debug Class
 */
class Vortex_Integration_Registry_Debug {
    
    private $debug_data = array();
    private $log_file;
    
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/integration-registry-debug.log';
        $this->init_debug();
    }
    
    /**
     * Initialize debugging
     */
    private function init_debug() {
        // Hook into WordPress early
        add_action( 'init', array( $this, 'start_debugging' ), 1 );
        add_action( 'wp_loaded', array( $this, 'analyze_integrations' ), 999 );
        add_action( 'shutdown', array( $this, 'generate_debug_report' ), 999 );
        
        // Hook into WooCommerce Blocks specifically
        add_action( 'woocommerce_blocks_loaded', array( $this, 'debug_blocks_loaded' ), 1 );
        add_action( 'woocommerce_blocks_register_integration', array( $this, 'debug_integration_registration' ), 1, 2 );
        
        // Monitor error log
        add_action( 'admin_init', array( $this, 'monitor_error_log' ) );
    }
    
    /**
     * Start debugging process
     */
    public function start_debugging() {
        $this->log( '=== INTEGRATION REGISTRY DEBUG START ===' );
        $this->log( 'Timestamp: ' . current_time( 'mysql' ) );
        $this->log( 'PHP Version: ' . PHP_VERSION );
        $this->log( 'WordPress Version: ' . get_bloginfo( 'version' ) );
        
        // Check WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
            $this->log( 'WooCommerce Version: ' . WC()->version );
        } else {
            $this->log( 'WARNING: WooCommerce not active' );
        }
        
        // Check WooCommerce Blocks
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            $this->log( 'WooCommerce Blocks IntegrationRegistry found' );
            if ( defined( 'WC_BLOCKS_VERSION' ) ) {
                $this->log( 'WooCommerce Blocks Version: ' . WC_BLOCKS_VERSION );
            }
        } else {
            $this->log( 'ERROR: WooCommerce Blocks IntegrationRegistry not found' );
        }
        
        // Check active plugins
        $this->debug_active_plugins();
        
        // Check theme
        $this->debug_theme_info();
    }
    
    /**
     * Debug active plugins
     */
    private function debug_active_plugins() {
        $this->log( '--- ACTIVE PLUGINS ---' );
        
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $active_plugins = get_option( 'active_plugins' );
        $all_plugins = get_plugins();
        
        foreach ( $active_plugins as $plugin ) {
            if ( isset( $all_plugins[ $plugin ] ) ) {
                $plugin_data = $all_plugins[ $plugin ];
                $this->log( "Plugin: {$plugin_data['Name']} v{$plugin_data['Version']}" );
                
                // Check for potential conflicts
                if ( strpos( strtolower( $plugin_data['Name'] ), 'woocommerce' ) !== false ) {
                    $this->log( "  -> WooCommerce-related plugin detected" );
                }
            }
        }
    }
    
    /**
     * Debug theme information
     */
    private function debug_theme_info() {
        $this->log( '--- THEME INFO ---' );
        
        $theme = wp_get_theme();
        $this->log( "Active Theme: {$theme->get('Name')} v{$theme->get('Version')}" );
        
        if ( $theme->parent() ) {
            $parent_theme = $theme->parent();
            $this->log( "Parent Theme: {$parent_theme->get('Name')} v{$parent_theme->get('Version')}" );
        }
        
        // Check for WooCommerce compatibility
        $theme_supports = array(
            'woocommerce' => current_theme_supports( 'woocommerce' ),
            'wc-product-gallery-zoom' => current_theme_supports( 'wc-product-gallery-zoom' ),
            'wc-product-gallery-lightbox' => current_theme_supports( 'wc-product-gallery-lightbox' ),
            'wc-product-gallery-slider' => current_theme_supports( 'wc-product-gallery-slider' ),
        );
        
        foreach ( $theme_supports as $feature => $supported ) {
            $this->log( "Theme supports {$feature}: " . ( $supported ? 'YES' : 'NO' ) );
        }
    }
    
    /**
     * Debug WooCommerce Blocks loaded
     */
    public function debug_blocks_loaded() {
        $this->log( '--- WOOCOMMERCE BLOCKS LOADED ---' );
        
        try {
            $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
            $this->log( 'IntegrationRegistry instance created successfully' );
            
            // Get initial integrations
            $integrations = $registry->get_all_registered();
            $this->log( "Initial integrations count: " . count( $integrations ) );
            
            // Store for later comparison
            $this->debug_data['initial_integrations'] = $integrations;
            
        } catch ( Exception $e ) {
            $this->log( 'ERROR: Failed to get IntegrationRegistry instance: ' . $e->getMessage() );
        }
    }
    
    /**
     * Debug integration registration
     */
    public function debug_integration_registration( $name, $integration ) {
        $this->log( "--- INTEGRATION REGISTRATION ATTEMPT ---" );
        $this->log( "Name: " . ( empty( $name ) ? 'EMPTY' : $name ) );
        $this->log( "Integration Class: " . ( is_object( $integration ) ? get_class( $integration ) : 'Not an object' ) );
        $this->log( "Integration Type: " . gettype( $integration ) );
        
        // Check for empty name
        if ( empty( $name ) ) {
            $this->log( "WARNING: Empty integration name detected!" );
            $this->debug_data['empty_names'][] = array(
                'name' => $name,
                'integration' => $integration,
                'backtrace' => debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 )
            );
        }
        
        // Check for duplicate registration
        if ( isset( $this->debug_data['registered_integrations'][ $name ] ) ) {
            $this->log( "WARNING: Duplicate registration attempt for: $name" );
            $this->debug_data['duplicates'][] = $name;
        }
        
        // Store registration
        $this->debug_data['registered_integrations'][ $name ] = array(
            'integration' => $integration,
            'timestamp' => microtime( true ),
            'backtrace' => debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 )
        );
    }
    
    /**
     * Analyze integrations
     */
    public function analyze_integrations() {
        $this->log( '--- INTEGRATION ANALYSIS ---' );
        
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            $this->log( 'ERROR: IntegrationRegistry not available for analysis' );
            return;
        }
        
        try {
            $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
            $integrations = $registry->get_all_registered();
            
            $this->log( "Final integrations count: " . count( $integrations ) );
            
            $empty_names = 0;
            $valid_integrations = 0;
            
            foreach ( $integrations as $name => $integration ) {
                if ( empty( $name ) ) {
                    $empty_names++;
                    $this->log( "PROBLEM: Integration with empty name found" );
                } else {
                    $valid_integrations++;
                    $this->log( "Valid integration: $name (" . get_class( $integration ) . ")" );
                }
            }
            
            $this->log( "Summary: $valid_integrations valid, $empty_names empty names" );
            
            // Store analysis results
            $this->debug_data['analysis'] = array(
                'total_integrations' => count( $integrations ),
                'empty_names' => $empty_names,
                'valid_integrations' => $valid_integrations,
                'integrations' => $integrations
            );
            
        } catch ( Exception $e ) {
            $this->log( 'ERROR: Failed to analyze integrations: ' . $e->getMessage() );
        }
    }
    
    /**
     * Monitor error log
     */
    public function monitor_error_log() {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if ( file_exists( $log_file ) ) {
            $log_content = file_get_contents( $log_file );
            
            // Check for IntegrationRegistry errors
            if ( strpos( $log_content, 'IntegrationRegistry::register' ) !== false ) {
                $this->log( '--- INTEGRATION REGISTRY ERRORS FOUND ---' );
                
                $lines = explode( "\n", $log_content );
                $recent_lines = array_slice( $lines, -100 ); // Last 100 lines
                
                $integration_errors = array();
                foreach ( $recent_lines as $line ) {
                    if ( strpos( $line, 'IntegrationRegistry::register' ) !== false ) {
                        $integration_errors[] = $line;
                    }
                }
                
                $this->log( "Found " . count( $integration_errors ) . " IntegrationRegistry errors in recent logs" );
                
                foreach ( array_slice( $integration_errors, -10 ) as $error ) {
                    $this->log( "Error: " . trim( $error ) );
                }
                
                $this->debug_data['log_errors'] = $integration_errors;
            }
        }
    }
    
    /**
     * Generate debug report
     */
    public function generate_debug_report() {
        $this->log( '--- DEBUG REPORT SUMMARY ---' );
        
        // Summary statistics
        $this->log( "Total registrations tracked: " . count( $this->debug_data['registered_integrations'] ?? array() ) );
        $this->log( "Empty names detected: " . count( $this->debug_data['empty_names'] ?? array() ) );
        $this->log( "Duplicates detected: " . count( $this->debug_data['duplicates'] ?? array() ) );
        
        // Recommendations
        $this->log( '--- RECOMMENDATIONS ---' );
        
        if ( ! empty( $this->debug_data['empty_names'] ) ) {
            $this->log( "1. IMPLEMENT FIX: Empty integration names detected - deploy the integration fix" );
        }
        
        if ( ! empty( $this->debug_data['duplicates'] ) ) {
            $this->log( "2. CHECK PLUGINS: Duplicate registrations detected - review active plugins" );
        }
        
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
            $this->log( "3. UPDATE WOOCOMMERCE: WooCommerce Blocks not found - update WooCommerce" );
        }
        
        $this->log( '=== DEBUG COMPLETE ===' );
        
        // Create HTML report
        $this->create_html_report();
    }
    
    /**
     * Create HTML debug report
     */
    private function create_html_report() {
        $html_report = WP_CONTENT_DIR . '/integration-registry-debug-report.html';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Integration Registry Debug Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .error { color: red; }
        .warning { color: orange; }
        .success { color: green; }
        .log { background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>WooCommerce Blocks Integration Registry Debug Report</h1>
    <p>Generated: ' . current_time( 'mysql' ) . '</p>';
        
        // Summary
        $html .= '<div class="section">
        <h2>Summary</h2>
        <p><strong>Total Registrations:</strong> ' . count( $this->debug_data['registered_integrations'] ?? array() ) . '</p>
        <p><strong>Empty Names:</strong> ' . count( $this->debug_data['empty_names'] ?? array() ) . '</p>
        <p><strong>Duplicates:</strong> ' . count( $this->debug_data['duplicates'] ?? array() ) . '</p>
        </div>';
        
        // Problems
        if ( ! empty( $this->debug_data['empty_names'] ) ) {
            $html .= '<div class="section">
            <h2 class="error">Problems Found</h2>
            <h3>Empty Integration Names</h3>
            <table>
                <tr><th>Count</th><th>Details</th></tr>';
            
            foreach ( $this->debug_data['empty_names'] as $index => $empty ) {
                $html .= '<tr><td>' . ( $index + 1 ) . '</td><td>Empty name detected</td></tr>';
            }
            
            $html .= '</table></div>';
        }
        
        // Log content
        if ( file_exists( $this->log_file ) ) {
            $log_content = file_get_contents( $this->log_file );
            $html .= '<div class="section">
            <h2>Debug Log</h2>
            <div class="log">' . nl2br( esc_html( $log_content ) ) . '</div>
            </div>';
        }
        
        $html .= '</body></html>';
        
        file_put_contents( $html_report, $html );
        $this->log( "HTML report created: $html_report" );
    }
    
    /**
     * Log message
     */
    private function log( $message ) {
        $timestamp = current_time( 'mysql' );
        $log_entry = "[$timestamp] $message" . PHP_EOL;
        
        file_put_contents( $this->log_file, $log_entry, FILE_APPEND | LOCK_EX );
    }
}

/**
 * Initialize debug tool
 */
function vortex_init_integration_debug() {
    if ( class_exists( 'WooCommerce' ) ) {
        new Vortex_Integration_Registry_Debug();
    }
}

// Initialize early
add_action( 'plugins_loaded', 'vortex_init_integration_debug', 1 );

/**
 * Admin page for debug results
 */
add_action( 'admin_menu', function() {
    add_management_page(
        'Integration Registry Debug',
        'Integration Debug',
        'manage_options',
        'integration-registry-debug',
        function() {
            echo '<div class="wrap">';
            echo '<h1>Integration Registry Debug Results</h1>';
            
            $log_file = WP_CONTENT_DIR . '/integration-registry-debug.log';
            $html_report = WP_CONTENT_DIR . '/integration-registry-debug-report.html';
            
            if ( file_exists( $log_file ) ) {
                echo '<h2>Debug Log</h2>';
                echo '<div style="background: #f5f5f5; padding: 15px; max-height: 400px; overflow-y: scroll;">';
                echo '<pre>' . esc_html( file_get_contents( $log_file ) ) . '</pre>';
                echo '</div>';
            }
            
            if ( file_exists( $html_report ) ) {
                echo '<h2>Detailed Report</h2>';
                echo '<p><a href="' . content_url( 'integration-registry-debug-report.html' ) . '" target="_blank">View Full HTML Report</a></p>';
            }
            
            echo '<h2>Quick Actions</h2>';
            echo '<p><a href="' . admin_url( 'admin.php?page=integration-registry-debug&action=clear_log' ) . '" class="button">Clear Debug Log</a></p>';
            echo '<p><a href="' . admin_url( 'admin.php?page=integration-registry-debug&action=run_debug' ) . '" class="button button-primary">Run Debug Again</a></p>';
            
            echo '</div>';
        }
    );
}); 