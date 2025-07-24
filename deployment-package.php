<?php
/**
 * WooCommerce Blocks IntegrationRegistry Fix - Deployment Package
 * 
 * This script creates a complete deployment package with all necessary files
 * for easy WordPress installation.
 */

echo "📦 Creating WooCommerce Blocks IntegrationRegistry Fix Deployment Package...\n\n";

// Create deployment directory
$deployment_dir = __DIR__ . '/deployment-package';
if ( ! is_dir( $deployment_dir ) ) {
    mkdir( $deployment_dir, 0755, true );
    echo "✅ Created deployment directory: $deployment_dir\n";
}

// Create mu-plugins directory in deployment package
$mu_plugins_dir = $deployment_dir . '/mu-plugins';
if ( ! is_dir( $mu_plugins_dir ) ) {
    mkdir( $mu_plugins_dir, 0755, true );
    echo "✅ Created mu-plugins directory\n";
}

// Copy core fix file
$source_fix = __DIR__ . '/wp-content/mu-plugins/woocommerce-blocks-fix.php';
$dest_fix = $mu_plugins_dir . '/woocommerce-blocks-fix.php';

if ( file_exists( $source_fix ) ) {
    copy( $source_fix, $dest_fix );
    echo "✅ Copied core fix file\n";
} else {
    echo "❌ Source fix file not found\n";
    exit( 1 );
}

// Copy test scripts
$test_scripts = array(
    'test-fix.php',
    'monitor-fix.php'
);

foreach ( $test_scripts as $script ) {
    $source = __DIR__ . '/' . $script;
    $dest = $deployment_dir . '/' . $script;
    
    if ( file_exists( $source ) ) {
        copy( $source, $dest );
        echo "✅ Copied $script\n";
    } else {
        echo "❌ $script not found\n";
    }
}

// Copy documentation
$docs = array(
    'DEPLOYMENT-SUMMARY-REPORT.md',
    'DEPLOYMENT-VERIFICATION.md',
    'FIX-DEPLOYMENT-SUMMARY.md',
    'DEPLOYMENT-READINESS-CONFIRMATION.md'
);

foreach ( $docs as $doc ) {
    $source = __DIR__ . '/' . $doc;
    $dest = $deployment_dir . '/' . $doc;
    
    if ( file_exists( $source ) ) {
        copy( $source, $dest );
        echo "✅ Copied $doc\n";
    } else {
        echo "⚠️  $doc not found\n";
    }
}

// Create deployment script
$deploy_script = $deployment_dir . '/deploy.php';
$deploy_content = '<?php
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

// Check if we\'re in a WordPress directory
$wp_config = $wp_path . "/wp-config.php";
if ( ! file_exists( $wp_config ) ) {
    echo "❌ WordPress not found at: $wp_path\n";
    echo "Usage: php deploy.php [wordpress-path]\n";
    echo "Example: php deploy.php /path/to/wordpress\n\n";
    exit( 1 );
}

echo "✅ WordPress found at: $wp_path\n\n";

// Create mu-plugins directory if it doesn\'t exist
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
$debug_enabled = strpos( $config_content, "define( \'WP_DEBUG\', true )" ) !== false;
$debug_log_enabled = strpos( $config_content, "define( \'WP_DEBUG_LOG\', true )" ) !== false;

echo "\n📊 Debug Logging Status:\n";
echo "- WP_DEBUG: " . ( $debug_enabled ? "✅ Enabled" : "❌ Disabled" ) . "\n";
echo "- WP_DEBUG_LOG: " . ( $debug_log_enabled ? "✅ Enabled" : "❌ Disabled" ) . "\n";

if ( ! $debug_enabled || ! $debug_log_enabled ) {
    echo "\n⚠️  Recommendation: Enable debug logging in wp-config.php:\n";
    echo "define( \'WP_DEBUG\', true );\n";
    echo "define( \'WP_DEBUG_LOG\', true );\n";
    echo "define( \'WP_DEBUG_DISPLAY\', false );\n";
}

echo "\n✅ Deployment completed successfully!\n";
echo "\n📋 Next steps:\n";
echo "1. Test the fix: php test-fix.php\n";
echo "2. Load a WordPress page to trigger the fix\n";
echo "3. Check for admin notices about the fix being applied\n";
echo "4. Monitor results: php monitor-fix.php\n";
echo "\n🎉 Fix is now active and ready to resolve IntegrationRegistry conflicts!\n";
';

file_put_contents( $deploy_script, $deploy_content );
echo "✅ Created deployment script\n";

// Create README
$readme = $deployment_dir . '/README.md';
$readme_content = '# WooCommerce Blocks IntegrationRegistry Fix - Deployment Package

## 🚀 Quick Deployment

### Automatic Deployment
```bash
# Deploy to WordPress site
php deploy.php /path/to/wordpress

# Or deploy to current directory
php deploy.php
```

### Manual Deployment
```bash
# 1. Copy fix file to mu-plugins
cp mu-plugins/woocommerce-blocks-fix.php /path/to/wordpress/wp-content/mu-plugins/

# 2. Copy test scripts
cp test-fix.php /path/to/wordpress/
cp monitor-fix.php /path/to/wordpress/
```

## 📋 Verification

### Test Deployment
```bash
php test-fix.php
```

### Monitor Results
```bash
php monitor-fix.php
```

## 📊 Expected Results

- ✅ Elimination of `IntegrationRegistry::register` PHP notices
- ✅ Clean integration registry (no empty names)
- ✅ Admin success notice on first page load
- ✅ Maintained WooCommerce Blocks functionality

## 📝 Documentation

- `DEPLOYMENT-SUMMARY-REPORT.md` - Complete deployment guide
- `DEPLOYMENT-VERIFICATION.md` - Verification procedures
- `FIX-DEPLOYMENT-SUMMARY.md` - Technical implementation details
- `DEPLOYMENT-READINESS-CONFIRMATION.md` - Final readiness confirmation

## 🔧 Support

For issues or questions, refer to the documentation files included in this package.

---

**Package Version:** 1.0.0  
**Created:** ' . date( 'Y-m-d H:i:s' ) . '  
**Status:** Ready for deployment
';

file_put_contents( $readme, $readme_content );
echo "✅ Created README.md\n";

// Create package info
$package_info = array(
    'version' => '1.0.0',
    'created' => date( 'Y-m-d H:i:s' ),
    'files' => array(
        'mu-plugins/woocommerce-blocks-fix.php',
        'test-fix.php',
        'monitor-fix.php',
        'deploy.php',
        'README.md',
        'DEPLOYMENT-SUMMARY-REPORT.md',
        'DEPLOYMENT-VERIFICATION.md',
        'FIX-DEPLOYMENT-SUMMARY.md',
        'DEPLOYMENT-READINESS-CONFIRMATION.md'
    ),
    'total_size' => 0
);

// Calculate total size
foreach ( $package_info['files'] as $file ) {
    $file_path = $deployment_dir . '/' . $file;
    if ( file_exists( $file_path ) ) {
        $package_info['total_size'] += filesize( $file_path );
    }
}

$package_info['total_size_kb'] = round( $package_info['total_size'] / 1024, 2 );

file_put_contents( $deployment_dir . '/package-info.json', json_encode( $package_info, JSON_PRETTY_PRINT ) );
echo "✅ Created package-info.json\n";

// List package contents
echo "\n📦 Deployment Package Contents:\n";
$contents = scandir( $deployment_dir );
foreach ( $contents as $item ) {
    if ( $item !== '.' && $item !== '..' ) {
        $item_path = $deployment_dir . '/' . $item;
        if ( is_file( $item_path ) ) {
            $size = filesize( $item_path );
            echo "  📄 $item (" . round( $size / 1024, 2 ) . " KB)\n";
        } elseif ( is_dir( $item_path ) ) {
            $sub_contents = scandir( $item_path );
            $file_count = count( array_filter( $sub_contents, function( $file ) { return $file !== '.' && $file !== '..'; } ) );
            echo "  📁 $item/ ($file_count files)\n";
        }
    }
}

echo "\n📊 Package Summary:\n";
echo "- Total Size: " . $package_info['total_size_kb'] . " KB\n";
echo "- Files: " . count( $package_info['files'] ) . "\n";
echo "- Version: " . $package_info['version'] . "\n";
echo "- Created: " . $package_info['created'] . "\n";

echo "\n🎉 Deployment package created successfully!\n";
echo "\n📋 Usage Instructions:\n";
echo "1. Copy the deployment-package folder to your WordPress site\n";
echo "2. Run: php deployment-package/deploy.php\n";
echo "3. Follow the verification steps\n";
echo "\n✅ Package is ready for WordPress deployment!\n"; 