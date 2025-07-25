<?php
/**
 * VORTEX AI ENGINE - REINFORCEMENT LEARNING SYSTEM
 * 
 * Continuous reinforcement learning with real-time optimization
 * and adaptive behavior based on performance feedback
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Reinforcement Learning System
 * 
 * Implements Q-learning and policy optimization for continuous improvement
 */
class Vortex_Reinforcement_Learning {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Q-learning parameters
     */
    private $q_learning_params = array();
    
    /**
     * Policy network
     */
    private $policy_network = array();
    
    /**
     * Experience replay buffer
     */
    private $experience_buffer = array();
    
    /**
     * Performance tracking
     */
    private $performance_tracking = array();
    
    /**
     * Learning metrics
     */
    private $learning_metrics = array();
    
    /**
     * Get single instance
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
        $this->init_reinforcement_learning();
    }
    
    /**
     * Initialize reinforcement learning
     */
    private function init_reinforcement_learning() {
        $this->q_learning_params = array(
            'learning_rate' => 0.01,
            'discount_factor' => 0.95,
            'epsilon' => 0.1,
            'epsilon_decay' => 0.995,
            'epsilon_min' => 0.01,
            'max_experience_size' => 10000,
            'batch_size' => 32,
            'update_frequency' => 100
        );
        
        // Load existing policy
        $this->load_policy_network();
        
        // Load experience buffer
        $this->load_experience_buffer();
        
        // Initialize performance tracking
        $this->init_performance_tracking();
        
        // Start learning cycles
        $this->start_learning_cycles();
    }
    
    /**
     * Load policy network
     */
    private function load_policy_network() {
        $saved_policy = get_option('vortex_rl_policy_network', array());
        if (!empty($saved_policy)) {
            $this->policy_network = $saved_policy;
        } else {
            $this->initialize_policy_network();
        }
    }
    
    /**
     * Initialize policy network
     */
    private function initialize_policy_network() {
        // Initialize Q-values for different states and actions
        $this->policy_network = array(
            'ai_agents' => array(
                'huraii' => array(),
                'cloe' => array(),
                'horace' => array(),
                'thorius' => array()
            ),
            'tool_calls' => array(),
            'error_correction' => array(),
            'performance_optimization' => array(),
            'user_interactions' => array()
        );
    }
    
    /**
     * Load experience buffer
     */
    private function load_experience_buffer() {
        $saved_experience = get_option('vortex_rl_experience_buffer', array());
        if (!empty($saved_experience)) {
            $this->experience_buffer = $saved_experience;
        }
    }
    
    /**
     * Initialize performance tracking
     */
    private function init_performance_tracking() {
        $this->performance_tracking = array(
            'total_episodes' => 0,
            'total_rewards' => 0,
            'average_reward' => 0,
            'best_reward' => 0,
            'convergence_rate' => 0,
            'learning_progress' => array()
        );
    }
    
    /**
     * Start learning cycles
     */
    private function start_learning_cycles() {
        // Schedule learning cycles
        if (!wp_next_scheduled('vortex_rl_learning_cycle')) {
            wp_schedule_event(time(), 'every_5_minutes', 'vortex_rl_learning_cycle');
        }
        
        add_action('vortex_rl_learning_cycle', array($this, 'run_learning_cycle'));
        
        // Real-time learning triggers
        add_action('wp_loaded', array($this, 'trigger_realtime_learning'));
    }
    
    /**
     * Run learning cycle
     */
    public function run_learning_cycle() {
        $this->log_learning_activity('🔄 Starting reinforcement learning cycle', 'LEARNING_CYCLE');
        
        // Update Q-values from experience
        $this->update_q_values_from_experience();
        
        // Optimize policy network
        $this->optimize_policy_network();
        
        // Update performance metrics
        $this->update_performance_metrics();
        
        // Save updated models
        $this->save_learning_models();
        
        // Decay epsilon for exploration
        $this->decay_epsilon();
        
        $this->log_learning_activity('✅ Completed reinforcement learning cycle', 'LEARNING_CYCLE');
    }
    
    /**
     * Trigger real-time learning
     */
    public function trigger_realtime_learning() {
        // Check for immediate learning opportunities
        $this->check_immediate_learning();
        
        // Apply real-time policy updates
        $this->apply_realtime_policy_updates();
    }
    
    /**
     * Add experience to buffer
     */
    public function add_experience($state, $action, $reward, $next_state, $done = false) {
        $experience = array(
            'state' => $state,
            'action' => $action,
            'reward' => $reward,
            'next_state' => $next_state,
            'done' => $done,
            'timestamp' => microtime(true)
        );
        
        $this->experience_buffer[] = $experience;
        
        // Keep buffer within size limit
        if (count($this->experience_buffer) > $this->q_learning_params['max_experience_size']) {
            $this->experience_buffer = array_slice($this->experience_buffer, -$this->q_learning_params['max_experience_size']);
        }
        
        // Update performance tracking
        $this->update_performance_tracking($reward);
        
        // Trigger learning if enough experience
        if (count($this->experience_buffer) >= $this->q_learning_params['batch_size']) {
            $this->trigger_learning_update();
        }
    }
    
    /**
     * Get action using epsilon-greedy policy
     */
    public function get_action($state, $context = 'general') {
        // Epsilon-greedy exploration
        if (rand(0, 100) / 100 < $this->q_learning_params['epsilon']) {
            // Explore: choose random action
            return $this->get_random_action($state, $context);
        } else {
            // Exploit: choose best action based on Q-values
            return $this->get_best_action($state, $context);
        }
    }
    
    /**
     * Get random action for exploration
     */
    private function get_random_action($state, $context) {
        $available_actions = $this->get_available_actions($state, $context);
        
        if (empty($available_actions)) {
            return 'default_action';
        }
        
        return $available_actions[array_rand($available_actions)];
    }
    
    /**
     * Get best action based on Q-values
     */
    private function get_best_action($state, $context) {
        $state_key = $this->get_state_key($state, $context);
        
        if (!isset($this->policy_network[$context][$state_key])) {
            return $this->get_random_action($state, $context);
        }
        
        $q_values = $this->policy_network[$context][$state_key];
        $best_action = array_keys($q_values, max($q_values))[0];
        
        return $best_action;
    }
    
    /**
     * Get available actions for state
     */
    private function get_available_actions($state, $context) {
        switch ($context) {
            case 'ai_agents':
                return array('optimize', 'retry', 'fallback', 'learn');
            case 'tool_calls':
                return array('retry', 'optimize_params', 'use_cache', 'fallback');
            case 'error_correction':
                return array('fix_syntax', 'fix_runtime', 'fix_database', 'fix_memory');
            case 'performance_optimization':
                return array('optimize_memory', 'optimize_cache', 'optimize_queries', 'optimize_files');
            case 'user_interactions':
                return array('improve_ui', 'optimize_response', 'personalize', 'learn_preference');
            default:
                return array('default_action');
        }
    }
    
    /**
     * Get state key
     */
    private function get_state_key($state, $context) {
        return $context . '_' . md5(serialize($state));
    }
    
    /**
     * Update Q-values from experience
     */
    private function update_q_values_from_experience() {
        if (count($this->experience_buffer) < $this->q_learning_params['batch_size']) {
            return;
        }
        
        // Sample batch from experience buffer
        $batch = $this->sample_experience_batch();
        
        foreach ($batch as $experience) {
            $this->update_q_value($experience);
        }
    }
    
    /**
     * Sample experience batch
     */
    private function sample_experience_batch() {
        $batch_size = min($this->q_learning_params['batch_size'], count($this->experience_buffer));
        $indices = array_rand($this->experience_buffer, $batch_size);
        
        if (!is_array($indices)) {
            $indices = array($indices);
        }
        
        $batch = array();
        foreach ($indices as $index) {
            $batch[] = $this->experience_buffer[$index];
        }
        
        return $batch;
    }
    
    /**
     * Update Q-value for experience
     */
    private function update_q_value($experience) {
        $state_key = $this->get_state_key($experience['state'], 'general');
        $action = $experience['action'];
        $reward = $experience['reward'];
        $next_state_key = $this->get_state_key($experience['next_state'], 'general');
        
        // Initialize Q-values if not exists
        if (!isset($this->policy_network['general'][$state_key])) {
            $this->policy_network['general'][$state_key] = array();
        }
        
        if (!isset($this->policy_network['general'][$state_key][$action])) {
            $this->policy_network['general'][$state_key][$action] = 0;
        }
        
        // Q-learning update
        $current_q = $this->policy_network['general'][$state_key][$action];
        $max_future_q = $this->get_max_future_q($next_state_key);
        
        $target_q = $reward;
        if (!$experience['done']) {
            $target_q += $this->q_learning_params['discount_factor'] * $max_future_q;
        }
        
        $new_q = $current_q + $this->q_learning_params['learning_rate'] * ($target_q - $current_q);
        $this->policy_network['general'][$state_key][$action] = $new_q;
    }
    
    /**
     * Get max future Q-value
     */
    private function get_max_future_q($state_key) {
        if (!isset($this->policy_network['general'][$state_key])) {
            return 0;
        }
        
        $q_values = $this->policy_network['general'][$state_key];
        return empty($q_values) ? 0 : max($q_values);
    }
    
    /**
     * Optimize policy network
     */
    private function optimize_policy_network() {
        // Optimize different contexts
        $this->optimize_ai_agent_policy();
        $this->optimize_tool_call_policy();
        $this->optimize_error_correction_policy();
        $this->optimize_performance_policy();
        $this->optimize_user_interaction_policy();
    }
    
    /**
     * Optimize AI agent policy
     */
    private function optimize_ai_agent_policy() {
        $agents = array('huraii', 'cloe', 'horace', 'thorius');
        
        foreach ($agents as $agent) {
            if (isset($this->policy_network['ai_agents'][$agent])) {
                // Optimize agent-specific policies
                $this->optimize_agent_policy($agent);
            }
        }
    }
    
    /**
     * Optimize agent policy
     */
    private function optimize_agent_policy($agent) {
        // Implement agent-specific policy optimization
        $this->log_learning_activity("Optimizing policy for agent: $agent", 'POLICY_OPTIMIZATION');
    }
    
    /**
     * Optimize tool call policy
     */
    private function optimize_tool_call_policy() {
        // Optimize tool call policies based on performance
        $this->log_learning_activity('Optimizing tool call policy', 'POLICY_OPTIMIZATION');
    }
    
    /**
     * Optimize error correction policy
     */
    private function optimize_error_correction_policy() {
        // Optimize error correction policies
        $this->log_learning_activity('Optimizing error correction policy', 'POLICY_OPTIMIZATION');
    }
    
    /**
     * Optimize performance policy
     */
    private function optimize_performance_policy() {
        // Optimize performance optimization policies
        $this->log_learning_activity('Optimizing performance policy', 'POLICY_OPTIMIZATION');
    }
    
    /**
     * Optimize user interaction policy
     */
    private function optimize_user_interaction_policy() {
        // Optimize user interaction policies
        $this->log_learning_activity('Optimizing user interaction policy', 'POLICY_OPTIMIZATION');
    }
    
    /**
     * Update performance metrics
     */
    private function update_performance_metrics() {
        $this->performance_tracking['total_episodes']++;
        
        // Calculate average reward
        if ($this->performance_tracking['total_rewards'] > 0) {
            $this->performance_tracking['average_reward'] = 
                $this->performance_tracking['total_rewards'] / $this->performance_tracking['total_episodes'];
        }
        
        // Update learning progress
        $this->performance_tracking['learning_progress'][] = array(
            'episode' => $this->performance_tracking['total_episodes'],
            'average_reward' => $this->performance_tracking['average_reward'],
            'epsilon' => $this->q_learning_params['epsilon'],
            'timestamp' => microtime(true)
        );
        
        // Keep progress history manageable
        if (count($this->performance_tracking['learning_progress']) > 1000) {
            $this->performance_tracking['learning_progress'] = 
                array_slice($this->performance_tracking['learning_progress'], -1000);
        }
    }
    
    /**
     * Update performance tracking
     */
    private function update_performance_tracking($reward) {
        $this->performance_tracking['total_rewards'] += $reward;
        
        if ($reward > $this->performance_tracking['best_reward']) {
            $this->performance_tracking['best_reward'] = $reward;
        }
    }
    
    /**
     * Save learning models
     */
    private function save_learning_models() {
        update_option('vortex_rl_policy_network', $this->policy_network);
        update_option('vortex_rl_experience_buffer', $this->experience_buffer);
        update_option('vortex_rl_performance_tracking', $this->performance_tracking);
    }
    
    /**
     * Decay epsilon for exploration
     */
    private function decay_epsilon() {
        $this->q_learning_params['epsilon'] = max(
            $this->q_learning_params['epsilon_min'],
            $this->q_learning_params['epsilon'] * $this->q_learning_params['epsilon_decay']
        );
    }
    
    /**
     * Check immediate learning
     */
    private function check_immediate_learning() {
        // Check for immediate learning opportunities
        $this->check_high_reward_opportunities();
        $this->check_error_patterns();
        $this->check_performance_degradation();
    }
    
    /**
     * Apply real-time policy updates
     */
    private function apply_realtime_policy_updates() {
        // Apply immediate policy updates based on recent experience
        $this->apply_immediate_q_updates();
        $this->apply_immediate_policy_optimization();
    }
    
    /**
     * Trigger learning update
     */
    private function trigger_learning_update() {
        // Trigger immediate learning update
        $this->update_q_values_from_experience();
        $this->optimize_policy_network();
    }
    
    /**
     * Log learning activity
     */
    public function log_learning_activity($message, $category = 'LEARNING_ACTIVITY') {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'microtime' => microtime(true),
            'category' => $category,
            'message' => $message,
            'epsilon' => $this->q_learning_params['epsilon'],
            'total_episodes' => $this->performance_tracking['total_episodes'],
            'average_reward' => $this->performance_tracking['average_reward']
        );
        
        // Log to file
        $log_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'logs/reinforcement-learning.log';
        $log_line = sprintf(
            "[%s] [%s] %s | Episodes: %d | Avg Reward: %.3f | Epsilon: %.3f\n",
            $log_entry['timestamp'],
            $category,
            $message,
            $this->performance_tracking['total_episodes'],
            $this->performance_tracking['average_reward'],
            $this->q_learning_params['epsilon']
        );
        
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get learning statistics
     */
    public function get_learning_stats() {
        return array(
            'q_learning_params' => $this->q_learning_params,
            'performance_tracking' => $this->performance_tracking,
            'learning_metrics' => $this->learning_metrics,
            'policy_network_size' => count($this->policy_network),
            'experience_buffer_size' => count($this->experience_buffer)
        );
    }
    
    // Placeholder methods for advanced learning features
    private function check_high_reward_opportunities() {}
    private function check_error_patterns() {}
    private function check_performance_degradation() {}
    private function apply_immediate_q_updates() {}
    private function apply_immediate_policy_optimization() {}
}

/**
 * Initialize the reinforcement learning system
 */
function vortex_reinforcement_learning_init() {
    return Vortex_Reinforcement_Learning::get_instance();
}

// Start the reinforcement learning system
vortex_reinforcement_learning_init();

/**
 * Global function to get RL instance
 */
function vortex_rl() {
    return Vortex_Reinforcement_Learning::get_instance();
} 