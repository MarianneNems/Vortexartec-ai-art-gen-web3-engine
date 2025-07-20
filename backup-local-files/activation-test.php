<?php
/**
 * VORTEX AI Engine - Activation Test
 * Run this in your WordPress root directory
 */

// Load WordPress
require_once("wp-load.php");

echo "<h1>VORTEX AI Engine Activation Test</h1>";
echo "<pre>";

// Check if plugin is active
if (is_plugin_active("vortex-ai-engine/vortex-ai-engine.php")) {
    echo "✅ Plugin is ACTIVE\n";
} else {
    echo "❌ Plugin is NOT ACTIVE\n";
    
    // Try to activate
    echo "\nAttempting to activate...\n";
    $result = activate_plugin("vortex-ai-engine/vortex-ai-engine.php");
    
    if (is_wp_error($result)) {
        echo "❌ Activation failed: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ Activation successful!\n";
    }
}

echo "</pre>";
?>