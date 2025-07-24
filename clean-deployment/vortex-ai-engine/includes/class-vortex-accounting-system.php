<?php
/**
 * Vortex AI Engine - Accounting System Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Accounting System Class
 *
 * Handles financial accounting and TOLA conversion tracking.
 */
class Vortex_Accounting_System {

    /**
     * Instance
     *
     * @var Vortex_Accounting_System
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Vortex_Accounting_System
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
    }

    /**
     * Initialize
     */
    public function init() {
        $this->setup_accounting_tables();
    }

    /**
     * Setup accounting tables
     */
    private function setup_accounting_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_accounting';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            tola_amount decimal(18,8) NOT NULL,
            usd_amount decimal(10,2) NOT NULL,
            conversion_rate decimal(10,8) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            transaction_hash varchar(255) NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Record transaction
     *
     * @param int $user_id User ID
     * @param string $transaction_type Transaction type
     * @param float $tola_amount TOLA amount
     * @param float $usd_amount USD amount
     * @param float $conversion_rate Conversion rate
     * @return array
     */
    public function record_transaction($user_id, $transaction_type, $tola_amount, $usd_amount, $conversion_rate) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_accounting';

        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'transaction_type' => $transaction_type,
                'tola_amount' => $tola_amount,
                'usd_amount' => $usd_amount,
                'conversion_rate' => $conversion_rate,
                'status' => 'pending'
            ),
            array('%d', '%s', '%f', '%f', '%f', '%s')
        );

        if (!$result) {
            return array(
                'success' => false,
                'message' => 'Failed to record transaction'
            );
        }

        return array(
            'success' => true,
            'transaction_id' => $wpdb->insert_id,
            'message' => 'Transaction recorded successfully'
        );
    }

    /**
     * Get current TOLA to USD conversion rate
     *
     * @return float
     */
    public function get_conversion_rate() {
        // This would typically fetch from an external API
        // For now, return a fixed rate
        return 0.50; // 1 TOLA = $0.50 USD
    }

    /**
     * Get user transaction history
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_user_transactions($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_accounting';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
    }
} 