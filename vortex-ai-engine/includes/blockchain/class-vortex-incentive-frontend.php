<?php
/**
 * VORTEX AI Engine - Incentive Frontend Interface
 * 
 * Frontend interface components for the incentive system
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Incentive Frontend Interface Class
 * 
 * Provides frontend interface for incentive system interactions
 */
class Vortex_Incentive_Frontend {
    
    /**
     * Initialize the frontend interface
     */
    public function init() {
        $this->register_hooks();
        $this->enqueue_assets();
        
        error_log('VORTEX AI Engine: Incentive Frontend Interface initialized');
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_footer', [$this, 'render_incentive_modals']);
        add_shortcode('vortex_incentive_dashboard', [$this, 'render_incentive_dashboard']);
        add_shortcode('vortex_wallet_interface', [$this, 'render_wallet_interface']);
        add_shortcode('vortex_conversion_interface', [$this, 'render_conversion_interface']);
        add_shortcode('vortex_incentive_status', [$this, 'render_incentive_status']);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'vortex-incentive-frontend',
            plugin_dir_url(__FILE__) . '../../assets/js/incentive-frontend.js',
            ['jquery'],
            '3.0.0',
            true
        );
        
        wp_enqueue_style(
            'vortex-incentive-frontend',
            plugin_dir_url(__FILE__) . '../../assets/css/incentive-frontend.css',
            [],
            '3.0.0'
        );
        
        // Localize script with AJAX data
        wp_localize_script('vortex-incentive-frontend', 'vortexIncentive', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_incentive_nonce'),
            'conversionNonce' => wp_create_nonce('vortex_conversion_nonce'),
            'integrationNonce' => wp_create_nonce('vortex_integration_nonce'),
            'walletNonce' => wp_create_nonce('vortex_wallet_nonce'),
            'accountingNonce' => wp_create_nonce('vortex_accounting_nonce')
        ]);
    }
    
    /**
     * Enqueue assets
     */
    private function enqueue_assets() {
        // Create assets directory if it doesn't exist
        $assets_dir = plugin_dir_path(__FILE__) . '../../assets/';
        if (!file_exists($assets_dir)) {
            mkdir($assets_dir, 0755, true);
        }
        
        // Create JS and CSS directories
        $js_dir = $assets_dir . 'js/';
        $css_dir = $assets_dir . 'css/';
        
        if (!file_exists($js_dir)) {
            mkdir($js_dir, 0755, true);
        }
        if (!file_exists($css_dir)) {
            mkdir($css_dir, 0755, true);
        }
        
        // Create JavaScript file
        $this->create_js_file($js_dir . 'incentive-frontend.js');
        
        // Create CSS file
        $this->create_css_file($css_dir . 'incentive-frontend.css');
    }
    
    /**
     * Create JavaScript file
     */
    private function create_js_file($file_path) {
        if (file_exists($file_path)) {
            return;
        }
        
        $js_content = '
jQuery(document).ready(function($) {
    "use strict";
    
    // Incentive Dashboard
    window.VortexIncentive = {
        init: function() {
            this.bindEvents();
            this.loadIncentiveStatus();
        },
        
        bindEvents: function() {
            // Connect wallet button
            $(document).on("click", ".vortex-connect-wallet", this.connectWallet);
            
            // Claim incentive button
            $(document).on("click", ".vortex-claim-incentive", this.claimIncentive);
            
            // Conversion request button
            $(document).on("click", ".vortex-request-conversion", this.requestConversion);
            
            // Refresh status button
            $(document).on("click", ".vortex-refresh-status", this.loadIncentiveStatus);
        },
        
        connectWallet: function(e) {
            e.preventDefault();
            
            var walletAddress = prompt("Enter your wallet address:");
            if (!walletAddress) return;
            
            $.ajax({
                url: vortexIncentive.ajaxUrl,
                type: "POST",
                data: {
                    action: "vortex_connect_wallet",
                    nonce: vortexIncentive.walletNonce,
                    wallet_address: walletAddress
                },
                success: function(response) {
                    if (response.success) {
                        alert("Wallet connected successfully!");
                        window.VortexIncentive.loadIncentiveStatus();
                    } else {
                        alert("Error: " + response.data.message);
                    }
                },
                error: function() {
                    alert("Connection failed. Please try again.");
                }
            });
        },
        
        claimIncentive: function(e) {
            e.preventDefault();
            
            var incentiveType = $(this).data("type");
            var contextData = $(this).data("context") || {};
            
            $.ajax({
                url: vortexIncentive.ajaxUrl,
                type: "POST",
                data: {
                    action: "vortex_claim_incentive",
                    nonce: vortexIncentive.nonce,
                    incentive_type: incentiveType,
                    context_data: JSON.stringify(contextData)
                },
                success: function(response) {
                    if (response.success) {
                        alert("Incentive claimed successfully! Amount: " + response.data.tola_amount + " TOLA");
                        window.VortexIncentive.loadIncentiveStatus();
                    } else {
                        alert("Error: " + response.data.error);
                    }
                },
                error: function() {
                    alert("Claim failed. Please try again.");
                }
            });
        },
        
        requestConversion: function(e) {
            e.preventDefault();
            
            var tolaAmount = prompt("Enter TOLA amount to convert:");
            if (!tolaAmount || isNaN(tolaAmount)) return;
            
            var walletAddress = prompt("Enter USDC wallet address:");
            if (!walletAddress) return;
            
            $.ajax({
                url: vortexIncentive.ajaxUrl,
                type: "POST",
                data: {
                    action: "vortex_request_conversion",
                    nonce: vortexIncentive.conversionNonce,
                    tola_amount: tolaAmount,
                    wallet_address: walletAddress
                },
                success: function(response) {
                    if (response.success) {
                        alert("Conversion requested successfully! USDC Amount: " + response.data.usdc_amount);
                        window.VortexIncentive.loadIncentiveStatus();
                    } else {
                        alert("Error: " + response.data.error);
                    }
                },
                error: function() {
                    alert("Conversion failed. Please try again.");
                }
            });
        },
        
        loadIncentiveStatus: function() {
            $.ajax({
                url: vortexIncentive.ajaxUrl,
                type: "POST",
                data: {
                    action: "vortex_get_incentive_status",
                    nonce: vortexIncentive.integrationNonce
                },
                success: function(response) {
                    if (response.success) {
                        window.VortexIncentive.updateStatusDisplay(response.data);
                    }
                }
            });
        },
        
        updateStatusDisplay: function(data) {
            // Update wallet info
            if (data.user_wallet) {
                $(".vortex-wallet-address").text(data.user_wallet.wallet_address);
                $(".vortex-wallet-balance").text(data.user_wallet.balance + " TOLA");
                $(".vortex-platform-credits").text(data.user_platform_credits + " TOLA");
            }
            
            // Update conversion status
            if (data.conversion_system) {
                $(".vortex-conversion-status").text(data.conversion_system.conversion_enabled ? "Enabled" : "Disabled");
                $(".vortex-artist-count").text(data.conversion_system.artist_count + "/" + data.conversion_system.milestone_required);
            }
            
            // Update incentive eligibility
            if (data.conversion_eligible) {
                $(".vortex-conversion-eligible").show();
                $(".vortex-conversion-disabled").hide();
            } else {
                $(".vortex-conversion-eligible").hide();
                $(".vortex-conversion-disabled").show();
            }
        }
    };
    
    // Initialize incentive system
    window.VortexIncentive.init();
});
';
        
        file_put_contents($file_path, $js_content);
    }
    
    /**
     * Create CSS file
     */
    private function create_css_file($file_path) {
        if (file_exists($file_path)) {
            return;
        }
        
        $css_content = '
/* VORTEX Incentive Frontend Styles */
.vortex-incentive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.vortex-incentive-dashboard {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 30px;
    color: white;
    margin-bottom: 30px;
}

.vortex-dashboard-header {
    text-align: center;
    margin-bottom: 30px;
}

.vortex-dashboard-header h2 {
    font-size: 2.5em;
    margin: 0;
    font-weight: 700;
}

.vortex-dashboard-header p {
    font-size: 1.2em;
    opacity: 0.9;
    margin: 10px 0 0 0;
}

.vortex-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.vortex-stat-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.vortex-stat-value {
    font-size: 2em;
    font-weight: 700;
    margin-bottom: 5px;
}

.vortex-stat-label {
    font-size: 0.9em;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.vortex-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.vortex-action-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
}

.vortex-action-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.vortex-wallet-interface {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.vortex-wallet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.vortex-wallet-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.5em;
}

.vortex-wallet-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.vortex-wallet-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.vortex-wallet-label {
    font-size: 0.9em;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.vortex-wallet-value {
    font-size: 1.2em;
    font-weight: 600;
    color: #333;
}

.vortex-conversion-interface {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.vortex-conversion-status {
    background: #e8f5e8;
    border: 1px solid #4caf50;
    color: #2e7d32;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

.vortex-conversion-disabled {
    background: #fff3e0;
    border: 1px solid #ff9800;
    color: #e65100;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

.vortex-incentive-status {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.vortex-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.vortex-status-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.vortex-status-value {
    font-size: 1.5em;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 5px;
}

.vortex-status-label {
    font-size: 0.8em;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .vortex-incentive-container {
        padding: 15px;
    }
    
    .vortex-dashboard-header h2 {
        font-size: 2em;
    }
    
    .vortex-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .vortex-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .vortex-wallet-info {
        grid-template-columns: 1fr;
    }
    
    .vortex-status-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Animation Classes */
.vortex-fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.vortex-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
';
        
        file_put_contents($file_path, $css_content);
    }
    
    /**
     * Render incentive dashboard shortcode
     */
    public function render_incentive_dashboard($atts) {
        $atts = shortcode_atts([
            'show_wallet' => 'true',
            'show_conversion' => 'true',
            'show_status' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-incentive-container">
            <div class="vortex-incentive-dashboard vortex-fade-in">
                <div class="vortex-dashboard-header">
                    <h2>🎨 VORTEX Incentive Dashboard</h2>
                    <p>Track your TOLA rewards, platform credits, and conversion status</p>
                </div>
                
                <div class="vortex-stats-grid">
                    <div class="vortex-stat-card">
                        <div class="vortex-stat-value vortex-wallet-balance">0</div>
                        <div class="vortex-stat-label">Wallet Balance</div>
                    </div>
                    <div class="vortex-stat-card">
                        <div class="vortex-stat-value vortex-platform-credits">0</div>
                        <div class="vortex-stat-label">Platform Credits</div>
                    </div>
                    <div class="vortex-stat-card">
                        <div class="vortex-stat-value vortex-conversion-status">Disabled</div>
                        <div class="vortex-stat-label">Conversion Status</div>
                    </div>
                    <div class="vortex-stat-card">
                        <div class="vortex-stat-value vortex-artist-count">0/1000</div>
                        <div class="vortex-stat-label">Artist Milestone</div>
                    </div>
                </div>
                
                <div class="vortex-actions-grid">
                    <button class="vortex-action-btn vortex-connect-wallet">
                        🔗 Connect Wallet
                    </button>
                    <button class="vortex-action-btn vortex-refresh-status">
                        🔄 Refresh Status
                    </button>
                    <button class="vortex-action-btn vortex-request-conversion" style="display: none;">
                        💱 Convert to USDC
                    </button>
                </div>
            </div>
            
            <?php if ($atts['show_wallet'] === 'true'): ?>
                <?php echo do_shortcode('[vortex_wallet_interface]'); ?>
            <?php endif; ?>
            
            <?php if ($atts['show_conversion'] === 'true'): ?>
                <?php echo do_shortcode('[vortex_conversion_interface]'); ?>
            <?php endif; ?>
            
            <?php if ($atts['show_status'] === 'true'): ?>
                <?php echo do_shortcode('[vortex_incentive_status]'); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render wallet interface shortcode
     */
    public function render_wallet_interface($atts) {
        ob_start();
        ?>
        <div class="vortex-wallet-interface vortex-fade-in">
            <div class="vortex-wallet-header">
                <h3>💼 Wallet Management</h3>
                <button class="vortex-action-btn vortex-connect-wallet">Connect Wallet</button>
            </div>
            
            <div class="vortex-wallet-info">
                <div class="vortex-wallet-item">
                    <div class="vortex-wallet-label">Wallet Address</div>
                    <div class="vortex-wallet-value vortex-wallet-address">Not Connected</div>
                </div>
                <div class="vortex-wallet-item">
                    <div class="vortex-wallet-label">TOLA Balance</div>
                    <div class="vortex-wallet-value vortex-wallet-balance">0 TOLA</div>
                </div>
                <div class="vortex-wallet-item">
                    <div class="vortex-wallet-label">Platform Credits</div>
                    <div class="vortex-wallet-value vortex-platform-credits">0 TOLA</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render conversion interface shortcode
     */
    public function render_conversion_interface($atts) {
        ob_start();
        ?>
        <div class="vortex-conversion-interface vortex-fade-in">
            <h3>💱 USDC Conversion</h3>
            
            <div class="vortex-conversion-status vortex-conversion-disabled" style="display: none;">
                ⏳ Conversion will be enabled when we reach 1000 artists
            </div>
            
            <div class="vortex-conversion-status vortex-conversion-eligible" style="display: none;">
                ✅ Conversion is now enabled! Convert your platform credits to USDC
            </div>
            
            <div class="vortex-actions-grid">
                <button class="vortex-action-btn vortex-request-conversion">
                    💱 Convert to USDC
                </button>
                <button class="vortex-action-btn vortex-refresh-status">
                    🔄 Check Status
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render incentive status shortcode
     */
    public function render_incentive_status($atts) {
        ob_start();
        ?>
        <div class="vortex-incentive-status vortex-fade-in">
            <h3>📊 Incentive Status</h3>
            
            <div class="vortex-status-grid">
                <div class="vortex-status-item">
                    <div class="vortex-status-value vortex-wallet-balance">0</div>
                    <div class="vortex-status-label">Wallet Balance</div>
                </div>
                <div class="vortex-status-item">
                    <div class="vortex-status-value vortex-platform-credits">0</div>
                    <div class="vortex-status-label">Platform Credits</div>
                </div>
                <div class="vortex-status-item">
                    <div class="vortex-status-value vortex-artist-count">0/1000</div>
                    <div class="vortex-status-label">Artist Milestone</div>
                </div>
                <div class="vortex-status-item">
                    <div class="vortex-status-value vortex-conversion-status">Disabled</div>
                    <div class="vortex-status-label">Conversion</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render incentive modals
     */
    public function render_incentive_modals() {
        ?>
        <!-- Incentive Success Modal -->
        <div id="vortex-incentive-modal" class="vortex-modal" style="display: none;">
            <div class="vortex-modal-content">
                <span class="vortex-modal-close">&times;</span>
                <h3>🎉 Incentive Claimed!</h3>
                <p id="vortex-modal-message"></p>
            </div>
        </div>
        
        <style>
        .vortex-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .vortex-modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 500px;
            text-align: center;
            position: relative;
        }
        
        .vortex-modal-close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .vortex-modal-close:hover {
            color: #667eea;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Modal functionality
            $(".vortex-modal-close").click(function() {
                $("#vortex-incentive-modal").hide();
            });
            
            $(window).click(function(e) {
                if (e.target == document.getElementById("vortex-incentive-modal")) {
                    $("#vortex-incentive-modal").hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get frontend interface status
     */
    public function get_status() {
        return [
            'name' => 'VORTEX Incentive Frontend Interface',
            'version' => '3.0.0',
            'shortcodes' => [
                'vortex_incentive_dashboard',
                'vortex_wallet_interface',
                'vortex_conversion_interface',
                'vortex_incentive_status'
            ],
            'assets_loaded' => true
        ];
    }
} 