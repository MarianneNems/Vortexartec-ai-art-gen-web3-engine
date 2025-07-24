<?php
/**
 * Vortex AI Engine - Health Monitor
 *
 * This script monitors the health and performance of the Vortex AI Engine plugin
 * in production. Run this regularly to ensure optimal performance.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_CLI')) {
    define('WP_CLI', true);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== VORTEX AI ENGINE - HEALTH MONITOR ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

/**
 * Health Monitor Class
 */
class Vortex_Health_Monitor {

    private $health_report = [];
    private $critical_issues = [];
    private $warnings = [];
    private $performance_metrics = [];

    /**
     * Run comprehensive health check
     */
    public function run_health_check() {
        echo "Running comprehensive health check...\n\n";

        $this->check_plugin_status();
        $this->check_database_health();
        $this->check_performance();
        $this->check_security();
        $this->check_integrations();
        $this->check_backups();
        $this->check_logs();

        $this->generate_health_report();
        $this->save_health_report();
    }

    /**
     * Check plugin status
     */
    private function check_plugin_status() {
        echo "Checking plugin status...\n";

        // Check if plugin is active
        if (function_exists('is_plugin_active')) {
            if (is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
                $this->health_report['plugin_status'] = [
                    'status' => 'healthy',
                    'message' => 'Plugin is active and running'
                ];
                echo "✅ Plugin is active\n";
            } else {
                $this->health_report['plugin_status'] = [
                    'status' => 'critical',
                    'message' => 'Plugin is not active'
                ];
                $this->critical_issues[] = 'Plugin is not active';
                echo "❌ Plugin is not active\n";
            }
        }

        // Check plugin files
        $required_files = [
            'vortex-ai-engine.php',
            'includes/class-vortex-loader.php',
            'includes/class-vortex-incentive-auditor.php',
            'includes/class-vortex-wallet-manager.php'
        ];

        $missing_files = [];
        foreach ($required_files as $file) {
            $file_path = WP_PLUGIN_DIR . '/vortex-ai-engine/' . $file;
            if (!file_exists($file_path)) {
                $missing_files[] = $file;
            }
        }

        if (empty($missing_files)) {
            $this->health_report['plugin_files'] = [
                'status' => 'healthy',
                'message' => 'All required files present'
            ];
            echo "✅ All required files present\n";
        } else {
            $this->health_report['plugin_files'] = [
                'status' => 'critical',
                'message' => 'Missing files: ' . implode(', ', $missing_files)
            ];
            $this->critical_issues[] = 'Missing required plugin files';
            echo "❌ Missing files: " . implode(', ', $missing_files) . "\n";
        }
    }

    /**
     * Check database health
     */
    private function check_database_health() {
        echo "Checking database health...\n";

        global $wpdb;

        // Check required tables
        $required_tables = [
            $wpdb->prefix . 'vortex_incentives',
            $wpdb->prefix . 'vortex_wallets',
            $wpdb->prefix . 'vortex_accounting'
        ];

        $missing_tables = [];
        foreach ($required_tables as $table) {
            $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$result) {
                $missing_tables[] = $table;
            }
        }

        if (empty($missing_tables)) {
            $this->health_report['database_tables'] = [
                'status' => 'healthy',
                'message' => 'All required tables present'
            ];
            echo "✅ All required tables present\n";
        } else {
            $this->health_report['database_tables'] = [
                'status' => 'critical',
                'message' => 'Missing tables: ' . implode(', ', $missing_tables)
            ];
            $this->critical_issues[] = 'Missing required database tables';
            echo "❌ Missing tables: " . implode(', ', $missing_tables) . "\n";
        }

        // Check database performance
        $start_time = microtime(true);
        $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->prefix}posts");
        $query_time = microtime(true) - $start_time;

        $this->performance_metrics['database_query_time'] = $query_time;

        if ($query_time < 0.1) {
            $this->health_report['database_performance'] = [
                'status' => 'healthy',
                'message' => "Database query time: " . round($query_time * 1000, 2) . "ms"
            ];
            echo "✅ Database performance: " . round($query_time * 1000, 2) . "ms\n";
        } else {
            $this->health_report['database_performance'] = [
                'status' => 'warning',
                'message' => "Slow database query time: " . round($query_time * 1000, 2) . "ms"
            ];
            $this->warnings[] = 'Slow database performance';
            echo "⚠️  Slow database performance: " . round($query_time * 1000, 2) . "ms\n";
        }
    }

    /**
     * Check performance
     */
    private function check_performance() {
        echo "Checking performance...\n";

        // Check memory usage
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        $memory_percentage = ($memory_usage / $this->parse_size($memory_limit)) * 100;

        $this->performance_metrics['memory_usage'] = $memory_usage;
        $this->performance_metrics['memory_percentage'] = $memory_percentage;

        if ($memory_percentage < 80) {
            $this->health_report['memory_usage'] = [
                'status' => 'healthy',
                'message' => "Memory usage: " . round($memory_percentage, 1) . "%"
            ];
            echo "✅ Memory usage: " . round($memory_percentage, 1) . "%\n";
        } else {
            $this->health_report['memory_usage'] = [
                'status' => 'warning',
                'message' => "High memory usage: " . round($memory_percentage, 1) . "%"
            ];
            $this->warnings[] = 'High memory usage';
            echo "⚠️  High memory usage: " . round($memory_percentage, 1) . "%\n";
        }

        // Check execution time
        $execution_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $this->performance_metrics['execution_time'] = $execution_time;

        if ($execution_time < 2.0) {
            $this->health_report['execution_time'] = [
                'status' => 'healthy',
                'message' => "Execution time: " . round($execution_time, 2) . "s"
            ];
            echo "✅ Execution time: " . round($execution_time, 2) . "s\n";
        } else {
            $this->health_report['execution_time'] = [
                'status' => 'warning',
                'message' => "Slow execution time: " . round($execution_time, 2) . "s"
            ];
            $this->warnings[] = 'Slow execution time';
            echo "⚠️  Slow execution time: " . round($execution_time, 2) . "s\n";
        }
    }

    /**
     * Check security
     */
    private function check_security() {
        echo "Checking security...\n";

        // Check file permissions
        $plugin_dir = WP_PLUGIN_DIR . '/vortex-ai-engine';
        $permission_issues = [];

        if (is_dir($plugin_dir)) {
            $dir_perms = substr(sprintf('%o', fileperms($plugin_dir)), -4);
            if ($dir_perms != '0755' && $dir_perms != '0750') {
                $permission_issues[] = "Plugin directory: $dir_perms (should be 0755)";
            }
        }

        if (empty($permission_issues)) {
            $this->health_report['file_permissions'] = [
                'status' => 'healthy',
                'message' => 'File permissions are correct'
            ];
            echo "✅ File permissions are correct\n";
        } else {
            $this->health_report['file_permissions'] = [
                'status' => 'warning',
                'message' => 'Permission issues: ' . implode(', ', $permission_issues)
            ];
            $this->warnings[] = 'File permission issues';
            echo "⚠️  Permission issues detected\n";
        }

        // Check for suspicious files
        $suspicious_files = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getRealPath());
                if (strpos($content, 'eval(') !== false || 
                    strpos($content, 'base64_decode(') !== false ||
                    strpos($content, 'gzinflate(') !== false) {
                    $suspicious_files[] = $file->getFilename();
                }
            }
        }

        if (empty($suspicious_files)) {
            $this->health_report['security_scan'] = [
                'status' => 'healthy',
                'message' => 'No suspicious files detected'
            ];
            echo "✅ No suspicious files detected\n";
        } else {
            $this->health_report['security_scan'] = [
                'status' => 'critical',
                'message' => 'Suspicious files detected: ' . implode(', ', $suspicious_files)
            ];
            $this->critical_issues[] = 'Suspicious files detected';
            echo "❌ Suspicious files detected: " . implode(', ', $suspicious_files) . "\n";
        }
    }

    /**
     * Check integrations
     */
    private function check_integrations() {
        echo "Checking integrations...\n";

        // Check if required extensions are loaded
        $required_extensions = ['curl', 'json', 'mbstring', 'pdo_mysql'];
        $missing_extensions = [];

        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }

        if (empty($missing_extensions)) {
            $this->health_report['php_extensions'] = [
                'status' => 'healthy',
                'message' => 'All required PHP extensions loaded'
            ];
            echo "✅ All required PHP extensions loaded\n";
        } else {
            $this->health_report['php_extensions'] = [
                'status' => 'critical',
                'message' => 'Missing extensions: ' . implode(', ', $missing_extensions)
            ];
            $this->critical_issues[] = 'Missing required PHP extensions';
            echo "❌ Missing extensions: " . implode(', ', $missing_extensions) . "\n";
        }
    }

    /**
     * Check backups
     */
    private function check_backups() {
        echo "Checking backups...\n";

        $backup_dir = WP_CONTENT_DIR . '/uploads/vortex-ai-engine/backups';
        $backup_files = [];

        if (is_dir($backup_dir)) {
            $backup_files = glob($backup_dir . '/*.sql');
        }

        if (!empty($backup_files)) {
            $latest_backup = basename(end($backup_files));
            $backup_age = time() - filemtime(end($backup_files));
            $backup_age_hours = round($backup_age / 3600, 1);

            if ($backup_age_hours < 24) {
                $this->health_report['backups'] = [
                    'status' => 'healthy',
                    'message' => "Recent backup found: $latest_backup ($backup_age_hours hours ago)"
                ];
                echo "✅ Recent backup found: $latest_backup ($backup_age_hours hours ago)\n";
            } else {
                $this->health_report['backups'] = [
                    'status' => 'warning',
                    'message' => "Backup is old: $latest_backup ($backup_age_hours hours ago)"
                ];
                $this->warnings[] = 'Backup is old';
                echo "⚠️  Backup is old: $latest_backup ($backup_age_hours hours ago)\n";
            }
        } else {
            $this->health_report['backups'] = [
                'status' => 'warning',
                'message' => 'No backup files found'
            ];
            $this->warnings[] = 'No backup files found';
            echo "⚠️  No backup files found\n";
        }
    }

    /**
     * Check logs
     */
    private function check_logs() {
        echo "Checking logs...\n";

        $log_file = WP_CONTENT_DIR . '/debug.log';
        $error_count = 0;

        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            $error_count = substr_count($log_content, '[ERROR]');
        }

        if ($error_count == 0) {
            $this->health_report['error_logs'] = [
                'status' => 'healthy',
                'message' => 'No errors in recent logs'
            ];
            echo "✅ No errors in recent logs\n";
        } else {
            $this->health_report['error_logs'] = [
                'status' => 'warning',
                'message' => "$error_count errors found in recent logs"
            ];
            $this->warnings[] = "$error_count errors in logs";
            echo "⚠️  $error_count errors found in recent logs\n";
        }
    }

    /**
     * Generate health report
     */
    private function generate_health_report() {
        echo "\n=== HEALTH REPORT ===\n\n";

        $healthy_count = 0;
        $warning_count = 0;
        $critical_count = 0;

        foreach ($this->health_report as $check => $result) {
            $status = $result['status'];
            $message = $result['message'];

            switch ($status) {
                case 'healthy':
                    echo "✅ $check: $message\n";
                    $healthy_count++;
                    break;
                case 'warning':
                    echo "⚠️  $check: $message\n";
                    $warning_count++;
                    break;
                case 'critical':
                    echo "❌ $check: $message\n";
                    $critical_count++;
                    break;
            }
        }

        echo "\n=== SUMMARY ===\n";
        echo "Total Checks: " . count($this->health_report) . "\n";
        echo "Healthy: $healthy_count\n";
        echo "Warnings: $warning_count\n";
        echo "Critical: $critical_count\n";

        if ($critical_count > 0) {
            echo "\n🚨 CRITICAL ISSUES DETECTED:\n";
            foreach ($this->critical_issues as $issue) {
                echo "❌ $issue\n";
            }
            echo "\n❌ HEALTH CHECK FAILED: Critical issues must be resolved.\n";
        } elseif ($warning_count > 0) {
            echo "\n⚠️  WARNINGS DETECTED:\n";
            foreach ($this->warnings as $warning) {
                echo "⚠️  $warning\n";
            }
            echo "\n⚠️  HEALTH CHECK PASSED WITH WARNINGS: Review warnings.\n";
        } else {
            echo "\n✅ HEALTH CHECK PASSED: All systems operational.\n";
        }
    }

    /**
     * Save health report
     */
    private function save_health_report() {
        $report_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'health_report' => $this->health_report,
            'critical_issues' => $this->critical_issues,
            'warnings' => $this->warnings,
            'performance_metrics' => $this->performance_metrics
        ];

        $report_file = 'health-report-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents($report_file, json_encode($report_data, JSON_PRETTY_PRINT));

        echo "\nHealth report saved to: $report_file\n";
    }

    /**
     * Parse size string to bytes
     */
    private function parse_size($size) {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
}

// Run health check
$monitor = new Vortex_Health_Monitor();
$monitor->run_health_check(); 