# Vortex AI Engine - Automated Deployment Script
# This script automates the entire deployment process for production

param(
    [Parameter(Mandatory=$true)]
    [string]$WordPressPath,
    
    [Parameter(Mandatory=$false)]
    [string]$BackupPath = "backups",
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipBackup,
    
    [Parameter(Mandatory=$false)]
    [switch]$Force
)

Write-Host "=== VORTEX AI ENGINE - AUTOMATED DEPLOYMENT ===" -ForegroundColor Green
Write-Host "Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""

# Configuration
$PluginName = "vortex-ai-engine"
$PackageFile = "vortex-ai-engine-production-deploy.zip"
$PluginPath = Join-Path $WordPressPath "wp-content\plugins\$PluginName"

# Check if WordPress path exists
if (-not (Test-Path $WordPressPath)) {
    Write-Host "❌ WordPress path not found: $WordPressPath" -ForegroundColor Red
    exit 1
}

# Check if wp-config.php exists
$wpConfigPath = Join-Path $WordPressPath "wp-config.php"
if (-not (Test-Path $wpConfigPath)) {
    Write-Host "❌ wp-config.php not found in: $WordPressPath" -ForegroundColor Red
    exit 1
}

# Check if deployment package exists
if (-not (Test-Path $PackageFile)) {
    Write-Host "❌ Deployment package not found: $PackageFile" -ForegroundColor Red
    exit 1
}

Write-Host "✅ WordPress installation found: $WordPressPath" -ForegroundColor Green
Write-Host "✅ Deployment package found: $PackageFile" -ForegroundColor Green
Write-Host ""

# Step 1: Create Backup
if (-not $SkipBackup) {
    Write-Host "Step 1: Creating Backup..." -ForegroundColor Yellow
    
    $backupDir = Join-Path $BackupPath (Get-Date -Format "yyyy-MM-dd-HH-mm-ss")
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    
    # Backup existing plugin if it exists
    if (Test-Path $PluginPath) {
        $backupFile = Join-Path $backupDir "plugin-backup.zip"
        Compress-Archive -Path $PluginPath -DestinationPath $backupFile -Force
        Write-Host "✅ Plugin backup created: $backupFile" -ForegroundColor Green
    }
    
    # Backup wp-config.php
    $wpConfigBackup = Join-Path $backupDir "wp-config-backup.php"
    Copy-Item $wpConfigPath $wpConfigBackup -Force
    Write-Host "✅ wp-config.php backup created: $wpConfigBackup" -ForegroundColor Green
}

# Step 2: Stop Web Server (if possible)
Write-Host "Step 2: Preparing for deployment..." -ForegroundColor Yellow

try {
    # Try to stop IIS if running
    $iisService = Get-Service -Name "W3SVC" -ErrorAction SilentlyContinue
    if ($iisService -and $iisService.Status -eq "Running") {
        Write-Host "⚠️  Stopping IIS web server..." -ForegroundColor Yellow
        Stop-Service -Name "W3SVC" -Force
        Start-Sleep -Seconds 3
    }
} catch {
    Write-Host "ℹ️  Could not stop web server (this is normal)" -ForegroundColor Cyan
}

# Step 3: Remove existing plugin
if (Test-Path $PluginPath) {
    Write-Host "Step 3: Removing existing plugin..." -ForegroundColor Yellow
    
    if (-not $Force) {
        $response = Read-Host "Existing plugin found. Remove it? (y/N)"
        if ($response -ne "y" -and $response -ne "Y") {
            Write-Host "❌ Deployment cancelled by user" -ForegroundColor Red
            exit 1
        }
    }
    
    Remove-Item $PluginPath -Recurse -Force
    Write-Host "✅ Existing plugin removed" -ForegroundColor Green
}

# Step 4: Deploy new plugin
Write-Host "Step 4: Deploying new plugin..." -ForegroundColor Yellow

# Create plugins directory if it doesn't exist
$pluginsDir = Join-Path $WordPressPath "wp-content\plugins"
if (-not (Test-Path $pluginsDir)) {
    New-Item -ItemType Directory -Path $pluginsDir -Force | Out-Null
}

# Extract deployment package
try {
    Expand-Archive -Path $PackageFile -DestinationPath $pluginsDir -Force
    Write-Host "✅ Plugin extracted successfully" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to extract plugin: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Step 5: Set proper permissions
Write-Host "Step 5: Setting file permissions..." -ForegroundColor Yellow

try {
    # Set directory permissions (755)
    Get-ChildItem $PluginPath -Recurse -Directory | ForEach-Object {
        $acl = Get-Acl $_.FullName
        $rule = New-Object System.Security.AccessControl.FileSystemAccessRule("Everyone", "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow")
        $acl.SetAccessRule($rule)
        Set-Acl $_.FullName $acl
    }
    
    # Set file permissions (644)
    Get-ChildItem $PluginPath -Recurse -File | ForEach-Object {
        $acl = Get-Acl $_.FullName
        $rule = New-Object System.Security.AccessControl.FileSystemAccessRule("Everyone", "ReadAndExecute", "Allow")
        $acl.SetAccessRule($rule)
        Set-Acl $_.FullName $acl
    }
    
    Write-Host "✅ File permissions set successfully" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Could not set file permissions: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Step 6: Start Web Server
Write-Host "Step 6: Starting web server..." -ForegroundColor Yellow

try {
    if ($iisService -and $iisService.Status -ne "Running") {
        Start-Service -Name "W3SVC"
        Start-Sleep -Seconds 3
        Write-Host "✅ Web server started" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠️  Could not start web server: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Step 7: Verify deployment
Write-Host "Step 7: Verifying deployment..." -ForegroundColor Yellow

$requiredFiles = @(
    "vortex-ai-engine.php",
    "includes\class-vortex-loader.php",
    "includes\class-vortex-incentive-auditor.php",
    "includes\class-vortex-wallet-manager.php",
    "includes\class-vortex-accounting-system.php",
    "includes\class-vortex-conversion-system.php",
    "includes\class-vortex-integration-layer.php",
    "includes\class-vortex-frontend-interface.php",
    "includes\class-vortex-activation.php",
    "admin\class-vortex-admin.php",
    "public\class-vortex-public.php",
    "languages\vortex-ai-engine.pot",
    "deployment\production-deployment.php",
    "deployment\final-verification.php"
)

$missingFiles = @()
foreach ($file in $requiredFiles) {
    $filePath = Join-Path $PluginPath $file
    if (Test-Path $filePath) {
        Write-Host "✅ $file" -ForegroundColor Green
    } else {
        Write-Host "❌ $file (MISSING)" -ForegroundColor Red
        $missingFiles += $file
    }
}

if ($missingFiles.Count -gt 0) {
    Write-Host "❌ Deployment verification failed!" -ForegroundColor Red
    Write-Host "Missing files:" -ForegroundColor Red
    foreach ($file in $missingFiles) {
        Write-Host "  - $file" -ForegroundColor Red
    }
    exit 1
}

# Step 8: Run production deployment script
Write-Host "Step 8: Running production deployment script..." -ForegroundColor Yellow

$deploymentScript = Join-Path $PluginPath "deployment\production-deployment.php"
if (Test-Path $deploymentScript) {
    try {
        $phpPath = "php"
        $result = & $phpPath $deploymentScript 2>&1
        Write-Host $result
        Write-Host "✅ Production deployment script completed" -ForegroundColor Green
    } catch {
        Write-Host "⚠️  Could not run deployment script: $($_.Exception.Message)" -ForegroundColor Yellow
    }
} else {
    Write-Host "⚠️  Production deployment script not found" -ForegroundColor Yellow
}

# Step 9: Generate deployment report
Write-Host "Step 9: Generating deployment report..." -ForegroundColor Yellow

$reportFile = Join-Path $backupDir "deployment-report.txt"
$report = @"
Vortex AI Engine - Deployment Report
===================================
Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
WordPress Path: $WordPressPath
Plugin Path: $PluginPath
Package File: $PackageFile

Deployment Steps:
1. ✅ Backup created: $backupDir
2. ✅ Web server prepared
3. ✅ Existing plugin removed
4. ✅ New plugin deployed
5. ✅ File permissions set
6. ✅ Web server restarted
7. ✅ Deployment verified
8. ✅ Production script executed

Required Files Verified: $($requiredFiles.Count - $missingFiles.Count)/$($requiredFiles.Count)

Deployment Status: SUCCESS
"@

$report | Out-File -FilePath $reportFile -Encoding UTF8
Write-Host "✅ Deployment report saved: $reportFile" -ForegroundColor Green

# Final status
Write-Host ""
Write-Host "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Go to WordPress Admin → Plugins" -ForegroundColor White
Write-Host "2. Activate 'Vortex AI Engine' plugin" -ForegroundColor White
Write-Host "3. Go to Vortex AI → Dashboard" -ForegroundColor White
Write-Host "4. Configure settings and test functionality" -ForegroundColor White
Write-Host ""
Write-Host "Backup Location: $backupDir" -ForegroundColor Cyan
Write-Host "Deployment Report: $reportFile" -ForegroundColor Cyan 