<?php
/**
 * Memory API for VORTEX AI Engine
 * Provides REST endpoints for user memory and live memory shortcode
 *
 * @package VortexAIEngine
 * @version 3.0.0 Enhanced
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_Memory_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_memory_endpoints']);
        add_shortcode('huraii_memory', [$this, 'render_memory_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_memory_scripts']);
    }
    
    /**
     * Register REST API endpoints for memory
     */
    public function register_memory_endpoints() {
        // Get user memory endpoint
        register_rest_route('vortex/v1', '/memory/(?P<user_id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_user_memory'],
            'permission_callback' => [$this, 'check_memory_permissions'],
            'args' => [
                'user_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'limit' => [
                    'default' => 50,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 1000;
                    }
                ],
                'offset' => [
                    'default' => 0,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 0;
                    }
                ]
            ]
        ]);
        
        // Get current user memory endpoint
        register_rest_route('vortex/v1', '/memory/current', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_current_user_memory'],
            'permission_callback' => [$this, 'check_current_user_permissions'],
            'args' => [
                'limit' => [
                    'default' => 20,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ],
                'since' => [
                    'default' => 0,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 0;
                    }
                ]
            ]
        ]);
        
        // Memory analytics endpoint (admin only)
        register_rest_route('vortex/v1', '/memory/analytics', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_memory_analytics'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'timeframe' => [
                    'default' => '24h',
                    'validate_callback' => function($param) {
                        return in_array($param, ['1h', '24h', '7d', '30d']);
                    }
                ]
            ]
        ]);
        
        // Clear user memory endpoint
        register_rest_route('vortex/v1', '/memory/(?P<user_id>\d+)/clear', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'clear_user_memory'],
            'permission_callback' => [$this, 'check_memory_permissions'],
            'args' => [
                'user_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
    }
    
    /**
     * Get user memory from DynamoDB
     */
    public function get_user_memory($request) {
        $user_id = $request['user_id'];
        $limit = $request['limit'];
        $offset = $request['offset'];
        
        try {
            // Initialize DynamoDB client
            $dynamoClient = $this->get_dynamodb_client();
            
            // Query user memory
            $result = $dynamoClient->query([
                'TableName' => 'vortex_user_memory',
                'KeyConditionExpression' => 'user_id = :user_id',
                'ExpressionAttributeValues' => [
                    ':user_id' => ['S' => (string) $user_id]
                ],
                'ScanIndexForward' => false, // Sort by timestamp descending
                'Limit' => $limit
            ]);
            
            // Process results
            $memory_items = [];
            foreach ($result['Items'] as $item) {
                $memory_items[] = [
                    'timestamp' => (float) $item['timestamp']['N'],
                    'action' => $item['action']['S'],
                    'params' => json_decode($item['params']['S'], true),
                    'result_summary' => json_decode($item['result_summary']['S'], true),
                    'costs' => json_decode($item['costs']['S'], true),
                    'quality_metrics' => json_decode($item['quality_metrics']['S'], true),
                    'performance_data' => json_decode($item['performance_data']['S'], true),
                    'context' => json_decode($item['context']['S'], true),
                    'formatted_timestamp' => date('Y-m-d H:i:s', $item['timestamp']['N'])
                ];
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => $memory_items,
                'total' => count($memory_items),
                'user_id' => $user_id,
                'has_more' => isset($result['LastEvaluatedKey'])
            ]);
            
        } catch (Exception $e) {
            error_log('[VortexAI Memory API] Error retrieving user memory: ' . $e->getMessage());
            return new WP_Error('memory_error', 'Failed to retrieve user memory', ['status' => 500]);
        }
    }
    
    /**
     * Get current user memory
     */
    public function get_current_user_memory($request) {
        $user_id = get_current_user_id();
        $limit = $request['limit'];
        $since = $request['since'];
        
        if (!$user_id) {
            return new WP_Error('auth_required', 'Authentication required', ['status' => 401]);
        }
        
        try {
            // Initialize DynamoDB client
            $dynamoClient = $this->get_dynamodb_client();
            
            // Build query parameters
            $queryParams = [
                'TableName' => 'vortex_user_memory',
                'KeyConditionExpression' => 'user_id = :user_id',
                'ExpressionAttributeValues' => [
                    ':user_id' => ['S' => (string) $user_id]
                ],
                'ScanIndexForward' => false,
                'Limit' => $limit
            ];
            
            // Add since filter if provided
            if ($since > 0) {
                $queryParams['FilterExpression'] = '#ts > :since';
                $queryParams['ExpressionAttributeNames'] = ['#ts' => 'timestamp'];
                $queryParams['ExpressionAttributeValues'][':since'] = ['N' => (string) $since];
            }
            
            // Query user memory
            $result = $dynamoClient->query($queryParams);
            
            // Process results
            $memory_items = [];
            foreach ($result['Items'] as $item) {
                $memory_items[] = [
                    'timestamp' => (float) $item['timestamp']['N'],
                    'action' => $item['action']['S'],
                    'result_summary' => json_decode($item['result_summary']['S'], true),
                    'quality_score' => json_decode($item['quality_metrics']['S'], true)['quality_score'] ?? 0,
                    'processing_time' => json_decode($item['quality_metrics']['S'], true)['processing_time'] ?? 0,
                    'cost' => json_decode($item['costs']['S'], true)['total_cost'] ?? 0,
                    'formatted_timestamp' => date('Y-m-d H:i:s', $item['timestamp']['N']),
                    'relative_time' => $this->get_relative_time($item['timestamp']['N'])
                ];
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => $memory_items,
                'total' => count($memory_items),
                'user_id' => $user_id,
                'last_updated' => microtime(true)
            ]);
            
        } catch (Exception $e) {
            error_log('[VortexAI Memory API] Error retrieving current user memory: ' . $e->getMessage());
            return new WP_Error('memory_error', 'Failed to retrieve user memory', ['status' => 500]);
        }
    }
    
    /**
     * Get memory analytics (admin only)
     */
    public function get_memory_analytics($request) {
        $timeframe = $request['timeframe'];
        
        try {
            // Initialize DynamoDB client
            $dynamoClient = $this->get_dynamodb_client();
            
            // Calculate time range
            $now = time();
            $time_ranges = [
                '1h' => $now - 3600,
                '24h' => $now - 86400,
                '7d' => $now - 604800,
                '30d' => $now - 2592000
            ];
            
            $start_time = $time_ranges[$timeframe];
            
            // Scan for analytics (in production, use a GSI for better performance)
            $result = $dynamoClient->scan([
                'TableName' => 'vortex_user_memory',
                'FilterExpression' => '#ts > :start_time',
                'ExpressionAttributeNames' => ['#ts' => 'timestamp'],
                'ExpressionAttributeValues' => [
                    ':start_time' => ['N' => (string) $start_time]
                ]
            ]);
            
            // Process analytics
            $analytics = [
                'total_events' => 0,
                'unique_users' => [],
                'actions' => [],
                'avg_quality_score' => 0,
                'avg_processing_time' => 0,
                'total_cost' => 0,
                'timeframe' => $timeframe
            ];
            
            $total_quality = 0;
            $total_processing_time = 0;
            
            foreach ($result['Items'] as $item) {
                $analytics['total_events']++;
                $analytics['unique_users'][$item['user_id']['S']] = true;
                
                $action = $item['action']['S'];
                $analytics['actions'][$action] = ($analytics['actions'][$action] ?? 0) + 1;
                
                $quality_metrics = json_decode($item['quality_metrics']['S'], true);
                $costs = json_decode($item['costs']['S'], true);
                
                $total_quality += $quality_metrics['quality_score'] ?? 0;
                $total_processing_time += $quality_metrics['processing_time'] ?? 0;
                $analytics['total_cost'] += $costs['total_cost'] ?? 0;
            }
            
            $analytics['unique_users'] = count($analytics['unique_users']);
            $analytics['avg_quality_score'] = $analytics['total_events'] > 0 ? 
                $total_quality / $analytics['total_events'] : 0;
            $analytics['avg_processing_time'] = $analytics['total_events'] > 0 ? 
                $total_processing_time / $analytics['total_events'] : 0;
            
            return rest_ensure_response([
                'success' => true,
                'data' => $analytics
            ]);
            
        } catch (Exception $e) {
            error_log('[VortexAI Memory API] Error retrieving analytics: ' . $e->getMessage());
            return new WP_Error('analytics_error', 'Failed to retrieve analytics', ['status' => 500]);
        }
    }
    
    /**
     * Clear user memory
     */
    public function clear_user_memory($request) {
        $user_id = $request['user_id'];
        
        try {
            // Initialize DynamoDB client
            $dynamoClient = $this->get_dynamodb_client();
            
            // Query all items for user
            $result = $dynamoClient->query([
                'TableName' => 'vortex_user_memory',
                'KeyConditionExpression' => 'user_id = :user_id',
                'ExpressionAttributeValues' => [
                    ':user_id' => ['S' => (string) $user_id]
                ],
                'ProjectionExpression' => 'user_id, #ts',
                'ExpressionAttributeNames' => ['#ts' => 'timestamp']
            ]);
            
            // Delete all items
            $deleted_count = 0;
            foreach ($result['Items'] as $item) {
                $dynamoClient->deleteItem([
                    'TableName' => 'vortex_user_memory',
                    'Key' => [
                        'user_id' => $item['user_id'],
                        'timestamp' => $item['timestamp']
                    ]
                ]);
                $deleted_count++;
            }
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'User memory cleared successfully',
                'deleted_count' => $deleted_count
            ]);
            
        } catch (Exception $e) {
            error_log('[VortexAI Memory API] Error clearing user memory: ' . $e->getMessage());
            return new WP_Error('clear_error', 'Failed to clear user memory', ['status' => 500]);
        }
    }
    
    /**
     * Render memory shortcode
     */
    public function render_memory_shortcode($atts) {
        $defaults = [
            'user_id' => get_current_user_id(),
            'limit' => 20,
            'live_update' => true,
            'height' => '400px',
            'theme' => 'default'
        ];
        
        $args = shortcode_atts($defaults, $atts, 'huraii_memory');
        
        // Security check
        if (!$this->can_view_memory($args['user_id'])) {
            return '<div class="huraii-memory-error">Access denied</div>';
        }
        
        ob_start();
        ?>
        <div class="huraii-memory-container" 
             data-user-id="<?php echo esc_attr($args['user_id']); ?>"
             data-limit="<?php echo esc_attr($args['limit']); ?>"
             data-live-update="<?php echo esc_attr($args['live_update']); ?>"
             data-theme="<?php echo esc_attr($args['theme']); ?>"
             style="height: <?php echo esc_attr($args['height']); ?>;">
            
            <div class="huraii-memory-header">
                <h3>Memory Timeline</h3>
                <div class="huraii-memory-controls">
                    <button class="huraii-memory-refresh" title="Refresh">🔄</button>
                    <button class="huraii-memory-clear" title="Clear Memory">🗑️</button>
                    <span class="huraii-memory-status">Live</span>
                </div>
            </div>
            
            <div class="huraii-memory-content">
                <div class="huraii-memory-loading">Loading memory...</div>
                <div class="huraii-memory-timeline"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue memory scripts and styles
     */
    public function enqueue_memory_scripts() {
        wp_enqueue_script(
            'huraii-memory-api',
            plugin_dir_url(__FILE__) . '../assets/js/memory-api.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'huraii-memory-api',
            plugin_dir_url(__FILE__) . '../assets/css/memory-api.css',
            [],
            '1.0.0'
        );
        
        wp_localize_script('huraii-memory-api', 'huraii_memory', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vortex/v1/memory/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'current_user_id' => get_current_user_id()
        ]);
    }
    
    /**
     * Check memory permissions
     */
    public function check_memory_permissions($request) {
        $user_id = $request['user_id'];
        $current_user = get_current_user_id();
        
        // Users can only view their own memory, admins can view all
        return $current_user == $user_id || current_user_can('administrator');
    }
    
    /**
     * Check current user permissions
     */
    public function check_current_user_permissions($request) {
        return is_user_logged_in();
    }
    
    /**
     * Check admin permissions
     */
    public function check_admin_permissions($request) {
        return current_user_can('administrator');
    }
    
    /**
     * Check if user can view memory
     */
    private function can_view_memory($user_id) {
        $current_user = get_current_user_id();
        return $current_user == $user_id || current_user_can('administrator');
    }
    
    /**
     * Get relative time
     */
    private function get_relative_time($timestamp) {
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } else {
            return floor($diff / 86400) . ' days ago';
        }
    }
    
    /**
     * Get DynamoDB client
     */
    private function get_dynamodb_client() {
        static $client = null;
        
        if ($client === null) {
            $client = new \Aws\DynamoDb\DynamoDbClient([
                'version' => 'latest',
                'region' => getenv('AWS_REGION') ?: 'us-east-1'
            ]);
        }
        
        return $client;
    }
}

// Initialize the Memory API
VortexAIEngine_MemoryAPI::get_instance();
?> 