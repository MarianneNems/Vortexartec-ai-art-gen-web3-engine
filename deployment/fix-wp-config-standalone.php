<?php
/**
 * Standalone WordPress Configuration Fix Script
 * 
 * Fixes WordPress configuration issues without requiring WordPress to be loaded
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

/**
 * WordPress Configuration Fix Class
 */
class WordPress_Config_Fix_Standalone {
    
    private $fixes_applied = array();
    private $errors = array();
    private $warnings = array();
    private $wp_config_path;
    private $wp_salt_path;
    
    public function __construct() {
        $this->wp_config_path = dirname(__FILE__) . '/../wp-config.php';
        $this->wp_salt_path = dirname(__FILE__) . '/../wp-salt.php';
    }
    
    public function run_fixes() {
        echo "🔧 Standalone WordPress Configuration Fix Script\n";
        echo "===============================================\n\n";
        
        // Check if wp-config.php exists
        if (!file_exists($this->wp_config_path)) {
            echo "❌ wp-config.php not found at: $this->wp_config_path\n";
            echo "   Please run this script from the WordPress root directory.\n";
            exit;
        }
        
        echo "📁 Found wp-config.php at: $this->wp_config_path\n\n";
        
        // Fix missing wp-salt.php
        $this->fix_wp_salt_file();
        
        // Fix debug settings
        $this->fix_debug_settings();
        
        // Fix memory settings
        $this->fix_memory_settings();
        
        // Fix security settings
        $this->fix_security_settings();
        
        // Fix file permissions
        $this->fix_file_permissions();
        
        // Generate fix report
        $this->generate_fix_report();
    }
    
    /**
     * Fix missing wp-salt.php file
     */
    private function fix_wp_salt_file() {
        echo "🔑 Fixing wp-salt.php file...\n";
        
        if (file_exists($this->wp_salt_path)) {
            echo "  ✅ wp-salt.php already exists\n";
            return;
        }
        
        // Generate secure authentication keys
        $auth_keys = $this->generate_auth_keys();
        
        $wp_salt_content = "<?php\n";
        $wp_salt_content .= "/**\n";
        $wp_salt_content .= " * WordPress Authentication Keys and Salts\n";
        $wp_salt_content .= " * \n";
        $wp_salt_content .= " * These are used in the wp-config.php file for security.\n";
        $wp_salt_content .= " * Generated automatically by VORTEX AI Engine fix script.\n";
        $wp_salt_content .= " */\n\n";
        
        foreach ($auth_keys as $key => $value) {
            $wp_salt_content .= "define('$key', '$value');\n";
        }
        
        if (file_put_contents($this->wp_salt_path, $wp_salt_content)) {
            echo "  ✅ Created wp-salt.php with secure authentication keys\n";
            $this->fixes_applied[] = 'Created wp-salt.php file';
        } else {
            echo "  ❌ Failed to create wp-salt.php file\n";
            $this->errors[] = 'Failed to create wp-salt.php file';
        }
    }
    
    /**
     * Generate secure authentication keys
     */
    private function generate_auth_keys() {
        $keys = array();
        $key_names = array(
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT'
        );
        
        foreach ($key_names as $key_name) {
            $keys[$key_name] = $this->generate_random_string(64);
        }
        
        return $keys;
    }
    
    /**
     * Generate random string
     */
    private function generate_random_string($length = 64) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|;:,.<>?';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
    
    /**
     * Fix debug settings
     */
    private function fix_debug_settings() {
        echo "🐛 Fixing debug settings...\n";
        
        $wp_config_content = file_get_contents($this->wp_config_path);
        
        $debug_settings = array(
            'WP_DEBUG' => 'false',
            'WP_DEBUG_LOG' => 'true',
            'WP_DEBUG_DISPLAY' => 'false'
        );
        
        foreach ($debug_settings as $setting => $value) {
            if (strpos($wp_config_content, "define('$setting'") === false) {
                // Add debug setting before "That's all, stop editing!"
                $insert_point = "/* That's all, stop editing! Happy blogging. */";
                $new_setting = "define('$setting', $value);\n";
                
                $wp_config_content = str_replace($insert_point, $new_setting . $insert_point, $wp_config_content);
                echo "  ✅ Added $setting = $value\n";
                $this->fixes_applied[] = "Added $setting setting";
            } else {
                echo "  ✅ $setting already configured\n";
            }
        }
        
        // Save updated wp-config.php
        if (file_put_contents($this->wp_config_path, $wp_config_content)) {
            echo "  ✅ Updated wp-config.php with debug settings\n";
        } else {
            echo "  ❌ Failed to update wp-config.php\n";
            $this->errors[] = 'Failed to update wp-config.php';
        }
    }
    
    /**
     * Fix memory settings
     */
    private function fix_memory_settings() {
        echo "💾 Fixing memory settings...\n";
        
        // Check current memory limit
        $current_memory = ini_get('memory_limit');
        $memory_bytes = $this->parse_memory_limit($current_memory);
        
        if ($memory_bytes < 256 * 1024 * 1024) { // 256MB
            echo "  ⚠️ Current memory limit: $current_memory (recommended: 256M)\n";
            echo "  💡 Increase memory_limit in php.ini to 256M\n";
            $this->warnings[] = "Memory limit below 256MB: $current_memory";
        } else {
            echo "  ✅ Memory limit: $current_memory (adequate)\n";
        }
        
        // Add WordPress memory limit setting
        $wp_config_content = file_get_contents($this->wp_config_path);
        
        if (strpos($wp_config_content, "define('WP_MEMORY_LIMIT'") === false) {
            $insert_point = "/* That's all, stop editing! Happy blogging. */";
            $memory_setting = "define('WP_MEMORY_LIMIT', '256M');\n";
            
            $wp_config_content = str_replace($insert_point, $memory_setting . $insert_point, $wp_config_content);
            
            if (file_put_contents($this->wp_config_path, $wp_config_content)) {
                echo "  ✅ Added WP_MEMORY_LIMIT setting\n";
                $this->fixes_applied[] = 'Added WP_MEMORY_LIMIT setting';
            } else {
                echo "  ❌ Failed to add WP_MEMORY_LIMIT setting\n";
                $this->errors[] = 'Failed to add WP_MEMORY_LIMIT setting';
            }
        } else {
            echo "  ✅ WP_MEMORY_LIMIT already configured\n";
        }
    }
    
    /**
     * Fix security settings
     */
    private function fix_security_settings() {
        echo "🔒 Fixing security settings...\n";
        
        $wp_config_content = file_get_contents($this->wp_config_path);
        
        $security_settings = array(
            'DISALLOW_FILE_EDIT' => 'true',
            'DISALLOW_FILE_MODS' => 'false', // Allow plugin installation
            'FORCE_SSL_ADMIN' => 'true',
            'AUTOMATIC_UPDATER_DISABLED' => 'false'
        );
        
        foreach ($security_settings as $setting => $value) {
            if (strpos($wp_config_content, "define('$setting'") === false) {
                $insert_point = "/* That's all, stop editing! Happy blogging. */";
                $security_setting = "define('$setting', $value);\n";
                
                $wp_config_content = str_replace($insert_point, $security_setting . $insert_point, $wp_config_content);
                echo "  ✅ Added $setting = $value\n";
                $this->fixes_applied[] = "Added $setting setting";
            } else {
                echo "  ✅ $setting already configured\n";
            }
        }
        
        // Save updated wp-config.php
        if (file_put_contents($this->wp_config_path, $wp_config_content)) {
            echo "  ✅ Updated wp-config.php with security settings\n";
        } else {
            echo "  ❌ Failed to update wp-config.php\n";
            $this->errors[] = 'Failed to update wp-config.php';
        }
    }
    
    /**
     * Fix file permissions
     */
    private function fix_file_permissions() {
        echo "📁 Fixing file permissions...\n";
        
        $wp_content_path = dirname($this->wp_config_path) . '/wp-content';
        $plugins_path = $wp_content_path . '/plugins';
        $uploads_path = $wp_content_path . '/uploads';
        
        $paths_to_fix = array(
            $wp_content_path => 0755,
            $plugins_path => 0755,
            $uploads_path => 0755,
            $this->wp_config_path => 0644,
            $this->wp_salt_path => 0644
        );
        
        foreach ($paths_to_fix as $path => $permission) {
            if (file_exists($path)) {
                $current_permission = substr(sprintf('%o', fileperms($path)), -4);
                $target_permission = sprintf('%04o', $permission);
                
                if ($current_permission !== $target_permission) {
                    if (chmod($path, $permission)) {
                        echo "  ✅ Fixed permissions for $path: $current_permission → $target_permission\n";
                        $this->fixes_applied[] = "Fixed permissions for $path";
                    } else {
                        echo "  ❌ Failed to fix permissions for $path\n";
                        $this->errors[] = "Failed to fix permissions for $path";
                    }
                } else {
                    echo "  ✅ Permissions for $path are correct: $current_permission\n";
                }
            } else {
                echo "  ⚠️ Path not found: $path\n";
                $this->warnings[] = "Path not found: $path";
            }
        }
    }
    
    /**
     * Generate fix report
     */
    private function generate_fix_report() {
        echo "\n📊 Fix Report\n";
        echo "=============\n\n";
        
        echo "🔧 Fixes Applied (" . count($this->fixes_applied) . "):\n";
        foreach ($this->fixes_applied as $fix) {
            echo "  ✓ $fix\n";
        }
        echo "\n";
        
        if (!empty($this->warnings)) {
            echo "⚠️ Warnings (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ $warning\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "❌ Errors (" . count($this->errors) . "):\n";
            foreach ($this->errors as $error) {
                echo "  ✗ $error\n";
            }
            echo "\n";
        }
        
        // Summary
        if (empty($this->errors)) {
            echo "🎉 WordPress configuration has been fixed!\n";
            echo "   VORTEX AI Engine should now activate successfully.\n\n";
            
            echo "📋 Next Steps:\n";
            echo "  1. Go to WordPress Admin → Plugins\n";
            echo "  2. Activate VORTEX AI Engine\n";
            echo "  3. Check the admin dashboard for any remaining issues\n";
        } else {
            echo "🔧 Some issues could not be automatically fixed.\n";
            echo "   Please address the errors above before activating VORTEX AI Engine.\n";
        }
        
        // Save report to file
        $report_file = dirname($this->wp_config_path) . '/wp-content/uploads/wordpress-config-fix-' . date('Y-m-d-H-i-s') . '.txt';
        $report_content = ob_get_contents();
        
        // Create uploads directory if it doesn't exist
        $uploads_dir = dirname($report_file);
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }
        
        file_put_contents($report_file, $report_content);
        echo "\n📄 Fix report saved to: $report_file\n";
    }
    
    /**
     * Parse memory limit
     */
    private function parse_memory_limit($memory_limit) {
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) substr($memory_limit, 0, -1);
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
}

// Run fixes if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $fix = new WordPress_Config_Fix_Standalone();
    $fix->run_fixes();
} 