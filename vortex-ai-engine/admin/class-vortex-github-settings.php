<?php
/**
 * VORTEX AI Engine - GitHub Integration Settings
 * 
 * Admin settings page for configuring GitHub integration
 * Manages secure synchronization of logs with GitHub repositories
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 * @since 2024-01-01
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub Settings Class
 * 
 * Handles GitHub integration configuration and management
 */
class VORTEX_GitHub_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_ajax_vortex_test_github_connection', array($this, 'ajax_test_github_connection'));
        add_action('wp_ajax_vortex_sync_logs_now', array($this, 'ajax_sync_logs_now'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'GitHub Integration',
            'GitHub Integration',
            'manage_options',
            'vortex-github-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('vortex_github_settings', 'vortex_github_logging_enabled');
        register_setting('vortex_github_settings', 'vortex_github_repository');
        register_setting('vortex_github_settings', 'vortex_github_token');
        register_setting('vortex_github_settings', 'vortex_github_branch');
        register_setting('vortex_github_settings', 'vortex_github_sync_interval');
        register_setting('vortex_github_settings', 'vortex_github_encrypt_sensitive');
        register_setting('vortex_github_settings', 'vortex_github_exclude_patterns');
        
        add_settings_section(
            'vortex_github_main',
            'GitHub Integration Settings',
            array($this, 'settings_section_callback'),
            'vortex_github_settings'
        );
        
        add_settings_field(
            'vortex_github_logging_enabled',
            'Enable GitHub Logging',
            array($this, 'checkbox_callback'),
            'vortex_github_settings',
            'vortex_github_main',
            array('field' => 'vortex_github_logging_enabled')
        );
        
        add_settings_field(
            'vortex_github_repository',
            'GitHub Repository',
            array($this, 'text_callback'),
            'vortex_github_settings',
            'vortex_github_main',
            array('field' => 'vortex_github_repository')
        );
        
        add_settings_field(
            'vortex_github_token',
            'GitHub Personal Access Token',
            array($this, 'password_callback'),
            'vortex_github_settings',
            'vortex_github_main',
            array('field' => 'vortex_github_token')
        );
        
        add_settings_field(
            'vortex_github_branch',
            'GitHub Branch',
            array($this, 'text_callback'),
            'vortex_github_settings',
            'vortex_github_main',
            array('field' => 'vortex_github_branch')
        );
        
        add_settings_field(
            'vortex_github_sync_interval',
            'Sync Interval (seconds)',
            array($this, 'number_callback'),
            'vortex_github_settings',
            'vortex_github_main',
            array('field' => 'vortex_github_sync_interval')
        );
        
        add_settings_field(
            'vortex_github_encrypt_sensitive',
            'Encrypt Sensitive Data',
            array($this, 'checkbox_callback'),
            'vortex_github_settings',
            'vortex_github_main',
            array('field' => 'vortex_github_encrypt_sensitive')
        );
        
        add_settings_field(
            'vortex_github_exclude_patterns',
            'Exclude Patterns',
            array($this, 'textarea_callback'),
            'vortex_github_settings',
            'vortex_github_main',
            array('field' => 'vortex_github_exclude_patterns')
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure GitHub integration for secure log synchronization. All sensitive data will be encrypted before being sent to GitHub.</p>';
    }
    
    /**
     * Checkbox callback
     */
    public function checkbox_callback($args) {
        $field = $args['field'];
        $value = get_option($field, false);
        echo '<input type="checkbox" id="' . $field . '" name="' . $field . '" value="1" ' . checked(1, $value, false) . '/>';
        echo '<label for="' . $field . '">Enable this feature</label>';
    }
    
    /**
     * Text callback
     */
    public function text_callback($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        echo '<input type="text" id="' . $field . '" name="' . $field . '" value="' . esc_attr($value) . '" class="regular-text" />';
        
        if ($field === 'vortex_github_repository') {
            echo '<p class="description">Format: username/repository-name</p>';
        } elseif ($field === 'vortex_github_branch') {
            echo '<p class="description">Default: main</p>';
        }
    }
    
    /**
     * Password callback
     */
    public function password_callback($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        echo '<input type="password" id="' . $field . '" name="' . $field . '" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Create a personal access token with repo permissions at <a href="https://github.com/settings/tokens" target="_blank">GitHub Settings</a></p>';
    }
    
    /**
     * Number callback
     */
    public function number_callback($args) {
        $field = $args['field'];
        $value = get_option($field, 300);
        echo '<input type="number" id="' . $field . '" name="' . $field . '" value="' . esc_attr($value) . '" min="60" max="3600" />';
        echo '<p class="description">Minimum: 60 seconds, Maximum: 3600 seconds (1 hour)</p>';
    }
    
    /**
     * Textarea callback
     */
    public function textarea_callback($args) {
        $field = $args['field'];
        $value = get_option($field, "password\ntoken\nkey\nsecret\nauth\ncredential\nprivate");
        echo '<textarea id="' . $field . '" name="' . $field . '" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">One pattern per line. Log entries containing these patterns will be encrypted.</p>';
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-ai-engine'));
        }
        
        $sync_history = VORTEX_Log_Database::get_github_sync_history(10);
        $last_sync = !empty($sync_history) ? $sync_history[0] : null;
        
        ?>
        <div class="wrap">
            <h1><?php _e('VORTEX AI Engine - GitHub Integration', 'vortex-ai-engine'); ?></h1>
            
            <!-- Status Overview -->
            <div class="vortex-github-status">
                <h2><?php _e('Integration Status', 'vortex-ai-engine'); ?></h2>
                <div class="vortex-status-grid">
                    <div class="vortex-status-item">
                        <span class="vortex-status-label"><?php _e('GitHub Logging:', 'vortex-ai-engine'); ?></span>
                        <span class="vortex-status-value <?php echo get_option('vortex_github_logging_enabled') ? 'enabled' : 'disabled'; ?>">
                            <?php echo get_option('vortex_github_logging_enabled') ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                    
                    <div class="vortex-status-item">
                        <span class="vortex-status-label"><?php _e('Repository:', 'vortex-ai-engine'); ?></span>
                        <span class="vortex-status-value">
                            <?php echo get_option('vortex_github_repository') ?: 'Not configured'; ?>
                        </span>
                    </div>
                    
                    <div class="vortex-status-item">
                        <span class="vortex-status-label"><?php _e('Branch:', 'vortex-ai-engine'); ?></span>
                        <span class="vortex-status-value">
                            <?php echo get_option('vortex_github_branch', 'main'); ?>
                        </span>
                    </div>
                    
                    <div class="vortex-status-item">
                        <span class="vortex-status-label"><?php _e('Last Sync:', 'vortex-ai-engine'); ?></span>
                        <span class="vortex-status-value">
                            <?php echo $last_sync ? $last_sync->sync_date : 'Never'; ?>
                        </span>
                    </div>
                    
                    <div class="vortex-status-item">
                        <span class="vortex-status-label"><?php _e('Sync Status:', 'vortex-ai-engine'); ?></span>
                        <span class="vortex-status-value <?php echo $last_sync && $last_sync->success ? 'success' : 'error'; ?>">
                            <?php echo $last_sync ? ($last_sync->success ? 'Success' : 'Failed') : 'Unknown'; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Settings Form -->
            <div class="vortex-github-settings">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('vortex_github_settings');
                    do_settings_sections('vortex_github_settings');
                    submit_button('Save Settings');
                    ?>
                </form>
            </div>
            
            <!-- Test Connection -->
            <div class="vortex-github-test">
                <h2><?php _e('Test Connection', 'vortex-ai-engine'); ?></h2>
                <p><?php _e('Test your GitHub connection and sync settings.', 'vortex-ai-engine'); ?></p>
                <button id="vortex-test-github" class="button button-secondary"><?php _e('Test GitHub Connection', 'vortex-ai-engine'); ?></button>
                <button id="vortex-sync-now" class="button button-primary"><?php _e('Sync Logs Now', 'vortex-ai-engine'); ?></button>
                <div id="vortex-test-result"></div>
            </div>
            
            <!-- Sync History -->
            <div class="vortex-sync-history">
                <h2><?php _e('Recent Sync History', 'vortex-ai-engine'); ?></h2>
                <?php if ($sync_history): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Logs Count', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Status', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Repository', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Branch', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Error', 'vortex-ai-engine'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sync_history as $sync): ?>
                                <tr>
                                    <td><?php echo esc_html($sync->sync_date); ?></td>
                                    <td><?php echo number_format($sync->logs_count); ?></td>
                                    <td>
                                        <span class="vortex-status-badge <?php echo $sync->success ? 'success' : 'error'; ?>">
                                            <?php echo $sync->success ? 'Success' : 'Failed'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($sync->repository); ?></td>
                                    <td><?php echo esc_html($sync->branch); ?></td>
                                    <td><?php echo esc_html($sync->error_message ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No sync history available.', 'vortex-ai-engine'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Security Information -->
            <div class="vortex-security-info">
                <h2><?php _e('Security Information', 'vortex-ai-engine'); ?></h2>
                <div class="vortex-security-grid">
                    <div class="vortex-security-item">
                        <h3><?php _e('Data Encryption', 'vortex-ai-engine'); ?></h3>
                        <p><?php _e('All sensitive data is encrypted using AES-256-CBC before being sent to GitHub. The encryption key is stored securely in your WordPress database.', 'vortex-ai-engine'); ?></p>
                    </div>
                    
                    <div class="vortex-security-item">
                        <h3><?php _e('Pattern Filtering', 'vortex-ai-engine'); ?></h3>
                        <p><?php _e('Log entries containing sensitive patterns (passwords, tokens, etc.) are automatically encrypted to prevent data exposure.', 'vortex-ai-engine'); ?></p>
                    </div>
                    
                    <div class="vortex-security-item">
                        <h3><?php _e('Access Control', 'vortex-ai-engine'); ?></h3>
                        <p><?php _e('Only administrators can access and configure GitHub integration settings. All API calls are authenticated using your GitHub personal access token.', 'vortex-ai-engine'); ?></p>
                    </div>
                    
                    <div class="vortex-security-item">
                        <h3><?php _e('Audit Trail', 'vortex-ai-engine'); ?></h3>
                        <p><?php _e('All GitHub sync operations are logged locally for audit purposes. Failed syncs are recorded with error details for troubleshooting.', 'vortex-ai-engine'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .vortex-github-status,
        .vortex-github-settings,
        .vortex-github-test,
        .vortex-sync-history,
        .vortex-security-info {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .vortex-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .vortex-status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .vortex-status-value.enabled,
        .vortex-status-value.success {
            color: #28a745;
            font-weight: 600;
        }
        
        .vortex-status-value.disabled,
        .vortex-status-value.error {
            color: #dc3545;
            font-weight: 600;
        }
        
        .vortex-status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .vortex-status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        
        .vortex-status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .vortex-security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .vortex-security-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .vortex-security-item h3 {
            margin-top: 0;
            color: #23282d;
            font-size: 16px;
            font-weight: 600;
        }
        
        #vortex-test-result {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        
        #vortex-test-result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        #vortex-test-result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#vortex-test-github').on('click', function() {
                const button = $(this);
                const result = $('#vortex-test-result');
                
                button.prop('disabled', true).text('Testing...');
                result.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_test_github_connection',
                        nonce: '<?php echo wp_create_nonce('vortex_github_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            result.removeClass('error').addClass('success').html(response.data.message).show();
                        } else {
                            result.removeClass('success').addClass('error').html(response.data).show();
                        }
                    },
                    error: function() {
                        result.removeClass('success').addClass('error').html('Connection test failed. Please check your settings.').show();
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test GitHub Connection');
                    }
                });
            });
            
            $('#vortex-sync-now').on('click', function() {
                const button = $(this);
                const result = $('#vortex-test-result');
                
                button.prop('disabled', true).text('Syncing...');
                result.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_sync_logs_now',
                        nonce: '<?php echo wp_create_nonce('vortex_github_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            result.removeClass('error').addClass('success').html(response.data.message).show();
                            // Reload page to update sync history
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            result.removeClass('success').addClass('error').html(response.data).show();
                        }
                    },
                    error: function() {
                        result.removeClass('success').addClass('error').html('Sync failed. Please check your settings.').show();
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Sync Logs Now');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Test GitHub connection
     */
    public function ajax_test_github_connection() {
        check_ajax_referer('vortex_github_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $repository = get_option('vortex_github_repository');
        $token = get_option('vortex_github_token');
        $branch = get_option('vortex_github_branch', 'main');
        
        if (!$repository || !$token) {
            wp_send_json_error('GitHub repository and token must be configured.');
        }
        
        $url = "https://api.github.com/repos/{$repository}/branches/{$branch}";
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'token ' . $token,
                'User-Agent' => 'VORTEX-AI-Engine/2.2.0'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['message'])) {
            wp_send_json_error('GitHub API error: ' . $body['message']);
        }
        
        wp_send_json_success(array(
            'message' => 'GitHub connection successful! Repository and branch are accessible.'
        ));
    }
    
    /**
     * AJAX: Sync logs now
     */
    public function ajax_sync_logs_now() {
        check_ajax_referer('vortex_github_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        try {
            $logger = VORTEX_Realtime_Logger::get_instance();
            $logger->sync_logs_to_github();
            
            wp_send_json_success(array(
                'message' => 'Logs synced successfully to GitHub!'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Sync failed: ' . $e->getMessage());
        }
    }
}

// Initialize the GitHub settings
new VORTEX_GitHub_Settings(); 