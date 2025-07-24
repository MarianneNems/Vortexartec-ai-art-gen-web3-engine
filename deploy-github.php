<?php
/**
 * Vortex AI Engine - GitHub Deployment Script
 * 
 * Complete deployment script for GitHub integration with
 * recursive self-improvement, real-time logging, and debug capabilities.
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub Deployment Manager
 */
class Vortex_GitHub_Deployment_Manager {
    
    /**
     * GitHub repository configuration
     */
    private $github_config = array(
        'repository' => 'MarianneNems/vortex-artec-ai-marketplace',
        'branch' => 'main',
        'token' => '',
        'webhook_secret' => '',
        'auto_deploy' => true,
        'backup_before_deploy' => true,
        'test_after_deploy' => true
    );
    
    /**
     * Deployment paths
     */
    private $paths = array(
        'plugin_root' => '',
        'backup_dir' => '',
        'temp_dir' => '',
        'logs_dir' => '',
        'cache_dir' => ''
    );
    
    /**
     * Current deployment status
     */
    private $deployment_status = array(
        'in_progress' => false,
        'last_deployment' => null,
        'last_check' => null,
        'current_version' => '',
        'latest_version' => '',
        'deployment_log' => array()
    );
    
    /**
     * Initialize deployment manager
     */
    public function __construct() {
        $this->init_deployment_manager();
    }
    
    /**
     * Initialize deployment manager
     */
    private function init_deployment_manager() {
        // Set paths
        $this->paths['plugin_root'] = VORTEX_AI_ENGINE_PLUGIN_PATH;
        $this->paths['backup_dir'] = $this->paths['plugin_root'] . 'backups/';
        $this->paths['temp_dir'] = $this->paths['plugin_root'] . 'temp/';
        $this->paths['logs_dir'] = $this->paths['plugin_root'] . 'logs/';
        $this->paths['cache_dir'] = $this->paths['plugin_root'] . 'cache/';
        
        // Create directories
        $this->create_directories();
        
        // Load configuration
        $this->load_configuration();
        
        // Initialize systems
        $this->init_systems();
        
        // Schedule tasks
        $this->schedule_tasks();
    }
    
    /**
     * Create necessary directories
     */
    private function create_directories() {
        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                wp_mkdir_p($path);
            }
        }
    }
    
    /**
     * Load configuration
     */
    private function load_configuration() {
        $this->github_config['token'] = get_option('vortex_github_token', '');
        $this->github_config['webhook_secret'] = get_option('vortex_webhook_secret', '');
        $this->github_config['auto_deploy'] = get_option('vortex_auto_deploy', true);
        $this->github_config['backup_before_deploy'] = get_option('vortex_backup_before_deploy', true);
        $this->github_config['test_after_deploy'] = get_option('vortex_test_after_deploy', true);
        
        $this->deployment_status['current_version'] = VORTEX_AI_ENGINE_VERSION;
        $this->deployment_status['last_deployment'] = get_option('vortex_last_deployment', null);
        $this->deployment_status['last_check'] = get_option('vortex_last_check', null);
    }
    
    /**
     * Initialize systems
     */
    private function init_systems() {
        // Initialize real-time logger
        if (class_exists('Vortex_Realtime_Logger')) {
            Vortex_Realtime_Logger::get_instance();
        }
        
        // Initialize recursive improvement system
        if (class_exists('Vortex_Recursive_Improvement')) {
            Vortex_Recursive_Improvement::get_instance();
        }
        
        // Initialize GitHub deployment system
        if (class_exists('Vortex_GitHub_Deployment')) {
            Vortex_GitHub_Deployment::get_instance();
        }
    }
    
    /**
     * Schedule tasks
     */
    private function schedule_tasks() {
        // Schedule deployment checks
        if (!wp_next_scheduled('vortex_github_deployment_check')) {
            wp_schedule_event(time(), 'hourly', 'vortex_github_deployment_check');
        }
        
        // Schedule system maintenance
        if (!wp_next_scheduled('vortex_system_maintenance')) {
            wp_schedule_event(time(), 'daily', 'vortex_system_maintenance');
        }
        
        // Schedule log rotation
        if (!wp_next_scheduled('vortex_log_rotation')) {
            wp_schedule_event(time(), 'daily', 'vortex_log_rotation');
        }
        
        // Add action hooks
        add_action('vortex_github_deployment_check', array($this, 'check_for_updates'));
        add_action('vortex_system_maintenance', array($this, 'run_maintenance'));
        add_action('vortex_log_rotation', array($this, 'rotate_logs'));
    }
    
    /**
     * Check for GitHub updates
     */
    public function check_for_updates() {
        $this->log_deployment_activity('Starting GitHub update check');
        
        try {
            // Get latest version from GitHub
            $latest_version = $this->get_latest_github_version();
            
            if ($latest_version) {
                $this->deployment_status['latest_version'] = $latest_version;
                
                if (version_compare($latest_version, $this->deployment_status['current_version'], '>')) {
                    $this->log_deployment_activity('Update available: ' . $latest_version);
                    
                    if ($this->github_config['auto_deploy']) {
                        $this->deploy_update();
                    } else {
                        $this->notify_update_available($latest_version);
                    }
                } else {
                    $this->log_deployment_activity('No updates available');
                }
            }
            
            $this->deployment_status['last_check'] = time();
            update_option('vortex_last_check', $this->deployment_status['last_check']);
            
        } catch (Exception $e) {
            $this->log_deployment_activity('Update check failed: ' . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Deploy update from GitHub
     */
    public function deploy_update() {
        if ($this->deployment_status['in_progress']) {
            $this->log_deployment_activity('Deployment already in progress', 'WARNING');
            return false;
        }
        
        $this->deployment_status['in_progress'] = true;
        $this->log_deployment_activity('Starting deployment of version ' . $this->deployment_status['latest_version']);
        
        try {
            // Step 1: Create backup
            if ($this->github_config['backup_before_deploy']) {
                $this->create_backup();
            }
            
            // Step 2: Download latest version
            $download_result = $this->download_latest_version();
            if (!$download_result) {
                throw new Exception('Failed to download latest version');
            }
            
            // Step 3: Extract and deploy
            $deploy_result = $this->extract_and_deploy();
            if (!$deploy_result) {
                throw new Exception('Failed to deploy update');
            }
            
            // Step 4: Update version
            $this->update_version();
            
            // Step 5: Run tests
            if ($this->github_config['test_after_deploy']) {
                $this->run_deployment_tests();
            }
            
            // Step 6: Clear caches
            $this->clear_all_caches();
            
            // Step 7: Run post-deployment tasks
            $this->run_post_deployment_tasks();
            
            $this->deployment_status['last_deployment'] = time();
            update_option('vortex_last_deployment', $this->deployment_status['last_deployment']);
            
            $this->log_deployment_activity('Deployment completed successfully');
            
            return true;
            
        } catch (Exception $e) {
            $this->log_deployment_activity('Deployment failed: ' . $e->getMessage(), 'ERROR');
            
            // Restore from backup
            $this->restore_backup();
            
            return false;
            
        } finally {
            $this->deployment_status['in_progress'] = false;
        }
    }
    
    /**
     * Get latest version from GitHub
     */
    private function get_latest_github_version() {
        if (empty($this->github_config['token'])) {
            throw new Exception('GitHub token not configured');
        }
        
        $api_url = "https://api.github.com/repos/{$this->github_config['repository']}/releases/latest";
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'token ' . $this->github_config['token'],
                'User-Agent' => 'Vortex-AI-Engine-Deployment',
                'Accept' => 'application/vnd.github.v3+json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('GitHub API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['tag_name'])) {
            return ltrim($data['tag_name'], 'v');
        }
        
        throw new Exception('No releases found');
    }
    
    /**
     * Download latest version
     */
    private function download_latest_version() {
        $download_url = "https://api.github.com/repos/{$this->github_config['repository']}/zipball/v{$this->deployment_status['latest_version']}";
        
        $response = wp_remote_get($download_url, array(
            'headers' => array(
                'Authorization' => 'token ' . $this->github_config['token'],
                'User-Agent' => 'Vortex-AI-Engine-Deployment'
            ),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Download failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $temp_file = $this->paths['temp_dir'] . 'vortex-update-' . $this->deployment_status['latest_version'] . '.zip';
        
        $result = file_put_contents($temp_file, $body);
        if ($result === false) {
            throw new Exception('Failed to save downloaded file');
        }
        
        $this->log_deployment_activity('Downloaded version ' . $this->deployment_status['latest_version']);
        return true;
    }
    
    /**
     * Extract and deploy update
     */
    private function extract_and_deploy() {
        $temp_file = $this->paths['temp_dir'] . 'vortex-update-' . $this->deployment_status['latest_version'] . '.zip';
        
        if (!file_exists($temp_file)) {
            throw new Exception('Download file not found');
        }
        
        $zip = new ZipArchive();
        if ($zip->open($temp_file) !== TRUE) {
            throw new Exception('Failed to open zip file');
        }
        
        // Extract to temp directory
        $extract_path = $this->paths['temp_dir'] . 'extract-' . time();
        if (!$zip->extractTo($extract_path)) {
            $zip->close();
            throw new Exception('Failed to extract zip file');
        }
        $zip->close();
        
        // Find the extracted directory
        $extracted_dirs = glob($extract_path . '/*', GLOB_ONLYDIR);
        if (empty($extracted_dirs)) {
            throw new Exception('No directories found in extracted archive');
        }
        
        $source_dir = $extracted_dirs[0];
        
        // Deploy files
        $this->deploy_files($source_dir, $this->paths['plugin_root']);
        
        // Clean up
        $this->cleanup_temp_files();
        
        $this->log_deployment_activity('Files deployed successfully');
        return true;
    }
    
    /**
     * Deploy files from source to destination
     */
    private function deploy_files($source, $destination) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($source) + 1);
            $dest_path = $destination . $relative_path;
            
            if ($file->isDir()) {
                if (!is_dir($dest_path)) {
                    mkdir($dest_path, 0755, true);
                }
            } else {
                // Ensure directory exists
                $dest_dir = dirname($dest_path);
                if (!is_dir($dest_dir)) {
                    mkdir($dest_dir, 0755, true);
                }
                
                copy($file_path, $dest_path);
            }
        }
    }
    
    /**
     * Create backup
     */
    private function create_backup() {
        $backup_file = $this->paths['backup_dir'] . 'vortex-backup-' . date('Y-m-d-H-i-s') . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Failed to create backup file');
        }
        
        $this->add_directory_to_zip($zip, $this->paths['plugin_root'], '');
        $zip->close();
        
        $this->log_deployment_activity('Backup created: ' . basename($backup_file));
        
        // Keep only last 5 backups
        $this->cleanup_old_backups();
    }
    
    /**
     * Restore from backup
     */
    private function restore_backup() {
        $backup_files = glob($this->paths['backup_dir'] . 'vortex-backup-*.zip');
        
        if (empty($backup_files)) {
            $this->log_deployment_activity('No backup files found for restoration', 'ERROR');
            return false;
        }
        
        $latest_backup = end($backup_files);
        
        $zip = new ZipArchive();
        if ($zip->open($latest_backup) !== TRUE) {
            $this->log_deployment_activity('Failed to open backup file', 'ERROR');
            return false;
        }
        
        $zip->extractTo($this->paths['plugin_root']);
        $zip->close();
        
        $this->log_deployment_activity('Restored from backup: ' . basename($latest_backup));
        return true;
    }
    
    /**
     * Update version
     */
    private function update_version() {
        $this->deployment_status['current_version'] = $this->deployment_status['latest_version'];
        update_option('vortex_ai_engine_version', $this->deployment_status['current_version']);
        
        $this->log_deployment_activity('Version updated to ' . $this->deployment_status['current_version']);
    }
    
    /**
     * Run deployment tests
     */
    private function run_deployment_tests() {
        $this->log_deployment_activity('Running deployment tests');
        
        // Test plugin activation
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        if (!is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
            throw new Exception('Plugin not active after deployment');
        }
        
        // Test basic functionality
        if (!class_exists('Vortex_AI_Engine')) {
            throw new Exception('Main plugin class not found');
        }
        
        // Test database connectivity
        global $wpdb;
        if (!$wpdb->check_connection()) {
            throw new Exception('Database connection failed');
        }
        
        $this->log_deployment_activity('Deployment tests passed');
    }
    
    /**
     * Clear all caches
     */
    private function clear_all_caches() {
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
        
        // Clear plugin cache
        $cache_files = glob($this->paths['cache_dir'] . '*');
        foreach ($cache_files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        $this->log_deployment_activity('All caches cleared');
    }
    
    /**
     * Run post-deployment tasks
     */
    private function run_post_deployment_tasks() {
        // Update database schema if needed
        $this->update_database_schema();
        
        // Run recursive improvement cycle
        if (class_exists('Vortex_Recursive_Improvement')) {
            Vortex_Recursive_Improvement::get_instance()->run_improvement_cycle();
        }
        
        // Send deployment notification
        $this->send_deployment_notification();
        
        $this->log_deployment_activity('Post-deployment tasks completed');
    }
    
    /**
     * Helper methods
     */
    private function add_directory_to_zip($zip, $directory, $relative_path) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $file_path = $file->getRealPath();
            $zip_path = $relative_path . '/' . basename($file_path);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($zip_path);
            } else {
                $zip->addFile($file_path, $zip_path);
            }
        }
    }
    
    private function cleanup_temp_files() {
        $temp_files = glob($this->paths['temp_dir'] . '*');
        foreach ($temp_files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                $this->remove_directory($file);
            }
        }
    }
    
    private function cleanup_old_backups() {
        $backup_files = glob($this->paths['backup_dir'] . 'vortex-backup-*.zip');
        if (count($backup_files) > 5) {
            array_map('unlink', array_slice($backup_files, 0, count($backup_files) - 5));
        }
    }
    
    private function remove_directory($dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($dir);
    }
    
    private function log_deployment_activity($message, $level = 'INFO') {
        $log_entry = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );
        
        $log_file = $this->paths['logs_dir'] . 'deployment.log';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        $this->deployment_status['deployment_log'][] = array(
            'timestamp' => time(),
            'level' => $level,
            'message' => $message
        );
        
        // Keep only last 1000 log entries
        if (count($this->deployment_status['deployment_log']) > 1000) {
            $this->deployment_status['deployment_log'] = array_slice($this->deployment_status['deployment_log'], -1000);
        }
    }
    
    private function notify_update_available($version) {
        // Send email notification
        $admin_email = get_option('admin_email');
        $subject = 'Vortex AI Engine Update Available';
        $message = "A new version ({$version}) of Vortex AI Engine is available for deployment.\n\n";
        $message .= "Visit the admin panel to deploy the update.\n";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    private function send_deployment_notification() {
        $admin_email = get_option('admin_email');
        $subject = 'Vortex AI Engine Deployment Completed';
        $message = "Vortex AI Engine has been successfully updated to version {$this->deployment_status['current_version']}.\n\n";
        $message .= "Deployment completed at: " . date('Y-m-d H:i:s') . "\n";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    private function update_database_schema() {
        // Implementation for database schema updates
    }
    
    public function run_maintenance() {
        $this->log_deployment_activity('Running system maintenance');
        
        // Clean up old files
        $this->cleanup_temp_files();
        $this->cleanup_old_backups();
        
        // Rotate logs
        $this->rotate_logs();
        
        // Optimize database
        $this->optimize_database();
        
        $this->log_deployment_activity('System maintenance completed');
    }
    
    public function rotate_logs() {
        $log_files = glob($this->paths['logs_dir'] . '*.log');
        
        foreach ($log_files as $log_file) {
            if (filesize($log_file) > 10 * 1024 * 1024) { // 10MB
                $backup_file = $log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
                rename($log_file, $backup_file);
                
                // Keep only last 5 backup files
                $backup_files = glob($log_file . '.*.bak');
                if (count($backup_files) > 5) {
                    array_map('unlink', array_slice($backup_files, 0, count($backup_files) - 5));
                }
            }
        }
    }
    
    private function optimize_database() {
        global $wpdb;
        
        // Optimize tables
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}vortex_%'");
        
        foreach ($tables as $table) {
            $table_name = array_values((array) $table)[0];
            $wpdb->query("OPTIMIZE TABLE {$table_name}");
        }
    }
    
    /**
     * Get deployment status
     */
    public function get_deployment_status() {
        return $this->deployment_status;
    }
    
    /**
     * Get GitHub configuration
     */
    public function get_github_config() {
        return $this->github_config;
    }
}

// Initialize deployment manager
$vortex_deployment_manager = new Vortex_GitHub_Deployment_Manager(); 