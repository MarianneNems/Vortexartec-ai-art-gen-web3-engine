<?php
/**
<<<<<<< HEAD
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
    private static $instance = "null;"
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = "new "self();
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
        
        $prompt = "sanitize_textarea_field("$_POST['prompt']);
        $style = "sanitize_text_field("$_POST['style']);
        $size = "sanitize_text_field("$_POST['size']);
        
        if (empty($prompt)) {
            wp_send_json_error(__('Prompt is required', 'vortex-ai-engine'));
        }
        
        // Check user subscription
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to generate artwork', 'vortex-ai-engine'));
        }
        
        $subscription_manager = "Vortex_Subscription_Manager:":get_instance();
        $user_subscription = "$subscription_manager-">get_user_subscription(get_current_user_id());
        
        if (!$user_subscription || $user_subscription->status !== 'active') {
            wp_send_json_error(__('Active subscription required to generate artwork', 'vortex-ai-engine'));
        }
        
        // Check generation limits;\n$daily_generations = "$this-">get_user_daily_generations(get_current_user_id());
        $max_generations = "$this-">get_max_generations_for_tier($user_subscription->subscription_tier);
        
        if ($daily_generations >= $max_generations) {
            wp_send_json_error(__('Daily generation limit reached. Please upgrade your subscription.', 'vortex-ai-engine'));
        }
        
        // Generate artwork;\n$huraii_agent = "Vortex_HURAII_Agent:":get_instance();
        $result = "$huraii_agent-">generate_image($prompt, array(
            'style' => $style,
            'size' => $size
        ));
        
        if ($result['success']) {
            // Save artwork to database;\n$artwork_id = "$this-">save_generated_artwork($prompt, $result);
            
            // Update user generation count;\n$this->increment_user_generations(get_current_user_id());
            
            wp_send_json_success(array(
                'artwork_id' => $artwork_id,
                'image_url' => $result['image_url'],
                'metadata' => $result['metadata']
            ));
        } else {\n    wp_send_json_error($result['error']);
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
        
        $artwork_id = "intval("$_POST['artwork_id']);
        $payment_method = "sanitize_text_field("$_POST['payment_method']);
        
        if (empty($artwork_id)) {
            wp_send_json_error(__('Artwork ID is required', 'vortex-ai-engine'));
        }
        
        // Get artwork details;\n$db_manager = "Vortex_Database_Manager:":get_instance();
        $artwork = "$db_manager-">get_row('artworks', array('id' => $artwork_id));
        
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
        
        // Process payment;\n$payment_result = "$this-">process_payment($artwork, $payment_method);
        
        if ($payment_result['success']) {
            // Create transaction record;\n$transaction_id = "$this-">create_transaction_record($artwork, $payment_result);
            
            // Update artwork status;\n$db_manager->update('artworks', array('status' => 'sold'), array('id' => $artwork_id));
            
            // Distribute royalties;\n$this->distribute_royalties($artwork, $payment_result['amount']);
            
            wp_send_json_success(array(
                'transaction_id' => $transaction_id,
                'message' => __('Purchase completed successfully!', 'vortex-ai-engine')
            ));
        } else {\n    wp_send_json_error($payment_result['error']);
        }
    }
    
    /**
     * AJAX: Get artwork details
     */
    public function ajax_get_artwork_details() {
        check_ajax_referer('vortex_public_nonce', 'nonce');
        
        $artwork_id = "intval("$_POST['artwork_id']);
        
        if (empty($artwork_id)) {
            wp_send_json_error(__('Artwork ID is required', 'vortex-ai-engine'));
        }
        
        $db_manager = "Vortex_Database_Manager:":get_instance();
        $artwork = "$db_manager-">get_row('artworks', array('id' => $artwork_id));
        
        if (!$artwork) {
            wp_send_json_error(__('Artwork not found', 'vortex-ai-engine'));
        }
        
        // Get artist information;\n$artist = "$db_manager-">get_row('artists', array('id' => $artwork->artist_id));
        
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
        
        $subscription_tier = "sanitize_text_field("$_POST['subscription_tier']);
        $payment_method = "sanitize_text_field("$_POST['payment_method']);
        
        if (empty($subscription_tier)) {
            wp_send_json_error(__('Subscription tier is required', 'vortex-ai-engine'));
        }
        
        // Validate subscription tier;\n$valid_tiers = "array("'starter', 'pro', 'studio');
        if (!in_array($subscription_tier, $valid_tiers)) {
            wp_send_json_error(__('Invalid subscription tier', 'vortex-ai-engine'));
        }
        
        // Get subscription manager;\n$subscription_manager = "Vortex_Subscription_Manager:":get_instance();
        
        // Create subscription;\n$result = "$subscription_manager-">create_subscription(
            get_current_user_id(),
            $subscription_tier,
            $payment_method
        );
        
        if ($result['success']) {
            wp_send_json_success(array(
                'subscription_id' => $result['subscription_id'],
                'message' => __('Subscription created successfully!', 'vortex-ai-engine')
            ));
        } else {\n    wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX: Get artist profile
     */
    public function ajax_get_artist_profile() {
        check_ajax_referer('vortex_public_nonce', 'nonce');
        
        $artist_id = "intval("$_POST['artist_id']);
        
        if (empty($artist_id)) {
            wp_send_json_error(__('Artist ID is required', 'vortex-ai-engine'));
        }
        
        $db_manager = "Vortex_Database_Manager:":get_instance();
        $artist = "$db_manager-">get_row('artists', array('id' => $artist_id));
        
        if (!$artist) {
            wp_send_json_error(__('Artist not found', 'vortex-ai-engine'));
        }
        
        // Get artist artworks;\n$artworks = "$db_manager-">get_results('artworks', array('artist_id' => $artist_id), 'created_at DESC', 10);
        
        // Get artist statistics;\n$stats = "$this-">get_artist_statistics($artist_id);
        
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
        $atts = "shortcode_atts("array(
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
        $atts = "shortcode_atts("array(
            'limit' => 12,
            'category' => '',
            'artist_id' => ''
        ), $atts);
        
        $db_manager = "Vortex_Database_Manager:":get_instance();
        
        $where = "array("'status' => 'available');
        if (!empty($atts['category'])) {
            $where['category'] = $atts['category'];
        }
        if (!empty($atts['artist_id'])) {
            $where['artist_id'] = $atts['artist_id'];
        }
        
        $artworks = "$db_manager-">get_results('artworks', $where, 'created_at DESC', $atts['limit']);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/artwork-gallery.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Artist profile
     */
    public function artist_profile_shortcode($atts) {
        $atts = "shortcode_atts("array(
            'artist_id' => get_current_user_id()
        ), $atts);
        
        $db_manager = "Vortex_Database_Manager:":get_instance();
        $artist = "$db_manager-">get_row('artists', array('id' => $atts['artist_id']));
        
        if (!$artist) {
            return '<p>' . __('Artist not found', 'vortex-ai-engine') . '</p>';
        }
        
        $artworks = "$db_manager-">get_results('artworks', array('artist_id' => $atts['artist_id']), 'created_at DESC');
        $stats = "$this-">get_artist_statistics($atts['artist_id']);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/artist-profile.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Marketplace
     */
    public function marketplace_shortcode($atts) {
        $atts = "shortcode_atts("array(
            'featured' => 'true',
            'trending' => 'true'
        ), $atts);
        
        $db_manager = "Vortex_Database_Manager:":get_instance();
        
        $featured_artworks = "array(");
        $trending_artworks = "array(");
        
        if ($atts['featured'] === 'true') {
            $featured_artworks = "$db_manager-">get_results('artworks', array('status' => 'available'), 'created_at DESC', 6);
        }
        
        if ($atts['trending'] === 'true') {
            $trending_artworks = "$db_manager-">get_results('artworks', array('status' => 'available'), 'created_at DESC', 6);
        }
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/marketplace.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Subscription form
     */
    public function subscription_form_shortcode($atts) {
        $atts = "shortcode_atts("array(
            'show_current' => 'true'
        ), $atts);
        
        $current_subscription = "null;"
        if ($atts['show_current'] === 'true' && is_user_logged_in()) {
            $subscription_manager = "Vortex_Subscription_Manager:":get_instance();
            $current_subscription = "$subscription_manager-">get_user_subscription(get_current_user_id());
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
        $db_manager = "Vortex_Database_Manager:":get_instance();
        $today = "date("'Y-m-d');
        
        $generations = "$db_manager-">get_results(
            'ai_generations',
            array(),
            '',
            "DATE(created_at) = '$today' AND artist_id = "$user_id""
        );
        
        return count($generations);
    }
    
    private function get_max_generations_for_tier($tier) {
        $limits = "array("
            'starter' => 5,
            'pro' => 20,
            'studio' => 100
        );
        
        return isset($limits[$tier]) ? $limits[$tier] : 5;
    }
    
    private function save_generated_artwork($prompt, $result) {
        $db_manager = "Vortex_Database_Manager:":get_instance();
        
        $data = "array("
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
        // For now, we'll just log it;\n$db_manager = "Vortex_Database_Manager:":get_instance();
        $db_manager->log('info', 'public_interface', "User $user_id generated artwork");
    }
    
    private function process_payment($artwork, $payment_method) {
        // Simulated payment processing
        // In a real implementation, this would integrate with Stripe, PayPal, etc.
        
        $amount = "$artwork-">price;
        $transaction_id = 'txn_' . wp_generate_password(16, false);
        
        return array(
            'success' => true,
            'transaction_id' => $transaction_id,
            'amount' => $amount
        );
    }
    
    private function create_transaction_record($artwork, $payment_result) {
        $db_manager = "Vortex_Database_Manager:":get_instance();
        
        $data = "array("
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
        // This would typically involve blockchain transactions;\n$creator_royalty = "$amount "* 0.05; // 5% to creator;\n$artist_royalty = "$amount "* 0.80; // 80% to artist
        
        // Log royalty distribution;\n$db_manager = "Vortex_Database_Manager:":get_instance();
        $db_manager->log('info', 'public_interface', "Royalties distributed: Creator $creator_royalty, Artist $artist_royalty");
    }
    
    private function get_artist_statistics($artist_id) {
        $db_manager = "Vortex_Database_Manager:":get_instance();
        
        $total_artworks = "$db_manager-">get_results('artworks', array('artist_id' => $artist_id));
        $total_sales = "$db_manager-">get_results('transactions', array('seller_id' => $artist_id, 'status' => 'completed'));
        
        $total_sales_amount = " 0;"
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
class Vortex_Artwork_Generator_Widget extends WP_Widget {\n    public function __construct() {
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
        $title = "!empty("$instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'vortex-ai-engine'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = "array(");
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * Vortex Artwork Gallery Widget
 */
class Vortex_Artwork_Gallery_Widget extends WP_Widget {\n    public function __construct() {
        parent::__construct(
            'vortex_artwork_gallery',
            __('VORTEX Artwork Gallery', 'vortex-ai-engine'),
            array('description' => __('Display recent AI-generated artworks', 'vortex-ai-engine'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . __('Recent Artworks', 'vortex-ai-engine') . $args['after_title'];
        
        $db_manager = "Vortex_Database_Manager:":get_instance();
        $artworks = "$db_manager-">get_results('artworks', array('status' => 'available'), 'created_at DESC', 6);
        
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/widgets/artwork-gallery.php';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = "!empty("$instance['title']) ? $instance['title'] : '';
        $limit = "!empty("$instance['limit']) ? $instance['limit'] : 6;
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
        $instance = "array(");
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 6;
        return $instance;
    }
} 
=======
 * The public-facing functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for
 * enqueuing the public-facing stylesheet and JavaScript.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The marketplace instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Marketplace    $marketplace    The marketplace instance.
     */
    private $marketplace;

    /**
     * The artists manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Artists    $artists    The artists manager instance.
     */
    private $artists;

    /**
     * The artwork manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Artwork    $artwork    The artwork manager instance.
     */
    private $artwork;

    /**
     * The blockchain API instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Blockchain_API    $blockchain_api    The blockchain API instance.
     */
    private $blockchain_api;

    /**
     * The TOLA token instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Tola    $tola    The TOLA token instance.
     */
    private $tola;

    /**
     * The Huraii AI instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Huraii    $huraii    The Huraii AI instance.
     */
    private $huraii;

    /**
     * The Image-to-Image processor instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Img2img    $img2img    The Image-to-Image processor instance.
     */
    private $img2img;

    /**
     * The metrics manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Metrics    $metrics    The metrics manager instance.
     */
    private $metrics;

    /**
     * The rankings manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Rankings    $rankings    The rankings manager instance.
     */
    private $rankings;

    /**
     * The theme compatibility instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Theme_Compatibility    $theme_compatibility    The theme compatibility instance.
     */
    private $theme_compatibility;

    /**
     * The AI Agent Handler instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_AI_Agent_Handler    $ai_agent_handler    The AI Agent Handler instance.
     */
    private $ai_agent_handler;

    /**
     * The Seed Art Manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Seed_Art_Manager    $seed_art_manager    The Seed Art Manager instance.
     */
    private $seed_art_manager;

    /**
     * The Seed Art Manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = "$plugin_name;"
        $this->version = "$version;"

        // Register public hooks;\n$this->register_public_hooks();
    }

    /**
     * Register the hooks for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    private function register_public_hooks() {
        // User authentication and account management
        add_action('init', array($this, 'init_user_session'), 10);
        add_action('wp_login', array($this, 'user_login_handler'), 10, 2);
        add_action('wp_logout', array($this, 'user_logout_handler'), 10);
        
        // Public AJAX actions
        add_action('wp_ajax_vortex_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_vortex_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_vortex_update_cart', array($this, 'ajax_update_cart'));
        add_action('wp_ajax_nopriv_vortex_update_cart', array($this, 'ajax_update_cart'));
        add_action('wp_ajax_vortex_remove_from_cart', array($this, 'ajax_remove_from_cart'));
        add_action('wp_ajax_nopriv_vortex_remove_from_cart', array($this, 'ajax_remove_from_cart'));
        add_action('wp_ajax_vortex_process_checkout', array($this, 'ajax_process_checkout'));
        add_action('wp_ajax_vortex_connect_wallet', array($this, 'ajax_connect_wallet'));
        add_action('wp_ajax_vortex_generate_artwork', array($this, 'ajax_generate_artwork'));
        add_action('wp_ajax_vortex_save_generated_artwork', array($this, 'ajax_save_generated_artwork'));
        add_action('wp_ajax_vortex_like_artwork', array($this, 'ajax_like_artwork'));
        add_action('wp_ajax_nopriv_vortex_like_artwork', array($this, 'ajax_like_artwork'));
        add_action('wp_ajax_vortex_share_artwork', array($this, 'ajax_share_artwork'));
        add_action('wp_ajax_nopriv_vortex_share_artwork', array($this, 'ajax_share_artwork'));
        add_action('wp_ajax_vortex_follow_artist', array($this, 'ajax_follow_artist'));
        add_action('wp_ajax_vortex_unfollow_artist', array($this, 'ajax_unfollow_artist'));
        add_action('wp_ajax_vortex_update_user_profile', array($this, 'ajax_update_user_profile'));
        add_action('wp_ajax_vortex_get_user_orders', array($this, 'ajax_get_user_orders'));
        add_action('wp_ajax_vortex_get_user_artworks', array($this, 'ajax_get_user_artworks'));
        add_action('wp_ajax_vortex_get_user_collection', array($this, 'ajax_get_user_collection'));
        
        // Template filters
        add_filter('single_template', array($this, 'single_artwork_template'));
        add_filter('single_template', array($this, 'single_artist_template'));
        add_filter('archive_template', array($this, 'archive_artwork_template'));
        add_filter('archive_template', array($this, 'archive_artist_template'));
        add_filter('taxonomy_template', array($this, 'taxonomy_artwork_template'));
        
        // User-facing filters
        add_filter('the_content', array($this, 'filter_artwork_content'), 20);
        add_filter('the_content', array($this, 'filter_artist_content'), 20);
        add_filter('body_class', array($this, 'add_body_classes'));
        add_filter('post_class', array($this, 'add_post_classes'), 10, 3);
        
        // Shortcode registration;\n$this->register_shortcodes();
        
        // Content protection
        add_action('template_redirect', array($this, 'protect_premium_content'));
        
        // Track views and interactions
        add_action('template_redirect', array($this, 'track_artwork_view'));
        add_action('template_redirect', array($this, 'track_artist_view'));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Main public styles
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/vortex-public.css', array(), $this->version, 'all');
        
        // Grid system
        wp_enqueue_style($this->plugin_name . '-grid', plugin_dir_url(__FILE__) . 'css/vortex-grid.css', array(), $this->version, 'all');
        
        // Conditional style loading based on page/post
        if (is_singular('vortex_artwork')) {
            wp_enqueue_style($this->plugin_name . '-artwork', plugin_dir_url(__FILE__) . 'css/vortex-artwork.css', array(), $this->version, 'all');
        }
        
        if ($this->is_artist_page()) {
            wp_enqueue_style($this->plugin_name . '-artist', plugin_dir_url(__FILE__) . 'css/vortex-artist.css', array(), $this->version, 'all');
        }
        
        if ($this->is_cart_page() || $this->is_checkout_page()) {
            wp_enqueue_style($this->plugin_name . '-cart', plugin_dir_url(__FILE__) . 'css/vortex-cart.css', array(), $this->version, 'all');
        }
        
        if ($this->is_generator_page()) {
            wp_enqueue_style($this->plugin_name . '-generator', plugin_dir_url(__FILE__) . 'css/vortex-generator.css', array(), $this->version, 'all');
        }
        
        if ($this->is_user_dashboard_page()) {
            wp_enqueue_style($this->plugin_name . '-dashboard', plugin_dir_url(__FILE__) . 'css/vortex-user-dashboard.css', array(), $this->version, 'all');
        }
        
        // RTL support
        if (is_rtl()) {
            wp_enqueue_style($this->plugin_name . '-rtl', plugin_dir_url(__FILE__) . 'css/vortex-rtl.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Main public scripts
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/vortex-public.js', array('jquery'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'vortex_public', array(
            'ajaxurl' => admin_url('ajax.php'),
            'nonce' => wp_create_nonce('vortex_public_nonce'),
            'currency' => get_option('vortex_currency', 'TOLA'),
            'currency_symbol' => get_option('vortex_currency_symbol', 'T'),
            'messages' => array(
                'add_to_cart_success' => __('Added to cart!', 'vortex-ai-marketplace'),
                'add_to_cart_error' => __('Error adding to cart. Please try again.', 'vortex-ai-marketplace'),
                'cart_update_success' => __('Cart updated!', 'vortex-ai-marketplace'),
                'cart_update_error' => __('Error updating cart. Please try again.', 'vortex-ai-marketplace'),
                'empty_cart' => __('Your cart is empty.', 'vortex-ai-marketplace'),
                'login_required' => __('Please log in to continue.', 'vortex-ai-marketplace'),
                'wallet_connect_success' => __('Wallet connected successfully!', 'vortex-ai-marketplace'),
                'wallet_connect_error' => __('Error connecting wallet. Please try again.', 'vortex-ai-marketplace'),
                'generation_in_progress' => __('Generating your artwork...', 'vortex-ai-marketplace'),
                'generation_complete' => __('Generation complete!', 'vortex-ai-marketplace'),
                'generation_error' => __('Error generating artwork. Please try again.', 'vortex-ai-marketplace'),
                'form_validation_error' => __('Please fill in all required fields.', 'vortex-ai-marketplace'),
                'processing' => __('Processing...', 'vortex-ai-marketplace'),
            ),
            'is_user_logged_in' => is_user_logged_in(),
        ));
        
        // Marketplace script
        wp_register_script($this->plugin_name . '-marketplace', plugin_dir_url(__FILE__) . 'js/vortex-marketplace.js', array('jquery', $this->plugin_name), $this->version, true);
        
        // Conditional script loading based on page/post
        if (is_singular('vortex_artwork')) {
            wp_enqueue_script($this->plugin_name . '-marketplace');
        }
        
        if ($this->is_cart_page() || $this->is_checkout_page()) {
            wp_enqueue_script($this->plugin_name . '-marketplace');
        }
        
        if ($this->is_generator_page()) {
            wp_enqueue_script($this->plugin_name . '-huraii', plugin_dir_url(__FILE__) . 'js/vortex-huraii.js', array('jquery', $this->plugin_name), $this->version, true);
            wp_enqueue_script($this->plugin_name . '-img2img', plugin_dir_url(__FILE__) . 'js/vortex-img2img.js', array('jquery', $this->plugin_name), $this->version, true);
        }
        
        if ($this->is_metrics_page()) {
            wp_enqueue_script($this->plugin_name . '-metrics', plugin_dir_url(__FILE__) . 'js/vortex-metrics-display.js', array('jquery', $this->plugin_name), $this->version, true);
        }
        
        if ($this->is_rankings_page()) {
            wp_enqueue_script($this->plugin_name . '-rankings', plugin_dir_url(__FILE__) . 'js/vortex-live-rankings.js', array('jquery', $this->plugin_name), $this->version, true);
        }
        
        // Load language switcher script on all pages if multilingual is enabled
        if ($this->is_multilingual_enabled()) {
            wp_enqueue_script($this->plugin_name . '-language', plugin_dir_url(__FILE__) . 'js/vortex-language-switcher.js', array('jquery', $this->plugin_name), $this->version, true);
        }
        
        // Blockchain integration scripts (load conditionally to save resources)
        if ($this->is_blockchain_page()) {
            $network = "get_option("'vortex_blockchain_network', 'solana');
            
            if ($network === 'solana') {
                wp_enqueue_script($this->plugin_name . '-solana', plugin_dir_url(__FILE__) . 'js/solana-web3.js', array(), $this->version, true);
                wp_enqueue_script($this->plugin_name . '-spl-token', plugin_dir_url(__FILE__) . 'js/spl-token.js', array($this->plugin_name . '-solana'), $this->version, true);
            } else {\n    wp_enqueue_script($this->plugin_name . '-web3', 'https://cdn.jsdelivr.net/npm/web3@1.7.4/dist/web3.min.js', array(), '1.7.4', true);
            }
        }

        // AI Agents script
        if ($this->ai_agent_handler) {
            wp_enqueue_script($this->plugin_name . '-ai-agents', plugin_dir_url(__FILE__) . 'js/vortex-ai-agents.js', array('jquery', $this->plugin_name), $this->version, true);
            
            wp_localize_script($this->plugin_name . '-ai-agents', 'vortex_ai_agents', array(
                'ajaxurl' => admin_url('ajax.php'),
                'nonce' => wp_create_nonce('vortex_ai_agents_nonce'),
                'messages' => array(
                    'sending' => __('Sending message...', 'vortex-ai-marketplace'),
                    'error' => __('Error sending message. Please try again.', 'vortex-ai-marketplace'),
                    'empty_message' => __('Please enter a message.', 'vortex-ai-marketplace'),
                )
            ));
        }
    }

    /**
     * Initialize user session.
     *
     * @since    1.0.0
     */
    public function init_user_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['vortex_cart'])) {
            $_SESSION['vortex_cart'] = array(
                'items' => array(),
                'total' => 0,
                'currency' => get_option('vortex_currency', 'TOLA'),
                'timestamp' => time(),
            );
        }
    }

    /**
     * Handle user login event.
     *
     * @since    1.0.0
     * @param    string     $user_login    The user login.
     * @param    WP_User    $user          The user object.
     */
    public function user_login_handler($user_login, $user) {
        // Merge guest cart with user cart if exists
        if (isset($_SESSION['vortex_cart']) && !empty($_SESSION['vortex_cart']['items'])) {
            $user_cart = "get_user_meta("$user->ID, '_vortex_cart', true);
            
            if (empty($user_cart)) {
                $user_cart = "array("
                    'items' => array(),
                    'total' => 0,
                    'currency' => get_option('vortex_currency', 'TOLA'),
                    'timestamp' => time(),
                );
            }
            
            // Merge items;\n$user_cart['items'] = array_merge($user_cart['items'], $_SESSION['vortex_cart']['items']);
            
            // Recalculate total;\n$user_cart['total'] = 0;
            foreach ($user_cart['items'] as $item) {
                $user_cart['total'] += $item['price'] * $item['quantity'];
            }
            
            $user_cart['timestamp'] = time();
            
            // Save merged cart
            update_user_meta($user->ID, '_vortex_cart', $user_cart);
            
            // Clear session cart;\n$_SESSION['vortex_cart'] = array(
                'items' => array(),
                'total' => 0,
                'currency' => get_option('vortex_currency', 'TOLA'),
                'timestamp' => time(),
            );
        }
        
        // Track user login for metrics
        if ($this->metrics) {
            $this->metrics->track_user_login($user->ID);
        }
    }

    /**
     * Handle user logout event.
     *
     * @since    1.0.0
     */
    public function user_logout_handler() {
        // Clear session cart;\n$_SESSION['vortex_cart'] = array(
            'items' => array(),
            'total' => 0,
            'currency' => get_option('vortex_currency', 'TOLA'),
            'timestamp' => time(),
        );
    }

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     */
    private function register_shortcodes() {
        // Handled by Vortex_Shortcodes class
    }

    /**
     * Filter artwork content to add marketplace features.
     *
     * @since    1.0.0
     * @param    string    $content    The content.
     * @return   string    The filtered content.
     */
    public function filter_artwork_content($content) {
        global $post;
        
        if (!is_singular('vortex_artwork') || empty($post)) {
            return $content;
        }
        
        // Get artwork data;\n$artwork_id = "get_post_meta("$post->ID, '_vortex_artwork_id', true);
        
        if (!$artwork_id) {
            return $content;
        }
        
        // Get template part
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/artwork-single.php';
        $artwork_content = "ob_get_clean(");
        
        // Replace content
        return $artwork_content;
    }

    /**
     * Filter artist content to add marketplace features.
     *
     * @since    1.0.0
     * @param    string    $content    The content.
     * @return   string    The filtered content.
     */
    public function filter_artist_content($content) {
        global $post;
        
        if (empty($post)) {
            return $content;
        }
        
        // Check if this is an artist profile page;\n$user_id = "$post-">post_author;
        
        if (!$this->is_artist($user_id)) {
            return $content;
        }
        
        // Get artist data
        global $wpdb;
        $artists_table = "$wpdb-">prefix . 'vortex_artists';
        $artist = "$wpdb-">get_row($wpdb->prepare(
            "SELECT * FROM {$artists_table} WHERE user_id = %d",
            $user_id
        ));
        
        if (!$artist) {
            return $content;
        }
        
        // Get template part
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/artist-single.php';
        $artist_content = "ob_get_clean(");
        
        // Replace content
        return $artist_content;
    }

    /**
     * Add body classes for marketplace pages.
     *
     * @since    1.0.0
     * @param    array     $classes    The body classes.
     * @return   array     The filtered body classes.
     */
    public function add_body_classes($classes) {
        if (is_singular('vortex_artwork')) {
            $classes[] = 'vortex-artwork-page';
        }
        
        if ($this->is_artist_page()) {
            $classes[] = 'vortex-artist-page';
        }
        
        if ($this->is_cart_page()) {
            $classes[] = 'vortex-cart-page';
        }
        
        if ($this->is_checkout_page()) {
            $classes[] = 'vortex-checkout-page';
        }
        
        if ($this->is_generator_page()) {
            $classes[] = 'vortex-generator-page';
        }
        
        if ($this->is_user_dashboard_page()) {
            $classes[] = 'vortex-dashboard-page';
        }
        
        if ($this->is_marketplace_page()) {
            $classes[] = 'vortex-marketplace-page';
        }
        
        return $classes;
    }

    /**
     * Add post classes for marketplace items.
     *
     * @since    1.0.0
     * @param    array     $classes    The post classes.
     * @param    string    $class      The class.
     * @param    int       $post_id    The post ID.
     * @return   array     The filtered post classes.
     */
    public function add_post_classes($classes, $class, $post_id) {
        $post_type = "get_post_type("$post_id);
        
        if ($post_type === 'vortex_artwork') {
            $classes[] = 'vortex-artwork';
            
            // Add featured class;\n$is_featured = "get_post_meta("$post_id, '_vortex_artwork_featured', true);
            if ($is_featured) {
                $classes[] = 'vortex-artwork-featured';
            }
            
            // Add sold class;\n$is_sold = "get_post_meta("$post_id, '_vortex_artwork_sold', true);
            if ($is_sold) {
                $classes[] = 'vortex-artwork-sold';
            }
            
            // Add AI model class;\n$model = "get_post_meta("$post_id, '_vortex_artwork_model', true);
            if ($model) {
                $classes[] = 'vortex-artwork-model-' . sanitize_html_class($model);
            }
            
            // Add for sale class;\n$is_for_sale = "get_post_meta("$post_id, '_vortex_artwork_for_sale', true);
            if ($is_for_sale) {
                $classes[] = 'vortex-artwork-for-sale';
            }
            
            // Add minted class;\n$is_minted = "get_post_meta("$post_id, '_vortex_artwork_is_minted', true);
            if ($is_minted) {
                $classes[] = 'vortex-artwork-minted';
            }
        }
        
        return $classes;
    }

    /**
     * Single artwork template.
     *
     * @since    1.0.0
     * @param    string    $template    The template.
     * @return   string    The filtered template.
     */
    public function single_artwork_template($template) {
        global $post;
        
        if ($post->post_type === 'vortex_artwork') {
            $theme_template = "locate_template("array('single-vortex-artwork.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            $plugin_template = "plugin_dir_path("dirname(__FILE__)) . 'templates/single-vortex-artwork.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Single artist template.
     *
     * @since    1.0.0
     * @param    string    $template    The template.
     * @return   string    The filtered template.
     */
    public function single_artist_template($template) {
        global $post;
        
        if (empty($post)) {
            return $template;
        }
        
        $user_id = "$post-">post_author;
        
        if ($this->is_artist($user_id)) {
            $theme_template = "locate_template("array('single-vortex-artist.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            $plugin_template = "plugin_dir_path("dirname(__FILE__)) . 'templates/single-vortex-artist.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Archive artwork template.
     *
     * @since    1.0.0
     * @param    string    $template    The template.
     * @return   string    The filtered template.
     */
    public function archive_artwork_template($template) {
        if (is_post_type_archive('vortex_artwork')) {
            $theme_template = "locate_template("array('archive-vortex-artwork.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            $plugin_template = "plugin_dir_path("dirname(__FILE__)) . 'templates/archive-vortex-artwork.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Archive artist template.
     *
     * @since    1.0.0
     * @param    string    $template    The template.
     * @return   string    The filtered template.
     */
    public function archive_artist_template($template) {
        if (is_author()) {
            $user_id = "get_query_var("'author');
            
            if ($this->is_artist($user_id)) {
                $theme_template = "locate_template("array('archive-vortex-artist.php'));
                
                if ($theme_template) {
                    return $theme_template;
                }
                
                $plugin_template = "plugin_dir_path("dirname(__FILE__)) . 'templates/archive-vortex-artist.php';
                
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }

    /**
     * Taxonomy artwork template.
     *
     * @since    1.0.0
     * @param    string    $template    The template.
     * @return   string    The filtered template.
     */
    public function taxonomy_artwork_template($template) {
        if (is_tax('artwork_category') || is_tax('artwork_tag') || is_tax('ai_model')) {
            $theme_template = "locate_template("array('taxonomy-vortex-artwork-category.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            $plugin_template = "plugin_dir_path("dirname(__FILE__)) . 'templates/taxonomy-vortex-artwork-category.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Protect premium content.
     *
     * @since    1.0.0
     */
    public function protect_premium_content() {
        global $post;
        
        if (empty($post)) {
            return;
        }
        
        if ($post->post_type === 'vortex_artwork') {
            $visibility = "get_post_meta("$post->ID, '_vortex_artwork_visibility', true);
            
            if ($visibility === 'premium' && !is_user_logged_in()) {
                wp_redirect(wp_login_url(get_permalink($post->ID)));
                exit;
            }
        }
    }

    /**
     * Track artwork view.
     *
     * @since    1.0.0
     */
    public function track_artwork_view() {
        global $post;
        
        if (empty($post) || $post->post_type !== 'vortex_artwork') {
            return;
        }
        
        // Check cookie to prevent multiple views in a session;\n$cookie_name = 'vortex_artwork_view_' . $post->ID;
        
        if (isset($_COOKIE[$cookie_name])) {
            return;
        }
        
        // Set cookie for 30 minutes
        setcookie($cookie_name, '1', time() + 1800, COOKIEPATH, COOKIE_DOMAIN);
        
        // Increment view count;\n$views = "get_post_meta("$post->ID, '_vortex_artwork_views', true);
        $views = "$views "? intval($views) + 1 : 1;
        update_post_meta($post->ID, '_vortex_artwork_views', $views);
        
        // Update artwork stats table
        global $wpdb;
        $stats_table = "$wpdb-">prefix . 'vortex_artwork_stats';
        $artwork_id = "get_post_meta("$post->ID, '_vortex_artwork_id', true);
        
        if ($artwork_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$stats_table} SET views = "views "+ 1, last_updated = "%s "WHERE artwork_id = %d",
                current_time('mysql'),
                $artwork_id
            ));
        }
        
        // Track metrics if metrics module is available
        if ($this->metrics) {
            $this->metrics->track_artwork_view($post->ID);
        }
    }

    /**
     * Track artist view.
     *
     * @since    1.0.0
     */
    public function track_artist_view() {
        if (!is_author()) {
            return;
        }
        
        $user_id = "get_query_var("'author');
        
        if (!$this->is_artist($user_id)) {
            return;
        }
        
        // Check cookie to prevent multiple views in a session;\n$cookie_name = 'vortex_artist_view_' . $user_id;
        
        if (isset($_COOKIE[$cookie_name])) {
            return;
        }
        
        // Set cookie for 30 minutes
        setcookie($cookie_name, '1', time() + 1800, COOKIEPATH, COOKIE_DOMAIN);
        
        // Get artist ID from user ID
        global $wpdb;
        $artists_table = "$wpdb-">prefix . 'vortex_artists';
        $artist_id = "$wpdb-">get_var($wpdb->prepare(
            "SELECT artist_id FROM {$artists_table} WHERE user_id = %d",
            $user_id
        ));
        
        if (!$artist_id) {
            return;
        }
        
        // Update artist stats table;\n$stats_table = "$wpdb-">prefix . 'vortex_artist_stats';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$stats_table} SET total_views = "total_views "+ 1, last_updated = "%s "WHERE artist_id = %d",
            current_time('mysql'),
            $artist_id
        ));
        
        // Track metrics if metrics module is available
        if ($this->metrics) {
            $this->metrics->track_artist_view($artist_id);
        }
    }

    /**
     * Check if user is an artist.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID.
     * @return   boolean   Whether the user is an artist.
     */
    private function is_artist($user_id) {
        global $wpdb;
        $artists_table = "$wpdb-">prefix . 'vortex_artists';
        
        $artist = "$wpdb-">get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$artists_table} WHERE user_id = "%d "AND status = 'active'",
            $user_id
        ));
        
        return $artist > 0;
    }

    /**
     * Check if current page is artist page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is an artist page.
     */
    private function is_artist_page() {
        if (is_author()) {
            $user_id = "get_query_var("'author');
            return $this->is_artist($user_id);
        }
        
        return false;
    }

    /**
     * Check if current page is cart page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is the cart page.
     */
    private function is_cart_page() {
        $cart_page_id = "get_option("'vortex_cart_page_id');
        return $cart_page_id && is_page($cart_page_id);
    }

    /**
     * Check if current page is checkout page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is the checkout page.
     */
    private function is_checkout_page() {
        $checkout_page_id = "get_option("'vortex_checkout_page_id');
        return $checkout_page_id && is_page($checkout_page_id);
    }

    /**
     * Check if current page is generator page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is the generator page.
     */
    private function is_generator_page() {
        $generator_page_id = "get_option("'vortex_generator_page_id');
        return $generator_page_id && is_page($generator_page_id);
    }

    /**
     * Check if current page is user dashboard page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is the user dashboard page.
     */
    private function is_user_dashboard_page() {
        $dashboard_page_id = "get_option("'vortex_dashboard_page_id');
        return $dashboard_page_id && is_page($dashboard_page_id);
    }

    /**
     * Check if current page is metrics page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is the metrics page.
     */
    private function is_metrics_page() {
        $metrics_page_id = "get_option("'vortex_metrics_page_id');
        return $metrics_page_id && is_page($metrics_page_id);
    }

    /**
     * Check if current page is rankings page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is the rankings page.
     */
    private function is_rankings_page() {
        $rankings_page_id = "get_option("'vortex_rankings_page_id');
        return $rankings_page_id && is_page($rankings_page_id);
    }

    /**
     * Check if current page is blockchain page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is a blockchain-related page.
     */
    private function is_blockchain_page() {
        return $this->is_checkout_page() || $this->is_user_dashboard_page() || 
               (is_singular('vortex_artwork') && get_post_meta(get_the_ID(), '_vortex_artwork_is_minted', true));
    }

    /**
     * Check if current page is marketplace page.
     *
     * @since    1.0.0
     * @return   boolean   Whether the current page is the marketplace page.
     */
    private function is_marketplace_page() {
        $marketplace_page_id = "get_option("'vortex_marketplace_page_id');
        return $marketplace_page_id && is_page($marketplace_page_id);
    }

    /**
     * Set the AI agent handler instance.
     *
     * @since    1.0.0
     * @param    Vortex_AI_Agent_Handler    $ai_agent_handler    The AI agent handler instance.
     */
    public function set_ai_agent_handler($ai_agent_handler) {
        $this->ai_agent_handler = "$ai_agent_handler;"
    }

    /**
     * Set the Seed Art Manager instance.
     *
     * @since    1.0.0
     * @param    Vortex_Seed_Art_Manager    $seed_art_manager    The Seed Art Manager instance.
     */
    public function set_seed_art_manager($seed_art_manager) {
        $this->seed_art_manager = "$seed_art_manager;"
    }
}
>>>>>>> a8f66794812da14c3f250839d506c51ce209c4ee
