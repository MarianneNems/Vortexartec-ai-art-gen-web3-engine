<?php
/**
 * Vortex AI Engine - REST API Endpoints
 *
 * @package Vortex_AI_Engine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Vortex_REST_Endpoints
 * 
 * Handles all REST API endpoints for the Vortex AI Engine plugin
 */
class Vortex_REST_Endpoints {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    /**
     * Register all REST endpoints
     */
    public function register_endpoints() {
        // Plugin sync endpoint
        register_rest_route('plugin-sync/v1', '/ping', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_plugin_sync'),
            'permission_callback' => array($this, 'verify_sync_token'),
            'args' => array(
                'plugin' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'version' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'deployed_at' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Plugin status endpoint
        register_rest_route('plugin-sync/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_plugin_status'),
            'permission_callback' => array($this, 'verify_sync_token'),
        ));

        // Supervisor system endpoint
        register_rest_route('vortex-ai/v1', '/supervisor/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_supervisor_status'),
            'permission_callback' => array($this, 'verify_sync_token'),
        ));
    }

    /**
     * Verify the sync token for authentication
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function verify_sync_token($request) {
        // Check for token in Authorization header
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        } else {
            // Check for token in URL parameter
            $token = $request->get_param('token');
        }

        // Get the expected token from WordPress options or constants
        $expected_token = defined('PLUGIN_SYNC_TOKEN') ? PLUGIN_SYNC_TOKEN : get_option('vortex_plugin_sync_token');

        if (!$expected_token) {
            return false;
        }

        return $token === $expected_token;
    }

    /**
     * Handle plugin sync notification
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_plugin_sync($request) {
        $plugin_name = $request->get_param('plugin');
        $version = $request->get_param('version');
        $deployed_at = $request->get_param('deployed_at');

        // Log the deployment
        $this->log_deployment($plugin_name, $version, $deployed_at);

        // Update plugin version in database
        update_option('vortex_plugin_last_deployment', array(
            'plugin' => $plugin_name,
            'version' => $version,
            'deployed_at' => $deployed_at,
            'timestamp' => current_time('mysql'),
        ));

        // Trigger supervisor system update
        if (class_exists('Vortex_Supervisor_Sync')) {
            $supervisor = new Vortex_Supervisor_Sync();
            $supervisor->notify_deployment($plugin_name, $version);
        }

        // Send admin notification
        $this->send_deployment_notification($plugin_name, $version);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Plugin sync completed successfully',
            'plugin' => $plugin_name,
            'version' => $version,
            'deployed_at' => $deployed_at,
            'timestamp' => current_time('mysql'),
        ), 200);
    }

    /**
     * Get plugin status
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_plugin_status($request) {
        $last_deployment = get_option('vortex_plugin_last_deployment', array());
        
        return new WP_REST_Response(array(
            'success' => true,
            'plugin_active' => is_plugin_active('vortex-ai-engine/vortex-ai-engine.php'),
            'last_deployment' => $last_deployment,
            'supervisor_active' => class_exists('Vortex_Supervisor_Monitor'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
        ), 200);
    }

    /**
     * Get supervisor system status
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_supervisor_status($request) {
        if (!class_exists('Vortex_Supervisor_Monitor')) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Supervisor system not available',
            ), 404);
        }

        $supervisor = new Vortex_Supervisor_Monitor();
        $status = $supervisor->get_status();

        return new WP_REST_Response(array(
            'success' => true,
            'supervisor_status' => $status,
        ), 200);
    }

    /**
     * Log deployment information
     *
     * @param string $plugin_name
     * @param string $version
     * @param string $deployed_at
     */
    private function log_deployment($plugin_name, $version, $deployed_at) {
        $log_entry = sprintf(
            '[%s] Plugin deployment: %s (version: %s, deployed: %s)',
            current_time('Y-m-d H:i:s'),
            $plugin_name,
            $version,
            $deployed_at
        );

        // Log to WordPress debug log
        error_log($log_entry);

        // Store in plugin-specific log
        $log_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'logs/deployment.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        file_put_contents($log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Send deployment notification to admin
     *
     * @param string $plugin_name
     * @param string $version
     */
    private function send_deployment_notification($plugin_name, $version) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] Plugin Deployment Notification', $site_name);
        $message = sprintf(
            "Hello,\n\n" .
            "The plugin '%s' has been successfully deployed to your WordPress site.\n\n" .
            "Details:\n" .
            "- Plugin: %s\n" .
            "- Version: %s\n" .
            "- Deployed: %s\n" .
            "- Site: %s\n\n" .
            "The plugin is now live and running with the latest updates.\n\n" .
            "Best regards,\n" .
            "Vortex AI Engine System",
            $plugin_name,
            $plugin_name,
            $version,
            current_time('Y-m-d H:i:s'),
            get_site_url()
        );

        wp_mail($admin_email, $subject, $message);
    }
}

// Initialize the REST endpoints
new Vortex_REST_Endpoints(); 