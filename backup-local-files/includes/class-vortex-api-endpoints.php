<?php
/**
 * VORTEX API Endpoints
 * Handles REST API endpoints for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_APIEndpoints {
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Health check endpoint
        register_rest_route('vortex/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check'],
            'permission_callback' => '__return_true'
        ]);
        
        // Generate endpoint
        register_rest_route('vortex/v1', '/generate', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_content'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'prompt' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field'
                ]
            ]
        ]);
    }
    
    /**
     * Health check endpoint
     */
    public function health_check() {
        return [
            'status' => 'ok',
            'version' => VORTEX_AI_ENGINE_VERSION,
            'database' => vortex_ai_engine_check_database_connection() ? 'connected' : 'disconnected',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Generate content endpoint
     */
    public function generate_content($request) {
        $prompt = $request->get_param('prompt');
        
        if (empty($prompt)) {
            return new WP_Error('missing_prompt', 'Prompt is required', ['status' => 400]);
        }
        
        try {
            // Simple response for now to prevent crashes
            $response = "AI response to: " . substr($prompt, 0, 100) . "...";
            
            return [
                'status' => 'success',
                'response' => $response,
                'timestamp' => current_time('mysql')
            ];
        } catch (Exception $e) {
            error_log('[VortexAI API] Generation error: ' . $e->getMessage());
            return new WP_Error('generation_failed', 'Failed to generate content', ['status' => 500]);
        }
    }
    
    /**
     * Check permissions
     */
    public function check_permissions() {
        return current_user_can('read');
    }
}

// Initialize the API endpoints
new VortexAIEngine_APIEndpoints(); 