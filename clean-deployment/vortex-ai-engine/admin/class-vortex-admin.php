<?php
/**
 * Vortex AI Engine - Admin Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Admin Class
 *
 * Handles WordPress admin interface.
 */
class Vortex_Admin {

    /**
     * Instance
     *
     * @var Vortex_Admin
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Vortex_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Vortex AI Engine',
            'Vortex AI',
            'manage_options',
            'vortex-ai-engine',
            array($this, 'admin_page'),
            'dashicons-art',
            30
        );

        add_submenu_page(
            'vortex-ai-engine',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'vortex-ai-engine',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'vortex-ai-engine',
            'Incentives',
            'Incentives',
            'manage_options',
            'vortex-incentives',
            array($this, 'incentives_page')
        );

        add_submenu_page(
            'vortex-ai-engine',
            'Settings',
            'Settings',
            'manage_options',
            'vortex-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'vortex-ai-engine') !== false) {
            wp_enqueue_script(
                'vortex-admin',
                plugin_dir_url(__FILE__) . '../assets/js/admin.js',
                array('jquery'),
                '3.0.0',
                true
            );
        }
    }

    /**
     * Admin page
     */
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>Vortex AI Engine Dashboard</h1>';
        echo '<p>Welcome to the Vortex AI Engine administration panel.</p>';
        echo '</div>';
    }

    /**
     * Incentives page
     */
    public function incentives_page() {
        echo '<div class="wrap">';
        echo '<h1>Incentive Management</h1>';
        echo '<p>Manage TOLA token incentives and distributions.</p>';
        echo '</div>';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>Vortex AI Engine Settings</h1>';
        echo '<p>Configure plugin settings and options.</p>';
        echo '</div>';
    }
} 