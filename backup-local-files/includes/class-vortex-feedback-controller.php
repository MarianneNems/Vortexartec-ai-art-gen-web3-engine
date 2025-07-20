<?php
/**
 * VORTEX AI Engine - Feedback Controller
 * Handles feedback submission and integration with orchestrator
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_FeedbackController {
    public static function submit_feedback(WP_REST_Request $req) {
        // Validate authentication
        if (!is_user_logged_in()) {
            return new WP_Error('authentication_required', 'User must be logged in', ['status' => 401]);
        }
        
        $user = get_current_user_id();
        $data = $req->get_json_params();
        
        // Validate request data
        if (!is_array($data)) {
            return new WP_Error('invalid_request', 'Invalid request format', ['status' => 400]);
        }
        
        // Sanitize and validate inputs
        $action = sanitize_text_field($data['action'] ?? '');
        $request_id = sanitize_text_field($data['request_id'] ?? '');
        $liked = filter_var($data['liked'] ?? 0, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        
        // Validate required fields
        if (empty($action) || empty($request_id)) {
            return new WP_Error('invalid_data', 'Missing required fields', ['status' => 400]);
        }
        
        // Validate action against allowed values
        $allowed_actions = ['generate', 'describe', 'upscale', 'enhance', 'export', 'share', 'upload', 'save', 'delete', 'edit', 'regenerate', 'vary', 'download'];
        if (!in_array($action, $allowed_actions)) {
            return new WP_Error('invalid_action', 'Invalid action specified', ['status' => 400]);
        }
        
        // Validate request_id format (should be alphanumeric with dashes/underscores)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $request_id)) {
            return new WP_Error('invalid_request_id', 'Invalid request ID format', ['status' => 400]);
        }
        
        // Rate limiting
        $rate_limit_key = 'vortex_feedback_rate_limit_' . $user;
        $current_requests = get_transient($rate_limit_key) ?: 0;
        
        if ($current_requests >= 30) { // 30 feedback submissions per minute
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded', ['status' => 429]);
        }
        
        // Update rate limit counter
        set_transient($rate_limit_key, ($current_requests + 1), 60);
        
        // Insert into database
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_feedback';
        $inserted = $wpdb->insert($table, [
            'user_id' => $user,
            'action' => $action,
            'request_id' => $request_id,
            'liked' => $liked
        ]);
        
        if (!$inserted) {
            return new WP_Error('db_error', 'Failed to save feedback', ['status' => 500]);
        }
        
        // Publish to orchestrator for reinforcement learning
        if (class_exists('VortexAIEngine_EnhancedOrchestrator')) {
            try {
                $orchestrator = VortexAIEngine_EnhancedOrchestrator::getInstance();
                if (method_exists($orchestrator, 'stepX_reinforcement_learning')) {
                    $orchestrator->stepX_reinforcement_learning($action, $request_id, $user, $liked);
                }
            } catch (Exception $e) {
                error_log('[VortexAI Feedback] Error in orchestrator: ' . $e->getMessage());
            }
        }
        
        return rest_ensure_response(['status' => 'ok']);
    }
} 