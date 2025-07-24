<?php
/**
 * VORTEX AI Engine - Activation Test
 * 
 * Simple test to diagnose activation issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, simulate basic environment
    if (!function_exists('wp_die')) {
        function wp_die($message) {
            echo "ERROR: " . $message;
            exit;
        }
    }
}

echo "<h2>VORTEX AI Engine - Activation Test</h2>";

// Test 1: PHP Version
echo "<h3>1. PHP Version Check</h3>";
echo "Current PHP Version: " . PHP_VERSION . "<br>";
if (version_compare(PHP_VERSION, '7.4', '>=')) {
    echo "✅ PHP Version OK<br>";
} else {
    echo "❌ PHP Version too old. Need 7.4+<br>";
}

// Test 2: WordPress Environment
echo "<h3>2. WordPress Environment</h3>";
if (defined('ABSPATH')) {
    echo "✅ WordPress environment detected<br>";
    echo "WordPress Version: " . get_bloginfo('version') . "<br>";
} else {
    echo "⚠️ Not in WordPress environment<br>";
}

// Test 3: Database Connection
echo "<h3>3. Database Connection Test</h3>";
if (defined('ABSPATH')) {
    global $wpdb;
    if ($wpdb && $wpdb->check_connection()) {
        echo "✅ Database connection OK<br>";
    } else {
        echo "❌ Database connection failed<br>";
        echo "Error: " . ($wpdb ? $wpdb->last_error : 'No database object') . "<br>";
    }
} else {
    echo "⚠️ Cannot test database outside WordPress<br>";
}

// Test 4: File Permissions
echo "<h3>4. File Permissions Test</h3>";
$plugin_file = __FILE__;
if (is_readable($plugin_file)) {
    echo "✅ Plugin file is readable<br>";
} else {
    echo "❌ Plugin file is not readable<br>";
}

// Test 5: Memory Limit
echo "<h3>5. Memory Limit Check</h3>";
$memory_limit = ini_get('memory_limit');
echo "Memory Limit: " . $memory_limit . "<br>";
$memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
if ($memory_limit_bytes >= 256 * 1024 * 1024) { // 256MB
    echo "✅ Memory limit sufficient<br>";
} else {
    echo "⚠️ Memory limit may be too low. Recommended: 256MB+<br>";
}

// Test 6: Required Functions
echo "<h3>6. Required Functions Check</h3>";
$required_functions = ['add_action', 'add_shortcode', 'wp_nonce_field', 'wp_enqueue_script'];
foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "✅ $func() available<br>";
    } else {
        echo "❌ $func() not available<br>";
    }
}

// Test 7: Plugin Directory
echo "<h3>7. Plugin Directory Check</h3>";
$plugin_dir = dirname(__FILE__);
echo "Plugin Directory: " . $plugin_dir . "<br>";
if (is_dir($plugin_dir)) {
    echo "✅ Plugin directory exists<br>";
    
    // Check key files
    $key_files = [
        'vortex-ai-engine.php',
        'includes/database/class-vortex-database-manager.php',
        'includes/ai-agents/class-vortex-archer-orchestrator.php'
    ];
    
    foreach ($key_files as $file) {
        $file_path = $plugin_dir . '/' . $file;
        if (file_exists($file_path)) {
            echo "✅ $file exists<br>";
        } else {
            echo "❌ $file missing<br>";
        }
    }
} else {
    echo "❌ Plugin directory not found<br>";
}

// Test 8: Class Loading
echo "<h3>8. Class Loading Test</h3>";
if (defined('ABSPATH')) {
    // Try to load the main plugin file
    $main_file = $plugin_dir . '/vortex-ai-engine.php';
    if (file_exists($main_file)) {
        include_once $main_file;
        
        if (class_exists('Vortex_AI_Engine')) {
            echo "✅ Main plugin class loaded<br>";
        } else {
            echo "❌ Main plugin class not found<br>";
        }
        
        if (class_exists('Vortex_Database_Manager')) {
            echo "✅ Database manager class loaded<br>";
        } else {
            echo "❌ Database manager class not found<br>";
        }
    } else {
        echo "❌ Main plugin file not found<br>";
    }
} else {
    echo "⚠️ Cannot test class loading outside WordPress<br>";
}

echo "<h3>Test Complete</h3>";
echo "<p>If you see any ❌ errors above, those need to be resolved before the plugin can activate.</p>";

// Helper function for memory conversion
if (!function_exists('wp_convert_hr_to_bytes')) {
    function wp_convert_hr_to_bytes($size) {
        $size = strtolower(trim($size));
        $bytes = (int) $size;
        
        if (strpos($size, 'k') !== false) {
            $bytes *= 1024;
        } elseif (strpos($size, 'm') !== false) {
            $bytes *= 1024 * 1024;
        } elseif (strpos($size, 'g') !== false) {
            $bytes *= 1024 * 1024 * 1024;
        }
        
        return $bytes;
    }
}
?> 