<?php
/**
 * VORTEX AI Engine - Artist Journey Dashboard
 * 
 * Real-time dashboard for monitoring artist journey from registration
 * to continuous activity with reinforcement learning loops and
 * recursive self-improvement metrics.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Artist_Journey_Dashboard {
    
    /**
     * Single instance of the dashboard
     */
    private static $instance = null;
    
    /**
     * Activity logger instance
     */
    private $logger;
    
    /**
     * Database manager instance
     */
    private $db_manager;
    
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
        $this->db_manager = Vortex_Artist_Journey_Database::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_journey_dashboard_menu'));
        add_action('wp_ajax_vortex_get_journey_stats', array($this, 'ajax_get_journey_stats'));
        add_action('wp_ajax_vortex_get_user_journey', array($this, 'ajax_get_user_journey'));
        add_action('wp_ajax_vortex_get_rl_metrics', array($this, 'ajax_get_rl_metrics'));
        add_action('wp_ajax_vortex_get_self_improvement', array($this, 'ajax_get_self_improvement'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add journey dashboard menu
     */
    public function add_journey_dashboard_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Artist Journey Dashboard',
            'Artist Journey',
            'manage_options',
            'vortex-artist-journey',
            array($this, 'render_journey_dashboard')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'vortex-ai-engine_page_vortex-artist-journey') {
            return;
        }
        
        wp_enqueue_script('vortex-journey-dashboard', VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/js/journey-dashboard.js', array('jquery', 'chart-js'), VORTEX_AI_ENGINE_VERSION, true);
        wp_enqueue_style('vortex-journey-dashboard', VORTEX_AI_ENGINE_PLUGIN_URL . 'admin/css/journey-dashboard.css', array(), VORTEX_AI_ENGINE_VERSION);
        
        wp_localize_script('vortex-journey-dashboard', 'vortex_journey_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_journey_nonce'),
            'refresh_interval' => 5000 // 5 seconds
        ));
    }
    
    /**
     * Render journey dashboard
     */
    public function render_journey_dashboard() {
        $stats = $this->db_manager->get_statistics();
        ?>
        <div class="wrap">
            <h1>🎨 VORTEX AI Engine - Artist Journey Dashboard</h1>
            
            <!-- Overview Statistics -->
            <div class="vortex-journey-overview">
                <div class="stat-card">
                    <h3>Total Artists</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Artists (30d)</h3>
                    <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Activities</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_activities']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Achievements</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_achievements']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Avg Engagement</h3>
                    <div class="stat-value"><?php echo number_format($stats['avg_engagement'], 2); ?></div>
                </div>
            </div>
            
            <!-- Real-Time Registration Log -->
            <div class="vortex-journey-section">
                <h2>📝 Real-Time Registration Log</h2>
                <div class="registration-feed">
                    <div id="registration-feed" class="feed-container">
                        <div class="loading">Loading recent registrations...</div>
                    </div>
                </div>
            </div>
            
            <!-- Continuous Activity Tracking -->
            <div class="vortex-journey-section">
                <h2>🔄 Continuous Activity Tracking</h2>
                <div class="activity-tracking">
                    <div class="activity-filters">
                        <label for="activity-type-filter">Filter by Type:</label>
                        <select id="activity-type-filter">
                            <option value="">All Activities</option>
                            <option value="artist_registration">Registrations</option>
                            <option value="artist_login">Logins</option>
                            <option value="artwork_creation">Artwork Creation</option>
                            <option value="ai_interaction">AI Interactions</option>
                            <option value="quiz_completion">Learning</option>
                            <option value="collaboration">Collaborations</option>
                        </select>
                        
                        <label for="time-range-filter">Time Range:</label>
                        <select id="time-range-filter">
                            <option value="1h">Last Hour</option>
                            <option value="24h" selected>Last 24 Hours</option>
                            <option value="7d">Last 7 Days</option>
                            <option value="30d">Last 30 Days</option>
                        </select>
                    </div>
                    
                    <div id="activity-chart" class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Reinforcement Learning Loops -->
            <div class="vortex-journey-section">
                <h2>🧠 Reinforcement Learning Loops</h2>
                <div class="rl-metrics">
                    <div class="rl-overview">
                        <div class="rl-stat">
                            <h4>Active RL Systems</h4>
                            <div id="active-rl-count" class="rl-value">Loading...</div>
                        </div>
                        <div class="rl-stat">
                            <h4>Total Patterns</h4>
                            <div id="total-patterns" class="rl-value">Loading...</div>
                        </div>
                        <div class="rl-stat">
                            <h4>Avg Reward</h4>
                            <div id="avg-reward" class="rl-value">Loading...</div>
                        </div>
                        <div class="rl-stat">
                            <h4>Policy Updates</h4>
                            <div id="policy-updates" class="rl-value">Loading...</div>
                        </div>
                    </div>
                    
                    <div id="rl-chart" class="chart-container">
                        <canvas id="rlChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recursive Self-Improvement -->
            <div class="vortex-journey-section">
                <h2>🔄 Recursive Self-Improvement</h2>
                <div class="self-improvement">
                    <div class="improvement-overview">
                        <div class="improvement-stat">
                            <h4>Improvements Applied</h4>
                            <div id="improvements-applied" class="improvement-value">Loading...</div>
                        </div>
                        <div class="improvement-stat">
                            <h4>Effectiveness Score</h4>
                            <div id="effectiveness-score" class="improvement-value">Loading...</div>
                        </div>
                        <div class="improvement-stat">
                            <h4>Users Affected</h4>
                            <div id="users-affected" class="improvement-value">Loading...</div>
                        </div>
                        <div class="improvement-stat">
                            <h4>Last Improvement</h4>
                            <div id="last-improvement" class="improvement-value">Loading...</div>
                        </div>
                    </div>
                    
                    <div id="improvement-chart" class="chart-container">
                        <canvas id="improvementChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Individual Artist Journey -->
            <div class="vortex-journey-section">
                <h2>👤 Individual Artist Journey</h2>
                <div class="artist-search">
                    <label for="artist-search">Search Artist:</label>
                    <input type="text" id="artist-search" placeholder="Enter username or email">
                    <button id="search-artist-btn" class="button button-primary">Search</button>
                </div>
                
                <div id="artist-journey-details" class="artist-details" style="display: none;">
                    <div class="artist-profile">
                        <h3 id="artist-name">Artist Name</h3>
                        <div class="profile-stats">
                            <div class="profile-stat">
                                <span class="stat-label">Journey Stage:</span>
                                <span id="journey-stage" class="stat-value">Loading...</span>
                            </div>
                            <div class="profile-stat">
                                <span class="stat-label">Skill Level:</span>
                                <span id="skill-level" class="stat-value">Loading...</span>
                            </div>
                            <div class="profile-stat">
                                <span class="stat-label">Engagement Score:</span>
                                <span id="engagement-score" class="stat-value">Loading...</span>
                            </div>
                            <div class="profile-stat">
                                <span class="stat-label">Total Activities:</span>
                                <span id="total-activities" class="stat-value">Loading...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="artist-activities">
                        <h4>Recent Activities</h4>
                        <div id="artist-activities-list" class="activities-list">
                            <div class="loading">Loading activities...</div>
                        </div>
                    </div>
                    
                    <div class="artist-achievements">
                        <h4>Achievements</h4>
                        <div id="artist-achievements-list" class="achievements-list">
                            <div class="loading">Loading achievements...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize journey dashboard
            VortexJourneyDashboard.init();
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for getting journey statistics
     */
    public function ajax_get_journey_stats() {
        check_ajax_referer('vortex_journey_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $stats = $this->db_manager->get_statistics();
        
        // Get recent registrations
        $recent_registrations = $this->get_recent_registrations(10);
        
        // Get activity data for chart
        $activity_data = $this->get_activity_data();
        
        wp_send_json_success(array(
            'stats' => $stats,
            'recent_registrations' => $recent_registrations,
            'activity_data' => $activity_data
        ));
    }
    
    /**
     * AJAX handler for getting user journey
     */
    public function ajax_get_user_journey() {
        check_ajax_referer('vortex_journey_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $user = $this->find_user($search_term);
        
        if (!$user) {
            wp_send_json_error('User not found');
        }
        
        $journey_data = $this->get_user_journey_data($user->ID);
        
        wp_send_json_success($journey_data);
    }
    
    /**
     * AJAX handler for getting RL metrics
     */
    public function ajax_get_rl_metrics() {
        check_ajax_referer('vortex_journey_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $rl_metrics = $this->get_rl_metrics();
        
        wp_send_json_success($rl_metrics);
    }
    
    /**
     * AJAX handler for getting self-improvement data
     */
    public function ajax_get_self_improvement() {
        check_ajax_referer('vortex_journey_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $improvement_data = $this->get_self_improvement_data();
        
        wp_send_json_success($improvement_data);
    }
    
    /**
     * Get recent registrations
     */
    private function get_recent_registrations($limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_artist_journey_profiles';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY journey_start_date DESC LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get activity data for charts
     */
    private function get_activity_data() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_artist_activities';
        
        // Get activity counts by type for last 7 days
        $results = $wpdb->get_results(
            "SELECT activity_type, COUNT(*) as count, DATE(timestamp) as date 
             FROM $table 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY activity_type, DATE(timestamp)
             ORDER BY date DESC, count DESC"
        );
        
        return $results;
    }
    
    /**
     * Find user by search term
     */
    private function find_user($search_term) {
        $user = get_user_by('login', $search_term);
        if (!$user) {
            $user = get_user_by('email', $search_term);
        }
        return $user;
    }
    
    /**
     * Get user journey data
     */
    private function get_user_journey_data($user_id) {
        $profile = $this->db_manager->get_journey_profile($user_id);
        $activities = $this->db_manager->get_user_activities($user_id, 20);
        $achievements = $this->db_manager->get_user_achievements($user_id);
        $rl_system = $this->db_manager->get_rl_system($user_id);
        
        return array(
            'profile' => $profile,
            'activities' => $activities,
            'achievements' => $achievements,
            'rl_system' => $rl_system
        );
    }
    
    /**
     * Get RL metrics
     */
    private function get_rl_metrics() {
        global $wpdb;
        
        $table_rl = $wpdb->prefix . 'vortex_rl_system';
        $table_patterns = $wpdb->prefix . 'vortex_rl_patterns';
        
        $metrics = array(
            'active_rl_count' => $wpdb->get_var("SELECT COUNT(*) FROM $table_rl"),
            'total_patterns' => $wpdb->get_var("SELECT COUNT(*) FROM $table_patterns"),
            'avg_reward' => $wpdb->get_var("SELECT AVG(reward) FROM $table_patterns"),
            'policy_updates' => $wpdb->get_var("SELECT COUNT(*) FROM $table_patterns WHERE policy_decision IS NOT NULL")
        );
        
        return $metrics;
    }
    
    /**
     * Get self-improvement data
     */
    private function get_self_improvement_data() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_self_improvement';
        
        $data = array(
            'improvements_applied' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'applied'"),
            'effectiveness_score' => $wpdb->get_var("SELECT AVG(effectiveness_score) FROM $table WHERE effectiveness_score IS NOT NULL"),
            'users_affected' => $wpdb->get_var("SELECT SUM(affected_users) FROM $table"),
            'last_improvement' => $wpdb->get_var("SELECT applied_at FROM $table WHERE status = 'applied' ORDER BY applied_at DESC LIMIT 1")
        );
        
        return $data;
    }
}

// Initialize the journey dashboard
Vortex_Artist_Journey_Dashboard::get_instance(); 