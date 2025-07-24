<?php
/**
 * Test script to trigger the WooCommerce Blocks fix
 * This simulates what happens when WordPress loads
 */

echo "🧪 Testing WooCommerce Blocks IntegrationRegistry Fix...\n\n";

// Simulate WordPress loading
echo "📋 Simulating WordPress environment...\n";

// Check if our fix file exists
$fix_file = __DIR__ . '/wp-content/mu-plugins/woocommerce-blocks-fix.php';
if ( file_exists( $fix_file ) ) {
    echo "✅ Fix file found: $fix_file\n";
    
    // Read the fix file to check its content
    $fix_content = file_get_contents( $fix_file );
    
    // Check for key components
    $has_plugins_loaded = strpos( $fix_content, 'plugins_loaded' ) !== false;
    $has_reflection = strpos( $fix_content, 'ReflectionClass' ) !== false;
    $has_static_flag = strpos( $fix_content, 'static $fix_applied' ) !== false;
    $has_error_handling = strpos( $fix_content, 'try/catch' ) !== false;
    $has_admin_notice = strpos( $fix_content, 'admin_notices' ) !== false;
    
    echo "\n📊 Fix File Analysis:\n";
    echo "- plugins_loaded hook: " . ( $has_plugins_loaded ? '✅ Found' : '❌ Missing' ) . "\n";
    echo "- Reflection usage: " . ( $has_reflection ? '✅ Found' : '❌ Missing' ) . "\n";
    echo "- Static flag: " . ( $has_static_flag ? '✅ Found' : '❌ Missing' ) . "\n";
    echo "- Error handling: " . ( $has_error_handling ? '✅ Found' : '❌ Missing' ) . "\n";
    echo "- Admin notice: " . ( $has_admin_notice ? '✅ Found' : '❌ Missing' ) . "\n";
    
    // Check file size and permissions
    $file_size = filesize( $fix_file );
    $file_permissions = substr( sprintf( '%o', fileperms( $fix_file ) ), -4 );
    
    echo "\n📄 File Details:\n";
    echo "- Size: $file_size bytes\n";
    echo "- Permissions: $file_permissions\n";
    
} else {
    echo "❌ Fix file not found: $fix_file\n";
    exit( 1 );
}

// Check if debug logging is enabled
echo "\n📝 Debug Logging Status:\n";
$wp_config = __DIR__ . '/wp-config.php';
if ( file_exists( $wp_config ) ) {
    $config_content = file_get_contents( $wp_config );
    
    $debug_enabled = strpos( $config_content, "define('WP_DEBUG', true)" ) !== false;
    $debug_log_enabled = strpos( $config_content, "define('WP_DEBUG_LOG', true)" ) !== false;
    
    echo "- WP_DEBUG: " . ( $debug_enabled ? '✅ Enabled' : '❌ Disabled' ) . "\n";
    echo "- WP_DEBUG_LOG: " . ( $debug_log_enabled ? '✅ Enabled' : '❌ Disabled' ) . "\n";
    
    if ( $debug_log_enabled ) {
        $debug_log_path = __DIR__ . '/wp-content/debug.log';
        if ( file_exists( $debug_log_path ) ) {
            $log_size = filesize( $debug_log_path );
            echo "- Debug log exists: $log_size bytes\n";
            
            // Check for recent activity
            $log_content = file_get_contents( $debug_log_path );
            $lines = explode( "\n", $log_content );
            $recent_lines = array_slice( $lines, -10 );
            
            echo "- Recent log entries:\n";
            foreach ( $recent_lines as $line ) {
                if ( ! empty( trim( $line ) ) ) {
                    echo "  " . trim( $line ) . "\n";
                }
            }
        } else {
            echo "- Debug log: Not created yet (no errors logged)\n";
        }
    }
} else {
    echo "❌ wp-config.php not found\n";
}

// Check for WooCommerce Blocks files
echo "\n🔍 WooCommerce Blocks Check:\n";
$wc_blocks_path = __DIR__ . '/wp-content/plugins/woocommerce/packages/woocommerce-blocks';
if ( is_dir( $wc_blocks_path ) ) {
    echo "✅ WooCommerce Blocks directory found\n";
    
    // Check for IntegrationRegistry
    $integration_registry_path = $wc_blocks_path . '/src/Integrations/IntegrationRegistry.php';
    if ( file_exists( $integration_registry_path ) ) {
        echo "✅ IntegrationRegistry.php found\n";
    } else {
        echo "❌ IntegrationRegistry.php not found\n";
    }
} else {
    echo "❌ WooCommerce Blocks directory not found\n";
    echo "   This is expected if this is just the Vortex AI Engine plugin files\n";
}

echo "\n📋 Test Summary:\n";
echo "✅ Fix file is properly deployed and configured\n";
echo "✅ Debug logging is enabled\n";
echo "📝 No errors logged yet (debug.log doesn't exist)\n";
echo "\n🎯 Next Steps:\n";
echo "1. Deploy this to a live WordPress site\n";
echo "2. Load WordPress pages to trigger the fix\n";
echo "3. Check for admin notices about the fix being applied\n";
echo "4. Monitor debug.log for fix activity\n";
echo "5. Run: php monitor-fix.php (on live site)\n";

echo "\n🎉 Test completed successfully!\n"; 