<?php
/**
 * Quick WooCommerce Blocks IntegrationRegistry Debug
 * 
 * Run this script to quickly diagnose IntegrationRegistry issues
 * 
 * Usage: php quick-debug.php
 */

// Load WordPress
$wp_load_path = dirname( __FILE__ ) . '/wp-load.php';
if ( file_exists( $wp_load_path ) ) {
    require_once $wp_load_path;
} else {
    die( "WordPress not found. Please run this script from WordPress root directory.\n" );
}

echo "🔍 WooCommerce Blocks IntegrationRegistry Quick Debug\n";
echo "==================================================\n\n";

// Check basic requirements
echo "📋 System Information:\n";
echo "- PHP Version: " . PHP_VERSION . "\n";
echo "- WordPress Version: " . get_bloginfo( 'version' ) . "\n";

if ( class_exists( 'WooCommerce' ) ) {
    echo "- WooCommerce Version: " . WC()->version . "\n";
} else {
    echo "- WooCommerce: ❌ NOT ACTIVE\n";
    exit( 1 );
}

if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
    echo "- WooCommerce Blocks: ✅ ACTIVE\n";
    if ( defined( 'WC_BLOCKS_VERSION' ) ) {
        echo "- WooCommerce Blocks Version: " . WC_BLOCKS_VERSION . "\n";
    }
} else {
    echo "- WooCommerce Blocks: ❌ NOT FOUND\n";
    exit( 1 );
}

echo "\n🔧 Integration Registry Analysis:\n";

try {
    $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    $integrations = $registry->get_all_registered();
    
    echo "- Total Integrations: " . count( $integrations ) . "\n";
    
    $empty_names = 0;
    $valid_integrations = array();
    
    foreach ( $integrations as $name => $integration ) {
        if ( empty( $name ) ) {
            $empty_names++;
            echo "- ❌ EMPTY NAME DETECTED\n";
        } else {
            $valid_integrations[] = $name;
        }
    }
    
    echo "- Empty Names: $empty_names\n";
    echo "- Valid Integrations: " . count( $valid_integrations ) . "\n";
    
    if ( $empty_names > 0 ) {
        echo "\n🚨 PROBLEM DETECTED: $empty_names integration(s) with empty names\n";
        echo "This is causing the IntegrationRegistry::register errors.\n";
    } else {
        echo "\n✅ No empty integration names found.\n";
    }
    
} catch ( Exception $e ) {
    echo "- ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n📊 Error Log Analysis:\n";

$log_file = WP_CONTENT_DIR . '/debug.log';
if ( file_exists( $log_file ) ) {
    $log_content = file_get_contents( $log_file );
    
    $integration_errors = 0;
    $lines = explode( "\n", $log_content );
    
    foreach ( $lines as $line ) {
        if ( strpos( $line, 'IntegrationRegistry::register' ) !== false ) {
            $integration_errors++;
        }
    }
    
    echo "- IntegrationRegistry Errors in Log: $integration_errors\n";
    
    if ( $integration_errors > 0 ) {
        echo "- Recent Errors:\n";
        $recent_lines = array_slice( $lines, -20 );
        $error_count = 0;
        
        foreach ( $recent_lines as $line ) {
            if ( strpos( $line, 'IntegrationRegistry::register' ) !== false ) {
                echo "  " . trim( $line ) . "\n";
                $error_count++;
                if ( $error_count >= 5 ) break;
            }
        }
    }
} else {
    echo "- Debug log not found. Enable WP_DEBUG_LOG to see errors.\n";
}

echo "\n🔍 Plugin Analysis:\n";

if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$active_plugins = get_option( 'active_plugins' );
$all_plugins = get_plugins();

$woocommerce_plugins = array();
$potential_conflicts = array();

foreach ( $active_plugins as $plugin ) {
    if ( isset( $all_plugins[ $plugin ] ) ) {
        $plugin_data = $all_plugins[ $plugin ];
        
        if ( strpos( strtolower( $plugin_data['Name'] ), 'woocommerce' ) !== false ) {
            $woocommerce_plugins[] = $plugin_data['Name'] . ' v' . $plugin_data['Version'];
        }
        
        // Check for potential conflict plugins
        $conflict_keywords = array( 'blocks', 'integration', 'registry', 'woo' );
        foreach ( $conflict_keywords as $keyword ) {
            if ( strpos( strtolower( $plugin_data['Name'] ), $keyword ) !== false ) {
                $potential_conflicts[] = $plugin_data['Name'] . ' v' . $plugin_data['Version'];
                break;
            }
        }
    }
}

echo "- WooCommerce Plugins: " . count( $woocommerce_plugins ) . "\n";
foreach ( $woocommerce_plugins as $plugin ) {
    echo "  - $plugin\n";
}

echo "- Potential Conflicts: " . count( $potential_conflicts ) . "\n";
foreach ( $potential_conflicts as $plugin ) {
    echo "  - $plugin\n";
}

echo "\n💡 Recommendations:\n";

if ( $empty_names > 0 ) {
    echo "1. 🚨 IMMEDIATE: Deploy the WooCommerce Blocks IntegrationRegistry fix\n";
    echo "   Run: ./deploy-woocommerce-fix.sh\n";
}

if ( ! empty( $potential_conflicts ) ) {
    echo "2. ⚠️  CHECK: Review potential conflict plugins\n";
    echo "   Consider temporarily disabling them to test\n";
}

if ( ! file_exists( $log_file ) ) {
    echo "3. 📝 ENABLE: Add to wp-config.php:\n";
    echo "   define( 'WP_DEBUG', true );\n";
    echo "   define( 'WP_DEBUG_LOG', true );\n";
}

echo "4. 🔄 TEST: Clear all caches and test again\n";
echo "5. 📊 MONITOR: Check error logs after implementing fix\n";

echo "\n📁 Debug Files Created:\n";
echo "- " . WP_CONTENT_DIR . "/integration-registry-debug.log\n";
echo "- " . WP_CONTENT_DIR . "/integration-registry-debug-report.html\n";

echo "\n🔗 Admin Tools:\n";
echo "- Debug Report: " . admin_url( 'tools.php?page=integration-registry-debug' ) . "\n";
echo "- Integration Registry: " . admin_url( 'tools.php?page=integration-registry-diagnostic' ) . "\n";

echo "\n✅ Debug complete!\n"; 