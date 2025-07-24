<?php
// Vault Path: secret/data/vortex-ai/cost_optimization_algorithms
/**
 * Cost Optimizer for VORTEX AI Engine
 * 80% Profit Margin Enforcement with Real-time Optimization
 * 
 * Features:
 * - Real-time cost tracking and optimization
 * - 80% profit margin enforcement
 * - Dynamic pricing adjustments
 * - Cost prediction algorithms
 * - Performance-based scaling
 *
 * @package VortexAIEngine
 * @version 3.0.0 Enhanced
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_CostOptimizer {
    
    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var float Target profit margin (80%) */
    private $target_profit_margin = 0.80;
    
    /** @var array Cost tracking data */
    private $cost_data = [
        'total_costs' => 0.0,
        'total_revenue' => 0.0,
        'current_margin' => 0.0,
        'daily_costs' => [],
        'hourly_costs' => [],
        'cost_breakdown' => []
    ];
    
    /** @var array Optimization rules */
    private $optimization_rules = [
        'agent_efficiency' => [
            'threshold' => 0.7,
            'suggestion' => 'Consider using more efficient agents'
        ],
        'processing_time' => [
            'threshold' => 30, // seconds
            'suggestion' => 'Optimize processing time to reduce costs'
        ],
        'quality_cost_ratio' => [
            'threshold' => 0.8,
            'suggestion' => 'Balance quality and cost for better efficiency'
        ],
        'batch_processing' => [
            'threshold' => 5, // requests
            'suggestion' => 'Use batch processing for multiple requests'
        ]
    ];
    
    /** @var array Cost thresholds */
    private $cost_thresholds = [
        'warning' => 0.75, // 75% margin
        'critical' => 0.70, // 70% margin
        'emergency' => 0.60 // 60% margin
    ];
    
    /** @var array Agent cost data */
    private $agent_costs = [
        'huraii' => ['base_cost' => 0.01, 'efficiency' => 0.85],
        'cloe' => ['base_cost' => 0.008, 'efficiency' => 0.90],
        'horace' => ['base_cost' => 0.012, 'efficiency' => 0.88],
        'thorius' => ['base_cost' => 0.015, 'efficiency' => 0.92],
        'archer' => ['base_cost' => 0.020, 'efficiency' => 0.95]
    ];
    
    /** @var array Revenue streams */
    private $revenue_streams = [
        'generate' => 0.10,
        'describe' => 0.05,
        'upscale' => 0.08,
        'enhance' => 0.12,
        'export' => 0.03,
        'share' => 0.02
    ];
    
    /** Singleton pattern */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /** Constructor */
    private function __construct() {
        $this->load_cost_data();
        $this->setup_cost_tracking();
        $this->setup_optimization_monitoring();
        $this->setup_wordpress_hooks();
    }
    
    /**
     * Load cost data from storage
     */
    private function load_cost_data() {
        $saved_data = get_option('vortex_cost_data', []);
        $this->cost_data = array_merge($this->cost_data, $saved_data);
        
        // Load target margin from settings
        $this->target_profit_margin = get_option('vortex_target_profit_margin', 0.80);
        
        // Load optimization rules
        $saved_rules = get_option('vortex_optimization_rules', []);
        $this->optimization_rules = array_merge($this->optimization_rules, $saved_rules);
    }
    
    /**
     * Setup cost tracking
     */
    private function setup_cost_tracking() {
        // Track costs in real-time
        add_action('vortex_ai_cost_incurred', [$this, 'track_cost'], 10, 3);
        add_action('vortex_ai_revenue_generated', [$this, 'track_revenue'], 10, 3);
        
        // Scheduled cost analysis
        if ( ! wp_next_scheduled('vortex_cost_analysis') ) {
            wp_schedule_event(time(), 'hourly', 'vortex_cost_analysis');
        }
        
        add_action('vortex_cost_analysis', [$this, 'run_cost_analysis']);
    }
    
    /**
     * Setup optimization monitoring
     */
    private function setup_optimization_monitoring() {
        // Real-time optimization checks
        add_action('vortex_ai_action_completed', [$this, 'analyze_action_efficiency'], 10, 2);
        
        // Daily optimization reports
        if ( ! wp_next_scheduled('vortex_daily_optimization_report') ) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_optimization_report');
        }
        
        add_action('vortex_daily_optimization_report', [$this, 'generate_optimization_report']);
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_wordpress_hooks() {
        // AJAX endpoints
        add_action('wp_ajax_vortex_get_cost_data', [$this, 'ajax_get_cost_data']);
        add_action('wp_ajax_vortex_get_optimization_suggestions', [$this, 'ajax_get_optimization_suggestions']);
        add_action('wp_ajax_vortex_apply_optimization', [$this, 'ajax_apply_optimization']);
        
        // Admin notices for cost alerts
        add_action('admin_notices', [$this, 'show_cost_alerts']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
    }
    
    /**
     * Track cost for an action
     */
    public function track_cost($action, $cost, $metadata = []) {
        $this->cost_data['total_costs'] += $cost;
        
        // Track by action type
        if ( !isset($this->cost_data['cost_breakdown'][$action]) ) {
            $this->cost_data['cost_breakdown'][$action] = 0;
        }
        $this->cost_data['cost_breakdown'][$action] += $cost;
        
        // Track hourly costs
        $current_hour = date('Y-m-d H:00:00');
        if ( !isset($this->cost_data['hourly_costs'][$current_hour]) ) {
            $this->cost_data['hourly_costs'][$current_hour] = 0;
        }
        $this->cost_data['hourly_costs'][$current_hour] += $cost;
        
        // Track daily costs
        $current_day = date('Y-m-d');
        if ( !isset($this->cost_data['daily_costs'][$current_day]) ) {
            $this->cost_data['daily_costs'][$current_day] = 0;
        }
        $this->cost_data['daily_costs'][$current_day] += $cost;
        
        // Update current margin
        $this->update_current_margin();
        
        // Check for threshold alerts
        $this->check_cost_thresholds();
        
        // Save data
        $this->save_cost_data();
        
        // Log cost tracking
        error_log("[VortexAI Cost] Tracked cost: {$action} = \${$cost}, Total: \${$this->cost_data['total_costs']}");
    }
    
    /**
     * Track revenue for an action
     */
    public function track_revenue($action, $revenue, $metadata = []) {
        $this->cost_data['total_revenue'] += $revenue;
        
        // Update current margin
        $this->update_current_margin();
        
        // Save data
        $this->save_cost_data();
        
        // Log revenue tracking
        error_log("[VortexAI Cost] Tracked revenue: {$action} = \${$revenue}, Total: \${$this->cost_data['total_revenue']}");
    }
    
    /**
     * Update current profit margin
     */
    private function update_current_margin() {
        if ( $this->cost_data['total_revenue'] > 0 ) {
            $this->cost_data['current_margin'] = ($this->cost_data['total_revenue'] - $this->cost_data['total_costs']) / $this->cost_data['total_revenue'];
        } else {
            $this->cost_data['current_margin'] = 0.0;
        }
    }
    
    /**
     * Check cost thresholds and trigger alerts
     */
    private function check_cost_thresholds() {
        $current_margin = $this->cost_data['current_margin'];
        
        foreach ( $this->cost_thresholds as $level => $threshold ) {
            if ( $current_margin <= $threshold ) {
                $this->trigger_cost_alert($level, $current_margin, $threshold);
                break;
            }
        }
    }
    
    /**
     * Trigger cost alert
     */
    private function trigger_cost_alert($level, $current_margin, $threshold) {
        $alert_data = [
            'level' => $level,
            'current_margin' => $current_margin,
            'threshold' => $threshold,
            'timestamp' => time(),
            'suggestions' => $this->get_optimization_suggestions()
        ];
        
        // Store alert
        $alerts = get_option('vortex_cost_alerts', []);
        $alerts[] = $alert_data;
        
        // Keep only last 50 alerts
        if ( count($alerts) > 50 ) {
            $alerts = array_slice($alerts, -50);
        }
        
        update_option('vortex_cost_alerts', $alerts);
        
        // Send notification if critical
        if ( $level === 'critical' || $level === 'emergency' ) {
            $this->send_cost_alert_notification($alert_data);
        }
        
        // Log alert
        error_log("[VortexAI Cost] {$level} alert: Margin {$current_margin}% below threshold {$threshold}%");
    }
    
    /**
     * Send cost alert notification
     */
    private function send_cost_alert_notification($alert_data) {
        $admin_email = get_option('admin_email');
        $subject = 'VortexAI Cost Alert: ' . ucfirst($alert_data['level']) . ' Profit Margin';
        
        $message = "VortexAI Cost Alert\n\n";
        $message .= "Alert Level: " . ucfirst($alert_data['level']) . "\n";
        $message .= "Current Profit Margin: " . number_format($alert_data['current_margin'] * 100, 1) . "%\n";
        $message .= "Threshold: " . number_format($alert_data['threshold'] * 100, 1) . "%\n";
        $message .= "Target Margin: " . number_format($this->target_profit_margin * 100, 1) . "%\n\n";
        
        $message .= "Optimization Suggestions:\n";
        foreach ( $alert_data['suggestions'] as $suggestion ) {
            $message .= "- " . $suggestion . "\n";
        }
        
        $message .= "\nPlease review the VortexAI dashboard for detailed analysis.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get optimization suggestions
     */
    public function get_optimization_suggestions() {
        $suggestions = [];
        
        // Agent efficiency optimization
        $agent_efficiency = $this->analyze_agent_efficiency();
        if ( $agent_efficiency['needs_optimization'] ) {
            $suggestions[] = "Switch to more efficient agents: " . implode(', ', $agent_efficiency['recommended_agents']);
        }
        
        // Processing time optimization
        $processing_analysis = $this->analyze_processing_times();
        if ( $processing_analysis['needs_optimization'] ) {
            $suggestions[] = "Optimize processing time: Current average " . $processing_analysis['average_time'] . "s, target <" . $this->optimization_rules['processing_time']['threshold'] . "s";
        }
        
        // Quality-cost ratio optimization
        $quality_analysis = $this->analyze_quality_cost_ratio();
        if ( $quality_analysis['needs_optimization'] ) {
            $suggestions[] = "Improve quality-cost ratio: Current " . number_format($quality_analysis['current_ratio'], 2) . ", target >" . $this->optimization_rules['quality_cost_ratio']['threshold'];
        }
        
        // Batch processing optimization
        $batch_analysis = $this->analyze_batch_opportunities();
        if ( $batch_analysis['needs_optimization'] ) {
            $suggestions[] = "Use batch processing for " . $batch_analysis['batch_candidates'] . " pending requests";
        }
        
        // Revenue optimization
        $revenue_analysis = $this->analyze_revenue_opportunities();
        if ( $revenue_analysis['needs_optimization'] ) {
            $suggestions[] = "Increase revenue: " . implode(', ', $revenue_analysis['opportunities']);
        }
        
        return $suggestions;
    }
    
    /**
     * Analyze agent efficiency
     */
    private function analyze_agent_efficiency() {
        $agent_usage = $this->get_agent_usage_stats();
        $inefficient_agents = [];
        $recommended_agents = [];
        
        foreach ( $agent_usage as $agent_id => $usage ) {
            $efficiency = $usage['quality'] / $usage['cost'];
            
            if ( $efficiency < $this->optimization_rules['agent_efficiency']['threshold'] ) {
                $inefficient_agents[] = $agent_id;
            } else {
                $recommended_agents[] = $agent_id;
            }
        }
        
        return [
            'needs_optimization' => !empty($inefficient_agents),
            'inefficient_agents' => $inefficient_agents,
            'recommended_agents' => $recommended_agents
        ];
    }
    
    /**
     * Analyze processing times
     */
    private function analyze_processing_times() {
        $processing_times = get_option('vortex_processing_times', []);
        
        if ( empty($processing_times) ) {
            return ['needs_optimization' => false, 'average_time' => 0];
        }
        
        $average_time = array_sum($processing_times) / count($processing_times);
        
        return [
            'needs_optimization' => $average_time > $this->optimization_rules['processing_time']['threshold'],
            'average_time' => $average_time
        ];
    }
    
    /**
     * Analyze quality-cost ratio
     */
    private function analyze_quality_cost_ratio() {
        $quality_scores = get_option('vortex_quality_scores', []);
        $action_costs = $this->cost_data['cost_breakdown'];
        
        if ( empty($quality_scores) || empty($action_costs) ) {
            return ['needs_optimization' => false, 'current_ratio' => 0];
        }
        
        $total_quality = array_sum($quality_scores);
        $total_cost = array_sum($action_costs);
        
        $current_ratio = $total_quality / $total_cost;
        
        return [
            'needs_optimization' => $current_ratio < $this->optimization_rules['quality_cost_ratio']['threshold'],
            'current_ratio' => $current_ratio
        ];
    }
    
    /**
     * Analyze batch processing opportunities
     */
    private function analyze_batch_opportunities() {
        $pending_requests = get_option('vortex_pending_requests', []);
        
        $batch_candidates = 0;
        $grouped_requests = [];
        
        foreach ( $pending_requests as $request ) {
            $key = $request['action'] . '_' . $request['user_id'];
            $grouped_requests[$key] = ($grouped_requests[$key] ?? 0) + 1;
        }
        
        foreach ( $grouped_requests as $count ) {
            if ( $count >= $this->optimization_rules['batch_processing']['threshold'] ) {
                $batch_candidates += $count;
            }
        }
        
        return [
            'needs_optimization' => $batch_candidates > 0,
            'batch_candidates' => $batch_candidates
        ];
    }
    
    /**
     * Analyze revenue opportunities
     */
    private function analyze_revenue_opportunities() {
        $opportunities = [];
        
        // Check for underpriced actions
        foreach ( $this->revenue_streams as $action => $current_price ) {
            $action_costs = $this->cost_data['cost_breakdown'][$action] ?? 0;
            
            if ( $action_costs > 0 ) {
                $margin = ($current_price - $action_costs) / $current_price;
                
                if ( $margin < $this->target_profit_margin ) {
                    $suggested_price = $action_costs / (1 - $this->target_profit_margin);
                    $opportunities[] = "Increase {$action} price from \${$current_price} to \${$suggested_price}";
                }
            }
        }
        
        // Check for new revenue streams
        $popular_actions = $this->get_popular_actions();
        foreach ( $popular_actions as $action => $count ) {
            if ( !isset($this->revenue_streams[$action]) && $count > 10 ) {
                $opportunities[] = "Add premium tier for {$action} (used {$count} times)";
            }
        }
        
        return [
            'needs_optimization' => !empty($opportunities),
            'opportunities' => $opportunities
        ];
    }
    
    /**
     * Get agent usage statistics
     */
    private function get_agent_usage_stats() {
        $usage_stats = get_option('vortex_agent_usage_stats', []);
        
        // Merge with current agent cost data
        foreach ( $this->agent_costs as $agent_id => $cost_data ) {
            if ( !isset($usage_stats[$agent_id]) ) {
                $usage_stats[$agent_id] = [
                    'quality' => $cost_data['efficiency'],
                    'cost' => $cost_data['base_cost'],
                    'usage_count' => 0
                ];
            }
        }
        
        return $usage_stats;
    }
    
    /**
     * Get popular actions
     */
    private function get_popular_actions() {
        $action_counts = [];
        
        foreach ( $this->cost_data['cost_breakdown'] as $action => $cost ) {
            $action_counts[$action] = $this->get_action_usage_count($action);
        }
        
        arsort($action_counts);
        
        return $action_counts;
    }
    
    /**
     * Get action usage count
     */
    private function get_action_usage_count($action) {
        $usage_log = get_option('vortex_action_usage_log', []);
        return $usage_log[$action] ?? 0;
    }
    
    /**
     * Run cost analysis
     */
    public function run_cost_analysis() {
        $analysis = [
            'timestamp' => time(),
            'current_margin' => $this->cost_data['current_margin'],
            'target_margin' => $this->target_profit_margin,
            'total_costs' => $this->cost_data['total_costs'],
            'total_revenue' => $this->cost_data['total_revenue'],
            'suggestions' => $this->get_optimization_suggestions(),
            'agent_efficiency' => $this->analyze_agent_efficiency(),
            'processing_analysis' => $this->analyze_processing_times(),
            'quality_analysis' => $this->analyze_quality_cost_ratio(),
            'batch_analysis' => $this->analyze_batch_opportunities(),
            'revenue_analysis' => $this->analyze_revenue_opportunities()
        ];
        
        // Store analysis
        $analyses = get_option('vortex_cost_analyses', []);
        $analyses[] = $analysis;
        
        // Keep only last 100 analyses
        if ( count($analyses) > 100 ) {
            $analyses = array_slice($analyses, -100);
        }
        
        update_option('vortex_cost_analyses', $analyses);
        
        // Apply automatic optimizations if enabled
        if ( get_option('vortex_auto_optimize', false) ) {
            $this->apply_automatic_optimizations($analysis);
        }
        
        return $analysis;
    }
    
    /**
     * Apply automatic optimizations
     */
    private function apply_automatic_optimizations($analysis) {
        // Auto-switch to more efficient agents
        if ( $analysis['agent_efficiency']['needs_optimization'] ) {
            $this->auto_optimize_agents($analysis['agent_efficiency']);
        }
        
        // Auto-enable batch processing
        if ( $analysis['batch_analysis']['needs_optimization'] ) {
            $this->auto_enable_batch_processing();
        }
        
        // Auto-adjust quality settings
        if ( $analysis['quality_analysis']['needs_optimization'] ) {
            $this->auto_adjust_quality_settings($analysis['quality_analysis']);
        }
    }
    
    /**
     * Auto-optimize agents
     */
    private function auto_optimize_agents($efficiency_data) {
        $agent_preferences = get_option('vortex_agent_preferences', []);
        
        foreach ( $efficiency_data['recommended_agents'] as $agent_id ) {
            $agent_preferences[$agent_id] = ($agent_preferences[$agent_id] ?? 0) + 1;
        }
        
        foreach ( $efficiency_data['inefficient_agents'] as $agent_id ) {
            $agent_preferences[$agent_id] = max(0, ($agent_preferences[$agent_id] ?? 0) - 1);
        }
        
        update_option('vortex_agent_preferences', $agent_preferences);
        
        error_log('[VortexAI Cost] Auto-optimized agent preferences');
    }
    
    /**
     * Auto-enable batch processing
     */
    private function auto_enable_batch_processing() {
        update_option('vortex_batch_processing_enabled', true);
        error_log('[VortexAI Cost] Auto-enabled batch processing');
    }
    
    /**
     * Auto-adjust quality settings
     */
    private function auto_adjust_quality_settings($quality_analysis) {
        $quality_settings = get_option('vortex_quality_settings', []);
        
        // Slightly reduce quality to improve cost efficiency
        foreach ( $quality_settings as $action => $quality ) {
            if ( $quality > 0.7 ) {
                $quality_settings[$action] = max(0.7, $quality - 0.05);
            }
        }
        
        update_option('vortex_quality_settings', $quality_settings);
        
        error_log('[VortexAI Cost] Auto-adjusted quality settings');
    }
    
    /**
     * Generate optimization report
     */
    public function generate_optimization_report() {
        $report = [
            'date' => date('Y-m-d'),
            'period' => 'daily',
            'cost_summary' => $this->get_cost_summary(),
            'revenue_summary' => $this->get_revenue_summary(),
            'profit_margin' => $this->cost_data['current_margin'],
            'target_margin' => $this->target_profit_margin,
            'optimization_suggestions' => $this->get_optimization_suggestions(),
            'agent_performance' => $this->get_agent_performance_report(),
            'top_cost_actions' => $this->get_top_cost_actions(),
            'efficiency_trends' => $this->get_efficiency_trends()
        ];
        
        // Store report
        $reports = get_option('vortex_optimization_reports', []);
        $reports[] = $report;
        
        // Keep only last 30 reports
        if ( count($reports) > 30 ) {
            $reports = array_slice($reports, -30);
        }
        
        update_option('vortex_optimization_reports', $reports);
        
        return $report;
    }
    
    /**
     * Get cost summary
     */
    private function get_cost_summary() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $today_cost = $this->cost_data['daily_costs'][$today] ?? 0;
        $yesterday_cost = $this->cost_data['daily_costs'][$yesterday] ?? 0;
        
        return [
            'today' => $today_cost,
            'yesterday' => $yesterday_cost,
            'change' => $yesterday_cost > 0 ? (($today_cost - $yesterday_cost) / $yesterday_cost) * 100 : 0,
            'total' => $this->cost_data['total_costs']
        ];
    }
    
    /**
     * Get revenue summary
     */
    private function get_revenue_summary() {
        // This would track daily revenue
        $revenue_data = get_option('vortex_daily_revenue', []);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $today_revenue = $revenue_data[$today] ?? 0;
        $yesterday_revenue = $revenue_data[$yesterday] ?? 0;
        
        return [
            'today' => $today_revenue,
            'yesterday' => $yesterday_revenue,
            'change' => $yesterday_revenue > 0 ? (($today_revenue - $yesterday_revenue) / $yesterday_revenue) * 100 : 0,
            'total' => $this->cost_data['total_revenue']
        ];
    }
    
    /**
     * Get agent performance report
     */
    private function get_agent_performance_report() {
        $agent_stats = $this->get_agent_usage_stats();
        $performance = [];
        
        foreach ( $agent_stats as $agent_id => $stats ) {
            $efficiency = $stats['quality'] / $stats['cost'];
            $performance[$agent_id] = [
                'efficiency' => $efficiency,
                'quality' => $stats['quality'],
                'cost' => $stats['cost'],
                'usage_count' => $stats['usage_count'],
                'recommendation' => $efficiency >= $this->optimization_rules['agent_efficiency']['threshold'] ? 'optimal' : 'needs_optimization'
            ];
        }
        
        // Sort by efficiency
        uasort($performance, function($a, $b) {
            return $b['efficiency'] <=> $a['efficiency'];
        });
        
        return $performance;
    }
    
    /**
     * Get top cost actions
     */
    private function get_top_cost_actions() {
        $cost_breakdown = $this->cost_data['cost_breakdown'];
        arsort($cost_breakdown);
        
        return array_slice($cost_breakdown, 0, 10, true);
    }
    
    /**
     * Get efficiency trends
     */
    private function get_efficiency_trends() {
        $analyses = get_option('vortex_cost_analyses', []);
        
        if ( count($analyses) < 2 ) {
            return ['trend' => 'insufficient_data'];
        }
        
        $recent_analyses = array_slice($analyses, -7); // Last 7 analyses
        $margins = array_column($recent_analyses, 'current_margin');
        
        $trend = 'stable';
        if ( count($margins) >= 2 ) {
            $first_margin = reset($margins);
            $last_margin = end($margins);
            
            if ( $last_margin > $first_margin + 0.02 ) {
                $trend = 'improving';
            } elseif ( $last_margin < $first_margin - 0.02 ) {
                $trend = 'declining';
            }
        }
        
        return [
            'trend' => $trend,
            'margins' => $margins,
            'average_margin' => count($margins) > 0 ? array_sum($margins) / count($margins) : 0
        ];
    }
    
    /**
     * Save cost data
     */
    private function save_cost_data() {
        update_option('vortex_cost_data', $this->cost_data);
    }
    
    /**
     * Analyze action efficiency
     */
    public function analyze_action_efficiency($action, $result) {
        $cost = $result['cost'] ?? 0;
        $quality = $result['quality'] ?? 0;
        $processing_time = $result['processing_time'] ?? 0;
        
        // Track this action
        $this->track_cost($action, $cost);
        
        // Update processing times
        $processing_times = get_option('vortex_processing_times', []);
        $processing_times[] = $processing_time;
        
        // Keep only last 100 processing times
        if ( count($processing_times) > 100 ) {
            $processing_times = array_slice($processing_times, -100);
        }
        
        update_option('vortex_processing_times', $processing_times);
        
        // Update quality scores
        $quality_scores = get_option('vortex_quality_scores', []);
        $quality_scores[] = $quality;
        
        // Keep only last 100 quality scores
        if ( count($quality_scores) > 100 ) {
            $quality_scores = array_slice($quality_scores, -100);
        }
        
        update_option('vortex_quality_scores', $quality_scores);
        
        // Update action usage log
        $usage_log = get_option('vortex_action_usage_log', []);
        $usage_log[$action] = ($usage_log[$action] ?? 0) + 1;
        update_option('vortex_action_usage_log', $usage_log);
    }
    
    /**
     * Show cost alerts in admin
     */
    public function show_cost_alerts() {
        if ( !current_user_can('manage_options') ) return;
        
        $alerts = get_option('vortex_cost_alerts', []);
        $recent_alerts = array_slice($alerts, -5); // Last 5 alerts
        
        foreach ( $recent_alerts as $alert ) {
            if ( (time() - $alert['timestamp']) < 3600 ) { // Show alerts from last hour
                $class = 'notice-warning';
                if ( $alert['level'] === 'critical' ) $class = 'notice-error';
                if ( $alert['level'] === 'emergency' ) $class = 'notice-error';
                
                echo '<div class="notice ' . $class . ' is-dismissible">';
                echo '<p><strong>VortexAI Cost Alert:</strong> ';
                echo ucfirst($alert['level']) . ' profit margin: ';
                echo number_format($alert['current_margin'] * 100, 1) . '% ';
                echo '(Target: ' . number_format($this->target_profit_margin * 100, 1) . '%)</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_endpoints() {
        register_rest_route('vortex/v1', '/cost-data', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_cost_data'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
        
        register_rest_route('vortex/v1', '/optimization-suggestions', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_optimization_suggestions'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    /**
     * AJAX: Get cost data
     */
    public function ajax_get_cost_data() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if ( !current_user_can('manage_options') ) {
            wp_die('Unauthorized');
        }
        
        wp_send_json_success([
            'cost_data' => $this->cost_data,
            'target_margin' => $this->target_profit_margin,
            'cost_summary' => $this->get_cost_summary(),
            'revenue_summary' => $this->get_revenue_summary()
        ]);
    }
    
    /**
     * AJAX: Get optimization suggestions
     */
    public function ajax_get_optimization_suggestions() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if ( !current_user_can('manage_options') ) {
            wp_die('Unauthorized');
        }
        
        wp_send_json_success([
            'suggestions' => $this->get_optimization_suggestions(),
            'agent_efficiency' => $this->analyze_agent_efficiency(),
            'processing_analysis' => $this->analyze_processing_times(),
            'quality_analysis' => $this->analyze_quality_cost_ratio()
        ]);
    }
    
    /**
     * AJAX: Apply optimization
     */
    public function ajax_apply_optimization() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if ( !current_user_can('manage_options') ) {
            wp_die('Unauthorized');
        }
        
        $optimization_type = sanitize_text_field($_POST['optimization_type']);
        $parameters = $_POST['parameters'] ?? [];
        
        $result = $this->apply_optimization($optimization_type, $parameters);
        
        if ( $result ) {
            wp_send_json_success(['message' => 'Optimization applied successfully']);
        } else {
            wp_send_json_error('Failed to apply optimization');
        }
    }
    
    /**
     * Apply specific optimization
     */
    private function apply_optimization($type, $parameters) {
        switch ( $type ) {
            case 'agent_optimization':
                return $this->auto_optimize_agents($parameters);
                
            case 'batch_processing':
                return $this->auto_enable_batch_processing();
                
            case 'quality_adjustment':
                return $this->auto_adjust_quality_settings($parameters);
                
            default:
                return false;
        }
    }
    
    /**
     * REST: Get cost data
     */
    public function rest_get_cost_data($request) {
        return rest_ensure_response([
            'cost_data' => $this->cost_data,
            'target_margin' => $this->target_profit_margin,
            'optimization_suggestions' => $this->get_optimization_suggestions()
        ]);
    }
    
    /**
     * REST: Get optimization suggestions
     */
    public function rest_get_optimization_suggestions($request) {
        return rest_ensure_response([
            'suggestions' => $this->get_optimization_suggestions(),
            'analysis' => $this->run_cost_analysis()
        ]);
    }
    
    /**
     * Get current cost data
     */
    public function get_current_cost_data() {
        return $this->cost_data;
    }
    
    /**
     * Get current profit margin
     */
    public function get_current_profit_margin() {
        return $this->cost_data['current_margin'];
    }
    
    /**
     * Get target profit margin
     */
    public function get_target_profit_margin() {
        return $this->target_profit_margin;
    }
    
    /**
     * Set target profit margin
     */
    public function set_target_profit_margin($margin) {
        $this->target_profit_margin = max(0.1, min(0.95, $margin));
        update_option('vortex_target_profit_margin', $this->target_profit_margin);
    }
    
    /**
     * Check if profit margin is healthy
     */
    public function is_profit_margin_healthy() {
        return $this->cost_data['current_margin'] >= $this->target_profit_margin;
    }
    
    /**
     * Get cost forecast
     */
    public function get_cost_forecast($days = 7) {
        $daily_costs = $this->cost_data['daily_costs'];
        
        if ( count($daily_costs) < 3 ) {
            return ['error' => 'Insufficient data for forecast'];
        }
        
        $recent_costs = array_slice($daily_costs, -7, null, true);
        $average_daily_cost = array_sum($recent_costs) / count($recent_costs);
        
        $forecast = [];
        for ( $i = 1; $i <= $days; $i++ ) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $forecast[$date] = $average_daily_cost * (1 + (rand(-10, 10) / 100)); // Add small random variation
        }
        
        return $forecast;
    }
} 