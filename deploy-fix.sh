#!/bin/bash

# WooCommerce Blocks IntegrationRegistry Fix Deployment Script
# This script deploys the fix for IntegrationRegistry conflicts

set -e

echo "🚀 Deploying WooCommerce Blocks IntegrationRegistry Fix..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}[HEADER]${NC} $1"
}

# Check if we're in a WordPress directory
if [ ! -f "wp-config.php" ]; then
    print_error "This script must be run from the WordPress root directory"
    exit 1
fi

print_header "WooCommerce Blocks IntegrationRegistry Fix Deployment"
echo "This fix addresses 'IntegrationRegistry::register was called incorrectly' notices"
echo ""

# Check for WooCommerce
print_status "Checking for WooCommerce..."
if [ -d "wp-content/plugins/woocommerce" ]; then
    print_status "WooCommerce found"
    WC_VERSION=$(grep "Version:" wp-content/plugins/woocommerce/woocommerce.php | cut -d':' -f2 | tr -d ' ')
    echo "  Version: $WC_VERSION"
else
    print_error "WooCommerce not found. This fix requires WooCommerce to be active."
    exit 1
fi

# Check for WooCommerce Blocks
print_status "Checking for WooCommerce Blocks..."
if [ -d "wp-content/plugins/woocommerce/packages/woocommerce-blocks" ]; then
    print_status "WooCommerce Blocks found"
else
    print_warning "WooCommerce Blocks not found in expected location"
fi

# Create mu-plugins directory if it doesn't exist
MU_PLUGINS_DIR="wp-content/mu-plugins"
if [ ! -d "$MU_PLUGINS_DIR" ]; then
    print_status "Creating mu-plugins directory..."
    mkdir -p "$MU_PLUGINS_DIR"
fi

# Copy the fix file
if [ -f "woocommerce-blocks-fix.php" ]; then
    print_status "Copying WooCommerce Blocks fix..."
    cp woocommerce-blocks-fix.php "$MU_PLUGINS_DIR/"
    chmod 644 "$MU_PLUGINS_DIR/woocommerce-blocks-fix.php"
    print_status "Fix deployed successfully"
else
    print_error "woocommerce-blocks-fix.php not found in current directory"
    exit 1
fi

# Create a test script to verify the fix
print_status "Creating test script..."
cat > "test-fix.php" << 'EOF'
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
EOF

chmod +x test-fix.php

print_status "Test script created"

# Clear WordPress cache if WP-CLI is available
if command -v wp &> /dev/null; then
    print_status "Clearing WordPress cache..."
    wp cache flush --quiet || print_warning "Could not clear cache (WP-CLI not available or no cache)"
else
    print_warning "WP-CLI not available. Please clear cache manually."
fi

# Create a monitoring script
print_status "Creating monitoring script..."
cat > "monitor-fix.php" << 'EOF'
<?php
/**
 * Monitor WooCommerce Blocks IntegrationRegistry Fix
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "Monitoring WooCommerce Blocks IntegrationRegistry Fix...\n\n";

// Check error logs for fix activity
$log_file = WP_CONTENT_DIR . '/debug.log';
if ( file_exists( $log_file ) ) {
    $log_content = file_get_contents( $log_file );
    
    // Look for fix-related entries
    $fix_entries = array();
    $lines = explode( "\n", $log_content );
    
    foreach ( $lines as $line ) {
        if ( strpos( $line, 'WooCommerce Blocks IntegrationRegistry Fix' ) !== false ) {
            $fix_entries[] = trim( $line );
        }
    }
    
    if ( ! empty( $fix_entries ) ) {
        echo "📝 Fix Activity Found:\n";
        foreach ( array_slice( $fix_entries, -5 ) as $entry ) {
            echo "  " . $entry . "\n";
        }
    } else {
        echo "📝 No fix activity found in logs yet\n";
        echo "   The fix will run on the next page load\n";
    }
    
    // Check for remaining IntegrationRegistry errors
    $remaining_errors = 0;
    foreach ( $lines as $line ) {
        if ( strpos( $line, 'IntegrationRegistry::register' ) !== false && 
             strpos( $line, 'WooCommerce Blocks IntegrationRegistry Fix' ) === false ) {
            $remaining_errors++;
        }
    }
    
    if ( $remaining_errors > 0 ) {
        echo "\n⚠️  Found $remaining_errors remaining IntegrationRegistry errors\n";
    } else {
        echo "\n✅ No remaining IntegrationRegistry errors found\n";
    }
    
} else {
    echo "📝 Debug log not found. Enable WP_DEBUG_LOG to monitor fix activity.\n";
}

// Test current integration registry state
try {
    $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    $integrations = $registry->get_all_registered();
    
    $empty_names = 0;
    foreach ( $integrations as $name => $integration ) {
        if ( empty( $name ) ) {
            $empty_names++;
        }
    }
    
    echo "\n📊 Current Integration Registry State:\n";
    echo "  - Total Integrations: " . count( $integrations ) . "\n";
    echo "  - Empty Names: $empty_names\n";
    
    if ( $empty_names === 0 ) {
        echo "✅ Integration Registry is clean\n";
    } else {
        echo "⚠️  Integration Registry still has empty names\n";
        echo "   The fix should clean these up on the next page load\n";
    }
    
} catch ( Exception $e ) {
    echo "\n❌ Error accessing Integration Registry: " . $e->getMessage() . "\n";
}

echo "\n✅ Monitoring complete!\n";
EOF

chmod +x monitor-fix.php

print_status "Monitoring script created"

echo ""
echo "✅ WooCommerce Blocks IntegrationRegistry Fix deployed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Test the fix: php test-fix.php"
echo "2. Monitor results: php monitor-fix.php"
echo "3. Load a WordPress page to trigger the fix"
echo "4. Check for admin notices about the fix being applied"
echo ""
echo "🔧 Files deployed:"
echo "   - $MU_PLUGINS_DIR/woocommerce-blocks-fix.php"
echo "   - test-fix.php"
echo "   - monitor-fix.php"
echo ""
echo "📊 The fix will:"
echo "   - Run once per request at plugins_loaded priority 5"
echo "   - Clean empty and duplicate integration entries"
echo "   - Show a success notice when applied"
echo "   - Log detailed information to error_log"
echo ""
echo "📞 For support, run the monitoring script or check error logs." 