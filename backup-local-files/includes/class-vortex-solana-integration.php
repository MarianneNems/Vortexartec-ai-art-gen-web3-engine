<?php
/**
 * VORTEX Solana Integration Class
 * 
 * Handles Solana blockchain interactions for TOLA NFT minting and royalty management
 * 
 * @package VortexAI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_Solana_Integration {
    
    private $program_id;
    private $rpc_url;
    private $authority_keypair;
    private $commitment;
    private $network;
    
    public function __construct() {
        $this->init_settings();
    }
    
    /**
     * Initialize Solana settings from WordPress options
     */
    private function init_settings() {
        $this->program_id = get_option('vortex_solana_program_id', 'Fg6PaFpoGXkYsidMpWTK6W2BeZ7FEfcYkg476zPFsLnS');
        $this->rpc_url = get_option('vortex_solana_rpc_url', 'https://api.tola.solana.com');
        $this->authority_keypair = get_option('vortex_solana_authority_keypair', '');
        $this->commitment = get_option('vortex_solana_commitment', 'confirmed');
        $this->network = get_option('vortex_solana_network', 'tola-mainnet');
    }
    
    /**
     * Validate Solana configuration
     */
    public function is_configured() {
        return !empty($this->program_id) && 
               !empty($this->rpc_url) && 
               !empty($this->authority_keypair);
    }
    
    /**
     * Upload artwork to Arweave or IPFS
     */
    public function upload_artwork($image_data, $metadata = []) {
        // Use Arweave for permanent storage (recommended for Solana NFTs)
        $arweave_endpoint = get_option('vortex_arweave_endpoint', 'https://arweave.net');
        $arweave_wallet = get_option('vortex_arweave_wallet', '');
        
        if (!empty($arweave_wallet)) {
            return $this->upload_to_arweave($image_data, $metadata);
        }
        
        // Fallback to IPFS
        return $this->upload_to_ipfs($image_data, $metadata);
    }
    
    /**
     * Upload to Arweave
     */
    private function upload_to_arweave($image_data, $metadata = []) {
        try {
            $arweave_endpoint = get_option('vortex_arweave_endpoint', 'https://arweave.net');
            $arweave_wallet = get_option('vortex_arweave_wallet', '');
            
            // Create temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'vortex_artwork_');
            file_put_contents($temp_file, $image_data);
            
            // Upload image to Arweave
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $arweave_endpoint . '/tx',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => file_get_contents($temp_file),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: image/png',
                    'Authorization: Bearer ' . $arweave_wallet
                ]
            ]);
            
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            // Clean up temp file
            unlink($temp_file);
            
            if ($http_code === 200) {
                $result = json_decode($response, true);
                $image_id = $result['id'];
                
                // Create metadata JSON
                $metadata_json = json_encode([
                    'name' => $metadata['name'] ?? 'TOLA Masterpiece',
                    'description' => $metadata['description'] ?? 'AI-generated artwork created by VORTEX AI Engine on TOLA network',
                    'image' => $arweave_endpoint . '/' . $image_id,
                    'attributes' => $metadata['attributes'] ?? [],
                    'properties' => [
                        'category' => 'image',
                        'creators' => $metadata['creators'] ?? [],
                        'files' => [
                            [
                                'uri' => $arweave_endpoint . '/' . $image_id,
                                'type' => 'image/png'
                            ]
                        ]
                    ],
                    'collection' => [
                        'name' => 'TOLA Masterpiece Collection',
                        'family' => 'VORTEX AI'
                    ],
                    'created_by' => 'VORTEX AI Engine',
                    'created_at' => date('c'),
                    'network' => 'TOLA'
                ]);
                
                // Upload metadata to Arweave
                $metadata_curl = curl_init();
                curl_setopt_array($metadata_curl, [
                    CURLOPT_URL => $arweave_endpoint . '/tx',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $metadata_json,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $arweave_wallet
                    ]
                ]);
                
                $metadata_response = curl_exec($metadata_curl);
                $metadata_http_code = curl_getinfo($metadata_curl, CURLINFO_HTTP_CODE);
                curl_close($metadata_curl);
                
                if ($metadata_http_code === 200) {
                    $metadata_result = json_decode($metadata_response, true);
                    return $arweave_endpoint . '/' . $metadata_result['id'];
                }
            }
            
            throw new Exception('Arweave upload failed');
            
        } catch (Exception $e) {
            error_log('VORTEX Arweave Upload Error: ' . $e->getMessage());
            return $this->upload_to_ipfs($image_data, $metadata);
        }
    }
    
    /**
     * Upload to IPFS (fallback)
     */
    private function upload_to_ipfs($image_data, $metadata = []) {
        // Use existing IPFS upload logic from Web3 integration
        // This is a simplified implementation
        
        try {
            $ipfs_gateway = get_option('vortex_ipfs_gateway', 'https://api.pinata.cloud');
            $ipfs_api_key = get_option('vortex_ipfs_api_key', '');
            
            if (empty($ipfs_api_key)) {
                throw new Exception('IPFS API key not configured');
            }
            
            // Create temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'vortex_nft_');
            file_put_contents($temp_file, $image_data);
            
            // Upload to IPFS
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $ipfs_gateway . '/pinning/pinFileToIPFS',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'file' => new CURLFile($temp_file, 'image/png', 'tola_artwork.png')
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $ipfs_api_key
                ]
            ]);
            
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            // Clean up temp file
            unlink($temp_file);
            
            if ($http_code === 200) {
                $result = json_decode($response, true);
                $image_hash = $result['IpfsHash'];
                
                // Create Solana-compatible metadata
                $metadata_json = json_encode([
                    'name' => $metadata['name'] ?? 'TOLA Masterpiece',
                    'description' => $metadata['description'] ?? 'AI-generated artwork created by VORTEX AI Engine on TOLA network',
                    'image' => 'https://ipfs.io/ipfs/' . $image_hash,
                    'external_url' => 'https://vortex-ai.com',
                    'attributes' => $metadata['attributes'] ?? [],
                    'properties' => [
                        'category' => 'image',
                        'creators' => $metadata['creators'] ?? [],
                        'files' => [
                            [
                                'uri' => 'https://ipfs.io/ipfs/' . $image_hash,
                                'type' => 'image/png'
                            ]
                        ]
                    ],
                    'collection' => [
                        'name' => 'TOLA Masterpiece Collection',
                        'family' => 'VORTEX AI'
                    ],
                    'created_by' => 'VORTEX AI Engine',
                    'created_at' => date('c'),
                    'network' => 'TOLA'
                ]);
                
                // Upload metadata to IPFS
                $metadata_curl = curl_init();
                curl_setopt_array($metadata_curl, [
                    CURLOPT_URL => $ipfs_gateway . '/pinning/pinJSONToIPFS',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode([
                        'pinataContent' => json_decode($metadata_json, true)
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $ipfs_api_key,
                        'Content-Type: application/json'
                    ]
                ]);
                
                $metadata_response = curl_exec($metadata_curl);
                $metadata_http_code = curl_getinfo($metadata_curl, CURLINFO_HTTP_CODE);
                curl_close($metadata_curl);
                
                if ($metadata_http_code === 200) {
                    $metadata_result = json_decode($metadata_response, true);
                    return 'https://ipfs.io/ipfs/' . $metadata_result['IpfsHash'];
                }
            }
            
            throw new Exception('IPFS upload failed');
            
        } catch (Exception $e) {
            error_log('VORTEX IPFS Upload Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Mint TOLA NFT artwork
     */
    public function mint_artwork($recipient_wallet, $image_data, $metadata = []) {
        if (!$this->is_configured()) {
            throw new Exception('Solana integration not configured');
        }
        
        try {
            // Upload artwork to Arweave/IPFS
            $artwork_uri = $this->upload_artwork($image_data, $metadata);
            
            // Generate artwork ID
            $artwork_id = $this->get_next_artwork_id();
            
            // Prepare transaction data
            $transaction_data = [
                'program_id' => $this->program_id,
                'instruction' => 'mint_artwork',
                'accounts' => [
                    'recipient' => $recipient_wallet,
                    'payer' => $this->get_authority_public_key(),
                    'authority' => get_option('vortex_tola_authority', 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky')
                ],
                'args' => [
                    'artwork_id' => $artwork_id,
                    'name' => $metadata['name'] ?? 'TOLA Masterpiece #' . $artwork_id,
                    'symbol' => 'TOLA',
                    'uri' => $artwork_uri,
                    'creators' => $this->format_creators($metadata['creators'] ?? [])
                ]
            ];
            
            // Send transaction
            $signature = $this->send_transaction($transaction_data);
            
            // Wait for confirmation
            $this->wait_for_confirmation($signature);
            
            // Store in database
            $this->store_solana_nft_data($recipient_wallet, $artwork_id, $signature, $artwork_uri, $metadata);
            
            return [
                'success' => true,
                'artwork_id' => $artwork_id,
                'signature' => $signature,
                'uri' => $artwork_uri,
                'program_id' => $this->program_id,
                'network' => $this->network
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX Solana Mint Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Set artwork royalty
     */
    public function set_artwork_royalty($artwork_id, $owner_wallet, $royalty_recipient, $fee_percent) {
        if (!$this->is_configured()) {
            throw new Exception('Solana integration not configured');
        }
        
        // Validate fee percentage (max 15%)
        if ($fee_percent > 15) {
            throw new Exception('Royalty fee cannot exceed 15%');
        }
        
        $fee_basis_points = $fee_percent * 100; // Convert to basis points
        
        try {
            // Prepare transaction data
            $transaction_data = [
                'program_id' => $this->program_id,
                'instruction' => 'set_artwork_royalty',
                'accounts' => [
                    'current_owner' => $owner_wallet,
                    'artwork_account' => $this->derive_artwork_account($artwork_id)
                ],
                'args' => [
                    'new_royalty_fee' => $fee_basis_points,
                    'royalty_recipient' => $royalty_recipient
                ]
            ];
            
            // Send transaction
            $signature = $this->send_transaction($transaction_data);
            
            // Wait for confirmation
            $this->wait_for_confirmation($signature);
            
            // Update database
            $this->update_solana_nft_royalty($artwork_id, $royalty_recipient, $fee_percent, $signature);
            
            return [
                'success' => true,
                'signature' => $signature,
                'artwork_id' => $artwork_id,
                'royalty_recipient' => $royalty_recipient,
                'fee_percent' => $fee_percent
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX Solana Royalty Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send transaction to Solana network
     */
    private function send_transaction($transaction_data) {
        // This is a simplified implementation
        // In production, use proper Solana PHP libraries or call external scripts
        
        $rpc_data = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'sendTransaction',
            'params' => [
                $this->build_transaction($transaction_data),
                [
                    'encoding' => 'base64',
                    'commitment' => $this->commitment
                ]
            ]
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->rpc_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($rpc_data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code !== 200) {
            throw new Exception('RPC request failed with HTTP code: ' . $http_code);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new Exception('Transaction failed: ' . $result['error']['message']);
        }
        
        return $result['result'];
    }
    
    /**
     * Build transaction for Solana
     */
    private function build_transaction($transaction_data) {
        // This would normally use proper Solana transaction building
        // For now, return a placeholder that would be built by external tooling
        
        return base64_encode(json_encode($transaction_data));
    }
    
    /**
     * Wait for transaction confirmation
     */
    private function wait_for_confirmation($signature) {
        $max_attempts = 30;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            $rpc_data = [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getSignatureStatus',
                'params' => [$signature]
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->rpc_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($rpc_data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            curl_close($curl);
            
            $result = json_decode($response, true);
            
            if (isset($result['result']['value']) && $result['result']['value'] !== null) {
                $status = $result['result']['value'];
                if (isset($status['confirmationStatus']) && 
                    $status['confirmationStatus'] === 'confirmed') {
                    return true;
                }
            }
            
            $attempt++;
            sleep(2);
        }
        
        throw new Exception('Transaction confirmation timeout');
    }
    
    /**
     * Get next artwork ID
     */
    private function get_next_artwork_id() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_solana_nfts';
        $max_id = $wpdb->get_var("SELECT MAX(artwork_id) FROM $table_name");
        
        return ($max_id ?? 0) + 1;
    }
    
    /**
     * Get authority public key
     */
    private function get_authority_public_key() {
        // Extract public key from keypair
        // This is a simplified implementation
        return get_option('vortex_tola_authority', 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky');
    }
    
    /**
     * Derive artwork account PDA
     */
    private function derive_artwork_account($artwork_id) {
        // This would normally use proper PDA derivation
        // For now, return a placeholder
        return $this->program_id . '_artwork_' . $artwork_id;
    }
    
    /**
     * Format creators for Solana metadata
     */
    private function format_creators($creators) {
        if (empty($creators)) {
            return [
                [
                    'address' => $this->get_authority_public_key(),
                    'verified' => true,
                    'share' => 100
                ]
            ];
        }
        
        $formatted = [];
        foreach ($creators as $creator) {
            $formatted[] = [
                'address' => $creator['address'],
                'verified' => $creator['verified'] ?? false,
                'share' => $creator['share'] ?? 0
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Store Solana NFT data in database
     */
    private function store_solana_nft_data($recipient_wallet, $artwork_id, $signature, $uri, $metadata) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_solana_nfts';
        
        $wpdb->insert($table_name, [
            'recipient_wallet' => $recipient_wallet,
            'artwork_id' => $artwork_id,
            'program_id' => $this->program_id,
            'signature' => $signature,
            'uri' => $uri,
            'metadata' => json_encode($metadata),
            'network' => $this->network,
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Update Solana NFT royalty data
     */
    private function update_solana_nft_royalty($artwork_id, $royalty_recipient, $fee_percent, $signature) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_solana_nfts';
        
        $wpdb->update($table_name, [
            'royalty_recipient' => $royalty_recipient,
            'royalty_fee_percent' => $fee_percent,
            'royalty_signature' => $signature,
            'updated_at' => current_time('mysql')
        ], [
            'artwork_id' => $artwork_id
        ]);
    }
    
    /**
     * Get Solana NFT data from database
     */
    public function get_solana_nft_data($recipient_wallet = null, $artwork_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_solana_nfts';
        $where_clause = '1=1';
        $params = [];
        
        if ($recipient_wallet) {
            $where_clause .= ' AND recipient_wallet = %s';
            $params[] = $recipient_wallet;
        }
        
        if ($artwork_id) {
            $where_clause .= ' AND artwork_id = %d';
            $params[] = $artwork_id;
        }
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            return $wpdb->get_results($query);
        }
    }
    
    /**
     * Get artwork info from Solana program
     */
    public function get_artwork_info($artwork_id) {
        $rpc_data = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getAccountInfo',
            'params' => [
                $this->derive_artwork_account($artwork_id),
                [
                    'encoding' => 'base64',
                    'commitment' => $this->commitment
                ]
            ]
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->rpc_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($rpc_data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        $result = json_decode($response, true);
        
        if (isset($result['result']['value'])) {
            return $result['result']['value'];
        }
        
        return null;
    }
}
?> 