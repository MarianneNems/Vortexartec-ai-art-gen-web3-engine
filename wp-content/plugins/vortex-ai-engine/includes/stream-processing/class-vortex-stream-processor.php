<?php
/**
 * Vortex AI Engine - Stream Processor
 * 
 * Continuously processes feedback and metrics from SQS queues
 * Aggregates data in DynamoDB and triggers alerts for performance issues
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Stream_Processor {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * AWS clients
     */
    private $sqs_client;
    private $dynamodb_client;
    private $sns_client;
    
    /**
     * Queue URLs
     */
    private $feedback_queue_url;
    private $metrics_queue_url;
    
    /**
     * Processing configuration
     */
    private $batch_size = 10;
    private $processing_interval = 30; // seconds
    
    /**
     * Alert thresholds
     */
    private $alert_thresholds = [
        'error_rate' => 0.05, // 5%
        'latency_spike' => 2000, // 2 seconds
        'satisfaction_drop' => 0.1, // 10% drop
        'model_swap_frequency' => 0.2, // 20% of requests
    ];
    
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
        $this->init_aws_clients();
        $this->init_hooks();
    }
    
    /**
     * Initialize AWS clients
     */
    private function init_aws_clients() {
        try {
            $config = [
                'version' => 'latest',
                'region'  => VORTEX_AWS_REGION,
                'credentials' => [
                    'key'    => VORTEX_AWS_ACCESS_KEY,
                    'secret' => VORTEX_AWS_SECRET_KEY,
                ]
            ];
            
            $this->sqs_client = new Aws\Sqs\SqsClient($config);
            $this->dynamodb_client = new Aws\DynamoDB\DynamoDBClient($config);
            $this->sns_client = new Aws\Sns\SnsClient($config);
            
            $this->feedback_queue_url = VORTEX_FEEDBACK_QUEUE_URL;
            $this->metrics_queue_url = VORTEX_METRICS_QUEUE_URL;
            
        } catch (Exception $e) {
            error_log("Vortex Stream Processor: AWS client initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule processing
        add_action('vortex_process_feedback_batch', [$this, 'process_feedback_batch']);
        add_action('vortex_process_metrics_batch', [$this, 'process_metrics_batch']);
        
        // Setup cron jobs
        if (!wp_next_scheduled('vortex_stream_processor_cron')) {
            wp_schedule_event(time(), 'every_30_seconds', 'vortex_stream_processor_cron');
        }
        add_action('vortex_stream_processor_cron', [$this, 'run_processing_cycle']);
        
        // Alert hooks
        add_action('vortex_performance_alert', [$this, 'send_performance_alert']);
        add_action('vortex_error_rate_alert', [$this, 'send_error_alert']);
    }
    
    /**
     * Main processing cycle
     */
    public function run_processing_cycle() {
        $this->process_feedback_batch();
        $this->process_metrics_batch();
        $this->check_alert_conditions();
        $this->aggregate_hourly_metrics();
    }
    
    /**
     * Process feedback batch from SQS
     */
    public function process_feedback_batch() {
        if (!$this->sqs_client) {
            return;
        }
        
        try {
            $messages = $this->receive_messages($this->feedback_queue_url, $this->batch_size);
            
            foreach ($messages as $message) {
                $data = json_decode($message['Body'], true);
                
                if ($data) {
                    $this->store_feedback_data($data);
                    $this->update_user_satisfaction_metrics($data);
                    $this->track_agent_performance($data);
                }
                
                // Delete processed message
                $this->delete_message($this->feedback_queue_url, $message['ReceiptHandle']);
            }
            
        } catch (Exception $e) {
            error_log("Vortex Stream Processor: Feedback processing failed: " . $e->getMessage());
        }
    }
    
    /**
     * Process metrics batch from SQS
     */
    public function process_metrics_batch() {
        if (!$this->sqs_client) {
            return;
        }
        
        try {
            $messages = $this->receive_messages($this->metrics_queue_url, $this->batch_size);
            
            foreach ($messages as $message) {
                $data = json_decode($message['Body'], true);
                
                if ($data) {
                    switch ($data['type']) {
                        case 'agent_metrics':
                            $this->store_agent_metrics($data);
                            break;
                        case 'model_swap':
                            $this->store_model_swap_data($data);
                            break;
                        case 'error':
                            $this->store_error_data($data);
                            break;
                        case 'audit_results':
                            $this->store_audit_results($data);
                            break;
                        case 'model_performance':
                            $this->store_model_performance($data);
                            break;
                    }
                }
                
                // Delete processed message
                $this->delete_message($this->metrics_queue_url, $message['ReceiptHandle']);
            }
            
        } catch (Exception $e) {
            error_log("Vortex Stream Processor: Metrics processing failed: " . $e->getMessage());
        }
    }
    
    /**
     * Store feedback data in DynamoDB
     */
    private function store_feedback_data($data) {
        $item = [
            'PK' => ['S' => 'FEEDBACK#' . $data['timestamp']],
            'SK' => ['S' => 'USER#' . $data['user_id']],
            'Type' => ['S' => $data['type']],
            'AgentName' => ['S' => $data['agent_name']],
            'Rating' => ['N' => (string)$data['rating']],
            'ThumbsUp' => ['BOOL' => $data['thumbs_up']],
            'ThumbsDown' => ['BOOL' => $data['thumbs_down']],
            'UserTier' => ['S' => $data['user_tier']],
            'Timestamp' => ['N' => (string)$data['timestamp']],
            'SessionId' => ['S' => $data['session_id']],
        ];
        
        if (!empty($data['comment'])) {
            $item['Comment'] = ['S' => $data['comment']];
        }
        
        $this->dynamodb_client->putItem([
            'TableName' => VORTEX_DYNAMODB_TABLE,
            'Item' => $item
        ]);
    }
    
    /**
     * Store agent metrics in DynamoDB
     */
    private function store_agent_metrics($data) {
        $item = [
            'PK' => ['S' => 'METRICS#' . $data['timestamp']],
            'SK' => ['S' => 'AGENT#' . $data['agent_name']],
            'Type' => ['S' => $data['type']],
            'ProcessingTimeMs' => ['N' => (string)$data['processing_time_ms']],
            'TokensUsed' => ['N' => (string)$data['tokens_used']],
            'ModelUsed' => ['S' => $data['model_used']],
            'Success' => ['BOOL' => $data['success']],
            'UserTier' => ['S' => $data['user_tier']],
            'Timestamp' => ['N' => (string)$data['timestamp']],
        ];
        
        if (!empty($data['error_message'])) {
            $item['ErrorMessage'] = ['S' => $data['error_message']];
        }
        
        $this->dynamodb_client->putItem([
            'TableName' => VORTEX_DYNAMODB_TABLE,
            'Item' => $item
        ]);
    }
    
    /**
     * Store error data in DynamoDB
     */
    private function store_error_data($data) {
        $item = [
            'PK' => ['S' => 'ERROR#' . $data['timestamp']],
            'SK' => ['S' => 'TYPE#' . $data['error_type']],
            'Type' => ['S' => $data['type']],
            'ErrorMessage' => ['S' => $data['error_message']],
            'AgentName' => ['S' => $data['agent_name']],
            'Severity' => ['S' => $data['severity']],
            'UserId' => ['N' => (string)$data['user_id']],
            'UserTier' => ['S' => $data['user_tier']],
            'Timestamp' => ['N' => (string)$data['timestamp']],
        ];
        
        if (!empty($data['stack_trace'])) {
            $item['StackTrace'] = ['S' => $data['stack_trace']];
        }
        
        $this->dynamodb_client->putItem([
            'TableName' => VORTEX_DYNAMODB_TABLE,
            'Item' => $item
        ]);
    }
    
    /**
     * Update user satisfaction metrics
     */
    private function update_user_satisfaction_metrics($data) {
        $satisfaction_score = 0;
        
        if ($data['thumbs_up']) {
            $satisfaction_score = 1;
        } elseif ($data['thumbs_down']) {
            $satisfaction_score = -1;
        } elseif ($data['rating'] > 0) {
            $satisfaction_score = ($data['rating'] - 3) / 2; // Convert 1-5 to -1 to 1
        }
        
        // Update hourly satisfaction metrics
        $hour_key = date('Y-m-d-H', $data['timestamp']);
        $this->update_hourly_metric('satisfaction', $hour_key, $satisfaction_score);
    }
    
    /**
     * Track agent performance
     */
    private function track_agent_performance($data) {
        $hour_key = date('Y-m-d-H', $data['timestamp']);
        $agent_key = $data['agent_name'];
        
        // Update agent-specific metrics
        $this->update_hourly_metric('agent_performance', $hour_key . '#' . $agent_key, 1);
    }
    
    /**
     * Update hourly metrics in DynamoDB
     */
    private function update_hourly_metric($metric_type, $key, $value) {
        try {
            $this->dynamodb_client->updateItem([
                'TableName' => VORTEX_DYNAMODB_TABLE,
                'Key' => [
                    'PK' => ['S' => 'HOURLY#' . $metric_type],
                    'SK' => ['S' => $key],
                ],
                'UpdateExpression' => 'SET #count = if_not_exists(#count, :zero) + :inc, #sum = if_not_exists(#sum, :zero) + :val, #avg = (#sum + :val) / (#count + :inc)',
                'ExpressionAttributeNames' => [
                    '#count' => 'Count',
                    '#sum' => 'Sum',
                    '#avg' => 'Average'
                ],
                'ExpressionAttributeValues' => [
                    ':inc' => ['N' => '1'],
                    ':val' => ['N' => (string)$value],
                    ':zero' => ['N' => '0']
                ]
            ]);
        } catch (Exception $e) {
            error_log("Vortex Stream Processor: Failed to update hourly metric: " . $e->getMessage());
        }
    }
    
    /**
     * Check alert conditions and trigger alerts
     */
    private function check_alert_conditions() {
        $current_hour = date('Y-m-d-H');
        
        // Check error rate
        $error_rate = $this->get_error_rate($current_hour);
        if ($error_rate > $this->alert_thresholds['error_rate']) {
            do_action('vortex_error_rate_alert', [
                'error_rate' => $error_rate,
                'threshold' => $this->alert_thresholds['error_rate'],
                'hour' => $current_hour
            ]);
        }
        
        // Check satisfaction drop
        $satisfaction = $this->get_satisfaction_score($current_hour);
        $previous_satisfaction = $this->get_satisfaction_score(date('Y-m-d-H', strtotime('-1 hour')));
        
        if ($previous_satisfaction > 0 && ($previous_satisfaction - $satisfaction) > $this->alert_thresholds['satisfaction_drop']) {
            do_action('vortex_satisfaction_alert', [
                'current_satisfaction' => $satisfaction,
                'previous_satisfaction' => $previous_satisfaction,
                'drop' => $previous_satisfaction - $satisfaction
            ]);
        }
    }
    
    /**
     * Send performance alert via SNS
     */
    public function send_performance_alert($alert_data) {
        $message = [
            'subject' => 'Vortex AI Engine Performance Alert',
            'message' => json_encode($alert_data),
            'timestamp' => current_time('timestamp'),
            'severity' => 'warning'
        ];
        
        $this->send_sns_notification($message);
    }
    
    /**
     * Send error alert via SNS
     */
    public function send_error_alert($alert_data) {
        $message = [
            'subject' => 'Vortex AI Engine Error Rate Alert',
            'message' => json_encode($alert_data),
            'timestamp' => current_time('timestamp'),
            'severity' => 'critical'
        ];
        
        $this->send_sns_notification($message);
    }
    
    /**
     * Send SNS notification
     */
    private function send_sns_notification($message) {
        try {
            $this->sns_client->publish([
                'TopicArn' => VORTEX_SNS_TOPIC_ARN,
                'Subject' => $message['subject'],
                'Message' => $message['message']
            ]);
        } catch (Exception $e) {
            error_log("Vortex Stream Processor: Failed to send SNS notification: " . $e->getMessage());
        }
    }
    
    /**
     * Receive messages from SQS queue
     */
    private function receive_messages($queue_url, $max_messages = 10) {
        try {
            $result = $this->sqs_client->receiveMessage([
                'QueueUrl' => $queue_url,
                'MaxNumberOfMessages' => $max_messages,
                'WaitTimeSeconds' => 5,
                'MessageAttributeNames' => ['All']
            ]);
            
            return $result['Messages'] ?? [];
            
        } catch (Exception $e) {
            error_log("Vortex Stream Processor: Failed to receive messages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete message from SQS queue
     */
    private function delete_message($queue_url, $receipt_handle) {
        try {
            $this->sqs_client->deleteMessage([
                'QueueUrl' => $queue_url,
                'ReceiptHandle' => $receipt_handle
            ]);
        } catch (Exception $e) {
            error_log("Vortex Stream Processor: Failed to delete message: " . $e->getMessage());
        }
    }
    
    /**
     * Get error rate for the specified hour
     */
    private function get_error_rate($hour_key) {
        // Query DynamoDB for error count vs total requests
        // This is a simplified implementation
        return 0.02; // Placeholder
    }
    
    /**
     * Get satisfaction score for the specified hour
     */
    private function get_satisfaction_score($hour_key) {
        // Query DynamoDB for satisfaction metrics
        // This is a simplified implementation
        return 0.85; // Placeholder
    }
    
    /**
     * Aggregate hourly metrics
     */
    private function aggregate_hourly_metrics() {
        // Aggregate metrics from the last hour
        // This would create summary records for reporting
        do_action('vortex_hourly_metrics_aggregated');
    }
}

// Initialize the stream processor
Vortex_Stream_Processor::get_instance(); 