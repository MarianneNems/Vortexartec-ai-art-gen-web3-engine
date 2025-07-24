# Vortex AI Engine - Plesk Upload Preparation Script
# 
# Prepares the plugin for upload via Plesk File Manager
# Creates a clean package ready for extraction
# 
# Usage: .\prepare-plesk-upload.ps1

param(
    [Parameter(Mandatory=$false)]
    [string]$OutputPath = ".\vortex-ai-engine-plesk.zip"
)

# Configuration
$PluginSource = ".\vortex-ai-engine"
$ExcludePatterns = @(
    ".git",
    ".github", 
    "scripts",
    "tests",
    "vendor",
    "composer.*",
    "*.log",
    "*.md",
    "deployment",
    "audit-system"
)

Write-Host "🚀 VORTEX AI ENGINE - PLESK UPLOAD PREPARATION" -ForegroundColor Magenta
Write-Host "===============================================" -ForegroundColor Magenta
Write-Host ""

# Check if source directory exists
if (!(Test-Path $PluginSource)) {
    Write-Host "❌ Plugin source directory not found: $PluginSource" -ForegroundColor Red
    Write-Host "Please run this script from the vortex-ai-engine directory" -ForegroundColor Red
    exit 1
}

Write-Host "📁 Preparing plugin for Plesk upload..." -ForegroundColor Cyan

# Create temporary directory for clean package
$TempDir = ".\temp-plesk-upload"
if (Test-Path $TempDir) {
    Remove-Item $TempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $TempDir | Out-Null

# Copy plugin files, excluding development files
Write-Host "📋 Copying plugin files..." -ForegroundColor Yellow

$excludeParams = $ExcludePatterns | ForEach-Object { "-Exclude", $_ }
Copy-Item -Path "$PluginSource\*" -Destination $TempDir -Recurse -Force @excludeParams

# Verify essential files are present
$essentialFiles = @(
    "vortex-ai-engine.php",
    "includes\class-vortex-agreement-policy.php",
    "includes\class-vortex-health-check.php",
    "assets\js\agreement.js",
    "assets\css\agreement.css"
)

Write-Host "🔍 Verifying essential files..." -ForegroundColor Yellow

foreach ($file in $essentialFiles) {
    $filePath = Join-Path $TempDir $file
    if (Test-Path $filePath) {
        Write-Host "✅ $file" -ForegroundColor Green
    } else {
        Write-Host "❌ Missing: $file" -ForegroundColor Red
        Write-Host "Upload preparation failed!" -ForegroundColor Red
        Remove-Item $TempDir -Recurse -Force
        exit 1
    }
}

# Create ZIP file
Write-Host "📦 Creating ZIP package..." -ForegroundColor Yellow

if (Test-Path $OutputPath) {
    Remove-Item $OutputPath -Force
}

Compress-Archive -Path "$TempDir\*" -DestinationPath $OutputPath -Force

# Verify ZIP file
if (Test-Path $OutputPath) {
    $zipSize = (Get-Item $OutputPath).Length
    $zipSizeMB = [math]::Round($zipSize / 1MB, 2)
    
    Write-Host "✅ ZIP package created successfully!" -ForegroundColor Green
    Write-Host "📁 File: $OutputPath" -ForegroundColor Cyan
    Write-Host "📊 Size: $zipSizeMB MB" -ForegroundColor Cyan
} else {
    Write-Host "❌ Failed to create ZIP package!" -ForegroundColor Red
    Remove-Item $TempDir -Recurse -Force
    exit 1
}

# Clean up temporary directory
Remove-Item $TempDir -Recurse -Force

# Display upload instructions
Write-Host ""
Write-Host "📋 PLESK UPLOAD INSTRUCTIONS" -ForegroundColor Magenta
Write-Host "============================" -ForegroundColor Magenta
Write-Host ""
Write-Host "1. Login to Plesk Control Panel" -ForegroundColor White
Write-Host "2. Go to File Manager" -ForegroundColor White
Write-Host "3. Navigate to: /httpdocs/wp-content/plugins/" -ForegroundColor White
Write-Host "4. Upload: $OutputPath" -ForegroundColor Cyan
Write-Host "5. Extract the ZIP file" -ForegroundColor White
Write-Host "6. Set permissions: Directories=755, Files=644" -ForegroundColor White
Write-Host "7. Activate plugin in WordPress Admin" -ForegroundColor White
Write-Host ""
Write-Host "📖 For detailed instructions, see: PLESK-UPLOAD-GUIDE.md" -ForegroundColor Yellow
Write-Host ""

# Check file size for upload limits
if ($zipSizeMB -gt 50) {
    Write-Host "⚠️  WARNING: ZIP file is large ($zipSizeMB MB)" -ForegroundColor Yellow
    Write-Host "   You may need to increase upload limits in Plesk:" -ForegroundColor Yellow
    Write-Host "   - Go to Plesk → Tools & Settings → PHP Settings" -ForegroundColor White
    Write-Host "   - Increase upload_max_filesize and post_max_size" -ForegroundColor White
    Write-Host ""
}

Write-Host "🎉 Upload preparation completed successfully!" -ForegroundColor Green
Write-Host "   Ready for Plesk File Manager upload!" -ForegroundColor Green 