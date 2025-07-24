<?php
// Vault Path: secret/data/vortex-ai/tier_subscription_algorithms

// File: includes/class-vortex-tier-api.php
namespace VortexAIEngine;

use WP_REST_Controller;
use WP_REST_Server;

class Tier_API extends WP_REST_Controller {
    private static $instance = null;
    private $tiers = [
        'basic'     => ['limit' => 250,  'node' => 'H100 x1'],
        'essential' => ['limit' => 600,  'node' => 'H100 x2'],
        'premium'   => ['limit' => 1500, 'node' => 'H200 x1'],
    ];

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_shortcode('vortex_tier_basic_api',     [$this, 'render_basic_shortcode']);
        add_shortcode('vortex_tier_essential_api', [$this, 'render_essential_shortcode']);
        add_shortcode('vortex_tier_premium_api',   [$this, 'render_premium_shortcode']);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_routes() {
        foreach ($this->tiers as $tier => $config) {
            register_rest_route('vortex/v3/tier', "/$tier/generate", [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'handle_generation'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => $this->get_generate_args(),
            ]);
            register_rest_route('vortex/v3/tier', "/$tier/status", [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'handle_status'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [ 'tier' => ['required'=>true] ],
            ]);
        }
    }

    private function get_generate_args() {
        return [
            'api_key' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'query'   => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'style'   => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'quality' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        ];
    }

    public function permission_check() {
        return isset($_SERVER['HTTP_X_WP_NONCE'])
            && wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'wp_rest');
    }

    public function handle_generation(\WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $route   = $request->get_route();
        preg_match('/\/tier\/([^\/]+)\/generate/', $route, $matches);
        $tier    = $matches[1] ?? '';

        if (!isset($this->tiers[$tier])) {
            return new \WP_Error('invalid_tier', 'Invalid subscription tier', ['status'=>400]);
        }
        $config = $this->tiers[$tier];

        // Rate limiting
        $month = date('Y-m');
        $key = "vortex_tier_usage:{$user_id}:{$tier}:{$month}";
        $redis = $this->initialize_redis();
        $used  = $redis ? $redis->incr($key) : $this->fallback_increment($user_id, $tier, $month);
        if ($used == 1 && $redis) {
            $redis->expire($key, 60*60*24*30);
        }
        if ($used > $config['limit']) {
            return new \WP_Error('rate_limit_exceeded', 'Monthly limit reached', ['status'=>429]);
        }

        // API Key management
        $api_key = $this->get_or_create_api_key($user_id, $tier);

        // Orchestration
        $response = Orchestrator::get_instance()->executeEnhancedOrchestration(
            'generate',
            array_merge($request->get_params(), [
                'node'    => $config['node'],
                'api_key' => $api_key,
            ]),
            $user_id
        );

        return rest_ensure_response($response);
    }

    public function handle_status(\WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $route   = $request->get_route();
        preg_match('/\/tier\/([^\/]+)\/status/', $route, $matches);
        $tier    = $matches[1] ?? '';

        if (!isset($this->tiers[$tier])) {
            return new \WP_Error('invalid_tier', 'Invalid subscription tier', ['status'=>400]);
        }
        $config = $this->tiers[$tier];

        $month = date('Y-m');
        $key   = "vortex_tier_usage:{$user_id}:{$tier}:{$month}";
        $redis = $this->initialize_redis();
        $used  = $redis ? $redis->get($key) : $this->get_db_usage($user_id, $tier, $month);

        return rest_ensure_response([
            'used'  => intval($used),
            'limit' => $config['limit'],
            'node'  => $config['node'],
        ]);
    }

    private function initialize_redis() {
        static $client = null;
        if ($client === null) {
            try {
                $client = new \Redis();
                $client->connect('127.0.0.1', 6379, 2);
                $client->ping();
            } catch (\Exception $e) {
                error_log('[VortexAI Tier API] Redis init failed: ' . $e->getMessage());
                $client = null;
            }
        }
        return $client;
    }

    private function fallback_increment($user_id, $tier, $month) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_tier_usage_log';
        
        // Try to increment existing record
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table SET used = used + 1, updated_at = NOW() WHERE user_id = %d AND tier = %s AND month = %s",
            $user_id, $tier, $month
        ));
        
        if ($result === 0) {
            // No existing record, create new one
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'tier' => $tier,
                'month' => $month,
                'used' => 1,
                'updated_at' => current_time('mysql')
            ]);
            return 1;
        }
        
        // Get current usage count
        return $wpdb->get_var($wpdb->prepare(
            "SELECT used FROM $table WHERE user_id = %d AND tier = %s AND month = %s",
            $user_id, $tier, $month
        ));
    }

    private function get_db_usage($user_id, $tier, $month) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_tier_usage_log';
        
        $used = $wpdb->get_var($wpdb->prepare(
            "SELECT used FROM $table WHERE user_id = %d AND tier = %s AND month = %s",
            $user_id, $tier, $month
        ));
        
        return $used ? intval($used) : 0;
    }

    private function get_or_create_api_key($user_id, $tier) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_tier_api_keys';
        $row = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key FROM $table WHERE user_id = %d AND tier = %s AND status = 'active'",
            $user_id, $tier
        ));
        if ($row) {
            return \VortexAIEngine\Security::decrypt_api_key($row);
        }

        $api_key   = 'vortex_' . $tier . '_' . wp_generate_password(16, false);
        $encrypted = \VortexAIEngine\Security::encrypt_api_key($api_key);
        $wpdb->insert($table, [
            'user_id'    => $user_id,
            'tier'       => $tier,
            'api_key'    => $encrypted,
            'status'     => 'active',
            'created_at' => current_time('mysql'),
        ]);
        return $api_key;
    }

    public function render_basic_shortcode($atts) {
        return $this->render_tier_interface('basic', $atts);
    }
    public function render_essential_shortcode($atts) {
        return $this->render_tier_interface('essential', $atts);
    }
    public function render_premium_shortcode($atts) {
        return $this->render_tier_interface('premium', $atts);
    }

    private function render_tier_interface($tier, $atts) {
        wp_enqueue_script('tier-interface-js');
        wp_enqueue_style('tier-interface-css');
        $defaults = ['height' => '600px', 'show_usage' => true, 'show_api_key' => false];
        $args     = shortcode_atts($defaults, $atts, "vortex_tier_{$tier}_api");
        ob_start();
        ?>
        <div class="vortex-tier-interface" data-tier="<?php echo esc_attr($tier); ?>" style="height:<?php echo esc_attr($args['height']); ?>;">
            <!-- Tier UI elements loaded via tier-interface-js -->
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the Tier API system
Tier_API::get_instance();
?> 