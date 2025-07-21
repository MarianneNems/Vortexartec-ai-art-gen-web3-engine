<?php
/**
 * Vortex AI Engine - Monitoring Dashboard
 * 
 * Real-time monitoring dashboard for feedback, performance, and audit health
 * Integrates with CloudWatch, DynamoDB, and GitHub for comprehensive monitoring
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Monitoring_Dashboard {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * AWS clients
     */
    private $cloudwatch_client;
    private $dynamodb_client;
    
    /**
     * Dashboard configuration
     */
    private $dashboard_config = [
        'refresh_interval' => 30, // seconds
        'metrics_retention_days' => 30,
        'alert_thresholds' => [
            'error_rate' => 0.05,
            'latency_p95' => 3000,
            'satisfaction_drop' => 0.1
        ]
    ];
    
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
        $this->init_aws_clients();
        $this->init_hooks();
    }
    
    /**
     * Initialize AWS clients
     */
    private function init_aws_clients() {
        try {
            $config = [
                'version' => 'latest',
                'region'  => VORTEX_AWS_REGION,
                'credentials' => [
                    'key'    => VORTEX_AWS_ACCESS_KEY,
                    'secret' => VORTEX_AWS_SECRET_KEY,
                ]
            ];
            
            $this->cloudwatch_client = new Aws\CloudWatch\CloudWatchClient($config);
            $this->dynamodb_client = new Aws\DynamoDB\DynamoDBClient($config);
            
        } catch (Exception $e) {
            error_log("Vortex Monitoring Dashboard: AWS client initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // AJAX endpoints for real-time data
        add_action('wp_ajax_vortex_get_dashboard_metrics', [$this, 'get_dashboard_metrics']);
        add_action('wp_ajax_vortex_get_audit_status', [$this, 'get_audit_status']);
        add_action('wp_ajax_vortex_get_model_performance', [$this, 'get_model_performance']);
        
        // Enqueue dashboard assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Monitoring Dashboard',
            'Monitoring',
            'manage_options',
            'vortex-monitoring',
            [$this, 'render_dashboard_page']
        );
    }
    
    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets($hook) {
        if ($hook !== 'vortex-ai-engine_page_vortex-monitoring') {
            return;
        }
        
        wp_enqueue_script(
            'vortex-monitoring-dashboard',
            VORTEX_PLUGIN_URL . 'assets/js/monitoring-dashboard.js',
            ['jquery', 'wp-util'],
            VORTEX_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vortex-monitoring-dashboard',
            VORTEX_PLUGIN_URL . 'assets/css/monitoring-dashboard.css',
            [],
            VORTEX_VERSION
        );
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('vortex-monitoring-dashboard', 'vortexMonitoring', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_monitoring_nonce'),
            'refreshInterval' => $this->dashboard_config['refresh_interval'] * 1000
        ]);
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap vortex-monitoring-dashboard">
            <h1>🤖 Vortex AI Engine - Monitoring Dashboard</h1>
            
            <div class="dashboard-header">
                <div class="last-updated">
                    Last updated: <span id="last-updated-time"><?php echo current_time('Y-m-d H:i:s'); ?></span>
                </div>
                <div class="refresh-controls">
                    <button id="refresh-dashboard" class="button button-primary">🔄 Refresh</button>
                    <label>
                        <input type="checkbox" id="auto-refresh" checked> Auto-refresh
                    </label>
                </div>
            </div>
            
            <!-- System Health Overview -->
            <div class="dashboard-section">
                <h2>📊 System Health Overview</h2>
                <div class="health-metrics">
                    <div class="metric-card" id="overall-health">
                        <div class="metric-value">--</div>
                        <div class="metric-label">Overall Health</div>
                        <div class="metric-status"></div>
                    </div>
                    <div class="metric-card" id="error-rate">
                        <div class="metric-value">--</div>
                        <div class="metric-label">Error Rate</div>
                        <div class="metric-status"></div>
                    </div>
                    <div class="metric-card" id="response-time">
                        <div class="metric-value">--</div>
                        <div class="metric-label">Avg Response Time</div>
                        <div class="metric-status"></div>
                    </div>
                    <div class="metric-card" id="satisfaction">
                        <div class="metric-value">--</div>
                        <div class="metric-label">User Satisfaction</div>
                        <div class="metric-status"></div>
                    </div>
                </div>
            </div>
            
            <!-- AI Agent Performance -->
            <div class="dashboard-section">
                <h2>🤖 AI Agent Performance</h2>
                <div class="agent-metrics" id="agent-metrics">
                    <!-- Agent metrics will be loaded dynamically -->
                </div>
            </div>
            
            <!-- Feedback Analytics -->
            <div class="dashboard-section">
                <h2>📈 Feedback Analytics</h2>
                <div class="feedback-analytics">
                    <div class="feedback-chart">
                        <canvas id="feedback-chart"></canvas>
                    </div>
                    <div class="feedback-stats">
                        <div class="stat-item">
                            <span class="stat-label">Total Feedback:</span>
                            <span class="stat-value" id="total-feedback">--</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Positive Ratio:</span>
                            <span class="stat-value" id="positive-ratio">--</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Avg Rating:</span>
                            <span class="stat-value" id="avg-rating">--</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Audit Status -->
            <div class="dashboard-section">
                <h2>🔍 Audit Status</h2>
                <div class="audit-status" id="audit-status">
                    <!-- Audit status will be loaded dynamically -->
                </div>
            </div>
            
            <!-- Model Training Status -->
            <div class="dashboard-section">
                <h2>🧠 Model Training Status</h2>
                <div class="training-status" id="training-status">
                    <!-- Training status will be loaded dynamically -->
                </div>
            </div>
            
            <!-- Recent Alerts -->
            <div class="dashboard-section">
                <h2>🚨 Recent Alerts</h2>
                <div class="recent-alerts" id="recent-alerts">
                    <!-- Alerts will be loaded dynamically -->
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-section">
                <h2>⚡ Quick Actions</h2>
                <div class="quick-actions">
                    <button class="button" onclick="runManualAudit()">🔍 Run Manual Audit</button>
                    <button class="button" onclick="triggerModelRetraining()">🧠 Trigger Model Retraining</button>
                    <button class="button" onclick="viewLatestReport()">📄 View Latest Report</button>
                    <button class="button" onclick="viewGitHubIssues()">🐙 View GitHub Issues</button>
                </div>
            </div>
        </div>
        
        <script>
            // Initialize dashboard
            document.addEventListener('DOMContentLoaded', function() {
                initializeDashboard();
            });
            
            function initializeDashboard() {
                loadDashboardMetrics();
                loadAuditStatus();
                loadModelPerformance();
                loadRecentAlerts();
                
                // Setup auto-refresh
                if (document.getElementById('auto-refresh').checked) {
                    setInterval(loadDashboardMetrics, vortexMonitoring.refreshInterval);
                }
            }
            
            function loadDashboardMetrics() {
                jQuery.ajax({
                    url: vortexMonitoring.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_dashboard_metrics',
                        nonce: vortexMonitoring.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            updateDashboardMetrics(response.data);
                        }
                    }
                });
            }
            
            function updateDashboardMetrics(data) {
                // Update health metrics
                document.getElementById('overall-health').querySelector('.metric-value').textContent = data.overall_health + '%';
                document.getElementById('error-rate').querySelector('.metric-value').textContent = data.error_rate + '%';
                document.getElementById('response-time').querySelector('.metric-value').textContent = data.avg_response_time + 'ms';
                document.getElementById('satisfaction').querySelector('.metric-value').textContent = data.user_satisfaction + '%';
                
                // Update feedback stats
                document.getElementById('total-feedback').textContent = data.total_feedback;
                document.getElementById('positive-ratio').textContent = data.positive_ratio + '%';
                document.getElementById('avg-rating').textContent = data.avg_rating + '/5';
                
                // Update last updated time
                document.getElementById('last-updated-time').textContent = new Date().toLocaleString();
            }
            
            function runManualAudit() {
                if (confirm('Run a manual comprehensive audit?')) {
                    jQuery.ajax({
                        url: vortexMonitoring.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'vortex_run_manual_audit',
                            nonce: vortexMonitoring.nonce,
                            audit_type: 'full'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Audit started successfully! Check the audit status for results.');
                                loadAuditStatus();
                            } else {
                                alert('Failed to start audit: ' + response.data.message);
                            }
                        }
                    });
                }
            }
            
            function triggerModelRetraining() {
                if (confirm('Trigger model retraining?')) {
                    jQuery.ajax({
                        url: vortexMonitoring.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'vortex_manual_retrain',
                            nonce: vortexMonitoring.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Model retraining started! Job ID: ' + response.data.job_id);
                                loadModelPerformance();
                            } else {
                                alert('Failed to start retraining: ' + response.data.message);
                            }
                        }
                    });
                }
            }
            
            function viewLatestReport() {
                window.open('<?php echo admin_url('admin-ajax.php?action=vortex_get_latest_report'); ?>', '_blank');
            }
            
            function viewGitHubIssues() {
                window.open('https://github.com/<?php echo $this->get_github_repo(); ?>/issues?q=label%3Aaudit-regression', '_blank');
            }
        </script>
        <?php
    }
    
    /**
     * Get dashboard metrics via AJAX
     */
    public function get_dashboard_metrics() {
        check_ajax_referer('vortex_monitoring_nonce', 'nonce');
        
        $metrics = [
            'overall_health' => $this->calculate_overall_health(),
            'error_rate' => $this->get_error_rate(),
            'avg_response_time' => $this->get_avg_response_time(),
            'user_satisfaction' => $this->get_user_satisfaction(),
            'total_feedback' => $this->get_total_feedback_count(),
            'positive_ratio' => $this->get_positive_feedback_ratio(),
            'avg_rating' => $this->get_average_rating(),
            'timestamp' => current_time('timestamp')
        ];
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Get audit status via AJAX
     */
    public function get_audit_status() {
        check_ajax_referer('vortex_monitoring_nonce', 'nonce');
        
        $audit_status = [
            'last_audit' => $this->get_last_audit_info(),
            'audit_health' => $this->get_audit_health(),
            'recent_issues' => $this->get_recent_audit_issues(),
            'next_scheduled' => $this->get_next_audit_schedule()
        ];
        
        wp_send_json_success($audit_status);
    }
    
    /**
     * Get model performance via AJAX
     */
    public function get_model_performance() {
        check_ajax_referer('vortex_monitoring_nonce', 'nonce');
        
        $model_performance = [
            'current_model' => $this->get_current_model_info(),
            'training_jobs' => $this->get_training_jobs(),
            'ab_tests' => $this->get_ab_test_status(),
            'performance_trends' => $this->get_performance_trends()
        ];
        
        wp_send_json_success($model_performance);
    }
    
    /**
     * Calculate overall system health
     */
    private function calculate_overall_health() {
        $error_rate = $this->get_error_rate();
        $satisfaction = $this->get_user_satisfaction();
        $response_time = $this->get_avg_response_time();
        
        // Health score based on multiple factors
        $health_score = 100;
        
        // Deduct points for high error rate
        if ($error_rate > 0.05) {
            $health_score -= ($error_rate - 0.05) * 1000;
        }
        
        // Deduct points for low satisfaction
        if ($satisfaction < 0.8) {
            $health_score -= (0.8 - $satisfaction) * 100;
        }
        
        // Deduct points for slow response time
        if ($response_time > 2000) {
            $health_score -= ($response_time - 2000) / 100;
        }
        
        return max(0, min(100, round($health_score)));
    }
    
    /**
     * Get error rate from metrics
     */
    private function get_error_rate() {
        try {
            $result = $this->cloudwatch_client->getMetricStatistics([
                'Namespace' => 'VortexAIEngine',
                'MetricName' => 'ErrorRate',
                'StartTime' => date('c', time() - 3600), // Last hour
                'EndTime' => date('c'),
                'Period' => 300, // 5 minutes
                'Statistics' => ['Average']
            ]);
            
            if (!empty($result['Datapoints'])) {
                return round(end($result['Datapoints'])['Average'] * 100, 2);
            }
            
        } catch (Exception $e) {
            error_log("Vortex Monitoring Dashboard: Failed to get error rate: " . $e->getMessage());
        }
        
        return 0.0;
    }
    
    /**
     * Get average response time
     */
    private function get_avg_response_time() {
        try {
            $result = $this->cloudwatch_client->getMetricStatistics([
                'Namespace' => 'VortexAIEngine',
                'MetricName' => 'ResponseTime',
                'StartTime' => date('c', time() - 3600),
                'EndTime' => date('c'),
                'Period' => 300,
                'Statistics' => ['Average']
            ]);
            
            if (!empty($result['Datapoints'])) {
                return round(end($result['Datapoints'])['Average']);
            }
            
        } catch (Exception $e) {
            error_log("Vortex Monitoring Dashboard: Failed to get response time: " . $e->getMessage());
        }
        
        return 0;
    }
    
    /**
     * Get user satisfaction score
     */
    private function get_user_satisfaction() {
        try {
            $result = $this->cloudwatch_client->getMetricStatistics([
                'Namespace' => 'VortexAIEngine',
                'MetricName' => 'UserSatisfaction',
                'StartTime' => date('c', time() - 3600),
                'EndTime' => date('c'),
                'Period' => 300,
                'Statistics' => ['Average']
            ]);
            
            if (!empty($result['Datapoints'])) {
                return round(end($result['Datapoints'])['Average'] * 100, 2);
            }
            
        } catch (Exception $e) {
            error_log("Vortex Monitoring Dashboard: Failed to get satisfaction: " . $e->getMessage());
        }
        
        return 85.0; // Default value
    }
    
    /**
     * Get total feedback count
     */
    private function get_total_feedback_count() {
        try {
            $result = $this->dynamodb_client->query([
                'TableName' => VORTEX_DYNAMODB_TABLE,
                'KeyConditionExpression' => 'PK = :pk',
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => 'FEEDBACK']
                ],
                'Select' => 'COUNT'
            ]);
            
            return $result['Count'] ?? 0;
            
        } catch (Exception $e) {
            error_log("Vortex Monitoring Dashboard: Failed to get feedback count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get positive feedback ratio
     */
    private function get_positive_feedback_ratio() {
        // This would query DynamoDB for positive vs total feedback
        // Simplified implementation
        return 87.5; // Placeholder
    }
    
    /**
     * Get average rating
     */
    private function get_average_rating() {
        // This would query DynamoDB for average rating
        // Simplified implementation
        return 4.2; // Placeholder
    }
    
    /**
     * Get last audit information
     */
    private function get_last_audit_info() {
        $last_audit = get_option('vortex_last_audit', []);
        
        if (!empty($last_audit)) {
            return [
                'timestamp' => $last_audit['timestamp'],
                'type' => $last_audit['type'],
                'file' => $last_audit['file'],
                'status' => 'completed'
            ];
        }
        
        return null;
    }
    
    /**
     * Get audit health status
     */
    private function get_audit_health() {
        $last_audit = $this->get_last_audit_info();
        
        if (!$last_audit) {
            return 'unknown';
        }
        
        // Check if audit is recent (within last hour)
        if (time() - $last_audit['timestamp'] < 3600) {
            return 'healthy';
        } elseif (time() - $last_audit['timestamp'] < 86400) {
            return 'warning';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Get recent audit issues
     */
    private function get_recent_audit_issues() {
        $issues = get_option('vortex_audit_issues', []);
        
        // Return last 5 issues
        return array_slice($issues, -5);
    }
    
    /**
     * Get next audit schedule
     */
    private function get_next_audit_schedule() {
        $next_hourly = wp_next_scheduled('vortex_hourly_audit');
        $next_daily = wp_next_scheduled('vortex_daily_audit');
        
        return [
            'hourly' => $next_hourly ? date('Y-m-d H:i:s', $next_hourly) : 'Not scheduled',
            'daily' => $next_daily ? date('Y-m-d H:i:s', $next_daily) : 'Not scheduled'
        ];
    }
    
    /**
     * Get current model information
     */
    private function get_current_model_info() {
        $routing_config = get_option('vortex_model_routing', []);
        $model_versions = get_option('vortex_model_versions', []);
        
        $production_model = $routing_config['production_model'] ?? 'v1';
        
        return $model_versions[$production_model] ?? [
            'version_id' => 'v1',
            'status' => 'production',
            'created_at' => time() - 86400
        ];
    }
    
    /**
     * Get training jobs
     */
    private function get_training_jobs() {
        $training_jobs = get_option('vortex_training_jobs', []);
        
        // Return last 5 jobs
        return array_slice($training_jobs, -5);
    }
    
    /**
     * Get A/B test status
     */
    private function get_ab_test_status() {
        $routing_config = get_option('vortex_model_routing', []);
        
        return $routing_config['ab_tests'] ?? [];
    }
    
    /**
     * Get performance trends
     */
    private function get_performance_trends() {
        // This would query CloudWatch for performance trends over time
        // Simplified implementation
        return [
            'latency_trend' => 'stable',
            'satisfaction_trend' => 'improving',
            'error_rate_trend' => 'stable'
        ];
    }
    
    /**
     * Get GitHub repository
     */
    private function get_github_repo() {
        return defined('VORTEX_GITHUB_REPO') ? VORTEX_GITHUB_REPO : 'YOUR_USERNAME/vortex-ai-engine';
    }
}

// Initialize the monitoring dashboard
Vortex_Monitoring_Dashboard::get_instance(); 