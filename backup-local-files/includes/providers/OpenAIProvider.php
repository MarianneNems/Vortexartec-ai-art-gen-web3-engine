<?php
/**
 * OpenAI Provider for VORTEX AI Engine
 * Handles OpenAI API integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_OpenAI_Provider implements ModelProviderInterface {
    
    private $api_key;
    private $organization;
    private $project_id;
    private $base_url = 'https://api.openai.com/v1';
    private $rate_limits = [
        'requests_per_minute' => 3500,
        'tokens_per_minute' => 90000
    ];
    
    public function __construct($api_key, $organization = null, $project_id = null) {
        $this->api_key = $api_key;
        $this->organization = $organization;
        $this->project_id = $project_id;
    }
    
    /**
     * Generate content using OpenAI API
     */
    public function generate($prompt, $options = []) {
        $this->validate_api_key();
        
        // Sanitize inputs
        $prompt = sanitize_textarea_field($prompt);
        $options = $this->sanitize_options($options);
        
        // Default options
        $defaults = [
            'model' => 'gpt-4',
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];
        
        $options = array_merge($defaults, $options);
        
        // Build request
        $request_body = [
            'model' => $options['model'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
            'top_p' => $options['top_p'],
            'frequency_penalty' => $options['frequency_penalty'],
            'presence_penalty' => $options['presence_penalty']
        ];
        
        return $this->make_request('chat/completions', $request_body);
    }
    
    /**
     * Validate API key
     */
    private function validate_api_key() {
        if (empty($this->api_key) || !is_string($this->api_key)) {
            throw new Exception('OpenAI API key is required');
        }
        
        if (!preg_match('/^sk-[a-zA-Z0-9]+$/', $this->api_key)) {
            throw new Exception('Invalid OpenAI API key format');
        }
    }
    
    /**
     * Sanitize options
     */
    private function sanitize_options($options) {
        if (!is_array($options)) {
            return [];
        }
        
        $sanitized = [];
        $allowed_keys = ['model', 'max_tokens', 'temperature', 'top_p', 'frequency_penalty', 'presence_penalty'];
        
        foreach ($options as $key => $value) {
            if (in_array($key, $allowed_keys)) {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Make API request
     */
    private function make_request($endpoint, $data) {
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'VortexAI-WordPress-Plugin/2.1.0'
        ];
        
        if ($this->organization) {
            $headers['OpenAI-Organization'] = $this->organization;
        }
        
        if ($this->project_id) {
            $headers['OpenAI-Project'] = $this->project_id;
        }
        
        $response = wp_remote_post($this->base_url . '/' . $endpoint, [
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30,
            'method' => 'POST'
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('OpenAI API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (!$decoded) {
            throw new Exception('Invalid JSON response from OpenAI API');
        }
        
        if (isset($decoded['error'])) {
            throw new Exception('OpenAI API error: ' . $decoded['error']['message']);
        }
        
        return $decoded;
    }
    
    /**
     * Get available models
     */
    public function get_models() {
        try {
            $response = $this->make_request('models', []);
            return $response['data'] ?? [];
        } catch (Exception $e) {
            error_log('[VortexAI OpenAI] Error fetching models: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if provider is available
     */
    public function is_available() {
        return !empty($this->api_key);
    }
    
    /**
     * Get provider name
     */
    public function get_name() {
        return 'OpenAI';
    }
    
    /**
     * Get provider capabilities
     */
    public function get_capabilities() {
        return [
            'text_generation' => true,
            'chat' => true,
            'completion' => true,
            'embedding' => true,
            'image_generation' => false,
            'fine_tuning' => true
        ];
    }
} 