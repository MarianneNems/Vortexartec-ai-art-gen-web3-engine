<?php
/**
 * VORTEX AI ENGINE - REINFORCEMENT LEARNING ENGINE
 * 
 * Advanced reinforcement learning engine for recursive self-improvement
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Reinforcement_Engine {
    
    /**
     * Configuration
     */
    private $config = [
        'learning_rate' => 0.1,
        'discount_factor' => 0.9,
        'epsilon' => 0.1,
        'epsilon_decay' => 0.995,
        'epsilon_min' => 0.01,
        'memory_size' => 10000,
        'batch_size' => 32,
        'update_frequency' => 100,
        'target_update_frequency' => 1000
    ];
    
    /**
     * Q-table for state-action values
     */
    private $q_table = [];
    
    /**
     * Experience replay memory
     */
    private $memory = [];
    
    /**
     * Current state
     */
    private $current_state = null;
    
    /**
     * Learning history
     */
    private $learning_history = [];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge($this->config, $config);
        $this->initialize_q_table();
    }
    
    /**
     * Initialize Q-table
     */
    private function initialize_q_table() {
        // Initialize with random values
        $this->q_table = [];
    }
    
    /**
     * Get Q-value for state-action pair
     */
    private function get_q_value($state, $action) {
        $state_key = $this->state_to_key($state);
        $action_key = $this->action_to_key($action);
        
        if (!isset($this->q_table[$state_key])) {
            $this->q_table[$state_key] = [];
        }
        
        if (!isset($this->q_table[$state_key][$action_key])) {
            $this->q_table[$state_key][$action_key] = 0.0;
        }
        
        return $this->q_table[$state_key][$action_key];
    }
    
    /**
     * Set Q-value for state-action pair
     */
    private function set_q_value($state, $action, $value) {
        $state_key = $this->state_to_key($state);
        $action_key = $this->action_to_key($action);
        
        if (!isset($this->q_table[$state_key])) {
            $this->q_table[$state_key] = [];
        }
        
        $this->q_table[$state_key][$action_key] = $value;
    }
    
    /**
     * Convert state to key
     */
    private function state_to_key($state) {
        return md5(serialize($state));
    }
    
    /**
     * Convert action to key
     */
    private function action_to_key($action) {
        return md5(serialize($action));
    }
    
    /**
     * Choose action using epsilon-greedy policy
     */
    public function choose_action($state, $available_actions) {
        $this->current_state = $state;
        
        // Epsilon-greedy exploration
        if (rand() / getrandmax() < $this->config['epsilon']) {
            // Explore: choose random action
            return $available_actions[array_rand($available_actions)];
        } else {
            // Exploit: choose best action
            return $this->get_best_action($state, $available_actions);
        }
    }
    
    /**
     * Get best action for state
     */
    private function get_best_action($state, $available_actions) {
        $best_action = null;
        $best_value = -INF;
        
        foreach ($available_actions as $action) {
            $q_value = $this->get_q_value($state, $action);
            if ($q_value > $best_value) {
                $best_value = $q_value;
                $best_action = $action;
            }
        }
        
        return $best_action;
    }
    
    /**
     * Learn from experience
     */
    public function learn($state, $action, $reward, $next_state, $done = false) {
        // Store experience in memory
        $this->store_experience($state, $action, $reward, $next_state, $done);
        
        // Update Q-value using Q-learning
        $this->update_q_value($state, $action, $reward, $next_state, $done);
        
        // Decay epsilon
        $this->decay_epsilon();
        
        // Log learning
        $this->log_learning($state, $action, $reward, $next_state, $done);
    }
    
    /**
     * Store experience in replay memory
     */
    private function store_experience($state, $action, $reward, $next_state, $done) {
        $experience = [
            'state' => $state,
            'action' => $action,
            'reward' => $reward,
            'next_state' => $next_state,
            'done' => $done,
            'timestamp' => time()
        ];
        
        $this->memory[] = $experience;
        
        // Keep memory size limited
        if (count($this->memory) > $this->config['memory_size']) {
            array_shift($this->memory);
        }
    }
    
    /**
     * Update Q-value using Q-learning
     */
    private function update_q_value($state, $action, $reward, $next_state, $done) {
        $current_q = $this->get_q_value($state, $action);
        
        if ($done) {
            $target_q = $reward;
        } else {
            // Get max Q-value for next state
            $next_q_values = [];
            $next_actions = $this->get_available_actions($next_state);
            
            foreach ($next_actions as $next_action) {
                $next_q_values[] = $this->get_q_value($next_state, $next_action);
            }
            
            $max_next_q = max($next_q_values);
            $target_q = $reward + $this->config['discount_factor'] * $max_next_q;
        }
        
        // Update Q-value
        $new_q = $current_q + $this->config['learning_rate'] * ($target_q - $current_q);
        $this->set_q_value($state, $action, $new_q);
    }
    
    /**
     * Get available actions for state
     */
    private function get_available_actions($state) {
        // This would be implemented based on the specific environment
        // For now, return a default set of actions
        return ['action1', 'action2', 'action3', 'action4'];
    }
    
    /**
     * Decay epsilon for exploration
     */
    private function decay_epsilon() {
        $this->config['epsilon'] = max(
            $this->config['epsilon_min'],
            $this->config['epsilon'] * $this->config['epsilon_decay']
        );
    }
    
    /**
     * Log learning progress
     */
    private function log_learning($state, $action, $reward, $next_state, $done) {
        $this->learning_history[] = [
            'timestamp' => time(),
            'state' => $state,
            'action' => $action,
            'reward' => $reward,
            'next_state' => $next_state,
            'done' => $done,
            'epsilon' => $this->config['epsilon']
        ];
        
        // Keep history size limited
        if (count($this->learning_history) > 1000) {
            array_shift($this->learning_history);
        }
    }
    
    /**
     * Train on batch of experiences
     */
    public function train_on_batch($batch_size = null) {
        if (!$batch_size) {
            $batch_size = $this->config['batch_size'];
        }
        
        if (count($this->memory) < $batch_size) {
            return; // Not enough experiences
        }
        
        // Sample random batch
        $batch = array_rand($this->memory, $batch_size);
        $batch_experiences = array_map(function($index) {
            return $this->memory[$index];
        }, $batch);
        
        // Train on batch
        foreach ($batch_experiences as $experience) {
            $this->update_q_value(
                $experience['state'],
                $experience['action'],
                $experience['reward'],
                $experience['next_state'],
                $experience['done']
            );
        }
    }
    
    /**
     * Get policy for state
     */
    public function get_policy($state) {
        $available_actions = $this->get_available_actions($state);
        $policy = [];
        
        foreach ($available_actions as $action) {
            $policy[$action] = $this->get_q_value($state, $action);
        }
        
        return $policy;
    }
    
    /**
     * Get value function for state
     */
    public function get_value($state) {
        $available_actions = $this->get_available_actions($state);
        $values = [];
        
        foreach ($available_actions as $action) {
            $values[] = $this->get_q_value($state, $action);
        }
        
        return max($values);
    }
    
    /**
     * Get learning statistics
     */
    public function get_learning_stats() {
        $recent_history = array_slice($this->learning_history, -100);
        
        if (empty($recent_history)) {
            return [
                'total_experiences' => count($this->memory),
                'epsilon' => $this->config['epsilon'],
                'average_reward' => 0,
                'total_episodes' => 0
            ];
        }
        
        $rewards = array_column($recent_history, 'reward');
        $average_reward = array_sum($rewards) / count($rewards);
        
        return [
            'total_experiences' => count($this->memory),
            'epsilon' => $this->config['epsilon'],
            'average_reward' => $average_reward,
            'total_episodes' => count($this->learning_history)
        ];
    }
    
    /**
     * Save model
     */
    public function save_model($filename) {
        $model_data = [
            'config' => $this->config,
            'q_table' => $this->q_table,
            'learning_history' => $this->learning_history
        ];
        
        file_put_contents($filename, serialize($model_data));
    }
    
    /**
     * Load model
     */
    public function load_model($filename) {
        if (file_exists($filename)) {
            $model_data = unserialize(file_get_contents($filename));
            $this->config = $model_data['config'];
            $this->q_table = $model_data['q_table'];
            $this->learning_history = $model_data['learning_history'];
        }
    }
    
    /**
     * Reset learning
     */
    public function reset_learning() {
        $this->q_table = [];
        $this->memory = [];
        $this->learning_history = [];
        $this->config['epsilon'] = 0.1;
    }
    
    /**
     * Get model summary
     */
    public function get_model_summary() {
        return [
            'total_states' => count($this->q_table),
            'total_experiences' => count($this->memory),
            'epsilon' => $this->config['epsilon'],
            'learning_rate' => $this->config['learning_rate'],
            'discount_factor' => $this->config['discount_factor']
        ];
    }
} 