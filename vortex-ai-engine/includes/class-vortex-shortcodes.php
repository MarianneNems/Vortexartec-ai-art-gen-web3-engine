<?php
/**
 * VORTEX AI Engine - Shortcodes Class
 * 
 * Handles shortcode registration and rendering
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX Shortcodes Class
 */
class VORTEX_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_shortcodes();
    }
    
    public function register_shortcodes() {
        add_shortcode('huraii_generate', array($this, 'render_huraii_generate_shortcode'));
        add_shortcode('vortex_wallet', array($this, 'render_vortex_wallet_shortcode'));
        add_shortcode('vortex_swap', array($this, 'render_vortex_swap_shortcode'));
        add_shortcode('vortex_metric', array($this, 'render_vortex_metric_shortcode'));
        add_shortcode('vortex_chat', array($this, 'render_vortex_chat_shortcode'));
        add_shortcode('vortex_feedback', array($this, 'render_vortex_feedback_shortcode'));
    }
    
    public function render_huraii_generate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'prompt' => '',
            'style' => 'default'
        ), $atts);
        
        $output = '<div id="vortex-huraii-generator" class="vortex-shortcode">';
        $output .= '<h3>AI Art Generator</h3>';
        $output .= '<form class="vortex-art-form">';
        $output .= '<textarea name="prompt" placeholder="Describe the art you want to generate...">' . esc_attr($atts['prompt']) . '</textarea>';
        $output .= '<button type="submit" class="vortex-generate-btn">Generate Art</button>';
        $output .= '</form>';
        $output .= '<div id="vortex-art-result"></div>';
        $output .= '</div>';
        
        return $output;
    }
    
    public function render_vortex_wallet_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_balance' => 'true',
            'show_transactions' => 'true'
        ), $atts);
        
        $output = '<div id="vortex-wallet" class="vortex-shortcode">';
        $output .= '<h3>VORTEX Wallet</h3>';
        $output .= '<div class="vortex-wallet-balance">';
        $output .= '<span class="vortex-balance-label">Balance:</span>';
        $output .= '<span class="vortex-balance-amount">0.00 TOLA</span>';
        $output .= '</div>';
        $output .= '<div class="vortex-wallet-actions">';
        $output .= '<button class="vortex-connect-wallet">Connect Wallet</button>';
        $output .= '<button class="vortex-send-tokens">Send Tokens</button>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    public function render_vortex_swap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'default_from' => 'TOLA',
            'default_to' => 'USDC'
        ), $atts);
        
        $output = '<div id="vortex-swap" class="vortex-shortcode">';
        $output .= '<h3>Token Swap</h3>';
        $output .= '<form class="vortex-swap-form">';
        $output .= '<div class="vortex-swap-input">';
        $output .= '<label>From:</label>';
        $output .= '<select name="from_token" class="vortex-from-token">';
        $output .= '<option value="TOLA">TOLA</option>';
        $output .= '<option value="USDC">USDC</option>';
        $output .= '<option value="SOL">SOL</option>';
        $output .= '</select>';
        $output .= '<input type="number" name="amount" class="vortex-amount-input" placeholder="Amount">';
        $output .= '</div>';
        $output .= '<div class="vortex-swap-output">';
        $output .= '<label>To:</label>';
        $output .= '<select name="to_token" class="vortex-to-token">';
        $output .= '<option value="USDC">USDC</option>';
        $output .= '<option value="TOLA">TOLA</option>';
        $output .= '<option value="SOL">SOL</option>';
        $output .= '</select>';
        $output .= '<span class="vortex-output-amount">0.00</span>';
        $output .= '</div>';
        $output .= '<button type="submit" class="vortex-swap-button">Swap</button>';
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }
    
    public function render_vortex_metric_shortcode($atts) {
        $atts = shortcode_atts(array(
            'metric' => 'transactions',
            'period' => '24h'
        ), $atts);
        
        $output = '<div id="vortex-metrics" class="vortex-shortcode">';
        $output .= '<h3>VORTEX Metrics</h3>';
        $output .= '<div class="vortex-metric-card">';
        $output .= '<div class="vortex-metric-value" data-metric="' . esc_attr($atts['metric']) . '">0</div>';
        $output .= '<div class="vortex-metric-label">' . esc_html(ucfirst($atts['metric'])) . '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    public function render_vortex_chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'agent' => 'cloe'
        ), $atts);
        
        $output = '<div id="vortex-chat" class="vortex-shortcode">';
        $output .= '<h3>AI Chat - ' . esc_html(ucfirst($atts['agent'])) . '</h3>';
        $output .= '<div class="vortex-chat-messages"></div>';
        $output .= '<form class="vortex-chat-form">';
        $output .= '<input type="text" name="message" class="vortex-chat-input" placeholder="Ask me anything...">';
        $output .= '<button type="submit" class="vortex-chat-send">Send</button>';
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }
    
    public function render_vortex_feedback_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'general'
        ), $atts);
        
        $output = '<div id="vortex-feedback" class="vortex-shortcode">';
        $output .= '<h3>Feedback</h3>';
        $output .= '<form class="vortex-feedback-form">';
        $output .= '<textarea name="feedback" class="vortex-feedback-input" placeholder="Share your feedback..."></textarea>';
        $output .= '<div class="vortex-feedback-rating">';
        $output .= '<span>Rating:</span>';
        $output .= '<div class="vortex-stars">';
        for ($i = 1; $i <= 5; $i++) {
            $output .= '<span class="vortex-star" data-rating="' . $i . '">★</span>';
        }
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<button type="submit" class="vortex-feedback-submit">Submit Feedback</button>';
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }
    
    public function testConnection() {
        return array(
            'status' => 'success',
            'message' => 'VORTEX Shortcodes loaded successfully',
            'shortcodes' => array(
                'huraii_generate',
                'vortex_wallet',
                'vortex_swap',
                'vortex_metric',
                'vortex_chat',
                'vortex_feedback'
            )
        );
    }
} 