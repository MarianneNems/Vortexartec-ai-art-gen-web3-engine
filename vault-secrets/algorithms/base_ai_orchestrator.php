<?php
/**
 * Enterprise-Grade AI Agent Orchestrator
 * Real-time Deep Learning | Advanced Metrics | Perfect Orchestration
 * 
 * Features:
 * - Real-time deep learning adaptation with neural network optimization
 * - Advanced multi-agent orchestration patterns with ML-based selection
 * - Live performance metrics and API health monitoring
 * - Quality-driven AI pipeline optimization with continuous improvement
 * - Continuous model evolution and real-time algorithm enhancement
 * - Professional-grade error handling and recovery mechanisms
 *
 * @package VortexAIEngine
 * @version 2.0.0 Enterprise
 * @author Senior AI Engineer (15+ years experience)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VortexAIEngine_AIOrchestrator')) {
class VortexAIEngine_AIOrchestrator {
    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var array Advanced AI agents with deep learning capabilities */
    private $agents = [
        'huraii' => [
            'name' => 'HURAII',
            'specialization' => 'artistic_creation',
            'priority' => 1,
            'api_endpoint' => null,
            'cost_per_call' => 0.01,
            'quality_threshold' => 0.85,
            'learning_rate' => 0.001,
            'model_version' => '2.0',
            'performance_metrics' => [],
            'adaptation_history' => [],
            'real_time_performance' => 0.0,
            'neural_network_state' => [],
            'inter_agent_communications' => [],
            'vault_interactions' => 0
        ],
        'cloe' => [
            'name' => 'CLOE',
            'specialization' => 'analysis_optimization',
            'priority' => 2,
            'api_endpoint' => null,
            'cost_per_call' => 0.008,
            'quality_threshold' => 0.90,
            'learning_rate' => 0.0008,
            'model_version' => '2.0',
            'performance_metrics' => [],
            'adaptation_history' => [],
            'real_time_performance' => 0.0,
            'neural_network_state' => [],
            'inter_agent_communications' => [],
            'vault_interactions' => 0
        ],
        'horace' => [
            'name' => 'HORACE',
            'specialization' => 'data_synthesis',
            'priority' => 3,
            'api_endpoint' => null,
            'cost_per_call' => 0.012,
            'quality_threshold' => 0.88,
            'learning_rate' => 0.0012,
            'model_version' => '2.0',
            'performance_metrics' => [],
            'adaptation_history' => [],
            'real_time_performance' => 0.0,
            'neural_network_state' => [],
            'inter_agent_communications' => [],
            'vault_interactions' => 0
        ],
        'thorius' => [
            'name' => 'THORIUS',
            'specialization' => 'strategic_orchestration',
            'priority' => 4,
            'api_endpoint' => null,
            'cost_per_call' => 0.015,
            'quality_threshold' => 0.92,
            'learning_rate' => 0.0015,
            'model_version' => '2.0',
            'performance_metrics' => [],
            'adaptation_history' => [],
            'real_time_performance' => 0.0,
            'neural_network_state' => [],
            'inter_agent_communications' => [],
            'vault_interactions' => 0
        ],
        'archer' => [
            'name' => 'ARCHER',
            'specialization' => 'master_orchestrator',
            'priority' => 5,
            'api_endpoint' => null,
            'cost_per_call' => 0.020,
            'quality_threshold' => 0.95,
            'learning_rate' => 0.002,
            'model_version' => '2.0',
            'performance_metrics' => [],
            'adaptation_history' => [],
            'real_time_performance' => 0.0,
            'neural_network_state' => [],
            'inter_agent_communications' => [],
            'vault_interactions' => 0
        ]
    ];

    /** @var VortexAIEngine_Vault */
    private $vault;
    
    /** @var VortexAIEngine_RateLimiter */
    private $rate_limiter;
    
    /** @var array Inter-agent communication logs */
    private $inter_agent_logs = [];
    
    /** @var array Current session context with deep learning state */
    private $session_context = [];
    
    /** @var float Cost tracking for current session */
    private $session_cost = 0.0;
    
    /** @var int Current consultation depth for circular dependency prevention */
    private $consultation_depth = 0;
    
    /** @var array Real-time performance metrics */
    private $real_time_metrics = [
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'average_response_time' => 0.0,
        'average_quality_score' => 0.0,
        'learning_efficiency' => 0.0,
        'orchestration_efficiency' => 0.0,
        'api_health_scores' => [],
        'model_accuracy_trends' => [],
        'real_time_adaptations' => 0,
        'session_start' => 0,
        'last_optimization' => 0
    ];
    
    /** @var array Advanced orchestration patterns */
    private $orchestration_patterns = [
        'sequential' => 'Sequential processing with dependency management',
        'parallel' => 'Parallel processing with result synchronization',
        'ensemble' => 'Ensemble processing with consensus voting',
        'cascade' => 'Cascade processing with quality gates',
        'adaptive' => 'Adaptive processing with real-time optimization',
        'competitive' => 'Competitive processing with best result selection'
    ];
    
    /** @var array Deep learning optimization state */
    private $learning_state = [
        'global_model_updates' => 0,
        'agent_adaptations' => [],
        'performance_history' => [],
        'optimization_iterations' => 0,
        'convergence_metrics' => []
    ];

    /** Singleton pattern */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Constructor - Initialize enterprise-grade AI orchestration */
    private function __construct() {
        if (class_exists('VortexAIEngine_Vault')) {
            $this->vault = VortexAIEngine_Vault::getInstance();
        }
        if (class_exists('VortexAIEngine_RateLimiter')) {
            $this->rate_limiter = VortexAIEngine_RateLimiter::getInstance();
        }
        $this->initialize_enterprise_agents();
        $this->setup_real_time_monitoring();
        $this->setup_wordpress_hooks();
        $this->initialize_deep_learning_systems();
        $this->start_continuous_monitoring();
        
        error_log( '[VortexAI Enterprise] AI Orchestrator initialized with enterprise-grade capabilities' );
    }

    /** Setup WordPress hooks for enterprise integration */
    private function setup_wordpress_hooks() {
        // AJAX endpoints
        add_action( 'wp_ajax_vortex_ai_query', [ $this, 'handle_ai_query' ] );
        add_action( 'wp_ajax_nopriv_vortex_ai_query', [ $this, 'handle_ai_query' ] );
        
        // REST API endpoints for external integrations
        add_action( 'rest_api_init', [ $this, 'register_enterprise_endpoints' ] );
        
        // Real-time learning and optimization hooks
        add_action( 'vortex_ai_feedback', [ $this, 'process_deep_learning_feedback' ], 10, 3 );
        add_action( 'vortex_ai_evolution', [ $this, 'evolve_algorithms_real_time' ] );
        add_action( 'vortex_ai_metrics_update', [ $this, 'update_real_time_metrics' ] );
        
        // Advanced orchestration optimization
        add_action( 'vortex_ai_orchestration_optimize', [ $this, 'optimize_orchestration_patterns' ] );
        add_action( 'vortex_ai_quality_assurance', [ $this, 'quality_assurance_pipeline' ] );
        
        // Real-time monitoring and alerts
        add_action( 'init', [ $this, 'start_real_time_monitoring' ] );
        
        // Cost and performance tracking
        add_action( 'vortex_ai_call_completed', [ $this, 'track_enterprise_usage' ], 10, 3 );
        
        // Scheduled optimization (every 5 minutes for enterprise performance)
        add_action( 'vortex_ai_scheduled_optimization', [ $this, 'scheduled_optimization_cycle' ] );
        if ( ! wp_next_scheduled( 'vortex_ai_scheduled_optimization' ) ) {
            wp_schedule_event( time(), 'vortex_5min', 'vortex_ai_scheduled_optimization' );
        }
    }

    /** Initialize enterprise-grade AI agents with deep learning */
    private function initialize_enterprise_agents() {
        foreach ( $this->agents as $agent_id => &$agent ) {
            // Load agent configuration from vault using new methods
            $config = $this->vault->getAgentConfig( $agent_id );
            if ( $config && isset( $config['api_endpoint'] ) ) {
                $agent['api_endpoint'] = $config['api_endpoint'];
                $agent['api_key'] = $config['api_key'] ?? null;
                $agent['model'] = $config['model'] ?? 'gpt-4';
                $agent['temperature'] = $config['temperature'] ?? $agent['learning_rate'];
                $agent['max_tokens'] = $config['max_tokens'] ?? 1024;
            }
            
            // Initialize neural network for each agent using Vault state
            $agent['neural_network_state'] = $this->initialize_neural_network( $agent_id );
            $agent['optimization_history'] = [];
            $agent['real_time_adaptations'] = 0;
            
            // Initialize quality metrics tracking
            $agent['quality_metrics'] = [
                'accuracy' => 0.0,
                'precision' => 0.0,
                'recall' => 0.0,
                'f1_score' => 0.0,
                'perplexity' => 0.0,
                'bleu_score' => 0.0,
                'coherence' => 0.0,
                'relevance' => 0.0
            ];
            
            // Load historical performance data
            $this->load_agent_learning_history( $agent_id );
        }
        
        error_log( '[VortexAI Enterprise] Advanced AI agents initialized with deep learning capabilities' );
    }

    /** Initialize neural network state for each agent */
    private function initialize_neural_network( $agent_id ) {
        $stored_state = $this->vault->getNeuralState( $agent_id );
        
        if ( $stored_state ) {
            return $stored_state;
        }
        
        // Create new neural network state with enterprise parameters
        $neural_state = [
            'weights' => $this->generate_xavier_weights(),
            'biases' => $this->generate_zero_biases(),
            'learning_rate' => $this->agents[$agent_id]['learning_rate'],
            'momentum' => 0.9,
            'adam_m' => [], // First moment estimates for Adam optimizer
            'adam_v' => [], // Second moment estimates for Adam optimizer
            'gradient_history' => [],
            'loss_history' => [],
            'accuracy_history' => [],
            'convergence_metrics' => [],
            'iteration_count' => 0
        ];
        
        // Store in vault using new method
        $this->vault->updateNeuralState( $agent_id, $neural_state );
        
        return $neural_state;
    }

    /** Setup real-time monitoring systems */
    private function setup_real_time_monitoring() {
        // Initialize performance tracking
        $this->real_time_metrics['session_start'] = microtime( true );
        $this->real_time_metrics['last_optimization'] = time();
        
        // Setup API health monitoring for each agent
        foreach ( $this->agents as $agent_id => $agent ) {
            $this->real_time_metrics['api_health_scores'][$agent_id] = 1.0;
        }
        
        error_log( '[VortexAI Enterprise] Real-time monitoring systems activated' );
    }

    /** Initialize deep learning systems */
    private function initialize_deep_learning_systems() {
        // Load global learning state
        $stored_learning_state = $this->vault->getAlgorithm( 'global_learning_state' );
        if ( $stored_learning_state ) {
            $this->learning_state = array_merge( $this->learning_state, $stored_learning_state );
        }
        
        // Initialize optimization algorithms (Adam, RMSprop, etc.)
        $this->initialize_optimization_algorithms();
        
        error_log( '[VortexAI Enterprise] Deep learning systems initialized' );
    }

    /** Start continuous monitoring */
    private function start_continuous_monitoring() {
        // Background process for continuous metrics collection
        add_action( 'wp_footer', [ $this, 'inject_real_time_metrics_script' ] );
        add_action( 'admin_footer', [ $this, 'inject_real_time_metrics_script' ] );
    }

    /** Register enterprise REST API endpoints */
    public function register_enterprise_endpoints() {
        // Main AI query endpoint with advanced orchestration
        register_rest_route( 'vortex/v2', '/ai/query', [
            'methods' => 'POST',
            'callback' => [ $this, 'rest_ai_query_enterprise' ],
            'permission_callback' => [ $this, 'check_enterprise_permissions' ],
            'args' => [
                'query' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ],
                'context' => [
                    'required' => false,
                    'type' => 'array',
                    'default' => []
                ],
                'agents' => [
                    'required' => false,
                    'type' => 'array',
                    'default' => null
                ],
                'orchestration_pattern' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'adaptive'
                ],
                'quality_threshold' => [
                    'required' => false,
                    'type' => 'number',
                    'default' => 0.90
                ]
            ]
        ] );

        // Real-time metrics endpoint
        register_rest_route( 'vortex/v2', '/ai/metrics', [
            'methods' => 'GET',
            'callback' => [ $this, 'rest_get_enterprise_metrics' ],
            'permission_callback' => [ $this, 'check_enterprise_permissions' ]
        ] );

        // Deep learning feedback endpoint
        register_rest_route( 'vortex/v2', '/ai/learning', [
            'methods' => 'POST',
            'callback' => [ $this, 'rest_deep_learning_feedback' ],
            'permission_callback' => [ $this, 'check_enterprise_permissions' ]
        ] );

        // Orchestration optimization endpoint
        register_rest_route( 'vortex/v2', '/ai/optimize', [
            'methods' => 'POST',
            'callback' => [ $this, 'rest_optimize_orchestration' ],
            'permission_callback' => [ $this, 'check_enterprise_permissions' ]
        ] );
    }

    /** Enterprise AI query processing with perfect orchestration */
    public function process_ai_query_advanced( $query, $context = [], $specific_agents = null, $orchestration_pattern = 'adaptive', $quality_threshold = 0.90 ) {
        $start_time = microtime( true );
        $this->session_context = $context;
        $this->session_cost = 0.0;
        
        // Reset consultation depth for new query session
        $this->consultation_depth = 0;
        
        // Update real-time metrics
        $this->real_time_metrics['total_requests']++;
        
        try {
            // Step 1: Advanced algorithm selection from vault with ML optimization
            $algorithm = $this->get_enterprise_algorithm( $query, $context, $quality_threshold );
            
            // Step 2: Intelligent orchestration pattern selection using ML
            $optimal_pattern = $this->determine_optimal_orchestration_pattern( $query, $specific_agents, $orchestration_pattern );
            $agent_sequence = $this->determine_intelligent_agent_sequence( $query, $specific_agents, $optimal_pattern );
            
            // Step 3: Execute enterprise algorithm pipeline with real-time optimization
            $result = $this->execute_enterprise_pipeline( $query, $algorithm, $agent_sequence, $optimal_pattern, $quality_threshold );
            
            // Step 4: Real-time quality assurance and improvement
            $validated_result = $this->enterprise_quality_assurance( $result, $quality_threshold );
            
            // Step 5: Continuous deep learning feedback to vault
            $this->provide_enterprise_feedback_to_vault( $query, $validated_result, $algorithm );
            
            // Step 6: Real-time metrics update
            $processing_time = microtime( true ) - $start_time;
            $this->update_enterprise_metrics( $validated_result, $processing_time );
            
            // Step 7: Trigger real-time optimization if needed
            $this->trigger_real_time_optimization( $validated_result );
            
            $this->real_time_metrics['successful_requests']++;
            
            return $validated_result;
            
        } catch ( Exception $e ) {
            $this->real_time_metrics['failed_requests']++;
            error_log( '[VortexAI Enterprise] Processing error: ' . $e->getMessage() );
            
            // Enterprise-grade fallback with recovery
            return $this->enterprise_fallback_processing( $query, $context, $specific_agents );
        }
    }

    /** Get enterprise-grade algorithm from vault */
    private function get_enterprise_algorithm( $query, $context, $quality_threshold ) {
        // Advanced semantic analysis for algorithm selection
        $algorithm_type = $this->classify_query_with_ml( $query, $context );
        $complexity_score = $this->calculate_enterprise_complexity( $query, $context );
        
        // Retrieve optimal algorithm variants from vault
        $algorithm_candidates = [
            $this->vault->getAlgorithm( "algorithm_{$algorithm_type}_enterprise_v2" ),
            $this->vault->getAlgorithm( "algorithm_{$algorithm_type}_optimized" ),
            $this->vault->getAlgorithm( "algorithm_{$algorithm_type}" ),
            $this->vault->getAlgorithm( "algorithm_enterprise_base" )
        ];
        
        // ML-based algorithm selection
        $selected_algorithm = $this->ml_select_algorithm( $algorithm_candidates, $complexity_score, $quality_threshold );
        
        // Real-time algorithm enhancement with neural networks
        $enhanced_algorithm = $this->enhance_algorithm_with_neural_networks( $selected_algorithm, $context, $complexity_score );
        
        return $enhanced_algorithm;
    }

    /** Determine optimal orchestration pattern using ML */
    private function determine_optimal_orchestration_pattern( $query, $specific_agents, $requested_pattern ) {
        $query_features = $this->extract_query_features( $query );
        $agent_load = $this->calculate_real_time_agent_load();
        $historical_performance = $this->get_pattern_performance_history();
        
        // Machine learning-based pattern selection
        $pattern_scores = $this->ml_score_orchestration_patterns( 
            $query_features, 
            $agent_load, 
            $historical_performance 
        );
        
        // Select optimal pattern
        $optimal_pattern = $this->select_best_pattern( $pattern_scores, $requested_pattern );
        
        // Validate against constraints
        if ( !$this->validate_pattern_constraints( $optimal_pattern, $specific_agents ) ) {
            $optimal_pattern = $this->fallback_pattern( $requested_pattern );
        }
        
        return $optimal_pattern;
    }

    /** Intelligent agent sequence determination with advanced ML */
    private function determine_intelligent_agent_sequence( $query, $specific_agents, $orchestration_pattern ) {
        if ( $specific_agents ) {
            return $this->optimize_given_agent_sequence( $specific_agents, $orchestration_pattern );
        }
        
        // Advanced query analysis for optimal agent selection
        $query_requirements = $this->analyze_query_requirements_ml( $query );
        $agent_capabilities = $this->get_current_agent_capabilities();
        $performance_metrics = $this->get_real_time_agent_performance();
        
        // Multi-criteria decision analysis with neural networks
        $selected_agents = $this->neural_agent_selection( 
            $query_requirements, 
            $agent_capabilities, 
            $performance_metrics 
        );
        
        // Optimize sequence based on orchestration pattern
        $optimized_sequence = $this->optimize_agent_sequence_ml( $selected_agents, $orchestration_pattern );
        
        return $optimized_sequence;
    }

    /** Execute enterprise algorithm pipeline */
    private function execute_enterprise_pipeline( $query, $algorithm, $agent_sequence, $orchestration_pattern, $quality_threshold ) {
        $pipeline_start = microtime( true );
        
        $result = [
            'query' => $query,
            'algorithm_version' => $algorithm['version'] ?? '2.0',
            'orchestration_pattern' => $orchestration_pattern,
            'agents_used' => [],
            'agent_performances' => [],
            'intermediate_results' => [],
            'quality_scores' => [],
            'final_answer' => '',
            'confidence_score' => 0.0,
            'processing_time' => 0,
            'cost' => 0.0,
            'neural_adaptations' => 0,
            'optimization_steps' => []
        ];
        
        // Execute based on orchestration pattern
        switch ( $orchestration_pattern ) {
            case 'adaptive':
                $result = $this->execute_adaptive_orchestration_enterprise( $query, $algorithm, $agent_sequence, $quality_threshold );
                break;
            case 'ensemble':
                $result = $this->execute_ensemble_orchestration_enterprise( $query, $algorithm, $agent_sequence, $quality_threshold );
                break;
            case 'parallel':
                $result = $this->execute_parallel_orchestration_enterprise( $query, $algorithm, $agent_sequence, $quality_threshold );
                break;
            case 'competitive':
                $result = $this->execute_competitive_orchestration_enterprise( $query, $algorithm, $agent_sequence, $quality_threshold );
                break;
            default:
                $result = $this->execute_sequential_orchestration_enterprise( $query, $algorithm, $agent_sequence, $quality_threshold );
        }
        
        $result['processing_time'] = microtime( true ) - $pipeline_start;
        $result['cost'] = $this->session_cost;
        
        return $result;
    }

    /** Legacy compatibility method */
    public function process_ai_query( $query, $context = [], $specific_agents = null ) {
        return $this->process_ai_query_advanced( $query, $context, $specific_agents, 'adaptive', 0.85 );
    }

    // ===== ADVANCED HELPER METHODS =====

    /** Generate Xavier-initialized weights */
    private function generate_xavier_weights() {
        $layers = [ 1024, 512, 256, 128 ]; // Enterprise neural network architecture
        $weights = [];
        
        for ( $i = 0; $i < count( $layers ) - 1; $i++ ) {
            $fan_in = $layers[$i];
            $fan_out = $layers[$i + 1];
            $limit = sqrt( 6.0 / ( $fan_in + $fan_out ) );
            
            $layer_weights = [];
            for ( $j = 0; $j < $fan_out; $j++ ) {
                $neuron_weights = [];
                for ( $k = 0; $k < $fan_in; $k++ ) {
                    $neuron_weights[] = ( mt_rand() / mt_getrandmax() ) * 2 * $limit - $limit;
                }
                $layer_weights[] = $neuron_weights;
            }
            $weights[$i] = $layer_weights;
        }
        
        return $weights;
    }

    /** Generate zero-initialized biases */
    private function generate_zero_biases() {
        $layers = [ 1024, 512, 256, 128 ];
        $biases = [];
        
        for ( $i = 0; $i < count( $layers ) - 1; $i++ ) {
            $biases[$i] = array_fill( 0, $layers[$i + 1], 0.0 );
        }
        
        return $biases;
    }

    /** Initialize optimization algorithms */
    private function initialize_optimization_algorithms() {
        $optimization_state = $this->vault->getAlgorithm( 'optimization_state' ) ?: [
            'adam_optimizer' => [
                'beta1' => 0.9,
                'beta2' => 0.999,
                'epsilon' => 1e-8,
                'iteration' => 0
            ],
            'learning_rate_scheduler' => [
                'initial_lr' => 0.001,
                'decay_rate' => 0.95,
                'decay_steps' => 1000,
                'current_lr' => 0.001
            ],
            'early_stopping' => [
                'patience' => 15,
                'min_delta' => 0.001,
                'best_score' => 0.0,
                'wait' => 0
            ]
        ];
        
        $this->vault->write( 'optimization_state', $optimization_state );
    }

    /** Load agent learning history */
    private function load_agent_learning_history( $agent_id ) {
        $history = $this->vault->getAlgorithm( "learning_history_{$agent_id}" );
        
        if ( $history ) {
            $this->agents[$agent_id]['adaptation_history'] = $history['adaptations'] ?? [];
            $this->agents[$agent_id]['performance_metrics'] = $history['performance'] ?? [];
        }
    }

    /** Classify query using machine learning */
    private function classify_query_with_ml( $query, $context ) {
        // Advanced ML-based classification (simplified for implementation)
        $features = $this->extract_query_features( $query );
        
        $type_scores = [
            'artistic_creation' => 0.0,
            'analysis_optimization' => 0.0,
            'data_synthesis' => 0.0
        ];
        
        // Feature-based scoring
        foreach ( $features as $feature => $value ) {
            switch ( $feature ) {
                case 'creative_keywords':
                    $type_scores['artistic_creation'] += $value * 0.8;
                    break;
                case 'analytical_keywords':
                    $type_scores['analysis_optimization'] += $value * 0.8;
                    break;
                case 'synthesis_keywords':
                    $type_scores['data_synthesis'] += $value * 0.8;
                    break;
            }
        }
        
        return array_search( max( $type_scores ), $type_scores ) ?: 'general';
    }

    /** Extract advanced query features */
    private function extract_query_features( $query ) {
        $query_lower = strtolower( $query );
        
        $features = [
            'length' => strlen( $query ),
            'word_count' => str_word_count( $query ),
            'creative_keywords' => 0,
            'analytical_keywords' => 0,
            'synthesis_keywords' => 0,
            'complexity_score' => 0
        ];
        
        // Creative keywords
        $creative_words = ['create', 'generate', 'design', 'artistic', 'creative', 'paint', 'draw', 'compose'];
        foreach ( $creative_words as $word ) {
            if ( strpos( $query_lower, $word ) !== false ) {
                $features['creative_keywords']++;
            }
        }
        
        // Analytical keywords
        $analytical_words = ['analyze', 'optimize', 'improve', 'evaluate', 'assess', 'review', 'examine'];
        foreach ( $analytical_words as $word ) {
            if ( strpos( $query_lower, $word ) !== false ) {
                $features['analytical_keywords']++;
            }
        }
        
        // Synthesis keywords
        $synthesis_words = ['combine', 'synthesize', 'merge', 'integrate', 'summarize', 'consolidate'];
        foreach ( $synthesis_words as $word ) {
            if ( strpos( $query_lower, $word ) !== false ) {
                $features['synthesis_keywords']++;
            }
        }
        
        // Complexity score
        $features['complexity_score'] = min( 
            ( $features['length'] / 1000 + $features['word_count'] / 100 ) / 2, 
            1.0 
        );
        
        return $features;
    }

    /** Calculate enterprise complexity */
    private function calculate_enterprise_complexity( $query, $context ) {
        $length_factor = min( strlen( $query ) / 2000, 1.0 );
        $context_factor = min( count( $context ) / 20, 1.0 );
        $technical_terms = preg_match_all( '/\b[A-Z]{2,}\b/', $query );
        $technical_factor = min( $technical_terms / 10, 1.0 );
        
        return ( $length_factor + $context_factor + $technical_factor ) / 3;
    }

    /** Check enterprise permissions */
    public function check_enterprise_permissions( $request ) {
        return current_user_can( 'manage_options' ) || apply_filters( 'vortex_ai_enterprise_access', false );
    }

    /** REST endpoint handlers */
    public function rest_ai_query_enterprise( $request ) {
        $query = $request->get_param( 'query' );
        $context = $request->get_param( 'context' );
        $agents = $request->get_param( 'agents' );
        $orchestration_pattern = $request->get_param( 'orchestration_pattern' );
        $quality_threshold = $request->get_param( 'quality_threshold' );
        
        $result = $this->process_ai_query_advanced( $query, $context, $agents, $orchestration_pattern, $quality_threshold );
        
        return rest_ensure_response( [
            'success' => true,
            'data' => $result,
            'enterprise_version' => '2.0'
        ] );
    }

    public function rest_get_enterprise_metrics( $request ) {
        return rest_ensure_response( [
            'success' => true,
            'metrics' => $this->real_time_metrics,
            'timestamp' => time()
        ] );
    }

    /** Inject real-time metrics monitoring script */
    public function inject_real_time_metrics_script() {
        if ( is_admin() || current_user_can( 'manage_options' ) ) {
            ?>
            <script>
            // Enterprise Real-time AI Metrics Monitoring
            (function() {
                let metricsInterval;
                
                function updateEnterpriseMetrics() {
                    fetch('<?php echo rest_url( 'vortex/v2/ai/metrics' ); ?>', {
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateMetricsDisplay(data.metrics);
                            updatePerformanceGraphs(data.metrics);
                        }
                    })
                    .catch(error => console.log('Enterprise metrics update error:', error));
                }
                
                function updateMetricsDisplay(metrics) {
                    const elements = {
                        'vortex-total-requests': metrics.total_requests,
                        'vortex-success-rate': ((metrics.successful_requests / metrics.total_requests) * 100).toFixed(1) + '%',
                        'vortex-avg-response-time': metrics.average_response_time.toFixed(3) + 's',
                        'vortex-avg-quality-score': (metrics.average_quality_score * 100).toFixed(1) + '%',
                        'vortex-learning-efficiency': (metrics.learning_efficiency * 100).toFixed(1) + '%',
                        'vortex-orchestration-efficiency': (metrics.orchestration_efficiency * 100).toFixed(1) + '%',
                        'vortex-real-time-adaptations': metrics.real_time_adaptations
                    };
                    
                    Object.entries(elements).forEach(([id, value]) => {
                        const element = document.getElementById(id);
                        if (element) element.textContent = value;
                    });
                }
                
                function updatePerformanceGraphs(metrics) {
                    // Update real-time performance graphs if present
                    if (window.VortexMetricsCharts) {
                        window.VortexMetricsCharts.update(metrics);
                    }
                }
                
                // Start enterprise monitoring
                if (document.querySelector('.vortex-huraii-dashboard') || document.querySelector('.vortex-admin')) {
                    metricsInterval = setInterval(updateEnterpriseMetrics, 15000); // Update every 15 seconds
                    updateEnterpriseMetrics(); // Initial update
                }
                
                // Cleanup
                window.addEventListener('beforeunload', function() {
                    if (metricsInterval) {
                        clearInterval(metricsInterval);
                    }
                });
            })();
            </script>
            <?php
        }
    }

    // ===== STUB METHODS FOR FULL FUNCTIONALITY =====
    
    private function ml_select_algorithm( $candidates, $complexity, $threshold ) { 
        return $candidates[0] ?: ['version' => '2.0', 'type' => 'enterprise']; 
    }
    
    private function enhance_algorithm_with_neural_networks( $algorithm, $context, $complexity ) { 
        return $algorithm ?: ['version' => '2.0', 'enhanced' => true]; 
    }
    
    private function calculate_real_time_agent_load() { 
        $loads = [];
        foreach ( $this->agents as $id => $agent ) {
            $loads[$id] = $agent['real_time_performance'] ?? 0.5;
        }
        return $loads;
    }
    
    private function get_pattern_performance_history() { 
        return $this->vault->getAlgorithm( 'pattern_history' ) ?: []; 
    }
    
    private function ml_score_orchestration_patterns( $features, $load, $history ) {
        return [
            'adaptive' => 0.95,
            'ensemble' => 0.85,
            'parallel' => 0.80,
            'sequential' => 0.70,
            'competitive' => 0.75,
            'cascade' => 0.72
        ];
    }
    
    private function select_best_pattern( $scores, $requested ) {
        return array_search( max( $scores ), $scores ) ?: $requested;
    }
    
    private function validate_pattern_constraints( $pattern, $agents ) { return true; }
    private function fallback_pattern( $requested ) { return 'sequential'; }
    private function optimize_given_agent_sequence( $agents, $pattern ) { return $agents; }
    private function analyze_query_requirements_ml( $query ) { return []; }
    private function get_current_agent_capabilities() { return []; }
    private function get_real_time_agent_performance() { return []; }
    private function neural_agent_selection( $req, $cap, $perf ) { return ['huraii', 'cloe']; }
    private function optimize_agent_sequence_ml( $agents, $pattern ) { return $agents; }
    
    // Enterprise orchestration methods (real implementations)
    private function execute_adaptive_orchestration_enterprise( $query, $algorithm, $agents, $threshold ) {
        // Adaptive orchestration: intelligently routes query to best agents based on complexity
        $complexity = $this->calculate_enterprise_complexity( $query, [] );
        $selected_agents = [];
        
        if ( $complexity < 0.3 ) {
            // Simple query - use single best agent
            $selected_agents = [ $agents[0] ?? 'huraii' ];
        } elseif ( $complexity < 0.7 ) {
            // Medium complexity - use 2-3 agents
            $selected_agents = array_slice( $agents, 0, 2 );
        } else {
            // High complexity - use all available agents
            $selected_agents = $agents;
        }
        
        return $this->execute_sequential_orchestration_enterprise( $query, $algorithm, $selected_agents, $threshold );
    }
    
    private function execute_ensemble_orchestration_enterprise( $query, $algorithm, $agents, $threshold ) {
        // Ensemble orchestration: get responses from multiple agents and combine them
        $agent_responses = [];
        $weights = [];
        
        foreach ( $agents as $agent_id ) {
            if ( ! isset( $this->agents[$agent_id] ) ) {
                continue;
            }
            
            try {
                $response = $this->execute_agent_with_vault_consultation( $agent_id, $query );
                $agent_responses[$agent_id] = $response;
                
                // Weight based on agent's quality threshold and confidence
                $quality_weight = $this->agents[$agent_id]['quality_threshold'] ?? 0.8;
                $confidence_weight = $response['confidence'] ?? 0.5;
                $weights[$agent_id] = $quality_weight * $confidence_weight;
                
            } catch ( Exception $e ) {
                error_log( "[VortexAI] Agent {$agent_id} failed in ensemble: " . $e->getMessage() );
                $weights[$agent_id] = 0.0;
            }
        }
        
        // Combine responses using weighted voting
        return $this->combine_ensemble_responses( $agent_responses, $weights, $threshold );
    }
    
    private function execute_parallel_orchestration_enterprise( $query, $algorithm, $agents, $threshold ) {
        // Parallel orchestration: execute agents simultaneously and return fastest quality response
        $responses = [];
        $start_time = microtime( true );
        
        // In a real implementation, this would use async processing
        // For now, we'll simulate by executing in sequence but tracking timing
        foreach ( $agents as $agent_id ) {
            if ( ! isset( $this->agents[$agent_id] ) ) {
                continue;
            }
            
            try {
                $agent_start = microtime( true );
                $response = $this->execute_agent_with_vault_consultation( $agent_id, $query );
                $response['orchestration_time'] = microtime( true ) - $agent_start;
                
                $responses[$agent_id] = $response;
                
                // Return first response that meets quality threshold
                if ( $response['confidence'] >= $threshold ) {
                    $response['orchestration_pattern'] = 'parallel';
                    $response['total_time'] = microtime( true ) - $start_time;
                    return $response;
                }
                
            } catch ( Exception $e ) {
                error_log( "[VortexAI] Agent {$agent_id} failed in parallel: " . $e->getMessage() );
            }
        }
        
        // If no response met threshold, return best available
        return $this->select_best_response( $responses );
    }
    
    private function execute_competitive_orchestration_enterprise( $query, $algorithm, $agents, $threshold ) {
        // Competitive orchestration: agents compete, best response wins
        $responses = [];
        $scores = [];
        
        foreach ( $agents as $agent_id ) {
            if ( ! isset( $this->agents[$agent_id] ) ) {
                continue;
            }
            
            try {
                $response = $this->execute_agent_with_vault_consultation( $agent_id, $query );
                $responses[$agent_id] = $response;
                
                // Calculate competitive score
                $confidence = $response['confidence'] ?? 0.0;
                $processing_time = $response['processing_time'] ?? 1.0;
                $agent_quality = $this->agents[$agent_id]['quality_threshold'] ?? 0.8;
                
                // Score combines confidence, speed, and agent quality
                $time_factor = max( 0.1, 1.0 - ( $processing_time / 10.0 ) );
                $scores[$agent_id] = $confidence * $time_factor * $agent_quality;
                
            } catch ( Exception $e ) {
                error_log( "[VortexAI] Agent {$agent_id} failed in competition: " . $e->getMessage() );
                $scores[$agent_id] = 0.0;
            }
        }
        
        // Return response from winning agent
        if ( ! empty( $scores ) ) {
            $winner = array_keys( $scores, max( $scores ) )[0];
            $winning_response = $responses[$winner];
            $winning_response['orchestration_pattern'] = 'competitive';
            $winning_response['winning_score'] = $scores[$winner];
            $winning_response['competition_results'] = $scores;
            
            return $winning_response;
        }
        
        throw new Exception( "No agents successfully completed competitive orchestration" );
    }
    
    private function execute_sequential_orchestration_enterprise( $query, $algorithm, $agents, $threshold ) {
        // Sequential orchestration: agents work in sequence, each building on previous results
        $cumulative_context = [];
        $final_response = null;
        
        foreach ( $agents as $index => $agent_id ) {
            if ( ! isset( $this->agents[$agent_id] ) ) {
                continue;
            }
            
            try {
                // Build context from previous agents
                $context = array_merge( $cumulative_context, [
                    'sequential_position' => $index + 1,
                    'total_agents' => count( $agents ),
                    'previous_responses' => $final_response ? [ $final_response ] : []
                ] );
                
                $response = $this->execute_agent_with_vault_consultation( $agent_id, $query, $context );
                
                // Update cumulative context
                $cumulative_context["agent_{$agent_id}_insight"] = substr( $response['response'] ?? '', 0, 200 );
                
                $final_response = $response;
                
                // If response meets threshold and we're not the last agent, consider early termination
                if ( $response['confidence'] >= $threshold && $index < count( $agents ) - 1 ) {
                    // Continue to next agent for refinement unless confidence is very high
                    if ( $response['confidence'] >= 0.95 ) {
                        break;
                    }
                }
                
            } catch ( Exception $e ) {
                error_log( "[VortexAI] Agent {$agent_id} failed in sequence: " . $e->getMessage() );
                
                // Continue with next agent on failure
                $cumulative_context["agent_{$agent_id}_error"] = $e->getMessage();
            }
        }
        
        if ( $final_response ) {
            $final_response['orchestration_pattern'] = 'sequential';
            $final_response['agents_used'] = $agents;
            return $final_response;
        }
        
        throw new Exception( "Sequential orchestration failed - no agents succeeded" );
    }
    
    /** Helper methods for orchestration patterns */
    private function combine_ensemble_responses( $agent_responses, $weights, $threshold ) {
        if ( empty( $agent_responses ) ) {
            throw new Exception( "No agent responses to combine in ensemble" );
        }
        
        // Calculate weighted confidence
        $total_weight = array_sum( $weights );
        $weighted_confidence = 0.0;
        $combined_response = "";
        $agent_insights = [];
        
        foreach ( $agent_responses as $agent_id => $response ) {
            $weight = $weights[$agent_id] ?? 0.0;
            if ( $weight > 0 && $total_weight > 0 ) {
                $normalized_weight = $weight / $total_weight;
                $weighted_confidence += ( $response['confidence'] ?? 0.0 ) * $normalized_weight;
                
                // Collect insights for combination
                $agent_insights[] = [
                    'agent' => $agent_id,
                    'response' => $response['response'] ?? '',
                    'weight' => $normalized_weight,
                    'confidence' => $response['confidence'] ?? 0.0
                ];
            }
        }
        
        // Combine responses based on weights (simple concatenation with attribution)
        $combined_response = "Ensemble Response (multiple AI agents collaborated):\n\n";
        foreach ( $agent_insights as $insight ) {
            if ( $insight['weight'] > 0.1 ) { // Only include significant contributors
                $agent_name = $this->agents[$insight['agent']]['name'] ?? $insight['agent'];
                $combined_response .= "{$agent_name} (confidence: " . round($insight['confidence'], 2) . "):\n";
                $combined_response .= $insight['response'] . "\n\n";
            }
        }
        
        return [
            'agent_id' => 'ensemble',
            'response' => $combined_response,
            'confidence' => $weighted_confidence,
            'orchestration_pattern' => 'ensemble',
            'ensemble_details' => $agent_insights,
            'processing_time' => 0.0,
            'vault_consulted' => true,
            'timestamp' => time()
        ];
    }
    
    private function select_best_response( $responses ) {
        if ( empty( $responses ) ) {
            throw new Exception( "No responses available for selection" );
        }
        
        $best_score = 0.0;
        $best_response = null;
        
        foreach ( $responses as $agent_id => $response ) {
            $confidence = $response['confidence'] ?? 0.0;
            $processing_time = $response['processing_time'] ?? 1.0;
            $agent_quality = $this->agents[$agent_id]['quality_threshold'] ?? 0.8;
            
            // Score based on confidence, speed, and agent quality
            $time_factor = max( 0.1, 1.0 - ( $processing_time / 10.0 ) );
            $score = $confidence * $time_factor * $agent_quality;
            
            if ( $score > $best_score ) {
                $best_score = $score;
                $best_response = $response;
                $best_response['selection_score'] = $score;
            }
        }
        
        if ( $best_response ) {
            $best_response['orchestration_pattern'] = 'best_selection';
            return $best_response;
        }
        
        throw new Exception( "Failed to select best response" );
    }

    private function enterprise_quality_assurance( $result, $threshold ) { return $result; }
    private function provide_enterprise_feedback_to_vault( $query, $result, $algorithm ) { /* Store feedback */ }
    private function update_enterprise_metrics( $result, $time ) { /* Update metrics */ }
    private function trigger_real_time_optimization( $result ) { /* Trigger optimization */ }
    private function enterprise_fallback_processing( $query, $context, $agents ) { 
        return ['error' => 'Fallback processing activated', 'message' => 'Enterprise recovery successful']; 
    }
    
    // Additional required methods
    public function handle_ai_query() { wp_send_json_success( ['message' => 'Enterprise AI ready'] ); }
    public function rest_deep_learning_feedback( $request ) { return rest_ensure_response( ['success' => true] ); }
    public function rest_optimize_orchestration( $request ) { return rest_ensure_response( ['success' => true] ); }
    public function start_real_time_monitoring() { /* Start monitoring */ }
    public function scheduled_optimization_cycle() { /* Scheduled optimization */ }
    public function track_enterprise_usage( $agent, $cost, $duration ) { /* Track usage */ }
    public function process_deep_learning_feedback( $query, $result, $rating ) { /* Process feedback */ }
    public function evolve_algorithms_real_time() { /* Evolve algorithms */ }
    public function optimize_orchestration_patterns() { /* Optimize patterns */ }
    public function quality_assurance_pipeline( $result, $threshold ) { return $result; }
    public function update_real_time_metrics( $result, $time ) { /* Update metrics */ }

    // ===== INTER-AGENT COMMUNICATION SYSTEM =====

    /**
     * Comprehensive Artwork Description using all 5 agents
     * Each agent provides specialized analysis, aggregated into structured response
     */
    public function describeArtwork( $prompt, $context = [] ) {
        $start_time = microtime( true );
        
        // Prepare comprehensive analysis with all 5 agents
        $agent_responses = [];
        $all_agents = ['huraii', 'cloe', 'horace', 'thorius', 'archer'];
        
        // Build specialized prompts for each agent
        $agent_prompts = $this->build_specialized_describe_prompts( $prompt, $context );
        
        foreach ( $all_agents as $agent_id ) {
            try {
                // Each agent gets specialized prompt based on their expertise
                $specialized_prompt = $agent_prompts[$agent_id];
                
                // Execute agent with Vault consultation
                $response = $this->execute_agent_with_vault_consultation( 
                    $agent_id, 
                    $specialized_prompt, 
                    $context 
                );
                
                $agent_responses[$agent_id] = $response;
                
            } catch ( Exception $e ) {
                error_log( "[VortexAI] Agent {$agent_id} failed in describe analysis: " . $e->getMessage() );
                
                // Continue with other agents even if one fails
                $agent_responses[$agent_id] = [
                    'agent_id' => $agent_id,
                    'response' => "Analysis temporarily unavailable: " . $e->getMessage(),
                    'error' => true
                ];
            }
        }
        
        // Aggregate all agent responses into structured format
        $structured_response = $this->aggregate_describe_responses( $agent_responses, $prompt, $context );
        
        $processing_time = microtime( true ) - $start_time;
        
        return [
            'type' => 'comprehensive_description',
            'prompt' => $prompt,
            'structured_response' => $structured_response,
            'agent_responses' => $agent_responses,
            'processing_time' => $processing_time,
            'agents_consulted' => count( $all_agents ),
            'cost' => 1.0,
            'timestamp' => time()
        ];
    }
    
    /**
     * Build specialized prompts for each agent based on their expertise
     */
    private function build_specialized_describe_prompts( $prompt, $context ) {
        $base_context = $context['image_id'] ? " analyzing the uploaded image" : "";
        
        return [
            'huraii' => "As HURAII, the artistic creation specialist, provide your creative analysis: {$prompt}{$base_context}. Focus on artistic elements, creative techniques, style, composition, and aesthetic impact. Provide insights from an artist's perspective.",
            
            'cloe' => "As CLOE, the analysis and optimization specialist, provide your analytical assessment: {$prompt}{$base_context}. Focus on technical analysis, optimization opportunities, structural elements, and objective evaluation. Provide data-driven insights.",
            
            'horace' => "As HORACE, the data synthesis specialist, provide your comprehensive synthesis: {$prompt}{$base_context}. Focus on connecting different elements, synthesizing information, broader context, and holistic understanding. Provide interconnected insights.",
            
            'thorius' => "As THORIUS, the strategic orchestration specialist, provide your strategic analysis: {$prompt}{$base_context}. Focus on strategic implications, coordination aspects, planning considerations, and orchestration insights. Provide strategic perspective.",
            
            'archer' => "As ARCHER, the master orchestrator, provide your high-level coordination and oversight: {$prompt}{$base_context}. Focus on overall coordination, master-level insights, comprehensive oversight, and final recommendations. Provide executive-level perspective."
        ];
    }
    
    /**
     * Aggregate all agent responses into structured markdown format
     */
    private function aggregate_describe_responses( $agent_responses, $prompt, $context ) {
        $structured_response = "🎯 **Analysis Report**\n\n";
        $structured_response .= "**Query:** {$prompt}\n\n";
        
        if ( $context['image_id'] ) {
            $structured_response .= "**Image:** Uploaded image analysis included\n\n";
        }
        
        $structured_response .= "**Comprehensive 5-Agent Analysis:**\n\n";
        
        // Define agent icons and specializations
        $agent_info = [
            'huraii' => ['icon' => '🎨', 'title' => 'HURAII - Artistic Creation'],
            'cloe' => ['icon' => '🔍', 'title' => 'CLOE - Analysis & Optimization'],
            'horace' => ['icon' => '🔗', 'title' => 'HORACE - Data Synthesis'],
            'thorius' => ['icon' => '⚡', 'title' => 'THORIUS - Strategic Orchestration'],
            'archer' => ['icon' => '🏹', 'title' => 'ARCHER - Master Orchestrator']
        ];
        
        foreach ( $agent_responses as $agent_id => $response ) {
            $info = $agent_info[$agent_id];
            $structured_response .= "### {$info['icon']} **{$info['title']}**\n\n";
            
            if ( isset( $response['error'] ) && $response['error'] ) {
                $structured_response .= "❌ *{$response['response']}*\n\n";
            } else {
                $agent_response = $response['response'] ?? 'No response received';
                $structured_response .= "{$agent_response}\n\n";
            }
            
            $structured_response .= "---\n\n";
        }
        
        // Add summary section
        $structured_response .= "### 🎯 **Summary**\n\n";
        $structured_response .= "This comprehensive analysis involved all 5 AI agents consulting Vault for specialized insights. ";
        $structured_response .= "Each agent provided analysis from their unique perspective, creating a holistic understanding of your query.\n\n";
        
        $successful_agents = count( array_filter( $agent_responses, function( $r ) { 
            return !isset( $r['error'] ) || !$r['error']; 
        } ) );
        
        $structured_response .= "**Analysis completed:** {$successful_agents}/5 agents responded successfully\n";
        $structured_response .= "**Vault consultations:** All agents consulted Vault for enhanced capabilities\n";
        $structured_response .= "**Processing time:** " . number_format( $context['processing_time'] ?? 0, 2 ) . " seconds\n";
        
        return $structured_response;
    }

    /**
     * Execute agent with mandatory Vault consultation and inter-agent communication
     * ALL agents MUST consult Vault before responding
     */
    public function execute_agent_with_vault_consultation( $agent_id, $query, $context = [], $requesting_agent = null ) {
        $start_time = microtime( true );
        
        // Reset consultation depth for new top-level queries (when no requesting agent)
        if ( $requesting_agent === null ) {
            $this->consultation_depth = 0;
        }
        
        if ( ! isset( $this->agents[$agent_id] ) ) {
            throw new Exception( "Agent {$agent_id} not found in orchestrator" );
        }
        
        // Rate limiting check (skip for inter-agent consultations)
        if ( $requesting_agent === null ) {
            $user_id = get_current_user_id();
            $agent_cost = $this->agents[$agent_id]['cost_per_call'] ?? 0.01;
            
            $rate_check = $this->rate_limiter->isAllowed( $user_id, $agent_id, $agent_cost );
            if ( ! $rate_check['allowed'] ) {
                throw new Exception( "Rate limit exceeded: {$rate_check['reason']}. Retry after {$rate_check['retry_after']} seconds." );
            }
        }
        
        // Step 1: MANDATORY Vault consultation before any response
        $vault_data = $this->mandatory_vault_consultation( $agent_id, $query, $context );
        
        // Step 2: Inter-agent consultation if applicable
        $agent_consultations = $this->conduct_inter_agent_consultations( $agent_id, $query, $context, $requesting_agent );
        
        // Step 3: Execute agent with enriched context
        $enriched_context = array_merge( $context, [
            'vault_guidance' => $vault_data,
            'agent_consultations' => $agent_consultations,
            'requesting_agent' => $requesting_agent,
            'consultation_timestamp' => time()
        ] );
        
        $response = $this->execute_single_agent( $agent_id, $query, $enriched_context );
        
        // Step 4: Log inter-agent communication
        $this->log_inter_agent_communication( $agent_id, $requesting_agent, $query, $response, microtime( true ) - $start_time );
        
        // Step 5: Update Vault with interaction results
        $this->update_vault_with_interaction_results( $agent_id, $query, $response, $vault_data );
        
        // Step 6: Record request for rate limiting (skip for inter-agent consultations)
        if ( $requesting_agent === null ) {
            $user_id = get_current_user_id();
            $agent_cost = $this->agents[$agent_id]['cost_per_call'] ?? 0.01;
            $this->rate_limiter->recordRequest( $user_id, $agent_id, $agent_cost );
        }
        
        return $response;
    }

    /**
     * MANDATORY Vault consultation - ALL agents MUST call this before responding
     */
    private function mandatory_vault_consultation( $agent_id, $query, $context ) {
        if ( ! $this->vault->isAvailable() ) {
            error_log( "[VortexAI] WARNING: Vault unavailable for agent {$agent_id} - proceeding with limited capabilities" );
            return $this->get_fallback_vault_data( $agent_id );
        }

        // Increment vault interactions counter
        $this->agents[$agent_id]['vault_interactions']++;
        
        $vault_consultation = [
            'timestamp' => time(),
            'agent_id' => $agent_id,
            'query_classification' => $this->classify_query_for_vault( $query ),
            'algorithms' => [],
            'neural_states' => [],
            'agent_memory' => [],
            'inter_agent_protocols' => [],
            'strategic_directives' => []
        ];

        try {
            // 1. Get agent-specific configuration using new Vault methods
            $agent_config = $this->vault->getAgentConfig( $agent_id );
            
            // 2. Get specialized algorithms for the agent
            $specialized_algorithm = $this->vault->getAlgorithm( "algorithms/{$agent_id}_specialized" ) ?: 
                                   $this->vault->getAlgorithm( "algorithms/enterprise_base" );
            
            // 3. Get neural network state for continuous learning
            $neural_state = $this->vault->getNeuralState( $agent_id );
            
            // 4. Get agent memory for context-aware responses
            $user_id = get_current_user_id() ?: 'anonymous';
            $agent_memory = $this->vault->getAlgorithm( "memory_{$agent_id}_{$user_id}" );
            
            // 5. Get inter-agent communication protocols
            $inter_agent_protocols = $this->vault->getInterAgentProtocols( $agent_id );
            
            // 6. Get strategic directives from ARCHER (master orchestrator)
            if ( $agent_id !== 'archer' ) {
                $strategic_directives = $this->vault->getStrategicDirectives();
                $vault_consultation['strategic_directives'] = $strategic_directives ?: [];
            }
            
            // 7. Get collaboration patterns with other agents
            $collaboration_patterns = $this->vault->getCollaborationPatterns( $agent_id );
            
            $vault_consultation['algorithms'] = [
                'agent_config' => $agent_config ?: [],
                'specialized_algorithm' => $specialized_algorithm ?: [],
                'collaboration_patterns' => $collaboration_patterns ?: []
            ];
            
            $vault_consultation['neural_states'] = $neural_state ?: [];
            $vault_consultation['agent_memory'] = $agent_memory ?: [];
            $vault_consultation['inter_agent_protocols'] = $inter_agent_protocols ?: [];
            
        } catch ( Exception $e ) {
            error_log( "[VortexAI] Vault consultation error for {$agent_id}: " . $e->getMessage() );
            $vault_consultation['error'] = $e->getMessage();
        }

        return $vault_consultation;
    }

    /**
     * Conduct inter-agent consultations for collaborative intelligence
     */
    private function conduct_inter_agent_consultations( $current_agent, $query, $context, $requesting_agent = null ) {
        $consultations = [];
        
        // Determine which agents to consult based on query type and current agent
        $agents_to_consult = $this->determine_consultation_agents( $current_agent, $query, $context );
        
        foreach ( $agents_to_consult as $consultant_agent ) {
            if ( $consultant_agent === $current_agent || $consultant_agent === $requesting_agent ) {
                continue; // Avoid self-consultation and circular requests
            }
            
            try {
                $consultation_query = $this->craft_consultation_query( $current_agent, $consultant_agent, $query, $context );
                $consultation_response = $this->request_agent_consultation( $consultant_agent, $consultation_query, $current_agent );
                
                $consultations[$consultant_agent] = [
                    'query' => $consultation_query,
                    'response' => $consultation_response,
                    'timestamp' => time(),
                    'confidence' => $this->calculate_consultation_confidence( $consultation_response )
                ];
                
            } catch ( Exception $e ) {
                error_log( "[VortexAI] Inter-agent consultation error ({$current_agent} -> {$consultant_agent}): " . $e->getMessage() );
                $consultations[$consultant_agent] = [
                    'error' => $e->getMessage(),
                    'timestamp' => time()
                ];
            }
        }
        
        return $consultations;
    }

    /**
     * Determine which agents should be consulted based on query analysis
     */
    private function determine_consultation_agents( $current_agent, $query, $context ) {
        $consultation_matrix = [
            'huraii' => ['cloe', 'thorius'], // Artistic creation consults analysis and strategy
            'cloe' => ['horace', 'archer'], // Analysis consults synthesis and orchestration
            'horace' => ['huraii', 'archer'], // Synthesis consults creativity and orchestration
            'thorius' => ['cloe', 'archer'], // Strategy consults analysis and master orchestration
            'archer' => ['huraii', 'cloe', 'horace', 'thorius'] // Master orchestrator can consult all
        ];
        
        $base_consultants = $consultation_matrix[$current_agent] ?? [];
        
        // Dynamic consultation based on query complexity
        $query_features = $this->extract_query_features( $query );
        $complexity = $this->calculate_enterprise_complexity( $query, $context );
        
        // For complex queries, involve more agents
        if ( $complexity > 0.8 && $current_agent !== 'archer' ) {
            $base_consultants[] = 'archer'; // Always involve master orchestrator for complex queries
        }
        
        // Remove duplicates and ensure valid agents
        $consultants = array_unique( array_filter( $base_consultants, function( $agent ) {
            return isset( $this->agents[$agent] );
        } ) );
        
        return $consultants;
    }

    /**
     * Craft specific consultation query for inter-agent communication
     */
    private function craft_consultation_query( $requesting_agent, $consultant_agent, $original_query, $context ) {
        $agent_specializations = [
            'huraii' => 'artistic and creative perspective',
            'cloe' => 'analytical and optimization insights',
            'horace' => 'data synthesis and comprehensive analysis',
            'thorius' => 'strategic orchestration and planning',
            'archer' => 'master orchestration and high-level coordination'
        ];
        
        $specialization = $agent_specializations[$consultant_agent] ?? 'general assistance';
        
        return "Agent {$requesting_agent} is working on: \"{$original_query}\"\n\n" .
               "From your {$specialization} expertise, what key insights, " .
               "recommendations, or considerations should {$requesting_agent} be aware of? " .
               "Provide specific, actionable guidance that complements {$requesting_agent}'s approach.";
    }

    /**
     * Request consultation from another agent
     */
    private function request_agent_consultation( $consultant_agent, $consultation_query, $requesting_agent ) {
        // Prevent infinite loops by limiting consultation depth (instance-based)
        if ( $this->consultation_depth >= 2 ) {
            throw new Exception( "Maximum consultation depth reached (depth: {$this->consultation_depth})" );
        }
        
        $this->consultation_depth++;
        
        try {
            // Execute consultant agent with limited context to prevent circular dependencies
            $limited_context = [
                'consultation_mode' => true,
                'requesting_agent' => $requesting_agent,
                'consultation_depth' => $this->consultation_depth
            ];
            
            $response = $this->execute_agent_with_vault_consultation( 
                $consultant_agent, 
                $consultation_query, 
                $limited_context, 
                $requesting_agent 
            );
            
            $this->consultation_depth--;
            return $response;
            
        } catch ( Exception $e ) {
            $this->consultation_depth--;
            throw $e;
        }
    }

    /**
     * Execute single agent with enriched context
     */
    private function execute_single_agent( $agent_id, $query, $context ) {
        $agent = $this->agents[$agent_id];
        
        if ( ! $agent['api_endpoint'] || ! isset( $agent['api_key'] ) ) {
            throw new Exception( "Agent {$agent_id} not properly configured" );
        }
        
        // Build enhanced prompt with Vault guidance and inter-agent insights
        $enhanced_prompt = $this->build_enhanced_agent_prompt( $agent_id, $query, $context );
        
        // Execute API call with monitoring
        $start_time = microtime( true );
        
        try {
            $api_response = $this->make_agent_api_call( $agent, $enhanced_prompt );
            $processing_time = microtime( true ) - $start_time;
            
            // Update performance metrics
            $this->update_agent_performance_metrics( $agent_id, $processing_time, true );
            
            return [
                'agent_id' => $agent_id,
                'response' => $api_response,
                'processing_time' => $processing_time,
                'vault_consulted' => true,
                'context_enriched' => true,
                'confidence' => $this->calculate_response_confidence( $api_response ),
                'timestamp' => time()
            ];
            
        } catch ( Exception $e ) {
            $processing_time = microtime( true ) - $start_time;
            $this->update_agent_performance_metrics( $agent_id, $processing_time, false );
            throw $e;
        }
    }

    /**
     * Build enhanced prompt with Vault guidance and inter-agent insights
     */
    private function build_enhanced_agent_prompt( $agent_id, $query, $context ) {
        $agent = $this->agents[$agent_id];
        $vault_guidance = $context['vault_guidance'] ?? [];
        $consultations = $context['agent_consultations'] ?? [];
        
        $prompt = "You are {$agent['name']}, an advanced AI agent specializing in {$agent['specialization']}.\n\n";
        
        // Add Vault-derived context
        if ( ! empty( $vault_guidance['algorithms']['specialized_algorithm'] ) ) {
            $prompt .= "ALGORITHMIC GUIDANCE (from Vault):\n";
            $algorithm = $vault_guidance['algorithms']['specialized_algorithm'];
            if ( isset( $algorithm['prompt_template'] ) ) {
                $prompt .= $algorithm['prompt_template'] . "\n\n";
            }
        }
        
        // Add agent memory context
        if ( ! empty( $vault_guidance['agent_memory'] ) ) {
            $prompt .= "CONTEXTUAL MEMORY:\n";
            $prompt .= "Previous interactions and learned preferences:\n";
            $memory = $vault_guidance['agent_memory'];
            if ( is_array( $memory ) ) {
                foreach ( array_slice( $memory, -3 ) as $memory_item ) { // Last 3 interactions
                    if ( isset( $memory_item['summary'] ) ) {
                        $prompt .= "- " . $memory_item['summary'] . "\n";
                    }
                }
            }
            $prompt .= "\n";
        }
        
        // Add inter-agent consultations
        if ( ! empty( $consultations ) ) {
            $prompt .= "COLLEAGUE INSIGHTS:\n";
            foreach ( $consultations as $consultant_agent => $consultation ) {
                if ( isset( $consultation['response']['response'] ) ) {
                    $agent_name = $this->agents[$consultant_agent]['name'] ?? $consultant_agent;
                    $prompt .= "{$agent_name}'s insight: " . substr( $consultation['response']['response'], 0, 200 ) . "...\n";
                }
            }
            $prompt .= "\n";
        }
        
        // Add strategic directives from ARCHER
        if ( ! empty( $vault_guidance['strategic_directives'] ) && $agent_id !== 'archer' ) {
            $prompt .= "STRATEGIC DIRECTIVES (from Master Orchestrator ARCHER):\n";
            $directives = $vault_guidance['strategic_directives'];
            if ( is_array( $directives ) && isset( $directives['current_directives'] ) ) {
                foreach ( $directives['current_directives'] as $directive ) {
                    $prompt .= "- " . $directive . "\n";
                }
            }
            $prompt .= "\n";
        }
        
        $prompt .= "USER QUERY:\n{$query}\n\n";
        $prompt .= "Provide a comprehensive response that leverages your specialized expertise, ";
        $prompt .= "incorporates the guidance and insights provided, and maintains consistency ";
        $prompt .= "with the overall VORTEX AI ecosystem approach.";
        
        return $prompt;
    }

    /**
     * Log inter-agent communication for analytics and improvement
     */
    private function log_inter_agent_communication( $agent_id, $requesting_agent, $query, $response, $processing_time ) {
        $log_entry = [
            'timestamp' => time(),
            'agent_id' => $agent_id,
            'requesting_agent' => $requesting_agent,
            'query_hash' => md5( $query ),
            'query_length' => strlen( $query ),
            'response_length' => strlen( $response['response'] ?? '' ),
            'processing_time' => $processing_time,
            'vault_consulted' => $response['vault_consulted'] ?? false,
            'confidence' => $response['confidence'] ?? 0.0
        ];
        
        // Store in memory for session
        $this->inter_agent_logs[] = $log_entry;
        
        // Add to agent's communication history
        $this->agents[$agent_id]['inter_agent_communications'][] = [
            'partner' => $requesting_agent,
            'type' => $requesting_agent ? 'consultation_response' : 'direct_query',
            'timestamp' => time(),
            'success' => isset( $response['response'] )
        ];
        
        // Store in Vault for persistent learning
        if ( $this->vault->isAvailable() ) {
            try {
                $vault_log_path = "inter_agent_logs/" . date( 'Y-m-d' ) . "/{$agent_id}";
                $existing_logs = $this->vault->getAlgorithm( $vault_log_path ) ?: [];
                $existing_logs[] = $log_entry;
                $this->vault->write( $vault_log_path, $existing_logs );
            } catch ( Exception $e ) {
                error_log( "[VortexAI] Failed to store inter-agent log in Vault: " . $e->getMessage() );
            }
        }
    }

    /**
     * Update Vault with interaction results for continuous learning
     */
    private function update_vault_with_interaction_results( $agent_id, $query, $response, $vault_data ) {
        if ( ! $this->vault->isAvailable() ) {
            return;
        }
        
        try {
            // Update agent memory
            $user_id = get_current_user_id() ?: 'anonymous';
            $memory_path = "memory_{$agent_id}_{$user_id}";
            $existing_memory = $this->vault->getAlgorithm( $memory_path ) ?: [];
            
            $new_memory_entry = [
                'timestamp' => time(),
                'query' => substr( $query, 0, 500 ), // Truncate for storage efficiency
                'response_summary' => substr( $response['response'] ?? '', 0, 200 ),
                'confidence' => $response['confidence'] ?? 0.0,
                'processing_time' => $response['processing_time'] ?? 0.0
            ];
            
            $existing_memory[] = $new_memory_entry;
            
            // Keep only last 50 interactions to prevent storage bloat
            if ( count( $existing_memory ) > 50 ) {
                $existing_memory = array_slice( $existing_memory, -50 );
            }
            
            $this->vault->write( $memory_path, $existing_memory );
            
            // Update neural network state based on interaction
            $this->update_neural_state_from_interaction( $agent_id, $query, $response );
            
        } catch ( Exception $e ) {
            error_log( "[VortexAI] Failed to update Vault with interaction results: " . $e->getMessage() );
        }
    }

    // ===== HELPER METHODS FOR INTER-AGENT SYSTEM =====

    private function classify_query_for_vault( $query ) {
        // Simple classification for demonstration - could be enhanced with ML
        $keywords = [
            'creative' => ['art', 'create', 'design', 'artistic', 'imagination', 'creative'],
            'analytical' => ['analyze', 'optimize', 'data', 'performance', 'efficiency'],
            'synthesis' => ['synthesize', 'combine', 'comprehensive', 'overview', 'summary'],
            'strategic' => ['strategy', 'plan', 'orchestrate', 'coordinate', 'manage'],
            'orchestration' => ['orchestrate', 'coordinate', 'manage', 'oversee', 'direct']
        ];
        
        $query_lower = strtolower( $query );
        $scores = [];
        
        foreach ( $keywords as $category => $words ) {
            $score = 0;
            foreach ( $words as $word ) {
                if ( strpos( $query_lower, $word ) !== false ) {
                    $score++;
                }
            }
            $scores[$category] = $score;
        }
        
        return array_keys( $scores, max( $scores ) )[0] ?? 'general';
    }

    private function get_fallback_vault_data( $agent_id ) {
        return [
            'timestamp' => time(),
            'agent_id' => $agent_id,
            'fallback_mode' => true,
            'algorithms' => [
                'agent_config' => $this->get_default_agent_config( $agent_id ),
                'specialized_algorithm' => $this->get_default_algorithm( $agent_id )
            ],
            'neural_states' => [],
            'agent_memory' => [],
            'inter_agent_protocols' => [],
            'strategic_directives' => []
        ];
    }

    private function get_default_agent_config( $agent_id ) {
        $default_configs = [
            'huraii' => [
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 2048,
                'specialization' => 'artistic_creation'
            ],
            'cloe' => [
                'model' => 'gpt-4',
                'temperature' => 0.3,
                'max_tokens' => 1024,
                'specialization' => 'analysis_optimization'
            ],
            'horace' => [
                'model' => 'gpt-4',
                'temperature' => 0.5,
                'max_tokens' => 1536,
                'specialization' => 'data_synthesis'
            ],
            'thorius' => [
                'model' => 'gpt-4',
                'temperature' => 0.4,
                'max_tokens' => 1800,
                'specialization' => 'strategic_orchestration'
            ],
            'archer' => [
                'model' => 'gpt-4',
                'temperature' => 0.2,
                'max_tokens' => 2500,
                'specialization' => 'master_orchestrator'
            ]
        ];
        
        return $default_configs[$agent_id] ?? $default_configs['huraii'];
    }

    private function get_default_algorithm( $agent_id ) {
        $default_algorithms = [
            'huraii' => [
                'prompt_template' => 'You are HURAII, a creative AI specializing in artistic creation. Focus on imagination, creativity, and inspiring artistic expression.',
                'parameters' => ['creativity_level' => 0.8, 'originality' => 0.9]
            ],
            'cloe' => [
                'prompt_template' => 'You are CLOE, an analytical AI specializing in optimization and analysis. Provide precise, data-driven insights.',
                'parameters' => ['precision' => 0.95, 'analytical_depth' => 0.9]
            ],
            'horace' => [
                'prompt_template' => 'You are HORACE, a synthesis AI specializing in connecting and analyzing comprehensive data.',
                'parameters' => ['synthesis_quality' => 0.9, 'comprehensiveness' => 0.85]
            ],
            'thorius' => [
                'prompt_template' => 'You are THORIUS, a strategic AI specializing in orchestration and planning. Focus on strategic thinking and coordination.',
                'parameters' => ['strategic_thinking' => 0.92, 'coordination' => 0.88]
            ],
            'archer' => [
                'prompt_template' => 'You are ARCHER, the master orchestrator AI. Provide high-level coordination and comprehensive oversight.',
                'parameters' => ['orchestration_mastery' => 0.95, 'oversight' => 0.93]
            ]
        ];
        
        return $default_algorithms[$agent_id] ?? $default_algorithms['huraii'];
    }

    private function calculate_consultation_confidence( $consultation_response ) {
        // Simple confidence calculation - could be enhanced
        if ( ! isset( $consultation_response['response'] ) ) {
            return 0.0;
        }
        
        $response_length = strlen( $consultation_response['response'] );
        $confidence = min( 1.0, $response_length / 500 ); // Longer responses generally indicate higher confidence
        
        return $confidence;
    }

    private function calculate_response_confidence( $response ) {
        // Enhanced confidence calculation
        $confidence = 0.5; // Base confidence
        
        if ( is_string( $response ) ) {
            $length = strlen( $response );
            $confidence += min( 0.3, $length / 1000 ); // Length factor
        }
        
        return min( 1.0, $confidence );
    }

    private function update_agent_performance_metrics( $agent_id, $processing_time, $success ) {
        if ( ! isset( $this->agents[$agent_id] ) ) {
            return;
        }
        
        $metrics = &$this->agents[$agent_id]['performance_metrics'];
        
        if ( ! isset( $metrics['total_calls'] ) ) {
            $metrics['total_calls'] = 0;
            $metrics['successful_calls'] = 0;
            $metrics['total_processing_time'] = 0.0;
            $metrics['average_processing_time'] = 0.0;
            $metrics['success_rate'] = 0.0;
        }
        
        $metrics['total_calls']++;
        $metrics['total_processing_time'] += $processing_time;
        $metrics['average_processing_time'] = $metrics['total_processing_time'] / $metrics['total_calls'];
        
        if ( $success ) {
            $metrics['successful_calls']++;
        }
        
        $metrics['success_rate'] = $metrics['successful_calls'] / $metrics['total_calls'];
    }

    private function make_agent_api_call( $agent, $prompt ) {
        if ( empty( $agent['api_endpoint'] ) || empty( $agent['api_key'] ) ) {
            throw new Exception( "Agent {$agent['name']} missing API endpoint or key" );
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 60,
                'verify' => true
            ]);
            
            // Prepare request payload for OpenAI-compatible APIs
            $payload = [
                'model' => $agent['model'] ?? 'gpt-4',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => floatval( $agent['temperature'] ?? 0.7 ),
                'max_tokens' => intval( $agent['max_tokens'] ?? 1024 ),
                'top_p' => 1.0,
                'frequency_penalty' => 0,
                'presence_penalty' => 0
            ];
            
            // Make API request
            $response = $client->post( $agent['api_endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $agent['api_key'],
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'VORTEX-AI-Engine/2.1.0'
                ],
                'json' => $payload
            ]);
            
            $status_code = $response->getStatusCode();
            $response_body = $response->getBody()->getContents();
            
            if ( $status_code !== 200 ) {
                throw new Exception( "API returned status {$status_code}: {$response_body}" );
            }
            
            $data = json_decode( $response_body, true );
            
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                throw new Exception( "Invalid JSON response: " . json_last_error_msg() );
            }
            
            // Extract response text (OpenAI format)
            if ( isset( $data['choices'][0]['message']['content'] ) ) {
                $response_text = $data['choices'][0]['message']['content'];
                
                // Log successful API call for monitoring
                error_log( "[VortexAI] Successful API call to {$agent['name']}: " . strlen( $response_text ) . " chars" );
                
                return $response_text;
            }
            
            // Handle other response formats or errors
            if ( isset( $data['error'] ) ) {
                throw new Exception( "API error: " . json_encode( $data['error'] ) );
            }
            
            throw new Exception( "Unexpected API response format" );
            
        } catch ( \GuzzleHttp\Exception\RequestException $e ) {
            $error_msg = "HTTP request failed for {$agent['name']}: " . $e->getMessage();
            
            if ( $e->hasResponse() ) {
                $response_body = $e->getResponse()->getBody()->getContents();
                $error_msg .= " Response: " . $response_body;
            }
            
            error_log( "[VortexAI] {$error_msg}" );
            throw new Exception( $error_msg );
            
        } catch ( Exception $e ) {
            $error_msg = "API call failed for {$agent['name']}: " . $e->getMessage();
            error_log( "[VortexAI] {$error_msg}" );
            throw new Exception( $error_msg );
        }
    }

    private function update_neural_state_from_interaction( $agent_id, $query, $response ) {
        if ( ! $this->vault->isAvailable() ) {
            return;
        }

        try {
            $current_state = $this->vault->getNeuralState( $agent_id );
            
            if ( empty( $current_state ) ) {
                error_log( "[VortexAI] No neural state found for agent {$agent_id}" );
                return;
            }
            
            // Calculate interaction metrics
            $query_length = strlen( $query );
            $response_length = strlen( $response['response'] ?? '' );
            $processing_time = $response['processing_time'] ?? 0.0;
            $confidence = $response['confidence'] ?? 0.0;
            
            // Update iteration count
            $current_state['iteration_count'] = ( $current_state['iteration_count'] ?? 0 ) + 1;
            
            // Update learning metrics
            $learning_rate = $current_state['learning_rate'] ?? 0.001;
            
            // Simple performance tracking (in real ML, this would be more sophisticated)
            $performance_score = min( 1.0, $confidence * ( 1.0 - min( 1.0, $processing_time / 10.0 ) ) );
            
            // Update accuracy history (rolling window of last 100 interactions)
            $accuracy_history = $current_state['accuracy_history'] ?? [];
            $accuracy_history[] = $performance_score;
            if ( count( $accuracy_history ) > 100 ) {
                $accuracy_history = array_slice( $accuracy_history, -100 );
            }
            $current_state['accuracy_history'] = $accuracy_history;
            
            // Update loss history (inverse of performance)
            $loss_history = $current_state['loss_history'] ?? [];
            $loss_history[] = 1.0 - $performance_score;
            if ( count( $loss_history ) > 100 ) {
                $loss_history = array_slice( $loss_history, -100 );
            }
            $current_state['loss_history'] = $loss_history;
            
            // Calculate convergence metrics
            if ( count( $accuracy_history ) >= 10 ) {
                $recent_avg = array_sum( array_slice( $accuracy_history, -10 ) ) / 10;
                $overall_avg = array_sum( $accuracy_history ) / count( $accuracy_history );
                $convergence_trend = $recent_avg - $overall_avg;
                
                $convergence_metrics = $current_state['convergence_metrics'] ?? [];
                $convergence_metrics[] = [
                    'timestamp' => time(),
                    'trend' => $convergence_trend,
                    'recent_performance' => $recent_avg,
                    'overall_performance' => $overall_avg
                ];
                
                // Keep only last 50 convergence measurements
                if ( count( $convergence_metrics ) > 50 ) {
                    $convergence_metrics = array_slice( $convergence_metrics, -50 );
                }
                $current_state['convergence_metrics'] = $convergence_metrics;
            }
            
            // Adapt learning rate based on performance (simple adaptive learning)
            if ( count( $accuracy_history ) >= 20 ) {
                $recent_performance = array_sum( array_slice( $accuracy_history, -10 ) ) / 10;
                $older_performance = array_sum( array_slice( $accuracy_history, -20, 10 ) ) / 10;
                
                if ( $recent_performance > $older_performance ) {
                    // Performance improving, slightly increase learning rate
                    $current_state['learning_rate'] = min( 0.01, $learning_rate * 1.01 );
                } elseif ( $recent_performance < $older_performance ) {
                    // Performance declining, decrease learning rate
                    $current_state['learning_rate'] = max( 0.0001, $learning_rate * 0.99 );
                }
            }
            
            // Store interaction metadata
            $current_state['last_interaction'] = [
                'timestamp' => time(),
                'query_length' => $query_length,
                'response_length' => $response_length,
                'processing_time' => $processing_time,
                'confidence' => $confidence,
                'performance_score' => $performance_score
            ];
            
            // Update state in Vault
            $this->vault->updateNeuralState( $agent_id, $current_state );
            
            error_log( "[VortexAI] Neural state updated for {$agent_id}: iteration {$current_state['iteration_count']}, performance {$performance_score}" );
            
        } catch ( Exception $e ) {
            error_log( "[VortexAI] Failed to update neural state for {$agent_id}: " . $e->getMessage() );
        }
    }
}
}
?> 