<?php
/**
 * VORTEX AI Engine - Real-Time Activity Monitor
 * 
 * Provides a real-time interface to monitor all VORTEX AI Engine activities
 * including AI agents, servers, algorithms, and system interactions.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Activity_Monitor {
    
    /**
     * Single instance of the monitor
     */
    private static $instance = null;
    
    /**
     * Activity logger instance
     */
    private $logger;
    
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
        $this->logger = Vortex_Activity_Logger::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_activity_monitor_menu'));
        add_action('wp_ajax_vortex_get_activity', array($this, 'ajax_get_activity'));
        add_action('wp_ajax_vortex_clear_activity', array($this, 'ajax_clear_activity'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add activity monitor menu
     */
    public function add_activity_monitor_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Activity Monitor',
            'Activity Monitor',
            'manage_options',
            'vortex-activity-monitor',
            array($this, 'render_activity_monitor_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'vortex-ai-engine_page_vortex-activity-monitor') {
            return;
        }
        
        wp_enqueue_script('vortex-activity-monitor', VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/js/activity-monitor.js', array('jquery'), VORTEX_AI_ENGINE_VERSION, true);
        wp_enqueue_style('vortex-activity-monitor', VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/css/activity-monitor.css', array(), VORTEX_AI_ENGINE_VERSION);
        
        wp_localize_script('vortex-activity-monitor', 'vortex_activity_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_activity_nonce'),
            'refresh_interval' => 2000 // 2 seconds
        ));
    }
    
    /**
     * Render activity monitor page
     */
    public function render_activity_monitor_page() {
        $stats = $this->logger->get_activity_stats();
        ?>
        <div class="wrap">
            <h1>🔍 VORTEX AI Engine - Real-Time Activity Monitor</h1>
            
            <!-- Statistics Dashboard -->
            <div class="vortex-stats-dashboard">
                <div class="stat-card">
                    <h3>Total Activities</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_entries']); ?></div>
                </div>
                <div class="stat-card error">
                    <h3>Recent Errors</h3>
                    <div class="stat-value"><?php echo $stats['recent_errors']; ?></div>
                </div>
                <div class="stat-card warning">
                    <h3>Recent Warnings</h3>
                    <div class="stat-value"><?php echo $stats['recent_warnings']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Log File Size</h3>
                    <div class="stat-value"><?php echo size_format($this->logger->get_log_file_size()); ?></div>
                </div>
            </div>
            
            <!-- Activity Type Filter -->
            <div class="vortex-activity-filters">
                <label for="activity-type-filter">Filter by Type:</label>
                <select id="activity-type-filter">
                    <option value="">All Types</option>
                    <option value="AI_AGENT">AI Agents</option>
                    <option value="SERVER">Servers</option>
                    <option value="ALGORITHM">Algorithms</option>
                    <option value="DATABASE">Database</option>
                    <option value="BLOCKCHAIN">Blockchain</option>
                    <option value="CLOUD">Cloud Services</option>
                    <option value="USER">User Activities</option>
                    <option value="SYSTEM">System</option>
                </select>
                
                <label for="activity-level-filter">Filter by Level:</label>
                <select id="activity-level-filter">
                    <option value="">All Levels</option>
                    <option value="INFO">Info</option>
                    <option value="SUCCESS">Success</option>
                    <option value="WARNING">Warning</option>
                    <option value="ERROR">Error</option>
                    <option value="DEBUG">Debug</option>
                </select>
                
                <button id="clear-activity-btn" class="button button-secondary">Clear Buffer</button>
                <button id="refresh-activity-btn" class="button button-primary">Refresh</button>
            </div>
            
            <!-- Real-Time Activity Feed -->
            <div class="vortex-activity-feed">
                <h2>🔄 Real-Time Activity Feed</h2>
                <div id="activity-feed" class="activity-container">
                    <div class="loading">Loading activities...</div>
                </div>
            </div>
            
            <!-- Activity Statistics -->
            <div class="vortex-activity-stats">
                <h2>📊 Activity Statistics</h2>
                
                <div class="stats-grid">
                    <div class="stats-section">
                        <h3>By Activity Type</h3>
                        <div class="stats-chart">
                            <?php foreach ($stats['by_type'] as $type => $count): ?>
                                <div class="stat-bar">
                                    <span class="stat-label"><?php echo $type; ?></span>
                                    <div class="stat-bar-container">
                                        <div class="stat-bar-fill" style="width: <?php echo ($count / max($stats['by_type'])) * 100; ?>%"></div>
                                    </div>
                                    <span class="stat-count"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="stats-section">
                        <h3>By Log Level</h3>
                        <div class="stats-chart">
                            <?php foreach ($stats['by_level'] as $level => $count): ?>
                                <div class="stat-bar">
                                    <span class="stat-label"><?php echo $level; ?></span>
                                    <div class="stat-bar-container">
                                        <div class="stat-bar-fill level-<?php echo strtolower($level); ?>" style="width: <?php echo ($count / max($stats['by_level'])) * 100; ?>%"></div>
                                    </div>
                                    <span class="stat-count"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize activity monitor
            VortexActivityMonitor.init();
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for getting activity
     */
    public function ajax_get_activity() {
        check_ajax_referer('vortex_activity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $type_filter = sanitize_text_field($_POST['type_filter'] ?? '');
        $level_filter = sanitize_text_field($_POST['level_filter'] ?? '');
        $limit = intval($_POST['limit'] ?? 50);
        
        $activities = $this->logger->get_recent_activity($limit);
        
        // Apply filters
        if ($type_filter) {
            $activities = array_filter($activities, function($activity) use ($type_filter) {
                return $activity['type'] === $type_filter;
            });
        }
        
        if ($level_filter) {
            $activities = array_filter($activities, function($activity) use ($level_filter) {
                return $activity['level'] === $level_filter;
            });
        }
        
        wp_send_json_success(array(
            'activities' => array_values($activities),
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * AJAX handler for clearing activity buffer
     */
    public function ajax_clear_activity() {
        check_ajax_referer('vortex_activity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $this->logger->clear_buffer();
        
        wp_send_json_success(array(
            'message' => 'Activity buffer cleared successfully'
        ));
    }
}

// Initialize the activity monitor
Vortex_Activity_Monitor::get_instance(); 