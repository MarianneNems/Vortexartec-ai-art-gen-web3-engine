<?php
/**
 * Simple test to check if algorithm files can be loaded
 */

echo "Testing VORTEX AI Engine algorithm files...\n";

// Define constants
define('VORTEX_AI_ENGINE_PLUGIN_DIR', __DIR__ . '/');

// Test loading algorithm files
$algorithm_files = [
    'vault-secrets/algorithms/class-vortex-shortcodes.php',
    'vault-secrets/algorithms/class-vortex-agreements.php',
    'vault-secrets/algorithms/individual_agent_algorithms.php',
    'vault-secrets/algorithms/base_ai_orchestrator.php',
    'vault-secrets/algorithms/class-vortex-security.php',
    'vault-secrets/algorithms/vault_integration.php',
    'vault-secrets/algorithms/class-vortex-tier-manager.php',
];

foreach ($algorithm_files as $file) {
    $path = VORTEX_AI_ENGINE_PLUGIN_DIR . $file;
    
    if (file_exists($path)) {
        echo "✓ File exists: $file\n";
        
        // Check syntax
        $syntax_check = shell_exec("php -l \"$path\" 2>&1");
        if (strpos($syntax_check, 'No syntax errors') !== false) {
            echo "  ✓ Syntax OK\n";
        } else {
            echo "  ✗ Syntax error: $syntax_check\n";
        }
        
        // Try to include the file
        try {
            ob_start();
            include_once $path;
            $output = ob_get_clean();
            
            if ($output) {
                echo "  ⚠ Output: $output\n";
            } else {
                echo "  ✓ Loaded successfully\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error loading: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✗ File missing: $file\n";
    }
}

echo "\nTest completed!\n"; 