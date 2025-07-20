<?php
/**
 * Core functionality: DB tables, cron scheduling, exchange rates,
 * daily masterpiece, quiz gating & memory sync.
 *
 * @package VortexAIEngine
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_Core {
    /** Singleton instance */
    private static $instance = null;

    /** Get/create instance and hook WP events */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /** Attach hooks */
    private function hooks() {
        // Activation: create tables
        register_activation_hook( VORTEX_AI_ENGINE_PLUGIN_FILE, [ $this, 'create_tables' ] );

        // Schedule crons at init
        add_action( 'init', [ $this, 'maybe_schedule_crons' ] );

        // Cron callbacks
        add_action( 'vortex_swap_update_rates',     [ $this, 'update_exchange_rates' ] );
        add_action( 'vortex_daily_masterpiece',     [ $this, 'run_daily_masterpiece' ] );

        // AJAX for rate fetch
        add_action( 'wp_ajax_vortex_get_exchange_rates',        [ $this, 'ajax_get_exchange_rates' ] );
        add_action( 'wp_ajax_nopriv_vortex_get_exchange_rates', [ $this, 'ajax_get_exchange_rates' ] );

        // Continuous memory sync
        add_action( 'vortex_agents_memory_sync', [ $this, 'sync_agent_memory' ] );
    }

    /**
     * Create all necessary tables: swap_rates, swap_wallets,
     * swap_transactions, user + product agreements.
     */
    public function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        // 1) Swap rates
        $table = $wpdb->prefix . 'vortex_swap_rates';
        $sql   = "CREATE TABLE {$table} (
            id         BIGINT NOT NULL AUTO_INCREMENT,
            from_token VARCHAR(20) NOT NULL,
            to_token   VARCHAR(20) NOT NULL,
            network    VARCHAR(20) NOT NULL,
            rate       FLOAT       NOT NULL,
            timestamp  DATETIME    DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pair (from_token,to_token,network)
        ) {$charset};";
        dbDelta( $sql );

        // 2) Wallets
        $table = $wpdb->prefix . 'vortex_swap_wallets';
        $sql   = "CREATE TABLE {$table} (
            id             BIGINT NOT NULL AUTO_INCREMENT,
            user_id        BIGINT NOT NULL,
            wallet_address VARCHAR(42) NOT NULL,
            network        VARCHAR(20) NOT NULL DEFAULT 'ethereum',
            is_primary     TINYINT(1) NOT NULL DEFAULT 0,
            created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY user_wallet (user_id,wallet_address,network)
        ) {$charset};";
        dbDelta( $sql );

        // 3) Transactions
        $table = $wpdb->prefix . 'vortex_swap_transactions';
        $sql   = "CREATE TABLE {$table} (
            id               BIGINT NOT NULL AUTO_INCREMENT,
            user_id          BIGINT NOT NULL,
            transaction_hash VARCHAR(66) NOT NULL,
            action           VARCHAR(50) NOT NULL,
            shortcode        VARCHAR(100) NOT NULL,
            data             LONGTEXT,
            ip_address       VARCHAR(45),
            user_agent       VARCHAR(255),
            created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY user_id (user_id)
        ) {$charset};";
        dbDelta( $sql );

        // 4) User agreements
        $table = $wpdb->prefix . 'vortex_user_agreements';
        $sql   = "CREATE TABLE {$table} (
            id             BIGINT NOT NULL AUTO_INCREMENT,
            user_id        BIGINT NOT NULL,
            agreement_type VARCHAR(100) NOT NULL,
            agreed         TINYINT(1) NOT NULL DEFAULT 0,
            created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY user_id (user_id),
            KEY type (agreement_type)
        ) {$charset};";
        dbDelta( $sql );

        // 5) Product (TOLA) agreements
        $table = $wpdb->prefix . 'vortex_product_agreements';
        $sql   = "CREATE TABLE {$table} (
            id             BIGINT NOT NULL AUTO_INCREMENT,
            post_id        BIGINT NOT NULL,
            agreement_type VARCHAR(100) NOT NULL,
            agreed         TINYINT(1) NOT NULL DEFAULT 0,
            created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY post_id (post_id),
            KEY type (agreement_type)
        ) {$charset};";
        dbDelta( $sql );

        // 6) AI Usage tracking
        $table = $wpdb->prefix . 'vortex_ai_usage';
        $sql   = "CREATE TABLE {$table} (
            id         BIGINT NOT NULL AUTO_INCREMENT,
            agent_id   VARCHAR(50) NOT NULL,
            cost       DECIMAL(10,4) NOT NULL,
            user_id    BIGINT NOT NULL,
            session_id VARCHAR(100) DEFAULT NULL,
            query_hash VARCHAR(64) DEFAULT NULL,
            timestamp  DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY agent_id (agent_id),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) {$charset};";
        dbDelta( $sql );
    }

    /** Schedule crons if they're not already */
    public function maybe_schedule_crons() {
        if ( ! wp_next_scheduled( 'vortex_swap_update_rates' ) ) {
            wp_schedule_event( time(), 'hourly', 'vortex_swap_update_rates' );
        }
        if ( ! wp_next_scheduled( 'vortex_daily_masterpiece' ) ) {
            wp_schedule_event( time(), 'daily', 'vortex_daily_masterpiece' );
        }
    }

    /** Cron: update exchange rates (implement your API call here) */
    public function update_exchange_rates() {
        error_log( '[VortexAIEngine] update_exchange_rates fired' );
        // TODO: fetch & store rates
    }

    /** Cron: generate and mint daily TOLA masterpiece */
    public function run_daily_masterpiece() {
        if ( class_exists( 'VortexAIEngine_MasterpieceGenerator' ) ) {
            VortexAIEngine_MasterpieceGenerator::getInstance()->generate_and_mint();
        }
    }

    /**
     * AJAX: get the latest exchange rate for a token pair
     */
    public function ajax_get_exchange_rates() {
        check_ajax_referer( 'vortex_swap_nonce', 'nonce' );

        $from    = sanitize_text_field( $_REQUEST['from'] );
        $to      = sanitize_text_field( $_REQUEST['to'] );
        $network = sanitize_text_field( $_REQUEST['network'] ?? 'ethereum' );

        $rate = $this->get_exchange_rate( $from, $to, $network );
        if ( false !== $rate ) {
            wp_send_json_success( [
                'rate'      => $rate,
                'timestamp' => current_time( 'timestamp' ),
            ] );
        }

        wp_send_json_error( 'Rate not found' );
    }

    /** Internal: fetch latest rate from DB */
    private function get_exchange_rate( $from, $to, $network ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_swap_rates';
        $row   = $wpdb->get_row( $wpdb->prepare(
            "SELECT rate 
               FROM {$table} 
              WHERE from_token = %s 
                AND to_token   = %s 
                AND network    = %s 
           ORDER BY timestamp DESC 
              LIMIT 1",
            $from, $to, $network
        ) );
        return $row ? floatval( $row->rate ) : false;
    }

    /**
     * Continuous memory sync for all agents after quiz
     */
    public function sync_agent_memory( $user_id ) {
        $vault = VortexAIEngine_Vault::getInstance();
        $answers = get_user_meta( $user_id, 'vortex_quiz_answers', true ) ?: [];

        foreach ( [ 'huraii', 'cloe', 'horace' ] as $agent ) {
            $memory = $vault->getAlgorithm( "memory_{$agent}_{$user_id}" ) ?: [];
            $memory['quiz'][] = $answers;
            $vault->write( "secret/data/ai-agents/memory/{$agent}/{$user_id}", $memory );
        }
    }
}

// Bootstrap
VortexAIEngine_Core::getInstance(); 