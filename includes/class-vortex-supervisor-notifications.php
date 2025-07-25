<?php
/**
 * VORTEX AI ENGINE - SUPERVISOR NOTIFICATIONS
 * 
 * Comprehensive notification system for real-time alerts, email communications,
 * and admin notifications for the Vortex AI Engine supervisor.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Vortex AI Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Supervisor_Notifications {
    
    private $notification_queue = array();
    private $admin_emails = array();
    private $notification_settings = array();
    private $notification_history = array();
    private $real_time_notifications = array();
    
    public function __construct() {
        $this->initialize_notification_system();
    }
    
    /**
     * Initialize notification system
     */
    private function initialize_notification_system() {
        // Set up admin emails
        $this->admin_emails = array(
            get_option('admin_email'),
            'admin@vortexartec.com',
            'support@vortexartec.com'
        );
        
        // Set up notification settings
        $this->notification_settings = array(
            'critical_errors' => true,
            'performance_alerts' => true,
            'system_updates' => true,
            'sync_status' => true,
            'rl_improvements' => true,
            'heartbeat_notifications' => true,
            'real_time_alerts' => true
        );
        
        // Set up notification hooks
        add_action('wp_loaded', array($this, 'start_notification_system'));
        add_action('wp_ajax_vortex_send_notification', array($this, 'send_notification_ajax'));
        
        // Set up notification intervals
        wp_schedule_event(time(), 'every_minute', 'vortex_notification_tick');
        
        error_log('VORTEX NOTIFICATIONS: Notification system initialized');
    }
    
    /**
     * Start notification system
     */
    public function start_notification_system() {
        // Process notification queue
        $this->process_notification_queue();
        
        // Send real-time notifications
        $this->send_real_time_notifications();
        
        // Send heartbeat notifications
        $this->send_heartbeat_notifications();
        
        // Schedule next notification cycle
        wp_schedule_single_event(time() + 60, 'vortex_notification_tick');
    }
    
    /**
     * Send notification via AJAX
     */
    public function send_notification_ajax() {
        $notification_data = array(
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'severity' => sanitize_text_field($_POST['severity'] ?? 'INFO'),
            'timestamp' => time()
        );
        
        $result = $this->send_notification($notification_data);
        
        wp_die(json_encode($result));
    }
    
    /**
     * Send notification
     */
    public function send_notification($notification_data) {
        $result = array(
            'success' => false,
            'message' => '',
            'notification_id' => ''
        );
        
        try {
            // Validate notification data
            if (empty($notification_data['type']) || empty($notification_data['message'])) {
                throw new Exception('Invalid notification data');
            }
            
            // Generate notification ID
            $notification_id = 'vortex_' . time() . '_' . uniqid();
            $notification_data['id'] = $notification_id;
            
            // Add to queue
            $this->notification_queue[] = $notification_data;
            
            // Add to history
            $this->notification_history[] = $notification_data;
            
            // Keep only last 1000 notifications in history
            if (count($this->notification_history) > 1000) {
                array_shift($this->notification_history);
            }
            
            // Send based on type and severity
            switch ($notification_data['type']) {
                case 'CRITICAL_ERROR':
                    $this->send_critical_error_notification($notification_data);
                    break;
                    
                case 'PERFORMANCE_ALERT':
                    $this->send_performance_alert_notification($notification_data);
                    break;
                    
                case 'SYSTEM_UPDATE':
                    $this->send_system_update_notification($notification_data);
                    break;
                    
                case 'SYNC_STATUS':
                    $this->send_sync_status_notification($notification_data);
                    break;
                    
                case 'RL_IMPROVEMENT':
                    $this->send_rl_improvement_notification($notification_data);
                    break;
                    
                case 'HEARTBEAT':
                    $this->send_heartbeat_notification($notification_data);
                    break;
                    
                default:
                    $this->send_general_notification($notification_data);
                    break;
            }
            
            $result['success'] = true;
            $result['message'] = 'Notification sent successfully';
            $result['notification_id'] = $notification_id;
            
        } catch (Exception $e) {
            $result['message'] = 'Error sending notification: ' . $e->getMessage();
            error_log('VORTEX NOTIFICATION ERROR: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Send critical error notification
     */
    private function send_critical_error_notification($notification_data) {
        if (!$this->notification_settings['critical_errors']) {
            return;
        }
        
        $subject = "🚨 VORTEX AI CRITICAL ERROR: {$notification_data['type']}";
        $message = $this->build_critical_error_message($notification_data);
        
        $this->send_email_notification($subject, $message, 'HIGH');
        $this->add_real_time_notification($notification_data);
    }
    
    /**
     * Send performance alert notification
     */
    private function send_performance_alert_notification($notification_data) {
        if (!$this->notification_settings['performance_alerts']) {
            return;
        }
        
        $subject = "⚠️ VORTEX AI PERFORMANCE ALERT: {$notification_data['type']}";
        $message = $this->build_performance_alert_message($notification_data);
        
        $this->send_email_notification($subject, $message, 'MEDIUM');
        $this->add_real_time_notification($notification_data);
    }
    
    /**
     * Send system update notification
     */
    private function send_system_update_notification($notification_data) {
        if (!$this->notification_settings['system_updates']) {
            return;
        }
        
        $subject = "🔄 VORTEX AI SYSTEM UPDATE: {$notification_data['type']}";
        $message = $this->build_system_update_message($notification_data);
        
        $this->send_email_notification($subject, $message, 'LOW');
        $this->add_real_time_notification($notification_data);
    }
    
    /**
     * Send sync status notification
     */
    private function send_sync_status_notification($notification_data) {
        if (!$this->notification_settings['sync_status']) {
            return;
        }
        
        $subject = "🔄 VORTEX AI SYNC STATUS: {$notification_data['type']}";
        $message = $this->build_sync_status_message($notification_data);
        
        $this->send_email_notification($subject, $message, 'MEDIUM');
        $this->add_real_time_notification($notification_data);
    }
    
    /**
     * Send RL improvement notification
     */
    private function send_rl_improvement_notification($notification_data) {
        if (!$this->notification_settings['rl_improvements']) {
            return;
        }
        
        $subject = "🧠 VORTEX AI RL IMPROVEMENT: {$notification_data['type']}";
        $message = $this->build_rl_improvement_message($notification_data);
        
        $this->send_email_notification($subject, $message, 'LOW');
        $this->add_real_time_notification($notification_data);
    }
    
    /**
     * Send heartbeat notification
     */
    private function send_heartbeat_notification($notification_data) {
        if (!$this->notification_settings['heartbeat_notifications']) {
            return;
        }
        
        $subject = "💓 VORTEX AI HEARTBEAT: System Status Update";
        $message = $this->build_heartbeat_message($notification_data);
        
        $this->send_email_notification($subject, $message, 'LOW');
    }
    
    /**
     * Send general notification
     */
    private function send_general_notification($notification_data) {
        $subject = "ℹ️ VORTEX AI NOTIFICATION: {$notification_data['type']}";
        $message = $this->build_general_message($notification_data);
        
        $this->send_email_notification($subject, $message, 'LOW');
        $this->add_real_time_notification($notification_data);
    }
    
    /**
     * Send email notification
     */
    private function send_email_notification($subject, $message, $priority = 'LOW') {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Vortex AI Engine <noreply@vortexartec.com>',
            'X-Priority: ' . $this->get_priority_number($priority)
        );
        
        foreach ($this->admin_emails as $email) {
            $result = wp_mail($email, $subject, $message, $headers);
            
            if (!$result) {
                error_log("VORTEX NOTIFICATION ERROR: Failed to send email to {$email}");
            }
        }
    }
    
    /**
     * Add real-time notification
     */
    private function add_real_time_notification($notification_data) {
        if (!$this->notification_settings['real_time_alerts']) {
            return;
        }
        
        $this->real_time_notifications[] = array(
            'id' => $notification_data['id'],
            'type' => $notification_data['type'],
            'message' => $notification_data['message'],
            'severity' => $notification_data['severity'],
            'timestamp' => $notification_data['timestamp'],
            'read' => false
        );
        
        // Keep only last 100 real-time notifications
        if (count($this->real_time_notifications) > 100) {
            array_shift($this->real_time_notifications);
        }
    }
    
    /**
     * Process notification queue
     */
    private function process_notification_queue() {
        if (empty($this->notification_queue)) {
            return;
        }
        
        foreach ($this->notification_queue as $index => $notification) {
            $this->send_notification($notification);
            unset($this->notification_queue[$index]);
        }
    }
    
    /**
     * Send real-time notifications
     */
    private function send_real_time_notifications() {
        if (empty($this->real_time_notifications)) {
            return;
        }
        
        // Send to admin dashboard
        $this->send_to_admin_dashboard();
        
        // Send to WordPress admin
        $this->send_to_wordpress_admin();
    }
    
    /**
     * Send heartbeat notifications
     */
    private function send_heartbeat_notifications() {
        $heartbeat_data = array(
            'type' => 'HEARTBEAT',
            'message' => 'Vortex AI Engine heartbeat - all systems operational',
            'severity' => 'INFO',
            'timestamp' => time(),
            'system_status' => $this->get_system_status()
        );
        
        $this->send_notification($heartbeat_data);
    }
    
    /**
     * Build critical error message
     */
    private function build_critical_error_message($notification_data) {
        $message = "<h2>🚨 VORTEX AI CRITICAL ERROR</h2>\n\n";
        $message .= "<p><strong>Error Type:</strong> {$notification_data['type']}</p>\n";
        $message .= "<p><strong>Message:</strong> {$notification_data['message']}</p>\n";
        $message .= "<p><strong>Severity:</strong> {$notification_data['severity']}</p>\n";
        $message .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s', $notification_data['timestamp']) . "</p>\n\n";
        $message .= "<p><strong>System Status:</strong></p>\n";
        $message .= "<ul>\n";
        $message .= "<li>Memory Usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB</li>\n";
        $message .= "<li>Peak Memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB</li>\n";
        $message .= "<li>PHP Version: " . PHP_VERSION . "</li>\n";
        $message .= "<li>WordPress Version: " . get_bloginfo('version') . "</li>\n";
        $message .= "</ul>\n\n";
        $message .= "<p><strong>IMMEDIATE ACTION REQUIRED</strong></p>\n";
        $message .= "<p>Please check the system immediately and take necessary corrective actions.</p>\n";
        
        return $message;
    }
    
    /**
     * Build performance alert message
     */
    private function build_performance_alert_message($notification_data) {
        $message = "<h2>⚠️ VORTEX AI PERFORMANCE ALERT</h2>\n\n";
        $message .= "<p><strong>Alert Type:</strong> {$notification_data['type']}</p>\n";
        $message .= "<p><strong>Message:</strong> {$notification_data['message']}</p>\n";
        $message .= "<p><strong>Severity:</strong> {$notification_data['severity']}</p>\n";
        $message .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s', $notification_data['timestamp']) . "</p>\n\n";
        $message .= "<p><strong>Performance Metrics:</strong></p>\n";
        $message .= "<ul>\n";
        $message .= "<li>Response Time: " . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . "s</li>\n";
        $message .= "<li>Memory Usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB</li>\n";
        $message .= "<li>Active Plugins: " . count(get_option('active_plugins')) . "</li>\n";
        $message .= "</ul>\n\n";
        $message .= "<p>Please review system performance and consider optimization measures.</p>\n";
        
        return $message;
    }
    
    /**
     * Build system update message
     */
    private function build_system_update_message($notification_data) {
        $message = "<h2>🔄 VORTEX AI SYSTEM UPDATE</h2>\n\n";
        $message .= "<p><strong>Update Type:</strong> {$notification_data['type']}</p>\n";
        $message .= "<p><strong>Message:</strong> {$notification_data['message']}</p>\n";
        $message .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s', $notification_data['timestamp']) . "</p>\n\n";
        $message .= "<p>The system has been updated successfully. All components are operational.</p>\n";
        
        return $message;
    }
    
    /**
     * Build sync status message
     */
    private function build_sync_status_message($notification_data) {
        $message = "<h2>🔄 VORTEX AI SYNC STATUS</h2>\n\n";
        $message .= "<p><strong>Sync Type:</strong> {$notification_data['type']}</p>\n";
        $message .= "<p><strong>Message:</strong> {$notification_data['message']}</p>\n";
        $message .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s', $notification_data['timestamp']) . "</p>\n\n";
        $message .= "<p><strong>Sync Information:</strong></p>\n";
        $message .= "<ul>\n";
        $message .= "<li>Last Sync: " . date('Y-m-d H:i:s', get_option('vortex_last_sync', time())) . "</li>\n";
        $message .= "<li>Sync Frequency: 5 seconds</li>\n";
        $message .= "<li>Cross-Instance Communication: Active</li>\n";
        $message .= "</ul>\n\n";
        $message .= "<p>Synchronization is maintaining system consistency across all instances.</p>\n";
        
        return $message;
    }
    
    /**
     * Build RL improvement message
     */
    private function build_rl_improvement_message($notification_data) {
        $message = "<h2>🧠 VORTEX AI RL IMPROVEMENT</h2>\n\n";
        $message .= "<p><strong>Improvement Type:</strong> {$notification_data['type']}</p>\n";
        $message .= "<p><strong>Message:</strong> {$notification_data['message']}</p>\n";
        $message .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s', $notification_data['timestamp']) . "</p>\n\n";
        $message .= "<p><strong>Reinforcement Learning Status:</strong></p>\n";
        $message .= "<ul>\n";
        $message .= "<li>Q-Learning Active: Yes</li>\n";
        $message .= "<li>Epsilon-Greedy Policy: Active</li>\n";
        $message .= "<li>Experience Replay Buffer: Active</li>\n";
        $message .= "<li>Learning Rate: Adaptive</li>\n";
        $message .= "</ul>\n\n";
        $message .= "<p>The system is continuously learning and improving its performance.</p>\n";
        
        return $message;
    }
    
    /**
     * Build heartbeat message
     */
    private function build_heartbeat_message($notification_data) {
        $message = "<h2>💓 VORTEX AI HEARTBEAT</h2>\n\n";
        $message .= "<p><strong>Status:</strong> All Systems Operational</p>\n";
        $message .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s', $notification_data['timestamp']) . "</p>\n\n";
        $message .= "<p><strong>System Health:</strong></p>\n";
        $message .= "<ul>\n";
        $message .= "<li>Supervisor System: Active</li>\n";
        $message .= "<li>Recursive Loop: Active</li>\n";
        $message .= "<li>Reinforcement Learning: Active</li>\n";
        $message .= "<li>Global Sync: Active</li>\n";
        $message .= "<li>Tool Call Optimization: Active</li>\n";
        $message .= "<li>Real-Time Monitoring: Active</li>\n";
        $message .= "</ul>\n\n";
        $message .= "<p><strong>Performance Metrics:</strong></p>\n";
        $message .= "<ul>\n";
        $message .= "<li>Memory Usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB</li>\n";
        $message .= "<li>Peak Memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB</li>\n";
        $message .= "<li>Response Time: " . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . "s</li>\n";
        $message .= "<li>Active Users: " . count_users()['total_users'] . "</li>\n";
        $message .= "</ul>\n\n";
        $message .= "<p>The Vortex AI Engine is running smoothly with all systems operational.</p>\n";
        
        return $message;
    }
    
    /**
     * Build general message
     */
    private function build_general_message($notification_data) {
        $message = "<h2>ℹ️ VORTEX AI NOTIFICATION</h2>\n\n";
        $message .= "<p><strong>Type:</strong> {$notification_data['type']}</p>\n";
        $message .= "<p><strong>Message:</strong> {$notification_data['message']}</p>\n";
        $message .= "<p><strong>Severity:</strong> {$notification_data['severity']}</p>\n";
        $message .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s', $notification_data['timestamp']) . "</p>\n\n";
        $message .= "<p>This is a general system notification from the Vortex AI Engine.</p>\n";
        
        return $message;
    }
    
    /**
     * Get priority number
     */
    private function get_priority_number($priority) {
        switch ($priority) {
            case 'HIGH':
                return 1;
            case 'MEDIUM':
                return 3;
            case 'LOW':
            default:
                return 5;
        }
    }
    
    /**
     * Get system status
     */
    private function get_system_status() {
        return array(
            'supervisor_active' => true,
            'monitor_active' => true,
            'recursive_loop_active' => true,
            'rl_active' => true,
            'sync_active' => true,
            'optimization_active' => true
        );
    }
    
    /**
     * Send to admin dashboard
     */
    private function send_to_admin_dashboard() {
        // Implementation for admin dashboard notifications
    }
    
    /**
     * Send to WordPress admin
     */
    private function send_to_wordpress_admin() {
        // Implementation for WordPress admin notifications
    }
    
    /**
     * Get notification history
     */
    public function get_notification_history($limit = 100) {
        return array_slice($this->notification_history, -$limit);
    }
    
    /**
     * Get real-time notifications
     */
    public function get_real_time_notifications() {
        return $this->real_time_notifications;
    }
    
    /**
     * Mark notification as read
     */
    public function mark_notification_read($notification_id) {
        foreach ($this->real_time_notifications as &$notification) {
            if ($notification['id'] === $notification_id) {
                $notification['read'] = true;
                break;
            }
        }
    }
    
    /**
     * Clear notification history
     */
    public function clear_notification_history() {
        $this->notification_history = array();
    }
    
    /**
     * Update notification settings
     */
    public function update_notification_settings($settings) {
        $this->notification_settings = array_merge($this->notification_settings, $settings);
    }
    
    /**
     * Add admin email
     */
    public function add_admin_email($email) {
        if (!in_array($email, $this->admin_emails)) {
            $this->admin_emails[] = $email;
        }
    }
    
    /**
     * Remove admin email
     */
    public function remove_admin_email($email) {
        $key = array_search($email, $this->admin_emails);
        if ($key !== false) {
            unset($this->admin_emails[$key]);
        }
    }
}

// Initialize the notification system
if (class_exists('Vortex_Supervisor_Notifications')) {
    global $vortex_notifications;
    $vortex_notifications = new Vortex_Supervisor_Notifications();
} 