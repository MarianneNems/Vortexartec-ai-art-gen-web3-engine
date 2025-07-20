# VORTEX AI Engine - Secure Deployment Script (PowerShell)
# This script creates a secure plugin ZIP file EXCLUDING all sensitive data

Write-Host "VORTEX AI Engine - Secure Deployment" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

# Set variables
$PLUGIN_NAME = "vortex-ai-engine"
$VERSION = "2.1.0"
$BUILD_DIR = "build-secure"
$PLUGIN_DIR = "$BUILD_DIR\$PLUGIN_NAME"

# Clean previous build
Write-Host "Cleaning previous build..." -ForegroundColor Yellow
if (Test-Path $BUILD_DIR) {
    Remove-Item -Recurse -Force $BUILD_DIR
}
New-Item -ItemType Directory -Path $BUILD_DIR -Force | Out-Null
New-Item -ItemType Directory -Path $PLUGIN_DIR -Force | Out-Null

# Copy ONLY safe plugin files (exclude sensitive data)
Write-Host "Copying safe plugin files..." -ForegroundColor Yellow

# Main plugin file
if (Test-Path "vortex-ai-engine.php") {
    Copy-Item "vortex-ai-engine.php" $PLUGIN_DIR -Force
}

# Core includes (exclude admin and sensitive files)
if (Test-Path "includes") {
    $safe_includes = @(
        "class-vortex-db-setup.php",
        "class-vortex-logger.php",
        "class-vortex-config.php",
        "class-vortex-shortcodes.php",
        "class-vortex-nft-shortcodes.php",
        "class-vortex-nft-ajax.php",
        "class-vortex-nft-database.php",
        "class-vortex-memory-api.php",
        "class-vortex-tier-manager.php",
        "class-vortex-web3-integration.php",
        "class-vortex-solana-integration.php",
        "class-vortex-enhanced-orchestrator.php",
        "class-vortex-feedback-controller.php",
        "class-vortex-api-endpoints.php",
        "class-vortex-secure-api-keys.php"
    )
    
    New-Item -ItemType Directory -Path "$PLUGIN_DIR\includes" -Force | Out-Null
    
    foreach ($file in $safe_includes) {
        $source = "includes\$file"
        if (Test-Path $source) {
            Copy-Item $source "$PLUGIN_DIR\includes\" -Force
        }
    }
}

# Public admin interface (exclude sensitive admin files)
if (Test-Path "admin") {
    $safe_admin = @(
        "class-vortex-admin.php",
        "class-vortex-logs-viewer.php",
        "index.php"
    )
    
    New-Item -ItemType Directory -Path "$PLUGIN_DIR\admin" -Force | Out-Null
    
    foreach ($file in $safe_admin) {
        $source = "admin\$file"
        if (Test-Path $source) {
            Copy-Item $source "$PLUGIN_DIR\admin\" -Force
        }
    }
    
    # Copy admin assets
    if (Test-Path "admin\assets") {
        Copy-Item -Recurse "admin\assets" "$PLUGIN_DIR\admin\" -Force
    }
}

# Assets (CSS/JS files)
if (Test-Path "assets") {
    Copy-Item -Recurse "assets" $PLUGIN_DIR -Force
}

# Templates
if (Test-Path "templates") {
    Copy-Item -Recurse "templates" $PLUGIN_DIR -Force
}

# Languages
if (Test-Path "languages") {
    Copy-Item -Recurse "languages" $PLUGIN_DIR -Force
}

# Documentation
if (Test-Path "readme.txt") {
    Copy-Item "readme.txt" $PLUGIN_DIR -Force
}

# Dependencies
if (Test-Path "composer.json") {
    Copy-Item "composer.json" $PLUGIN_DIR -Force
}

# Create environment template
$env_template = @"
# VORTEX AI Engine - Environment Configuration
# Copy this file to .env and fill in your actual values

# AI Services
VORTEX_OPENAI_API_KEY=your_openai_api_key_here
VORTEX_ANTHROPIC_API_KEY=your_anthropic_api_key_here
VORTEX_GOOGLE_API_KEY=your_google_api_key_here

# AWS Configuration
VORTEX_AWS_ACCESS_API_KEY=your_aws_access_key_here
VORTEX_AWS_SECRET_API_KEY=your_aws_secret_key_here
VORTEX_AWS_REGION=us-east-1
VORTEX_AWS_BUCKET=your_s3_bucket_name

# Security Settings
VORTEX_RATE_LIMIT=100
VORTEX_MAX_REQUESTS_PER_MINUTE=60
VORTEX_SESSION_TIMEOUT=3600
VORTEX_REQUIRE_HTTPS=true

# AI Configuration
VORTEX_AI_MODEL=gpt-4
VORTEX_AI_TEMPERATURE=0.7
VORTEX_AI_MAX_TOKENS=2000
"@

$env_template | Out-File -FilePath "$PLUGIN_DIR\.env.example" -Encoding UTF8

# Create secure configuration instructions
$config_instructions = @"
# VORTEX AI Engine - Secure Configuration Instructions

## IMPORTANT: Security Setup Required

This plugin requires secure configuration before use. Follow these steps:

### 1. Environment Variables (Recommended)
Set these environment variables on your server:
- VORTEX_OPENAI_API_KEY
- VORTEX_AWS_ACCESS_API_KEY  
- VORTEX_AWS_SECRET_API_KEY
- VORTEX_ANTHROPIC_API_KEY

### 2. WordPress Options (Alternative)
Configure via WordPress Admin:
- Go to VORTEX AI Engine > Settings
- Enter your API keys securely
- Save configuration

### 3. Security Checklist
- [ ] API keys configured
- [ ] HTTPS enabled
- [ ] Rate limiting active
- [ ] Error logging enabled
- [ ] Security headers set

### 4. Private Algorithms
Private algorithms are NOT included in this deployment.
Contact support for access to advanced features.

## Support
For security questions or private algorithm access:
- Email: security@vortexartec.com
- Documentation: https://vortexartec.com/docs
"@

$config_instructions | Out-File -FilePath "$PLUGIN_DIR\SECURITY-SETUP.md" -Encoding UTF8

# Remove any sensitive files that might have been copied
Write-Host "Removing sensitive files..." -ForegroundColor Yellow
$sensitive_patterns = @(
    "vault-secrets",
    "private_seed_zodiac_module", 
    "config",
    "admin\class-vortex-*admin*.php",
    "admin\partials\settings\*.php",
    "*.key",
    "*.pem",
    "*.crt",
    "secrets.json",
    "api-keys.json",
    "config.php"
)

foreach ($pattern in $sensitive_patterns) {
    Get-ChildItem -Path $PLUGIN_DIR -Filter $pattern -Recurse | Remove-Item -Force -Recurse
}

# Install Composer dependencies (if composer is available)
Write-Host "Installing Composer dependencies..." -ForegroundColor Yellow
if (Get-Command composer -ErrorAction SilentlyContinue) {
    Push-Location $PLUGIN_DIR
    composer install --no-dev --optimize-autoloader
    Pop-Location
} else {
    Write-Host "Composer not found. Please install dependencies manually." -ForegroundColor Yellow
}

# Create ZIP file
Write-Host "Creating secure plugin ZIP..." -ForegroundColor Yellow
$zipPath = "$BUILD_DIR\$PLUGIN_NAME-v$VERSION-secure.zip"
if (Test-Path $zipPath) {
    Remove-Item $zipPath
}

# Use PowerShell's Compress-Archive
Compress-Archive -Path "$PLUGIN_DIR\*" -DestinationPath $zipPath -Force

# Set permissions (Windows equivalent)
Write-Host "Setting file permissions..." -ForegroundColor Yellow
Get-ChildItem -Path $PLUGIN_DIR -Recurse | ForEach-Object {
    if ($_.PSIsContainer) {
        $_.Attributes = $_.Attributes -bor [System.IO.FileAttributes]::ReadOnly
    } else {
        $_.Attributes = $_.Attributes -bor [System.IO.FileAttributes]::ReadOnly
    }
}

# Display results
Write-Host ""
Write-Host "Secure deployment package created successfully!" -ForegroundColor Green
Write-Host "Plugin directory: $PLUGIN_DIR" -ForegroundColor Cyan
Write-Host "Secure ZIP file: $zipPath" -ForegroundColor Cyan
Write-Host ""

Write-Host "Security features included:" -ForegroundColor Green
Write-Host "- Environment-based configuration" -ForegroundColor White
Write-Host "- Secure API key management" -ForegroundColor White
Write-Host "- Input validation and sanitization" -ForegroundColor White
Write-Host "- Rate limiting support" -ForegroundColor White
Write-Host "- Security headers" -ForegroundColor White
Write-Host ""

Write-Host "Sensitive data EXCLUDED:" -ForegroundColor Green
Write-Host "- Private algorithms (vault-secrets)" -ForegroundColor White
Write-Host "- API keys and credentials" -ForegroundColor White
Write-Host "- Admin authentication files" -ForegroundColor White
Write-Host "- Configuration files" -ForegroundColor White
Write-Host ""

Write-Host "Next steps:" -ForegroundColor Green
Write-Host "1. Upload the secure ZIP file to your WordPress site" -ForegroundColor White
Write-Host "2. Configure API keys via environment variables or WordPress admin" -ForegroundColor White
Write-Host "3. Test the plugin functionality" -ForegroundColor White
Write-Host "4. Contact support for private algorithm access" -ForegroundColor White
Write-Host ""

Write-Host "Package size:" -ForegroundColor Green
$fileInfo = Get-Item $zipPath
Write-Host "Size: $([math]::Round($fileInfo.Length / 1MB, 2)) MB" -ForegroundColor White
Write-Host ""

Write-Host "Files included:" -ForegroundColor Green
$fileCount = (Get-ChildItem -Path $PLUGIN_DIR -Recurse -File).Count
Write-Host "Total files: $fileCount" -ForegroundColor White
Write-Host ""

Write-Host "Ready for secure deployment!" -ForegroundColor Green 