<?php
/**
 * VORTEX AI Engine - Monitoring Dashboard
 * 
 * Real-time monitoring dashboard for production deployment
 * Tracks system health, performance, and external integrations
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 * @since 2024-01-01
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../');
    require_once ABSPATH . 'wp-config.php';
}

/**
 * VORTEX Monitoring Dashboard
 */
class VORTEX_Monitoring_Dashboard {
    
    private $data = array();
    private $refresh_interval = 30; // seconds
    
    public function __construct() {
        $this->collect_data();
    }
    
    /**
     * Collect monitoring data
     */
    private function collect_data() {
        $this->data = array(
            'system' => $this->get_system_data(),
            'wordpress' => $this->get_wordpress_data(),
            'vortex' => $this->get_vortex_data(),
            'database' => $this->get_database_data(),
            'aws' => $this->get_aws_data(),
            'blockchain' => $this->get_blockchain_data(),
            'github' => $this->get_github_data(),
            'performance' => $this->get_performance_data(),
            'security' => $this->get_security_data(),
            'alerts' => $this->get_alerts_data()
        );
    }
    
    /**
     * Get system data
     */
    private function get_system_data() {
        return array(
            'server_time' => current_time('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'disk_free_space' => disk_free_space(ABSPATH),
            'disk_total_space' => disk_total_space(ABSPATH),
            'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : array(0, 0, 0),
            'uptime' => $this->get_uptime()
        );
    }
    
    /**
     * Get WordPress data
     */
    private function get_wordpress_data() {
        return array(
            'version' => get_bloginfo('version'),
            'site_url' => get_site_url(),
            'admin_email' => get_option('admin_email'),
            'timezone' => get_option('timezone_string'),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'active_plugins' => count(get_option('active_plugins')),
            'active_theme' => get_template(),
            'users_count' => count_users()['total_users'],
            'posts_count' => wp_count_posts()->publish,
            'comments_count' => wp_count_comments()->total_comments
        );
    }
    
    /**
     * Get VORTEX data
     */
    private function get_vortex_data() {
        $plugin_data = get_plugin_data(ABSPATH . 'wp-content/plugins/vortex-ai-engine/vortex-ai-engine.php');
        
        return array(
            'version' => $plugin_data['Version'],
            'active' => is_plugin_active('vortex-ai-engine/vortex-ai-engine.php'),
            'ai_agents' => $this->get_ai_agents_status(),
            'shortcodes' => $this->get_shortcodes_status(),
            'artworks_count' => $this->get_artworks_count(),
            'users_registered' => $this->get_vortex_users_count(),
            'transactions_count' => $this->get_transactions_count(),
            'nfts_minted' => $this->get_nfts_count()
        );
    }
    
    /**
     * Get database data
     */
    private function get_database_data() {
        global $wpdb;
        
        $vortex_tables = array(
            'vortex_logs',
            'vortex_log_stats',
            'vortex_log_alerts',
            'vortex_github_sync'
        );
        
        $table_data = array();
        foreach ($vortex_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $table_data[$table] = $count;
        }
        
        return array(
            'tables' => $table_data,
            'total_logs' => $table_data['vortex_logs'],
            'logs_today' => $this->get_logs_count_today(),
            'logs_this_week' => $this->get_logs_count_week(),
            'database_size' => $this->get_database_size(),
            'slow_queries' => $this->get_slow_queries_count()
        );
    }
    
    /**
     * Get AWS data
     */
    private function get_aws_data() {
        $aws_config = get_option('vortex_aws_config', array());
        
        return array(
            'configured' => !empty($aws_config),
            's3_bucket' => $aws_config['s3_bucket'] ?? 'Not configured',
            'lambda_function' => $aws_config['lambda_function'] ?? 'Not configured',
            'dynamodb_table' => $aws_config['dynamodb_table'] ?? 'Not configured',
            'cloudfront_distribution' => $aws_config['cloudfront_distribution'] ?? 'Not configured',
            's3_uploads_today' => $this->get_s3_uploads_count(),
            'lambda_invocations' => $this->get_lambda_invocations(),
            'dynamodb_operations' => $this->get_dynamodb_operations()
        );
    }
    
    /**
     * Get blockchain data
     */
    private function get_blockchain_data() {
        $solana_config = get_option('vortex_solana_config', array());
        $tola_config = get_option('vortex_tola_config', array());
        
        return array(
            'solana_configured' => !empty($solana_config),
            'tola_configured' => !empty($tola_config),
            'network' => $solana_config['network'] ?? 'Not configured',
            'tola_token_address' => $tola_config['token_address'] ?? 'Not configured',
            'transactions_today' => $this->get_blockchain_transactions_count(),
            'wallet_connections' => $this->get_wallet_connections_count(),
            'nfts_minted_today' => $this->get_nfts_minted_today(),
            'tola_balance_total' => $this->get_total_tola_balance()
        );
    }
    
    /**
     * Get GitHub data
     */
    private function get_github_data() {
        $github_enabled = get_option('vortex_github_logging_enabled', false);
        
        return array(
            'enabled' => $github_enabled,
            'repository' => get_option('vortex_github_repository', 'Not configured'),
            'branch' => get_option('vortex_github_branch', 'main'),
            'last_sync' => $this->get_last_github_sync(),
            'sync_success_rate' => $this->get_github_sync_success_rate(),
            'logs_synced_today' => $this->get_logs_synced_today(),
            'sync_errors' => $this->get_github_sync_errors()
        );
    }
    
    /**
     * Get performance data
     */
    private function get_performance_data() {
        return array(
            'page_load_time' => $this->get_average_page_load_time(),
            'database_query_time' => $this->get_average_query_time(),
            'memory_usage_percent' => $this->get_memory_usage_percent(),
            'cpu_usage' => $this->get_cpu_usage(),
            'disk_usage_percent' => $this->get_disk_usage_percent(),
            'cache_hit_rate' => $this->get_cache_hit_rate(),
            'error_rate' => $this->get_error_rate()
        );
    }
    
    /**
     * Get security data
     */
    private function get_security_data() {
        return array(
            'failed_logins_today' => $this->get_failed_logins_count(),
            'security_events' => $this->get_security_events_count(),
            'encryption_key_exists' => !empty(get_option('vortex_log_encryption_key')),
            'admin_users_count' => count(get_users(array('role' => 'administrator'))),
            'file_permissions_secure' => $this->check_file_permissions(),
            'ssl_enabled' => is_ssl(),
            'firewall_active' => $this->check_firewall_status()
        );
    }
    
    /**
     * Get alerts data
     */
    private function get_alerts_data() {
        global $wpdb;
        
        $alerts_table = $wpdb->prefix . 'vortex_log_alerts';
        $active_alerts = $wpdb->get_results(
            "SELECT * FROM $alerts_table WHERE status = 'active' ORDER BY triggered_at DESC LIMIT 10"
        );
        
        return array(
            'active_alerts' => $active_alerts,
            'alerts_count' => count($active_alerts),
            'critical_alerts' => $this->get_critical_alerts_count(),
            'recent_alerts' => $this->get_recent_alerts()
        );
    }
    
    /**
     * Render dashboard
     */
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>VORTEX AI Engine - Monitoring Dashboard</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
                .dashboard { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .header { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header h1 { color: #333; margin-bottom: 10px; }
                .status-bar { display: flex; gap: 20px; }
                .status-item { flex: 1; text-align: center; padding: 15px; border-radius: 6px; }
                .status-good { background: #d4edda; color: #155724; }
                .status-warning { background: #fff3cd; color: #856404; }
                .status-error { background: #f8d7da; color: #721c24; }
                .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 20px; }
                .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .card h2 { color: #333; margin-bottom: 15px; font-size: 18px; }
                .metric { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .metric:last-child { border-bottom: none; }
                .metric-label { font-weight: 500; }
                .metric-value { font-weight: 600; }
                .metric-good { color: #28a745; }
                .metric-warning { color: #ffc107; }
                .metric-error { color: #dc3545; }
                .refresh-info { text-align: center; color: #666; margin-top: 20px; }
                .auto-refresh { background: #fff; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .auto-refresh input { margin-right: 10px; }
                .chart-container { height: 200px; margin-top: 15px; }
                @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } .status-bar { flex-direction: column; } }
            </style>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </head>
        <body>
            <div class="dashboard">
                <div class="header">
                    <h1>🚀 VORTEX AI Engine - Monitoring Dashboard</h1>
                    <div class="status-bar">
                        <div class="status-item <?php echo $this->get_overall_status_class(); ?>">
                            <strong>Overall Status</strong><br>
                            <?php echo $this->get_overall_status(); ?>
                        </div>
                        <div class="status-item">
                            <strong>Last Updated</strong><br>
                            <span id="last-updated"><?php echo current_time('H:i:s'); ?></span>
                        </div>
                        <div class="status-item">
                            <strong>Uptime</strong><br>
                            <?php echo $this->data['system']['uptime']; ?>
                        </div>
                        <div class="status-item">
                            <strong>Active Alerts</strong><br>
                            <?php echo $this->data['alerts']['alerts_count']; ?>
                        </div>
                    </div>
                </div>

                <div class="grid">
                    <!-- System Status -->
                    <div class="card">
                        <h2>🖥️ System Status</h2>
                        <div class="metric">
                            <span class="metric-label">PHP Version</span>
                            <span class="metric-value"><?php echo $this->data['system']['php_version']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Memory Usage</span>
                            <span class="metric-value <?php echo $this->get_memory_status_class(); ?>">
                                <?php echo $this->format_bytes($this->data['system']['memory_usage']); ?>
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Disk Usage</span>
                            <span class="metric-value <?php echo $this->get_disk_status_class(); ?>">
                                <?php echo $this->get_disk_usage_percent(); ?>%
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Load Average</span>
                            <span class="metric-value"><?php echo implode(', ', $this->data['system']['load_average']); ?></span>
                        </div>
                    </div>

                    <!-- WordPress Status -->
                    <div class="card">
                        <h2>📝 WordPress Status</h2>
                        <div class="metric">
                            <span class="metric-label">Version</span>
                            <span class="metric-value"><?php echo $this->data['wordpress']['version']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Active Plugins</span>
                            <span class="metric-value"><?php echo $this->data['wordpress']['active_plugins']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Users</span>
                            <span class="metric-value"><?php echo $this->data['wordpress']['users_count']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Posts</span>
                            <span class="metric-value"><?php echo $this->data['wordpress']['posts_count']; ?></span>
                        </div>
                    </div>

                    <!-- VORTEX Status -->
                    <div class="card">
                        <h2>🎨 VORTEX AI Engine</h2>
                        <div class="metric">
                            <span class="metric-label">Version</span>
                            <span class="metric-value"><?php echo $this->data['vortex']['version']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Status</span>
                            <span class="metric-value <?php echo $this->data['vortex']['active'] ? 'metric-good' : 'metric-error'; ?>">
                                <?php echo $this->data['vortex']['active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">AI Agents</span>
                            <span class="metric-value"><?php echo count($this->data['vortex']['ai_agents']); ?> Active</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Artworks</span>
                            <span class="metric-value"><?php echo $this->data['vortex']['artworks_count']; ?></span>
                        </div>
                    </div>

                    <!-- Database Status -->
                    <div class="card">
                        <h2>🗄️ Database Status</h2>
                        <div class="metric">
                            <span class="metric-label">Total Logs</span>
                            <span class="metric-value"><?php echo number_format($this->data['database']['total_logs']); ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Logs Today</span>
                            <span class="metric-value"><?php echo number_format($this->data['database']['logs_today']); ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Database Size</span>
                            <span class="metric-value"><?php echo $this->format_bytes($this->data['database']['database_size']); ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Slow Queries</span>
                            <span class="metric-value"><?php echo $this->data['database']['slow_queries']; ?></span>
                        </div>
                    </div>

                    <!-- AWS Status -->
                    <div class="card">
                        <h2>☁️ AWS Integration</h2>
                        <div class="metric">
                            <span class="metric-label">Status</span>
                            <span class="metric-value <?php echo $this->data['aws']['configured'] ? 'metric-good' : 'metric-warning'; ?>">
                                <?php echo $this->data['aws']['configured'] ? 'Configured' : 'Not Configured'; ?>
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">S3 Bucket</span>
                            <span class="metric-value"><?php echo $this->data['aws']['s3_bucket']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Uploads Today</span>
                            <span class="metric-value"><?php echo $this->data['aws']['s3_uploads_today']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Lambda Invocations</span>
                            <span class="metric-value"><?php echo $this->data['aws']['lambda_invocations']; ?></span>
                        </div>
                    </div>

                    <!-- Blockchain Status -->
                    <div class="card">
                        <h2>⛓️ Blockchain</h2>
                        <div class="metric">
                            <span class="metric-label">Solana</span>
                            <span class="metric-value <?php echo $this->data['blockchain']['solana_configured'] ? 'metric-good' : 'metric-warning'; ?>">
                                <?php echo $this->data['blockchain']['solana_configured'] ? 'Connected' : 'Not Configured'; ?>
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">TOLA Token</span>
                            <span class="metric-value <?php echo $this->data['blockchain']['tola_configured'] ? 'metric-good' : 'metric-warning'; ?>">
                                <?php echo $this->data['blockchain']['tola_configured'] ? 'Configured' : 'Not Configured'; ?>
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Transactions Today</span>
                            <span class="metric-value"><?php echo $this->data['blockchain']['transactions_today']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">NFTs Minted Today</span>
                            <span class="metric-value"><?php echo $this->data['blockchain']['nfts_minted_today']; ?></span>
                        </div>
                    </div>

                    <!-- GitHub Status -->
                    <div class="card">
                        <h2>🐙 GitHub Integration</h2>
                        <div class="metric">
                            <span class="metric-label">Status</span>
                            <span class="metric-value <?php echo $this->data['github']['enabled'] ? 'metric-good' : 'metric-warning'; ?>">
                                <?php echo $this->data['github']['enabled'] ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Repository</span>
                            <span class="metric-value"><?php echo $this->data['github']['repository']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Last Sync</span>
                            <span class="metric-value"><?php echo $this->data['github']['last_sync']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Success Rate</span>
                            <span class="metric-value"><?php echo $this->data['github']['sync_success_rate']; ?>%</span>
                        </div>
                    </div>

                    <!-- Performance Status -->
                    <div class="card">
                        <h2>⚡ Performance</h2>
                        <div class="metric">
                            <span class="metric-label">Page Load Time</span>
                            <span class="metric-value <?php echo $this->get_performance_status_class('page_load'); ?>">
                                <?php echo $this->data['performance']['page_load_time']; ?>ms
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Database Query Time</span>
                            <span class="metric-value <?php echo $this->get_performance_status_class('query'); ?>">
                                <?php echo $this->data['performance']['database_query_time']; ?>ms
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Memory Usage</span>
                            <span class="metric-value <?php echo $this->get_performance_status_class('memory'); ?>">
                                <?php echo $this->data['performance']['memory_usage_percent']; ?>%
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Error Rate</span>
                            <span class="metric-value <?php echo $this->get_performance_status_class('error'); ?>">
                                <?php echo $this->data['performance']['error_rate']; ?>%
                            </span>
                        </div>
                    </div>

                    <!-- Security Status -->
                    <div class="card">
                        <h2>🔒 Security</h2>
                        <div class="metric">
                            <span class="metric-label">SSL Enabled</span>
                            <span class="metric-value <?php echo $this->data['security']['ssl_enabled'] ? 'metric-good' : 'metric-error'; ?>">
                                <?php echo $this->data['security']['ssl_enabled'] ? 'Yes' : 'No'; ?>
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Failed Logins Today</span>
                            <span class="metric-value"><?php echo $this->data['security']['failed_logins_today']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Security Events</span>
                            <span class="metric-value"><?php echo $this->data['security']['security_events']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Encryption Key</span>
                            <span class="metric-value <?php echo $this->data['security']['encryption_key_exists'] ? 'metric-good' : 'metric-error'; ?>">
                                <?php echo $this->data['security']['encryption_key_exists'] ? 'Configured' : 'Missing'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Active Alerts -->
                <?php if (!empty($this->data['alerts']['active_alerts'])): ?>
                <div class="card">
                    <h2>🚨 Active Alerts</h2>
                    <?php foreach ($this->data['alerts']['active_alerts'] as $alert): ?>
                    <div class="metric">
                        <span class="metric-label"><?php echo esc_html($alert->alert_type); ?></span>
                        <span class="metric-value"><?php echo esc_html($alert->alert_message); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Auto-refresh -->
                <div class="auto-refresh">
                    <input type="checkbox" id="auto-refresh" checked>
                    <label for="auto-refresh">Auto-refresh every <?php echo $this->refresh_interval; ?> seconds</label>
                </div>
            </div>

            <script>
                // Auto-refresh functionality
                let refreshInterval;
                const refreshTime = <?php echo $this->refresh_interval * 1000; ?>;
                
                function startAutoRefresh() {
                    refreshInterval = setInterval(() => {
                        location.reload();
                    }, refreshTime);
                }
                
                function stopAutoRefresh() {
                    clearInterval(refreshInterval);
                }
                
                document.getElementById('auto-refresh').addEventListener('change', function() {
                    if (this.checked) {
                        startAutoRefresh();
                    } else {
                        stopAutoRefresh();
                    }
                });
                
                // Start auto-refresh if checked
                if (document.getElementById('auto-refresh').checked) {
                    startAutoRefresh();
                }
                
                // Update last updated time
                setInterval(() => {
                    const now = new Date();
                    document.getElementById('last-updated').textContent = now.toLocaleTimeString();
                }, 1000);
            </script>
        </body>
        </html>
        <?php
    }
    
    // Helper methods for data collection
    private function get_uptime() {
        // This would typically use system commands
        return '24h 15m 30s'; // Placeholder
    }
    
    private function get_ai_agents_status() {
        return array('ARCHER', 'HURAII', 'CLOE', 'HORACE', 'THORIUS');
    }
    
    private function get_shortcodes_status() {
        return array('huraii_generate', 'vortex_wallet', 'vortex_swap', 'vortex_metric');
    }
    
    private function get_artworks_count() {
        return wp_count_posts('vortex_artwork')->publish;
    }
    
    private function get_vortex_users_count() {
        return count(get_users(array('meta_key' => 'vortex_user_type')));
    }
    
    private function get_transactions_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE message LIKE '%blockchain transaction%'");
    }
    
    private function get_nfts_count() {
        return wp_count_posts('vortex_nft')->publish;
    }
    
    private function get_logs_count_today() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
    }
    
    private function get_logs_count_week() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            7
        ));
    }
    
    private function get_database_size() {
        global $wpdb;
        $result = $wpdb->get_results("
            SELECT SUM(data_length + index_length) AS size
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name LIKE '{$wpdb->prefix}vortex_%'
        ");
        return $result[0]->size ?? 0;
    }
    
    private function get_slow_queries_count() {
        // This would typically check MySQL slow query log
        return 0;
    }
    
    private function get_s3_uploads_count() {
        // This would typically check AWS CloudWatch metrics
        return 0;
    }
    
    private function get_lambda_invocations() {
        // This would typically check AWS CloudWatch metrics
        return 0;
    }
    
    private function get_dynamodb_operations() {
        // This would typically check AWS CloudWatch metrics
        return 0;
    }
    
    private function get_blockchain_transactions_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE message LIKE '%blockchain transaction%' AND DATE(created_at) = CURDATE()");
    }
    
    private function get_wallet_connections_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE message LIKE '%wallet connected%' AND DATE(created_at) = CURDATE()");
    }
    
    private function get_nfts_minted_today() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE message LIKE '%NFT minted%' AND DATE(created_at) = CURDATE()");
    }
    
    private function get_total_tola_balance() {
        // This would typically query the blockchain
        return '0 TOLA';
    }
    
    private function get_last_github_sync() {
        global $wpdb;
        $last_sync = $wpdb->get_var("SELECT sync_date FROM {$wpdb->prefix}vortex_github_sync ORDER BY sync_date DESC LIMIT 1");
        return $last_sync ? date('H:i:s', strtotime($last_sync)) : 'Never';
    }
    
    private function get_github_sync_success_rate() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_github_sync");
        $success = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_github_sync WHERE success = 1");
        return $total > 0 ? round(($success / $total) * 100, 1) : 0;
    }
    
    private function get_logs_synced_today() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(logs_count) FROM {$wpdb->prefix}vortex_github_sync WHERE DATE(sync_date) = %s",
            current_time('Y-m-d')
        )) ?? 0;
    }
    
    private function get_github_sync_errors() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_github_sync WHERE success = 0 AND sync_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    }
    
    private function get_average_page_load_time() {
        // This would typically use performance monitoring
        return 250;
    }
    
    private function get_average_query_time() {
        // This would typically use database monitoring
        return 15;
    }
    
    private function get_memory_usage_percent() {
        $usage = $this->data['system']['memory_usage'];
        $limit = $this->parse_memory_limit($this->data['system']['memory_limit']);
        return $limit > 0 ? round(($usage / $limit) * 100, 1) : 0;
    }
    
    private function get_cpu_usage() {
        // This would typically use system monitoring
        return 25;
    }
    
    private function get_disk_usage_percent() {
        $free = $this->data['system']['disk_free_space'];
        $total = $this->data['system']['disk_total_space'];
        return $total > 0 ? round((($total - $free) / $total) * 100, 1) : 0;
    }
    
    private function get_cache_hit_rate() {
        // This would typically use cache monitoring
        return 85;
    }
    
    private function get_error_rate() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $errors = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE level IN ('ERROR', 'CRITICAL') AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        return $total > 0 ? round(($errors / $total) * 100, 2) : 0;
    }
    
    private function get_failed_logins_count() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE message LIKE '%login failed%' AND DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
    }
    
    private function get_security_events_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs WHERE level = 'SECURITY' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    }
    
    private function check_file_permissions() {
        $plugin_dir = ABSPATH . 'wp-content/plugins/vortex-ai-engine/';
        $permissions = substr(sprintf('%o', fileperms($plugin_dir)), -4);
        return $permissions === '0755' || $permissions === '0750';
    }
    
    private function check_firewall_status() {
        // This would typically check firewall status
        return true;
    }
    
    private function get_critical_alerts_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_log_alerts WHERE alert_type = 'critical' AND status = 'active'");
    }
    
    private function get_recent_alerts() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vortex_log_alerts WHERE triggered_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY triggered_at DESC LIMIT 5");
    }
    
    // Status helper methods
    private function get_overall_status() {
        $errors = 0;
        $warnings = 0;
        
        // Count errors and warnings from various systems
        if (!$this->data['vortex']['active']) $errors++;
        if (!$this->data['aws']['configured']) $warnings++;
        if (!$this->data['blockchain']['solana_configured']) $warnings++;
        if ($this->data['alerts']['alerts_count'] > 0) $warnings++;
        
        if ($errors > 0) return 'ERROR';
        if ($warnings > 0) return 'WARNING';
        return 'HEALTHY';
    }
    
    private function get_overall_status_class() {
        $status = $this->get_overall_status();
        switch ($status) {
            case 'HEALTHY': return 'status-good';
            case 'WARNING': return 'status-warning';
            case 'ERROR': return 'status-error';
            default: return 'status-warning';
        }
    }
    
    private function get_memory_status_class() {
        $usage = $this->get_memory_usage_percent();
        if ($usage > 90) return 'metric-error';
        if ($usage > 75) return 'metric-warning';
        return 'metric-good';
    }
    
    private function get_disk_status_class() {
        $usage = $this->get_disk_usage_percent();
        if ($usage > 90) return 'metric-error';
        if ($usage > 80) return 'metric-warning';
        return 'metric-good';
    }
    
    private function get_performance_status_class($type) {
        switch ($type) {
            case 'page_load':
                $time = $this->data['performance']['page_load_time'];
                if ($time > 3000) return 'metric-error';
                if ($time > 1000) return 'metric-warning';
                return 'metric-good';
            case 'query':
                $time = $this->data['performance']['database_query_time'];
                if ($time > 100) return 'metric-error';
                if ($time > 50) return 'metric-warning';
                return 'metric-good';
            case 'memory':
                $usage = $this->data['performance']['memory_usage_percent'];
                if ($usage > 90) return 'metric-error';
                if ($usage > 75) return 'metric-warning';
                return 'metric-good';
            case 'error':
                $rate = $this->data['performance']['error_rate'];
                if ($rate > 5) return 'metric-error';
                if ($rate > 1) return 'metric-warning';
                return 'metric-good';
            default:
                return 'metric-good';
        }
    }
    
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function parse_memory_limit($memory_limit) {
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) substr($memory_limit, 0, -1);
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
}

// Render dashboard if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $dashboard = new VORTEX_Monitoring_Dashboard();
    $dashboard->render();
} 