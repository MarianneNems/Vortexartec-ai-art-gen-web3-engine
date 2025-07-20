<?php
/**
 * Manages the Seed-Commitment & TOLA-Masterpiece modals:
 * - Enqueues CSS/JS on front-end & product editor
 * - Prints modal markup in footer
 *
 * @package    VortexAIEngine
 * @version    2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VortexAIEngine_Agreements')) {
class VortexAIEngine_Agreements {
    /** @var self|null */
    private static $instance = null;

    /** Register hooks */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /** Attach WP actions */
    private function hooks() {
        add_action( 'wp_enqueue_scripts',   [ $this, 'enqueue_assets' ] );
        add_action( 'admin_enqueue_scripts',[ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer',            [ $this, 'print_modals' ] );
        add_action( 'admin_footer',         [ $this, 'print_modals' ] );
    }

    /**
     * Enqueue modal CSS & JS, localize_ajax data
     */
    public function enqueue_assets() {
        // On front-end, show on registration form pages (or everywhere if you prefer)
        // On admin, only on WooCommerce product editor
        if ( is_admin() ) {
            $screen = get_current_screen();
            if ( 'product' !== $screen->post_type ) {
                return;
            }
        } else {
            // If you only want on registration, you could check is_page('register') or #registerform
            // For simplicity, we'll load everywhere front-end:
        }

        // CSS
        wp_enqueue_style(
            'vortex-agreements-css',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/vortex-modal-agreements.css',
            [],
            VORTEX_AI_ENGINE_VERSION
        );

        // JS
        wp_enqueue_script(
            'vortex-agreements-js',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/agreements.js',
            [ 'jquery' ],
            VORTEX_AI_ENGINE_VERSION,
            true
        );

        // Localize AJAX URL, nonce, context
        wp_localize_script(
            'vortex-agreements-js',
            'vortexModalAgreements',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'vortex_agreement_nonce' ),
                'isAdmin' => is_admin() ? 1 : 0,
            ]
        );
    }

    /** Output the two modals (hidden by default) */
    public function print_modals() {
        ?>
        <!-- Seed Commitment Modal -->
        <div id="vortex-seed-backdrop" class="vortex-modal-overlay"></div>
        <div id="vortex-seed-modal" class="vortex-modal" role="dialog" aria-modal="true">
            <div class="vortex-modal-header">
                <h3><?php esc_html_e( 'Seed Artwork Commitment', 'vortex-ai-engine' ); ?></h3>
            </div>
            <div class="vortex-modal-body vortex-agreement-text">
                <p><?php esc_html_e( 'To unlock the Artist Dashboard, you must commit to uploading at least 2 artworks per week into your private library.', 'vortex-ai-engine' ); ?></p>
            </div>
            <div class="vortex-modal-footer">
                <div class="vortex-agreement-actions">
                    <button id="vortex-seed-decline" class="vortex-agreement-btn vortex-btn-decline">
                        <?php esc_html_e( 'Decline', 'vortex-ai-engine' ); ?>
                    </button>
                    <button id="vortex-seed-agree" class="vortex-agreement-btn vortex-btn-agree">
                        <?php esc_html_e( 'I Agree', 'vortex-ai-engine' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- TOLA Masterpiece Modal -->
        <div id="vortex-tola-backdrop" class="vortex-modal-overlay"></div>
        <div id="vortex-tola-modal" class="vortex-modal" role="dialog" aria-modal="true">
            <div class="vortex-modal-header">
                <h3><?php esc_html_e( 'TOLA Masterpiece Participation', 'vortex-ai-engine' ); ?></h3>
            </div>
            <div class="vortex-modal-body vortex-agreement-text">
                <p><?php esc_html_e( 'Would you like this artwork to participate in the daily TOLA Masterpiece generation?', 'vortex-ai-engine' ); ?></p>
            </div>
            <div class="vortex-modal-footer">
                <div class="vortex-agreement-actions">
                    <button id="vortex-tola-decline" class="vortex-agreement-btn vortex-btn-decline">
                        <?php esc_html_e( 'Decline', 'vortex-ai-engine' ); ?>
                    </button>
                    <button id="vortex-tola-agree" class="vortex-agreement-btn vortex-btn-agree">
                        <?php esc_html_e( 'Participate', 'vortex-ai-engine' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

// Kickoff
VortexAIEngine_Agreements::getInstance();
} 