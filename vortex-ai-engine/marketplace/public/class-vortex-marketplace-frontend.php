<?php
/**
 * Vortex Marketplace Frontend
 * 
 * Handles marketplace-specific frontend functionality for the VORTEX AI Engine plugin
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
 * Vortex Marketplace Frontend Class
 */
class Vortex_Marketplace_Frontend {
    
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_marketplace_scripts'));
        add_action('wp_ajax_vortex_search_artworks', array($this, 'ajax_search_artworks'));
        add_action('wp_ajax_nopriv_vortex_search_artworks', array($this, 'ajax_search_artworks'));
        add_action('wp_ajax_vortex_filter_artworks', array($this, 'ajax_filter_artworks'));
        add_action('wp_ajax_nopriv_vortex_filter_artworks', array($this, 'ajax_filter_artworks'));
        add_action('wp_ajax_vortex_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_vortex_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_vortex_remove_from_cart', array($this, 'ajax_remove_from_cart'));
        add_action('wp_ajax_nopriv_vortex_remove_from_cart', array($this, 'ajax_remove_from_cart'));
        add_action('wp_ajax_vortex_checkout', array($this, 'ajax_checkout'));
        add_action('wp_ajax_nopriv_vortex_checkout', array($this, 'ajax_checkout'));
        add_action('wp_ajax_vortex_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_nopriv_vortex_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_vortex_bid_on_artwork', array($this, 'ajax_bid_on_artwork'));
        add_action('wp_ajax_nopriv_vortex_bid_on_artwork', array($this, 'ajax_bid_on_artwork'));
        add_action('wp_ajax_vortex_follow_artist', array($this, 'ajax_follow_artist'));
        add_action('wp_ajax_nopriv_vortex_follow_artist', array($this, 'ajax_follow_artist'));
        
        // Shortcodes
        add_shortcode('vortex_marketplace_home', array($this, 'marketplace_home_shortcode'));
        add_shortcode('vortex_artwork_detail', array($this, 'artwork_detail_shortcode'));
        add_shortcode('vortex_artist_marketplace', array($this, 'artist_marketplace_shortcode'));
        add_shortcode('vortex_auction_house', array($this, 'auction_house_shortcode'));
        add_shortcode('vortex_shopping_cart', array($this, 'shopping_cart_shortcode'));
        
        // Custom post types
        add_action('init', array($this, 'register_custom_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        
        // Template hooks
        add_filter('template_include', array($this, 'load_marketplace_templates'));
    }
    
    /**
     * Enqueue marketplace scripts
     */
    public function enqueue_marketplace_scripts() {
        wp_enqueue_script(
            'vortex-marketplace-js',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'public/js/vortex-marketplace.js',
            array('jquery'),
            VORTEX_AI_ENGINE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vortex-marketplace-css',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'public/css/vortex-marketplace.css',
            array(),
            VORTEX_AI_ENGINE_VERSION
        );
        
        // Localize script
        wp_localize_script('vortex-marketplace-js', 'vortex_marketplace', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_marketplace_nonce'),
            'user_id' => get_current_user_id(),
            'currency' => 'USD',
            'strings' => array(
                'searching' => __('Searching artworks...', 'vortex-ai-engine'),
                'adding_to_cart' => __('Adding to cart...', 'vortex-ai-engine'),
                'checking_out' => __('Processing checkout...', 'vortex-ai-engine'),
                'bidding' => __('Placing bid...', 'vortex-ai-engine'),
                'following' => __('Following artist...', 'vortex-ai-engine'),
                'success' => __('Operation completed successfully!', 'vortex-ai-engine'),
                'error' => __('An error occurred. Please try again.', 'vortex-ai-engine'),
                'cart_updated' => __('Cart updated successfully!', 'vortex-ai-engine'),
                'bid_placed' => __('Bid placed successfully!', 'vortex-ai-engine'),
                'artist_followed' => __('Artist followed successfully!', 'vortex-ai-engine')
            )
        ));
    }
    
    /**
     * Register custom post types
     */
    public function register_custom_post_types() {
        // Artwork post type
        register_post_type('vortex_artwork', array(
            'labels' => array(
                'name' => __('Artworks', 'vortex-ai-engine'),
                'singular_name' => __('Artwork', 'vortex-ai-engine'),
                'add_new' => __('Add New Artwork', 'vortex-ai-engine'),
                'add_new_item' => __('Add New Artwork', 'vortex-ai-engine'),
                'edit_item' => __('Edit Artwork', 'vortex-ai-engine'),
                'new_item' => __('New Artwork', 'vortex-ai-engine'),
                'view_item' => __('View Artwork', 'vortex-ai-engine'),
                'search_items' => __('Search Artworks', 'vortex-ai-engine'),
                'not_found' => __('No artworks found', 'vortex-ai-engine'),
                'not_found_in_trash' => __('No artworks found in trash', 'vortex-ai-engine')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-art',
            'rewrite' => array('slug' => 'artwork'),
            'show_in_rest' => true
        ));
        
        // Artist post type
        register_post_type('vortex_artist', array(
            'labels' => array(
                'name' => __('Artists', 'vortex-ai-engine'),
                'singular_name' => __('Artist', 'vortex-ai-engine'),
                'add_new' => __('Add New Artist', 'vortex-ai-engine'),
                'add_new_item' => __('Add New Artist', 'vortex-ai-engine'),
                'edit_item' => __('Edit Artist', 'vortex-ai-engine'),
                'new_item' => __('New Artist', 'vortex-ai-engine'),
                'view_item' => __('View Artist', 'vortex-ai-engine'),
                'search_items' => __('Search Artists', 'vortex-ai-engine'),
                'not_found' => __('No artists found', 'vortex-ai-engine'),
                'not_found_in_trash' => __('No artists found in trash', 'vortex-ai-engine')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-admin-users',
            'rewrite' => array('slug' => 'artist'),
            'show_in_rest' => true
        ));
        
        // Auction post type
        register_post_type('vortex_auction', array(
            'labels' => array(
                'name' => __('Auctions', 'vortex-ai-engine'),
                'singular_name' => __('Auction', 'vortex-ai-engine'),
                'add_new' => __('Add New Auction', 'vortex-ai-engine'),
                'add_new_item' => __('Add New Auction', 'vortex-ai-engine'),
                'edit_item' => __('Edit Auction', 'vortex-ai-engine'),
                'new_item' => __('New Auction', 'vortex-ai-engine'),
                'view_item' => __('View Auction', 'vortex-ai-engine'),
                'search_items' => __('Search Auctions', 'vortex-ai-engine'),
                'not_found' => __('No auctions found', 'vortex-ai-engine'),
                'not_found_in_trash' => __('No auctions found in trash', 'vortex-ai-engine')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-hammer',
            'rewrite' => array('slug' => 'auction'),
            'show_in_rest' => true
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Artwork categories
        register_taxonomy('vortex_artwork_category', 'vortex_artwork', array(
            'labels' => array(
                'name' => __('Artwork Categories', 'vortex-ai-engine'),
                'singular_name' => __('Artwork Category', 'vortex-ai-engine'),
                'search_items' => __('Search Categories', 'vortex-ai-engine'),
                'all_items' => __('All Categories', 'vortex-ai-engine'),
                'parent_item' => __('Parent Category', 'vortex-ai-engine'),
                'parent_item_colon' => __('Parent Category:', 'vortex-ai-engine'),
                'edit_item' => __('Edit Category', 'vortex-ai-engine'),
                'update_item' => __('Update Category', 'vortex-ai-engine'),
                'add_new_item' => __('Add New Category', 'vortex-ai-engine'),
                'new_item_name' => __('New Category Name', 'vortex-ai-engine'),
                'menu_name' => __('Categories', 'vortex-ai-engine')
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'artwork-category'),
            'show_in_rest' => true
        ));
        
        // Artwork styles
        register_taxonomy('vortex_artwork_style', 'vortex_artwork', array(
            'labels' => array(
                'name' => __('Artwork Styles', 'vortex-ai-engine'),
                'singular_name' => __('Artwork Style', 'vortex-ai-engine'),
                'search_items' => __('Search Styles', 'vortex-ai-engine'),
                'all_items' => __('All Styles', 'vortex-ai-engine'),
                'edit_item' => __('Edit Style', 'vortex-ai-engine'),
                'update_item' => __('Update Style', 'vortex-ai-engine'),
                'add_new_item' => __('Add New Style', 'vortex-ai-engine'),
                'new_item_name' => __('New Style Name', 'vortex-ai-engine'),
                'menu_name' => __('Styles', 'vortex-ai-engine')
            ),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'artwork-style'),
            'show_in_rest' => true
        ));
    }
    
    /**
     * Load marketplace templates
     */
    public function load_marketplace_templates($template) {
        if (is_post_type_archive('vortex_artwork')) {
            $new_template = VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/archive-artwork.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        
        if (is_singular('vortex_artwork')) {
            $new_template = VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/single-artwork.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        
        if (is_post_type_archive('vortex_artist')) {
            $new_template = VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/archive-artist.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        
        if (is_singular('vortex_artist')) {
            $new_template = VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/single-artist.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        
        return $template;
    }
    
    /**
     * AJAX: Search artworks
     */
    public function ajax_search_artworks() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $page = intval($_POST['page']) ?: 1;
        $per_page = intval($_POST['per_page']) ?: 12;
        
        $db_manager = Vortex_Database_Manager::get_instance();
        
        // Search in artworks table
        $artworks = $db_manager->get_results(
            'artworks',
            array('status' => 'available'),
            'created_at DESC',
            "$per_page OFFSET " . (($page - 1) * $per_page)
        );
        
        // Filter by search term if provided
        if (!empty($search_term)) {
            $filtered_artworks = array();
            foreach ($artworks as $artwork) {
                if (stripos($artwork->title, $search_term) !== false || 
                    stripos($artwork->description, $search_term) !== false ||
                    stripos($artwork->prompt, $search_term) !== false) {
                    $filtered_artworks[] = $artwork;
                }
            }
            $artworks = $filtered_artworks;
        }
        
        wp_send_json_success(array(
            'artworks' => $artworks,
            'total' => count($artworks),
            'page' => $page,
            'per_page' => $per_page
        ));
    }
    
    /**
     * AJAX: Filter artworks
     */
    public function ajax_filter_artworks() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category']);
        $style = sanitize_text_field($_POST['style']);
        $price_min = floatval($_POST['price_min']);
        $price_max = floatval($_POST['price_max']);
        $artist_id = intval($_POST['artist_id']);
        $page = intval($_POST['page']) ?: 1;
        $per_page = intval($_POST['per_page']) ?: 12;
        
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $where = array('status' => 'available');
        
        if (!empty($artist_id)) {
            $where['artist_id'] = $artist_id;
        }
        
        if (!empty($price_min)) {
            $where['price_min'] = $price_min;
        }
        
        if (!empty($price_max)) {
            $where['price_max'] = $price_max;
        }
        
        $artworks = $db_manager->get_results(
            'artworks',
            $where,
            'created_at DESC',
            "$per_page OFFSET " . (($page - 1) * $per_page)
        );
        
        // Apply additional filters
        $filtered_artworks = array();
        foreach ($artworks as $artwork) {
            $metadata = json_decode($artwork->metadata, true);
            
            $include = true;
            
            if (!empty($category) && (!isset($metadata['category']) || $metadata['category'] !== $category)) {
                $include = false;
            }
            
            if (!empty($style) && (!isset($metadata['style']) || $metadata['style'] !== $style)) {
                $include = false;
            }
            
            if (!empty($price_min) && $artwork->price < $price_min) {
                $include = false;
            }
            
            if (!empty($price_max) && $artwork->price > $price_max) {
                $include = false;
            }
            
            if ($include) {
                $filtered_artworks[] = $artwork;
            }
        }
        
        wp_send_json_success(array(
            'artworks' => $filtered_artworks,
            'total' => count($filtered_artworks),
            'page' => $page,
            'per_page' => $per_page
        ));
    }
    
    /**
     * AJAX: Add to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        $artwork_id = intval($_POST['artwork_id']);
        
        if (empty($artwork_id)) {
            wp_send_json_error(__('Artwork ID is required', 'vortex-ai-engine'));
        }
        
        // Get cart from session
        $cart = $this->get_cart();
        
        // Check if artwork is already in cart
        if (in_array($artwork_id, $cart)) {
            wp_send_json_error(__('Artwork is already in cart', 'vortex-ai-engine'));
        }
        
        // Add to cart
        $cart[] = $artwork_id;
        $this->set_cart($cart);
        
        wp_send_json_success(array(
            'message' => __('Artwork added to cart', 'vortex-ai-engine'),
            'cart_count' => count($cart)
        ));
    }
    
    /**
     * AJAX: Remove from cart
     */
    public function ajax_remove_from_cart() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        $artwork_id = intval($_POST['artwork_id']);
        
        if (empty($artwork_id)) {
            wp_send_json_error(__('Artwork ID is required', 'vortex-ai-engine'));
        }
        
        // Get cart from session
        $cart = $this->get_cart();
        
        // Remove from cart
        $cart = array_diff($cart, array($artwork_id));
        $this->set_cart($cart);
        
        wp_send_json_success(array(
            'message' => __('Artwork removed from cart', 'vortex-ai-engine'),
            'cart_count' => count($cart)
        ));
    }
    
    /**
     * AJAX: Get cart
     */
    public function ajax_get_cart() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        $cart = $this->get_cart();
        $cart_items = array();
        $total = 0;
        
        if (!empty($cart)) {
            $db_manager = Vortex_Database_Manager::get_instance();
            
            foreach ($cart as $artwork_id) {
                $artwork = $db_manager->get_row('artworks', array('id' => $artwork_id));
                if ($artwork && $artwork->status === 'available') {
                    $cart_items[] = $artwork;
                    $total += $artwork->price;
                }
            }
        }
        
        wp_send_json_success(array(
            'items' => $cart_items,
            'total' => $total,
            'count' => count($cart_items)
        ));
    }
    
    /**
     * AJAX: Checkout
     */
    public function ajax_checkout() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to checkout', 'vortex-ai-engine'));
        }
        
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $cart = $this->get_cart();
        
        if (empty($cart)) {
            wp_send_json_error(__('Cart is empty', 'vortex-ai-engine'));
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $total = 0;
        $purchases = array();
        
        // Process each item in cart
        foreach ($cart as $artwork_id) {
            $artwork = $db_manager->get_row('artworks', array('id' => $artwork_id));
            
            if (!$artwork || $artwork->status !== 'available') {
                wp_send_json_error(__('One or more artworks are no longer available', 'vortex-ai-engine'));
            }
            
            // Check if user is trying to buy their own artwork
            if ($artwork->artist_id == get_current_user_id()) {
                wp_send_json_error(__('You cannot purchase your own artwork', 'vortex-ai-engine'));
            }
            
            $total += $artwork->price;
            $purchases[] = $artwork;
        }
        
        // Process payment
        $payment_result = $this->process_bulk_payment($purchases, $payment_method);
        
        if ($payment_result['success']) {
            // Create transaction records
            foreach ($purchases as $artwork) {
                $this->create_transaction_record($artwork, $payment_result['transaction_id']);
                $db_manager->update('artworks', array('status' => 'sold'), array('id' => $artwork->id));
            }
            
            // Clear cart
            $this->set_cart(array());
            
            wp_send_json_success(array(
                'message' => __('Checkout completed successfully!', 'vortex-ai-engine'),
                'transaction_id' => $payment_result['transaction_id']
            ));
        } else {
            wp_send_json_error($payment_result['error']);
        }
    }
    
    /**
     * AJAX: Bid on artwork
     */
    public function ajax_bid_on_artwork() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to place a bid', 'vortex-ai-engine'));
        }
        
        $artwork_id = intval($_POST['artwork_id']);
        $bid_amount = floatval($_POST['bid_amount']);
        
        if (empty($artwork_id) || empty($bid_amount)) {
            wp_send_json_error(__('Artwork ID and bid amount are required', 'vortex-ai-engine'));
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artwork = $db_manager->get_row('artworks', array('id' => $artwork_id));
        
        if (!$artwork) {
            wp_send_json_error(__('Artwork not found', 'vortex-ai-engine'));
        }
        
        if ($artwork->status !== 'auction') {
            wp_send_json_error(__('Artwork is not available for bidding', 'vortex-ai-engine'));
        }
        
        // Check if bid is higher than current highest bid
        $current_bid = $this->get_highest_bid($artwork_id);
        if ($bid_amount <= $current_bid) {
            wp_send_json_error(__('Bid must be higher than current highest bid', 'vortex-ai-engine'));
        }
        
        // Place bid
        $bid_result = $this->place_bid($artwork_id, $bid_amount);
        
        if ($bid_result['success']) {
            wp_send_json_success(array(
                'message' => __('Bid placed successfully!', 'vortex-ai-engine'),
                'bid_id' => $bid_result['bid_id']
            ));
        } else {
            wp_send_json_error($bid_result['error']);
        }
    }
    
    /**
     * AJAX: Follow artist
     */
    public function ajax_follow_artist() {
        check_ajax_referer('vortex_marketplace_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to follow artists', 'vortex-ai-engine'));
        }
        
        $artist_id = intval($_POST['artist_id']);
        
        if (empty($artist_id)) {
            wp_send_json_error(__('Artist ID is required', 'vortex-ai-engine'));
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artist = $db_manager->get_row('artists', array('id' => $artist_id));
        
        if (!$artist) {
            wp_send_json_error(__('Artist not found', 'vortex-ai-engine'));
        }
        
        // Check if already following
        $following = $this->is_following_artist($artist_id);
        
        if ($following) {
            // Unfollow
            $this->unfollow_artist($artist_id);
            $message = __('Artist unfollowed', 'vortex-ai-engine');
            $following = false;
        } else {
            // Follow
            $this->follow_artist($artist_id);
            $message = __('Artist followed', 'vortex-ai-engine');
            $following = true;
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'following' => $following
        ));
    }
    
    /**
     * Shortcode: Marketplace home
     */
    public function marketplace_home_shortcode($atts) {
        $atts = shortcode_atts(array(
            'featured_count' => 6,
            'trending_count' => 6,
            'recent_count' => 12
        ), $atts);
        
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $featured_artworks = $db_manager->get_results('artworks', array('status' => 'available'), 'created_at DESC', $atts['featured_count']);
        $trending_artworks = $db_manager->get_results('artworks', array('status' => 'available'), 'created_at DESC', $atts['trending_count']);
        $recent_artworks = $db_manager->get_results('artworks', array('status' => 'available'), 'created_at DESC', $atts['recent_count']);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/marketplace-home.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Artwork detail
     */
    public function artwork_detail_shortcode($atts) {
        $atts = shortcode_atts(array(
            'artwork_id' => 0
        ), $atts);
        
        if (empty($atts['artwork_id'])) {
            return '<p>' . __('Artwork ID is required', 'vortex-ai-engine') . '</p>';
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artwork = $db_manager->get_row('artworks', array('id' => $atts['artwork_id']));
        
        if (!$artwork) {
            return '<p>' . __('Artwork not found', 'vortex-ai-engine') . '</p>';
        }
        
        $artist = $db_manager->get_row('artists', array('id' => $artwork->artist_id));
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/artwork-detail.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Artist marketplace
     */
    public function artist_marketplace_shortcode($atts) {
        $atts = shortcode_atts(array(
            'artist_id' => 0,
            'limit' => 12
        ), $atts);
        
        if (empty($atts['artist_id'])) {
            return '<p>' . __('Artist ID is required', 'vortex-ai-engine') . '</p>';
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $artist = $db_manager->get_row('artists', array('id' => $atts['artist_id']));
        
        if (!$artist) {
            return '<p>' . __('Artist not found', 'vortex-ai-engine') . '</p>';
        }
        
        $artworks = $db_manager->get_results('artworks', array('artist_id' => $atts['artist_id'], 'status' => 'available'), 'created_at DESC', $atts['limit']);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/artist-marketplace.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Auction house
     */
    public function auction_house_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12
        ), $atts);
        
        $db_manager = Vortex_Database_Manager::get_instance();
        $auctions = $db_manager->get_results('artworks', array('status' => 'auction'), 'created_at DESC', $atts['limit']);
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/auction-house.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Shopping cart
     */
    public function shopping_cart_shortcode($atts) {
        $cart = $this->get_cart();
        $cart_items = array();
        $total = 0;
        
        if (!empty($cart)) {
            $db_manager = Vortex_Database_Manager::get_instance();
            
            foreach ($cart as $artwork_id) {
                $artwork = $db_manager->get_row('artworks', array('id' => $artwork_id));
                if ($artwork && $artwork->status === 'available') {
                    $cart_items[] = $artwork;
                    $total += $artwork->price;
                }
            }
        }
        
        ob_start();
        include VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/templates/shopping-cart.php';
        return ob_get_clean();
    }
    
    /**
     * Helper methods
     */
    private function get_cart() {
        if (!isset($_SESSION['vortex_cart'])) {
            $_SESSION['vortex_cart'] = array();
        }
        return $_SESSION['vortex_cart'];
    }
    
    private function set_cart($cart) {
        $_SESSION['vortex_cart'] = $cart;
    }
    
    private function process_bulk_payment($purchases, $payment_method) {
        // Simulated bulk payment processing
        $total = 0;
        foreach ($purchases as $artwork) {
            $total += $artwork->price;
        }
        
        $transaction_id = 'bulk_txn_' . wp_generate_password(16, false);
        
        return array(
            'success' => true,
            'transaction_id' => $transaction_id,
            'amount' => $total
        );
    }
    
    private function create_transaction_record($artwork, $transaction_id) {
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $data = array(
            'transaction_hash' => $transaction_id,
            'artwork_id' => $artwork->id,
            'seller_id' => $artwork->artist_id,
            'buyer_id' => get_current_user_id(),
            'amount' => $artwork->price,
            'marketplace_fee' => $artwork->price * 0.15,
            'creator_royalty' => $artwork->price * 0.05,
            'artist_royalty' => $artwork->price * 0.80,
            'transaction_type' => 'sale',
            'status' => 'completed'
        );
        
        return $db_manager->insert('transactions', $data);
    }
    
    private function get_highest_bid($artwork_id) {
        // This would typically query a bids table
        // For now, return a simulated value
        return 0;
    }
    
    private function place_bid($artwork_id, $bid_amount) {
        // This would typically insert into a bids table
        // For now, return success
        return array(
            'success' => true,
            'bid_id' => 'bid_' . wp_generate_password(16, false)
        );
    }
    
    private function is_following_artist($artist_id) {
        // This would typically query a follows table
        // For now, return false
        return false;
    }
    
    private function follow_artist($artist_id) {
        // This would typically insert into a follows table
        // For now, just log it
        $db_manager = Vortex_Database_Manager::get_instance();
        $db_manager->log('info', 'marketplace_frontend', "User " . get_current_user_id() . " followed artist $artist_id");
    }
    
    private function unfollow_artist($artist_id) {
        // This would typically delete from a follows table
        // For now, just log it
        $db_manager = Vortex_Database_Manager::get_instance();
        $db_manager->log('info', 'marketplace_frontend', "User " . get_current_user_id() . " unfollowed artist $artist_id");
    }
} 