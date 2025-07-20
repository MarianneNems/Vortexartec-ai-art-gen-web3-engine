<?php
/**
 * VORTEX Web3 Integration Class
 * 
 * Handles blockchain interactions for NFT minting and royalty management
 * 
 * @package VortexAI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VortexAIEngine_Web3_Integration {
    
    private $contract_address;
    private $contract_abi;
    private $rpc_url;
    private $private_key;
    private $chain_id;
    private $gas_limit;
    private $gas_price;
    
    public function __construct() {
        $this->init_settings();
        $this->load_contract_abi();
    }
    
    /**
     * Initialize Web3 settings from WordPress options
     */
    private function init_settings() {
        $this->contract_address = get_option('vortex_nft_contract_address', '');
        $this->rpc_url = get_option('vortex_rpc_url', '');
        $this->private_key = get_option('vortex_private_key', '');
        $this->chain_id = get_option('vortex_chain_id', 1);
        $this->gas_limit = get_option('vortex_gas_limit', 300000);
        $this->gas_price = get_option('vortex_gas_price', 20000000000); // 20 gwei
    }
    
    /**
     * Load contract ABI from file
     */
    private function load_contract_abi() {
        $abi_file = plugin_dir_path(__FILE__) . '../blockchain/artifacts/contracts/VortexArtwork.sol/VortexArtwork.json';
        
        if (file_exists($abi_file)) {
            $contract_data = json_decode(file_get_contents($abi_file), true);
            $this->contract_abi = $contract_data['abi'];
        } else {
            // Fallback ABI with essential functions
            $this->contract_abi = $this->get_fallback_abi();
        }
    }
    
    /**
     * Get fallback ABI for essential functions
     */
    private function get_fallback_abi() {
        return [
            [
                "inputs" => [
                    ["internalType" => "address", "name" => "to", "type" => "address"],
                    ["internalType" => "string", "name" => "tokenURI", "type" => "string"]
                ],
                "name" => "mintArtwork",
                "outputs" => [
                    ["internalType" => "uint256", "name" => "", "type" => "uint256"]
                ],
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            [
                "inputs" => [
                    ["internalType" => "uint256", "name" => "tokenId", "type" => "uint256"],
                    ["internalType" => "address", "name" => "receiver", "type" => "address"],
                    ["internalType" => "uint96", "name" => "feeNumerator", "type" => "uint96"]
                ],
                "name" => "setTokenRoyalty",
                "outputs" => [],
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            [
                "inputs" => [],
                "name" => "getCurrentTokenId",
                "outputs" => [
                    ["internalType" => "uint256", "name" => "", "type" => "uint256"]
                ],
                "stateMutability" => "view",
                "type" => "function"
            ],
            [
                "inputs" => [
                    ["internalType" => "uint256", "name" => "tokenId", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "salePrice", "type" => "uint256"]
                ],
                "name" => "royaltyInfo",
                "outputs" => [
                    ["internalType" => "address", "name" => "", "type" => "address"],
                    ["internalType" => "uint256", "name" => "", "type" => "uint256"]
                ],
                "stateMutability" => "view",
                "type" => "function"
            ]
        ];
    }
    
    /**
     * Validate Web3 configuration
     */
    public function is_configured() {
        return !empty($this->contract_address) && 
               !empty($this->rpc_url) && 
               !empty($this->private_key);
    }
    
    /**
     * Upload image to IPFS and return tokenURI
     */
    public function upload_to_ipfs($image_data, $metadata = []) {
        // Use Pinata or IPFS service
        $ipfs_gateway = get_option('vortex_ipfs_gateway', 'https://api.pinata.cloud');
        $ipfs_api_key = get_option('vortex_ipfs_api_key', '');
        
        if (empty($ipfs_api_key)) {
            return $this->upload_to_s3($image_data, $metadata);
        }
        
        try {
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
                    'file' => new CURLFile($temp_file, 'image/png', 'artwork.png')
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
                
                // Create metadata JSON
                $metadata_json = json_encode([
                    'name' => $metadata['name'] ?? 'VORTEX AI Artwork',
                    'description' => $metadata['description'] ?? 'AI-generated artwork created by VORTEX AI Engine',
                    'image' => 'ipfs://' . $image_hash,
                    'attributes' => $metadata['attributes'] ?? [],
                    'created_by' => 'VORTEX AI Engine',
                    'created_at' => date('c')
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
                    return 'ipfs://' . $metadata_result['IpfsHash'];
                }
            }
            
            throw new Exception('IPFS upload failed');
            
        } catch (Exception $e) {
            error_log('VORTEX IPFS Upload Error: ' . $e->getMessage());
            return $this->upload_to_s3($image_data, $metadata);
        }
    }
    
    /**
     * Fallback: Upload to S3 and return tokenURI
     */
    private function upload_to_s3($image_data, $metadata = []) {
        // Use existing S3 integration
        $s3_client = new VortexAIEngine_AWSServices();
        
        $image_key = 'nft-artworks/' . uniqid() . '.png';
        $image_url = $s3_client->upload_to_s3($image_data, $image_key, 'image/png');
        
        // Create metadata
        $metadata_json = json_encode([
            'name' => $metadata['name'] ?? 'VORTEX AI Artwork',
            'description' => $metadata['description'] ?? 'AI-generated artwork created by VORTEX AI Engine',
            'image' => $image_url,
            'attributes' => $metadata['attributes'] ?? [],
            'created_by' => 'VORTEX AI Engine',
            'created_at' => date('c')
        ]);
        
        $metadata_key = 'nft-metadata/' . uniqid() . '.json';
        $metadata_url = $s3_client->upload_to_s3($metadata_json, $metadata_key, 'application/json');
        
        return $metadata_url;
    }
    
    /**
     * Mint NFT artwork
     */
    public function mint_artwork($user_wallet, $image_data, $metadata = []) {
        if (!$this->is_configured()) {
            throw new Exception('Web3 integration not configured');
        }
        
        try {
            // Upload to IPFS/S3
            $token_uri = $this->upload_to_ipfs($image_data, $metadata);
            
            // Create transaction data
            $transaction_data = $this->create_transaction_data('mintArtwork', [
                $user_wallet,
                $token_uri
            ]);
            
            // Send transaction
            $tx_hash = $this->send_transaction($transaction_data);
            
            // Wait for confirmation and get token ID
            $token_id = $this->wait_for_mint_confirmation($tx_hash);
            
            // Store in database
            $this->store_nft_data($user_wallet, $token_id, $tx_hash, $token_uri, $metadata);
            
            return [
                'success' => true,
                'token_id' => $token_id,
                'tx_hash' => $tx_hash,
                'token_uri' => $token_uri,
                'contract_address' => $this->contract_address
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX NFT Mint Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Set token royalty
     */
    public function set_token_royalty($token_id, $receiver, $fee_percent) {
        if (!$this->is_configured()) {
            throw new Exception('Web3 integration not configured');
        }
        
        // Validate fee percentage (max 15%)
        if ($fee_percent > 15) {
            throw new Exception('Royalty fee cannot exceed 15%');
        }
        
        $fee_numerator = $fee_percent * 100; // Convert to basis points
        
        try {
            // Create transaction data
            $transaction_data = $this->create_transaction_data('setTokenRoyalty', [
                $token_id,
                $receiver,
                $fee_numerator
            ]);
            
            // Send transaction
            $tx_hash = $this->send_transaction($transaction_data);
            
            // Update database
            $this->update_nft_royalty($token_id, $receiver, $fee_percent, $tx_hash);
            
            return [
                'success' => true,
                'tx_hash' => $tx_hash,
                'token_id' => $token_id,
                'receiver' => $receiver,
                'fee_percent' => $fee_percent
            ];
            
        } catch (Exception $e) {
            error_log('VORTEX NFT Royalty Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create transaction data for contract interaction
     */
    private function create_transaction_data($function_name, $params) {
        // This is a simplified implementation
        // In production, use a proper Web3 library like Web3.php
        
        $function_signature = $this->get_function_signature($function_name);
        $encoded_params = $this->encode_parameters($params);
        
        return [
            'to' => $this->contract_address,
            'data' => $function_signature . $encoded_params,
            'gas' => $this->gas_limit,
            'gasPrice' => $this->gas_price,
            'chainId' => $this->chain_id
        ];
    }
    
    /**
     * Send transaction to blockchain
     */
    private function send_transaction($transaction_data) {
        // Use RPC call to send transaction
        $rpc_data = [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [$transaction_data],
            'id' => 1
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
        
        if (isset($result['error'])) {
            throw new Exception('Transaction failed: ' . $result['error']['message']);
        }
        
        return $result['result'];
    }
    
    /**
     * Wait for mint confirmation and extract token ID
     */
    private function wait_for_mint_confirmation($tx_hash) {
        $max_attempts = 30;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            $receipt = $this->get_transaction_receipt($tx_hash);
            
            if ($receipt && $receipt['status'] === '0x1') {
                // Parse logs to get token ID
                foreach ($receipt['logs'] as $log) {
                    if ($log['address'] === $this->contract_address) {
                        // Decode ArtworkMinted event
                        $token_id = hexdec($log['topics'][1]);
                        return $token_id;
                    }
                }
            }
            
            $attempt++;
            sleep(2);
        }
        
        throw new Exception('Transaction confirmation timeout');
    }
    
    /**
     * Get transaction receipt
     */
    private function get_transaction_receipt($tx_hash) {
        $rpc_data = [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionReceipt',
            'params' => [$tx_hash],
            'id' => 1
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
        return $result['result'] ?? null;
    }
    
    /**
     * Store NFT data in database
     */
    private function store_nft_data($user_wallet, $token_id, $tx_hash, $token_uri, $metadata) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_nft_tokens';
        
        $wpdb->insert($table_name, [
            'user_wallet' => $user_wallet,
            'token_id' => $token_id,
            'contract_address' => $this->contract_address,
            'tx_hash' => $tx_hash,
            'token_uri' => $token_uri,
            'metadata' => json_encode($metadata),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Update NFT royalty data
     */
    private function update_nft_royalty($token_id, $receiver, $fee_percent, $tx_hash) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_nft_tokens';
        
        $wpdb->update($table_name, [
            'royalty_receiver' => $receiver,
            'royalty_fee_percent' => $fee_percent,
            'royalty_tx_hash' => $tx_hash,
            'updated_at' => current_time('mysql')
        ], [
            'token_id' => $token_id
        ]);
    }
    
    /**
     * Get function signature for contract interaction
     */
    private function get_function_signature($function_name) {
        $signatures = [
            'mintArtwork' => '0x12345678', // Placeholder - use proper function selector
            'setTokenRoyalty' => '0x87654321' // Placeholder - use proper function selector
        ];
        
        return $signatures[$function_name] ?? '0x00000000';
    }
    
    /**
     * Encode parameters for contract interaction
     */
    private function encode_parameters($params) {
        // Simplified parameter encoding
        // In production, use proper ABI encoding
        $encoded = '';
        
        foreach ($params as $param) {
            if (is_string($param)) {
                $encoded .= str_pad(dechex(strlen($param)), 64, '0', STR_PAD_LEFT);
                $encoded .= str_pad(bin2hex($param), 64, '0', STR_PAD_RIGHT);
            } else {
                $encoded .= str_pad(dechex($param), 64, '0', STR_PAD_LEFT);
            }
        }
        
        return $encoded;
    }
    
    /**
     * Get current token ID from contract
     */
    public function get_current_token_id() {
        $rpc_data = [
            'jsonrpc' => '2.0',
            'method' => 'eth_call',
            'params' => [
                [
                    'to' => $this->contract_address,
                    'data' => '0x12345678' // getCurrentTokenId function selector
                ],
                'latest'
            ],
            'id' => 1
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
        return hexdec($result['result']);
    }
    
    /**
     * Get NFT data from database
     */
    public function get_nft_data($user_wallet = null, $token_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_nft_tokens';
        $where_clause = '1=1';
        $params = [];
        
        if ($user_wallet) {
            $where_clause .= ' AND user_wallet = %s';
            $params[] = $user_wallet;
        }
        
        if ($token_id) {
            $where_clause .= ' AND token_id = %d';
            $params[] = $token_id;
        }
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            return $wpdb->get_results($query);
        }
    }
}
?> 