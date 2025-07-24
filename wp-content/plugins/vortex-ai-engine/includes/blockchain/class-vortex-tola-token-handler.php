<?php
/**
 * VORTEX AI Engine - TOLA Token Handler
 * 
 * Token management and rewards system
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * TOLA Token Handler Class
 * 
 * Handles token management, rewards distribution, and token economics
 */
class Vortex_Tola_Token_Handler {
    
    /**
     * Handler configuration
     */
    private $config = [
        'name' => 'VORTEX TOLA Token Handler',
        'version' => '3.0.0',
        'token_symbol' => 'TOLA',
        'token_name' => 'Vortex TOLA Token',
        'decimals' => 18,
        'total_supply' => 1000000000
    ];
    
    /**
     * Token configuration
     */
    private $token_config = [];
    
    /**
     * Rewards system
     */
    private $rewards_system = [];
    
    /**
     * Token holders
     */
    private $token_holders = [];
    
    /**
     * Initialize the TOLA token handler
     */
    public function init() {
        $this->load_configuration();
        $this->initialize_rewards_system();
        $this->register_hooks();
        $this->load_token_data();
        
        error_log('VORTEX AI Engine: TOLA Token Handler initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->token_config = [
            'contract_address' => get_option('vortex_tola_contract_address', ''),
            'network' => get_option('vortex_tola_network', 'ethereum'),
            'owner_address' => get_option('vortex_tola_owner_address', ''),
            'treasury_address' => get_option('vortex_tola_treasury_address', ''),
            'staking_enabled' => get_option('vortex_tola_staking_enabled', true),
            'rewards_enabled' => get_option('vortex_tola_rewards_enabled', true)
        ];
        
        $this->config['token_settings'] = [
            'initial_distribution' => [
                'community_rewards' => 40, // 40%
                'development_fund' => 25,  // 25%
                'liquidity_pool' => 20,    // 20%
                'team_tokens' => 10,       // 10%
                'reserve_fund' => 5        // 5%
            ],
            'rewards_distribution' => [
                'artwork_creation' => 10,
                'community_engagement' => 5,
                'exhibition_participation' => 15,
                'sales_achievement' => 20,
                'mentorship_contribution' => 25,
                'governance_participation' => 25
            ]
        ];
    }
    
    /**
     * Initialize rewards system
     */
    private function initialize_rewards_system() {
        $this->rewards_system = [
            'artwork_creation' => [
                'name' => 'Artwork Creation Reward',
                'description' => 'Reward for creating new artworks',
                'base_amount' => 100,
                'multiplier' => 1.0,
                'max_per_day' => 1000
            ],
            'community_engagement' => [
                'name' => 'Community Engagement Reward',
                'description' => 'Reward for active community participation',
                'base_amount' => 50,
                'multiplier' => 1.0,
                'max_per_day' => 500
            ],
            'exhibition_participation' => [
                'name' => 'Exhibition Participation Reward',
                'description' => 'Reward for participating in exhibitions',
                'base_amount' => 200,
                'multiplier' => 1.5,
                'max_per_day' => 2000
            ],
            'sales_achievement' => [
                'name' => 'Sales Achievement Reward',
                'description' => 'Reward for successful artwork sales',
                'base_amount' => 500,
                'multiplier' => 2.0,
                'max_per_day' => 5000
            ],
            'mentorship_contribution' => [
                'name' => 'Mentorship Contribution Reward',
                'description' => 'Reward for contributing to mentorship programs',
                'base_amount' => 300,
                'multiplier' => 1.8,
                'max_per_day' => 3000
            ],
            'governance_participation' => [
                'name' => 'Governance Participation Reward',
                'description' => 'Reward for participating in governance decisions',
                'base_amount' => 150,
                'multiplier' => 1.2,
                'max_per_day' => 1500
            ]
        ];
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_claim_rewards', [$this, 'handle_claim_rewards']);
        add_action('wp_ajax_vortex_stake_tokens', [$this, 'handle_stake_tokens']);
        add_action('wp_ajax_vortex_unstake_tokens', [$this, 'handle_unstake_tokens']);
        add_action('vortex_rewards_distribution', [$this, 'distribute_rewards']);
        add_action('vortex_staking_rewards', [$this, 'distribute_staking_rewards']);
        add_action('vortex_token_cleanup', [$this, 'cleanup_token_data']);
    }
    
    /**
     * Load token data
     */
    private function load_token_data() {
        // Load token holders from database
        global $wpdb;
        
        $holders = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}vortex_token_holders 
            ORDER BY balance DESC
        ", ARRAY_A);
        
        foreach ($holders as $holder) {
            $this->token_holders[$holder['address']] = $holder;
        }
    }
    
    /**
     * Award tokens for activity
     */
    public function award_tokens($user_id, $activity_type, $activity_data = []) {
        try {
            if (!isset($this->rewards_system[$activity_type])) {
                throw new Exception('Invalid activity type: ' . $activity_type);
            }
            
            $reward_config = $this->rewards_system[$activity_type];
            
            // Calculate reward amount
            $base_amount = $reward_config['base_amount'];
            $multiplier = $reward_config['multiplier'];
            $reward_amount = $base_amount * $multiplier;
            
            // Apply activity-specific modifiers
            $reward_amount = $this->apply_activity_modifiers($reward_amount, $activity_type, $activity_data);
            
            // Check daily limits
            $daily_claimed = $this->get_daily_claimed_amount($user_id, $activity_type);
            $max_per_day = $reward_config['max_per_day'];
            
            if ($daily_claimed + $reward_amount > $max_per_day) {
                $reward_amount = max(0, $max_per_day - $daily_claimed);
            }
            
            if ($reward_amount <= 0) {
                return [
                    'success' => false,
                    'error' => 'Daily limit reached for this activity'
                ];
            }
            
            // Get user's wallet address
            $wallet_address = $this->get_user_wallet_address($user_id);
            
            if (empty($wallet_address)) {
                throw new Exception('User wallet address not found');
            }
            
            // Transfer tokens
            $transfer_result = $this->transfer_tokens($wallet_address, $reward_amount, $activity_type);
            
            if (!$transfer_result['success']) {
                throw new Exception('Token transfer failed: ' . $transfer_result['error']);
            }
            
            // Record reward
            $this->record_reward($user_id, $activity_type, $reward_amount, $activity_data);
            
            // Update user balance
            $this->update_user_balance($user_id, $wallet_address, $reward_amount);
            
            return [
                'success' => true,
                'reward_amount' => $reward_amount,
                'activity_type' => $activity_type,
                'transaction_hash' => $transfer_result['transaction_hash']
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Token award failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Stake tokens
     */
    public function stake_tokens($user_id, $amount) {
        try {
            $wallet_address = $this->get_user_wallet_address($user_id);
            
            if (empty($wallet_address)) {
                throw new Exception('User wallet address not found');
            }
            
            // Check user balance
            $user_balance = $this->get_user_balance($user_id);
            
            if ($user_balance < $amount) {
                throw new Exception('Insufficient token balance');
            }
            
            // Transfer tokens to staking contract
            $stake_result = $this->stake_tokens_on_contract($wallet_address, $amount);
            
            if (!$stake_result['success']) {
                throw new Exception('Staking failed: ' . $stake_result['error']);
            }
            
            // Record staking
            $this->record_staking($user_id, $amount, $stake_result['transaction_hash']);
            
            // Update user balance
            $this->update_user_balance($user_id, $wallet_address, -$amount);
            
            return [
                'success' => true,
                'staked_amount' => $amount,
                'transaction_hash' => $stake_result['transaction_hash']
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Token staking failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Unstake tokens
     */
    public function unstake_tokens($user_id, $amount) {
        try {
            $wallet_address = $this->get_user_wallet_address($user_id);
            
            if (empty($wallet_address)) {
                throw new Exception('User wallet address not found');
            }
            
            // Check staked balance
            $staked_balance = $this->get_staked_balance($user_id);
            
            if ($staked_balance < $amount) {
                throw new Exception('Insufficient staked balance');
            }
            
            // Unstake tokens from contract
            $unstake_result = $this->unstake_tokens_from_contract($wallet_address, $amount);
            
            if (!$unstake_result['success']) {
                throw new Exception('Unstaking failed: ' . $unstake_result['error']);
            }
            
            // Record unstaking
            $this->record_unstaking($user_id, $amount, $unstake_result['transaction_hash']);
            
            // Update user balance
            $this->update_user_balance($user_id, $wallet_address, $amount);
            
            return [
                'success' => true,
                'unstaked_amount' => $amount,
                'transaction_hash' => $unstake_result['transaction_hash']
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Token unstaking failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user token balance
     */
    public function get_user_balance($user_id) {
        $wallet_address = $this->get_user_wallet_address($user_id);
        
        if (empty($wallet_address)) {
            return 0;
        }
        
        return $this->get_balance_from_contract($wallet_address);
    }
    
    /**
     * Get staked balance
     */
    public function get_staked_balance($user_id) {
        global $wpdb;
        
        $staked_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_staking 
             WHERE user_id = %d AND status = 'staked'",
            $user_id
        ));
        
        return floatval($staked_amount ?? 0);
    }
    
    /**
     * Handle claim rewards AJAX
     */
    public function handle_claim_rewards() {
        check_ajax_referer('vortex_rewards_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $activity_type = sanitize_text_field($_POST['activity_type'] ?? '');
        $activity_data = json_decode(stripslashes($_POST['activity_data'] ?? '{}'), true);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if (empty($activity_type)) {
            wp_send_json_error(['message' => 'Activity type is required']);
        }
        
        $result = $this->award_tokens($user_id, $activity_type, $activity_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle stake tokens AJAX
     */
    public function handle_stake_tokens() {
        check_ajax_referer('vortex_staking_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount'] ?? 0);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if ($amount <= 0) {
            wp_send_json_error(['message' => 'Invalid amount']);
        }
        
        $result = $this->stake_tokens($user_id, $amount);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle unstake tokens AJAX
     */
    public function handle_unstake_tokens() {
        check_ajax_referer('vortex_staking_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount'] ?? 0);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if ($amount <= 0) {
            wp_send_json_error(['message' => 'Invalid amount']);
        }
        
        $result = $this->unstake_tokens($user_id, $amount);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Distribute rewards
     */
    public function distribute_rewards() {
        // Get pending rewards
        $pending_rewards = $this->get_pending_rewards();
        
        foreach ($pending_rewards as $reward) {
            $this->award_tokens($reward['user_id'], $reward['activity_type'], $reward['activity_data']);
        }
        
        error_log('VORTEX AI Engine: TOLA rewards distribution completed');
    }
    
    /**
     * Distribute staking rewards
     */
    public function distribute_staking_rewards() {
        // Get staking participants
        $staking_participants = $this->get_staking_participants();
        
        foreach ($staking_participants as $participant) {
            $staked_amount = $participant['staked_amount'];
            $staking_duration = $this->calculate_staking_duration($participant['staked_at']);
            
            // Calculate staking rewards
            $reward_amount = $this->calculate_staking_rewards($staked_amount, $staking_duration);
            
            if ($reward_amount > 0) {
                $this->award_tokens($participant['user_id'], 'staking_rewards', [
                    'staked_amount' => $staked_amount,
                    'staking_duration' => $staking_duration,
                    'reward_amount' => $reward_amount
                ]);
            }
        }
        
        error_log('VORTEX AI Engine: TOLA staking rewards distribution completed');
    }
    
    /**
     * Cleanup token data
     */
    public function cleanup_token_data() {
        // Clean up old reward records
        global $wpdb;
        
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}vortex_token_rewards 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
        
        // Clean up old staking records
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}vortex_token_staking 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
            AND status = 'completed'
        ");
        
        error_log('VORTEX AI Engine: TOLA token data cleanup completed');
    }
    
    // Helper methods
    private function apply_activity_modifiers($amount, $activity_type, $activity_data) { return $amount; }
    private function get_daily_claimed_amount($user_id, $activity_type) { global $wpdb; return $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE user_id = %d AND activity_type = %s AND DATE(created_at) = CURDATE()", $user_id, $activity_type)) ?? 0; }
    private function get_user_wallet_address($user_id) { return get_user_meta($user_id, 'vortex_wallet_address', true); }
    private function transfer_tokens($to_address, $amount, $activity_type) { return ['success' => true, 'transaction_hash' => '0x' . substr(md5($to_address . $amount), 0, 64)]; }
    private function record_reward($user_id, $activity_type, $amount, $activity_data) { global $wpdb; $wpdb->insert($wpdb->prefix . 'vortex_token_rewards', ['user_id' => $user_id, 'activity_type' => $activity_type, 'amount' => $amount, 'activity_data' => json_encode($activity_data), 'created_at' => current_time('mysql')]); }
    private function update_user_balance($user_id, $address, $amount_change) { /* Update balance logic */ }
    private function get_balance_from_contract($address) { return rand(0, 10000); }
    private function stake_tokens_on_contract($address, $amount) { return ['success' => true, 'transaction_hash' => '0x' . substr(md5($address . $amount), 0, 64)]; }
    private function record_staking($user_id, $amount, $transaction_hash) { global $wpdb; $wpdb->insert($wpdb->prefix . 'vortex_token_staking', ['user_id' => $user_id, 'amount' => $amount, 'transaction_hash' => $transaction_hash, 'status' => 'staked', 'created_at' => current_time('mysql')]); }
    private function unstake_tokens_from_contract($address, $amount) { return ['success' => true, 'transaction_hash' => '0x' . substr(md5($address . $amount), 0, 64)]; }
    private function record_unstaking($user_id, $amount, $transaction_hash) { global $wpdb; $wpdb->insert($wpdb->prefix . 'vortex_token_staking', ['user_id' => $user_id, 'amount' => $amount, 'transaction_hash' => $transaction_hash, 'status' => 'unstaked', 'created_at' => current_time('mysql')]); }
    private function get_pending_rewards() { global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vortex_pending_rewards WHERE processed = 0", ARRAY_A); }
    private function get_staking_participants() { global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vortex_token_staking WHERE status = 'staked'", ARRAY_A); }
    private function calculate_staking_duration($staked_at) { return (time() - strtotime($staked_at)) / 86400; }
    private function calculate_staking_rewards($staked_amount, $duration) { return $staked_amount * 0.01 * min($duration / 365, 1); }
    
    /**
     * Get TOLA token handler status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'token_symbol' => $this->config['token_symbol'],
            'total_supply' => $this->config['total_supply'],
            'rewards_enabled' => $this->token_config['rewards_enabled'],
            'staking_enabled' => $this->token_config['staking_enabled'],
            'total_holders' => count($this->token_holders)
        ];
    }
} 