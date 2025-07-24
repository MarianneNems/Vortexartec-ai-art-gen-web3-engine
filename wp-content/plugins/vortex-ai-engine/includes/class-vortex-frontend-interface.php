<?php
/**
 * Vortex AI Engine - Frontend Interface Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Frontend Interface Class
 *
 * Handles frontend user interface and interactions.
 */
class Vortex_Frontend_Interface {

    /**
     * Instance
     *
     * @var Vortex_Frontend_Interface
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Vortex_Frontend_Interface
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('vortex_wallet', array($this, 'wallet_shortcode'));
        add_shortcode('vortex_incentives', array($this, 'incentives_shortcode'));
    }

    /**
     * Initialize
     */
    public function init() {
        // Initialize frontend interface
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'vortex-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/frontend.js',
            array('jquery'),
            '3.0.0',
            true
        );

        wp_localize_script('vortex-frontend', 'vortex_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_frontend_nonce')
        ));
    }

    /**
     * Wallet shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wallet_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your wallet.</p>';
        }

        $user_id = get_current_user_id();
        $wallet_manager = Vortex_Wallet_Manager::get_instance();
        $tola_balance = $wallet_manager->get_tola_balance($user_id);

        $output = '<div class="vortex-wallet">';
        $output .= '<h3>Your TOLA Wallet</h3>';
        $output .= '<p>Balance: ' . number_format($tola_balance, 8) . ' TOLA</p>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Incentives shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function incentives_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your incentives.</p>';
        }

        $user_id = get_current_user_id();
        $incentive_auditor = Vortex_Incentive_Auditor::get_instance();
        $incentive_history = $incentive_auditor->get_user_incentive_history($user_id);

        $output = '<div class="vortex-incentives">';
        $output .= '<h3>Your Incentive History</h3>';
        
        if (!empty($incentive_history)) {
            $output .= '<ul>';
            foreach ($incentive_history as $incentive) {
                $output .= '<li>' . $incentive->action_type . ': ' . $incentive->tola_amount . ' TOLA</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No incentives earned yet.</p>';
        }
        
        $output .= '</div>';

        return $output;
    }
} 