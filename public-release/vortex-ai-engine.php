<?php
/**
 * Plugin Name: VORTEX AI Engine
 * Plugin URI: https://vortexartec.com
 * Description: AI-powered marketplace engine for WordPress featuring advanced AI agents, blockchain integration, and automated art generation.
 * Version: 2.2.0
 * Author: Marianne Nems - VORTEX ARTEC
 * Author URI: https://vortexartec.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vortex-ai-engine
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 * @author Marianne Nems - VORTEX ARTEC
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_AI_ENGINE_VERSION', '2.2.0');
define('VORTEX_AI_ENGINE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_AI_ENGINE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VORTEX_AI_ENGINE_PLUGIN_FILE', __FILE__);

/**
 * Main VORTEX AI Engine Class
 */
class VORTEX_AI_Engine {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
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
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load core classes
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/class-vortex-loader.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/class-vortex-config.php';
        
        // Load AI agents
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-archer-orchestrator.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-huraii-agent.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-cloe-agent.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-horace-agent.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-thorius-agent.php';
        
        // Load database classes
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/database/class-vortex-database-manager.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/database/class-vortex-artist-journey-database.php';
        
        // Load admin classes
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/class-vortex-admin.php';
        
        // Load public classes
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/class-vortex-public.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize AI agents
        $this->init_ai_agents();
        
        // Initialize blockchain integration
        $this->init_blockchain();
        
        // Initialize audit system
        $this->init_audit_system();
        
        // Initialize admin interface
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize public interface
        $this->init_public();
    }
    
    /**
     * Initialize AI agents
     */
    private function init_ai_agents() {
        // Initialize ARCHER orchestrator
        VORTEX_ARCHER_Orchestrator::get_instance();
        
        // Initialize individual agents
        Vortex_Huraii_Agent::get_instance();
        Vortex_Cloe_Agent::get_instance();
        Vortex_Horace_Agent::get_instance();
        Vortex_Thorius_Agent::get_instance();
    }
    
    /**
     * Initialize blockchain integration
     */
    private function init_blockchain() {
        // Initialize Solana integration
        // Initialize TOLA token integration
        // Initialize smart contracts
    }
    
    /**
     * Initialize audit system
     */
    private function init_audit_system() {
        // Initialize real-time logging
        // Initialize GitHub integration
        // Initialize monitoring dashboard
    }
    
    /**
     * Initialize admin interface
     */
    private function init_admin() {
        VORTEX_Admin::get_instance();
    }
    
    /**
     * Initialize public interface
     */
    private function init_public() {
        VORTEX_Public::get_instance();
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'vortex-ai-engine',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Create necessary directories
        $this->create_directories();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('vortex_ai_engine_audit');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        $db_manager = new VORTEX_Database_Manager();
        $db_manager->create_tables();
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'vortex_ai_engine_version' => VORTEX_AI_ENGINE_VERSION,
            'vortex_ai_engine_activated' => current_time('mysql'),
            'vortex_ai_engine_debug_mode' => false,
            'vortex_ai_engine_logging_enabled' => true,
            'vortex_ai_engine_github_integration' => false
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
        $upload_dir = wp_upload_dir();
        $vortex_dir = $upload_dir['basedir'] . '/vortex-ai-engine/';
        
        $directories = array(
            $vortex_dir,
            $vortex_dir . 'logs/',
            $vortex_dir . 'cache/',
            $vortex_dir . 'uploads/',
            $vortex_dir . 'backups/'
        );
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function vortex_ai_engine_init() {
    return VORTEX_AI_Engine::get_instance();
}

// Start the plugin
vortex_ai_engine_init(); 