<?php
/**
 * Vault integration for fetching AI agent configs & memory
 *
 * @package VortexAIEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VortexAIEngine_Vault')) {
class VortexAIEngine_Vault {
    private static $instance = null;
    private $client;
    private $vault_addr;
    private $vault_token;
    private $is_available = false;

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Try WordPress options first, fallback to ENV variables
        $this->vault_addr = get_option( 'vortex_vault_addr' ) ?: getenv( 'VAULT_ADDR' );
        $this->vault_token = get_option( 'vortex_vault_token' ) ?: getenv( 'VAULT_TOKEN' );
        
        // Skip Vault integration gracefully if not configured
        if ( empty( $this->vault_addr ) || empty( $this->vault_token ) ) {
            $this->is_available = false;
            error_log( '[VortexAI] Vault not configured, skipping integration. Configure in VORTEX AI Engine > API Configuration if needed.' );
            return;
        }
        
        // Initialize Guzzle HTTP client for Vault API
        try {
            // Enforce HTTPS in production
            if ( ! $this->isHttpsOrLocal( $this->vault_addr ) ) {
                throw new Exception( "Vault address must use HTTPS in production. Current address: {$this->vault_addr}" );
            }
            
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => rtrim( $this->vault_addr, '/' ) . '/',
                'timeout' => 30,
                'verify' => $this->shouldVerifySSL(),
                'headers' => [
                    'X-Vault-Token' => $this->vault_token,
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            // Test connection
            $this->testConnection();
            $this->is_available = true;
            
        } catch ( Exception $e ) {
            error_log( '[VortexAI] Vault client initialization failed: ' . $e->getMessage() );
            $this->is_available = false;
        }
    }

    /** Test Vault connection */
    private function testConnection() {
        try {
            $response = $this->client->get( 'v1/sys/health' );
            $status_code = $response->getStatusCode();
            
            if ( $status_code !== 200 ) {
                throw new Exception( "Vault health check failed with status: {$status_code}" );
            }
            
        } catch ( Exception $e ) {
            throw new Exception( "Vault connection test failed: " . $e->getMessage() );
        }
    }

    /** Check if Vault is available */
    public function isAvailable() {
        return $this->is_available;
    }

    /**
     * Get algorithm/data from Vault using KV v2 API
     * Updated to use correct paths that match setup-vault-data.sh
     */
    public function getAlgorithm( $path ) {
        if ( ! $this->is_available ) {
            error_log( '[VortexAI] Vault not available, returning empty algorithm for: ' . $path );
            return [];
        }

        try {
            // Use KV v2 API format: /v1/secret/data/{path}
            $api_path = "v1/secret/data/{$path}";
            $response = $this->client->get( $api_path );
            $data = json_decode( $response->getBody()->getContents(), true );
            
            // KV v2 stores data under 'data.data'
            return $data['data']['data'] ?? [];
            
        } catch ( \GuzzleHttp\Exception\ClientException $e ) {
            $status_code = $e->getResponse()->getStatusCode();
            if ( $status_code === 404 ) {
                error_log( "[VortexAI] Vault path not found: {$path}" );
                return [];
            }
            error_log( "[VortexAI] Vault client error for path {$path}: " . $e->getMessage() );
            return [];
            
        } catch ( Exception $e ) {
            error_log( "[VortexAI] Failed to read from Vault path {$path}: " . $e->getMessage() );
            return [];
        }
    }

    /**
     * Write data to Vault using KV v2 API
     */
    public function write( $path, $data ) {
        if ( ! $this->is_available ) {
            error_log( '[VortexAI] Vault not available, cannot write to: ' . $path );
            return false;
        }

        try {
            // Use KV v2 API format: /v1/secret/data/{path}
            $api_path = "v1/secret/data/{$path}";
            $response = $this->client->post( $api_path, [
                'json' => ['data' => $data]
            ]);
            
            return $response->getStatusCode() === 200 || $response->getStatusCode() === 204;
            
        } catch ( Exception $e ) {
            error_log( "[VortexAI] Failed to write to Vault path {$path}: " . $e->getMessage() );
            return false;
        }
    }

    /** 
     * Enhanced method for algorithm storage and retrieval
     */
    public function storeAlgorithm( $algorithm_id, $algorithm_data ) {
        $path = "algorithms/{$algorithm_id}";
        return $this->write( $path, $algorithm_data );
    }

    /**
     * Get algorithm with version tracking
     */
    public function getAlgorithmVersion( $algorithm_id, $version = 'latest' ) {
        if ( $version === 'latest' ) {
            return $this->getAlgorithm( "algorithms/{$algorithm_id}" );
        }
        
        $path = "algorithms/{$algorithm_id}/versions/{$version}";
        return $this->getAlgorithm( $path );
    }

    /**
     * Store agent memory with continuous sync
     */
    public function syncAgentMemory( $agent_id, $user_id, $memory_data ) {
        $path = "memory_{$agent_id}_{$user_id}";
        
        // Get existing memory
        $existing = $this->getAlgorithm( $path ) ?: [];
        
        // Merge with new data
        $merged = array_merge_recursive( $existing, $memory_data );
        
        // Store updated memory
        $this->write( $path, $merged );
        
        return $merged;
    }

    /**
     * Get all agent memories for a user
     */
    public function getUserAgentMemories( $user_id ) {
        $memories = [];
        $agents = ['huraii', 'cloe', 'horace', 'thorius', 'archer'];
        
        foreach ( $agents as $agent_id ) {
            $memories[$agent_id] = $this->getAlgorithm( "memory_{$agent_id}_{$user_id}" ) ?: [];
        }
        
        return $memories;
    }

    /**
     * Store feedback for algorithm evolution
     */
    public function storeFeedback( $feedback_id, $feedback_data ) {
        $path = "feedback/{$feedback_id}";
        return $this->write( $path, $feedback_data );
    }

    /**
     * Batch operation for efficient data storage
     */
    public function batchWrite( $operations ) {
        $results = [];
        foreach ( $operations as $operation ) {
            $results[] = $this->write( $operation['path'], $operation['data'] );
        }
        return $results;
    }

    /**
     * Get agent configuration with fallback
     */
    public function getAgentConfig( $agent_id ) {
        $config = $this->getAlgorithm( "config_{$agent_id}" );
        
        if ( empty( $config ) ) {
            error_log( "[VortexAI] No Vault config found for agent {$agent_id}, using defaults" );
            return $this->getDefaultAgentConfig( $agent_id );
        }
        
        return $config;
    }

    /**
     * Get neural network state for agent
     */
    public function getNeuralState( $agent_id ) {
        return $this->getAlgorithm( "neural_state_{$agent_id}" ) ?: [];
    }

    /**
     * Update neural network state
     */
    public function updateNeuralState( $agent_id, $state_updates ) {
        $current_state = $this->getNeuralState( $agent_id );
        $updated_state = array_merge( $current_state, $state_updates );
        
        return $this->write( "neural_state_{$agent_id}", $updated_state );
    }

    /**
     * Get inter-agent protocols
     */
    public function getInterAgentProtocols( $agent_id ) {
        return $this->getAlgorithm( "inter_agent_protocols_{$agent_id}" ) ?: [];
    }

    /**
     * Get strategic directives from ARCHER
     */
    public function getStrategicDirectives() {
        return $this->getAlgorithm( "strategic_directives_from_archer" ) ?: [];
    }

    /**
     * Get collaboration patterns for agent
     */
    public function getCollaborationPatterns( $agent_id ) {
        return $this->getAlgorithm( "collaboration_patterns_{$agent_id}" ) ?: [];
    }

    /**
     * Default agent configuration fallback
     */
    private function getDefaultAgentConfig( $agent_id ) {
        $defaults = [
            'huraii' => [
                'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 2048,
                'specialization' => 'artistic_creation'
            ],
            'cloe' => [
                'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-4',
                'temperature' => 0.3,
                'max_tokens' => 1024,
                'specialization' => 'analysis_optimization'
            ],
            'horace' => [
                'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-4',
                'temperature' => 0.5,
                'max_tokens' => 1536,
                'specialization' => 'data_synthesis'
            ],
            'thorius' => [
                'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-4',
                'temperature' => 0.4,
                'max_tokens' => 1800,
                'specialization' => 'strategic_orchestration'
            ],
            'archer' => [
                'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-4',
                'temperature' => 0.2,
                'max_tokens' => 2500,
                'specialization' => 'master_orchestrator'
            ]
        ];

        return $defaults[$agent_id] ?? $defaults['huraii'];
    }

    /**
     * Check if Vault address uses HTTPS or is localhost
     */
    private function isHttpsOrLocal( $url ) {
        if ( empty( $url ) ) {
            return false;
        }
        
        $parsed = parse_url( $url );
        
        // Allow HTTP for localhost and development environments
        if ( isset( $parsed['host'] ) ) {
            $host = strtolower( $parsed['host'] );
            if ( in_array( $host, ['localhost', '127.0.0.1', '::1'] ) ) {
                return true;
            }
            
            // Allow HTTP for development/staging environments
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                return true;
            }
        }
        
        // Require HTTPS for production
        return isset( $parsed['scheme'] ) && $parsed['scheme'] === 'https';
    }

    /**
     * Determine SSL verification settings
     */
    private function shouldVerifySSL() {
        // Disable SSL verification for localhost in development
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $parsed = parse_url( $this->vault_addr );
            if ( isset( $parsed['host'] ) ) {
                $host = strtolower( $parsed['host'] );
                if ( in_array( $host, ['localhost', '127.0.0.1', '::1'] ) ) {
                    return false;
                }
            }
        }
        
        return true; // Always verify SSL in production
    }
}
} 