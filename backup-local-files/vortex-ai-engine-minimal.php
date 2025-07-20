<?php
/**
 * Plugin Name:     VORTEX AI Engine For the ARTS (Minimal Test)
 * Plugin URI:      https://vortexartec.com
 * Description:     Minimal test version of VORTEX AI Engine
 * Version:         2.1.0
 * Author:          VORTEX ARTEC
 * Author URI:      https://vortexartec.com
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     vortex-ai-engine
 * Domain Path:     /languages
 * Requires at least: 5.0
 * Tested up to:    6.4
 * Requires PHP:    7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin version
if ( ! defined( 'VORTEX_AI_ENGINE_VERSION' ) ) {
    define( 'VORTEX_AI_ENGINE_VERSION', '2.1.0' );
}

// Plugin directories
if ( ! defined( 'VORTEX_AI_ENGINE_PLUGIN_FILE' ) ) {
    define( 'VORTEX_AI_ENGINE_PLUGIN_FILE', __FILE__ );
    define( 'VORTEX_AI_ENGINE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'VORTEX_AI_ENGINE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    define( 'VORTEX_AI_ENGINE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Database check
function vortex_ai_engine_check_database_connection() {
    global $wpdb;
    $wpdb->suppress_errors();
    $result = $wpdb->get_var( "SELECT 1" );
    $wpdb->suppress_errors( false );
    return $result === '1';
}

// Optional Composer autoload
$autoload = VORTEX_AI_ENGINE_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

// Database setup
require_once VORTEX_AI_ENGINE_PLUGIN_DIR . 'includes/class-vortex-db-setup.php';

register_activation_hook( __FILE__, function() {
    VortexAIEngine_DBSetup::create_tables();
    update_option( 'vortex_ai_engine_version', VORTEX_AI_ENGINE_VERSION );
    error_log( '[VortexAI Plugin] Plugin activated successfully' );
});

add_action( 'plugins_loaded', function() {
    if ( ! vortex_ai_engine_check_database_connection() ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>VORTEX AI Engine:</strong> Database connection unavailable. Please check your WordPress database settings.';
            echo '</p></div>';
        });
        error_log( '[VortexAI Plugin] Database connection unavailable' );
    }

    // Load only essential files for testing
    $essential_files = [
        'vault-secrets/algorithms/class-vortex-shortcodes.php',
        'vault-secrets/algorithms/class-vortex-agreements.php',
        'vault-secrets/algorithms/individual_agent_algorithms.php',
        'vault-secrets/algorithms/base_ai_orchestrator.php',
        'vault-secrets/algorithms/class-vortex-security.php',
        'vault-secrets/algorithms/vault_integration.php',
    ];

    foreach ( $essential_files as $file ) {
        $path = VORTEX_AI_ENGINE_PLUGIN_DIR . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
            error_log( "[VortexAI Plugin] Loaded: $file" );
        } else {
            error_log( "[VortexAI Plugin] Missing: $file" );
        }
    }

    // Initialize only essential classes
    try {
        if ( class_exists( 'VortexAIEngine_Shortcodes' ) ) {
            VortexAIEngine_Shortcodes::getInstance();
            error_log( '[VortexAI Plugin] Shortcodes initialized' );
        }

        if ( class_exists( 'VortexAIEngine_Agreements' ) ) {
            VortexAIEngine_Agreements::getInstance();
            error_log( '[VortexAI Plugin] Agreements initialized' );
        }

        if ( class_exists( 'VortexAIEngine_IndividualShortcodes' ) ) {
            new VortexAIEngine_IndividualShortcodes();
            error_log( '[VortexAI Plugin] IndividualShortcodes initialized' );
        }

        if ( class_exists( 'VortexAIEngine_AIOrchestrator' ) ) {
            VortexAIEngine_AIOrchestrator::getInstance();
            error_log( '[VortexAI Plugin] AIOrchestrator initialized' );
        }

        if ( class_exists( 'VortexAIEngine_Security' ) ) {
            VortexAIEngine_Security::getInstance();
            error_log( '[VortexAI Plugin] Security initialized' );
        }

        if ( class_exists( 'VortexAIEngine_Vault' ) ) {
            VortexAIEngine_Vault::getInstance();
            error_log( '[VortexAI Plugin] Vault initialized' );
        }

        error_log( '[VortexAI Plugin] All essential components initialized successfully' );

    } catch ( Exception $e ) {
        error_log( '[VortexAI Plugin] Error during initialization: ' . $e->getMessage() );
        
        add_action( 'admin_notices', function() use ( $e ) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>VORTEX AI Engine Error:</strong> ' . esc_html( $e->getMessage() );
            echo '</p></div>';
        });
    }
});

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    error_log( '[VortexAI Plugin] Plugin deactivated' );
});

// Uninstall hook
register_uninstall_hook( __FILE__, function() {
    delete_option( 'vortex_ai_engine_version' );
    error_log( '[VortexAI Plugin] Plugin uninstalled' );
}); 