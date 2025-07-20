<?php
/**
 * VORTEX AI Engine - Database Connection Test
 * This script tests the database connection to identify the issues found in the debug log
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Test database connection function
function test_vortex_database_connection() {
    echo "<h2>VORTEX AI Engine - Database Connection Test</h2>\n";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border: 1px solid #ddd;'>\n";
    
    // Test 1: Check if wp-config.php exists
    echo "<h3>1. WordPress Configuration Check</h3>\n";
    $wp_config_path = ABSPATH . 'wp-config.php';
    if (file_exists($wp_config_path)) {
        echo "✅ wp-config.php found at: " . $wp_config_path . "\n";
    } else {
        echo "❌ wp-config.php not found at: " . $wp_config_path . "\n";
        echo "   Looking for alternative locations...\n";
        
        // Check common alternative locations
        $alternative_paths = [
            dirname(ABSPATH) . '/wp-config.php',
            dirname(dirname(ABSPATH)) . '/wp-config.php',
            dirname(dirname(dirname(ABSPATH))) . '/wp-config.php'
        ];
        
        foreach ($alternative_paths as $path) {
            if (file_exists($path)) {
                echo "   ✅ Found wp-config.php at: " . $path . "\n";
                $wp_config_path = $path;
                break;
            }
        }
    }
    
    // Test 2: Try to load WordPress
    echo "\n<h3>2. WordPress Loading Test</h3>\n";
    if (file_exists($wp_config_path)) {
        try {
            // Load WordPress
            require_once($wp_config_path);
            
            if (defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_HOST')) {
                echo "✅ WordPress configuration loaded successfully\n";
                echo "   Database: " . DB_NAME . "\n";
                echo "   Host: " . DB_HOST . "\n";
                echo "   User: " . DB_USER . "\n";
                echo "   Password: " . (DB_PASSWORD ? '[SET]' : '[NOT SET]') . "\n";
            } else {
                echo "❌ WordPress configuration incomplete\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Error loading WordPress: " . $e->getMessage() . "\n";
            return false;
        }
    } else {
        echo "❌ Cannot test WordPress loading - wp-config.php not found\n";
        return false;
    }
    
    // Test 3: Test direct database connection
    echo "\n<h3>3. Direct Database Connection Test</h3>\n";
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        
        if ($mysqli->connect_error) {
            echo "❌ Database connection failed: " . $mysqli->connect_error . "\n";
            echo "   Error code: " . $mysqli->connect_errno . "\n";
            
            // Provide specific troubleshooting based on error code
            switch ($mysqli->connect_errno) {
                case 1045:
                    echo "   🔧 This is an access denied error. Check:\n";
                    echo "      - Database username and password\n";
                    echo "      - User permissions in MySQL\n";
                    echo "      - Host restrictions\n";
                    break;
                case 2002:
                    echo "   🔧 This is a connection timeout. Check:\n";
                    echo "      - Database host address\n";
                    echo "      - Network connectivity\n";
                    echo "      - Firewall settings\n";
                    break;
                case 1049:
                    echo "   🔧 Database doesn't exist. Check:\n";
                    echo "      - Database name is correct\n";
                    echo "      - Database has been created\n";
                    break;
                default:
                    echo "   🔧 Unknown connection error. Check MySQL logs.\n";
            }
            
            return false;
        } else {
            echo "✅ Database connection successful!\n";
            echo "   Server version: " . $mysqli->server_info . "\n";
            echo "   Client version: " . $mysqli->client_info . "\n";
            
            // Test basic query
            $result = $mysqli->query("SELECT 1 as test");
            if ($result) {
                echo "   ✅ Basic query test passed\n";
            } else {
                echo "   ❌ Basic query test failed: " . $mysqli->error . "\n";
            }
            
            $mysqli->close();
        }
    } catch (Exception $e) {
        echo "❌ Exception during database connection: " . $e->getMessage() . "\n";
        return false;
    }
    
    // Test 4: Test WordPress database connection
    echo "\n<h3>4. WordPress Database Connection Test</h3>\n";
    try {
        global $wpdb;
        
        if (isset($wpdb) && $wpdb instanceof wpdb) {
            $wpdb->suppress_errors();
            $result = $wpdb->get_var("SELECT 1");
            $wpdb->suppress_errors(false);
            
            if ($result === '1') {
                echo "✅ WordPress database connection working\n";
                
                // Test VORTEX AI Engine tables
                echo "\n<h3>5. VORTEX AI Engine Tables Check</h3>\n";
                $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}vortex_%'");
                
                if (empty($tables)) {
                    echo "⚠️  No VORTEX AI Engine tables found. This is normal for first installation.\n";
                } else {
                    echo "✅ Found VORTEX AI Engine tables:\n";
                    foreach ($tables as $table) {
                        $table_name = array_values((array)$table)[0];
                        echo "   - " . $table_name . "\n";
                    }
                }
                
                return true;
            } else {
                echo "❌ WordPress database query failed\n";
                return false;
            }
        } else {
            echo "❌ WordPress database object not available\n";
            return false;
        }
    } catch (Exception $e) {
        echo "❌ Exception during WordPress database test: " . $e->getMessage() . "\n";
        return false;
    }
    
    echo "</div>\n";
}

// Test 6: Environment Information
function test_vortex_environment() {
    echo "\n<h3>6. Environment Information</h3>\n";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border: 1px solid #ddd;'>\n";
    
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
    echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
    echo "Current Directory: " . getcwd() . "\n";
    echo "WordPress Version: " . (defined('get_bloginfo') ? get_bloginfo('version') : 'Not loaded') . "\n";
    
    // Check for required PHP extensions
    $required_extensions = ['mysqli', 'json', 'curl', 'openssl'];
    echo "\nRequired PHP Extensions:\n";
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            echo "   ✅ " . $ext . "\n";
        } else {
            echo "   ❌ " . $ext . " (Missing)\n";
        }
    }
    
    echo "</div>\n";
}

// Run the tests
if (php_sapi_name() === 'cli') {
    // Command line mode
    echo "VORTEX AI Engine - Database Connection Test\n";
    echo "==========================================\n\n";
    
    // Load WordPress if possible
    $wp_load_path = dirname(__FILE__) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    }
    
    test_vortex_database_connection();
    test_vortex_environment();
} else {
    // Web mode
    echo "<!DOCTYPE html>\n";
    echo "<html><head><title>VORTEX AI Engine - Database Test</title></head><body>\n";
    
    test_vortex_database_connection();
    test_vortex_environment();
    
    echo "</body></html>\n";
}
?> 