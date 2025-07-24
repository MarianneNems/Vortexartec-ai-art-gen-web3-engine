<?php
/**
 * Vortex Auditor
 * 
 * Handles system auditing and security monitoring for the VORTEX AI Engine plugin
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Auditor Class
 */
class VortexAIEngine_Auditor {
    
    /**
     * Audit results
     */
    private $audit_results = array();
    
    /**
     * Database manager
     */
    private $db_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_manager = Vortex_Database_Manager::get_instance();
    }
    
    /**
     * Run full system audit
     */
    public function run_full_audit() {
        $this->audit_results = array();
        
        // Run all audit checks
        $this->audit_database_integrity();
        $this->audit_file_permissions();
        $this->audit_ai_agents_status();
        $this->audit_blockchain_connections();
        $this->audit_subscription_system();
        $this->audit_security_settings();
        $this->audit_performance_metrics();
        $this->audit_error_logs();
        $this->audit_user_activity();
        $this->audit_financial_transactions();
        
        // Generate audit report
        $report = $this->generate_audit_report();
        
        // Save audit results
        $this->save_audit_results($report);
        
        return $report;
    }
    
    /**
     * Audit database integrity
     */
    private function audit_database_integrity() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check if all required tables exist
        $required_tables = array(
            'artworks', 'artists', 'transactions', 'ai_generations',
            'smart_contracts', 'subscriptions', 'artist_journey',
            'market_analysis', 'system_logs', 'ai_agents_status'
        );
        
        foreach ($required_tables as $table) {
            $table_name = $this->db_manager->get_table($table);
            if (!$table_name) {
                $results['status'] = 'failed';
                $results['issues'][] = "Missing table: $table";
                $results['recommendations'][] = "Create missing table: $table";
            }
        }
        
        // Check for orphaned records
        $orphaned_artworks = $this->check_orphaned_artworks();
        if ($orphaned_artworks > 0) {
            $results['issues'][] = "Found $orphaned_artworks orphaned artworks";
            $results['recommendations'][] = "Clean up orphaned artworks";
        }
        
        // Check for data consistency
        $inconsistent_data = $this->check_data_consistency();
        if (!empty($inconsistent_data)) {
            $results['status'] = 'warning';
            $results['issues'] = array_merge($results['issues'], $inconsistent_data);
            $results['recommendations'][] = "Fix data consistency issues";
        }
        
        $this->audit_results['database_integrity'] = $results;
    }
    
    /**
     * Audit file permissions
     */
    private function audit_file_permissions() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check upload directory permissions
        $upload_dir = wp_upload_dir();
        $vortex_dir = $upload_dir['basedir'] . '/vortex-ai-engine/';
        
        if (file_exists($vortex_dir)) {
            $permissions = substr(sprintf('%o', fileperms($vortex_dir)), -4);
            if ($permissions !== '0755') {
                $results['status'] = 'warning';
                $results['issues'][] = "Upload directory permissions: $permissions (recommended: 0755)";
                $results['recommendations'][] = "Set upload directory permissions to 0755";
            }
        }
        
        // Check plugin file permissions
        $plugin_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'vortex-ai-engine.php';
        if (file_exists($plugin_file)) {
            $permissions = substr(sprintf('%o', fileperms($plugin_file)), -4);
            if ($permissions !== '0644') {
                $results['status'] = 'warning';
                $results['issues'][] = "Plugin file permissions: $permissions (recommended: 0644)";
                $results['recommendations'][] = "Set plugin file permissions to 0644";
            }
        }
        
        $this->audit_results['file_permissions'] = $results;
    }
    
    /**
     * Audit AI agents status
     */
    private function audit_ai_agents_status() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        $agents = array('HURAII', 'CLOE', 'HORACE', 'THORIUS');
        
        foreach ($agents as $agent) {
            $agent_status = $this->db_manager->get_row('ai_agents_status', array('agent_name' => $agent));
            
            if (!$agent_status) {
                $results['status'] = 'failed';
                $results['issues'][] = "Missing status record for agent: $agent";
                $results['recommendations'][] = "Initialize status for agent: $agent";
            } else {
                // Check if agent is active
                if ($agent_status->status !== 'active') {
                    $results['status'] = 'warning';
                    $results['issues'][] = "Agent $agent is not active (status: {$agent_status->status})";
                    $results['recommendations'][] = "Investigate agent $agent status";
                }
                
                // Check last activity
                $last_activity = strtotime($agent_status->last_activity);
                $hours_since_activity = (time() - $last_activity) / 3600;
                
                if ($hours_since_activity > 24) {
                    $results['status'] = 'warning';
                    $results['issues'][] = "Agent $agent has been inactive for " . round($hours_since_activity) . " hours";
                    $results['recommendations'][] = "Check agent $agent functionality";
                }
                
                // Check error rate
                $total_operations = $agent_status->error_count + $agent_status->success_count;
                if ($total_operations > 0) {
                    $error_rate = ($agent_status->error_count / $total_operations) * 100;
                    if ($error_rate > 10) {
                        $results['status'] = 'warning';
                        $results['issues'][] = "Agent $agent has high error rate: " . round($error_rate, 2) . "%";
                        $results['recommendations'][] = "Investigate agent $agent errors";
                    }
                }
            }
        }
        
        $this->audit_results['ai_agents_status'] = $results;
    }
    
    /**
     * Audit blockchain connections
     */
    private function audit_blockchain_connections() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check Solana RPC connection
        $options = get_option('vortex_ai_engine_options', array());
        if (empty($options['solana_rpc_url'])) {
            $results['status'] = 'warning';
            $results['issues'][] = "Solana RPC URL not configured";
            $results['recommendations'][] = "Configure Solana RPC URL";
        } else {
            // Test connection
            $connection_test = $this->test_solana_connection($options['solana_rpc_url']);
            if (!$connection_test) {
                $results['status'] = 'failed';
                $results['issues'][] = "Solana RPC connection failed";
                $results['recommendations'][] = "Check Solana RPC configuration";
            }
        }
        
        // Check wallet configuration
        if (empty($options['wallet_private_key'])) {
            $results['status'] = 'warning';
            $results['issues'][] = "Wallet private key not configured";
            $results['recommendations'][] = "Configure wallet private key";
        }
        
        // Check smart contract deployment
        $deployed_contracts = $this->db_manager->get_results('smart_contracts', array('status' => 'deployed'));
        if (empty($deployed_contracts)) {
            $results['status'] = 'warning';
            $results['issues'][] = "No smart contracts deployed";
            $results['recommendations'][] = "Deploy required smart contracts";
        }
        
        $this->audit_results['blockchain_connections'] = $results;
    }
    
    /**
     * Audit subscription system
     */
    private function audit_subscription_system() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check active subscriptions
        $active_subscriptions = $this->db_manager->get_results('subscriptions', array('status' => 'active'));
        $expired_subscriptions = $this->db_manager->get_results('subscriptions', array('status' => 'expired'));
        
        if (empty($active_subscriptions)) {
            $results['status'] = 'warning';
            $results['issues'][] = "No active subscriptions found";
            $results['recommendations'][] = "Check subscription system";
        }
        
        // Check for expired subscriptions that should be renewed
        $renewal_candidates = $this->get_renewal_candidates();
        if (!empty($renewal_candidates)) {
            $results['status'] = 'warning';
            $results['issues'][] = count($renewal_candidates) . " subscriptions need renewal";
            $results['recommendations'][] = "Process subscription renewals";
        }
        
        // Check payment processing
        $failed_payments = $this->get_failed_payments();
        if (!empty($failed_payments)) {
            $results['status'] = 'warning';
            $results['issues'][] = count($failed_payments) . " failed payments detected";
            $results['recommendations'][] = "Investigate failed payments";
        }
        
        $this->audit_results['subscription_system'] = $results;
    }
    
    /**
     * Audit security settings
     */
    private function audit_security_settings() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check WordPress security settings
        if (!defined('DISALLOW_FILE_EDIT') || !DISALLOW_FILE_EDIT) {
            $results['status'] = 'warning';
            $results['issues'][] = "File editing is enabled in WordPress";
            $results['recommendations'][] = "Disable file editing in wp-config.php";
        }
        
        if (!defined('DISALLOW_FILE_MODS') || !DISALLOW_FILE_MODS) {
            $results['status'] = 'warning';
            $results['issues'][] = "File modifications are enabled in WordPress";
            $results['recommendations'][] = "Disable file modifications in wp-config.php";
        }
        
        // Check for suspicious activity
        $suspicious_activity = $this->detect_suspicious_activity();
        if (!empty($suspicious_activity)) {
            $results['status'] = 'failed';
            $results['issues'] = array_merge($results['issues'], $suspicious_activity);
            $results['recommendations'][] = "Investigate suspicious activity";
        }
        
        // Check API key security
        $options = get_option('vortex_ai_engine_options', array());
        if (!empty($options['runpod_api_key']) && strlen($options['runpod_api_key']) < 32) {
            $results['status'] = 'warning';
            $results['issues'][] = "RunPod API key appears to be weak";
            $results['recommendations'][] = "Use a stronger API key";
        }
        
        $this->audit_results['security_settings'] = $results;
    }
    
    /**
     * Audit performance metrics
     */
    private function audit_performance_metrics() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check database performance
        $db_stats = $this->db_manager->get_stats();
        foreach ($db_stats as $table => $count) {
            if ($count > 10000) {
                $results['status'] = 'warning';
                $results['issues'][] = "Large table: $table has $count records";
                $results['recommendations'][] = "Consider archiving old data from $table";
            }
        }
        
        // Check AI generation performance
        $recent_generations = $this->db_manager->get_results(
            'ai_generations',
            array(),
            'created_at DESC',
            'created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        );
        
        $avg_processing_time = 0;
        if (!empty($recent_generations)) {
            $total_time = 0;
            foreach ($recent_generations as $generation) {
                $total_time += $generation->processing_time;
            }
            $avg_processing_time = $total_time / count($recent_generations);
            
            if ($avg_processing_time > 60) {
                $results['status'] = 'warning';
                $results['issues'][] = "High average processing time: " . round($avg_processing_time) . " seconds";
                $results['recommendations'][] = "Optimize AI generation performance";
            }
        }
        
        // Check storage usage
        $storage_usage = $this->get_storage_usage();
        if ($storage_usage > 1024 * 1024 * 1024) { // 1GB
            $results['status'] = 'warning';
            $results['issues'][] = "High storage usage: " . round($storage_usage / 1024 / 1024, 2) . " MB";
            $results['recommendations'][] = "Consider cloud storage or cleanup";
        }
        
        $this->audit_results['performance_metrics'] = $results;
    }
    
    /**
     * Audit error logs
     */
    private function audit_error_logs() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check recent error logs
        $recent_errors = $this->db_manager->get_results(
            'system_logs',
            array('log_level' => 'error'),
            'created_at DESC',
            'created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        );
        
        if (!empty($recent_errors)) {
            $results['status'] = 'warning';
            $results['issues'][] = count($recent_errors) . " errors in the last 24 hours";
            $results['recommendations'][] = "Review and fix recent errors";
            
            // Group errors by component
            $error_components = array();
            foreach ($recent_errors as $error) {
                if (!isset($error_components[$error->component])) {
                    $error_components[$error->component] = 0;
                }
                $error_components[$error->component]++;
            }
            
            foreach ($error_components as $component => $count) {
                if ($count > 5) {
                    $results['issues'][] = "High error rate in $component: $count errors";
                    $results['recommendations'][] = "Investigate $component errors";
                }
            }
        }
        
        // Check for critical errors
        $critical_errors = $this->db_manager->get_results(
            'system_logs',
            array('log_level' => 'critical'),
            'created_at DESC',
            'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );
        
        if (!empty($critical_errors)) {
            $results['status'] = 'failed';
            $results['issues'][] = count($critical_errors) . " critical errors in the last 7 days";
            $results['recommendations'][] = "Immediate attention required for critical errors";
        }
        
        $this->audit_results['error_logs'] = $results;
    }
    
    /**
     * Audit user activity
     */
    private function audit_user_activity() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check user registration rate
        $recent_users = count_users()['total_users'];
        $last_week_users = $this->get_users_registered_in_period(7);
        
        if ($last_week_users > 100) {
            $results['status'] = 'warning';
            $results['issues'][] = "High user registration rate: $last_week_users in last week";
            $results['recommendations'][] = "Monitor for potential spam registrations";
        }
        
        // Check user engagement
        $active_users = $this->get_active_users_count();
        $total_users = count_users()['total_users'];
        
        if ($total_users > 0) {
            $engagement_rate = ($active_users / $total_users) * 100;
            if ($engagement_rate < 10) {
                $results['status'] = 'warning';
                $results['issues'][] = "Low user engagement rate: " . round($engagement_rate, 2) . "%";
                $results['recommendations'][] = "Improve user engagement strategies";
            }
        }
        
        // Check for suspicious user activity
        $suspicious_users = $this->detect_suspicious_users();
        if (!empty($suspicious_users)) {
            $results['status'] = 'warning';
            $results['issues'][] = count($suspicious_users) . " suspicious user accounts detected";
            $results['recommendations'][] = "Review suspicious user accounts";
        }
        
        $this->audit_results['user_activity'] = $results;
    }
    
    /**
     * Audit financial transactions
     */
    private function audit_financial_transactions() {
        $results = array(
            'status' => 'passed',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check transaction volume
        $recent_transactions = $this->db_manager->get_results(
            'transactions',
            array('status' => 'completed'),
            'created_at DESC',
            'created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        );
        
        $total_volume = 0;
        foreach ($recent_transactions as $transaction) {
            $total_volume += $transaction->amount;
        }
        
        if ($total_volume > 10000) {
            $results['status'] = 'warning';
            $results['issues'][] = "High transaction volume: $" . number_format($total_volume, 2) . " in 24 hours";
            $results['recommendations'][] = "Monitor for unusual transaction patterns";
        }
        
        // Check for failed transactions
        $failed_transactions = $this->db_manager->get_results(
            'transactions',
            array('status' => 'failed'),
            'created_at DESC',
            'created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        );
        
        if (!empty($failed_transactions)) {
            $results['status'] = 'warning';
            $results['issues'][] = count($failed_transactions) . " failed transactions in 24 hours";
            $results['recommendations'][] = "Investigate failed transactions";
        }
        
        // Check royalty distribution
        $royalty_issues = $this->check_royalty_distribution();
        if (!empty($royalty_issues)) {
            $results['status'] = 'warning';
            $results['issues'] = array_merge($results['issues'], $royalty_issues);
            $results['recommendations'][] = "Review royalty distribution";
        }
        
        $this->audit_results['financial_transactions'] = $results;
    }
    
    /**
     * Generate audit report
     */
    private function generate_audit_report() {
        $report = array(
            'timestamp' => current_time('mysql'),
            'overall_status' => 'passed',
            'summary' => array(
                'total_checks' => count($this->audit_results),
                'passed' => 0,
                'warnings' => 0,
                'failed' => 0
            ),
            'details' => $this->audit_results,
            'recommendations' => array()
        );
        
        // Calculate summary
        foreach ($this->audit_results as $check => $result) {
            switch ($result['status']) {
                case 'passed':
                    $report['summary']['passed']++;
                    break;
                case 'warning':
                    $report['summary']['warnings']++;
                    if ($report['overall_status'] === 'passed') {
                        $report['overall_status'] = 'warning';
                    }
                    break;
                case 'failed':
                    $report['summary']['failed']++;
                    $report['overall_status'] = 'failed';
                    break;
            }
            
            // Collect recommendations
            if (!empty($result['recommendations'])) {
                $report['recommendations'] = array_merge($report['recommendations'], $result['recommendations']);
            }
        }
        
        // Remove duplicate recommendations
        $report['recommendations'] = array_unique($report['recommendations']);
        
        return $report;
    }
    
    /**
     * Save audit results
     */
    private function save_audit_results($report) {
        $this->db_manager->log('info', 'auditor', 'Full system audit completed', $report);
        
        // Store audit report in options
        update_option('vortex_last_audit_report', $report);
        update_option('vortex_audit_timestamp', current_time('mysql'));
    }
    
    /**
     * Helper methods
     */
    private function check_orphaned_artworks() {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM {$this->db_manager->get_table('artworks')} a 
                LEFT JOIN {$this->db_manager->get_table('artists')} ar ON a.artist_id = ar.id 
                WHERE ar.id IS NULL";
        
        return $wpdb->get_var($sql);
    }
    
    private function check_data_consistency() {
        $issues = array();
        
        // Check for artworks without images
        $artworks_without_images = $this->db_manager->get_results('artworks', array('image_url' => ''));
        if (!empty($artworks_without_images)) {
            $issues[] = count($artworks_without_images) . " artworks without images";
        }
        
        // Check for transactions without valid artwork
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$this->db_manager->get_table('transactions')} t 
                LEFT JOIN {$this->db_manager->get_table('artworks')} a ON t.artwork_id = a.id 
                WHERE a.id IS NULL";
        $invalid_transactions = $wpdb->get_var($sql);
        
        if ($invalid_transactions > 0) {
            $issues[] = "$invalid_transactions transactions with invalid artwork references";
        }
        
        return $issues;
    }
    
    private function test_solana_connection($rpc_url) {
        // Simulated Solana connection test
        // In a real implementation, this would make an actual RPC call
        return true;
    }
    
    private function get_renewal_candidates() {
        $renewal_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        return $this->db_manager->get_results(
            'subscriptions',
            array('status' => 'active'),
            '',
            "end_date <= '$renewal_date' AND auto_renew = 1"
        );
    }
    
    private function get_failed_payments() {
        // This would typically query a payments table
        // For now, return empty array
        return array();
    }
    
    private function detect_suspicious_activity() {
        $suspicious = array();
        
        // Check for multiple failed login attempts
        $failed_logins = $this->db_manager->get_results(
            'system_logs',
            array('log_level' => 'warning'),
            'created_at DESC',
            'created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) AND message LIKE "%failed login%"'
        );
        
        if (count($failed_logins) > 10) {
            $suspicious[] = "Multiple failed login attempts detected";
        }
        
        return $suspicious;
    }
    
    private function get_storage_usage() {
        $upload_dir = wp_upload_dir();
        $vortex_dir = $upload_dir['basedir'] . '/vortex-ai-engine/';
        
        if (!is_dir($vortex_dir)) {
            return 0;
        }
        
        $size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vortex_dir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    private function get_users_registered_in_period($days) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->users WHERE user_registered >= %s",
            $date
        ));
    }
    
    private function get_active_users_count() {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime('-30 days'));
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $wpdb->usermeta WHERE meta_key = 'last_activity' AND meta_value >= %s",
            $date
        ));
    }
    
    private function detect_suspicious_users() {
        // This would implement logic to detect suspicious user accounts
        // For now, return empty array
        return array();
    }
    
    private function check_royalty_distribution() {
        $issues = array();
        
        // Check for transactions with incorrect royalty calculations
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$this->db_manager->get_table('transactions')} 
                WHERE (creator_royalty + artist_royalty + marketplace_fee) != amount";
        $incorrect_royalties = $wpdb->get_var($sql);
        
        if ($incorrect_royalties > 0) {
            $issues[] = "$incorrect_royalties transactions with incorrect royalty calculations";
        }
        
        return $issues;
    }
} 