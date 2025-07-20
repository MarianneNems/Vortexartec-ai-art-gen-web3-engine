<?php
/**
 * VORTEX AI Engine - Gradio Client
 * 
 * AI model integration and Gradio interface management
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gradio Client Class
 * 
 * Handles AI model integration, Gradio interface management, and model inference
 */
class Vortex_Gradio_Client {
    
    /**
     * Client configuration
     */
    private $config = [
        'name' => 'VORTEX Gradio Client',
        'version' => '3.0.0',
        'api_version' => 'v1',
        'supported_models' => [
            'stable_diffusion',
            'midjourney_style',
            'dalle_style',
            'custom_model'
        ]
    ];
    
    /**
     * Gradio API configuration
     */
    private $api_config = [];
    
    /**
     * Model endpoints
     */
    private $model_endpoints = [];
    
    /**
     * Request cache
     */
    private $request_cache = [];
    
    /**
     * Initialize the Gradio client
     */
    public function init() {
        $this->load_configuration();
        $this->initialize_endpoints();
        $this->register_hooks();
        $this->test_connections();
        
        error_log('VORTEX AI Engine: Gradio Client initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->api_config = [
            'base_url' => get_option('vortex_gradio_base_url', ''),
            'api_key' => get_option('vortex_gradio_api_key', ''),
            'timeout' => get_option('vortex_gradio_timeout', 120),
            'retry_attempts' => get_option('vortex_gradio_retry_attempts', 3),
            'cache_enabled' => get_option('vortex_gradio_cache_enabled', true)
        ];
        
        $this->config['model_settings'] = [
            'stable_diffusion_endpoint' => get_option('vortex_sd_endpoint', ''),
            'midjourney_endpoint' => get_option('vortex_midjourney_endpoint', ''),
            'dalle_endpoint' => get_option('vortex_dalle_endpoint', ''),
            'custom_model_endpoint' => get_option('vortex_custom_model_endpoint', ''),
            'default_model' => get_option('vortex_default_model', 'stable_diffusion')
        ];
    }
    
    /**
     * Initialize endpoints
     */
    private function initialize_endpoints() {
        $this->model_endpoints = [
            'stable_diffusion' => [
                'url' => $this->config['model_settings']['stable_diffusion_endpoint'],
                'api_type' => 'gradio',
                'supported_tasks' => ['text_to_image', 'image_to_image', 'inpainting'],
                'max_resolution' => '1024x1024',
                'default_params' => [
                    'steps' => 50,
                    'guidance_scale' => 7.5,
                    'seed' => -1
                ]
            ],
            'midjourney_style' => [
                'url' => $this->config['model_settings']['midjourney_endpoint'],
                'api_type' => 'gradio',
                'supported_tasks' => ['text_to_image'],
                'max_resolution' => '1024x1024',
                'default_params' => [
                    'steps' => 30,
                    'guidance_scale' => 8.0,
                    'seed' => -1
                ]
            ],
            'dalle_style' => [
                'url' => $this->config['model_settings']['dalle_endpoint'],
                'api_type' => 'gradio',
                'supported_tasks' => ['text_to_image'],
                'max_resolution' => '1024x1024',
                'default_params' => [
                    'steps' => 40,
                    'guidance_scale' => 7.0,
                    'seed' => -1
                ]
            ],
            'custom_model' => [
                'url' => $this->config['model_settings']['custom_model_endpoint'],
                'api_type' => 'gradio',
                'supported_tasks' => ['text_to_image', 'custom_task'],
                'max_resolution' => '1024x1024',
                'default_params' => [
                    'steps' => 50,
                    'guidance_scale' => 7.5,
                    'seed' => -1
                ]
            ]
        ];
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_gradio_inference', [$this, 'handle_inference_request']);
        add_action('wp_ajax_vortex_gradio_status', [$this, 'handle_status_request']);
        add_action('vortex_gradio_health_check', [$this, 'check_endpoint_health']);
        add_action('vortex_gradio_cache_cleanup', [$this, 'cleanup_cache']);
    }
    
    /**
     * Test connections
     */
    private function test_connections() {
        foreach ($this->model_endpoints as $model_name => $endpoint) {
            if (!empty($endpoint['url'])) {
                $health_result = $this->check_endpoint_health($model_name);
                
                if ($health_result['success']) {
                    error_log("VORTEX AI Engine: Gradio endpoint {$model_name} is healthy");
                } else {
                    error_log("VORTEX AI Engine: Gradio endpoint {$model_name} is unhealthy");
                }
            }
        }
    }
    
    /**
     * Generate image using AI model
     */
    public function generate_image($prompt, $model = null, $parameters = []) {
        try {
            $model = $model ?: $this->config['model_settings']['default_model'];
            
            if (!isset($this->model_endpoints[$model])) {
                throw new Exception('Unsupported model: ' . $model);
            }
            
            $endpoint = $this->model_endpoints[$model];
            
            if (empty($endpoint['url'])) {
                throw new Exception('Model endpoint not configured: ' . $model);
            }
            
            // Check cache first
            $cache_key = $this->generate_cache_key($prompt, $model, $parameters);
            $cached_result = $this->get_cached_result($cache_key);
            
            if ($cached_result) {
                return [
                    'success' => true,
                    'image_data' => $cached_result,
                    'cached' => true,
                    'model' => $model
                ];
            }
            
            // Prepare request parameters
            $request_params = array_merge($endpoint['default_params'], $parameters);
            $request_params['prompt'] = $prompt;
            
            // Make inference request
            $response = $this->make_inference_request($endpoint['url'], $request_params);
            
            if (!$response['success']) {
                throw new Exception('Inference failed: ' . $response['error']);
            }
            
            $image_data = $response['data'];
            
            // Cache the result
            $this->cache_result($cache_key, $image_data);
            
            return [
                'success' => true,
                'image_data' => $image_data,
                'model' => $model,
                'parameters' => $request_params,
                'processing_time' => $response['processing_time'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Gradio image generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process image-to-image transformation
     */
    public function process_image_to_image($input_image, $prompt, $model = null, $parameters = []) {
        try {
            $model = $model ?: $this->config['model_settings']['default_model'];
            
            if (!isset($this->model_endpoints[$model])) {
                throw new Exception('Unsupported model: ' . $model);
            }
            
            $endpoint = $this->model_endpoints[$model];
            
            if (!in_array('image_to_image', $endpoint['supported_tasks'])) {
                throw new Exception('Model does not support image-to-image: ' . $model);
            }
            
            // Prepare request parameters
            $request_params = array_merge($endpoint['default_params'], $parameters);
            $request_params['prompt'] = $prompt;
            $request_params['init_image'] = $input_image;
            
            // Make inference request
            $response = $this->make_inference_request($endpoint['url'], $request_params, 'image_to_image');
            
            if (!$response['success']) {
                throw new Exception('Image-to-image processing failed: ' . $response['error']);
            }
            
            return [
                'success' => true,
                'image_data' => $response['data'],
                'model' => $model,
                'parameters' => $request_params,
                'processing_time' => $response['processing_time'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Gradio image-to-image processing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process inpainting
     */
    public function process_inpainting($input_image, $mask_image, $prompt, $model = null, $parameters = []) {
        try {
            $model = $model ?: $this->config['model_settings']['default_model'];
            
            if (!isset($this->model_endpoints[$model])) {
                throw new Exception('Unsupported model: ' . $model);
            }
            
            $endpoint = $this->model_endpoints[$model];
            
            if (!in_array('inpainting', $endpoint['supported_tasks'])) {
                throw new Exception('Model does not support inpainting: ' . $model);
            }
            
            // Prepare request parameters
            $request_params = array_merge($endpoint['default_params'], $parameters);
            $request_params['prompt'] = $prompt;
            $request_params['init_image'] = $input_image;
            $request_params['mask_image'] = $mask_image;
            
            // Make inference request
            $response = $this->make_inference_request($endpoint['url'], $request_params, 'inpainting');
            
            if (!$response['success']) {
                throw new Exception('Inpainting processing failed: ' . $response['error']);
            }
            
            return [
                'success' => true,
                'image_data' => $response['data'],
                'model' => $model,
                'parameters' => $request_params,
                'processing_time' => $response['processing_time'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Gradio inpainting processing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle inference request AJAX
     */
    public function handle_inference_request() {
        check_ajax_referer('vortex_gradio_nonce', 'nonce');
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? '');
        $parameters = json_decode(stripslashes($_POST['parameters'] ?? '{}'), true);
        $task_type = sanitize_text_field($_POST['task_type'] ?? 'text_to_image');
        
        if (empty($prompt)) {
            wp_send_json_error(['message' => 'Prompt is required']);
        }
        
        switch ($task_type) {
            case 'text_to_image':
                $result = $this->generate_image($prompt, $model, $parameters);
                break;
            case 'image_to_image':
                $input_image = $_POST['input_image'] ?? '';
                $result = $this->process_image_to_image($input_image, $prompt, $model, $parameters);
                break;
            case 'inpainting':
                $input_image = $_POST['input_image'] ?? '';
                $mask_image = $_POST['mask_image'] ?? '';
                $result = $this->process_inpainting($input_image, $mask_image, $prompt, $model, $parameters);
                break;
            default:
                wp_send_json_error(['message' => 'Unsupported task type']);
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle status request AJAX
     */
    public function handle_status_request() {
        check_ajax_referer('vortex_gradio_nonce', 'nonce');
        
        $model = sanitize_text_field($_POST['model'] ?? '');
        
        if (empty($model)) {
            $model = $this->config['model_settings']['default_model'];
        }
        
        $status = $this->get_model_status($model);
        wp_send_json_success($status);
    }
    
    /**
     * Check endpoint health
     */
    public function check_endpoint_health($model = null) {
        if ($model) {
            return $this->check_single_endpoint_health($model);
        }
        
        $health_results = [];
        
        foreach ($this->model_endpoints as $model_name => $endpoint) {
            if (!empty($endpoint['url'])) {
                $health_results[$model_name] = $this->check_single_endpoint_health($model_name);
            }
        }
        
        return $health_results;
    }
    
    /**
     * Cleanup cache
     */
    public function cleanup_cache() {
        $cache_dir = VORTEX_AI_ENGINE_PLUGIN_PATH . 'cache/gradio/';
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.cache');
            $cutoff_time = time() - (24 * 60 * 60); // 24 hours
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
        
        // Clear request cache
        $this->request_cache = [];
        
        error_log('VORTEX AI Engine: Gradio cache cleanup completed');
    }
    
    /**
     * Make inference request
     */
    private function make_inference_request($endpoint_url, $parameters, $task_type = 'text_to_image') {
        $start_time = microtime(true);
        
        $headers = [
            'Content-Type' => 'application/json'
        ];
        
        if (!empty($this->api_config['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $this->api_config['api_key'];
        }
        
        $request_data = [
            'task_type' => $task_type,
            'parameters' => $parameters
        ];
        
        $response = wp_remote_post($endpoint_url, [
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => $this->api_config['timeout']
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $processing_time = microtime(true) - $start_time;
        
        return [
            'success' => wp_remote_retrieve_response_code($response) < 400,
            'data' => $data,
            'processing_time' => $processing_time,
            'error' => wp_remote_retrieve_response_code($response) >= 400 ? $body : null
        ];
    }
    
    /**
     * Generate cache key
     */
    private function generate_cache_key($prompt, $model, $parameters) {
        $key_data = [
            'prompt' => $prompt,
            'model' => $model,
            'parameters' => $parameters
        ];
        
        return 'gradio_' . md5(json_encode($key_data));
    }
    
    /**
     * Get cached result
     */
    private function get_cached_result($cache_key) {
        if (!$this->api_config['cache_enabled']) {
            return null;
        }
        
        $cache_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'cache/gradio/' . $cache_key . '.cache';
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 3600) {
            return file_get_contents($cache_file);
        }
        
        return null;
    }
    
    /**
     * Cache result
     */
    private function cache_result($cache_key, $data) {
        if (!$this->api_config['cache_enabled']) {
            return;
        }
        
        $cache_dir = VORTEX_AI_ENGINE_PLUGIN_PATH . 'cache/gradio/';
        
        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        $cache_file = $cache_dir . $cache_key . '.cache';
        file_put_contents($cache_file, $data);
    }
    
    /**
     * Get model status
     */
    private function get_model_status($model) {
        if (!isset($this->model_endpoints[$model])) {
            return [
                'success' => false,
                'error' => 'Model not found'
            ];
        }
        
        $endpoint = $this->model_endpoints[$model];
        
        if (empty($endpoint['url'])) {
            return [
                'success' => false,
                'error' => 'Endpoint not configured'
            ];
        }
        
        $health_result = $this->check_single_endpoint_health($model);
        
        return [
            'success' => true,
            'model' => $model,
            'endpoint' => $endpoint['url'],
            'status' => $health_result['status'],
            'supported_tasks' => $endpoint['supported_tasks'],
            'max_resolution' => $endpoint['max_resolution']
        ];
    }
    
    /**
     * Check single endpoint health
     */
    private function check_single_endpoint_health($model) {
        $endpoint = $this->model_endpoints[$model];
        
        if (empty($endpoint['url'])) {
            return [
                'success' => false,
                'status' => 'not_configured',
                'error' => 'Endpoint not configured'
            ];
        }
        
        $response = wp_remote_get($endpoint['url'] . '/health', [
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'status' => 'unhealthy',
                'error' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'success' => $status_code < 400,
            'status' => $status_code < 400 ? 'healthy' : 'unhealthy',
            'response_code' => $status_code
        ];
    }
    
    /**
     * Get Gradio client status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'supported_models' => count($this->config['supported_models']),
            'configured_endpoints' => count(array_filter($this->model_endpoints, function($e) { return !empty($e['url']); })),
            'cache_enabled' => $this->api_config['cache_enabled'],
            'cache_size' => $this->get_cache_size()
        ];
    }
    
    /**
     * Get cache size
     */
    private function get_cache_size() {
        $cache_dir = VORTEX_AI_ENGINE_PLUGIN_PATH . 'cache/gradio/';
        
        if (!is_dir($cache_dir)) {
            return 0;
        }
        
        $files = glob($cache_dir . '*.cache');
        return count($files);
    }
} 