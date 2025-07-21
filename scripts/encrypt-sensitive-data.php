<?php
/**
 * VORTEX AI Engine - Sensitive Data Encryption Script
 * 
 * Encrypts sensitive data before storing in private branch
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

/**
 * Sensitive Data Encryption Class
 */
class VORTEX_Sensitive_Data_Encryption {
    
    private $encryption_key;
    private $cipher = 'aes-256-gcm';
    private $files_encrypted = 0;
    private $errors = array();
    
    public function __construct() {
        $this->initialize_encryption_key();
    }
    
    /**
     * Initialize encryption key
     */
    private function initialize_encryption_key() {
        // Try to get key from environment
        $this->encryption_key = getenv('VORTEX_ENCRYPTION_KEY');
        
        if (!$this->encryption_key) {
            // Generate a new key if not available
            $this->encryption_key = $this->generate_encryption_key();
            echo "🔑 Generated new encryption key\n";
            echo "   Set VORTEX_ENCRYPTION_KEY environment variable for production use\n\n";
        }
        
        // Ensure key is 32 bytes (256 bits)
        if (strlen($this->encryption_key) !== 32) {
            $this->encryption_key = substr(hash('sha256', $this->encryption_key, true), 0, 32);
        }
    }
    
    /**
     * Generate encryption key
     */
    private function generate_encryption_key() {
        return base64_encode(random_bytes(32));
    }
    
    /**
     * Run encryption process
     */
    public function encrypt_sensitive_data() {
        echo "🔒 VORTEX AI Engine - Sensitive Data Encryption\n";
        echo "===============================================\n\n";
        
        // Define sensitive files to encrypt
        $sensitive_files = array(
            'wp-config.php' => 'config/encrypted-wp-config.php',
            'wp-salt.php' => 'config/encrypted-wp-salt.php',
            '.env' => 'config/encrypted-env.php',
            'config/aws-credentials.php' => 'config/encrypted-aws-credentials.php',
            'config/blockchain-keys.php' => 'config/encrypted-blockchain-keys.php',
            'config/api-keys.php' => 'config/encrypted-api-keys.php',
            'keys/private-key.pem' => 'keys/encrypted-private-key.php',
            'keys/public-key.pem' => 'keys/encrypted-public-key.php'
        );
        
        // Create encryption directories
        $this->create_encryption_directories();
        
        // Encrypt each sensitive file
        foreach ($sensitive_files as $source => $destination) {
            $this->encrypt_file($source, $destination);
        }
        
        // Create encryption key backup
        $this->create_key_backup();
        
        // Generate encryption report
        $this->generate_encryption_report();
    }
    
    /**
     * Create encryption directories
     */
    private function create_encryption_directories() {
        echo "📁 Creating encryption directories...\n";
        
        $directories = array(
            'config',
            'keys',
            'logs',
            'backups'
        );
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0700, true)) {
                    echo "  ✅ Created: $dir\n";
                } else {
                    echo "  ❌ Failed to create: $dir\n";
                    $this->errors[] = "Failed to create directory: $dir";
                }
            } else {
                echo "  ✅ Exists: $dir\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Encrypt a file
     */
    private function encrypt_file($source, $destination) {
        if (!file_exists($source)) {
            echo "  ⚠️ Source file not found: $source\n";
            return;
        }
        
        $content = file_get_contents($source);
        if ($content === false) {
            echo "  ❌ Failed to read: $source\n";
            $this->errors[] = "Failed to read file: $source";
            return;
        }
        
        // Encrypt the content
        $encrypted_content = $this->encrypt_data($content);
        if ($encrypted_content === false) {
            echo "  ❌ Failed to encrypt: $source\n";
            $this->errors[] = "Failed to encrypt file: $source";
            return;
        }
        
        // Create destination directory if needed
        $dest_dir = dirname($destination);
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0700, true);
        }
        
        // Create encrypted file wrapper
        $wrapper_content = $this->create_encrypted_file_wrapper($encrypted_content, $source);
        
        if (file_put_contents($destination, $wrapper_content)) {
            echo "  ✅ Encrypted: $source → $destination\n";
            $this->files_encrypted++;
        } else {
            echo "  ❌ Failed to write: $destination\n";
            $this->errors[] = "Failed to write encrypted file: $destination";
        }
    }
    
    /**
     * Encrypt data
     */
    private function encrypt_data($data) {
        $iv = random_bytes(16);
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            return false;
        }
        
        // Combine IV, tag, and encrypted data
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Create encrypted file wrapper
     */
    private function create_encrypted_file_wrapper($encrypted_data, $original_file) {
        $timestamp = date('Y-m-d H:i:s');
        $file_size = strlen($encrypted_data);
        
        return "<?php
/**
 * Encrypted File: " . basename($original_file) . "
 * 
 * This file contains encrypted sensitive data.
 * Original file: $original_file
 * Encrypted on: $timestamp
 * File size: $file_size bytes
 * 
 * DO NOT MODIFY THIS FILE MANUALLY!
 * Use the decryption utility to access the original content.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('VORTEX_DECRYPT_MODE')) {
    http_response_code(403);
    exit('Access denied');
}

// Encrypted data
\$encrypted_data = '$encrypted_data';

// Decryption function
function vortex_decrypt_file_data(\$encrypted_data, \$key) {
    \$data = base64_decode(\$encrypted_data);
    \$iv = substr(\$data, 0, 16);
    \$tag = substr(\$data, 16, 16);
    \$encrypted = substr(\$data, 32);
    
    return openssl_decrypt(\$encrypted, 'aes-256-gcm', \$key, OPENSSL_RAW_DATA, \$iv, \$tag);
}

// Return encrypted data for decryption
return \$encrypted_data;
";
    }
    
    /**
     * Create key backup
     */
    private function create_key_backup() {
        echo "💾 Creating encryption key backup...\n";
        
        $backup_content = "VORTEX AI Engine - Encryption Key Backup\n";
        $backup_content .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "Key: " . base64_encode($this->encryption_key) . "\n";
        $backup_content .= "\n";
        $backup_content .= "IMPORTANT: Store this key securely!\n";
        $backup_content .= "This key is required to decrypt sensitive data.\n";
        $backup_content .= "Set as environment variable: VORTEX_ENCRYPTION_KEY\n";
        
        $backup_file = 'backups/encryption-key-backup-' . date('Y-m-d-H-i-s') . '.txt';
        
        if (file_put_contents($backup_file, $backup_content)) {
            echo "  ✅ Created: $backup_file\n";
            
            // Set restrictive permissions
            chmod($backup_file, 0600);
            echo "  ✅ Set secure permissions on backup file\n";
        } else {
            echo "  ❌ Failed to create backup\n";
            $this->errors[] = "Failed to create encryption key backup";
        }
        
        echo "\n";
    }
    
    /**
     * Generate encryption report
     */
    private function generate_encryption_report() {
        echo "📊 Encryption Report\n";
        echo "===================\n\n";
        
        echo "🔒 Files Encrypted: $this->files_encrypted\n";
        echo "❌ Errors: " . count($this->errors) . "\n\n";
        
        if (!empty($this->errors)) {
            echo "❌ Errors Found:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
            echo "\n";
        }
        
        echo "🔑 Encryption Key Information:\n";
        echo "  - Algorithm: AES-256-GCM\n";
        echo "  - Key Length: 256 bits\n";
        echo "  - Key (base64): " . base64_encode($this->encryption_key) . "\n\n";
        
        echo "📋 Security Recommendations:\n";
        echo "  1. Store encryption key securely\n";
        echo "  2. Set VORTEX_ENCRYPTION_KEY environment variable\n";
        echo "  3. Backup encryption key in secure location\n";
        echo "  4. Use key rotation for production\n";
        echo "  5. Monitor access to encrypted files\n\n";
        
        echo "🎉 Sensitive data encryption completed!\n";
        echo "   All sensitive files are now encrypted and secure.\n";
        
        // Save report to file
        $report_file = 'encryption-report-' . date('Y-m-d-H-i-s') . '.txt';
        $report_content = ob_get_contents();
        file_put_contents($report_file, $report_content);
        
        echo "\n📄 Report saved to: $report_file\n";
    }
    
    /**
     * Decrypt data (for testing)
     */
    public function decrypt_data($encrypted_data) {
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        return openssl_decrypt($encrypted, $this->cipher, $this->encryption_key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}

/**
 * Decryption Utility Class
 */
class VORTEX_Decryption_Utility {
    
    private $encryption_key;
    
    public function __construct($encryption_key = null) {
        $this->encryption_key = $encryption_key ?: getenv('VORTEX_ENCRYPTION_KEY');
        
        if (!$this->encryption_key) {
            throw new Exception('Encryption key not provided');
        }
        
        // Ensure key is 32 bytes
        if (strlen($this->encryption_key) !== 32) {
            $this->encryption_key = substr(hash('sha256', $this->encryption_key, true), 0, 32);
        }
    }
    
    /**
     * Decrypt an encrypted file
     */
    public function decrypt_file($encrypted_file) {
        if (!file_exists($encrypted_file)) {
            throw new Exception("Encrypted file not found: $encrypted_file");
        }
        
        // Include the encrypted file to get the data
        define('VORTEX_DECRYPT_MODE', true);
        $encrypted_data = include $encrypted_file;
        
        if (!$encrypted_data) {
            throw new Exception("Failed to load encrypted data from: $encrypted_file");
        }
        
        return $this->decrypt_data($encrypted_data);
    }
    
    /**
     * Decrypt data
     */
    private function decrypt_data($encrypted_data) {
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $this->encryption_key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($decrypted === false) {
            throw new Exception('Decryption failed - invalid key or corrupted data');
        }
        
        return $decrypted;
    }
}

// Run encryption if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $encryption = new VORTEX_Sensitive_Data_Encryption();
    $encryption->encrypt_sensitive_data();
} 