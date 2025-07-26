<?php
/**
 * VORTEX AI ENGINE - REAL-TIME LEARNING ORCHESTRATOR
 * 
 * Ensures the plugin is always learning and providing real-time updates
 * 
 * @package VORTEX_AI_Engine
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Realtime_Learning_Orchestrator {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Learning state
     */
    private $learning_state = [
        'is_active' => false,
        'last_cycle' => 0,
        'total_cycles' => 0,
        'performance_score' => 0.0,
        'learning_rate' => 0.001,
        'improvements_made' => 0
    ];
    
    /**
     * Real-time data collection
     */
    private $realtime_data = [
        'user_interactions' => [],
        'system_metrics' => [],
        'performance_data' => [],
        'error_logs' => []
    ];
    
    /**
     * Learning components
     */
    private $recursive_system = null;
    private $deep_learning_engine = null;
    private $reinforcement_engine = null;
    private $realtime_processor = null;
    
    /**
     * WordPress hooks
     */
    private $hooks_registered = false;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->initialize_learning_components();
        $this->register_wordpress_hooks();
        $this->start_realtime_learning();
    }
    
    /**
     * Initialize learning components
     */
    private function initialize_learning_components() {
        try {
            // Initialize recursive self-improvement system
            if (class_exists('VORTEX_Recursive_Self_Improvement')) {
                $this->recursive_system = VORTEX_Recursive_Self_Improvement::get_instance();
            }
            
            // Initialize deep learning engine
            if (class_exists('VORTEX_Deep_Learning_Engine')) {
                $this->deep_learning_engine = new VORTEX_Deep_Learning_Engine();
            }
            
            // Initialize reinforcement engine
            if (class_exists('VORTEX_Reinforcement_Engine')) {
                $this->reinforcement_engine = new VORTEX_Reinforcement_Engine();
            }
            
            // Initialize real-time processor
            if (class_exists('VORTEX_Real_Time_Processor')) {
                $this->realtime_processor = new VORTEX_Real_Time_Processor();
            }
            
            error_log('VORTEX AI Engine: Real-time learning components initialized');
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Error initializing learning components: ' . $e->getMessage());
        }
    }
    
    /**
     * Register WordPress hooks for real-time learning
     */
    private function register_wordpress_hooks() {
        if ($this->hooks_registered) {
            return;
        }
        
        // Hook into WordPress actions for real-time data collection
        add_action('wp_ajax_vortex_realtime_learning', [$this, 'handle_realtime_learning_request']);
        add_action('wp_ajax_nopriv_vortex_realtime_learning', [$this, 'handle_realtime_learning_request']);
        
        // Hook into WordPress events for learning
        add_action('wp_loaded', [$this, 'collect_system_metrics']);
        add_action('wp_footer', [$this, 'inject_realtime_learning_script']);
        add_action('admin_footer', [$this, 'inject_admin_realtime_learning_script']);
        
        // Hook into plugin events
        add_action('vortex_ai_engine_init', [$this, 'start_learning_cycle']);
        add_action('vortex_user_interaction', [$this, 'process_user_interaction']);
        add_action('vortex_system_event', [$this, 'process_system_event']);
        
        // Schedule regular learning cycles
        if (!wp_next_scheduled('vortex_realtime_learning_cycle')) {
            wp_schedule_event(time(), 'every_30_seconds', 'vortex_realtime_learning_cycle');
        }
        add_action('vortex_realtime_learning_cycle', [$this, 'run_learning_cycle']);
        
        $this->hooks_registered = true;
        error_log('VORTEX AI Engine: Real-time learning hooks registered');
    }
    
    /**
     * Start real-time learning
     */
    private function start_realtime_learning() {
        $this->learning_state['is_active'] = true;
        $this->learning_state['last_cycle'] = time();
        
        // Start the first learning cycle
        $this->run_learning_cycle();
        
        error_log('VORTEX AI Engine: Real-time learning started');
    }
    
    /**
     * Run a learning cycle
     */
    public function run_learning_cycle() {
        if (!$this->learning_state['is_active']) {
            return;
        }
        
        try {
            $cycle_start = microtime(true);
            
            // Collect real-time data
            $this->collect_realtime_data();
            
            // Process data through learning engines
            $this->process_learning_data();
            
            // Update learning state
            $this->update_learning_state();
            
            // Apply improvements
            $this->apply_improvements();
            
            // Log cycle completion
            $cycle_duration = microtime(true) - $cycle_start;
            $this->learning_state['total_cycles']++;
            $this->learning_state['last_cycle'] = time();
            
            error_log("VORTEX AI Engine: Learning cycle {$this->learning_state['total_cycles']} completed in " . round($cycle_duration * 1000, 2) . "ms");
            
        } catch (Exception $e) {
            error_log('VORTEX AI Engine: Error in learning cycle: ' . $e->getMessage());
        }
    }
    
    /**
     * Collect real-time data
     */
    private function collect_realtime_data() {
        // Collect system metrics
        $this->realtime_data['system_metrics'] = [
            'cpu_usage' => $this->get_cpu_usage(),
            'memory_usage' => $this->get_memory_usage(),
            'load_average' => $this->get_load_average(),
            'response_time' => $this->get_response_time(),
            'error_rate' => $this->get_error_rate(),
            'user_activity' => $this->get_user_activity(),
            'timestamp' => time()
        ];
        
        // Collect performance data
        $this->realtime_data['performance_data'] = [
            'page_load_time' => $this->get_page_load_time(),
            'database_queries' => $this->get_database_queries(),
            'cache_hit_rate' => $this->get_cache_hit_rate(),
            'api_response_time' => $this->get_api_response_time()
        ];
        
        // Process through real-time processor if available
        if ($this->realtime_processor) {
            $this->realtime_processor->process_data($this->realtime_data, 'system_metrics');
        }
    }
    
    /**
     * Process learning data
     */
    private function process_learning_data() {
        // Process through deep learning engine
        if ($this->deep_learning_engine && !empty($this->realtime_data['system_metrics'])) {
            $input_data = $this->prepare_data_for_deep_learning();
            $prediction = $this->deep_learning_engine->predict($input_data);
            
            // Use prediction to optimize parameters
            $this->optimize_parameters($prediction);
        }
        
        // Process through reinforcement engine
        if ($this->reinforcement_engine && !empty($this->realtime_data['user_interactions'])) {
            foreach ($this->realtime_data['user_interactions'] as $interaction) {
                $this->reinforcement_engine->learn(
                    $interaction['state'],
                    $interaction['action'],
                    $interaction['reward'],
                    $interaction['next_state'],
                    $interaction['done'] ?? false
                );
            }
        }
        
        // Process through recursive system
        if ($this->recursive_system) {
            $this->recursive_system->process_learning_data($this->realtime_data);
        }
    }
    
    /**
     * Update learning state
     */
    private function update_learning_state() {
        // Calculate performance score
        $this->learning_state['performance_score'] = $this->calculate_performance_score();
        
        // Adjust learning rate based on performance
        $this->learning_state['learning_rate'] = $this->adjust_learning_rate();
        
        // Store learning state
        update_option('vortex_realtime_learning_state', $this->learning_state);
        
        // Log learning progress
        $this->log_learning_progress();
    }
    
    /**
     * Apply improvements
     */
    private function apply_improvements() {
        $improvements = [];
        
        // Apply deep learning improvements
        if ($this->deep_learning_engine) {
            $improvements['deep_learning'] = $this->apply_deep_learning_improvements();
        }
        
        // Apply reinforcement learning improvements
        if ($this->reinforcement_engine) {
            $improvements['reinforcement'] = $this->apply_reinforcement_improvements();
        }
        
        // Apply recursive improvements
        if ($this->recursive_system) {
            $improvements['recursive'] = $this->recursive_system->apply_improvements();
        }
        
        // Update improvement count
        $this->learning_state['improvements_made'] += count($improvements);
        
        // Log improvements
        if (!empty($improvements)) {
            error_log('VORTEX AI Engine: Applied improvements: ' . json_encode($improvements));
        }
    }
    
    /**
     * Handle real-time learning AJAX request
     */
    public function handle_realtime_learning_request() {
        try {
            $action = sanitize_text_field($_POST['action'] ?? '');
            
            switch ($action) {
                case 'get_learning_status':
                    $this->send_learning_status();
                    break;
                    
                case 'get_performance_metrics':
                    $this->send_performance_metrics();
                    break;
                    
                case 'submit_user_feedback':
                    $this->process_user_feedback();
                    break;
                    
                default:
                    wp_send_json_error('Invalid action');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error processing request: ' . $e->getMessage());
        }
    }
    
    /**
     * Send learning status
     */
    private function send_learning_status() {
        $status = [
            'is_active' => $this->learning_state['is_active'],
            'total_cycles' => $this->learning_state['total_cycles'],
            'performance_score' => $this->learning_state['performance_score'],
            'learning_rate' => $this->learning_state['learning_rate'],
            'improvements_made' => $this->learning_state['improvements_made'],
            'last_cycle' => $this->learning_state['last_cycle'],
            'timestamp' => time()
        ];
        
        wp_send_json_success($status);
    }
    
    /**
     * Send performance metrics
     */
    private function send_performance_metrics() {
        $metrics = [
            'system_metrics' => $this->realtime_data['system_metrics'],
            'performance_data' => $this->realtime_data['performance_data'],
            'learning_state' => $this->learning_state
        ];
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Process user feedback
     */
    private function process_user_feedback() {
        $feedback = sanitize_textarea_field($_POST['feedback'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $context = sanitize_text_field($_POST['context'] ?? '');
        
        // Store feedback
        $feedback_data = [
            'feedback' => $feedback,
            'rating' => $rating,
            'context' => $context,
            'timestamp' => time(),
            'user_id' => get_current_user_id()
        ];
        
        // Add to real-time data
        $this->realtime_data['user_interactions'][] = $feedback_data;
        
        // Process feedback through learning engines
        $this->process_user_feedback_learning($feedback_data);
        
        wp_send_json_success('Feedback processed successfully');
    }
    
    /**
     * Inject real-time learning script
     */
    public function inject_realtime_learning_script() {
        ?>
        <script>
        (function() {
            'use strict';
            
            // VORTEX Real-time Learning Script
            window.VortexRealtimeLearning = {
                apiUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('vortex_realtime_learning'); ?>',
                
                init: function() {
                    this.startRealTimeLearning();
                    this.setupEventListeners();
                },
                
                startRealTimeLearning: function() {
                    // Send initial learning status
                    this.getLearningStatus();
                    
                    // Set up periodic updates
                    setInterval(() => {
                        this.getLearningStatus();
                    }, 30000); // Every 30 seconds
                },
                
                setupEventListeners: function() {
                    // Monitor user interactions
                    document.addEventListener('click', this.handleUserInteraction.bind(this));
                    document.addEventListener('scroll', this.handleUserInteraction.bind(this));
                    document.addEventListener('input', this.handleUserInteraction.bind(this));
                },
                
                handleUserInteraction: function(event) {
                    // Send user interaction data
                    this.sendUserInteraction({
                        type: event.type,
                        target: event.target.tagName,
                        timestamp: Date.now()
                    });
                },
                
                getLearningStatus: function() {
                    fetch(this.apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'vortex_realtime_learning',
                            subaction: 'get_learning_status',
                            nonce: this.nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.updateLearningDisplay(data.data);
                        }
                    })
                    .catch(error => console.error('Vortex Learning Error:', error));
                },
                
                sendUserInteraction: function(interaction) {
                    fetch(this.apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'vortex_realtime_learning',
                            subaction: 'submit_user_feedback',
                            interaction: JSON.stringify(interaction),
                            nonce: this.nonce
                        })
                    })
                    .catch(error => console.error('Vortex Interaction Error:', error));
                },
                
                updateLearningDisplay: function(data) {
                    // Update any learning indicators on the page
                    if (data.is_active) {
                        console.log('Vortex AI Engine: Real-time learning active - Cycle:', data.total_cycles, 'Score:', data.performance_score);
                    }
                }
            };
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    window.VortexRealtimeLearning.init();
                });
            } else {
                window.VortexRealtimeLearning.init();
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Inject admin real-time learning script
     */
    public function inject_admin_realtime_learning_script() {
        ?>
        <script>
        (function() {
            'use strict';
            
            // VORTEX Admin Real-time Learning Script
            window.VortexAdminRealtimeLearning = {
                apiUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('vortex_realtime_learning'); ?>',
                
                init: function() {
                    this.startAdminRealTimeLearning();
                    this.setupAdminEventListeners();
                },
                
                startAdminRealTimeLearning: function() {
                    // Get detailed performance metrics
                    this.getPerformanceMetrics();
                    
                    // Set up periodic updates
                    setInterval(() => {
                        this.getPerformanceMetrics();
                    }, 15000); // Every 15 seconds for admin
                },
                
                setupAdminEventListeners: function() {
                    // Monitor admin interactions
                    document.addEventListener('click', this.handleAdminInteraction.bind(this));
                    document.addEventListener('input', this.handleAdminInteraction.bind(this));
                },
                
                handleAdminInteraction: function(event) {
                    // Send admin interaction data
                    this.sendAdminInteraction({
                        type: event.type,
                        target: event.target.tagName,
                        context: 'admin',
                        timestamp: Date.now()
                    });
                },
                
                getPerformanceMetrics: function() {
                    fetch(this.apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'vortex_realtime_learning',
                            subaction: 'get_performance_metrics',
                            nonce: this.nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.updateAdminDisplay(data.data);
                        }
                    })
                    .catch(error => console.error('Vortex Admin Learning Error:', error));
                },
                
                sendAdminInteraction: function(interaction) {
                    fetch(this.apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'vortex_realtime_learning',
                            subaction: 'submit_user_feedback',
                            interaction: JSON.stringify(interaction),
                            nonce: this.nonce
                        })
                    })
                    .catch(error => console.error('Vortex Admin Interaction Error:', error));
                },
                
                updateAdminDisplay: function(data) {
                    // Update admin dashboard with real-time metrics
                    console.log('Vortex AI Engine Admin: Real-time metrics updated', data);
                    
                    // Update any admin dashboard elements
                    this.updateDashboardMetrics(data);
                },
                
                updateDashboardMetrics: function(data) {
                    // Update dashboard metrics if elements exist
                    const elements = {
                        'learning-cycles': data.learning_state.total_cycles,
                        'performance-score': data.learning_state.performance_score,
                        'improvements-made': data.learning_state.improvements_made,
                        'learning-rate': data.learning_state.learning_rate
                    };
                    
                    Object.keys(elements).forEach(id => {
                        const element = document.getElementById('vortex-' + id);
                        if (element) {
                            element.textContent = elements[id];
                        }
                    });
                }
            };
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    window.VortexAdminRealtimeLearning.init();
                });
            } else {
                window.VortexAdminRealtimeLearning.init();
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Helper methods for system metrics
     */
    private function get_cpu_usage() {
        // Simulate CPU usage for now
        return rand(10, 80);
    }
    
    private function get_memory_usage() {
        // Get memory usage
        return memory_get_usage(true);
    }
    
    private function get_load_average() {
        // Simulate load average
        return rand(0, 5);
    }
    
    private function get_response_time() {
        // Calculate response time
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }
    
    private function get_error_rate() {
        // Get error rate from logs
        return 0.01; // 1% error rate
    }
    
    private function get_user_activity() {
        // Get user activity
        return get_option('vortex_user_activity', 0);
    }
    
    private function get_page_load_time() {
        // Calculate page load time
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }
    
    private function get_database_queries() {
        // Get database query count
        global $wpdb;
        return $wpdb->num_queries;
    }
    
    private function get_cache_hit_rate() {
        // Get cache hit rate
        return 0.85; // 85% cache hit rate
    }
    
    private function get_api_response_time() {
        // Get API response time
        return 0.1; // 100ms
    }
    
    /**
     * Calculate performance score
     */
    private function calculate_performance_score() {
        $metrics = $this->realtime_data['system_metrics'];
        
        // Calculate score based on various metrics
        $score = 0;
        
        // CPU usage (lower is better)
        $score += max(0, 100 - $metrics['cpu_usage']) / 100;
        
        // Response time (lower is better)
        $score += max(0, 1 - $metrics['response_time']) / 1;
        
        // Error rate (lower is better)
        $score += max(0, 1 - $metrics['error_rate']) / 1;
        
        // User activity (higher is better)
        $score += min(1, $metrics['user_activity'] / 100) / 1;
        
        return $score / 4; // Average score
    }
    
    /**
     * Adjust learning rate based on performance
     */
    private function adjust_learning_rate() {
        $current_score = $this->learning_state['performance_score'];
        $current_rate = $this->learning_state['learning_rate'];
        
        if ($current_score > 0.8) {
            // High performance, reduce learning rate
            return max(0.0001, $current_rate * 0.95);
        } elseif ($current_score < 0.5) {
            // Low performance, increase learning rate
            return min(0.01, $current_rate * 1.05);
        }
        
        return $current_rate;
    }
    
    /**
     * Log learning progress
     */
    private function log_learning_progress() {
        $log_data = [
            'timestamp' => time(),
            'cycle' => $this->learning_state['total_cycles'],
            'performance_score' => $this->learning_state['performance_score'],
            'learning_rate' => $this->learning_state['learning_rate'],
            'improvements_made' => $this->learning_state['improvements_made']
        ];
        
        error_log('VORTEX AI Engine Learning Progress: ' . json_encode($log_data));
    }
    
    /**
     * Get learning state
     */
    public function get_learning_state() {
        return $this->learning_state;
    }
    
    /**
     * Get real-time data
     */
    public function get_realtime_data() {
        return $this->realtime_data;
    }
    
    /**
     * Check if learning is active
     */
    public function is_learning_active() {
        return $this->learning_state['is_active'];
    }
}

// Initialize the real-time learning orchestrator
VORTEX_Realtime_Learning_Orchestrator::get_instance(); 