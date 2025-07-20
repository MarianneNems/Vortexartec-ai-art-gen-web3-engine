<?php
/**
 * Plugin Name:     VORTEX AI Engine For the ARTS
 * Plugin URI:      https://vortexartec.com
 * Description:     The VORTEX AI Engine For the ARTS powers token swap shortcodes, wallet management, metric rankings,
 *                   modal agreements, daily TOLA masterpieces, and real-time AI agent orchestration via Vault & AWS S3.
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

// Initialize logger first
require_once VORTEX_AI_ENGINE_PLUGIN_DIR . 'includes/class-vortex-logger.php';

register_activation_hook( __FILE__, function() {
    VortexAIEngine_DBSetup::create_tables();
    
    // Create logs table
    if (class_exists('VortexAIEngine_Logger')) {
        VortexAIEngine_Logger::create_logs_table();
    }
    
    update_option( 'vortex_ai_engine_version', VORTEX_AI_ENGINE_VERSION );
    error_log( '[VortexAI Plugin] Plugin activated successfully' );
});

add_action( 'init', function() {
    if ( ! is_admin() ) {
        add_action( 'wp_head', function() {
            echo '<meta http-equiv="X-Content-Type-Options" content="nosniff">' . "\n";
            echo '<meta http-equiv="X-Frame-Options" content="SAMEORIGIN">' . "\n";
            echo '<meta http-equiv="X-XSS-Protection" content="1; mode=block">' . "\n";
            $csp  = "default-src 'self'; ";
            $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; ";
            $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
            $csp .= "img-src 'self' data: https:; ";
            $csp .= "font-src 'self' https://fonts.gstatic.com; ";
            $csp .= "connect-src 'self' https: wss:; ";
            $csp .= "frame-ancestors 'self'; ";
            $csp .= "base-uri 'self'; ";
            $csp .= "form-action 'self';";
            echo '<meta http-equiv="Content-Security-Policy" content="' . esc_attr( $csp ) . '">' . "\n";
        });
    }
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

    // -----------------------------------------------------------------------------
    // STEP 1: Load vault-secrets algorithm files (only existing ones)
    // -----------------------------------------------------------------------------
    $algorithm_files = [
        'vault-secrets/algorithms/tier_subscription_algorithms.php',
        'vault-secrets/algorithms/cost_optimization_algorithms.php',
        'vault-secrets/algorithms/class-vortex-aws-services.php',
        'vault-secrets/algorithms/base_ai_orchestrator.php',
        'vault-secrets/algorithms/class-vortex-agreements.php',
        'vault-secrets/algorithms/class-vortex-shortcodes.php',
        'vault-secrets/algorithms/individual_agent_algorithms.php',
        'vault-secrets/algorithms/vault_integration.php',
        'vault-secrets/algorithms/class-vortex-tier-manager.php',
        'vault-secrets/algorithms/class-vortex-security.php',
        'vault-secrets/algorithms/class-vortex-core.php',
        'vault-secrets/algorithms/class-vortex-masterpiece-generator.php',
        'vault-secrets/algorithms/class-vortex-rate-limiter.php',
        'vault-secrets/algorithms/class-vortex-database.php',
        'vault-secrets/algorithms/class-vortex-s3.php',
        'vault-secrets/algorithms/class-vortex-ajax.php',
        'vault-secrets/algorithms/class-vortex-swap-interface.php',
        'vault-secrets/algorithms/class-vortex-tier-api-improved.php',
        'vault-secrets/algorithms/ai_orchestration.php',
    ];

    foreach ( $algorithm_files as $file ) {
        $path = VORTEX_AI_ENGINE_PLUGIN_DIR . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        } else {
            error_log( "[VortexAI Plugin] Missing algorithm file: $file" );
        }
    }

    // -----------------------------------------------------------------------------
    // STEP 2: Load admin interface
    // -----------------------------------------------------------------------------
    if ( file_exists( VORTEX_AI_ENGINE_PLUGIN_DIR . 'admin/class-vortex-admin.php' ) ) {
        require_once VORTEX_AI_ENGINE_PLUGIN_DIR . 'admin/class-vortex-admin.php';
    }
    
    if ( file_exists( VORTEX_AI_ENGINE_PLUGIN_DIR . 'admin/class-vortex-logs-viewer.php' ) ) {
        require_once VORTEX_AI_ENGINE_PLUGIN_DIR . 'admin/class-vortex-logs-viewer.php';
    }

    // -----------------------------------------------------------------------------
    // STEP 3: Load core includes (only existing ones)
    // -----------------------------------------------------------------------------
    $includes = [
        'ModelProviderInterface.php',
        'ProviderFactory.php',
        'class-vortex-enhanced-orchestrator.php',
        'class-vortex-feedback-controller.php',
        'class-vortex-api-endpoints.php',
        'class-vortex-secure-api-keys.php',
        'class-vortex-nft-database.php',
        'class-vortex-solana-integration.php',
        'class-vortex-web3-integration.php',
        'class-vortex-memory-api.php',
        'class-vortex-tier-manager.php',
        'class-vortex-shortcodes.php',
        'class-vortex-nft-shortcodes.php',
        'class-vortex-nft-ajax.php',
    ];

    foreach ( $includes as $file ) {
        $path = VORTEX_AI_ENGINE_PLUGIN_DIR . 'includes/' . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        } else {
            error_log( "[VortexAI Plugin] Missing include: $file" );
        }
    }

    // -----------------------------------------------------------------------------
    // STEP 4: Load provider files
    // -----------------------------------------------------------------------------
    $provider_files = [
        'providers/OpenAIProvider.php',
    ];

    foreach ( $provider_files as $file ) {
        $path = VORTEX_AI_ENGINE_PLUGIN_DIR . 'includes/' . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        } else {
            error_log( "[VortexAI Plugin] Missing provider file: $file" );
        }
    }

    // -----------------------------------------------------------------------------
    // STEP 5: Initialize classes with proper error handling
    // -----------------------------------------------------------------------------
    
    // Initialize logger
    $logger = null;
    if (class_exists('VortexAIEngine_Logger')) {
        $logger = VortexAIEngine_Logger::getInstance();
        $logger->info('Starting VORTEX AI Engine initialization', VortexAIEngine_Logger::CATEGORY_ACTIVATION);
    }
    
    try {
        // Initialize admin interface
        if ( class_exists( 'VortexAIEngine_Admin' ) ) {
            new VortexAIEngine_Admin();
        }

        // Initialize core orchestrator
        if ( class_exists( 'VortexAIEngine_EnhancedOrchestrator' ) ) {
            VortexAIEngine_EnhancedOrchestrator::getInstance();
        }

        // Initialize feedback controller
        if ( class_exists( 'VortexAIEngine_FeedbackController' ) ) {
            new VortexAIEngine_FeedbackController();
        }

        // Initialize memory API
        if ( class_exists( 'VortexAIEngine_Memory_API' ) ) {
            new VortexAIEngine_Memory_API();
        }

        // Initialize integrations (safe instantiation)
        if ( class_exists( 'VortexAIEngine_Solana_Integration' ) ) {
            new VortexAIEngine_Solana_Integration();
        }

        if ( class_exists( 'VortexAIEngine_Web3_Integration' ) ) {
            new VortexAIEngine_Web3_Integration();
        }

        // Initialize NFT components (after dependencies are loaded)
        if ( class_exists( 'VortexAIEngine_NFT_Shortcodes' ) ) {
            new VortexAIEngine_NFT_Shortcodes();
        }

        if ( class_exists( 'VortexAIEngine_NFT_Ajax' ) ) {
            new VortexAIEngine_NFT_Ajax();
        }

        // Initialize algorithm classes
        if ( class_exists( 'VortexAIEngine_Shortcodes' ) ) {
            VortexAIEngine_Shortcodes::getInstance();
        }

        if ( class_exists( 'VortexAIEngine_Agreements' ) ) {
            VortexAIEngine_Agreements::getInstance();
        }

        if ( class_exists( 'VortexAIEngine_IndividualShortcodes' ) ) {
            new VortexAIEngine_IndividualShortcodes();
        }

        if ( class_exists( 'VortexAIEngine_AIOrchestrator' ) ) {
            VortexAIEngine_AIOrchestrator::getInstance();
        }

        if ( class_exists( 'VortexAIEngine_Security' ) ) {
            VortexAIEngine_Security::getInstance();
        }

        if ( class_exists( 'VortexAIEngine_Vault' ) ) {
            VortexAIEngine_Vault::getInstance();
        }

        if ($logger) {
            $logger->info('All VORTEX AI Engine components initialized successfully', VortexAIEngine_Logger::CATEGORY_ACTIVATION);
        }
        error_log( '[VortexAI Plugin] All components initialized successfully' );

    } catch ( Exception $e ) {
        if ($logger) {
            $logger->error('Error during VORTEX AI Engine initialization: ' . $e->getMessage(), VortexAIEngine_Logger::CATEGORY_ACTIVATION, [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
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
    // Clean up any temporary data
    error_log( '[VortexAI Plugin] Plugin deactivated' );
});

// Uninstall hook
register_uninstall_hook( __FILE__, function() {
    // Remove plugin data if needed
    delete_option( 'vortex_ai_engine_version' );
    error_log( '[VortexAI Plugin] Plugin uninstalled' );
});
