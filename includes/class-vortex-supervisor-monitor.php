<?php
/**
 * VORTEX AI ENGINE - SUPERVISOR MONITOR
 * 
 * Real-time monitoring, logging, and alerting system for the Vortex AI Engine.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Vortex AI Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Supervisor_Monitor {
    
    private $monitor_active = false;
    private $log_buffer = array();
    private $alert_queue = array();
    private $performance_tracker = array();
    private $error_tracker = array();
    private $sync_tracker = array();
    
    public function __construct() {
        $this->initialize_monitor();
    }
    
    /**
     * Initialize the monitor
     */
    private function initialize_monitor() {
        $this->monitor_active = true;
        
        // Set up monitoring hooks
        add_action('wp_loaded', array($this, 'start_monitoring'));
        add_action('wp_ajax_vortex_monitor_system', array($this, 'execute_system_monitoring'));
        
        // Set up real-time logging
        add_action('wp_loaded', array($this, 'start_real_time_logging'));
        
        // Set up alerting
        add_action('wp_loaded', array($this, 'start_alerting_system'));
        
        error_log('VORTEX MONITOR: Real-time monitoring system initialized');
    }
    
    /**
     * Start monitoring
     */
    public function start_monitoring() {
        if (!$this->monitor_active) {
            return;
        }
        
        // Monitor system health
        $this->monitor_system_health();
        
        // Monitor performance
        $this->monitor_performance();
        
        // Monitor errors
        $this->monitor_errors();
        
        // Monitor synchronization
        $this->monitor_synchronization();
        
        // Schedule next monitoring cycle
        wp_schedule_single_event(time() + 30, 'vortex_monitor_tick');
    }
    
    /**
     * Execute system monitoring
     */
    public function execute_system_monitoring() {
        $monitoring_data = array(
            'timestamp' => time(),
            'system_health' => $this->get_system_health(),
            'performance_metrics' => $this->get_performance_metrics(),
            'error_summary' => $this->get_error_summary(),
            'sync_status' => $this->get_sync_status(),
            'active_processes' => $this->get_active_processes()
        );
        
        // Log monitoring data
        $this->log_monitoring_data($monitoring_data);
        
        // Check for alerts
        $this->check_for_alerts($monitoring_data);
        
        // Send response
        wp_die(json_encode($monitoring_data));
    }
    
    /**
     * Monitor system health
     */
    private function monitor_system_health() {
        $health_data = array(
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'execution_time' => microtime(true),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_status' => $this->get_plugin_status()
        );
        
        $this->performance_tracker['health'] = $health_data;
        
        // Check for health issues
        $this->check_health_issues($health_data);
    }
    
    /**
     * Monitor performance
     */
    private function monitor_performance() {
        $performance_data = array(
            'response_time' => $this->measure_response_time(),
            'throughput' => $this->measure_throughput(),
            'resource_usage' => $this->measure_resource_usage(),
            'optimization_score' => $this->calculate_optimization_score()
        );
        
        $this->performance_tracker['performance'] = $performance_data;
        
        // Check for performance issues
        $this->check_performance_issues($performance_data);
    }
    
    /**
     * Monitor errors
     */
    private function monitor_errors() {
        $error_data = array(
            'total_errors' => count($this->error_tracker),
            'critical_errors' => $this->count_critical_errors(),
            'recent_errors' => array_slice($this->error_tracker, -10),
            'error_trend' => $this->calculate_error_trend()
        );
        
        $this->error_tracker = $error_data;
        
        // Check for error patterns
        $this->check_error_patterns($error_data);
    }
    
    /**
     * Monitor synchronization
     */
    private function monitor_synchronization() {
        $sync_data = array(
            'last_sync' => get_option('vortex_last_sync', time()),
            'sync_frequency' => 5,
            'sync_success_rate' => $this->calculate_sync_success_rate(),
            'cross_instance_communication' => $this->get_cross_instance_status()
        );
        
        $this->sync_tracker = $sync_data;
        
        // Check for sync issues
        $this->check_sync_issues($sync_data);
    }
    
    /**
     * Start real-time logging
     */
    public function start_real_time_logging() {
        // Set up logging intervals
        add_action('wp_loaded', array($this, 'log_system_activity'));
        wp_schedule_event(time(), 'every_minute', 'vortex_logging_tick');
    }
    
    /**
     * Log system activity
     */
    public function log_system_activity() {
        $log_entry = array(
            'timestamp' => time(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'active_plugins' => count(get_option('active_plugins')),
            'current_user' => wp_get_current_user()->user_login,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $this->log_buffer[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($this->log_buffer) > 1000) {
            array_shift($this->log_buffer);
        }
        
        // Log to WordPress error log
        error_log('VORTEX LOG: ' . json_encode($log_entry));
    }
    
    /**
     * Start alerting system
     */
    public function start_alerting_system() {
        // Set up alerting intervals
        add_action('wp_loaded', array($this, 'check_alerts'));
        wp_schedule_event(time(), 'every_minute', 'vortex_alerting_tick');
    }
    
    /**
     * Check for alerts
     */
    public function check_alerts() {
        $alerts = array();
        
        // Check for critical errors
        if ($this->count_critical_errors() > 5) {
            $alerts[] = array(
                'type' => 'CRITICAL_ERROR',
                'message' => 'High number of critical errors detected',
                'severity' => 'HIGH'
            );
        }
        
        // Check for memory issues
        if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB
            $alerts[] = array(
                'type' => 'MEMORY_WARNING',
                'message' => 'High memory usage detected',
                'severity' => 'MEDIUM'
            );
        }
        
        // Check for sync issues
        if (time() - get_option('vortex_last_sync', time()) > 300) { // 5 minutes
            $alerts[] = array(
                'type' => 'SYNC_WARNING',
                'message' => 'Synchronization delay detected',
                'severity' => 'MEDIUM'
            );
        }
        
        // Process alerts
        foreach ($alerts as $alert) {
            $this->process_alert($alert);
        }
    }
    
    /**
     * Process alert
     */
    private function process_alert($alert) {
        $this->alert_queue[] = $alert;
        
        // Send email notification for high severity alerts
        if ($alert['severity'] === 'HIGH') {
            $this->send_alert_notification($alert);
        }
        
        // Log alert
        error_log("VORTEX ALERT [{$alert['severity']}]: {$alert['type']} - {$alert['message']}");
    }
    
    /**
     * Send alert notification
     */
    private function send_alert_notification($alert) {
        $admin_emails = array(
            get_option('admin_email'),
            'admin@vortexartec.com'
        );
        
        $subject = "VORTEX AI ALERT: {$alert['type']}";
        $message = "Alert Details:\n\n";
        $message .= "Type: {$alert['type']}\n";
        $message .= "Message: {$alert['message']}\n";
        $message .= "Severity: {$alert['severity']}\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
        $message .= "Please check the system immediately.";
        
        foreach ($admin_emails as $email) {
            wp_mail($email, $subject, $message);
        }
    }
    
    /**
     * Get system health
     */
    private function get_system_health() {
        return $this->performance_tracker['health'] ?? array();
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics() {
        return $this->performance_tracker['performance'] ?? array();
    }
    
    /**
     * Get error summary
     */
    private function get_error_summary() {
        return $this->error_tracker;
    }
    
    /**
     * Get sync status
     */
    private function get_sync_status() {
        return $this->sync_tracker;
    }
    
    /**
     * Get active processes
     */
    private function get_active_processes() {
        return array(
            'supervisor' => true,
            'monitor' => $this->monitor_active,
            'logging' => true,
            'alerting' => true
        );
    }
    
    /**
     * Log monitoring data
     */
    private function log_monitoring_data($data) {
        $this->log_buffer[] = array(
            'timestamp' => time(),
            'type' => 'MONITORING_DATA',
            'data' => $data
        );
    }
    
    /**
     * Check for alerts
     */
    private function check_for_alerts($data) {
        // Check system health
        if (isset($data['system_health']['memory_usage'])) {
            if ($data['system_health']['memory_usage'] > 100 * 1024 * 1024) {
                $this->process_alert(array(
                    'type' => 'MEMORY_WARNING',
                    'message' => 'High memory usage detected',
                    'severity' => 'MEDIUM'
                ));
            }
        }
        
        // Check error count
        if (isset($data['error_summary']['total_errors'])) {
            if ($data['error_summary']['total_errors'] > 10) {
                $this->process_alert(array(
                    'type' => 'ERROR_WARNING',
                    'message' => 'High error count detected',
                    'severity' => 'HIGH'
                ));
            }
        }
    }
    
    /**
     * Check health issues
     */
    private function check_health_issues($health_data) {
        // Check memory usage
        if ($health_data['memory_usage'] > 100 * 1024 * 1024) {
            $this->process_alert(array(
                'type' => 'MEMORY_WARNING',
                'message' => 'High memory usage: ' . round($health_data['memory_usage'] / 1024 / 1024, 2) . 'MB',
                'severity' => 'MEDIUM'
            ));
        }
        
        // Check PHP version
        if (version_compare($health_data['php_version'], '7.4', '<')) {
            $this->process_alert(array(
                'type' => 'PHP_VERSION_WARNING',
                'message' => 'PHP version ' . $health_data['php_version'] . ' is below recommended 7.4',
                'severity' => 'LOW'
            ));
        }
    }
    
    /**
     * Check performance issues
     */
    private function check_performance_issues($performance_data) {
        // Check response time
        if ($performance_data['response_time'] > 2.0) {
            $this->process_alert(array(
                'type' => 'PERFORMANCE_WARNING',
                'message' => 'Slow response time: ' . round($performance_data['response_time'], 2) . 's',
                'severity' => 'MEDIUM'
            ));
        }
        
        // Check optimization score
        if ($performance_data['optimization_score'] < 70) {
            $this->process_alert(array(
                'type' => 'OPTIMIZATION_WARNING',
                'message' => 'Low optimization score: ' . $performance_data['optimization_score'],
                'severity' => 'LOW'
            ));
        }
    }
    
    /**
     * Check error patterns
     */
    private function check_error_patterns($error_data) {
        // Check for critical errors
        if ($error_data['critical_errors'] > 5) {
            $this->process_alert(array(
                'type' => 'CRITICAL_ERROR_PATTERN',
                'message' => 'Multiple critical errors detected: ' . $error_data['critical_errors'],
                'severity' => 'HIGH'
            ));
        }
        
        // Check error trend
        if ($error_data['error_trend'] > 0.5) {
            $this->process_alert(array(
                'type' => 'ERROR_TREND_WARNING',
                'message' => 'Increasing error trend detected',
                'severity' => 'MEDIUM'
            ));
        }
    }
    
    /**
     * Check sync issues
     */
    private function check_sync_issues($sync_data) {
        // Check sync delay
        if (time() - $sync_data['last_sync'] > 300) {
            $this->process_alert(array(
                'type' => 'SYNC_DELAY_WARNING',
                'message' => 'Synchronization delay: ' . (time() - $sync_data['last_sync']) . 's',
                'severity' => 'MEDIUM'
            ));
        }
        
        // Check sync success rate
        if ($sync_data['sync_success_rate'] < 0.9) {
            $this->process_alert(array(
                'type' => 'SYNC_SUCCESS_WARNING',
                'message' => 'Low sync success rate: ' . round($sync_data['sync_success_rate'] * 100, 1) . '%',
                'severity' => 'MEDIUM'
            ));
        }
    }
    
    // Helper methods
    private function get_plugin_status() {
        return array(
            'active' => is_plugin_active('vortex-ai-engine/vortex-ai-engine.php'),
            'version' => defined('VORTEX_AI_ENGINE_VERSION') ? VORTEX_AI_ENGINE_VERSION : 'unknown'
        );
    }
    
    private function measure_response_time() {
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }
    
    private function measure_throughput() {
        return 100; // Placeholder
    }
    
    private function measure_resource_usage() {
        return array(
            'cpu' => 50,
            'memory' => memory_get_usage(true) / 1024 / 1024
        );
    }
    
    private function calculate_optimization_score() {
        return 85; // Placeholder
    }
    
    private function count_critical_errors() {
        return 0; // Placeholder
    }
    
    private function calculate_error_trend() {
        return 0.1; // Placeholder
    }
    
    private function calculate_sync_success_rate() {
        return 0.95; // Placeholder
    }
    
    private function get_cross_instance_status() {
        return array(
            'connected' => true,
            'last_communication' => time()
        );
    }
}

// Initialize the monitor
if (class_exists('Vortex_Supervisor_Monitor')) {
    global $vortex_monitor;
    $vortex_monitor = new Vortex_Supervisor_Monitor();
} 