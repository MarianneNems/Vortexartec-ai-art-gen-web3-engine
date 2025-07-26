<?php
/**
 * VORTEX AI ENGINE - GITHUB REALTIME INTEGRATION
 * 
 * Real-time GitHub integration with:
 * - Live logging and debugging
 * - Recursive self-improvement loop
 * - Self-reinforcement continuous deep learning
 * - End-to-end ecosystem monitoring
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vortex_GitHub_Realtime_Integration {
    
    private static $instance = null;
    private $github_token;
    private $repository;
    private $branch;
    private $webhook_secret;
    private $last_sync_time;
    private $sync_interval = 30; // 30 seconds
    private $improvement_cycle = 0;
    private $learning_data = array();
    private $debug_log = array();
    private $performance_metrics = array();
    
    // GitHub API endpoints
    private $github_api_base = 'https://api.github.com';
    private $github_webhook_url;
    
    // Real-time monitoring
    private $monitoring_active = false;
    private $recursive_loop_active = false;
    private $deep_learning_active = false;
    
    // Performance tracking
    private $start_time;
    private $memory_usage = array();
    private $execution_times = array();
    
    public function __construct() {
        $this->init();
        $this->setup_hooks();
        $this->start_monitoring();
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the GitHub integration
     */
    private function init() {
        $this->github_token = defined('VORTEX_GITHUB_TOKEN') ? VORTEX_GITHUB_TOKEN : '';
        $this->repository = defined('VORTEX_GITHUB_REPO') ? VORTEX_GITHUB_REPO : 'mariannenems/vortexartec-ai-marketplace';
        $this->branch = defined('VORTEX_GITHUB_BRANCH') ? VORTEX_GITHUB_BRANCH : 'main';
        $this->webhook_secret = defined('VORTEX_GITHUB_WEBHOOK_SECRET') ? VORTEX_GITHUB_WEBHOOK_SECRET : '';
        $this->github_webhook_url = defined('VORTEX_GITHUB_WEBHOOK_URL') ? VORTEX_GITHUB_WEBHOOK_URL : '';
        
        $this->last_sync_time = get_option('vortex_github_last_sync', 0);
        $this->start_time = microtime(true);
        
        // Initialize learning data
        $this->learning_data = get_option('vortex_learning_data', array(
            'patterns' => array(),
            'optimizations' => array(),
            'performance_history' => array(),
            'error_patterns' => array(),
            'success_metrics' => array()
        ));
        
        $this->log_debug('GitHub Realtime Integration initialized', 'INFO');
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        add_action('init', array($this, 'start_realtime_loop'));
        add_action('wp_ajax_vortex_github_sync', array($this, 'ajax_github_sync'));
        add_action('wp_ajax_vortex_github_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_vortex_github_webhook', array($this, 'handle_webhook'));
        
        // Cron jobs for continuous monitoring
        add_action('vortex_github_sync_cron', array($this, 'perform_sync'));
        add_action('vortex_recursive_improvement_cron', array($this, 'recursive_improvement_cycle'));
        add_action('vortex_deep_learning_cron', array($this, 'deep_learning_cycle'));
        
        // Performance monitoring
        add_action('shutdown', array($this, 'log_performance_metrics'));
        
        // Register cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules($schedules) {
        $schedules['vortex_realtime'] = array(
            'interval' => 30,
            'display' => 'Vortex Realtime (30 seconds)'
        );
        $schedules['vortex_improvement'] = array(
            'interval' => 300, // 5 minutes
            'display' => 'Vortex Improvement (5 minutes)'
        );
        $schedules['vortex_learning'] = array(
            'interval' => 600, // 10 minutes
            'display' => 'Vortex Learning (10 minutes)'
        );
        return $schedules;
    }
    
    /**
     * Start real-time monitoring loop
     */
    public function start_realtime_loop() {
        if (!$this->monitoring_active) {
            $this->monitoring_active = true;
            $this->schedule_cron_jobs();
            $this->log_debug('Real-time monitoring loop started', 'INFO');
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('vortex_github_sync_cron')) {
            wp_schedule_event(time(), 'vortex_realtime', 'vortex_github_sync_cron');
        }
        if (!wp_next_scheduled('vortex_recursive_improvement_cron')) {
            wp_schedule_event(time(), 'vortex_improvement', 'vortex_recursive_improvement_cron');
        }
        if (!wp_next_scheduled('vortex_deep_learning_cron')) {
            wp_schedule_event(time(), 'vortex_learning', 'vortex_deep_learning_cron');
        }
    }
    
    /**
     * Perform GitHub sync
     */
    public function perform_sync() {
        $start_time = microtime(true);
        
        try {
            // Get latest commits
            $commits = $this->get_latest_commits();
            
            // Check for changes
            $changes = $this->analyze_changes($commits);
            
            // Update local files if needed
            if (!empty($changes)) {
                $this->apply_changes($changes);
            }
            
            // Update sync time
            $this->last_sync_time = time();
            update_option('vortex_github_last_sync', $this->last_sync_time);
            
            // Log performance
            $execution_time = microtime(true) - $start_time;
            $this->log_performance('github_sync', $execution_time);
            
            $this->log_debug("GitHub sync completed in {$execution_time}s", 'SUCCESS');
            
        } catch (Exception $e) {
            $this->log_debug("GitHub sync failed: " . $e->getMessage(), 'ERROR');
            $this->record_error_pattern($e->getMessage());
        }
    }
    
    /**
     * Get latest commits from GitHub
     */
    private function get_latest_commits() {
        $url = "{$this->github_api_base}/repos/{$this->repository}/commits";
        $args = array(
            'headers' => array(
                'Authorization' => 'token ' . $this->github_token,
                'Accept' => 'application/vnd.github.v3+json'
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch commits: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $commits = json_decode($body, true);
        
        if (empty($commits)) {
            throw new Exception('No commits found or invalid response');
        }
        
        return $commits;
    }
    
    /**
     * Analyze changes in commits
     */
    private function analyze_changes($commits) {
        $changes = array();
        
        foreach ($commits as $commit) {
            $commit_time = strtotime($commit['commit']['author']['date']);
            
            // Only process recent commits
            if ($commit_time > $this->last_sync_time) {
                $commit_sha = $commit['sha'];
                $commit_message = $commit['commit']['message'];
                
                // Get commit details
                $commit_details = $this->get_commit_details($commit_sha);
                
                if ($commit_details) {
                    $changes[] = array(
                        'sha' => $commit_sha,
                        'message' => $commit_message,
                        'author' => $commit['commit']['author']['name'],
                        'date' => $commit_time,
                        'files' => $commit_details['files']
                    );
                }
            }
        }
        
        return $changes;
    }
    
    /**
     * Get detailed commit information
     */
    private function get_commit_details($sha) {
        $url = "{$this->github_api_base}/repos/{$this->repository}/commits/{$sha}";
        $args = array(
            'headers' => array(
                'Authorization' => 'token ' . $this->github_token,
                'Accept' => 'application/vnd.github.v3+json'
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Apply changes to local files
     */
    private function apply_changes($changes) {
        foreach ($changes as $change) {
            $this->log_debug("Applying changes from commit: {$change['sha']}", 'INFO');
            
            foreach ($change['files'] as $file) {
                $this->process_file_change($file);
            }
        }
    }
    
    /**
     * Process individual file changes
     */
    private function process_file_change($file) {
        $filename = $file['filename'];
        $status = $file['status'];
        
        switch ($status) {
            case 'modified':
            case 'added':
                $this->update_local_file($filename, $file['raw_url']);
                break;
            case 'removed':
                $this->remove_local_file($filename);
                break;
        }
    }
    
    /**
     * Update local file from GitHub
     */
    private function update_local_file($filename, $raw_url) {
        $local_path = VORTEX_AI_ENGINE_PLUGIN_PATH . $filename;
        
        // Create directory if it doesn't exist
        $dir = dirname($local_path);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        
        // Download file content
        $response = wp_remote_get($raw_url, array('timeout' => 30));
        
        if (!is_wp_error($response)) {
            $content = wp_remote_retrieve_body($response);
            file_put_contents($local_path, $content);
            $this->log_debug("Updated file: {$filename}", 'SUCCESS');
        }
    }
    
    /**
     * Remove local file
     */
    private function remove_local_file($filename) {
        $local_path = VORTEX_AI_ENGINE_PLUGIN_PATH . $filename;
        
        if (file_exists($local_path)) {
            unlink($local_path);
            $this->log_debug("Removed file: {$filename}", 'INFO');
        }
    }
    
    /**
     * Recursive self-improvement cycle
     */
    public function recursive_improvement_cycle() {
        $this->improvement_cycle++;
        $start_time = microtime(true);
        
        $this->log_debug("Starting recursive improvement cycle #{$this->improvement_cycle}", 'INFO');
        
        try {
            // Analyze current performance
            $performance_analysis = $this->analyze_performance();
            
            // Identify improvement opportunities
            $improvements = $this->identify_improvements($performance_analysis);
            
            // Apply improvements
            foreach ($improvements as $improvement) {
                $this->apply_improvement($improvement);
            }
            
            // Update learning data
            $this->update_learning_data('improvements', $improvements);
            
            $execution_time = microtime(true) - $start_time;
            $this->log_performance('recursive_improvement', $execution_time);
            
            $this->log_debug("Recursive improvement cycle completed in {$execution_time}s", 'SUCCESS');
            
        } catch (Exception $e) {
            $this->log_debug("Recursive improvement failed: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Deep learning cycle
     */
    public function deep_learning_cycle() {
        $start_time = microtime(true);
        
        $this->log_debug("Starting deep learning cycle", 'INFO');
        
        try {
            // Collect learning data
            $new_data = $this->collect_learning_data();
            
            // Process and analyze patterns
            $patterns = $this->analyze_patterns($new_data);
            
            // Update learning models
            $this->update_learning_models($patterns);
            
            // Apply learned optimizations
            $this->apply_learned_optimizations($patterns);
            
            $execution_time = microtime(true) - $start_time;
            $this->log_performance('deep_learning', $execution_time);
            
            $this->log_debug("Deep learning cycle completed in {$execution_time}s", 'SUCCESS');
            
        } catch (Exception $e) {
            $this->log_debug("Deep learning failed: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Analyze performance patterns
     */
    private function analyze_performance() {
        $analysis = array(
            'memory_usage' => $this->analyze_memory_usage(),
            'execution_times' => $this->analyze_execution_times(),
            'error_patterns' => $this->analyze_error_patterns(),
            'success_metrics' => $this->analyze_success_metrics()
        );
        
        return $analysis;
    }
    
    /**
     * Identify improvement opportunities
     */
    private function identify_improvements($analysis) {
        $improvements = array();
        
        // Memory optimization
        if ($analysis['memory_usage']['average'] > 50) {
            $improvements[] = array(
                'type' => 'memory_optimization',
                'priority' => 'high',
                'description' => 'Reduce memory usage through optimization'
            );
        }
        
        // Performance optimization
        if ($analysis['execution_times']['average'] > 2.0) {
            $improvements[] = array(
                'type' => 'performance_optimization',
                'priority' => 'high',
                'description' => 'Optimize execution times'
            );
        }
        
        // Error reduction
        if (count($analysis['error_patterns']) > 0) {
            $improvements[] = array(
                'type' => 'error_reduction',
                'priority' => 'medium',
                'description' => 'Implement error handling improvements'
            );
        }
        
        return $improvements;
    }
    
    /**
     * Apply improvement
     */
    private function apply_improvement($improvement) {
        switch ($improvement['type']) {
            case 'memory_optimization':
                $this->optimize_memory_usage();
                break;
            case 'performance_optimization':
                $this->optimize_performance();
                break;
            case 'error_reduction':
                $this->improve_error_handling();
                break;
        }
        
        $this->log_debug("Applied improvement: {$improvement['description']}", 'SUCCESS');
    }
    
    /**
     * Collect learning data
     */
    private function collect_learning_data() {
        $data = array(
            'performance_metrics' => $this->performance_metrics,
            'debug_log' => $this->debug_log,
            'memory_usage' => $this->memory_usage,
            'execution_times' => $this->execution_times,
            'user_interactions' => $this->get_user_interactions(),
            'system_events' => $this->get_system_events()
        );
        
        return $data;
    }
    
    /**
     * Analyze patterns in data
     */
    private function analyze_patterns($data) {
        $patterns = array(
            'performance_patterns' => $this->find_performance_patterns($data),
            'error_patterns' => $this->find_error_patterns($data),
            'usage_patterns' => $this->find_usage_patterns($data),
            'optimization_opportunities' => $this->find_optimization_opportunities($data)
        );
        
        return $patterns;
    }
    
    /**
     * Update learning models
     */
    private function update_learning_models($patterns) {
        // Update learning data
        $this->learning_data['patterns'] = array_merge(
            $this->learning_data['patterns'],
            $patterns
        );
        
        // Save to database
        update_option('vortex_learning_data', $this->learning_data);
        
        $this->log_debug("Learning models updated with new patterns", 'INFO');
    }
    
    /**
     * Apply learned optimizations
     */
    private function apply_learned_optimizations($patterns) {
        foreach ($patterns['optimization_opportunities'] as $optimization) {
            $this->apply_optimization($optimization);
        }
    }
    
    /**
     * Log debug information
     */
    public function log_debug($message, $level = 'INFO') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        );
        
        $this->debug_log[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($this->debug_log) > 1000) {
            $this->debug_log = array_slice($this->debug_log, -1000);
        }
        
        // Save to database
        update_option('vortex_debug_log', $this->debug_log);
        
        // Also log to WordPress debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[VORTEX GITHUB] [{$level}] {$message}");
        }
    }
    
    /**
     * Log performance metrics
     */
    private function log_performance($operation, $execution_time) {
        $this->performance_metrics[$operation][] = array(
            'timestamp' => current_time('mysql'),
            'execution_time' => $execution_time,
            'memory_usage' => memory_get_usage(true)
        );
        
        // Keep only last 100 entries per operation
        if (count($this->performance_metrics[$operation]) > 100) {
            $this->performance_metrics[$operation] = array_slice(
                $this->performance_metrics[$operation], -100
            );
        }
        
        update_option('vortex_performance_metrics', $this->performance_metrics);
    }
    
    /**
     * Record error pattern
     */
    private function record_error_pattern($error_message) {
        $this->learning_data['error_patterns'][] = array(
            'timestamp' => current_time('mysql'),
            'error' => $error_message,
            'context' => $this->get_current_context()
        );
        
        update_option('vortex_learning_data', $this->learning_data);
    }
    
    /**
     * Get current context
     */
    private function get_current_context() {
        return array(
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        );
    }
    
    /**
     * AJAX handler for GitHub sync
     */
    public function ajax_github_sync() {
        check_ajax_referer('vortex_github_sync', 'nonce');
        
        try {
            $this->perform_sync();
            wp_send_json_success(array(
                'message' => 'GitHub sync completed successfully',
                'last_sync' => $this->last_sync_time
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'GitHub sync failed: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle GitHub webhook
     */
    public function handle_webhook() {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($payload, $signature)) {
            http_response_code(401);
            exit('Unauthorized');
        }
        
        $data = json_decode($payload, true);
        
        if ($data) {
            $this->process_webhook_event($data);
        }
        
        http_response_code(200);
        exit('OK');
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($payload, $signature) {
        if (empty($this->webhook_secret)) {
            return true; // Skip verification if no secret configured
        }
        
        $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Process webhook event
     */
    private function process_webhook_event($data) {
        $event_type = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
        
        switch ($event_type) {
            case 'push':
                $this->handle_push_event($data);
                break;
            case 'pull_request':
                $this->handle_pull_request_event($data);
                break;
            case 'issues':
                $this->handle_issue_event($data);
                break;
        }
        
        $this->log_debug("Processed webhook event: {$event_type}", 'INFO');
    }
    
    /**
     * Handle push event
     */
    private function handle_push_event($data) {
        $this->log_debug("Push event received for branch: {$data['ref']}", 'INFO');
        
        // Trigger immediate sync
        $this->perform_sync();
        
        // Trigger improvement cycle
        $this->recursive_improvement_cycle();
    }
    
    /**
     * Handle pull request event
     */
    private function handle_pull_request_event($data) {
        $action = $data['action'];
        $pr_number = $data['pull_request']['number'];
        
        $this->log_debug("Pull request #{$pr_number} {$action}", 'INFO');
        
        if ($action === 'closed' && $data['pull_request']['merged']) {
            // PR was merged, trigger sync
            $this->perform_sync();
        }
    }
    
    /**
     * Handle issue event
     */
    private function handle_issue_event($data) {
        $action = $data['action'];
        $issue_number = $data['issue']['number'];
        
        $this->log_debug("Issue #{$issue_number} {$action}", 'INFO');
        
        // Log issue for learning
        $this->learning_data['issues'][] = array(
            'number' => $issue_number,
            'action' => $action,
            'title' => $data['issue']['title'],
            'timestamp' => current_time('mysql')
        );
        
        update_option('vortex_learning_data', $this->learning_data);
    }
    
    /**
     * Get system status
     */
    public function get_system_status() {
        return array(
            'monitoring_active' => $this->monitoring_active,
            'recursive_loop_active' => $this->recursive_loop_active,
            'deep_learning_active' => $this->deep_learning_active,
            'last_sync_time' => $this->last_sync_time,
            'improvement_cycle' => $this->improvement_cycle,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'uptime' => microtime(true) - $this->start_time
        );
    }
    
    /**
     * Get debug log
     */
    public function get_debug_log($limit = 100) {
        return array_slice($this->debug_log, -$limit);
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        return $this->performance_metrics;
    }
    
    /**
     * Get learning data
     */
    public function get_learning_data() {
        return $this->learning_data;
    }
    
    /**
     * Cleanup on deactivation
     */
    public function cleanup() {
        wp_clear_scheduled_hook('vortex_github_sync_cron');
        wp_clear_scheduled_hook('vortex_recursive_improvement_cron');
        wp_clear_scheduled_hook('vortex_deep_learning_cron');
    }
}

// Initialize the integration
Vortex_GitHub_Realtime_Integration::get_instance(); 