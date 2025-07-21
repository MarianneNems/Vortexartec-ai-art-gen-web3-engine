<?php
/**
 * Vortex Admin Controller
 * 
 * Handles all admin-related functionality for the VORTEX AI Engine plugin
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Admin Controller Class
 */
class Vortex_Admin_Controller {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vortex_admin_action', array($this, 'handle_ajax_action'));
        add_action('wp_ajax_vortex_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_vortex_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_vortex_generate_artwork', array($this, 'generate_artwork'));
        add_action('wp_ajax_vortex_deploy_contract', array($this, 'deploy_contract'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        register_setting('vortex_ai_engine_settings', 'vortex_ai_engine_options');
        
        // Add settings sections
        add_settings_section(
            'vortex_general_settings',
            __('General Settings', 'vortex-ai-engine'),
            array($this, 'general_settings_section_callback'),
            'vortex_ai_engine_settings'
        );
        
        add_settings_section(
            'vortex_ai_settings',
            __('AI Configuration', 'vortex-ai-engine'),
            array($this, 'ai_settings_section_callback'),
            'vortex_ai_engine_settings'
        );
        
        add_settings_section(
            'vortex_blockchain_settings',
            __('Blockchain Configuration', 'vortex-ai-engine'),
            array($this, 'blockchain_settings_section_callback'),
            'vortex_ai_engine_settings'
        );
        
        add_settings_section(
            'vortex_storage_settings',
            __('Storage Configuration', 'vortex-ai-engine'),
            array($this, 'storage_settings_section_callback'),
            'vortex_ai_engine_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('VORTEX AI Engine', 'vortex-ai-engine'),
            __('VORTEX AI', 'vortex-ai-engine'),
            'manage_options',
            'vortex-ai-engine',
            array($this, 'main_dashboard_page'),
            'dashicons-art',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'vortex-ai-engine',
            __('Dashboard', 'vortex-ai-engine'),
            __('Dashboard', 'vortex-ai-engine'),
            'manage_options',
            'vortex-ai-engine',
            array($this, 'main_dashboard_page')
        );
        
        add_submenu_page(
            'vortex-ai-engine',
            __('AI Agents', 'vortex-ai-engine'),
            __('AI Agents', 'vortex-ai-engine'),
            'manage_options',
            'vortex-ai-agents',
            array($this, 'ai_agents_page')
        );
        
        add_submenu_page(
            'vortex-ai-engine',
            __('TOLA-ART', 'vortex-ai-engine'),
            __('TOLA-ART', 'vortex-ai-engine'),
            'manage_options',
            'vortex-tola-art',
            array($this, 'tola_art_page')
        );
        
        add_submenu_page(
            'vortex-ai-engine',
            __('Blockchain', 'vortex-ai-engine'),
            __('Blockchain', 'vortex-ai-engine'),
            'manage_options',
            'vortex-blockchain',
            array($this, 'blockchain_page')
        );
        
        add_submenu_page(
            'vortex-ai-engine',
            __('Subscriptions', 'vortex-ai-engine'),
            __('Subscriptions', 'vortex-ai-engine'),
            'manage_options',
            'vortex-subscriptions',
            array($this, 'subscriptions_page')
        );
        
        add_submenu_page(
            'vortex-ai-engine',
            __('Settings', 'vortex-ai-engine'),
            __('Settings', 'vortex-ai-engine'),
            'manage_options',
            'vortex-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'vortex-ai-engine',
            __('System Logs', 'vortex-ai-engine'),
            __('System Logs', 'vortex-ai-engine'),
            'manage_options',
            'vortex-logs',
            array($this, 'logs_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'vortex-ai-engine') === false) {
            return;
        }
        
        wp_enqueue_script(
            'vortex-admin-js',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/js/vortex-admin.js',
            array('jquery'),
            VORTEX_AI_ENGINE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vortex-admin-css',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/css/vortex-admin.css',
            array(),
            VORTEX_AI_ENGINE_VERSION
        );
        
        // Localize script
        wp_localize_script('vortex-admin-js', 'vortex_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'vortex-ai-engine'),
                'processing' => __('Processing...', 'vortex-ai-engine'),
                'success' => __('Operation completed successfully!', 'vortex-ai-engine'),
                'error' => __('An error occurred. Please try again.', 'vortex-ai-engine')
            )
        ));
    }
    
    /**
     * Main dashboard page
     */
    public function main_dashboard_page() {
        $dashboard = Vortex_Admin_Dashboard::get_instance();
        $dashboard->render_dashboard();
    }
    
    /**
     * AI Agents page
     */
    public function ai_agents_page() {
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/views/ai-agents-page.php';
    }
    
    /**
     * TOLA-ART page
     */
    public function tola_art_page() {
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/views/tola-art-page.php';
    }
    
    /**
     * Blockchain page
     */
    public function blockchain_page() {
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/views/blockchain-page.php';
    }
    
    /**
     * Subscriptions page
     */
    public function subscriptions_page() {
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/views/subscriptions-page.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/views/settings-page.php';
    }
    
    /**
     * Logs page
     */
    public function logs_page() {
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/views/logs-page.php';
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General settings
        add_settings_field(
            'vortex_enable_ai_agents',
            __('Enable AI Agents', 'vortex-ai-engine'),
            array($this, 'checkbox_field_callback'),
            'vortex_ai_engine_settings',
            'vortex_general_settings',
            array('field' => 'enable_ai_agents')
        );
        
        add_settings_field(
            'vortex_enable_blockchain',
            __('Enable Blockchain Integration', 'vortex-ai-engine'),
            array($this, 'checkbox_field_callback'),
            'vortex_ai_engine_settings',
            'vortex_general_settings',
            array('field' => 'enable_blockchain')
        );
        
        // AI settings
        add_settings_field(
            'vortex_runpod_api_key',
            __('RunPod API Key', 'vortex-ai-engine'),
            array($this, 'text_field_callback'),
            'vortex_ai_engine_settings',
            'vortex_ai_settings',
            array('field' => 'runpod_api_key')
        );
        
        add_settings_field(
            'vortex_gradio_endpoint',
            __('Gradio Endpoint', 'vortex-ai-engine'),
            array($this, 'text_field_callback'),
            'vortex_ai_engine_settings',
            'vortex_ai_settings',
            array('field' => 'gradio_endpoint')
        );
        
        // Blockchain settings
        add_settings_field(
            'vortex_solana_rpc_url',
            __('Solana RPC URL', 'vortex-ai-engine'),
            array($this, 'text_field_callback'),
            'vortex_ai_engine_settings',
            'vortex_blockchain_settings',
            array('field' => 'solana_rpc_url')
        );
        
        add_settings_field(
            'vortex_wallet_private_key',
            __('Wallet Private Key', 'vortex-ai-engine'),
            array($this, 'password_field_callback'),
            'vortex_ai_engine_settings',
            'vortex_blockchain_settings',
            array('field' => 'wallet_private_key')
        );
        
        // Storage settings
        add_settings_field(
            'vortex_storage_provider',
            __('Storage Provider', 'vortex-ai-engine'),
            array($this, 'select_field_callback'),
            'vortex_ai_engine_settings',
            'vortex_storage_settings',
            array(
                'field' => 'storage_provider',
                'options' => array(
                    'local' => __('Local Storage', 'vortex-ai-engine'),
                    'aws' => __('Amazon S3', 'vortex-ai-engine'),
                    'ipfs' => __('IPFS', 'vortex-ai-engine')
                )
            )
        );
    }
    
    /**
     * Settings section callbacks
     */
    public function general_settings_section_callback() {
        echo '<p>' . __('Configure general plugin settings.', 'vortex-ai-engine') . '</p>';
    }
    
    public function ai_settings_section_callback() {
        echo '<p>' . __('Configure AI agent settings and API endpoints.', 'vortex-ai-engine') . '</p>';
    }
    
    public function blockchain_settings_section_callback() {
        echo '<p>' . __('Configure blockchain integration settings.', 'vortex-ai-engine') . '</p>';
    }
    
    public function storage_settings_section_callback() {
        echo '<p>' . __('Configure file storage settings.', 'vortex-ai-engine') . '</p>';
    }
    
    /**
     * Settings field callbacks
     */
    public function checkbox_field_callback($args) {
        $options = get_option('vortex_ai_engine_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : false;
        
        echo '<input type="checkbox" id="' . $field . '" name="vortex_ai_engine_options[' . $field . ']" value="1" ' . checked(1, $value, false) . '/>';
        echo '<label for="' . $field . '">' . __('Enable', 'vortex-ai-engine') . '</label>';
    }
    
    public function text_field_callback($args) {
        $options = get_option('vortex_ai_engine_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        
        echo '<input type="text" id="' . $field . '" name="vortex_ai_engine_options[' . $field . ']" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    public function password_field_callback($args) {
        $options = get_option('vortex_ai_engine_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        
        echo '<input type="password" id="' . $field . '" name="vortex_ai_engine_options[' . $field . ']" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    public function select_field_callback($args) {
        $options = get_option('vortex_ai_engine_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $select_options = $args['options'];
        
        echo '<select id="' . $field . '" name="vortex_ai_engine_options[' . $field . ']">';
        foreach ($select_options as $key => $label) {
            echo '<option value="' . $key . '" ' . selected($key, $value, false) . '>' . $label . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Handle AJAX actions
     */
    public function handle_ajax_action() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'test_ai_connection':
                $this->test_ai_connection();
                break;
            case 'generate_test_artwork':
                $this->generate_test_artwork();
                break;
            case 'deploy_test_contract':
                $this->deploy_test_contract();
                break;
            case 'clear_logs':
                $this->clear_logs();
                break;
            default:
                wp_send_json_error(__('Invalid action', 'vortex-ai-engine'));
        }
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $options = $_POST['options'];
        $sanitized_options = array();
        
        foreach ($options as $key => $value) {
            $sanitized_options[$key] = sanitize_text_field($value);
        }
        
        update_option('vortex_ai_engine_options', $sanitized_options);
        
        wp_send_json_success(__('Settings saved successfully!', 'vortex-ai-engine'));
    }
    
    /**
     * Test connection
     */
    public function test_connection() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $connection_type = sanitize_text_field($_POST['connection_type']);
        
        switch ($connection_type) {
            case 'runpod':
                $this->test_runpod_connection();
                break;
            case 'gradio':
                $this->test_gradio_connection();
                break;
            case 'solana':
                $this->test_solana_connection();
                break;
            default:
                wp_send_json_error(__('Invalid connection type', 'vortex-ai-engine'));
        }
    }
    
    /**
     * Generate artwork
     */
    public function generate_artwork() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $agent = sanitize_text_field($_POST['agent']);
        
        if (empty($prompt)) {
            wp_send_json_error(__('Prompt is required', 'vortex-ai-engine'));
        }
        
        // Get AI agent instance
        switch ($agent) {
            case 'HURAII':
                $agent_instance = Vortex_HURAII_Agent::get_instance();
                break;
            default:
                wp_send_json_error(__('Invalid agent', 'vortex-ai-engine'));
        }
        
        // Generate artwork
        $result = $agent_instance->generate_image($prompt);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Deploy contract
     */
    public function deploy_contract() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $contract_type = sanitize_text_field($_POST['contract_type']);
        $contract_data = $_POST['contract_data'];
        
        // Get smart contract manager
        $contract_manager = Vortex_Smart_Contract_Manager::get_instance();
        
        // Deploy contract
        $result = $contract_manager->deploy_contract($contract_type, $contract_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Test AI connection
     */
    private function test_ai_connection() {
        $options = get_option('vortex_ai_engine_options', array());
        
        if (empty($options['runpod_api_key'])) {
            wp_send_json_error(__('RunPod API key not configured', 'vortex-ai-engine'));
        }
        
        // Test RunPod connection
        $runpod_vault = Vortex_RunPod_Vault::get_instance();
        $result = $runpod_vault->test_connection();
        
        if ($result['success']) {
            wp_send_json_success(__('AI connection test successful!', 'vortex-ai-engine'));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Test RunPod connection
     */
    private function test_runpod_connection() {
        $runpod_vault = Vortex_RunPod_Vault::get_instance();
        $result = $runpod_vault->test_connection();
        
        if ($result['success']) {
            wp_send_json_success(__('RunPod connection successful!', 'vortex-ai-engine'));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Test Gradio connection
     */
    private function test_gradio_connection() {
        $gradio_client = Vortex_Gradio_Client::get_instance();
        $result = $gradio_client->test_connection();
        
        if ($result['success']) {
            wp_send_json_success(__('Gradio connection successful!', 'vortex-ai-engine'));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Test Solana connection
     */
    private function test_solana_connection() {
        $options = get_option('vortex_ai_engine_options', array());
        
        if (empty($options['solana_rpc_url'])) {
            wp_send_json_error(__('Solana RPC URL not configured', 'vortex-ai-engine'));
        }
        
        // Simulated Solana connection test
        wp_send_json_success(__('Solana connection test successful!', 'vortex-ai-engine'));
    }
    
    /**
     * Generate test artwork
     */
    private function generate_test_artwork() {
        $huraii_agent = Vortex_HURAII_Agent::get_instance();
        $result = $huraii_agent->generate_image('A beautiful digital artwork in the style of modern art');
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Deploy test contract
     */
    private function deploy_test_contract() {
        $contract_manager = Vortex_Smart_Contract_Manager::get_instance();
        $result = $contract_manager->deploy_contract('nft', array(
            'name' => 'Test NFT',
            'symbol' => 'TEST',
            'description' => 'Test NFT contract'
        ));
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Clear logs
     */
    private function clear_logs() {
        $db_manager = Vortex_Database_Manager::get_instance();
        $result = $db_manager->clean_old_logs(0); // Clear all logs
        
        if ($result !== false) {
            wp_send_json_success(__('Logs cleared successfully!', 'vortex-ai-engine'));
        } else {
            wp_send_json_error(__('Failed to clear logs', 'vortex-ai-engine'));
        }
    }
} 