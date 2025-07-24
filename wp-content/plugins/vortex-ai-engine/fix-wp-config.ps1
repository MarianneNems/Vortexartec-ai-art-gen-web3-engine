# VORTEX AI Engine - WordPress Config Fix Script
# This script removes Redis configuration from wp-config.php to fix staging site crashes

Write-Host "🔧 Fixing WordPress Config..." -ForegroundColor Green

# Navigate to WordPress root
$wpConfigPath = "..\wp-config.php"

if (Test-Path $wpConfigPath) {
    Write-Host "✅ Found wp-config.php" -ForegroundColor Green
    
    # Create backup
    $backupPath = "..\wp-config-backup-$(Get-Date -Format 'yyyy-MM-dd-HH-mm-ss').php"
    Copy-Item $wpConfigPath $backupPath
    Write-Host "✅ Backup created: $(Split-Path $backupPath -Leaf)" -ForegroundColor Green
    
    # Read current content
    $content = Get-Content $wpConfigPath -Raw
    
    # Check if Redis configuration exists
    if ($content -match "WP_REDIS_HOST") {
        Write-Host "⚠️ Redis configuration detected - removing..." -ForegroundColor Yellow
        
        # Remove Redis configuration and replace with disabled cache
        $fixedContent = $content -replace "define\( 'WP_CACHE', true \);\s*// Redis Configuration.*?define\('WP_REDIS_DISABLED', true\);", "// Disable WordPress caching to prevent Redis connection issues`ndefine( 'WP_CACHE', false );"
        
        # If regex didn't work, do manual replacement
        if ($fixedContent -eq $content) {
            Write-Host "⚠️ Regex failed, using manual replacement..." -ForegroundColor Yellow
            
            $lines = $content -split "`n"
            $newLines = @()
            $skipRedis = $false
            
            foreach ($line in $lines) {
                if ($line.Trim() -eq "define( 'WP_CACHE', true );") {
                    $newLines += "// Disable WordPress caching to prevent Redis connection issues"
                    $newLines += "define( 'WP_CACHE', false );"
                    $skipRedis = $true
                    continue
                }
                
                if ($skipRedis -and ($line -match "WP_REDIS_" -or $line -match "// Redis Configuration")) {
                    continue
                }
                
                if ($skipRedis -and $line.Trim() -eq "") {
                    $skipRedis = $false
                }
                
                $newLines += $line
            }
            
            $fixedContent = $newLines -join "`n"
        }
        
        # Write fixed content
        Set-Content -Path $wpConfigPath -Value $fixedContent -Encoding UTF8
        
        # Verify fix
        $newContent = Get-Content $wpConfigPath -Raw
        if ($newContent -notmatch "WP_REDIS_HOST") {
            Write-Host "✅ Redis configuration successfully removed!" -ForegroundColor Green
        } else {
            Write-Host "❌ Redis configuration still present" -ForegroundColor Red
        }
        
        if ($newContent -match "define\( 'WP_CACHE', false \);") {
            Write-Host "✅ WordPress caching disabled!" -ForegroundColor Green
        } else {
            Write-Host "❌ WordPress caching still enabled" -ForegroundColor Red
        }
        
    } else {
        Write-Host "✅ No Redis configuration found" -ForegroundColor Green
    }
    
} else {
    Write-Host "❌ wp-config.php not found at: $wpConfigPath" -ForegroundColor Red
    Write-Host "Please run this script from the vortex-ai-engine directory" -ForegroundColor Yellow
}

Write-Host "`n🎉 WordPress config fix completed!" -ForegroundColor Green
Write-Host "Your staging site should now work without Redis errors." -ForegroundColor Cyan 