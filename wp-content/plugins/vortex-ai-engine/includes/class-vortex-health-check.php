<?php
/**
 * Vortex AI Engine - Health Check Endpoint
 * 
 * Provides health check functionality for monitoring and CI/CD pipelines
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Health_Check {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Health check results
     */
    private $health_results = [];
    
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
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register REST endpoint
        add_action('rest_api_init', [$this, 'register_health_check_endpoint']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_health_check_menu']);
        
        // Add health check to admin dashboard
        add_action('vortex_admin_dashboard', [$this, 'add_health_check_widget']);
    }
    
    /**
     * Register health check REST endpoint
     */
    public function register_health_check_endpoint() {
        register_rest_route('vortex/v1', '/health-check', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check_callback'],
            'permission_callback' => [$this, 'health_check_permission'],
            'args' => [
                'detailed' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Include detailed health information'
                ]
            ]
        ]);
    }
    
    /**
     * Health check permission callback
     */
    public function health_check_permission($request) {
        // Allow public access for basic health check
        $detailed = $request->get_param('detailed');
        
        if ($detailed) {
            // Require authentication for detailed health check
            return current_user_can('manage_options');
        }
        
        return true;
    }
    
    /**
     * Health check callback
     */
    public function health_check_callback($request) {
        $detailed = $request->get_param('detailed');
        
        $this->run_health_checks($detailed);
        
        $response_data = [
            'status' => $this->get_overall_status(),
            'timestamp' => current_time('mysql'),
            'version' => VORTEX_AI_ENGINE_VERSION,
            'environment' => $this->get_environment(),
            'checks' => $this->health_results
        ];
        
        $status_code = $this->get_overall_status() === 'healthy' ? 200 : 503;
        
        return new WP_REST_Response($response_data, $status_code);
    }
    
    /**
     * Run health checks
     */
    private function run_health_checks($detailed = false) {
        $this->health_results = [];
        
        // Basic health checks (always run)
        $this->check_plugin_status();
        $this->check_database_connectivity();
        $this->check_core_classes();
        
        if ($detailed) {
            // Detailed health checks (require authentication)
            $this->check_ai_agents();
            $this->check_aws_integration();
            $this->check_agreement_policy();
            $this->check_file_permissions();
            $this->check_performance_metrics();
            $this->check_error_logs();
        }
    }
    
    /**
     * Check plugin status
     */
    private function check_plugin_status() {
        $status = 'healthy';
        $message = 'Plugin is active and functioning';
        $details = [];
        
        // Check if plugin is active
        if (!is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
            $status = 'unhealthy';
            $message = 'Plugin is not active';
        }
        
        // Check plugin version
        $plugin_data = get_plugin_data(VORTEX_AI_ENGINE_PLUGIN_PATH . 'vortex-ai-engine.php');
        $details['version'] = $plugin_data['Version'] ?? 'unknown';
        
        // Check if main class exists
        if (!class_exists('Vortex_AI_Engine')) {
            $status = 'unhealthy';
            $message = 'Main plugin class not found';
        }
        
        $this->health_results['plugin_status'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check database connectivity
     */
    private function check_database_connectivity() {
        global $wpdb;
        
        $status = 'healthy';
        $message = 'Database connection successful';
        $details = [];
        
        // Test database connection
        $result = $wpdb->get_var("SELECT 1");
        if ($result !== '1') {
            $status = 'unhealthy';
            $message = 'Database connection failed: ' . $wpdb->last_error;
        }
        
        // Check Vortex tables
        $tables = [
            $wpdb->prefix . 'vortex_activity_logs',
            $wpdb->prefix . 'vortex_artist_journey',
            $wpdb->prefix . 'vortex_agreements'
        ];
        
        $missing_tables = [];
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if (!$exists) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            $status = 'warning';
            $message = 'Some Vortex tables are missing';
            $details['missing_tables'] = $missing_tables;
        }
        
        $details['database_name'] = DB_NAME;
        $details['database_host'] = DB_HOST;
        
        $this->health_results['database_connectivity'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check core classes
     */
    private function check_core_classes() {
        $status = 'healthy';
        $message = 'All core classes loaded';
        $details = [];
        
        $core_classes = [
            'Vortex_AI_Engine',
            'Vortex_Agreement_Policy',
            'Vortex_Activity_Logger',
            'Vortex_Config',
            'Vortex_Database_Manager'
        ];
        
        $missing_classes = [];
        foreach ($core_classes as $class) {
            if (!class_exists($class)) {
                $missing_classes[] = $class;
            }
        }
        
        if (!empty($missing_classes)) {
            $status = 'unhealthy';
            $message = 'Some core classes are missing';
            $details['missing_classes'] = $missing_classes;
        }
        
        $this->health_results['core_classes'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check AI agents
     */
    private function check_ai_agents() {
        $status = 'healthy';
        $message = 'All AI agents are available';
        $details = [];
        
        $agents = [
            'Archer Orchestrator' => 'VORTEX_ARCHER_Orchestrator',
            'Huraii Agent' => 'Vortex_Huraii_Agent',
            'Cloe Agent' => 'Vortex_Cloe_Agent',
            'Horace Agent' => 'Vortex_Horace_Agent',
            'Thorius Agent' => 'Vortex_Thorius_Agent'
        ];
        
        $unavailable_agents = [];
        foreach ($agents as $name => $class) {
            if (!class_exists($class)) {
                $unavailable_agents[] = $name;
            }
        }
        
        if (!empty($unavailable_agents)) {
            $status = 'warning';
            $message = 'Some AI agents are unavailable';
            $details['unavailable_agents'] = $unavailable_agents;
        }
        
        $this->health_results['ai_agents'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check AWS integration
     */
    private function check_aws_integration() {
        $status = 'healthy';
        $message = 'AWS integration configured';
        $details = [];
        
        // Check AWS constants
        $aws_constants = ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION'];
        $missing_constants = [];
        
        foreach ($aws_constants as $constant) {
            if (!defined($constant) || empty(constant($constant))) {
                $missing_constants[] = $constant;
            }
        }
        
        if (!empty($missing_constants)) {
            $status = 'warning';
            $message = 'AWS not fully configured';
            $details['missing_constants'] = $missing_constants;
        }
        
        // Test AWS SDK if available
        if (class_exists('Aws\Sqs\SqsClient') && empty($missing_constants)) {
            try {
                $sqs = new Aws\Sqs\SqsClient([
                    'version' => 'latest',
                    'region'  => AWS_DEFAULT_REGION,
                    'credentials' => [
                        'key'    => AWS_ACCESS_KEY_ID,
                        'secret' => AWS_SECRET_ACCESS_KEY,
                    ]
                ]);
                
                $result = $sqs->listQueues();
                $details['sqs_connection'] = 'successful';
            } catch (Exception $e) {
                $status = 'warning';
                $message = 'AWS SQS connection failed';
                $details['sqs_error'] = $e->getMessage();
            }
        }
        
        $this->health_results['aws_integration'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check agreement policy
     */
    private function check_agreement_policy() {
        $status = 'healthy';
        $message = 'Agreement policy functioning';
        $details = [];
        
        if (class_exists('Vortex_Agreement_Policy')) {
            $agreement = Vortex_Agreement_Policy::get_instance();
            
            // Check agreement assets
            $js_file = VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/agreement.js';
            $css_file = VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/agreement.css';
            
            $js_response = wp_remote_get($js_file);
            $css_response = wp_remote_get($css_file);
            
            if (is_wp_error($js_response) || wp_remote_retrieve_response_code($js_response) !== 200) {
                $status = 'warning';
                $message = 'Agreement assets not accessible';
                $details['js_file'] = 'not accessible';
            }
            
            if (is_wp_error($css_response) || wp_remote_retrieve_response_code($css_response) !== 200) {
                $status = 'warning';
                $message = 'Agreement assets not accessible';
                $details['css_file'] = 'not accessible';
            }
        } else {
            $status = 'unhealthy';
            $message = 'Agreement policy class not found';
        }
        
        $this->health_results['agreement_policy'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $status = 'healthy';
        $message = 'File permissions are correct';
        $details = [];
        
        $directories = [
            'assets/css',
            'assets/js',
            'includes',
            'admin',
            'public'
        ];
        
        $permission_issues = [];
        foreach ($directories as $dir) {
            $full_path = VORTEX_AI_ENGINE_PLUGIN_PATH . $dir;
            if (!is_readable($full_path)) {
                $permission_issues[] = $dir;
            }
        }
        
        if (!empty($permission_issues)) {
            $status = 'warning';
            $message = 'Some directories have permission issues';
            $details['permission_issues'] = $permission_issues;
        }
        
        $this->health_results['file_permissions'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check performance metrics
     */
    private function check_performance_metrics() {
        $status = 'healthy';
        $message = 'Performance metrics are normal';
        $details = [];
        
        // Check memory usage
        $memory_limit = ini_get('memory_limit');
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        $details['memory_limit'] = $memory_limit;
        $details['memory_usage'] = $this->format_bytes($memory_usage);
        $details['memory_peak'] = $this->format_bytes($memory_peak);
        
        // Check execution time
        $execution_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $details['execution_time'] = round($execution_time, 4) . 's';
        
        if ($execution_time > 5) {
            $status = 'warning';
            $message = 'Execution time is high';
        }
        
        $this->health_results['performance_metrics'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Check error logs
     */
    private function check_error_logs() {
        $status = 'healthy';
        $message = 'No recent errors found';
        $details = [];
        
        // Check WordPress debug log
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_file = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($log_file)) {
                $log_size = filesize($log_file);
                $details['debug_log_size'] = $this->format_bytes($log_size);
                
                if ($log_size > 10 * 1024 * 1024) { // 10MB
                    $status = 'warning';
                    $message = 'Debug log is large';
                }
            }
        }
        
        $this->health_results['error_logs'] = [
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Get overall health status
     */
    private function get_overall_status() {
        $statuses = array_column($this->health_results, 'status');
        
        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        } elseif (in_array('warning', $statuses)) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }
    
    /**
     * Get environment information
     */
    private function get_environment() {
        return [
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    private function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Add health check admin menu
     */
    public function add_health_check_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Health Check',
            'Health Check',
            'manage_options',
            'vortex-health-check',
            [$this, 'render_health_check_page']
        );
    }
    
    /**
     * Render health check admin page
     */
    public function render_health_check_page() {
        $this->run_health_checks(true);
        
        ?>
        <div class="wrap">
            <h1>🤖 Vortex AI Engine - Health Check</h1>
            
            <div class="health-status-overview">
                <h2>Overall Status: 
                    <span class="status-<?php echo $this->get_overall_status(); ?>">
                        <?php echo ucfirst($this->get_overall_status()); ?>
                    </span>
                </h2>
            </div>
            
            <div class="health-checks">
                <?php foreach ($this->health_results as $check_name => $check_data): ?>
                    <div class="health-check-item">
                        <h3><?php echo ucwords(str_replace('_', ' ', $check_name)); ?></h3>
                        <div class="check-status status-<?php echo $check_data['status']; ?>">
                            <?php echo $check_data['message']; ?>
                        </div>
                        
                        <?php if (!empty($check_data['details'])): ?>
                            <div class="check-details">
                                <h4>Details:</h4>
                                <ul>
                                    <?php foreach ($check_data['details'] as $key => $value): ?>
                                        <li><strong><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</strong> 
                                            <?php echo is_array($value) ? implode(', ', $value) : $value; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="health-actions">
                <button class="button button-primary" onclick="refreshHealthCheck()">Refresh Health Check</button>
                <button class="button" onclick="exportHealthReport()">Export Report</button>
            </div>
        </div>
        
        <style>
            .health-check-item {
                background: #fff;
                border: 1px solid #ddd;
                margin: 10px 0;
                padding: 15px;
                border-radius: 5px;
            }
            
            .check-status {
                font-weight: bold;
                padding: 5px 10px;
                border-radius: 3px;
                display: inline-block;
                margin: 10px 0;
            }
            
            .status-healthy { background: #d4edda; color: #155724; }
            .status-warning { background: #fff3cd; color: #856404; }
            .status-unhealthy { background: #f8d7da; color: #721c24; }
            
            .check-details {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 3px;
                margin-top: 10px;
            }
            
            .check-details ul {
                margin: 0;
                padding-left: 20px;
            }
            
            .health-actions {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 5px;
            }
        </style>
        
        <script>
            function refreshHealthCheck() {
                location.reload();
            }
            
            function exportHealthReport() {
                const data = <?php echo json_encode($this->health_results); ?>;
                const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'vortex-health-report.json';
                a.click();
                URL.revokeObjectURL(url);
            }
        </script>
        <?php
    }
    
    /**
     * Add health check widget to admin dashboard
     */
    public function add_health_check_widget() {
        $this->run_health_checks(false);
        $overall_status = $this->get_overall_status();
        
        ?>
        <div class="health-check-widget">
            <h3>System Health</h3>
            <div class="health-status status-<?php echo $overall_status; ?>">
                <?php echo ucfirst($overall_status); ?>
            </div>
            <p><a href="<?php echo admin_url('admin.php?page=vortex-health-check'); ?>">View Details</a></p>
        </div>
        <?php
    }
}

// Initialize the health check
Vortex_Health_Check::get_instance(); 