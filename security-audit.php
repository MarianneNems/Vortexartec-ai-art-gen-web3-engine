<?php
/**
 * VORTEX AI Engine - Security Audit Script
 * This script identifies sensitive data, API keys, and private algorithms
 */

echo "🔒 VORTEX AI Engine - Security Audit\n";
echo "===================================\n\n";

$sensitive_patterns = [
    // API Keys and Credentials
    '/(api_key|api_secret|access_token|private_key|secret_key|password|credential)/i',
    '/(aws_access_key|aws_secret_key|aws_region)/i',
    '/(openai_api_key|anthropic_api_key|google_api_key)/i',
    '/(database_password|db_password|mysql_password)/i',
    '/(vault_token|vault_secret|encryption_key)/i',
    
    // Private Algorithms
    '/(private_algorithm|secret_algorithm|proprietary)/i',
    '/(ai_model|neural_network|machine_learning)/i',
    '/(blockchain_private|smart_contract_private)/i',
    
    // Configuration Files
    '/(config\.php|\.env|secrets\.json|keys\.json)/i',
    '/(vault-secrets|private_keys|secure_config)/i',
    
    // Database and Storage
    '/(database_url|connection_string|storage_key)/i',
    '/(s3_bucket|s3_key|cloud_storage)/i',
    
    // Authentication
    '/(jwt_secret|session_secret|auth_token)/i',
    '/(oauth_client_id|oauth_client_secret)/i'
];

$sensitive_files = [];
$sensitive_content = [];
$recommendations = [];

// Scan for sensitive files
echo "📁 Scanning for sensitive files...\n";
$directories = ['vault-secrets', 'includes', 'admin', 'config', 'private_seed_zodiac_module'];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $filename = $file->getPathname();
                $content = file_get_contents($filename);
                
                // Check for sensitive patterns
                foreach ($sensitive_patterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $sensitive_files[] = $filename;
                        $sensitive_content[$filename] = $content;
                        break;
                    }
                }
            }
        }
    }
}

// Display findings
echo "\n🔍 Security Audit Results:\n";
echo "==========================\n\n";

if (empty($sensitive_files)) {
    echo "✅ No obvious sensitive files found\n";
} else {
    echo "⚠️ Sensitive files detected:\n";
    foreach ($sensitive_files as $file) {
        echo "   - $file\n";
    }
}

// Check specific sensitive directories
echo "\n📋 Directory Analysis:\n";
echo "=====================\n\n";

$sensitive_dirs = [
    'vault-secrets' => 'AI algorithms and secrets',
    'private_seed_zodiac_module' => 'Private zodiac algorithms',
    'config' => 'Configuration files',
    'includes/admin' => 'Admin authentication',
    'vault-secrets/credentials' => 'API credentials',
    'vault-secrets/keys' => 'Encryption keys'
];

foreach ($sensitive_dirs as $dir => $description) {
    if (is_dir($dir)) {
        $file_count = count(glob("$dir/*"));
        echo "⚠️ $dir ($description): $file_count files\n";
        $recommendations[] = "Move $dir to private branch";
    }
}

// Check for API keys in files
echo "\n🔑 API Key Analysis:\n";
echo "===================\n\n";

$api_key_files = [];
foreach ($sensitive_content as $file => $content) {
    if (preg_match('/(api_key|access_token|private_key)/i', $content)) {
        $api_key_files[] = $file;
    }
}

if (!empty($api_key_files)) {
    echo "🚨 API keys found in:\n";
    foreach ($api_key_files as $file) {
        echo "   - $file\n";
    }
    $recommendations[] = "Remove API keys from public files";
}

// Check .gitignore
echo "\n📝 .gitignore Analysis:\n";
echo "======================\n\n";

if (file_exists('.gitignore')) {
    $gitignore = file_get_contents('.gitignore');
    $protected_patterns = [
        '*.key', '*.pem', '*.crt', 'secrets/', 'keys/', '*.env', 'config.php'
    ];
    
    $missing_protections = [];
    foreach ($protected_patterns as $pattern) {
        if (strpos($gitignore, $pattern) === false) {
            $missing_protections[] = $pattern;
        }
    }
    
    if (empty($missing_protections)) {
        echo "✅ .gitignore properly configured\n";
    } else {
        echo "⚠️ Missing protections in .gitignore:\n";
        foreach ($missing_protections as $pattern) {
            echo "   - $pattern\n";
        }
        $recommendations[] = "Add missing patterns to .gitignore";
    }
} else {
    echo "❌ .gitignore not found\n";
    $recommendations[] = "Create .gitignore file";
}

// Security recommendations
echo "\n🛡️ Security Recommendations:\n";
echo "============================\n\n";

if (empty($recommendations)) {
    echo "✅ No immediate security issues found\n";
} else {
    foreach ($recommendations as $i => $rec) {
        echo ($i + 1) . ". $rec\n";
    }
}

// Create secure deployment checklist
echo "\n📋 Secure Deployment Checklist:\n";
echo "===============================\n\n";

$checklist = [
    "Move sensitive algorithms to private branch",
    "Remove API keys from public files",
    "Use environment variables for credentials",
    "Implement proper authentication",
    "Add rate limiting",
    "Enable HTTPS only",
    "Implement input validation",
    "Add security headers",
    "Use prepared statements for database",
    "Implement proper error handling",
    "Add logging for security events",
    "Regular security updates"
];

foreach ($checklist as $i => $item) {
    echo ($i + 1) . ". $item\n";
}

echo "\n🔒 Next Steps:\n";
echo "==============\n";
echo "1. Create private branch for sensitive data\n";
echo "2. Move vault-secrets to private branch\n";
echo "3. Remove API keys from public files\n";
echo "4. Implement environment-based configuration\n";
echo "5. Test security measures\n";
echo "6. Deploy to WordPress\n\n";

?> 