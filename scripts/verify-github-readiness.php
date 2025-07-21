<?php
/**
 * VORTEX AI Engine - GitHub Release Readiness Verification
 * 
 * Final verification script to ensure repository is ready for GitHub release
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

/**
 * GitHub Release Readiness Verification Class
 */
class VORTEX_GitHub_Readiness_Verification {
    
    private $source_dir;
    private $public_dir;
    private $private_dir;
    private $verification_results = array();
    private $errors = array();
    private $warnings = array();
    
    public function __construct() {
        $this->source_dir = dirname(__FILE__) . '/../';
        $this->public_dir = $this->source_dir . 'public-release/';
        $this->private_dir = $this->source_dir . 'private/';
    }
    
    /**
     * Run complete verification
     */
    public function verify_readiness() {
        echo "🔍 VORTEX AI Engine - GitHub Release Readiness Verification\n";
        echo "==========================================================\n\n";
        
        // Verify directory structure
        $this->verify_directory_structure();
        
        // Verify public files
        $this->verify_public_files();
        
        // Verify sensitive data protection
        $this->verify_sensitive_data_protection();
        
        // Verify documentation
        $this->verify_documentation();
        
        // Verify security measures
        $this->verify_security_measures();
        
        // Verify configuration templates
        $this->verify_configuration_templates();
        
        // Generate verification report
        $this->generate_verification_report();
    }
    
    /**
     * Verify directory structure
     */
    private function verify_directory_structure() {
        echo "📁 Verifying directory structure...\n";
        
        $required_directories = array(
            $this->public_dir,
            $this->public_dir . 'includes',
            $this->public_dir . 'admin',
            $this->public_dir . 'public',
            $this->public_dir . 'assets',
            $this->public_dir . 'languages',
            $this->public_dir . 'docs',
            $this->public_dir . 'deployment',
            $this->public_dir . 'config',
            $this->private_dir,
            $this->private_dir . 'config',
            $this->private_dir . 'keys',
            $this->private_dir . 'logs',
            $this->private_dir . 'backups',
            $this->private_dir . 'sensitive-data'
        );
        
        foreach ($required_directories as $dir) {
            if (is_dir($dir)) {
                $this->add_success("Directory exists: " . basename($dir));
            } else {
                $this->add_error("Directory missing: " . basename($dir));
            }
        }
        
        echo "\n";
    }
    
    /**
     * Verify public files
     */
    private function verify_public_files() {
        echo "📄 Verifying public files...\n";
        
        $required_public_files = array(
            'README.md',
            'LICENSE',
            'CHANGELOG.md',
            'SECURITY.md',
            'CONTRIBUTING.md',
            'CODE_OF_CONDUCT.md',
            '.gitignore',
            'vortex-ai-engine.php',
            'docs/INSTALLATION.md',
            'docs/API-REFERENCE.md',
            'docs/CONFIGURATION.md',
            'config/wp-config-template.php',
            'config/.env-template'
        );
        
        foreach ($required_public_files as $file) {
            $file_path = $this->public_dir . $file;
            if (file_exists($file_path)) {
                $this->add_success("Public file exists: $file");
                
                // Check file size
                $size = filesize($file_path);
                if ($size > 0) {
                    $this->add_success("File has content: $file ($size bytes)");
                } else {
                    $this->add_warning("File is empty: $file");
                }
            } else {
                $this->add_error("Public file missing: $file");
            }
        }
        
        echo "\n";
    }
    
    /**
     * Verify sensitive data protection
     */
    private function verify_sensitive_data_protection() {
        echo "🔒 Verifying sensitive data protection...\n";
        
        // Check if sensitive files are in public directory
        $sensitive_files = array(
            'wp-config.php',
            'wp-salt.php',
            '.env',
            'config/aws-credentials.php',
            'config/blockchain-keys.php',
            'config/api-keys.php'
        );
        
        foreach ($sensitive_files as $file) {
            $public_file_path = $this->public_dir . $file;
            if (file_exists($public_file_path)) {
                $this->add_error("Sensitive file found in public directory: $file");
            } else {
                $this->add_success("Sensitive file not in public directory: $file");
            }
        }
        
        // Check if sensitive files are properly encrypted
        $encrypted_files = array(
            'config/encrypted-wp-config.php',
            'config/encrypted-wp-salt.php'
        );
        
        foreach ($encrypted_files as $file) {
            $file_path = $this->source_dir . $file;
            if (file_exists($file_path)) {
                $this->add_success("Encrypted file exists: $file");
                
                // Check if it's properly encrypted
                $content = file_get_contents($file_path);
                if (strpos($content, 'encrypted_data') !== false) {
                    $this->add_success("File appears to be properly encrypted: $file");
                } else {
                    $this->add_warning("File may not be properly encrypted: $file");
                }
            } else {
                $this->add_warning("Encrypted file missing: $file");
            }
        }
        
        // Check .gitignore for sensitive files
        $gitignore_path = $this->public_dir . '.gitignore';
        if (file_exists($gitignore_path)) {
            $gitignore_content = file_get_contents($gitignore_path);
            $sensitive_patterns = array(
                '.env',
                'wp-config.php',
                'wp-salt.php',
                'config/aws-credentials.php',
                'config/blockchain-keys.php',
                'config/api-keys.php',
                'keys/',
                'private/',
                'sensitive-data/'
            );
            
            foreach ($sensitive_patterns as $pattern) {
                if (strpos($gitignore_content, $pattern) !== false) {
                    $this->add_success("Sensitive pattern in .gitignore: $pattern");
                } else {
                    $this->add_warning("Sensitive pattern missing from .gitignore: $pattern");
                }
            }
        } else {
            $this->add_error(".gitignore file missing");
        }
        
        echo "\n";
    }
    
    /**
     * Verify documentation
     */
    private function verify_documentation() {
        echo "📚 Verifying documentation...\n";
        
        $documentation_files = array(
            'README.md' => array('min_size' => 1000, 'required_sections' => array('Features', 'Quick Start', 'Documentation')),
            'SECURITY.md' => array('min_size' => 500, 'required_sections' => array('Supported Versions', 'Reporting a Vulnerability')),
            'CONTRIBUTING.md' => array('min_size' => 500, 'required_sections' => array('Getting Started', 'Code Standards')),
            'CODE_OF_CONDUCT.md' => array('min_size' => 300, 'required_sections' => array('Our Pledge', 'Our Standards')),
            'docs/INSTALLATION.md' => array('min_size' => 200, 'required_sections' => array('Prerequisites', 'Installation Steps')),
            'docs/API-REFERENCE.md' => array('min_size' => 200, 'required_sections' => array('AI Agents', 'Shortcodes')),
            'docs/CONFIGURATION.md' => array('min_size' => 200, 'required_sections' => array('Environment Setup', 'Security Checklist'))
        );
        
        foreach ($documentation_files as $file => $requirements) {
            $file_path = $this->public_dir . $file;
            if (file_exists($file_path)) {
                $this->add_success("Documentation exists: $file");
                
                $content = file_get_contents($file_path);
                $size = strlen($content);
                
                if ($size >= $requirements['min_size']) {
                    $this->add_success("Documentation has sufficient content: $file ($size bytes)");
                } else {
                    $this->add_warning("Documentation may be too short: $file ($size bytes)");
                }
                
                foreach ($requirements['required_sections'] as $section) {
                    if (strpos($content, $section) !== false) {
                        $this->add_success("Required section found: $section in $file");
                    } else {
                        $this->add_warning("Required section missing: $section in $file");
                    }
                }
            } else {
                $this->add_error("Documentation missing: $file");
            }
        }
        
        echo "\n";
    }
    
    /**
     * Verify security measures
     */
    private function verify_security_measures() {
        echo "🛡️ Verifying security measures...\n";
        
        // Check for security-related files
        $security_files = array(
            'SECURITY.md',
            'CONTRIBUTING.md',
            'CODE_OF_CONDUCT.md',
            '.gitignore'
        );
        
        foreach ($security_files as $file) {
            $file_path = $this->public_dir . $file;
            if (file_exists($file_path)) {
                $this->add_success("Security file exists: $file");
            } else {
                $this->add_error("Security file missing: $file");
            }
        }
        
        // Check for sensitive data patterns in public files
        $sensitive_patterns = array(
            '/define\(\s*[\'"]DB_PASSWORD[\'"]\s*,\s*[\'"][^\'"]+[\'"]\s*\)/',
            '/[\'"]api_key[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/',
            '/[\'"]secret_key[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/',
            '/[\'"]aws_access_key[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/',
            '/[\'"]aws_secret_key[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/',
            '/[\'"]solana_private_key[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/'
        );
        
        $public_files = $this->get_public_files();
        foreach ($public_files as $file) {
            $content = file_get_contents($file);
            foreach ($sensitive_patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->add_error("Sensitive data pattern found in public file: " . basename($file));
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Verify configuration templates
     */
    private function verify_configuration_templates() {
        echo "⚙️ Verifying configuration templates...\n";
        
        $template_files = array(
            'config/wp-config-template.php' => array('required' => array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'WP_DEBUG')),
            'config/.env-template' => array('required' => array('DB_NAME', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'SOLANA_PRIVATE_KEY'))
        );
        
        foreach ($template_files as $file => $requirements) {
            $file_path = $this->public_dir . $file;
            if (file_exists($file_path)) {
                $this->add_success("Template exists: $file");
                
                $content = file_get_contents($file_path);
                foreach ($requirements['required'] as $required) {
                    if (strpos($content, $required) !== false) {
                        $this->add_success("Required configuration found: $required in $file");
                    } else {
                        $this->add_warning("Required configuration missing: $required in $file");
                    }
                }
                
                // Check if it uses placeholder values
                if (strpos($content, 'your_') !== false) {
                    $this->add_success("Template uses placeholder values: $file");
                } else {
                    $this->add_warning("Template may not use placeholder values: $file");
                }
            } else {
                $this->add_error("Template missing: $file");
            }
        }
        
        echo "\n";
    }
    
    /**
     * Get all public files recursively
     */
    private function get_public_files() {
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->public_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Add success result
     */
    private function add_success($message) {
        $this->verification_results[] = array('type' => 'success', 'message' => $message);
    }
    
    /**
     * Add warning result
     */
    private function add_warning($message) {
        $this->warnings[] = $message;
        $this->verification_results[] = array('type' => 'warning', 'message' => $message);
    }
    
    /**
     * Add error result
     */
    private function add_error($message) {
        $this->errors[] = $message;
        $this->verification_results[] = array('type' => 'error', 'message' => $message);
    }
    
    /**
     * Generate verification report
     */
    private function generate_verification_report() {
        $total_checks = count($this->verification_results);
        $success_count = count(array_filter($this->verification_results, function($r) { return $r['type'] === 'success'; }));
        $warning_count = count($this->warnings);
        $error_count = count($this->errors);
        
        echo "📊 GitHub Release Readiness Report\n";
        echo "==================================\n\n";
        
        echo "📈 Summary:\n";
        echo "  Total Checks: $total_checks\n";
        echo "  Passed: $success_count\n";
        echo "  Warnings: $warning_count\n";
        echo "  Errors: $error_count\n\n";
        
        if (!empty($this->errors)) {
            echo "❌ Critical Issues:\n";
            foreach ($this->errors as $error) {
                echo "  ✗ $error\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️ Warnings:\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ $warning\n";
            }
            echo "\n";
        }
        
        // Overall assessment
        if ($error_count === 0) {
            if ($warning_count === 0) {
                echo "🎉 Repository is READY for GitHub release!\n";
                echo "   All checks passed successfully.\n\n";
                
                echo "📋 Next Steps:\n";
                echo "  1. Create GitHub repository\n";
                echo "  2. Push public-release/ contents to main branch\n";
                echo "  3. Set up branch protection\n";
                echo "  4. Create proprietary branch with sensitive data\n";
                echo "  5. Configure CI/CD pipeline\n";
                echo "  6. Announce public release\n";
            } else {
                echo "✅ Repository is READY for GitHub release with minor warnings.\n";
                echo "   Consider addressing warnings for optimal setup.\n\n";
                
                echo "📋 Next Steps:\n";
                echo "  1. Address any warnings above\n";
                echo "  2. Create GitHub repository\n";
                echo "  3. Push public-release/ contents to main branch\n";
                echo "  4. Set up branch protection\n";
                echo "  5. Create proprietary branch with sensitive data\n";
            }
        } else {
            echo "🔧 Repository needs attention before GitHub release.\n";
            echo "   Please fix the critical issues above.\n\n";
            
            echo "📋 Required Actions:\n";
            echo "  1. Fix all critical issues\n";
            echo "  2. Re-run verification\n";
            echo "  3. Then proceed with GitHub release\n";
        }
        
        // Save report to file
        $report_file = $this->source_dir . 'github-readiness-report-' . date('Y-m-d-H-i-s') . '.txt';
        $report_content = ob_get_contents();
        file_put_contents($report_file, $report_content);
        
        echo "\n📄 Report saved to: $report_file\n";
    }
}

// Run verification if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $verification = new VORTEX_GitHub_Readiness_Verification();
    $verification->verify_readiness();
} 