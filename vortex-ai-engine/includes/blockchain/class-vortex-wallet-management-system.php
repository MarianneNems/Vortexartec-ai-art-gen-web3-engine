<?php
/**
 * VORTEX AI Engine - Wallet Management System
 * 
 * Handles user wallet creation, token distribution, and balance tracking
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wallet Management System Class
 * 
 * Manages user wallets, token distribution, and balance tracking
 */
class Vortex_Wallet_Management_System {
    
    /**
     * System configuration
     */
    private $config = [
        'name' => 'VORTEX Wallet Management System',
        'version' => '3.0.0',
        'network' => 'solana',
        'token_contract' => '',
        'treasury_wallet' => ''
    ];
    
    /**
     * Initialize the wallet management system
     */
    public function init() {
        $this->load_configuration();
        $this->register_hooks();
        $this->create_tables();
        
        error_log('VORTEX AI Engine: Wallet Management System initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->config['token_contract'] = get_option('vortex_tola_contract_address', '');
        $this->config['treasury_wallet'] = get_option('vortex_treasury_wallet', '');
        $this->config['network'] = get_option('vortex_blockchain_network', 'solana');
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('user_register', [$this, 'create_user_wallet']);
        add_action('wp_ajax_vortex_connect_wallet', [$this, 'handle_connect_wallet']);
        add_action('wp_ajax_vortex_get_wallet_balance', [$this, 'handle_get_wallet_balance']);
        add_action('wp_ajax_vortex_transfer_tokens', [$this, 'handle_transfer_tokens']);
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // User wallets table
        $table_name = $wpdb->prefix . 'vortex_user_wallets';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(255) NOT NULL,
            wallet_type varchar(50) DEFAULT 'solana',
            balance decimal(20,8) DEFAULT 0.00000000,
            platform_credits decimal(20,8) DEFAULT 0.00000000,
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY wallet_address (wallet_address),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Token transactions table
        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_hash varchar(255) NOT NULL,
            from_address varchar(255),
            to_address varchar(255) NOT NULL,
            amount decimal(20,8) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_hash (transaction_hash),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY transaction_type (transaction_type),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create user wallet
     */
    public function create_user_wallet($user_id) {
        try {
            // Check if wallet already exists
            if ($this->get_user_wallet($user_id)) {
                return [
                    'success' => true,
                    'message' => 'Wallet already exists'
                ];
            }
            
            // Generate wallet address
            $wallet_address = $this->generate_wallet_address();
            
            // Create wallet record
            global $wpdb;
            $result = $wpdb->insert($wpdb->prefix . 'vortex_user_wallets', [
                'user_id' => $user_id,
                'wallet_address' => $wallet_address,
                'wallet_type' => $this->config['network'],
                'status' => 'active'
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create wallet record');
            }
            
            // Store wallet address in user meta
            update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
            
            error_log("VORTEX AI Engine: Created wallet for user {$user_id}: {$wallet_address}");
            
            return [
                'success' => true,
                'wallet_address' => $wallet_address
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Wallet creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user wallet
     */
    public function get_user_wallet($user_id) {
        global $wpdb;
        
        $wallet = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_wallets WHERE user_id = %d",
            $user_id
        ));
        
        return $wallet;
    }
    
    /**
     * Get user wallet balance
     */
    public function get_wallet_balance($user_id) {
        $wallet = $this->get_user_wallet($user_id);
        
        if (!$wallet) {
            return 0;
        }
        
        return floatval($wallet->balance);
    }
    
    /**
     * Get user platform credits
     */
    public function get_platform_credits($user_id) {
        $wallet = $this->get_user_wallet($user_id);
        
        if (!$wallet) {
            return 0;
        }
        
        return floatval($wallet->platform_credits);
    }
    
    /**
     * Distribute tokens
     */
    public function distribute_tokens($wallet_address, $amount, $transaction_type, $metadata = []) {
        try {
            // Validate wallet address
            if (!$this->is_valid_wallet_address($wallet_address)) {
                throw new Exception('Invalid wallet address');
            }
            
            // Generate transaction hash
            $transaction_hash = $this->generate_transaction_hash($wallet_address, $amount, $transaction_type);
            
            // Record transaction
            global $wpdb;
            $result = $wpdb->insert($wpdb->prefix . 'vortex_token_transactions', [
                'transaction_hash' => $transaction_hash,
                'from_address' => $this->config['treasury_wallet'],
                'to_address' => $wallet_address,
                'amount' => $amount,
                'transaction_type' => $transaction_type,
                'status' => 'completed',
                'metadata' => json_encode($metadata),
                'completed_at' => current_time('mysql')
            ]);
            
            if (!$result) {
                throw new Exception('Failed to record transaction');
            }
            
            // Update wallet balance
            $this->update_wallet_balance($wallet_address, $amount);
            
            // Update platform credits if needed
            if ($this->is_platform_credit_transaction($transaction_type)) {
                $this->update_platform_credits($wallet_address, $amount);
            }
            
            error_log("VORTEX AI Engine: Distributed {$amount} TOLA to {$wallet_address}");
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash,
                'amount' => $amount
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Token distribution failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Transfer tokens between wallets
     */
    public function transfer_tokens($from_address, $to_address, $amount, $transaction_type = 'transfer') {
        try {
            // Validate addresses
            if (!$this->is_valid_wallet_address($from_address) || !$this->is_valid_wallet_address($to_address)) {
                throw new Exception('Invalid wallet address');
            }
            
            // Check sender balance
            $sender_balance = $this->get_address_balance($from_address);
            if ($sender_balance < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            // Generate transaction hash
            $transaction_hash = $this->generate_transaction_hash($from_address, $amount, $transaction_type);
            
            // Record transaction
            global $wpdb;
            $result = $wpdb->insert($wpdb->prefix . 'vortex_token_transactions', [
                'transaction_hash' => $transaction_hash,
                'from_address' => $from_address,
                'to_address' => $to_address,
                'amount' => $amount,
                'transaction_type' => $transaction_type,
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ]);
            
            if (!$result) {
                throw new Exception('Failed to record transaction');
            }
            
            // Update balances
            $this->update_wallet_balance($from_address, -$amount);
            $this->update_wallet_balance($to_address, $amount);
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash,
                'amount' => $amount
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Token transfer failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle connect wallet AJAX
     */
    public function handle_connect_wallet() {
        check_ajax_referer('vortex_wallet_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $external_wallet = sanitize_text_field($_POST['wallet_address'] ?? '');
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if (empty($external_wallet)) {
            wp_send_json_error(['message' => 'Wallet address is required']);
        }
        
        // Validate external wallet address
        if (!$this->is_valid_wallet_address($external_wallet)) {
            wp_send_json_error(['message' => 'Invalid wallet address']);
        }
        
        // Update user's wallet address
        $result = $this->connect_external_wallet($user_id, $external_wallet);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle get wallet balance AJAX
     */
    public function handle_get_wallet_balance() {
        check_ajax_referer('vortex_wallet_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $wallet = $this->get_user_wallet($user_id);
        
        if (!$wallet) {
            wp_send_json_error(['message' => 'Wallet not found']);
        }
        
        $balance = [
            'wallet_address' => $wallet->wallet_address,
            'balance' => floatval($wallet->balance),
            'platform_credits' => floatval($wallet->platform_credits),
            'status' => $wallet->status
        ];
        
        wp_send_json_success($balance);
    }
    
    /**
     * Handle transfer tokens AJAX
     */
    public function handle_transfer_tokens() {
        check_ajax_referer('vortex_wallet_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $to_address = sanitize_text_field($_POST['to_address'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if (empty($to_address) || $amount <= 0) {
            wp_send_json_error(['message' => 'Invalid transfer parameters']);
        }
        
        $wallet = $this->get_user_wallet($user_id);
        
        if (!$wallet) {
            wp_send_json_error(['message' => 'Wallet not found']);
        }
        
        $result = $this->transfer_tokens($wallet->wallet_address, $to_address, $amount);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    // Helper methods
    private function generate_wallet_address() {
        return 'vortex_' . substr(md5(uniqid() . time()), 0, 32);
    }
    
    private function generate_transaction_hash($address, $amount, $type) {
        return '0x' . substr(md5($address . $amount . $type . time()), 0, 64);
    }
    
    private function is_valid_wallet_address($address) {
        return !empty($address) && (strpos($address, 'vortex_') === 0 || strlen($address) >= 32);
    }
    
    private function update_wallet_balance($wallet_address, $amount_change) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}vortex_user_wallets 
             SET balance = balance + %f, updated_at = NOW() 
             WHERE wallet_address = %s",
            $amount_change,
            $wallet_address
        ));
    }
    
    private function update_platform_credits($wallet_address, $amount_change) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}vortex_user_wallets 
             SET platform_credits = platform_credits + %f, updated_at = NOW() 
             WHERE wallet_address = %s",
            $amount_change,
            $wallet_address
        ));
    }
    
    private function get_address_balance($wallet_address) {
        global $wpdb;
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM {$wpdb->prefix}vortex_user_wallets WHERE wallet_address = %s",
            $wallet_address
        ));
        return floatval($balance ?? 0);
    }
    
    private function is_platform_credit_transaction($transaction_type) {
        $platform_credit_types = ['incentive', 'reward', 'bonus'];
        return in_array($transaction_type, $platform_credit_types);
    }
    
    private function connect_external_wallet($user_id, $external_wallet) {
        try {
            // Update existing wallet or create new one
            global $wpdb;
            
            $existing_wallet = $this->get_user_wallet($user_id);
            
            if ($existing_wallet) {
                $wpdb->update($wpdb->prefix . 'vortex_user_wallets', [
                    'wallet_address' => $external_wallet,
                    'updated_at' => current_time('mysql')
                ], ['user_id' => $user_id]);
            } else {
                $wpdb->insert($wpdb->prefix . 'vortex_user_wallets', [
                    'user_id' => $user_id,
                    'wallet_address' => $external_wallet,
                    'wallet_type' => $this->config['network'],
                    'status' => 'active'
                ]);
            }
            
            update_user_meta($user_id, 'vortex_wallet_address', $external_wallet);
            
            return [
                'success' => true,
                'wallet_address' => $external_wallet
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get wallet management system status
     */
    public function get_status() {
        global $wpdb;
        
        $total_wallets = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_user_wallets");
        $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_token_transactions");
        $total_balance = $wpdb->get_var("SELECT SUM(balance) FROM {$wpdb->prefix}vortex_user_wallets");
        
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'network' => $this->config['network'],
            'total_wallets' => intval($total_wallets),
            'total_transactions' => intval($total_transactions),
            'total_balance' => floatval($total_balance)
        ];
    }
} 