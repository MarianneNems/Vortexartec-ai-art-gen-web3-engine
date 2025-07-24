<?php
/**
 * Vortex Storage Router
 * 
 * Handles file storage operations for the VORTEX AI Engine plugin
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
 * Vortex Storage Router Class
 */
class Vortex_Storage_Router {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Storage configurations
     */
    private $config = array();
    
    /**
     * Storage providers
     */
    private $providers = array();
    
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
        $this->init_config();
        $this->init_providers();
    }
    
    /**
     * Initialize storage configuration
     */
    private function init_config() {
        $this->config = array(
            'local' => array(
                'enabled' => true,
                'path' => wp_upload_dir()['basedir'] . '/vortex-ai-engine/',
                'url' => wp_upload_dir()['baseurl'] . '/vortex-ai-engine/',
                'max_size' => 50 * 1024 * 1024, // 50MB
                'allowed_types' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'avi')
            ),
            'cloud' => array(
                'enabled' => false,
                'provider' => 'aws', // aws, gcp, azure
                'bucket' => '',
                'region' => '',
                'access_key' => '',
                'secret_key' => '',
                'cdn_url' => ''
            ),
            'ipfs' => array(
                'enabled' => false,
                'gateway' => 'https://ipfs.io/ipfs/',
                'api_endpoint' => '',
                'api_key' => ''
            )
        );
        
        // Load custom configuration from options
        $saved_config = get_option('vortex_storage_config', array());
        $this->config = array_merge($this->config, $saved_config);
    }
    
    /**
     * Initialize storage providers
     */
    private function init_providers() {
        // Local storage provider
        if ($this->config['local']['enabled']) {
            $this->providers['local'] = new Vortex_Local_Storage_Provider($this->config['local']);
        }
        
        // Cloud storage provider
        if ($this->config['cloud']['enabled']) {
            switch ($this->config['cloud']['provider']) {
                case 'aws':
                    $this->providers['cloud'] = new Vortex_AWS_Storage_Provider($this->config['cloud']);
                    break;
                case 'gcp':
                    $this->providers['cloud'] = new Vortex_GCP_Storage_Provider($this->config['cloud']);
                    break;
                case 'azure':
                    $this->providers['cloud'] = new Vortex_Azure_Storage_Provider($this->config['cloud']);
                    break;
            }
        }
        
        // IPFS storage provider
        if ($this->config['ipfs']['enabled']) {
            $this->providers['ipfs'] = new Vortex_IPFS_Storage_Provider($this->config['ipfs']);
        }
    }
    
    /**
     * Store file
     */
    public function store_file($file_data, $file_name, $category = 'artworks', $provider = 'auto') {
        // Validate file
        if (!$this->validate_file($file_data)) {
            return array('success' => false, 'error' => 'Invalid file data');
        }
        
        // Determine storage provider
        $storage_provider = $this->get_storage_provider($provider);
        if (!$storage_provider) {
            return array('success' => false, 'error' => 'No storage provider available');
        }
        
        // Generate file path
        $file_path = $this->generate_file_path($file_name, $category);
        
        // Store file
        $result = $storage_provider->store($file_data, $file_path);
        
        if ($result['success']) {
            // Log storage operation
            $this->log_storage_operation('store', $file_path, $result);
            
            // Update database if needed
            $this->update_storage_record($file_path, $result);
        }
        
        return $result;
    }
    
    /**
     * Retrieve file
     */
    public function retrieve_file($file_path, $provider = 'auto') {
        // Determine storage provider
        $storage_provider = $this->get_storage_provider($provider);
        if (!$storage_provider) {
            return array('success' => false, 'error' => 'No storage provider available');
        }
        
        // Retrieve file
        $result = $storage_provider->retrieve($file_path);
        
        if ($result['success']) {
            // Log retrieval operation
            $this->log_storage_operation('retrieve', $file_path, $result);
        }
        
        return $result;
    }
    
    /**
     * Delete file
     */
    public function delete_file($file_path, $provider = 'auto') {
        // Determine storage provider
        $storage_provider = $this->get_storage_provider($provider);
        if (!$storage_provider) {
            return array('success' => false, 'error' => 'No storage provider available');
        }
        
        // Delete file
        $result = $storage_provider->delete($file_path);
        
        if ($result['success']) {
            // Log deletion operation
            $this->log_storage_operation('delete', $file_path, $result);
            
            // Remove from database
            $this->remove_storage_record($file_path);
        }
        
        return $result;
    }
    
    /**
     * Get file URL
     */
    public function get_file_url($file_path, $provider = 'auto') {
        // Determine storage provider
        $storage_provider = $this->get_storage_provider($provider);
        if (!$storage_provider) {
            return false;
        }
        
        return $storage_provider->get_url($file_path);
    }
    
    /**
     * Validate file
     */
    private function validate_file($file_data) {
        if (empty($file_data)) {
            return false;
        }
        
        // Check file size
        if (isset($file_data['size']) && $file_data['size'] > $this->config['local']['max_size']) {
            return false;
        }
        
        // Check file type
        if (isset($file_data['type'])) {
            $extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($extension), $this->config['local']['allowed_types'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get storage provider
     */
    private function get_storage_provider($provider = 'auto') {
        if ($provider === 'auto') {
            // Auto-select based on file type and size
            return $this->get_optimal_provider();
        }
        
        return isset($this->providers[$provider]) ? $this->providers[$provider] : null;
    }
    
    /**
     * Get optimal storage provider
     */
    private function get_optimal_provider() {
        // Priority: local -> cloud -> ipfs
        if (isset($this->providers['local'])) {
            return $this->providers['local'];
        }
        
        if (isset($this->providers['cloud'])) {
            return $this->providers['cloud'];
        }
        
        if (isset($this->providers['ipfs'])) {
            return $this->providers['ipfs'];
        }
        
        return null;
    }
    
    /**
     * Generate file path
     */
    private function generate_file_path($file_name, $category) {
        $timestamp = time();
        $random_string = wp_generate_password(8, false);
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        
        return $category . '/' . $timestamp . '_' . $random_string . '.' . $extension;
    }
    
    /**
     * Log storage operation
     */
    private function log_storage_operation($operation, $file_path, $result) {
        $db_manager = Vortex_Database_Manager::get_instance();
        $db_manager->log(
            'info',
            'storage_router',
            "Storage operation: $operation - $file_path",
            array(
                'operation' => $operation,
                'file_path' => $file_path,
                'result' => $result
            )
        );
    }
    
    /**
     * Update storage record
     */
    private function update_storage_record($file_path, $result) {
        $db_manager = Vortex_Database_Manager::get_instance();
        
        $data = array(
            'file_path' => $file_path,
            'storage_provider' => $result['provider'] ?? 'local',
            'file_url' => $result['url'] ?? '',
            'file_size' => $result['size'] ?? 0,
            'metadata' => json_encode($result['metadata'] ?? array()),
            'created_at' => current_time('mysql')
        );
        
        $db_manager->insert('storage_records', $data);
    }
    
    /**
     * Remove storage record
     */
    private function remove_storage_record($file_path) {
        $db_manager = Vortex_Database_Manager::get_instance();
        $db_manager->delete('storage_records', array('file_path' => $file_path));
    }
    
    /**
     * Get storage statistics
     */
    public function get_storage_stats() {
        $stats = array();
        
        foreach ($this->providers as $name => $provider) {
            $stats[$name] = $provider->get_stats();
        }
        
        return $stats;
    }
    
    /**
     * Clean up old files
     */
    public function cleanup_old_files($days = 30) {
        $db_manager = Vortex_Database_Manager::get_instance();
        
        // Get old storage records
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $old_records = $db_manager->get_results(
            'storage_records',
            array(),
            'created_at ASC',
            "created_at < '$cutoff_date'"
        );
        
        $deleted_count = 0;
        
        foreach ($old_records as $record) {
            $result = $this->delete_file($record->file_path);
            if ($result['success']) {
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Migrate files between providers
     */
    public function migrate_files($from_provider, $to_provider, $category = null) {
        if (!isset($this->providers[$from_provider]) || !isset($this->providers[$to_provider])) {
            return array('success' => false, 'error' => 'Invalid providers');
        }
        
        $db_manager = Vortex_Database_Manager::get_instance();
        
        // Get files to migrate
        $where = array('storage_provider' => $from_provider);
        if ($category) {
            $where['category'] = $category;
        }
        
        $files = $db_manager->get_results('storage_records', $where);
        
        $migrated_count = 0;
        $errors = array();
        
        foreach ($files as $file) {
            // Retrieve from source
            $retrieve_result = $this->retrieve_file($file->file_path, $from_provider);
            
            if ($retrieve_result['success']) {
                // Store to destination
                $store_result = $this->store_file(
                    $retrieve_result['data'],
                    basename($file->file_path),
                    dirname($file->file_path),
                    $to_provider
                );
                
                if ($store_result['success']) {
                    // Delete from source
                    $this->delete_file($file->file_path, $from_provider);
                    $migrated_count++;
                } else {
                    $errors[] = "Failed to store $file->file_path: " . $store_result['error'];
                }
            } else {
                $errors[] = "Failed to retrieve $file->file_path: " . $retrieve_result['error'];
            }
        }
        
        return array(
            'success' => true,
            'migrated_count' => $migrated_count,
            'errors' => $errors
        );
    }
}

/**
 * Abstract Storage Provider Class
 */
abstract class Vortex_Storage_Provider {
    
    protected $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    abstract public function store($file_data, $file_path);
    abstract public function retrieve($file_path);
    abstract public function delete($file_path);
    abstract public function get_url($file_path);
    abstract public function get_stats();
}

/**
 * Local Storage Provider
 */
class Vortex_Local_Storage_Provider extends Vortex_Storage_Provider {
    
    public function store($file_data, $file_path) {
        $full_path = $this->config['path'] . $file_path;
        $directory = dirname($full_path);
        
        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }
        
        // Handle different file data formats
        if (is_array($file_data) && isset($file_data['tmp_name'])) {
            // Uploaded file
            $result = move_uploaded_file($file_data['tmp_name'], $full_path);
        } elseif (is_string($file_data)) {
            // File content as string
            $result = file_put_contents($full_path, $file_data);
        } else {
            return array('success' => false, 'error' => 'Invalid file data format');
        }
        
        if ($result) {
            return array(
                'success' => true,
                'url' => $this->config['url'] . $file_path,
                'path' => $full_path,
                'size' => filesize($full_path),
                'provider' => 'local'
            );
        }
        
        return array('success' => false, 'error' => 'Failed to store file');
    }
    
    public function retrieve($file_path) {
        $full_path = $this->config['path'] . $file_path;
        
        if (file_exists($full_path)) {
            return array(
                'success' => true,
                'data' => file_get_contents($full_path),
                'size' => filesize($full_path),
                'provider' => 'local'
            );
        }
        
        return array('success' => false, 'error' => 'File not found');
    }
    
    public function delete($file_path) {
        $full_path = $this->config['path'] . $file_path;
        
        if (file_exists($full_path)) {
            $result = unlink($full_path);
            return array(
                'success' => $result,
                'provider' => 'local'
            );
        }
        
        return array('success' => false, 'error' => 'File not found');
    }
    
    public function get_url($file_path) {
        return $this->config['url'] . $file_path;
    }
    
    public function get_stats() {
        $path = $this->config['path'];
        $total_size = 0;
        $file_count = 0;
        
        if (is_dir($path)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $total_size += $file->getSize();
                    $file_count++;
                }
            }
        }
        
        return array(
            'total_size' => $total_size,
            'file_count' => $file_count,
            'available_space' => disk_free_space($path)
        );
    }
}

/**
 * AWS Storage Provider
 */
class Vortex_AWS_Storage_Provider extends Vortex_Storage_Provider {
    
    public function store($file_data, $file_path) {
        // Simulated AWS S3 upload
        // In a real implementation, you would use AWS SDK
        return array(
            'success' => true,
            'url' => 'https://s3.amazonaws.com/' . $this->config['bucket'] . '/' . $file_path,
            'size' => strlen($file_data),
            'provider' => 'aws'
        );
    }
    
    public function retrieve($file_path) {
        // Simulated AWS S3 download
        return array(
            'success' => true,
            'data' => 'simulated_file_content',
            'size' => 1024,
            'provider' => 'aws'
        );
    }
    
    public function delete($file_path) {
        // Simulated AWS S3 deletion
        return array(
            'success' => true,
            'provider' => 'aws'
        );
    }
    
    public function get_url($file_path) {
        return 'https://s3.amazonaws.com/' . $this->config['bucket'] . '/' . $file_path;
    }
    
    public function get_stats() {
        return array(
            'total_size' => 0,
            'file_count' => 0,
            'available_space' => 0
        );
    }
}

/**
 * IPFS Storage Provider
 */
class Vortex_IPFS_Storage_Provider extends Vortex_Storage_Provider {
    
    public function store($file_data, $file_path) {
        // Simulated IPFS upload
        $hash = 'Qm' . wp_generate_password(44, false, 'abcdefghijklmnopqrstuvwxyz0123456789');
        
        return array(
            'success' => true,
            'url' => $this->config['gateway'] . $hash,
            'hash' => $hash,
            'size' => strlen($file_data),
            'provider' => 'ipfs'
        );
    }
    
    public function retrieve($file_path) {
        // Simulated IPFS download
        return array(
            'success' => true,
            'data' => 'simulated_ipfs_content',
            'size' => 1024,
            'provider' => 'ipfs'
        );
    }
    
    public function delete($file_path) {
        // IPFS doesn't support deletion, but we can unpin
        return array(
            'success' => true,
            'provider' => 'ipfs'
        );
    }
    
    public function get_url($file_path) {
        return $this->config['gateway'] . $file_path;
    }
    
    public function get_stats() {
        return array(
            'total_size' => 0,
            'file_count' => 0,
            'available_space' => 0
        );
    }
} 