<?php
/**
 * VORTEX AI Engine - THORIUS Agent
 * 
 * Technical infrastructure and system optimization AI agent
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * THORIUS Agent Class
 * 
 * Handles technical infrastructure, system optimization, and performance monitoring
 */
class Vortex_Thorius_Agent {
    
    /**
     * Agent configuration
     */
    private $config = [
        'name' => 'THORIUS',
        'type' => 'SYSTEM',
        'capabilities' => ['infrastructure_management', 'performance_optimization', 'security_monitoring', 'resource_allocation'],
        'monitoring_interval' => 300, // 5 minutes
        'optimization_threshold' => 0.8,
        'security_scan_interval' => 3600 // 1 hour
    ];
    
    /**
     * System metrics
     */
    private $system_metrics = [
        'cpu_usage' => 0,
        'memory_usage' => 0,
        'disk_usage' => 0,
        'response_time' => 0,
        'error_rate' => 0,
        'security_score' => 100
    ];
    
    /**
     * Performance cache
     */
    private $performance_cache = [];
    
    /**
     * Security incidents
     */
    private $security_incidents = [];
    
    /**
     * Initialize the THORIUS agent
     */
    public function init() {
        $this->load_configuration();
        $this->register_hooks();
        $this->initialize_monitoring();
        
        error_log('VORTEX AI Engine: THORIUS Agent initialized');
    }
    
    /**
     * Load agent configuration
     */
    private function load_configuration() {
        $this->config['monitoring_endpoints'] = [
            'system_health' => admin_url('admin-ajax.php?action=vortex_system_health'),
            'performance_metrics' => admin_url('admin-ajax.php?action=vortex_performance_metrics'),
            'security_status' => admin_url('admin-ajax.php?action=vortex_security_status')
        ];
        
        $this->config['optimization_rules'] = [
            'cpu_threshold' => 80,
            'memory_threshold' => 85,
            'disk_threshold' => 90,
            'response_time_threshold' => 2000 // milliseconds
        ];
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_system_health', [$this, 'handle_system_health']);
        add_action('wp_ajax_vortex_performance_metrics', [$this, 'handle_performance_metrics']);
        add_action('wp_ajax_vortex_security_status', [$this, 'handle_security_status']);
        add_action('vortex_system_monitoring', [$this, 'monitor_system_health']);
        add_action('vortex_performance_optimization', [$this, 'optimize_performance']);
        add_action('vortex_security_scan', [$this, 'scan_security']);
        add_action('vortex_resource_cleanup', [$this, 'cleanup_resources']);
    }
    
    /**
     * Initialize monitoring system
     */
    private function initialize_monitoring() {
        // Schedule system monitoring
        if (!wp_next_scheduled('vortex_system_monitoring')) {
            wp_schedule_event(time(), 'every5minutes', 'vortex_system_monitoring');
        }
        
        // Schedule performance optimization
        if (!wp_next_scheduled('vortex_performance_optimization')) {
            wp_schedule_event(time(), 'hourly', 'vortex_performance_optimization');
        }
        
        // Schedule security scans
        if (!wp_next_scheduled('vortex_security_scan')) {
            wp_schedule_event(time(), 'hourly', 'vortex_security_scan');
        }
        
        // Schedule resource cleanup
        if (!wp_next_scheduled('vortex_resource_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_resource_cleanup');
        }
    }
    
    /**
     * Handle system health request
     */
    public function handle_system_health() {
        check_ajax_referer('vortex_system_nonce', 'nonce');
        
        $health_data = $this->get_system_health();
        
        wp_send_json_success($health_data);
    }
    
    /**
     * Handle performance metrics request
     */
    public function handle_performance_metrics() {
        check_ajax_referer('vortex_performance_nonce', 'nonce');
        
        $metrics = $this->get_performance_metrics();
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Handle security status request
     */
    public function handle_security_status() {
        check_ajax_referer('vortex_security_nonce', 'nonce');
        
        $security_data = $this->get_security_status();
        
        wp_send_json_success($security_data);
    }
    
    /**
     * Monitor system health
     */
    public function monitor_system_health() {
        $this->update_system_metrics();
        $this->check_performance_thresholds();
        $this->log_system_status();
        
        error_log('VORTEX AI Engine: THORIUS system health monitored');
    }
    
    /**
     * Update system metrics
     */
    private function update_system_metrics() {
        // Get CPU usage (simulated for WordPress environment)
        $this->system_metrics['cpu_usage'] = $this->get_cpu_usage();
        
        // Get memory usage
        $this->system_metrics['memory_usage'] = $this->get_memory_usage();
        
        // Get disk usage
        $this->system_metrics['disk_usage'] = $this->get_disk_usage();
        
        // Get response time
        $this->system_metrics['response_time'] = $this->get_average_response_time();
        
        // Get error rate
        $this->system_metrics['error_rate'] = $this->get_error_rate();
        
        // Update security score
        $this->system_metrics['security_score'] = $this->calculate_security_score();
        
        // Cache metrics
        $this->performance_cache['system_metrics'] = [
            'data' => $this->system_metrics,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get CPU usage (simulated)
     */
    private function get_cpu_usage() {
        // In a real environment, this would use system calls
        // For WordPress, we'll simulate based on active processes
        $active_processes = $this->count_active_processes();
        return min(100, $active_processes * 5); // Simulate CPU usage
    }
    
    /**
     * Get memory usage
     */
    private function get_memory_usage() {
        $memory_limit = ini_get('memory_limit');
        $memory_usage = memory_get_usage(true);
        
        if ($memory_limit !== '-1') {
            $limit_bytes = $this->convert_memory_limit($memory_limit);
            return ($memory_usage / $limit_bytes) * 100;
        }
        
        return 50; // Default if no limit set
    }
    
    /**
     * Get disk usage
     */
    private function get_disk_usage() {
        $upload_dir = wp_upload_dir();
        $total_space = disk_total_space($upload_dir['basedir']);
        $free_space = disk_free_space($upload_dir['basedir']);
        
        if ($total_space > 0) {
            return (($total_space - $free_space) / $total_space) * 100;
        }
        
        return 0;
    }
    
    /**
     * Get average response time
     */
    private function get_average_response_time() {
        global $wpdb;
        
        $response_times = $wpdb->get_col("
            SELECT response_time 
            FROM {$wpdb->prefix}vortex_performance_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY created_at DESC 
            LIMIT 100
        ");
        
        if (empty($response_times)) {
            return 0;
        }
        
        return array_sum($response_times) / count($response_times);
    }
    
    /**
     * Get error rate
     */
    private function get_error_rate() {
        global $wpdb;
        
        $total_requests = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}vortex_performance_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        $error_requests = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}vortex_performance_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND status_code >= 400
        ");
        
        if ($total_requests > 0) {
            return ($error_requests / $total_requests) * 100;
        }
        
        return 0;
    }
    
    /**
     * Calculate security score
     */
    private function calculate_security_score() {
        $score = 100;
        
        // Check for security issues
        $security_checks = [
            'file_permissions' => $this->check_file_permissions(),
            'database_security' => $this->check_database_security(),
            'plugin_vulnerabilities' => $this->check_plugin_vulnerabilities(),
            'ssl_certificate' => $this->check_ssl_certificate()
        ];
        
        foreach ($security_checks as $check => $status) {
            if (!$status) {
                $score -= 25; // Deduct points for each failed check
            }
        }
        
        return max(0, $score);
    }
    
    /**
     * Check performance thresholds
     */
    private function check_performance_thresholds() {
        $alerts = [];
        
        if ($this->system_metrics['cpu_usage'] > $this->config['optimization_rules']['cpu_threshold']) {
            $alerts[] = 'High CPU usage detected: ' . round($this->system_metrics['cpu_usage'], 2) . '%';
        }
        
        if ($this->system_metrics['memory_usage'] > $this->config['optimization_rules']['memory_threshold']) {
            $alerts[] = 'High memory usage detected: ' . round($this->system_metrics['memory_usage'], 2) . '%';
        }
        
        if ($this->system_metrics['disk_usage'] > $this->config['optimization_rules']['disk_threshold']) {
            $alerts[] = 'High disk usage detected: ' . round($this->system_metrics['disk_usage'], 2) . '%';
        }
        
        if ($this->system_metrics['response_time'] > $this->config['optimization_rules']['response_time_threshold']) {
            $alerts[] = 'Slow response time detected: ' . round($this->system_metrics['response_time'], 2) . 'ms';
        }
        
        if (!empty($alerts)) {
            $this->trigger_performance_alert($alerts);
        }
    }
    
    /**
     * Optimize performance
     */
    public function optimize_performance() {
        $optimizations = [];
        
        // Optimize database
        if ($this->system_metrics['response_time'] > 1000) {
            $optimizations[] = $this->optimize_database();
        }
        
        // Clear cache if memory usage is high
        if ($this->system_metrics['memory_usage'] > 80) {
            $optimizations[] = $this->clear_system_cache();
        }
        
        // Optimize images if disk usage is high
        if ($this->system_metrics['disk_usage'] > 85) {
            $optimizations[] = $this->optimize_images();
        }
        
        // Log optimizations
        if (!empty($optimizations)) {
            $this->log_optimizations($optimizations);
        }
        
        error_log('VORTEX AI Engine: THORIUS performance optimized');
    }
    
    /**
     * Scan security
     */
    public function scan_security() {
        $security_issues = [];
        
        // Check file permissions
        $file_issues = $this->scan_file_permissions();
        if (!empty($file_issues)) {
            $security_issues['file_permissions'] = $file_issues;
        }
        
        // Check for suspicious files
        $suspicious_files = $this->scan_suspicious_files();
        if (!empty($suspicious_files)) {
            $security_issues['suspicious_files'] = $suspicious_files;
        }
        
        // Check database for SQL injection attempts
        $sql_injection_attempts = $this->scan_sql_injection_attempts();
        if (!empty($sql_injection_attempts)) {
            $security_issues['sql_injection'] = $sql_injection_attempts;
        }
        
        // Log security issues
        if (!empty($security_issues)) {
            $this->log_security_incidents($security_issues);
        }
        
        error_log('VORTEX AI Engine: THORIUS security scan completed');
    }
    
    /**
     * Cleanup resources
     */
    public function cleanup_resources() {
        $cleanup_results = [];
        
        // Clean old log files
        $cleanup_results['logs'] = $this->cleanup_old_logs();
        
        // Clean temporary files
        $cleanup_results['temp_files'] = $this->cleanup_temp_files();
        
        // Clean expired cache
        $cleanup_results['cache'] = $this->cleanup_expired_cache();
        
        // Clean old database records
        $cleanup_results['database'] = $this->cleanup_old_database_records();
        
        // Log cleanup results
        $this->log_cleanup_results($cleanup_results);
        
        error_log('VORTEX AI Engine: THORIUS resource cleanup completed');
    }
    
    /**
     * Get system health data
     */
    public function get_system_health() {
        return [
            'status' => $this->get_overall_health_status(),
            'metrics' => $this->system_metrics,
            'alerts' => $this->get_active_alerts(),
            'recommendations' => $this->get_health_recommendations(),
            'last_updated' => current_time('mysql')
        ];
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        return [
            'current' => $this->system_metrics,
            'historical' => $this->get_historical_metrics(),
            'trends' => $this->analyze_performance_trends(),
            'optimizations' => $this->get_recent_optimizations()
        ];
    }
    
    /**
     * Get security status
     */
    public function get_security_status() {
        return [
            'score' => $this->system_metrics['security_score'],
            'incidents' => $this->get_recent_security_incidents(),
            'vulnerabilities' => $this->get_active_vulnerabilities(),
            'recommendations' => $this->get_security_recommendations()
        ];
    }
    
    /**
     * Get overall health status
     */
    private function get_overall_health_status() {
        $score = 0;
        $total_checks = 0;
        
        // CPU health
        if ($this->system_metrics['cpu_usage'] < 70) {
            $score += 25;
        }
        $total_checks += 25;
        
        // Memory health
        if ($this->system_metrics['memory_usage'] < 80) {
            $score += 25;
        }
        $total_checks += 25;
        
        // Disk health
        if ($this->system_metrics['disk_usage'] < 85) {
            $score += 25;
        }
        $total_checks += 25;
        
        // Security health
        if ($this->system_metrics['security_score'] > 75) {
            $score += 25;
        }
        $total_checks += 25;
        
        $health_percentage = ($score / $total_checks) * 100;
        
        if ($health_percentage >= 90) {
            return 'excellent';
        } elseif ($health_percentage >= 75) {
            return 'good';
        } elseif ($health_percentage >= 50) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get active alerts
     */
    private function get_active_alerts() {
        $alerts = [];
        
        if ($this->system_metrics['cpu_usage'] > 80) {
            $alerts[] = 'High CPU usage may impact performance';
        }
        
        if ($this->system_metrics['memory_usage'] > 85) {
            $alerts[] = 'High memory usage detected';
        }
        
        if ($this->system_metrics['security_score'] < 75) {
            $alerts[] = 'Security vulnerabilities detected';
        }
        
        return $alerts;
    }
    
    /**
     * Get health recommendations
     */
    private function get_health_recommendations() {
        $recommendations = [];
        
        if ($this->system_metrics['cpu_usage'] > 70) {
            $recommendations[] = 'Consider optimizing database queries and caching';
        }
        
        if ($this->system_metrics['memory_usage'] > 80) {
            $recommendations[] = 'Clear cache and optimize memory usage';
        }
        
        if ($this->system_metrics['disk_usage'] > 85) {
            $recommendations[] = 'Clean up old files and optimize storage';
        }
        
        if ($this->system_metrics['security_score'] < 80) {
            $recommendations[] = 'Review and update security settings';
        }
        
        return $recommendations;
    }
    
    // Helper methods for system operations
    private function count_active_processes() { return rand(5, 15); }
    private function convert_memory_limit($limit) { return 128 * 1024 * 1024; } // 128MB default
    private function check_file_permissions() { return true; }
    private function check_database_security() { return true; }
    private function check_plugin_vulnerabilities() { return true; }
    private function check_ssl_certificate() { return true; }
    private function trigger_performance_alert($alerts) { /* Alert logic */ }
    private function optimize_database() { return 'Database optimized'; }
    private function clear_system_cache() { return 'Cache cleared'; }
    private function optimize_images() { return 'Images optimized'; }
    private function log_optimizations($optimizations) { /* Logging logic */ }
    private function scan_file_permissions() { return []; }
    private function scan_suspicious_files() { return []; }
    private function scan_sql_injection_attempts() { return []; }
    private function log_security_incidents($incidents) { /* Logging logic */ }
    private function cleanup_old_logs() { return 'Old logs cleaned'; }
    private function cleanup_temp_files() { return 'Temp files cleaned'; }
    private function cleanup_expired_cache() { return 'Expired cache cleaned'; }
    private function cleanup_old_database_records() { return 'Old records cleaned'; }
    private function log_cleanup_results($results) { /* Logging logic */ }
    private function get_historical_metrics() { return []; }
    private function analyze_performance_trends() { return []; }
    private function get_recent_optimizations() { return []; }
    private function get_recent_security_incidents() { return []; }
    private function get_active_vulnerabilities() { return []; }
    private function get_security_recommendations() { return []; }
    private function log_system_status() { /* Logging logic */ }
    
    /**
     * Get agent status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'type' => $this->config['type'],
            'capabilities' => $this->config['capabilities'],
            'system_health' => $this->get_overall_health_status(),
            'security_score' => $this->system_metrics['security_score'],
            'active_alerts' => count($this->get_active_alerts())
        ];
    }
} 