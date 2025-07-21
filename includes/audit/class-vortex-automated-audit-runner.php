<?php
/**
 * Vortex AI Engine - Automated Audit Runner
 * 
 * Runs periodic audits, compares to baselines, and creates GitHub Issues for regressions
 * Integrates with CI/CD pipeline for continuous monitoring
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Automated_Audit_Runner {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * GitHub API client
     */
    private $github_client;
    
    /**
     * Audit configuration
     */
    private $audit_config = [
        'frequency' => 'hourly', // hourly, daily, weekly
        'baseline_file' => 'audit-baseline.json',
        'report_dir' => 'audit-reports/',
        'github_repo' => 'YOUR_USERNAME/vortex-ai-engine',
        'github_token' => '',
    ];
    
    /**
     * Alert thresholds
     */
    private $regression_thresholds = [
        'error_rate_increase' => 0.05, // 5% increase
        'performance_degradation' => 0.2, // 20% slower
        'satisfaction_drop' => 0.1, // 10% drop
        'new_warnings' => 10, // 10 new warnings
        'missing_files' => 1, // Any missing files
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
        $this->init_github_client();
        $this->init_hooks();
        $this->load_config();
    }
    
    /**
     * Initialize GitHub API client
     */
    private function init_github_client() {
        $this->audit_config['github_token'] = defined('VORTEX_GITHUB_TOKEN') ? VORTEX_GITHUB_TOKEN : '';
        
        if ($this->audit_config['github_token']) {
            // Initialize GitHub API client
            // This would use a proper GitHub API library
        }
    }
    
    /**
     * Load configuration from database
     */
    private function load_config() {
        $saved_config = get_option('vortex_audit_config', []);
        $this->audit_config = array_merge($this->audit_config, $saved_config);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule audit jobs
        add_action('vortex_hourly_audit', [$this, 'run_hourly_audit']);
        add_action('vortex_daily_audit', [$this, 'run_daily_audit']);
        add_action('vortex_weekly_audit', [$this, 'run_weekly_audit']);
        
        // Setup cron schedules
        if (!wp_next_scheduled('vortex_hourly_audit')) {
            wp_schedule_event(time(), 'hourly', 'vortex_hourly_audit');
        }
        
        if (!wp_next_scheduled('vortex_daily_audit')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_audit');
        }
        
        if (!wp_next_scheduled('vortex_weekly_audit')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_audit');
        }
        
        // Manual audit triggers
        add_action('wp_ajax_vortex_run_manual_audit', [$this, 'run_manual_audit']);
        add_action('vortex_audit_completed', [$this, 'process_audit_results']);
    }
    
    /**
     * Run hourly audit
     */
    public function run_hourly_audit() {
        $this->run_audit('hourly');
    }
    
    /**
     * Run daily audit
     */
    public function run_daily_audit() {
        $this->run_audit('daily');
    }
    
    /**
     * Run weekly audit
     */
    public function run_weekly_audit() {
        $this->run_audit('weekly');
    }
    
    /**
     * Run manual audit via AJAX
     */
    public function run_manual_audit() {
        check_ajax_referer('vortex_audit_nonce', 'nonce');
        
        $audit_type = sanitize_text_field($_POST['audit_type'] ?? 'full');
        $results = $this->run_audit($audit_type);
        
        wp_send_json_success($results);
    }
    
    /**
     * Main audit execution
     */
    public function run_audit($audit_type = 'full') {
        $start_time = microtime(true);
        
        // Run the comprehensive audit script
        $audit_script = VORTEX_PLUGIN_DIR . 'scripts/comprehensive-recursive-audit.php';
        
        if (!file_exists($audit_script)) {
            error_log("Vortex Audit Runner: Audit script not found: " . $audit_script);
            return false;
        }
        
        // Execute audit script
        $output = [];
        $return_code = 0;
        
        exec("php $audit_script --full --json", $output, $return_code);
        
        $audit_results = [
            'type' => $audit_type,
            'timestamp' => current_time('timestamp'),
            'duration_ms' => round((microtime(true) - $start_time) * 1000),
            'return_code' => $return_code,
            'output' => implode("\n", $output),
        ];
        
        // Parse JSON output if available
        $json_output = end($output);
        if ($json_output && $this->is_json($json_output)) {
            $audit_results['data'] = json_decode($json_output, true);
        }
        
        // Store audit results
        $this->store_audit_results($audit_results);
        
        // Trigger post-audit processing
        do_action('vortex_audit_completed', $audit_results);
        
        return $audit_results;
    }
    
    /**
     * Process audit results and check for regressions
     */
    public function process_audit_results($audit_results) {
        // Load baseline for comparison
        $baseline = $this->load_baseline();
        
        if (!$baseline) {
            // Create initial baseline
            $this->create_baseline($audit_results);
            return;
        }
        
        // Compare current results with baseline
        $regressions = $this->detect_regressions($audit_results, $baseline);
        
        if (!empty($regressions)) {
            // Create GitHub Issue for regressions
            $this->create_regression_issue($regressions, $audit_results);
            
            // Send alert notifications
            $this->send_regression_alerts($regressions);
        }
        
        // Update baseline if no critical regressions
        if (empty($regressions['critical'])) {
            $this->update_baseline($audit_results);
        }
        
        // Generate audit report
        $this->generate_audit_report($audit_results, $regressions);
    }
    
    /**
     * Detect regressions by comparing current results with baseline
     */
    private function detect_regressions($current, $baseline) {
        $regressions = [
            'critical' => [],
            'warnings' => [],
            'metrics' => []
        ];
        
        if (!isset($current['data']) || !isset($baseline['data'])) {
            return $regressions;
        }
        
        $current_data = $current['data'];
        $baseline_data = $baseline['data'];
        
        // Check error rate increase
        $current_errors = $current_data['errors'] ?? 0;
        $baseline_errors = $baseline_data['errors'] ?? 0;
        $error_increase = $current_errors - $baseline_errors;
        
        if ($error_increase > $this->regression_thresholds['new_warnings']) {
            $regressions['critical'][] = [
                'type' => 'error_rate_increase',
                'current' => $current_errors,
                'baseline' => $baseline_errors,
                'increase' => $error_increase,
                'threshold' => $this->regression_thresholds['new_warnings']
            ];
        }
        
        // Check performance degradation
        $current_duration = $current['duration_ms'] ?? 0;
        $baseline_duration = $baseline['duration_ms'] ?? 0;
        
        if ($baseline_duration > 0) {
            $performance_degradation = ($current_duration - $baseline_duration) / $baseline_duration;
            
            if ($performance_degradation > $this->regression_thresholds['performance_degradation']) {
                $regressions['warnings'][] = [
                    'type' => 'performance_degradation',
                    'current' => $current_duration,
                    'baseline' => $baseline_duration,
                    'degradation' => $performance_degradation,
                    'threshold' => $this->regression_thresholds['performance_degradation']
                ];
            }
        }
        
        // Check for missing files
        $current_files = $current_data['files_checked'] ?? 0;
        $baseline_files = $baseline_data['files_checked'] ?? 0;
        
        if ($current_files < $baseline_files) {
            $missing_files = $baseline_files - $current_files;
            
            if ($missing_files >= $this->regression_thresholds['missing_files']) {
                $regressions['critical'][] = [
                    'type' => 'missing_files',
                    'current' => $current_files,
                    'baseline' => $baseline_files,
                    'missing' => $missing_files,
                    'threshold' => $this->regression_thresholds['missing_files']
                ];
            }
        }
        
        // Check satisfaction metrics if available
        $current_satisfaction = $this->get_current_satisfaction_score();
        $baseline_satisfaction = $baseline_data['satisfaction_score'] ?? 0;
        
        if ($baseline_satisfaction > 0 && $current_satisfaction > 0) {
            $satisfaction_drop = $baseline_satisfaction - $current_satisfaction;
            
            if ($satisfaction_drop > $this->regression_thresholds['satisfaction_drop']) {
                $regressions['warnings'][] = [
                    'type' => 'satisfaction_drop',
                    'current' => $current_satisfaction,
                    'baseline' => $baseline_satisfaction,
                    'drop' => $satisfaction_drop,
                    'threshold' => $this->regression_thresholds['satisfaction_drop']
                ];
            }
        }
        
        return $regressions;
    }
    
    /**
     * Create GitHub Issue for regressions
     */
    private function create_regression_issue($regressions, $audit_results) {
        if (!$this->audit_config['github_token']) {
            error_log("Vortex Audit Runner: GitHub token not configured");
            return false;
        }
        
        $issue_title = "🚨 Audit Regression Detected - " . date('Y-m-d H:i:s');
        
        $issue_body = $this->generate_regression_issue_body($regressions, $audit_results);
        
        $issue_data = [
            'title' => $issue_title,
            'body' => $issue_body,
            'labels' => ['audit-regression', 'automated'],
            'assignees' => [], // Add team members as needed
        ];
        
        // Create GitHub Issue via API
        $issue_url = $this->create_github_issue($issue_data);
        
        if ($issue_url) {
            error_log("Vortex Audit Runner: Created GitHub Issue: " . $issue_url);
            
            // Store issue reference
            $this->store_issue_reference($audit_results['timestamp'], $issue_url);
        }
        
        return $issue_url;
    }
    
    /**
     * Generate issue body for regression report
     */
    private function generate_regression_issue_body($regressions, $audit_results) {
        $body = "## 🚨 Audit Regression Report\n\n";
        $body .= "**Audit Type:** " . $audit_results['type'] . "\n";
        $body .= "**Timestamp:** " . date('Y-m-d H:i:s', $audit_results['timestamp']) . "\n";
        $body .= "**Duration:** " . $audit_results['duration_ms'] . "ms\n\n";
        
        if (!empty($regressions['critical'])) {
            $body .= "## 🔴 Critical Regressions\n\n";
            foreach ($regressions['critical'] as $regression) {
                $body .= $this->format_regression_item($regression);
            }
        }
        
        if (!empty($regressions['warnings'])) {
            $body .= "## 🟡 Warning Regressions\n\n";
            foreach ($regressions['warnings'] as $regression) {
                $body .= $this->format_regression_item($regression);
            }
        }
        
        $body .= "\n## 📊 Audit Summary\n\n";
        $body .= "- **Total Checks:** " . ($audit_results['data']['total_checks'] ?? 'N/A') . "\n";
        $body .= "- **Passed:** " . ($audit_results['data']['passed_checks'] ?? 'N/A') . "\n";
        $body .= "- **Warnings:** " . ($audit_results['data']['warnings'] ?? 'N/A') . "\n";
        $body .= "- **Errors:** " . ($audit_results['data']['errors'] ?? 'N/A') . "\n";
        
        $body .= "\n## 🔧 Recommended Actions\n\n";
        $body .= "1. Review the regression details above\n";
        $body .= "2. Investigate root cause of performance degradation\n";
        $body .= "3. Implement fixes and re-run audit\n";
        $body .= "4. Update baseline once issues are resolved\n";
        
        return $body;
    }
    
    /**
     * Format individual regression item
     */
    private function format_regression_item($regression) {
        $formatted = "### " . ucfirst(str_replace('_', ' ', $regression['type'])) . "\n\n";
        
        switch ($regression['type']) {
            case 'error_rate_increase':
                $formatted .= "- **Current Errors:** " . $regression['current'] . "\n";
                $formatted .= "- **Baseline Errors:** " . $regression['baseline'] . "\n";
                $formatted .= "- **Increase:** " . $regression['increase'] . " (threshold: " . $regression['threshold'] . ")\n";
                break;
                
            case 'performance_degradation':
                $formatted .= "- **Current Duration:** " . $regression['current'] . "ms\n";
                $formatted .= "- **Baseline Duration:** " . $regression['baseline'] . "ms\n";
                $formatted .= "- **Degradation:** " . round($regression['degradation'] * 100, 2) . "% (threshold: " . ($regression['threshold'] * 100) . "%)\n";
                break;
                
            case 'missing_files':
                $formatted .= "- **Current Files:** " . $regression['current'] . "\n";
                $formatted .= "- **Baseline Files:** " . $regression['baseline'] . "\n";
                $formatted .= "- **Missing Files:** " . $regression['missing'] . "\n";
                break;
                
            case 'satisfaction_drop':
                $formatted .= "- **Current Satisfaction:** " . round($regression['current'] * 100, 2) . "%\n";
                $formatted .= "- **Baseline Satisfaction:** " . round($regression['baseline'] * 100, 2) . "%\n";
                $formatted .= "- **Drop:** " . round($regression['drop'] * 100, 2) . "% (threshold: " . ($regression['threshold'] * 100) . "%)\n";
                break;
        }
        
        $formatted .= "\n";
        return $formatted;
    }
    
    /**
     * Create GitHub Issue via API
     */
    private function create_github_issue($issue_data) {
        // This would use GitHub API to create the issue
        // For now, return a placeholder URL
        return "https://github.com/" . $this->audit_config['github_repo'] . "/issues/new";
    }
    
    /**
     * Send regression alerts
     */
    private function send_regression_alerts($regressions) {
        $alert_data = [
            'regressions' => $regressions,
            'timestamp' => current_time('timestamp'),
            'audit_type' => 'automated'
        ];
        
        do_action('vortex_regression_alert', $alert_data);
    }
    
    /**
     * Store audit results
     */
    private function store_audit_results($results) {
        $audit_dir = VORTEX_PLUGIN_DIR . $this->audit_config['report_dir'];
        
        if (!is_dir($audit_dir)) {
            wp_mkdir_p($audit_dir);
        }
        
        $filename = 'audit-' . date('Y-m-d-H-i-s', $results['timestamp']) . '.json';
        $filepath = $audit_dir . $filename;
        
        file_put_contents($filepath, json_encode($results, JSON_PRETTY_PRINT));
        
        // Store reference in database
        update_option('vortex_last_audit', [
            'timestamp' => $results['timestamp'],
            'file' => $filename,
            'type' => $results['type']
        ]);
    }
    
    /**
     * Load baseline data
     */
    private function load_baseline() {
        $baseline_file = VORTEX_PLUGIN_DIR . $this->audit_config['baseline_file'];
        
        if (file_exists($baseline_file)) {
            $baseline_data = file_get_contents($baseline_file);
            return json_decode($baseline_data, true);
        }
        
        return null;
    }
    
    /**
     * Create initial baseline
     */
    private function create_baseline($audit_results) {
        $baseline_file = VORTEX_PLUGIN_DIR . $this->audit_config['baseline_file'];
        
        $baseline_data = [
            'created_at' => current_time('timestamp'),
            'data' => $audit_results['data'] ?? [],
            'duration_ms' => $audit_results['duration_ms'],
            'satisfaction_score' => $this->get_current_satisfaction_score()
        ];
        
        file_put_contents($baseline_file, json_encode($baseline_data, JSON_PRETTY_PRINT));
        
        error_log("Vortex Audit Runner: Created initial baseline");
    }
    
    /**
     * Update baseline with current results
     */
    private function update_baseline($audit_results) {
        $this->create_baseline($audit_results);
        error_log("Vortex Audit Runner: Updated baseline");
    }
    
    /**
     * Get current satisfaction score
     */
    private function get_current_satisfaction_score() {
        // Query DynamoDB or other metrics store for current satisfaction
        // This is a simplified implementation
        return 0.85; // Placeholder
    }
    
    /**
     * Generate audit report
     */
    private function generate_audit_report($audit_results, $regressions) {
        $report_data = [
            'audit_results' => $audit_results,
            'regressions' => $regressions,
            'generated_at' => current_time('timestamp')
        ];
        
        do_action('vortex_audit_report_generated', $report_data);
    }
    
    /**
     * Store issue reference
     */
    private function store_issue_reference($timestamp, $issue_url) {
        $issue_references = get_option('vortex_audit_issues', []);
        $issue_references[] = [
            'timestamp' => $timestamp,
            'issue_url' => $issue_url
        ];
        
        update_option('vortex_audit_issues', $issue_references);
    }
    
    /**
     * Check if string is valid JSON
     */
    private function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

// Initialize the automated audit runner
Vortex_Automated_Audit_Runner::get_instance(); 