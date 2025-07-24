<?php
/**
 * Vortex AI Engine - Conversion System Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Conversion System Class
 *
 * Handles TOLA to USD conversion with platform restrictions.
 */
class Vortex_Conversion_System {

    /**
     * Instance
     *
     * @var Vortex_Conversion_System
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Vortex_Conversion_System
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
        add_action('wp_ajax_vortex_convert_tola', array($this, 'convert_tola_to_usd'));
        add_action('wp_ajax_nopriv_vortex_convert_tola', array($this, 'convert_tola_to_usd'));
    }

    /**
     * Initialize
     */
    public function init() {
        // Initialize conversion system
    }

    /**
     * Convert TOLA to USD
     */
    public function convert_tola_to_usd() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vortex_tola_conversion')) {
            wp_die('Security check failed');
        }

        $user_id = intval($_POST['user_id']);
        $tola_amount = floatval($_POST['tola_amount']);

        // Validate user
        if (!$user_id || !get_user_by('id', $user_id)) {
            wp_send_json_error('Invalid user');
        }

        // Check conversion eligibility
        $eligibility = $this->check_conversion_eligibility($user_id, $tola_amount);
        if (!$eligibility['eligible']) {
            wp_send_json_error($eligibility['message']);
        }

        // Process conversion
        $result = $this->process_conversion($user_id, $tola_amount);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Check conversion eligibility
     *
     * @param int $user_id User ID
     * @param float $tola_amount TOLA amount
     * @return array
     */
    public function check_conversion_eligibility($user_id, $tola_amount) {
        // Check if 1,000 artists are registered
        $artist_count = $this->get_registered_artist_count();
        
        if ($artist_count < 1000) {
            return array(
                'eligible' => false,
                'message' => 'Conversion not available until 1,000 artists are registered. Current: ' . $artist_count
            );
        }

        // Check minimum conversion amount
        if ($tola_amount < 100) {
            return array(
                'eligible' => false,
                'message' => 'Minimum conversion amount is 100 TOLA'
            );
        }

        // Check user's TOLA balance
        $wallet_manager = Vortex_Wallet_Manager::get_instance();
        $balance = $wallet_manager->get_tola_balance($user_id);

        if ($balance < $tola_amount) {
            return array(
                'eligible' => false,
                'message' => 'Insufficient TOLA balance'
            );
        }

        return array(
            'eligible' => true,
            'message' => 'Eligible for conversion'
        );
    }

    /**
     * Process conversion
     *
     * @param int $user_id User ID
     * @param float $tola_amount TOLA amount
     * @return array
     */
    public function process_conversion($user_id, $tola_amount) {
        global $wpdb;

        // Get conversion rate
        $accounting_system = Vortex_Accounting_System::get_instance();
        $conversion_rate = $accounting_system->get_conversion_rate();
        $usd_amount = $tola_amount * $conversion_rate;

        // Deduct TOLA from wallet
        $wallet_manager = Vortex_Wallet_Manager::get_instance();
        $wallet_result = $wallet_manager->deduct_tola_balance($user_id, $tola_amount);

        if (!$wallet_result['success']) {
            return array(
                'success' => false,
                'message' => 'Failed to deduct TOLA: ' . $wallet_result['message']
            );
        }

        // Record transaction
        $accounting_result = $accounting_system->record_transaction(
            $user_id,
            'tola_to_usd_conversion',
            $tola_amount,
            $usd_amount,
            $conversion_rate
        );

        if (!$accounting_result['success']) {
            // Revert wallet deduction
            $wallet_manager->add_tola_balance($user_id, $tola_amount);
            
            return array(
                'success' => false,
                'message' => 'Failed to record transaction: ' . $accounting_result['message']
            );
        }

        // Add USD to user's account (implement as needed)
        $this->add_usd_balance($user_id, $usd_amount);

        return array(
            'success' => true,
            'message' => 'Conversion completed successfully',
            'tola_amount' => $tola_amount,
            'usd_amount' => $usd_amount,
            'conversion_rate' => $conversion_rate,
            'transaction_id' => $accounting_result['transaction_id']
        );
    }

    /**
     * Get registered artist count
     *
     * @return int
     */
    private function get_registered_artist_count() {
        $users = get_users(array(
            'role' => 'artist',
            'count_total' => true
        ));

        return $users;
    }

    /**
     * Add USD balance
     *
     * @param int $user_id User ID
     * @param float $usd_amount USD amount
     */
    private function add_usd_balance($user_id, $usd_amount) {
        // Implement USD balance addition
        // This could be integrated with WooCommerce, PayPal, or other payment systems
        update_user_meta($user_id, 'vortex_usd_balance', 
            floatval(get_user_meta($user_id, 'vortex_usd_balance', true)) + $usd_amount
        );
    }
} 