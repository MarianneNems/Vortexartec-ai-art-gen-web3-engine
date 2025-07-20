<?php
/**
 * VORTEX AI Engine - CLOE Agent
 * 
 * Market analysis and collector matching AI agent
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CLOE Agent Class
 * 
 * Handles market analysis, trend prediction, and collector-artist matching
 */
class Vortex_Cloe_Agent {
    
    /**
     * Agent configuration
     */
    private $config = [
        'name' => 'CLOE',
        'type' => 'CPU',
        'capabilities' => ['market_analysis', 'trend_prediction', 'collector_matching', 'price_optimization'],
        'analysis_interval' => 3600, // 1 hour
        'market_data_sources' => [],
        'matching_algorithm' => 'neural_network'
    ];
    
    /**
     * Market data cache
     */
    private $market_cache = [];
    
    /**
     * Collector profiles
     */
    private $collector_profiles = [];
    
    /**
     * Artist profiles
     */
    private $artist_profiles = [];
    
    /**
     * Initialize the CLOE agent
     */
    public function init() {
        $this->load_configuration();
        $this->register_hooks();
        $this->initialize_market_analysis();
        
        error_log('VORTEX AI Engine: CLOE Agent initialized');
    }
    
    /**
     * Load agent configuration
     */
    private function load_configuration() {
        $this->config['market_data_sources'] = [
            'opensea' => get_option('vortex_opensea_api_key', ''),
            'rarible' => get_option('vortex_rarible_api_key', ''),
            'foundation' => get_option('vortex_foundation_api_key', ''),
            'superrare' => get_option('vortex_superrare_api_key', ''),
            'internal' => get_option('vortex_internal_market_data', true)
        ];
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_market_analysis', [$this, 'handle_market_analysis']);
        add_action('wp_ajax_vortex_collector_match', [$this, 'handle_collector_match']);
        add_action('vortex_market_analysis_cron', [$this, 'run_market_analysis']);
        add_action('vortex_trend_prediction_cron', [$this, 'predict_trends']);
        add_action('vortex_collector_matching_cron', [$this, 'update_collector_matches']);
    }
    
    /**
     * Initialize market analysis
     */
    private function initialize_market_analysis() {
        // Schedule regular market analysis
        if (!wp_next_scheduled('vortex_market_analysis_cron')) {
            wp_schedule_event(time(), 'hourly', 'vortex_market_analysis_cron');
        }
        
        // Schedule trend prediction
        if (!wp_next_scheduled('vortex_trend_prediction_cron')) {
            wp_schedule_event(time(), 'daily', 'vortex_trend_prediction_cron');
        }
        
        // Schedule collector matching
        if (!wp_next_scheduled('vortex_collector_matching_cron')) {
            wp_schedule_event(time(), 'twicedaily', 'vortex_collector_matching_cron');
        }
    }
    
    /**
     * Handle market analysis request
     */
    public function handle_market_analysis() {
        check_ajax_referer('vortex_analysis_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        $timeframe = sanitize_text_field($_POST['timeframe'] ?? '24h');
        
        $analysis = $this->analyze_market($category, $timeframe);
        
        if ($analysis['success']) {
            wp_send_json_success($analysis);
        } else {
            wp_send_json_error($analysis);
        }
    }
    
    /**
     * Handle collector matching request
     */
    public function handle_collector_match() {
        check_ajax_referer('vortex_matching_nonce', 'nonce');
        
        $collector_id = intval($_POST['collector_id'] ?? 0);
        $artist_id = intval($_POST['artist_id'] ?? 0);
        
        if (!$collector_id || !$artist_id) {
            wp_send_json_error(['message' => 'Collector and artist IDs are required']);
        }
        
        $match_score = $this->calculate_match_score($collector_id, $artist_id);
        
        wp_send_json_success([
            'match_score' => $match_score,
            'recommendations' => $this->get_match_recommendations($collector_id, $artist_id)
        ]);
    }
    
    /**
     * Analyze market data
     */
    public function analyze_market($category = 'all', $timeframe = '24h') {
        try {
            $market_data = $this->fetch_market_data($category, $timeframe);
            $trends = $this->identify_trends($market_data);
            $predictions = $this->generate_predictions($trends);
            
            return [
                'success' => true,
                'market_data' => $market_data,
                'trends' => $trends,
                'predictions' => $predictions,
                'timestamp' => current_time('mysql')
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: CLOE market analysis failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Fetch market data from various sources
     */
    private function fetch_market_data($category, $timeframe) {
        $data = [
            'sales_volume' => 0,
            'transaction_count' => 0,
            'average_price' => 0,
            'top_collections' => [],
            'trending_artists' => [],
            'price_changes' => []
        ];
        
        // Fetch from OpenSea
        if (!empty($this->config['market_data_sources']['opensea'])) {
            $opensea_data = $this->fetch_opensea_data($category, $timeframe);
            $data = array_merge($data, $opensea_data);
        }
        
        // Fetch from internal marketplace
        if ($this->config['market_data_sources']['internal']) {
            $internal_data = $this->fetch_internal_market_data($category, $timeframe);
            $data = array_merge($data, $internal_data);
        }
        
        // Cache the data
        $this->market_cache[$category . '_' . $timeframe] = [
            'data' => $data,
            'timestamp' => time()
        ];
        
        return $data;
    }
    
    /**
     * Fetch data from OpenSea API
     */
    private function fetch_opensea_data($category, $timeframe) {
        $api_key = $this->config['market_data_sources']['opensea'];
        $endpoint = 'https://api.opensea.io/api/v1/stats/overall';
        
        $response = wp_remote_get($endpoint, [
            'headers' => [
                'X-API-KEY' => $api_key
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return [
            'sales_volume' => $data['stats']['total_volume'] ?? 0,
            'transaction_count' => $data['stats']['total_sales'] ?? 0,
            'average_price' => $data['stats']['average_price'] ?? 0
        ];
    }
    
    /**
     * Fetch internal marketplace data
     */
    private function fetch_internal_market_data($category, $timeframe) {
        global $wpdb;
        
        $time_condition = '';
        switch ($timeframe) {
            case '24h':
                $time_condition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)';
                break;
            case '7d':
                $time_condition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case '30d':
                $time_condition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
        }
        
        $category_condition = $category !== 'all' ? "AND category = '$category'" : '';
        
        // Get sales data
        $sales_query = "
            SELECT 
                SUM(sale_price) as total_volume,
                COUNT(*) as total_sales,
                AVG(sale_price) as average_price
            FROM {$wpdb->prefix}vortex_marketplace_sales 
            WHERE status = 'completed' 
            $time_condition 
            $category_condition
        ";
        
        $sales_data = $wpdb->get_row($sales_query, ARRAY_A);
        
        // Get trending artists
        $artists_query = "
            SELECT 
                artist_id,
                COUNT(*) as sales_count,
                SUM(sale_price) as total_volume
            FROM {$wpdb->prefix}vortex_marketplace_sales 
            WHERE status = 'completed' 
            $time_condition 
            $category_condition
            GROUP BY artist_id 
            ORDER BY total_volume DESC 
            LIMIT 10
        ";
        
        $trending_artists = $wpdb->get_results($artists_query, ARRAY_A);
        
        return [
            'sales_volume' => $sales_data->total_volume ?? 0,
            'transaction_count' => $sales_data->total_sales ?? 0,
            'average_price' => $sales_data->average_price ?? 0,
            'trending_artists' => $trending_artists
        ];
    }
    
    /**
     * Identify market trends
     */
    private function identify_trends($market_data) {
        $trends = [
            'volume_trend' => 'stable',
            'price_trend' => 'stable',
            'popular_categories' => [],
            'emerging_artists' => [],
            'market_sentiment' => 'neutral'
        ];
        
        // Analyze volume trend
        if ($market_data['sales_volume'] > 1000000) {
            $trends['volume_trend'] = 'increasing';
        } elseif ($market_data['sales_volume'] < 100000) {
            $trends['volume_trend'] = 'decreasing';
        }
        
        // Analyze price trend
        if ($market_data['average_price'] > 1000) {
            $trends['price_trend'] = 'increasing';
        } elseif ($market_data['average_price'] < 100) {
            $trends['price_trend'] = 'decreasing';
        }
        
        // Analyze market sentiment
        if ($trends['volume_trend'] === 'increasing' && $trends['price_trend'] === 'increasing') {
            $trends['market_sentiment'] = 'bullish';
        } elseif ($trends['volume_trend'] === 'decreasing' && $trends['price_trend'] === 'decreasing') {
            $trends['market_sentiment'] = 'bearish';
        }
        
        return $trends;
    }
    
    /**
     * Generate market predictions
     */
    private function generate_predictions($trends) {
        $predictions = [
            'short_term' => [],
            'medium_term' => [],
            'long_term' => []
        ];
        
        // Short-term predictions (1-7 days)
        if ($trends['market_sentiment'] === 'bullish') {
            $predictions['short_term'][] = 'Expected increase in trading volume';
            $predictions['short_term'][] = 'Rising average sale prices';
        } elseif ($trends['market_sentiment'] === 'bearish') {
            $predictions['short_term'][] = 'Potential decrease in trading activity';
            $predictions['short_term'][] = 'Stabilizing price levels expected';
        }
        
        // Medium-term predictions (1-4 weeks)
        $predictions['medium_term'][] = 'Seasonal market patterns may emerge';
        $predictions['medium_term'][] = 'New artist discovery opportunities';
        
        // Long-term predictions (1-6 months)
        $predictions['long_term'][] = 'Market maturation and stabilization';
        $predictions['long_term'][] = 'Increased institutional adoption';
        
        return $predictions;
    }
    
    /**
     * Calculate match score between collector and artist
     */
    public function calculate_match_score($collector_id, $artist_id) {
        $collector_profile = $this->get_collector_profile($collector_id);
        $artist_profile = $this->get_artist_profile($artist_id);
        
        if (!$collector_profile || !$artist_profile) {
            return 0;
        }
        
        $score = 0;
        
        // Style preference matching (40% weight)
        $style_match = $this->calculate_style_match($collector_profile, $artist_profile);
        $score += $style_match * 0.4;
        
        // Price range matching (30% weight)
        $price_match = $this->calculate_price_match($collector_profile, $artist_profile);
        $score += $price_match * 0.3;
        
        // Category preference matching (20% weight)
        $category_match = $this->calculate_category_match($collector_profile, $artist_profile);
        $score += $category_match * 0.2;
        
        // Collection history matching (10% weight)
        $history_match = $this->calculate_history_match($collector_profile, $artist_profile);
        $score += $history_match * 0.1;
        
        return round($score, 2);
    }
    
    /**
     * Get collector profile
     */
    private function get_collector_profile($collector_id) {
        if (isset($this->collector_profiles[$collector_id])) {
            return $this->collector_profiles[$collector_id];
        }
        
        global $wpdb;
        $profile = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_collector_profiles WHERE collector_id = %d",
                $collector_id
            ),
            ARRAY_A
        );
        
        if ($profile) {
            $this->collector_profiles[$collector_id] = $profile;
        }
        
        return $profile;
    }
    
    /**
     * Get artist profile
     */
    private function get_artist_profile($artist_id) {
        if (isset($this->artist_profiles[$artist_id])) {
            return $this->artist_profiles[$artist_id];
        }
        
        global $wpdb;
        $profile = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_artist_profiles WHERE artist_id = %d",
                $artist_id
            ),
            ARRAY_A
        );
        
        if ($profile) {
            $this->artist_profiles[$artist_id] = $profile;
        }
        
        return $profile;
    }
    
    /**
     * Calculate style match score
     */
    private function calculate_style_match($collector_profile, $artist_profile) {
        $collector_styles = json_decode($collector_profile['preferred_styles'] ?? '[]', true);
        $artist_styles = json_decode($artist_profile['artistic_styles'] ?? '[]', true);
        
        if (empty($collector_styles) || empty($artist_styles)) {
            return 0.5; // Neutral score
        }
        
        $matching_styles = array_intersect($collector_styles, $artist_styles);
        return count($matching_styles) / max(count($collector_styles), 1);
    }
    
    /**
     * Calculate price match score
     */
    private function calculate_price_match($collector_profile, $artist_profile) {
        $collector_max_price = floatval($collector_profile['max_price_range'] ?? 1000);
        $artist_avg_price = floatval($artist_profile['average_price'] ?? 500);
        
        if ($artist_avg_price <= $collector_max_price) {
            return 1.0; // Perfect match
        } elseif ($artist_avg_price <= $collector_max_price * 1.5) {
            return 0.7; // Good match
        } else {
            return 0.3; // Poor match
        }
    }
    
    /**
     * Calculate category match score
     */
    private function calculate_category_match($collector_profile, $artist_profile) {
        $collector_categories = json_decode($collector_profile['preferred_categories'] ?? '[]', true);
        $artist_categories = json_decode($artist_profile['art_categories'] ?? '[]', true);
        
        if (empty($collector_categories) || empty($artist_categories)) {
            return 0.5; // Neutral score
        }
        
        $matching_categories = array_intersect($collector_categories, $artist_categories);
        return count($matching_categories) / max(count($collector_categories), 1);
    }
    
    /**
     * Calculate history match score
     */
    private function calculate_history_match($collector_profile, $artist_profile) {
        // Check if collector has previously purchased from similar artists
        global $wpdb;
        
        $similar_artists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_marketplace_sales 
             WHERE buyer_id = %d AND artist_id IN (
                 SELECT artist_id FROM {$wpdb->prefix}vortex_artist_profiles 
                 WHERE artistic_styles LIKE %s
             )",
            $collector_profile['collector_id'],
            '%' . $wpdb->esc_like($artist_profile['artistic_styles']) . '%'
        ));
        
        return min($similar_artists / 10, 1.0); // Normalize to 0-1
    }
    
    /**
     * Get match recommendations
     */
    private function get_match_recommendations($collector_id, $artist_id) {
        $recommendations = [];
        $match_score = $this->calculate_match_score($collector_id, $artist_id);
        
        if ($match_score >= 0.8) {
            $recommendations[] = 'Excellent match! High likelihood of successful collaboration.';
            $recommendations[] = 'Consider commissioning a custom piece.';
        } elseif ($match_score >= 0.6) {
            $recommendations[] = 'Good match with potential for collaboration.';
            $recommendations[] = 'Start with smaller pieces to build relationship.';
        } elseif ($match_score >= 0.4) {
            $recommendations[] = 'Moderate match. Consider exploring different styles.';
            $recommendations[] = 'Focus on unique selling points.';
        } else {
            $recommendations[] = 'Limited match. Consider other artists or styles.';
            $recommendations[] = 'Explore different categories or price ranges.';
        }
        
        return $recommendations;
    }
    
    /**
     * Run scheduled market analysis
     */
    public function run_market_analysis() {
        $categories = ['all', 'digital_art', 'photography', 'painting', 'sculpture'];
        
        foreach ($categories as $category) {
            $this->analyze_market($category, '24h');
        }
        
        error_log('VORTEX AI Engine: CLOE market analysis completed');
    }
    
    /**
     * Predict market trends
     */
    public function predict_trends() {
        // Analyze historical data and predict future trends
        $predictions = $this->generate_market_predictions();
        
        // Store predictions in database
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_market_predictions',
            [
                'predictions' => json_encode($predictions),
                'created_at' => current_time('mysql')
            ]
        );
        
        error_log('VORTEX AI Engine: CLOE trend predictions generated');
    }
    
    /**
     * Update collector matches
     */
    public function update_collector_matches() {
        // Update collector-artist match scores
        global $wpdb;
        
        $collectors = $wpdb->get_results("SELECT collector_id FROM {$wpdb->prefix}vortex_collector_profiles");
        $artists = $wpdb->get_results("SELECT artist_id FROM {$wpdb->prefix}vortex_artist_profiles");
        
        foreach ($collectors as $collector) {
            foreach ($artists as $artist) {
                $match_score = $this->calculate_match_score($collector->collector_id, $artist->artist_id);
                
                $wpdb->replace(
                    $wpdb->prefix . 'vortex_collector_artist_matches',
                    [
                        'collector_id' => $collector->collector_id,
                        'artist_id' => $artist->artist_id,
                        'match_score' => $match_score,
                        'updated_at' => current_time('mysql')
                    ]
                );
            }
        }
        
        error_log('VORTEX AI Engine: CLOE collector matches updated');
    }
    
    /**
     * Generate market predictions
     */
    private function generate_market_predictions() {
        // This would use machine learning models to predict market trends
        return [
            'volume_prediction' => 'increasing',
            'price_prediction' => 'stable',
            'confidence_score' => 0.75,
            'factors' => ['seasonal_trends', 'market_sentiment', 'economic_indicators']
        ];
    }
    
    /**
     * Get agent status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'type' => $this->config['type'],
            'capabilities' => $this->config['capabilities'],
            'cache_size' => count($this->market_cache),
            'profiles_loaded' => count($this->collector_profiles) + count($this->artist_profiles)
        ];
    }
} 