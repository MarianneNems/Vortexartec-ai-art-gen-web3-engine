<?php
/**
 * Vortex AI Engine - Feedback Controller
 * 
 * Centralizes all feedback collection and publishes to SQS for real-time processing
 * Enables continuous learning and self-improvement loops
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Feedback_Controller {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * AWS SQS client
     */
    private $sqs_client;
    
    /**
     * Feedback queue URL
     */
    private $feedback_queue_url;
    
    /**
     * Metrics queue URL
     */
    private $metrics_queue_url;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_aws_client();
        $this->init_hooks();
    }
    
    /**
     * Initialize AWS SQS client
     */
    private function init_aws_client() {
        try {
            $this->sqs_client = new Aws\Sqs\SqsClient([
                'version' => 'latest',
                'region'  => VORTEX_AWS_REGION,
                'credentials' => [
                    'key'    => VORTEX_AWS_ACCESS_KEY,
                    'secret' => VORTEX_AWS_SECRET_KEY,
                ]
            ]);
            
            $this->feedback_queue_url = VORTEX_FEEDBACK_QUEUE_URL;
            $this->metrics_queue_url = VORTEX_METRICS_QUEUE_URL;
            
        } catch (Exception $e) {
            error_log("Vortex Feedback Controller: AWS SQS initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // User feedback hooks
        add_action('wp_ajax_vortex_user_feedback', [$this, 'handle_user_feedback']);
        add_action('wp_ajax_nopriv_vortex_user_feedback', [$this, 'handle_user_feedback']);
        
        // Performance metrics hooks
        add_action('vortex_agent_response_complete', [$this, 'record_agent_metrics']);
        add_action('vortex_model_swap_decision', [$this, 'record_model_swap']);
        add_action('vortex_error_occurred', [$this, 'record_error']);
        
        // Audit completion hooks
        add_action('vortex_audit_completed', [$this, 'record_audit_results']);
        
        // Model performance hooks
        add_action('vortex_model_performance_update', [$this, 'record_model_performance']);
    }
    
    /**
     * Handle user feedback (thumbs up/down, ratings, comments)
     */
    public function handle_user_feedback() {
        check_ajax_referer('vortex_feedback_nonce', 'nonce');
        
        $feedback_data = [
            'type' => 'user_feedback',
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
            'user_tier' => $this->get_user_tier(),
            'agent_name' => sanitize_text_field($_POST['agent_name'] ?? ''),
            'prompt' => sanitize_textarea_field($_POST['prompt'] ?? ''),
            'response_id' => sanitize_text_field($_POST['response_id'] ?? ''),
            'rating' => intval($_POST['rating'] ?? 0), // 1-5 scale
            'thumbs_up' => boolval($_POST['thumbs_up'] ?? false),
            'thumbs_down' => boolval($_POST['thumbs_down'] ?? false),
            'comment' => sanitize_textarea_field($_POST['comment'] ?? ''),
            'session_id' => sanitize_text_field($_POST['session_id'] ?? ''),
            'page_url' => sanitize_url($_POST['page_url'] ?? ''),
            'user_agent' => sanitize_text_field($_POST['user_agent'] ?? ''),
        ];
        
        $this->publish_to_sqs($feedback_data, 'feedback');
        
        wp_send_json_success(['message' => 'Feedback recorded successfully']);
    }
    
    /**
     * Record agent performance metrics
     */
    public function record_agent_metrics($metrics) {
        $metrics_data = [
            'type' => 'agent_metrics',
            'timestamp' => current_time('timestamp'),
            'agent_name' => $metrics['agent_name'],
            'prompt_length' => $metrics['prompt_length'] ?? 0,
            'response_length' => $metrics['response_length'] ?? 0,
            'processing_time_ms' => $metrics['processing_time_ms'] ?? 0,
            'tokens_used' => $metrics['tokens_used'] ?? 0,
            'model_used' => $metrics['model_used'] ?? '',
            'success' => $metrics['success'] ?? true,
            'error_message' => $metrics['error_message'] ?? '',
            'user_tier' => $this->get_user_tier(),
            'session_id' => $metrics['session_id'] ?? '',
        ];
        
        $this->publish_to_sqs($metrics_data, 'metrics');
    }
    
    /**
     * Record model swap decisions
     */
    public function record_model_swap($swap_data) {
        $swap_metrics = [
            'type' => 'model_swap',
            'timestamp' => current_time('timestamp'),
            'from_model' => $swap_data['from_model'],
            'to_model' => $swap_data['to_model'],
            'reason' => $swap_data['reason'],
            'agent_name' => $swap_data['agent_name'],
            'performance_delta' => $swap_data['performance_delta'] ?? 0,
            'user_tier' => $this->get_user_tier(),
        ];
        
        $this->publish_to_sqs($swap_metrics, 'metrics');
    }
    
    /**
     * Record errors for analysis
     */
    public function record_error($error_data) {
        $error_metrics = [
            'type' => 'error',
            'timestamp' => current_time('timestamp'),
            'error_type' => $error_data['type'],
            'error_message' => $error_data['message'],
            'stack_trace' => $error_data['stack_trace'] ?? '',
            'agent_name' => $error_data['agent_name'] ?? '',
            'user_id' => get_current_user_id(),
            'user_tier' => $this->get_user_tier(),
            'page_url' => $error_data['page_url'] ?? '',
            'severity' => $error_data['severity'] ?? 'medium',
        ];
        
        $this->publish_to_sqs($error_metrics, 'metrics');
    }
    
    /**
     * Record audit results
     */
    public function record_audit_results($audit_data) {
        $audit_metrics = [
            'type' => 'audit_results',
            'timestamp' => current_time('timestamp'),
            'audit_type' => $audit_data['type'],
            'total_checks' => $audit_data['total_checks'],
            'passed_checks' => $audit_data['passed_checks'],
            'warnings' => $audit_data['warnings'],
            'errors' => $audit_data['errors'],
            'files_checked' => $audit_data['files_checked'],
            'audit_duration_ms' => $audit_data['duration_ms'],
            'report_url' => $audit_data['report_url'] ?? '',
        ];
        
        $this->publish_to_sqs($audit_metrics, 'metrics');
    }
    
    /**
     * Record model performance updates
     */
    public function record_model_performance($performance_data) {
        $perf_metrics = [
            'type' => 'model_performance',
            'timestamp' => current_time('timestamp'),
            'model_name' => $performance_data['model_name'],
            'agent_name' => $performance_data['agent_name'],
            'accuracy' => $performance_data['accuracy'],
            'latency_avg_ms' => $performance_data['latency_avg_ms'],
            'throughput_rps' => $performance_data['throughput_rps'],
            'error_rate' => $performance_data['error_rate'],
            'user_satisfaction' => $performance_data['user_satisfaction'],
            'training_data_size' => $performance_data['training_data_size'],
        ];
        
        $this->publish_to_sqs($perf_metrics, 'metrics');
    }
    
    /**
     * Publish data to SQS queue
     */
    private function publish_to_sqs($data, $queue_type = 'feedback') {
        if (!$this->sqs_client) {
            error_log("Vortex Feedback Controller: SQS client not initialized");
            return false;
        }
        
        try {
            $queue_url = ($queue_type === 'feedback') ? $this->feedback_queue_url : $this->metrics_queue_url;
            
            $message_body = json_encode($data);
            
            $result = $this->sqs_client->sendMessage([
                'QueueUrl' => $queue_url,
                'MessageBody' => $message_body,
                'MessageAttributes' => [
                    'Type' => [
                        'DataType' => 'String',
                        'StringValue' => $data['type']
                    ],
                    'Timestamp' => [
                        'DataType' => 'Number',
                        'StringValue' => (string)$data['timestamp']
                    ],
                    'AgentName' => [
                        'DataType' => 'String',
                        'StringValue' => $data['agent_name'] ?? 'unknown'
                    ]
                ]
            ]);
            
            return $result['MessageId'];
            
        } catch (Exception $e) {
            error_log("Vortex Feedback Controller: Failed to publish to SQS: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user tier for analytics
     */
    private function get_user_tier() {
        if (!is_user_logged_in()) {
            return 'anonymous';
        }
        
        $user_id = get_current_user_id();
        $subscription = get_user_meta($user_id, 'vortex_subscription_tier', true);
        
        return $subscription ?: 'free';
    }
    
    /**
     * Get feedback statistics
     */
    public function get_feedback_stats($timeframe = '24h') {
        // This would query DynamoDB for aggregated stats
        // For now, return placeholder data
        return [
            'total_feedback' => 0,
            'positive_ratio' => 0.0,
            'avg_rating' => 0.0,
            'top_agents' => [],
            'common_issues' => [],
        ];
    }
    
    /**
     * Trigger manual feedback processing
     */
    public function process_pending_feedback() {
        // This would trigger the Lambda function to process pending feedback
        do_action('vortex_process_feedback_batch');
    }
}

// Initialize the feedback controller
Vortex_Feedback_Controller::get_instance(); 