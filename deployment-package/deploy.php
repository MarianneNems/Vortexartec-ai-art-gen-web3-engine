<?php
/**
 * WooCommerce Blocks IntegrationRegistry Fix - Deployment Script
 * 
 * Run this script to deploy the fix to your WordPress site.
 * 
 * Usage: php deploy.php [wordpress-path]
 */

// Get WordPress path from command line or use current directory
$wp_path = isset( $argv[1] ) ? $argv[1] : dirname( __FILE__ ) . "/..";

echo "🚀 Deploying WooCommerce Blocks IntegrationRegistry Fix...\n\n";

// Check if we're in a WordPress directory
$wp_config = $wp_path . "/wp-config.php";
if ( ! file_exists( $wp_config ) ) {
    echo "❌ WordPress not found at: $wp_path\n";
    echo "Usage: php deploy.php [wordpress-path]\n";
    echo "Example: php deploy.php /path/to/wordpress\n\n";
    exit( 1 );
}

echo "✅ WordPress found at: $wp_path\n\n";

// Create mu-plugins directory if it doesn't exist
$mu_plugins_dir = $wp_path . "/wp-content/mu-plugins";
if ( ! is_dir( $mu_plugins_dir ) ) {
    echo "📁 Creating mu-plugins directory...\n";
    mkdir( $mu_plugins_dir, 0755, true );
}

// Copy fix file
$source_fix = __DIR__ . "/mu-plugins/woocommerce-blocks-fix.php";
$dest_fix = $mu_plugins_dir . "/woocommerce-blocks-fix.php";

if ( file_exists( $source_fix ) ) {
    copy( $source_fix, $dest_fix );
    echo "✅ Fix file deployed to: $dest_fix\n";
} else {
    echo "❌ Fix file not found in deployment package\n";
    exit( 1 );
}

// Copy test scripts
$test_scripts = array(
    "test-fix.php",
    "monitor-fix.php"
);

foreach ( $test_scripts as $script ) {
    $source = __DIR__ . "/$script";
    $dest = $wp_path . "/$script";
    
    if ( file_exists( $source ) ) {
        copy( $source, $dest );
        echo "✅ $script deployed to: $dest\n";
    }
}

// Check wp-config.php for debug settings
$config_content = file_get_contents( $wp_config );
$debug_enabled = strpos( $config_content, "define( 'WP_DEBUG', true )" ) !== false;
$debug_log_enabled = strpos( $config_content, "define( 'WP_DEBUG_LOG', true )" ) !== false;

echo "\n📊 Debug Logging Status:\n";
echo "- WP_DEBUG: " . ( $debug_enabled ? "✅ Enabled" : "❌ Disabled" ) . "\n";
echo "- WP_DEBUG_LOG: " . ( $debug_log_enabled ? "✅ Enabled" : "❌ Disabled" ) . "\n";

if ( ! $debug_enabled || ! $debug_log_enabled ) {
    echo "\n⚠️  Recommendation: Enable debug logging in wp-config.php:\n";
    echo "define( 'WP_DEBUG', true );\n";
    echo "define( 'WP_DEBUG_LOG', true );\n";
    echo "define( 'WP_DEBUG_DISPLAY', false );\n";
}

echo "\n✅ Deployment completed successfully!\n";
echo "\n📋 Next steps:\n";
echo "1. Test the fix: php test-fix.php\n";
echo "2. Load a WordPress page to trigger the fix\n";
echo "3. Check for admin notices about the fix being applied\n";
echo "4. Monitor results: php monitor-fix.php\n";
echo "\n🎉 Fix is now active and ready to resolve IntegrationRegistry conflicts!\n";
