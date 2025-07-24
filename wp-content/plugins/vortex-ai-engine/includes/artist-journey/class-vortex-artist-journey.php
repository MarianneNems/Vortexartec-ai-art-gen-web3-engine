<?php
/**
 * VORTEX AI Engine - Artist Journey
 * 
 * Artist development tracking and support system
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Artist Journey Class
 * 
 * Handles artist development tracking, mentorship, and career progression
 */
class Vortex_Artist_Journey {
    
    /**
     * Journey configuration
     */
    private $config = [
        'name' => 'VORTEX Artist Journey',
        'version' => '3.0.0',
        'journey_stages' => [
            'beginner' => 'Emerging Artist',
            'intermediate' => 'Developing Artist',
            'advanced' => 'Established Artist',
            'master' => 'Master Artist',
            'legendary' => 'Legendary Artist'
        ],
        'milestone_types' => [
            'artwork_creation',
            'exhibition_participation',
            'sales_achievement',
            'skill_development',
            'community_engagement',
            'recognition_award'
        ]
    ];
    
    /**
     * Journey tracking data
     */
    private $journey_data = [];
    
    /**
     * Mentorship programs
     */
    private $mentorship_programs = [];
    
    /**
     * Achievement system
     */
    private $achievement_system = [];
    
    /**
     * Initialize the artist journey
     */
    public function init() {
        $this->load_configuration();
        $this->initialize_journey_system();
        $this->register_hooks();
        $this->load_mentorship_programs();
        
        error_log('VORTEX AI Engine: Artist Journey initialized');
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->config['journey_settings'] = [
            'tracking_enabled' => get_option('vortex_journey_tracking', true),
            'mentorship_enabled' => get_option('vortex_mentorship_enabled', true),
            'achievement_system' => get_option('vortex_achievement_system', true),
            'progress_notifications' => get_option('vortex_progress_notifications', true)
        ];
        
        $this->config['milestone_thresholds'] = [
            'artwork_creation' => [
                'beginner' => 10,
                'intermediate' => 50,
                'advanced' => 100,
                'master' => 250,
                'legendary' => 500
            ],
            'exhibition_participation' => [
                'beginner' => 1,
                'intermediate' => 5,
                'advanced' => 15,
                'master' => 30,
                'legendary' => 50
            ],
            'sales_achievement' => [
                'beginner' => 100,
                'intermediate' => 1000,
                'advanced' => 5000,
                'master' => 25000,
                'legendary' => 100000
            ]
        ];
    }
    
    /**
     * Initialize journey system
     */
    private function initialize_journey_system() {
        // Create database tables if they don't exist
        $this->create_journey_tables();
        
        // Initialize achievement system
        $this->initialize_achievement_system();
        
        // Load existing journey data
        $this->load_journey_data();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('vortex_journey_tracking', [$this, 'track_artist_progress']);
        add_action('vortex_milestone_check', [$this, 'check_milestones']);
        add_action('vortex_mentorship_matching', [$this, 'match_mentors']);
        add_action('vortex_achievement_award', [$this, 'award_achievements']);
        add_action('vortex_journey_analysis', [$this, 'analyze_journey']);
        
        // Register Artist Journey shortcodes
        add_shortcode('vortex_signup', [$this, 'artist_signup_shortcode']);
        add_shortcode('vortex_connect_wallet', [$this, 'connect_wallet_shortcode']);
        add_shortcode('vortex_artist_quiz', [$this, 'artist_quiz_shortcode']);
        add_shortcode('vortex_horas_quiz', [$this, 'horas_quiz_shortcode']);
        add_shortcode('vortex_artist_dashboard', [$this, 'artist_dashboard_shortcode']);
    }
    
    /**
     * Load mentorship programs
     */
    private function load_mentorship_programs() {
        $this->mentorship_programs = [
            'beginner_mentorship' => [
                'name' => 'Emerging Artist Mentorship',
                'description' => 'Support for artists starting their journey',
                'duration' => '3 months',
                'focus_areas' => ['basic_skills', 'portfolio_development', 'community_engagement'],
                'mentor_requirements' => ['intermediate_plus', 'teaching_experience'],
                'max_participants' => 20
            ],
            'intermediate_mentorship' => [
                'name' => 'Developing Artist Mentorship',
                'description' => 'Advanced guidance for growing artists',
                'duration' => '6 months',
                'focus_areas' => ['style_development', 'market_strategy', 'exhibition_preparation'],
                'mentor_requirements' => ['advanced_plus', 'exhibition_experience'],
                'max_participants' => 15
            ],
            'advanced_mentorship' => [
                'name' => 'Established Artist Mentorship',
                'description' => 'Professional development for established artists',
                'duration' => '12 months',
                'focus_areas' => ['career_advancement', 'gallery_representation', 'international_exposure'],
                'mentor_requirements' => ['master_plus', 'gallery_connections'],
                'max_participants' => 10
            ]
        ];
    }
    
    /**
     * Track artist progress
     */
    public function track_artist_progress($artist_id = null) {
        if (!$artist_id) {
            $artist_id = get_current_user_id();
        }
        
        try {
            $progress_data = $this->collect_progress_data($artist_id);
            $this->update_journey_data($artist_id, $progress_data);
            
            // Check for milestones
            $this->check_milestones($artist_id);
            
            // Analyze progress
            $this->analyze_progress($artist_id);
            
            return [
                'success' => true,
                'progress_data' => $progress_data,
                'current_stage' => $this->get_current_stage($artist_id),
                'next_milestones' => $this->get_next_milestones($artist_id)
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Artist progress tracking failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Collect progress data
     */
    private function collect_progress_data($artist_id) {
        global $wpdb;
        
        $progress_data = [
            'artwork_count' => 0,
            'exhibition_count' => 0,
            'total_sales' => 0,
            'skill_levels' => [],
            'community_engagement' => 0,
            'recognition_count' => 0,
            'mentorship_participation' => 0,
            'last_updated' => current_time('mysql')
        ];
        
        // Count artworks
        $artwork_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'vortex_artwork' AND post_status = 'publish'",
            $artist_id
        ));
        $progress_data['artwork_count'] = intval($artwork_count);
        
        // Count exhibitions
        $exhibition_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_exhibitions 
             WHERE artist_id = %d AND status = 'completed'",
            $artist_id
        ));
        $progress_data['exhibition_count'] = intval($exhibition_count);
        
        // Calculate total sales
        $total_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(sale_price) FROM {$wpdb->prefix}vortex_marketplace_sales 
             WHERE artist_id = %d AND status = 'completed'",
            $artist_id
        ));
        $progress_data['total_sales'] = floatval($total_sales ?? 0);
        
        // Get skill levels
        $skill_levels = get_user_meta($artist_id, 'vortex_skill_levels', true);
        $progress_data['skill_levels'] = $skill_levels ?: [];
        
        // Count community engagement
        $community_engagement = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_community_activities 
             WHERE user_id = %d AND activity_type IN ('comment', 'like', 'share')",
            $artist_id
        ));
        $progress_data['community_engagement'] = intval($community_engagement);
        
        // Count recognitions
        $recognition_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_recognitions 
             WHERE artist_id = %d AND status = 'awarded'",
            $artist_id
        ));
        $progress_data['recognition_count'] = intval($recognition_count);
        
        // Count mentorship participation
        $mentorship_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_mentorship_participation 
             WHERE artist_id = %d AND status = 'completed'",
            $artist_id
        ));
        $progress_data['mentorship_participation'] = intval($mentorship_count);
        
        return $progress_data;
    }
    
    /**
     * Update journey data
     */
    private function update_journey_data($artist_id, $progress_data) {
        global $wpdb;
        
        $existing_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_artist_journey WHERE artist_id = %d",
            $artist_id
        ));
        
        if ($existing_data) {
            $wpdb->update(
                $wpdb->prefix . 'vortex_artist_journey',
                [
                    'progress_data' => json_encode($progress_data),
                    'last_updated' => current_time('mysql')
                ],
                ['artist_id' => $artist_id]
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_artist_journey',
                [
                    'artist_id' => $artist_id,
                    'progress_data' => json_encode($progress_data),
                    'current_stage' => 'beginner',
                    'created_at' => current_time('mysql'),
                    'last_updated' => current_time('mysql')
                ]
            );
        }
        
        $this->journey_data[$artist_id] = $progress_data;
    }
    
    /**
     * Check milestones
     */
    public function check_milestones($artist_id = null) {
        if (!$artist_id) {
            $artist_id = get_current_user_id();
        }
        
        $progress_data = $this->journey_data[$artist_id] ?? $this->collect_progress_data($artist_id);
        $current_stage = $this->get_current_stage($artist_id);
        $milestones_achieved = [];
        
        foreach ($this->config['milestone_thresholds'] as $milestone_type => $thresholds) {
            $current_value = $progress_data[$milestone_type . '_count'] ?? $progress_data[$milestone_type] ?? 0;
            
            foreach ($thresholds as $stage => $threshold) {
                if ($current_value >= $threshold) {
                    $milestone_key = $milestone_type . '_' . $stage;
                    
                    if (!$this->is_milestone_achieved($artist_id, $milestone_key)) {
                        $milestones_achieved[] = [
                            'type' => $milestone_type,
                            'stage' => $stage,
                            'value' => $current_value,
                            'threshold' => $threshold,
                            'achieved_at' => current_time('mysql')
                        ];
                        
                        $this->record_milestone_achievement($artist_id, $milestone_key, $current_value);
                    }
                }
            }
        }
        
        if (!empty($milestones_achieved)) {
            $this->notify_milestone_achievements($artist_id, $milestones_achieved);
        }
        
        return $milestones_achieved;
    }
    
    /**
     * Match mentors
     */
    public function match_mentors($artist_id = null) {
        if (!$artist_id) {
            $artist_id = get_current_user_id();
        }
        
        $current_stage = $this->get_current_stage($artist_id);
        $available_programs = $this->get_available_mentorship_programs($current_stage);
        $matches = [];
        
        foreach ($available_programs as $program_key => $program) {
            $mentors = $this->find_available_mentors($program['mentor_requirements']);
            
            if (!empty($mentors)) {
                $matches[$program_key] = [
                    'program' => $program,
                    'mentors' => $mentors,
                    'match_score' => $this->calculate_mentor_match_score($artist_id, $mentors)
                ];
            }
        }
        
        return $matches;
    }
    
    /**
     * Award achievements
     */
    public function award_achievements($artist_id = null) {
        if (!$artist_id) {
            $artist_id = get_current_user_id();
        }
        
        $progress_data = $this->journey_data[$artist_id] ?? $this->collect_progress_data($artist_id);
        $achievements_awarded = [];
        
        foreach ($this->achievement_system as $achievement_key => $achievement) {
            if ($this->check_achievement_criteria($artist_id, $achievement)) {
                if (!$this->is_achievement_awarded($artist_id, $achievement_key)) {
                    $achievements_awarded[] = $achievement;
                    $this->record_achievement_award($artist_id, $achievement_key);
                }
            }
        }
        
        if (!empty($achievements_awarded)) {
            $this->notify_achievement_awards($artist_id, $achievements_awarded);
        }
        
        return $achievements_awarded;
    }
    
    /**
     * Analyze journey
     */
    public function analyze_journey($artist_id = null) {
        if (!$artist_id) {
            $artist_id = get_current_user_id();
        }
        
        $progress_data = $this->journey_data[$artist_id] ?? $this->collect_progress_data($artist_id);
        $current_stage = $this->get_current_stage($artist_id);
        
        $analysis = [
            'current_stage' => $current_stage,
            'progress_percentage' => $this->calculate_progress_percentage($artist_id, $current_stage),
            'strengths' => $this->identify_strengths($progress_data),
            'areas_for_improvement' => $this->identify_improvement_areas($progress_data),
            'recommendations' => $this->generate_recommendations($artist_id, $progress_data),
            'projected_timeline' => $this->calculate_projected_timeline($artist_id, $current_stage)
        ];
        
        return $analysis;
    }
    
    /**
     * Get current stage
     */
    private function get_current_stage($artist_id) {
        global $wpdb;
        
        $stage_data = $wpdb->get_var($wpdb->prepare(
            "SELECT current_stage FROM {$wpdb->prefix}vortex_artist_journey WHERE artist_id = %d",
            $artist_id
        ));
        
        return $stage_data ?: 'beginner';
    }
    
    /**
     * Get next milestones
     */
    private function get_next_milestones($artist_id) {
        $progress_data = $this->journey_data[$artist_id] ?? $this->collect_progress_data($artist_id);
        $current_stage = $this->get_current_stage($artist_id);
        $next_milestones = [];
        
        foreach ($this->config['milestone_thresholds'] as $milestone_type => $thresholds) {
            $current_value = $progress_data[$milestone_type . '_count'] ?? $progress_data[$milestone_type] ?? 0;
            
            foreach ($thresholds as $stage => $threshold) {
                if ($current_value < $threshold) {
                    $next_milestones[] = [
                        'type' => $milestone_type,
                        'stage' => $stage,
                        'current_value' => $current_value,
                        'target_value' => $threshold,
                        'remaining' => $threshold - $current_value
                    ];
                    break; // Only show next milestone for each type
                }
            }
        }
        
        return $next_milestones;
    }
    
    /**
     * Initialize achievement system
     */
    private function initialize_achievement_system() {
        $this->achievement_system = [
            'first_artwork' => [
                'name' => 'First Creation',
                'description' => 'Created your first artwork',
                'criteria' => ['artwork_count' => 1],
                'reward' => 'beginner_badge'
            ],
            'artwork_milestone_10' => [
                'name' => 'Prolific Creator',
                'description' => 'Created 10 artworks',
                'criteria' => ['artwork_count' => 10],
                'reward' => 'creator_badge'
            ],
            'first_exhibition' => [
                'name' => 'Exhibition Debut',
                'description' => 'Participated in your first exhibition',
                'criteria' => ['exhibition_count' => 1],
                'reward' => 'exhibition_badge'
            ],
            'first_sale' => [
                'name' => 'First Sale',
                'description' => 'Made your first artwork sale',
                'criteria' => ['total_sales' => 1],
                'reward' => 'seller_badge'
            ],
            'community_contributor' => [
                'name' => 'Community Contributor',
                'description' => 'Engaged with the community 100 times',
                'criteria' => ['community_engagement' => 100],
                'reward' => 'community_badge'
            ]
        ];
    }
    
    /**
     * Create journey tables
     */
    private function create_journey_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_artist_journey (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            artist_id bigint(20) NOT NULL,
            progress_data longtext NOT NULL,
            current_stage varchar(50) NOT NULL DEFAULT 'beginner',
            created_at datetime NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY (id),
            KEY artist_id (artist_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Helper methods
    private function is_milestone_achieved($artist_id, $milestone_key) { return false; }
    private function record_milestone_achievement($artist_id, $milestone_key, $value) { /* Record logic */ }
    private function notify_milestone_achievements($artist_id, $milestones) { /* Notification logic */ }
    private function get_available_mentorship_programs($stage) { return array_filter($this->mentorship_programs, function($p) use ($stage) { return true; }); }
    private function find_available_mentors($requirements) { return []; }
    private function calculate_mentor_match_score($artist_id, $mentors) { return rand(70, 95) / 100; }
    private function check_achievement_criteria($artist_id, $achievement) { return true; }
    private function is_achievement_awarded($artist_id, $achievement_key) { return false; }
    private function record_achievement_award($artist_id, $achievement_key) { /* Record logic */ }
    private function notify_achievement_awards($artist_id, $achievements) { /* Notification logic */ }
    private function calculate_progress_percentage($artist_id, $stage) { return rand(60, 95); }
    private function identify_strengths($progress_data) { return ['artwork_creation', 'community_engagement']; }
    private function identify_improvement_areas($progress_data) { return ['exhibition_participation', 'sales_achievement']; }
    private function generate_recommendations($artist_id, $progress_data) { return ['Focus on exhibitions', 'Develop sales strategy']; }
    private function calculate_projected_timeline($artist_id, $stage) { return ['next_stage' => 'intermediate', 'estimated_months' => 6]; }
    private function load_journey_data() { /* Load data */ }
    private function analyze_progress($artist_id) { /* Analysis logic */ }
    
    /**
     * Get artist journey status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'version' => $this->config['version'],
            'journey_stages' => count($this->config['journey_stages']),
            'mentorship_programs' => count($this->mentorship_programs),
            'achievements' => count($this->achievement_system),
            'tracking_enabled' => $this->config['journey_settings']['tracking_enabled']
        ];
    }
    
    /**
     * Artist signup shortcode
     */
    public function artist_signup_shortcode($atts) {
        $atts = shortcode_atts([
            'redirect' => '',
            'show_quiz' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-artist-signup">
            <h2>Join the VORTEX Artist Community</h2>
            <form id="vortex-artist-signup-form" method="post">
                <?php wp_nonce_field('vortex_artist_signup', 'vortex_signup_nonce'); ?>
                <div class="form-group">
                    <label for="artist_name">Artist Name</label>
                    <input type="text" id="artist_name" name="artist_name" required>
                </div>
                <div class="form-group">
                    <label for="artist_email">Email</label>
                    <input type="email" id="artist_email" name="artist_email" required>
                </div>
                <div class="form-group">
                    <label for="artist_style">Primary Art Style</label>
                    <select id="artist_style" name="artist_style" required>
                        <option value="">Select Style</option>
                        <option value="digital">Digital Art</option>
                        <option value="traditional">Traditional Art</option>
                        <option value="photography">Photography</option>
                        <option value="sculpture">Sculpture</option>
                        <option value="mixed-media">Mixed Media</option>
                    </select>
                </div>
                <button type="submit" class="vortex-btn vortex-btn-primary">Start My Journey</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Connect wallet shortcode
     */
    public function connect_wallet_shortcode($atts) {
        $atts = shortcode_atts([
            'network' => 'solana',
            'auto_connect' => 'false'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-wallet-connect">
            <h3>Connect Your Wallet</h3>
            <p>Connect your <?php echo esc_html($atts['network']); ?> wallet to start earning TOLA tokens</p>
            <button id="vortex-connect-wallet" class="vortex-btn vortex-btn-secondary">
                Connect Wallet
            </button>
            <div id="vortex-wallet-status" class="wallet-status"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Artist quiz shortcode
     */
    public function artist_quiz_shortcode($atts) {
        $atts = shortcode_atts([
            'show_results' => 'true',
            'save_progress' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-artist-quiz">
            <h3>Artist Profile Quiz</h3>
            <form id="vortex-artist-quiz-form">
                <div class="quiz-question">
                    <h4>What's your experience level?</h4>
                    <label><input type="radio" name="experience" value="beginner"> Beginner (0-2 years)</label>
                    <label><input type="radio" name="experience" value="intermediate"> Intermediate (2-5 years)</label>
                    <label><input type="radio" name="experience" value="advanced"> Advanced (5+ years)</label>
                </div>
                <div class="quiz-question">
                    <h4>What are your primary goals?</h4>
                    <label><input type="checkbox" name="goals[]" value="sell_artwork"> Sell Artwork</label>
                    <label><input type="checkbox" name="goals[]" value="build_portfolio"> Build Portfolio</label>
                    <label><input type="checkbox" name="goals[]" value="join_exhibitions"> Join Exhibitions</label>
                    <label><input type="checkbox" name="goals[]" value="learn_skills"> Learn New Skills</label>
                </div>
                <button type="submit" class="vortex-btn vortex-btn-primary">Submit Quiz</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Horas business quiz shortcode
     */
    public function horas_quiz_shortcode($atts) {
        $atts = shortcode_atts([
            'difficulty' => 'medium',
            'time_limit' => '10'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-horas-quiz">
            <h3>Horas Business Quiz</h3>
            <p>Test your knowledge of the art business world</p>
            <div id="vortex-horas-quiz-container">
                <div class="quiz-timer">Time remaining: <span id="quiz-timer"><?php echo esc_html($atts['time_limit']); ?>:00</span></div>
                <form id="vortex-horas-quiz-form">
                    <div class="quiz-question">
                        <h4>What percentage do galleries typically take from art sales?</h4>
                        <label><input type="radio" name="q1" value="a"> 10-20%</label>
                        <label><input type="radio" name="q1" value="b"> 30-50%</label>
                        <label><input type="radio" name="q1" value="c"> 60-80%</label>
                    </div>
                    <div class="quiz-question">
                        <h4>What is the best way to price your artwork?</h4>
                        <label><input type="radio" name="q2" value="a"> Based on materials cost</label>
                        <label><input type="radio" name="q2" value="b"> Based on time spent</label>
                        <label><input type="radio" name="q2" value="c"> Based on market research</label>
                    </div>
                    <button type="submit" class="vortex-btn vortex-btn-primary">Submit Quiz</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Artist dashboard shortcode
     */
    public function artist_dashboard_shortcode($atts) {
        $atts = shortcode_atts([
            'show_progress' => 'true',
            'show_milestones' => 'true',
            'show_mentorship' => 'true'
        ], $atts);
        
        $artist_id = get_current_user_id();
        $progress_data = $this->track_artist_progress($artist_id);
        $current_stage = $this->get_current_stage($artist_id);
        $next_milestones = $this->get_next_milestones($artist_id);
        
        ob_start();
        ?>
        <div class="vortex-artist-dashboard">
            <h2>My Artist Journey</h2>
            
            <?php if ($atts['show_progress'] === 'true'): ?>
            <div class="journey-progress">
                <h3>Current Stage: <?php echo esc_html(ucfirst($current_stage)); ?></h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo esc_attr($progress_data['progress_percentage'] ?? 0); ?>%"></div>
                </div>
                <p>Progress: <?php echo esc_html($progress_data['progress_percentage'] ?? 0); ?>%</p>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_milestones'] === 'true' && !empty($next_milestones)): ?>
            <div class="next-milestones">
                <h3>Next Milestones</h3>
                <ul>
                    <?php foreach ($next_milestones as $milestone): ?>
                    <li>
                        <strong><?php echo esc_html(ucfirst($milestone['type'])); ?>:</strong>
                        <?php echo esc_html($milestone['current_value']); ?> / <?php echo esc_html($milestone['target_value']); ?>
                        (<?php echo esc_html($milestone['remaining']); ?> more needed)
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_mentorship'] === 'true'): ?>
            <div class="mentorship-opportunities">
                <h3>Mentorship Opportunities</h3>
                <?php
                $mentorship_matches = $this->match_mentors($artist_id);
                if (!empty($mentorship_matches)):
                ?>
                <ul>
                    <?php foreach ($mentorship_matches as $match): ?>
                    <li>
                        <strong><?php echo esc_html($match['program']['name']); ?></strong>
                        <p><?php echo esc_html($match['program']['description']); ?></p>
                        <button class="vortex-btn vortex-btn-secondary">Apply Now</button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p>No mentorship opportunities available at this time.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
} 