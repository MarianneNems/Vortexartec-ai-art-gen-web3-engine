<?php
/**
 * Vortex AI Engine - GitHub Deployment System
 * 
 * Automated GitHub deployment with version control,
 * continuous integration, and real-time updates.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_GitHub_Deployment {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * GitHub repository settings
     */
    private $github_repo = 'MarianneNems/vortex-artec-ai-marketplace';
    private $github_branch = 'main';
    private $github_token = '';
    
    /**
     * Deployment settings
     */
    private $deployment_path = '';
    private $backup_path = '';
    private $temp_path = '';
    
    /**
     * Version control
     */
    private $current_version = '';
    private $latest_version = '';
    
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
     * Constructor
     */
    private function __construct() {
        $this->init_deployment_system();
    }
    
    /**
     * Initialize deployment system
     */
    private function init_deployment_system() {
        // Set paths
        $this->deployment_path = VORTEX_AI_ENGINE_PLUGIN_PATH;
        $this->backup_path = VORTEX_AI_ENGINE_PLUGIN_PATH . 'backups/';
        $this->temp_path = VORTEX_AI_ENGINE_PLUGIN_PATH . 'temp/';
        
        // Create necessary directories
        $this->create_deployment_directories();
        
        // Get current version
        $this->current_version = VORTEX_AI_ENGINE_VERSION;
        
        // Get GitHub token from options
        $this->github_token = get_option('vortex_github_token', '');
        
        // Schedule deployment checks
        add_action('init', array($this, 'schedule_deployment_checks'));
        
        // Hook into WordPress events
        add_action('wp_loaded', array($this, 'check_for_updates'));
        add_action('admin_init', array($this, 'admin_deployment_checks'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_deployment_menu'));
        
        // Add deployment hooks
        add_action('vortex_deployment_check', array($this, 'run_deployment_check'));
        add_action('vortex_deployment_update', array($this, 'run_deployment_update'));
        
        // Initialize GitHub integration
        $this->init_github_integration();
    }
    
    /**
     * Create deployment directories
     */
    private function create_deployment_directories() {
        $directories = array($this->backup_path, $this->temp_path);
        
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                wp_mkdir_p($directory);
            }
        }
    }
    
    /**
     * Initialize GitHub integration
     */
    private function init_github_integration() {
        // Check if GitHub integration is enabled
        if (get_option('vortex_github_integration_enabled', false)) {
            add_action('wp_loaded', array($this, 'check_github_updates'));
            add_action('vortex_github_webhook', array($this, 'handle_github_webhook'));
        }
    }
    
    /**
     * Schedule deployment checks
     */
    public function schedule_deployment_checks() {
        if (!wp_next_scheduled('vortex_deployment_check')) {
            wp_schedule_event(time(), 'hourly', 'vortex_deployment_check');
        }
        
        if (!wp_next_scheduled('vortex_deployment_update')) {
            wp_schedule_event(time(), 'daily', 'vortex_deployment_update');
        }
    }
    
    /**
     * Check for updates
     */
    public function check_for_updates() {
        // Check GitHub for latest version
        $latest_version = $this->get_latest_github_version();
        
        if ($latest_version && version_compare($latest_version, $this->current_version, '>')) {
            $this->latest_version = $latest_version;
            
            // Log update available
            Vortex_Realtime_Logger::get_instance()->info('Update available', array(
                'current_version' => $this->current_version,
                'latest_version' => $this->latest_version
            ));
            
            // Show admin notice
            add_action('admin_notices', array($this, 'show_update_notice'));
        }
    }
    
    /**
     * Admin deployment checks
     */
    public function admin_deployment_checks() {
        if (is_admin()) {
            // Check deployment status
            $this->check_deployment_status();
            
            // Check for deployment errors
            $this->check_deployment_errors();
        }
    }
    
    /**
     * Add deployment menu
     */
    public function add_deployment_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'GitHub Deployment',
            'Deployment',
            'manage_options',
            'vortex-github-deployment',
            array($this, 'deployment_admin_page')
        );
    }
    
    /**
     * Deployment admin page
     */
    public function deployment_admin_page() {
        ?>
        <div class="wrap">
            <h1>Vortex AI Engine - GitHub Deployment</h1>
            
            <div class="vortex-deployment-status">
                <h2>Deployment Status</h2>
                <p><strong>Current Version:</strong> <?php echo $this->current_version; ?></p>
                <p><strong>Latest Version:</strong> <?php echo $this->latest_version ?: 'Checking...'; ?></p>
                <p><strong>GitHub Repository:</strong> <?php echo $this->github_repo; ?></p>
                <p><strong>Branch:</strong> <?php echo $this->github_branch; ?></p>
            </div>
            
            <div class="vortex-deployment-actions">
                <h2>Deployment Actions</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('vortex_deployment_action', 'vortex_deployment_nonce'); ?>
                    
                    <p>
                        <input type="submit" name="vortex_check_updates" class="button button-primary" value="Check for Updates">
                        <input type="submit" name="vortex_deploy_update" class="button button-secondary" value="Deploy Update" <?php echo !$this->latest_version ? 'disabled' : ''; ?>>
                        <input type="submit" name="vortex_backup_current" class="button button-secondary" value="Backup Current Version">
                        <input type="submit" name="vortex_restore_backup" class="button button-secondary" value="Restore from Backup">
                    </p>
                </form>
            </div>
            
            <div class="vortex-deployment-logs">
                <h2>Deployment Logs</h2>
                <pre><?php echo $this->get_deployment_logs(); ?></pre>
            </div>
            
            <div class="vortex-github-settings">
                <h2>GitHub Settings</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('vortex_github_settings', 'vortex_github_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">GitHub Token</th>
                            <td>
                                <input type="password" name="vortex_github_token" value="<?php echo esc_attr($this->github_token); ?>" class="regular-text">
                                <p class="description">Personal access token for GitHub API access</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enable Auto-Updates</th>
                            <td>
                                <input type="checkbox" name="vortex_auto_updates" value="1" <?php checked(get_option('vortex_auto_updates', false)); ?>>
                                <p class="description">Automatically deploy updates when available</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p><input type="submit" name="vortex_save_github_settings" class="button button-primary" value="Save Settings"></p>
                </form>
            </div>
        </div>
        <?php
        
        // Handle form submissions
        $this->handle_deployment_actions();
    }
    
    /**
     * Handle deployment actions
     */
    private function handle_deployment_actions() {
        if (isset($_POST['vortex_check_updates']) && wp_verify_nonce($_POST['vortex_deployment_nonce'], 'vortex_deployment_action')) {
            $this->run_deployment_check();
            echo '<div class="notice notice-success"><p>Update check completed.</p></div>';
        }
        
        if (isset($_POST['vortex_deploy_update']) && wp_verify_nonce($_POST['vortex_deployment_nonce'], 'vortex_deployment_action')) {
            $this->run_deployment_update();
            echo '<div class="notice notice-success"><p>Deployment completed.</p></div>';
        }
        
        if (isset($_POST['vortex_backup_current']) && wp_verify_nonce($_POST['vortex_deployment_nonce'], 'vortex_deployment_action')) {
            $this->create_backup();
            echo '<div class="notice notice-success"><p>Backup created successfully.</p></div>';
        }
        
        if (isset($_POST['vortex_restore_backup']) && wp_verify_nonce($_POST['vortex_deployment_nonce'], 'vortex_deployment_action')) {
            $this->restore_backup();
            echo '<div class="notice notice-success"><p>Backup restored successfully.</p></div>';
        }
        
        if (isset($_POST['vortex_save_github_settings']) && wp_verify_nonce($_POST['vortex_github_nonce'], 'vortex_github_settings')) {
            $this->save_github_settings();
            echo '<div class="notice notice-success"><p>GitHub settings saved.</p></div>';
        }
    }
    
    /**
     * Run deployment check
     */
    public function run_deployment_check() {
        Vortex_Realtime_Logger::get_instance()->info('Starting deployment check');
        
        try {
            // Check GitHub for latest version
            $latest_version = $this->get_latest_github_version();
            
            if ($latest_version) {
                $this->latest_version = $latest_version;
                
                if (version_compare($latest_version, $this->current_version, '>')) {
                    Vortex_Realtime_Logger::get_instance()->info('Update available', array(
                        'current_version' => $this->current_version,
                        'latest_version' => $latest_version
                    ));
                    
                    // Auto-update if enabled
                    if (get_option('vortex_auto_updates', false)) {
                        $this->run_deployment_update();
                    }
                } else {
                    Vortex_Realtime_Logger::get_instance()->info('No updates available');
                }
            }
        } catch (Exception $e) {
            Vortex_Realtime_Logger::get_instance()->error('Deployment check failed', array(
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Run deployment update
     */
    public function run_deployment_update() {
        Vortex_Realtime_Logger::get_instance()->info('Starting deployment update');
        
        try {
            // Create backup before update
            $this->create_backup();
            
            // Download latest version
            $download_result = $this->download_latest_version();
            
            if ($download_result) {
                // Extract and deploy
                $deploy_result = $this->deploy_update();
                
                if ($deploy_result) {
                    // Update version
                    $this->update_version();
                    
                    Vortex_Realtime_Logger::get_instance()->info('Deployment update completed successfully');
                    
                    // Clear caches
                    $this->clear_caches();
                    
                    // Run post-deployment tasks
                    $this->run_post_deployment_tasks();
                } else {
                    throw new Exception('Deployment failed');
                }
            } else {
                throw new Exception('Download failed');
            }
        } catch (Exception $e) {
            Vortex_Realtime_Logger::get_instance()->error('Deployment update failed', array(
                'error' => $e->getMessage()
            ));
            
            // Restore from backup
            $this->restore_backup();
        }
    }
    
    /**
     * Get latest GitHub version
     */
    private function get_latest_github_version() {
        if (empty($this->github_token)) {
            return false;
        }
        
        $api_url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'token ' . $this->github_token,
                'User-Agent' => 'Vortex-AI-Engine'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['tag_name'])) {
            return ltrim($data['tag_name'], 'v');
        }
        
        return false;
    }
    
    /**
     * Download latest version
     */
    private function download_latest_version() {
        if (empty($this->github_token) || empty($this->latest_version)) {
            return false;
        }
        
        $download_url = "https://api.github.com/repos/{$this->github_repo}/zipball/v{$this->latest_version}";
        
        $response = wp_remote_get($download_url, array(
            'headers' => array(
                'Authorization' => 'token ' . $this->github_token,
                'User-Agent' => 'Vortex-AI-Engine'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $temp_file = $this->temp_path . 'vortex-update-' . $this->latest_version . '.zip';
        
        return file_put_contents($temp_file, $body) !== false;
    }
    
    /**
     * Deploy update
     */
    private function deploy_update() {
        $temp_file = $this->temp_path . 'vortex-update-' . $this->latest_version . '.zip';
        
        if (!file_exists($temp_file)) {
            return false;
        }
        
        // Extract zip file
        $zip = new ZipArchive();
        if ($zip->open($temp_file) === TRUE) {
            $zip->extractTo($this->temp_path);
            $zip->close();
            
            // Move files to deployment path
            $extracted_dir = $this->temp_path . $this->github_repo . '-' . $this->latest_version;
            
            if (is_dir($extracted_dir)) {
                return $this->copy_directory($extracted_dir, $this->deployment_path);
            }
        }
        
        return false;
    }
    
    /**
     * Create backup
     */
    private function create_backup() {
        $backup_file = $this->backup_path . 'vortex-backup-' . date('Y-m-d-H-i-s') . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
            $this->add_directory_to_zip($zip, $this->deployment_path, '');
            $zip->close();
            
            Vortex_Realtime_Logger::get_instance()->info('Backup created', array(
                'backup_file' => $backup_file
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Restore backup
     */
    private function restore_backup() {
        $backup_files = glob($this->backup_path . 'vortex-backup-*.zip');
        
        if (empty($backup_files)) {
            return false;
        }
        
        $latest_backup = end($backup_files);
        
        $zip = new ZipArchive();
        if ($zip->open($latest_backup) === TRUE) {
            $zip->extractTo($this->deployment_path);
            $zip->close();
            
            Vortex_Realtime_Logger::get_instance()->info('Backup restored', array(
                'backup_file' => $latest_backup
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Update version
     */
    private function update_version() {
        $this->current_version = $this->latest_version;
        update_option('vortex_ai_engine_version', $this->current_version);
    }
    
    /**
     * Clear caches
     */
    private function clear_caches() {
        // Clear WordPress cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear object cache
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('vortex-ai-engine');
        }
        
        // Clear transients
        delete_transient('vortex_ai_engine_cache');
    }
    
    /**
     * Run post-deployment tasks
     */
    private function run_post_deployment_tasks() {
        // Update database if needed
        $this->update_database_schema();
        
        // Clear plugin cache
        $this->clear_plugin_cache();
        
        // Run system tests
        $this->run_system_tests();
    }
    
    /**
     * Helper methods
     */
    private function copy_directory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $source_path = $source . '/' . $file;
                $dest_path = $destination . '/' . $file;
                
                if (is_dir($source_path)) {
                    $this->copy_directory($source_path, $dest_path);
                } else {
                    copy($source_path, $dest_path);
                }
            }
        }
        closedir($dir);
        
        return true;
    }
    
    private function add_directory_to_zip($zip, $directory, $relative_path) {
        $dir = opendir($directory);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $file_path = $directory . '/' . $file;
                $zip_path = $relative_path . '/' . $file;
                
                if (is_dir($file_path)) {
                    $this->add_directory_to_zip($zip, $file_path, $zip_path);
                } else {
                    $zip->addFile($file_path, $zip_path);
                }
            }
        }
        closedir($dir);
    }
    
    private function get_deployment_logs() {
        $log_file = VORTEX_AI_ENGINE_PLUGIN_PATH . 'logs/vortex-deployment.log';
        
        if (file_exists($log_file)) {
            return file_get_contents($log_file);
        }
        
        return 'No deployment logs available.';
    }
    
    private function save_github_settings() {
        if (isset($_POST['vortex_github_token'])) {
            update_option('vortex_github_token', sanitize_text_field($_POST['vortex_github_token']));
            $this->github_token = get_option('vortex_github_token', '');
        }
        
        if (isset($_POST['vortex_auto_updates'])) {
            update_option('vortex_auto_updates', true);
        } else {
            update_option('vortex_auto_updates', false);
        }
    }
    
    private function check_deployment_status() {
        // Implementation for checking deployment status
    }
    
    private function check_deployment_errors() {
        // Implementation for checking deployment errors
    }
    
    private function update_database_schema() {
        // Implementation for updating database schema
    }
    
    private function clear_plugin_cache() {
        // Implementation for clearing plugin cache
    }
    
    private function run_system_tests() {
        // Implementation for running system tests
    }
    
    private function show_update_notice() {
        if ($this->latest_version) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Vortex AI Engine Update Available:</strong> ';
            echo 'Version ' . $this->latest_version . ' is available. ';
            echo '<a href="' . admin_url('admin.php?page=vortex-github-deployment') . '">Update Now</a></p>';
            echo '</div>';
        }
    }
} 