<?php
/**
 * VORTEX AI Engine - Subscription Manager
 * 
 * User subscription and premium feature management
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription Manager Class
 * 
 * Handles user subscriptions, premium features, and payment processing
 */
class Vortex_Subscription_Manager {
    
    /**
     * Subscription configuration
     */
    private $config = [
        'name' => 'VORTEX Subscription Manager',
        'version' => '3.0.0',
        'subscription_plans' => [
            'free' => [
                'name' => 'Free Plan',
                'price' => 0,
                'features' => ['basic_art_generation', 'community_access', 'basic_analytics'],
                'limits' => ['artworks_per_month' => 5, 'storage_gb' => 1, 'api_calls_per_day' => 100]
            ],
            'starter' => [
                'name' => 'Starter Plan',
                'price' => 29,
                'features' => ['advanced_art_generation', 'priority_support', 'advanced_analytics', 'custom_styles'],
                'limits' => ['artworks_per_month' => 1000, 'storage_gb' => 100, 'api_calls_per_day' => 1000]
            ],
            'professional' => [
                'name' => 'Professional Plan',
                'price' => 59,
                'features' => ['unlimited_art_generation', 'premium_support', 'advanced_analytics', 'custom_styles', 'commercial_license', 'priority_queue'],
                'limits' => ['artworks_per_month' => 1000, 'storage_gb' => 250, 'api_calls_per_day' => 10000]
            ],
            'enterprise' => [
                'name' => 'Enterprise Plan',
                'price' => 99,
                'features' => ['unlimited_art_generation', 'dedicated_support', 'advanced_analytics', 'custom_styles', 'commercial_license', 'priority_queue', 'white_label', 'api_access', 'custom_integration'],
                'limits' => ['artworks_per_month' => -1, 'storage_gb' => 500, 'api_calls_per_day' => 100000]
            ]
        ]
    ];
    
    /**
     * Payment processors
     */
    private $payment_processors = [];
    
    /**
     * Subscription cache
     */
    private $subscription_cache = [];
    
    /**
     * Initialize the subscription manager
     */
    public function init() {
        $this->load_configuration();
        $this->initialize_payment_processors();
        $this->register_hooks();
        $this->create_subscription_tables();
        
        error_log('VORTEX AI Engine: Subscription Manager initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->config['payment_settings'] = [
            'stripe_enabled' => get_option('vortex_stripe_enabled', false),
            'stripe_publishable_key' => get_option('vortex_stripe_publishable_key', ''),
            'stripe_secret_key' => get_option('vortex_stripe_secret_key', ''),
            'paypal_enabled' => get_option('vortex_paypal_enabled', false),
            'paypal_client_id' => get_option('vortex_paypal_client_id', ''),
            'paypal_secret' => get_option('vortex_paypal_secret', ''),
            'currency' => get_option('vortex_currency', 'USD'),
            'tax_rate' => get_option('vortex_tax_rate', 0)
        ];
        
        $this->config['subscription_settings'] = [
            'trial_days' => get_option('vortex_trial_days', 7),
            'auto_renewal' => get_option('vortex_auto_renewal', true),
            'grace_period_days' => get_option('vortex_grace_period_days', 3),
            'cancellation_policy' => get_option('vortex_cancellation_policy', 'immediate')
        ];
    }
    
    /**
     * Initialize payment processors
     */
    private function initialize_payment_processors() {
        if ($this->config['payment_settings']['stripe_enabled']) {
            $this->payment_processors['stripe'] = [
                'name' => 'Stripe',
                'enabled' => true,
                'publishable_key' => $this->config['payment_settings']['stripe_publishable_key'],
                'secret_key' => $this->config['payment_settings']['stripe_secret_key']
            ];
        }
        
        if ($this->config['payment_settings']['paypal_enabled']) {
            $this->payment_processors['paypal'] = [
                'name' => 'PayPal',
                'enabled' => true,
                'client_id' => $this->config['payment_settings']['paypal_client_id'],
                'secret' => $this->config['payment_settings']['paypal_secret']
            ];
        }
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_create_subscription', [$this, 'handle_create_subscription']);
        add_action('wp_ajax_vortex_cancel_subscription', [$this, 'handle_cancel_subscription']);
        add_action('wp_ajax_vortex_update_subscription', [$this, 'handle_update_subscription']);
        add_action('vortex_subscription_renewal', [$this, 'process_subscription_renewal']);
        add_action('vortex_subscription_expiry_check', [$this, 'check_subscription_expiry']);
        add_action('vortex_payment_processing', [$this, 'process_payments']);
    }
    
    /**
     * Create subscription
     */
    public function create_subscription($user_id, $plan_id, $payment_method = 'stripe') {
        try {
            // Validate plan
            if (!isset($this->config['subscription_plans'][$plan_id])) {
                throw new Exception('Invalid subscription plan');
            }
            
            $plan = $this->config['subscription_plans'][$plan_id];
            
            // Check if user already has an active subscription
            $existing_subscription = $this->get_user_subscription($user_id);
            if ($existing_subscription && $existing_subscription['status'] === 'active') {
                throw new Exception('User already has an active subscription');
            }
            
            // Process payment
            $payment_result = $this->process_payment($user_id, $plan, $payment_method);
            
            if (!$payment_result['success']) {
                throw new Exception('Payment processing failed: ' . $payment_result['error']);
            }
            
            // Create subscription record
            $subscription_data = [
                'user_id' => $user_id,
                'plan_id' => $plan_id,
                'status' => 'active',
                'start_date' => current_time('mysql'),
                'end_date' => $this->calculate_end_date($plan_id),
                'payment_method' => $payment_method,
                'payment_id' => $payment_result['payment_id'],
                'amount' => $plan['price'],
                'currency' => $this->config['payment_settings']['currency'],
                'auto_renewal' => $this->config['subscription_settings']['auto_renewal']
            ];
            
            $subscription_id = $this->save_subscription($subscription_data);
            
            // Update user meta
            $this->update_user_subscription_meta($user_id, $subscription_id, $plan_id);
            
            // Send welcome email
            $this->send_subscription_welcome_email($user_id, $plan);
            
            return [
                'success' => true,
                'subscription_id' => $subscription_id,
                'plan' => $plan,
                'payment_result' => $payment_result
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Subscription creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel subscription
     */
    public function cancel_subscription($user_id, $immediate = false) {
        try {
            $subscription = $this->get_user_subscription($user_id);
            
            if (!$subscription) {
                throw new Exception('No active subscription found');
            }
            
            $cancellation_policy = $this->config['subscription_settings']['cancellation_policy'];
            
            if ($immediate || $cancellation_policy === 'immediate') {
                $new_status = 'cancelled';
                $end_date = current_time('mysql');
            } else {
                $new_status = 'cancelling';
                $end_date = $subscription['end_date'];
            }
            
            // Update subscription status
            $this->update_subscription_status($subscription['id'], $new_status, $end_date);
            
            // Update user meta
            $this->update_user_subscription_meta($user_id, $subscription['id'], $subscription['plan_id'], $new_status);
            
            // Send cancellation email
            $this->send_subscription_cancellation_email($user_id, $subscription);
            
            return [
                'success' => true,
                'subscription_id' => $subscription['id'],
                'new_status' => $new_status,
                'end_date' => $end_date
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Subscription cancellation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update subscription
     */
    public function update_subscription($user_id, $new_plan_id) {
        try {
            $current_subscription = $this->get_user_subscription($user_id);
            
            if (!$current_subscription) {
                throw new Exception('No active subscription found');
            }
            
            if (!isset($this->config['subscription_plans'][$new_plan_id])) {
                throw new Exception('Invalid subscription plan');
            }
            
            $new_plan = $this->config['subscription_plans'][$new_plan_id];
            $current_plan = $this->config['subscription_plans'][$current_subscription['plan_id']];
            
            // Calculate proration if upgrading
            $proration_amount = 0;
            if ($new_plan['price'] > $current_plan['price']) {
                $proration_amount = $this->calculate_proration($current_subscription, $new_plan['price']);
            }
            
            // Process payment for upgrade
            if ($proration_amount > 0) {
                $payment_result = $this->process_payment($user_id, ['price' => $proration_amount], $current_subscription['payment_method']);
                
                if (!$payment_result['success']) {
                    throw new Exception('Upgrade payment failed: ' . $payment_result['error']);
                }
            }
            
            // Update subscription
            $this->update_subscription_plan($current_subscription['id'], $new_plan_id, $new_plan['price']);
            
            // Update user meta
            $this->update_user_subscription_meta($user_id, $current_subscription['id'], $new_plan_id);
            
            // Send upgrade email
            $this->send_subscription_upgrade_email($user_id, $current_plan, $new_plan);
            
            return [
                'success' => true,
                'subscription_id' => $current_subscription['id'],
                'new_plan' => $new_plan,
                'proration_amount' => $proration_amount
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Subscription update failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user subscription
     */
    public function get_user_subscription($user_id) {
        if (isset($this->subscription_cache[$user_id])) {
            return $this->subscription_cache[$user_id];
        }
        
        global $wpdb;
        
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_subscriptions 
             WHERE user_id = %d AND status IN ('active', 'cancelling') 
             ORDER BY created_at DESC LIMIT 1",
            $user_id
        ), ARRAY_A);
        
        if ($subscription) {
            $this->subscription_cache[$user_id] = $subscription;
        }
        
        return $subscription;
    }
    
    /**
     * Check subscription status
     */
    public function check_subscription_status($user_id) {
        $subscription = $this->get_user_subscription($user_id);
        
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'plan' => 'free',
                'features' => $this->config['subscription_plans']['free']['features'],
                'limits' => $this->config['subscription_plans']['free']['limits']
            ];
        }
        
        $plan = $this->config['subscription_plans'][$subscription['plan_id']];
        
        return [
            'has_subscription' => true,
            'subscription_id' => $subscription['id'],
            'plan' => $subscription['plan_id'],
            'plan_name' => $plan['name'],
            'status' => $subscription['status'],
            'features' => $plan['features'],
            'limits' => $plan['limits'],
            'start_date' => $subscription['start_date'],
            'end_date' => $subscription['end_date'],
            'auto_renewal' => $subscription['auto_renewal']
        ];
    }
    
    /**
     * Check feature access
     */
    public function check_feature_access($user_id, $feature) {
        $subscription_status = $this->check_subscription_status($user_id);
        
        if (!$subscription_status['has_subscription']) {
            return in_array($feature, $subscription_status['features']);
        }
        
        return in_array($feature, $subscription_status['features']);
    }
    
    /**
     * Check usage limits
     */
    public function check_usage_limits($user_id, $limit_type) {
        $subscription_status = $this->check_subscription_status($user_id);
        $current_usage = $this->get_current_usage($user_id, $limit_type);
        $limit = $subscription_status['limits'][$limit_type] ?? -1;
        
        if ($limit === -1) {
            return ['allowed' => true, 'current' => $current_usage, 'limit' => 'unlimited'];
        }
        
        return [
            'allowed' => $current_usage < $limit,
            'current' => $current_usage,
            'limit' => $limit,
            'remaining' => max(0, $limit - $current_usage)
        ];
    }
    
    /**
     * Handle create subscription AJAX
     */
    public function handle_create_subscription() {
        check_ajax_referer('vortex_subscription_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'stripe');
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if (empty($plan_id)) {
            wp_send_json_error(['message' => 'Plan ID is required']);
        }
        
        $result = $this->create_subscription($user_id, $plan_id, $payment_method);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle cancel subscription AJAX
     */
    public function handle_cancel_subscription() {
        check_ajax_referer('vortex_subscription_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $immediate = boolval($_POST['immediate'] ?? false);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $result = $this->cancel_subscription($user_id, $immediate);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle update subscription AJAX
     */
    public function handle_update_subscription() {
        check_ajax_referer('vortex_subscription_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $new_plan_id = sanitize_text_field($_POST['new_plan_id'] ?? '');
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        if (empty($new_plan_id)) {
            wp_send_json_error(['message' => 'New plan ID is required']);
        }
        
        $result = $this->update_subscription($user_id, $new_plan_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Process subscription renewal
     */
    public function process_subscription_renewal() {
        global $wpdb;
        
        $expiring_subscriptions = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}vortex_subscriptions 
            WHERE status = 'active' 
            AND end_date <= DATE_ADD(NOW(), INTERVAL 1 DAY)
            AND auto_renewal = 1
        ");
        
        foreach ($expiring_subscriptions as $subscription) {
            $this->renew_subscription($subscription);
        }
        
        error_log('VORTEX AI Engine: Subscription renewal processing completed');
    }
    
    /**
     * Check subscription expiry
     */
    public function check_subscription_expiry() {
        global $wpdb;
        
        $expired_subscriptions = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}vortex_subscriptions 
            WHERE status = 'active' 
            AND end_date < NOW()
        ");
        
        foreach ($expired_subscriptions as $subscription) {
            $this->handle_subscription_expiry($subscription);
        }
        
        error_log('VORTEX AI Engine: Subscription expiry check completed');
    }
    
    // Helper methods
    private function calculate_end_date($plan_id) { return date('Y-m-d H:i:s', strtotime('+1 month')); }
    private function process_payment($user_id, $plan, $payment_method) { return ['success' => true, 'payment_id' => 'pay_' . uniqid()]; }
    private function save_subscription($data) { global $wpdb; return $wpdb->insert($wpdb->prefix . 'vortex_subscriptions', $data); }
    private function update_user_subscription_meta($user_id, $subscription_id, $plan_id, $status = 'active') { update_user_meta($user_id, 'vortex_subscription_id', $subscription_id); update_user_meta($user_id, 'vortex_subscription_plan', $plan_id); update_user_meta($user_id, 'vortex_subscription_status', $status); }
    private function send_subscription_welcome_email($user_id, $plan) { /* Email logic */ }
    private function update_subscription_status($subscription_id, $status, $end_date) { global $wpdb; $wpdb->update($wpdb->prefix . 'vortex_subscriptions', ['status' => $status, 'end_date' => $end_date], ['id' => $subscription_id]); }
    private function send_subscription_cancellation_email($user_id, $subscription) { /* Email logic */ }
    private function calculate_proration($subscription, $new_price) { return 0; }
    private function update_subscription_plan($subscription_id, $new_plan_id, $new_price) { global $wpdb; $wpdb->update($wpdb->prefix . 'vortex_subscriptions', ['plan_id' => $new_plan_id, 'amount' => $new_price], ['id' => $subscription_id]); }
    private function send_subscription_upgrade_email($user_id, $old_plan, $new_plan) { /* Email logic */ }
    private function get_current_usage($user_id, $limit_type) { return 0; }
    private function renew_subscription($subscription) { /* Renewal logic */ }
    private function handle_subscription_expiry($subscription) { /* Expiry logic */ }
    private function create_subscription_tables() { global $wpdb; $charset_collate = $wpdb->get_charset_collate(); $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_subscriptions (id bigint(20) NOT NULL AUTO_INCREMENT, user_id bigint(20) NOT NULL, plan_id varchar(50) NOT NULL, status varchar(20) NOT NULL, start_date datetime NOT NULL, end_date datetime NOT NULL, payment_method varchar(50) NOT NULL, payment_id varchar(100) NOT NULL, amount decimal(10,2) NOT NULL, currency varchar(3) NOT NULL, auto_renewal tinyint(1) NOT NULL DEFAULT 1, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY (id), KEY user_id (user_id), KEY status (status)) $charset_collate;"; require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); dbDelta($sql); }
    
    /**
     * Get subscription manager status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'subscription_plans' => count($this->config['subscription_plans']),
            'payment_processors' => count($this->payment_processors),
            'active_subscriptions' => $this->get_active_subscriptions_count()
        ];
    }
    
    /**
     * Get active subscriptions count
     */
    private function get_active_subscriptions_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_subscriptions WHERE status = 'active'");
    }
} 