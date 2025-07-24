# Vortex AI Engine - Create Deployment Package
# This script creates a clean deployment package with all required files

Write-Host "Creating Vortex AI Engine deployment package..." -ForegroundColor Green

# Create clean deployment directory
$deploymentDir = "clean-deployment\vortex-ai-engine"
if (Test-Path $deploymentDir) {
    Remove-Item $deploymentDir -Recurse -Force
}
New-Item -ItemType Directory -Path $deploymentDir -Force | Out-Null

# Create subdirectories
$subdirs = @("includes", "admin", "public", "languages", "deployment", "assets")
foreach ($dir in $subdirs) {
    New-Item -ItemType Directory -Path "$deploymentDir\$dir" -Force | Out-Null
}

# Copy main plugin file
Copy-Item "wp-content\plugins\vortex-ai-engine\vortex-ai-engine.php" "$deploymentDir\" -Force

# Copy includes files
$includesFiles = @(
    "class-vortex-loader.php",
    "class-vortex-incentive-auditor.php", 
    "class-vortex-wallet-manager.php",
    "class-vortex-accounting-system.php",
    "class-vortex-conversion-system.php",
    "class-vortex-integration-layer.php",
    "class-vortex-frontend-interface.php",
    "class-vortex-activation.php"
)

foreach ($file in $includesFiles) {
    $source = "wp-content\plugins\vortex-ai-engine\includes\$file"
    $dest = "$deploymentDir\includes\$file"
    if (Test-Path $source) {
        Copy-Item $source $dest -Force
        Write-Host "Copied: $file" -ForegroundColor Yellow
    } else {
        Write-Host "Missing: $file" -ForegroundColor Red
    }
}

# Copy admin files
$adminFiles = @("class-vortex-admin.php")
foreach ($file in $adminFiles) {
    $source = "wp-content\plugins\vortex-ai-engine\admin\$file"
    $dest = "$deploymentDir\admin\$file"
    if (Test-Path $source) {
        Copy-Item $source $dest -Force
        Write-Host "Copied: $file" -ForegroundColor Yellow
    } else {
        Write-Host "Missing: $file" -ForegroundColor Red
    }
}

# Copy public files
$publicFiles = @("class-vortex-public.php")
foreach ($file in $publicFiles) {
    $source = "wp-content\plugins\vortex-ai-engine\public\$file"
    $dest = "$deploymentDir\public\$file"
    if (Test-Path $source) {
        Copy-Item $source $dest -Force
        Write-Host "Copied: $file" -ForegroundColor Yellow
    } else {
        Write-Host "Missing: $file" -ForegroundColor Red
    }
}

# Copy language files
$langFiles = @("vortex-ai-engine.pot")
foreach ($file in $langFiles) {
    $source = "wp-content\plugins\vortex-ai-engine\languages\$file"
    $dest = "$deploymentDir\languages\$file"
    if (Test-Path $source) {
        Copy-Item $source $dest -Force
        Write-Host "Copied: $file" -ForegroundColor Yellow
    } else {
        Write-Host "Missing: $file" -ForegroundColor Red
    }
}

# Copy deployment files
$deploymentFiles = @("production-deployment.php", "final-verification.php")
foreach ($file in $deploymentFiles) {
    $source = "deployment\$file"
    $dest = "$deploymentDir\deployment\$file"
    if (Test-Path $source) {
        Copy-Item $source $dest -Force
        Write-Host "Copied: $file" -ForegroundColor Yellow
    } else {
        Write-Host "Missing: $file" -ForegroundColor Red
    }
}

# Copy assets (if they exist)
if (Test-Path "wp-content\plugins\vortex-ai-engine\assets") {
    Copy-Item "wp-content\plugins\vortex-ai-engine\assets\*" "$deploymentDir\assets\" -Recurse -Force
    Write-Host "Copied: assets directory" -ForegroundColor Yellow
}

# Create deployment package
$packageName = "vortex-ai-engine-production-deploy.zip"
if (Test-Path $packageName) {
    Remove-Item $packageName -Force
}

Compress-Archive -Path "clean-deployment\vortex-ai-engine\*" -DestinationPath $packageName -Force

Write-Host "`nDeployment package created: $packageName" -ForegroundColor Green

# Verify package
Write-Host "`nVerifying package contents..." -ForegroundColor Cyan
Expand-Archive -Path $packageName -DestinationPath "verify-package" -Force

$fileCount = (Get-ChildItem "verify-package" -Recurse | Measure-Object).Count
Write-Host "Package contains $fileCount files" -ForegroundColor Green

Write-Host "`nDeployment package ready for production!" -ForegroundColor Green 