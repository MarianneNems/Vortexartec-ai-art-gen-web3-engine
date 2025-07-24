<?php
/**
 * Standalone WooCommerce Blocks IntegrationRegistry Debug
 * 
 * This script can analyze WooCommerce Blocks issues without loading WordPress
 * 
 * Usage: php standalone-debug.php [wordpress-path]
 */

// Get WordPress path from command line or use current directory
$wp_path = isset( $argv[1] ) ? $argv[1] : dirname( __FILE__ );

echo "🔍 WooCommerce Blocks IntegrationRegistry Standalone Debug\n";
echo "========================================================\n\n";

// Check if we're in a WordPress directory
$wp_config = $wp_path . '/wp-config.php';
if ( ! file_exists( $wp_config ) ) {
    echo "❌ WordPress not found at: $wp_path\n";
    echo "Usage: php standalone-debug.php [wordpress-path]\n";
    echo "Example: php standalone-debug.php /path/to/wordpress\n\n";
    
    // Try to find WordPress in common locations
    $common_paths = array(
        dirname( __FILE__ ) . '/..',
        dirname( __FILE__ ) . '/../..',
        dirname( __FILE__ ) . '/../../..',
        '/var/www/html',
        '/home/*/public_html',
        'C:/xampp/htdocs',
        'C:/wamp/www'
    );
    
    echo "🔍 Searching for WordPress in common locations...\n";
    foreach ( $common_paths as $path ) {
        if ( file_exists( $path . '/wp-config.php' ) ) {
            echo "✅ Found WordPress at: $path\n";
            echo "Run: php standalone-debug.php $path\n\n";
            break;
        }
    }
    
    exit( 1 );
}

echo "✅ WordPress found at: $wp_path\n\n";

// Load WordPress configuration
echo "📋 Loading WordPress configuration...\n";

// Extract database configuration from wp-config.php
$wp_config_content = file_get_contents( $wp_config );

// Extract basic WordPress info
preg_match( "/define\(\s*'DB_NAME',\s*'([^']+)'\s*\)/", $wp_config_content, $db_name_match );
preg_match( "/define\(\s*'DB_HOST',\s*'([^']+)'\s*\)/", $wp_config_content, $db_host_match );
preg_match( "/define\(\s*'DB_USER',\s*'([^']+)'\s*\)/", $wp_config_content, $db_user_match );

$db_name = $db_name_match[1] ?? 'unknown';
$db_host = $db_host_match[1] ?? 'unknown';
$db_user = $db_user_match[1] ?? 'unknown';

echo "- Database: $db_name on $db_host\n";
echo "- User: $db_user\n";

// Check for debug settings
$debug_enabled = strpos( $wp_config_content, "define( 'WP_DEBUG', true )" ) !== false;
echo "- Debug Mode: " . ( $debug_enabled ? '✅ Enabled' : '❌ Disabled' ) . "\n";

// Check for debug log
$debug_log_enabled = strpos( $wp_config_content, "define( 'WP_DEBUG_LOG', true )" ) !== false;
echo "- Debug Log: " . ( $debug_log_enabled ? '✅ Enabled' : '❌ Disabled' ) . "\n\n";

// Check WordPress version
echo "📊 WordPress Version Check:\n";
$wp_version_file = $wp_path . '/wp-includes/version.php';
if ( file_exists( $wp_version_file ) ) {
    $version_content = file_get_contents( $wp_version_file );
    preg_match( "/\\\$wp_version\s*=\s*'([^']+)'/", $version_content, $version_match );
    $wp_version = $version_match[1] ?? 'unknown';
    echo "- WordPress Version: $wp_version\n";
    
    if ( version_compare( $wp_version, '5.0', '<' ) ) {
        echo "- ⚠️  Warning: WordPress version below 5.0 may cause compatibility issues\n";
    }
} else {
    echo "- WordPress Version: Could not determine\n";
}

// Check PHP version
echo "- PHP Version: " . PHP_VERSION . "\n";
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    echo "- ⚠️  Warning: PHP version below 7.4 may cause compatibility issues\n";
}

echo "\n🔍 File System Analysis:\n";

// Check WooCommerce
$woocommerce_dir = $wp_path . '/wp-content/plugins/woocommerce';
if ( is_dir( $woocommerce_dir ) ) {
    $wc_version_file = $woocommerce_dir . '/woocommerce.php';
    if ( file_exists( $wc_version_file ) ) {
        $wc_content = file_get_contents( $wc_version_file );
        preg_match( "/Version:\s*([^\n]+)/", $wc_content, $wc_version_match );
        $wc_version = trim( $wc_version_match[1] ?? 'unknown' );
        echo "- WooCommerce: ✅ Found (v$wc_version)\n";
    } else {
        echo "- WooCommerce: ❌ Version file not found\n";
    }
} else {
    echo "- WooCommerce: ❌ Not found\n";
}

// Check WooCommerce Blocks
$wc_blocks_dir = $wp_path . '/wp-content/plugins/woocommerce/packages/woocommerce-blocks';
if ( is_dir( $wc_blocks_dir ) ) {
    echo "- WooCommerce Blocks: ✅ Found\n";
    
    // Check for IntegrationRegistry
    $integration_registry_file = $wc_blocks_dir . '/src/Integrations/IntegrationRegistry.php';
    if ( file_exists( $integration_registry_file ) ) {
        echo "- IntegrationRegistry: ✅ Found\n";
    } else {
        echo "- IntegrationRegistry: ❌ Not found\n";
    }
} else {
    echo "- WooCommerce Blocks: ❌ Not found\n";
}

// Check for active plugins
echo "\n📦 Plugin Analysis:\n";
$plugins_dir = $wp_path . '/wp-content/plugins';
$active_plugins_file = $wp_path . '/wp-content/plugins/.htaccess';

if ( is_dir( $plugins_dir ) ) {
    $plugins = scandir( $plugins_dir );
    $woocommerce_plugins = array();
    $potential_conflicts = array();
    
    foreach ( $plugins as $plugin ) {
        if ( $plugin !== '.' && $plugin !== '..' && is_dir( $plugins_dir . '/' . $plugin ) ) {
            $plugin_file = $plugins_dir . '/' . $plugin . '/' . $plugin . '.php';
            if ( file_exists( $plugin_file ) ) {
                $plugin_content = file_get_contents( $plugin_file );
                
                // Check if it's a WooCommerce plugin
                if ( strpos( $plugin_content, 'WooCommerce' ) !== false || strpos( $plugin, 'woo' ) !== false ) {
                    preg_match( "/Plugin Name:\s*([^\n]+)/", $plugin_content, $name_match );
                    preg_match( "/Version:\s*([^\n]+)/", $plugin_content, $version_match );
                    
                    $plugin_name = trim( $name_match[1] ?? $plugin );
                    $plugin_version = trim( $version_match[1] ?? 'unknown' );
                    
                    $woocommerce_plugins[] = "$plugin_name v$plugin_version";
                }
                
                // Check for potential conflicts
                $conflict_keywords = array( 'blocks', 'integration', 'registry' );
                foreach ( $conflict_keywords as $keyword ) {
                    if ( strpos( strtolower( $plugin_content ), $keyword ) !== false ) {
                        preg_match( "/Plugin Name:\s*([^\n]+)/", $plugin_content, $name_match );
                        $plugin_name = trim( $name_match[1] ?? $plugin );
                        $potential_conflicts[] = $plugin_name;
                        break;
                    }
                }
            }
        }
    }
    
    echo "- WooCommerce Plugins Found: " . count( $woocommerce_plugins ) . "\n";
    foreach ( $woocommerce_plugins as $plugin ) {
        echo "  - $plugin\n";
    }
    
    echo "- Potential Conflicts: " . count( $potential_conflicts ) . "\n";
    foreach ( $potential_conflicts as $plugin ) {
        echo "  - $plugin\n";
    }
}

// Check error logs
echo "\n📝 Error Log Analysis:\n";
$debug_log = $wp_path . '/wp-content/debug.log';
if ( file_exists( $debug_log ) ) {
    $log_content = file_get_contents( $debug_log );
    $lines = explode( "\n", $log_content );
    
    $integration_errors = 0;
    $recent_errors = array();
    
    foreach ( $lines as $line ) {
        if ( strpos( $line, 'IntegrationRegistry::register' ) !== false ) {
            $integration_errors++;
            if ( count( $recent_errors ) < 5 ) {
                $recent_errors[] = trim( $line );
            }
        }
    }
    
    echo "- IntegrationRegistry Errors: $integration_errors\n";
    
    if ( $integration_errors > 0 ) {
        echo "- Recent Errors:\n";
        foreach ( $recent_errors as $error ) {
            echo "  " . $error . "\n";
        }
    }
} else {
    echo "- Debug Log: Not found\n";
    if ( ! $debug_log_enabled ) {
        echo "  Enable debug logging by adding to wp-config.php:\n";
        echo "  define( 'WP_DEBUG', true );\n";
        echo "  define( 'WP_DEBUG_LOG', true );\n";
    }
}

// Check for our fix files
echo "\n🔧 Fix Files Check:\n";
$fix_files = array(
    'woocommerce-blocks-integration-fix.php',
    'debug-integration-registry.php',
    'quick-debug.php',
    'deploy-woocommerce-fix.sh'
);

foreach ( $fix_files as $file ) {
    if ( file_exists( $file ) ) {
        echo "- $file: ✅ Found\n";
    } else {
        echo "- $file: ❌ Not found\n";
    }
}

// Recommendations
echo "\n💡 Recommendations:\n";

if ( ! $debug_enabled ) {
    echo "1. 🔧 Enable debug mode in wp-config.php\n";
}

if ( ! $debug_log_enabled ) {
    echo "2. 📝 Enable debug logging in wp-config.php\n";
}

if ( ! file_exists( 'woocommerce-blocks-integration-fix.php' ) ) {
    echo "3. 🚨 Deploy the WooCommerce Blocks integration fix\n";
}

if ( ! empty( $potential_conflicts ) ) {
    echo "4. ⚠️  Review potential conflict plugins\n";
}

echo "5. 🔄 Clear all caches and test again\n";
echo "6. 📊 Monitor error logs after implementing fixes\n";

echo "\n✅ Standalone debug complete!\n";
echo "\nNext steps:\n";
echo "1. Load WordPress and run: php quick-debug.php\n";
echo "2. Deploy fix: ./deploy-woocommerce-fix.sh\n";
echo "3. Monitor: Check error logs and admin tools\n"; 