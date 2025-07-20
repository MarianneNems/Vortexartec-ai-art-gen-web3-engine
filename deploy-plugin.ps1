# VORTEX AI Engine - Plugin Deployment Script (PowerShell)
# This script creates a clean plugin ZIP file for WordPress deployment

Write-Host "VORTEX AI Engine - Plugin Deployment" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Green

# Set variables
$PLUGIN_NAME = "vortex-ai-engine"
$VERSION = "2.1.0"
$BUILD_DIR = "build"
$PLUGIN_DIR = "$BUILD_DIR\$PLUGIN_NAME"

# Clean previous build
Write-Host "Cleaning previous build..." -ForegroundColor Yellow
if (Test-Path $BUILD_DIR) {
    Remove-Item -Recurse -Force $BUILD_DIR
}
New-Item -ItemType Directory -Path $BUILD_DIR -Force | Out-Null
New-Item -ItemType Directory -Path $PLUGIN_DIR -Force | Out-Null

# Copy essential plugin files
Write-Host "Copying plugin files..." -ForegroundColor Yellow
if (Test-Path "vortex-ai-engine") {
    Copy-Item -Recurse "vortex-ai-engine\*" $PLUGIN_DIR -Force
}
if (Test-Path "admin") {
    if (Test-Path "$PLUGIN_DIR\admin") {
        Remove-Item -Recurse -Force "$PLUGIN_DIR\admin"
    }
    Copy-Item -Recurse "admin" $PLUGIN_DIR
}
if (Test-Path "includes") {
    if (Test-Path "$PLUGIN_DIR\includes") {
        Remove-Item -Recurse -Force "$PLUGIN_DIR\includes"
    }
    Copy-Item -Recurse "includes" $PLUGIN_DIR
}
if (Test-Path "vault-secrets") {
    if (Test-Path "$PLUGIN_DIR\vault-secrets") {
        Remove-Item -Recurse -Force "$PLUGIN_DIR\vault-secrets"
    }
    Copy-Item -Recurse "vault-secrets" $PLUGIN_DIR
}
if (Test-Path "assets") {
    if (Test-Path "$PLUGIN_DIR\assets") {
        Remove-Item -Recurse -Force "$PLUGIN_DIR\assets"
    }
    Copy-Item -Recurse "assets" $PLUGIN_DIR
}
if (Test-Path "templates") {
    if (Test-Path "$PLUGIN_DIR\templates") {
        Remove-Item -Recurse -Force "$PLUGIN_DIR\templates"
    }
    Copy-Item -Recurse "templates" $PLUGIN_DIR
}
if (Test-Path "languages") {
    if (Test-Path "$PLUGIN_DIR\languages") {
        Remove-Item -Recurse -Force "$PLUGIN_DIR\languages"
    }
    Copy-Item -Recurse "languages" $PLUGIN_DIR
}
if (Test-Path "composer.json") {
    Copy-Item "composer.json" $PLUGIN_DIR -Force
}
if (Test-Path "readme.txt") {
    Copy-Item "readme.txt" $PLUGIN_DIR -Force
}

# Remove unnecessary files
Write-Host "Removing unnecessary files..." -ForegroundColor Yellow
$unnecessaryDirs = @("vendor", "tests", "backup-local-files", "infra", "solana-program", "contracts", "blockchain")
foreach ($dir in $unnecessaryDirs) {
    $path = "$PLUGIN_DIR\$dir"
    if (Test-Path $path) {
        Remove-Item -Recurse -Force $path
    }
}

# Remove unnecessary file types
$unnecessaryExtensions = @("*.log", "*.zip", "*.md", "*.sh", "*.ps1", "*.py", "*.js", "*.json", "*.html", "*.css")
foreach ($ext in $unnecessaryExtensions) {
    Get-ChildItem -Path $PLUGIN_DIR -Filter $ext -Recurse | Remove-Item -Force
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
Write-Host "Creating plugin ZIP..." -ForegroundColor Yellow
$zipPath = "$BUILD_DIR\$PLUGIN_NAME-v$VERSION.zip"
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
Write-Host "Deployment package created successfully!" -ForegroundColor Green
Write-Host "Plugin directory: $PLUGIN_DIR" -ForegroundColor Cyan
Write-Host "ZIP file: $zipPath" -ForegroundColor Cyan
Write-Host ""

Write-Host "Next steps:" -ForegroundColor Green
Write-Host "1. Upload the ZIP file to your WordPress site" -ForegroundColor White
Write-Host "2. Go to Plugins > Add New > Upload Plugin" -ForegroundColor White
Write-Host "3. Select the ZIP file and install" -ForegroundColor White
Write-Host "4. Activate the plugin" -ForegroundColor White
Write-Host ""

Write-Host "Package size:" -ForegroundColor Green
$fileInfo = Get-Item $zipPath
Write-Host "Size: $([math]::Round($fileInfo.Length / 1MB, 2)) MB" -ForegroundColor White
Write-Host ""

Write-Host "Files included:" -ForegroundColor Green
$fileCount = (Get-ChildItem -Path $PLUGIN_DIR -Recurse -File).Count
Write-Host "Total files: $fileCount" -ForegroundColor White
Write-Host ""

Write-Host "Ready for deployment!" -ForegroundColor Green 