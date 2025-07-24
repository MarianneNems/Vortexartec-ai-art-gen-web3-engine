<?php
/**
 * Vortex AI Engine - Wallet Manager Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Wallet Manager Class
 *
 * Handles TOLA token wallet operations and balance management.
 */
class Vortex_Wallet_Manager {

    /**
     * Instance
     *
     * @var Vortex_Wallet_Manager
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Vortex_Wallet_Manager
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
        $this->setup_wallet_tables();
    }

    /**
     * Setup wallet tables
     */
    private function setup_wallet_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_wallets';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tola_balance decimal(18,8) DEFAULT 0.00000000,
            usd_balance decimal(10,2) DEFAULT 0.00,
            wallet_address varchar(255) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY wallet_address (wallet_address)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get TOLA balance
     *
     * @param int $user_id User ID
     * @return float
     */
    public function get_tola_balance($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_wallets';

        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT tola_balance FROM $table_name WHERE user_id = %d",
            $user_id
        ));

        return $balance ? floatval($balance) : 0.0;
    }

    /**
     * Add TOLA balance
     *
     * @param int $user_id User ID
     * @param float $amount Amount to add
     * @return array
     */
    public function add_tola_balance($user_id, $amount) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_wallets';

        // Ensure wallet exists
        $this->ensure_wallet_exists($user_id);

        // Update balance
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET tola_balance = tola_balance + %f WHERE user_id = %d",
            $amount,
            $user_id
        ));

        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Failed to update wallet balance'
            );
        }

        $new_balance = $this->get_tola_balance($user_id);
        $transaction_hash = $this->generate_transaction_hash($user_id, $amount, 'add');

        return array(
            'success' => true,
            'message' => 'Balance updated successfully',
            'new_balance' => $new_balance,
            'transaction_hash' => $transaction_hash
        );
    }

    /**
     * Ensure wallet exists
     *
     * @param int $user_id User ID
     */
    private function ensure_wallet_exists($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_wallets';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d",
            $user_id
        ));

        if (!$exists) {
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'tola_balance' => 0,
                    'usd_balance' => 0,
                    'wallet_address' => $this->generate_wallet_address($user_id)
                ),
                array('%d', '%f', '%f', '%s')
            );
        }
    }

    /**
     * Generate wallet address
     *
     * @param int $user_id User ID
     * @return string
     */
    private function generate_wallet_address($user_id) {
        return 'VORTEX_' . $user_id . '_' . substr(md5($user_id . time()), 0, 8);
    }

    /**
     * Generate transaction hash
     *
     * @param int $user_id User ID
     * @param float $amount Amount
     * @param string $type Transaction type
     * @return string
     */
    private function generate_transaction_hash($user_id, $amount, $type) {
        return 'TXN_' . $type . '_' . $user_id . '_' . time() . '_' . substr(md5($amount . $user_id), 0, 8);
    }
} 