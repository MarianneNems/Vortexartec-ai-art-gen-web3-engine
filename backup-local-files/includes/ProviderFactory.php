<?php
/**
 * VORTEX AI Engine - Provider Factory
 * Manages AI service providers and their configurations
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_ProviderFactory {
    private static $instance = null;
    private $providers = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_default_providers();
    }
    
    private function register_default_providers() {
        // Register OpenAI provider
        $this->register_provider('openai', [
            'name' => 'OpenAI',
            'class' => 'VortexAIEngine_OpenAI_Provider',
            'config' => [
                'api_key' => get_option('vortex_openai_api_key', ''),
                'model' => 'gpt-4',
                'max_tokens' => 4000
            ]
        ]);
        
        // Register Anthropic provider
        $this->register_provider('anthropic', [
            'name' => 'Anthropic',
            'class' => 'VortexAIEngine_Anthropic_Provider',
            'config' => [
                'api_key' => get_option('vortex_anthropic_api_key', ''),
                'model' => 'claude-3-sonnet-20240229',
                'max_tokens' => 4000
            ]
        ]);
        
        // Register Google provider
        $this->register_provider('google', [
            'name' => 'Google AI',
            'class' => 'VortexAIEngine_Google_Provider',
            'config' => [
                'api_key' => get_option('vortex_google_api_key', ''),
                'model' => 'gemini-pro',
                'max_tokens' => 4000
            ]
        ]);
        
        // Register local provider
        $this->register_provider('local', [
            'name' => 'Local AI',
            'class' => 'VortexAIEngine_Local_Provider',
            'config' => [
                'endpoint' => get_option('vortex_local_endpoint', 'http://localhost:11434'),
                'model' => 'llama2',
                'max_tokens' => 4000
            ]
        ]);
    }
    
    public function register_provider($id, $config) {
        $this->providers[$id] = $config;
    }
    
    public function get_provider($id) {
        if (!isset($this->providers[$id])) {
            throw new Exception("Provider '$id' not found");
        }
        
        $provider_config = $this->providers[$id];
        $class_name = $provider_config['class'];
        
        if (!class_exists($class_name)) {
            throw new Exception("Provider class '$class_name' not found");
        }
        
        return new $class_name($provider_config['config']);
    }
    
    public function get_available_providers() {
        return array_keys($this->providers);
    }
    
    public function get_provider_config($id) {
        return isset($this->providers[$id]) ? $this->providers[$id] : null;
    }
    
    public function get_best_provider($task_type = 'general') {
        // Logic to select the best provider based on task type and availability
        $priority_order = ['openai', 'anthropic', 'google', 'local'];
        
        foreach ($priority_order as $provider_id) {
            if (isset($this->providers[$provider_id])) {
                $config = $this->providers[$provider_id];
                if (!empty($config['config']['api_key']) || $provider_id === 'local') {
                    return $this->get_provider($provider_id);
                }
            }
        }
        
        throw new Exception("No available AI providers found");
    }
}

// Base provider class
abstract class VortexAIEngine_BaseProvider {
    protected $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    abstract public function generate_text($prompt, $options = []);
    abstract public function generate_image($prompt, $options = []);
    abstract public function analyze_content($content, $options = []);
    
    protected function validate_config() {
        if (empty($this->config)) {
            throw new Exception("Provider configuration is required");
        }
    }
    
    protected function log_request($method, $params, $response, $error = null) {
        error_log(sprintf(
            '[VortexAI] %s Provider Request - Method: %s, Params: %s, Response: %s, Error: %s',
            get_class($this),
            $method,
            json_encode($params),
            json_encode($response),
            $error
        ));
    }
}

// Initialize the provider factory
VortexAIEngine_ProviderFactory::getInstance(); 