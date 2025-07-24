#!/bin/bash

# WooCommerce Blocks IntegrationRegistry Fix Deployment Script
# This script deploys the fix for WooCommerce Blocks integration conflicts

set -e

echo "🚀 Deploying WooCommerce Blocks IntegrationRegistry Fix..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Check if we're in a WordPress directory
if [ ! -f "wp-config.php" ]; then
    print_error "This script must be run from the WordPress root directory"
    exit 1
fi

# Create mu-plugins directory if it doesn't exist
MU_PLUGINS_DIR="wp-content/mu-plugins"
if [ ! -d "$MU_PLUGINS_DIR" ]; then
    print_status "Creating mu-plugins directory..."
    mkdir -p "$MU_PLUGINS_DIR"
fi

# Copy the fix file
if [ -f "woocommerce-blocks-integration-fix.php" ]; then
    print_status "Copying fix file to mu-plugins..."
    cp woocommerce-blocks-integration-fix.php "$MU_PLUGINS_DIR/"
    chmod 644 "$MU_PLUGINS_DIR/woocommerce-blocks-integration-fix.php"
    print_status "Fix file deployed successfully"
else
    print_error "woocommerce-blocks-integration-fix.php not found in current directory"
    exit 1
fi

# Create diagnostic tool
print_status "Creating diagnostic tool..."
cat > "$MU_PLUGINS_DIR/integration-registry-diagnostic.php" << 'EOF'
<?php
/**
 * WooCommerce Blocks Integration Registry Diagnostic Tool
 * 
 * @package Vortex-AI-Engine
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add diagnostic page to admin
add_action( 'admin_menu', function() {
    add_management_page(
        'Integration Registry Diagnostic',
        'Integration Registry',
        'manage_options',
        'integration-registry-diagnostic',
        function() {
            echo '<div class="wrap">';
            echo '<h1>WooCommerce Blocks Integration Registry Diagnostic</h1>';
            
            // Check if WooCommerce Blocks is active
            if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
                echo '<div class="notice notice-warning"><p>WooCommerce Blocks IntegrationRegistry not found. WooCommerce Blocks may not be active.</p></div>';
                echo '</div>';
                return;
            }
            
            try {
                $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                $integrations = $registry->get_all_registered();
                
                echo '<h2>Registered Integrations</h2>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Name</th><th>Class</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                
                if ( empty( $integrations ) ) {
                    echo '<tr><td colspan="3">No integrations registered</td></tr>';
                } else {
                    foreach ( $integrations as $name => $integration ) {
                        $status = empty( $name ) ? '<span style="color: red;">EMPTY NAME - CONFLICT</span>' : '<span style="color: green;">OK</span>';
                        $class = is_object( $integration ) ? get_class( $integration ) : 'Unknown';
                        
                        echo "<tr><td>" . esc_html( $name ) . "</td><td>" . esc_html( $class ) . "</td><td>$status</td></tr>";
                    }
                }
                
                echo '</tbody></table>';
                
                // Check for recent errors
                echo '<h2>Recent Error Log Entries</h2>';
                $log_file = WP_CONTENT_DIR . '/debug.log';
                
                if ( file_exists( $log_file ) ) {
                    $log_content = file_get_contents( $log_file );
                    $lines = explode( "\n", $log_content );
                    $recent_lines = array_slice( $lines, -50 ); // Last 50 lines
                    
                    $integration_errors = array();
                    foreach ( $recent_lines as $line ) {
                        if ( strpos( $line, 'IntegrationRegistry::register' ) !== false ) {
                            $integration_errors[] = $line;
                        }
                    }
                    
                    if ( empty( $integration_errors ) ) {
                        echo '<p style="color: green;">No recent IntegrationRegistry errors found.</p>';
                    } else {
                        echo '<div style="background: #f1f1f1; padding: 10px; border-left: 4px solid #dc3232;">';
                        echo '<h3>Recent IntegrationRegistry Errors:</h3>';
                        echo '<pre style="font-size: 12px;">';
                        foreach ( array_slice( $integration_errors, -10 ) as $error ) {
                            echo esc_html( $error ) . "\n";
                        }
                        echo '</pre></div>';
                    }
                } else {
                    echo '<p>Debug log not found. Enable WP_DEBUG_LOG to see error details.</p>';
                }
                
            } catch ( Exception $e ) {
                echo '<div class="notice notice-error"><p>Error accessing IntegrationRegistry: ' . esc_html( $e->getMessage() ) . '</p></div>';
            }
            
            echo '</div>';
        }
    );
});
EOF

print_status "Diagnostic tool created"

# Create monitoring script
print_status "Creating monitoring script..."
cat > "$MU_PLUGINS_DIR/integration-monitor.php" << 'EOF'
<?php
/**
 * WooCommerce Blocks Integration Monitor
 * 
 * @package Vortex-AI-Engine
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Monitor for integration registry issues
add_action( 'admin_init', function() {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    if ( file_exists( $log_file ) ) {
        $log_content = file_get_contents( $log_file );
        
        if ( strpos( $log_content, 'IntegrationRegistry::register' ) !== false ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>WooCommerce Blocks Integration Issue Detected:</strong> ';
                echo 'IntegrationRegistry conflicts have been detected. ';
                echo '<a href="' . admin_url( 'tools.php?page=integration-registry-diagnostic' ) . '">View Diagnostic Report</a>';
                echo '</p></div>';
            });
        }
    }
});

// Add health check endpoint
add_action( 'rest_api_init', function() {
    register_rest_route( 'vortex/v1', '/integration-health', array(
        'methods' => 'GET',
        'callback' => function() {
            $health_status = array(
                'status' => 'healthy',
                'timestamp' => current_time( 'mysql' ),
                'woocommerce_blocks_active' => class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ),
                'fix_active' => class_exists( 'Vortex_WooCommerce_Blocks_Fix' ),
            );
            
            if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
                try {
                    $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                    $integrations = $registry->get_all_registered();
                    
                    $empty_names = 0;
                    foreach ( $integrations as $name => $integration ) {
                        if ( empty( $name ) ) {
                            $empty_names++;
                        }
                    }
                    
                    $health_status['integrations_count'] = count( $integrations );
                    $health_status['empty_names_count'] = $empty_names;
                    
                    if ( $empty_names > 0 ) {
                        $health_status['status'] = 'warning';
                        $health_status['message'] = "Found $empty_names integrations with empty names";
                    }
                    
                } catch ( Exception $e ) {
                    $health_status['status'] = 'error';
                    $health_status['error'] = $e->getMessage();
                }
            }
            
            return $health_status;
        },
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        }
    ));
});
EOF

print_status "Monitoring script created"

# Set proper permissions
print_status "Setting file permissions..."
chmod 644 "$MU_PLUGINS_DIR/integration-registry-diagnostic.php"
chmod 644 "$MU_PLUGINS_DIR/integration-monitor.php"

# Clear WordPress cache if WP-CLI is available
if command -v wp &> /dev/null; then
    print_status "Clearing WordPress cache..."
    wp cache flush --quiet || print_warning "Could not clear cache (WP-CLI not available or no cache)"
else
    print_warning "WP-CLI not available. Please clear cache manually."
fi

# Create a simple test script
print_status "Creating test script..."
cat > "test-integration-fix.php" << 'EOF'
<?php
/**
 * Test script for WooCommerce Blocks Integration Fix
 * Run this script to test if the fix is working
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "Testing WooCommerce Blocks Integration Fix...\n\n";

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
if ( ! class_exists( 'Vortex_WooCommerce_Blocks_Fix' ) ) {
    echo "❌ Vortex WooCommerce Blocks Fix not found\n";
    exit(1);
}

echo "✅ Vortex WooCommerce Blocks Fix is active\n";

// Test integration registry
try {
    $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    $integrations = $registry->get_all_registered();
    
    echo "✅ Integration Registry accessible\n";
    echo "📊 Found " . count( $integrations ) . " registered integrations\n";
    
    $empty_names = 0;
    foreach ( $integrations as $name => $integration ) {
        if ( empty( $name ) ) {
            $empty_names++;
        }
    }
    
    if ( $empty_names > 0 ) {
        echo "⚠️  Found $empty_names integrations with empty names\n";
    } else {
        echo "✅ No integrations with empty names found\n";
    }
    
} catch ( Exception $e ) {
    echo "❌ Error accessing Integration Registry: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 All tests passed! The fix should be working correctly.\n";
EOF

chmod +x test-integration-fix.php

print_status "Test script created"

echo ""
echo "✅ WooCommerce Blocks IntegrationRegistry Fix deployed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Test the fix: php test-integration-fix.php"
echo "2. Check admin area for any remaining errors"
echo "3. Visit Tools > Integration Registry for diagnostic information"
echo "4. Monitor error logs for any remaining issues"
echo ""
echo "🔧 Files deployed:"
echo "   - $MU_PLUGINS_DIR/woocommerce-blocks-integration-fix.php"
echo "   - $MU_PLUGINS_DIR/integration-registry-diagnostic.php"
echo "   - $MU_PLUGINS_DIR/integration-monitor.php"
echo "   - test-integration-fix.php"
echo ""
echo "📞 For support, check the diagnostic tools or review the implementation guide." 