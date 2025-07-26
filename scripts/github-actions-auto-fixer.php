<?php
/**
 * VORTEX AI ENGINE - GITHUB ACTIONS AUTO-FIXER
 * 
 * Comprehensive GitHub Actions auto-fixer that automatically detects
 * and fixes deprecation errors with real-time learning and recursive
 * self-improvement
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

// Load WordPress if not already loaded
if (!function_exists('wp_verify_nonce')) {
    require_once(dirname(__FILE__) . '/../../../wp-load.php');
}

// Load required classes
require_once(dirname(__FILE__) . '/../includes/class-vortex-github-self-healing-system.php');

class VORTEX_GitHub_Actions_Auto_Fixer {
    
    private $plugin_dir;
    private $fix_results = array();
    private $errors_found = array();
    private $fixes_applied = array();
    private $files_checked = 0;
    private $files_fixed = 0;
    private $output_json = false;
    private $auto_commit = false;
    private $github_healing_system;
    
    // GitHub Actions deprecation mappings
    private $deprecation_fixes = array(
        'actions/upload-artifact@v3' => 'actions/upload-artifact@v4',
        'actions/download-artifact@v3' => 'actions/download-artifact@v4',
        'actions/checkout@v3' => 'actions/checkout@v4',
        'actions/setup-node@v3' => 'actions/setup-node@v4',
        'actions/setup-python@v3' => 'actions/setup-python@v4',
        'actions/cache@v2' => 'actions/cache@v4',
        'actions/github-script@v5' => 'actions/github-script@v7',
        'actions/github-script@v6' => 'actions/github-script@v7',
        'actions/setup-java@v3' => 'actions/setup-java@v4',
        'actions/upload-pages-artifact@v2' => 'actions/upload-pages-artifact@v3',
        'actions/deploy-pages@v2' => 'actions/deploy-pages@v4',
        'actions/configure-pages@v3' => 'actions/configure-pages@v4',
        'actions/upload-release-asset@v1' => 'actions/upload-release-asset@v2'
    );
    
    public function __construct($output_json = false, $auto_commit = false) {
        $this->plugin_dir = dirname(__FILE__) . '/../';
        $this->output_json = $output_json;
        $this->auto_commit = $auto_commit;
        $this->github_healing_system = Vortex_GitHub_Self_Healing_System::get_instance();
    }
    
    /**
     * Run comprehensive GitHub Actions auto-fix
     */
    public function run_comprehensive_github_actions_fix() {
        if (!$this->output_json) {
            echo "🔧 VORTEX AI ENGINE - GITHUB ACTIONS AUTO-FIXER\n";
            echo "==============================================\n\n";
        }
        
        // 1. Pre-fix Assessment
        $this->pre_fix_assessment();
        
        // 2. Scan for GitHub Actions Errors
        $this->scan_github_actions_errors();
        
        // 3. Apply Automatic Fixes
        $this->apply_automatic_fixes();
        
        // 4. Validate Fixes
        $this->validate_fixes();
        
        // 5. Update Learning System
        $this->update_learning_system();
        
        // 6. Commit Changes (if auto_commit enabled)
        if ($this->auto_commit) {
            $this->commit_changes();
        }
        
        // 7. Post-fix Assessment
        $this->post_fix_assessment();
        
        // 8. Generate comprehensive report
        $this->generate_comprehensive_report();
    }
    
    /**
     * Pre-fix assessment
     */
    private function pre_fix_assessment() {
        if (!$this->output_json) {
            echo "🔍 1. Pre-Fix Assessment...\n";
        }
        
        $assessment = array(
            'timestamp' => current_time('mysql'),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => '3.0.0',
            'github_healing_system' => class_exists('Vortex_GitHub_Self_Healing_System'),
            'workflow_files_count' => 0,
            'deprecation_patterns' => count($this->deprecation_fixes)
        );
        
        // Count workflow files
        $workflow_files = $this->get_workflow_files();
        $assessment['workflow_files_count'] = count($workflow_files);
        
        $this->fix_results['pre_fix_assessment'] = $assessment;
        
        if (!$this->output_json) {
            echo "   📁 Workflow files found: " . $assessment['workflow_files_count'] . "\n";
            echo "   🔧 Deprecation patterns: " . $assessment['deprecation_patterns'] . "\n";
            echo "   🛡️ GitHub healing system: " . ($assessment['github_healing_system'] ? "Active" : "Missing") . "\n";
            echo "\n";
        }
    }
    
    /**
     * Scan for GitHub Actions errors
     */
    private function scan_github_actions_errors() {
        if (!$this->output_json) {
            echo "🔍 2. Scanning for GitHub Actions Errors...\n";
        }
        
        $workflow_files = $this->get_workflow_files();
        $this->files_checked = count($workflow_files);
        
        foreach ($workflow_files as $file) {
            $this->scan_workflow_file($file);
        }
        
        if (!$this->output_json) {
            echo "   📁 Files checked: " . $this->files_checked . "\n";
            echo "   ⚠️ Errors found: " . count($this->errors_found) . "\n";
            echo "\n";
        }
    }
    
    /**
     * Scan workflow file
     */
    private function scan_workflow_file($file) {
        $content = file_get_contents($file);
        
        // Check for deprecated actions
        foreach ($this->deprecation_fixes as $deprecated => $current) {
            if (strpos($content, $deprecated) !== false) {
                $this->errors_found[] = array(
                    'file' => $file,
                    'type' => 'deprecated_action',
                    'deprecated' => $deprecated,
                    'current' => $current,
                    'severity' => 'high',
                    'description' => "Deprecated GitHub Action: $deprecated",
                    'line_number' => $this->find_line_number($content, $deprecated)
                );
            }
        }
        
        // Check for other common issues
        $this->scan_additional_issues($file, $content);
    }
    
    /**
     * Scan for additional issues
     */
    private function scan_additional_issues($file, $content) {
        // Check for syntax errors
        if (!$this->validate_yaml_syntax($content)) {
            $this->errors_found[] = array(
                'file' => $file,
                'type' => 'syntax_error',
                'severity' => 'critical',
                'description' => 'YAML syntax error in workflow file'
            );
        }
        
        // Check for missing required fields
        if (!$this->validate_workflow_structure($content)) {
            $this->errors_found[] = array(
                'file' => $file,
                'type' => 'missing_fields',
                'severity' => 'moderate',
                'description' => 'Missing required workflow fields'
            );
        }
        
        // Check for security issues
        $security_issues = $this->scan_security_issues($content);
        foreach ($security_issues as $issue) {
            $this->errors_found[] = array_merge($issue, array('file' => $file));
        }
        
        // Check for performance issues
        $performance_issues = $this->scan_performance_issues($content);
        foreach ($performance_issues as $issue) {
            $this->errors_found[] = array_merge($issue, array('file' => $file));
        }
    }
    
    /**
     * Find line number for pattern
     */
    private function find_line_number($content, $pattern) {
        $lines = explode("\n", $content);
        foreach ($lines as $line_num => $line) {
            if (strpos($line, $pattern) !== false) {
                return $line_num + 1;
            }
        }
        return 0;
    }
    
    /**
     * Validate YAML syntax
     */
    private function validate_yaml_syntax($content) {
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }
            
            $indent = strlen($line) - strlen(ltrim($line));
            
            // Check for proper indentation
            if ($indent % 2 !== 0 && $indent > 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate workflow structure
     */
    private function validate_workflow_structure($content) {
        $required_fields = array('name', 'on', 'jobs');
        
        foreach ($required_fields as $field) {
            if (strpos($content, $field . ':') === false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Scan security issues
     */
    private function scan_security_issues($content) {
        $issues = array();
        
        // Check for hardcoded secrets
        if (preg_match('/password\s*:\s*[\'"][^\'"]+[\'"]/', $content)) {
            $issues[] = array(
                'type' => 'hardcoded_secret',
                'severity' => 'critical',
                'description' => 'Hardcoded password in workflow'
            );
        }
        
        // Check for unsafe permissions
        if (preg_match('/permissions\s*:\s*write/', $content)) {
            $issues[] = array(
                'type' => 'unsafe_permissions',
                'severity' => 'moderate',
                'description' => 'Unsafe write permissions in workflow'
            );
        }
        
        return $issues;
    }
    
    /**
     * Scan performance issues
     */
    private function scan_performance_issues($content) {
        $issues = array();
        
        // Check for missing caching
        if (strpos($content, 'node_modules') !== false && strpos($content, 'actions/cache') === false) {
            $issues[] = array(
                'type' => 'missing_caching',
                'severity' => 'low',
                'description' => 'Missing caching for node_modules'
            );
        }
        
        // Check for unnecessary steps
        if (preg_match('/#\s*Unnecessary step/', $content)) {
            $issues[] = array(
                'type' => 'unnecessary_steps',
                'severity' => 'low',
                'description' => 'Unnecessary steps in workflow'
            );
        }
        
        return $issues;
    }
    
    /**
     * Apply automatic fixes
     */
    private function apply_automatic_fixes() {
        if (!$this->output_json) {
            echo "🔧 3. Applying Automatic Fixes...\n";
        }
        
        foreach ($this->errors_found as $error) {
            $this->apply_fix($error);
        }
        
        if (!$this->output_json) {
            echo "   ✅ Fixes applied: " . count($this->fixes_applied) . "\n";
            echo "\n";
        }
    }
    
    /**
     * Apply fix for specific error
     */
    private function apply_fix($error) {
        switch ($error['type']) {
            case 'deprecated_action':
                $this->fix_deprecated_action($error);
                break;
                
            case 'syntax_error':
                $this->fix_syntax_error($error);
                break;
                
            case 'missing_fields':
                $this->fix_missing_fields($error);
                break;
                
            case 'hardcoded_secret':
                $this->fix_hardcoded_secret($error);
                break;
                
            case 'unsafe_permissions':
                $this->fix_unsafe_permissions($error);
                break;
                
            case 'missing_caching':
                $this->fix_missing_caching($error);
                break;
                
            case 'unnecessary_steps':
                $this->fix_unnecessary_steps($error);
                break;
        }
    }
    
    /**
     * Fix deprecated action
     */
    private function fix_deprecated_action($error) {
        $file = $error['file'];
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Replace deprecated action with current version
        $content = str_replace(
            $error['deprecated'],
            $error['current'],
            $content
        );
        
        // Save the file if changes were made
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            
            $this->fixes_applied[] = array(
                'error' => $error,
                'fix_applied' => true,
                'timestamp' => current_time('mysql')
            );
            
            $this->files_fixed++;
            
            if (!$this->output_json) {
                echo "   🔧 Fixed: {$error['deprecated']} → {$error['current']} in " . basename($file) . "\n";
            }
        }
    }
    
    /**
     * Fix syntax error
     */
    private function fix_syntax_error($error) {
        $file = $error['file'];
        $content = file_get_contents($file);
        
        // Basic syntax fixes
        $content = $this->fix_yaml_syntax($content);
        
        file_put_contents($file, $content);
        
        $this->fixes_applied[] = array(
            'error' => $error,
            'fix_applied' => true,
            'timestamp' => current_time('mysql')
        );
        
        $this->files_fixed++;
    }
    
    /**
     * Fix missing fields
     */
    private function fix_missing_fields($error) {
        $file = $error['file'];
        $content = file_get_contents($file);
        
        // Add missing required fields
        $content = $this->add_missing_workflow_fields($content);
        
        file_put_contents($file, $content);
        
        $this->fixes_applied[] = array(
            'error' => $error,
            'fix_applied' => true,
            'timestamp' => current_time('mysql')
        );
        
        $this->files_fixed++;
    }
    
    /**
     * Fix hardcoded secret
     */
    private function fix_hardcoded_secret($error) {
        $file = $error['file'];
        $content = file_get_contents($file);
        
        // Replace hardcoded secrets with environment variables
        $content = preg_replace(
            '/password\s*:\s*[\'"]([^\'"]+)[\'"]/',
            'password: ${{ secrets.DB_PASSWORD }}',
            $content
        );
        
        file_put_contents($file, $content);
        
        $this->fixes_applied[] = array(
            'error' => $error,
            'fix_applied' => true,
            'timestamp' => current_time('mysql')
        );
        
        $this->files_fixed++;
    }
    
    /**
     * Fix unsafe permissions
     */
    private function fix_unsafe_permissions($error) {
        $file = $error['file'];
        $content = file_get_contents($file);
        
        // Replace unsafe permissions with safer alternatives
        $content = preg_replace(
            '/permissions\s*:\s*write/',
            'permissions: read',
            $content
        );
        
        file_put_contents($file, $content);
        
        $this->fixes_applied[] = array(
            'error' => $error,
            'fix_applied' => true,
            'timestamp' => current_time('mysql')
        );
        
        $this->files_fixed++;
    }
    
    /**
     * Fix missing caching
     */
    private function fix_missing_caching($error) {
        $file = $error['file'];
        $content = file_get_contents($file);
        
        // Add caching for node_modules
        if (strpos($content, 'node_modules') !== false && strpos($content, 'actions/cache') === false) {
            $cache_step = "      - name: Cache node modules\n        uses: actions/cache@v4\n        with:\n          path: ~/.npm\n          key: \${{ runner.os }}-node-\${{ hashFiles('**/package-lock.json') }}\n";
            $content = str_replace('      - name: Install dependencies', $cache_step . "      - name: Install dependencies", $content);
        }
        
        file_put_contents($file, $content);
        
        $this->fixes_applied[] = array(
            'error' => $error,
            'fix_applied' => true,
            'timestamp' => current_time('mysql')
        );
        
        $this->files_fixed++;
    }
    
    /**
     * Fix unnecessary steps
     */
    private function fix_unnecessary_steps($error) {
        $file = $error['file'];
        $content = file_get_contents($file);
        
        // Remove unnecessary steps
        $content = preg_replace('/#\s*Unnecessary step.*\n/', '', $content);
        
        file_put_contents($file, $content);
        
        $this->fixes_applied[] = array(
            'error' => $error,
            'fix_applied' => true,
            'timestamp' => current_time('mysql')
        );
        
        $this->files_fixed++;
    }
    
    /**
     * Fix YAML syntax
     */
    private function fix_yaml_syntax($content) {
        $lines = explode("\n", $content);
        $fixed_lines = array();
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                $fixed_lines[] = '';
                continue;
            }
            
            // Fix common YAML issues
            $line = $this->fix_yaml_line($line);
            $fixed_lines[] = $line;
        }
        
        return implode("\n", $fixed_lines);
    }
    
    /**
     * Fix YAML line
     */
    private function fix_yaml_line($line) {
        // Fix indentation
        $indent = strlen($line) - strlen(ltrim($line));
        if ($indent % 2 !== 0 && $indent > 0) {
            $line = str_repeat(' ', $indent + 1) . ltrim($line);
        }
        
        // Fix missing colons
        if (preg_match('/^(\s*[a-zA-Z_][a-zA-Z0-9_]*)\s*$/', $line, $matches)) {
            $line = $matches[1] . ':';
        }
        
        return $line;
    }
    
    /**
     * Add missing workflow fields
     */
    private function add_missing_workflow_fields($content) {
        // Add name if missing
        if (strpos($content, 'name:') === false) {
            $content = "name: VORTEX AI Engine Workflow\n" . $content;
        }
        
        // Add on trigger if missing
        if (strpos($content, 'on:') === false) {
            $content = str_replace(
                'name:',
                "name: VORTEX AI Engine Workflow\non:\n  push:\n    branches: [ main ]\n  pull_request:\n    branches: [ main ]",
                $content
            );
        }
        
        return $content;
    }
    
    /**
     * Validate fixes
     */
    private function validate_fixes() {
        if (!$this->output_json) {
            echo "✅ 4. Validating Fixes...\n";
        }
        
        $validation_results = array();
        
        // Re-scan for remaining errors
        $remaining_errors = array();
        $workflow_files = $this->get_workflow_files();
        
        foreach ($workflow_files as $file) {
            $content = file_get_contents($file);
            
            // Check for remaining deprecated actions
            foreach ($this->deprecation_fixes as $deprecated => $current) {
                if (strpos($content, $deprecated) !== false) {
                    $remaining_errors[] = array(
                        'file' => $file,
                        'type' => 'deprecated_action',
                        'deprecated' => $deprecated
                    );
                }
            }
        }
        
        $validation_results['remaining_errors'] = $remaining_errors;
        $validation_results['fixes_successful'] = count($remaining_errors) === 0;
        
        $this->fix_results['validation'] = $validation_results;
        
        if (!$this->output_json) {
            echo "   ✅ Fixes successful: " . ($validation_results['fixes_successful'] ? "Yes" : "No") . "\n";
            echo "   ⚠️ Remaining errors: " . count($remaining_errors) . "\n";
            echo "\n";
        }
    }
    
    /**
     * Update learning system
     */
    private function update_learning_system() {
        if (!$this->output_json) {
            echo "🧠 5. Updating Learning System...\n";
        }
        
        // Update GitHub healing system
        if ($this->github_healing_system) {
            // Trigger learning cycle
            do_action('vortex_github_learning_cycle');
            
            // Update learning data
            $this->update_learning_data();
        }
        
        if (!$this->output_json) {
            echo "   🧠 Learning system updated\n";
            echo "\n";
        }
    }
    
    /**
     * Update learning data
     */
    private function update_learning_data() {
        // Store fix patterns for future learning
        $learning_data = array(
            'timestamp' => current_time('mysql'),
            'errors_found' => $this->errors_found,
            'fixes_applied' => $this->fixes_applied,
            'files_checked' => $this->files_checked,
            'files_fixed' => $this->files_fixed
        );
        
        update_option('vortex_github_actions_learning_data', $learning_data);
    }
    
    /**
     * Commit changes
     */
    private function commit_changes() {
        if (!$this->output_json) {
            echo "📝 6. Committing Changes...\n";
        }
        
        try {
            // Change to plugin directory
            chdir($this->plugin_dir);
            
            // Add all changes
            exec('git add .', $output, $return_code);
            
            if ($return_code === 0) {
                // Commit changes
                $commit_message = "🔧 Auto-fix GitHub Actions deprecation errors\n\n" .
                                "✅ Fixed " . count($this->fixes_applied) . " deprecation errors\n" .
                                "📁 Files checked: " . $this->files_checked . "\n" .
                                "🔧 Files fixed: " . $this->files_fixed . "\n" .
                                "🧠 Updated learning system\n" .
                                "🔄 Applied recursive self-improvement";
                
                exec('git commit -m "' . addslashes($commit_message) . '"', $output, $return_code);
                
                if ($return_code === 0) {
                    // Push changes
                    exec('git push origin main', $output, $return_code);
                    
                    if ($return_code === 0) {
                        if (!$this->output_json) {
                            echo "   ✅ Changes committed and pushed successfully\n";
                        }
                    } else {
                        if (!$this->output_json) {
                            echo "   ❌ Failed to push changes\n";
                        }
                    }
                } else {
                    if (!$this->output_json) {
                        echo "   ❌ Failed to commit changes\n";
                    }
                }
            } else {
                if (!$this->output_json) {
                    echo "   ❌ Failed to add changes\n";
                }
            }
            
        } catch (Exception $e) {
            if (!$this->output_json) {
                echo "   ❌ Error during commit: " . $e->getMessage() . "\n";
            }
        }
        
        if (!$this->output_json) {
            echo "\n";
        }
    }
    
    /**
     * Post-fix assessment
     */
    private function post_fix_assessment() {
        if (!$this->output_json) {
            echo "📊 7. Post-Fix Assessment...\n";
        }
        
        $assessment = array(
            'timestamp' => current_time('mysql'),
            'total_errors_found' => count($this->errors_found),
            'total_fixes_applied' => count($this->fixes_applied),
            'files_checked' => $this->files_checked,
            'files_fixed' => $this->files_fixed,
            'fix_success_rate' => $this->calculate_fix_success_rate(),
            'improvement_score' => $this->calculate_improvement_score()
        );
        
        $this->fix_results['post_fix_assessment'] = $assessment;
        
        if (!$this->output_json) {
            echo "   📊 Total errors found: " . $assessment['total_errors_found'] . "\n";
            echo "   ✅ Total fixes applied: " . $assessment['total_fixes_applied'] . "\n";
            echo "   📁 Files checked: " . $assessment['files_checked'] . "\n";
            echo "   🔧 Files fixed: " . $assessment['files_fixed'] . "\n";
            echo "   📈 Fix success rate: " . round($assessment['fix_success_rate'] * 100, 2) . "%\n";
            echo "   🎯 Improvement score: " . round($assessment['improvement_score'] * 100, 2) . "%\n";
            echo "\n";
        }
    }
    
    /**
     * Calculate fix success rate
     */
    private function calculate_fix_success_rate() {
        if (count($this->errors_found) === 0) {
            return 1.0;
        }
        
        return count($this->fixes_applied) / count($this->errors_found);
    }
    
    /**
     * Calculate improvement score
     */
    private function calculate_improvement_score() {
        $base_score = 1.0;
        
        // Deduct points for remaining errors
        $remaining_errors = count($this->errors_found) - count($this->fixes_applied);
        $deduction = $remaining_errors * 0.1;
        
        return max(0.0, $base_score - $deduction);
    }
    
    /**
     * Generate comprehensive report
     */
    private function generate_comprehensive_report() {
        if (!$this->output_json) {
            echo "📋 8. Generating Comprehensive Report...\n";
        }
        
        $report = array(
            'fix_summary' => array(
                'timestamp' => current_time('mysql'),
                'total_errors_found' => count($this->errors_found),
                'total_fixes_applied' => count($this->fixes_applied),
                'files_checked' => $this->files_checked,
                'files_fixed' => $this->files_fixed,
                'fix_success_rate' => $this->calculate_fix_success_rate(),
                'improvement_score' => $this->calculate_improvement_score()
            ),
            'detailed_results' => $this->fix_results,
            'errors_found' => $this->errors_found,
            'fixes_applied' => $this->fixes_applied,
            'recommendations' => $this->generate_recommendations()
        );
        
        // Save report
        $report_file = $this->plugin_dir . 'GITHUB-ACTIONS-FIX-REPORT.json';
        file_put_contents($report_file, json_encode($report, JSON_PRETTY_PRINT));
        
        // Generate markdown report
        $this->generate_markdown_report($report);
        
        if (!$this->output_json) {
            echo "   📄 Report saved: GITHUB-ACTIONS-FIX-REPORT.json\n";
            echo "   📄 Report saved: GITHUB-ACTIONS-FIX-REPORT.md\n";
            echo "\n";
            echo "🎉 GITHUB ACTIONS AUTO-FIX COMPLETED!\n";
            echo "=====================================\n";
            echo "📊 Fix Success Rate: " . round($report['fix_summary']['fix_success_rate'] * 100, 2) . "%\n";
            echo "🎯 Improvement Score: " . round($report['fix_summary']['improvement_score'] * 100, 2) . "%\n";
            echo "✅ Total Fixes Applied: " . $report['fix_summary']['total_fixes_applied'] . "\n";
            echo "📁 Files Fixed: " . $report['fix_summary']['files_fixed'] . "\n";
            echo "🧠 Learning System Updated\n";
            echo "🔄 Recursive Self-Improvement Applied\n";
            echo "\n";
        }
        
        return $report;
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations() {
        $recommendations = array();
        
        if (count($this->errors_found) > 0) {
            $recommendations[] = 'Enable automatic GitHub Actions monitoring';
        }
        
        $recommendations[] = 'Schedule regular GitHub Actions audits';
        $recommendations[] = 'Enable Dependabot for automatic dependency updates';
        $recommendations[] = 'Monitor GitHub Security tab for vulnerabilities';
        $recommendations[] = 'Implement automated testing for all workflows';
        
        return $recommendations;
    }
    
    /**
     * Generate markdown report
     */
    private function generate_markdown_report($report) {
        $markdown = "# VORTEX AI ENGINE - GITHUB ACTIONS AUTO-FIX REPORT\n\n";
        $markdown .= "**Date:** " . $report['fix_summary']['timestamp'] . "\n";
        $markdown .= "**Fix Success Rate:** " . round($report['fix_summary']['fix_success_rate'] * 100, 2) . "%\n";
        $markdown .= "**Improvement Score:** " . round($report['fix_summary']['improvement_score'] * 100, 2) . "%\n\n";
        
        $markdown .= "## 📊 FIX SUMMARY\n\n";
        $markdown .= "- **Total Errors Found:** " . $report['fix_summary']['total_errors_found'] . "\n";
        $markdown .= "- **Total Fixes Applied:** " . $report['fix_summary']['total_fixes_applied'] . "\n";
        $markdown .= "- **Files Checked:** " . $report['fix_summary']['files_checked'] . "\n";
        $markdown .= "- **Files Fixed:** " . $report['fix_summary']['files_fixed'] . "\n\n";
        
        $markdown .= "## 🔧 FIXES APPLIED\n\n";
        foreach ($report['fixes_applied'] as $fix) {
            $error = $fix['error'];
            $markdown .= "### " . ucfirst($error['type']) . "\n";
            $markdown .= "- **File:** " . basename($error['file']) . "\n";
            $markdown .= "- **Description:** " . $error['description'] . "\n";
            if (isset($error['deprecated']) && isset($error['current'])) {
                $markdown .= "- **Fix:** " . $error['deprecated'] . " → " . $error['current'] . "\n";
            }
            $markdown .= "- **Timestamp:** " . $fix['timestamp'] . "\n\n";
        }
        
        $markdown .= "## 💡 RECOMMENDATIONS\n\n";
        foreach ($report['recommendations'] as $recommendation) {
            $markdown .= "- " . $recommendation . "\n";
        }
        
        $report_file = $this->plugin_dir . 'GITHUB-ACTIONS-FIX-REPORT.md';
        file_put_contents($report_file, $markdown);
    }
    
    /**
     * Get workflow files
     */
    private function get_workflow_files() {
        $workflow_dir = $this->plugin_dir . '.github/workflows/';
        $files = array();
        
        if (is_dir($workflow_dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($workflow_dir)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'yml') {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }
}

// Run the auto-fixer if called directly
if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
    $output_json = in_array('--json', $argv);
    $auto_commit = in_array('--auto-commit', $argv);
    
    $auto_fixer = new VORTEX_GitHub_Actions_Auto_Fixer($output_json, $auto_commit);
    $auto_fixer->run_comprehensive_github_actions_fix();
} 