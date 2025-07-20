<?php
/**
 * Model Provider Interface for VORTEX AI Engine
 * Defines the contract that all AI providers must implement
 */

if (!defined('ABSPATH')) {
    exit;
}

interface ModelProviderInterface {
    
    /**
     * Generate content using the provider's API
     *
     * @param string $prompt The input prompt
     * @param array $options Optional parameters for generation
     * @return array|WP_Error The generated content or error
     */
    public function generate($prompt, $options = []);
    
    /**
     * Test API connection
     *
     * @return array Connection test results
     */
    public function test_connection();
    
    /**
     * Get available models
     *
     * @return array|WP_Error List of available models or error
     */
    public function get_models();
    
    /**
     * Get rate limits for this provider
     *
     * @return array Rate limit information
     */
    public function get_rate_limits();
    
    /**
     * Get provider information
     *
     * @return array Provider details and capabilities
     */
    public function get_provider_info();
} 