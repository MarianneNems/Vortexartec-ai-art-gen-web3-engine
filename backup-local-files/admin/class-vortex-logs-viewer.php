<?php
/**
 * VORTEX AI Engine - Logs Viewer
 * 
 * Provides a web interface to view and manage logs
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VortexAIEngine_LogsViewer')) {
class VortexAIEngine_LogsViewer {
    
    private static $instance = null;
    private $logger;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->logger = VortexAIEngine_Logger::getInstance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_logs_menu']);
        add_action('wp_ajax_vortex_get_logs', [$this, 'ajax_get_logs']);
        add_action('wp_ajax_vortex_clear_logs', [$this, 'ajax_clear_logs']);
        add_action('wp_ajax_vortex_export_logs', [$this, 'ajax_export_logs']);
    }
    
    public function add_logs_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'VORTEX AI Logs',
            'System Logs',
            'manage_options',
            'vortex-ai-logs',
            [$this, 'render_logs_page']
        );
    }
    
    public function render_logs_page() {
        ?>
        <div class="wrap">
            <h1>VORTEX AI Engine - System Logs</h1>
            
            <div class="vortex-logs-controls">
                <div class="vortex-logs-filters">
                    <select id="log-level-filter">
                        <option value="">All Levels</option>
                        <option value="DEBUG">Debug</option>
                        <option value="INFO">Info</option>
                        <option value="WARNING">Warning</option>
                        <option value="ERROR">Error</option>
                        <option value="CRITICAL">Critical</option>
                    </select>
                    
                    <select id="log-category-filter">
                        <option value="">All Categories</option>
                        <option value="SYSTEM">System</option>
                        <option value="ACTIVATION">Activation</option>
                        <option value="DATABASE">Database</option>
                        <option value="AI_ORCHESTRATION">AI Orchestration</option>
                        <option value="AGENT_INTERACTION">Agent Interaction</option>
                        <option value="VAULT_OPERATIONS">Vault Operations</option>
                        <option value="SECURITY">Security</option>
                        <option value="TIER_MANAGEMENT">Tier Management</option>
                        <option value="SHORTCODES">Shortcodes</option>
                        <option value="REST_API">REST API</option>
                        <option value="AJAX">AJAX</option>
                        <option value="ASSETS">Assets</option>
                        <option value="PERFORMANCE">Performance</option>
                        <option value="ERROR">Error</option>
                        <option value="USER_ACTION">User Action</option>
                    </select>
                    
                    <input type="text" id="log-search" placeholder="Search logs...">
                    <button id="refresh-logs" class="button">Refresh</button>
                </div>
                
                <div class="vortex-logs-actions">
                    <button id="export-logs" class="button">Export Logs</button>
                    <button id="clear-logs" class="button button-secondary">Clear Logs</button>
                    <button id="download-log-file" class="button">Download Log File</button>
                </div>
            </div>
            
            <div class="vortex-logs-stats">
                <div class="vortex-stat">
                    <span class="stat-label">Total Logs:</span>
                    <span id="total-logs" class="stat-value">0</span>
                </div>
                <div class="vortex-stat">
                    <span class="stat-label">Errors:</span>
                    <span id="error-count" class="stat-value">0</span>
                </div>
                <div class="vortex-stat">
                    <span class="stat-label">Warnings:</span>
                    <span id="warning-count" class="stat-value">0</span>
                </div>
                <div class="vortex-stat">
                    <span class="stat-label">Last Updated:</span>
                    <span id="last-updated" class="stat-value">Never</span>
                </div>
            </div>
            
            <div class="vortex-logs-container">
                <table class="wp-list-table widefat fixed striped vortex-logs-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Level</th>
                            <th>Category</th>
                            <th>Message</th>
                            <th>User</th>
                            <th>Session</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        <tr>
                            <td colspan="7">Loading logs...</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="vortex-logs-pagination">
                    <button id="load-more-logs" class="button">Load More</button>
                    <span id="logs-count">Showing 0 of 0 logs</span>
                </div>
            </div>
            
            <!-- Log Detail Modal -->
            <div id="log-detail-modal" class="vortex-modal" style="display: none;">
                <div class="vortex-modal-content">
                    <div class="vortex-modal-header">
                        <h3>Log Details</h3>
                        <span class="vortex-modal-close">&times;</span>
                    </div>
                    <div class="vortex-modal-body">
                        <div id="log-detail-content"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .vortex-logs-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .vortex-logs-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .vortex-logs-filters select,
        .vortex-logs-filters input {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .vortex-logs-actions {
            display: flex;
            gap: 10px;
        }
        
        .vortex-logs-stats {
            display: flex;
            gap: 30px;
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .vortex-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .vortex-logs-table {
            margin-top: 20px;
        }
        
        .vortex-logs-table th {
            font-weight: bold;
            background: #f1f1f1;
        }
        
        .log-level {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .log-level.debug { background: #e7f3ff; color: #0066cc; }
        .log-level.info { background: #e7f7e7; color: #006600; }
        .log-level.warning { background: #fff3cd; color: #856404; }
        .log-level.error { background: #f8d7da; color: #721c24; }
        .log-level.critical { background: #f5c6cb; color: #721c24; }
        
        .log-category {
            font-size: 11px;
            color: #666;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .log-message {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .vortex-modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .vortex-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 5px;
        }
        
        .vortex-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .vortex-modal-close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .vortex-modal-close:hover {
            color: #000;
        }
        
        .vortex-modal-body {
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .log-detail-section {
            margin-bottom: 20px;
        }
        
        .log-detail-section h4 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .log-detail-section pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 12px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let currentPage = 1;
            let totalLogs = 0;
            
            // Load logs on page load
            loadLogs();
            
            // Filter change handlers
            $('#log-level-filter, #log-category-filter').on('change', function() {
                currentPage = 1;
                loadLogs();
            });
            
            // Search handler
            let searchTimeout;
            $('#log-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    loadLogs();
                }, 500);
            });
            
            // Refresh button
            $('#refresh-logs').on('click', function() {
                loadLogs();
            });
            
            // Load more button
            $('#load-more-logs').on('click', function() {
                currentPage++;
                loadLogs(true);
            });
            
            // Export logs
            $('#export-logs').on('click', function() {
                exportLogs();
            });
            
            // Clear logs
            $('#clear-logs').on('click', function() {
                if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                    clearLogs();
                }
            });
            
            // Download log file
            $('#download-log-file').on('click', function() {
                window.open(ajaxurl + '?action=vortex_download_log_file&nonce=' + vortexLogsNonce, '_blank');
            });
            
            // Modal close
            $('.vortex-modal-close').on('click', function() {
                $('#log-detail-modal').hide();
            });
            
            // Close modal when clicking outside
            $(window).on('click', function(event) {
                if (event.target == document.getElementById('log-detail-modal')) {
                    $('#log-detail-modal').hide();
                }
            });
            
            function loadLogs(append = false) {
                const level = $('#log-level-filter').val();
                const category = $('#log-category-filter').val();
                const search = $('#log-search').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_logs',
                        nonce: vortexLogsNonce,
                        level: level,
                        category: category,
                        search: search,
                        page: currentPage,
                        append: append ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            if (append) {
                                appendLogs(response.data.logs);
                            } else {
                                displayLogs(response.data.logs);
                            }
                            updateStats(response.data.stats);
                            totalLogs = response.data.stats.total;
                            updatePagination();
                        } else {
                            alert('Error loading logs: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error loading logs. Please try again.');
                    }
                });
            }
            
            function displayLogs(logs) {
                const tbody = $('#logs-table-body');
                tbody.empty();
                
                if (logs.length === 0) {
                    tbody.html('<tr><td colspan="7">No logs found</td></tr>');
                    return;
                }
                
                logs.forEach(function(log) {
                    const row = createLogRow(log);
                    tbody.append(row);
                });
            }
            
            function appendLogs(logs) {
                const tbody = $('#logs-table-body');
                
                logs.forEach(function(log) {
                    const row = createLogRow(log);
                    tbody.append(row);
                });
            }
            
            function createLogRow(log) {
                const levelClass = 'log-level ' + log.level.toLowerCase();
                const timestamp = new Date(log.timestamp).toLocaleString();
                
                return `
                    <tr>
                        <td>${timestamp}</td>
                        <td><span class="${levelClass}">${log.level}</span></td>
                        <td><span class="log-category">${log.category}</span></td>
                        <td class="log-message" title="${log.message}">${log.message}</td>
                        <td>${log.user_id}</td>
                        <td>${log.session_id.substring(0, 8)}...</td>
                        <td>
                            <button class="button button-small view-log-detail" data-log-id="${log.id}">View</button>
                        </td>
                    </tr>
                `;
            }
            
            function updateStats(stats) {
                $('#total-logs').text(stats.total);
                $('#error-count').text(stats.errors);
                $('#warning-count').text(stats.warnings);
                $('#last-updated').text(new Date().toLocaleString());
            }
            
            function updatePagination() {
                const currentCount = $('#logs-table-body tr').length;
                $('#logs-count').text(`Showing ${currentCount} of ${totalLogs} logs`);
                
                if (currentCount >= totalLogs) {
                    $('#load-more-logs').hide();
                } else {
                    $('#load-more-logs').show();
                }
            }
            
            function exportLogs() {
                const level = $('#log-level-filter').val();
                const category = $('#log-category-filter').val();
                const search = $('#log-search').val();
                
                const params = new URLSearchParams({
                    action: 'vortex_export_logs',
                    nonce: vortexLogsNonce,
                    level: level,
                    category: category,
                    search: search
                });
                
                window.open(ajaxurl + '?' + params.toString(), '_blank');
            }
            
            function clearLogs() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_clear_logs',
                        nonce: vortexLogsNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Logs cleared successfully');
                            loadLogs();
                        } else {
                            alert('Error clearing logs: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error clearing logs. Please try again.');
                    }
                });
            }
            
            // View log detail
            $(document).on('click', '.view-log-detail', function() {
                const logId = $(this).data('log-id');
                viewLogDetail(logId);
            });
            
            function viewLogDetail(logId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_log_detail',
                        nonce: vortexLogsNonce,
                        log_id: logId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayLogDetail(response.data);
                            $('#log-detail-modal').show();
                        } else {
                            alert('Error loading log detail: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error loading log detail. Please try again.');
                    }
                });
            }
            
            function displayLogDetail(log) {
                const content = $('#log-detail-content');
                const timestamp = new Date(log.timestamp).toLocaleString();
                
                content.html(`
                    <div class="log-detail-section">
                        <h4>Basic Information</h4>
                        <p><strong>Timestamp:</strong> ${timestamp}</p>
                        <p><strong>Level:</strong> <span class="log-level ${log.level.toLowerCase()}">${log.level}</span></p>
                        <p><strong>Category:</strong> <span class="log-category">${log.category}</span></p>
                        <p><strong>Message:</strong> ${log.message}</p>
                        <p><strong>User ID:</strong> ${log.user_id}</p>
                        <p><strong>Session ID:</strong> ${log.session_id}</p>
                        <p><strong>Request ID:</strong> ${log.request_id}</p>
                    </div>
                    
                    <div class="log-detail-section">
                        <h4>Context</h4>
                        <pre>${JSON.stringify(JSON.parse(log.context || '{}'), null, 2)}</pre>
                    </div>
                    
                    <div class="log-detail-section">
                        <h4>Backtrace</h4>
                        <pre>${JSON.stringify(JSON.parse(log.backtrace || '[]'), null, 2)}</pre>
                    </div>
                    
                    <div class="log-detail-section">
                        <h4>Request Data</h4>
                        <pre>${JSON.stringify(JSON.parse(log.request_data || '{}'), null, 2)}</pre>
                    </div>
                `);
            }
        });
        </script>
        <?php
    }
    
    public function ajax_get_logs() {
        check_ajax_referer('vortex_logs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $level = sanitize_text_field($_POST['level'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $append = intval($_POST['append'] ?? 0);
        
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $logs = $this->logger->get_logs($limit, $level, $category, null, $search, $offset);
        $stats = $this->get_log_stats($level, $category, $search);
        
        wp_send_json_success([
            'logs' => $logs,
            'stats' => $stats
        ]);
    }
    
    public function ajax_clear_logs() {
        check_ajax_referer('vortex_logs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_ai_logs';
        
        $result = $wpdb->query("TRUNCATE TABLE {$table_name}");
        
        if ($result !== false) {
            wp_send_json_success('Logs cleared successfully');
        } else {
            wp_send_json_error('Failed to clear logs');
        }
    }
    
    public function ajax_export_logs() {
        check_ajax_referer('vortex_logs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $level = sanitize_text_field($_GET['level'] ?? '');
        $category = sanitize_text_field($_GET['category'] ?? '');
        $search = sanitize_text_field($_GET['search'] ?? '');
        
        $logs = $this->logger->get_logs(1000, $level, $category, null, $search);
        
        $filename = 'vortex-ai-logs-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['Timestamp', 'Level', 'Category', 'Message', 'User ID', 'Session ID', 'Request ID', 'Context']);
        
        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log->timestamp,
                $log->level,
                $log->category,
                $log->message,
                $log->user_id,
                $log->session_id,
                $log->request_id,
                $log->context
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function get_log_stats($level = '', $category = '', $search = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_logs';
        $where_conditions = [];
        $where_values = [];
        
        if ($level) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $level;
        }
        
        if ($category) {
            $where_conditions[] = 'category = %s';
            $where_values[] = $category;
        }
        
        if ($search) {
            $where_conditions[] = 'message LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Total count
        $total_query = "SELECT COUNT(*) FROM {$table_name} {$where_clause}";
        if (!empty($where_values)) {
            $total_query = $wpdb->prepare($total_query, $where_values);
        }
        $total = $wpdb->get_var($total_query);
        
        // Error count
        $error_query = "SELECT COUNT(*) FROM {$table_name} {$where_clause} AND level IN ('ERROR', 'CRITICAL')";
        if (!empty($where_values)) {
            $error_query = $wpdb->prepare($error_query, $where_values);
        }
        $errors = $wpdb->get_var($error_query);
        
        // Warning count
        $warning_query = "SELECT COUNT(*) FROM {$table_name} {$where_clause} AND level = 'WARNING'";
        if (!empty($where_values)) {
            $warning_query = $wpdb->prepare($warning_query, $where_values);
        }
        $warnings = $wpdb->get_var($warning_query);
        
        return [
            'total' => intval($total),
            'errors' => intval($errors),
            'warnings' => intval($warnings)
        ];
    }
}
} 