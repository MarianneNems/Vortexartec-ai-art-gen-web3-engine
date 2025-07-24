<?php
/**
 * Vortex AI Engine - Deployment Package Verification
 *
 * This script verifies that all required files are present in the deployment package.
 */

echo "=== VORTEX AI ENGINE - DEPLOYMENT PACKAGE VERIFICATION ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "========================================================\n\n";

// Required files for deployment
$required_files = [
    // Main plugin file
    'vortex-ai-engine.php',
    
    // Core includes
    'includes/class-vortex-loader.php',
    'includes/class-vortex-incentive-auditor.php',
    'includes/class-vortex-wallet-manager.php',
    'includes/class-vortex-accounting-system.php',
    'includes/class-vortex-conversion-system.php',
    'includes/class-vortex-integration-layer.php',
    'includes/class-vortex-frontend-interface.php',
    'includes/class-vortex-activation.php',
    
    // Admin and public
    'admin/class-vortex-admin.php',
    'public/class-vortex-public.php',
    
    // Languages
    'languages/vortex-ai-engine.pot',
    
    // Deployment scripts
    'deployment/production-deployment.php',
    'deployment/final-verification.php'
];

// Check if verification directory exists
$verify_dir = 'verify-package';
if (!is_dir($verify_dir)) {
    echo "❌ Verification directory not found: $verify_dir\n";
    echo "Please extract the deployment package first.\n";
    exit(1);
}

echo "Checking deployment package contents...\n\n";

$missing_files = [];
$found_files = [];

foreach ($required_files as $file) {
    $file_path = $verify_dir . '/' . $file;
    
    if (file_exists($file_path)) {
        $found_files[] = $file;
        echo "✅ $file\n";
    } else {
        $missing_files[] = $file;
        echo "❌ $file (MISSING)\n";
    }
}

echo "\n=== VERIFICATION RESULTS ===\n";
echo "Total Required Files: " . count($required_files) . "\n";
echo "Found Files: " . count($found_files) . "\n";
echo "Missing Files: " . count($missing_files) . "\n";

if (empty($missing_files)) {
    echo "\n🎉 DEPLOYMENT PACKAGE VERIFICATION PASSED!\n";
    echo "All required files are present and ready for deployment.\n";
    
    // Check package size
    $package_file = 'vortex-ai-engine-production-deploy.zip';
    if (file_exists($package_file)) {
        $size = filesize($package_file);
        $size_mb = round($size / 1024 / 1024, 2);
        echo "\nPackage Details:\n";
        echo "- File: $package_file\n";
        echo "- Size: $size_mb MB\n";
        echo "- Status: ✅ Ready for production deployment\n";
    }
    
    exit(0);
} else {
    echo "\n❌ DEPLOYMENT PACKAGE VERIFICATION FAILED!\n";
    echo "Missing files:\n";
    foreach ($missing_files as $file) {
        echo "- $file\n";
    }
    echo "\nPlease ensure all required files are included before deployment.\n";
    exit(1);
} 