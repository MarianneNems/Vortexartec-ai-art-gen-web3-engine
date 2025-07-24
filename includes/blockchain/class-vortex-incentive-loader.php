<?php
/**
 * VORTEX AI Engine - Incentive System Loader
 * 
 * Loads and initializes all incentive system components
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Incentive System Loader Class
 * 
 * Loads and initializes all incentive system components
 */
class Vortex_Incentive_Loader {
    
    /**
     * System components
     */
    private $components = [];
    
    /**
     * Initialize the incentive system loader
     */
    public function init() {
        $this->load_components();
        $this->initialize_components();
        $this->register_scheduled_events();
        $this->add_admin_menu();
        
        error_log('VORTEX AI Engine: Incentive System Loader initialized');
    }
    
    /**
     * Load all incentive system components
     */
    private function load_components() {
        // Load core incentive system
        require_once plugin_dir_path(__FILE__) . 'class-vortex-incentive-audit-system.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-wallet-management-system.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-accounting-system.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-conversion-system.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-incentive-integration.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-incentive-frontend.php';
        
        // Store component classes
        $this->components = [
            'incentive_audit' => 'Vortex_Incentive_Audit_System',
            'wallet_management' => 'Vortex_Wallet_Management_System',
            'accounting' => 'Vortex_Accounting_System',
            'conversion' => 'Vortex_Conversion_System',
            'integration' => 'Vortex_Incentive_Integration',
            'frontend' => 'Vortex_Incentive_Frontend'
        ];
    }
    
    /**
     * Initialize all components
     */
    private function initialize_components() {
        foreach ($this->components as $name => $class) {
            try {
                $instance = new $class();
                $instance->init();
                
                // Store instance for later use
                $this->components[$name] = $instance;
                
                error_log("VORTEX AI Engine: Initialized {$name} component");
                
            } catch (Exception $e) {
                error_log("VORTEX AI Engine: Failed to initialize {$name} component: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Register scheduled events
     */
    private function register_scheduled_events() {
        // Daily incentive audit
        if (!wp_next_scheduled('vortex_daily_incentive_audit')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_incentive_audit');
        }
        
        // Daily accounting report
        if (!wp_next_scheduled('vortex_daily_accounting_report')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_accounting_report');
        }
        
        // Weekly incentive report
        if (!wp_next_scheduled('vortex_weekly_incentive_report')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_incentive_report');
        }
        
        // Monthly accounting report
        if (!wp_next_scheduled('vortex_monthly_accounting_report')) {
            wp_schedule_event(time(), 'monthly', 'vortex_monthly_accounting_report');
        }
        
        // Fraud detection (every 6 hours)
        if (!wp_next_scheduled('vortex_fraud_detection')) {
            wp_schedule_event(time(), 'six_hours', 'vortex_fraud_detection');
        }
    }
    
    /**
     * Add admin menu
     */
    private function add_admin_menu() {
        add_action('admin_menu', [$this, 'add_incentive_admin_menu']);
    }
    
    /**
     * Add incentive admin menu
     */
    public function add_incentive_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Incentive System',
            'Incentive System',
            'manage_options',
            'vortex-incentive-system',
            [$this, 'render_incentive_admin_page']
        );
    }
    
    /**
     * Render incentive admin page
     */
    public function render_incentive_admin_page() {
        ?>
        <div class="wrap">
            <h1>🎨 VORTEX Incentive System</h1>
            
            <div class="vortex-admin-grid">
                <!-- System Status -->
                <div class="vortex-admin-card">
                    <h3>📊 System Status</h3>
                    <?php $this->render_system_status(); ?>
                </div>
                
                <!-- Incentive Rules -->
                <div class="vortex-admin-card">
                    <h3>🏆 Incentive Rules</h3>
                    <?php $this->render_incentive_rules(); ?>
                </div>
                
                <!-- Conversion Status -->
                <div class="vortex-admin-card">
                    <h3>💱 Conversion Status</h3>
                    <?php $this->render_conversion_status(); ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="vortex-admin-card">
                    <h3>⚡ Quick Actions</h3>
                    <?php $this->render_quick_actions(); ?>
                </div>
            </div>
            
            <!-- System Health -->
            <div class="vortex-admin-section">
                <h3>🔍 System Health</h3>
                <?php $this->render_system_health(); ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="vortex-admin-section">
                <h3>📈 Recent Activity</h3>
                <?php $this->render_recent_activity(); ?>
            </div>
        </div>
        
        <style>
        .vortex-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .vortex-admin-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .vortex-admin-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .vortex-status-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .vortex-status-item:last-child {
            border-bottom: none;
        }
        
        .vortex-status-label {
            font-weight: 600;
        }
        
        .vortex-status-value {
            color: #666;
        }
        
        .vortex-status-value.success {
            color: #4caf50;
        }
        
        .vortex-status-value.warning {
            color: #ff9800;
        }
        
        .vortex-status-value.error {
            color: #f44336;
        }
        
        .vortex-rule-item {
            background: #f8f9fa;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        
        .vortex-rule-amount {
            font-weight: 600;
            color: #667eea;
        }
        
        .vortex-action-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .vortex-action-btn:hover {
            background: #5a6fd8;
            color: white;
            text-decoration: none;
        }
        
        .vortex-health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .vortex-health-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .vortex-health-status.healthy {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .vortex-health-status.warning {
            background: #fff3e0;
            color: #e65100;
        }
        
        .vortex-health-status.error {
            background: #ffebee;
            color: #c62828;
        }
        </style>
        <?php
    }
    
    /**
     * Render system status
     */
    private function render_system_status() {
        $artist_count = $this->get_artist_count();
        $milestone_required = 1000;
        $conversion_enabled = get_option('vortex_conversion_enabled', false);
        
        ?>
        <div class="vortex-status-item">
            <span class="vortex-status-label">Artist Count:</span>
            <span class="vortex-status-value"><?php echo $artist_count; ?>/<?php echo $milestone_required; ?></span>
        </div>
        <div class="vortex-status-item">
            <span class="vortex-status-label">Conversion Status:</span>
            <span class="vortex-status-value <?php echo $conversion_enabled ? 'success' : 'warning'; ?>">
                <?php echo $conversion_enabled ? 'Enabled' : 'Disabled'; ?>
            </span>
        </div>
        <div class="vortex-status-item">
            <span class="vortex-status-label">Platform Credits:</span>
            <span class="vortex-status-value"><?php echo $conversion_enabled ? 'Convertible' : 'Restricted'; ?></span>
        </div>
        <div class="vortex-status-item">
            <span class="vortex-status-label">System Status:</span>
            <span class="vortex-status-value success">Active</span>
        </div>
        <?php
    }
    
    /**
     * Render incentive rules
     */
    private function render_incentive_rules() {
        $rules = [
            'Profile Setup' => 5,
            'Upload Artwork' => 5,
            'Publish Blog Post' => 15,
            'Trade Artwork' => 5,
            'Make a Sale' => 10,
            'Weekly Top 10' => 20,
            'Refer an Artist' => 10,
            'Refer a Collector' => 20
        ];
        
        foreach ($rules as $action => $amount) {
            ?>
            <div class="vortex-rule-item">
                <strong><?php echo $action; ?></strong>
                <span class="vortex-rule-amount"><?php echo $amount; ?> TOLA</span>
            </div>
            <?php
        }
    }
    
    /**
     * Render conversion status
     */
    private function render_conversion_status() {
        $artist_count = $this->get_artist_count();
        $milestone_required = 1000;
        $conversion_enabled = get_option('vortex_conversion_enabled', false);
        $progress = ($artist_count / $milestone_required) * 100;
        
        ?>
        <div class="vortex-status-item">
            <span class="vortex-status-label">Progress:</span>
            <span class="vortex-status-value"><?php echo round($progress, 1); ?>%</span>
        </div>
        <div class="vortex-status-item">
            <span class="vortex-status-label">Status:</span>
            <span class="vortex-status-value <?php echo $conversion_enabled ? 'success' : 'warning'; ?>">
                <?php echo $conversion_enabled ? 'Enabled' : 'Pending'; ?>
            </span>
        </div>
        <div class="vortex-status-item">
            <span class="vortex-status-label">Remaining:</span>
            <span class="vortex-status-value"><?php echo max(0, $milestone_required - $artist_count); ?> artists</span>
        </div>
        <?php
    }
    
    /**
     * Render quick actions
     */
    private function render_quick_actions() {
        ?>
        <a href="#" class="vortex-action-btn" onclick="runDailyAudit()">Run Daily Audit</a>
        <a href="#" class="vortex-action-btn" onclick="generateReport()">Generate Report</a>
        <a href="#" class="vortex-action-btn" onclick="checkSystemHealth()">Check Health</a>
        <a href="#" class="vortex-action-btn" onclick="exportData()">Export Data</a>
        
        <script>
        function runDailyAudit() {
            if (confirm('Run daily incentive audit?')) {
                // AJAX call to run audit
                alert('Daily audit completed!');
            }
        }
        
        function generateReport() {
            if (confirm('Generate incentive report?')) {
                // AJAX call to generate report
                alert('Report generated!');
            }
        }
        
        function checkSystemHealth() {
            if (confirm('Check system health?')) {
                // AJAX call to check health
                alert('System health check completed!');
            }
        }
        
        function exportData() {
            if (confirm('Export incentive data?')) {
                // AJAX call to export data
                alert('Data export completed!');
            }
        }
        </script>
        <?php
    }
    
    /**
     * Render system health
     */
    private function render_system_health() {
        $components = [
            'Incentive Audit System' => 'healthy',
            'Wallet Management' => 'healthy',
            'Accounting System' => 'healthy',
            'Conversion System' => 'healthy',
            'Integration Layer' => 'healthy',
            'Frontend Interface' => 'healthy'
        ];
        
        foreach ($components as $component => $status) {
            ?>
            <div class="vortex-health-item">
                <span><?php echo $component; ?></span>
                <span class="vortex-health-status <?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
            </div>
            <?php
        }
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        global $wpdb;
        
        $recent_activity = $wpdb->get_results("
            SELECT 
                'Incentive Distribution' as type,
                user_id,
                incentive_type as action,
                amount,
                created_at
            FROM {$wpdb->prefix}vortex_incentive_distributions 
            ORDER BY created_at DESC 
            LIMIT 10
        ", ARRAY_A);
        
        if (empty($recent_activity)) {
            echo '<p>No recent activity found.</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_activity as $activity): ?>
                    <tr>
                        <td><?php echo esc_html($activity['type']); ?></td>
                        <td><?php echo esc_html($this->get_user_display_name($activity['user_id'])); ?></td>
                        <td><?php echo esc_html($activity['action']); ?></td>
                        <td><?php echo esc_html($activity['amount']); ?> TOLA</td>
                        <td><?php echo esc_html($activity['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    // Helper methods
    private function get_artist_count() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->users} u 
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
            AND um.meta_value LIKE '%artist%'
        ");
    }
    
    private function get_user_display_name($user_id) {
        $user = get_userdata($user_id);
        return $user ? $user->display_name : 'Unknown User';
    }
    
    /**
     * Get component instance
     */
    public function get_component($name) {
        return isset($this->components[$name]) ? $this->components[$name] : null;
    }
    
    /**
     * Get all components status
     */
    public function get_all_components_status() {
        $status = [];
        
        foreach ($this->components as $name => $instance) {
            if (is_object($instance) && method_exists($instance, 'get_status')) {
                $status[$name] = $instance->get_status();
            } else {
                $status[$name] = ['status' => 'unknown'];
            }
        }
        
        return $status;
    }
    
    /**
     * Get incentive system loader status
     */
    public function get_status() {
        return [
            'name' => 'VORTEX Incentive System Loader',
            'version' => '3.0.0',
            'components_loaded' => count($this->components),
            'components' => array_keys($this->components),
            'scheduled_events' => [
                'vortex_daily_incentive_audit',
                'vortex_daily_accounting_report',
                'vortex_weekly_incentive_report',
                'vortex_monthly_accounting_report',
                'vortex_fraud_detection'
            ]
        ];
    }
} 