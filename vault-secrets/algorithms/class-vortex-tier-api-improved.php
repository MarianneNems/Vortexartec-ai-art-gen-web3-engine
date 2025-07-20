<?php
/**
 * Improved Tier API Implementation
 * Based on user's approach but with security fixes and proper PHP syntax
 * 
 * @package VortexAIEngine
 * @version 3.1.1
 */

namespace VortexAIEngine;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

class Tier_API_Improved extends WP_REST_Controller {
    
    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var array Tier configurations */
    private $tiers = [
        'basic' => [
            'limit' => 250,
            'node' => 'H100x1',
            'node_config' => [
                'type' => 'H100',
                'count' => 1,
                'memory' => '80GB',
                'compute_units' => 1.0
            ],
            'cost_per_generation' => 0.024
        ],
        'essential' => [
            'limit' => 600,
            'node' => 'H100x2',
            'node_config' => [
                'type' => 'H100',
                'count' => 2,
                'memory' => '160GB',
                'compute_units' => 2.0
            ],
            'cost_per_generation' => 0.024
        ],
        'premium' => [
            'limit' => 1500,
            'node' => 'H200x1',
            'node_config' => [
                'type' => 'H200',
                'count' => 1,
                'memory' => '141GB',
                'compute_units' => 2.5
            ],
            'cost_per_generation' => 0.024
        ]
    ];
    
    /** @var \Redis|null Redis client */
    private $redis_client = null;
    
    /** @var \VortexAIEngine_EnhancedOrchestrator Enhanced orchestrator */
    private $orchestrator = null;
    
    /** @var \VortexAIEngine_Security Security instance */
    private $security = null;
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->orchestrator = \VortexAIEngine_EnhancedOrchestrator::getInstance();
        $this->security = \VortexAIEngine_Security::getInstance();
        
        add_action('rest_api_init', [$this, 'register_routes']);
        $this->register_shortcodes();
        $this->initialize_redis();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'vortex/v3/tier';
        
        foreach ($this->tiers as $tier => $config) {
            // Generation endpoint
            register_rest_route($namespace, "/$tier/generate", [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'handle_generation'],
                'permission_callback' => [$this, 'permission_check'],
                'args' => $this->get_generate_args(),
            ]);
            
            // Status endpoint
            register_rest_route($namespace, "/$tier/status", [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'handle_status'],
                'permission_callback' => [$this, 'permission_check'],
                'args' => [
                    'api_key' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]);
        }
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        foreach ($this->tiers as $tier => $config) {
            add_shortcode("vortex_tier_{$tier}_api", function($atts) use ($tier) {
                return $this->render_tier_interface($tier, $atts);
            });
        }
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Get generation endpoint arguments
     */
    private function get_generate_args() {
        return [
            'api_key' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => [$this, 'validate_api_key']
            ],
            'query' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'validate_callback' => [$this, 'validate_query']
            ],
            'style' => [
                'required' => false,
                'default' => 'artistic',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'quality' => [
                'required' => false,
                'default' => 'standard',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ];
    }
    
    /**
     * Enhanced permission check with proper security
     */
    public function permission_check(WP_REST_Request $request) {
        // Check for API key in request
        $api_key = $request->get_param('api_key');
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API key is required', ['status' => 401]);
        }
        
        // Validate API key
        if (!$this->validate_api_key_security($api_key)) {
            return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
        }
        
        // Additional security checks
        if (!$this->check_rate_limiting($request)) {
            return new WP_Error('rate_limited', 'Rate limit exceeded', ['status' => 429]);
        }
        
        return true;
    }
    
    /**
     * Handle generation requests (unified handler)
     */
    public function handle_generation(WP_REST_Request $request) {
        // Extract tier from route
        $route = $request->get_route();
        preg_match('/\/tier\/([^\/]+)\/generate/', $route, $matches);
        $tier = $matches[1] ?? '';
        
        if (!isset($this->tiers[$tier])) {
            return new WP_Error('invalid_tier', 'Invalid tier specified', ['status' => 400]);
        }
        
        $config = $this->tiers[$tier];
        $api_key = $request->get_param('api_key');
        $user_data = $this->get_user_from_api_key($api_key);
        
        if (!$user_data) {
            return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
        }
        
        // Check rate limits
        $rate_limit_result = $this->check_tier_rate_limit($user_data['user_id'], $tier);
        if (!$rate_limit_result['allowed']) {
            return new WP_Error('rate_limit_exceeded', $rate_limit_result['message'], [
                'status' => 429,
                'data' => $rate_limit_result
            ]);
        }
        
        try {
            // Execute enhanced orchestration
            $orchestration_params = [
                'query' => $request->get_param('query'),
                'style' => $request->get_param('style'),
                'quality' => $request->get_param('quality'),
                'tier' => $tier,
                'colossalai_config' => [
                    'node_type' => $config['node_config']['type'],
                    'node_count' => $config['node_config']['count'],
                    'memory' => $config['node_config']['memory'],
                    'compute_units' => $config['node_config']['compute_units']
                ]
            ];
            
            $result = $this->orchestrator->executeEnhancedOrchestration(
                'generate',
                $orchestration_params,
                $user_data['user_id']
            );
            
            // Increment usage counter
            $this->increment_usage($user_data['user_id'], $tier);
            
            // Log usage
            $this->log_usage($user_data['user_id'], $tier, 'generate', $result);
            
            return rest_ensure_response([
                'success' => true,
                'tier' => $tier,
                'result' => $result,
                'usage' => [
                    'used' => $rate_limit_result['used'] + 1,
                    'limit' => $config['limit'],
                    'remaining' => $config['limit'] - ($rate_limit_result['used'] + 1)
                ],
                'colossalai_nodes' => $config['node'],
                'generation_id' => wp_generate_uuid4(),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("[VortexAI Tier API] Generation failed: " . $e->getMessage());
            return new WP_Error('generation_failed', 'Generation request failed', ['status' => 500]);
        }
    }
    
    /**
     * Handle status requests (unified handler)
     */
    public function handle_status(WP_REST_Request $request) {
        // Extract tier from route
        $route = $request->get_route();
        preg_match('/\/tier\/([^\/]+)\/status/', $route, $matches);
        $tier = $matches[1] ?? '';
        
        if (!isset($this->tiers[$tier])) {
            return new WP_Error('invalid_tier', 'Invalid tier specified', ['status' => 400]);
        }
        
        $config = $this->tiers[$tier];
        $api_key = $request->get_param('api_key');
        $user_data = $this->get_user_from_api_key($api_key);
        
        if (!$user_data) {
            return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
        }
        
        $usage_data = $this->get_usage_data($user_data['user_id'], $tier);
        
        return rest_ensure_response([
            'success' => true,
            'tier' => [
                'name' => ucfirst($tier),
                'level' => $tier,
                'colossalai_nodes' => $config['node'],
                'node_config' => $config['node_config']
            ],
            'usage' => [
                'used' => $usage_data['used'],
                'limit' => $config['limit'],
                'remaining' => $config['limit'] - $usage_data['used'],
                'reset_date' => $usage_data['reset_date']
            ],
            'api_key' => substr($api_key, 0, 8) . '...' . substr($api_key, -4),
            'status' => $usage_data['used'] >= $config['limit'] ? 'limit_reached' : 'active',
            'timestamp' => time()
        ]);
    }
    
    /**
     * Initialize Redis connection with error handling
     */
    private function initialize_redis() {
        if (class_exists('Redis')) {
            try {
                $this->redis_client = new \Redis();
                $redis_host = get_option('vortex_redis_host', '127.0.0.1');
                $redis_port = get_option('vortex_redis_port', 6379);
                $redis_auth = get_option('vortex_redis_auth', '');
                
                $connected = $this->redis_client->connect($redis_host, $redis_port, 2); // 2 second timeout
                
                if (!$connected) {
                    throw new \Exception('Redis connection failed');
                }
                
                if (!empty($redis_auth)) {
                    $this->redis_client->auth($redis_auth);
                }
                
                // Test connection
                $this->redis_client->ping();
                
                error_log('[VortexAI Tier API] Redis connection established');
                
            } catch (\Exception $e) {
                error_log('[VortexAI Tier API] Redis initialization failed: ' . $e->getMessage());
                $this->redis_client = null;
            }
        }
    }
    
    /**
     * Check tier rate limit with Redis or fallback
     */
    private function check_tier_rate_limit($user_id, $tier) {
        $config = $this->tiers[$tier];
        $monthly_limit = $config['limit'];
        $key = "vortex_tier_usage:{$user_id}:{$tier}:" . date('Y-m');
        
        if ($this->redis_client) {
            try {
                $current_usage = $this->redis_client->get($key);
                $current_usage = $current_usage ? (int)$current_usage : 0;
                
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
                
            } catch (\Exception $e) {
                error_log('[VortexAI Tier API] Redis rate limit check failed: ' . $e->getMessage());
                return $this->check_fallback_rate_limit($user_id, $tier, $monthly_limit);
            }
        }
        
        return $this->check_fallback_rate_limit($user_id, $tier, $monthly_limit);
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
     * Increment usage counter
     */
    private function increment_usage($user_id, $tier) {
        $key = "vortex_tier_usage:{$user_id}:{$tier}:" . date('Y-m');
        
        if ($this->redis_client) {
            try {
                $this->redis_client->incr($key);
                $this->redis_client->expire($key, strtotime('first day of next month 00:00:00') - time());
                return;
            } catch (\Exception $e) {
                error_log('[VortexAI Tier API] Redis usage increment failed: ' . $e->getMessage());
            }
        }
        
        // Fallback to WordPress options
        $usage_key = "vortex_tier_usage_{$user_id}_{$tier}_" . date('Y_m');
        $current_usage = get_option($usage_key, 0);
        update_option($usage_key, $current_usage + 1);
    }
    
    /**
     * Get or create API key with enhanced security
     */
    private function get_or_create_api_key($user_id, $tier) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_tier_api_keys';
        
        // Try to get existing key
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT api_key FROM $table WHERE user_id = %d AND tier = %s AND status = 'active' ORDER BY created_at DESC LIMIT 1",
            $user_id, $tier
        ));
        
        if ($row) {
            return $this->decrypt_api_key($row->api_key);
        }
        
        // Generate new API key
        $api_key = 'vortex_' . $tier . '_' . wp_generate_password(32, false, false);
        $encrypted = $this->encrypt_api_key($api_key);
        
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'tier' => $tier,
            'api_key' => $encrypted,
            'status' => 'active',
            'created_at' => current_time('mysql')
        ]);
        
        return $api_key;
    }
    
    /**
     * Secure API key encryption using proper methods
     */
    private function encrypt_api_key($key) {
        return \VortexAIEngine_Security::encrypt_api_key($key);
    }
    
    /**
     * Secure API key decryption
     */
    private function decrypt_api_key($encrypted_key) {
        return \VortexAIEngine_Security::decrypt_api_key($encrypted_key);
    }
    
    /**
     * Validate API key format and security
     */
    public function validate_api_key($api_key) {
        return \VortexAIEngine_Security::validate_api_key($api_key);
    }
    
    /**
     * Enhanced API key security validation
     */
    private function validate_api_key_security($api_key) {
        $decrypted_key = $this->decrypt_api_key($api_key);
        return $this->validate_api_key($decrypted_key);
    }
    
    /**
     * Get user data from API key
     */
    private function get_user_from_api_key($api_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_tier_api_keys';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, tier, status, created_at FROM $table WHERE api_key = %s AND status = 'active'",
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
     * Validate query parameter
     */
    public function validate_query($query) {
        if (empty($query) || strlen($query) < 10) {
            return false;
        }
        
        if (strlen($query) > 500) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check rate limiting for security
     */
    private function check_rate_limiting(WP_REST_Request $request) {
        // Add additional rate limiting checks here
        return true;
    }
    
    /**
     * Get usage data
     */
    private function get_usage_data($user_id, $tier) {
        $key = "vortex_tier_usage:{$user_id}:{$tier}:" . date('Y-m');
        
        if ($this->redis_client) {
            try {
                $used = $this->redis_client->get($key);
                return [
                    'used' => $used ? (int)$used : 0,
                    'reset_date' => date('Y-m-01', strtotime('first day of next month'))
                ];
            } catch (\Exception $e) {
                error_log('[VortexAI Tier API] Redis usage fetch failed: ' . $e->getMessage());
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
     * Log usage for analytics
     */
    private function log_usage($user_id, $tier, $action, $result) {
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
                'colossalai_nodes' => $this->tiers[$tier]['node'],
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s']
        );
    }
    
    /**
     * Render tier interface (simplified)
     */
    private function render_tier_interface($tier, $atts) {
        $defaults = [
            'height' => '600px',
            'width' => '100%',
            'show_usage' => 'true',
            'show_api_key' => 'false'
        ];
        $args = shortcode_atts($defaults, $atts, "vortex_tier_{$tier}_api");
        
        $config = $this->tiers[$tier];
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return '<div class="vortex-tier-error">Please log in to access the ' . ucfirst($tier) . ' tier.</div>';
        }
        
        ob_start();
        ?>
        <div class="vortex-tier-interface vortex-tier-<?php echo esc_attr($tier); ?>" 
             data-tier="<?php echo esc_attr($tier); ?>"
             style="height: <?php echo esc_attr($args['height']); ?>; width: <?php echo esc_attr($args['width']); ?>;">
            
            <div class="vortex-tier-header">
                <h3><?php echo esc_html(ucfirst($tier)); ?> Tier</h3>
                <div class="tier-specs">
                    <span class="colossalai-nodes"><?php echo esc_html($config['node']); ?></span>
                    <span class="monthly-limit"><?php echo esc_html(number_format($config['limit'])); ?> generations/month</span>
                </div>
            </div>
            
            <div class="vortex-tier-generation">
                <textarea class="tier-prompt" placeholder="Describe the artwork you want to generate..." rows="4"></textarea>
                <div class="generation-options">
                    <select class="tier-style">
                        <option value="artistic">Artistic</option>
                        <option value="photorealistic">Photorealistic</option>
                        <option value="abstract">Abstract</option>
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
                        Generate with <?php echo esc_html($config['node']); ?>
                    </button>
                </div>
            </div>
            
            <div class="generation-result">
                <div class="loading-state" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Generating on <?php echo esc_html($config['node']); ?> nodes...</p>
                </div>
                <div class="result-content"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_style('vortex-tier-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/tier-interface.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-tier-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/tier-interface.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        
        wp_localize_script('vortex-tier-js', 'vortexTierConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('vortex/v3/tier/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'tierConfigs' => $this->tiers
        ]);
    }
}

// Initialize the improved Tier API system
add_action('plugins_loaded', function() {
    if (class_exists('VortexAIEngine_EnhancedOrchestrator') && class_exists('VortexAIEngine_Security')) {
        \VortexAIEngine\Tier_API_Improved::get_instance();
    }
}); 