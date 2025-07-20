<?php
/**
 * VORTEX AI Engine - Tier Manager
 * Manages subscription tiers and API access levels
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VortexAIEngine_TierManager')) {
class VortexAIEngine_TierManager {
    private static $instance = null;
    
    // Define available tiers
    private $tiers = [
        'free' => [
            'name' => 'Free',
            'api_calls_per_month' => 10,
            'features' => ['basic_generation', 'community_support']
        ],
        'starter' => [
            'name' => 'Starter',
            'api_calls_per_month' => 100,
            'features' => ['basic_generation', 'priority_support', 'custom_prompts']
        ],
        'professional' => [
            'name' => 'Professional',
            'api_calls_per_month' => 1000,
            'features' => ['advanced_generation', 'priority_support', 'custom_prompts', 'bulk_processing']
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'api_calls_per_month' => -1, // Unlimited
            'features' => ['all_features', 'dedicated_support', 'custom_integration']
        ]
    ];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'init_tier_system']);
    }
    
    public function init_tier_system() {
        // Initialize tier system
        $this->maybe_create_tier_tables();
    }
    
    private function maybe_create_tier_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_tiers';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            tier varchar(32) NOT NULL DEFAULT 'free',
            api_calls_used int(11) DEFAULT 0,
            api_calls_reset_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY tier (tier)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function get_user_tier($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_tiers';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if (!$result) {
            // Create default free tier for user
            $this->set_user_tier($user_id, 'free');
            return $this->get_user_tier($user_id);
        }
        
        return $result;
    }
    
    public function set_user_tier($user_id, $tier) {
        global $wpdb;
        
        if (!isset($this->tiers[$tier])) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'vortex_user_tiers';
        
        $result = $wpdb->replace(
            $table_name,
            [
                'user_id' => $user_id,
                'tier' => $tier,
                'api_calls_used' => 0,
                'api_calls_reset_date' => date('Y-m-d')
            ],
            ['%d', '%s', '%d', '%s']
        );
        
        return $result !== false;
    }
    
    public function can_make_api_call($user_id) {
        $user_tier = $this->get_user_tier($user_id);
        $tier_config = $this->tiers[$user_tier->tier];
        
        // Enterprise tier has unlimited calls
        if ($tier_config['api_calls_per_month'] === -1) {
            return true;
        }
        
        // Check if we need to reset the counter
        $this->maybe_reset_api_counter($user_id);
        
        return $user_tier->api_calls_used < $tier_config['api_calls_per_month'];
    }
    
    public function increment_api_calls($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_tiers';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET api_calls_used = api_calls_used + 1 WHERE user_id = %d",
            $user_id
        ));
        
        return $result !== false;
    }
    
    private function maybe_reset_api_counter($user_id) {
        global $wpdb;
        
        $user_tier = $this->get_user_tier($user_id);
        $reset_date = new DateTime($user_tier->api_calls_reset_date);
        $now = new DateTime();
        
        // Reset if it's been more than a month
        if ($reset_date->diff($now)->days > 30) {
            $table_name = $wpdb->prefix . 'vortex_user_tiers';
            
            $wpdb->update(
                $table_name,
                [
                    'api_calls_used' => 0,
                    'api_calls_reset_date' => $now->format('Y-m-d')
                ],
                ['user_id' => $user_id],
                ['%d', '%s'],
                ['%d']
            );
        }
    }
    
    public function get_available_tiers() {
        return $this->tiers;
    }
    
    public function get_user_remaining_calls($user_id) {
        $user_tier = $this->get_user_tier($user_id);
        $tier_config = $this->tiers[$user_tier->tier];
        
        if ($tier_config['api_calls_per_month'] === -1) {
            return 'unlimited';
        }
        
        $this->maybe_reset_api_counter($user_id);
        $user_tier = $this->get_user_tier($user_id); // Refresh data
        
        return max(0, $tier_config['api_calls_per_month'] - $user_tier->api_calls_used);
    }
}

// Initialize the tier manager
VortexAIEngine_TierManager::getInstance();
} 