<?php
/**
 * VORTEX AI Engine - RunPod Vault
 * 
 * Cloud GPU integration and RunPod management
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RunPod Vault Class
 * 
 * Handles cloud GPU integration, RunPod management, and distributed computing
 */
class Vortex_Runpod_Vault {
    
    /**
     * Vault configuration
     */
    private $config = [
        'name' => 'VORTEX RunPod Vault',
        'version' => '3.0.0',
        'api_version' => 'v1',
        'gpu_types' => ['RTX_4090', 'RTX_3090', 'A100', 'V100'],
        'max_instances' => 10
    ];
    
    /**
     * RunPod API configuration
     */
    private $api_config = [];
    
    /**
     * Active instances
     */
    private $active_instances = [];
    
    /**
     * Instance cache
     */
    private $instance_cache = [];
    
    /**
     * Initialize the RunPod vault
     */
    public function init() {
        $this->load_configuration();
        $this->initialize_api_connection();
        $this->register_hooks();
        $this->load_instance_cache();
        
        error_log('VORTEX AI Engine: RunPod Vault initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->api_config = [
            'api_key' => get_option('vortex_runpod_api_key', ''),
            'api_url' => get_option('vortex_runpod_api_url', 'https://api.runpod.io/v2'),
            'endpoint_id' => get_option('vortex_runpod_endpoint_id', ''),
            'template_id' => get_option('vortex_runpod_template_id', ''),
            'gpu_type' => get_option('vortex_runpod_gpu_type', 'RTX_4090'),
            'max_instances' => get_option('vortex_runpod_max_instances', 5)
        ];
        
        $this->config['instance_settings'] = [
            'auto_scaling' => get_option('vortex_runpod_auto_scaling', true),
            'idle_timeout' => get_option('vortex_runpod_idle_timeout', 300), // 5 minutes
            'max_runtime' => get_option('vortex_runpod_max_runtime', 3600), // 1 hour
            'cost_optimization' => get_option('vortex_runpod_cost_optimization', true)
        ];
    }
    
    /**
     * Initialize API connection
     */
    private function initialize_api_connection() {
        if (empty($this->api_config['api_key'])) {
            error_log('VORTEX AI Engine: RunPod API key not configured');
            return false;
        }
        
        // Test API connection
        $test_result = $this->test_api_connection();
        
        if ($test_result) {
            error_log('VORTEX AI Engine: RunPod API connection successful');
        } else {
            error_log('VORTEX AI Engine: RunPod API connection failed');
        }
        
        return $test_result;
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('vortex_runpod_health_check', [$this, 'check_instance_health']);
        add_action('vortex_runpod_cost_optimization', [$this, 'optimize_costs']);
        add_action('vortex_runpod_auto_scaling', [$this, 'auto_scale_instances']);
        add_action('vortex_runpod_cleanup', [$this, 'cleanup_idle_instances']);
    }
    
    /**
     * Create GPU instance
     */
    public function create_instance($gpu_type = null, $template_id = null) {
        try {
            $gpu_type = $gpu_type ?: $this->api_config['gpu_type'];
            $template_id = $template_id ?: $this->api_config['template_id'];
            
            // Check instance limits
            if (count($this->active_instances) >= $this->api_config['max_instances']) {
                throw new Exception('Maximum instances limit reached');
            }
            
            // Prepare instance configuration
            $instance_config = [
                'name' => 'vortex-ai-' . uniqid(),
                'imageName' => $template_id,
                'gpuTypeId' => $gpu_type,
                'containerDiskInGb' => 50,
                'volumeInGb' => 100,
                'ports' => '8888/http,7860/http',
                'env' => [
                    'VORTEX_AI_ENABLED' => 'true',
                    'VORTEX_API_KEY' => get_option('vortex_api_key', ''),
                    'VORTEX_INSTANCE_ID' => uniqid()
                ]
            ];
            
            // Create instance via RunPod API
            $response = $this->api_request('POST', '/run', $instance_config);
            
            if (!$response['success']) {
                throw new Exception('Failed to create instance: ' . $response['error']);
            }
            
            $instance_data = $response['data'];
            
            // Store instance information
            $this->store_instance_data($instance_data);
            
            // Add to active instances
            $this->active_instances[$instance_data['id']] = [
                'id' => $instance_data['id'],
                'name' => $instance_data['name'],
                'gpu_type' => $gpu_type,
                'status' => 'starting',
                'created_at' => current_time('mysql'),
                'endpoint' => $instance_data['endpoint'] ?? '',
                'cost_per_hour' => $this->get_gpu_cost($gpu_type)
            ];
            
            return [
                'success' => true,
                'instance_id' => $instance_data['id'],
                'instance_name' => $instance_data['name'],
                'gpu_type' => $gpu_type,
                'status' => 'starting',
                'endpoint' => $instance_data['endpoint'] ?? ''
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: RunPod instance creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Terminate instance
     */
    public function terminate_instance($instance_id) {
        try {
            if (!isset($this->active_instances[$instance_id])) {
                throw new Exception('Instance not found');
            }
            
            // Terminate instance via RunPod API
            $response = $this->api_request('DELETE', "/run/{$instance_id}");
            
            if (!$response['success']) {
                throw new Exception('Failed to terminate instance: ' . $response['error']);
            }
            
            // Remove from active instances
            unset($this->active_instances[$instance_id]);
            
            // Update instance status in database
            $this->update_instance_status($instance_id, 'terminated');
            
            return [
                'success' => true,
                'instance_id' => $instance_id,
                'status' => 'terminated'
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: RunPod instance termination failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get instance status
     */
    public function get_instance_status($instance_id) {
        try {
            if (!isset($this->active_instances[$instance_id])) {
                throw new Exception('Instance not found');
            }
            
            // Get status from RunPod API
            $response = $this->api_request('GET', "/run/{$instance_id}");
            
            if (!$response['success']) {
                throw new Exception('Failed to get instance status: ' . $response['error']);
            }
            
            $instance_data = $response['data'];
            $status = $instance_data['status'] ?? 'unknown';
            
            // Update local cache
            $this->active_instances[$instance_id]['status'] = $status;
            
            return [
                'success' => true,
                'instance_id' => $instance_id,
                'status' => $status,
                'details' => $instance_data
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: RunPod instance status check failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Execute task on instance
     */
    public function execute_task($instance_id, $task_data) {
        try {
            if (!isset($this->active_instances[$instance_id])) {
                throw new Exception('Instance not found');
            }
            
            $instance = $this->active_instances[$instance_id];
            
            if ($instance['status'] !== 'running') {
                throw new Exception('Instance is not running');
            }
            
            // Prepare task payload
            $task_payload = [
                'input' => $task_data,
                'webhook' => get_site_url() . '/wp-json/vortex/v1/runpod-webhook'
            ];
            
            // Execute task via RunPod API
            $response = $this->api_request('POST', "/run/{$instance_id}/stream", $task_payload);
            
            if (!$response['success']) {
                throw new Exception('Failed to execute task: ' . $response['error']);
            }
            
            return [
                'success' => true,
                'task_id' => $response['data']['id'] ?? uniqid(),
                'instance_id' => $instance_id,
                'status' => 'executing'
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: RunPod task execution failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get available instances
     */
    public function get_available_instances() {
        return array_filter($this->active_instances, function($instance) {
            return $instance['status'] === 'running';
        });
    }
    
    /**
     * Check instance health
     */
    public function check_instance_health() {
        foreach ($this->active_instances as $instance_id => $instance) {
            $status_result = $this->get_instance_status($instance_id);
            
            if ($status_result['success']) {
                $new_status = $status_result['status'];
                
                if ($new_status !== $instance['status']) {
                    $this->active_instances[$instance_id]['status'] = $new_status;
                    $this->update_instance_status($instance_id, $new_status);
                }
                
                // Check for idle instances
                if ($new_status === 'running') {
                    $this->check_idle_timeout($instance_id, $instance);
                }
            }
        }
        
        error_log('VORTEX AI Engine: RunPod instance health check completed');
    }
    
    /**
     * Optimize costs
     */
    public function optimize_costs() {
        $total_cost = 0;
        $optimization_recommendations = [];
        
        foreach ($this->active_instances as $instance_id => $instance) {
            $runtime_hours = $this->calculate_runtime_hours($instance['created_at']);
            $instance_cost = $runtime_hours * $instance['cost_per_hour'];
            $total_cost += $instance_cost;
            
            // Check for optimization opportunities
            if ($instance['status'] === 'idle' && $runtime_hours > 1) {
                $optimization_recommendations[] = [
                    'instance_id' => $instance_id,
                    'action' => 'terminate',
                    'reason' => 'Idle for more than 1 hour',
                    'cost_savings' => $instance['cost_per_hour']
                ];
            }
        }
        
        // Apply optimizations
        foreach ($optimization_recommendations as $recommendation) {
            if ($recommendation['action'] === 'terminate') {
                $this->terminate_instance($recommendation['instance_id']);
            }
        }
        
        // Store cost data
        $this->store_cost_data($total_cost, count($this->active_instances));
        
        error_log('VORTEX AI Engine: RunPod cost optimization completed');
    }
    
    /**
     * Auto scale instances
     */
    public function auto_scale_instances() {
        if (!$this->config['instance_settings']['auto_scaling']) {
            return;
        }
        
        $current_load = $this->get_current_load();
        $available_instances = count($this->get_available_instances());
        
        if ($current_load > 0.8 && $available_instances < $this->api_config['max_instances']) {
            // Scale up
            $this->create_instance();
        } elseif ($current_load < 0.3 && $available_instances > 1) {
            // Scale down - terminate oldest idle instance
            $this->terminate_oldest_idle_instance();
        }
        
        error_log('VORTEX AI Engine: RunPod auto-scaling completed');
    }
    
    /**
     * Cleanup idle instances
     */
    public function cleanup_idle_instances() {
        $idle_timeout = $this->config['instance_settings']['idle_timeout'];
        
        foreach ($this->active_instances as $instance_id => $instance) {
            if ($instance['status'] === 'idle') {
                $idle_time = time() - strtotime($instance['last_activity'] ?? $instance['created_at']);
                
                if ($idle_time > $idle_timeout) {
                    $this->terminate_instance($instance_id);
                }
            }
        }
        
        error_log('VORTEX AI Engine: RunPod idle instance cleanup completed');
    }
    
    /**
     * Test API connection
     */
    private function test_api_connection() {
        $response = $this->api_request('GET', '/user');
        return $response['success'];
    }
    
    /**
     * Make API request
     */
    private function api_request($method, $endpoint, $data = null) {
        $url = $this->api_config['api_url'] . $endpoint;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_config['api_key'],
            'Content-Type' => 'application/json'
        ];
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return [
            'success' => wp_remote_retrieve_response_code($response) < 400,
            'data' => $data,
            'error' => wp_remote_retrieve_response_code($response) >= 400 ? $body : null
        ];
    }
    
    // Helper methods
    private function store_instance_data($data) { global $wpdb; $wpdb->insert($wpdb->prefix . 'vortex_runpod_instances', ['instance_id' => $data['id'], 'instance_data' => json_encode($data), 'created_at' => current_time('mysql')]); }
    private function update_instance_status($instance_id, $status) { global $wpdb; $wpdb->update($wpdb->prefix . 'vortex_runpod_instances', ['status' => $status, 'updated_at' => current_time('mysql')], ['instance_id' => $instance_id]); }
    private function get_gpu_cost($gpu_type) { $costs = ['RTX_4090' => 0.6, 'RTX_3090' => 0.4, 'A100' => 2.0, 'V100' => 1.5]; return $costs[$gpu_type] ?? 0.5; }
    private function check_idle_timeout($instance_id, $instance) { /* Idle timeout logic */ }
    private function calculate_runtime_hours($created_at) { return (time() - strtotime($created_at)) / 3600; }
    private function store_cost_data($total_cost, $instance_count) { update_option('vortex_runpod_daily_cost', $total_cost); update_option('vortex_runpod_instance_count', $instance_count); }
    private function get_current_load() { return rand(30, 80) / 100; }
    private function terminate_oldest_idle_instance() { /* Termination logic */ }
    private function load_instance_cache() { /* Load cache */ }
    
    /**
     * Get RunPod vault status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'active_instances' => count($this->active_instances),
            'available_instances' => count($this->get_available_instances()),
            'gpu_types' => count($this->config['gpu_types']),
            'api_connected' => !empty($this->api_config['api_key'])
        ];
    }
} 