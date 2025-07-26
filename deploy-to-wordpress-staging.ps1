# VORTEX AI ENGINE - WORDPRESS STAGING DEPLOYMENT
# Deploys the plugin to WordPress staging environment

Write-Host "🚀 VORTEX AI ENGINE - WORDPRESS STAGING DEPLOYMENT" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Staging environment configuration
$STAGING_URL = "https://wordpress-1205138-5700514.cloudwaysapps.com"
$STAGING_ADMIN_URL = "$STAGING_URL/wp-admin/"
$PLUGIN_DIR = "vortex-ai-engine"
$DEPLOYMENT_PACKAGE = "vortex-ai-engine-staging.zip"

Write-Host "[INFO] Staging Environment:" -ForegroundColor Blue
Write-Host "   URL: $STAGING_URL" -ForegroundColor White
Write-Host "   Admin: $STAGING_ADMIN_URL" -ForegroundColor White
Write-Host "   Plugin: $PLUGIN_DIR" -ForegroundColor White
Write-Host ""

# Step 1: Verify plugin structure
Write-Host "[INFO] Step 1: Verifying plugin structure..." -ForegroundColor Blue

$REQUIRED_FILES = @(
    "vortex-ai-engine.php",
    "composer.json",
    "composer.lock",
    "vendor/",
    "includes/",
    "admin/",
    "public/",
    "marketplace/"
)

foreach ($file in $REQUIRED_FILES) {
    if (Test-Path $file) {
        Write-Host "[SUCCESS] ✓ Found $file" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] ✗ Missing $file" -ForegroundColor Red
        exit 1
    }
}

# Step 2: Create deployment package
Write-Host "[INFO] Step 2: Creating deployment package..." -ForegroundColor Blue

try {
    # Remove existing package
    if (Test-Path $DEPLOYMENT_PACKAGE) {
        Remove-Item $DEPLOYMENT_PACKAGE -Force
    }
    
    # Create ZIP package
    Compress-Archive -Path $PLUGIN_DIR -DestinationPath $DEPLOYMENT_PACKAGE -Force
    Write-Host "[SUCCESS] ✓ Created $DEPLOYMENT_PACKAGE" -ForegroundColor Green
    
    # Get package size
    $packageSize = (Get-Item $DEPLOYMENT_PACKAGE).Length / 1MB
    Write-Host "[INFO] Package size: $([math]::Round($packageSize, 2)) MB" -ForegroundColor White
} catch {
    Write-Host "[ERROR] Failed to create deployment package: $_" -ForegroundColor Red
    exit 1
}

# Step 3: Test WordPress connectivity
Write-Host "[INFO] Step 3: Testing WordPress connectivity..." -ForegroundColor Blue

try {
    $response = Invoke-WebRequest -Uri $STAGING_URL -Method GET -TimeoutSec 30
    if ($response.StatusCode -eq 200) {
        Write-Host "[SUCCESS] ✓ WordPress staging site is accessible" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] ⚠ WordPress returned status: $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ERROR] Cannot connect to WordPress staging: $_" -ForegroundColor Red
    Write-Host "[INFO] Please verify the staging URL is correct" -ForegroundColor Yellow
}

# Step 4: Create deployment instructions
Write-Host "[INFO] Step 4: Creating deployment instructions..." -ForegroundColor Blue

$instructions = @"
# VORTEX AI ENGINE - WORDPRESS STAGING DEPLOYMENT INSTRUCTIONS

## 🎯 DEPLOYMENT PACKAGE READY
Package: $DEPLOYMENT_PACKAGE
Size: $([math]::Round($packageSize, 2)) MB
Created: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## 📋 MANUAL DEPLOYMENT STEPS

### Step 1: Access WordPress Admin
1. Go to: $STAGING_ADMIN_URL
2. Login with your admin credentials

### Step 2: Upload Plugin
1. Navigate to: Plugins → Add New → Upload Plugin
2. Click "Choose File" and select: $DEPLOYMENT_PACKAGE
3. Click "Install Now"
4. Wait for installation to complete

### Step 3: Activate Plugin
1. Click "Activate Plugin" after installation
2. Verify plugin appears in the plugins list
3. Check for any activation errors

### Step 4: Configure Plugin
1. Go to: Vortex AI Engine → Settings
2. Configure API keys and settings
3. Test basic functionality

### Step 5: Verify Installation
1. Check plugin status in admin dashboard
2. Test marketplace functionality
3. Verify AI agents are working
4. Check for any error logs

## 🔧 TROUBLESHOOTING

### If Installation Fails:
- Check WordPress version compatibility (requires 5.0+)
- Verify PHP version (requires 8.1+)
- Check server memory limits
- Review error logs

### If Plugin Won't Activate:
- Check for plugin conflicts
- Verify file permissions
- Review PHP error logs
- Test with default theme

### If Features Don't Work:
- Verify API keys are configured
- Check network connectivity
- Review browser console for errors
- Test with different browsers

## 📞 SUPPORT

- Technical Support: support@vortexartec.com
- GitHub Issues: https://github.com/mariannenems/vortexartec-ai-marketplace/issues
- Documentation: https://www.vortexartec.com/docs

---

**Deployment package ready for WordPress staging installation!**
"@

$instructions | Out-File -FilePath "WORDPRESS-STAGING-DEPLOYMENT-INSTRUCTIONS.md" -Encoding UTF8
Write-Host "[SUCCESS] ✓ Created deployment instructions" -ForegroundColor Green

# Step 5: Create quick deployment script
Write-Host "[INFO] Step 5: Creating quick deployment script..." -ForegroundColor Blue

$quickDeploy = @"
# Quick WordPress Staging Deployment
Write-Host "Opening WordPress staging admin..." -ForegroundColor Green
Start-Process "$STAGING_ADMIN_URL"

Write-Host "Opening deployment package location..." -ForegroundColor Green
Start-Process (Get-Location)

Write-Host ""
Write-Host "🎯 NEXT STEPS:" -ForegroundColor Cyan
Write-Host "1. Upload $DEPLOYMENT_PACKAGE to WordPress" -ForegroundColor White
Write-Host "2. Activate the Vortex AI Engine plugin" -ForegroundColor White
Write-Host "3. Configure settings and API keys" -ForegroundColor White
Write-Host "4. Test functionality" -ForegroundColor White
Write-Host ""
"@

$quickDeploy | Out-File -FilePath "quick-deploy-staging.ps1" -Encoding UTF8
Write-Host "[SUCCESS] ✓ Created quick deployment script" -ForegroundColor Green

# Final status
Write-Host ""
Write-Host "🎉 WORDPRESS STAGING DEPLOYMENT READY!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "✅ Plugin package created: $DEPLOYMENT_PACKAGE" -ForegroundColor Green
Write-Host "✅ Deployment instructions: WORDPRESS-STAGING-DEPLOYMENT-INSTRUCTIONS.md" -ForegroundColor Green
Write-Host "✅ Quick deployment script: quick-deploy-staging.ps1" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 WordPress Staging:" -ForegroundColor Cyan
Write-Host "   URL: $STAGING_URL" -ForegroundColor White
Write-Host "   Admin: $STAGING_ADMIN_URL" -ForegroundColor White
Write-Host ""
Write-Host "📦 Deployment Package:" -ForegroundColor Cyan
Write-Host "   File: $DEPLOYMENT_PACKAGE" -ForegroundColor White
Write-Host "   Size: $([math]::Round($packageSize, 2)) MB" -ForegroundColor White
Write-Host "   Location: $(Get-Location)" -ForegroundColor White
Write-Host ""
Write-Host "🚀 Ready to deploy to WordPress staging!" -ForegroundColor Green
Write-Host "   Run: .\quick-deploy-staging.ps1" -ForegroundColor White
Write-Host "" 