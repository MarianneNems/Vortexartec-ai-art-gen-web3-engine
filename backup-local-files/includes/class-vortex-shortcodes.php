<?php
/**
 * VORTEX AI Engine - Shortcodes
 * Manages all frontend shortcodes for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VortexAIEngine_Shortcodes')) {
class VortexAIEngine_Shortcodes {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_shortcodes();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    private function register_shortcodes() {
        // Core shortcodes
        add_shortcode('vortex_swap', [$this, 'render_swap']);
        add_shortcode('vortex_wallet', [$this, 'render_wallet']);
        add_shortcode('vortex_metric', [$this, 'render_metric']);
        add_shortcode('vortex_ai_chat', [$this, 'render_ai_chat']);
        add_shortcode('vortex_huraii', [$this, 'render_huraii_dashboard']);
        
        // NFT shortcodes
        add_shortcode('tola_mint_status', [$this, 'render_mint_status']);
        add_shortcode('tola_royalty_manager', [$this, 'render_royalty_manager']);
        add_shortcode('tola_nft_gallery', [$this, 'render_nft_gallery']);
        add_shortcode('tola_wallet_connect', [$this, 'render_wallet_connect']);
        add_shortcode('tola_marketplace_link', [$this, 'render_marketplace_link']);
        add_shortcode('tola_nft_stats', [$this, 'render_nft_stats']);
        
        // Memory shortcode
        add_shortcode('huraii_memory', [$this, 'render_memory_shortcode']);
        
        // Quiz shortcode
        add_shortcode('vortex_horace_quiz', [$this, 'render_quiz_form']);
    }
    
    public function enqueue_assets() {
        // Core assets
        wp_enqueue_style('vortex-swap-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/vortex-swap-shortcodes.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-swap-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/swap.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        
        wp_enqueue_style('vortex-wallet-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/wallet.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-wallet-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/wallet.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        
        wp_enqueue_style('vortex-metrics-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/metrics.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-metrics-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/metrics.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        
        wp_enqueue_style('vortex-ai-chat-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/ai-chat.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-ai-chat-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/ai-chat.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        
        wp_enqueue_style('vortex-huraii-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/huraii-dashboard.css', [], VORTEX_AI_ENGINE_VERSION);
        wp_enqueue_script('vortex-huraii-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/huraii-dashboard.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        
        // Memory assets
        wp_enqueue_script('huraii-memory-api', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/memory-api.js', ['jquery'], VORTEX_AI_ENGINE_VERSION, true);
        wp_enqueue_style('huraii-memory-api', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/memory-api.css', [], VORTEX_AI_ENGINE_VERSION);
        
        // Localize scripts
        wp_localize_script('vortex-swap-js', 'vortexSwapConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_swap_nonce'),
        ]);
        
        wp_localize_script('vortex-ai-chat-js', 'vortexAIConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('vortex/v1/'),
            'nonce' => wp_create_nonce('vortex_ai_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
        
        wp_localize_script('vortex-huraii-js', 'vortexHuraiiConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('vortex/v1/'),
            'nonce' => wp_create_nonce('vortex_huraii_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'vaultEndpoint' => admin_url('admin-ajax.php?action=vortex_ai_query'),
        ]);
        
        wp_localize_script('huraii-memory-api', 'huraii_memory', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vortex/v1/memory/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'current_user_id' => get_current_user_id()
        ]);
    }
    
    // Core shortcode renderers
    public function render_swap($atts) {
        $atts = shortcode_atts([
            'theme' => 'default',
            'width' => '100%',
            'height' => 'auto',
            'default_from' => 'ETH',
            'default_to' => 'USDC'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-swap-container" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="vortex-swap-interface" data-theme="<?php echo esc_attr($atts['theme']); ?>">
                <div class="swap-form">
                    <div class="swap-input">
                        <label>From</label>
                        <input type="text" value="<?php echo esc_attr($atts['default_from']); ?>" class="from-token">
                    </div>
                    <div class="swap-arrow">↓</div>
                    <div class="swap-input">
                        <label>To</label>
                        <input type="text" value="<?php echo esc_attr($atts['default_to']); ?>" class="to-token">
                    </div>
                    <button class="swap-button">Swap</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_wallet($atts) {
        $atts = shortcode_atts([
            'theme' => 'default'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-wallet-container" data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="wallet-header">
                <h3>Vortex Wallet</h3>
            </div>
            <div class="wallet-balance">
                <span class="balance-label">Balance:</span>
                <span class="balance-amount">0.00 ETH</span>
            </div>
            <div class="wallet-actions">
                <button class="connect-wallet">Connect Wallet</button>
                <button class="disconnect-wallet" style="display: none;">Disconnect</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_metric($atts) {
        $atts = shortcode_atts([
            'type' => 'ranking',
            'limit' => 10
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-metric-container" data-type="<?php echo esc_attr($atts['type']); ?>">
            <div class="metric-header">
                <h3>User Rankings</h3>
            </div>
            <div class="metric-content">
                <div class="loading">Loading rankings...</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_ai_chat($atts) {
        $atts = shortcode_atts([
            'agent' => 'general'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-ai-chat-container" data-agent="<?php echo esc_attr($atts['agent']); ?>">
            <div class="chat-header">
                <h3>AI Chat</h3>
            </div>
            <div class="chat-messages"></div>
            <div class="chat-input">
                <textarea placeholder="Ask me anything..."></textarea>
                <button class="send-message">Send</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_huraii_dashboard($atts) {
        $atts = shortcode_atts([
            'tabs' => 'generate,describe,upscale,vary,upload,save'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-huraii-dashboard">
            <div class="huraii-header">
                <h2>HURAII Dashboard</h2>
            </div>
            <div class="huraii-tabs">
                <button class="tab-button active" data-tab="generate">Generate</button>
                <button class="tab-button" data-tab="describe">Describe</button>
                <button class="tab-button" data-tab="upscale">Upscale</button>
                <button class="tab-button" data-tab="vary">Vary</button>
                <button class="tab-button" data-tab="upload">Upload</button>
                <button class="tab-button" data-tab="save">Save</button>
            </div>
            <div class="huraii-content">
                <div class="tab-content active" id="generate">
                    <h3>Generate New Artwork</h3>
                    <textarea placeholder="Describe your artwork..."></textarea>
                    <button class="generate-btn">Generate</button>
                </div>
                <!-- Other tab contents would be similar -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // NFT shortcode renderers
    public function render_mint_status($atts) {
        $atts = shortcode_atts([
            'artwork_id' => ''
        ], $atts);
        
        ob_start();
        ?>
        <div class="tola-mint-status" data-artwork-id="<?php echo esc_attr($atts['artwork_id']); ?>">
            <div class="status-indicator">
                <span class="status-text">Checking mint status...</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_royalty_manager($atts) {
        ob_start();
        ?>
        <div class="tola-royalty-manager">
            <h3>Royalty Manager</h3>
            <div class="royalty-settings">
                <label>Royalty Percentage:</label>
                <input type="number" min="0" max="100" value="10" class="royalty-percentage">
                <button class="update-royalty">Update Royalty</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_nft_gallery($atts) {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'limit' => 12
        ], $atts);
        
        ob_start();
        ?>
        <div class="tola-nft-gallery" data-user-id="<?php echo esc_attr($atts['user_id']); ?>">
            <div class="gallery-header">
                <h3>My NFT Collection</h3>
            </div>
            <div class="gallery-grid">
                <div class="loading">Loading NFTs...</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_wallet_connect($atts) {
        ob_start();
        ?>
        <div class="tola-wallet-connect">
            <button class="connect-solana-wallet">Connect Solana Wallet</button>
            <div class="wallet-status" style="display: none;">
                <span class="wallet-address"></span>
                <button class="disconnect-wallet">Disconnect</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_marketplace_link($atts) {
        $atts = shortcode_atts([
            'nft_id' => ''
        ], $atts);
        
        ob_start();
        ?>
        <div class="tola-marketplace-link">
            <a href="#" class="marketplace-btn" data-nft-id="<?php echo esc_attr($atts['nft_id']); ?>">
                View on Marketplace
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_nft_stats($atts) {
        ob_start();
        ?>
        <div class="tola-nft-stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Total NFTs</span>
                    <span class="stat-value" id="total-nfts">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Value</span>
                    <span class="stat-value" id="total-value">0 SOL</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Royalties Earned</span>
                    <span class="stat-value" id="royalties-earned">0 SOL</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Memory shortcode renderer
    public function render_memory_shortcode($atts) {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'limit' => 20
        ], $atts);
        
        ob_start();
        ?>
        <div class="huraii-memory-container" data-user-id="<?php echo esc_attr($atts['user_id']); ?>">
            <div class="memory-header">
                <h3>HURAII Memory</h3>
                <button class="refresh-memory">Refresh</button>
            </div>
            <div class="memory-content">
                <div class="loading">Loading memory...</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Quiz shortcode renderer
    public function render_quiz_form($atts) {
        ob_start();
        ?>
        <div class="vortex-horace-quiz">
            <form id="horace-quiz-form">
                <div class="quiz-question">
                    <label>1. What is your best business idea? (Describe in detail)</label>
                    <textarea name="idea" required></textarea>
                </div>
                <div class="quiz-question">
                    <label>2. What is your artistic style or niche?</label>
                    <input type="text" name="style" required>
                </div>
                <div class="quiz-question">
                    <label>3. Target audience demographics?</label>
                    <input type="text" name="audience" required>
                </div>
                <div class="quiz-question">
                    <label>4. Current challenges in selling artwork?</label>
                    <textarea name="challenges" required></textarea>
                </div>
                <div class="quiz-question">
                    <label>5. Desired income goals?</label>
                    <input type="text" name="goals" required>
                </div>
                <button type="submit" class="submit-quiz">Submit to HORACE</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the shortcodes
VortexAIEngine_Shortcodes::getInstance();
} 