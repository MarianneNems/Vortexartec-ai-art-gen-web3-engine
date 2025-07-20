<?php
/**
 * VORTEX Secure API Keys Manager
 * Handles encrypted storage and retrieval of API keys
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexSecureAPIKeys {
    private $encryption_key;
    private $cipher_method = 'AES-256-CBC';
    private $supported_keys = [
        'OPENAI_API_KEY',
        'CLAUDE_API_KEY', 
        'GEMINI_API_KEY',
        'GROK_API_KEY',
        'DALL_E_API_KEY',
        'SORA_API_KEY',
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY'
    ];
    
    // CPU vs GPU API key mapping
    private $cpu_keys = [
        'OPENAI_API_KEY',
        'CLAUDE_API_KEY',
        'GEMINI_API_KEY',
        'GROK_API_KEY',
        'DALL_E_API_KEY',
        'SORA_API_KEY'
    ];
    
    private $gpu_keys = [
        // No GPU keys currently configured
    ];
    
    public function __construct() {
        $this->encryption_key = $this->get_or_create_encryption_key();
    }
    
    /**
     * Get or create encryption key
     */
    private function get_or_create_encryption_key() {
        $key = get_option('vortex_encryption_key');
        
        if (!$key) {
            $key = base64_encode(random_bytes(32));
            update_option('vortex_encryption_key', $key);
        }
        
        return base64_decode($key);
    }
    
    /**
     * Encrypt API key
     */
    private function encrypt_key($plaintext) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($plaintext, $this->cipher_method, $this->encryption_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt API key
     */
    private function decrypt_key($encrypted_data) {
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, $this->cipher_method, $this->encryption_key, 0, $iv);
    }
    
    /**
     * Resolve environment variables in configuration
     */
    private function resolve_env_vars($value) {
        if (is_string($value) && preg_match('/^\$\{([^}]+)\}$/', $value, $matches)) {
            $env_var = $matches[1];
            $env_value = getenv($env_var);
            if ($env_value === false) {
                // Try WordPress constant
                if (defined($env_var)) {
                    return constant($env_var);
                }
                // Try WordPress option
                $option_value = get_option('vortex_' . strtolower($env_var));
                if ($option_value) {
                    return $option_value;
                }
                error_log("VORTEX Security Warning: Environment variable {$env_var} not found");
                return '';
            }
            return $env_value;
        }
        return $value;
    }
    
    /**
     * Store encrypted API key
     */
    public function store_api_key($key_name, $key_value) {
        if (!in_array($key_name, $this->supported_keys)) {
            throw new Exception("Unsupported API key: {$key_name}");
        }
        
        if (empty($key_value)) {
            throw new Exception("API key value cannot be empty");
        }
        
        $encrypted_key = $this->encrypt_key($key_value);
        $option_name = 'vortex_encrypted_' . strtolower($key_name);
        
        $success = update_option($option_name, $encrypted_key);
        
        if ($success) {
            // Log the storage (without the actual key)
            error_log("VORTEX: Encrypted API key stored for {$key_name}");
            
            // Update last modified timestamp
            update_option('vortex_api_keys_last_modified', current_time('mysql'));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Retrieve decrypted API key
     */
    public function get_api_key($key_name) {
        if (!in_array($key_name, $this->supported_keys)) {
            throw new Exception("Unsupported API key: {$key_name}");
        }
        
        // First try to get from encrypted storage
        $option_name = 'vortex_encrypted_' . strtolower($key_name);
        $encrypted_key = get_option($option_name);
        
        if ($encrypted_key) {
            try {
                return $this->decrypt_key($encrypted_key);
            } catch (Exception $e) {
                error_log("VORTEX: Failed to decrypt API key for {$key_name}: " . $e->getMessage());
            }
        }
        
        // Fallback to environment variable
        $env_value = getenv($key_name);
        if ($env_value && !empty($env_value)) {
            return $env_value;
        }
        
        // Try WordPress constant
        if (defined($key_name)) {
            return constant($key_name);
        }
        
        // Try WordPress option
        $option_value = get_option('vortex_' . strtolower($key_name));
        if ($option_value) {
            return $option_value;
        }
        
        return null;
    }
    
    /**
     * Store multiple API keys securely
     */
    public function store_multiple_keys($keys_array) {
        $results = [];
        
        foreach ($keys_array as $key_name => $key_value) {
            try {
                $results[$key_name] = $this->store_api_key($key_name, $key_value);
            } catch (Exception $e) {
                $results[$key_name] = false;
                error_log("VORTEX: Failed to store {$key_name}: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Setup initial API keys from environment variables
     */
    public function setup_initial_keys() {
        $keys_to_setup = [];
        
        // Try to get keys from environment variables
        foreach ($this->supported_keys as $key_name) {
            $env_value = getenv($key_name);
            if ($env_value && !empty($env_value)) {
                $keys_to_setup[$key_name] = $env_value;
            }
        }
        
        if (empty($keys_to_setup)) {
            error_log("VORTEX: No API keys found in environment variables");
            return [];
        }
        
        $results = $this->store_multiple_keys($keys_to_setup);
        
        // Log success/failure for each key
        foreach ($results as $key_name => $success) {
            if ($success) {
                error_log("VORTEX: Successfully encrypted and stored {$key_name}");
            } else {
                error_log("VORTEX: Failed to store {$key_name}");
            }
        }
        
        return $results;
    }
    
    /**
     * Get CPU-specific API keys
     */
    public function get_cpu_keys() {
        $keys = [];
        
        foreach ($this->cpu_keys as $key_name) {
            $key_value = $this->get_api_key($key_name);
            if ($key_value) {
                $keys[$key_name] = $key_value;
            }
        }
        
        return $keys;
    }
    
    /**
     * Get GPU-specific API keys
     */
    public function get_gpu_keys() {
        $keys = [];
        
        foreach ($this->gpu_keys as $key_name) {
            $key_value = $this->get_api_key($key_name);
            if ($key_value) {
                $keys[$key_name] = $key_value;
            }
        }
        
        return $keys;
    }
    
    /**
     * Check if a key exists
     */
    public function key_exists($key_name) {
        if (!in_array($key_name, $this->supported_keys)) {
            return false;
        }
        
        $option_name = 'vortex_encrypted_' . strtolower($key_name);
        return get_option($option_name) !== false;
    }
    
    /**
     * Delete a key
     */
    public function delete_key($key_name) {
        if (!in_array($key_name, $this->supported_keys)) {
            throw new Exception("Unsupported API key: {$key_name}");
        }
        
        $option_name = 'vortex_encrypted_' . strtolower($key_name);
        $success = delete_option($option_name);
        
        if ($success) {
            error_log("VORTEX: Deleted API key for {$key_name}");
            update_option('vortex_api_keys_last_modified', current_time('mysql'));
        }
        
        return $success;
    }
    
    /**
     * Get all supported key names
     */
    public function get_supported_keys() {
        return $this->supported_keys;
    }
    
    /**
     * Get key statistics
     */
    public function get_key_statistics() {
        $stats = [
            'total_supported' => count($this->supported_keys),
            'total_stored' => 0,
            'cpu_keys' => count($this->cpu_keys),
            'gpu_keys' => count($this->gpu_keys),
            'last_modified' => get_option('vortex_api_keys_last_modified', 'Never'),
            'encryption_method' => $this->cipher_method
        ];
        
        foreach ($this->supported_keys as $key_name) {
            if ($this->key_exists($key_name)) {
                $stats['total_stored']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Validate key format
     */
    public function validate_key_format($key_name, $key_value) {
        $patterns = [
            'OPENAI_API_KEY' => '/^sk-(proj-)?[a-zA-Z0-9]{20,}$/',
            'CLAUDE_API_KEY' => '/^sk-ant-api03-[a-zA-Z0-9_-]{95}$/',
            'GEMINI_API_KEY' => '/^AIza[a-zA-Z0-9_-]{35}$/',
            'GROK_API_KEY' => '/^xai-[a-zA-Z0-9]{48}$/',
            'DALL_E_API_KEY' => '/^sk-(proj-)?[a-zA-Z0-9]{20,}$/',
            'SORA_API_KEY' => '/^sk-(proj-)?[a-zA-Z0-9]{20,}$/',
            'AWS_ACCESS_KEY_ID' => '/^AKIA[a-zA-Z0-9]{16}$/',
            'AWS_SECRET_ACCESS_KEY' => '/^[a-zA-Z0-9\/\+]{40}$/'
        ];
        
        if (!isset($patterns[$key_name])) {
            return false;
        }
        
        return preg_match($patterns[$key_name], $key_value);
    }
    
    /**
     * Test key connectivity
     */
    public function test_key_connectivity($key_name) {
        $key_value = $this->get_api_key($key_name);
        
        if (!$key_value) {
            return [
                'status' => 'error',
                'message' => 'Key not found or failed to decrypt'
            ];
        }
        
        // Basic format validation
        if (!$this->validate_key_format($key_name, $key_value)) {
            return [
                'status' => 'error',
                'message' => 'Invalid key format'
            ];
        }
        
        // TODO: Implement actual API connectivity tests
        return [
            'status' => 'success',
            'message' => 'Key format valid and decryption successful'
        ];
    }
} 