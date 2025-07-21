<?php
/**
 * VORTEX AI Engine - HORACE Agent
 * 
 * Content creation and curation AI agent
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HORACE Agent Class
 * 
 * Handles content creation, curation, and artistic storytelling
 */
class Vortex_Horace_Agent {
    
    /**
     * Agent configuration
     */
    private $config = [
        'name' => 'HORACE',
        'type' => 'CPU',
        'capabilities' => ['content_creation', 'storytelling', 'curation', 'seo_optimization'],
        'content_templates' => [],
        'writing_styles' => [],
        'seo_keywords' => []
    ];
    
    /**
     * Content cache
     */
    private $content_cache = [];
    
    /**
     * Story templates
     */
    private $story_templates = [];
    
    /**
     * Initialize the HORACE agent
     */
    public function init() {
        $this->load_configuration();
        $this->register_hooks();
        $this->initialize_content_system();
        
        error_log('VORTEX AI Engine: HORACE Agent initialized');
    }
    
    /**
     * Load agent configuration
     */
    private function load_configuration() {
        $this->config['content_templates'] = [
            'artist_spotlight' => [
                'title' => 'Artist Spotlight: {artist_name}',
                'structure' => ['intro', 'background', 'artistic_style', 'notable_works', 'future_projects'],
                'word_count' => 800
            ],
            'art_analysis' => [
                'title' => 'Art Analysis: {artwork_title}',
                'structure' => ['overview', 'technique', 'meaning', 'context', 'impact'],
                'word_count' => 600
            ],
            'market_trends' => [
                'title' => 'Market Trends: {trend_topic}',
                'structure' => ['introduction', 'current_state', 'analysis', 'predictions', 'conclusion'],
                'word_count' => 1000
            ],
            'collector_guide' => [
                'title' => 'Collector Guide: {guide_topic}',
                'structure' => ['overview', 'tips', 'examples', 'resources', 'next_steps'],
                'word_count' => 1200
            ]
        ];
        
        $this->config['writing_styles'] = [
            'professional' => 'formal, analytical, authoritative',
            'conversational' => 'friendly, engaging, accessible',
            'artistic' => 'creative, descriptive, emotional',
            'educational' => 'clear, informative, structured'
        ];
        
        $this->config['seo_keywords'] = [
            'art_collection' => ['art collection', 'art investment', 'art market', 'collecting art'],
            'digital_art' => ['digital art', 'NFT art', 'crypto art', 'digital artists'],
            'art_analysis' => ['art analysis', 'art critique', 'art interpretation', 'art history'],
            'artist_spotlight' => ['artist spotlight', 'emerging artists', 'contemporary art', 'artists to watch']
        ];
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_vortex_create_content', [$this, 'handle_content_creation']);
        add_action('wp_ajax_vortex_curate_content', [$this, 'handle_content_curation']);
        add_action('vortex_daily_content_creation', [$this, 'create_daily_content']);
        add_action('vortex_content_optimization', [$this, 'optimize_content']);
        add_action('vortex_story_generation', [$this, 'generate_artistic_stories']);
    }
    
    /**
     * Initialize content system
     */
    private function initialize_content_system() {
        // Schedule daily content creation
        if (!wp_next_scheduled('vortex_daily_content_creation')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_content_creation');
        }
        
        // Schedule content optimization
        if (!wp_next_scheduled('vortex_content_optimization')) {
            wp_schedule_event(time(), 'twicedaily', 'vortex_content_optimization');
        }
        
        // Schedule story generation
        if (!wp_next_scheduled('vortex_story_generation')) {
            wp_schedule_event(time(), 'daily', 'vortex_story_generation');
        }
    }
    
    /**
     * Handle content creation request
     */
    public function handle_content_creation() {
        check_ajax_referer('vortex_content_nonce', 'nonce');
        
        $template = sanitize_text_field($_POST['template'] ?? 'artist_spotlight');
        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $style = sanitize_text_field($_POST['style'] ?? 'professional');
        
        if (empty($topic)) {
            wp_send_json_error(['message' => 'Topic is required']);
        }
        
        $content = $this->create_content($template, $topic, $style);
        
        if ($content['success']) {
            wp_send_json_success($content);
        } else {
            wp_send_json_error($content);
        }
    }
    
    /**
     * Handle content curation request
     */
    public function handle_content_curation() {
        check_ajax_referer('vortex_curation_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        $limit = intval($_POST['limit'] ?? 10);
        
        $curated_content = $this->curate_content($category, $limit);
        
        wp_send_json_success($curated_content);
    }
    
    /**
     * Create content based on template and topic
     */
    public function create_content($template, $topic, $style = 'professional') {
        try {
            if (!isset($this->config['content_templates'][$template])) {
                throw new Exception('Invalid content template');
            }
            
            $template_config = $this->config['content_templates'][$template];
            $content_structure = $this->generate_content_structure($template_config, $topic);
            $content_text = $this->write_content($content_structure, $style);
            $seo_optimized = $this->optimize_for_seo($content_text, $template);
            
            return [
                'success' => true,
                'title' => $this->generate_title($template_config['title'], $topic),
                'content' => $content_text,
                'seo_optimized' => $seo_optimized,
                'word_count' => str_word_count($content_text),
                'template' => $template,
                'style' => $style
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: HORACE content creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate content structure
     */
    private function generate_content_structure($template_config, $topic) {
        $structure = [];
        
        foreach ($template_config['structure'] as $section) {
            $structure[$section] = $this->generate_section_content($section, $topic);
        }
        
        return $structure;
    }
    
    /**
     * Generate section content
     */
    private function generate_section_content($section, $topic) {
        $section_templates = [
            'intro' => [
                'artist_spotlight' => "Discover the captivating world of {artist_name}, a visionary artist whose work transcends traditional boundaries and challenges our perception of contemporary art.",
                'art_analysis' => "In this comprehensive analysis, we explore the intricate details and profound meaning behind {artwork_title}, a masterpiece that continues to inspire and provoke thought.",
                'market_trends' => "The art market is experiencing significant shifts in {trend_topic}, presenting both challenges and opportunities for collectors and artists alike.",
                'collector_guide' => "Navigating the world of {guide_topic} can be both exciting and overwhelming. This comprehensive guide provides essential insights for collectors at every level."
            ],
            'background' => [
                'artist_spotlight' => "{artist_name} began their artistic journey in {birth_year}, developing a unique style that combines {artistic_influences}. Their early work was characterized by {early_characteristics}, which evolved into their signature approach.",
                'art_analysis' => "Created in {creation_year}, this piece emerged during a period of {historical_context}. The artist's intention was to {artistic_intention}, resulting in a work that resonates with audiences across generations.",
                'market_trends' => "The current landscape of {trend_topic} has been shaped by {market_factors}. Understanding these influences is crucial for making informed decisions in today's dynamic art market.",
                'collector_guide' => "The fundamentals of {guide_topic} have evolved significantly over the past decade. From traditional methods to digital innovations, the landscape continues to transform."
            ]
        ];
        
        $template = $section_templates[$section][$topic] ?? $this->get_default_section_template($section);
        
        return $this->fill_template_placeholders($template, $topic);
    }
    
    /**
     * Get default section template
     */
    private function get_default_section_template($section) {
        $defaults = [
            'intro' => "This section introduces the key concepts and themes that will be explored throughout this comprehensive analysis.",
            'background' => "Understanding the historical and contextual background provides essential foundation for deeper appreciation and analysis.",
            'analysis' => "Through careful examination of various elements, we can uncover the deeper meanings and artistic significance.",
            'conclusion' => "As we conclude our exploration, the lasting impact and continued relevance of these artistic contributions become clear."
        ];
        
        return $defaults[$section] ?? "This section provides valuable insights and information relevant to the topic at hand.";
    }
    
    /**
     * Fill template placeholders
     */
    private function fill_template_placeholders($template, $topic) {
        $placeholders = [
            '{artist_name}' => $this->generate_artist_name($topic),
            '{artwork_title}' => $this->generate_artwork_title($topic),
            '{trend_topic}' => $topic,
            '{guide_topic}' => $topic,
            '{birth_year}' => rand(1960, 2000),
            '{creation_year}' => rand(2010, 2024),
            '{artistic_influences}' => $this->get_artistic_influences(),
            '{early_characteristics}' => $this->get_early_characteristics(),
            '{historical_context}' => $this->get_historical_context(),
            '{artistic_intention}' => $this->get_artistic_intention(),
            '{market_factors}' => $this->get_market_factors()
        ];
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
    
    /**
     * Write content based on structure and style
     */
    private function write_content($structure, $style) {
        $content_parts = [];
        
        foreach ($structure as $section => $content) {
            $content_parts[] = $this->format_section($section, $content, $style);
        }
        
        return implode("\n\n", $content_parts);
    }
    
    /**
     * Format section with appropriate styling
     */
    private function format_section($section, $content, $style) {
        $section_title = ucwords(str_replace('_', ' ', $section));
        
        if ($style === 'professional') {
            return "<h2>$section_title</h2>\n<p>$content</p>";
        } elseif ($style === 'conversational') {
            return "<h2>$section_title</h2>\n<p>$content</p>";
        } elseif ($style === 'artistic') {
            return "<h2>$section_title</h2>\n<p>$content</p>";
        } else {
            return "<h2>$section_title</h2>\n<p>$content</p>";
        }
    }
    
    /**
     * Optimize content for SEO
     */
    private function optimize_for_seo($content, $template) {
        $keywords = $this->config['seo_keywords'][$template] ?? [];
        $optimized_content = $content;
        
        foreach ($keywords as $keyword) {
            // Add keyword density optimization
            $keyword_count = substr_count(strtolower($optimized_content), strtolower($keyword));
            $word_count = str_word_count($optimized_content);
            $density = $keyword_count / $word_count;
            
            if ($density < 0.01) { // Less than 1% density
                $optimized_content = $this->add_keyword_naturally($optimized_content, $keyword);
            }
        }
        
        return $optimized_content;
    }
    
    /**
     * Add keyword naturally to content
     */
    private function add_keyword_naturally($content, $keyword) {
        $sentences = explode('.', $content);
        $random_sentence_index = array_rand($sentences);
        
        if (!empty($sentences[$random_sentence_index])) {
            $sentences[$random_sentence_index] .= " This highlights the importance of $keyword in contemporary art.";
        }
        
        return implode('.', $sentences);
    }
    
    /**
     * Generate title
     */
    private function generate_title($title_template, $topic) {
        return str_replace(['{artist_name}', '{artwork_title}', '{trend_topic}', '{guide_topic}'], $topic, $title_template);
    }
    
    /**
     * Curate content based on category
     */
    public function curate_content($category = 'all', $limit = 10) {
        global $wpdb;
        
        $category_condition = $category !== 'all' ? "AND category = '$category'" : '';
        
        $query = "
            SELECT 
                p.ID,
                p.post_title,
                p.post_content,
                p.post_date,
                p.post_author,
                pm.meta_value as art_category
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'art_category'
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
            $category_condition
            ORDER BY p.post_date DESC
            LIMIT $limit
        ";
        
        $curated_posts = $wpdb->get_results($query);
        
        $curated_content = [];
        foreach ($curated_posts as $post) {
            $curated_content[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_content, 50),
                'date' => $post->post_date,
                'author' => get_the_author_meta('display_name', $post->post_author),
                'category' => $post->art_category,
                'url' => get_permalink($post->ID)
            ];
        }
        
        return $curated_content;
    }
    
    /**
     * Create daily content
     */
    public function create_daily_content() {
        $content_types = ['artist_spotlight', 'art_analysis', 'market_trends', 'collector_guide'];
        $topics = $this->get_daily_topics();
        
        foreach ($content_types as $type) {
            $topic = $topics[$type] ?? 'contemporary art trends';
            $content = $this->create_content($type, $topic, 'professional');
            
            if ($content['success']) {
                $this->publish_content($content);
            }
        }
        
        error_log('VORTEX AI Engine: HORACE daily content created');
    }
    
    /**
     * Get daily topics
     */
    private function get_daily_topics() {
        return [
            'artist_spotlight' => $this->get_featured_artist(),
            'art_analysis' => $this->get_featured_artwork(),
            'market_trends' => $this->get_trending_topic(),
            'collector_guide' => $this->get_guide_topic()
        ];
    }
    
    /**
     * Publish content to WordPress
     */
    private function publish_content($content) {
        $post_data = [
            'post_title' => $content['title'],
            'post_content' => $content['seo_optimized'],
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1, // Default admin user
            'post_category' => [get_cat_ID('Art & Culture')]
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            // Add custom meta
            update_post_meta($post_id, 'vortex_ai_generated', true);
            update_post_meta($post_id, 'vortex_agent', 'HORACE');
            update_post_meta($post_id, 'vortex_template', $content['template']);
            
            error_log("VORTEX AI Engine: HORACE published content - $post_id");
        }
    }
    
    /**
     * Generate artistic stories
     */
    public function generate_artistic_stories() {
        $story_templates = [
            'artist_journey' => 'The transformative journey of an artist discovering their unique voice',
            'artwork_creation' => 'The creative process behind a masterpiece and its evolution',
            'collector_experience' => 'A collector\'s emotional connection with a piece of art',
            'market_discovery' => 'The discovery of an emerging artist and their impact on the market'
        ];
        
        foreach ($story_templates as $type => $prompt) {
            $story = $this->create_story($type, $prompt);
            $this->save_story($story);
        }
        
        error_log('VORTEX AI Engine: HORACE artistic stories generated');
    }
    
    /**
     * Create story
     */
    private function create_story($type, $prompt) {
        $story_structure = [
            'opening' => $this->generate_story_opening($prompt),
            'development' => $this->generate_story_development($prompt),
            'climax' => $this->generate_story_climax($prompt),
            'resolution' => $this->generate_story_resolution($prompt)
        ];
        
        return [
            'type' => $type,
            'title' => $this->generate_story_title($type),
            'content' => implode("\n\n", $story_structure),
            'prompt' => $prompt,
            'created_at' => current_time('mysql')
        ];
    }
    
    /**
     * Save story to database
     */
    private function save_story($story) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_artistic_stories',
            $story
        );
    }
    
    /**
     * Optimize content
     */
    public function optimize_content() {
        // Analyze content performance and optimize
        $this->analyze_content_performance();
        $this->update_content_strategies();
        
        error_log('VORTEX AI Engine: HORACE content optimized');
    }
    
    /**
     * Analyze content performance
     */
    private function analyze_content_performance() {
        global $wpdb;
        
        $performance_data = $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title,
                p.post_views,
                p.post_engagement,
                pm.meta_value as ai_generated
            FROM {$wpdb->prefix}vortex_content_performance p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'vortex_ai_generated'
            WHERE pm.meta_value = '1'
            ORDER BY p.post_views DESC
            LIMIT 20
        ");
        
        // Store performance insights
        update_option('vortex_content_performance_insights', $performance_data);
    }
    
    /**
     * Update content strategies
     */
    private function update_content_strategies() {
        $insights = get_option('vortex_content_performance_insights', []);
        
        // Adjust content templates based on performance
        foreach ($insights as $insight) {
            if ($insight->post_views > 1000) {
                // Successful content type - enhance similar templates
                $this->enhance_successful_templates($insight);
            }
        }
    }
    
    // Helper methods for generating content elements
    private function generate_artist_name($topic) { return "Alexandra Chen"; }
    private function generate_artwork_title($topic) { return "Ethereal Dreams"; }
    private function get_artistic_influences() { return "classical techniques and contemporary digital media"; }
    private function get_early_characteristics() { return "bold experimentation with color and form"; }
    private function get_historical_context() { return "significant social and technological change"; }
    private function get_artistic_intention() { return "challenge conventional perspectives"; }
    private function get_market_factors() { return "digital transformation and changing collector preferences"; }
    private function get_featured_artist() { return "Sarah Rodriguez"; }
    private function get_featured_artwork() { return "Digital Harmony #7"; }
    private function get_trending_topic() { return "AI-generated art market"; }
    private function get_guide_topic() { return "investing in emerging artists"; }
    private function generate_story_opening($prompt) { return "In the quiet studio, where creativity meets determination..."; }
    private function generate_story_development($prompt) { return "As the project evolved, unexpected challenges emerged..."; }
    private function generate_story_climax($prompt) { return "The breakthrough moment arrived when..."; }
    private function generate_story_resolution($prompt) { return "This journey transformed not just the art, but the artist themselves..."; }
    private function generate_story_title($type) { return "The " . ucwords(str_replace('_', ' ', $type)); }
    private function enhance_successful_templates($insight) { /* Template enhancement logic */ }
    
    /**
     * Get agent status
     */
    public function get_status() {
        return [
            'name' => $this->config['name'],
            'type' => $this->config['type'],
            'capabilities' => $this->config['capabilities'],
            'templates_available' => count($this->config['content_templates']),
            'cache_size' => count($this->content_cache)
        ];
    }
} 