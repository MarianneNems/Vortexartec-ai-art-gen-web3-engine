<?php
/**
 * Tier Manager - Subscription Tier API Endpoints with Rate Limiting
 * 
 * Features:
 * - Redis-based distributed rate limiting
 * - Tier-specific ColossalAI node assignments
 * - User-specific API key management
 * - Integration with executeEnhancedOrchestration()
 * - WordPress shortcode registration
 * - Comprehensive security validation
 *
 * @package VortexAIEngine
 * @version 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VortexAIEngine_TierManager')) {
class VortexAIEngine_TierManager {
    
    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var Redis Redis connection for rate limiting */
    private $redis = null;
    
    /** @var array Subscription tier configurations */
    private $tier_configs = [
        'basic' => [
            'name' => 'Basic',
            'monthly_limit' => 250,
            'colossalai_nodes' => 'H100x1',
            'node_config' => [
                'type' => 'H100',
                'count' => 1,
                'memory' => '80GB',
                'compute_units' => 1.0
            ],
            'cost_per_generation' => 0.024,
            'features' => ['basic_generation', 'standard_quality']
        ],
        'essential' => [
            'name' => 'Essential',
            'monthly_limit' => 600,
            'colossalai_nodes' => 'H100x2',
            'node_config' => [
                'type' => 'H100',
                'count' => 2,
                'memory' => '160GB',
                'compute_units' => 2.0
            ],
            'cost_per_generation' => 0.024,
            'features' => ['enhanced_generation', 'high_quality', 'batch_processing']
        ],
        'premium' => [
            'name' => 'Premium',
            'monthly_limit' => 1500,
            'colossalai_nodes' => 'H200x1',
            'node_config' => [
                'type' => 'H200',
                'count' => 1,
                'memory' => '141GB',
                'compute_units' => 2.5
            ],
            'cost_per_generation' => 0.024,
            'features' => ['premium_generation', 'ultra_quality', 'priority_processing', 'advanced_features']
        ]
    ];
    
    /** @var VortexAIEngine_EnhancedOrchestrator */
    private $enhanced_orchestrator;
    
    /** @var VortexAIEngine_Security */
    private $security;
    
    /** Singleton pattern */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /** Constructor */
    private function __construct() {
        if (class_exists('VortexAIEngine_EnhancedOrchestrator')) {
            $this->enhanced_orchestrator = VortexAIEngine_EnhancedOrchestrator::getInstance();
        }
        if (class_exists('VortexAIEngine_Security')) {
            $this->security = VortexAIEngine_Security::getInstance();
        }
        
        $this->initialize_redis();
        $this->register_rest_endpoints();
        $this->register_shortcodes();
        $this->setup_ajax_handlers();
        $this->setup_wordpress_hooks();
        
        error_log('[VortexAI Tier] Tier Manager initialized with subscription tiers');
    }
    
    /**
     * Initialize Redis connection for distributed rate limiting
     */
    private function initialize_redis() {
        if ( class_exists('Redis') ) {
            try {
                $this->redis = new Redis();
                $redis_host = get_option('vortex_redis_host', 'localhost');
                $redis_port = get_option('vortex_redis_port', 6379);
                $redis_auth = get_option('vortex_redis_auth', '');
                
                $this->redis->connect($redis_host, $redis_port);
                
                if (!empty($redis_auth)) {
                    $this->redis->auth($redis_auth);
                }
                
                // Test connection
                $this->redis->ping();
                
                error_log('[VortexAI Tier] Redis connection established');
                
            } catch (Exception $e) {
                error_log('[VortexAI Tier] Redis connection failed: ' . $e->getMessage());
                $this->redis = null;
            }
        } else {
            error_log('[VortexAI Tier] Redis extension not available, using fallback rate limiting');
        }
    }
    
    /**
     * Register REST API endpoints for each tier
     */
    private function register_rest_endpoints() {
        add_action('rest_api_init', [$this, 'register_tier_endpoints']);
    }
    
    /**
     * Register tier-specific REST endpoints
     */
    public function register_tier_endpoints() {
        foreach ($this->tier_configs as $tier => $config) {
            // Main tier API endpoint
            register_rest_route('vortex/v3', "/tier/{$tier}/generate", [
                'methods' => 'POST',
                'callback' => [$this, "rest_tier_{$tier}_generate"],
                'permission_callback' => [$this, 'check_tier_permissions'],
                'args' => [
                    'query' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'api_key' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'style' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => 'artistic',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'quality' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => 'standard',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]);
            
            // Tier status endpoint
            register_rest_route('vortex/v3', "/tier/{$tier}/status", [
                'methods' => 'GET',
                'callback' => [$this, "rest_tier_{$tier}_status"],
                'permission_callback' => [$this, 'check_tier_permissions'],
                'args' => [
                    'api_key' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]);
        }
    }
    
    /**
     * Register WordPress shortcodes for each tier
     */
    private function register_shortcodes() {
        add_shortcode('vortex_tier_basic_api', [$this, 'render_tier_basic_shortcode']);
        add_shortcode('vortex_tier_essential_api', [$this, 'render_tier_essential_shortcode']);
        add_shortcode('vortex_tier_premium_api', [$this, 'render_tier_premium_shortcode']);
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_tier_assets']);
    }
    
    /**
     * Setup AJAX handlers for tier management
     */
    private function setup_ajax_handlers() {
        add_action('wp_ajax_vortex_generate_tier_api_key', [$this, 'ajax_generate_tier_api_key']);
        add_action('wp_ajax_vortex_get_tier_usage', [$this, 'ajax_get_tier_usage']);
        add_action('wp_ajax_vortex_tier_generation_request', [$this, 'ajax_tier_generation_request']);
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_wordpress_hooks() {
        add_action('user_register', [$this, 'assign_default_tier']);
        add_action('vortex_tier_upgraded', [$this, 'handle_tier_upgrade'], 10, 3);
        add_action('vortex_daily_tier_reset', [$this, 'reset_daily_limits']);
        
        // Schedule daily reset if not already scheduled
        if (!wp_next_scheduled('vortex_daily_tier_reset')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_tier_reset');
        }
    }
    
    /**
     * Check if user has permissions for tier operations
     */
    public function check_tier_permissions($request) {
        $api_key = $request->get_param('api_key');
        
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API key is required', ['status' => 401]);
        }
        
        $user_data = $this->validate_api_key($api_key);
        if (!$user_data) {
            return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
        }
        
        return true;
    }
    
    /**
     * REST endpoint handlers for each tier
     */
    public function rest_tier_basic_generate($request) {
        return $this->handle_tier_generation('basic', $request);
    }
    
    public function rest_tier_essential_generate($request) {
        return $this->handle_tier_generation('essential', $request);
    }
    
    public function rest_tier_premium_generate($request) {
        return $this->handle_tier_generation('premium', $request);
    }
    
    public function rest_tier_basic_status($request) {
        return $this->handle_tier_status('basic', $request);
    }
    
    public function rest_tier_essential_status($request) {
        return $this->handle_tier_status('essential', $request);
    }
    
    public function rest_tier_premium_status($request) {
        return $this->handle_tier_status('premium', $request);
    }
    
    /**
     * Handle tier-specific generation requests
     */
    private function handle_tier_generation($tier, $request) {
        $api_key = $request->get_param('api_key');
        $query = $request->get_param('query');
        $style = $request->get_param('style');
        $quality = $request->get_param('quality');
        
        // Validate API key and get user data
        $user_data = $this->validate_api_key($api_key);
        if (!$user_data) {
            return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
        }
        
        // Check if user's tier matches the endpoint
        if ($user_data['tier'] !== $tier) {
            return new WP_Error('tier_mismatch', "Your subscription tier ({$user_data['tier']}) does not match this endpoint ({$tier})", ['status' => 403]);
        }
        
        // Check rate limits
        $rate_limit_result = $this->check_rate_limit($user_data['user_id'], $tier);
        if (!$rate_limit_result['allowed']) {
            return new WP_Error('rate_limit_exceeded', $rate_limit_result['message'], [
                'status' => 429,
                'data' => [
                    'limit' => $rate_limit_result['limit'],
                    'used' => $rate_limit_result['used'],
                    'reset_time' => $rate_limit_result['reset_time']
                ]
            ]);
        }
        
        try {
            // Prepare enhanced orchestration parameters with tier-specific ColossalAI config
            $orchestration_params = [
                'query' => $query,
                'style' => $style,
                'quality' => $quality,
                'tier' => $tier,
                'colossalai_config' => $this->get_tier_colossalai_config($tier),
                'user_preferences' => $this->get_user_tier_preferences($user_data['user_id'], $tier)
            ];
            
            // Execute enhanced orchestration
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'generate',
                $orchestration_params,
                $user_data['user_id']
            );
            
            // Increment usage counter
            $this->increment_tier_usage($user_data['user_id'], $tier);
            
            // Log tier usage
            $this->log_tier_usage($user_data['user_id'], $tier, 'generate', $result);
            
            return rest_ensure_response([
                'success' => true,
                'tier' => $tier,
                'result' => $result,
                'usage' => [
                    'used' => $rate_limit_result['used'] + 1,
                    'limit' => $rate_limit_result['limit'],
                    'remaining' => $rate_limit_result['limit'] - ($rate_limit_result['used'] + 1)
                ],
                'colossalai_nodes' => $this->tier_configs[$tier]['colossalai_nodes'],
                'generation_id' => wp_generate_uuid4(),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("[VortexAI Tier] Generation failed for tier {$tier}: " . $e->getMessage());
            
            return new WP_Error('generation_failed', 'Generation request failed: ' . $e->getMessage(), [
                'status' => 500,
                'data' => ['tier' => $tier, 'error_code' => $e->getCode()]
            ]);
        }
    }
    
    /**
     * Handle tier status requests
     */
    private function handle_tier_status($tier, $request) {
        $api_key = $request->get_param('api_key');
        
        // Validate API key and get user data
        $user_data = $this->validate_api_key($api_key);
        if (!$user_data) {
            return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
        }
        
        // Get current usage
        $usage_data = $this->get_tier_usage($user_data['user_id'], $tier);
        $config = $this->tier_configs[$tier];
        
        return rest_ensure_response([
            'success' => true,
            'tier' => [
                'name' => $config['name'],
                'level' => $tier,
                'colossalai_nodes' => $config['colossalai_nodes'],
                'features' => $config['features']
            ],
            'usage' => [
                'used' => $usage_data['used'],
                'limit' => $config['monthly_limit'],
                'remaining' => $config['monthly_limit'] - $usage_data['used'],
                'reset_date' => $usage_data['reset_date']
            ],
            'api_key' => substr($api_key, 0, 8) . '...' . substr($api_key, -4),
            'status' => $usage_data['used'] >= $config['monthly_limit'] ? 'limit_reached' : 'active',
            'timestamp' => time()
        ]);
    }
    
    /**
     * Check rate limits using Redis or fallback
     */
    private function check_rate_limit($user_id, $tier) {
        $config = $this->tier_configs[$tier];
        $monthly_limit = $config['monthly_limit'];
        
        if ($this->redis) {
            return $this->check_redis_rate_limit($user_id, $tier, $monthly_limit);
        } else {
            return $this->check_fallback_rate_limit($user_id, $tier, $monthly_limit);
        }
    }
    
    /**
     * Redis-based distributed rate limiting
     */
    private function check_redis_rate_limit($user_id, $tier, $monthly_limit) {
        $key = "vortex_tier_usage:{$user_id}:{$tier}:" . date('Y-m');
        
        try {
            $current_usage = $this->redis->get($key);
            if ($current_usage === false) {
                $current_usage = 0;
            } else {
                $current_usage = (int)$current_usage;
            }
            
            if ($current_usage >= $monthly_limit) {
                return [
                    'allowed' => false,
                    'used' => $current_usage,
                    'limit' => $monthly_limit,
                    'message' => "Monthly limit of {$monthly_limit} generations exceeded for {$tier} tier",
                    'reset_time' => strtotime('first day of next month 00:00:00')
                ];
            }
            
            return [
                'allowed' => true,
                'used' => $current_usage,
                'limit' => $monthly_limit,
                'reset_time' => strtotime('first day of next month 00:00:00')
            ];
            
        } catch (Exception $e) {
            error_log('[VortexAI Tier] Redis rate limit check failed: ' . $e->getMessage());
            return $this->check_fallback_rate_limit($user_id, $tier, $monthly_limit);
        }
    }
    
    /**
     * Fallback rate limiting using WordPress options
     */
    private function check_fallback_rate_limit($user_id, $tier, $monthly_limit) {
        $usage_key = "vortex_tier_usage_{$user_id}_{$tier}_" . date('Y_m');
        $current_usage = get_option($usage_key, 0);
        
        if ($current_usage >= $monthly_limit) {
            return [
                'allowed' => false,
                'used' => $current_usage,
                'limit' => $monthly_limit,
                'message' => "Monthly limit of {$monthly_limit} generations exceeded for {$tier} tier",
                'reset_time' => strtotime('first day of next month 00:00:00')
            ];
        }
        
        return [
            'allowed' => true,
            'used' => $current_usage,
            'limit' => $monthly_limit,
            'reset_time' => strtotime('first day of next month 00:00:00')
        ];
    }
    
    /**
     * Increment tier usage counter
     */
    private function increment_tier_usage($user_id, $tier) {
        if ($this->redis) {
            $key = "vortex_tier_usage:{$user_id}:{$tier}:" . date('Y-m');
            try {
                $this->redis->incr($key);
                $this->redis->expire($key, strtotime('first day of next month 00:00:00') - time());
            } catch (Exception $e) {
                error_log('[VortexAI Tier] Redis usage increment failed: ' . $e->getMessage());
                $this->increment_fallback_usage($user_id, $tier);
            }
        } else {
            $this->increment_fallback_usage($user_id, $tier);
        }
    }
    
    /**
     * Fallback usage increment
     */
    private function increment_fallback_usage($user_id, $tier) {
        $usage_key = "vortex_tier_usage_{$user_id}_{$tier}_" . date('Y_m');
        $current_usage = get_option($usage_key, 0);
        update_option($usage_key, $current_usage + 1);
    }
    
    /**
     * Get tier-specific ColossalAI configuration
     */
    private function get_tier_colossalai_config($tier) {
        $config = $this->tier_configs[$tier];
        
        return [
            'endpoint' => 'https://company.hpc-ai.com/api/v1/inference',
            'node_type' => $config['node_config']['type'],
            'node_count' => $config['node_config']['count'],
            'memory' => $config['node_config']['memory'],
            'compute_units' => $config['node_config']['compute_units'],
            'tier' => $tier,
            'priority' => $tier === 'premium' ? 'high' : 'standard',
            'optimization' => true
        ];
    }
    
    /**
     * Validate API key and return user data
     */
    private function validate_api_key($api_key) {
        // Decrypt and validate API key
        $decrypted_key = VortexAIEngine_Security::decrypt_api_key($api_key);
        
        if (!VortexAIEngine_Security::validate_api_key($decrypted_key)) {
            return false;
        }
        
        // Get user data from API key
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, tier, status, created_at FROM {$wpdb->prefix}vortex_tier_api_keys WHERE api_key = %s AND status = 'active'",
            $api_key
        ));
        
        if (!$result) {
            return false;
        }
        
        return [
            'user_id' => $result->user_id,
            'tier' => $result->tier,
            'status' => $result->status,
            'created_at' => $result->created_at
        ];
    }
    
    /**
     * Generate tier-specific API key
     */
    public function generate_tier_api_key($user_id, $tier) {
        // Generate secure API key
        $api_key = 'vortex_' . $tier . '_' . wp_generate_password(32, false, false);
        
        // Encrypt API key
        $encrypted_key = VortexAIEngine_Security::encrypt_api_key($api_key);
        
        // Store in database
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_tier_api_keys';
        
        // Deactivate existing keys for this user/tier
        $wpdb->update(
            $table,
            ['status' => 'inactive'],
            ['user_id' => $user_id, 'tier' => $tier],
            ['%s'],
            ['%d', '%s']
        );
        
        // Insert new key
        $result = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'tier' => $tier,
                'api_key' => $encrypted_key,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
        
        if ($result) {
            return $api_key;
        }
        
        return false;
    }
    
    /**
     * Get tier usage data
     */
    private function get_tier_usage($user_id, $tier) {
        if ($this->redis) {
            $key = "vortex_tier_usage:{$user_id}:{$tier}:" . date('Y-m');
            try {
                $used = $this->redis->get($key);
                return [
                    'used' => $used ? (int)$used : 0,
                    'reset_date' => date('Y-m-01', strtotime('first day of next month'))
                ];
            } catch (Exception $e) {
                error_log('[VortexAI Tier] Redis usage fetch failed: ' . $e->getMessage());
            }
        }
        
        // Fallback
        $usage_key = "vortex_tier_usage_{$user_id}_{$tier}_" . date('Y_m');
        return [
            'used' => get_option($usage_key, 0),
            'reset_date' => date('Y-m-01', strtotime('first day of next month'))
        ];
    }
    
    /**
     * Get user tier preferences
     */
    private function get_user_tier_preferences($user_id, $tier) {
        $preferences = get_user_meta($user_id, "vortex_tier_preferences_{$tier}", true);
        
        if (!$preferences) {
            $preferences = [
                'default_style' => 'artistic',
                'default_quality' => $tier === 'premium' ? 'ultra' : 'standard',
                'auto_enhance' => $tier !== 'basic',
                'batch_processing' => in_array($tier, ['essential', 'premium'])
            ];
        }
        
        return $preferences;
    }
    
    /**
     * Log tier usage for analytics
     */
    private function log_tier_usage($user_id, $tier, $action, $result) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_tier_usage_log',
            [
                'user_id' => $user_id,
                'tier' => $tier,
                'action' => $action,
                'cost' => $result['cost'] ?? 0,
                'quality_score' => $result['quality_score'] ?? 0,
                'processing_time' => $result['processing_time'] ?? 0,
                'colossalai_nodes' => $this->tier_configs[$tier]['colossalai_nodes'],
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s']
        );
    }
    
    /**
     * AJAX: Generate tier API key
     */
    public function ajax_generate_tier_api_key() {
        check_ajax_referer('vortex_tier_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $tier = sanitize_text_field($_POST['tier'] ?? '');
        $user_id = get_current_user_id();
        
        if (!array_key_exists($tier, $this->tier_configs)) {
            wp_send_json_error('Invalid tier specified');
        }
        
        // Check if user has access to this tier
        $user_tier = get_user_meta($user_id, 'vortex_subscription_tier', true) ?: 'basic';
        if (!$this->can_user_access_tier($user_tier, $tier)) {
            wp_send_json_error('You do not have access to this tier');
        }
        
        $api_key = $this->generate_tier_api_key($user_id, $tier);
        
        if ($api_key) {
            wp_send_json_success([
                'api_key' => $api_key,
                'tier' => $tier,
                'config' => $this->tier_configs[$tier],
                'message' => "API key generated for {$tier} tier"
            ]);
        } else {
            wp_send_json_error('Failed to generate API key');
        }
    }
    
    /**
     * AJAX: Get tier usage
     */
    public function ajax_get_tier_usage() {
        check_ajax_referer('vortex_tier_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $tier = sanitize_text_field($_POST['tier'] ?? '');
        $user_id = get_current_user_id();
        
        if (!array_key_exists($tier, $this->tier_configs)) {
            wp_send_json_error('Invalid tier specified');
        }
        
        $usage_data = $this->get_tier_usage($user_id, $tier);
        $config = $this->tier_configs[$tier];
        
        wp_send_json_success([
            'tier' => $tier,
            'usage' => [
                'used' => $usage_data['used'],
                'limit' => $config['monthly_limit'],
                'remaining' => $config['monthly_limit'] - $usage_data['used'],
                'reset_date' => $usage_data['reset_date']
            ],
            'config' => $config
        ]);
    }
    
    /**
     * AJAX: Handle tier generation request
     */
    public function ajax_tier_generation_request() {
        check_ajax_referer('vortex_tier_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $tier = sanitize_text_field($_POST['tier'] ?? '');
        $query = sanitize_textarea_field($_POST['query'] ?? '');
        $style = sanitize_text_field($_POST['style'] ?? 'artistic');
        $quality = sanitize_text_field($_POST['quality'] ?? 'standard');
        $user_id = get_current_user_id();
        
        if (!array_key_exists($tier, $this->tier_configs)) {
            wp_send_json_error('Invalid tier specified');
        }
        
        if (empty($query)) {
            wp_send_json_error('Query is required');
        }
        
        // Check rate limits
        $rate_limit_result = $this->check_rate_limit($user_id, $tier);
        if (!$rate_limit_result['allowed']) {
            wp_send_json_error($rate_limit_result['message'], [
                'limit' => $rate_limit_result['limit'],
                'used' => $rate_limit_result['used'],
                'reset_time' => $rate_limit_result['reset_time']
            ]);
        }
        
        try {
            // Execute enhanced orchestration
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'generate',
                [
                    'query' => $query,
                    'style' => $style,
                    'quality' => $quality,
                    'tier' => $tier,
                    'colossalai_config' => $this->get_tier_colossalai_config($tier)
                ],
                $user_id
            );
            
            // Increment usage
            $this->increment_tier_usage($user_id, $tier);
            
            // Log usage
            $this->log_tier_usage($user_id, $tier, 'generate', $result);
            
            wp_send_json_success([
                'result' => $result,
                'tier' => $tier,
                'usage' => [
                    'used' => $rate_limit_result['used'] + 1,
                    'limit' => $rate_limit_result['limit'],
                    'remaining' => $rate_limit_result['limit'] - ($rate_limit_result['used'] + 1)
                ],
                'colossalai_nodes' => $this->tier_configs[$tier]['colossalai_nodes']
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Generation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if user can access specific tier
     */
    private function can_user_access_tier($user_tier, $requested_tier) {
        $tier_hierarchy = ['basic' => 1, 'essential' => 2, 'premium' => 3];
        return $tier_hierarchy[$user_tier] >= $tier_hierarchy[$requested_tier];
    }
    
    /**
     * Render Basic tier shortcode
     */
    public function render_tier_basic_shortcode($atts) {
        return $this->render_tier_shortcode('basic', $atts);
    }
    
    /**
     * Render Essential tier shortcode
     */
    public function render_tier_essential_shortcode($atts) {
        return $this->render_tier_shortcode('essential', $atts);
    }
    
    /**
     * Render Premium tier shortcode
     */
    public function render_tier_premium_shortcode($atts) {
        return $this->render_tier_shortcode('premium', $atts);
    }
    
    /**
     * Render tier-specific shortcode interface
     */
    private function render_tier_shortcode($tier, $atts) {
        $atts = shortcode_atts([
            'theme' => 'default',
            'height' => '600px',
            'width' => '100%',
            'show_usage' => 'true',
            'show_api_key' => 'false'
        ], $atts);
        
        $config = $this->tier_configs[$tier];
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return '<div class="vortex-tier-error">Please log in to access the ' . $config['name'] . ' tier.</div>';
        }
        
        // Check user access
        $user_tier = get_user_meta($user_id, 'vortex_subscription_tier', true) ?: 'basic';
        if (!$this->can_user_access_tier($user_tier, $tier)) {
            return '<div class="vortex-tier-error">You need to upgrade to access the ' . $config['name'] . ' tier.</div>';
        }
        
        // Get usage data
        $usage_data = $this->get_tier_usage($user_id, $tier);
        
        ob_start();
        ?>
        <div class="vortex-tier-interface vortex-tier-<?php echo esc_attr($tier); ?>" 
             data-tier="<?php echo esc_attr($tier); ?>"
             style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            
            <!-- Tier Header -->
            <div class="vortex-tier-header">
                <div class="tier-info">
                    <h3><?php echo esc_html($config['name']); ?> Tier</h3>
                    <div class="tier-specs">
                        <span class="colossalai-nodes"><?php echo esc_html($config['colossalai_nodes']); ?></span>
                        <span class="monthly-limit"><?php echo number_format($config['monthly_limit']); ?> generations/month</span>
                    </div>
                </div>
                
                <?php if ($atts['show_usage'] === 'true'): ?>
                <div class="tier-usage">
                    <div class="usage-stats">
                        <span class="used"><?php echo number_format($usage_data['used']); ?></span> / 
                        <span class="limit"><?php echo number_format($config['monthly_limit']); ?></span>
                    </div>
                    <div class="usage-bar">
                        <div class="usage-progress" style="width: <?php echo min(100, ($usage_data['used'] / $config['monthly_limit']) * 100); ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Generation Interface -->
            <div class="vortex-tier-generation">
                <div class="generation-form">
                    <textarea class="tier-prompt" 
                              placeholder="Describe the artwork you want to generate..."
                              rows="4"></textarea>
                    
                    <div class="generation-options">
                        <select class="tier-style">
                            <option value="artistic">Artistic</option>
                            <option value="photorealistic">Photorealistic</option>
                            <option value="abstract">Abstract</option>
                            <option value="digital_art">Digital Art</option>
                            <?php if ($tier !== 'basic'): ?>
                            <option value="professional">Professional</option>
                            <?php endif; ?>
                            <?php if ($tier === 'premium'): ?>
                            <option value="ultra_realistic">Ultra Realistic</option>
                            <option value="masterpiece">Masterpiece</option>
                            <?php endif; ?>
                        </select>
                        
                        <select class="tier-quality">
                            <option value="standard">Standard</option>
                            <?php if ($tier !== 'basic'): ?>
                            <option value="high">High Quality</option>
                            <?php endif; ?>
                            <?php if ($tier === 'premium'): ?>
                            <option value="ultra">Ultra Quality</option>
                            <?php endif; ?>
                        </select>
                        
                        <button class="tier-generate-btn" type="button">
                            Generate with <?php echo esc_html($config['colossalai_nodes']); ?>
                        </button>
                    </div>
                </div>
                
                <div class="generation-result">
                    <div class="loading-state" style="display: none;">
                        <div class="loading-spinner"></div>
                        <p>Generating on <?php echo esc_html($config['colossalai_nodes']); ?> nodes...</p>
                    </div>
                    
                    <div class="result-content"></div>
                </div>
            </div>
            
            <?php if ($atts['show_api_key'] === 'true' && current_user_can('manage_options')): ?>
            <!-- API Key Management -->
            <div class="vortex-tier-api">
                <h4>API Key Management</h4>
                <button class="generate-api-key-btn" data-tier="<?php echo esc_attr($tier); ?>">
                    Generate <?php echo esc_html($config['name']); ?> API Key
                </button>
                <div class="api-key-display"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue tier-specific assets
     */
    public function enqueue_tier_assets() {
        wp_enqueue_style('vortex-tier-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/tier-interface.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-tier-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/tier-interface.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        
        wp_localize_script('vortex-tier-js', 'vortexTierConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('vortex/v3/'),
            'nonce' => wp_create_nonce('vortex_tier_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'tierConfigs' => $this->tier_configs
        ]);
    }
    
    /**
     * Assign default tier to new users
     */
    public function assign_default_tier($user_id) {
        update_user_meta($user_id, 'vortex_subscription_tier', 'basic');
        
        // Generate default API key
        $this->generate_tier_api_key($user_id, 'basic');
    }
    
    /**
     * Handle tier upgrades
     */
    public function handle_tier_upgrade($user_id, $old_tier, $new_tier) {
        // Update user tier
        update_user_meta($user_id, 'vortex_subscription_tier', $new_tier);
        
        // Generate new API key for new tier
        $this->generate_tier_api_key($user_id, $new_tier);
        
        // Log tier upgrade
        error_log("[VortexAI Tier] User {$user_id} upgraded from {$old_tier} to {$new_tier}");
    }
    
    /**
     * Reset daily limits (called by cron)
     */
    public function reset_daily_limits() {
        // This is for monthly limits, but can be extended for daily limits if needed
        error_log('[VortexAI Tier] Daily tier reset executed');
    }
    
    /**
     * Create necessary database tables
     */
    public static function create_tier_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // API Keys table
        $api_keys_table = $wpdb->prefix . 'vortex_tier_api_keys';
        $api_keys_sql = "CREATE TABLE $api_keys_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tier varchar(20) NOT NULL,
            api_key text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_tier (user_id, tier),
            KEY status (status)
        ) $charset_collate;";
        
        // Usage log table
        $usage_log_table = $wpdb->prefix . 'vortex_tier_usage_log';
        $usage_log_sql = "CREATE TABLE $usage_log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tier varchar(20) NOT NULL,
            action varchar(50) NOT NULL,
            cost decimal(10,6) NOT NULL DEFAULT 0,
            quality_score decimal(5,3) NOT NULL DEFAULT 0,
            processing_time decimal(8,3) NOT NULL DEFAULT 0,
            colossalai_nodes varchar(20) NOT NULL,
            timestamp datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_tier_date (user_id, tier, timestamp),
            KEY action (action)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($api_keys_sql);
        dbDelta($usage_log_sql);
    }
    
    /**
     * Get tier configuration
     */
    public function get_tier_config($tier) {
        return $this->tier_configs[$tier] ?? null;
    }
    
    /**
     * Get all tier configurations
     */
    public function get_all_tier_configs() {
        return $this->tier_configs;
    }
}
}

// Initialize on plugin activation
if (class_exists('VortexAIEngine_TierManager')) {
    register_activation_hook(VORTEX_AI_ENGINE_PLUGIN_FILE, ['VortexAIEngine_TierManager', 'create_tier_tables']);
    
    // Bootstrap
    VortexAIEngine_TierManager::getInstance();
} 