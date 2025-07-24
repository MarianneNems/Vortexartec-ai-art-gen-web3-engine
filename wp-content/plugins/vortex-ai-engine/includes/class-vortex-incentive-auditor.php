<?php
/**
 * Vortex AI Engine - Incentive Auditor Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Incentive Auditor Class
 *
 * Handles automated TOLA token distribution and auditing.
 */
class Vortex_Incentive_Auditor {

    /**
     * Instance
     *
     * @var Vortex_Incentive_Auditor
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Vortex_Incentive_Auditor
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
        add_action('wp_ajax_vortex_distribute_tola', array($this, 'distribute_tola'));
        add_action('wp_ajax_nopriv_vortex_distribute_tola', array($this, 'distribute_tola'));
    }

    /**
     * Initialize
     */
    public function init() {
        // Initialize incentive system
        $this->setup_incentive_tables();
    }

    /**
     * Setup incentive tables
     */
    private function setup_incentive_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_incentives';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            tola_amount decimal(18,8) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            distributed_at datetime NULL,
            transaction_hash varchar(255) NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Distribute TOLA tokens
     */
    public function distribute_tola() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vortex_tola_distribution')) {
            wp_die('Security check failed');
        }

        $user_id = intval($_POST['user_id']);
        $action_type = sanitize_text_field($_POST['action_type']);
        $amount = floatval($_POST['amount']);

        // Validate user
        if (!$user_id || !get_user_by('id', $user_id)) {
            wp_send_json_error('Invalid user');
        }

        // Check if user meets conversion requirements
        if (!$this->can_convert_to_dollars($user_id)) {
            wp_send_json_error('User does not meet conversion requirements');
        }

        // Process distribution
        $result = $this->process_tola_distribution($user_id, $action_type, $amount);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Process TOLA distribution
     *
     * @param int $user_id User ID
     * @param string $action_type Action type
     * @param float $amount Amount to distribute
     * @return array
     */
    public function process_tola_distribution($user_id, $action_type, $amount) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_incentives';

        // Check for duplicate distribution
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND action_type = %s AND status = 'completed'",
            $user_id,
            $action_type
        ));

        if ($existing) {
            return array(
                'success' => false,
                'message' => 'Incentive already distributed for this action'
            );
        }

        // Insert incentive record
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action_type' => $action_type,
                'tola_amount' => $amount,
                'status' => 'pending'
            ),
            array('%d', '%s', '%f', '%s')
        );

        if (!$result) {
            return array(
                'success' => false,
                'message' => 'Failed to record incentive'
            );
        }

        // Update wallet balance
        $wallet_manager = Vortex_Wallet_Manager::get_instance();
        $wallet_result = $wallet_manager->add_tola_balance($user_id, $amount);

        if (!$wallet_result['success']) {
            // Revert incentive record
            $wpdb->delete($table_name, array('id' => $wpdb->insert_id));
            
            return array(
                'success' => false,
                'message' => 'Failed to update wallet: ' . $wallet_result['message']
            );
        }

        // Mark as completed
        $wpdb->update(
            $table_name,
            array(
                'status' => 'completed',
                'distributed_at' => current_time('mysql'),
                'transaction_hash' => $wallet_result['transaction_hash']
            ),
            array('id' => $wpdb->insert_id)
        );

        // Log the distribution
        $this->log_distribution($user_id, $action_type, $amount, $wallet_result['transaction_hash']);

        return array(
            'success' => true,
            'message' => 'TOLA tokens distributed successfully',
            'transaction_hash' => $wallet_result['transaction_hash'],
            'new_balance' => $wallet_result['new_balance']
        );
    }

    /**
     * Check if user can convert to dollars
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function can_convert_to_dollars($user_id) {
        // Check if 1,000 artists are registered
        $artist_count = $this->get_registered_artist_count();
        
        if ($artist_count < 1000) {
            return false;
        }

        // Check user's TOLA balance
        $wallet_manager = Vortex_Wallet_Manager::get_instance();
        $balance = $wallet_manager->get_tola_balance($user_id);

        // Minimum balance required for conversion
        return $balance >= 100; // 100 TOLA minimum
    }

    /**
     * Get registered artist count
     *
     * @return int
     */
    public function get_registered_artist_count() {
        $users = get_users(array(
            'role' => 'artist',
            'count_total' => true
        ));

        return $users;
    }

    /**
     * Log distribution
     *
     * @param int $user_id User ID
     * @param string $action_type Action type
     * @param float $amount Amount
     * @param string $transaction_hash Transaction hash
     */
    private function log_distribution($user_id, $action_type, $amount, $transaction_hash) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'action' => 'tola_distribution',
            'action_type' => $action_type,
            'amount' => $amount,
            'transaction_hash' => $transaction_hash,
            'status' => 'success'
        );

        // Store in WordPress options for audit trail
        $audit_log = get_option('vortex_audit_log', array());
        $audit_log[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($audit_log) > 1000) {
            $audit_log = array_slice($audit_log, -1000);
        }

        update_option('vortex_audit_log', $audit_log);
    }

    /**
     * Get user incentive history
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_user_incentive_history($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_incentives';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
    }

    /**
     * Get total distributed TOLA
     *
     * @return float
     */
    public function get_total_distributed_tola() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_incentives';

        $result = $wpdb->get_var(
            "SELECT SUM(tola_amount) FROM $table_name WHERE status = 'completed'"
        );

        return $result ? floatval($result) : 0;
    }
} 