<?php
// Vault Path: secret/data/vortex-ai/individual_agent_algorithms
/**
 * Individual Agent Shortcodes with Enhanced Orchestration
 * Provides individual access to each HURAII agent with full orchestration pipeline
 * 
 * Features:
 * - Direct shortcode access to individual agents
 * - Complete 7-step orchestration integration
 * - Cost tracking and continuous learning
 * - CLOE multi-agent analysis for describe operations
 * - Real-time performance optimization
 *
 * @package VortexAIEngine
 * @version 3.0.0 Enhanced
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_IndividualShortcodes {
    
    /** @var VortexAIEngine_EnhancedOrchestrator */
    private $enhanced_orchestrator;
    
    /** @var array Default shortcode attributes */
    private $default_atts = [
        'theme' => 'midjourney',
        'height' => '600px',
        'width' => '100%',
        'enable_voice' => 'false',
        'enable_export' => 'true',
        'show_cost' => 'true',
        'show_quality' => 'true'
    ];
    
    public function __construct() {
        $this->enhanced_orchestrator = VortexAIEngine_EnhancedOrchestrator::getInstance();
        $this->register_shortcodes();
        $this->setup_ajax_handlers();
    }
    
    /**
     * Register all individual shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('huraii_generate', [$this, 'render_generate_shortcode']);
        add_shortcode('huraii_describe', [$this, 'render_describe_shortcode']);
        add_shortcode('huraii_upscale', [$this, 'render_upscale_shortcode']);
        add_shortcode('huraii_enhance', [$this, 'render_enhance_shortcode']);
        add_shortcode('huraii_export', [$this, 'render_export_shortcode']);
        add_shortcode('huraii_share', [$this, 'render_share_shortcode']);
        add_shortcode('huraii_upload', [$this, 'render_upload_shortcode']);
        add_shortcode('huraii_save', [$this, 'render_save_shortcode']);
        add_shortcode('huraii_delete', [$this, 'render_delete_shortcode']);
        add_shortcode('huraii_edit', [$this, 'render_edit_shortcode']);
        add_shortcode('huraii_regenerate', [$this, 'render_regenerate_shortcode']);
        add_shortcode('huraii_vary', [$this, 'render_vary_shortcode']);
        add_shortcode('huraii_download', [$this, 'render_download_shortcode']);
        
        // Enqueue assets for individual shortcodes
        add_action('wp_enqueue_scripts', [$this, 'enqueue_individual_shortcode_assets']);
    }
    
    /**
     * Setup AJAX handlers for individual shortcodes
     */
    private function setup_ajax_handlers() {
        // Enhanced AJAX handlers with 7-step orchestration
        add_action('wp_ajax_huraii_individual_generate', [$this, 'handle_individual_generate']);
        add_action('wp_ajax_nopriv_huraii_individual_generate', [$this, 'handle_individual_generate']);
        
        add_action('wp_ajax_huraii_individual_describe', [$this, 'handle_individual_describe']);
        add_action('wp_ajax_nopriv_huraii_individual_describe', [$this, 'handle_individual_describe']);
        
        add_action('wp_ajax_huraii_individual_upscale', [$this, 'handle_individual_upscale']);
        add_action('wp_ajax_nopriv_huraii_individual_upscale', [$this, 'handle_individual_upscale']);
        
        add_action('wp_ajax_huraii_individual_enhance', [$this, 'handle_individual_enhance']);
        add_action('wp_ajax_nopriv_huraii_individual_enhance', [$this, 'handle_individual_enhance']);
        
        add_action('wp_ajax_huraii_individual_export', [$this, 'handle_individual_export']);
        add_action('wp_ajax_nopriv_huraii_individual_export', [$this, 'handle_individual_export']);
        
        add_action('wp_ajax_huraii_individual_share', [$this, 'handle_individual_share']);
        add_action('wp_ajax_nopriv_huraii_individual_share', [$this, 'handle_individual_share']);
    }
    
    /**
     * Enqueue assets for individual shortcodes
     */
    public function enqueue_individual_shortcode_assets() {
        // Individual shortcode CSS
        wp_enqueue_style(
            'vortex-individual-shortcodes',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/individual-shortcodes.css',
            ['vortex-huraii-css'],
            VORTEX_AI_ENGINE_VERSION
        );
        
        // Individual shortcode JS
        wp_enqueue_script(
            'vortex-individual-shortcodes',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/individual-shortcodes.js',
            ['jquery', 'vortex-huraii-js'],
            VORTEX_AI_ENGINE_VERSION,
            true
        );
        
        // Localize script with enhanced configuration
        wp_localize_script('vortex-individual-shortcodes', 'vortexIndividualConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('vortex/v1/'),
            'nonce' => wp_create_nonce('vortex_individual_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'orchestrationEndpoint' => admin_url('admin-ajax.php?action=huraii_individual_'),
            'costTracking' => get_option('vortex_cost_tracking_enabled', true),
            'profitMargin' => get_option('vortex_target_profit_margin', 0.80),
            'continuousLearning' => get_option('vortex_continuous_learning_enabled', true)
        ]);
    }
    
    /**
     * [huraii_generate] - AI Art Generation Shortcode
     */
    public function render_generate_shortcode($atts) {
        $atts = shortcode_atts(array_merge($this->default_atts, [
            'default_prompt' => '',
            'style_presets' => 'artistic,photorealistic,abstract,modern',
            'aspect_ratios' => '1:1,16:9,9:16,4:3',
            'quality_levels' => 'standard,high,ultra',
            'show_advanced' => 'true'
        ]), $atts);
        
        $user_id = get_current_user_id();
        
        ob_start();
        ?>
        <div class="huraii-individual-shortcode huraii-generate-container" data-shortcode="generate">
            <div class="huraii-individual-header">
                <h3>🎨 AI Art Generation</h3>
                <p>Create stunning AI artwork with advanced orchestration</p>
                <?php if ($atts['show_cost'] === 'true'): ?>
                    <div class="cost-tracker">
                        <span>Session Cost: $<span class="session-cost">0.00</span></span>
                        <span>Profit Margin: <span class="profit-margin">80%</span></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="huraii-generate-form">
                <div class="prompt-section">
                    <label for="generate-prompt">Describe your artwork:</label>
                    <textarea 
                        id="generate-prompt" 
                        class="generate-prompt-textarea" 
                        placeholder="A majestic sunset over mountains, painted in vibrant colors..."
                        rows="4"
                    ><?php echo esc_textarea($atts['default_prompt']); ?></textarea>
                </div>
                
                <div class="generation-controls">
                    <div class="style-presets">
                        <label>Style Preset:</label>
                        <div class="preset-buttons">
                            <?php foreach (explode(',', $atts['style_presets']) as $preset): ?>
                                <button type="button" class="preset-btn" data-preset="<?php echo esc_attr($preset); ?>">
                                    <?php echo esc_html(ucfirst($preset)); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="aspect-ratios">
                        <label>Aspect Ratio:</label>
                        <div class="ratio-buttons">
                            <?php foreach (explode(',', $atts['aspect_ratios']) as $ratio): ?>
                                <button type="button" class="ratio-btn" data-ratio="<?php echo esc_attr($ratio); ?>">
                                    <?php echo esc_html($ratio); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="quality-controls">
                        <label>Quality Level:</label>
                        <select id="quality-level" class="quality-select">
                            <?php foreach (explode(',', $atts['quality_levels']) as $level): ?>
                                <option value="<?php echo esc_attr($level); ?>"><?php echo esc_html(ucfirst($level)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php if ($atts['show_advanced'] === 'true'): ?>
                    <div class="advanced-controls">
                        <details>
                            <summary>Advanced Settings</summary>
                            <div class="advanced-settings">
                                <div class="setting-group">
                                    <label>Creativity Level:</label>
                                    <input type="range" id="creativity-level" min="0" max="100" value="70" class="creativity-slider">
                                    <span class="slider-value">70%</span>
                                </div>
                                <div class="setting-group">
                                    <label>Detail Level:</label>
                                    <input type="range" id="detail-level" min="0" max="100" value="80" class="detail-slider">
                                    <span class="slider-value">80%</span>
                                </div>
                                <div class="setting-group">
                                    <label>AI Agents:</label>
                                    <div class="agent-selection">
                                        <input type="checkbox" id="use-huraii" checked> <label for="use-huraii">HURAII (Artistic)</label>
                                        <input type="checkbox" id="use-archer"> <label for="use-archer">ARCHER (Orchestrator)</label>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>
                
                <div class="generation-actions">
                    <button type="button" class="huraii-btn huraii-btn-primary generate-btn" data-action="generate">
                        <span class="btn-text">Generate Artwork</span>
                        <span class="btn-loading" style="display: none;">🎨 Creating...</span>
                    </button>
                    <?php if ($atts['enable_voice'] === 'true'): ?>
                        <button type="button" class="huraii-btn huraii-btn-secondary voice-btn" data-action="voice">
                            🎤 Voice Command
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="generation-results" id="generation-results">
                <!-- Results will be populated here -->
            </div>
            
            <?php if ($atts['show_quality'] === 'true'): ?>
                <div class="quality-metrics">
                    <div class="metric-item">
                        <label>Quality Score:</label>
                        <div class="quality-bar">
                            <div class="quality-fill" style="width: 0%"></div>
                        </div>
                        <span class="quality-value">-</span>
                    </div>
                    <div class="metric-item">
                        <label>Processing Time:</label>
                        <span class="processing-time">-</span>
                    </div>
                    <div class="metric-item">
                        <label>Agents Used:</label>
                        <span class="agents-used">-</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * [huraii_describe] - AI Description Shortcode (CHLOE Analysis)
     */
    public function render_describe_shortcode($atts) {
        $atts = shortcode_atts(array_merge($this->default_atts, [
            'analysis_depth' => 'comprehensive',
            'include_style' => 'true',
            'include_composition' => 'true',
            'include_technical' => 'true',
            'include_emotional' => 'true',
            'agents' => 'cloe,horace,archer'
        ]), $atts);
        
        ob_start();
        ?>
        <div class="huraii-individual-shortcode huraii-describe-container" data-shortcode="describe">
            <div class="huraii-individual-header">
                <h3>👁️ AI Description & Analysis (CHLOE)</h3>
                <p>Comprehensive 5-Agent analysis with Vault consultation</p>
                <?php if ($atts['show_cost'] === 'true'): ?>
                    <div class="cost-tracker">
                        <span>Analysis Cost: $<span class="session-cost">0.00</span></span>
                        <span>Profit Margin: <span class="profit-margin">80%</span></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="huraii-describe-form">
                <div class="input-section">
                    <label for="describe-input">Describe your image or idea:</label>
                    <textarea 
                        id="describe-input" 
                        class="describe-input-textarea" 
                        placeholder="Upload an image or describe your idea for comprehensive AI analysis..."
                        rows="4"
                    ></textarea>
                </div>
                
                <div class="upload-section">
                    <label>Or upload an image for analysis:</label>
                    <div class="upload-area" id="describe-upload-area">
                        <div class="upload-placeholder">
                            <div class="upload-icon">📤</div>
                            <div class="upload-text">
                                <p>Drag & drop an image here or click to browse</p>
                                <p class="upload-hint">Supported: JPG, PNG, GIF (Max 10MB)</p>
                            </div>
                            <input type="file" id="describe-file-input" accept="image/*" hidden>
                        </div>
                    </div>
                </div>
                
                <div class="analysis-options">
                    <div class="analysis-depth">
                        <label>Analysis Depth:</label>
                        <select id="analysis-depth" class="analysis-select">
                            <option value="basic">Basic Analysis</option>
                            <option value="comprehensive" selected>Comprehensive Analysis</option>
                            <option value="expert">Expert Analysis</option>
                        </select>
                    </div>
                    
                    <div class="analysis-aspects">
                        <label>Include in Analysis:</label>
                        <div class="aspect-checkboxes">
                            <input type="checkbox" id="include-style" <?php echo $atts['include_style'] === 'true' ? 'checked' : ''; ?>>
                            <label for="include-style">Style Analysis</label>
                            
                            <input type="checkbox" id="include-composition" <?php echo $atts['include_composition'] === 'true' ? 'checked' : ''; ?>>
                            <label for="include-composition">Composition</label>
                            
                            <input type="checkbox" id="include-technical" <?php echo $atts['include_technical'] === 'true' ? 'checked' : ''; ?>>
                            <label for="include-technical">Technical Details</label>
                            
                            <input type="checkbox" id="include-emotional" <?php echo $atts['include_emotional'] === 'true' ? 'checked' : ''; ?>>
                            <label for="include-emotional">Emotional Impact</label>
                        </div>
                    </div>
                    
                    <div class="agent-selection">
                        <label>AI Agents to Use:</label>
                        <div class="agent-checkboxes">
                            <?php foreach (explode(',', $atts['agents']) as $agent): ?>
                                <input type="checkbox" id="agent-<?php echo esc_attr($agent); ?>" checked>
                                <label for="agent-<?php echo esc_attr($agent); ?>"><?php echo esc_html(strtoupper($agent)); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="describe-actions">
                    <button type="button" class="huraii-btn huraii-btn-primary describe-btn" data-action="describe">
                        <span class="btn-text">Analyze with CHLOE</span>
                        <span class="btn-loading" style="display: none;">🔍 Analyzing...</span>
                    </button>
                    <button type="button" class="huraii-btn huraii-btn-secondary clear-btn" data-action="clear">
                        Clear Input
                    </button>
                </div>
            </div>
            
            <div class="describe-results" id="describe-results">
                <!-- Analysis results will be populated here -->
            </div>
            
            <?php if ($atts['show_quality'] === 'true'): ?>
                <div class="analysis-metrics">
                    <div class="metric-group">
                        <h4>Analysis Quality</h4>
                        <div class="quality-indicators">
                            <div class="indicator">
                                <label>CLOE (Analysis):</label>
                                <div class="quality-bar"><div class="quality-fill" style="width: 0%"></div></div>
                                <span class="quality-value">-</span>
                            </div>
                            <div class="indicator">
                                <label>HORACE (Synthesis):</label>
                                <div class="quality-bar"><div class="quality-fill" style="width: 0%"></div></div>
                                <span class="quality-value">-</span>
                            </div>
                            <div class="indicator">
                                <label>ARCHER (Orchestration):</label>
                                <div class="quality-bar"><div class="quality-fill" style="width: 0%"></div></div>
                                <span class="quality-value">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * [huraii_upscale] - Image Upscaling Shortcode
     */
    public function render_upscale_shortcode($atts) {
        $atts = shortcode_atts(array_merge($this->default_atts, [
            'max_upscale' => '4x',
            'quality_modes' => 'standard,high,ultra',
            'enhancement_options' => 'true'
        ]), $atts);
        
        ob_start();
        ?>
        <div class="huraii-individual-shortcode huraii-upscale-container" data-shortcode="upscale">
            <div class="huraii-individual-header">
                <h3>🔍 AI Image Upscaling</h3>
                <p>Enhance resolution with AI-powered upscaling</p>
                <?php if ($atts['show_cost'] === 'true'): ?>
                    <div class="cost-tracker">
                        <span>Upscale Cost: $<span class="session-cost">0.00</span></span>
                        <span>Profit Margin: <span class="profit-margin">80%</span></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="upscale-form">
                <div class="image-upload">
                    <label>Select image to upscale:</label>
                    <div class="upload-area" id="upscale-upload-area">
                        <div class="upload-placeholder">
                            <div class="upload-icon">🖼️</div>
                            <div class="upload-text">
                                <p>Drag & drop an image here or click to browse</p>
                                <p class="upload-hint">Best results with images 512x512 or larger</p>
                            </div>
                            <input type="file" id="upscale-file-input" accept="image/*" hidden>
                        </div>
                    </div>
                </div>
                
                <div class="upscale-options">
                    <div class="upscale-factor">
                        <label>Upscale Factor:</label>
                        <div class="factor-buttons">
                            <button type="button" class="factor-btn" data-factor="2">2x</button>
                            <button type="button" class="factor-btn" data-factor="3">3x</button>
                            <button type="button" class="factor-btn active" data-factor="4">4x</button>
                        </div>
                    </div>
                    
                    <div class="quality-mode">
                        <label>Quality Mode:</label>
                        <select id="upscale-quality" class="quality-select">
                            <?php foreach (explode(',', $atts['quality_modes']) as $mode): ?>
                                <option value="<?php echo esc_attr($mode); ?>"><?php echo esc_html(ucfirst($mode)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($atts['enhancement_options'] === 'true'): ?>
                        <div class="enhancement-options">
                            <label>Enhancement Options:</label>
                            <div class="enhancement-checkboxes">
                                <input type="checkbox" id="enhance-details" checked>
                                <label for="enhance-details">Enhance Details</label>
                                
                                <input type="checkbox" id="reduce-noise">
                                <label for="reduce-noise">Reduce Noise</label>
                                
                                <input type="checkbox" id="sharpen-edges">
                                <label for="sharpen-edges">Sharpen Edges</label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="upscale-actions">
                    <button type="button" class="huraii-btn huraii-btn-primary upscale-btn" data-action="upscale">
                        <span class="btn-text">Upscale Image</span>
                        <span class="btn-loading" style="display: none;">🔍 Upscaling...</span>
                    </button>
                </div>
            </div>
            
            <div class="upscale-results" id="upscale-results">
                <!-- Results will be populated here -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * [huraii_enhance] - Image Enhancement Shortcode
     */
    public function render_enhance_shortcode($atts) {
        $atts = shortcode_atts(array_merge($this->default_atts, [
            'enhancement_types' => 'super-res,detail,artistic,color',
            'batch_processing' => 'true'
        ]), $atts);
        
        ob_start();
        ?>
        <div class="huraii-individual-shortcode huraii-enhance-container" data-shortcode="enhance">
            <div class="huraii-individual-header">
                <h3>✨ AI Image Enhancement</h3>
                <p>Professional image enhancement with multiple AI agents</p>
                <?php if ($atts['show_cost'] === 'true'): ?>
                    <div class="cost-tracker">
                        <span>Enhancement Cost: $<span class="session-cost">0.00</span></span>
                        <span>Profit Margin: <span class="profit-margin">80%</span></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="enhance-form">
                <div class="enhancement-types">
                    <label>Enhancement Type:</label>
                    <div class="enhancement-grid">
                        <?php foreach (explode(',', $atts['enhancement_types']) as $type): ?>
                            <div class="enhancement-card" data-type="<?php echo esc_attr($type); ?>">
                                <div class="enhancement-icon">
                                    <?php
                                    $icons = [
                                        'super-res' => '🔍',
                                        'detail' => '✨',
                                        'artistic' => '🎨',
                                        'color' => '🌈'
                                    ];
                                    echo $icons[$type] ?? '✨';
                                    ?>
                                </div>
                                <div class="enhancement-title"><?php echo esc_html(ucfirst(str_replace('-', ' ', $type))); ?></div>
                                <div class="enhancement-desc">
                                    <?php
                                    $descriptions = [
                                        'super-res' => 'Increase resolution while preserving quality',
                                        'detail' => 'Add fine details and textures',
                                        'artistic' => 'Enhance artistic style and refinement',
                                        'color' => 'Improve colors and vibrancy'
                                    ];
                                    echo $descriptions[$type] ?? 'Enhanced processing';
                                    ?>
                                </div>
                                <button type="button" class="enhance-btn" data-enhance="<?php echo esc_attr($type); ?>">
                                    Enhance
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="image-selection">
                    <label>Select images to enhance:</label>
                    <div class="image-grid" id="enhancement-image-grid">
                        <!-- Images will be populated here -->
                    </div>
                </div>
                
                <?php if ($atts['batch_processing'] === 'true'): ?>
                    <div class="batch-options">
                        <label>Batch Processing:</label>
                        <div class="batch-controls">
                            <button type="button" class="huraii-btn huraii-btn-secondary select-all-btn">
                                Select All
                            </button>
                            <button type="button" class="huraii-btn huraii-btn-secondary clear-selection-btn">
                                Clear Selection
                            </button>
                            <button type="button" class="huraii-btn huraii-btn-primary batch-enhance-btn">
                                Enhance Selected
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="enhance-results" id="enhance-results">
                <!-- Results will be populated here -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * [huraii_export] - Export Functionality Shortcode
     */
    public function render_export_shortcode($atts) {
        $atts = shortcode_atts(array_merge($this->default_atts, [
            'export_formats' => 'jpg,png,webp,svg,pdf',
            'quality_options' => 'web,print,archive',
            'batch_export' => 'true'
        ]), $atts);
        
        ob_start();
        ?>
        <div class="huraii-individual-shortcode huraii-export-container" data-shortcode="export">
            <div class="huraii-individual-header">
                <h3>📤 Export & Download</h3>
                <p>Export your artwork in multiple formats</p>
            </div>
            
            <div class="export-form">
                <div class="format-selection">
                    <label>Export Format:</label>
                    <div class="format-buttons">
                        <?php foreach (explode(',', $atts['export_formats']) as $format): ?>
                            <button type="button" class="format-btn" data-format="<?php echo esc_attr($format); ?>">
                                <?php echo esc_html(strtoupper($format)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="quality-selection">
                    <label>Quality Preset:</label>
                    <div class="quality-options">
                        <?php foreach (explode(',', $atts['quality_options']) as $option): ?>
                            <div class="quality-option" data-quality="<?php echo esc_attr($option); ?>">
                                <input type="radio" name="export-quality" id="quality-<?php echo esc_attr($option); ?>" value="<?php echo esc_attr($option); ?>">
                                <label for="quality-<?php echo esc_attr($option); ?>"><?php echo esc_html(ucfirst($option)); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="export-actions">
                    <button type="button" class="huraii-btn huraii-btn-primary export-btn" data-action="export">
                        <span class="btn-text">Export Selected</span>
                        <span class="btn-loading" style="display: none;">📤 Exporting...</span>
                    </button>
                </div>
            </div>
            
            <div class="export-results" id="export-results">
                <!-- Results will be populated here -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * [huraii_share] - Social Sharing Shortcode
     */
    public function render_share_shortcode($atts) {
        $atts = shortcode_atts(array_merge($this->default_atts, [
            'platforms' => 'facebook,twitter,instagram,linkedin,pinterest',
            'privacy_levels' => 'public,private,friends',
            'watermark_options' => 'true'
        ]), $atts);
        
        ob_start();
        ?>
        <div class="huraii-individual-shortcode huraii-share-container" data-shortcode="share">
            <div class="huraii-individual-header">
                <h3>🔗 Social Sharing</h3>
                <p>Share your artwork across social platforms</p>
            </div>
            
            <div class="share-form">
                <div class="platform-selection">
                    <label>Choose Platform:</label>
                    <div class="platform-buttons">
                        <?php foreach (explode(',', $atts['platforms']) as $platform): ?>
                            <button type="button" class="platform-btn" data-platform="<?php echo esc_attr($platform); ?>">
                                <span class="platform-icon">
                                    <?php
                                    $icons = [
                                        'facebook' => '📘',
                                        'twitter' => '🐦',
                                        'instagram' => '📷',
                                        'linkedin' => '💼',
                                        'pinterest' => '📌'
                                    ];
                                    echo $icons[$platform] ?? '🔗';
                                    ?>
                                </span>
                                <?php echo esc_html(ucfirst($platform)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="privacy-settings">
                    <label>Privacy Level:</label>
                    <select id="privacy-level" class="privacy-select">
                        <?php foreach (explode(',', $atts['privacy_levels']) as $level): ?>
                            <option value="<?php echo esc_attr($level); ?>"><?php echo esc_html(ucfirst($level)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($atts['watermark_options'] === 'true'): ?>
                    <div class="watermark-options">
                        <label>Watermark Options:</label>
                        <div class="watermark-controls">
                            <input type="checkbox" id="add-watermark">
                            <label for="add-watermark">Add Watermark</label>
                            
                            <input type="checkbox" id="include-signature">
                            <label for="include-signature">Include Signature</label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="share-actions">
                    <button type="button" class="huraii-btn huraii-btn-primary share-btn" data-action="share">
                        <span class="btn-text">Share Now</span>
                        <span class="btn-loading" style="display: none;">🔗 Sharing...</span>
                    </button>
                </div>
            </div>
            
            <div class="share-results" id="share-results">
                <!-- Results will be populated here -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enhanced AJAX handler for Generate with 7-step orchestration
     */
    public function handle_individual_generate() {
        check_ajax_referer('vortex_individual_nonce', 'nonce');
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $style = sanitize_text_field($_POST['style'] ?? 'artistic');
        $quality = sanitize_text_field($_POST['quality'] ?? 'standard');
        $user_id = get_current_user_id();
        
        if (empty($prompt)) {
            wp_send_json_error('Prompt is required');
        }
        
        try {
            // Execute enhanced orchestration with 7-step pipeline
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'generate',
                [
                    'query' => $prompt,
                    'style' => $style,
                    'quality' => $quality,
                    'user_preferences' => $this->get_user_preferences($user_id)
                ],
                $user_id
            );
            
            wp_send_json_success([
                'message' => 'Artwork generated successfully',
                'result' => $result,
                'orchestration_data' => [
                    'steps_completed' => 7,
                    'cost_analysis' => $result['cost_analysis'],
                    'quality_metrics' => $result['performance_metrics'],
                    'continuous_learning' => $result['continuous_learning']
                ]
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Generation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Enhanced AJAX handler for Describe with CHLOE analysis
     */
    public function handle_individual_describe() {
        check_ajax_referer('vortex_individual_nonce', 'nonce');
        
        $input = sanitize_textarea_field($_POST['input'] ?? '');
        $analysis_depth = sanitize_text_field($_POST['analysis_depth'] ?? 'comprehensive');
        $aspects = $_POST['aspects'] ?? [];
        $user_id = get_current_user_id();
        
        if (empty($input)) {
            wp_send_json_error('Input is required for analysis');
        }
        
        try {
            // Execute enhanced orchestration with CHLOE (CLOE) analysis
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'describe',
                [
                    'query' => $input,
                    'analysis_depth' => $analysis_depth,
                    'aspects' => $aspects,
                    'specialized_agents' => ['cloe', 'horace', 'archer'] // CHLOE analysis team
                ],
                $user_id
            );
            
            wp_send_json_success([
                'message' => 'Analysis completed successfully',
                'result' => $result,
                'chloe_analysis' => [
                    'analysis_depth' => $analysis_depth,
                    'aspects_analyzed' => $aspects,
                    'agent_contributions' => $result['results'],
                    'quality_scores' => $result['performance_metrics']['quality_score']
                ]
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Analysis failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Enhanced AJAX handler for other actions
     */
    public function handle_individual_upscale() {
        check_ajax_referer('vortex_individual_nonce', 'nonce');
        
        $image_id = sanitize_text_field($_POST['image_id'] ?? '');
        $upscale_factor = intval($_POST['upscale_factor'] ?? 2);
        $user_id = get_current_user_id();
        
        try {
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'upscale',
                [
                    'image_id' => $image_id,
                    'upscale_factor' => $upscale_factor,
                    'enhancement_options' => $_POST['enhancement_options'] ?? []
                ],
                $user_id
            );
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Upscale failed: ' . $e->getMessage());
        }
    }
    
    public function handle_individual_enhance() {
        check_ajax_referer('vortex_individual_nonce', 'nonce');
        
        $image_ids = $_POST['image_ids'] ?? [];
        $enhancement_type = sanitize_text_field($_POST['enhancement_type'] ?? 'super-res');
        $user_id = get_current_user_id();
        
        try {
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'enhance',
                [
                    'image_ids' => $image_ids,
                    'enhancement_type' => $enhancement_type
                ],
                $user_id
            );
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Enhancement failed: ' . $e->getMessage());
        }
    }
    
    public function handle_individual_export() {
        check_ajax_referer('vortex_individual_nonce', 'nonce');
        
        $image_ids = $_POST['image_ids'] ?? [];
        $format = sanitize_text_field($_POST['format'] ?? 'jpg');
        $quality = sanitize_text_field($_POST['quality'] ?? 'web');
        $user_id = get_current_user_id();
        
        try {
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'export',
                [
                    'image_ids' => $image_ids,
                    'format' => $format,
                    'quality' => $quality
                ],
                $user_id
            );
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Export failed: ' . $e->getMessage());
        }
    }
    
    public function handle_individual_share() {
        check_ajax_referer('vortex_individual_nonce', 'nonce');
        
        $image_ids = $_POST['image_ids'] ?? [];
        $platform = sanitize_text_field($_POST['platform'] ?? 'facebook');
        $privacy = sanitize_text_field($_POST['privacy'] ?? 'public');
        $user_id = get_current_user_id();
        
        try {
            $result = $this->enhanced_orchestrator->executeEnhancedOrchestration(
                'share',
                [
                    'image_ids' => $image_ids,
                    'platform' => $platform,
                    'privacy' => $privacy
                ],
                $user_id
            );
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Share failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user preferences for personalization
     */
    private function get_user_preferences($user_id) {
        return get_user_meta($user_id, 'vortex_ai_preferences', true) ?: [
            'preferred_style' => 'artistic',
            'quality_preference' => 'high',
            'cost_sensitivity' => 'medium'
        ];
    }
}

// Initialize individual shortcodes
new VortexAIEngine_IndividualShortcodes(); 