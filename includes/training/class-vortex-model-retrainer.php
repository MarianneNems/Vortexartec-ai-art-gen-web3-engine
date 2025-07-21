<?php
/**
 * Vortex AI Engine - Model Retrainer
 * 
 * Continuous model retraining pipeline using feedback data
 * Implements fine-tuning, A/B testing, and model versioning
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Model_Retrainer {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * AWS clients
     */
    private $dynamodb_client;
    private $s3_client;
    private $lambda_client;
    
    /**
     * Training configuration
     */
    private $training_config = [
        'retrain_interval' => 14400, // 4 hours in seconds
        'min_feedback_samples' => 100,
        'ab_test_traffic_percentage' => 0.1, // 10%
        'performance_threshold' => 0.05, // 5% improvement required
        'model_storage_bucket' => 'vortex-ai-models',
        'training_job_prefix' => 'vortex-retrain-',
    ];
    
    /**
     * Model versions
     */
    private $model_versions = [];
    
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
        $this->load_model_versions();
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
            
            $this->dynamodb_client = new Aws\DynamoDB\DynamoDBClient($config);
            $this->s3_client = new Aws\S3\S3Client($config);
            $this->lambda_client = new Aws\Lambda\LambdaClient($config);
            
        } catch (Exception $e) {
            error_log("Vortex Model Retrainer: AWS client initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule retraining
        add_action('vortex_retrain_models', [$this, 'trigger_model_retraining']);
        add_action('vortex_ab_test_complete', [$this, 'evaluate_ab_test_results']);
        
        // Setup cron jobs
        if (!wp_next_scheduled('vortex_retrain_models')) {
            wp_schedule_event(time(), 'every_4_hours', 'vortex_retrain_models');
        }
        
        // Feedback processing hooks
        add_action('vortex_feedback_processed', [$this, 'check_retraining_trigger']);
        add_action('vortex_model_performance_update', [$this, 'update_model_performance']);
        
        // Manual retraining triggers
        add_action('wp_ajax_vortex_manual_retrain', [$this, 'manual_retrain']);
    }
    
    /**
     * Load model versions from database
     */
    private function load_model_versions() {
        $this->model_versions = get_option('vortex_model_versions', []);
    }
    
    /**
     * Check if retraining should be triggered
     */
    public function check_retraining_trigger($feedback_data) {
        $feedback_count = $this->get_feedback_count_since_last_training();
        
        if ($feedback_count >= $this->training_config['min_feedback_samples']) {
            $this->trigger_model_retraining();
        }
    }
    
    /**
     * Trigger model retraining process
     */
    public function trigger_model_retraining() {
        error_log("Vortex Model Retrainer: Starting model retraining process");
        
        // Collect training data
        $training_data = $this->collect_training_data();
        
        if (empty($training_data)) {
            error_log("Vortex Model Retrainer: No training data available");
            return false;
        }
        
        // Prepare training dataset
        $dataset = $this->prepare_training_dataset($training_data);
        
        // Start training job
        $training_job = $this->start_training_job($dataset);
        
        if ($training_job) {
            // Store training job reference
            $this->store_training_job($training_job);
            
            // Schedule A/B test setup
            wp_schedule_single_event(time() + 3600, 'vortex_setup_ab_test', [$training_job['job_id']]);
            
            error_log("Vortex Model Retrainer: Training job started: " . $training_job['job_id']);
        }
        
        return $training_job;
    }
    
    /**
     * Collect training data from DynamoDB
     */
    private function collect_training_data() {
        try {
            $training_data = [];
            
            // Query feedback data from the last 24 hours
            $start_time = time() - 86400; // 24 hours ago
            
            $result = $this->dynamodb_client->query([
                'TableName' => VORTEX_DYNAMODB_TABLE,
                'KeyConditionExpression' => 'PK = :pk AND #ts >= :start_time',
                'ExpressionAttributeNames' => [
                    '#ts' => 'Timestamp'
                ],
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => 'FEEDBACK'],
                    ':start_time' => ['N' => (string)$start_time]
                ]
            ]);
            
            foreach ($result['Items'] as $item) {
                $training_data[] = [
                    'prompt' => $item['Prompt']['S'] ?? '',
                    'response' => $item['Response']['S'] ?? '',
                    'rating' => intval($item['Rating']['N'] ?? 0),
                    'thumbs_up' => $item['ThumbsUp']['BOOL'] ?? false,
                    'thumbs_down' => $item['ThumbsDown']['BOOL'] ?? false,
                    'agent_name' => $item['AgentName']['S'] ?? '',
                    'user_tier' => $item['UserTier']['S'] ?? '',
                    'timestamp' => intval($item['Timestamp']['N'] ?? 0)
                ];
            }
            
            return $training_data;
            
        } catch (Exception $e) {
            error_log("Vortex Model Retrainer: Failed to collect training data: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Prepare training dataset
     */
    private function prepare_training_dataset($training_data) {
        $dataset = [
            'positive_samples' => [],
            'negative_samples' => [],
            'neutral_samples' => [],
            'metadata' => [
                'total_samples' => count($training_data),
                'created_at' => time(),
                'version' => '1.0'
            ]
        ];
        
        foreach ($training_data as $sample) {
            $score = $this->calculate_sample_score($sample);
            
            if ($score > 0.5) {
                $dataset['positive_samples'][] = $sample;
            } elseif ($score < -0.5) {
                $dataset['negative_samples'][] = $sample;
            } else {
                $dataset['neutral_samples'][] = $sample;
            }
        }
        
        // Upload dataset to S3
        $dataset_key = $this->upload_dataset_to_s3($dataset);
        
        return [
            'dataset_key' => $dataset_key,
            'positive_count' => count($dataset['positive_samples']),
            'negative_count' => count($dataset['negative_samples']),
            'neutral_count' => count($dataset['neutral_samples'])
        ];
    }
    
    /**
     * Calculate sample score for training
     */
    private function calculate_sample_score($sample) {
        $score = 0;
        
        // Rating contribution (1-5 scale)
        if ($sample['rating'] > 0) {
            $score += ($sample['rating'] - 3) / 2; // Convert to -1 to 1 scale
        }
        
        // Thumbs up/down contribution
        if ($sample['thumbs_up']) {
            $score += 1;
        }
        if ($sample['thumbs_down']) {
            $score -= 1;
        }
        
        // User tier weighting
        $tier_weight = $this->get_user_tier_weight($sample['user_tier']);
        $score *= $tier_weight;
        
        return $score;
    }
    
    /**
     * Get user tier weight for training
     */
    private function get_user_tier_weight($user_tier) {
        $weights = [
            'premium' => 1.5,
            'pro' => 1.2,
            'basic' => 1.0,
            'free' => 0.8,
            'anonymous' => 0.5
        ];
        
        return $weights[$user_tier] ?? 1.0;
    }
    
    /**
     * Upload dataset to S3
     */
    private function upload_dataset_to_s3($dataset) {
        try {
            $dataset_key = 'training-datasets/' . date('Y-m-d-H-i-s') . '-dataset.json';
            
            $this->s3_client->putObject([
                'Bucket' => $this->training_config['model_storage_bucket'],
                'Key' => $dataset_key,
                'Body' => json_encode($dataset, JSON_PRETTY_PRINT),
                'ContentType' => 'application/json'
            ]);
            
            return $dataset_key;
            
        } catch (Exception $e) {
            error_log("Vortex Model Retrainer: Failed to upload dataset to S3: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Start training job
     */
    private function start_training_job($dataset) {
        try {
            $job_id = $this->training_config['training_job_prefix'] . time();
            
            $training_payload = [
                'job_id' => $job_id,
                'dataset_key' => $dataset['dataset_key'],
                'model_config' => [
                    'base_model' => 'gpt-3.5-turbo',
                    'fine_tuning_method' => 'lora',
                    'learning_rate' => 1e-5,
                    'epochs' => 3,
                    'batch_size' => 8
                ],
                'hyperparameters' => [
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                    'top_p' => 0.9
                ]
            ];
            
            // Invoke Lambda function for training
            $result = $this->lambda_client->invoke([
                'FunctionName' => 'vortex-model-trainer',
                'Payload' => json_encode($training_payload),
                'InvocationType' => 'Event' // Async invocation
            ]);
            
            return [
                'job_id' => $job_id,
                'status' => 'started',
                'dataset' => $dataset,
                'started_at' => time(),
                'lambda_request_id' => $result['ResponseMetadata']['RequestId']
            ];
            
        } catch (Exception $e) {
            error_log("Vortex Model Retrainer: Failed to start training job: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Setup A/B test for new model
     */
    public function setup_ab_test($job_id) {
        $training_job = $this->get_training_job($job_id);
        
        if (!$training_job || $training_job['status'] !== 'completed') {
            error_log("Vortex Model Retrainer: Training job not completed for A/B test setup");
            return false;
        }
        
        // Create new model version
        $new_version = $this->create_model_version($training_job);
        
        // Setup A/B test routing
        $this->setup_ab_test_routing($new_version);
        
        // Schedule A/B test evaluation
        wp_schedule_single_event(time() + 3600, 'vortex_evaluate_ab_test', [$new_version['version_id']]);
        
        error_log("Vortex Model Retrainer: A/B test setup for model version: " . $new_version['version_id']);
        
        return $new_version;
    }
    
    /**
     * Create new model version
     */
    private function create_model_version($training_job) {
        $version_id = 'v' . (count($this->model_versions) + 1);
        
        $model_version = [
            'version_id' => $version_id,
            'training_job_id' => $training_job['job_id'],
            'model_artifact_key' => $training_job['model_artifact_key'],
            'created_at' => time(),
            'status' => 'testing',
            'ab_test_traffic' => $this->training_config['ab_test_traffic_percentage'],
            'performance_metrics' => []
        ];
        
        $this->model_versions[$version_id] = $model_version;
        update_option('vortex_model_versions', $this->model_versions);
        
        return $model_version;
    }
    
    /**
     * Setup A/B test routing
     */
    private function setup_ab_test_routing($model_version) {
        // Update model routing configuration
        $routing_config = get_option('vortex_model_routing', []);
        
        $routing_config['ab_tests'][$model_version['version_id']] = [
            'traffic_percentage' => $model_version['ab_test_traffic'],
            'started_at' => time(),
            'status' => 'active'
        ];
        
        update_option('vortex_model_routing', $routing_config);
    }
    
    /**
     * Evaluate A/B test results
     */
    public function evaluate_ab_test_results($version_id) {
        $model_version = $this->model_versions[$version_id] ?? null;
        
        if (!$model_version) {
            error_log("Vortex Model Retrainer: Model version not found: " . $version_id);
            return false;
        }
        
        // Collect performance metrics
        $performance_metrics = $this->collect_ab_test_metrics($version_id);
        
        // Compare with baseline
        $baseline_metrics = $this->get_baseline_metrics();
        $improvement = $this->calculate_performance_improvement($performance_metrics, $baseline_metrics);
        
        if ($improvement > $this->training_config['performance_threshold']) {
            // Promote to production
            $this->promote_model_to_production($version_id);
        } else {
            // Rollback to baseline
            $this->rollback_model_version($version_id);
        }
        
        // Update model version status
        $this->update_model_version_status($version_id, $improvement > $this->training_config['performance_threshold'] ? 'promoted' : 'rolled_back');
        
        error_log("Vortex Model Retrainer: A/B test evaluation complete for " . $version_id . " (improvement: " . round($improvement * 100, 2) . "%)");
    }
    
    /**
     * Collect A/B test metrics
     */
    private function collect_ab_test_metrics($version_id) {
        // Query DynamoDB for metrics during A/B test period
        $start_time = time() - 3600; // Last hour
        
        try {
            $result = $this->dynamodb_client->query([
                'TableName' => VORTEX_DYNAMODB_TABLE,
                'KeyConditionExpression' => 'PK = :pk AND #ts >= :start_time',
                'FilterExpression' => 'ModelVersion = :version',
                'ExpressionAttributeNames' => [
                    '#ts' => 'Timestamp'
                ],
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => 'METRICS'],
                    ':start_time' => ['N' => (string)$start_time],
                    ':version' => ['S' => $version_id]
                ]
            ]);
            
            return $this->calculate_metrics_from_data($result['Items']);
            
        } catch (Exception $e) {
            error_log("Vortex Model Retrainer: Failed to collect A/B test metrics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate metrics from raw data
     */
    private function calculate_metrics_from_data($items) {
        $metrics = [
            'total_requests' => 0,
            'successful_requests' => 0,
            'avg_latency' => 0,
            'avg_satisfaction' => 0,
            'error_rate' => 0
        ];
        
        $total_latency = 0;
        $total_satisfaction = 0;
        
        foreach ($items as $item) {
            $metrics['total_requests']++;
            
            if ($item['Success']['BOOL'] ?? true) {
                $metrics['successful_requests']++;
            }
            
            $total_latency += intval($item['ProcessingTimeMs']['N'] ?? 0);
            $total_satisfaction += floatval($item['SatisfactionScore']['N'] ?? 0);
        }
        
        if ($metrics['total_requests'] > 0) {
            $metrics['avg_latency'] = $total_latency / $metrics['total_requests'];
            $metrics['avg_satisfaction'] = $total_satisfaction / $metrics['total_requests'];
            $metrics['error_rate'] = ($metrics['total_requests'] - $metrics['successful_requests']) / $metrics['total_requests'];
        }
        
        return $metrics;
    }
    
    /**
     * Get baseline metrics
     */
    private function get_baseline_metrics() {
        // Get metrics for the current production model
        return [
            'avg_latency' => 1200, // ms
            'avg_satisfaction' => 0.85,
            'error_rate' => 0.02
        ];
    }
    
    /**
     * Calculate performance improvement
     */
    private function calculate_performance_improvement($new_metrics, $baseline_metrics) {
        $improvements = [];
        
        // Latency improvement (lower is better)
        if ($baseline_metrics['avg_latency'] > 0) {
            $latency_improvement = ($baseline_metrics['avg_latency'] - $new_metrics['avg_latency']) / $baseline_metrics['avg_latency'];
            $improvements[] = $latency_improvement;
        }
        
        // Satisfaction improvement (higher is better)
        if ($baseline_metrics['avg_satisfaction'] > 0) {
            $satisfaction_improvement = ($new_metrics['avg_satisfaction'] - $baseline_metrics['avg_satisfaction']) / $baseline_metrics['avg_satisfaction'];
            $improvements[] = $satisfaction_improvement;
        }
        
        // Error rate improvement (lower is better)
        if ($baseline_metrics['error_rate'] > 0) {
            $error_improvement = ($baseline_metrics['error_rate'] - $new_metrics['error_rate']) / $baseline_metrics['error_rate'];
            $improvements[] = $error_improvement;
        }
        
        // Return average improvement
        return !empty($improvements) ? array_sum($improvements) / count($improvements) : 0;
    }
    
    /**
     * Promote model to production
     */
    private function promote_model_to_production($version_id) {
        $routing_config = get_option('vortex_model_routing', []);
        
        // Update production model
        $routing_config['production_model'] = $version_id;
        $routing_config['ab_tests'][$version_id]['status'] = 'promoted';
        
        update_option('vortex_model_routing', $routing_config);
        
        error_log("Vortex Model Retrainer: Model version " . $version_id . " promoted to production");
    }
    
    /**
     * Rollback model version
     */
    private function rollback_model_version($version_id) {
        $routing_config = get_option('vortex_model_routing', []);
        
        // Remove from A/B test
        unset($routing_config['ab_tests'][$version_id]);
        
        update_option('vortex_model_routing', $routing_config);
        
        error_log("Vortex Model Retrainer: Model version " . $version_id . " rolled back");
    }
    
    /**
     * Get feedback count since last training
     */
    private function get_feedback_count_since_last_training() {
        $last_training = get_option('vortex_last_training_time', 0);
        $start_time = $last_training ?: (time() - 86400);
        
        try {
            $result = $this->dynamodb_client->query([
                'TableName' => VORTEX_DYNAMODB_TABLE,
                'KeyConditionExpression' => 'PK = :pk AND #ts >= :start_time',
                'ExpressionAttributeNames' => [
                    '#ts' => 'Timestamp'
                ],
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => 'FEEDBACK'],
                    ':start_time' => ['N' => (string)$start_time]
                ],
                'Select' => 'COUNT'
            ]);
            
            return $result['Count'] ?? 0;
            
        } catch (Exception $e) {
            error_log("Vortex Model Retrainer: Failed to get feedback count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Store training job reference
     */
    private function store_training_job($training_job) {
        $training_jobs = get_option('vortex_training_jobs', []);
        $training_jobs[$training_job['job_id']] = $training_job;
        update_option('vortex_training_jobs', $training_jobs);
    }
    
    /**
     * Get training job by ID
     */
    private function get_training_job($job_id) {
        $training_jobs = get_option('vortex_training_jobs', []);
        return $training_jobs[$job_id] ?? null;
    }
    
    /**
     * Update model version status
     */
    private function update_model_version_status($version_id, $status) {
        if (isset($this->model_versions[$version_id])) {
            $this->model_versions[$version_id]['status'] = $status;
            update_option('vortex_model_versions', $this->model_versions);
        }
    }
    
    /**
     * Manual retrain trigger
     */
    public function manual_retrain() {
        check_ajax_referer('vortex_retrain_nonce', 'nonce');
        
        $results = $this->trigger_model_retraining();
        
        if ($results) {
            wp_send_json_success([
                'message' => 'Model retraining started successfully',
                'job_id' => $results['job_id']
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to start model retraining']);
        }
    }
}

// Initialize the model retrainer
Vortex_Model_Retrainer::get_instance(); 