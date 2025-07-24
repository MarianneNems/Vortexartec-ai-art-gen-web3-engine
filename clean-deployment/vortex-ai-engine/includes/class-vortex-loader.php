<?php
/**
 * Vortex AI Engine - Main Loader Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Vortex AI Engine Loader Class
 *
 * Handles plugin initialization, hooks, and core functionality.
 */
class Vortex_Loader {

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '3.0.0';

    /**
     * Plugin instance
     *
     * @var Vortex_Loader
     */
    private static $instance = null;

    /**
     * Plugin directory path
     *
     * @var string
     */
    private $plugin_path;

    /**
     * Plugin URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Get plugin instance
     *
     * @return Vortex_Loader
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
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
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
        // Core classes
        require_once $this->plugin_path . 'class-vortex-incentive-auditor.php';
        require_once $this->plugin_path . 'class-vortex-wallet-manager.php';
        require_once $this->plugin_path . 'class-vortex-accounting-system.php';
        require_once $this->plugin_path . 'class-vortex-conversion-system.php';
        require_once $this->plugin_path . 'class-vortex-integration-layer.php';
        require_once $this->plugin_path . 'class-vortex-frontend-interface.php';
        require_once $this->plugin_path . 'class-vortex-activation.php';

        // Admin classes
        if (is_admin()) {
            require_once $this->plugin_path . '../admin/class-vortex-admin.php';
        }

        // Public classes
        if (!is_admin()) {
            require_once $this->plugin_path . '../public/class-vortex-public.php';
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core systems
        Vortex_Incentive_Auditor::get_instance();
        Vortex_Wallet_Manager::get_instance();
        Vortex_Accounting_System::get_instance();
        Vortex_Conversion_System::get_instance();
        Vortex_Integration_Layer::get_instance();
        Vortex_Frontend_Interface::get_instance();
    }

    /**
     * Load plugin textdomain
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
        Vortex_Activation::activate();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        Vortex_Activation::deactivate();
    }

    /**
     * Get plugin path
     *
     * @return string
     */
    public function get_plugin_path() {
        return $this->plugin_path;
    }

    /**
     * Get plugin URL
     *
     * @return string
     */
    public function get_plugin_url() {
        return $this->plugin_url;
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version() {
        return self::VERSION;
    }
}

// Initialize the plugin
Vortex_Loader::get_instance(); 