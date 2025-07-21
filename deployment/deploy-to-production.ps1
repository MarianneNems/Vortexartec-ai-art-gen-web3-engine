# Vortex AI Engine - Production Deployment Script
# 
# Automated deployment script with backup, verification, and rollback
# Run this script to deploy the plugin to production
# 
# Usage: .\deploy-to-production.ps1 -Environment "production" -BackupDatabase $true

param(
    [Parameter(Mandatory=$true)]
    [string]$Environment,
    
    [Parameter(Mandatory=$false)]
    [bool]$BackupDatabase = $true,
    
    [Parameter(Mandatory=$false)]
    [bool]$RunSmokeTests = $true,
    
    [Parameter(Mandatory=$false)]
    [bool]$EnableMaintenanceMode = $true
)

# Configuration
$Config = @{
    Staging = @{
        SiteUrl = "https://staging.yoursite.com"
        PluginPath = "C:\inetpub\wwwroot\staging\wp-content\plugins\vortex-ai-engine"
        DatabaseName = "staging_db"
        BackupPath = "C:\backups\staging"
    }
    Production = @{
        SiteUrl = "https://yoursite.com"
        PluginPath = "C:\inetpub\wwwroot\wp-content\plugins\vortex-ai-engine"
        DatabaseName = "production_db"
        BackupPath = "C:\backups\production"
    }
}

# Logging function
function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    Write-Host $logMessage
    Add-Content -Path "deployment.log" -Value $logMessage
}

# Error handling function
function Handle-Error {
    param([string]$ErrorMessage, [bool]$Rollback = $false)
    Write-Log "ERROR: $ErrorMessage" "ERROR"
    
    if ($Rollback) {
        Write-Log "Initiating rollback..." "WARN"
        Invoke-Rollback
    }
    
    exit 1
}

# Database backup function
function Backup-Database {
    param([string]$DatabaseName, [string]$BackupPath)
    
    Write-Log "Creating database backup for $DatabaseName..."
    
    try {
        $backupFile = "$BackupPath\backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
        
        # Create backup directory if it doesn't exist
        if (!(Test-Path $BackupPath)) {
            New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
        }
        
        # MySQL backup command (adjust for your database)
        $mysqldump = "mysqldump"
        $mysqlUser = "root"
        $mysqlPassword = "your_password"
        
        $backupCommand = "$mysqldump -u $mysqlUser -p$mysqlPassword $DatabaseName > $backupFile"
        
        Invoke-Expression $backupCommand
        
        if (Test-Path $backupFile) {
            Write-Log "Database backup created: $backupFile" "SUCCESS"
            return $backupFile
        } else {
            throw "Backup file was not created"
        }
    }
    catch {
        Handle-Error "Database backup failed: $($_.Exception.Message)"
    }
}

# Plugin backup function
function Backup-Plugin {
    param([string]$PluginPath)
    
    Write-Log "Creating plugin backup..."
    
    try {
        $backupDir = "$PluginPath\backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        
        if (Test-Path $PluginPath) {
            Copy-Item -Path $PluginPath -Destination $backupDir -Recurse -Force
            Write-Log "Plugin backup created: $backupDir" "SUCCESS"
            return $backupDir
        } else {
            Write-Log "Plugin directory not found, skipping backup" "WARN"
            return $null
        }
    }
    catch {
        Handle-Error "Plugin backup failed: $($_.Exception.Message)"
    }
}

# Deploy plugin function
function Deploy-Plugin {
    param([string]$SourcePath, [string]$TargetPath)
    
    Write-Log "Deploying plugin to $TargetPath..."
    
    try {
        # Stop application pool if using IIS
        if (Get-Command "Import-Module WebAdministration" -ErrorAction SilentlyContinue) {
            Import-Module WebAdministration
            $appPool = "DefaultAppPool" # Adjust to your app pool name
            Stop-WebAppPool -Name $appPool
            Write-Log "Application pool stopped: $appPool"
        }
        
        # Remove existing plugin
        if (Test-Path $TargetPath) {
            Remove-Item -Path $TargetPath -Recurse -Force
            Write-Log "Existing plugin removed"
        }
        
        # Copy new plugin
        Copy-Item -Path $SourcePath -Destination $TargetPath -Recurse -Force
        Write-Log "New plugin deployed"
        
        # Set proper permissions
        $acl = Get-Acl $TargetPath
        $rule = New-Object System.Security.AccessControl.FileSystemAccessRule("IIS_IUSRS", "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow")
        $acl.SetAccessRule($rule)
        Set-Acl -Path $TargetPath -AclObject $acl
        Write-Log "Permissions set"
        
        # Start application pool
        if (Get-Command "Start-WebAppPool" -ErrorAction SilentlyContinue) {
            Start-WebAppPool -Name $appPool
            Write-Log "Application pool started: $appPool"
        }
        
        Write-Log "Plugin deployment completed" "SUCCESS"
    }
    catch {
        Handle-Error "Plugin deployment failed: $($_.Exception.Message)" $true
    }
}

# Run smoke tests function
function Invoke-SmokeTests {
    param([string]$SiteUrl)
    
    Write-Log "Running smoke tests against $SiteUrl..."
    
    try {
        $tests = @(
            @{ Name = "Health Check"; Url = "$SiteUrl/wp-json/vortex/v1/health-check" },
            @{ Name = "Feedback Endpoint"; Url = "$SiteUrl/wp-json/vortex/v1/feedback" },
            @{ Name = "Generate Endpoint"; Url = "$SiteUrl/wp-json/vortex/v1/generate" },
            @{ Name = "Wallet Endpoint"; Url = "$SiteUrl/wp-json/vortex/v1/wallet" }
        )
        
        $failedTests = @()
        
        foreach ($test in $tests) {
            try {
                $response = Invoke-WebRequest -Uri $test.Url -Method GET -TimeoutSec 30
                
                if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 401) {
                    Write-Log "✓ $($test.Name): Status $($response.StatusCode)" "SUCCESS"
                } else {
                    $failedTests += "$($test.Name) returned status $($response.StatusCode)"
                    Write-Log "✗ $($test.Name): Status $($response.StatusCode)" "ERROR"
                }
            }
            catch {
                $failedTests += "$($test.Name) failed: $($_.Exception.Message)"
                Write-Log "✗ $($test.Name): $($_.Exception.Message)" "ERROR"
            }
        }
        
        if ($failedTests.Count -gt 0) {
            throw "Smoke tests failed: $($failedTests -join ', ')"
        }
        
        Write-Log "All smoke tests passed" "SUCCESS"
    }
    catch {
        Handle-Error "Smoke tests failed: $($_.Exception.Message)" $true
    }
}

# Rollback function
function Invoke-Rollback {
    Write-Log "Initiating rollback..." "WARN"
    
    try {
        # Restore plugin backup
        if ($script:PluginBackupPath -and (Test-Path $script:PluginBackupPath)) {
            if (Test-Path $Config[$Environment].PluginPath) {
                Remove-Item -Path $Config[$Environment].PluginPath -Recurse -Force
            }
            Copy-Item -Path $script:PluginBackupPath -Destination $Config[$Environment].PluginPath -Recurse -Force
            Write-Log "Plugin rollback completed" "SUCCESS"
        }
        
        # Restore database backup
        if ($script:DatabaseBackupPath -and (Test-Path $script:DatabaseBackupPath)) {
            $mysql = "mysql"
            $mysqlUser = "root"
            $mysqlPassword = "your_password"
            
            $restoreCommand = "$mysql -u $mysqlUser -p$mysqlPassword $($Config[$Environment].DatabaseName) < $script:DatabaseBackupPath"
            Invoke-Expression $restoreCommand
            
            Write-Log "Database rollback completed" "SUCCESS"
        }
        
        Write-Log "Rollback completed successfully" "SUCCESS"
    }
    catch {
        Write-Log "Rollback failed: $($_.Exception.Message)" "ERROR"
        exit 1
    }
}

# Main deployment function
function Start-Deployment {
    Write-Log "Starting Vortex AI Engine deployment to $Environment environment..."
    
    # Validate environment
    if (!$Config.ContainsKey($Environment)) {
        Handle-Error "Invalid environment: $Environment. Valid options: $($Config.Keys -join ', ')"
    }
    
    $envConfig = $Config[$Environment]
    
    # Create backups
    if ($BackupDatabase) {
        $script:DatabaseBackupPath = Backup-Database -DatabaseName $envConfig.DatabaseName -BackupPath $envConfig.BackupPath
    }
    
    $script:PluginBackupPath = Backup-Plugin -PluginPath $envConfig.PluginPath
    
    # Enable maintenance mode
    if ($EnableMaintenanceMode) {
        Write-Log "Enabling maintenance mode..."
        # Add your maintenance mode logic here
    }
    
    # Deploy plugin
    $sourcePath = ".\vortex-ai-engine" # Adjust to your source path
    Deploy-Plugin -SourcePath $sourcePath -TargetPath $envConfig.PluginPath
    
    # Wait for deployment to settle
    Write-Log "Waiting for deployment to settle..."
    Start-Sleep -Seconds 10
    
    # Run smoke tests
    if ($RunSmokeTests) {
        Invoke-SmokeTests -SiteUrl $envConfig.SiteUrl
    }
    
    # Disable maintenance mode
    if ($EnableMaintenanceMode) {
        Write-Log "Disabling maintenance mode..."
        # Add your maintenance mode logic here
    }
    
    Write-Log "Deployment completed successfully!" "SUCCESS"
    
    # Clean up old backups (keep last 5)
    Write-Log "Cleaning up old backups..."
    $backupDirs = Get-ChildItem -Path $envConfig.BackupPath -Directory | Sort-Object CreationTime -Descending | Select-Object -Skip 5
    foreach ($dir in $backupDirs) {
        Remove-Item -Path $dir.FullName -Recurse -Force
        Write-Log "Removed old backup: $($dir.Name)"
    }
}

# Execute deployment
try {
    Start-Deployment
}
catch {
    Handle-Error "Deployment failed: $($_.Exception.Message)" $true
}
finally {
    Write-Log "Deployment script completed"
} 