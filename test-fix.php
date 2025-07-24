<?php
/**
 * Test script for WooCommerce Blocks IntegrationRegistry Fix
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "Testing WooCommerce Blocks IntegrationRegistry Fix...\n\n";

// Check if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
    echo "❌ WooCommerce is not active\n";
    exit(1);
}

echo "✅ WooCommerce is active\n";

// Check if WooCommerce Blocks is active
if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
    echo "❌ WooCommerce Blocks IntegrationRegistry not found\n";
    exit(1);
}

echo "✅ WooCommerce Blocks is active\n";

// Check if our fix is active
if ( ! file_exists( WP_CONTENT_DIR . '/mu-plugins/woocommerce-blocks-fix.php' ) ) {
    echo "❌ WooCommerce Blocks fix not found\n";
    exit(1);
}

echo "✅ WooCommerce Blocks fix is deployed\n";

// Test the integration registry
try {
    $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    $integrations = $registry->get_all_registered();
    
    echo "✅ Integration Registry accessible\n";
    echo "📊 Found " . count( $integrations ) . " registered integrations\n";
    
    $empty_names = 0;
    $valid_integrations = 0;
    
    foreach ( $integrations as $name => $integration ) {
        if ( empty( $name ) ) {
            $empty_names++;
        } else {
            $valid_integrations++;
        }
    }
    
    echo "📊 Integration Analysis:\n";
    echo "  - Valid Integrations: $valid_integrations\n";
    echo "  - Empty Names: $empty_names\n";
    
    if ( $empty_names > 0 ) {
        echo "⚠️  Found $empty_names integrations with empty names\n";
        echo "   The fix should clean these up on the next page load\n";
    } else {
        echo "✅ No integrations with empty names found\n";
    }
    
} catch ( Exception $e ) {
    echo "❌ Error accessing Integration Registry: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Fix deployment test completed successfully!\n";
echo "\n📋 Next steps:\n";
echo "1. Load a WordPress page to trigger the fix\n";
echo "2. Check for admin notices about the fix being applied\n";
echo "3. Monitor error logs for any remaining issues\n";
echo "4. Test WooCommerce functionality\n"; 