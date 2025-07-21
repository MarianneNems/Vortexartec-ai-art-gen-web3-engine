# VORTEX AI Engine - Production Deployment Script
# 
# This script automates the deployment of VORTEX AI Engine to production
# Includes safety checks, backup creation, and rollback capabilities

param(
    [Parameter(Mandatory=$true)]
    [string]$Environment = "staging", # staging or production
    
    [Parameter(Mandatory=$false)]
    [string]$BackupPath = "C:\backups\vortex",
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipBackup,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipTests,
    
    [Parameter(Mandatory=$false)]
    [switch]$Force
)

# Configuration
$Config = @{
    Staging = @{
        WordPressPath = "C:\xampp\htdocs\staging"
        DatabaseName = "vortex_staging"
        BackupPath = "$BackupPath\staging"
    }
    Production = @{
        WordPressPath = "C:\xampp\htdocs\production"
        DatabaseName = "vortex_production"
        BackupPath = "$BackupPath\production"
    }
}

# Colors for output
$Colors = @{
    Success = "Green"
    Error = "Red"
    Warning = "Yellow"
    Info = "Cyan"
}

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Write-Success {
    param([string]$Message)
    Write-ColorOutput "✅ $Message" $Colors.Success
}

function Write-Error {
    param([string]$Message)
    Write-ColorOutput "❌ $Message" $Colors.Error
}

function Write-Warning {
    param([string]$Message)
    Write-ColorOutput "⚠️ $Message" $Colors.Warning
}

function Write-Info {
    param([string]$Message)
    Write-ColorOutput "ℹ️ $Message" $Colors.Info
}

function Test-Prerequisites {
    Write-Info "Checking prerequisites..."
    
    # Check if running as administrator
    $isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
    if (-not $isAdmin) {
        Write-Error "This script must be run as Administrator"
        exit 1
    }
    
    # Check WordPress path
    $wpPath = $Config[$Environment].WordPressPath
    if (-not (Test-Path $wpPath)) {
        Write-Error "WordPress path not found: $wpPath"
        exit 1
    }
    
    # Check if wp-config.php exists
    $wpConfig = Join-Path $wpPath "wp-config.php"
    if (-not (Test-Path $wpConfig)) {
        Write-Error "wp-config.php not found in: $wpPath"
        exit 1
    }
    
    # Check if VORTEX plugin exists
    $pluginPath = Join-Path $wpPath "wp-content\plugins\vortex-ai-engine"
    if (-not (Test-Path $pluginPath)) {
        Write-Error "VORTEX AI Engine plugin not found in: $pluginPath"
        exit 1
    }
    
    Write-Success "Prerequisites check passed"
}

function Create-Backup {
    if ($SkipBackup) {
        Write-Warning "Skipping backup as requested"
        return
    }
    
    Write-Info "Creating backup..."
    
    $backupDir = $Config[$Environment].BackupPath
    $timestamp = Get-Date -Format "yyyy-MM-dd-HH-mm-ss"
    $backupPath = Join-Path $backupDir "backup-$timestamp"
    
    # Create backup directory
    if (-not (Test-Path $backupDir)) {
        New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    }
    
    # Create backup subdirectory
    New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
    
    try {
        # Backup WordPress files
        $wpPath = $Config[$Environment].WordPressPath
        $filesBackup = Join-Path $backupPath "files"
        Write-Info "Backing up WordPress files..."
        Copy-Item -Path $wpPath -Destination $filesBackup -Recurse -Force
        
        # Backup database
        $dbName = $Config[$Environment].DatabaseName
        $dbBackup = Join-Path $backupPath "database.sql"
        Write-Info "Backing up database: $dbName"
        
        # Use mysqldump if available
        $mysqldump = "C:\xampp\mysql\bin\mysqldump.exe"
        if (Test-Path $mysqldump) {
            & $mysqldump -u root -p --databases $dbName > $dbBackup
        } else {
            Write-Warning "mysqldump not found, database backup skipped"
        }
        
        # Create backup manifest
        $manifest = @{
            timestamp = $timestamp
            environment = $Environment
            files_backup = $filesBackup
            database_backup = $dbBackup
            version = "2.2.0"
        }
        
        $manifestPath = Join-Path $backupPath "manifest.json"
        $manifest | ConvertTo-Json | Out-File -FilePath $manifestPath
        
        Write-Success "Backup created successfully: $backupPath"
        return $backupPath
        
    } catch {
        Write-Error "Backup failed: $($_.Exception.Message)"
        exit 1
    }
}

function Run-SmokeTest {
    if ($SkipTests) {
        Write-Warning "Skipping smoke tests as requested"
        return $true
    }
    
    Write-Info "Running smoke tests..."
    
    $wpPath = $Config[$Environment].WordPressPath
    $smokeTestPath = Join-Path $wpPath "wp-content\plugins\vortex-ai-engine\deployment\smoke-test.php"
    
    if (-not (Test-Path $smokeTestPath)) {
        Write-Error "Smoke test script not found: $smokeTestPath"
        return $false
    }
    
    try {
        # Run smoke test via PHP CLI
        $phpPath = "C:\xampp\php\php.exe"
        if (Test-Path $phpPath) {
            $output = & $phpPath $smokeTestPath 2>&1
            $exitCode = $LASTEXITCODE
            
            if ($exitCode -eq 0) {
                Write-Success "Smoke tests passed"
                return $true
            } else {
                Write-Error "Smoke tests failed with exit code: $exitCode"
                Write-Error "Output: $output"
                return $false
            }
        } else {
            Write-Warning "PHP CLI not found, smoke tests skipped"
            return $true
        }
    } catch {
        Write-Error "Smoke test execution failed: $($_.Exception.Message)"
        return $false
    }
}

function Deploy-Plugin {
    Write-Info "Deploying VORTEX AI Engine plugin..."
    
    $wpPath = $Config[$Environment].WordPressPath
    $pluginPath = Join-Path $wpPath "wp-content\plugins\vortex-ai-engine"
    
    try {
        # Stop WordPress cron temporarily
        Write-Info "Stopping WordPress cron..."
        $wpCronDisabled = Join-Path $wpPath "wp-cron-disabled"
        if (-not (Test-Path $wpCronDisabled)) {
            New-Item -ItemType File -Path $wpCronDisabled -Force | Out-Null
        }
        
        # Deactivate plugin if active
        Write-Info "Deactivating plugin..."
        $deactivationScript = @"
<?php
require_once '$wpPath\wp-config.php';
require_once '$wpPath\wp-load.php';

if (is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
    deactivate_plugins('vortex-ai-engine/vortex-ai-engine.php');
    echo "Plugin deactivated successfully\n";
} else {
    echo "Plugin was not active\n";
}
"@
        
        $deactivationPath = Join-Path $wpPath "deactivate-vortex.php"
        $deactivationScript | Out-File -FilePath $deactivationPath -Encoding UTF8
        & "C:\xampp\php\php.exe" $deactivationPath
        Remove-Item $deactivationPath -Force
        
        # Update plugin files
        Write-Info "Updating plugin files..."
        $sourcePath = "C:\Users\mvill\Documents\vortex-ai-engine\vortex-ai-engine"
        Copy-Item -Path "$sourcePath\*" -Destination $pluginPath -Recurse -Force
        
        # Set proper permissions
        Write-Info "Setting file permissions..."
        Get-ChildItem -Path $pluginPath -Recurse | ForEach-Object {
            if ($_.PSIsContainer) {
                $_.Attributes = $_.Attributes -bor [System.IO.FileAttributes]::ReadOnly
            }
        }
        
        # Reactivate plugin
        Write-Info "Reactivating plugin..."
        $activationScript = @"
<?php
require_once '$wpPath\wp-config.php';
require_once '$wpPath\wp-load.php';

activate_plugin('vortex-ai-engine/vortex-ai-engine.php');
echo "Plugin activated successfully\n";
"@
        
        $activationPath = Join-Path $wpPath "activate-vortex.php"
        $activationScript | Out-File -FilePath $activationPath -Encoding UTF8
        & "C:\xampp\php\php.exe" $activationPath
        Remove-Item $activationPath -Force
        
        # Re-enable WordPress cron
        Write-Info "Re-enabling WordPress cron..."
        if (Test-Path $wpCronDisabled) {
            Remove-Item $wpCronDisabled -Force
        }
        
        Write-Success "Plugin deployment completed"
        return $true
        
    } catch {
        Write-Error "Plugin deployment failed: $($_.Exception.Message)"
        return $false
    }
}

function Test-Deployment {
    Write-Info "Testing deployment..."
    
    # Wait for WordPress to initialize
    Start-Sleep -Seconds 5
    
    # Run post-deployment tests
    $wpPath = $Config[$Environment].WordPressPath
    $testScript = @"
<?php
require_once '$wpPath\wp-config.php';
require_once '$wpPath\wp-load.php';

// Test plugin activation
if (is_plugin_active('vortex-ai-engine/vortex-ai-engine.php')) {
    echo "Plugin is active\n";
} else {
    echo "Plugin is not active\n";
    exit(1);
}

// Test database tables
global `$wpdb;
`$tables = array('vortex_logs', 'vortex_log_stats', 'vortex_log_alerts', 'vortex_github_sync');
foreach (`$tables as `$table) {
    `$table_name = `$wpdb->prefix . `$table;
    `$exists = `$wpdb->get_var("SHOW TABLES LIKE '`$table_name'") === `$table_name;
    if (`$exists) {
        echo "Table `$table exists\n";
    } else {
        echo "Table `$table missing\n";
        exit(1);
    }
}

// Test shortcodes
`$shortcodes = array('huraii_generate', 'vortex_wallet', 'vortex_swap');
foreach (`$shortcodes as `$shortcode) {
    if (shortcode_exists(`$shortcode)) {
        echo "Shortcode `$shortcode registered\n";
    } else {
        echo "Shortcode `$shortcode not registered\n";
        exit(1);
    }
}

echo "All tests passed\n";
"@
    
    $testPath = Join-Path $wpPath "test-deployment.php"
    $testScript | Out-File -FilePath $testPath -Encoding UTF8
    
    try {
        $output = & "C:\xampp\php\php.exe" $testPath 2>&1
        $exitCode = $LASTEXITCODE
        Remove-Item $testPath -Force
        
        if ($exitCode -eq 0) {
            Write-Success "Deployment test passed"
            return $true
        } else {
            Write-Error "Deployment test failed"
            Write-Error "Output: $output"
            return $false
        }
    } catch {
        Write-Error "Deployment test execution failed: $($_.Exception.Message)"
        return $false
    }
}

function Rollback-Deployment {
    param([string]$BackupPath)
    
    Write-Warning "Rolling back deployment..."
    
    if (-not $BackupPath -or -not (Test-Path $BackupPath)) {
        Write-Error "Backup path not found for rollback"
        return $false
    }
    
    try {
        $wpPath = $Config[$Environment].WordPressPath
        $filesBackup = Join-Path $BackupPath "files"
        
        # Restore WordPress files
        Write-Info "Restoring WordPress files..."
        Copy-Item -Path "$filesBackup\*" -Destination $wpPath -Recurse -Force
        
        # Restore database if backup exists
        $dbBackup = Join-Path $BackupPath "database.sql"
        if (Test-Path $dbBackup) {
            Write-Info "Restoring database..."
            $dbName = $Config[$Environment].DatabaseName
            $mysql = "C:\xampp\mysql\bin\mysql.exe"
            
            if (Test-Path $mysql) {
                Get-Content $dbBackup | & $mysql -u root -p $dbName
            }
        }
        
        Write-Success "Rollback completed successfully"
        return $true
        
    } catch {
        Write-Error "Rollback failed: $($_.Exception.Message)"
        return $false
    }
}

function Send-Notification {
    param(
        [string]$Status,
        [string]$Message,
        [string]$BackupPath = ""
    )
    
    Write-Info "Sending deployment notification..."
    
    $notification = @{
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        environment = $Environment
        status = $Status
        message = $Message
        backup_path = $BackupPath
        version = "2.2.0"
    }
    
    # Save notification to file
    $notificationPath = Join-Path $Config[$Environment].BackupPath "deployment-notification.json"
    $notification | ConvertTo-Json | Out-File -FilePath $notificationPath
    
    # You can add email/Slack notification here
    Write-Info "Notification saved to: $notificationPath"
}

# Main deployment process
function Start-Deployment {
    Write-ColorOutput "🚀 VORTEX AI Engine - Production Deployment" $Colors.Info
    Write-ColorOutput "=============================================" $Colors.Info
    Write-ColorOutput "Environment: $Environment" $Colors.Info
    Write-ColorOutput "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" $Colors.Info
    Write-ColorOutput "" $Colors.Info
    
    # Confirmation prompt
    if (-not $Force) {
        $confirmation = Read-Host "Are you sure you want to deploy to $Environment? (y/N)"
        if ($confirmation -ne "y" -and $confirmation -ne "Y") {
            Write-Info "Deployment cancelled by user"
            exit 0
        }
    }
    
    $backupPath = ""
    $deploymentSuccess = $false
    
    try {
        # Step 1: Check prerequisites
        Test-Prerequisites
        
        # Step 2: Create backup
        $backupPath = Create-Backup
        
        # Step 3: Run pre-deployment tests
        if (-not (Run-SmokeTest)) {
            throw "Pre-deployment tests failed"
        }
        
        # Step 4: Deploy plugin
        if (-not (Deploy-Plugin)) {
            throw "Plugin deployment failed"
        }
        
        # Step 5: Test deployment
        if (-not (Test-Deployment)) {
            throw "Post-deployment tests failed"
        }
        
        # Step 6: Run post-deployment smoke tests
        if (-not (Run-SmokeTest)) {
            throw "Post-deployment tests failed"
        }
        
        $deploymentSuccess = $true
        Write-Success "Deployment completed successfully!"
        
    } catch {
        Write-Error "Deployment failed: $($_.Exception.Message)"
        
        # Attempt rollback
        if ($backupPath) {
            Write-Warning "Attempting rollback..."
            if (Rollback-Deployment -BackupPath $backupPath) {
                Write-Success "Rollback completed successfully"
            } else {
                Write-Error "Rollback failed - manual intervention required"
            }
        }
        
        $deploymentSuccess = $false
    }
    
    # Send notification
    if ($deploymentSuccess) {
        Send-Notification -Status "SUCCESS" -Message "Deployment completed successfully" -BackupPath $backupPath
    } else {
        Send-Notification -Status "FAILED" -Message "Deployment failed - check logs for details" -BackupPath $backupPath
    }
    
    # Cleanup
    Write-Info "Cleaning up temporary files..."
    
    return $deploymentSuccess
}

# Execute deployment
$success = Start-Deployment

if ($success) {
    Write-Success "VORTEX AI Engine deployment completed successfully!"
    exit 0
} else {
    Write-Error "VORTEX AI Engine deployment failed!"
    exit 1
} 