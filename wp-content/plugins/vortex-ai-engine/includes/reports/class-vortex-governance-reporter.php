<?php
/**
 * Vortex AI Engine - Governance Reporter
 * 
 * Generates monthly self-improvement reports from audit runner outputs
 * Provides comprehensive analysis for team review and decision making
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Governance_Reporter {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * AWS clients
     */
    private $dynamodb_client;
    private $cloudwatch_client;
    
    /**
     * Report configuration
     */
    private $report_config = [
        'report_dir' => 'governance-reports/',
        'retention_months' => 12,
        'metrics_retention_days' => 90,
        'report_template' => 'monthly-governance-template.html'
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
            
            $this->dynamodb_client = new Aws\DynamoDB\DynamoDBClient($config);
            $this->cloudwatch_client = new Aws\CloudWatch\CloudWatchClient($config);
            
        } catch (Exception $e) {
            error_log("Vortex Governance Reporter: AWS client initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule monthly report generation
        add_action('vortex_generate_monthly_report', [$this, 'generate_monthly_report']);
        
        // Setup cron job for monthly reports
        if (!wp_next_scheduled('vortex_generate_monthly_report')) {
            wp_schedule_event(time(), 'monthly', 'vortex_generate_monthly_report');
        }
        
        // Manual report generation
        add_action('wp_ajax_vortex_generate_governance_report', [$this, 'generate_manual_report']);
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Governance Reports',
            'Reports',
            'manage_options',
            'vortex-governance-reports',
            [$this, 'render_reports_page']
        );
    }
    
    /**
     * Generate monthly governance report
     */
    public function generate_monthly_report() {
        $report_month = date('Y-m', strtotime('-1 month'));
        $this->generate_report($report_month);
    }
    
    /**
     * Generate manual report via AJAX
     */
    public function generate_manual_report() {
        check_ajax_referer('vortex_governance_nonce', 'nonce');
        
        $report_month = sanitize_text_field($_POST['report_month'] ?? date('Y-m'));
        $report = $this->generate_report($report_month);
        
        if ($report) {
            wp_send_json_success([
                'message' => 'Governance report generated successfully',
                'report_url' => $report['url'],
                'report_data' => $report
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate governance report']);
        }
    }
    
    /**
     * Generate comprehensive governance report
     */
    public function generate_report($report_month) {
        error_log("Vortex Governance Reporter: Generating report for " . $report_month);
        
        $start_time = microtime(true);
        
        // Collect data for the month
        $report_data = [
            'report_month' => $report_month,
            'generated_at' => current_time('timestamp'),
            'audit_summary' => $this->collect_audit_summary($report_month),
            'performance_metrics' => $this->collect_performance_metrics($report_month),
            'feedback_analysis' => $this->collect_feedback_analysis($report_month),
            'model_training_summary' => $this->collect_model_training_summary($report_month),
            'regression_analysis' => $this->collect_regression_analysis($report_month),
            'recommendations' => $this->generate_recommendations($report_month),
            'trends' => $this->analyze_trends($report_month)
        ];
        
        // Generate report file
        $report_file = $this->create_report_file($report_data);
        
        if (!$report_file) {
            error_log("Vortex Governance Reporter: Failed to create report file");
            return false;
        }
        
        // Store report metadata
        $this->store_report_metadata($report_data, $report_file);
        
        // Send notifications
        $this->send_report_notifications($report_data, $report_file);
        
        $generation_time = round((microtime(true) - $start_time) * 1000);
        error_log("Vortex Governance Reporter: Report generated in {$generation_time}ms");
        
        return [
            'url' => $report_file,
            'data' => $report_data,
            'generation_time_ms' => $generation_time
        ];
    }
    
    /**
     * Collect audit summary for the month
     */
    private function collect_audit_summary($report_month) {
        $start_date = strtotime($report_month . '-01');
        $end_date = strtotime($report_month . '-01 +1 month -1 day');
        
        $audit_reports = $this->get_audit_reports_in_range($start_date, $end_date);
        
        $summary = [
            'total_audits' => count($audit_reports),
            'successful_audits' => 0,
            'failed_audits' => 0,
            'total_checks' => 0,
            'total_errors' => 0,
            'total_warnings' => 0,
            'avg_duration_ms' => 0,
            'critical_issues' => [],
            'regressions_detected' => 0
        ];
        
        $total_duration = 0;
        
        foreach ($audit_reports as $report) {
            $summary['total_checks'] += $report['total_checks'] ?? 0;
            $summary['total_errors'] += $report['errors'] ?? 0;
            $summary['total_warnings'] += $report['warnings'] ?? 0;
            $total_duration += $report['duration_ms'] ?? 0;
            
            if (($report['errors'] ?? 0) === 0) {
                $summary['successful_audits']++;
            } else {
                $summary['failed_audits']++;
            }
            
            // Collect critical issues
            if (isset($report['regressions']) && !empty($report['regressions']['critical'])) {
                $summary['critical_issues'] = array_merge(
                    $summary['critical_issues'],
                    $report['regressions']['critical']
                );
                $summary['regressions_detected']++;
            }
        }
        
        if ($summary['total_audits'] > 0) {
            $summary['avg_duration_ms'] = round($total_duration / $summary['total_audits']);
        }
        
        return $summary;
    }
    
    /**
     * Collect performance metrics for the month
     */
    private function collect_performance_metrics($report_month) {
        $start_date = strtotime($report_month . '-01');
        $end_date = strtotime($report_month . '-01 +1 month -1 day');
        
        try {
            $metrics = [
                'avg_response_time' => $this->get_metric_average('ResponseTime', $start_date, $end_date),
                'p95_response_time' => $this->get_metric_percentile('ResponseTime', 95, $start_date, $end_date),
                'error_rate' => $this->get_metric_average('ErrorRate', $start_date, $end_date),
                'throughput_rps' => $this->get_metric_average('Throughput', $start_date, $end_date),
                'user_satisfaction' => $this->get_metric_average('UserSatisfaction', $start_date, $end_date),
                'model_swap_frequency' => $this->get_metric_average('ModelSwapFrequency', $start_date, $end_date),
                'daily_trends' => $this->get_daily_metrics_trends($start_date, $end_date)
            ];
            
            return $metrics;
            
        } catch (Exception $e) {
            error_log("Vortex Governance Reporter: Failed to collect performance metrics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Collect feedback analysis for the month
     */
    private function collect_feedback_analysis($report_month) {
        $start_date = strtotime($report_month . '-01');
        $end_date = strtotime($report_month . '-01 +1 month -1 day');
        
        try {
            $result = $this->dynamodb_client->query([
                'TableName' => VORTEX_DYNAMODB_TABLE,
                'KeyConditionExpression' => 'PK = :pk AND #ts BETWEEN :start_date AND :end_date',
                'ExpressionAttributeNames' => [
                    '#ts' => 'Timestamp'
                ],
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => 'FEEDBACK'],
                    ':start_date' => ['N' => (string)$start_date],
                    ':end_date' => ['N' => (string)$end_date]
                ]
            ]);
            
            $feedback_data = $result['Items'] ?? [];
            
            $analysis = [
                'total_feedback' => count($feedback_data),
                'positive_feedback' => 0,
                'negative_feedback' => 0,
                'neutral_feedback' => 0,
                'avg_rating' => 0,
                'top_agents' => [],
                'feedback_trends' => [],
                'user_tier_breakdown' => []
            ];
            
            $total_rating = 0;
            $rating_count = 0;
            $agent_counts = [];
            $tier_counts = [];
            
            foreach ($feedback_data as $item) {
                $rating = intval($item['Rating']['N'] ?? 0);
                $thumbs_up = $item['ThumbsUp']['BOOL'] ?? false;
                $thumbs_down = $item['ThumbsDown']['BOOL'] ?? false;
                $agent_name = $item['AgentName']['S'] ?? 'unknown';
                $user_tier = $item['UserTier']['S'] ?? 'unknown';
                
                // Categorize feedback
                if ($thumbs_up || $rating >= 4) {
                    $analysis['positive_feedback']++;
                } elseif ($thumbs_down || $rating <= 2) {
                    $analysis['negative_feedback']++;
                } else {
                    $analysis['neutral_feedback']++;
                }
                
                // Calculate average rating
                if ($rating > 0) {
                    $total_rating += $rating;
                    $rating_count++;
                }
                
                // Count by agent
                $agent_counts[$agent_name] = ($agent_counts[$agent_name] ?? 0) + 1;
                
                // Count by user tier
                $tier_counts[$user_tier] = ($tier_counts[$user_tier] ?? 0) + 1;
            }
            
            if ($rating_count > 0) {
                $analysis['avg_rating'] = round($total_rating / $rating_count, 2);
            }
            
            // Get top agents
            arsort($agent_counts);
            $analysis['top_agents'] = array_slice($agent_counts, 0, 5, true);
            
            // Get user tier breakdown
            $analysis['user_tier_breakdown'] = $tier_counts;
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log("Vortex Governance Reporter: Failed to collect feedback analysis: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Collect model training summary for the month
     */
    private function collect_model_training_summary($report_month) {
        $start_date = strtotime($report_month . '-01');
        $end_date = strtotime($report_month . '-01 +1 month -1 day');
        
        $training_jobs = get_option('vortex_training_jobs', []);
        $model_versions = get_option('vortex_model_versions', []);
        
        $summary = [
            'total_training_jobs' => 0,
            'successful_jobs' => 0,
            'failed_jobs' => 0,
            'avg_training_duration' => 0,
            'models_deployed' => 0,
            'ab_tests_conducted' => 0,
            'performance_improvements' => [],
            'training_data_size' => 0
        ];
        
        $total_duration = 0;
        $jobs_in_period = 0;
        
        foreach ($training_jobs as $job) {
            $job_time = $job['started_at'] ?? 0;
            
            if ($job_time >= $start_date && $job_time <= $end_date) {
                $summary['total_training_jobs']++;
                $jobs_in_period++;
                
                if (($job['status'] ?? '') === 'completed') {
                    $summary['successful_jobs']++;
                    $total_duration += $job['duration_ms'] ?? 0;
                } else {
                    $summary['failed_jobs']++;
                }
            }
        }
        
        if ($jobs_in_period > 0) {
            $summary['avg_training_duration'] = round($total_duration / $jobs_in_period);
        }
        
        // Count deployed models and A/B tests
        foreach ($model_versions as $version) {
            $version_time = $version['created_at'] ?? 0;
            
            if ($version_time >= $start_date && $version_time <= $end_date) {
                if (($version['status'] ?? '') === 'promoted') {
                    $summary['models_deployed']++;
                }
                
                if (($version['status'] ?? '') === 'testing') {
                    $summary['ab_tests_conducted']++;
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Collect regression analysis for the month
     */
    private function collect_regression_analysis($report_month) {
        $start_date = strtotime($report_month . '-01');
        $end_date = strtotime($report_month . '-01 +1 month -1 day');
        
        $regressions = [
            'total_regressions' => 0,
            'critical_regressions' => 0,
            'warning_regressions' => 0,
            'regression_types' => [],
            'most_affected_agents' => [],
            'resolution_time_avg' => 0,
            'regression_trends' => []
        ];
        
        // Get regression data from audit reports
        $audit_reports = $this->get_audit_reports_in_range($start_date, $end_date);
        
        foreach ($audit_reports as $report) {
            if (isset($report['regressions'])) {
                $regressions['total_regressions'] += count($report['regressions']['critical'] ?? []);
                $regressions['total_regressions'] += count($report['regressions']['warnings'] ?? []);
                $regressions['critical_regressions'] += count($report['regressions']['critical'] ?? []);
                $regressions['warning_regressions'] += count($report['regressions']['warnings'] ?? []);
                
                // Collect regression types
                foreach ($report['regressions']['critical'] ?? [] as $regression) {
                    $type = $regression['type'] ?? 'unknown';
                    $regressions['regression_types'][$type] = ($regressions['regression_types'][$type] ?? 0) + 1;
                }
            }
        }
        
        return $regressions;
    }
    
    /**
     * Generate recommendations based on analysis
     */
    private function generate_recommendations($report_month) {
        $recommendations = [];
        
        // Get analysis data
        $audit_summary = $this->collect_audit_summary($report_month);
        $performance_metrics = $this->collect_performance_metrics($report_month);
        $feedback_analysis = $this->collect_feedback_analysis($report_month);
        $regression_analysis = $this->collect_regression_analysis($report_month);
        
        // Performance recommendations
        if (($performance_metrics['avg_response_time'] ?? 0) > 2000) {
            $recommendations[] = [
                'category' => 'performance',
                'priority' => 'high',
                'title' => 'Optimize Response Time',
                'description' => 'Average response time is above 2 seconds. Consider model optimization or infrastructure scaling.',
                'action_items' => [
                    'Review model complexity and optimization opportunities',
                    'Consider implementing caching strategies',
                    'Evaluate infrastructure scaling needs'
                ]
            ];
        }
        
        // Error rate recommendations
        if (($performance_metrics['error_rate'] ?? 0) > 0.05) {
            $recommendations[] = [
                'category' => 'reliability',
                'priority' => 'critical',
                'title' => 'Reduce Error Rate',
                'description' => 'Error rate is above 5%. Immediate attention required.',
                'action_items' => [
                    'Investigate root causes of errors',
                    'Implement additional error handling',
                    'Review and improve error monitoring'
                ]
            ];
        }
        
        // Satisfaction recommendations
        if (($performance_metrics['user_satisfaction'] ?? 0) < 0.8) {
            $recommendations[] = [
                'category' => 'user_experience',
                'priority' => 'high',
                'title' => 'Improve User Satisfaction',
                'description' => 'User satisfaction is below 80%. Focus on quality improvements.',
                'action_items' => [
                    'Analyze negative feedback patterns',
                    'Improve model response quality',
                    'Enhance user interface and experience'
                ]
            ];
        }
        
        // Regression recommendations
        if (($regression_analysis['critical_regressions'] ?? 0) > 0) {
            $recommendations[] = [
                'category' => 'quality',
                'priority' => 'critical',
                'title' => 'Address Critical Regressions',
                'description' => 'Critical regressions detected. Immediate fixes required.',
                'action_items' => [
                    'Review and fix critical regression issues',
                    'Improve regression detection and prevention',
                    'Enhance testing and validation processes'
                ]
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Analyze trends over time
     */
    private function analyze_trends($report_month) {
        $trends = [
            'performance_trend' => 'stable',
            'satisfaction_trend' => 'stable',
            'error_rate_trend' => 'stable',
            'training_frequency_trend' => 'stable',
            'regression_frequency_trend' => 'stable'
        ];
        
        // This would analyze historical data to determine trends
        // Simplified implementation for now
        
        return $trends;
    }
    
    /**
     * Create report file
     */
    private function create_report_file($report_data) {
        $report_dir = VORTEX_PLUGIN_DIR . $this->report_config['report_dir'];
        
        if (!is_dir($report_dir)) {
            wp_mkdir_p($report_dir);
        }
        
        $filename = 'governance-report-' . $report_data['report_month'] . '.html';
        $filepath = $report_dir . $filename;
        
        $html_content = $this->generate_report_html($report_data);
        
        if (file_put_contents($filepath, $html_content)) {
            return VORTEX_PLUGIN_URL . $this->report_config['report_dir'] . $filename;
        }
        
        return false;
    }
    
    /**
     * Generate HTML report content
     */
    private function generate_report_html($report_data) {
        $html = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Vortex AI Engine - Governance Report ' . $report_data['report_month'] . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                .header { text-align: center; border-bottom: 3px solid #0073aa; padding-bottom: 20px; margin-bottom: 30px; }
                .section { margin-bottom: 30px; }
                .section h2 { color: #0073aa; border-left: 4px solid #0073aa; padding-left: 15px; }
                .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
                .metric-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
                .metric-value { font-size: 2em; font-weight: bold; color: #0073aa; }
                .metric-label { color: #6c757d; margin-top: 10px; }
                .recommendation { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .recommendation.critical { background: #f8d7da; border-color: #f5c6cb; }
                .recommendation.high { background: #fff3cd; border-color: #ffeaa7; }
                .recommendation.medium { background: #d1ecf1; border-color: #bee5eb; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🤖 Vortex AI Engine - Governance Report</h1>
                <p><strong>Report Period:</strong> ' . $report_data['report_month'] . '</p>
                <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s', $report_data['generated_at']) . '</p>
            </div>
            
            <div class="section">
                <h2>📊 Executive Summary</h2>
                <div class="metric-grid">
                    <div class="metric-card">
                        <div class="metric-value">' . $report_data['audit_summary']['successful_audits'] . '</div>
                        <div class="metric-label">Successful Audits</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . round($report_data['performance_metrics']['user_satisfaction'] * 100, 1) . '%</div>
                        <div class="metric-label">User Satisfaction</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . $report_data['regression_analysis']['critical_regressions'] . '</div>
                        <div class="metric-label">Critical Regressions</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . $report_data['model_training_summary']['models_deployed'] . '</div>
                        <div class="metric-label">Models Deployed</div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2>🔍 Audit Summary</h2>
                <table>
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Total Audits</td><td>' . $report_data['audit_summary']['total_audits'] . '</td></tr>
                    <tr><td>Successful Audits</td><td>' . $report_data['audit_summary']['successful_audits'] . '</td></tr>
                    <tr><td>Failed Audits</td><td>' . $report_data['audit_summary']['failed_audits'] . '</td></tr>
                    <tr><td>Total Checks</td><td>' . $report_data['audit_summary']['total_checks'] . '</td></tr>
                    <tr><td>Total Errors</td><td>' . $report_data['audit_summary']['total_errors'] . '</td></tr>
                    <tr><td>Total Warnings</td><td>' . $report_data['audit_summary']['total_warnings'] . '</td></tr>
                    <tr><td>Average Duration</td><td>' . $report_data['audit_summary']['avg_duration_ms'] . 'ms</td></tr>
                </table>
            </div>
            
            <div class="section">
                <h2>📈 Performance Metrics</h2>
                <table>
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Average Response Time</td><td>' . round($report_data['performance_metrics']['avg_response_time']) . 'ms</td></tr>
                    <tr><td>95th Percentile Response Time</td><td>' . round($report_data['performance_metrics']['p95_response_time']) . 'ms</td></tr>
                    <tr><td>Error Rate</td><td>' . round($report_data['performance_metrics']['error_rate'] * 100, 2) . '%</td></tr>
                    <tr><td>Throughput</td><td>' . round($report_data['performance_metrics']['throughput_rps'], 2) . ' RPS</td></tr>
                    <tr><td>User Satisfaction</td><td>' . round($report_data['performance_metrics']['user_satisfaction'] * 100, 1) . '%</td></tr>
                </table>
            </div>
            
            <div class="section">
                <h2>💬 Feedback Analysis</h2>
                <table>
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Total Feedback</td><td>' . $report_data['feedback_analysis']['total_feedback'] . '</td></tr>
                    <tr><td>Positive Feedback</td><td>' . $report_data['feedback_analysis']['positive_feedback'] . '</td></tr>
                    <tr><td>Negative Feedback</td><td>' . $report_data['feedback_analysis']['negative_feedback'] . '</td></tr>
                    <tr><td>Average Rating</td><td>' . $report_data['feedback_analysis']['avg_rating'] . '/5</td></tr>
                </table>
            </div>
            
            <div class="section">
                <h2>🧠 Model Training Summary</h2>
                <table>
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Total Training Jobs</td><td>' . $report_data['model_training_summary']['total_training_jobs'] . '</td></tr>
                    <tr><td>Successful Jobs</td><td>' . $report_data['model_training_summary']['successful_jobs'] . '</td></tr>
                    <tr><td>Failed Jobs</td><td>' . $report_data['model_training_summary']['failed_jobs'] . '</td></tr>
                    <tr><td>Models Deployed</td><td>' . $report_data['model_training_summary']['models_deployed'] . '</td></tr>
                    <tr><td>A/B Tests Conducted</td><td>' . $report_data['model_training_summary']['ab_tests_conducted'] . '</td></tr>
                </table>
            </div>
            
            <div class="section">
                <h2>🚨 Regression Analysis</h2>
                <table>
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Total Regressions</td><td>' . $report_data['regression_analysis']['total_regressions'] . '</td></tr>
                    <tr><td>Critical Regressions</td><td>' . $report_data['regression_analysis']['critical_regressions'] . '</td></tr>
                    <tr><td>Warning Regressions</td><td>' . $report_data['regression_analysis']['warning_regressions'] . '</td></tr>
                </table>
            </div>
            
            <div class="section">
                <h2>💡 Recommendations</h2>';
        
        foreach ($report_data['recommendations'] as $recommendation) {
            $html .= '
                <div class="recommendation ' . $recommendation['priority'] . '">
                    <h3>' . $recommendation['title'] . '</h3>
                    <p><strong>Category:</strong> ' . ucfirst($recommendation['category']) . ' | <strong>Priority:</strong> ' . ucfirst($recommendation['priority']) . '</p>
                    <p>' . $recommendation['description'] . '</p>
                    <h4>Action Items:</h4>
                    <ul>';
            
            foreach ($recommendation['action_items'] as $action) {
                $html .= '<li>' . $action . '</li>';
            }
            
            $html .= '
                    </ul>
                </div>';
        }
        
        $html .= '
            </div>
            
            <div class="footer">
                <p>This report was automatically generated by the Vortex AI Engine Governance System.</p>
                <p>For questions or concerns, please contact the development team.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Store report metadata
     */
    private function store_report_metadata($report_data, $report_file) {
        $reports = get_option('vortex_governance_reports', []);
        
        $reports[] = [
            'month' => $report_data['report_month'],
            'file' => $report_file,
            'generated_at' => $report_data['generated_at'],
            'summary' => [
                'audits' => $report_data['audit_summary']['total_audits'],
                'satisfaction' => $report_data['performance_metrics']['user_satisfaction'],
                'regressions' => $report_data['regression_analysis']['critical_regressions']
            ]
        ];
        
        update_option('vortex_governance_reports', $reports);
    }
    
    /**
     * Send report notifications
     */
    private function send_report_notifications($report_data, $report_file) {
        // Send email notification
        $subject = 'Vortex AI Engine - Monthly Governance Report - ' . $report_data['report_month'];
        $message = $this->generate_notification_email($report_data, $report_file);
        
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
        
        // Trigger action for other notification systems
        do_action('vortex_governance_report_generated', $report_data, $report_file);
    }
    
    /**
     * Generate notification email
     */
    private function generate_notification_email($report_data, $report_file) {
        return '
        <html>
        <body>
            <h2>🤖 Vortex AI Engine - Monthly Governance Report</h2>
            <p><strong>Report Period:</strong> ' . $report_data['report_month'] . '</p>
            <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s', $report_data['generated_at']) . '</p>
            
            <h3>📊 Key Highlights</h3>
            <ul>
                <li>Total Audits: ' . $report_data['audit_summary']['total_audits'] . '</li>
                <li>User Satisfaction: ' . round($report_data['performance_metrics']['user_satisfaction'] * 100, 1) . '%</li>
                <li>Critical Regressions: ' . $report_data['regression_analysis']['critical_regressions'] . '</li>
                <li>Models Deployed: ' . $report_data['model_training_summary']['models_deployed'] . '</li>
            </ul>
            
            <p><a href="' . $report_file . '">📄 View Full Report</a></p>
            
            <p>This report was automatically generated by the Vortex AI Engine Governance System.</p>
        </body>
        </html>';
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        $reports = get_option('vortex_governance_reports', []);
        
        ?>
        <div class="wrap">
            <h1>📊 Vortex AI Engine - Governance Reports</h1>
            
            <div class="report-controls">
                <h2>Generate New Report</h2>
                <form id="generate-report-form">
                    <label for="report-month">Report Month:</label>
                    <input type="month" id="report-month" name="report_month" value="<?php echo date('Y-m'); ?>">
                    <button type="submit" class="button button-primary">Generate Report</button>
                </form>
            </div>
            
            <div class="reports-list">
                <h2>Available Reports</h2>
                <?php if (empty($reports)): ?>
                    <p>No reports available yet.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Generated</th>
                                <th>Audits</th>
                                <th>Satisfaction</th>
                                <th>Regressions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($reports) as $report): ?>
                                <tr>
                                    <td><?php echo $report['month']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', $report['generated_at']); ?></td>
                                    <td><?php echo $report['summary']['audits']; ?></td>
                                    <td><?php echo round($report['summary']['satisfaction'] * 100, 1); ?>%</td>
                                    <td><?php echo $report['summary']['regressions']; ?></td>
                                    <td>
                                        <a href="<?php echo $report['file']; ?>" target="_blank" class="button">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                $('#generate-report-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    const reportMonth = $('#report-month').val();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_generate_governance_report',
                            nonce: '<?php echo wp_create_nonce('vortex_governance_nonce'); ?>',
                            report_month: reportMonth
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✅ Report generated successfully!');
                                location.reload();
                            } else {
                                alert('❌ Failed to generate report: ' + response.data.message);
                            }
                        }
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Get audit reports in date range
     */
    private function get_audit_reports_in_range($start_date, $end_date) {
        $audit_dir = VORTEX_PLUGIN_DIR . 'audit-reports/';
        $reports = [];
        
        if (is_dir($audit_dir)) {
            $files = glob($audit_dir . 'audit-report-*.json');
            
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                
                if ($data && isset($data['timestamp'])) {
                    if ($data['timestamp'] >= $start_date && $data['timestamp'] <= $end_date) {
                        $reports[] = $data;
                    }
                }
            }
        }
        
        return $reports;
    }
    
    /**
     * Get metric average from CloudWatch
     */
    private function get_metric_average($metric_name, $start_time, $end_time) {
        try {
            $result = $this->cloudwatch_client->getMetricStatistics([
                'Namespace' => 'VortexAIEngine',
                'MetricName' => $metric_name,
                'StartTime' => date('c', $start_time),
                'EndTime' => date('c', $end_time),
                'Period' => 86400, // Daily
                'Statistics' => ['Average']
            ]);
            
            if (!empty($result['Datapoints'])) {
                $sum = 0;
                $count = 0;
                
                foreach ($result['Datapoints'] as $datapoint) {
                    $sum += $datapoint['Average'];
                    $count++;
                }
                
                return $count > 0 ? $sum / $count : 0;
            }
            
        } catch (Exception $e) {
            error_log("Vortex Governance Reporter: Failed to get metric average: " . $e->getMessage());
        }
        
        return 0;
    }
    
    /**
     * Get metric percentile from CloudWatch
     */
    private function get_metric_percentile($metric_name, $percentile, $start_time, $end_time) {
        try {
            $result = $this->cloudwatch_client->getMetricStatistics([
                'Namespace' => 'VortexAIEngine',
                'MetricName' => $metric_name,
                'StartTime' => date('c', $start_time),
                'EndTime' => date('c', $end_time),
                'Period' => 86400,
                'ExtendedStatistics' => ['p' . $percentile]
            ]);
            
            if (!empty($result['Datapoints'])) {
                $sum = 0;
                $count = 0;
                
                foreach ($result['Datapoints'] as $datapoint) {
                    $sum += $datapoint['ExtendedStatistics']['p' . $percentile];
                    $count++;
                }
                
                return $count > 0 ? $sum / $count : 0;
            }
            
        } catch (Exception $e) {
            error_log("Vortex Governance Reporter: Failed to get metric percentile: " . $e->getMessage());
        }
        
        return 0;
    }
    
    /**
     * Get daily metrics trends
     */
    private function get_daily_metrics_trends($start_time, $end_time) {
        // This would return daily trends for various metrics
        // Simplified implementation
        return [
            'response_time_trend' => 'stable',
            'satisfaction_trend' => 'improving',
            'error_rate_trend' => 'stable'
        ];
    }
}

// Initialize the governance reporter
Vortex_Governance_Reporter::get_instance(); 