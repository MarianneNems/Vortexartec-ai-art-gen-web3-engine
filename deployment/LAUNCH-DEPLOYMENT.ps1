# Vortex AI Engine - Master Deployment Launcher
# 
# Orchestrates the complete staging → production deployment pipeline
# This script guides you through the entire deployment process
# 
# Usage: .\LAUNCH-DEPLOYMENT.ps1

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("staging", "production")]
    [string]$Target = "staging",
    
    [Parameter(Mandatory=$false)]
    [bool]$SkipPrompts = $false
)

# Configuration
$Config = @{
    Staging = @{
        Name = "Staging Environment"
        SiteUrl = "https://staging.yoursite.com"
        PluginPath = "C:\inetpub\wwwroot\staging\wp-content\plugins\vortex-ai-engine"
        DatabaseName = "staging_db"
    }
    Production = @{
        Name = "Production Environment"
        SiteUrl = "https://yoursite.com"
        PluginPath = "C:\inetpub\wwwroot\wp-content\plugins\vortex-ai-engine"
        DatabaseName = "production_db"
    }
}

# Colors for output
$Colors = @{
    Success = "Green"
    Warning = "Yellow"
    Error = "Red"
    Info = "Cyan"
    Header = "Magenta"
}

# Logging function
function Write-ColorLog {
    param([string]$Message, [string]$Color = "White", [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    Write-Host $logMessage -ForegroundColor $Colors[$Color]
    Add-Content -Path "deployment-launch.log" -Value $logMessage
}

# User confirmation function
function Confirm-Action {
    param([string]$Message, [string]$Default = "N")
    
    if ($SkipPrompts) {
        Write-ColorLog "Auto-confirming: $Message" "Info"
        return $true
    }
    
    $response = Read-Host "$Message (Y/N) [$Default]"
    if ([string]::IsNullOrEmpty($response)) {
        $response = $Default
    }
    
    return $response.ToUpper() -eq "Y"
}

# Header display
function Show-Header {
    Clear-Host
    Write-Host "🚀 VORTEX AI ENGINE - DEPLOYMENT LAUNCHER" -ForegroundColor $Colors.Header
    Write-Host "=============================================" -ForegroundColor $Colors.Header
    Write-Host ""
    Write-Host "Target Environment: $($Config[$Target].Name)" -ForegroundColor $Colors.Info
    Write-Host "Site URL: $($Config[$Target].SiteUrl)" -ForegroundColor $Colors.Info
    Write-Host "Plugin Path: $($Config[$Target].PluginPath)" -ForegroundColor $Colors.Info
    Write-Host ""
}

# Environment verification
function Invoke-EnvironmentVerification {
    Write-ColorLog "Step 1: Environment Verification" "Header"
    Write-ColorLog "Running environment verification script..." "Info"
    
    try {
        $result = php deployment/verify-environment.php
        if ($LASTEXITCODE -eq 0) {
            Write-ColorLog "✅ Environment verification PASSED!" "Success"
            return $true
        } else {
            Write-ColorLog "❌ Environment verification FAILED!" "Error"
            Write-ColorLog "Please fix the issues above before proceeding." "Error"
            return $false
        }
    }
    catch {
        Write-ColorLog "❌ Environment verification script failed: $($_.Exception.Message)" "Error"
        return $false
    }
}

# Pre-deployment checks
function Invoke-PreDeploymentChecks {
    Write-ColorLog "Step 2: Pre-Deployment Checks" "Header"
    
    # Check if we're in the right directory
    if (!(Test-Path "vortex-ai-engine.php")) {
        Write-ColorLog "❌ Not in plugin directory. Please run from vortex-ai-engine folder." "Error"
        return $false
    }
    
    # Check if git is clean
    $gitStatus = git status --porcelain
    if ($gitStatus) {
        Write-ColorLog "⚠️  Git repository has uncommitted changes:" "Warning"
        Write-Host $gitStatus
        if (!(Confirm-Action "Continue with uncommitted changes?")) {
            return $false
        }
    } else {
        Write-ColorLog "✅ Git repository is clean" "Success"
    }
    
    # Check if all required files exist
    $requiredFiles = @(
        "deployment/verify-environment.php",
        "deployment/smoke-test.php",
        "deployment/deploy-to-production.ps1",
        "includes/class-vortex-health-check.php",
        "includes/class-vortex-agreement-policy.php"
    )
    
    foreach ($file in $requiredFiles) {
        if (!(Test-Path $file)) {
            Write-ColorLog "❌ Missing required file: $file" "Error"
            return $false
        }
    }
    
    Write-ColorLog "✅ All required files present" "Success"
    return $true
}

# Staging deployment
function Invoke-StagingDeployment {
    Write-ColorLog "Step 3: Staging Deployment" "Header"
    
    Write-ColorLog "Deploying to staging environment..." "Info"
    
    # Create staging package
    Write-ColorLog "Creating staging package..." "Info"
    $stagingPackage = "vortex-ai-engine-staging.zip"
    
    if (Test-Path $stagingPackage) {
        Remove-Item $stagingPackage -Force
    }
    
    # Exclude development files
    $excludeFiles = @(".git", ".github", "scripts", "tests", "vendor", "composer.*", "*.log")
    $excludeParams = $excludeFiles | ForEach-Object { "-x", "*$_*" }
    
    Compress-Archive -Path * -DestinationPath $stagingPackage -Force
    Write-ColorLog "✅ Staging package created: $stagingPackage" "Success"
    
    # Upload instructions
    Write-ColorLog "📤 Manual Upload Required:" "Warning"
    Write-Host "1. Upload $stagingPackage to your staging server"
    Write-Host "2. Extract to: $($Config.Staging.PluginPath)"
    Write-Host "3. Activate the plugin in WordPress Admin"
    Write-Host "4. Configure staging environment variables"
    Write-Host ""
    
    if (Confirm-Action "Have you uploaded and activated the plugin on staging?") {
        # Run smoke tests on staging
        Write-ColorLog "Running smoke tests on staging..." "Info"
        try {
            $smokeTestUrl = "$($Config.Staging.SiteUrl)/wp-json/vortex/v1/health-check"
            $response = Invoke-WebRequest -Uri $smokeTestUrl -Method GET -TimeoutSec 30
            
            if ($response.StatusCode -eq 200) {
                Write-ColorLog "✅ Staging health check passed" "Success"
            } else {
                Write-ColorLog "⚠️  Staging health check returned status: $($response.StatusCode)" "Warning"
            }
        }
        catch {
            Write-ColorLog "⚠️  Could not reach staging health check: $($_.Exception.Message)" "Warning"
        }
    }
    
    return $true
}

# Production deployment
function Invoke-ProductionDeployment {
    Write-ColorLog "Step 4: Production Deployment" "Header"
    
    Write-ColorLog "⚠️  PRODUCTION DEPLOYMENT WARNING" "Warning"
    Write-Host "This will deploy to your LIVE production environment."
    Write-Host "Make sure you have:"
    Write-Host "- Backed up your production database"
    Write-Host "- Tested thoroughly on staging"
    Write-Host "- Scheduled maintenance window"
    Write-Host ""
    
    if (!(Confirm-Action "Are you ready to deploy to PRODUCTION?" "N")) {
        Write-ColorLog "Production deployment cancelled by user." "Warning"
        return $false
    }
    
    # Run production deployment script
    Write-ColorLog "Executing production deployment..." "Info"
    try {
        & .\deployment\deploy-to-production.ps1 -Environment "production" -BackupDatabase $true -RunSmokeTests $true
        if ($LASTEXITCODE -eq 0) {
            Write-ColorLog "✅ Production deployment completed successfully!" "Success"
            return $true
        } else {
            Write-ColorLog "❌ Production deployment failed!" "Error"
            return $false
        }
    }
    catch {
        Write-ColorLog "❌ Production deployment script failed: $($_.Exception.Message)" "Error"
        return $false
    }
}

# Post-deployment verification
function Invoke-PostDeploymentVerification {
    Write-ColorLog "Step 5: Post-Deployment Verification" "Header"
    
    $siteUrl = $Config[$Target].SiteUrl
    
    Write-ColorLog "Running post-deployment verification..." "Info"
    
    # Health check
    try {
        $healthUrl = "$siteUrl/wp-json/vortex/v1/health-check"
        $response = Invoke-WebRequest -Uri $healthUrl -Method GET -TimeoutSec 30
        
        if ($response.StatusCode -eq 200) {
            $healthData = $response.Content | ConvertFrom-Json
            Write-ColorLog "✅ Health check passed - Status: $($healthData.status)" "Success"
        } else {
            Write-ColorLog "⚠️  Health check returned status: $($response.StatusCode)" "Warning"
        }
    }
    catch {
        Write-ColorLog "⚠️  Health check failed: $($_.Exception.Message)" "Warning"
    }
    
    # Test key endpoints
    $endpoints = @(
        @{ Name = "Feedback"; Url = "$siteUrl/wp-json/vortex/v1/feedback" },
        @{ Name = "Generate"; Url = "$siteUrl/wp-json/vortex/v1/generate" },
        @{ Name = "Wallet"; Url = "$siteUrl/wp-json/vortex/v1/wallet" }
    )
    
    foreach ($endpoint in $endpoints) {
        try {
            $response = Invoke-WebRequest -Uri $endpoint.Url -Method GET -TimeoutSec 10
            if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 401) {
                Write-ColorLog "✅ $($endpoint.Name) endpoint: OK" "Success"
            } else {
                Write-ColorLog "⚠️  $($endpoint.Name) endpoint: Status $($response.StatusCode)" "Warning"
            }
        }
        catch {
            Write-ColorLog "⚠️  $($endpoint.Name) endpoint: Failed" "Warning"
        }
    }
    
    Write-ColorLog "Post-deployment verification completed" "Info"
}

# Main deployment orchestration
function Start-DeploymentOrchestration {
    Show-Header
    
    Write-ColorLog "Starting Vortex AI Engine deployment to $($Config[$Target].Name)..." "Info"
    Write-Host ""
    
    # Step 1: Environment Verification
    if (!(Invoke-EnvironmentVerification)) {
        Write-ColorLog "❌ Deployment aborted due to environment issues." "Error"
        return $false
    }
    
    # Step 2: Pre-Deployment Checks
    if (!(Invoke-PreDeploymentChecks)) {
        Write-ColorLog "❌ Deployment aborted due to pre-deployment issues." "Error"
        return $false
    }
    
    # Step 3: Environment-Specific Deployment
    if ($Target -eq "staging") {
        if (!(Invoke-StagingDeployment)) {
            Write-ColorLog "❌ Staging deployment failed." "Error"
            return $false
        }
    } else {
        if (!(Invoke-ProductionDeployment)) {
            Write-ColorLog "❌ Production deployment failed." "Error"
            return $false
        }
    }
    
    # Step 4: Post-Deployment Verification
    Invoke-PostDeploymentVerification
    
    # Success message
    Write-Host ""
    Write-ColorLog "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!" "Success"
    Write-Host ""
    Write-ColorLog "Next Steps:" "Info"
    Write-Host "1. Monitor the health dashboard: $($Config[$Target].SiteUrl)/wp-admin/admin.php?page=vortex-health-check"
    Write-Host "2. Test all shortcodes and features"
    Write-Host "3. Monitor error logs for 24 hours"
    Write-Host "4. Gather user feedback"
    Write-Host ""
    
    if ($Target -eq "staging") {
        Write-ColorLog "Ready for production deployment when staging testing is complete." "Info"
    } else {
        Write-ColorLog "Production deployment complete. Monitor closely for the next 24 hours." "Warning"
    }
    
    return $true
}

# Execute deployment
try {
    $success = Start-DeploymentOrchestration
    
    if ($success) {
        Write-ColorLog "Deployment orchestration completed successfully!" "Success"
        exit 0
    } else {
        Write-ColorLog "Deployment orchestration failed!" "Error"
        exit 1
    }
}
catch {
    Write-ColorLog "Deployment orchestration failed with exception: $($_.Exception.Message)" "Error"
    exit 1
}
finally {
    Write-ColorLog "Deployment launcher completed at $(Get-Date)" "Info"
} 