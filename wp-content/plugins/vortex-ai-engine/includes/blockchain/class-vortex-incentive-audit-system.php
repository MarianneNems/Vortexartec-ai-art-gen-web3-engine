<?php
/**
 * VORTEX AI Engine - Automated Incentive Audit System
 * 
 * Handles automated TOLA token distribution, wallet management, accounting,
 * and conversion enforcement with 1000 artist milestone requirement
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automated Incentive Audit System Class
 * 
 * Manages real-time TOLA token distribution, wallet integration,
 * accounting, and conversion rules enforcement
 */
class Vortex_Incentive_Audit_System {
    
    /**
     * System configuration
     */
    private $config = [
        'name' => 'VORTEX Automated Incentive Audit System',
        'version' => '3.0.0',
        'milestone_artist_count' => 1000,
        'conversion_enabled' => false,
        'platform_credit_restriction' => true
    ];
    
    /**
     * Incentive rules
     */
    private $incentive_rules = [
        'subscription_signup' => [
            'tola_amount' => 500,
            'description' => 'Artist subscription signup bonus',
            'immediate_distribution' => true,
            'platform_credit_only' => true
        ],
        'artwork_upload' => [
            'tola_amount' => 100,
            'description' => 'Artwork upload reward',
            'immediate_distribution' => true,
            'platform_credit_only' => true
        ],
        'first_sale' => [
            'tola_amount' => 1000,
            'description' => 'First artwork sale milestone',
            'immediate_distribution' => true,
            'platform_credit_only' => true
        ],
        'community_engagement' => [
            'tola_amount' => 50,
            'description' => 'Community engagement reward',
            'immediate_distribution' => true,
            'platform_credit_only' => true
        ],
        'exhibition_participation' => [
            'tola_amount' => 200,
            'description' => 'Exhibition participation reward',
            'immediate_distribution' => true,
            'platform_credit_only' => true
        ]
    ];
    
    /**
     * Wallet management
     */
    private $wallet_system = null;
    
    /**
     * Accounting system
     */
    private $accounting_system = null;
    
    /**
     * Conversion system
     */
    private $conversion_system = null;
    
    /**
     * Initialize the incentive audit system
     */
    public function init() {
        $this->load_configuration();
        $this->initialize_subsystems();
        $this->register_hooks();
        $this->check_milestone_status();
        
        error_log('VORTEX AI Engine: Automated Incentive Audit System initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->config['conversion_enabled'] = get_option('vortex_conversion_enabled', false);
        $this->config['platform_credit_restriction'] = get_option('vortex_platform_credit_restriction', true);
        $this->config['milestone_artist_count'] = get_option('vortex_milestone_artist_count', 1000);
    }
    
    /**
     * Initialize subsystems
     */
    private function initialize_subsystems() {
        $this->wallet_system = new Vortex_Wallet_Management_System();
        $this->accounting_system = new Vortex_Accounting_System();
        $this->conversion_system = new Vortex_Conversion_System();
        
        $this->wallet_system->init();
        $this->accounting_system->init();
        $this->conversion_system->init();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Artist registration and subscription hooks
        add_action('user_register', [$this, 'handle_artist_registration']);
        add_action('vortex_subscription_activated', [$this, 'handle_subscription_activation']);
        
        // Artwork upload hooks
        add_action('vortex_artwork_uploaded', [$this, 'handle_artwork_upload']);
        add_action('vortex_artwork_sold', [$this, 'handle_artwork_sale']);
        
        // Community engagement hooks
        add_action('vortex_community_engagement', [$this, 'handle_community_engagement']);
        add_action('vortex_exhibition_participation', [$this, 'handle_exhibition_participation']);
        
        // Conversion hooks
        add_action('vortex_request_conversion', [$this, 'handle_conversion_request']);
        add_action('vortex_milestone_reached', [$this, 'handle_milestone_reached']);
        
        // Audit and monitoring hooks
        add_action('vortex_daily_audit', [$this, 'run_daily_audit']);
        add_action('vortex_fraud_detection', [$this, 'run_fraud_detection']);
        
        // AJAX handlers
        add_action('wp_ajax_vortex_claim_incentive', [$this, 'handle_claim_incentive']);
        add_action('wp_ajax_vortex_check_conversion_status', [$this, 'handle_check_conversion_status']);
    }
    
    /**
     * Check milestone status
     */
    private function check_milestone_status() {
        $artist_count = $this->get_artist_count();
        
        if ($artist_count >= $this->config['milestone_artist_count'] && !$this->config['conversion_enabled']) {
            $this->enable_conversion();
        }
        
        error_log("VORTEX AI Engine: Artist count: {$artist_count}/{$this->config['milestone_artist_count']}");
    }
    
    /**
     * Handle artist registration
     */
    public function handle_artist_registration($user_id) {
        try {
            // Verify user has artist role
            $user = get_userdata($user_id);
            if (!$user || !in_array('artist', $user->roles)) {
                return;
            }
            
            // Check if this is a new artist registration
            if ($this->is_new_artist_registration($user_id)) {
                $this->distribute_incentive($user_id, 'subscription_signup');
                
                // Check milestone after registration
                $this->check_milestone_status();
            }
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Artist registration incentive failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle subscription activation
     */
    public function handle_subscription_activation($user_id) {
        try {
            $this->distribute_incentive($user_id, 'subscription_signup');
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Subscription activation incentive failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle artwork upload
     */
    public function handle_artwork_upload($artwork_data) {
        try {
            $user_id = $artwork_data['artist_id'];
            $this->distribute_incentive($user_id, 'artwork_upload', $artwork_data);
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Artwork upload incentive failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle artwork sale
     */
    public function handle_artwork_sale($sale_data) {
        try {
            $user_id = $sale_data['artist_id'];
            
            // Check if this is the artist's first sale
            if ($this->is_first_sale($user_id)) {
                $this->distribute_incentive($user_id, 'first_sale', $sale_data);
            }
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Artwork sale incentive failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle community engagement
     */
    public function handle_community_engagement($engagement_data) {
        try {
            $user_id = $engagement_data['user_id'];
            $this->distribute_incentive($user_id, 'community_engagement', $engagement_data);
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Community engagement incentive failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle exhibition participation
     */
    public function handle_exhibition_participation($participation_data) {
        try {
            $user_id = $participation_data['user_id'];
            $this->distribute_incentive($user_id, 'exhibition_participation', $participation_data);
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Exhibition participation incentive failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Distribute incentive
     */
    private function distribute_incentive($user_id, $incentive_type, $context_data = []) {
        try {
            if (!isset($this->incentive_rules[$incentive_type])) {
                throw new Exception('Invalid incentive type: ' . $incentive_type);
            }
            
            $rule = $this->incentive_rules[$incentive_type];
            
            // Check if user is eligible
            if (!$this->is_user_eligible($user_id, $incentive_type)) {
                return [
                    'success' => false,
                    'error' => 'User not eligible for this incentive'
                ];
            }
            
            // Get user wallet
            $wallet_address = $this->wallet_system->get_user_wallet($user_id);
            
            if (empty($wallet_address)) {
                throw new Exception('User wallet not found');
            }
            
            // Distribute TOLA tokens
            $distribution_result = $this->wallet_system->distribute_tokens(
                $wallet_address,
                $rule['tola_amount'],
                $incentive_type,
                $context_data
            );
            
            if (!$distribution_result['success']) {
                throw new Exception('Token distribution failed: ' . $distribution_result['error']);
            }
            
            // Record in accounting system
            $this->accounting_system->record_incentive_distribution(
                $user_id,
                $incentive_type,
                $rule['tola_amount'],
                $distribution_result['transaction_hash'],
                $context_data
            );
            
            // Apply platform credit restriction if needed
            if ($rule['platform_credit_only'] && $this->config['platform_credit_restriction']) {
                $this->conversion_system->restrict_to_platform_credit($user_id, $rule['tola_amount']);
            }
            
            // Log the distribution
            $this->log_incentive_distribution($user_id, $incentive_type, $rule['tola_amount'], $context_data);
            
            return [
                'success' => true,
                'incentive_type' => $incentive_type,
                'tola_amount' => $rule['tola_amount'],
                'transaction_hash' => $distribution_result['transaction_hash'],
                'platform_credit_only' => $rule['platform_credit_only']
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Incentive distribution failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle conversion request
     */
    public function handle_conversion_request($user_id) {
        try {
            if (!$this->config['conversion_enabled']) {
                return [
                    'success' => false,
                    'error' => 'Conversion not yet enabled. Need 1000 artists milestone.'
                ];
            }
            
            $conversion_result = $this->conversion_system->process_conversion_request($user_id);
            
            if ($conversion_result['success']) {
                $this->log_conversion_request($user_id, $conversion_result);
            }
            
            return $conversion_result;
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Conversion request failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle milestone reached
     */
    public function handle_milestone_reached() {
        try {
            $this->enable_conversion();
            $this->notify_milestone_reached();
            
            error_log('VORTEX AI Engine: 1000 artists milestone reached - conversion enabled');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Milestone handling failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Enable conversion
     */
    private function enable_conversion() {
        $this->config['conversion_enabled'] = true;
        update_option('vortex_conversion_enabled', true);
        
        // Notify all users
        $this->notify_conversion_enabled();
    }
    
    /**
     * Run daily audit
     */
    public function run_daily_audit() {
        try {
            $audit_results = [
                'total_distributions' => $this->accounting_system->get_daily_distributions(),
                'total_tola_distributed' => $this->accounting_system->get_daily_tola_distributed(),
                'artist_count' => $this->get_artist_count(),
                'conversion_status' => $this->config['conversion_enabled'],
                'fraud_detection' => $this->run_fraud_detection()
            ];
            
            $this->log_audit_results($audit_results);
            
            error_log('VORTEX AI Engine: Daily audit completed');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Daily audit failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Run fraud detection
     */
    public function run_fraud_detection() {
        try {
            $fraud_indicators = [
                'multiple_accounts' => $this->detect_multiple_accounts(),
                'unusual_activity' => $this->detect_unusual_activity(),
                'suspicious_conversions' => $this->detect_suspicious_conversions()
            ];
            
            return $fraud_indicators;
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Fraud detection failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Handle claim incentive AJAX
     */
    public function handle_claim_incentive() {
        check_ajax_referer('vortex_incentive_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $incentive_type = sanitize_text_field($_POST['incentive_type'] ?? '');
        $context_data = json_decode(stripslashes($_POST['context_data'] ?? '{}'), true);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if (empty($incentive_type)) {
            wp_send_json_error(['message' => 'Incentive type is required']);
        }
        
        $result = $this->distribute_incentive($user_id, $incentive_type, $context_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle check conversion status AJAX
     */
    public function handle_check_conversion_status() {
        check_ajax_referer('vortex_conversion_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $status = [
            'conversion_enabled' => $this->config['conversion_enabled'],
            'artist_count' => $this->get_artist_count(),
            'milestone_required' => $this->config['milestone_artist_count'],
            'user_platform_credits' => $this->conversion_system->get_user_platform_credits($user_id),
            'conversion_eligible' => $this->conversion_system->is_user_conversion_eligible($user_id)
        ];
        
        wp_send_json_success($status);
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
    
    private function is_new_artist_registration($user_id) {
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_incentive_distributions 
             WHERE user_id = %d AND incentive_type = 'subscription_signup'",
            $user_id
        ));
        return $existing == 0;
    }
    
    private function is_first_sale($user_id) {
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_incentive_distributions 
             WHERE user_id = %d AND incentive_type = 'first_sale'",
            $user_id
        ));
        return $existing == 0;
    }
    
    private function is_user_eligible($user_id, $incentive_type) {
        // Check daily limits, user status, etc.
        return true; // Simplified for now
    }
    
    private function log_incentive_distribution($user_id, $incentive_type, $amount, $context_data) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'vortex_incentive_distributions', [
            'user_id' => $user_id,
            'incentive_type' => $incentive_type,
            'amount' => $amount,
            'context_data' => json_encode($context_data),
            'created_at' => current_time('mysql')
        ]);
    }
    
    private function log_conversion_request($user_id, $conversion_result) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'vortex_conversion_requests', [
            'user_id' => $user_id,
            'requested_amount' => $conversion_result['requested_amount'],
            'converted_amount' => $conversion_result['converted_amount'],
            'status' => $conversion_result['success'] ? 'completed' : 'failed',
            'created_at' => current_time('mysql')
        ]);
    }
    
    private function log_audit_results($audit_results) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'vortex_audit_logs', [
            'audit_type' => 'daily_incentive_audit',
            'audit_data' => json_encode($audit_results),
            'created_at' => current_time('mysql')
        ]);
    }
    
    private function detect_multiple_accounts() {
        // Implement multiple account detection logic
        return [];
    }
    
    private function detect_unusual_activity() {
        // Implement unusual activity detection logic
        return [];
    }
    
    private function detect_suspicious_conversions() {
        // Implement suspicious conversion detection logic
        return [];
    }
    
    private function notify_milestone_reached() {
        // Send notifications to all users about milestone
        do_action('vortex_milestone_notification', $this->config['milestone_artist_count']);
    }
    
    private function notify_conversion_enabled() {
        // Send notifications to all users about conversion being enabled
        do_action('vortex_conversion_enabled_notification');
    }
    
    /**
     * Get incentive audit system status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'artist_count' => $this->get_artist_count(),
            'milestone_required' => $this->config['milestone_artist_count'],
            'conversion_enabled' => $this->config['conversion_enabled'],
            'platform_credit_restriction' => $this->config['platform_credit_restriction'],
            'incentive_rules' => array_keys($this->incentive_rules)
        ];
    }
} 