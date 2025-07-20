<?php
/**
 * AWS Services Integration
 * Complete integration for all AWS services needed for the 7-step orchestration pipeline
 * 
 * Services:
 * - SNS (Simple Notification Service) for EventBus
 * - SQS (Simple Queue Service) for message queuing  
 * - DynamoDB for memory storage
 * - Batch for training job triggers
 * - S3 for data lake storage (existing)
 * - Lambda for serverless functions
 * - EventBridge for event routing
 *
 * @package VortexAIEngine
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_AWSServices {
    
    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var array AWS SDK clients */
    private $clients = [];
    
    /** @var array AWS configuration */
    private $config = [];
    
    /** @var bool AWS services availability */
    private $is_available = false;
    
    /** @var array Service status tracking */
    private $service_status = [
        'sns' => false,
        'sqs' => false,
        'dynamodb' => false,
        'batch' => false,
        's3' => false,
        'lambda' => false,
        'eventbridge' => false
    ];
    
    /** @var array Default configuration */
    private $default_config = [
        'region' => 'us-east-1',
        'version' => 'latest',
        'profile' => 'default',
        'timeout' => 30,
        'retry_attempts' => 3
    ];
    
    /** Singleton pattern */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /** Constructor */
    private function __construct() {
        $this->initialize_configuration();
        $this->initialize_aws_sdk();
        $this->setup_service_clients();
        $this->verify_services();
    }
    
    /**
     * Initialize configuration
     */
    private function initialize_configuration() {
        $this->config = array_merge($this->default_config, [
            'region' => get_option('vortex_aws_region', $this->default_config['region']),
            'credentials' => [
                'key' => get_option('vortex_aws_access_key'),
                'secret' => get_option('vortex_aws_secret_key')
            ]
        ]);
        
        // Validate credentials
        if ( empty($this->config['credentials']['key']) || empty($this->config['credentials']['secret']) ) {
            error_log('[VortexAI AWS] Missing AWS credentials in configuration');
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize AWS SDK
     */
    private function initialize_aws_sdk() {
        if ( !class_exists('Aws\Sdk') ) {
            error_log('[VortexAI AWS] AWS SDK not found. Please install via composer: aws/aws-sdk-php');
            return false;
        }
        
        try {
            $this->sdk = new \Aws\Sdk([
                'region' => $this->config['region'],
                'version' => $this->config['version'],
                'credentials' => $this->config['credentials']
            ]);
            
            $this->is_available = true;
            error_log('[VortexAI AWS] AWS SDK initialized successfully');
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] Failed to initialize AWS SDK: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Setup service clients
     */
    private function setup_service_clients() {
        if ( !$this->is_available ) return;
        
        $services = ['sns', 'sqs', 'dynamodb', 'batch', 's3', 'lambda', 'eventbridge'];
        
        foreach ( $services as $service ) {
            try {
                $method = 'create' . ucfirst($service);
                $this->clients[$service] = $this->sdk->$method();
                $this->service_status[$service] = true;
                
            } catch (Exception $e) {
                error_log("[VortexAI AWS] Failed to create {$service} client: " . $e->getMessage());
                $this->service_status[$service] = false;
            }
        }
    }
    
    /**
     * Verify services are working
     */
    private function verify_services() {
        if ( !$this->is_available ) return;
        
        // Test SNS
        $this->verify_sns();
        
        // Test SQS
        $this->verify_sqs();
        
        // Test DynamoDB
        $this->verify_dynamodb();
        
        // Test Batch
        $this->verify_batch();
        
        // Test S3 (using existing class)
        $this->verify_s3();
    }
    
    /**
     * Verify SNS service
     */
    private function verify_sns() {
        if ( !$this->service_status['sns'] ) return false;
        
        try {
            $result = $this->clients['sns']->listTopics();
            error_log('[VortexAI AWS] SNS service verified successfully');
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SNS verification failed: ' . $e->getMessage());
            $this->service_status['sns'] = false;
            return false;
        }
    }
    
    /**
     * Verify SQS service
     */
    private function verify_sqs() {
        if ( !$this->service_status['sqs'] ) return false;
        
        try {
            $result = $this->clients['sqs']->listQueues();
            error_log('[VortexAI AWS] SQS service verified successfully');
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SQS verification failed: ' . $e->getMessage());
            $this->service_status['sqs'] = false;
            return false;
        }
    }
    
    /**
     * Verify DynamoDB service
     */
    private function verify_dynamodb() {
        if ( !$this->service_status['dynamodb'] ) return false;
        
        try {
            $result = $this->clients['dynamodb']->listTables();
            error_log('[VortexAI AWS] DynamoDB service verified successfully');
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] DynamoDB verification failed: ' . $e->getMessage());
            $this->service_status['dynamodb'] = false;
            return false;
        }
    }
    
    /**
     * Verify Batch service
     */
    private function verify_batch() {
        if ( !$this->service_status['batch'] ) return false;
        
        try {
            $result = $this->clients['batch']->describeJobQueues();
            error_log('[VortexAI AWS] Batch service verified successfully');
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] Batch verification failed: ' . $e->getMessage());
            $this->service_status['batch'] = false;
            return false;
        }
    }
    
    /**
     * Verify S3 service
     */
    private function verify_s3() {
        $s3 = VortexAIEngine_S3::getInstance();
        $this->service_status['s3'] = $s3->isAvailable();
        
        if ( $this->service_status['s3'] ) {
            error_log('[VortexAI AWS] S3 service verified successfully');
        } else {
            error_log('[VortexAI AWS] S3 service verification failed');
        }
        
        return $this->service_status['s3'];
    }
    
    // ===========================================
    // SNS (EventBus) Methods
    // ===========================================
    
    /**
     * Publish message to SNS topic
     */
    public function publishToSNS($topicArn, $message, $subject = null) {
        if ( !$this->service_status['sns'] ) {
            error_log('[VortexAI AWS] SNS not available');
            return false;
        }
        
        try {
            $params = [
                'TopicArn' => $topicArn,
                'Message' => is_array($message) ? json_encode($message) : $message
            ];
            
            if ( $subject ) {
                $params['Subject'] = $subject;
            }
            
            $result = $this->clients['sns']->publish($params);
            
            return [
                'success' => true,
                'message_id' => $result['MessageId']
            ];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SNS publish failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create SNS topic
     */
    public function createSNSTopic($topicName) {
        if ( !$this->service_status['sns'] ) return false;
        
        try {
            $result = $this->clients['sns']->createTopic([
                'Name' => $topicName
            ]);
            
            return $result['TopicArn'];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SNS topic creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Subscribe to SNS topic
     */
    public function subscribeToSNS($topicArn, $protocol, $endpoint) {
        if ( !$this->service_status['sns'] ) return false;
        
        try {
            $result = $this->clients['sns']->subscribe([
                'TopicArn' => $topicArn,
                'Protocol' => $protocol,
                'Endpoint' => $endpoint
            ]);
            
            return $result['SubscriptionArn'];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SNS subscription failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // ===========================================
    // SQS (Message Queue) Methods
    // ===========================================
    
    /**
     * Send message to SQS queue
     */
    public function sendToSQS($queueUrl, $message, $delaySeconds = 0) {
        if ( !$this->service_status['sqs'] ) {
            error_log('[VortexAI AWS] SQS not available');
            return false;
        }
        
        try {
            $params = [
                'QueueUrl' => $queueUrl,
                'MessageBody' => is_array($message) ? json_encode($message) : $message
            ];
            
            if ( $delaySeconds > 0 ) {
                $params['DelaySeconds'] = $delaySeconds;
            }
            
            $result = $this->clients['sqs']->sendMessage($params);
            
            return [
                'success' => true,
                'message_id' => $result['MessageId']
            ];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SQS send failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Receive messages from SQS queue
     */
    public function receiveFromSQS($queueUrl, $maxMessages = 1) {
        if ( !$this->service_status['sqs'] ) return false;
        
        try {
            $result = $this->clients['sqs']->receiveMessage([
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => $maxMessages,
                'WaitTimeSeconds' => 20
            ]);
            
            return $result['Messages'] ?? [];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SQS receive failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete message from SQS queue
     */
    public function deleteFromSQS($queueUrl, $receiptHandle) {
        if ( !$this->service_status['sqs'] ) return false;
        
        try {
            $this->clients['sqs']->deleteMessage([
                'QueueUrl' => $queueUrl,
                'ReceiptHandle' => $receiptHandle
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SQS delete failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create SQS queue
     */
    public function createSQSQueue($queueName, $attributes = []) {
        if ( !$this->service_status['sqs'] ) return false;
        
        try {
            $params = ['QueueName' => $queueName];
            
            if ( !empty($attributes) ) {
                $params['Attributes'] = $attributes;
            }
            
            $result = $this->clients['sqs']->createQueue($params);
            
            return $result['QueueUrl'];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] SQS queue creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // ===========================================
    // DynamoDB (Memory Store) Methods
    // ===========================================
    
    /**
     * Put item in DynamoDB table
     */
    public function putItemDynamoDB($tableName, $item) {
        if ( !$this->service_status['dynamodb'] ) {
            error_log('[VortexAI AWS] DynamoDB not available');
            return false;
        }
        
        try {
            $result = $this->clients['dynamodb']->putItem([
                'TableName' => $tableName,
                'Item' => $item
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] DynamoDB put failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get item from DynamoDB table
     */
    public function getItemDynamoDB($tableName, $key) {
        if ( !$this->service_status['dynamodb'] ) return false;
        
        try {
            $result = $this->clients['dynamodb']->getItem([
                'TableName' => $tableName,
                'Key' => $key
            ]);
            
            return $result['Item'] ?? null;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] DynamoDB get failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Query DynamoDB table
     */
    public function queryDynamoDB($tableName, $keyConditionExpression, $expressionAttributeValues = []) {
        if ( !$this->service_status['dynamodb'] ) return false;
        
        try {
            $params = [
                'TableName' => $tableName,
                'KeyConditionExpression' => $keyConditionExpression
            ];
            
            if ( !empty($expressionAttributeValues) ) {
                $params['ExpressionAttributeValues'] = $expressionAttributeValues;
            }
            
            $result = $this->clients['dynamodb']->query($params);
            
            return $result['Items'] ?? [];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] DynamoDB query failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create DynamoDB table
     */
    public function createDynamoDBTable($tableName, $keySchema, $attributeDefinitions, $provisionedThroughput) {
        if ( !$this->service_status['dynamodb'] ) return false;
        
        try {
            $result = $this->clients['dynamodb']->createTable([
                'TableName' => $tableName,
                'KeySchema' => $keySchema,
                'AttributeDefinitions' => $attributeDefinitions,
                'ProvisionedThroughput' => $provisionedThroughput
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] DynamoDB table creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // ===========================================
    // Batch (Training Jobs) Methods
    // ===========================================
    
    /**
     * Submit batch job
     */
    public function submitBatchJob($jobName, $jobQueue, $jobDefinition, $parameters = []) {
        if ( !$this->service_status['batch'] ) {
            error_log('[VortexAI AWS] Batch not available');
            return false;
        }
        
        try {
            $params = [
                'jobName' => $jobName,
                'jobQueue' => $jobQueue,
                'jobDefinition' => $jobDefinition
            ];
            
            if ( !empty($parameters) ) {
                $params['parameters'] = $parameters;
            }
            
            $result = $this->clients['batch']->submitJob($params);
            
            return [
                'success' => true,
                'job_id' => $result['jobId'],
                'job_name' => $result['jobName']
            ];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] Batch job submission failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Describe batch jobs
     */
    public function describeBatchJobs($jobIds) {
        if ( !$this->service_status['batch'] ) return false;
        
        try {
            $result = $this->clients['batch']->describeJobs([
                'jobs' => is_array($jobIds) ? $jobIds : [$jobIds]
            ]);
            
            return $result['jobs'] ?? [];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] Batch job description failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel batch job
     */
    public function cancelBatchJob($jobId, $reason = 'User cancelled') {
        if ( !$this->service_status['batch'] ) return false;
        
        try {
            $this->clients['batch']->cancelJob([
                'jobId' => $jobId,
                'reason' => $reason
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] Batch job cancellation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // ===========================================
    // EventBridge Methods
    // ===========================================
    
    /**
     * Put events to EventBridge
     */
    public function putEvents($events) {
        if ( !$this->service_status['eventbridge'] ) return false;
        
        try {
            $result = $this->clients['eventbridge']->putEvents([
                'Entries' => $events
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] EventBridge put events failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // ===========================================
    // Lambda Methods
    // ===========================================
    
    /**
     * Invoke Lambda function
     */
    public function invokeLambda($functionName, $payload = null, $invocationType = 'RequestResponse') {
        if ( !$this->service_status['lambda'] ) return false;
        
        try {
            $params = [
                'FunctionName' => $functionName,
                'InvocationType' => $invocationType
            ];
            
            if ( $payload !== null ) {
                $params['Payload'] = is_array($payload) ? json_encode($payload) : $payload;
            }
            
            $result = $this->clients['lambda']->invoke($params);
            
            return [
                'success' => true,
                'status_code' => $result['StatusCode'],
                'payload' => $result['Payload']
            ];
            
        } catch (Exception $e) {
            error_log('[VortexAI AWS] Lambda invocation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // ===========================================
    // Utility Methods
    // ===========================================
    
    /**
     * Check if AWS services are available
     */
    public function isAvailable() {
        return $this->is_available;
    }
    
    /**
     * Get service status
     */
    public function getServiceStatus($service = null) {
        if ( $service ) {
            return $this->service_status[$service] ?? false;
        }
        
        return $this->service_status;
    }
    
    /**
     * Get AWS client for specific service
     */
    public function getClient($service) {
        return $this->clients[$service] ?? null;
    }
    
    /**
     * Get AWS configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Setup required AWS resources
     */
    public function setupRequiredResources() {
        $results = [];
        
        // Create SNS topic for events
        $topicArn = $this->createSNSTopic('VortexAI-Events');
        if ( $topicArn ) {
            update_option('vortex_sns_topic_arn', $topicArn);
            $results['sns_topic'] = $topicArn;
        }
        
        // Create SQS queue for batch processing
        $queueUrl = $this->createSQSQueue('VortexAI-BatchQueue');
        if ( $queueUrl ) {
            update_option('vortex_sqs_queue_url', $queueUrl);
            $results['sqs_queue'] = $queueUrl;
        }
        
        // Create DynamoDB table for user memory
        $memoryTableCreated = $this->createDynamoDBTable(
            'vortex_user_memory',
            [
                ['AttributeName' => 'user_id', 'KeyType' => 'HASH'],
                ['AttributeName' => 'timestamp', 'KeyType' => 'RANGE']
            ],
            [
                ['AttributeName' => 'user_id', 'AttributeType' => 'S'],
                ['AttributeName' => 'timestamp', 'AttributeType' => 'N']
            ],
            ['ReadCapacityUnits' => 5, 'WriteCapacityUnits' => 5]
        );
        
        if ( $memoryTableCreated ) {
            $results['dynamodb_table'] = 'vortex_user_memory';
        }
        
        return $results;
    }
    
    /**
     * Test all services
     */
    public function testServices() {
        $results = [];
        
        foreach ( $this->service_status as $service => $status ) {
            $results[$service] = [
                'available' => $status,
                'test_result' => $status ? $this->{'test_' . $service}() : false
            ];
        }
        
        return $results;
    }
    
    /**
     * Test SNS service
     */
    private function test_sns() {
        $topicArn = get_option('vortex_sns_topic_arn');
        if ( !$topicArn ) return false;
        
        return $this->publishToSNS($topicArn, 'Test message from VortexAI', 'Test Message');
    }
    
    /**
     * Test SQS service
     */
    private function test_sqs() {
        $queueUrl = get_option('vortex_sqs_queue_url');
        if ( !$queueUrl ) return false;
        
        return $this->sendToSQS($queueUrl, 'Test message from VortexAI');
    }
    
    /**
     * Test DynamoDB service
     */
    private function test_dynamodb() {
        $testItem = [
            'user_id' => ['S' => 'test_user'],
            'timestamp' => ['N' => (string)time()],
            'data' => ['S' => 'Test data from VortexAI']
        ];
        
        return $this->putItemDynamoDB('vortex_user_memory', $testItem);
    }
    
    /**
     * Test Batch service
     */
    private function test_batch() {
        // Just check if we can describe job queues
        try {
            $result = $this->clients['batch']->describeJobQueues();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test S3 service
     */
    private function test_s3() {
        $s3 = VortexAIEngine_S3::getInstance();
        return $s3->isAvailable();
    }
    
    /**
     * Test Lambda service
     */
    private function test_lambda() {
        try {
            $result = $this->clients['lambda']->listFunctions(['MaxItems' => 1]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test EventBridge service
     */
    private function test_eventbridge() {
        try {
            $result = $this->clients['eventbridge']->listEventBuses(['Limit' => 1]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get orchestration pipeline status
     */
    public function getOrchestrationPipelineStatus() {
        return [
            'step1_vault_fetch' => class_exists('VortexAIEngine_Vault') && VortexAIEngine_Vault::getInstance()->isAvailable(),
            'step2_gpu_call' => true, // API calls are always available
            'step3_memory_store' => $this->service_status['dynamodb'],
            'step4_eventbus_emit' => $this->service_status['sns'] && $this->service_status['sqs'],
            'step5_s3_write' => $this->service_status['s3'],
            'step6_batch_training' => $this->service_status['batch'],
            'step7_response_processing' => true, // Always available
            'overall_status' => $this->calculateOverallStatus()
        ];
    }
    
    /**
     * Calculate overall orchestration status
     */
    private function calculateOverallStatus() {
        $pipeline = $this->getOrchestrationPipelineStatus();
        $available_steps = 0;
        $total_steps = 7;
        
        foreach ( $pipeline as $step => $status ) {
            if ( $step !== 'overall_status' && $status ) {
                $available_steps++;
            }
        }
        
        return ($available_steps / $total_steps) * 100;
    }
} 