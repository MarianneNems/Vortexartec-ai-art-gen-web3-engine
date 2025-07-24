<?php
/**
 * Vortex AI Engine - Environment Verification Script
 * 
 * Verifies wp-config.php, database connectivity, Redis status, and plugin readiness
 * Run this before deploying to staging/production
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    if (!file_exists('../../../wp-config.php')) {
        die('❌ WordPress not found. Please run this script from wp-content/plugins/vortex-ai-engine/deployment/');
    }
    require_once '../../../wp-config.php';
}

class Vortex_Environment_Verifier {
    
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    /**
     * Run all verification checks
     */
    public function run_verification() {
        echo "🔍 VORTEX AI ENGINE - Environment Verification\n";
        echo "==============================================\n\n";
        
        $this->check_wp_config();
        $this->check_database_connectivity();
        $this->check_redis_status();
        $this->check_plugin_files();
        $this->check_permissions();
        $this->check_aws_credentials();
        $this->check_agreement_policy();
        
        $this->display_results();
    }
    
    /**
     * Check wp-config.php configuration
     */
    private function check_wp_config() {
        echo "📋 1. Checking wp-config.php...\n";
        
        // Check database constants
        $db_constants = ['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST'];
        foreach ($db_constants as $constant) {
            if (!defined($constant) || empty(constant($constant))) {
                $this->errors[] = "Missing or empty {$constant} in wp-config.php";
            } else {
                $this->success[] = "✓ {$constant} is set";
            }
        }
        
        // Check salts
        $salts = [
            'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
            'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'
        ];
        
        $placeholder_salts = 0;
        foreach ($salts as $salt) {
            if (!defined($salt)) {
                $this->errors[] = "Missing {$salt} in wp-config.php";
            } elseif (strpos(constant($salt), 'put your unique phrase here') !== false) {
                $placeholder_salts++;
                $this->warnings[] = "⚠ {$salt} is using placeholder value";
            } else {
                $this->success[] = "✓ {$salt} is properly set";
            }
        }
        
        if ($placeholder_salts > 0) {
            $this->warnings[] = "⚠ {$placeholder_salts} salt keys are using placeholder values. Generate new ones at: https://api.wordpress.org/secret-key/1.1/salt/";
        }
        
        // Check Redis configuration
        if (defined('WP_REDIS_HOST') && !empty(WP_REDIS_HOST)) {
            $this->success[] = "✓ Redis host configured: " . WP_REDIS_HOST;
        } else {
            $this->success[] = "✓ Redis disabled (no WP_REDIS_HOST defined)";
        }
        
        // Check debug settings
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->warnings[] = "⚠ WP_DEBUG is enabled - disable in production";
        } else {
            $this->success[] = "✓ WP_DEBUG is disabled";
        }
        
        echo "   Completed wp-config.php check\n\n";
    }
    
    /**
     * Check database connectivity
     */
    private function check_database_connectivity() {
        echo "🗄️ 2. Testing database connectivity...\n";
        
        global $wpdb;
        
        if (!$wpdb) {
            $this->errors[] = "WordPress database object not available";
            return;
        }
        
        // Test connection
        $result = $wpdb->get_var("SELECT 1");
        if ($result === '1') {
            $this->success[] = "✓ Database connection successful";
        } else {
            $this->errors[] = "Database connection failed: " . $wpdb->last_error;
        }
        
        // Check Vortex tables
        $tables = [
            $wpdb->prefix . 'vortex_activity_logs',
            $wpdb->prefix . 'vortex_artist_journey',
            $wpdb->prefix . 'vortex_agreements'
        ];
        
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($exists) {
                $this->success[] = "✓ Table exists: {$table}";
            } else {
                $this->warnings[] = "⚠ Table missing: {$table} (will be created on plugin activation)";
            }
        }
        
        echo "   Completed database connectivity check\n\n";
    }
    
    /**
     * Check Redis status
     */
    private function check_redis_status() {
        echo "🔴 3. Checking Redis status...\n";
        
        if (defined('WP_REDIS_HOST') && !empty(WP_REDIS_HOST)) {
            // Try to connect to Redis
            if (extension_loaded('redis')) {
                try {
                    $redis = new Redis();
                    $connected = $redis->connect(WP_REDIS_HOST, defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379);
                    
                    if ($connected) {
                        $this->success[] = "✓ Redis connection successful";
                        $redis->close();
                    } else {
                        $this->errors[] = "Redis connection failed";
                    }
                } catch (Exception $e) {
                    $this->errors[] = "Redis error: " . $e->getMessage();
                }
            } else {
                $this->errors[] = "Redis extension not loaded but Redis is configured";
            }
        } else {
            $this->success[] = "✓ Redis not configured (using default WordPress caching)";
        }
        
        echo "   Completed Redis status check\n\n";
    }
    
    /**
     * Check plugin files
     */
    private function check_plugin_files() {
        echo "📁 4. Verifying plugin files...\n";
        
        $required_files = [
            'vortex-ai-engine.php',
            'includes/class-vortex-agreement-policy.php',
            'includes/class-vortex-loader.php',
            'includes/class-vortex-config.php',
            'includes/class-vortex-database-manager.php',
            'includes/ai-agents/class-vortex-archer-orchestrator.php',
            'includes/ai-agents/class-vortex-huraii-agent.php',
            'includes/ai-agents/class-vortex-cloe-agent.php',
            'includes/ai-agents/class-vortex-horace-agent.php',
            'includes/ai-agents/class-vortex-thorius-agent.php',
            'assets/js/agreement.js',
            'assets/css/agreement.css'
        ];
        
        foreach ($required_files as $file) {
            if (file_exists($file)) {
                $this->success[] = "✓ File exists: {$file}";
            } else {
                $this->errors[] = "Missing file: {$file}";
            }
        }
        
        // Check PHP syntax
        $php_files = glob('includes/**/*.php');
        $php_files[] = 'vortex-ai-engine.php';
        
        foreach ($php_files as $file) {
            $output = [];
            $return_var = 0;
            exec("php -l {$file} 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                $this->success[] = "✓ Syntax OK: {$file}";
            } else {
                $this->errors[] = "Syntax error in {$file}: " . implode(' ', $output);
            }
        }
        
        echo "   Completed plugin files check\n\n";
    }
    
    /**
     * Check file permissions
     */
    private function check_permissions() {
        echo "🔐 5. Checking file permissions...\n";
        
        $directories = [
            'assets/css',
            'assets/js',
            'includes',
            'admin',
            'public'
        ];
        
        foreach ($directories as $dir) {
            if (is_dir($dir) && is_readable($dir)) {
                $this->success[] = "✓ Directory readable: {$dir}";
            } else {
                $this->errors[] = "Directory not readable: {$dir}";
            }
        }
        
        // Check upload directory
        $upload_dir = wp_upload_dir();
        if (is_writable($upload_dir['basedir'])) {
            $this->success[] = "✓ Upload directory writable";
        } else {
            $this->warnings[] = "⚠ Upload directory not writable: {$upload_dir['basedir']}";
        }
        
        echo "   Completed permissions check\n\n";
    }
    
    /**
     * Check AWS credentials
     */
    private function check_aws_credentials() {
        echo "☁️ 6. Checking AWS credentials...\n";
        
        $aws_constants = [
            'AWS_ACCESS_KEY_ID',
            'AWS_SECRET_ACCESS_KEY',
            'AWS_DEFAULT_REGION'
        ];
        
        $aws_configured = true;
        foreach ($aws_constants as $constant) {
            if (!defined($constant) || empty(constant($constant))) {
                $aws_configured = false;
                $this->warnings[] = "⚠ {$constant} not configured";
            }
        }
        
        if ($aws_configured) {
            $this->success[] = "✓ AWS credentials configured";
            
            // Test AWS SDK if available
            if (class_exists('Aws\Sqs\SqsClient')) {
                try {
                    $sqs = new Aws\Sqs\SqsClient([
                        'version' => 'latest',
                        'region'  => AWS_DEFAULT_REGION,
                        'credentials' => [
                            'key'    => AWS_ACCESS_KEY_ID,
                            'secret' => AWS_SECRET_ACCESS_KEY,
                        ]
                    ]);
                    
                    // Try to list queues (this will fail if credentials are wrong)
                    $result = $sqs->listQueues();
                    $this->success[] = "✓ AWS SQS connection successful";
                } catch (Exception $e) {
                    $this->warnings[] = "⚠ AWS SQS connection failed: " . $e->getMessage();
                }
            }
        } else {
            $this->warnings[] = "⚠ AWS not configured (some features will be limited)";
        }
        
        echo "   Completed AWS credentials check\n\n";
    }
    
    /**
     * Check agreement policy
     */
    private function check_agreement_policy() {
        echo "📜 7. Checking agreement policy...\n";
        
        if (class_exists('Vortex_Agreement_Policy')) {
            $this->success[] = "✓ Agreement policy class loaded";
            
            $agreement = Vortex_Agreement_Policy::get_instance();
            if ($agreement) {
                $this->success[] = "✓ Agreement policy instance created";
            } else {
                $this->errors[] = "Failed to create agreement policy instance";
            }
        } else {
            $this->errors[] = "Agreement policy class not found";
        }
        
        // Check agreement assets
        if (file_exists('assets/js/agreement.js') && filesize('assets/js/agreement.js') > 0) {
            $this->success[] = "✓ Agreement JavaScript file exists";
        } else {
            $this->errors[] = "Agreement JavaScript file missing or empty";
        }
        
        if (file_exists('assets/css/agreement.css') && filesize('assets/css/agreement.css') > 0) {
            $this->success[] = "✓ Agreement CSS file exists";
        } else {
            $this->errors[] = "Agreement CSS file missing or empty";
        }
        
        echo "   Completed agreement policy check\n\n";
    }
    
    /**
     * Display verification results
     */
    private function display_results() {
        echo "📊 VERIFICATION RESULTS\n";
        echo "=======================\n\n";
        
        if (!empty($this->success)) {
            echo "✅ SUCCESS (" . count($this->success) . "):\n";
            foreach ($this->success as $message) {
                echo "   {$message}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️  WARNINGS (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $message) {
                echo "   {$message}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "❌ ERRORS (" . count($this->errors) . "):\n";
            foreach ($this->errors as $message) {
                echo "   {$message}\n";
            }
            echo "\n";
        }
        
        // Summary
        $total_checks = count($this->success) + count($this->warnings) + count($this->errors);
        echo "📈 SUMMARY:\n";
        echo "   Total Checks: {$total_checks}\n";
        echo "   Passed: " . count($this->success) . "\n";
        echo "   Warnings: " . count($this->warnings) . "\n";
        echo "   Errors: " . count($this->errors) . "\n\n";
        
        if (empty($this->errors)) {
            echo "🎉 Environment verification PASSED!\n";
            echo "   Ready for staging deployment.\n";
        } else {
            echo "🚨 Environment verification FAILED!\n";
            echo "   Please fix the errors above before deployment.\n";
        }
        
        echo "\n";
    }
}

// Run verification if script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $verifier = new Vortex_Environment_Verifier();
    $verifier->run_verification();
} 