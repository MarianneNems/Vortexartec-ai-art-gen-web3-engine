<?php
/**
 * VORTEX NFT Shortcodes
 * 
 * WordPress shortcodes for TOLA NFT minting and royalty management
 * 
 * @package VortexAI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_NFT_Shortcodes {
    
    private $solana_integration;
    
    public function __construct() {
        $this->solana_integration = new VortexAIEngine_Solana_Integration();
        $this->init_shortcodes();
    }
    
    /**
     * Initialize shortcodes
     */
    private function init_shortcodes() {
        add_shortcode('tola_mint_status', [$this, 'render_mint_status']);
        add_shortcode('tola_royalty_manager', [$this, 'render_royalty_manager']);
        add_shortcode('tola_nft_gallery', [$this, 'render_nft_gallery']);
        add_shortcode('tola_wallet_connect', [$this, 'render_wallet_connect']);
        add_shortcode('tola_marketplace_link', [$this, 'render_marketplace_link']);
        add_shortcode('tola_nft_stats', [$this, 'render_nft_stats']);
    }
    
    /**
     * Render mint status shortcode
     * Usage: [tola_mint_status artwork_id="123"]
     */
    public function render_mint_status($atts) {
        $atts = shortcode_atts([
            'artwork_id' => '',
            'wallet' => '',
            'show_details' => 'true'
        ], $atts);
        
        if (empty($atts['artwork_id'])) {
            return '<div class="tola-error">Artwork ID is required</div>';
        }
        
        // Get NFT data
        $nft_data = $this->get_nft_data($atts['artwork_id']);
        
        if (!$nft_data) {
            return '<div class="tola-error">NFT not found</div>';
        }
        
        $show_details = $atts['show_details'] === 'true';
        
        ob_start();
        ?>
        <div class="tola-mint-status" data-artwork-id="<?php echo esc_attr($atts['artwork_id']); ?>">
            <div class="tola-mint-header">
                <h3>TOLA NFT Status</h3>
                <span class="tola-status-badge <?php echo $nft_data->signature ? 'success' : 'pending'; ?>">
                    <?php echo $nft_data->signature ? 'Minted' : 'Processing'; ?>
                </span>
            </div>
            
            <?php if ($show_details && $nft_data->signature): ?>
            <div class="tola-mint-details">
                <div class="tola-detail-item">
                    <label>Artwork ID:</label>
                    <span><?php echo esc_html($nft_data->artwork_id); ?></span>
                </div>
                
                <div class="tola-detail-item">
                    <label>Network:</label>
                    <span><?php echo esc_html(strtoupper($nft_data->network)); ?></span>
                </div>
                
                <div class="tola-detail-item">
                    <label>Transaction:</label>
                    <a href="<?php echo esc_url($this->get_explorer_url($nft_data->signature)); ?>" 
                       target="_blank" rel="noopener">
                        <?php echo esc_html(substr($nft_data->signature, 0, 20) . '...'); ?>
                    </a>
                </div>
                
                <div class="tola-detail-item">
                    <label>Created:</label>
                    <span><?php echo esc_html(date('M j, Y g:i A', strtotime($nft_data->created_at))); ?></span>
                </div>
                
                <?php if ($nft_data->royalty_fee_percent): ?>
                <div class="tola-detail-item">
                    <label>Royalty:</label>
                    <span><?php echo esc_html($nft_data->royalty_fee_percent); ?>%</span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="tola-mint-actions">
                <?php if ($nft_data->signature): ?>
                <a href="<?php echo esc_url($this->get_marketplace_url($nft_data->artwork_id)); ?>" 
                   class="tola-btn tola-btn-primary" target="_blank">
                    View on Marketplace
                </a>
                <?php endif; ?>
                
                <button class="tola-btn tola-btn-secondary" onclick="tolaRefreshStatus(<?php echo esc_attr($atts['artwork_id']); ?>)">
                    Refresh Status
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render royalty manager shortcode
     * Usage: [tola_royalty_manager artwork_id="123" max_fee="15"]
     */
    public function render_royalty_manager($atts) {
        $atts = shortcode_atts([
            'artwork_id' => '',
            'max_fee' => '15'
        ], $atts);
        
        if (empty($atts['artwork_id'])) {
            return '<div class="tola-error">Artwork ID is required</div>';
        }
        
        // Check if user owns this NFT
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<div class="tola-error">Please log in to manage royalties</div>';
        }
        
        $nft_data = $this->get_nft_data($atts['artwork_id']);
        $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
        
        if (!$nft_data || !$user_wallet) {
            return '<div class="tola-error">NFT not found or wallet not connected</div>';
        }
        
        $max_fee = min(15, intval($atts['max_fee']));
        $current_fee = $nft_data->royalty_fee_percent ?? 5;
        
        ob_start();
        ?>
        <div class="tola-royalty-manager" data-artwork-id="<?php echo esc_attr($atts['artwork_id']); ?>">
            <div class="tola-royalty-header">
                <h3>Royalty Settings</h3>
                <p>Set your secondary sale royalty (up to <?php echo esc_html($max_fee); ?>%)</p>
            </div>
            
            <form class="tola-royalty-form" onsubmit="tolaUpdateRoyalty(event)">
                <div class="tola-form-group">
                    <label for="royalty_fee">Royalty Percentage:</label>
                    <input type="range" 
                           id="royalty_fee" 
                           name="royalty_fee" 
                           min="0" 
                           max="<?php echo esc_attr($max_fee); ?>" 
                           value="<?php echo esc_attr($current_fee); ?>" 
                           step="0.5"
                           oninput="tolaUpdateRoyaltyDisplay(this.value)">
                    <div class="tola-royalty-display">
                        <span id="royalty_display"><?php echo esc_html($current_fee); ?>%</span>
                    </div>
                </div>
                
                <div class="tola-form-group">
                    <label for="royalty_recipient">Royalty Recipient:</label>
                    <input type="text" 
                           id="royalty_recipient" 
                           name="royalty_recipient" 
                           value="<?php echo esc_attr($user_wallet); ?>" 
                           placeholder="Solana wallet address"
                           pattern="[1-9A-HJ-NP-Za-km-z]{32,44}"
                           required>
                    <small>Enter the Solana wallet address that will receive royalty payments</small>
                </div>
                
                <div class="tola-form-actions">
                    <button type="submit" class="tola-btn tola-btn-primary">
                        Update Royalty
                    </button>
                    <button type="button" class="tola-btn tola-btn-secondary" onclick="tolaResetRoyalty()">
                        Reset to Default
                    </button>
                </div>
                
                <div class="tola-royalty-info">
                    <p><strong>Note:</strong> Royalty changes may take a few minutes to process on the blockchain.</p>
                    <p>Current royalty: <strong><?php echo esc_html($current_fee); ?>%</strong></p>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render NFT gallery shortcode
     * Usage: [tola_nft_gallery user_id="123" limit="10"]
     */
    public function render_nft_gallery($atts) {
        $atts = shortcode_atts([
            'user_id' => '',
            'limit' => '10',
            'show_details' => 'true'
        ], $atts);
        
        $user_id = $atts['user_id'] ?: get_current_user_id();
        
        if (!$user_id) {
            return '<div class="tola-error">User not specified</div>';
        }
        
        $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
        
        if (!$user_wallet) {
            return '<div class="tola-error">Wallet not connected</div>';
        }
        
        $limit = intval($atts['limit']);
        $show_details = $atts['show_details'] === 'true';
        
        // Get user's NFTs
        $nfts = $this->get_user_nfts($user_wallet, $limit);
        
        ob_start();
        ?>
        <div class="tola-nft-gallery">
            <div class="tola-gallery-header">
                <h3>TOLA NFT Collection</h3>
                <p>Your AI-generated masterpieces on the TOLA network</p>
            </div>
            
            <?php if (empty($nfts)): ?>
            <div class="tola-empty-gallery">
                <p>No NFTs found. Start generating artwork to build your collection!</p>
            </div>
            <?php else: ?>
            <div class="tola-gallery-grid">
                <?php foreach ($nfts as $nft): ?>
                <div class="tola-nft-card" data-artwork-id="<?php echo esc_attr($nft->artwork_id); ?>">
                    <div class="tola-nft-image">
                        <img src="<?php echo esc_url($this->get_nft_image_url($nft->uri)); ?>" 
                             alt="<?php echo esc_attr($nft->metadata ? json_decode($nft->metadata)->name : 'TOLA NFT'); ?>"
                             loading="lazy">
                    </div>
                    
                    <?php if ($show_details): ?>
                    <div class="tola-nft-details">
                        <h4><?php echo esc_html($this->get_nft_name($nft)); ?></h4>
                        <p class="tola-nft-id">ID: <?php echo esc_html($nft->artwork_id); ?></p>
                        
                        <?php if ($nft->royalty_fee_percent): ?>
                        <p class="tola-nft-royalty">Royalty: <?php echo esc_html($nft->royalty_fee_percent); ?>%</p>
                        <?php endif; ?>
                        
                        <div class="tola-nft-actions">
                            <a href="<?php echo esc_url($this->get_marketplace_url($nft->artwork_id)); ?>" 
                               class="tola-btn tola-btn-small tola-btn-primary" target="_blank">
                                View
                            </a>
                            
                            <button class="tola-btn tola-btn-small tola-btn-secondary" 
                                    onclick="tolaManageRoyalty(<?php echo esc_attr($nft->artwork_id); ?>)">
                                Manage
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render wallet connect shortcode
     * Usage: [tola_wallet_connect]
     */
    public function render_wallet_connect($atts) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return '<div class="tola-error">Please log in to connect your wallet</div>';
        }
        
        $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
        
        ob_start();
        ?>
        <div class="tola-wallet-connect">
            <div class="tola-wallet-header">
                <h3>TOLA Wallet Connection</h3>
                <p>Connect your Solana wallet to mint and manage NFTs</p>
            </div>
            
            <?php if ($user_wallet): ?>
            <div class="tola-wallet-connected">
                <div class="tola-wallet-info">
                    <span class="tola-wallet-icon">✓</span>
                    <div>
                        <p><strong>Wallet Connected</strong></p>
                        <p class="tola-wallet-address"><?php echo esc_html(substr($user_wallet, 0, 20) . '...'); ?></p>
                    </div>
                </div>
                
                <div class="tola-wallet-actions">
                    <button class="tola-btn tola-btn-secondary" onclick="tolaDisconnectWallet()">
                        Disconnect
                    </button>
                    <button class="tola-btn tola-btn-primary" onclick="tolaViewWalletNFTs()">
                        View NFTs
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="tola-wallet-disconnected">
                <p>No wallet connected. Connect your Solana wallet to start minting NFTs.</p>
                
                <div class="tola-wallet-options">
                    <button class="tola-btn tola-btn-primary" onclick="tolaConnectPhantom()">
                        Connect Phantom
                    </button>
                    <button class="tola-btn tola-btn-secondary" onclick="tolaConnectSolflare()">
                        Connect Solflare
                    </button>
                </div>
                
                <div class="tola-manual-connect">
                    <p>Or enter your wallet address manually:</p>
                    <form onsubmit="tolaManualConnect(event)">
                        <input type="text" 
                               id="manual_wallet" 
                               placeholder="Enter Solana wallet address"
                               pattern="[1-9A-HJ-NP-Za-km-z]{32,44}"
                               required>
                        <button type="submit" class="tola-btn tola-btn-primary">Connect</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render marketplace link shortcode
     * Usage: [tola_marketplace_link artwork_id="123" text="View on Marketplace"]
     */
    public function render_marketplace_link($atts) {
        $atts = shortcode_atts([
            'artwork_id' => '',
            'text' => 'View on Marketplace',
            'class' => 'tola-marketplace-link'
        ], $atts);
        
        if (empty($atts['artwork_id'])) {
            return '<span class="tola-error">Artwork ID is required</span>';
        }
        
        $marketplace_url = $this->get_marketplace_url($atts['artwork_id']);
        
        return sprintf(
            '<a href="%s" class="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($marketplace_url),
            esc_attr($atts['class']),
            esc_html($atts['text'])
        );
    }
    
    /**
     * Render NFT stats shortcode
     * Usage: [tola_nft_stats user_id="123"]
     */
    public function render_nft_stats($atts) {
        $atts = shortcode_atts([
            'user_id' => ''
        ], $atts);
        
        $user_id = $atts['user_id'] ?: get_current_user_id();
        
        if (!$user_id) {
            return '<div class="tola-error">User not specified</div>';
        }
        
        $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
        
        if (!$user_wallet) {
            return '<div class="tola-error">Wallet not connected</div>';
        }
        
        $stats = $this->get_user_nft_stats($user_wallet);
        
        ob_start();
        ?>
        <div class="tola-nft-stats">
            <div class="tola-stats-header">
                <h3>NFT Statistics</h3>
            </div>
            
            <div class="tola-stats-grid">
                <div class="tola-stat-card">
                    <div class="tola-stat-number"><?php echo esc_html($stats['total_nfts']); ?></div>
                    <div class="tola-stat-label">Total NFTs</div>
                </div>
                
                <div class="tola-stat-card">
                    <div class="tola-stat-number"><?php echo esc_html($stats['total_royalties']); ?>%</div>
                    <div class="tola-stat-label">Avg Royalty</div>
                </div>
                
                <div class="tola-stat-card">
                    <div class="tola-stat-number"><?php echo esc_html($stats['this_month']); ?></div>
                    <div class="tola-stat-label">This Month</div>
                </div>
                
                <div class="tola-stat-card">
                    <div class="tola-stat-number"><?php echo esc_html($stats['network']); ?></div>
                    <div class="tola-stat-label">Network</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Helper methods
     */
    
    private function get_nft_data($artwork_id) {
        return $this->solana_integration->get_solana_nft_data(null, $artwork_id)[0] ?? null;
    }
    
    private function get_user_nfts($user_wallet, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_solana_nfts';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE recipient_wallet = %s ORDER BY created_at DESC LIMIT %d",
            $user_wallet,
            $limit
        ));
    }
    
    private function get_user_nft_stats($user_wallet) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_solana_nfts';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_nfts,
                AVG(royalty_fee_percent) as avg_royalty,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 END) as this_month
            FROM $table_name 
            WHERE recipient_wallet = %s",
            $user_wallet
        ));
        
        return [
            'total_nfts' => $stats->total_nfts ?? 0,
            'total_royalties' => number_format($stats->avg_royalty ?? 0, 1),
            'this_month' => $stats->this_month ?? 0,
            'network' => 'TOLA'
        ];
    }
    
    private function get_explorer_url($signature) {
        $explorer_base = get_option('vortex_solana_explorer_url', 'https://explorer.solana.com');
        $network = get_option('vortex_solana_network', 'tola-mainnet');
        
        return $explorer_base . '/tx/' . $signature . '?cluster=' . $network;
    }
    
    private function get_marketplace_url($artwork_id) {
        $marketplace_base = get_option('vortex_nft_marketplace_url', 'https://marketplace.tola.com');
        
        return $marketplace_base . '/artwork/' . $artwork_id;
    }
    
    private function get_nft_image_url($uri) {
        // Extract image URL from metadata URI
        if (empty($uri)) {
            return '';
        }
        
        // For IPFS/Arweave URIs, try to get the image directly
        if (strpos($uri, 'ipfs://') === 0) {
            return str_replace('ipfs://', 'https://ipfs.io/ipfs/', $uri);
        }
        
        if (strpos($uri, 'https://arweave.net/') === 0) {
            return $uri;
        }
        
        // Otherwise, try to fetch metadata and extract image URL
        $metadata = $this->fetch_metadata($uri);
        return $metadata['image'] ?? '';
    }
    
    private function fetch_metadata($uri) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        if ($response) {
            return json_decode($response, true) ?: [];
        }
        
        return [];
    }
    
    private function get_nft_name($nft) {
        if ($nft->metadata) {
            $metadata = json_decode($nft->metadata, true);
            return $metadata['name'] ?? 'TOLA Masterpiece #' . $nft->artwork_id;
        }
        
        return 'TOLA Masterpiece #' . $nft->artwork_id;
    }
}

// Initialize shortcodes
new VortexAIEngine_NFT_Shortcodes();
?> 