<?php
/**
 * VORTEX AI Engine - Secure Configuration Manager
 * Handles all configuration securely using environment variables and WordPress options
 */

class VortexAIEngine_Config {
    private static $config = [];
    private static $initialized = false;
    
    /**
     * Initialize configuration
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Load default configuration
        self::$config = [
            'debug_mode' => false,
            'api_timeout' => 30,
            'rate_limit' => 100,
            'max_retries' => 3,
            'cache_duration' => 3600,
            'security_level' => 'high'
        ];
        
        self::$initialized = true;
    }
    
    /**
     * Get configuration value
     * Priority: Environment Variable > WordPress Option > Default
     */
    public static function get($key, $default = null) {
        self::init();
        
        // Check environment variables first (most secure)
        $env_key = 'VORTEX_' . strtoupper(str_replace('-', '_', $key));
        $env_value = getenv($env_key);
        if ($env_value !== false) {
            return self::sanitize_value($env_value);
        }
        
        // Check WordPress options
        $wp_key = 'vortex_' . str_replace('-', '_', $key);
        $wp_value = get_option($wp_key);
        if ($wp_value !== false) {
            return self::sanitize_value($wp_value);
        }
        
        // Check static config
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        
        return $default;
    }
    
    /**
     * Set configuration value (WordPress option only)
     */
    public static function set($key, $value) {
        $wp_key = 'vortex_' . str_replace('-', '_', $key);
        update_option($wp_key, self::sanitize_value($value));
    }
    
    /**
     * Get API key securely
     */
    public static function get_api_key($service) {
        $key = self::get($service . '_api_key');
        if (!$key) {
            error_log("VORTEX AI Engine: Missing API key for service: $service");
            return false;
        }
        
        // Validate API key format
        if (!self::validate_api_key($service, $key)) {
            error_log("VORTEX AI Engine: Invalid API key format for service: $service");
            return false;
        }
        
        return $key;
    }
    
    /**
     * Validate API key format
     */
    public static function validate_api_key($service, $key) {
        if (empty($key) || strlen($key) < 20) {
            return false;
        }
        
        // Service-specific validation
        switch ($service) {
            case 'openai':
                return strpos($key, 'sk-') === 0;
            case 'anthropic':
                return strpos($key, 'sk-ant-') === 0;
            case 'aws':
                return strlen($key) >= 20 && strlen($key) <= 40;
            case 'google':
                return strpos($key, 'AIza') === 0;
            default:
                return strlen($key) >= 20;
        }
    }
    
    /**
     * Get database configuration
     */
    public static function get_database_config() {
        return [
            'host' => self::get('db_host', DB_HOST),
            'name' => self::get('db_name', DB_NAME),
            'user' => self::get('db_user', DB_USER),
            'password' => self::get('db_password', DB_PASSWORD),
            'charset' => self::get('db_charset', DB_CHARSET),
            'collate' => self::get('db_collate', DB_COLLATE)
        ];
    }
    
    /**
     * Get AWS configuration
     */
    public static function get_aws_config() {
        return [
            'access_key' => self::get_api_key('aws_access'),
            'secret_key' => self::get_api_key('aws_secret'),
            'region' => self::get('aws_region', 'us-east-1'),
            'bucket' => self::get('aws_bucket'),
            'endpoint' => self::get('aws_endpoint')
        ];
    }
    
    /**
     * Get AI service configuration
     */
    public static function get_ai_config() {
        return [
            'openai_key' => self::get_api_key('openai'),
            'anthropic_key' => self::get_api_key('anthropic'),
            'google_key' => self::get_api_key('google'),
            'model' => self::get('ai_model', 'gpt-4'),
            'temperature' => self::get('ai_temperature', 0.7),
            'max_tokens' => self::get('ai_max_tokens', 2000)
        ];
    }
    
    /**
     * Get security configuration
     */
    public static function get_security_config() {
        return [
            'rate_limit' => self::get('rate_limit', 100),
            'max_requests_per_minute' => self::get('max_requests_per_minute', 60),
            'session_timeout' => self::get('session_timeout', 3600),
            'require_https' => self::get('require_https', true),
            'enable_csrf' => self::get('enable_csrf', true),
            'enable_xss_protection' => self::get('enable_xss_protection', true)
        ];
    }
    
    /**
     * Sanitize configuration value
     */
    private static function sanitize_value($value) {
        if (is_string($value)) {
            $value = trim($value);
            // Remove any potential script tags
            $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        }
        return $value;
    }
    
    /**
     * Check if configuration is secure
     */
    public static function is_secure() {
        $required_keys = [
            'openai_api_key',
            'aws_access_api_key',
            'aws_secret_api_key'
        ];
        
        foreach ($required_keys as $key) {
            if (!self::get_api_key(str_replace('_api_key', '', $key))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get configuration status
     */
    public static function get_status() {
        $status = [
            'secure' => self::is_secure(),
            'environment_variables' => [],
            'wordpress_options' => [],
            'missing_keys' => []
        ];
        
        // Check environment variables
        $env_vars = [
            'VORTEX_OPENAI_API_KEY',
            'VORTEX_AWS_ACCESS_API_KEY',
            'VORTEX_AWS_SECRET_API_KEY',
            'VORTEX_ANTHROPIC_API_KEY'
        ];
        
        foreach ($env_vars as $env_var) {
            $value = getenv($env_var);
            $status['environment_variables'][$env_var] = $value ? 'Set' : 'Not Set';
        }
        
        // Check WordPress options
        $wp_options = [
            'vortex_openai_api_key',
            'vortex_aws_access_api_key',
            'vortex_aws_secret_api_key',
            'vortex_anthropic_api_key'
        ];
        
        foreach ($wp_options as $option) {
            $value = get_option($option);
            $status['wordpress_options'][$option] = $value ? 'Set' : 'Not Set';
        }
        
        return $status;
    }
    
    /**
     * Export configuration (for debugging only)
     */
    public static function export_config() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $config = [
            'database' => self::get_database_config(),
            'aws' => self::get_aws_config(),
            'ai' => self::get_ai_config(),
            'security' => self::get_security_config(),
            'status' => self::get_status()
        ];
        
        // Remove sensitive data
        unset($config['aws']['access_key']);
        unset($config['aws']['secret_key']);
        unset($config['ai']['openai_key']);
        unset($config['ai']['anthropic_key']);
        unset($config['ai']['google_key']);
        
        return $config;
    }
}

// Initialize configuration
VortexAIEngine_Config::init(); 