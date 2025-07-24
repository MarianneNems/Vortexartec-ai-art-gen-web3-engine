<?php
/**
 * Vortex AI Engine - Public Class
 *
 * @package VortexAIEngine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vortex Public Class
 *
 * Handles public-facing functionality.
 */
class Vortex_Public {

    /**
     * Instance
     *
     * @var Vortex_Public
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Vortex_Public
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_footer_content'));
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'vortex-public',
            plugin_dir_url(__FILE__) . '../assets/js/public.js',
            array('jquery'),
            '3.0.0',
            true
        );
    }

    /**
     * Add footer content
     */
    public function add_footer_content() {
        if (is_user_logged_in()) {
            echo '<div id="vortex-footer-info" style="display:none;">';
            echo '<p>Vortex AI Engine v3.0.0</p>';
            echo '</div>';
        }
    }
} 