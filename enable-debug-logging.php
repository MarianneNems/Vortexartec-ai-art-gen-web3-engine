<?php
/**
 * Enable Debug Logging and Check for IntegrationRegistry Errors
 * 
 * This script enables debug logging and checks for any existing errors
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "🔧 Enabling Debug Logging and Checking for IntegrationRegistry Errors...\n\n";

// Check if debug logging is already enabled
$wp_config_path = ABSPATH . 'wp-config.php';
$wp_config_content = file_get_contents( $wp_config_path );

$debug_enabled = strpos( $wp_config_content, "define( 'WP_DEBUG', true )" ) !== false;
$debug_log_enabled = strpos( $wp_config_content, "define( 'WP_DEBUG_LOG', true )" ) !== false;

echo "📊 Current Debug Status:\n";
echo "- WP_DEBUG: " . ( $debug_enabled ? '✅ Enabled' : '❌ Disabled' ) . "\n";
echo "- WP_DEBUG_LOG: " . ( $debug_log_enabled ? '✅ Enabled' : '❌ Disabled' ) . "\n";

// Check if our fix is active
$fix_file = WP_CONTENT_DIR . '/mu-plugins/woocommerce-blocks-fix.php';
if ( file_exists( $fix_file ) ) {
    echo "- WooCommerce Blocks Fix: ✅ Deployed\n";
} else {
    echo "- WooCommerce Blocks Fix: ❌ Not found\n";
}

// Check for WooCommerce Blocks
if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
    echo "- WooCommerce Blocks: ✅ Active\n";
    
    // Test the integration registry
    try {
        $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
        $integrations = $registry->get_all_registered();
        
        echo "\n📊 Integration Registry Analysis:\n";
        echo "- Total Integrations: " . count( $integrations ) . "\n";
        
        $empty_names = 0;
        $valid_integrations = 0;
        
        foreach ( $integrations as $name => $integration ) {
            if ( empty( $name ) ) {
                $empty_names++;
            } else {
                $valid_integrations++;
            }
        }
        
        echo "- Valid Integrations: $valid_integrations\n";
        echo "- Empty Names: $empty_names\n";
        
        if ( $empty_names > 0 ) {
            echo "\n⚠️  Found $empty_names integrations with empty names!\n";
            echo "   This is causing the IntegrationRegistry::register errors.\n";
        } else {
            echo "\n✅ No integrations with empty names found.\n";
        }
        
    } catch ( Exception $e ) {
        echo "\n❌ Error accessing Integration Registry: " . $e->getMessage() . "\n";
    }
} else {
    echo "- WooCommerce Blocks: ❌ Not active\n";
}

// Check for any existing error logs
$log_files = array(
    WP_CONTENT_DIR . '/debug.log',
    WP_CONTENT_DIR . '/error.log',
    ABSPATH . 'error_log',
    ABSPATH . 'php_errors.log'
);

echo "\n📝 Checking for existing log files:\n";
$found_logs = false;
foreach ( $log_files as $log_file ) {
    if ( file_exists( $log_file ) ) {
        echo "- Found: $log_file\n";
        $found_logs = true;
        
        // Check for IntegrationRegistry errors
        $log_content = file_get_contents( $log_file );
        $lines = explode( "\n", $log_content );
        
        $integration_errors = 0;
        foreach ( $lines as $line ) {
            if ( strpos( $line, 'IntegrationRegistry::register' ) !== false ) {
                $integration_errors++;
            }
        }
        
        if ( $integration_errors > 0 ) {
            echo "  - Found $integration_errors IntegrationRegistry errors\n";
            
            // Show recent errors
            echo "  - Recent errors:\n";
            $recent_lines = array_slice( $lines, -20 );
            $error_count = 0;
            foreach ( $recent_lines as $line ) {
                if ( strpos( $line, 'IntegrationRegistry::register' ) !== false ) {
                    echo "    " . trim( $line ) . "\n";
                    $error_count++;
                    if ( $error_count >= 5 ) break;
                }
            }
        } else {
            echo "  - No IntegrationRegistry errors found\n";
        }
    }
}

if ( ! $found_logs ) {
    echo "- No log files found\n";
}

// Enable debug logging if not already enabled
if ( ! $debug_enabled || ! $debug_log_enabled ) {
    echo "\n🔧 Enabling debug logging...\n";
    
    // Create backup of wp-config.php
    $backup_path = ABSPATH . 'wp-config.php.backup.' . date( 'Y-m-d-H-i-s' );
    copy( $wp_config_path, $backup_path );
    echo "- Backup created: $backup_path\n";
    
    // Add debug constants if not present
    $new_content = $wp_config_content;
    
    if ( ! $debug_enabled ) {
        // Find the line before "/* That's all, stop editing! */"
        $stop_editing = "/* That's all, stop editing! */";
        $debug_line = "define( 'WP_DEBUG', true );";
        
        if ( strpos( $new_content, $debug_line ) === false ) {
            $new_content = str_replace( $stop_editing, $debug_line . "\n\n" . $stop_editing, $new_content );
            echo "- Added WP_DEBUG = true\n";
        }
    }
    
    if ( ! $debug_log_enabled ) {
        $stop_editing = "/* That's all, stop editing! */";
        $debug_log_line = "define( 'WP_DEBUG_LOG', true );";
        
        if ( strpos( $new_content, $debug_log_line ) === false ) {
            $new_content = str_replace( $stop_editing, $debug_log_line . "\n\n" . $stop_editing, $new_content );
            echo "- Added WP_DEBUG_LOG = true\n";
        }
    }
    
    // Write the updated config
    if ( $new_content !== $wp_config_content ) {
        file_put_contents( $wp_config_path, $new_content );
        echo "✅ Debug logging enabled!\n";
        echo "📝 Error logs will now be written to: " . WP_CONTENT_DIR . "/debug.log\n";
    } else {
        echo "ℹ️  Debug logging already properly configured\n";
    }
} else {
    echo "\n✅ Debug logging is already enabled\n";
}

echo "\n📋 Next Steps:\n";
echo "1. Load a WordPress page to trigger the fix\n";
echo "2. Check for admin notices about the fix being applied\n";
echo "3. Run: php monitor-fix.php\n";
echo "4. Check the debug log for fix activity\n";

echo "\n🎉 Debug logging setup complete!\n"; 