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