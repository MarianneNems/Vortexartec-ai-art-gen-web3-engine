<?php
/**
 * VORTEX AI Engine - HURAII Agent
 * 
 * GPU-powered generative AI agent for Stable Diffusion and image generation
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HURAII Agent Class
 * 
 * Handles GPU-powered image generation, Stable Diffusion, and creative AI tasks
 */
class Vortex_Huraii_Agent {
    
    /**
     * Agent configuration
     */
    private $config = [
        'name' => 'HURAII',
        'type' => 'GPU',
        'capabilities' => ['image_generation', 'style_transfer', 'creative_ai'],
        'gpu_enabled' => true,
        'api_endpoints' => [],
        'models' => []
    ];
    
    /**
     * Performance metrics
     */
    private $metrics = [
        'generations_completed' => 0,
        'total_processing_time' => 0,
        'average_response_time' => 0,
        'success_rate' => 100
    ];
    
    /**
     * Current generation queue
     */
    private $generation_queue = [];
    
    /**
     * Initialize the HURAII agent
     */
    public function init() {
        $this->load_configuration();
        $this->register_hooks();
        $this->initialize_gpu_connection();
        
        error_log('VORTEX AI Engine: HURAII Agent initialized');
    }
    
    /**
     * Load agent configuration
     */
    private function load_configuration() {
        $this->config['api_endpoints'] = [
            'stable_diffusion' => get_option('vortex_huraii_sd_endpoint', ''),
            'runpod' => get_option('vortex_runpod_endpoint', ''),
            'gradio' => get_option('vortex_gradio_endpoint', '')
        ];
        
        $this->config['models'] = [
            'stable_diffusion_v1_5' => 'stabilityai/stable-diffusion-2-1',
            'stable_diffusion_v2_1' => 'stabilityai/stable-diffusion-2-1',
            'stable_diffusion_xl' => 'stabilityai/stable-diffusion-xl-base-1.0',
            'midjourney_style' => 'midjourney/midjourney-v5',
            'custom_model' => get_option('vortex_custom_model_path', '')
        ];
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_generate_image', [$this, 'handle_image_generation']);
        add_action('wp_ajax_nopriv_vortex_generate_image', [$this, 'handle_image_generation']);
        add_action('vortex_daily_art_generation', [$this, 'generate_daily_art']);
        add_action('vortex_huraii_optimization', [$this, 'optimize_performance']);
    }
    
    /**
     * Initialize GPU connection
     */
    private function initialize_gpu_connection() {
        if (VORTEX_HURAII_GPU_ENABLED) {
            // Initialize RunPod connection if available
            if (!empty($this->config['api_endpoints']['runpod'])) {
                $this->test_gpu_connection();
            }
        }
    }
    
    /**
     * Test GPU connection
     */
    private function test_gpu_connection() {
        try {
            $response = wp_remote_get($this->config['api_endpoints']['runpod'] . '/health');
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                error_log('VORTEX AI Engine: HURAII GPU connection successful');
                return true;
            }
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: HURAII GPU connection failed: ' . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Handle image generation request
     */
    public function handle_image_generation() {
        check_ajax_referer('vortex_generation_nonce', 'nonce');
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $style = sanitize_text_field($_POST['style'] ?? 'realistic');
        $size = sanitize_text_field($_POST['size'] ?? '512x512');
        $model = sanitize_text_field($_POST['model'] ?? 'stable_diffusion_v2_1');
        
        if (empty($prompt)) {
            wp_send_json_error(['message' => 'Prompt is required']);
        }
        
        $result = $this->generate_image($prompt, $style, $size, $model);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Generate image using AI
     */
    public function generate_image($prompt, $style = 'realistic', $size = '512x512', $model = 'stable_diffusion_v2_1') {
        $start_time = microtime(true);
        
        try {
            // Enhance prompt with style
            $enhanced_prompt = $this->enhance_prompt($prompt, $style);
            
            // Select best model for the task
            $selected_model = $this->select_model($model, $style);
            
            // Generate image
            $image_data = $this->call_generation_api($enhanced_prompt, $size, $selected_model);
            
            // Process and save image
            $image_url = $this->process_and_save_image($image_data, $prompt);
            
            // Update metrics
            $this->update_metrics($start_time);
            
            return [
                'success' => true,
                'image_url' => $image_url,
                'prompt' => $enhanced_prompt,
                'model' => $selected_model,
                'generation_time' => microtime(true) - $start_time
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: HURAII generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enhance prompt with style and artistic elements
     */
    private function enhance_prompt($prompt, $style) {
        $style_enhancements = [
            'realistic' => 'photorealistic, high quality, detailed, 8k',
            'artistic' => 'artistic, creative, vibrant colors, masterpiece',
            'anime' => 'anime style, cel shading, vibrant, detailed',
            'abstract' => 'abstract art, modern, contemporary, artistic',
            'vintage' => 'vintage style, retro, classic, nostalgic',
            'futuristic' => 'futuristic, sci-fi, advanced technology, neon'
        ];
        
        $enhancement = $style_enhancements[$style] ?? $style_enhancements['realistic'];
        
        return $prompt . ', ' . $enhancement;
    }
    
    /**
     * Select best model for the task
     */
    private function select_model($requested_model, $style) {
        // Model selection logic based on style and performance
        $model_mapping = [
            'anime' => 'stable_diffusion_v2_1',
            'realistic' => 'stable_diffusion_xl',
            'artistic' => 'stable_diffusion_v2_1',
            'abstract' => 'stable_diffusion_v1_5'
        ];
        
        return $model_mapping[$style] ?? $requested_model;
    }
    
    /**
     * Call generation API
     */
    private function call_generation_api($prompt, $size, $model) {
        $endpoint = $this->config['api_endpoints']['runpod'];
        
        if (empty($endpoint)) {
            throw new Exception('No GPU endpoint configured');
        }
        
        $payload = [
            'prompt' => $prompt,
            'width' => (int) explode('x', $size)[0],
            'height' => (int) explode('x', $size)[1],
            'model' => $model,
            'steps' => 50,
            'guidance_scale' => 7.5,
            'seed' => rand(1, 999999999)
        ];
        
        $response = wp_remote_post($endpoint . '/generate', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . get_option('vortex_runpod_api_key', '')
            ],
            'body' => json_encode($payload),
            'timeout' => 120
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || !isset($data['image'])) {
            throw new Exception('Invalid API response');
        }
        
        return $data;
    }
    
    /**
     * Process and save generated image
     */
    private function process_and_save_image($image_data, $prompt) {
        // Decode base64 image
        $image_base64 = $image_data['image'];
        $image_binary = base64_decode($image_base64);
        
        // Create unique filename
        $filename = 'vortex_generated_' . time() . '_' . rand(1000, 9999) . '.png';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Save image
        file_put_contents($file_path, $image_binary);
        
        // Create WordPress attachment
        $attachment = [
            'post_mime_type' => 'image/png',
            'post_title' => 'VORTEX AI Generated: ' . substr($prompt, 0, 50),
            'post_content' => $prompt,
            'post_status' => 'inherit'
        ];
        
        $attach_id = wp_insert_attachment($attachment, $file_path);
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return wp_get_attachment_url($attach_id);
    }
    
    /**
     * Generate daily art for TOLA-ART system
     */
    public function generate_daily_art() {
        $themes = [
            'nature' => 'Beautiful landscape with mountains and sunset',
            'abstract' => 'Abstract geometric patterns with vibrant colors',
            'portrait' => 'Elegant portrait with artistic lighting',
            'fantasy' => 'Fantasy world with magical creatures',
            'urban' => 'Modern cityscape with neon lights'
        ];
        
        $selected_theme = array_rand($themes);
        $prompt = $themes[$selected_theme];
        
        $result = $this->generate_image($prompt, 'artistic', '1024x1024', 'stable_diffusion_xl');
        
        if ($result['success']) {
            // Store in daily art database
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'vortex_daily_art',
                [
                    'prompt' => $prompt,
                    'image_url' => $result['image_url'],
                    'theme' => $selected_theme,
                    'generated_at' => current_time('mysql'),
                    'model_used' => $result['model']
                ]
            );
            
            error_log('VORTEX AI Engine: Daily art generated successfully');
        }
    }
    
    /**
     * Optimize performance
     */
    public function optimize_performance() {
        // Analyze performance metrics
        $avg_response_time = $this->metrics['total_processing_time'] / max($this->metrics['generations_completed'], 1);
        
        // Adjust settings based on performance
        if ($avg_response_time > 30) {
            // Reduce quality for faster generation
            update_option('vortex_generation_quality', 'fast');
        } elseif ($avg_response_time < 10) {
            // Increase quality for better results
            update_option('vortex_generation_quality', 'high');
        }
        
        // Clear old cache
        $this->clear_old_cache();
        
        error_log('VORTEX AI Engine: HURAII performance optimized');
    }
    
    /**
     * Update performance metrics
     */
    private function update_metrics($start_time) {
        $this->metrics['generations_completed']++;
        $this->metrics['total_processing_time'] += microtime(true) - $start_time;
        $this->metrics['average_response_time'] = $this->metrics['total_processing_time'] / $this->metrics['generations_completed'];
    }
    
    /**
     * Clear old cache
     */
    private function clear_old_cache() {
        $cache_dir = VORTEX_AI_ENGINE_PLUGIN_PATH . 'cache/';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.tmp');
            foreach ($files as $file) {
                if (filemtime($file) < time() - 3600) { // Older than 1 hour
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Get agent status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'type' => $this->config['type'],
            'gpu_enabled' => $this->config['gpu_enabled'],
            'metrics' => $this->metrics,
            'queue_size' => count($this->generation_queue)
        ];
    }
    
    /**
     * Get agent capabilities
     */
    public function get_capabilities() {
        return $this->config['capabilities'];
    }
} 