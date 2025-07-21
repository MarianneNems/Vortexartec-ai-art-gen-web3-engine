<?php
/**
 * VORTEX AI Engine - Artist Journey Database Manager
 * 
 * Database schema and management for artist journey tracking,
 * reinforcement learning loops, and recursive self-improvement.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Artist_Journey_Database {
    
    /**
     * Single instance of the database manager
     */
    private static $instance = null;
    
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
        add_action('init', array($this, 'create_tables'));
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Artist Journey Profiles Table
        $table_journey_profiles = $wpdb->prefix . 'vortex_artist_journey_profiles';
        $sql_journey_profiles = "CREATE TABLE $table_journey_profiles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            journey_start_date datetime NOT NULL,
            current_stage varchar(50) NOT NULL DEFAULT 'registration',
            total_activities int(11) NOT NULL DEFAULT 0,
            artworks_created int(11) NOT NULL DEFAULT 0,
            ai_interactions int(11) NOT NULL DEFAULT 0,
            learning_completed int(11) NOT NULL DEFAULT 0,
            engagement_score decimal(5,2) NOT NULL DEFAULT 0.00,
            skill_level varchar(50) NOT NULL DEFAULT 'beginner',
            preferred_styles text,
            goals text,
            last_activity datetime NOT NULL,
            session_count int(11) NOT NULL DEFAULT 0,
            total_session_time int(11) NOT NULL DEFAULT 0,
            achievement_count int(11) NOT NULL DEFAULT 0,
            collaboration_count int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY current_stage (current_stage),
            KEY skill_level (skill_level),
            KEY engagement_score (engagement_score)
        ) $charset_collate;";
        
        // Artist Activities Table
        $table_activities = $wpdb->prefix . 'vortex_artist_activities';
        $sql_activities = "CREATE TABLE $table_activities (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity_type varchar(100) NOT NULL,
            activity_data longtext,
            activity_metadata longtext,
            timestamp datetime NOT NULL,
            session_id varchar(100),
            ip_address varchar(45),
            user_agent text,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY timestamp (timestamp),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        // RL System Table
        $table_rl_system = $wpdb->prefix . 'vortex_rl_system';
        $sql_rl_system = "CREATE TABLE $table_rl_system (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            initial_state longtext,
            action_space longtext,
            reward_function longtext,
            learning_rate decimal(5,4) NOT NULL DEFAULT 0.0100,
            exploration_rate decimal(5,4) NOT NULL DEFAULT 0.1000,
            policy varchar(50) NOT NULL DEFAULT 'epsilon_greedy',
            q_values longtext,
            policy_weights longtext,
            total_rewards decimal(10,4) NOT NULL DEFAULT 0.0000,
            episode_count int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY policy (policy),
            KEY learning_rate (learning_rate)
        ) $charset_collate;";
        
        // RL Patterns Table
        $table_rl_patterns = $wpdb->prefix . 'vortex_rl_patterns';
        $sql_rl_patterns = "CREATE TABLE $table_rl_patterns (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(100) NOT NULL,
            action_data longtext,
            current_state longtext,
            next_state longtext,
            reward decimal(10,4) NOT NULL,
            q_value decimal(10,4),
            policy_decision varchar(50),
            exploration_used tinyint(1) NOT NULL DEFAULT 0,
            timestamp datetime NOT NULL,
            episode_id varchar(100),
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY timestamp (timestamp),
            KEY episode_id (episode_id)
        ) $charset_collate;";
        
        // Self-Improvement Table
        $table_self_improvement = $wpdb->prefix . 'vortex_self_improvement';
        $sql_self_improvement = "CREATE TABLE $table_self_improvement (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            improvement_type varchar(100) NOT NULL,
            target_metric varchar(100) NOT NULL,
            strategy varchar(100) NOT NULL,
            priority varchar(20) NOT NULL DEFAULT 'medium',
            status varchar(20) NOT NULL DEFAULT 'pending',
            applied_at datetime,
            effectiveness_score decimal(5,2),
            affected_users int(11) NOT NULL DEFAULT 0,
            improvement_data longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY improvement_type (improvement_type),
            KEY target_metric (target_metric),
            KEY status (status),
            KEY priority (priority)
        ) $charset_collate;";
        
        // Global Patterns Table
        $table_global_patterns = $wpdb->prefix . 'vortex_global_patterns';
        $sql_global_patterns = "CREATE TABLE $table_global_patterns (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pattern_type varchar(100) NOT NULL,
            pattern_data longtext,
            analysis_date date NOT NULL,
            user_count int(11) NOT NULL DEFAULT 0,
            confidence_score decimal(5,2),
            pattern_insights longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY pattern_type (pattern_type),
            KEY analysis_date (analysis_date),
            KEY confidence_score (confidence_score)
        ) $charset_collate;";
        
        // Achievements Table
        $table_achievements = $wpdb->prefix . 'vortex_achievements';
        $sql_achievements = "CREATE TABLE $table_achievements (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            achievement_type varchar(100) NOT NULL,
            achievement_name varchar(200) NOT NULL,
            achievement_description text,
            criteria_met longtext,
            points_awarded int(11) NOT NULL DEFAULT 0,
            unlocked_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY achievement_type (achievement_type),
            KEY unlocked_at (unlocked_at)
        ) $charset_collate;";
        
        // Learning Progress Table
        $table_learning_progress = $wpdb->prefix . 'vortex_learning_progress';
        $sql_learning_progress = "CREATE TABLE $table_learning_progress (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            learning_module varchar(100) NOT NULL,
            module_type varchar(50) NOT NULL,
            progress_percentage decimal(5,2) NOT NULL DEFAULT 0.00,
            completed_lessons int(11) NOT NULL DEFAULT 0,
            total_lessons int(11) NOT NULL DEFAULT 0,
            quiz_scores longtext,
            time_spent int(11) NOT NULL DEFAULT 0,
            last_accessed datetime,
            completed_at datetime,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_module (user_id, learning_module),
            KEY module_type (module_type),
            KEY progress_percentage (progress_percentage)
        ) $charset_collate;";
        
        // Collaboration Table
        $table_collaborations = $wpdb->prefix . 'vortex_collaborations';
        $sql_collaborations = "CREATE TABLE $table_collaborations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            collaboration_id varchar(100) NOT NULL,
            initiator_id bigint(20) NOT NULL,
            collaborator_id bigint(20) NOT NULL,
            collaboration_type varchar(50) NOT NULL,
            project_id bigint(20),
            status varchar(20) NOT NULL DEFAULT 'active',
            collaboration_data longtext,
            start_date datetime NOT NULL,
            end_date datetime,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY collaboration_id (collaboration_id),
            KEY initiator_id (initiator_id),
            KEY collaborator_id (collaborator_id),
            KEY collaboration_type (collaboration_type),
            KEY status (status)
        ) $charset_collate;";
        
        // Execute table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_journey_profiles);
        dbDelta($sql_activities);
        dbDelta($sql_rl_system);
        dbDelta($sql_rl_patterns);
        dbDelta($sql_self_improvement);
        dbDelta($sql_global_patterns);
        dbDelta($sql_achievements);
        dbDelta($sql_learning_progress);
        dbDelta($sql_collaborations);
    }
    
    /**
     * Insert journey profile
     */
    public function insert_journey_profile($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_artist_journey_profiles';
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Update journey metrics
     */
    public function update_journey_metrics($user_id, $metrics) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_artist_journey_profiles';
        
        $metrics['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $metrics, array('user_id' => $user_id));
    }
    
    /**
     * Insert activity
     */
    public function insert_activity($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_artist_activities';
        
        $data['created_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Insert RL system
     */
    public function insert_rl_system($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_rl_system';
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Insert RL pattern
     */
    public function insert_rl_pattern($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_rl_patterns';
        
        $data['created_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Insert self-improvement record
     */
    public function insert_self_improvement($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_self_improvement';
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Insert global pattern
     */
    public function insert_global_pattern($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_global_patterns';
        
        $data['created_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Insert achievement
     */
    public function insert_achievement($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_achievements';
        
        $data['created_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Insert learning progress
     */
    public function insert_learning_progress($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_learning_progress';
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Insert collaboration
     */
    public function insert_collaboration($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_collaborations';
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Get user journey profile
     */
    public function get_journey_profile($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_artist_journey_profiles';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Get user activities
     */
    public function get_user_activities($user_id, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_artist_activities';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        ));
    }
    
    /**
     * Get RL system for user
     */
    public function get_rl_system($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_rl_system';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Get RL patterns for user
     */
    public function get_rl_patterns($user_id, $limit = 100) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_rl_patterns';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY timestamp DESC LIMIT %d",
            $user_id, $limit
        ));
    }
    
    /**
     * Get user achievements
     */
    public function get_user_achievements($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_achievements';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY unlocked_at DESC",
            $user_id
        ));
    }
    
    /**
     * Get learning progress for user
     */
    public function get_learning_progress($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_learning_progress';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY updated_at DESC",
            $user_id
        ));
    }
    
    /**
     * Get user collaborations
     */
    public function get_user_collaborations($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_collaborations';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE initiator_id = %d OR collaborator_id = %d ORDER BY created_at DESC",
            $user_id, $user_id
        ));
    }
    
    /**
     * Get global patterns
     */
    public function get_global_patterns($pattern_type = null, $limit = 100) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_global_patterns';
        
        $where = '';
        if ($pattern_type) {
            $where = $wpdb->prepare("WHERE pattern_type = %s", $pattern_type);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table $where ORDER BY analysis_date DESC LIMIT $limit"
        );
    }
    
    /**
     * Get self-improvement records
     */
    public function get_self_improvements($status = null, $limit = 100) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_self_improvement';
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $limit"
        );
    }
    
    /**
     * Update RL system
     */
    public function update_rl_system($user_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_rl_system';
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $data, array('user_id' => $user_id));
    }
    
    /**
     * Update learning progress
     */
    public function update_learning_progress($user_id, $module, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_learning_progress';
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $data, array(
            'user_id' => $user_id,
            'learning_module' => $module
        ));
    }
    
    /**
     * Update self-improvement record
     */
    public function update_self_improvement($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_self_improvement';
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $data, array('id' => $id));
    }
    
    /**
     * Get statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total users
        $table_profiles = $wpdb->prefix . 'vortex_artist_journey_profiles';
        $stats['total_users'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_profiles");
        
        // Active users (last 30 days)
        $stats['active_users'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_profiles WHERE last_activity >= %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Total activities
        $table_activities = $wpdb->prefix . 'vortex_artist_activities';
        $stats['total_activities'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_activities");
        
        // Total achievements
        $table_achievements = $wpdb->prefix . 'vortex_achievements';
        $stats['total_achievements'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_achievements");
        
        // Average engagement score
        $stats['avg_engagement'] = $wpdb->get_var("SELECT AVG(engagement_score) FROM $table_profiles");
        
        return $stats;
    }
}

// Initialize the database manager
Vortex_Artist_Journey_Database::get_instance(); 