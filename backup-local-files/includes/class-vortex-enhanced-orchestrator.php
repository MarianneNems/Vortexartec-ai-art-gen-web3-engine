<?php
/**
 * Enhanced Orchestrator for VORTEX AI Engine
 * Manages the 7-step AI processing pipeline
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VortexAIEngine_EnhancedOrchestrator')) {
class VortexAIEngine_EnhancedOrchestrator {
    
    private static $instance = null;
    private $database;
    private $tier_api = null;
    private $cost_optimizer = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Safe initialization - don't crash if classes don't exist
        try {
            // Use WordPress database directly
            global $wpdb;
            $this->database = $wpdb;
            
            // Only initialize if classes exist
            if (class_exists('VortexAIEngine\Tier_API')) {
                $this->tier_api = VortexAIEngine\Tier_API::get_instance();
            }
            
            if (class_exists('VortexAIEngine_CostOptimizer')) {
                $this->cost_optimizer = VortexAIEngine_CostOptimizer::get_instance();
            }
            
        } catch (Exception $e) {
            error_log('[VortexAI Plugin] Enhanced Orchestrator initialization error: ' . $e->getMessage());
        }
        
        // Hook into WordPress
        add_filter('vortex_orchestration_params', [$this, 'enhance_orchestration_params'], 10, 3);
        add_action('vortex_before_orchestration', [$this, 'pre_orchestration_checks'], 10, 2);
        add_action('vortex_after_orchestration', [$this, 'post_orchestration_processing'], 10, 3);
        
        // Add shortcode support
        add_shortcode('huraii_generate', [$this, 'handle_generate_shortcode']);
    }
    
    /**
     * Handle the main generation shortcode
     */
    public function handle_generate_shortcode($atts) {
        $atts = shortcode_atts([
            'prompt' => '',
            'model' => 'gpt-4',
            'max_tokens' => 1000,
            'temperature' => 0.7
        ], $atts);
        
        if (empty($atts['prompt'])) {
            return '<div class="vortex-error">Error: No prompt provided</div>';
        }
        
        try {
            // Use basic generation for now
            $result = $this->generate_response($atts['prompt'], $atts);
            return '<div class="vortex-response">' . esc_html($result) . '</div>';
        } catch (Exception $e) {
            error_log('[VortexAI] Generation error: ' . $e->getMessage());
            return '<div class="vortex-error">Error generating response</div>';
        }
    }
    
    /**
     * Generate response using available providers
     */
    private function generate_response($prompt, $options = []) {
        // For now, return a simple response to prevent crashes
        // This will be enhanced once the provider system is fully working
        return "AI response to: " . substr($prompt, 0, 100) . "...";
    }
    
    /**
     * Enhance orchestration parameters
     */
    public function enhance_orchestration_params($params, $request_id, $context) {
        // Add tier-based enhancements if tier API is available
        if ($this->tier_api) {
            $user_id = get_current_user_id();
            $tier_info = $this->tier_api->get_user_tier($user_id);
            $params['tier_info'] = $tier_info;
        }
        
        // Add cost optimization if available
        if ($this->cost_optimizer) {
            $params['cost_optimization'] = $this->cost_optimizer->get_optimization_params();
        }
        
        return $params;
    }
    
    /**
     * Pre-orchestration checks
     */
    public function pre_orchestration_checks($request_id, $params) {
        // Validate user permissions
        if (!current_user_can('read')) {
            throw new Exception('Insufficient permissions');
        }
        
        // Check database connectivity
        if (!vortex_ai_engine_check_database_connection()) {
            throw new Exception('Database connection unavailable');
        }
        
        // Log the request
        error_log("[VortexAI] Starting orchestration for request: {$request_id}");
    }
    
    /**
     * Post-orchestration processing
     */
    public function post_orchestration_processing($request_id, $params, $result) {
        // Log completion
        error_log("[VortexAI] Completed orchestration for request: {$request_id}");
        
        // Update usage stats if tier API is available
        if ($this->tier_api) {
            $user_id = get_current_user_id();
            $this->tier_api->update_usage($user_id, 1);
        }
    }
    
    /**
     * Health check endpoint
     */
    public function health_check() {
        return [
            'status' => 'ok',
            'database' => vortex_ai_engine_check_database_connection() ? 'connected' : 'disconnected',
            'tier_api' => $this->tier_api ? 'available' : 'unavailable',
            'cost_optimizer' => $this->cost_optimizer ? 'available' : 'unavailable'
        ];
    }
}
} 