# VORTEX AI ENGINE - SIMPLE STAGING DEPLOYMENT

Write-Host "🚀 VORTEX AI ENGINE - WORDPRESS STAGING DEPLOYMENT" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$STAGING_URL = "https://wordpress-1205138-5700514.cloudwaysapps.com"
$STAGING_ADMIN_URL = "$STAGING_URL/wp-admin/"
$DEPLOYMENT_PACKAGE = "vortex-ai-engine-staging.zip"

Write-Host "[INFO] Staging Environment:" -ForegroundColor Blue
Write-Host "   URL: $STAGING_URL" -ForegroundColor White
Write-Host "   Admin: $STAGING_ADMIN_URL" -ForegroundColor White
Write-Host ""

# Step 1: Verify plugin files
Write-Host "[INFO] Step 1: Verifying plugin files..." -ForegroundColor Blue

if (Test-Path "vortex-ai-engine.php") {
    Write-Host "[SUCCESS] ✓ Main plugin file found" -ForegroundColor Green
} else {
    Write-Host "[ERROR] ✗ Main plugin file missing" -ForegroundColor Red
    exit 1
}

if (Test-Path "vendor") {
    Write-Host "[SUCCESS] ✓ Vendor directory found" -ForegroundColor Green
} else {
    Write-Host "[ERROR] ✗ Vendor directory missing" -ForegroundColor Red
    exit 1
}

if (Test-Path "includes") {
    Write-Host "[SUCCESS] ✓ Includes directory found" -ForegroundColor Green
} else {
    Write-Host "[ERROR] ✗ Includes directory missing" -ForegroundColor Red
    exit 1
}

# Step 2: Create deployment package
Write-Host "[INFO] Step 2: Creating deployment package..." -ForegroundColor Blue

try {
    if (Test-Path $DEPLOYMENT_PACKAGE) {
        Remove-Item $DEPLOYMENT_PACKAGE -Force
    }
    
    Compress-Archive -Path "vortex-ai-engine" -DestinationPath $DEPLOYMENT_PACKAGE -Force
    Write-Host "[SUCCESS] ✓ Created $DEPLOYMENT_PACKAGE" -ForegroundColor Green
    
    $packageSize = (Get-Item $DEPLOYMENT_PACKAGE).Length / 1MB
    Write-Host "[INFO] Package size: $([math]::Round($packageSize, 2)) MB" -ForegroundColor White
} catch {
    Write-Host "[ERROR] Failed to create package: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Step 3: Create instructions file
Write-Host "[INFO] Step 3: Creating deployment instructions..." -ForegroundColor Blue

$instructions = @"
VORTEX AI ENGINE - WORDPRESS STAGING DEPLOYMENT

DEPLOYMENT PACKAGE: $DEPLOYMENT_PACKAGE
SIZE: $([math]::Round($packageSize, 2)) MB
CREATED: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

WORDPRESS STAGING URL: $STAGING_URL
ADMIN URL: $STAGING_ADMIN_URL

DEPLOYMENT STEPS:
1. Go to $STAGING_ADMIN_URL
2. Login with admin credentials
3. Go to Plugins > Add New > Upload Plugin
4. Click "Choose File" and select $DEPLOYMENT_PACKAGE
5. Click "Install Now"
6. Click "Activate Plugin"
7. Go to Vortex AI Engine > Settings to configure

TROUBLESHOOTING:
- If installation fails, check WordPress version (requires 5.0+)
- If activation fails, check PHP version (requires 8.1+)
- Check error logs for specific issues
- Verify file permissions on server

SUPPORT: support@vortexartec.com
"@

$instructions | Out-File -FilePath "STAGING-DEPLOYMENT-INSTRUCTIONS.txt" -Encoding UTF8
Write-Host "[SUCCESS] ✓ Created deployment instructions" -ForegroundColor Green

# Step 4: Open deployment resources
Write-Host "[INFO] Step 4: Opening deployment resources..." -ForegroundColor Blue

try {
    Start-Process $STAGING_ADMIN_URL
    Write-Host "[SUCCESS] ✓ Opened WordPress admin" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Could not open WordPress admin automatically" -ForegroundColor Yellow
}

try {
    Start-Process (Get-Location)
    Write-Host "[SUCCESS] ✓ Opened package location" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Could not open package location automatically" -ForegroundColor Yellow
}

# Final status
Write-Host ""
Write-Host "🎉 DEPLOYMENT READY!" -ForegroundColor Green
Write-Host "===================" -ForegroundColor Green
Write-Host ""
Write-Host "✅ Package: $DEPLOYMENT_PACKAGE" -ForegroundColor Green
Write-Host "✅ Size: $([math]::Round($packageSize, 2)) MB" -ForegroundColor Green
Write-Host "✅ Instructions: STAGING-DEPLOYMENT-INSTRUCTIONS.txt" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 WordPress Staging: $STAGING_URL" -ForegroundColor Cyan
Write-Host "🔧 Admin Panel: $STAGING_ADMIN_URL" -ForegroundColor Cyan
Write-Host ""
Write-Host "🚀 Ready to deploy to WordPress staging!" -ForegroundColor Green
Write-Host "" 