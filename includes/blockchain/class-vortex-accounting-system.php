<?php
/**
 * VORTEX AI Engine - Accounting System
 * 
 * Handles financial tracking, reporting, and audit trails for the incentive system
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Accounting System Class
 * 
 * Manages financial tracking, reporting, and audit trails
 */
class Vortex_Accounting_System {
    
    /**
     * System configuration
     */
    private $config = [
        'name' => 'VORTEX Accounting System',
        'version' => '3.0.0',
        'currency' => 'TOLA',
        'usdc_rate' => 1.0, // 1 TOLA = 1 USDC
        'decimal_places' => 8
    ];
    
    /**
     * Initialize the accounting system
     */
    public function init() {
        $this->load_configuration();
        $this->register_hooks();
        $this->create_tables();
        
        error_log('VORTEX AI Engine: Accounting System initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->config['usdc_rate'] = get_option('vortex_tola_usdc_rate', 1.0);
        $this->config['decimal_places'] = get_option('vortex_decimal_places', 8);
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('vortex_daily_accounting_report', [$this, 'generate_daily_report']);
        add_action('vortex_monthly_accounting_report', [$this, 'generate_monthly_report']);
        add_action('wp_ajax_vortex_get_accounting_report', [$this, 'handle_get_accounting_report']);
        add_action('wp_ajax_vortex_export_accounting_data', [$this, 'handle_export_accounting_data']);
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Financial transactions table
        $table_name = $wpdb->prefix . 'vortex_financial_transactions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            currency varchar(10) DEFAULT 'TOLA',
            usdc_equivalent decimal(20,8) DEFAULT 0.00000000,
            transaction_category varchar(50) NOT NULL,
            description text,
            metadata longtext,
            status varchar(50) DEFAULT 'completed',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_id (transaction_id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY transaction_category (transaction_category),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Incentive distributions table
        $table_name = $wpdb->prefix . 'vortex_incentive_distributions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            incentive_type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            transaction_hash varchar(255),
            context_data longtext,
            platform_credit_only tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY incentive_type (incentive_type),
            KEY platform_credit_only (platform_credit_only),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Platform credits table
        $table_name = $wpdb->prefix . 'vortex_platform_credits';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            credit_type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            balance_before decimal(20,8) NOT NULL,
            balance_after decimal(20,8) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY credit_type (credit_type),
            KEY transaction_type (transaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Financial reports table
        $table_name = $wpdb->prefix . 'vortex_financial_reports';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            report_period varchar(50) NOT NULL,
            report_data longtext NOT NULL,
            total_tola_distributed decimal(20,8) DEFAULT 0.00000000,
            total_usdc_equivalent decimal(20,8) DEFAULT 0.00000000,
            total_transactions int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY report_type (report_type),
            KEY report_period (report_period),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Record incentive distribution
     */
    public function record_incentive_distribution($user_id, $incentive_type, $amount, $transaction_hash, $context_data = []) {
        try {
            global $wpdb;
            
            // Calculate USDC equivalent
            $usdc_equivalent = $this->calculate_usdc_equivalent($amount);
            
            // Record financial transaction
            $financial_result = $wpdb->insert($wpdb->prefix . 'vortex_financial_transactions', [
                'transaction_id' => $transaction_hash,
                'user_id' => $user_id,
                'transaction_type' => 'incentive_distribution',
                'amount' => $amount,
                'currency' => 'TOLA',
                'usdc_equivalent' => $usdc_equivalent,
                'transaction_category' => $incentive_type,
                'description' => "Incentive distribution for {$incentive_type}",
                'metadata' => json_encode($context_data),
                'status' => 'completed'
            ]);
            
            if (!$financial_result) {
                throw new Exception('Failed to record financial transaction');
            }
            
            // Record incentive distribution
            $incentive_result = $wpdb->insert($wpdb->prefix . 'vortex_incentive_distributions', [
                'user_id' => $user_id,
                'incentive_type' => $incentive_type,
                'amount' => $amount,
                'transaction_hash' => $transaction_hash,
                'context_data' => json_encode($context_data),
                'platform_credit_only' => 1
            ]);
            
            if (!$incentive_result) {
                throw new Exception('Failed to record incentive distribution');
            }
            
            // Update platform credits
            $this->update_platform_credits($user_id, $amount, 'incentive_distribution', $incentive_type);
            
            error_log("VORTEX AI Engine: Recorded incentive distribution - User: {$user_id}, Type: {$incentive_type}, Amount: {$amount} TOLA");
            
            return [
                'success' => true,
                'transaction_id' => $transaction_hash,
                'amount' => $amount,
                'usdc_equivalent' => $usdc_equivalent
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Failed to record incentive distribution: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Record conversion transaction
     */
    public function record_conversion_transaction($user_id, $tola_amount, $usdc_amount, $transaction_hash, $context_data = []) {
        try {
            global $wpdb;
            
            // Record financial transaction
            $result = $wpdb->insert($wpdb->prefix . 'vortex_financial_transactions', [
                'transaction_id' => $transaction_hash,
                'user_id' => $user_id,
                'transaction_type' => 'conversion',
                'amount' => $tola_amount,
                'currency' => 'TOLA',
                'usdc_equivalent' => $usdc_amount,
                'transaction_category' => 'platform_credit_conversion',
                'description' => 'Platform credit conversion to USDC',
                'metadata' => json_encode($context_data),
                'status' => 'completed'
            ]);
            
            if (!$result) {
                throw new Exception('Failed to record conversion transaction');
            }
            
            // Update platform credits (deduct)
            $this->update_platform_credits($user_id, -$tola_amount, 'conversion', 'platform_credit_conversion');
            
            error_log("VORTEX AI Engine: Recorded conversion transaction - User: {$user_id}, TOLA: {$tola_amount}, USDC: {$usdc_amount}");
            
            return [
                'success' => true,
                'transaction_id' => $transaction_hash,
                'tola_amount' => $tola_amount,
                'usdc_amount' => $usdc_amount
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Failed to record conversion transaction: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update platform credits
     */
    private function update_platform_credits($user_id, $amount_change, $transaction_type, $description) {
        try {
            global $wpdb;
            
            // Get current balance
            $current_balance = $this->get_user_platform_credits($user_id);
            $new_balance = $current_balance + $amount_change;
            
            // Update wallet balance
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}vortex_user_wallets 
                 SET platform_credits = %f, updated_at = NOW() 
                 WHERE user_id = %d",
                $new_balance,
                $user_id
            ));
            
            // Record platform credit transaction
            $wpdb->insert($wpdb->prefix . 'vortex_platform_credits', [
                'user_id' => $user_id,
                'credit_type' => 'tola_platform_credits',
                'amount' => $amount_change,
                'balance_before' => $current_balance,
                'balance_after' => $new_balance,
                'transaction_type' => $transaction_type,
                'description' => $description
            ]);
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Failed to update platform credits: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user platform credits
     */
    public function get_user_platform_credits($user_id) {
        global $wpdb;
        
        $credits = $wpdb->get_var($wpdb->prepare(
            "SELECT platform_credits FROM {$wpdb->prefix}vortex_user_wallets WHERE user_id = %d",
            $user_id
        ));
        
        return floatval($credits ?? 0);
    }
    
    /**
     * Get daily distributions
     */
    public function get_daily_distributions() {
        global $wpdb;
        
        $distributions = $wpdb->get_results("
            SELECT 
                incentive_type,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM {$wpdb->prefix}vortex_incentive_distributions 
            WHERE DATE(created_at) = CURDATE()
            GROUP BY incentive_type
        ", ARRAY_A);
        
        return $distributions;
    }
    
    /**
     * Get daily TOLA distributed
     */
    public function get_daily_tola_distributed() {
        global $wpdb;
        
        $total = $wpdb->get_var("
            SELECT SUM(amount) 
            FROM {$wpdb->prefix}vortex_incentive_distributions 
            WHERE DATE(created_at) = CURDATE()
        ");
        
        return floatval($total ?? 0);
    }
    
    /**
     * Generate daily report
     */
    public function generate_daily_report() {
        try {
            $report_data = [
                'date' => date('Y-m-d'),
                'total_distributions' => $this->get_daily_distributions(),
                'total_tola_distributed' => $this->get_daily_tola_distributed(),
                'total_usdc_equivalent' => $this->calculate_usdc_equivalent($this->get_daily_tola_distributed()),
                'total_transactions' => $this->get_daily_transaction_count(),
                'artist_count' => $this->get_artist_count(),
                'conversion_status' => get_option('vortex_conversion_enabled', false)
            ];
            
            $this->save_report('daily', date('Y-m-d'), $report_data);
            
            error_log('VORTEX AI Engine: Daily accounting report generated');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Daily report generation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate monthly report
     */
    public function generate_monthly_report() {
        try {
            $month = date('Y-m');
            $report_data = [
                'month' => $month,
                'total_distributions' => $this->get_monthly_distributions(),
                'total_tola_distributed' => $this->get_monthly_tola_distributed(),
                'total_usdc_equivalent' => $this->calculate_usdc_equivalent($this->get_monthly_tola_distributed()),
                'total_transactions' => $this->get_monthly_transaction_count(),
                'artist_growth' => $this->get_artist_growth_rate(),
                'conversion_activity' => $this->get_monthly_conversion_activity()
            ];
            
            $this->save_report('monthly', $month, $report_data);
            
            error_log('VORTEX AI Engine: Monthly accounting report generated');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Monthly report generation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle get accounting report AJAX
     */
    public function handle_get_accounting_report() {
        check_ajax_referer('vortex_accounting_nonce', 'nonce');
        
        $report_type = sanitize_text_field($_POST['report_type'] ?? 'daily');
        $period = sanitize_text_field($_POST['period'] ?? date('Y-m-d'));
        
        $report = $this->get_report($report_type, $period);
        
        if ($report) {
            wp_send_json_success($report);
        } else {
            wp_send_json_error(['message' => 'Report not found']);
        }
    }
    
    /**
     * Handle export accounting data AJAX
     */
    public function handle_export_accounting_data() {
        check_ajax_referer('vortex_accounting_nonce', 'nonce');
        
        $export_type = sanitize_text_field($_POST['export_type'] ?? 'transactions');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        $data = $this->export_data($export_type, $date_from, $date_to);
        
        if ($data) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error(['message' => 'No data to export']);
        }
    }
    
    // Helper methods
    private function calculate_usdc_equivalent($tola_amount) {
        return $tola_amount * $this->config['usdc_rate'];
    }
    
    private function get_daily_transaction_count() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}vortex_financial_transactions 
            WHERE DATE(created_at) = CURDATE()
        ");
    }
    
    private function get_monthly_distributions() {
        global $wpdb;
        $month = date('Y-m');
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                incentive_type,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM {$wpdb->prefix}vortex_incentive_distributions 
            WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s
            GROUP BY incentive_type
        ", $month), ARRAY_A);
    }
    
    private function get_monthly_tola_distributed() {
        global $wpdb;
        $month = date('Y-m');
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(amount) 
            FROM {$wpdb->prefix}vortex_incentive_distributions 
            WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s
        ", $month));
        return floatval($total ?? 0);
    }
    
    private function get_monthly_transaction_count() {
        global $wpdb;
        $month = date('Y-m');
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}vortex_financial_transactions 
            WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s
        ", $month));
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
    
    private function get_artist_growth_rate() {
        // Calculate artist growth rate for the month
        return 0; // Simplified for now
    }
    
    private function get_monthly_conversion_activity() {
        global $wpdb;
        $month = date('Y-m');
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                COUNT(*) as conversion_count,
                SUM(amount) as total_converted_tola,
                SUM(usdc_equivalent) as total_converted_usdc
            FROM {$wpdb->prefix}vortex_financial_transactions 
            WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s
            AND transaction_type = 'conversion'
        ", $month), ARRAY_A);
    }
    
    private function save_report($report_type, $period, $report_data) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'vortex_financial_reports', [
            'report_type' => $report_type,
            'report_period' => $period,
            'report_data' => json_encode($report_data),
            'total_tola_distributed' => $report_data['total_tola_distributed'] ?? 0,
            'total_usdc_equivalent' => $report_data['total_usdc_equivalent'] ?? 0,
            'total_transactions' => $report_data['total_transactions'] ?? 0
        ]);
    }
    
    private function get_report($report_type, $period) {
        global $wpdb;
        
        $report = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vortex_financial_reports 
            WHERE report_type = %s AND report_period = %s
            ORDER BY created_at DESC LIMIT 1
        ", $report_type, $period));
        
        if ($report) {
            $report->report_data = json_decode($report->report_data, true);
        }
        
        return $report;
    }
    
    private function export_data($export_type, $date_from, $date_to) {
        global $wpdb;
        
        $where_clause = '';
        if ($date_from && $date_to) {
            $where_clause = $wpdb->prepare(" WHERE created_at BETWEEN %s AND %s", $date_from, $date_to);
        }
        
        switch ($export_type) {
            case 'transactions':
                return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vortex_financial_transactions{$where_clause} ORDER BY created_at DESC");
            case 'incentives':
                return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vortex_incentive_distributions{$where_clause} ORDER BY created_at DESC");
            case 'platform_credits':
                return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vortex_platform_credits{$where_clause} ORDER BY created_at DESC");
            default:
                return null;
        }
    }
    
    /**
     * Get accounting system status
     */
    public function get_status() {
        global $wpdb;
        
        $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_financial_transactions");
        $total_incentives = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_incentive_distributions");
        $total_tola_distributed = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_incentive_distributions");
        
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'currency' => $this->config['currency'],
            'usdc_rate' => $this->config['usdc_rate'],
            'total_transactions' => intval($total_transactions),
            'total_incentives' => intval($total_incentives),
            'total_tola_distributed' => floatval($total_tola_distributed)
        ];
    }
} 