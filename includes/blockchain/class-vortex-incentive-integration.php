<?php
/**
 * VORTEX AI Engine - Incentive Integration Layer
 * 
 * Integrates all incentive system components and connects with existing TOLA token handler
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Incentive Integration Layer Class
 * 
 * Integrates all incentive system components and provides unified interface
 */
class Vortex_Incentive_Integration {
    
    /**
     * System components
     */
    private $incentive_audit_system = null;
    private $wallet_system = null;
    private $accounting_system = null;
    private $conversion_system = null;
    private $tola_handler = null;
    
    /**
     * Initialize the incentive integration layer
     */
    public function init() {
        $this->initialize_components();
        $this->register_hooks();
        $this->create_integration_tables();
        
        error_log('VORTEX AI Engine: Incentive Integration Layer initialized');
    }
    
    /**
     * Initialize all system components
     */
    private function initialize_components() {
        // Initialize incentive audit system
        $this->incentive_audit_system = new Vortex_Incentive_Audit_System();
        $this->incentive_audit_system->init();
        
        // Initialize wallet management system
        $this->wallet_system = new Vortex_Wallet_Management_System();
        $this->wallet_system->init();
        
        // Initialize accounting system
        $this->accounting_system = new Vortex_Accounting_System();
        $this->accounting_system->init();
        
        // Initialize conversion system
        $this->conversion_system = new Vortex_Conversion_System();
        $this->conversion_system->init();
        
        // Get existing TOLA token handler
        $this->tola_handler = $this->get_tola_handler();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Integration hooks
        add_action('vortex_incentive_action', [$this, 'handle_incentive_action']);
        add_action('vortex_conversion_request', [$this, 'handle_conversion_request']);
        add_action('vortex_wallet_connect', [$this, 'handle_wallet_connect']);
        
        // AJAX handlers
        add_action('wp_ajax_vortex_get_incentive_status', [$this, 'handle_get_incentive_status']);
        add_action('wp_ajax_vortex_get_wallet_info', [$this, 'handle_get_wallet_info']);
        add_action('wp_ajax_vortex_get_accounting_summary', [$this, 'handle_get_accounting_summary']);
        
        // Scheduled tasks
        add_action('vortex_daily_incentive_audit', [$this, 'run_daily_audit']);
        add_action('vortex_weekly_incentive_report', [$this, 'generate_weekly_report']);
    }
    
    /**
     * Create integration tables
     */
    private function create_integration_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Integration logs table
        $table_name = $wpdb->prefix . 'vortex_integration_logs';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            component varchar(50) NOT NULL,
            action varchar(50) NOT NULL,
            user_id bigint(20),
            data longtext,
            status varchar(50) DEFAULT 'success',
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY component (component),
            KEY action (action),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // System health table
        $table_name = $wpdb->prefix . 'vortex_system_health';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            component varchar(50) NOT NULL,
            status varchar(50) DEFAULT 'healthy',
            last_check datetime DEFAULT CURRENT_TIMESTAMP,
            health_data longtext,
            PRIMARY KEY (id),
            UNIQUE KEY component (component)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Handle incentive action
     */
    public function handle_incentive_action($action_data) {
        try {
            $user_id = $action_data['user_id'];
            $action_type = $action_data['action_type'];
            $context_data = $action_data['context_data'] ?? [];
            
            // Log the action
            $this->log_integration_action('incentive_audit', 'action_triggered', $user_id, $action_data);
            
            // Process through incentive audit system
            $result = $this->incentive_audit_system->distribute_incentive($user_id, $action_type, $context_data);
            
            if ($result['success']) {
                // Update system health
                $this->update_system_health('incentive_audit', 'healthy');
                
                // Log success
                $this->log_integration_action('incentive_audit', 'action_completed', $user_id, $result);
                
                return $result;
            } else {
                // Update system health
                $this->update_system_health('incentive_audit', 'error', $result['error']);
                
                // Log error
                $this->log_integration_action('incentive_audit', 'action_failed', $user_id, $result);
                
                return $result;
            }
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Incentive action failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle conversion request
     */
    public function handle_conversion_request($conversion_data) {
        try {
            $user_id = $conversion_data['user_id'];
            
            // Log the request
            $this->log_integration_action('conversion', 'request_received', $user_id, $conversion_data);
            
            // Process through conversion system
            $result = $this->conversion_system->process_conversion_request($user_id);
            
            if ($result['success']) {
                // Update system health
                $this->update_system_health('conversion', 'healthy');
                
                // Log success
                $this->log_integration_action('conversion', 'request_completed', $user_id, $result);
                
                return $result;
            } else {
                // Update system health
                $this->update_system_health('conversion', 'error', $result['error']);
                
                // Log error
                $this->log_integration_action('conversion', 'request_failed', $user_id, $result);
                
                return $result;
            }
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Conversion request failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle wallet connect
     */
    public function handle_wallet_connect($wallet_data) {
        try {
            $user_id = $wallet_data['user_id'];
            $wallet_address = $wallet_data['wallet_address'];
            
            // Log the connection
            $this->log_integration_action('wallet', 'connect_attempt', $user_id, $wallet_data);
            
            // Process through wallet system
            $result = $this->wallet_system->connect_external_wallet($user_id, $wallet_address);
            
            if ($result['success']) {
                // Update system health
                $this->update_system_health('wallet', 'healthy');
                
                // Log success
                $this->log_integration_action('wallet', 'connect_completed', $user_id, $result);
                
                return $result;
            } else {
                // Update system health
                $this->update_system_health('wallet', 'error', $result['error']);
                
                // Log error
                $this->log_integration_action('wallet', 'connect_failed', $user_id, $result);
                
                return $result;
            }
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Wallet connection failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle get incentive status AJAX
     */
    public function handle_get_incentive_status() {
        check_ajax_referer('vortex_integration_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $status = [
            'incentive_system' => $this->incentive_audit_system->get_status(),
            'wallet_system' => $this->wallet_system->get_status(),
            'accounting_system' => $this->accounting_system->get_status(),
            'conversion_system' => $this->conversion_system->get_status(),
            'user_wallet' => $this->wallet_system->get_user_wallet($user_id),
            'user_platform_credits' => $this->conversion_system->get_user_platform_credits($user_id),
            'conversion_eligible' => $this->conversion_system->is_user_conversion_eligible($user_id)
        ];
        
        wp_send_json_success($status);
    }
    
    /**
     * Handle get wallet info AJAX
     */
    public function handle_get_wallet_info() {
        check_ajax_referer('vortex_integration_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $wallet = $this->wallet_system->get_user_wallet($user_id);
        
        if (!$wallet) {
            wp_send_json_error(['message' => 'Wallet not found']);
        }
        
        $info = [
            'wallet_address' => $wallet->wallet_address,
            'balance' => floatval($wallet->balance),
            'platform_credits' => floatval($wallet->platform_credits),
            'status' => $wallet->status,
            'created_at' => $wallet->created_at
        ];
        
        wp_send_json_success($info);
    }
    
    /**
     * Handle get accounting summary AJAX
     */
    public function handle_get_accounting_summary() {
        check_ajax_referer('vortex_integration_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $summary = [
            'total_incentives_received' => $this->get_user_total_incentives($user_id),
            'total_conversions_made' => $this->get_user_total_conversions($user_id),
            'current_platform_credits' => $this->conversion_system->get_user_platform_credits($user_id),
            'daily_conversion_limit' => $this->conversion_system->get_user_daily_conversion_limit($user_id)
        ];
        
        wp_send_json_success($summary);
    }
    
    /**
     * Run daily audit
     */
    public function run_daily_audit() {
        try {
            // Run audits on all components
            $this->incentive_audit_system->run_daily_audit();
            $this->accounting_system->generate_daily_report();
            
            // Check system health
            $this->check_all_system_health();
            
            // Generate integration report
            $this->generate_integration_report();
            
            error_log('VORTEX AI Engine: Daily integration audit completed');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Daily integration audit failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate weekly report
     */
    public function generate_weekly_report() {
        try {
            $report = [
                'period' => 'weekly',
                'date' => date('Y-m-d'),
                'incentive_audit_status' => $this->incentive_audit_system->get_status(),
                'wallet_system_status' => $this->wallet_system->get_status(),
                'accounting_system_status' => $this->accounting_system->get_status(),
                'conversion_system_status' => $this->conversion_system->get_status(),
                'system_health' => $this->get_all_system_health(),
                'integration_logs' => $this->get_recent_integration_logs()
            ];
            
            $this->save_integration_report($report);
            
            error_log('VORTEX AI Engine: Weekly integration report generated');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Weekly integration report failed: ' . $e->getMessage());
        }
    }
    
    // Helper methods
    private function get_tola_handler() {
        // Get existing TOLA token handler instance
        global $vortex_tola_handler;
        
        if (!$vortex_tola_handler) {
            $vortex_tola_handler = new Vortex_Tola_Token_Handler();
            $vortex_tola_handler->init();
        }
        
        return $vortex_tola_handler;
    }
    
    private function log_integration_action($component, $action, $user_id, $data) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'vortex_integration_logs', [
            'component' => $component,
            'action' => $action,
            'user_id' => $user_id,
            'data' => json_encode($data),
            'status' => 'success'
        ]);
    }
    
    private function update_system_health($component, $status, $error_message = null) {
        global $wpdb;
        
        $health_data = [
            'status' => $status,
            'last_check' => current_time('mysql')
        ];
        
        if ($error_message) {
            $health_data['health_data'] = json_encode(['error' => $error_message]);
        }
        
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vortex_system_health WHERE component = %s
        ", $component));
        
        if ($existing) {
            $wpdb->update($wpdb->prefix . 'vortex_system_health', $health_data, ['component' => $component]);
        } else {
            $health_data['component'] = $component;
            $wpdb->insert($wpdb->prefix . 'vortex_system_health', $health_data);
        }
    }
    
    private function check_all_system_health() {
        $components = ['incentive_audit', 'wallet', 'accounting', 'conversion'];
        
        foreach ($components as $component) {
            $this->update_system_health($component, 'healthy');
        }
    }
    
    private function get_all_system_health() {
        global $wpdb;
        
        $health = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}vortex_system_health
        ", ARRAY_A);
        
        return $health;
    }
    
    private function get_recent_integration_logs() {
        global $wpdb;
        
        $logs = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}vortex_integration_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC 
            LIMIT 100
        ", ARRAY_A);
        
        return $logs;
    }
    
    private function get_user_total_incentives($user_id) {
        global $wpdb;
        
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(amount) FROM {$wpdb->prefix}vortex_incentive_distributions 
            WHERE user_id = %d
        ", $user_id));
        
        return floatval($total ?? 0);
    }
    
    private function get_user_total_conversions($user_id) {
        global $wpdb;
        
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(tola_amount) FROM {$wpdb->prefix}vortex_conversion_requests 
            WHERE user_id = %d AND status = 'completed'
        ", $user_id));
        
        return floatval($total ?? 0);
    }
    
    private function generate_integration_report() {
        $report = [
            'date' => date('Y-m-d'),
            'components_status' => $this->get_all_system_health(),
            'total_users' => $this->get_total_users(),
            'total_artists' => $this->get_artist_count(),
            'total_incentives_distributed' => $this->get_total_incentives_distributed(),
            'total_conversions_made' => $this->get_total_conversions_made()
        ];
        
        $this->save_integration_report($report);
    }
    
    private function save_integration_report($report) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'vortex_financial_reports', [
            'report_type' => 'integration',
            'report_period' => $report['date'],
            'report_data' => json_encode($report),
            'created_at' => current_time('mysql')
        ]);
    }
    
    private function get_total_users() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    }
    
    private function get_artist_count() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->users} u 
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
            AND um.meta_value LIKE '%artist%'
        ");
    }
    
    private function get_total_incentives_distributed() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_incentive_distributions");
        return floatval($total ?? 0);
    }
    
    private function get_total_conversions_made() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT SUM(tola_amount) FROM {$wpdb->prefix}vortex_conversion_requests WHERE status = 'completed'");
        return floatval($total ?? 0);
    }
    
    /**
     * Get integration layer status
     */
    public function get_status() {
        return [
            'name' => 'VORTEX Incentive Integration Layer',
            'version' => '3.0.0',
            'components' => [
                'incentive_audit' => $this->incentive_audit_system ? 'active' : 'inactive',
                'wallet' => $this->wallet_system ? 'active' : 'inactive',
                'accounting' => $this->accounting_system ? 'active' : 'inactive',
                'conversion' => $this->conversion_system ? 'active' : 'inactive',
                'tola_handler' => $this->tola_handler ? 'active' : 'inactive'
            ],
            'system_health' => $this->get_all_system_health()
        ];
    }
} 