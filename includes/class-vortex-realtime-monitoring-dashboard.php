<?php
/**
 * VORTEX AI ENGINE - REALTIME MONITORING DASHBOARD
 * 
 * Real-time monitoring dashboard with:
 * - Live GitHub sync status
 * - Recursive self-improvement tracking
 * - Deep learning progress monitoring
 * - Performance metrics visualization
 * - Debug log streaming
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Realtime_Monitoring_Dashboard {
    
    private static $instance = null;
    private $github_integration;
    private $dashboard_data = array();
    private $websocket_enabled = false;
    
    public function __construct() {
        $this->github_integration = Vortex_GitHub_Realtime_Integration::get_instance();
        $this->setup_hooks();
        $this->init_dashboard();
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_vortex_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_vortex_trigger_sync', array($this, 'ajax_trigger_sync'));
        add_action('wp_ajax_vortex_trigger_improvement', array($this, 'ajax_trigger_improvement'));
        add_action('wp_ajax_vortex_trigger_learning', array($this, 'ajax_trigger_learning'));
        add_action('wp_ajax_vortex_clear_logs', array($this, 'ajax_clear_logs'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Realtime Monitoring',
            'Realtime Monitoring',
            'manage_options',
            'vortex-realtime-monitoring',
            array($this, 'render_dashboard_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('vortex-ai-engine_page_vortex-realtime-monitoring' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'vortex-realtime-dashboard',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/js/realtime-dashboard.js',
            array('jquery', 'wp-util'),
            VORTEX_AI_ENGINE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vortex-realtime-dashboard',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/css/realtime-dashboard.css',
            array(),
            VORTEX_AI_ENGINE_VERSION
        );
        
        wp_localize_script('vortex-realtime-dashboard', 'vortexRealtime', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_realtime_nonce'),
            'updateInterval' => 5000 // 5 seconds
        ));
    }
    
    /**
     * Initialize dashboard
     */
    private function init_dashboard() {
        $this->dashboard_data = array(
            'system_status' => $this->github_integration->get_system_status(),
            'debug_log' => $this->github_integration->get_debug_log(50),
            'performance_metrics' => $this->github_integration->get_performance_metrics(),
            'learning_data' => $this->github_integration->get_learning_data()
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap vortex-realtime-dashboard">
            <h1>🚀 VORTEX AI ENGINE - REALTIME MONITORING</h1>
            
            <div class="vortex-dashboard-grid">
                <!-- System Status -->
                <div class="vortex-dashboard-card">
                    <h2>🔄 System Status</h2>
                    <div id="system-status" class="vortex-status-grid">
                        <?php $this->render_system_status(); ?>
                    </div>
                </div>
                
                <!-- GitHub Integration -->
                <div class="vortex-dashboard-card">
                    <h2>📡 GitHub Integration</h2>
                    <div id="github-status" class="vortex-github-status">
                        <?php $this->render_github_status(); ?>
                    </div>
                    <div class="vortex-actions">
                        <button id="trigger-sync" class="button button-primary">🔄 Trigger Sync</button>
                        <button id="trigger-improvement" class="button button-secondary">⚡ Trigger Improvement</button>
                        <button id="trigger-learning" class="button button-secondary">🧠 Trigger Learning</button>
                    </div>
                </div>
                
                <!-- Performance Metrics -->
                <div class="vortex-dashboard-card">
                    <h2>📊 Performance Metrics</h2>
                    <div id="performance-metrics" class="vortex-metrics-grid">
                        <?php $this->render_performance_metrics(); ?>
                    </div>
                </div>
                
                <!-- Learning Progress -->
                <div class="vortex-dashboard-card">
                    <h2>🧠 Learning Progress</h2>
                    <div id="learning-progress" class="vortex-learning-progress">
                        <?php $this->render_learning_progress(); ?>
                    </div>
                </div>
            </div>
            
            <!-- Real-time Debug Log -->
            <div class="vortex-dashboard-card full-width">
                <h2>📝 Real-time Debug Log</h2>
                <div class="vortex-log-controls">
                    <button id="clear-logs" class="button button-secondary">🗑️ Clear Logs</button>
                    <label>
                        <input type="checkbox" id="auto-scroll" checked> Auto-scroll
                    </label>
                </div>
                <div id="debug-log" class="vortex-debug-log">
                    <?php $this->render_debug_log(); ?>
                </div>
            </div>
            
            <!-- Recursive Improvement Cycle -->
            <div class="vortex-dashboard-card full-width">
                <h2>🔄 Recursive Self-Improvement Cycle</h2>
                <div id="improvement-cycle" class="vortex-improvement-cycle">
                    <?php $this->render_improvement_cycle(); ?>
                </div>
            </div>
        </div>
        
        <script>
            // Initialize real-time updates
            jQuery(document).ready(function($) {
                vortexRealtimeDashboard.init();
            });
        </script>
        <?php
    }
    
    /**
     * Render system status
     */
    private function render_system_status() {
        $status = $this->dashboard_data['system_status'];
        ?>
        <div class="status-item">
            <span class="status-label">Monitoring:</span>
            <span class="status-value <?php echo $status['monitoring_active'] ? 'active' : 'inactive'; ?>">
                <?php echo $status['monitoring_active'] ? '🟢 Active' : '🔴 Inactive'; ?>
            </span>
        </div>
        <div class="status-item">
            <span class="status-label">Recursive Loop:</span>
            <span class="status-value <?php echo $status['recursive_loop_active'] ? 'active' : 'inactive'; ?>">
                <?php echo $status['recursive_loop_active'] ? '🟢 Active' : '🔴 Inactive'; ?>
            </span>
        </div>
        <div class="status-item">
            <span class="status-label">Deep Learning:</span>
            <span class="status-value <?php echo $status['deep_learning_active'] ? 'active' : 'inactive'; ?>">
                <?php echo $status['deep_learning_active'] ? '🟢 Active' : '🔴 Inactive'; ?>
            </span>
        </div>
        <div class="status-item">
            <span class="status-label">Memory Usage:</span>
            <span class="status-value">
                <?php echo $this->format_bytes($status['memory_usage']); ?>
            </span>
        </div>
        <div class="status-item">
            <span class="status-label">Peak Memory:</span>
            <span class="status-value">
                <?php echo $this->format_bytes($status['peak_memory']); ?>
            </span>
        </div>
        <div class="status-item">
            <span class="status-label">Uptime:</span>
            <span class="status-value">
                <?php echo $this->format_uptime($status['uptime']); ?>
            </span>
        </div>
        <?php
    }
    
    /**
     * Render GitHub status
     */
    private function render_github_status() {
        $status = $this->dashboard_data['system_status'];
        $last_sync = $status['last_sync_time'];
        ?>
        <div class="github-status-item">
            <span class="status-label">Last Sync:</span>
            <span class="status-value">
                <?php echo $last_sync ? date('Y-m-d H:i:s', $last_sync) : 'Never'; ?>
            </span>
        </div>
        <div class="github-status-item">
            <span class="status-label">Improvement Cycle:</span>
            <span class="status-value">
                #<?php echo $status['improvement_cycle']; ?>
            </span>
        </div>
        <div class="github-status-item">
            <span class="status-label">Repository:</span>
            <span class="status-value">
                mariannenems/vortexartec-ai-marketplace
            </span>
        </div>
        <div class="github-status-item">
            <span class="status-label">Branch:</span>
            <span class="status-value">
                main
            </span>
        </div>
        <?php
    }
    
    /**
     * Render performance metrics
     */
    private function render_performance_metrics() {
        $metrics = $this->dashboard_data['performance_metrics'];
        ?>
        <div class="metrics-grid">
            <?php foreach ($metrics as $operation => $data): ?>
                <?php if (!empty($data)): ?>
                    <?php
                    $latest = end($data);
                    $avg_time = array_sum(array_column($data, 'execution_time')) / count($data);
                    ?>
                    <div class="metric-item">
                        <span class="metric-label"><?php echo ucfirst(str_replace('_', ' ', $operation)); ?>:</span>
                        <span class="metric-value">
                            <?php echo number_format($latest['execution_time'], 3); ?>s
                        </span>
                        <span class="metric-avg">
                            (avg: <?php echo number_format($avg_time, 3); ?>s)
                        </span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render learning progress
     */
    private function render_learning_progress() {
        $learning_data = $this->dashboard_data['learning_data'];
        ?>
        <div class="learning-progress-grid">
            <div class="progress-item">
                <span class="progress-label">Patterns Learned:</span>
                <span class="progress-value">
                    <?php echo count($learning_data['patterns'] ?? array()); ?>
                </span>
            </div>
            <div class="progress-item">
                <span class="progress-label">Optimizations Applied:</span>
                <span class="progress-value">
                    <?php echo count($learning_data['optimizations'] ?? array()); ?>
                </span>
            </div>
            <div class="progress-item">
                <span class="progress-label">Error Patterns:</span>
                <span class="progress-value">
                    <?php echo count($learning_data['error_patterns'] ?? array()); ?>
                </span>
            </div>
            <div class="progress-item">
                <span class="progress-label">Success Metrics:</span>
                <span class="progress-value">
                    <?php echo count($learning_data['success_metrics'] ?? array()); ?>
                </span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render debug log
     */
    private function render_debug_log() {
        $logs = $this->dashboard_data['debug_log'];
        ?>
        <div class="log-container">
            <?php foreach (array_reverse($logs) as $log): ?>
                <div class="log-entry log-level-<?php echo strtolower($log['level']); ?>">
                    <span class="log-timestamp"><?php echo $log['timestamp']; ?></span>
                    <span class="log-level">[<?php echo $log['level']; ?>]</span>
                    <span class="log-message"><?php echo esc_html($log['message']); ?></span>
                    <span class="log-memory"><?php echo $this->format_bytes($log['memory_usage']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render improvement cycle
     */
    private function render_improvement_cycle() {
        $status = $this->dashboard_data['system_status'];
        ?>
        <div class="improvement-cycle-info">
            <div class="cycle-item">
                <span class="cycle-label">Current Cycle:</span>
                <span class="cycle-value">#<?php echo $status['improvement_cycle']; ?></span>
            </div>
            <div class="cycle-item">
                <span class="cycle-label">Status:</span>
                <span class="cycle-value <?php echo $status['recursive_loop_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $status['recursive_loop_active'] ? '🔄 Running' : '⏸️ Paused'; ?>
                </span>
            </div>
            <div class="cycle-item">
                <span class="cycle-label">Next Improvement:</span>
                <span class="cycle-value" id="next-improvement">
                    Calculating...
                </span>
            </div>
        </div>
        <div class="improvement-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="improvement-progress" style="width: 0%"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for getting dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('vortex_realtime_nonce', 'nonce');
        
        $this->init_dashboard();
        
        wp_send_json_success($this->dashboard_data);
    }
    
    /**
     * AJAX handler for triggering sync
     */
    public function ajax_trigger_sync() {
        check_ajax_referer('vortex_realtime_nonce', 'nonce');
        
        try {
            $this->github_integration->perform_sync();
            wp_send_json_success(array('message' => 'Sync triggered successfully'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Sync failed: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for triggering improvement
     */
    public function ajax_trigger_improvement() {
        check_ajax_referer('vortex_realtime_nonce', 'nonce');
        
        try {
            $this->github_integration->recursive_improvement_cycle();
            wp_send_json_success(array('message' => 'Improvement cycle triggered successfully'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Improvement failed: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for triggering learning
     */
    public function ajax_trigger_learning() {
        check_ajax_referer('vortex_realtime_nonce', 'nonce');
        
        try {
            $this->github_integration->deep_learning_cycle();
            wp_send_json_success(array('message' => 'Learning cycle triggered successfully'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Learning failed: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for clearing logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('vortex_realtime_nonce', 'nonce');
        
        update_option('vortex_debug_log', array());
        update_option('vortex_performance_metrics', array());
        
        wp_send_json_success(array('message' => 'Logs cleared successfully'));
    }
    
    /**
     * Format bytes to human readable format
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Format uptime
     */
    private function format_uptime($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}

// Initialize the dashboard
Vortex_Realtime_Monitoring_Dashboard::get_instance(); 