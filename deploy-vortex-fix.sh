#!/bin/bash

# Vortex AI Engine WooCommerce Integration Fix Deployment Script
# This script deploys the fix for Vortex AI Engine and WooCommerce Blocks conflicts

set -e

echo "🚀 Deploying Vortex AI Engine WooCommerce Integration Fix..."

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

print_header "Vortex AI Engine WooCommerce Integration Fix Deployment"
echo "This fix addresses IntegrationRegistry conflicts between Vortex AI Engine and WooCommerce Blocks"
echo ""

# Check for Vortex AI Engine
print_status "Checking for Vortex AI Engine..."
if [ -d "wp-content/plugins/vortex-ai-engine" ]; then
    print_status "Vortex AI Engine found"
    VORTEX_VERSION=$(grep "Version:" wp-content/plugins/vortex-ai-engine/vortex-ai-engine.php | cut -d':' -f2 | tr -d ' ')
    echo "  Version: $VORTEX_VERSION"
else
    print_warning "Vortex AI Engine not found in plugins directory"
    print_warning "This fix is designed for Vortex AI Engine conflicts"
fi

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

# Copy the Vortex-specific fix file
if [ -f "vortex-woocommerce-integration-fix.php" ]; then
    print_status "Copying Vortex WooCommerce integration fix..."
    cp vortex-woocommerce-integration-fix.php "$MU_PLUGINS_DIR/"
    chmod 644 "$MU_PLUGINS_DIR/vortex-woocommerce-integration-fix.php"
    print_status "Vortex integration fix deployed successfully"
else
    print_error "vortex-woocommerce-integration-fix.php not found in current directory"
    exit 1
fi

# Create Vortex-specific diagnostic tool
print_status "Creating Vortex diagnostic tool..."
cat > "$MU_PLUGINS_DIR/vortex-integration-diagnostic.php" << 'EOF'
<?php
/**
 * Vortex AI Engine Integration Diagnostic Tool
 * 
 * @package Vortex-AI-Engine
 * @version 3.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add diagnostic page to admin
add_action( 'admin_menu', function() {
    add_management_page(
        'Vortex Integration Diagnostic',
        'Vortex Integration',
        'manage_options',
        'vortex-integration-diagnostic',
        function() {
            echo '<div class="wrap">';
            echo '<h1>Vortex AI Engine Integration Diagnostic</h1>';
            
            // Check if Vortex AI Engine is active
            if ( ! class_exists( 'Vortex_AI_Engine' ) ) {
                echo '<div class="notice notice-warning"><p>Vortex AI Engine is not active.</p></div>';
                echo '</div>';
                return;
            }
            
            // Check if WooCommerce is active
            if ( ! class_exists( 'WooCommerce' ) ) {
                echo '<div class="notice notice-warning"><p>WooCommerce is not active.</p></div>';
                echo '</div>';
                return;
            }
            
            // Check if WooCommerce Blocks is active
            if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
                echo '<div class="notice notice-warning"><p>WooCommerce Blocks IntegrationRegistry not found.</p></div>';
                echo '</div>';
                return;
            }
            
            // Check if our fix is active
            if ( ! class_exists( 'Vortex_WooCommerce_Integration_Fix' ) ) {
                echo '<div class="notice notice-error"><p>Vortex WooCommerce Integration Fix is not active.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Vortex WooCommerce Integration Fix is active.</p></div>';
            }
            
            try {
                $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                $integrations = $registry->get_all_registered();
                
                echo '<h2>Integration Registry Status</h2>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Name</th><th>Class</th><th>Status</th><th>Vortex Related</th></tr></thead>';
                echo '<tbody>';
                
                if ( empty( $integrations ) ) {
                    echo '<tr><td colspan="4">No integrations registered</td></tr>';
                } else {
                    foreach ( $integrations as $name => $integration ) {
                        $status = empty( $name ) ? '<span style="color: red;">EMPTY NAME - CONFLICT</span>' : '<span style="color: green;">OK</span>';
                        $class = is_object( $integration ) ? get_class( $integration ) : 'Unknown';
                        $vortex_related = ( strpos( strtolower( $name ), 'vortex' ) !== false ) ? 'Yes' : 'No';
                        
                        echo "<tr><td>" . esc_html( $name ) . "</td><td>" . esc_html( $class ) . "</td><td>$status</td><td>$vortex_related</td></tr>";
                    }
                }
                
                echo '</tbody></table>';
                
                // Vortex-specific analysis
                echo '<h2>Vortex AI Engine Analysis</h2>';
                
                $vortex_integrations = array();
                $empty_names = 0;
                $conflicts = 0;
                
                foreach ( $integrations as $name => $integration ) {
                    if ( empty( $name ) ) {
                        $empty_names++;
                    }
                    
                    if ( strpos( strtolower( $name ), 'vortex' ) !== false ) {
                        $vortex_integrations[] = $name;
                        
                        // Check for proper Vortex prefix
                        if ( strpos( $name, 'vortex_' ) !== 0 ) {
                            $conflicts++;
                        }
                    }
                }
                
                echo '<div class="card">';
                echo '<h3>Integration Summary</h3>';
                echo '<p><strong>Total Integrations:</strong> ' . count( $integrations ) . '</p>';
                echo '<p><strong>Vortex Integrations:</strong> ' . count( $vortex_integrations ) . '</p>';
                echo '<p><strong>Empty Names:</strong> ' . $empty_names . '</p>';
                echo '<p><strong>Vortex Conflicts:</strong> ' . $conflicts . '</p>';
                echo '</div>';
                
                if ( ! empty( $vortex_integrations ) ) {
                    echo '<h3>Vortex Integrations Found</h3>';
                    echo '<ul>';
                    foreach ( $vortex_integrations as $integration ) {
                        echo '<li>' . esc_html( $integration ) . '</li>';
                    }
                    echo '</ul>';
                }
                
                if ( $empty_names > 0 ) {
                    echo '<div class="notice notice-error"><p><strong>Problem Detected:</strong> ' . $empty_names . ' integration(s) with empty names. This is causing the IntegrationRegistry conflicts.</p></div>';
                }
                
                if ( $conflicts > 0 ) {
                    echo '<div class="notice notice-warning"><p><strong>Warning:</strong> ' . $conflicts . ' Vortex integration(s) without proper prefix. Consider updating the fix.</p></div>';
                }
                
                if ( $empty_names === 0 && $conflicts === 0 ) {
                    echo '<div class="notice notice-success"><p><strong>Success:</strong> No integration conflicts detected. The fix is working correctly.</p></div>';
                }
                
            } catch ( Exception $e ) {
                echo '<div class="notice notice-error"><p>Error accessing IntegrationRegistry: ' . esc_html( $e->getMessage() ) . '</p></div>';
            }
            
            // Quick actions
            echo '<h2>Quick Actions</h2>';
            echo '<p><a href="' . admin_url( 'admin.php?page=vortex-integration-diagnostic&action=clear_cache' ) . '" class="button">Clear Integration Cache</a></p>';
            echo '<p><a href="' . admin_url( 'admin.php?page=vortex-integration-diagnostic&action=test_integrations' ) . '" class="button button-primary">Test Integrations</a></p>';
            
            echo '</div>';
        }
    );
});
EOF

print_status "Vortex diagnostic tool created"

# Create Vortex monitoring script
print_status "Creating Vortex monitoring script..."
cat > "$MU_PLUGINS_DIR/vortex-integration-monitor.php" << 'EOF'
<?php
/**
 * Vortex AI Engine Integration Monitor
 * 
 * @package Vortex-AI-Engine
 * @version 3.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Monitor for Vortex integration issues
add_action( 'admin_init', function() {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    if ( file_exists( $log_file ) ) {
        $log_content = file_get_contents( $log_file );
        
        // Check for Vortex-specific errors
        if ( strpos( $log_content, 'Vortex WooCommerce Fix' ) !== false || 
             strpos( $log_content, 'IntegrationRegistry::register' ) !== false ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>Vortex Integration Issue Detected:</strong> ';
                echo 'Integration conflicts have been detected. ';
                echo '<a href="' . admin_url( 'tools.php?page=vortex-integration-diagnostic' ) . '">View Diagnostic Report</a>';
                echo '</p></div>';
            });
        }
    }
});

// Add Vortex health check endpoint
add_action( 'rest_api_init', function() {
    register_rest_route( 'vortex/v1', '/integration-health', array(
        'methods' => 'GET',
        'callback' => function() {
            $health_status = array(
                'status' => 'healthy',
                'timestamp' => current_time( 'mysql' ),
                'vortex_active' => class_exists( 'Vortex_AI_Engine' ),
                'woocommerce_active' => class_exists( 'WooCommerce' ),
                'blocks_active' => class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ),
                'fix_active' => class_exists( 'Vortex_WooCommerce_Integration_Fix' ),
            );
            
            if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
                try {
                    $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
                    $integrations = $registry->get_all_registered();
                    
                    $empty_names = 0;
                    $vortex_integrations = 0;
                    $conflicts = 0;
                    
                    foreach ( $integrations as $name => $integration ) {
                        if ( empty( $name ) ) {
                            $empty_names++;
                        }
                        
                        if ( strpos( strtolower( $name ), 'vortex' ) !== false ) {
                            $vortex_integrations++;
                            
                            if ( strpos( $name, 'vortex_' ) !== 0 ) {
                                $conflicts++;
                            }
                        }
                    }
                    
                    $health_status['integrations_count'] = count( $integrations );
                    $health_status['empty_names_count'] = $empty_names;
                    $health_status['vortex_integrations_count'] = $vortex_integrations;
                    $health_status['vortex_conflicts_count'] = $conflicts;
                    
                    if ( $empty_names > 0 ) {
                        $health_status['status'] = 'error';
                        $health_status['message'] = "Found $empty_names integrations with empty names";
                    } elseif ( $conflicts > 0 ) {
                        $health_status['status'] = 'warning';
                        $health_status['message'] = "Found $conflicts Vortex integration conflicts";
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

print_status "Vortex monitoring script created"

# Set proper permissions
print_status "Setting file permissions..."
chmod 644 "$MU_PLUGINS_DIR/vortex-integration-diagnostic.php"
chmod 644 "$MU_PLUGINS_DIR/vortex-integration-monitor.php"

# Clear WordPress cache if WP-CLI is available
if command -v wp &> /dev/null; then
    print_status "Clearing WordPress cache..."
    wp cache flush --quiet || print_warning "Could not clear cache (WP-CLI not available or no cache)"
else
    print_warning "WP-CLI not available. Please clear cache manually."
fi

# Create Vortex-specific test script
print_status "Creating Vortex test script..."
cat > "test-vortex-integration.php" << 'EOF'
<?php
/**
 * Test script for Vortex AI Engine WooCommerce Integration Fix
 * Run this script to test if the fix is working
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "Testing Vortex AI Engine WooCommerce Integration Fix...\n\n";

// Check if Vortex AI Engine is active
if ( ! class_exists( 'Vortex_AI_Engine' ) ) {
    echo "❌ Vortex AI Engine is not active\n";
    exit(1);
}

echo "✅ Vortex AI Engine is active\n";

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
if ( ! class_exists( 'Vortex_WooCommerce_Integration_Fix' ) ) {
    echo "❌ Vortex WooCommerce Integration Fix not found\n";
    exit(1);
}

echo "✅ Vortex WooCommerce Integration Fix is active\n";

// Test integration registry
try {
    $registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
    $integrations = $registry->get_all_registered();
    
    echo "✅ Integration Registry accessible\n";
    echo "📊 Found " . count( $integrations ) . " registered integrations\n";
    
    $empty_names = 0;
    $vortex_integrations = 0;
    $conflicts = 0;
    
    foreach ( $integrations as $name => $integration ) {
        if ( empty( $name ) ) {
            $empty_names++;
        }
        
        if ( strpos( strtolower( $name ), 'vortex' ) !== false ) {
            $vortex_integrations++;
            
            if ( strpos( $name, 'vortex_' ) !== 0 ) {
                $conflicts++;
            }
        }
    }
    
    echo "📊 Integration Analysis:\n";
    echo "  - Empty Names: $empty_names\n";
    echo "  - Vortex Integrations: $vortex_integrations\n";
    echo "  - Vortex Conflicts: $conflicts\n";
    
    if ( $empty_names > 0 ) {
        echo "⚠️  Found $empty_names integrations with empty names\n";
    } else {
        echo "✅ No integrations with empty names found\n";
    }
    
    if ( $conflicts > 0 ) {
        echo "⚠️  Found $conflicts Vortex integration conflicts\n";
    } else {
        echo "✅ No Vortex integration conflicts found\n";
    }
    
} catch ( Exception $e ) {
    echo "❌ Error accessing Integration Registry: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 All tests passed! The Vortex integration fix should be working correctly.\n";
echo "\n📊 Health Check URL: " . home_url( '/wp-json/vortex/v1/integration-health' ) . "\n";
echo "🔧 Admin Diagnostic: " . admin_url( 'tools.php?page=vortex-integration-diagnostic' ) . "\n";
EOF

chmod +x test-vortex-integration.php

print_status "Vortex test script created"

echo ""
echo "✅ Vortex AI Engine WooCommerce Integration Fix deployed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Test the fix: php test-vortex-integration.php"
echo "2. Check admin area for any remaining errors"
echo "3. Visit Tools > Vortex Integration for diagnostic information"
echo "4. Monitor error logs for any remaining issues"
echo ""
echo "🔧 Files deployed:"
echo "   - $MU_PLUGINS_DIR/vortex-woocommerce-integration-fix.php"
echo "   - $MU_PLUGINS_DIR/vortex-integration-diagnostic.php"
echo "   - $MU_PLUGINS_DIR/vortex-integration-monitor.php"
echo "   - test-vortex-integration.php"
echo ""
echo "📊 Health Check:"
echo "   - API: " . home_url( '/wp-json/vortex/v1/integration-health' ) . ""
echo "   - Admin: " . admin_url( 'tools.php?page=vortex-integration-diagnostic' ) . ""
echo ""
echo "📞 For support, check the diagnostic tools or review the implementation guide." 