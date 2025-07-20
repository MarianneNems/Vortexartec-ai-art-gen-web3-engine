<?php
/**
 * Enqueues front-end assets for the Swap widget
 *
 * @package    VortexAIEngine
 * @version    1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_SwapInterface {
    private static $instance = null;

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->register_hooks();
        }
        return self::$instance;
    }

    private function register_hooks() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'vortex-swap-css',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/swap.css',
            [],
            VORTEX_AI_ENGINE_VERSION
        );

        wp_enqueue_script(
            'vortex-swap-js',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/swap.js',
            [ 'jquery' ],
            VORTEX_AI_ENGINE_VERSION,
            true
        );

        wp_localize_script(
            'vortex-swap-js',
            'vortexSwapConfig',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'vortex_swap_nonce' ),
            ]
        );
    }
}

add_action( 'plugins_loaded', [ 'VortexAIEngine_SwapInterface', 'getInstance' ] ); 