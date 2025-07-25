<?php
/**
 * Vortex Public Interface
 * 
 * Handles frontend functionality for the VORTEX AI Engine plugin
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Public Interface Class
 */
class Vortex_Public_Interface {
    
    /**
     * Single instance of the class
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_vortex_generate_artwork', array($this, 'ajax_generate_artwork'));
        add_action('wp_ajax_nopriv_vortex_generate_artwork', array($this, 'ajax_generate_artwork'));
        add_action('wp_ajax_vortex_purchase_artwork', array($this, 'ajax_purchase_artwork'));
        add_action('wp_ajax_nopriv_vortex_purchase_artwork', array($this, 'ajax_purchase_artwork'));
        add_action('wp_ajax_vortex_get_artwork_details', array($this, 'ajax_get_artwork_details'));
        add_action('wp_ajax_nopriv_vortex_get_artwork_details', array($this, 'ajax_get_artwork_details'));
        add_action('wp_ajax_vortex_subscribe_user', array($this, 'ajax_subscribe_user'));
        add_action('wp_ajax_nopriv_vortex_subscribe_user', array($this, 'ajax_subscribe_user'));
        add_action('wp_ajax_vortex_get_artist_profile', array($this, 'ajax_get_artist_profile'));
        add_action('wp_ajax_nopriv_vortex_get_artist_profile', array($this, 'ajax_get_artist_profile'));
        
        // Shortcodes
        add_shortcode('vortex_artwork_generator', array($this, 'artwork_generator_shortcode'));
        add_shortcode('vortex_artwork_gallery', array($this, 'artwork_gallery_shortcode'));
        add_shortcode('vortex_artist_profile', array($this, 'artist_profile_shortcode'));
        add_shortcode('vortex_marketplace', array($this, 'marketplace_shortcode'));
        add_shortcode('vortex_subscription_form', array($this, 'subscription_form_shortcode'));
        
        // Widgets
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'vortex-public-js',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'public/js/vortex-public.js',
            array('jquery'),
            VORTEX_AI_ENGINE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vortex-public-css',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'public/css/vortex-public.css',
            array(),
            VORTEX_AI_ENGINE_VERSION
        );
        
        // Localize script
        wp_localize_script('vortex-public-js', 'vortex_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_public_nonce'),
            'user_id' => get_current_user_id(),
            'strings' => array(
                'generating' => __('Generating artwork...', 'vortex-ai-engine'),
                'purchasing' => __('Processing purchase...', 'vortex-ai-engine'),
                'success' => __('Operation completed successfully!', 'vortex-ai-engine'),
                'error' => __('An error occurred. Please try again.', 'vortex-ai-engine'),
                'insufficient_funds' => __('Insufficient funds for this purchase.', 'vortex-ai-engine'),
                'login_required' => __('Please log in to perform this action.', 'vortex-ai-engine')
            )
        ));
    }
    
    /**
     * AJAX: Generate artwork
     */
    public function ajax_generate_artwork() {
        check_ajax_referer('vortex_public_nonce', 'nonce');
        
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $style = sanitize_text_field($_POST['style']);
        $size = sanitize_text_field($_POST['size']);
        
        if (empty($prompt)) {
            wp_send_json_error(__('Prompt is required', 'vortex-ai-engine'));
        }
        
        // Check user subscription
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to generate artwork', 'vortex-ai-engine'));
        }
        
        $subscription_manager = Vortex_Subscription_Manager::get_instance();
        $user_subscription = $subscription_manager->get_user_subscription(get_current_user_id());
        
        if (!$user_subscription || $user_subscription->status !== 'active') {
            wp_send_json_error(__('Active subscription required to generate artwork', 'vortex-ai-engine'));
        }
        
        // Check generation limits
        $daily_generations = $this->get_user_daily_generations(get_current_user_id());
        $max_generations = $this->get_max_generations_for_tier($user_subscription->subscription_tier);
        
        if ($daily_generations >= $max_generations) {
            wp_send_json_error(__('Daily generation limit reached. Please upgrade your subscription.', 'vortex-ai-engine'));
        }
        
        // Generate artwork
        $huraii_agent = Vortex_HURAII_Agent::get_instance();
        $result = $huraii_agent->generate_image($prompt, array(
            'style' => $style,
            'size' => $size
        ));
        
        if ($result['success']) {
            // Save artwork to database
            $artwork_id = $this->save_generated_artwork($prompt, $result);
            
            // Update user generation count
            $this->increment_user_generations(get_current_user_id());
            
            wp_send_json_success(array(
                'artwork_id' => $artwork_id,
                'image_url' => $result['image_url'],
                'metadata' => $result['metadata']
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX: Purchase artwork
     */
    public function ajax_purchase_artwork() {
        check_ajax_referer('vortex_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to purchase artwork', 'vortex-ai-engine'));
        }
        
        $artwork_id = intval($_POST['artwork_id']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        
        if (empty($artwork_id)) {
            wp_send_json_error(__('Artwork ID is required', 'vortex-ai-engine'));
        }
        
        // Get artwork details
        $db_manager = Vortex_Database_Manager::get_instance();
        $artwork = $db_manager->get_row('artworks', array('id' => $artwork_id));
        
        if (!$artwork) {
            wp_send_json_error(__('Artwork not found', 'vortex-ai-engine'));
        }
        
        if ($artwork->status !== 'available') {
            wp_send_json_error(__('Artwork is not available for purchase', 'vortex-ai-engine'));
        }
        
        // Check if user is trying to buy their own artwork
        if ($artwork->artist_id == get_current_user_id()) {
            wp_send_json_error(__('You cannot purchase your own artwork', 'vortex-ai-engine'));
        }
        
        // Process payment
        $payment_result = $this->process_payment($artwork, $payment_method);
        
        if ($payment_result['success']) {
            // Create transaction record
            $transaction_id = $this->create_transaction_record($artwork, $payment_result);
            
            // Update artwork status
            $db_manager->update('artworks', array('status' => 'sold'), array('id' => $artwork_id));
            
            // Distribute royalties
            $this->distribute_royalties($artwork, $payment_result['amount']);
            
            wp_send_json_success(array(
                'transaction_id' => $transaction_id,
                'message' => __('Purchase completed successfully!', 'vortex-ai-engine')
            ));
        } else {
            wp_send_json_error($payment_result['error']);
        }
    }
    
    /**
     * AJAX: Get artwork details
     */
    public function ajax_get_artwork_details() {
        check_ajax_referer('vortex_public_nonce', 'nonce');
        
        $artwork_id = intval($_POST['artwork_id']);
        
        if (empty($artwork_id)) {
            wp_send_json_error(__('Artwork ID is required', 'vortex-ai-engine'));
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artwork = $db_manager->get_row('artworks', array('id' => $artwork_id));
        
        if (!$artwork) {
            wp_send_json_error(__('Artwork not found', 'vortex-ai-engine'));
        }
        
        // Get artist information
        $artist = $db_manager->get_row('artists', array('id' => $artwork->artist_id));
        
        wp_send_json_success(array(
            'artwork' => $artwork,
            'artist' => $artist
        ));
    }
    
    /**
     * AJAX: Subscribe user
     */
    public function ajax_subscribe_user() {
        check_ajax_referer('vortex_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to subscribe', 'vortex-ai-engine'));
        }
        
        $subscription_tier = sanitize_text_field($_POST['subscription_tier']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        
        if (empty($subscription_tier)) {
            wp_send_json_error(__('Subscription tier is required', 'vortex-ai-engine'));
        }
        
        // Validate subscription tier
        $valid_tiers = array('starter', 'pro', 'studio');
        if (!in_array($subscription_tier, $valid_tiers)) {
            wp_send_json_error(__('Invalid subscription tier', 'vortex-ai-engine'));
        }
        
        // Get subscription manager
        $subscription_manager = Vortex_Subscription_Manager::get_instance();
        
        // Create subscription
        $result = $subscription_manager->create_subscription(
            get_current_user_id(),
            $subscription_tier,
            $payment_method
        );
        
        if ($result['success']) {
            wp_send_json_success(array(
                'subscription_id' => $result['subscription_id'],
                'message' => __('Subscription created successfully!', 'vortex-ai-engine')
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX: Get artist profile
     */
    public function ajax_get_artist_profile() {
        check_ajax_referer('vortex_public_nonce', 'nonce');
        
        $artist_id = intval($_POST['artist_id']);
        
        if (empty($artist_id)) {
            wp_send_json_error(__('Artist ID is required', 'vortex-ai-engine'));
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artist = $db_manager->get_row('artists', array('id' => $artist_id));
        
        if (!$artist) {
            wp_send_json_error(__('Artist not found', 'vortex-ai-engine'));
        }
        
        // Get artist artworks
        $artworks = $db_manager->get_results('artworks', array('artist_id' => $artist_id), 'created_at DESC', 10);
        
        // Get artist statistics
        $stats = $this->get_artist_statistics($artist_id);
        
        wp_send_json_success(array(
            'artist' => $artist,
            'artworks' => $artworks,
            'statistics' => $stats
        ));
    }
    
    /**
     * Shortcode: Artwork generator
     */
    public function artwork_generator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'modern',
            'size' => '1024x1024'
        ), $atts);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/artwork-generator.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Artwork gallery
     */
    public function artwork_gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'category' => '',
            'artist_id' => ''
        ), $atts);
        
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $where = array('status' => 'available');
        if (!empty($atts['category'])) {
            $where['category'] = $atts['category'];
        }
        if (!empty($atts['artist_id'])) {
            $where['artist_id'] = $atts['artist_id'];
        }
        
        $artworks = $db_manager->get_results('artworks', $where, 'created_at DESC', $atts['limit']);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/artwork-gallery.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Artist profile
     */
    public function artist_profile_shortcode($atts) {
        $atts = shortcode_atts(array(
            'artist_id' => get_current_user_id()
        ), $atts);
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artist = $db_manager->get_row('artists', array('id' => $atts['artist_id']));
        
        if (!$artist) {
            return '<p>' . __('Artist not found', 'vortex-ai-engine') . '</p>';
        }
        
        $artworks = $db_manager->get_results('artworks', array('artist_id' => $atts['artist_id']), 'created_at DESC');
        $stats = $this->get_artist_statistics($atts['artist_id']);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/artist-profile.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Marketplace
     */
    public function marketplace_shortcode($atts) {
        $atts = shortcode_atts(array(
            'featured' => 'true',
            'trending' => 'true'
        ), $atts);
        
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $featured_artworks = array();
        $trending_artworks = array();
        
        if ($atts['featured'] === 'true') {
            $featured_artworks = $db_manager->get_results('artworks', array('status' => 'available'), 'created_at DESC', 6);
        }
        
        if ($atts['trending'] === 'true') {
            $trending_artworks = $db_manager->get_results('artworks', array('status' => 'available'), 'created_at DESC', 6);
        }
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/marketplace.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Subscription form
     */
    public function subscription_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_current' => 'true'
        ), $atts);
        
        $current_subscription = null;
        if ($atts['show_current'] === 'true' && is_user_logged_in()) {
            $subscription_manager = Vortex_Subscription_Manager::get_instance();
            $current_subscription = $subscription_manager->get_user_subscription(get_current_user_id());
        }
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/subscription-form.php';
        return ob_get_clean();
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('Vortex_Artwork_Generator_Widget');
        register_widget('Vortex_Artwork_Gallery_Widget');
        register_widget('Vortex_Artist_Profile_Widget');
        register_widget('Vortex_Marketplace_Widget');
    }
    
    /**
     * Helper methods
     */
    private function get_user_daily_generations($user_id) {
        $db_manager = Vortex_Database_Manager::get_instance();
        $today = date('Y-m-d');
        
        $generations = $db_manager->get_results(
            'ai_generations',
            array(),
            '',
            "DATE(created_at) = '$today' AND artist_id = $user_id"
        );
        
        return count($generations);
    }
    
    private function get_max_generations_for_tier($tier) {
        $limits = array(
            'starter' => 5,
            'pro' => 20,
            'studio' => 100
        );
        
        return isset($limits[$tier]) ? $limits[$tier] : 5;
    }
    
    private function save_generated_artwork($prompt, $result) {
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $data = array(
            'title' => 'AI Generated Artwork',
            'description' => $prompt,
            'artist_id' => get_current_user_id(),
            'ai_agent' => 'HURAII',
            'prompt' => $prompt,
            'image_url' => $result['image_url'],
            'metadata' => json_encode($result['metadata']),
            'status' => 'draft'
        );
        
        return $db_manager->insert('artworks', $data);
    }
    
    private function increment_user_generations($user_id) {
        // This would typically update a user_generations table
        // For now, we'll just log it
        $db_manager = Vortex_Database_Manager::get_instance();
        $db_manager->log('info', 'public_interface', "User $user_id generated artwork");
    }
    
    private function process_payment($artwork, $payment_method) {
        // Simulated payment processing
        // In a real implementation, this would integrate with Stripe, PayPal, etc.
        
        $amount = $artwork->price;
        $transaction_id = 'txn_' . wp_generate_password(16, false);
        
        return array(
            'success' => true,
            'transaction_id' => $transaction_id,
            'amount' => $amount
        );
    }
    
    private function create_transaction_record($artwork, $payment_result) {
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $data = array(
            'transaction_hash' => $payment_result['transaction_id'],
            'artwork_id' => $artwork->id,
            'seller_id' => $artwork->artist_id,
            'buyer_id' => get_current_user_id(),
            'amount' => $payment_result['amount'],
            'marketplace_fee' => $payment_result['amount'] * 0.15, // 15% marketplace fee
            'creator_royalty' => $payment_result['amount'] * 0.05, // 5% creator royalty
            'artist_royalty' => $payment_result['amount'] * 0.80, // 80% artist royalty
            'transaction_type' => 'sale',
            'status' => 'completed'
        );
        
        return $db_manager->insert('transactions', $data);
    }
    
    private function distribute_royalties($artwork, $amount) {
        // Distribute royalties to creator and artist
        // This would typically involve blockchain transactions
        
        $creator_royalty = $amount * 0.05; // 5% to creator
        $artist_royalty = $amount * 0.80; // 80% to artist
        
        // Log royalty distribution
        $db_manager = Vortex_Database_Manager::get_instance();
        $db_manager->log('info', 'public_interface', "Royalties distributed: Creator $creator_royalty, Artist $artist_royalty");
    }
    
    private function get_artist_statistics($artist_id) {
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $total_artworks = $db_manager->get_results('artworks', array('artist_id' => $artist_id));
        $total_sales = $db_manager->get_results('transactions', array('seller_id' => $artist_id, 'status' => 'completed'));
        
        $total_sales_amount = 0;
        foreach ($total_sales as $sale) {
            $total_sales_amount += $sale->amount;
        }
        
        return array(
            'total_artworks' => count($total_artworks),
            'total_sales' => count($total_sales),
            'total_sales_amount' => $total_sales_amount
        );
    }
}

/**
 * Vortex Artwork Generator Widget
 */
class Vortex_Artwork_Generator_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'vortex_artwork_generator',
            __('VORTEX Artwork Generator', 'vortex-ai-engine'),
            array('description' => __('Generate AI artwork with HURAII agent', 'vortex-ai-engine'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . __('AI Artwork Generator', 'vortex-ai-engine') . $args['after_title'];
        
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/widgets/artwork-generator.php';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'vortex-ai-engine'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * Vortex Artwork Gallery Widget
 */
class Vortex_Artwork_Gallery_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'vortex_artwork_gallery',
            __('VORTEX Artwork Gallery', 'vortex-ai-engine'),
            array('description' => __('Display recent AI-generated artworks', 'vortex-ai-engine'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . __('Recent Artworks', 'vortex-ai-engine') . $args['after_title'];
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artworks = $db_manager->get_results('artworks', array('status' => 'available'), 'created_at DESC', 6);
        
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/widgets/artwork-gallery.php';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $limit = !empty($instance['limit']) ? $instance['limit'] : 6;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'vortex-ai-engine'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of artworks:', 'vortex-ai-engine'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="20">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 6;
        return $instance;
    }
} 