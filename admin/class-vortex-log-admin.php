<?php
/**
 * VORTEX AI Engine - Log Admin Interface
 * 
 * Admin interface for viewing and managing real-time logs
 * Provides secure access to log data with filtering and export capabilities
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
 * Log Admin Class
 * 
 * Handles admin interface for log management
 */
class VORTEX_Log_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vortex_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_vortex_export_logs', array($this, 'ajax_export_logs'));
        add_action('wp_ajax_vortex_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_vortex_get_statistics', array($this, 'ajax_get_statistics'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Real-Time Logs',
            'Real-Time Logs',
            'manage_options',
            'vortex-logs',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('vortex-ai-engine_page_vortex-logs' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'vortex-log-admin',
            plugin_dir_url(__FILE__) . 'js/vortex-log-admin.js',
            array('jquery', 'wp-util'),
            '2.2.0',
            true
        );
        
        wp_enqueue_style(
            'vortex-log-admin',
            plugin_dir_url(__FILE__) . 'css/vortex-log-admin.css',
            array(),
            '2.2.0'
        );
        
        wp_localize_script('vortex-log-admin', 'vortexLogAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_log_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'vortex-ai-engine'),
                'error' => __('Error occurred', 'vortex-ai-engine'),
                'confirm_clear' => __('Are you sure you want to clear all logs?', 'vortex-ai-engine'),
                'confirm_export' => __('Export logs?', 'vortex-ai-engine')
            )
        ));
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-ai-engine'));
        }
        
        $logger = VORTEX_Realtime_Logger::get_instance();
        $statistics = VORTEX_Log_Database::get_statistics(7);
        $active_alerts = VORTEX_Log_Database::get_active_alerts();
        $db_size = VORTEX_Log_Database::get_database_size();
        
        ?>
        <div class="wrap">
            <h1><?php _e('VORTEX AI Engine - Real-Time Logs', 'vortex-ai-engine'); ?></h1>
            
            <!-- Statistics Dashboard -->
            <div class="vortex-log-dashboard">
                <div class="vortex-log-stats">
                    <h2><?php _e('Log Statistics (Last 7 Days)', 'vortex-ai-engine'); ?></h2>
                    <div class="vortex-stats-grid">
                        <?php
                        $level_counts = array();
                        foreach ($statistics as $stat) {
                            if (!isset($level_counts[$stat->level])) {
                                $level_counts[$stat->level] = 0;
                            }
                            $level_counts[$stat->level] += $stat->count;
                        }
                        
                        foreach ($level_counts as $level => $count) {
                            $class = 'vortex-stat-' . strtolower($level);
                            ?>
                            <div class="vortex-stat-item <?php echo $class; ?>">
                                <span class="vortex-stat-level"><?php echo esc_html($level); ?></span>
                                <span class="vortex-stat-count"><?php echo number_format($count); ?></span>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Database Size -->
                <div class="vortex-db-info">
                    <h3><?php _e('Database Information', 'vortex-ai-engine'); ?></h3>
                    <?php if ($db_size): ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Table', 'vortex-ai-engine'); ?></th>
                                    <th><?php _e('Size (MB)', 'vortex-ai-engine'); ?></th>
                                    <th><?php _e('Rows', 'vortex-ai-engine'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($db_size as $table): ?>
                                    <tr>
                                        <td><?php echo esc_html($table->table_name); ?></td>
                                        <td><?php echo esc_html($table->size_mb); ?></td>
                                        <td><?php echo number_format($table->table_rows); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Active Alerts -->
                <?php if ($active_alerts): ?>
                    <div class="vortex-alerts">
                        <h3><?php _e('Active Alerts', 'vortex-ai-engine'); ?></h3>
                        <?php foreach ($active_alerts as $alert): ?>
                            <div class="vortex-alert-item">
                                <span class="vortex-alert-type"><?php echo esc_html($alert->alert_type); ?></span>
                                <span class="vortex-alert-message"><?php echo esc_html($alert->alert_message); ?></span>
                                <span class="vortex-alert-time"><?php echo esc_html($alert->triggered_at); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Log Filters -->
            <div class="vortex-log-filters">
                <h2><?php _e('Log Filters', 'vortex-ai-engine'); ?></h2>
                <form id="vortex-log-filter-form">
                    <div class="vortex-filter-row">
                        <div class="vortex-filter-group">
                            <label for="log-level"><?php _e('Log Level:', 'vortex-ai-engine'); ?></label>
                            <select id="log-level" name="level">
                                <option value=""><?php _e('All Levels', 'vortex-ai-engine'); ?></option>
                                <option value="DEBUG"><?php _e('Debug', 'vortex-ai-engine'); ?></option>
                                <option value="INFO"><?php _e('Info', 'vortex-ai-engine'); ?></option>
                                <option value="WARNING"><?php _e('Warning', 'vortex-ai-engine'); ?></option>
                                <option value="ERROR"><?php _e('Error', 'vortex-ai-engine'); ?></option>
                                <option value="CRITICAL"><?php _e('Critical', 'vortex-ai-engine'); ?></option>
                                <option value="SECURITY"><?php _e('Security', 'vortex-ai-engine'); ?></option>
                            </select>
                        </div>
                        
                        <div class="vortex-filter-group">
                            <label for="log-user"><?php _e('User ID:', 'vortex-ai-engine'); ?></label>
                            <input type="number" id="log-user" name="user_id" placeholder="<?php _e('All Users', 'vortex-ai-engine'); ?>">
                        </div>
                        
                        <div class="vortex-filter-group">
                            <label for="log-date-from"><?php _e('Date From:', 'vortex-ai-engine'); ?></label>
                            <input type="date" id="log-date-from" name="date_from">
                        </div>
                        
                        <div class="vortex-filter-group">
                            <label for="log-date-to"><?php _e('Date To:', 'vortex-ai-engine'); ?></label>
                            <input type="date" id="log-date-to" name="date_to">
                        </div>
                        
                        <div class="vortex-filter-group">
                            <button type="submit" class="button button-primary"><?php _e('Filter Logs', 'vortex-ai-engine'); ?></button>
                            <button type="button" id="vortex-clear-filters" class="button"><?php _e('Clear Filters', 'vortex-ai-engine'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Log Actions -->
            <div class="vortex-log-actions">
                <button id="vortex-export-logs" class="button button-secondary"><?php _e('Export Logs', 'vortex-ai-engine'); ?></button>
                <button id="vortex-clear-logs" class="button button-secondary"><?php _e('Clear Old Logs', 'vortex-ai-engine'); ?></button>
                <button id="vortex-optimize-db" class="button button-secondary"><?php _e('Optimize Database', 'vortex-ai-engine'); ?></button>
                <button id="vortex-refresh-logs" class="button button-primary"><?php _e('Refresh Logs', 'vortex-ai-engine'); ?></button>
            </div>
            
            <!-- Log Table -->
            <div class="vortex-log-container">
                <div id="vortex-log-loading" class="vortex-loading" style="display: none;">
                    <?php _e('Loading logs...', 'vortex-ai-engine'); ?>
                </div>
                
                <div id="vortex-log-table-container">
                    <table id="vortex-log-table" class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Timestamp', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Level', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Message', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('User', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('IP Address', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Context', 'vortex-ai-engine'); ?></th>
                                <th><?php _e('Actions', 'vortex-ai-engine'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="vortex-log-tbody">
                            <!-- Logs will be loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <div id="vortex-log-pagination" class="vortex-pagination">
                    <!-- Pagination will be added here -->
                </div>
            </div>
            
            <!-- Log Details Modal -->
            <div id="vortex-log-modal" class="vortex-modal" style="display: none;">
                <div class="vortex-modal-content">
                    <span class="vortex-modal-close">&times;</span>
                    <h2><?php _e('Log Entry Details', 'vortex-ai-engine'); ?></h2>
                    <div id="vortex-log-details">
                        <!-- Log details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/template" id="vortex-log-row-template">
            <tr class="vortex-log-row vortex-log-level-{{level}}">
                <td>{{timestamp}}</td>
                <td><span class="vortex-log-level vortex-log-level-{{level}}">{{level}}</span></td>
                <td>{{message}}</td>
                <td>{{user_info}}</td>
                <td>{{ip_address}}</td>
                <td>
                    {{#if has_context}}
                        <button class="button button-small vortex-view-context" data-log-id="{{id}}"><?php _e('View', 'vortex-ai-engine'); ?></button>
                    {{else}}
                        -
                    {{/if}}
                </td>
                <td>
                    <button class="button button-small vortex-view-details" data-log-id="{{id}}"><?php _e('Details', 'vortex-ai-engine'); ?></button>
                </td>
            </tr>
        </script>
        <?php
    }
    
    /**
     * AJAX: Get logs
     */
    public function ajax_get_logs() {
        check_ajax_referer('vortex_log_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $filters = array();
        
        if (!empty($_POST['level'])) {
            $filters['level'] = sanitize_text_field($_POST['level']);
        }
        
        if (!empty($_POST['user_id'])) {
            $filters['user_id'] = intval($_POST['user_id']);
        }
        
        if (!empty($_POST['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_POST['date_from']);
        }
        
        if (!empty($_POST['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_POST['date_to']);
        }
        
        $logger = VORTEX_Realtime_Logger::get_instance();
        $logs = $logger->get_logs($filters);
        
        // Process logs for display
        $processed_logs = array();
        foreach ($logs as $log) {
            $user_info = $log['user_id'] ? get_userdata($log['user_id']) : null;
            
            $processed_logs[] = array(
                'id' => $log['id'],
                'timestamp' => $log['timestamp'],
                'level' => $log['level'],
                'message' => $log['message'],
                'user_info' => $user_info ? $user_info->user_login : __('Guest', 'vortex-ai-engine'),
                'ip_address' => $log['ip_address'],
                'has_context' => !empty($log['context']),
                'context' => $log['context'],
                'encrypted' => $log['encrypted']
            );
        }
        
        wp_send_json_success(array(
            'logs' => $processed_logs,
            'total' => count($processed_logs)
        ));
    }
    
    /**
     * AJAX: Export logs
     */
    public function ajax_export_logs() {
        check_ajax_referer('vortex_log_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $logger = VORTEX_Realtime_Logger::get_instance();
        $logs = $logger->get_logs();
        
        $filename = 'vortex-logs-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Timestamp',
            'Level',
            'Message',
            'User ID',
            'IP Address',
            'Request URI',
            'Encrypted'
        ));
        
        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log['timestamp'],
                $log['level'],
                $log['message'],
                $log['user_id'],
                $log['ip_address'],
                $log['request_uri'],
                $log['encrypted'] ? 'Yes' : 'No'
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX: Clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('vortex_log_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $days = intval($_POST['days'] ?? 30);
        $deleted = VORTEX_Log_Database::clean_old_data($days);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Cleared %d log entries older than %d days', 'vortex-ai-engine'), $deleted['logs'], $days),
            'deleted' => $deleted
        ));
    }
    
    /**
     * AJAX: Get statistics
     */
    public function ajax_get_statistics() {
        check_ajax_referer('vortex_log_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'vortex-ai-engine'));
        }
        
        $days = intval($_POST['days'] ?? 7);
        $statistics = VORTEX_Log_Database::get_statistics($days);
        
        wp_send_json_success($statistics);
    }
}

// Initialize the admin interface
new VORTEX_Log_Admin(); 