<?php
/**
 * VORTEX NFT AJAX Handlers
 * 
 * Handles AJAX requests for TOLA NFT operations
 * 
 * @package VortexAI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_NFT_Ajax {
    
    private $solana_integration;
    private $nft_database;
    
    public function __construct() {
        $this->solana_integration = new VortexAIEngine_Solana_Integration();
        $this->nft_database = new VortexAIEngine_NFT_Database();
        
        $this->init_ajax_handlers();
    }
    
    /**
     * Initialize AJAX handlers
     */
    private function init_ajax_handlers() {
        // NFT status and management
        add_action('wp_ajax_tola_refresh_status', [$this, 'refresh_nft_status']);
        add_action('wp_ajax_tola_update_royalty', [$this, 'update_nft_royalty']);
        add_action('wp_ajax_tola_mint_nft', [$this, 'mint_nft']);
        
        // Wallet management
        add_action('wp_ajax_tola_connect_wallet', [$this, 'connect_wallet']);
        add_action('wp_ajax_tola_disconnect_wallet', [$this, 'disconnect_wallet']);
        add_action('wp_ajax_tola_validate_wallet', [$this, 'validate_wallet']);
        
        // Gallery and stats
        add_action('wp_ajax_tola_get_user_nfts', [$this, 'get_user_nfts']);
        add_action('wp_ajax_tola_get_nft_stats', [$this, 'get_nft_stats']);
        add_action('wp_ajax_tola_get_nft_details', [$this, 'get_nft_details']);
        
        // Admin endpoints
        add_action('wp_ajax_tola_admin_nft_status', [$this, 'admin_nft_status']);
        add_action('wp_ajax_tola_admin_pause_minting', [$this, 'admin_pause_minting']);
        add_action('wp_ajax_tola_admin_update_settings', [$this, 'admin_update_settings']);
        
        // Public endpoints (for non-logged-in users)
        add_action('wp_ajax_nopriv_tola_get_nft_details', [$this, 'get_nft_details']);
        add_action('wp_ajax_nopriv_tola_validate_wallet', [$this, 'validate_wallet']);
    }
    
    /**
     * Refresh NFT status
     */
    public function refresh_nft_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        // Validate user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        // Validate and sanitize input
        $artwork_id = intval($_POST['artwork_id']);
        
        if (!$artwork_id || $artwork_id <= 0) {
            wp_send_json_error('Invalid artwork ID');
        }
        
        // Additional validation - check if artwork belongs to user
        if (!current_user_can('administrator')) {
            $artwork_owner = $this->nft_database->get_artwork_owner($artwork_id);
            if ($artwork_owner !== get_current_user_id()) {
                wp_send_json_error('Access denied');
            }
        }
        
        try {
            // Get NFT data from database
            $nft_data = $this->nft_database->get_nft_by_artwork_id($artwork_id);
            
            if (!$nft_data) {
                wp_send_json_error('NFT not found');
            }
            
            // If signature exists, get blockchain status
            $response_data = [
                'artwork_id' => $artwork_id,
                'minted' => !empty($nft_data->signature),
                'signature' => $nft_data->signature,
                'network' => $nft_data->network,
                'royalty_fee_percent' => $nft_data->royalty_fee_percent
            ];
            
            if ($nft_data->signature) {
                $response_data['explorer_url'] = $this->get_explorer_url($nft_data->signature, $nft_data->network);
                $response_data['marketplace_url'] = $this->get_marketplace_url($artwork_id);
                
                // Check if transaction is confirmed on blockchain
                $blockchain_status = $this->solana_integration->get_artwork_info($artwork_id);
                if ($blockchain_status) {
                    $response_data['blockchain_confirmed'] = true;
                }
            }
            
            // Track analytics
            $this->track_analytics($artwork_id, 'mint_status_check');
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Refresh Status Error: ' . $e->getMessage());
            wp_send_json_error('Failed to refresh status: ' . $e->getMessage());
        }
    }
    
    /**
     * Update NFT royalty
     */
    public function update_nft_royalty() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        $artwork_id = intval($_POST['artwork_id']);
        $royalty_fee = floatval($_POST['royalty_fee']);
        $royalty_recipient = sanitize_text_field($_POST['royalty_recipient']);
        
        // Validation
        if (!$artwork_id || $royalty_fee < 0 || $royalty_fee > 15) {
            wp_send_json_error('Invalid parameters');
        }
        
        if (!$this->validate_solana_address($royalty_recipient)) {
            wp_send_json_error('Invalid royalty recipient address');
        }
        
        try {
            // Get NFT data
            $nft_data = $this->nft_database->get_nft_by_artwork_id($artwork_id);
            
            if (!$nft_data) {
                wp_send_json_error('NFT not found');
            }
            
            // Check ownership
            $user_id = get_current_user_id();
            $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
            
            if (!$user_wallet || $user_wallet !== $nft_data->recipient_wallet) {
                wp_send_json_error('Not authorized to update this NFT');
            }
            
            // Update royalty on blockchain
            $result = $this->solana_integration->set_artwork_royalty(
                $artwork_id,
                $user_wallet,
                $royalty_recipient,
                $royalty_fee
            );
            
            if (!$result['success']) {
                wp_send_json_error($result['error']);
            }
            
            // Save old values for history
            $old_fee = $nft_data->royalty_fee_percent;
            $old_recipient = $nft_data->royalty_recipient;
            
            // Update database
            $this->nft_database->update_nft($artwork_id, [
                'royalty_fee_percent' => $royalty_fee,
                'royalty_recipient' => $royalty_recipient,
                'royalty_signature' => $result['signature']
            ]);
            
            // Record history
            $this->nft_database->insert_royalty_history([
                'artwork_id' => $artwork_id,
                'old_fee_percent' => $old_fee,
                'new_fee_percent' => $royalty_fee,
                'old_recipient' => $old_recipient,
                'new_recipient' => $royalty_recipient,
                'signature' => $result['signature'],
                'updated_by' => $user_wallet,
                'network' => $nft_data->network
            ]);
            
            // Record transaction
            $this->nft_database->insert_transaction([
                'artwork_id' => $artwork_id,
                'transaction_type' => 'royalty_update',
                'signature' => $result['signature'],
                'from_address' => $user_wallet,
                'to_address' => $royalty_recipient,
                'status' => 'confirmed',
                'network' => $nft_data->network,
                'transaction_data' => json_encode([
                    'royalty_fee' => $royalty_fee,
                    'royalty_recipient' => $royalty_recipient
                ])
            ]);
            
            // Track analytics
            $this->track_analytics($artwork_id, 'royalty_update');
            
            wp_send_json_success([
                'signature' => $result['signature'],
                'artwork_id' => $artwork_id,
                'royalty_fee' => $royalty_fee,
                'royalty_recipient' => $royalty_recipient,
                'explorer_url' => $this->get_explorer_url($result['signature'], $nft_data->network)
            ]);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Update Royalty Error: ' . $e->getMessage());
            wp_send_json_error('Failed to update royalty: ' . $e->getMessage());
        }
    }
    
    /**
     * Mint NFT (for manual minting)
     */
    public function mint_nft() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        $image_url = esc_url_raw($_POST['image_url']);
        $metadata = json_decode(stripslashes($_POST['metadata']), true);
        
        if (!$image_url) {
            wp_send_json_error('Image URL is required');
        }
        
        try {
            $user_id = get_current_user_id();
            $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
            
            if (!$user_wallet) {
                wp_send_json_error('Wallet not connected');
            }
            
            // Download image data
            $image_data = $this->download_image($image_url);
            
            if (!$image_data) {
                wp_send_json_error('Failed to download image');
            }
            
            // Mint NFT
            $result = $this->solana_integration->mint_artwork($user_wallet, $image_data, $metadata);
            
            if (!$result['success']) {
                wp_send_json_error($result['error']);
            }
            
            // Track analytics
            $this->track_analytics($result['artwork_id'], 'manual_mint');
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Manual Mint Error: ' . $e->getMessage());
            wp_send_json_error('Failed to mint NFT: ' . $e->getMessage());
        }
    }
    
    /**
     * Connect wallet
     */
    public function connect_wallet() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        $wallet_type = sanitize_text_field($_POST['wallet_type']);
        
        if (!$this->validate_solana_address($wallet_address)) {
            wp_send_json_error('Invalid wallet address');
        }
        
        try {
            $user_id = get_current_user_id();
            
            // Save wallet address
            update_user_meta($user_id, 'vortex_solana_wallet', $wallet_address);
            update_user_meta($user_id, 'vortex_solana_wallet_type', $wallet_type);
            update_user_meta($user_id, 'vortex_solana_wallet_connected_at', current_time('mysql'));
            
            // Track analytics
            $this->track_analytics(null, 'wallet_connect', [
                'wallet_address' => $wallet_address,
                'wallet_type' => $wallet_type
            ]);
            
            wp_send_json_success([
                'wallet_address' => $wallet_address,
                'wallet_type' => $wallet_type,
                'connected_at' => current_time('mysql')
            ]);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Connect Wallet Error: ' . $e->getMessage());
            wp_send_json_error('Failed to connect wallet: ' . $e->getMessage());
        }
    }
    
    /**
     * Disconnect wallet
     */
    public function disconnect_wallet() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        try {
            $user_id = get_current_user_id();
            $old_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
            
            // Remove wallet data
            delete_user_meta($user_id, 'vortex_solana_wallet');
            delete_user_meta($user_id, 'vortex_solana_wallet_type');
            delete_user_meta($user_id, 'vortex_solana_wallet_connected_at');
            
            // Track analytics
            $this->track_analytics(null, 'wallet_disconnect', [
                'wallet_address' => $old_wallet
            ]);
            
            wp_send_json_success(['disconnected' => true]);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Disconnect Wallet Error: ' . $e->getMessage());
            wp_send_json_error('Failed to disconnect wallet: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate wallet address
     */
    public function validate_wallet() {
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        
        $is_valid = $this->validate_solana_address($wallet_address);
        
        wp_send_json_success(['valid' => $is_valid]);
    }
    
    /**
     * Get user NFTs
     */
    public function get_user_nfts() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        try {
            $user_id = get_current_user_id();
            $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
            
            if (!$user_wallet) {
                wp_send_json_error('Wallet not connected');
            }
            
            $limit = intval($_POST['limit'] ?? 10);
            $offset = intval($_POST['offset'] ?? 0);
            
            $nfts = $this->nft_database->get_nfts_by_wallet($user_wallet, $limit, $offset);
            
            // Format NFT data
            $formatted_nfts = array_map(function($nft) {
                $metadata = json_decode($nft->metadata, true);
                
                return [
                    'artwork_id' => $nft->artwork_id,
                    'name' => $metadata['name'] ?? 'TOLA Masterpiece #' . $nft->artwork_id,
                    'description' => $metadata['description'] ?? '',
                    'image_url' => $this->get_image_url_from_metadata($metadata),
                    'uri' => $nft->uri,
                    'royalty_fee_percent' => $nft->royalty_fee_percent,
                    'royalty_recipient' => $nft->royalty_recipient,
                    'signature' => $nft->signature,
                    'network' => $nft->network,
                    'created_at' => $nft->created_at,
                    'explorer_url' => $this->get_explorer_url($nft->signature, $nft->network),
                    'marketplace_url' => $this->get_marketplace_url($nft->artwork_id)
                ];
            }, $nfts);
            
            wp_send_json_success([
                'nfts' => $formatted_nfts,
                'total' => count($nfts),
                'has_more' => count($nfts) === $limit
            ]);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Get User NFTs Error: ' . $e->getMessage());
            wp_send_json_error('Failed to get NFTs: ' . $e->getMessage());
        }
    }
    
    /**
     * Get NFT statistics
     */
    public function get_nft_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        try {
            $user_id = get_current_user_id();
            $user_wallet = get_user_meta($user_id, 'vortex_solana_wallet', true);
            
            if (!$user_wallet) {
                wp_send_json_error('Wallet not connected');
            }
            
            $stats = $this->nft_database->get_nft_stats($user_wallet);
            
            wp_send_json_success([
                'total_nfts' => $stats->total_nfts ?? 0,
                'avg_royalty' => number_format($stats->avg_royalty ?? 0, 1),
                'this_month' => $stats->this_month ?? 0,
                'this_week' => $stats->this_week ?? 0,
                'minted_count' => $stats->minted_count ?? 0,
                'pending_count' => $stats->pending_count ?? 0,
                'network' => 'TOLA'
            ]);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Get Stats Error: ' . $e->getMessage());
            wp_send_json_error('Failed to get stats: ' . $e->getMessage());
        }
    }
    
    /**
     * Get NFT details
     */
    public function get_nft_details() {
        $artwork_id = intval($_POST['artwork_id']);
        
        if (!$artwork_id) {
            wp_send_json_error('Invalid artwork ID');
        }
        
        try {
            $nft_data = $this->nft_database->get_nft_by_artwork_id($artwork_id);
            
            if (!$nft_data) {
                wp_send_json_error('NFT not found');
            }
            
            $metadata = json_decode($nft_data->metadata, true);
            
            $details = [
                'artwork_id' => $nft_data->artwork_id,
                'name' => $metadata['name'] ?? 'TOLA Masterpiece #' . $nft_data->artwork_id,
                'description' => $metadata['description'] ?? '',
                'image_url' => $this->get_image_url_from_metadata($metadata),
                'attributes' => $metadata['attributes'] ?? [],
                'uri' => $nft_data->uri,
                'royalty_fee_percent' => $nft_data->royalty_fee_percent,
                'royalty_recipient' => $nft_data->royalty_recipient,
                'signature' => $nft_data->signature,
                'network' => $nft_data->network,
                'created_at' => $nft_data->created_at,
                'explorer_url' => $this->get_explorer_url($nft_data->signature, $nft_data->network),
                'marketplace_url' => $this->get_marketplace_url($nft_data->artwork_id)
            ];
            
            // Track analytics
            $this->track_analytics($artwork_id, 'view');
            
            wp_send_json_success($details);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Get Details Error: ' . $e->getMessage());
            wp_send_json_error('Failed to get NFT details: ' . $e->getMessage());
        }
    }
    
    /**
     * Admin NFT status
     */
    public function admin_nft_status() {
        // Check admin permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tola_nft_nonce')) {
            wp_die('Security check failed');
        }
        
        try {
            $global_stats = $this->nft_database->get_nft_stats();
            
            wp_send_json_success([
                'total_nfts' => $global_stats->total_nfts ?? 0,
                'avg_royalty' => number_format($global_stats->avg_royalty ?? 0, 1),
                'this_month' => $global_stats->this_month ?? 0,
                'this_week' => $global_stats->this_week ?? 0,
                'minted_count' => $global_stats->minted_count ?? 0,
                'pending_count' => $global_stats->pending_count ?? 0,
                'network' => 'TOLA',
                'minting_enabled' => get_option('vortex_nft_minting_enabled', false),
                'program_id' => get_option('vortex_solana_program_id', ''),
                'rpc_url' => get_option('vortex_solana_rpc_url', ''),
                'network_status' => $this->get_network_status()
            ]);
            
        } catch (Exception $e) {
            error_log('TOLA NFT Admin Status Error: ' . $e->getMessage());
            wp_send_json_error('Failed to get admin status: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper methods
     */
    
    private function validate_solana_address($address) {
        return preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address);
    }
    
    private function get_explorer_url($signature, $network) {
        $explorer_base = get_option('vortex_solana_explorer_url', 'https://explorer.solana.com');
        return $explorer_base . '/tx/' . $signature . '?cluster=' . $network;
    }
    
    private function get_marketplace_url($artwork_id) {
        $marketplace_base = get_option('vortex_nft_marketplace_url', 'https://marketplace.tola.com');
        return $marketplace_base . '/artwork/' . $artwork_id;
    }
    
    private function get_image_url_from_metadata($metadata) {
        if (empty($metadata)) {
            return '';
        }
        
        $image_url = $metadata['image'] ?? '';
        
        // Convert IPFS URLs to HTTP
        if (strpos($image_url, 'ipfs://') === 0) {
            $image_url = str_replace('ipfs://', 'https://ipfs.io/ipfs/', $image_url);
        }
        
        return $image_url;
    }
    
    private function download_image($url) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'VORTEX AI Engine NFT'
        ]);
        
        $data = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        return ($http_code === 200) ? $data : false;
    }
    
    private function track_analytics($artwork_id, $metric_type, $metadata = []) {
        try {
            $this->nft_database->insert_analytics([
                'artwork_id' => $artwork_id,
                'metric_type' => $metric_type,
                'user_id' => get_current_user_id(),
                'user_wallet' => get_user_meta(get_current_user_id(), 'vortex_solana_wallet', true),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'metadata' => json_encode($metadata)
            ]);
        } catch (Exception $e) {
            error_log('TOLA NFT Analytics Error: ' . $e->getMessage());
        }
    }
    
    private function get_network_status() {
        try {
            $rpc_url = get_option('vortex_solana_rpc_url', 'https://api.tola.solana.com');
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $rpc_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getHealth'
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($http_code === 200) {
                $result = json_decode($response, true);
                return $result['result'] ?? 'unknown';
            }
            
            return 'offline';
            
        } catch (Exception $e) {
            return 'error';
        }
    }
}

// Initialize AJAX handlers
new VortexAIEngine_NFT_Ajax();
?> 