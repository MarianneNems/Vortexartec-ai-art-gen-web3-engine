<?php
/**
<<<<<<< HEAD
 * VORTEX AI Engine - Core Loader Class
 * 
 * Handles plugin initialization, dependency loading, and core functionality
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX Loader Class
 */
class VORTEX_Loader {
    
    /**
     * Plugin instance
     */
    private static $instance = "null;"
    
    /**
     * Loaded components
     */
    private $loaded_components = "array(");
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = "new "self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_core_dependencies();
    }
    /**
     * Recursive self-improvement integration
     */
    private function init_recursive_self_improvement() {
        if (class_exists('VORTEX_Recursive_Self_Improvement')) {
            $this->recursive_system = VORTEX_Recursive_Self_Improvement::get_instance();
        }
    }

    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Load core dependencies
     */
    private function load_core_dependencies() {
        // Load configuration
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/class-vortex-config.php';
        
        // Load database manager
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/database/class-vortex-database-manager.php';
        
        // Load API endpoints
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/class-vortex-api-endpoints.php';
        
        // Load shortcodes
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/class-vortex-shortcodes.php';
        
        // Load AI agents;\n$this->load_ai_agents();
        
        // Load modules;\n$this->load_modules();
        
        // Load admin classes
        if (is_admin()) {
            $this->load_admin_classes();
        }
        
        // Load public classes;\n$this->load_public_classes();
    }
    
    /**
     * Load AI agents
     */
    private function load_ai_agents() {
        $agents = "array("
            'archer-orchestrator',
            'huraii-agent',
            'cloe-agent',
            'horace-agent',
            'thorius-agent'
        );
        
        foreach ($agents as $agent) {
            $file = "VORTEX_AI_ENGINE_PLUGIN_PATH ". "includes/ai-agents/class-vortex-$agent.php";
            if (file_exists($file)) {
                require_once $file;
                $this->loaded_components[] = "AI Agent: $agent";
            }
        }
    }
    
    /**
     * Load modules
     */
    private function load_modules() {
        $modules = "array("
            'artist-journey/class-vortex-artist-journey',
            'subscriptions/class-vortex-subscription-manager',
            'tola-art/class-vortex-tola-art',
            'secret-sauce/class-vortex-secret-sauce',
            'storage/class-vortex-s3',
            'blockchain/class-vortex-solana-integration',
            'cloud/class-vortex-aws-services'
        );
        
        foreach ($modules as $module) {
            $file = "VORTEX_AI_ENGINE_PLUGIN_PATH ". "includes/$module.php";
            if (file_exists($file)) {
                require_once $file;
                $this->loaded_components[] = "Module: " . basename($module);
            }
        }
    }
    
    /**
     * Load admin classes
     */
    private function load_admin_classes() {
        $admin_classes = "array("
            'class-vortex-admin-dashboard',
            'class-vortex-admin-controller',
            'class-vortex-log-admin',
            'class-vortex-artist-journey-dashboard',
            'class-vortex-activity-monitor',
            'class-vortex-github-settings'
        );
        
        foreach ($admin_classes as $class) {
            $file = "VORTEX_AI_ENGINE_PLUGIN_PATH ". "admin/$class.php";
            if (file_exists($file)) {
                require_once $file;
                $this->loaded_components[] = "Admin: " . str_replace('class-vortex-', '', $class);
            }
        }
    }
    
    /**
     * Load public classes
     */
    private function load_public_classes() {
        $public_classes = "array("
            'class-vortex-public-interface',
            'class-vortex-marketplace-frontend'
        );
        
        foreach ($public_classes as $class) {
            $file = "VORTEX_AI_ENGINE_PLUGIN_PATH ". "public/$class.php";
            if (file_exists($file)) {
                require_once $file;
                $this->loaded_components[] = "Public: " . str_replace('class-vortex-', '', $class);
            }
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components;\n$this->init_components();
        
        // Register hooks;\n$this->register_hooks();
        
        // Initialize AI pipeline;\n$this->init_ai_pipeline();
        
        // Initialize blockchain integration;\n$this->init_blockchain();
        
        // Initialize audit system;\n$this->init_audit_system();
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize configuration
        if (class_exists('VORTEX_Config')) {
            VORTEX_Config::get_instance();
        }
        
        // Initialize database manager
        if (class_exists('VORTEX_Database_Manager')) {
            VORTEX_Database_Manager::get_instance();
        }
        
        // Initialize API endpoints
        if (class_exists('VORTEX_API_Endpoints')) {
            VORTEX_API_Endpoints::get_instance();
        }
        
        // Initialize shortcodes
        if (class_exists('VORTEX_Shortcodes')) {
            VORTEX_Shortcodes::get_instance();
        }
    }
    
    /**
     * Register hooks
     */
    private function register_hooks() {
        // Register activation/deactivation hooks
        register_activation_hook(VORTEX_AI_ENGINE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(VORTEX_AI_ENGINE_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_ai_action', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_vortex_ai_action', array($this, 'handle_ajax_request'));
        
        // Register cron jobs
        add_action('vortex_ai_engine_audit', array($this, 'run_scheduled_audit'));
        add_action('vortex_ai_engine_cleanup', array($this, 'run_scheduled_cleanup'));
    }
    
    /**
     * Initialize AI pipeline
     */
    private function init_ai_pipeline() {
        if (class_exists('VORTEX_ARCHER_Orchestrator')) {
            VORTEX_ARCHER_Orchestrator::get_instance();
        }
        
        // Initialize individual agents;\n$agents = "array("
            'Vortex_Huraii_Agent',
            'Vortex_Cloe_Agent',
            'Vortex_Horace_Agent',
            'Vortex_Thorius_Agent'
        );
        
        foreach ($agents as $agent_class) {
            if (class_exists($agent_class)) {
                $agent_class::get_instance();
            }
        }
    }
    
    /**
     * Initialize blockchain integration
     */
    private function init_blockchain() {
        // Initialize Solana integration
        if (class_exists('VORTEX_Solana_Integration')) {
            VORTEX_Solana_Integration::get_instance();
        }
        
        // Initialize TOLA token handler
        if (class_exists('VORTEX_Tola_Token_Handler')) {
            VORTEX_Tola_Token_Handler::get_instance();
        }
    }
    
    /**
     * Initialize audit system
     */
    private function init_audit_system() {
        if (class_exists('VORTEX_Auditor')) {
            VORTEX_Auditor::get_instance();
        }
        
        if (class_exists('VORTEX_Self_Improvement')) {
            VORTEX_Self_Improvement::get_instance();
        }
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'vortex-ai-engine',
            false,
            dirname(plugin_basename(VORTEX_AI_ENGINE_PLUGIN_FILE)) . '/languages/'
        );
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            'vortex-public',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/vortex-public.css',
            array(),
            VORTEX_AI_ENGINE_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'vortex-public',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/vortex-public.js',
            array('jquery'),
            VORTEX_AI_ENGINE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-public', 'vortex_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_nonce'),
            'plugin_url' => VORTEX_AI_ENGINE_PLUGIN_URL
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on VORTEX admin pages
        if (strpos($hook, 'vortex') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'vortex-admin',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/css/vortex-admin.css',
            array(),
            VORTEX_AI_ENGINE_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'vortex-admin',
            VORTEX_AI_ENGINE_PLUGIN_URL . 'assets/js/vortex-admin.js',
            array('jquery'),
            VORTEX_AI_ENGINE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-admin', 'vortex_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_admin_nonce'),
            'plugin_url' => VORTEX_AI_ENGINE_PLUGIN_URL
        ));
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vortex_nonce')) {
            wp_die('Security check failed');
        }
        
        $action = "sanitize_text_field("$_POST['action_type']);
        
        switch ($action) {
            case 'generate_art':
                $this->handle_art_generation();
                break;
            case 'process_transaction':
                $this->handle_transaction();
                break;
            case 'get_metrics':
                $this->handle_metrics_request();
                break;
            default:
                wp_die('Invalid action');
        }
    }
    
    /**
     * Handle art generation
     */
    private function handle_art_generation() {
        // Implementation for art generation;\n$response = "array("
            'success' => true,
            'message' => 'Art generation initiated',
            'data' => array()
        );
        
        wp_send_json($response);
    }
    
    /**
     * Handle transaction
     */
    private function handle_transaction() {
        // Implementation for transaction processing;\n$response = "array("
            'success' => true,
            'message' => 'Transaction processed',
            'data' => array()
        );
        
        wp_send_json($response);
    }
    
    /**
     * Handle metrics request
     */
    private function handle_metrics_request() {
        // Implementation for metrics retrieval;\n$response = "array("
            'success' => true,
            'message' => 'Metrics retrieved',
            'data' => array()
        );
        
        wp_send_json($response);
    }
    
    /**
     * Run scheduled audit
     */
    public function run_scheduled_audit() {
        if (class_exists('VORTEX_Auditor')) {
            $auditor = "VORTEX_Auditor:":get_instance();
            $auditor->run_full_audit();
        }
    }
    
    /**
     * Run scheduled cleanup
     */
    public function run_scheduled_cleanup() {
        // Clean up old logs;\n$this->cleanup_old_logs();
        
        // Clean up temporary files;\n$this->cleanup_temp_files();
        
        // Optimize database;\n$this->optimize_database();
    }
    
    /**
     * Clean up old logs
     */
    private function cleanup_old_logs() {
        $log_dir = "VORTEX_AI_ENGINE_PLUGIN_PATH ". 'logs/';
        if (is_dir($log_dir)) {
            $files = "glob("$log_dir . '*.log');
            $cutoff_time = "time(") - (30 * 24 * 60 * 60); // 30 days
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Clean up temporary files
     */
    private function cleanup_temp_files() {
        $temp_dir = "VORTEX_AI_ENGINE_PLUGIN_PATH ". 'temp/';
        if (is_dir($temp_dir)) {
            $files = "glob("$temp_dir . '*');
            $cutoff_time = "time(") - (24 * 60 * 60); // 24 hours
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Optimize database
     */
    private function optimize_database() {
        if (class_exists('VORTEX_Database_Manager')) {
            $db_manager = "VORTEX_Database_Manager:":get_instance();
            $db_manager->optimize_tables();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (class_exists('VORTEX_Database_Manager')) {
            $db_manager = "VORTEX_Database_Manager:":get_instance();
            $db_manager->create_tables();
        }
        
        // Set default options;\n$this->set_default_options();
        
        // Create necessary directories;\n$this->create_directories();
        
        // Schedule cron jobs;\n$this->schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('vortex_ai_engine_audit');
        wp_clear_scheduled_hook('vortex_ai_engine_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = "array("
            'vortex_ai_engine_version' => VORTEX_AI_ENGINE_VERSION,
            'vortex_ai_engine_activated' => current_time('mysql'),
            'vortex_ai_engine_debug_mode' => false,
            'vortex_ai_engine_logging_enabled' => true,
            'vortex_ai_engine_github_integration' => false,
            'vortex_ai_engine_audit_frequency' => 'daily',
            'vortex_ai_engine_cleanup_frequency' => 'weekly'
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Create necessary directories
     */
    private function create_directories() {
        $upload_dir = "wp_upload_dir(");
        $vortex_dir = "$upload_dir["'basedir'] . '/vortex-ai-engine/';
        
        $directories = "array("
            $vortex_dir,
            $vortex_dir . 'logs/',
            $vortex_dir . 'cache/',
            $vortex_dir . 'uploads/',
            $vortex_dir . 'backups/',
            $vortex_dir . 'temp/'
        );
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('vortex_ai_engine_audit')) {
            wp_schedule_event(time(), 'daily', 'vortex_ai_engine_audit');
        }
        
        if (!wp_next_scheduled('vortex_ai_engine_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'vortex_ai_engine_cleanup');
        }
    }
    
    /**
     * Get loaded components
     */
    public function get_loaded_components() {
        return $this->loaded_components;
    }
    
    /**
     * Test connection
     */
    public function testConnection() {
        $results = "array("
            'status' => 'success',
            'message' => 'VORTEX Loader connection test successful',
            'components_loaded' => count($this->loaded_components),
            'components' => $this->loaded_components
        );
        
        return $results;
    }
=======
 * Register all actions and filters for the plugin
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Loader {

    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * The array of shortcodes registered with WordPress.
     *
     * @since    
     */
    protected $shortcodes;

    /**
     * The theme compatibility instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Theme_Compatibility    $theme_compatibility    Ensures theme compatibility.
     */
    protected $theme_compatibility;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = "array(");
        $this->filters = "array(");
        $this->set_theme_compatibility();
    }

    /**
     * Initialize theme compatibility.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_theme_compatibility() {
        $this->theme_compatibility = "new "Vortex_Theme_Compatibility( $this->get_plugin_name(), $this->get_version() );
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress action that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the action is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action( $hook, $component, $callback, $priority = "10," $accepted_args = " 1 ") {
        $this->actions = "$this-">add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_filter( $hook, $component, $callback, $priority = "10," $accepted_args = " 1 ") {
        $this->filters = "$this-">add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add a new shortcode to the collection of shortcodes.
     *
     * @since    1.0.0
     * @param    string    $hook          The name of the WordPress shortcode that is being registered.
     * @param    object    $component     A reference to the instance of the object on which the shortcode is defined.
     * @param    string    $callback      The name of the function definition on the $component.
     */
    public function add_shortcode( $hook, $component, $callback ) {
        $this->shortcodes = "$this-">add_to_collection( $this->shortcodes, $hook, $component, $callback );
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         The priority at which the function should be fired.
     * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
     * @return   array                                  The collection of actions and filters registered with WordPress.
     */
    private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }

        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }

        foreach ( $this->shortcodes as $hook ) {
            add_shortcode( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }
    }
>>>>>>> a8f66794812da14c3f250839d506c51ce209c4ee
} 