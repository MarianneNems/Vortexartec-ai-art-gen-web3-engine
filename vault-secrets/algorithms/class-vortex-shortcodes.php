<?php
/**
 * Front-end shortcodes: swap, wallet, leaderboard
 *
 * @package VortexAIEngine
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VortexAIEngine_Shortcodes')) {
class VortexAIEngine_Shortcodes {
    private static $instance = null;

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->register();
        }
        return self::$instance;
    }

    private function register() {
        add_shortcode( 'vortex_swap',   [ $this, 'render_swap' ] );
        add_shortcode( 'vortex_wallet', [ $this, 'render_wallet' ] );
        add_shortcode( 'vortex_metric', [ $this, 'render_metric' ] );
        add_shortcode( 'vortex_ai_chat', [ $this, 'render_ai_chat' ] );
        add_shortcode( 'vortex_huraii', [ $this, 'render_huraii_dashboard' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets() {
        // Swap
        wp_enqueue_style( 'vortex-swap-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/vortex-swap-shortcodes.css', [], VORTEX_AI_ENGINE_VERSION );
        wp_enqueue_script( 'vortex-swap-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/swap.js', [ 'jquery' ], VORTEX_AI_ENGINE_VERSION, true );
        wp_localize_script( 'vortex-swap-js', 'vortexSwapConfig', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'vortex_swap_nonce' ),
        ] );

        // Wallet
        wp_enqueue_style( 'vortex-wallet-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/wallet.css', [], VORTEX_AI_ENGINE_VERSION );
        wp_enqueue_script( 'vortex-wallet-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/wallet.js', [ 'jquery' ], VORTEX_AI_ENGINE_VERSION, true );

        // Metrics
        wp_enqueue_style( 'vortex-metrics-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/metrics.css', [], VORTEX_AI_ENGINE_VERSION );
        wp_enqueue_script( 'vortex-metrics-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/metrics.js', [ 'jquery' ], VORTEX_AI_ENGINE_VERSION, true );

        // AI Chat
        wp_enqueue_style( 'vortex-ai-chat-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/ai-chat.css', [], VORTEX_AI_ENGINE_VERSION );
        wp_enqueue_script( 'vortex-ai-chat-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/ai-chat.js', [ 'jquery' ], VORTEX_AI_ENGINE_VERSION, true );
        wp_localize_script( 'vortex-ai-chat-js', 'vortexAIConfig', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( 'vortex/v1/' ),
            'nonce'   => wp_create_nonce( 'vortex_ai_nonce' ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
        ] );

        // HURAII Dashboard
        wp_enqueue_style( 'vortex-huraii-css', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/huraii-dashboard.css', [], VORTEX_AI_ENGINE_VERSION );
        wp_enqueue_script( 'vortex-huraii-js', VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/huraii-dashboard.js', [ 'jquery' ], VORTEX_AI_ENGINE_VERSION, true );
        wp_localize_script( 'vortex-huraii-js', 'vortexHuraiiConfig', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( 'vortex/v1/' ),
            'nonce'   => wp_create_nonce( 'vortex_huraii_nonce' ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'vaultEndpoint' => admin_url( 'admin-ajax.php?action=vortex_ai_query' ),
        ] );
        
        // Setup HURAII AJAX handlers
        $this->setup_huraii_ajax_handlers();
    }

    public function render_swap( $atts ) {
        return '<div class="vortex-swap-container"><!-- Swap UI placeholder --></div>';
    }

    public function render_wallet( $atts ) {
        return '<div class="vortex-wallet-container"><!-- Wallet UI placeholder --></div>';
    }

    public function render_metric( $atts ) {
        return '<div class="vortex-metrics-container"><!-- Leaderboard placeholder --></div>';
    }

    public function render_ai_chat( $atts ) {
        $atts = shortcode_atts( [
            'agents' => 'all',
            'theme' => 'default',
            'height' => '400px',
            'width' => '100%'
        ], $atts );

        ob_start();
        ?>
        <div class="vortex-ai-chat-container" style="height: <?php echo esc_attr( $atts['height'] ); ?>; width: <?php echo esc_attr( $atts['width'] ); ?>;">
            <div class="vortex-ai-chat-header">
                <h3>🤖 VORTEX AI Agents</h3>
                <div class="vortex-ai-agents-status">
                    <span class="agent-status huraii" title="HURAII - Artistic Creation">H</span>
                    <span class="agent-status cloe" title="CLOE - Analysis & Optimization">C</span>
                    <span class="agent-status horace" title="HORACE - Data Synthesis">H</span>
                </div>
            </div>
            
            <div class="vortex-ai-chat-messages" id="vortex-ai-messages">
                <div class="ai-message system-message">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">
                        <p>Hello! I'm your VORTEX AI assistant. I work with three specialized agents:</p>
                        <ul>
                            <li><strong>HURAII</strong> - Artistic creation and innovation</li>
                            <li><strong>CLOE</strong> - Analysis and optimization</li>
                            <li><strong>HORACE</strong> - Data synthesis and connections</li>
                        </ul>
                        <p>Ask me anything and I'll coordinate with the appropriate agents to provide you with the best answer!</p>
                    </div>
                </div>
            </div>
            
            <div class="vortex-ai-chat-input">
                <form id="vortex-ai-chat-form" method="post">
                    <div class="input-group">
                        <input type="text" id="vortex-ai-query" placeholder="Ask your question..." autocomplete="off" />
                        <button type="submit" id="vortex-ai-submit">
                            <span class="submit-text">Send</span>
                            <span class="submit-loading" style="display: none;">⏳</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="vortex-ai-chat-cost">
                <small>Session Cost: $<span id="session-cost">0.00</span></small>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_huraii_dashboard( $atts ) {
        $atts = shortcode_atts( [
            'theme' => 'vortex',
            'height' => '1200px',  // Increased from 700px to 1200px for better workspace
            'width' => '100%',
            'default_tab' => 'generate',
            'show_tabs' => 'generate,regenerate,upscale,vary,describe,download,upload,save,delete,edit,enhance,export,share',
            'chat_height' => '600px',  // Increased from 400px for better chat experience
            'enable_voice' => 'true',
            'enable_image_gen' => 'true',
            'enable_export' => 'true',
            'vortex_style' => 'true',
            'min_height' => '800px',  // New: minimum height for responsiveness
            'max_height' => '1800px'  // New: maximum height to prevent overflow
        ], $atts );

        $enabled_tabs = explode(',', $atts['show_tabs']);
        $user_id = get_current_user_id();
        $is_logged_in = is_user_logged_in();

        // Calculate dynamic height based on screen size
        $dynamic_style = sprintf(
            'height: %s; min-height: %s; max-height: %s; width: %s;',
            esc_attr( $atts['height'] ),
            esc_attr( $atts['min_height'] ),
            esc_attr( $atts['max_height'] ),
            esc_attr( $atts['width'] )
        );

        ob_start();
        ?>
        <div class="vortex-huraii-dashboard vortex-style vortex-huraii-extended" data-theme="<?php echo esc_attr( $atts['theme'] ); ?>" style="<?php echo $dynamic_style; ?>">
            
            <!-- HURAII Header - Professional Style -->
            <div class="huraii-header vortex-header">
                <div class="huraii-logo">
                    <div class="huraii-avatar vortex-avatar">
                        <div class="avatar-inner">🎨</div>
                        <div class="avatar-glow"></div>
                    </div>
                    <div class="huraii-info">
                        <h2>HURAII Studio</h2>
                        <p>AI-Powered Creative Workshop</p>
                        <div class="huraii-status">
                            <span class="status-indicator online"></span>
                            <span class="status-text">Ready to Create</span>
                            <span class="cost-display">Credits: <span id="huraii-session-cost">1000</span></span>
                        </div>
                    </div>
                </div>
                <div class="huraii-actions vortex-actions">
                    <button class="huraii-btn huraii-btn-voice vortex-btn" title="Voice Commands">
                        🎤 Voice
                    </button>
                    <button class="huraii-btn huraii-btn-history vortex-btn" title="Generation History">
                        📚 History
                    </button>
                    <button class="huraii-btn huraii-btn-settings vortex-btn" title="Studio Settings">
                        ⚙️ Settings
                    </button>
                    
                    <button class="huraii-btn huraii-btn-fullscreen vortex-btn" title="Toggle Fullscreen" onclick="toggleHuraiiFullscreen()">
                        🔳 Fullscreen
                    </button>
                </div>
            </div>

            <!-- Navigation Tabs - Professional Style Grid -->
            <div class="huraii-nav-tabs vortex-tabs">
                <?php if (in_array('generate', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'generate' ? 'active' : ''; ?>" data-tab="generate">
                    <span class="tab-icon">🎨</span>
                    <span class="tab-label">Generate</span>
                    <span class="tab-shortcut">1</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('regenerate', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'regenerate' ? 'active' : ''; ?>" data-tab="regenerate">
                    <span class="tab-icon">🔄</span>
                    <span class="tab-label">Regenerate</span>
                    <span class="tab-shortcut">2</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('upscale', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'upscale' ? 'active' : ''; ?>" data-tab="upscale">
                    <span class="tab-icon">🔍</span>
                    <span class="tab-label">Upscale</span>
                    <span class="tab-shortcut">3</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('vary', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'vary' ? 'active' : ''; ?>" data-tab="vary">
                    <span class="tab-icon">🎲</span>
                    <span class="tab-label">Vary</span>
                    <span class="tab-shortcut">4</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('describe', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'describe' ? 'active' : ''; ?>" data-tab="describe">
                    <span class="tab-icon">📝</span>
                    <span class="tab-label">Describe</span>
                    <span class="tab-shortcut">5</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('download', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'download' ? 'active' : ''; ?>" data-tab="download">
                    <span class="tab-icon">⬇️</span>
                    <span class="tab-label">Download</span>
                    <span class="tab-shortcut">6</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('upload', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'upload' ? 'active' : ''; ?>" data-tab="upload">
                    <span class="tab-icon">⬆️</span>
                    <span class="tab-label">Upload</span>
                    <span class="tab-shortcut">7</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('save', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'save' ? 'active' : ''; ?>" data-tab="save">
                    <span class="tab-icon">💾</span>
                    <span class="tab-label">Save</span>
                    <span class="tab-shortcut">8</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('delete', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'delete' ? 'active' : ''; ?>" data-tab="delete">
                    <span class="tab-icon">🗑️</span>
                    <span class="tab-label">Delete</span>
                    <span class="tab-shortcut">9</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('edit', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'edit' ? 'active' : ''; ?>" data-tab="edit">
                    <span class="tab-icon">✏️</span>
                    <span class="tab-label">Edit</span>
                    <span class="tab-shortcut">0</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('enhance', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'enhance' ? 'active' : ''; ?>" data-tab="enhance">
                    <span class="tab-icon">✨</span>
                    <span class="tab-label">Enhance</span>
                    <span class="tab-shortcut">-</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('export', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'export' ? 'active' : ''; ?>" data-tab="export">
                    <span class="tab-icon">📤</span>
                    <span class="tab-label">Export</span>
                    <span class="tab-shortcut">=</span>
                </button>
                <?php endif; ?>
                
                <?php if (in_array('share', $enabled_tabs)): ?>
                <button class="huraii-tab vortex-tab <?php echo $atts['default_tab'] === 'share' ? 'active' : ''; ?>" data-tab="share">
                    <span class="tab-icon">🔗</span>
                    <span class="tab-label">Share</span>
                    <span class="tab-shortcut">S</span>
                </button>
                <?php endif; ?>
            </div>

            <!-- Tab Content - Professional Style -->
            <div class="huraii-tab-content vortex-content">
                
                <!-- Generate Tab -->
                <?php if ( in_array( 'generate', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'generate' ? 'active' : ''; ?>" id="huraii-generate">
                        <div class="vortex-generate-container">
                            <div class="prompt-section">
                                <h3>🎨 Create New Artwork</h3>
                                <div class="prompt-input-area">
                                    <textarea id="generate-prompt" placeholder="Describe your vision... (e.g., 'mystical forest with glowing mushrooms, ethereal lighting, digital art style')" rows="3"></textarea>
                                    <div class="prompt-tools">
                                        <button class="tool-btn" title="Style Presets">🎨</button>
                                        <button class="tool-btn" title="Random Prompt">🎲</button>
                                        <button class="tool-btn" title="Advanced Settings">⚙️</button>
                                    </div>
                                </div>
                                
                                <div class="generation-settings">
                                    <div class="setting-group">
                                        <label>Aspect Ratio</label>
                                        <div class="ratio-buttons">
                                            <button class="ratio-btn active" data-ratio="1:1">1:1</button>
                                            <button class="ratio-btn" data-ratio="16:9">16:9</button>
                                            <button class="ratio-btn" data-ratio="9:16">9:16</button>
                                            <button class="ratio-btn" data-ratio="4:3">4:3</button>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <label>Style Intensity</label>
                                        <input type="range" class="style-slider" min="1" max="10" value="5">
                                        <span class="slider-value">5</span>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <label>Quality</label>
                                        <select class="quality-select">
                                            <option value="standard">Standard</option>
                                            <option value="high">High Quality</option>
                                            <option value="ultra">Ultra HD</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button class="generate-btn vortex-generate-btn">
                                    <span class="btn-icon">🎨</span>
                                    <span class="btn-text">Generate Artwork</span>
                                    <span class="btn-cost">2 credits</span>
                                </button>
                            </div>
                            
                            <div class="generation-grid">
                                <div class="grid-placeholder">
                                    <div class="placeholder-content">
                                        <div class="placeholder-icon">🎨</div>
                                        <div class="placeholder-text">Your generated artworks will appear here</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Regenerate Tab -->
                <?php if ( in_array( 'regenerate', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'regenerate' ? 'active' : ''; ?>" id="huraii-regenerate">
                        <div class="vortex-regenerate-container">
                            <div class="regenerate-header">
                                <h3>🔄 Regenerate Artwork</h3>
                                <p>Create new variations of existing artworks</p>
                            </div>
                            
                            <div class="source-selection">
                                <div class="source-grid" id="regenerate-source-grid">
                                    <!-- Source images will be populated here -->
                                </div>
                            </div>
                            
                            <div class="regenerate-options">
                                <div class="option-group">
                                    <label>Variation Strength</label>
                                    <div class="strength-buttons">
                                        <button class="strength-btn" data-strength="low">Low</button>
                                        <button class="strength-btn active" data-strength="medium">Medium</button>
                                        <button class="strength-btn" data-strength="high">High</button>
                                        <button class="strength-btn" data-strength="chaos">Chaos</button>
                                    </div>
                                </div>
                                
                                <button class="regenerate-btn vortex-action-btn">
                                    <span class="btn-icon">🔄</span>
                                    <span class="btn-text">Regenerate Selected</span>
                                    <span class="btn-cost">1 credit</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Upscale Tab -->
                <?php if ( in_array( 'upscale', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'upscale' ? 'active' : ''; ?>" id="huraii-upscale">
                        <div class="vortex-upscale-container">
                            <div class="upscale-header">
                                <h3>🔍 Upscale Artwork</h3>
                                <p>Enhance resolution and add fine details</p>
                            </div>
                            
                            <div class="upscale-grid" id="upscale-source-grid">
                                <!-- Upscale candidates will be populated here -->
                            </div>
                            
                            <div class="upscale-settings">
                                <div class="setting-row">
                                    <label>Upscale Factor</label>
                                    <div class="factor-buttons">
                                        <button class="factor-btn" data-factor="2x">2x</button>
                                        <button class="factor-btn active" data-factor="4x">4x</button>
                                        <button class="factor-btn" data-factor="8x">8x</button>
                                    </div>
                                </div>
                                
                                <div class="setting-row">
                                    <label>Enhancement Type</label>
                                    <select class="enhancement-select">
                                        <option value="detail">Add Details</option>
                                        <option value="creative">Creative Upscale</option>
                                        <option value="subtle">Subtle Enhancement</option>
                                    </select>
                                </div>
                                
                                <button class="upscale-btn vortex-action-btn">
                                    <span class="btn-icon">🔍</span>
                                    <span class="btn-text">Upscale Selected</span>
                                    <span class="btn-cost">3 credits</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Vary Tab -->
                <?php if ( in_array( 'vary', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'vary' ? 'active' : ''; ?>" id="huraii-vary">
                        <div class="vortex-vary-container">
                            <div class="vary-header">
                                <h3>🎭 Create Variations</h3>
                                <p>Generate subtle or strong variations of your artwork</p>
                            </div>
                            
                            <div class="vary-source-selection">
                                <div class="source-image-area">
                                    <div class="image-placeholder">
                                        <div class="placeholder-icon">🖼️</div>
                                        <div class="placeholder-text">Select an image to vary</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="vary-options">
                                <div class="variation-types">
                                    <button class="variation-btn active" data-type="subtle">
                                        <div class="variation-icon">🌸</div>
                                        <div class="variation-label">Subtle</div>
                                        <div class="variation-desc">Small changes</div>
                                    </button>
                                    <button class="variation-btn" data-type="strong">
                                        <div class="variation-icon">🌪️</div>
                                        <div class="variation-label">Strong</div>
                                        <div class="variation-desc">Major changes</div>
                                    </button>
                                    <button class="variation-btn" data-type="regional">
                                        <div class="variation-icon">🎯</div>
                                        <div class="variation-label">Regional</div>
                                        <div class="variation-desc">Specific areas</div>
                                    </button>
                                </div>
                                
                                <button class="vary-btn vortex-action-btn">
                                    <span class="btn-icon">🎭</span>
                                    <span class="btn-text">Create Variations</span>
                                    <span class="btn-cost">2 credits</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Describe Tab -->
                <?php if ( in_array( 'describe', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'describe' ? 'active' : ''; ?>" id="huraii-describe">
                        <div class="vortex-describe-container">
                            <div class="describe-header">
                                <h3>👀 Describe with AI (5-Agent Analysis)</h3>
                                <p>Get comprehensive analysis from all 5 AI agents with Vault consultation</p>
                            </div>
                            
                            <!-- Describe Input Form -->
                            <div class="describe-input-form">
                                <div class="describe-prompt-section">
                                    <label for="describe-prompt">Describe your image or idea:</label>
                                    <textarea 
                                        id="describe-prompt" 
                                        class="describe-prompt-textarea" 
                                        placeholder="Describe your image or idea here... (e.g., 'Analyze this artwork and tell me about its style, composition, and artistic elements')"
                                        rows="4"
                                    ></textarea>
                                </div>
                                
                                <div class="describe-image-section">
                                    <label for="describe-image-upload">Optional: Upload image for analysis</label>
                                    <div class="describe-image-upload-area">
                                        <div class="upload-placeholder" id="describe-upload-placeholder">
                                            <div class="placeholder-icon">📷</div>
                                            <div class="placeholder-text">
                                                <div class="upload-main-text">Click to upload image</div>
                                                <div class="upload-sub-text">or drag and drop here</div>
                                            </div>
                                        </div>
                                        <input type="file" id="describe-image-upload" accept="image/*" style="display: none;">
                                        <div class="uploaded-image-preview" id="describe-image-preview" style="display: none;">
                                            <img id="describe-preview-img" src="" alt="Uploaded image">
                                            <button type="button" class="remove-image-btn" id="describe-remove-image">✕</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="describe-submit-section">
                                    <button class="describe-btn vortex-action-btn" id="describe-submit-btn">
                                        <span class="btn-icon">🤖</span>
                                        <span class="btn-text">Analyze with AI</span>
                                        <span class="btn-cost">1 credit</span>
                                    </button>
                                    <div class="describe-loading" id="describe-loading" style="display: none;">
                                        <div class="loading-spinner"></div>
                                        <div class="loading-text">Consulting all 5 AI agents...</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Describe Results Chat Thread -->
                            <div class="describe-results-section">
                                <div class="describe-chat-thread" id="describe-chat-thread">
                                    <div class="chat-thread-placeholder">
                                        <div class="placeholder-icon">🤖</div>
                                        <div class="placeholder-text">
                                            <div class="placeholder-main">AI Analysis Results</div>
                                            <div class="placeholder-sub">Comprehensive analysis from all 5 agents will appear here</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Download Tab -->
                <?php if ( in_array( 'download', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'download' ? 'active' : ''; ?>" id="huraii-download">
                        <div class="vortex-download-container">
                            <div class="download-header">
                                <h3>📥 Download Artworks</h3>
                                <p>Save your creations in various formats</p>
                            </div>
                            
                            <div class="download-gallery" id="download-gallery">
                                <!-- Downloadable artworks will be populated here -->
                            </div>
                            
                            <div class="download-options">
                                <div class="format-selection">
                                    <label>Download Format</label>
                                    <div class="format-buttons">
                                        <button class="format-btn active" data-format="png">PNG</button>
                                        <button class="format-btn" data-format="jpg">JPG</button>
                                        <button class="format-btn" data-format="webp">WebP</button>
                                        <button class="format-btn" data-format="svg">SVG</button>
                                    </div>
                                </div>
                                
                                <div class="resolution-selection">
                                    <label>Resolution</label>
                                    <select class="resolution-select">
                                        <option value="original">Original</option>
                                        <option value="1080p">1080p</option>
                                        <option value="4k">4K</option>
                                        <option value="print">Print Quality</option>
                                    </select>
                                </div>
                                
                                <button class="download-btn vortex-action-btn">
                                    <span class="btn-icon">📥</span>
                                    <span class="btn-text">Download Selected</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Upload Tab -->
                <?php if ( in_array( 'upload', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'upload' ? 'active' : ''; ?>" id="huraii-upload">
                        <div class="vortex-upload-container">
                            <div class="upload-header">
                                <h3>📤 Upload Reference Images</h3>
                                <p>Add reference images for better AI generation</p>
                            </div>
                            
                            <div class="upload-area" id="upload-dropzone">
                                <div class="upload-placeholder">
                                    <div class="upload-icon">📤</div>
                                    <div class="upload-text">
                                        <h4>Drag & Drop Images Here</h4>
                                        <p>Or click to browse files</p>
                                    </div>
                                    <input type="file" id="file-input" multiple accept="image/*" hidden>
                                </div>
                            </div>
                            
                            <div class="uploaded-images" id="uploaded-images">
                                <!-- Uploaded images will appear here -->
                            </div>
                            
                            <div class="upload-settings">
                                <div class="setting-row">
                                    <label>
                                        <input type="checkbox" id="auto-enhance"> 
                                        Auto-enhance uploaded images
                                    </label>
                                </div>
                                <div class="setting-row">
                                    <label>
                                        <input type="checkbox" id="extract-style"> 
                                        Extract style for future generations
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Save Tab -->
                <?php if ( in_array( 'save', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'save' ? 'active' : ''; ?>" id="huraii-save">
                        <div class="vortex-save-container">
                            <div class="save-header">
                                <h3>💾 Save to Collections</h3>
                                <p>Organize your artworks into collections</p>
                            </div>
                            
                            <div class="collections-grid">
                                <div class="collection-card new-collection">
                                    <div class="collection-icon">➕</div>
                                    <div class="collection-name">New Collection</div>
                                </div>
                                
                                <div class="collection-card">
                                    <div class="collection-preview">
                                        <div class="preview-images">
                                            <!-- Preview thumbnails -->
                                        </div>
                                    </div>
                                    <div class="collection-info">
                                        <div class="collection-name">Landscapes</div>
                                        <div class="collection-count">24 items</div>
                                    </div>
                                </div>
                                
                                <div class="collection-card">
                                    <div class="collection-preview">
                                        <div class="preview-images">
                                            <!-- Preview thumbnails -->
                                        </div>
                                    </div>
                                    <div class="collection-info">
                                        <div class="collection-name">Portraits</div>
                                        <div class="collection-count">18 items</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="save-actions">
                                <button class="save-to-collection-btn vortex-action-btn">
                                    <span class="btn-icon">💾</span>
                                    <span class="btn-text">Save Selected</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Delete Tab -->
                <?php if ( in_array( 'delete', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'delete' ? 'active' : ''; ?>" id="huraii-delete">
                        <div class="vortex-delete-container">
                            <div class="delete-header">
                                <h3>🗑️ Manage Artworks</h3>
                                <p>Delete unwanted generations and free up space</p>
                            </div>
                            
                            <div class="delete-gallery" id="delete-gallery">
                                <!-- Artworks available for deletion -->
                            </div>
                            
                            <div class="delete-actions">
                                <div class="bulk-actions">
                                    <button class="select-all-btn">Select All</button>
                                    <button class="deselect-all-btn">Deselect All</button>
                                </div>
                                
                                <button class="delete-selected-btn danger-btn">
                                    <span class="btn-icon">🗑️</span>
                                    <span class="btn-text">Delete Selected</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Edit Tab -->
                <?php if ( in_array( 'edit', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'edit' ? 'active' : ''; ?>" id="huraii-edit">
                        <div class="vortex-edit-container">
                            <div class="edit-header">
                                <h3>✏️ Edit Artwork</h3>
                                <p>Fine-tune your creations with AI-powered editing</p>
                            </div>
                            
                            <div class="edit-workspace">
                                <div class="canvas-area">
                                    <div class="canvas-placeholder">
                                        <div class="placeholder-icon">🖼️</div>
                                        <div class="placeholder-text">Select an image to edit</div>
                                    </div>
                                </div>
                                
                                <div class="edit-tools">
                                    <div class="tool-section">
                                        <h4>🎨 Color Tools</h4>
                                        <button class="edit-tool-btn" data-tool="brightness">🔆 Brightness</button>
                                        <button class="edit-tool-btn" data-tool="contrast">🌗 Contrast</button>
                                        <button class="edit-tool-btn" data-tool="saturation">🎨 Saturation</button>
                                        <button class="edit-tool-btn" data-tool="hue">🌈 Hue</button>
                                    </div>
                                    
                                    <div class="tool-section">
                                        <h4>🖌️ AI Tools</h4>
                                        <button class="edit-tool-btn" data-tool="inpaint">🎯 Inpaint</button>
                                        <button class="edit-tool-btn" data-tool="outpaint">📐 Outpaint</button>
                                        <button class="edit-tool-btn" data-tool="remove-bg">🗑️ Remove BG</button>
                                        <button class="edit-tool-btn" data-tool="style-transfer">🎭 Style Transfer</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="edit-actions">
                                <button class="apply-edits-btn vortex-action-btn">
                                    <span class="btn-icon">✏️</span>
                                    <span class="btn-text">Apply Edits</span>
                                    <span class="btn-cost">1 credit</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Enhance Tab -->
                <?php if ( in_array( 'enhance', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'enhance' ? 'active' : ''; ?>" id="huraii-enhance">
                        <div class="vortex-enhance-container">
                            <div class="enhance-header">
                                <h3>✨ Enhance Quality</h3>
                                <p>AI-powered enhancement for better quality and details</p>
                            </div>
                            
                            <div class="enhance-options">
                                <div class="enhancement-types">
                                    <div class="enhancement-card">
                                        <div class="enhancement-icon">🔍</div>
                                        <div class="enhancement-title">Super Resolution</div>
                                        <div class="enhancement-desc">Increase resolution while preserving quality</div>
                                        <button class="enhance-btn" data-enhance="super-res">Enhance</button>
                                    </div>
                                    
                                    <div class="enhancement-card">
                                        <div class="enhancement-icon">✨</div>
                                        <div class="enhancement-title">Detail Enhancement</div>
                                        <div class="enhancement-desc">Add fine details and textures</div>
                                        <button class="enhance-btn" data-enhance="detail">Enhance</button>
                                    </div>
                                    
                                    <div class="enhancement-card">
                                        <div class="enhancement-icon">🌟</div>
                                        <div class="enhancement-title">Artistic Enhancement</div>
                                        <div class="enhancement-desc">Enhance artistic quality and style</div>
                                        <button class="enhance-btn" data-enhance="artistic">Enhance</button>
                                    </div>
                                    
                                    <div class="enhancement-card">
                                        <div class="enhancement-icon">🎨</div>
                                        <div class="enhancement-title">Color Enhancement</div>
                                        <div class="enhancement-desc">Improve colors and vibrancy</div>
                                        <button class="enhance-btn" data-enhance="color">Enhance</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Export Tab -->
                <?php if ( in_array( 'export', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'export' ? 'active' : ''; ?>" id="huraii-export">
                        <div class="vortex-export-container">
                            <div class="export-header">
                                <h3>📤 Export Creations</h3>
                                <p>Export your artworks for various purposes</p>
                            </div>
                            
                            <div class="export-formats">
                                <div class="format-category">
                                    <h4>🖼️ Images</h4>
                                    <div class="format-options">
                                        <button class="format-option" data-format="png-hd">PNG HD</button>
                                        <button class="format-option" data-format="jpg-high">JPG High Quality</button>
                                        <button class="format-option" data-format="webp">WebP Optimized</button>
                                        <button class="format-option" data-format="tiff">TIFF Print</button>
                                    </div>
                                </div>
                                
                                <div class="format-category">
                                    <h4>📱 Social Media</h4>
                                    <div class="format-options">
                                        <button class="format-option" data-format="instagram-post">Instagram Post</button>
                                        <button class="format-option" data-format="instagram-story">Instagram Story</button>
                                        <button class="format-option" data-format="twitter-header">Twitter Header</button>
                                        <button class="format-option" data-format="facebook-cover">Facebook Cover</button>
                                    </div>
                                </div>
                                
                                <div class="format-category">
                                    <h4>🖨️ Print</h4>
                                    <div class="format-options">
                                        <button class="format-option" data-format="poster-a3">A3 Poster</button>
                                        <button class="format-option" data-format="postcard">Postcard</button>
                                        <button class="format-option" data-format="canvas-print">Canvas Print</button>
                                        <button class="format-option" data-format="business-card">Business Card</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="export-actions">
                                <button class="export-selected-btn vortex-action-btn">
                                    <span class="btn-icon">📤</span>
                                    <span class="btn-text">Export Selected</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Share Tab -->
                <?php if ( in_array( 'share', $enabled_tabs ) ) : ?>
                    <div class="huraii-tab-pane vortex-pane <?php echo $atts['default_tab'] === 'share' ? 'active' : ''; ?>" id="huraii-share">
                        <div class="vortex-share-container">
                            <div class="share-header">
                                <h3>🔗 Share Your Art</h3>
                                <p>Share your creations with the world</p>
                            </div>
                            
                            <div class="share-options">
                                <div class="share-platforms">
                                    <div class="platform-card">
                                        <div class="platform-icon">📸</div>
                                        <div class="platform-name">Instagram</div>
                                        <button class="share-btn instagram-btn">Share</button>
                                    </div>
                                    
                                    <div class="platform-card">
                                        <div class="platform-icon">🐦</div>
                                        <div class="platform-name">Twitter</div>
                                        <button class="share-btn twitter-btn">Share</button>
                                    </div>
                                    
                                    <div class="platform-card">
                                        <div class="platform-icon">📌</div>
                                        <div class="platform-name">Pinterest</div>
                                        <button class="share-btn pinterest-btn">Share</button>
                                    </div>
                                    
                                    <div class="platform-card">
                                        <div class="platform-icon">💼</div>
                                        <div class="platform-name">LinkedIn</div>
                                        <button class="share-btn linkedin-btn">Share</button>
                                    </div>
                                </div>
                                
                                <div class="share-settings">
                                    <div class="setting-row">
                                        <label>Privacy Level</label>
                                        <select class="privacy-select">
                                            <option value="public">Public</option>
                                            <option value="unlisted">Unlisted</option>
                                            <option value="private">Private</option>
                                        </select>
                                    </div>
                                    
                                    <div class="setting-row">
                                        <label>
                                            <input type="checkbox" id="include-prompt"> 
                                            Include generation prompt
                                        </label>
                                    </div>
                                    
                                    <div class="setting-row">
                                        <label>
                                            <input type="checkbox" id="watermark"> 
                                            Add HURAII watermark
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="share-link">
                                    <label>Direct Link</label>
                                    <div class="link-input-group">
                                        <input type="text" readonly value="https://huraii.studio/share/abc123" class="share-link-input">
                                        <button class="copy-link-btn">📋 Copy</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Setup HURAII AJAX handlers - All tabs connect through Vault to AI Orchestrator
     */
    private function setup_huraii_ajax_handlers() {
        // HURAII Generate - Calls HURAII agent through Vault
        add_action( 'wp_ajax_huraii_generate', [ $this, 'handle_huraii_generate' ] );
        add_action( 'wp_ajax_nopriv_huraii_generate', [ $this, 'handle_huraii_generate' ] );
        
        // HURAII Regenerate - Calls HURAII agent through Vault
        add_action( 'wp_ajax_huraii_regenerate', [ $this, 'handle_huraii_regenerate' ] );
        add_action( 'wp_ajax_nopriv_huraii_regenerate', [ $this, 'handle_huraii_regenerate' ] );
        
        // HURAII Upscale - Calls HURAII + CLOE agents through Vault
        add_action( 'wp_ajax_huraii_upscale', [ $this, 'handle_huraii_upscale' ] );
        add_action( 'wp_ajax_nopriv_huraii_upscale', [ $this, 'handle_huraii_upscale' ] );
        
        // HURAII Vary - Calls HURAII agent through Vault
        add_action( 'wp_ajax_huraii_vary', [ $this, 'handle_huraii_vary' ] );
        add_action( 'wp_ajax_nopriv_huraii_vary', [ $this, 'handle_huraii_vary' ] );
        
        // HURAII Describe - Calls CLOE + HORACE agents through Vault
        add_action( 'wp_ajax_huraii_describe', [ $this, 'handle_huraii_describe' ] );
        add_action( 'wp_ajax_nopriv_huraii_describe', [ $this, 'handle_huraii_describe' ] );
        
        // HURAII Upload - Calls CLOE agent for analysis through Vault
        add_action( 'wp_ajax_huraii_upload', [ $this, 'handle_huraii_upload' ] );
        add_action( 'wp_ajax_nopriv_huraii_upload', [ $this, 'handle_huraii_upload' ] );
        
        // HURAII Save - Local WordPress functionality
        add_action( 'wp_ajax_huraii_save', [ $this, 'handle_huraii_save' ] );
        add_action( 'wp_ajax_nopriv_huraii_save', [ $this, 'handle_huraii_save' ] );
        
        // HURAII Download - Local file functionality
        add_action( 'wp_ajax_huraii_download', [ $this, 'handle_huraii_download' ] );
        add_action( 'wp_ajax_nopriv_huraii_download', [ $this, 'handle_huraii_download' ] );
        
        // HURAII Delete - Local WordPress functionality
        add_action( 'wp_ajax_huraii_delete', [ $this, 'handle_huraii_delete' ] );
        add_action( 'wp_ajax_nopriv_huraii_delete', [ $this, 'handle_huraii_delete' ] );
        
        // HURAII Edit - Calls HURAII + CLOE agents through Vault
        add_action( 'wp_ajax_huraii_edit', [ $this, 'handle_huraii_edit' ] );
        add_action( 'wp_ajax_nopriv_huraii_edit', [ $this, 'handle_huraii_edit' ] );
        
        // HURAII Enhance - Calls HURAII + CLOE agents through Vault
        add_action( 'wp_ajax_huraii_enhance', [ $this, 'handle_huraii_enhance' ] );
        add_action( 'wp_ajax_nopriv_huraii_enhance', [ $this, 'handle_huraii_enhance' ] );
        
        // HURAII Export - Local file functionality
        add_action( 'wp_ajax_huraii_export', [ $this, 'handle_huraii_export' ] );
        add_action( 'wp_ajax_nopriv_huraii_export', [ $this, 'handle_huraii_export' ] );
        
        // HURAII Share - Local sharing functionality
        add_action( 'wp_ajax_huraii_share', [ $this, 'handle_huraii_share' ] );
        add_action( 'wp_ajax_nopriv_huraii_share', [ $this, 'handle_huraii_share' ] );
    }
    
    /**
     * Handle HURAII Generate - AI Art Creation through Vault
     */
    public function handle_huraii_generate() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $prompt = sanitize_textarea_field( $_POST['prompt'] ?? '' );
        $settings = $_POST['settings'] ?? [];
        
        if ( empty( $prompt ) ) {
            wp_send_json_error( 'Prompt is required' );
        }
        
        // Prepare AI query for HURAII agent through Vault
        $ai_query = "Generate an AI artwork with the following prompt: {$prompt}. ";
        if ( !empty( $settings['aspect_ratio'] ) ) {
            $ai_query .= "Aspect ratio: {$settings['aspect_ratio']}. ";
        }
        if ( !empty( $settings['style_intensity'] ) ) {
            $ai_query .= "Style intensity: {$settings['style_intensity']}/10. ";
        }
        if ( !empty( $settings['quality'] ) ) {
            $ai_query .= "Quality level: {$settings['quality']}. ";
        }
        
        $context = [
            'action' => 'generate_artwork',
            'user_id' => get_current_user_id(),
            'settings' => $settings,
            'timestamp' => current_time( 'mysql' )
        ];
        
        // Call Enhanced Orchestrator with 7-step pipeline - HURAII generation
        $enhanced_orchestrator = VortexAIEngine_EnhancedOrchestrator::getInstance();
        $result = $enhanced_orchestrator->executeEnhancedOrchestration(
            'generate',
            [
                'query' => $prompt,
                'settings' => $settings,
                'aspect_ratio' => $settings['aspect_ratio'] ?? 'default',
                'style_intensity' => $settings['style_intensity'] ?? 5,
                'quality' => $settings['quality'] ?? 'standard',
                'context' => $context
            ],
            get_current_user_id()
        );
        
        if ( $result ) {
            wp_send_json_success( [
                'message' => 'Artwork generated successfully with enhanced orchestration',
                'result' => $result,
                'cost' => $result['cost'] ?? 2,
                'orchestration_data' => [
                    'steps_completed' => 7,
                    'cost_analysis' => $result['cost_analysis'] ?? [],
                    'quality_metrics' => $result['performance_metrics'] ?? [],
                    'continuous_learning' => $result['continuous_learning'] ?? []
                ]
            ] );
        } else {
            wp_send_json_error( 'Failed to generate artwork' );
        }
    }
    
    /**
     * Handle HURAII Regenerate - Create Variations through Vault
     */
    public function handle_huraii_regenerate() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        $strength = sanitize_text_field( $_POST['strength'] ?? 'medium' );
        
        if ( empty( $image_ids ) ) {
            wp_send_json_error( 'No images selected for regeneration' );
        }
        
        // Prepare AI query for HURAII agent through Vault
        $ai_query = "Regenerate variations of the selected artworks with {$strength} variation strength. ";
        $ai_query .= "Create new versions that maintain the core concept but introduce creative changes.";
        
        $context = [
            'action' => 'regenerate_artwork',
            'image_ids' => $image_ids,
            'strength' => $strength,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time( 'mysql' )
        ];
        
        // Call AI Orchestrator through Vault - HURAII agent only
        $orchestrator = VortexAIEngine_AIOrchestrator::getInstance();
        $result = $orchestrator->process_ai_query_advanced( 
            $ai_query, 
            $context, 
            ['huraii'],
            'adaptive',
            0.90
        );
        
        if ( $result ) {
            wp_send_json_success( [
                'message' => 'Variations generated successfully',
                'result' => $result,
                'cost' => $result['cost'] ?? 1
            ] );
        } else {
            wp_send_json_error( 'Failed to regenerate artwork' );
        }
    }
    
    /**
     * Handle HURAII Upscale - Enhance Resolution through Vault
     */
    public function handle_huraii_upscale() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        $factor = sanitize_text_field( $_POST['factor'] ?? '4x' );
        $enhancement_type = sanitize_text_field( $_POST['enhancement_type'] ?? 'detail' );
        
        if ( empty( $image_ids ) ) {
            wp_send_json_error( 'No images selected for upscaling' );
        }
        
        // Prepare AI query for HURAII + CLOE agents through Vault
        $ai_query = "Upscale the selected artworks by {$factor} using {$enhancement_type} enhancement. ";
        $ai_query .= "Preserve artistic integrity while increasing resolution and adding fine details.";
        
        $context = [
            'action' => 'upscale_artwork',
            'image_ids' => $image_ids,
            'factor' => $factor,
            'enhancement_type' => $enhancement_type,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time( 'mysql' )
        ];
        
        // Call AI Orchestrator through Vault - HURAII for creativity + CLOE for optimization
        $orchestrator = VortexAIEngine_AIOrchestrator::getInstance();
        $result = $orchestrator->process_ai_query_advanced( 
            $ai_query, 
            $context, 
            ['huraii', 'cloe'],
            'adaptive',
            0.90
        );
        
        if ( $result ) {
            wp_send_json_success( [
                'message' => 'Artwork upscaled successfully',
                'result' => $result,
                'cost' => $result['cost'] ?? 3
            ] );
        } else {
            wp_send_json_error( 'Failed to upscale artwork' );
        }
    }
    
    /**
     * Handle HURAII Vary - Create Variations through Vault
     */
    public function handle_huraii_vary() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_id = sanitize_text_field( $_POST['image_id'] ?? '' );
        $variation_type = sanitize_text_field( $_POST['variation_type'] ?? 'subtle' );
        
        if ( empty( $image_id ) ) {
            wp_send_json_error( 'No image selected for variation' );
        }
        
        // Prepare AI query for HURAII agent through Vault
        $ai_query = "Create {$variation_type} variations of the selected artwork. ";
        
        switch ( $variation_type ) {
            case 'subtle':
                $ai_query .= "Make small, refined changes while maintaining the original concept and style.";
                break;
            case 'strong':
                $ai_query .= "Make significant creative changes while preserving the core theme.";
                break;
            case 'regional':
                $ai_query .= "Focus variations on specific regions or elements of the artwork.";
                break;
        }
        
        $context = [
            'action' => 'vary_artwork',
            'image_id' => $image_id,
            'variation_type' => $variation_type,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time( 'mysql' )
        ];
        
        // Call AI Orchestrator through Vault - HURAII agent only
        $orchestrator = VortexAIEngine_AIOrchestrator::getInstance();
        $result = $orchestrator->process_ai_query_advanced( 
            $ai_query, 
            $context, 
            ['huraii'],
            'adaptive',
            0.90
        );
        
        if ( $result ) {
            wp_send_json_success( [
                'message' => 'Variations created successfully',
                'result' => $result,
                'cost' => $result['cost'] ?? 2
            ] );
        } else {
            wp_send_json_error( 'Failed to create variations' );
        }
    }
    
    /**
     * Handle HURAII Describe - Comprehensive 5-Agent Analysis through Vault
     */
    public function handle_huraii_describe() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $prompt = sanitize_textarea_field( $_POST['prompt'] ?? '' );
        $image_id = sanitize_text_field( $_POST['image_id'] ?? '' );
        
        if ( empty( $prompt ) ) {
            wp_send_json_error( 'Prompt is required for analysis' );
        }
        
        // Deduct 1 credit from user account
        $user_id = get_current_user_id();
        $current_credits = get_user_meta( $user_id, 'huraii_credits', true ) ?: 1000;
        
        if ( $current_credits < 1 ) {
            wp_send_json_error( 'Insufficient credits. You need 1 credit for this analysis.' );
        }
        
        // Deduct credit
        update_user_meta( $user_id, 'huraii_credits', $current_credits - 1 );
        
        // Prepare comprehensive AI query for all 5 agents through Vault
        $ai_query = "Comprehensive 5-agent analysis: {$prompt}";
        
        if ( $image_id ) {
            $ai_query .= " Include analysis of the uploaded image (ID: {$image_id}).";
        }
        
        $context = [
            'action' => 'describe_artwork_comprehensive',
            'prompt' => $prompt,
            'image_id' => $image_id,
            'user_id' => $user_id,
            'timestamp' => current_time( 'mysql' ),
            'requires_all_agents' => true
        ];
        
        // Call Enhanced Orchestrator with 7-step pipeline - CLOE multi-agent analysis
        $enhanced_orchestrator = VortexAIEngine_EnhancedOrchestrator::getInstance();
        
        try {
            $result = $enhanced_orchestrator->executeEnhancedOrchestration(
                'describe',
                [
                    'query' => $prompt,
                    'image_id' => $image_id,
                    'specialized_agents' => ['cloe', 'horace', 'archer'], // CLOE multi-agent analysis
                    'analysis_type' => 'comprehensive',
                    'context' => $context
                ],
                $user_id
            );
            
            if ( $result ) {
                wp_send_json_success( [
                    'message' => 'CLOE comprehensive analysis completed successfully',
                    'result' => $result,
                    'cost' => 1,
                    'credits_remaining' => $current_credits - 1,
                    'orchestration_data' => [
                        'steps_completed' => 7,
                        'agents_used' => ['cloe', 'horace', 'archer'],
                        'cost_analysis' => $result['cost_analysis'] ?? [],
                        'quality_metrics' => $result['performance_metrics'] ?? [],
                        'continuous_learning' => $result['continuous_learning'] ?? []
                    ]
                ] );
            } else {
                // Refund credit on failure
                update_user_meta( $user_id, 'huraii_credits', $current_credits );
                wp_send_json_error( 'Failed to complete analysis' );
            }
        } catch ( Exception $e ) {
            // Refund credit on exception
            update_user_meta( $user_id, 'huraii_credits', $current_credits );
            wp_send_json_error( 'Analysis failed: ' . $e->getMessage() );
        }
    }
    
    /**
     * Handle HURAII Upload - Analyze Upload through Vault
     */
    public function handle_huraii_upload() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        if ( empty( $_FILES['image'] ) ) {
            wp_send_json_error( 'No image uploaded' );
        }
        
        // Handle file upload
        $upload = wp_handle_upload( $_FILES['image'], [ 'test_form' => false ] );
        
        if ( $upload && empty( $upload['error'] ) ) {
            $file_url = $upload['url'];
            $file_path = $upload['file'];
            
            $auto_enhance = $_POST['auto_enhance'] ?? false;
            $extract_style = $_POST['extract_style'] ?? false;
            
            // Prepare AI query for CLOE agent through Vault for analysis
            $ai_query = "Analyze the uploaded reference image. Identify artistic style, composition, color palette, and techniques. ";
            
            if ( $auto_enhance ) {
                $ai_query .= "Suggest enhancements for the image. ";
            }
            
            if ( $extract_style ) {
                $ai_query .= "Extract the artistic style for future artwork generation. ";
            }
            
            $context = [
                'action' => 'analyze_upload',
                'file_url' => $file_url,
                'file_path' => $file_path,
                'auto_enhance' => $auto_enhance,
                'extract_style' => $extract_style,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time( 'mysql' )
            ];
            
            // Call AI Orchestrator through Vault - CLOE for analysis
            $orchestrator = VortexAIEngine_AIOrchestrator::getInstance();
            $result = $orchestrator->process_ai_query_advanced( 
                $ai_query, 
                $context, 
                ['cloe'],
                'adaptive',
                0.90
            );
            
            wp_send_json_success( [
                'message' => 'Image uploaded and analyzed successfully',
                'file_url' => $file_url,
                'analysis' => $result,
                'cost' => $result['cost'] ?? 0.5
            ] );
        } else {
            wp_send_json_error( 'Failed to upload image: ' . $upload['error'] );
        }
    }
    
    /**
     * Handle HURAII Edit - AI-Powered Editing through Vault
     */
    public function handle_huraii_edit() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_id = sanitize_text_field( $_POST['image_id'] ?? '' );
        $edit_tool = sanitize_text_field( $_POST['edit_tool'] ?? '' );
        $edit_parameters = $_POST['edit_parameters'] ?? [];
        
        if ( empty( $image_id ) || empty( $edit_tool ) ) {
            wp_send_json_error( 'Image and edit tool are required' );
        }
        
        // Prepare AI query for HURAII + CLOE agents through Vault
        $ai_query = "Apply {$edit_tool} editing to the selected artwork. ";
        
        switch ( $edit_tool ) {
            case 'brightness':
                $ai_query .= "Adjust brightness levels while maintaining artistic integrity.";
                break;
            case 'contrast':
                $ai_query .= "Enhance contrast to improve visual impact.";
                break;
            case 'saturation':
                $ai_query .= "Adjust color saturation for optimal visual appeal.";
                break;
            case 'hue':
                $ai_query .= "Modify hue values to achieve desired color harmony.";
                break;
            case 'inpaint':
                $ai_query .= "Intelligently fill selected areas with contextually appropriate content.";
                break;
            case 'outpaint':
                $ai_query .= "Extend the artwork beyond its current boundaries seamlessly.";
                break;
            case 'remove-bg':
                $ai_query .= "Remove the background while preserving the main subject.";
                break;
            case 'style-transfer':
                $ai_query .= "Apply the specified artistic style to the artwork.";
                break;
        }
        
        $context = [
            'action' => 'edit_artwork',
            'image_id' => $image_id,
            'edit_tool' => $edit_tool,
            'edit_parameters' => $edit_parameters,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time( 'mysql' )
        ];
        
        // Call AI Orchestrator through Vault - HURAII for creativity + CLOE for optimization
        $orchestrator = VortexAIEngine_AIOrchestrator::getInstance();
        $result = $orchestrator->process_ai_query_advanced( 
            $ai_query, 
            $context, 
            ['huraii', 'cloe'],
            'adaptive',
            0.90
        );
        
        if ( $result ) {
            wp_send_json_success( [
                'message' => 'Artwork edited successfully',
                'result' => $result,
                'cost' => $result['cost'] ?? 1
            ] );
        } else {
            wp_send_json_error( 'Failed to edit artwork' );
        }
    }
    
    /**
     * Handle HURAII Enhance - Quality Enhancement through Vault
     */
    public function handle_huraii_enhance() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        $enhancement_type = sanitize_text_field( $_POST['enhancement_type'] ?? 'super-res' );
        
        if ( empty( $image_ids ) ) {
            wp_send_json_error( 'No images selected for enhancement' );
        }
        
        // Prepare AI query for HURAII + CLOE agents through Vault
        $ai_query = "Apply {$enhancement_type} enhancement to the selected artworks. ";
        
        switch ( $enhancement_type ) {
            case 'super-res':
                $ai_query .= "Increase resolution while preserving and enhancing quality.";
                break;
            case 'detail':
                $ai_query .= "Add fine details and textures to enhance visual richness.";
                break;
            case 'artistic':
                $ai_query .= "Enhance artistic quality and style refinement.";
                break;
            case 'color':
                $ai_query .= "Improve colors, vibrancy, and color harmony.";
                break;
        }
        
        $context = [
            'action' => 'enhance_artwork',
            'image_ids' => $image_ids,
            'enhancement_type' => $enhancement_type,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time( 'mysql' )
        ];
        
        // Call AI Orchestrator through Vault - HURAII for creativity + CLOE for optimization
        $orchestrator = VortexAIEngine_AIOrchestrator::getInstance();
        $result = $orchestrator->process_ai_query_advanced( 
            $ai_query, 
            $context, 
            ['huraii', 'cloe'],
            'adaptive',
            0.90
        );
        
        if ( $result ) {
            wp_send_json_success( [
                'message' => 'Artwork enhanced successfully',
                'result' => $result,
                'cost' => $result['cost'] ?? 2
            ] );
        } else {
            wp_send_json_error( 'Failed to enhance artwork' );
        }
    }
    
    /**
     * Handle HURAII Save - Local WordPress functionality
     */
    public function handle_huraii_save() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        $collection_id = sanitize_text_field( $_POST['collection_id'] ?? '' );
        $collection_name = sanitize_text_field( $_POST['collection_name'] ?? '' );
        
        if ( empty( $image_ids ) ) {
            wp_send_json_error( 'No images selected to save' );
        }
        
        $user_id = get_current_user_id();
        
        // Create new collection if needed
        if ( empty( $collection_id ) && !empty( $collection_name ) ) {
            // Create new collection in user meta
            $collections = get_user_meta( $user_id, 'huraii_collections', true ) ?: [];
            $collection_id = uniqid( 'collection_' );
            $collections[$collection_id] = [
                'name' => $collection_name,
                'created' => current_time( 'mysql' ),
                'images' => []
            ];
            update_user_meta( $user_id, 'huraii_collections', $collections );
        }
        
        // Add images to collection
        if ( $collection_id ) {
            $collections = get_user_meta( $user_id, 'huraii_collections', true ) ?: [];
            if ( isset( $collections[$collection_id] ) ) {
                $collections[$collection_id]['images'] = array_merge( 
                    $collections[$collection_id]['images'], 
                    $image_ids 
                );
                $collections[$collection_id]['images'] = array_unique( $collections[$collection_id]['images'] );
                update_user_meta( $user_id, 'huraii_collections', $collections );
                
                wp_send_json_success( [
                    'message' => 'Images saved to collection successfully',
                    'collection_id' => $collection_id,
                    'collection_name' => $collections[$collection_id]['name']
                ] );
            } else {
                wp_send_json_error( 'Collection not found' );
            }
        } else {
            wp_send_json_error( 'No collection specified' );
        }
    }
    
    /**
     * Handle HURAII Download - Local file functionality
     */
    public function handle_huraii_download() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        $format = sanitize_text_field( $_POST['format'] ?? 'png' );
        $resolution = sanitize_text_field( $_POST['resolution'] ?? 'original' );
        
        if ( empty( $image_ids ) ) {
            wp_send_json_error( 'No images selected for download' );
        }
        
        $download_urls = [];
        
        foreach ( $image_ids as $image_id ) {
            // Get image URL and process for download
            $image_url = wp_get_attachment_url( $image_id );
            if ( $image_url ) {
                $download_urls[] = [
                    'id' => $image_id,
                    'url' => $image_url,
                    'filename' => "huraii-{$image_id}.{$format}"
                ];
            }
        }
        
        wp_send_json_success( [
            'message' => 'Download links prepared',
            'downloads' => $download_urls,
            'format' => $format,
            'resolution' => $resolution
        ] );
    }
    
    /**
     * Handle HURAII Delete - Local WordPress functionality
     */
    public function handle_huraii_delete() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        
        if ( empty( $image_ids ) ) {
            wp_send_json_error( 'No images selected for deletion' );
        }
        
        $user_id = get_current_user_id();
        $deleted_count = 0;
        
        foreach ( $image_ids as $image_id ) {
            // Check if user owns this image
            $post = get_post( $image_id );
            if ( $post && $post->post_author == $user_id ) {
                if ( wp_delete_attachment( $image_id, true ) ) {
                    $deleted_count++;
                }
            }
        }
        
        wp_send_json_success( [
            'message' => "Successfully deleted {$deleted_count} images",
            'deleted_count' => $deleted_count
        ] );
    }
    
    /**
     * Handle HURAII Export - Local file functionality
     */
    public function handle_huraii_export() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        $export_format = sanitize_text_field( $_POST['export_format'] ?? 'png-hd' );
        
        if ( empty( $image_ids ) ) {
            wp_send_json_error( 'No images selected for export' );
        }
        
        $export_specs = [
            'png-hd' => ['format' => 'png', 'quality' => 'high'],
            'jpg-high' => ['format' => 'jpg', 'quality' => 'high'],
            'webp' => ['format' => 'webp', 'quality' => 'optimized'],
            'instagram-post' => ['format' => 'jpg', 'dimensions' => '1080x1080'],
            'instagram-story' => ['format' => 'jpg', 'dimensions' => '1080x1920'],
            'twitter-header' => ['format' => 'jpg', 'dimensions' => '1500x500'],
            'facebook-cover' => ['format' => 'jpg', 'dimensions' => '1200x630'],
            'poster-a3' => ['format' => 'png', 'dimensions' => '3508x4961', 'dpi' => 300]
        ];
        
        $spec = $export_specs[$export_format] ?? $export_specs['png-hd'];
        
        wp_send_json_success( [
            'message' => 'Export prepared successfully',
            'export_format' => $export_format,
            'specifications' => $spec,
            'image_count' => count( $image_ids )
        ] );
    }
    
    /**
     * Handle HURAII Share - Local sharing functionality
     */
    public function handle_huraii_share() {
        check_ajax_referer( 'vortex_huraii_nonce', 'nonce' );
        
        $image_ids = $_POST['image_ids'] ?? [];
        $platform = sanitize_text_field( $_POST['platform'] ?? '' );
        $privacy_level = sanitize_text_field( $_POST['privacy_level'] ?? 'public' );
        $include_prompt = $_POST['include_prompt'] ?? false;
        $watermark = $_POST['watermark'] ?? false;
        
        if ( empty( $image_ids ) || empty( $platform ) ) {
            wp_send_json_error( 'Image and platform are required' );
        }
        
        // Generate sharing URLs
        $share_urls = [];
        $base_url = home_url( '/huraii-share/' );
        
        foreach ( $image_ids as $image_id ) {
            $share_token = wp_generate_password( 12, false );
            $share_url = $base_url . $share_token;
            
            // Store share data
            $share_data = [
                'image_id' => $image_id,
                'platform' => $platform,
                'privacy_level' => $privacy_level,
                'include_prompt' => $include_prompt,
                'watermark' => $watermark,
                'created' => current_time( 'mysql' ),
                'user_id' => get_current_user_id()
            ];
            
            update_option( "huraii_share_{$share_token}", $share_data );
            
            $share_urls[] = [
                'image_id' => $image_id,
                'share_url' => $share_url,
                'token' => $share_token
            ];
        }
        
        wp_send_json_success( [
            'message' => 'Sharing links generated successfully',
            'platform' => $platform,
            'share_urls' => $share_urls
        ] );
    }
}
}

// Bootstrap
if (class_exists('VortexAIEngine_Shortcodes')) {
    VortexAIEngine_Shortcodes::getInstance();
} 