<?php
/**
 * VORTEX AI Engine - Conversion System
 * 
 * Handles USDC conversion with 1000 artist milestone requirement and platform credit restrictions
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conversion System Class
 * 
 * Manages USDC conversion with milestone requirements and platform credit restrictions
 */
class Vortex_Conversion_System {
    
    /**
     * System configuration
     */
    private $config = [
        'name' => 'VORTEX Conversion System',
        'version' => '3.0.0',
        'milestone_artist_count' => 1000,
        'conversion_enabled' => false,
        'platform_credit_restriction' => true,
        'min_conversion_amount' => 10, // Minimum 10 TOLA for conversion
        'max_conversion_amount' => 10000, // Maximum 10,000 TOLA per day
        'conversion_fee' => 0.01 // 1% conversion fee
    ];
    
    /**
     * Initialize the conversion system
     */
    public function init() {
        $this->load_configuration();
        $this->register_hooks();
        $this->create_tables();
        $this->check_milestone_status();
        
        error_log('VORTEX AI Engine: Conversion System initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->config['conversion_enabled'] = get_option('vortex_conversion_enabled', false);
        $this->config['platform_credit_restriction'] = get_option('vortex_platform_credit_restriction', true);
        $this->config['milestone_artist_count'] = get_option('vortex_milestone_artist_count', 1000);
        $this->config['min_conversion_amount'] = get_option('vortex_min_conversion_amount', 10);
        $this->config['max_conversion_amount'] = get_option('vortex_max_conversion_amount', 10000);
        $this->config['conversion_fee'] = get_option('vortex_conversion_fee', 0.01);
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_request_conversion', [$this, 'handle_conversion_request']);
        add_action('wp_ajax_vortex_get_conversion_status', [$this, 'handle_get_conversion_status']);
        add_action('wp_ajax_vortex_get_conversion_history', [$this, 'handle_get_conversion_history']);
        add_action('vortex_milestone_reached', [$this, 'enable_conversion']);
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Conversion requests table
        $table_name = $wpdb->prefix . 'vortex_conversion_requests';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            request_id varchar(255) NOT NULL,
            tola_amount decimal(20,8) NOT NULL,
            usdc_amount decimal(20,8) NOT NULL,
            conversion_fee decimal(20,8) DEFAULT 0.00000000,
            wallet_address varchar(255) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            transaction_hash varchar(255),
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY request_id (request_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Conversion limits table
        $table_name = $wpdb->prefix . 'vortex_conversion_limits';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            limit_type varchar(50) NOT NULL,
            amount_used decimal(20,8) DEFAULT 0.00000000,
            limit_amount decimal(20,8) NOT NULL,
            reset_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_limit (user_id, limit_type, reset_date),
            KEY user_id (user_id),
            KEY reset_date (reset_date)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Check milestone status
     */
    private function check_milestone_status() {
        $artist_count = $this->get_artist_count();
        
        if ($artist_count >= $this->config['milestone_artist_count'] && !$this->config['conversion_enabled']) {
            $this->enable_conversion();
        }
        
        error_log("VORTEX AI Engine: Conversion milestone check - Artists: {$artist_count}/{$this->config['milestone_artist_count']}");
    }
    
    /**
     * Enable conversion
     */
    public function enable_conversion() {
        $this->config['conversion_enabled'] = true;
        update_option('vortex_conversion_enabled', true);
        
        // Notify all users
        $this->notify_conversion_enabled();
        
        error_log('VORTEX AI Engine: Conversion enabled - 1000 artists milestone reached');
    }
    
    /**
     * Process conversion request
     */
    public function process_conversion_request($user_id) {
        try {
            // Check if conversion is enabled
            if (!$this->config['conversion_enabled']) {
                return [
                    'success' => false,
                    'error' => 'Conversion not yet enabled. Need 1000 artists milestone.',
                    'artist_count' => $this->get_artist_count(),
                    'milestone_required' => $this->config['milestone_artist_count']
                ];
            }
            
            // Get request parameters
            $tola_amount = floatval($_POST['tola_amount'] ?? 0);
            $wallet_address = sanitize_text_field($_POST['wallet_address'] ?? '');
            
            // Validate request
            $validation = $this->validate_conversion_request($user_id, $tola_amount, $wallet_address);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }
            
            // Calculate conversion amounts
            $conversion_fee = $tola_amount * $this->config['conversion_fee'];
            $net_tola_amount = $tola_amount - $conversion_fee;
            $usdc_amount = $this->calculate_usdc_amount($net_tola_amount);
            
            // Generate request ID
            $request_id = $this->generate_request_id($user_id, $tola_amount);
            
            // Record conversion request
            $request_result = $this->record_conversion_request(
                $user_id,
                $request_id,
                $tola_amount,
                $usdc_amount,
                $conversion_fee,
                $wallet_address
            );
            
            if (!$request_result['success']) {
                throw new Exception('Failed to record conversion request');
            }
            
            // Process the conversion
            $conversion_result = $this->execute_conversion(
                $user_id,
                $request_id,
                $tola_amount,
                $usdc_amount,
                $wallet_address
            );
            
            if (!$conversion_result['success']) {
                throw new Exception('Conversion execution failed: ' . $conversion_result['error']);
            }
            
            // Update conversion limits
            $this->update_conversion_limits($user_id, $tola_amount);
            
            // Record in accounting system
            $this->record_conversion_in_accounting($user_id, $tola_amount, $usdc_amount, $conversion_result['transaction_hash']);
            
            return [
                'success' => true,
                'request_id' => $request_id,
                'tola_amount' => $tola_amount,
                'usdc_amount' => $usdc_amount,
                'conversion_fee' => $conversion_fee,
                'transaction_hash' => $conversion_result['transaction_hash'],
                'wallet_address' => $wallet_address
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Conversion request failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate conversion request
     */
    private function validate_conversion_request($user_id, $tola_amount, $wallet_address) {
        // Check minimum amount
        if ($tola_amount < $this->config['min_conversion_amount']) {
            return [
                'valid' => false,
                'error' => "Minimum conversion amount is {$this->config['min_conversion_amount']} TOLA"
            ];
        }
        
        // Check maximum daily limit
        $daily_limit = $this->get_user_daily_conversion_limit($user_id);
        if ($tola_amount > $daily_limit) {
            return [
                'valid' => false,
                'error' => "Daily conversion limit exceeded. Available: {$daily_limit} TOLA"
            ];
        }
        
        // Check user platform credits
        $platform_credits = $this->get_user_platform_credits($user_id);
        if ($platform_credits < $tola_amount) {
            return [
                'valid' => false,
                'error' => "Insufficient platform credits. Available: {$platform_credits} TOLA"
            ];
        }
        
        // Validate wallet address
        if (empty($wallet_address) || !$this->is_valid_wallet_address($wallet_address)) {
            return [
                'valid' => false,
                'error' => 'Invalid wallet address'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Execute conversion
     */
    private function execute_conversion($user_id, $request_id, $tola_amount, $usdc_amount, $wallet_address) {
        try {
            // Deduct platform credits
            $this->deduct_platform_credits($user_id, $tola_amount);
            
            // Transfer USDC to user wallet
            $transaction_hash = $this->transfer_usdc_to_wallet($wallet_address, $usdc_amount);
            
            // Update conversion request status
            $this->update_conversion_request_status($request_id, 'completed', $transaction_hash);
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash
            ];
            
        } catch (Exception $e) {
            // Revert platform credits on failure
            $this->add_platform_credits($user_id, $tola_amount);
            
            $this->update_conversion_request_status($request_id, 'failed', null, $e->getMessage());
            
            throw $e;
        }
    }
    
    /**
     * Restrict to platform credit
     */
    public function restrict_to_platform_credit($user_id, $tola_amount) {
        try {
            // Add to platform credits only
            $this->add_platform_credits($user_id, $tola_amount);
            
            error_log("VORTEX AI Engine: Restricted {$tola_amount} TOLA to platform credits for user {$user_id}");
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Platform credit restriction failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if user is conversion eligible
     */
    public function is_user_conversion_eligible($user_id) {
        return $this->config['conversion_enabled'] && $this->get_user_platform_credits($user_id) > 0;
    }
    
    /**
     * Get user platform credits
     */
    public function get_user_platform_credits($user_id) {
        global $wpdb;
        
        $credits = $wpdb->get_var($wpdb->prepare(
            "SELECT platform_credits FROM {$wpdb->prefix}vortex_user_wallets WHERE user_id = %d",
            $user_id
        ));
        
        return floatval($credits ?? 0);
    }
    
    /**
     * Handle conversion request AJAX
     */
    public function handle_conversion_request() {
        check_ajax_referer('vortex_conversion_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $result = $this->process_conversion_request($user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle get conversion status AJAX
     */
    public function handle_get_conversion_status() {
        check_ajax_referer('vortex_conversion_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $status = [
            'conversion_enabled' => $this->config['conversion_enabled'],
            'artist_count' => $this->get_artist_count(),
            'milestone_required' => $this->config['milestone_artist_count'],
            'platform_credits' => $this->get_user_platform_credits($user_id),
            'daily_limit' => $this->get_user_daily_conversion_limit($user_id),
            'min_amount' => $this->config['min_conversion_amount'],
            'max_amount' => $this->config['max_conversion_amount'],
            'conversion_fee' => $this->config['conversion_fee']
        ];
        
        wp_send_json_success($status);
    }
    
    /**
     * Handle get conversion history AJAX
     */
    public function handle_get_conversion_history() {
        check_ajax_referer('vortex_conversion_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $history = $this->get_user_conversion_history($user_id, $page, $per_page);
        
        wp_send_json_success($history);
    }
    
    // Helper methods
    private function get_artist_count() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->users} u 
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
            AND um.meta_value LIKE '%artist%'
        ");
    }
    
    private function calculate_usdc_amount($tola_amount) {
        // 1 TOLA = 1 USDC (as per business plan)
        return $tola_amount;
    }
    
    private function generate_request_id($user_id, $tola_amount) {
        return 'conv_' . $user_id . '_' . time() . '_' . substr(md5($tola_amount), 0, 8);
    }
    
    private function record_conversion_request($user_id, $request_id, $tola_amount, $usdc_amount, $conversion_fee, $wallet_address) {
        global $wpdb;
        
        $result = $wpdb->insert($wpdb->prefix . 'vortex_conversion_requests', [
            'user_id' => $user_id,
            'request_id' => $request_id,
            'tola_amount' => $tola_amount,
            'usdc_amount' => $usdc_amount,
            'conversion_fee' => $conversion_fee,
            'wallet_address' => $wallet_address,
            'status' => 'pending'
        ]);
        
        return ['success' => $result !== false];
    }
    
    private function update_conversion_request_status($request_id, $status, $transaction_hash = null, $error_message = null) {
        global $wpdb;
        
        $data = [
            'status' => $status,
            'processed_at' => current_time('mysql')
        ];
        
        if ($transaction_hash) {
            $data['transaction_hash'] = $transaction_hash;
        }
        
        if ($error_message) {
            $data['metadata'] = json_encode(['error' => $error_message]);
        }
        
        $wpdb->update($wpdb->prefix . 'vortex_conversion_requests', $data, ['request_id' => $request_id]);
    }
    
    private function get_user_daily_conversion_limit($user_id) {
        global $wpdb;
        
        $today = date('Y-m-d');
        $used_amount = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(tola_amount) FROM {$wpdb->prefix}vortex_conversion_requests 
            WHERE user_id = %d AND DATE(created_at) = %s AND status = 'completed'
        ", $user_id, $today));
        
        $used_amount = floatval($used_amount ?? 0);
        return max(0, $this->config['max_conversion_amount'] - $used_amount);
    }
    
    private function update_conversion_limits($user_id, $tola_amount) {
        global $wpdb;
        
        $today = date('Y-m-d');
        
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vortex_conversion_limits 
            WHERE user_id = %d AND limit_type = 'daily' AND reset_date = %s
        ", $user_id, $today));
        
        if ($existing) {
            $wpdb->update($wpdb->prefix . 'vortex_conversion_limits', [
                'amount_used' => $existing->amount_used + $tola_amount,
                'updated_at' => current_time('mysql')
            ], ['id' => $existing->id]);
        } else {
            $wpdb->insert($wpdb->prefix . 'vortex_conversion_limits', [
                'user_id' => $user_id,
                'limit_type' => 'daily',
                'amount_used' => $tola_amount,
                'limit_amount' => $this->config['max_conversion_amount'],
                'reset_date' => $today
            ]);
        }
    }
    
    private function deduct_platform_credits($user_id, $amount) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}vortex_user_wallets 
            SET platform_credits = platform_credits - %f, updated_at = NOW() 
            WHERE user_id = %d
        ", $amount, $user_id));
    }
    
    private function add_platform_credits($user_id, $amount) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}vortex_user_wallets 
            SET platform_credits = platform_credits + %f, updated_at = NOW() 
            WHERE user_id = %d
        ", $amount, $user_id));
    }
    
    private function transfer_usdc_to_wallet($wallet_address, $usdc_amount) {
        // Simulate USDC transfer - in production, this would integrate with actual blockchain
        return '0x' . substr(md5($wallet_address . $usdc_amount . time()), 0, 64);
    }
    
    private function record_conversion_in_accounting($user_id, $tola_amount, $usdc_amount, $transaction_hash) {
        // This would integrate with the accounting system
        do_action('vortex_record_conversion_transaction', $user_id, $tola_amount, $usdc_amount, $transaction_hash);
    }
    
    private function is_valid_wallet_address($address) {
        return !empty($address) && strlen($address) >= 32;
    }
    
    private function get_user_conversion_history($user_id, $page, $per_page) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $history = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vortex_conversion_requests 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d
        ", $user_id, $per_page, $offset), ARRAY_A);
        
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vortex_conversion_requests 
            WHERE user_id = %d
        ", $user_id));
        
        return [
            'history' => $history,
            'total' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    private function notify_conversion_enabled() {
        // Send notifications to all users
        do_action('vortex_conversion_enabled_notification');
    }
    
    /**
     * Get conversion system status
     */
    public function get_status() {
        global $wpdb;
        
        $total_conversions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_conversion_requests WHERE status = 'completed'");
        $total_converted_tola = $wpdb->get_var("SELECT SUM(tola_amount) FROM {$wpdb->prefix}vortex_conversion_requests WHERE status = 'completed'");
        $total_converted_usdc = $wpdb->get_var("SELECT SUM(usdc_amount) FROM {$wpdb->prefix}vortex_conversion_requests WHERE status = 'completed'");
        
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'conversion_enabled' => $this->config['conversion_enabled'],
            'artist_count' => $this->get_artist_count(),
            'milestone_required' => $this->config['milestone_artist_count'],
            'total_conversions' => intval($total_conversions),
            'total_converted_tola' => floatval($total_converted_tola),
            'total_converted_usdc' => floatval($total_converted_usdc)
        ];
    }
} 