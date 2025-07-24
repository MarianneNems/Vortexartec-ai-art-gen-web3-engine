<?php
/**
 * Vortex AI Engine - Final Verification Script
 * 
 * This script performs comprehensive verification of all components
 * before production deployment.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_CLI')) {
    define('WP_CLI', true);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== VORTEX AI ENGINE - FINAL VERIFICATION ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "=============================================\n\n";

$verification_results = [];
$critical_errors = [];
$warnings = [];

/**
 * Check PHP Version
 */
function check_php_version() {
    global $verification_results;
    
    $required_version = '8.0';
    $current_version = PHP_VERSION;
    
    if (version_compare($current_version, $required_version, '>=')) {
        $verification_results['php_version'] = [
            'status' => 'PASS',
            'message' => "PHP Version: $current_version (Required: $required_version+)"
        ];
    } else {
        $verification_results['php_version'] = [
            'status' => 'FAIL',
            'message' => "PHP Version: $current_version (Required: $required_version+)"
        ];
        global $critical_errors;
        $critical_errors[] = "PHP version $current_version is below required version $required_version";
    }
}

/**
 * Check WordPress Installation
 */
function check_wordpress_installation() {
    global $verification_results;
    
    if (file_exists('wp-config.php')) {
        $verification_results['wordpress_config'] = [
            'status' => 'PASS',
            'message' => 'WordPress configuration file found'
        ];
    } else {
        $verification_results['wordpress_config'] = [
            'status' => 'FAIL',
            'message' => 'WordPress configuration file not found'
        ];
        global $critical_errors;
        $critical_errors[] = 'WordPress configuration file not found';
    }
    
    if (file_exists('wp-content/plugins/vortex-ai-engine/vortex-ai-engine.php')) {
        $verification_results['plugin_main_file'] = [
            'status' => 'PASS',
            'message' => 'Vortex AI Engine main plugin file found'
        ];
    } else {
        $verification_results['plugin_main_file'] = [
            'status' => 'FAIL',
            'message' => 'Vortex AI Engine main plugin file not found'
        ];
        global $critical_errors;
        $critical_errors[] = 'Vortex AI Engine main plugin file not found';
    }
}

/**
 * Check Database Connection
 */
function check_database_connection() {
    global $verification_results;
    
    if (file_exists('wp-config.php')) {
        // Try to include wp-config to get database settings
        $wp_config_content = file_get_contents('wp-config.php');
        
        if (preg_match("/define\(\s*'DB_HOST',\s*'([^']+)'\s*\)/", $wp_config_content, $matches)) {
            $db_host = $matches[1];
        } else {
            $db_host = 'localhost';
        }
        
        if (preg_match("/define\(\s*'DB_NAME',\s*'([^']+)'\s*\)/", $wp_config_content, $matches)) {
            $db_name = $matches[1];
        } else {
            $db_name = 'wordpress';
        }
        
        if (preg_match("/define\(\s*'DB_USER',\s*'([^']+)'\s*\)/", $wp_config_content, $matches)) {
            $db_user = $matches[1];
        } else {
            $db_user = 'root';
        }
        
        if (preg_match("/define\(\s*'DB_PASSWORD',\s*'([^']+)'\s*\)/", $wp_config_content, $matches)) {
            $db_password = $matches[1];
        } else {
            $db_password = '';
        }
        
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $verification_results['database_connection'] = [
                'status' => 'PASS',
                'message' => "Database connection successful to $db_name on $db_host"
            ];
        } catch (PDOException $e) {
            $verification_results['database_connection'] = [
                'status' => 'FAIL',
                'message' => "Database connection failed: " . $e->getMessage()
            ];
            global $critical_errors;
            $critical_errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    } else {
        $verification_results['database_connection'] = [
            'status' => 'SKIP',
            'message' => 'Skipped - wp-config.php not found'
        ];
    }
}

/**
 * Check Plugin Files
 */
function check_plugin_files() {
    global $verification_results;
    
    $required_files = [
        'vortex-ai-engine.php',
        'includes/class-vortex-loader.php',
        'includes/class-vortex-incentive-auditor.php',
        'includes/class-vortex-wallet-manager.php',
        'includes/class-vortex-accounting-system.php',
        'includes/class-vortex-conversion-system.php',
        'includes/class-vortex-integration-layer.php',
        'includes/class-vortex-frontend-interface.php',
        'includes/class-vortex-activation.php',
        'admin/class-vortex-admin.php',
        'public/class-vortex-public.php',
        'languages/vortex-ai-engine.pot'
    ];
    
    $missing_files = [];
    $existing_files = [];
    
    foreach ($required_files as $file) {
        $file_path = "wp-content/plugins/vortex-ai-engine/$file";
        if (file_exists($file_path)) {
            $existing_files[] = $file;
        } else {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        $verification_results['plugin_files'] = [
            'status' => 'PASS',
            'message' => 'All required plugin files present (' . count($existing_files) . ' files)'
        ];
    } else {
        $verification_results['plugin_files'] = [
            'status' => 'FAIL',
            'message' => 'Missing files: ' . implode(', ', $missing_files)
        ];
        global $critical_errors;
        $critical_errors[] = 'Missing required plugin files: ' . implode(', ', $missing_files);
    }
}

/**
 * Check File Permissions
 */
function check_file_permissions() {
    global $verification_results;
    
    $directories_to_check = [
        'wp-content/plugins/vortex-ai-engine',
        'wp-content/uploads',
        'wp-content/cache',
        'wp-content/logs'
    ];
    
    $permission_issues = [];
    
    foreach ($directories_to_check as $dir) {
        if (is_dir($dir)) {
            $perms = substr(sprintf('%o', fileperms($dir)), -4);
            if ($perms != '0755' && $perms != '0750') {
                $permission_issues[] = "$dir (current: $perms, recommended: 0755)";
            }
        }
    }
    
    if (empty($permission_issues)) {
        $verification_results['file_permissions'] = [
            'status' => 'PASS',
            'message' => 'File permissions are correct'
        ];
    } else {
        $verification_results['file_permissions'] = [
            'status' => 'WARNING',
            'message' => 'Permission issues: ' . implode(', ', $permission_issues)
        ];
        global $warnings;
        $warnings[] = 'File permission issues detected';
    }
}

/**
 * Check Security Configuration
 */
function check_security_configuration() {
    global $verification_results;
    
    $security_checks = [];
    
    // Check if wp-config.php is readable by web server
    if (file_exists('wp-config.php')) {
        $wp_config_perms = substr(sprintf('%o', fileperms('wp-config.php')), -4);
        if ($wp_config_perms == '0644') {
            $security_checks[] = 'wp-config.php has correct permissions';
        } else {
            $security_checks[] = "wp-config.php permissions: $wp_config_perms (should be 0644)";
        }
    }
    
    // Check for .htaccess file
    if (file_exists('.htaccess')) {
        $security_checks[] = '.htaccess file exists';
    } else {
        $security_checks[] = '.htaccess file missing';
    }
    
    // Check for wp-config.php backup
    if (file_exists('wp-config-backup.php')) {
        $security_checks[] = 'wp-config.php backup exists';
    } else {
        $security_checks[] = 'wp-config.php backup missing';
    }
    
    $verification_results['security_configuration'] = [
        'status' => 'INFO',
        'message' => implode(', ', $security_checks)
    ];
}

/**
 * Check System Requirements
 */
function check_system_requirements() {
    global $verification_results;
    
    $requirements = [];
    
    // Check memory limit
    $memory_limit = ini_get('memory_limit');
    $requirements[] = "Memory limit: $memory_limit";
    
    // Check max execution time
    $max_execution_time = ini_get('max_execution_time');
    $requirements[] = "Max execution time: $max_execution_time seconds";
    
    // Check upload max filesize
    $upload_max_filesize = ini_get('upload_max_filesize');
    $requirements[] = "Upload max filesize: $upload_max_filesize";
    
    // Check post max size
    $post_max_size = ini_get('post_max_size');
    $requirements[] = "Post max size: $post_max_size";
    
    $verification_results['system_requirements'] = [
        'status' => 'INFO',
        'message' => implode(', ', $requirements)
    ];
}

/**
 * Check Backup Status
 */
function check_backup_status() {
    global $verification_results;
    
    $backup_files = [];
    
    if (is_dir('backups')) {
        $backup_files = glob('backups/*.sql');
    }
    
    if (!empty($backup_files)) {
        $latest_backup = basename(end($backup_files));
        $verification_results['backup_status'] = [
            'status' => 'PASS',
            'message' => "Backup files found: " . count($backup_files) . " (Latest: $latest_backup)"
        ];
    } else {
        $verification_results['backup_status'] = [
            'status' => 'WARNING',
            'message' => 'No backup files found in backups directory'
        ];
        global $warnings;
        $warnings[] = 'No backup files found';
    }
}

/**
 * Generate Summary Report
 */
function generate_summary_report() {
    global $verification_results, $critical_errors, $warnings;
    
    echo "=== VERIFICATION RESULTS ===\n\n";
    
    $pass_count = 0;
    $fail_count = 0;
    $warning_count = 0;
    $info_count = 0;
    
    foreach ($verification_results as $check => $result) {
        $status = $result['status'];
        $message = $result['message'];
        
        switch ($status) {
            case 'PASS':
                echo "✅ PASS: $message\n";
                $pass_count++;
                break;
            case 'FAIL':
                echo "❌ FAIL: $message\n";
                $fail_count++;
                break;
            case 'WARNING':
                echo "⚠️  WARNING: $message\n";
                $warning_count++;
                break;
            case 'INFO':
                echo "ℹ️  INFO: $message\n";
                $info_count++;
                break;
            case 'SKIP':
                echo "⏭️  SKIP: $message\n";
                break;
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Total Checks: " . count($verification_results) . "\n";
    echo "Passed: $pass_count\n";
    echo "Failed: $fail_count\n";
    echo "Warnings: $warning_count\n";
    echo "Info: $info_count\n";
    
    if (!empty($critical_errors)) {
        echo "\n=== CRITICAL ERRORS ===\n";
        foreach ($critical_errors as $error) {
            echo "❌ $error\n";
        }
        echo "\n🚫 DEPLOYMENT BLOCKED: Critical errors must be resolved before deployment.\n";
        return false;
    }
    
    if (!empty($warnings)) {
        echo "\n=== WARNINGS ===\n";
        foreach ($warnings as $warning) {
            echo "⚠️  $warning\n";
        }
        echo "\n⚠️  WARNINGS: Review warnings before deployment.\n";
    }
    
    if ($fail_count == 0) {
        echo "\n✅ VERIFICATION PASSED: Ready for deployment!\n";
        return true;
    } else {
        echo "\n❌ VERIFICATION FAILED: Fix issues before deployment.\n";
        return false;
    }
}

// Run all verification checks
echo "Running verification checks...\n\n";

check_php_version();
check_wordpress_installation();
check_database_connection();
check_plugin_files();
check_file_permissions();
check_security_configuration();
check_system_requirements();
check_backup_status();

// Generate summary report
$verification_passed = generate_summary_report();

// Save results to file
$results_file = 'deployment/verification-results-' . date('Y-m-d-H-i-s') . '.json';
file_put_contents($results_file, json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $verification_results,
    'critical_errors' => $critical_errors,
    'warnings' => $warnings,
    'verification_passed' => $verification_passed
], JSON_PRETTY_PRINT));

echo "\nResults saved to: $results_file\n";

// Exit with appropriate code
if ($verification_passed) {
    echo "\n✅ Final verification completed successfully!\n";
    exit(0);
} else {
    echo "\n❌ Final verification failed. Please fix critical errors before deployment.\n";
    exit(1);
} 