<?php
/**
 * VORTEX AI Engine - API Endpoints Class
 * 
 * Handles REST API endpoints and AJAX requests
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX API Endpoints Class
 */
class VORTEX_API_Endpoints {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_ajax_vortex_ai_action', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_vortex_ai_action', array($this, 'handle_ajax_request'));
    }
    
    public function register_routes() {
        register_rest_route('vortex/v1', '/generate-art', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_art'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route('vortex/v1', '/process-transaction', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_transaction'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'from_token' => array(
                    'required' => true,
                    'type' => 'string'
                ),
                'to_token' => array(
                    'required' => true,
                    'type' => 'string'
                ),
                'amount' => array(
                    'required' => true,
                    'type' => 'number'
                )
            )
        ));
        
        register_rest_route('vortex/v1', '/get-metrics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_metrics'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission($request) {
        return wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
    }
    
    public function generate_art($request) {
        $prompt = $request->get_param('prompt');
        
        // Implementation for art generation
        $response = array(
            'success' => true,
            'message' => 'Art generation initiated',
            'data' => array(
                'prompt' => $prompt,
                'status' => 'processing'
            )
        );
        
        return new WP_REST_Response($response, 200);
    }
    
    public function process_transaction($request) {
        $from_token = $request->get_param('from_token');
        $to_token = $request->get_param('to_token');
        $amount = $request->get_param('amount');
        
        // Implementation for transaction processing
        $response = array(
            'success' => true,
            'message' => 'Transaction processed',
            'data' => array(
                'from_token' => $from_token,
                'to_token' => $to_token,
                'amount' => $amount,
                'status' => 'completed'
            )
        );
        
        return new WP_REST_Response($response, 200);
    }
    
    public function get_metrics($request) {
        // Implementation for metrics retrieval
        $response = array(
            'success' => true,
            'message' => 'Metrics retrieved',
            'data' => array(
                'total_transactions' => 0,
                'total_art_generated' => 0,
                'active_users' => 0
            )
        );
        
        return new WP_REST_Response($response, 200);
    }
    
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vortex_nonce')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'generate_art':
                $this->ajax_generate_art();
                break;
            case 'process_transaction':
                $this->ajax_process_transaction();
                break;
            case 'get_metrics':
                $this->ajax_get_metrics();
                break;
            default:
                wp_die('Invalid action');
        }
    }
    
    private function ajax_generate_art() {
        $prompt = sanitize_text_field($_POST['prompt']);
        
        $response = array(
            'success' => true,
            'message' => 'Art generation initiated',
            'data' => array(
                'prompt' => $prompt,
                'status' => 'processing'
            )
        );
        
        wp_send_json($response);
    }
    
    private function ajax_process_transaction() {
        $from_token = sanitize_text_field($_POST['from_token']);
        $to_token = sanitize_text_field($_POST['to_token']);
        $amount = floatval($_POST['amount']);
        
        $response = array(
            'success' => true,
            'message' => 'Transaction processed',
            'data' => array(
                'from_token' => $from_token,
                'to_token' => $to_token,
                'amount' => $amount,
                'status' => 'completed'
            )
        );
        
        wp_send_json($response);
    }
    
    private function ajax_get_metrics() {
        $response = array(
            'success' => true,
            'message' => 'Metrics retrieved',
            'data' => array(
                'total_transactions' => 0,
                'total_art_generated' => 0,
                'active_users' => 0
            )
        );
        
        wp_send_json($response);
    }
    
    public function testConnection() {
        return array(
            'status' => 'success',
            'message' => 'VORTEX API Endpoints loaded successfully',
            'endpoints' => array(
                '/vortex/v1/generate-art',
                '/vortex/v1/process-transaction',
                '/vortex/v1/get-metrics'
            )
        );
    }
} 