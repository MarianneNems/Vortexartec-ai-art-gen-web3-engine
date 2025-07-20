<?php
/**
 * Simple VORTEX AI Engine Activation Test
 * This will help identify the specific activation issue
 */

echo "🔍 VORTEX AI Engine - Simple Activation Test\n";
echo "============================================\n\n";

// Test 1: Check PHP version
echo "1. PHP Version Check:\n";
echo "   Current PHP version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "   ✅ PHP version is compatible (7.4+ required)\n";
} else {
    echo "   ❌ PHP version too old (7.4+ required)\n";
    exit(1);
}

// Test 2: Check if main plugin file exists and is readable
echo "\n2. Plugin File Check:\n";
if (file_exists('vortex-ai-engine.php')) {
    echo "   ✅ Main plugin file exists\n";
    if (is_readable('vortex-ai-engine.php')) {
        echo "   ✅ Main plugin file is readable\n";
    } else {
        echo "   ❌ Main plugin file is not readable\n";
        exit(1);
    }
} else {
    echo "   ❌ Main plugin file not found\n";
    exit(1);
}

// Test 3: Check for syntax errors in main plugin file
echo "\n3. Syntax Check:\n";
$output = shell_exec("php -l vortex-ai-engine.php 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "   ✅ Main plugin file has no syntax errors\n";
} else {
    echo "   ❌ Syntax errors found in main plugin file:\n";
    echo "   $output\n";
    exit(1);
}

// Test 4: Check required include files
echo "\n4. Required Files Check:\n";
$required_files = [
    'includes/class-vortex-db-setup.php',
    'includes/class-vortex-logger.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file exists\n";
        // Check syntax
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "   ✅ $file has no syntax errors\n";
        } else {
            echo "   ❌ Syntax errors in $file:\n";
            echo "   $output\n";
        }
    } else {
        echo "   ❌ $file missing\n";
    }
}

// Test 5: Try to include the main plugin file and catch any errors
echo "\n5. Plugin Loading Test:\n";
try {
    // Set up basic constants that the plugin expects
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__FILE__) . '/');
    }
    
    // Capture any output or errors
    ob_start();
    
    // Include the plugin file
    include 'vortex-ai-engine.php';
    
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "   📝 Plugin produced output:\n";
        echo "   $output\n";
    }
    
    echo "   ✅ Plugin file loaded successfully\n";
    
} catch (ParseError $e) {
    echo "   ❌ Parse error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "   ❌ Fatal error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

// Test 6: Check for common WordPress plugin issues
echo "\n6. WordPress Compatibility Check:\n";

// Check if plugin header is correct
$plugin_content = file_get_contents('vortex-ai-engine.php');
if (strpos($plugin_content, 'Plugin Name:') !== false) {
    echo "   ✅ Plugin header found\n";
} else {
    echo "   ❌ Plugin header missing\n";
}

if (strpos($plugin_content, 'register_activation_hook') !== false) {
    echo "   ✅ Activation hook found\n";
} else {
    echo "   ❌ Activation hook missing\n";
}

// Test 7: Check file permissions
echo "\n7. File Permissions Check:\n";
$files_to_check = [
    'vortex-ai-engine.php',
    'includes/class-vortex-db-setup.php',
    'includes/class-vortex-logger.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        echo "   $file permissions: $perms_octal\n";
        
        if (is_readable($file)) {
            echo "   ✅ $file is readable\n";
        } else {
            echo "   ❌ $file is not readable\n";
        }
    }
}

echo "\n🎯 Test Complete!\n";
echo "If you see any ❌ errors above, those are likely preventing activation.\n";
echo "Common solutions:\n";
echo "1. Fix any syntax errors\n";
echo "2. Ensure all required files exist\n";
echo "3. Check file permissions\n";
echo "4. Verify PHP version compatibility\n"; 