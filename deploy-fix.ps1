# WooCommerce Blocks IntegrationRegistry Fix Deployment Script
# This script deploys the fix for IntegrationRegistry conflicts

Write-Host "🚀 Deploying WooCommerce Blocks IntegrationRegistry Fix..." -ForegroundColor Green

# Check if we're in a WordPress directory
if (-not (Test-Path "wp-config.php")) {
    Write-Host "❌ This script must be run from the WordPress root directory" -ForegroundColor Red
    exit 1
}

Write-Host "📋 WooCommerce Blocks IntegrationRegistry Fix Deployment" -ForegroundColor Blue
Write-Host "This fix addresses 'IntegrationRegistry::register was called incorrectly' notices"
Write-Host ""

# Check for WooCommerce
Write-Host "📦 Checking for WooCommerce..." -ForegroundColor Yellow
if (Test-Path "wp-content/plugins/woocommerce") {
    Write-Host "✅ WooCommerce found" -ForegroundColor Green
    $wcVersion = (Select-String "Version:" "wp-content/plugins/woocommerce/woocommerce.php" | ForEach-Object { $_.Line.Split(':')[1].Trim() })
    Write-Host "  Version: $wcVersion" -ForegroundColor Gray
} else {
    Write-Host "❌ WooCommerce not found. This fix requires WooCommerce to be active." -ForegroundColor Red
    exit 1
}

# Check for WooCommerce Blocks
Write-Host "📦 Checking for WooCommerce Blocks..." -ForegroundColor Yellow
if (Test-Path "wp-content/plugins/woocommerce/packages/woocommerce-blocks") {
    Write-Host "✅ WooCommerce Blocks found" -ForegroundColor Green
} else {
    Write-Host "⚠️  WooCommerce Blocks not found in expected location" -ForegroundColor Yellow
}

# Create mu-plugins directory if it doesn't exist
$muPluginsDir = "wp-content/mu-plugins"
if (-not (Test-Path $muPluginsDir)) {
    Write-Host "📁 Creating mu-plugins directory..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $muPluginsDir -Force | Out-Null
}

# Copy the fix file
if (Test-Path "woocommerce-blocks-fix.php") {
    Write-Host "📄 Copying WooCommerce Blocks fix..." -ForegroundColor Yellow
    Copy-Item "woocommerce-blocks-fix.php" "$muPluginsDir/" -Force
    Write-Host "✅ Fix deployed successfully" -ForegroundColor Green
} else {
    Write-Host "❌ woocommerce-blocks-fix.php not found in current directory" -ForegroundColor Red
    exit 1
}

# Create a test script to verify the fix
Write-Host "📄 Creating test script..." -ForegroundColor Yellow
$testScript = @"
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
    `$registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    `$integrations = `$registry->get_all_registered();
    
    echo "✅ Integration Registry accessible\n";
    echo "📊 Found " . count( `$integrations ) . " registered integrations\n";
    
    `$empty_names = 0;
    `$valid_integrations = 0;
    
    foreach ( `$integrations as `$name => `$integration ) {
        if ( empty( `$name ) ) {
            `$empty_names++;
        } else {
            `$valid_integrations++;
        }
    }
    
    echo "📊 Integration Analysis:\n";
    echo "  - Valid Integrations: `$valid_integrations\n";
    echo "  - Empty Names: `$empty_names\n";
    
    if ( `$empty_names > 0 ) {
        echo "⚠️  Found `$empty_names integrations with empty names\n";
        echo "   The fix should clean these up on the next page load\n";
    } else {
        echo "✅ No integrations with empty names found\n";
    }
    
} catch ( Exception `$e ) {
    echo "❌ Error accessing Integration Registry: " . `$e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Fix deployment test completed successfully!\n";
echo "\n📋 Next steps:\n";
echo "1. Load a WordPress page to trigger the fix\n";
echo "2. Check for admin notices about the fix being applied\n";
echo "3. Monitor error logs for any remaining issues\n";
echo "4. Test WooCommerce functionality\n";
"@

$testScript | Out-File -FilePath "test-fix.php" -Encoding UTF8
Write-Host "✅ Test script created" -ForegroundColor Green

# Create a monitoring script
Write-Host "📄 Creating monitoring script..." -ForegroundColor Yellow
$monitorScript = @"
<?php
/**
 * Monitor WooCommerce Blocks IntegrationRegistry Fix
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "Monitoring WooCommerce Blocks IntegrationRegistry Fix...\n\n";

// Check error logs for fix activity
`$log_file = WP_CONTENT_DIR . '/debug.log';
if ( file_exists( `$log_file ) ) {
    `$log_content = file_get_contents( `$log_file );
    
    // Look for fix-related entries
    `$fix_entries = array();
    `$lines = explode( "\n", `$log_content );
    
    foreach ( `$lines as `$line ) {
        if ( strpos( `$line, 'WooCommerce Blocks IntegrationRegistry Fix' ) !== false ) {
            `$fix_entries[] = trim( `$line );
        }
    }
    
    if ( ! empty( `$fix_entries ) ) {
        echo "📝 Fix Activity Found:\n";
        foreach ( array_slice( `$fix_entries, -5 ) as `$entry ) {
            echo "  " . `$entry . "\n";
        }
    } else {
        echo "📝 No fix activity found in logs yet\n";
        echo "   The fix will run on the next page load\n";
    }
    
    // Check for remaining IntegrationRegistry errors
    `$remaining_errors = 0;
    foreach ( `$lines as `$line ) {
        if ( strpos( `$line, 'IntegrationRegistry::register' ) !== false && 
             strpos( `$line, 'WooCommerce Blocks IntegrationRegistry Fix' ) === false ) {
            `$remaining_errors++;
        }
    }
    
    if ( `$remaining_errors > 0 ) {
        echo "\n⚠️  Found `$remaining_errors remaining IntegrationRegistry errors\n";
    } else {
        echo "\n✅ No remaining IntegrationRegistry errors found\n";
    }
    
} else {
    echo "📝 Debug log not found. Enable WP_DEBUG_LOG to monitor fix activity.\n";
}

// Test current integration registry state
try {
    `$registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    `$integrations = `$registry->get_all_registered();
    
    `$empty_names = 0;
    foreach ( `$integrations as `$name => `$integration ) {
        if ( empty( `$name ) ) {
            `$empty_names++;
        }
    }
    
    echo "\n📊 Current Integration Registry State:\n";
    echo "  - Total Integrations: " . count( `$integrations ) . "\n";
    echo "  - Empty Names: `$empty_names\n";
    
    if ( `$empty_names === 0 ) {
        echo "✅ Integration Registry is clean\n";
    } else {
        echo "⚠️  Integration Registry still has empty names\n";
        echo "   The fix should clean these up on the next page load\n";
    }
    
} catch ( Exception `$e ) {
    echo "\n❌ Error accessing Integration Registry: " . `$e->getMessage() . "\n";
}

echo "\n✅ Monitoring complete!\n";
"@

$monitorScript | Out-File -FilePath "monitor-fix.php" -Encoding UTF8
Write-Host "✅ Monitoring script created" -ForegroundColor Green

Write-Host ""
Write-Host "✅ WooCommerce Blocks IntegrationRegistry Fix deployed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next steps:" -ForegroundColor Yellow
Write-Host "1. Test the fix: php test-fix.php"
Write-Host "2. Monitor results: php monitor-fix.php"
Write-Host "3. Load a WordPress page to trigger the fix"
Write-Host "4. Check for admin notices about the fix being applied"
Write-Host ""
Write-Host "🔧 Files deployed:" -ForegroundColor Yellow
Write-Host "   - $muPluginsDir/woocommerce-blocks-fix.php"
Write-Host "   - test-fix.php"
Write-Host "   - monitor-fix.php"
Write-Host ""
Write-Host "📊 The fix will:" -ForegroundColor Yellow
Write-Host "   - Run once per request at plugins_loaded priority 5"
Write-Host "   - Clean empty and duplicate integration entries"
Write-Host "   - Show a success notice when applied"
Write-Host "   - Log detailed information to error_log"
Write-Host ""
Write-Host "📞 For support, run the monitoring script or check error logs." -ForegroundColor Yellow 