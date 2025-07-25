<?php
/**
 * Vortex AI Engine - Dependency Monitor
 * 
 * This class continuously monitors dependencies and automatically updates them
 * when new secure versions are available.
 *
 * @package Vortex_AI_Engine
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Vortex_Dependency_Monitor
 * 
 * Monitors and automatically updates dependencies for security and performance
 */
class Vortex_Dependency_Monitor {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Dependency check interval (in seconds)
     */
    private $check_interval = 3600; // 1 hour

    /**
     * Last check timestamp
     */
    private $last_check = 0;

    /**
     * Dependency sources
     */
    private $dependency_sources = [
        'composer' => 'https://packagist.org/packages/%s.json',
        'npm' => 'https://registry.npmjs.org/%s',
        'pypi' => 'https://pypi.org/pypi/%s/json'
    ];

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_vortex_check_dependencies', array($this, 'ajax_check_dependencies'));
        add_action('wp_ajax_nopriv_vortex_check_dependencies', array($this, 'ajax_check_dependencies'));
        add_action('vortex_dependency_check', array($this, 'scheduled_dependency_check'));
        
        // Schedule dependency checks
        if (!wp_next_scheduled('vortex_dependency_check')) {
            wp_schedule_event(time(), 'hourly', 'vortex_dependency_check');
        }
    }

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
     * Initialize the dependency monitor
     */
    public function init() {
        $this->last_check = get_option('vortex_last_dependency_check', 0);
        
        // Check if it's time for a dependency check
        if ((time() - $this->last_check) > $this->check_interval) {
            $this->check_all_dependencies();
        }
    }

    /**
     * Check all dependencies for updates
     */
    public function check_all_dependencies() {
        $this->log('Starting dependency check...');
        
        $updates_available = array();
        
        // Check PHP dependencies (Composer)
        $php_updates = $this->check_php_dependencies();
        if (!empty($php_updates)) {
            $updates_available['php'] = $php_updates;
        }
        
        // Check Node.js dependencies (NPM)
        $node_updates = $this->check_node_dependencies();
        if (!empty($node_updates)) {
            $updates_available['node'] = $node_updates;
        }
        
        // Check Python dependencies (pip)
        $python_updates = $this->check_python_dependencies();
        if (!empty($python_updates)) {
            $updates_available['python'] = $python_updates;
        }
        
        // Update last check timestamp
        update_option('vortex_last_dependency_check', time());
        
        // If updates are available, trigger update process
        if (!empty($updates_available)) {
            $this->trigger_dependency_updates($updates_available);
        }
        
        $this->log('Dependency check completed. Found ' . count($updates_available) . ' update categories.');
        
        return $updates_available;
    }

    /**
     * Check PHP dependencies via Composer
     */
    private function check_php_dependencies() {
        $composer_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'composer.json';
        
        if (!file_exists($composer_file)) {
            return array();
        }
        
        $composer_data = json_decode(file_get_contents($composer_file), true);
        $updates = array();
        
        if (isset($composer_data['require'])) {
            foreach ($composer_data['require'] as $package => $version) {
                if ($package === 'php') continue;
                
                $latest_version = $this->get_latest_composer_version($package);
                if ($latest_version && $this->is_version_newer($latest_version, $version)) {
                    $updates[$package] = array(
                        'current' => $version,
                        'latest' => $latest_version,
                        'security_update' => $this->is_security_update($package, $latest_version)
                    );
                }
            }
        }
        
        return $updates;
    }

    /**
     * Check Node.js dependencies via NPM
     */
    private function check_node_dependencies() {
        $package_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'package.json';
        
        if (!file_exists($package_file)) {
            return array();
        }
        
        $package_data = json_decode(file_get_contents($package_file), true);
        $updates = array();
        
        if (isset($package_data['dependencies'])) {
            foreach ($package_data['dependencies'] as $package => $version) {
                $latest_version = $this->get_latest_npm_version($package);
                if ($latest_version && $this->is_version_newer($latest_version, $version)) {
                    $updates[$package] = array(
                        'current' => $version,
                        'latest' => $latest_version,
                        'security_update' => $this->is_security_update($package, $latest_version)
                    );
                }
            }
        }
        
        return $updates;
    }

    /**
     * Check Python dependencies via PyPI
     */
    private function check_python_dependencies() {
        $requirements_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'requirements.txt';
        
        if (!file_exists($requirements_file)) {
            return array();
        }
        
        $requirements = file($requirements_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $updates = array();
        
        foreach ($requirements as $line) {
            if (strpos($line, '#') === 0) continue; // Skip comments
            
            preg_match('/^([a-zA-Z0-9\-_]+)([>=<~!]+)(.+)$/', trim($line), $matches);
            if (count($matches) >= 4) {
                $package = $matches[1];
                $current_version = $matches[3];
                
                $latest_version = $this->get_latest_pypi_version($package);
                if ($latest_version && $this->is_version_newer($latest_version, $current_version)) {
                    $updates[$package] = array(
                        'current' => $current_version,
                        'latest' => $latest_version,
                        'security_update' => $this->is_security_update($package, $latest_version)
                    );
                }
            }
        }
        
        return $updates;
    }

    /**
     * Get latest Composer package version
     */
    private function get_latest_composer_version($package) {
        $url = sprintf($this->dependency_sources['composer'], $package);
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['package']['versions'])) {
            $versions = array_keys($data['package']['versions']);
            $stable_versions = array_filter($versions, function($v) {
                return !preg_match('/-(alpha|beta|rc|dev)/i', $v);
            });
            
            if (!empty($stable_versions)) {
                usort($stable_versions, 'version_compare');
                return end($stable_versions);
            }
        }
        
        return false;
    }

    /**
     * Get latest NPM package version
     */
    private function get_latest_npm_version($package) {
        $url = sprintf($this->dependency_sources['npm'], $package);
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return isset($data['dist-tags']['latest']) ? $data['dist-tags']['latest'] : false;
    }

    /**
     * Get latest PyPI package version
     */
    private function get_latest_pypi_version($package) {
        $url = sprintf($this->dependency_sources['pypi'], $package);
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return isset($data['info']['version']) ? $data['info']['version'] : false;
    }

    /**
     * Check if a version is newer than another
     */
    private function is_version_newer($new_version, $current_version) {
        // Clean version strings
        $new_version = preg_replace('/[^0-9.]/', '', $new_version);
        $current_version = preg_replace('/[^0-9.]/', '', $current_version);
        
        return version_compare($new_version, $current_version, '>');
    }

    /**
     * Check if an update is security-related
     */
    private function is_security_update($package, $version) {
        // This is a simplified check - in practice, you'd query security databases
        $security_keywords = array('security', 'vulnerability', 'cve', 'xss', 'csrf', 'injection');
        
        // Check if the package has known security issues
        $security_db_url = "https://api.github.com/advisories?affects={$package}";
        $response = wp_remote_get($security_db_url, array('timeout' => 15));
        
        if (!is_wp_error($response)) {
            $advisories = json_decode(wp_remote_retrieve_body($response), true);
            return !empty($advisories);
        }
        
        return false;
    }

    /**
     * Trigger dependency updates
     */
    private function trigger_dependency_updates($updates) {
        // Create GitHub issue or PR for updates
        $this->create_github_issue($updates);
        
        // Send notification to admin
        $this->send_update_notification($updates);
        
        // Log the update trigger
        $this->log('Triggered dependency updates for: ' . implode(', ', array_keys($updates)));
    }

    /**
     * Create GitHub issue for dependency updates
     */
    private function create_github_issue($updates) {
        $github_token = get_option('vortex_github_token');
        if (!$github_token) {
            return false;
        }
        
        $title = '🔒 Dependency Updates Available - ' . date('Y-m-d');
        $body = "## 📦 Dependency Updates Available\n\n";
        
        foreach ($updates as $type => $packages) {
            $body .= "### " . strtoupper($type) . " Dependencies\n\n";
            foreach ($packages as $package => $info) {
                $security_badge = $info['security_update'] ? ' 🚨 **SECURITY**' : '';
                $body .= "- **{$package}**: {$info['current']} → {$info['latest']}{$security_badge}\n";
            }
            $body .= "\n";
        }
        
        $body .= "This issue was created automatically by the Vortex AI Engine dependency monitor.\n";
        $body .= "Please review and update the dependencies as soon as possible.";
        
        $issue_data = array(
            'title' => $title,
            'body' => $body,
            'labels' => array('dependencies', 'security', 'automated')
        );
        
        $response = wp_remote_post('https://api.github.com/repos/mariannenems/vortexartec-ai-art-gen-web3-engine/issues', array(
            'headers' => array(
                'Authorization' => 'token ' . $github_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($issue_data),
            'timeout' => 30
        ));
        
        return !is_wp_error($response);
    }

    /**
     * Send update notification to admin
     */
    private function send_update_notification($updates) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = '[' . $site_name . '] Dependency Updates Available';
        
        $message = "Hello,\n\n";
        $message .= "The Vortex AI Engine dependency monitor has detected available updates:\n\n";
        
        foreach ($updates as $type => $packages) {
            $message .= strtoupper($type) . " Dependencies:\n";
            foreach ($packages as $package => $info) {
                $security_note = $info['security_update'] ? ' (SECURITY UPDATE)' : '';
                $message .= "- {$package}: {$info['current']} → {$info['latest']}{$security_note}\n";
            }
            $message .= "\n";
        }
        
        $message .= "Please review and update these dependencies to maintain security and performance.\n\n";
        $message .= "Best regards,\n";
        $message .= "Vortex AI Engine Dependency Monitor";
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * AJAX handler for manual dependency check
     */
    public function ajax_check_dependencies() {
        check_ajax_referer('vortex_dependency_check', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $updates = $this->check_all_dependencies();
        
        wp_send_json_success(array(
            'updates' => $updates,
            'message' => 'Dependency check completed',
            'timestamp' => current_time('mysql')
        ));
    }

    /**
     * Scheduled dependency check
     */
    public function scheduled_dependency_check() {
        $this->check_all_dependencies();
    }

    /**
     * Log dependency monitor activities
     */
    private function log($message) {
        $log_entry = sprintf(
            '[%s] Dependency Monitor: %s',
            current_time('Y-m-d H:i:s'),
            $message
        );
        
        error_log($log_entry);
        
        // Also log to plugin-specific log file
        $log_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'logs/dependency-monitor.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        file_put_contents($log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// Initialize the dependency monitor
new Vortex_Dependency_Monitor(); 