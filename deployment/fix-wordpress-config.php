<?php
/**
 * WordPress Configuration Fix Script
 * 
 * Automatically fixes common WordPress configuration issues
 * that prevent VORTEX AI Engine from activating properly
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, simulate basic environment
    define('ABSPATH', dirname(__FILE__) . '/../../');
    if (file_exists(ABSPATH . 'wp-config.php')) {
        require_once ABSPATH . 'wp-config.php';
    } else {
        echo "❌ WordPress configuration not found. Please run this from within WordPress.\n";
        exit;
    }
}

/**
 * WordPress Configuration Fix Class
 */
class WordPress_Config_Fix {
    
    private $fixes_applied = array();
    private $errors = array();
    private $warnings = array();
    
    public function run_fixes() {
        echo "🔧 WordPress Configuration Fix Script\n";
        echo "=====================================\n\n";
        
        // Check if we can write to wp-config.php
        if (!$this->can_write_wp_config()) {
            echo "❌ Cannot write to wp-config.php. Please check file permissions.\n";
            exit;
        }
        
        // Fix missing wp-salt.php
        $this->fix_wp_salt_file();
        
        // Fix debug settings
        $this->fix_debug_settings();
        
        // Fix memory limit
        $this->fix_memory_settings();
        
        // Fix file permissions
        $this->fix_file_permissions();
        
        // Fix database settings
        $this->fix_database_settings();
        
        // Fix security settings
        $this->fix_security_settings();
        
        // Generate fix report
        $this->generate_fix_report();
    }
    
    /**
     * Check if we can write to wp-config.php
     */
    private function can_write_wp_config() {
        $wp_config_path = ABSPATH . 'wp-config.php';
        return is_writable($wp_config_path);
    }
    
    /**
     * Fix missing wp-salt.php file
     */
    private function fix_wp_salt_file() {
        echo "🔑 Fixing wp-salt.php file...\n";
        
        $wp_salt_path = ABSPATH . 'wp-salt.php';
        
        if (file_exists($wp_salt_path)) {
            $this->add_success('wp-salt.php already exists');
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
        
        if (file_put_contents($wp_salt_path, $wp_salt_content)) {
            $this->add_success('Created wp-salt.php with secure authentication keys');
            $this->fixes_applied[] = 'Created wp-salt.php file';
        } else {
            $this->add_error('Failed to create wp-salt.php file');
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
        
        $wp_config_path = ABSPATH . 'wp-config.php';
        $wp_config_content = file_get_contents($wp_config_path);
        
        $debug_settings = array(
            'WP_DEBUG' => 'false',
            'WP_DEBUG_LOG' => 'true',
            'WP_DEBUG_DISPLAY' => 'false',
            'WP_DEBUG_DISPLAY' => 'false'
        );
        
        foreach ($debug_settings as $setting => $value) {
            if (strpos($wp_config_content, "define('$setting'") === false) {
                // Add debug setting before "That's all, stop editing!"
                $insert_point = "/* That's all, stop editing! Happy blogging. */";
                $new_setting = "define('$setting', $value);\n";
                
                $wp_config_content = str_replace($insert_point, $new_setting . $insert_point, $wp_config_content);
                $this->add_success("Added $setting = $value");
                $this->fixes_applied[] = "Added $setting setting";
            } else {
                $this->add_success("$setting already configured");
            }
        }
        
        // Save updated wp-config.php
        if (file_put_contents($wp_config_path, $wp_config_content)) {
            $this->add_success('Updated wp-config.php with debug settings');
        } else {
            $this->add_error('Failed to update wp-config.php');
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
            $this->add_warning("Current memory limit: $current_memory (recommended: 256M)");
            $this->add_recommendation("Increase memory_limit in php.ini to 256M");
        } else {
            $this->add_success("Memory limit: $current_memory (adequate)");
        }
        
        // Add WordPress memory limit setting
        $wp_config_path = ABSPATH . 'wp-config.php';
        $wp_config_content = file_get_contents($wp_config_path);
        
        if (strpos($wp_config_content, "define('WP_MEMORY_LIMIT'") === false) {
            $insert_point = "/* That's all, stop editing! Happy blogging. */";
            $memory_setting = "define('WP_MEMORY_LIMIT', '256M');\n";
            
            $wp_config_content = str_replace($insert_point, $memory_setting . $insert_point, $wp_config_content);
            
            if (file_put_contents($wp_config_path, $wp_config_content)) {
                $this->add_success('Added WP_MEMORY_LIMIT setting');
                $this->fixes_applied[] = 'Added WP_MEMORY_LIMIT setting';
            }
        } else {
            $this->add_success('WP_MEMORY_LIMIT already configured');
        }
    }
    
    /**
     * Fix file permissions
     */
    private function fix_file_permissions() {
        echo "📁 Fixing file permissions...\n";
        
        $paths_to_fix = array(
            ABSPATH . 'wp-content' => 0755,
            ABSPATH . 'wp-content/plugins' => 0755,
            ABSPATH . 'wp-content/uploads' => 0755,
            ABSPATH . 'wp-config.php' => 0644
        );
        
        foreach ($paths_to_fix as $path => $permission) {
            if (file_exists($path)) {
                $current_permission = substr(sprintf('%o', fileperms($path)), -4);
                $target_permission = sprintf('%04o', $permission);
                
                if ($current_permission !== $target_permission) {
                    if (chmod($path, $permission)) {
                        $this->add_success("Fixed permissions for $path: $current_permission → $target_permission");
                        $this->fixes_applied[] = "Fixed permissions for $path";
                    } else {
                        $this->add_error("Failed to fix permissions for $path");
                    }
                } else {
                    $this->add_success("Permissions for $path are correct: $current_permission");
                }
            } else {
                $this->add_warning("Path not found: $path");
            }
        }
    }
    
    /**
     * Fix database settings
     */
    private function fix_database_settings() {
        echo "🗄️ Checking database settings...\n";
        
        global $wpdb;
        
        if (!$wpdb) {
            $this->add_error('WordPress database object not available');
            return;
        }
        
        // Test database connection
        $test_query = $wpdb->get_var("SELECT 1");
        if ($test_query) {
            $this->add_success('Database connection is working');
        } else {
            $this->add_error('Database connection failed: ' . $wpdb->last_error);
            return;
        }
        
        // Check if we can create tables
        $test_table = $wpdb->prefix . 'vortex_test_table';
        $create_result = $wpdb->query("CREATE TABLE IF NOT EXISTS $test_table (id INT PRIMARY KEY)");
        
        if ($create_result !== false) {
            $this->add_success('Database write permissions are working');
            // Clean up test table
            $wpdb->query("DROP TABLE IF EXISTS $test_table");
        } else {
            $this->add_error('Database write permissions failed: ' . $wpdb->last_error);
        }
    }
    
    /**
     * Fix security settings
     */
    private function fix_security_settings() {
        echo "🔒 Fixing security settings...\n";
        
        $wp_config_path = ABSPATH . 'wp-config.php';
        $wp_config_content = file_get_contents($wp_config_path);
        
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
                $this->add_success("Added $setting = $value");
                $this->fixes_applied[] = "Added $setting setting";
            } else {
                $this->add_success("$setting already configured");
            }
        }
        
        // Save updated wp-config.php
        if (file_put_contents($wp_config_path, $wp_config_content)) {
            $this->add_success('Updated wp-config.php with security settings');
        } else {
            $this->add_error('Failed to update wp-config.php');
        }
    }
    
    /**
     * Add success message
     */
    private function add_success($message) {
        echo "  ✅ $message\n";
    }
    
    /**
     * Add warning message
     */
    private function add_warning($message) {
        echo "  ⚠️ $message\n";
        $this->warnings[] = $message;
    }
    
    /**
     * Add error message
     */
    private function add_error($message) {
        echo "  ❌ $message\n";
        $this->errors[] = $message;
    }
    
    /**
     * Add recommendation
     */
    private function add_recommendation($message) {
        echo "  💡 $message\n";
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
        $report_file = ABSPATH . 'wp-content/uploads/wordpress-config-fix-' . date('Y-m-d-H-i-s') . '.txt';
        $report_content = ob_get_contents();
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
    $fix = new WordPress_Config_Fix();
    $fix->run_fixes();
} 