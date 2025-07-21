<?php
/**
 * VORTEX AI Engine - Configuration Class
 * 
 * Manages plugin configuration, settings, and environment variables
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX Configuration Class
 */
class VORTEX_Config {
    
    private static $instance = null;
    private $config = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_configuration();
    }
    
    private function load_configuration() {
        // Load from environment variables
        $this->config = array(
            'aws_access_key' => getenv('AWS_ACCESS_KEY'),
            'aws_secret_key' => getenv('AWS_SECRET_KEY'),
            'aws_region' => getenv('AWS_REGION') ?: 'us-east-1',
            'solana_private_key' => getenv('SOLANA_PRIVATE_KEY'),
            'tola_token_address' => getenv('TOLA_TOKEN_ADDRESS'),
            'vortex_api_key' => getenv('VORTEX_API_KEY'),
            'vortex_api_secret' => getenv('VORTEX_API_SECRET'),
            'github_token' => getenv('GITHUB_TOKEN'),
            'encryption_key' => getenv('VORTEX_ENCRYPTION_KEY')
        );
        
        // Load from WordPress options
        $this->config['debug_mode'] = get_option('vortex_ai_engine_debug_mode', false);
        $this->config['logging_enabled'] = get_option('vortex_ai_engine_logging_enabled', true);
        $this->config['github_integration'] = get_option('vortex_ai_engine_github_integration', false);
    }
    
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
    
    public function testConnection() {
        return array(
            'status' => 'success',
            'message' => 'VORTEX Config loaded successfully',
            'config_keys' => array_keys($this->config)
        );
    }
} 