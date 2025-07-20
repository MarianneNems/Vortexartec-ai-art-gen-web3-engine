<?php
/**
 * VORTEX AI Engine - Admin Interface Class
 * Handles WordPress admin integration and settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('plugin_action_links_' . plugin_basename(VORTEX_AI_ENGINE_PLUGIN_FILE), [$this, 'add_settings_link']);
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_options_page(
            'VORTEX AI Engine Settings',
            'VORTEX AI',
            'manage_options',
            'vortex-ai-settings',
            [$this, 'render_settings_page']
        );
        
        add_management_page(
            'VORTEX AI Diagnostics',
            'VORTEX AI Diagnostics',
            'manage_options',
            'vortex-ai-diagnostics',
            [$this, 'render_diagnostics_page']
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('vortex_ai_settings', 'vortex_ai_options', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
        
        add_settings_section(
            'vortex_ai_general',
            'General Settings',
            [$this, 'general_section_callback'],
            'vortex-ai-settings'
        );
        
        add_settings_section(
            'vortex_ai_api_keys',
            'API Keys',
            [$this, 'api_keys_section_callback'],
            'vortex-ai-settings'
        );
        
        add_settings_section(
            'vortex_ai_security',
            'Security Settings',
            [$this, 'security_section_callback'],
            'vortex-ai-settings'
        );
        
        // General settings fields
        add_settings_field(
            'enable_debug',
            'Enable Debug Mode',
            [$this, 'render_checkbox_field'],
            'vortex-ai-settings',
            'vortex_ai_general',
            ['field' => 'enable_debug', 'label' => 'Enable debug logging']
        );
        
        add_settings_field(
            'default_tier',
            'Default Tier',
            [$this, 'render_select_field'],
            'vortex-ai-settings',
            'vortex_ai_general',
            [
                'field' => 'default_tier',
                'options' => [
                    'basic' => 'Basic',
                    'premium' => 'Premium',
                    'enterprise' => 'Enterprise'
                ]
            ]
        );
        
        // API Keys fields
        $api_keys = [
            'openai_api_key' => 'OpenAI API Key',
            'claude_api_key' => 'Claude API Key',
            'gemini_api_key' => 'Gemini API Key',
            'grok_api_key' => 'Grok API Key'
        ];
        
        foreach ($api_keys as $key => $label) {
            add_settings_field(
                $key,
                $label,
                [$this, 'render_password_field'],
                'vortex-ai-settings',
                'vortex_ai_api_keys',
                ['field' => $key, 'label' => $label]
            );
        }
        
        // Security settings fields
        add_settings_field(
            'enable_rate_limiting',
            'Enable Rate Limiting',
            [$this, 'render_checkbox_field'],
            'vortex-ai-settings',
            'vortex_ai_security',
            ['field' => 'enable_rate_limiting', 'label' => 'Enable API rate limiting']
        );
        
        add_settings_field(
            'max_requests_per_minute',
            'Max Requests Per Minute',
            [$this, 'render_number_field'],
            'vortex-ai-settings',
            'vortex_ai_security',
            ['field' => 'max_requests_per_minute', 'min' => 1, 'max' => 1000, 'default' => 60]
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Sanitize each field
        if (isset($input['enable_debug'])) {
            $sanitized['enable_debug'] = (bool) $input['enable_debug'];
        }
        
        if (isset($input['default_tier'])) {
            $allowed_tiers = ['basic', 'premium', 'enterprise'];
            $sanitized['default_tier'] = in_array($input['default_tier'], $allowed_tiers) 
                ? $input['default_tier'] 
                : 'basic';
        }
        
        // Sanitize API keys
        $api_keys = ['openai_api_key', 'claude_api_key', 'gemini_api_key', 'grok_api_key'];
        foreach ($api_keys as $key) {
            if (isset($input[$key])) {
                $sanitized[$key] = sanitize_text_field($input[$key]);
                
                // Store encrypted version if not empty
                if (!empty($sanitized[$key]) && class_exists('VortexSecureAPIKeys')) {
                    try {
                        $secure_api = new VortexSecureAPIKeys();
                        $secure_api->store_api_key(strtoupper(str_replace('_', '_', $key)), $sanitized[$key]);
                    } catch (Exception $e) {
                        error_log('[VortexAI Admin] Error storing API key: ' . $e->getMessage());
                        add_settings_error('vortex_ai_options', 'api_key_error', 'Failed to store API key securely: ' . $e->getMessage());
                    }
                }
            }
        }
        
        if (isset($input['enable_rate_limiting'])) {
            $sanitized['enable_rate_limiting'] = (bool) $input['enable_rate_limiting'];
        }
        
        if (isset($input['max_requests_per_minute'])) {
            $sanitized['max_requests_per_minute'] = intval($input['max_requests_per_minute']);
            if ($sanitized['max_requests_per_minute'] < 1) {
                $sanitized['max_requests_per_minute'] = 60;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>VORTEX AI Engine Settings</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('vortex_ai_settings');
                do_settings_sections('vortex-ai-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render diagnostics page
     */
    public function render_diagnostics_page() {
        ?>
        <div class="wrap">
            <h1>VORTEX AI Diagnostics</h1>
            
            <div class="card">
                <h2>System Status</h2>
                <?php $this->render_system_status(); ?>
            </div>
            
            <div class="card">
                <h2>API Connectivity</h2>
                <?php $this->render_api_connectivity(); ?>
            </div>
            
            <div class="card">
                <h2>Database Status</h2>
                <?php $this->render_database_status(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render system status
     */
    private function render_system_status() {
        $status = [
            'Plugin Version' => VORTEX_AI_ENGINE_VERSION,
            'WordPress Version' => get_bloginfo('version'),
            'PHP Version' => PHP_VERSION,
            'OpenSSL Available' => extension_loaded('openssl') ? 'Yes' : 'No',
            'cURL Available' => extension_loaded('curl') ? 'Yes' : 'No',
            'JSON Extension' => extension_loaded('json') ? 'Yes' : 'No'
        ];
        
        echo '<table class="widefat">';
        foreach ($status as $key => $value) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($key) . '</strong></td>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    /**
     * Render API connectivity status
     */
    private function render_api_connectivity() {
        $apis = [
            'OpenAI' => 'OPENAI_API_KEY',
            'Claude' => 'CLAUDE_API_KEY',
            'Gemini' => 'GEMINI_API_KEY',
            'Grok' => 'GROK_API_KEY'
        ];
        
        echo '<table class="widefat">';
        foreach ($apis as $name => $key) {
            $has_key = !empty(get_option('vortex_encrypted_' . strtolower($key)));
            echo '<tr>';
            echo '<td><strong>' . esc_html($name) . '</strong></td>';
            echo '<td>' . ($has_key ? '<span style="color: green;">✓ Configured</span>' : '<span style="color: red;">✗ Not Configured</span>') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    /**
     * Render database status
     */
    private function render_database_status() {
        global $wpdb;
        
        $tables = [
            'vortex_feedback' => $wpdb->prefix . 'vortex_feedback',
            'vortex_tier_api_keys' => $wpdb->prefix . 'vortex_tier_api_keys',
            'vortex_tier_usage_log' => $wpdb->prefix . 'vortex_tier_usage_log'
        ];
        
        echo '<table class="widefat">';
        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            echo '<tr>';
            echo '<td><strong>' . esc_html($name) . '</strong></td>';
            echo '<td>' . ($exists ? '<span style="color: green;">✓ Exists</span>' : '<span style="color: red;">✗ Missing</span>') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>Configure general plugin settings.</p>';
    }
    
    public function api_keys_section_callback() {
        echo '<p>Configure API keys for AI providers. Keys are encrypted before storage.</p>';
    }
    
    public function security_section_callback() {
        echo '<p>Configure security and rate limiting settings.</p>';
    }
    
    /**
     * Field rendering methods
     */
    public function render_checkbox_field($args) {
        $options = get_option('vortex_ai_options', []);
        $value = isset($options[$args['field']]) ? $options[$args['field']] : false;
        
        echo '<input type="checkbox" name="vortex_ai_options[' . esc_attr($args['field']) . ']" value="1" ' . checked($value, true, false) . '>';
        echo '<label for="vortex_ai_options[' . esc_attr($args['field']) . ']">' . esc_html($args['label']) . '</label>';
    }
    
    public function render_select_field($args) {
        $options = get_option('vortex_ai_options', []);
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        
        echo '<select name="vortex_ai_options[' . esc_attr($args['field']) . ']">';
        foreach ($args['options'] as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    
    public function render_password_field($args) {
        $options = get_option('vortex_ai_options', []);
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        
        echo '<input type="password" name="vortex_ai_options[' . esc_attr($args['field']) . ']" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Enter your ' . esc_html($args['label']) . '. Keys are encrypted before storage.</p>';
    }
    
    public function render_number_field($args) {
        $options = get_option('vortex_ai_options', []);
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        
        echo '<input type="number" name="vortex_ai_options[' . esc_attr($args['field']) . ']" value="' . esc_attr($value) . '" min="' . esc_attr($args['min']) . '" max="' . esc_attr($args['max']) . '" class="small-text">';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_vortex-ai-settings' !== $hook && 'tools_page_vortex-ai-diagnostics' !== $hook) {
            return;
        }
        
        wp_enqueue_style('vortex-ai-admin', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/admin.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-ai-admin', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
    }
    
    /**
     * Add settings link to plugin actions
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=vortex-ai-settings">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
} 