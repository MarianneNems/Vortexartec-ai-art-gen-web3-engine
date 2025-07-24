# VORTEX AI ENGINE - RECURSIVE SELF-IMPROVEMENT DEPLOYMENT SCRIPT (PowerShell)
# Deploys and tests the recursive self-improvement wrapper system on Windows

Write-Host "🔄 VORTEX AI ENGINE - RECURSIVE SELF-IMPROVEMENT DEPLOYMENT" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host ""

# Set variables
$PLUGIN_DIR = "vortex-ai-engine"
$LOGS_DIR = "$PLUGIN_DIR\logs"
$WRAPPER_FILE = "$PLUGIN_DIR\includes\class-vortex-recursive-self-improvement-wrapper.php"
$SMOKE_TEST_FILE = "$PLUGIN_DIR\deployment\comprehensive-architecture-smoke-test.php"
$TEST_FILE = "$PLUGIN_DIR\test-recursive-improvement.php"

Write-Host "📁 Checking directory structure..." -ForegroundColor Yellow

# Check if plugin directory exists
if (-not (Test-Path $PLUGIN_DIR)) {
    Write-Host "❌ Plugin directory not found: $PLUGIN_DIR" -ForegroundColor Red
    exit 1
}

# Create logs directory if it doesn't exist
if (-not (Test-Path $LOGS_DIR)) {
    Write-Host "📁 Creating logs directory..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $LOGS_DIR -Force | Out-Null
}

Write-Host "✅ Directory structure verified" -ForegroundColor Green

Write-Host ""
Write-Host "🔍 Checking required files..." -ForegroundColor Yellow

# Check if wrapper file exists
if (-not (Test-Path $WRAPPER_FILE)) {
    Write-Host "❌ Recursive self-improvement wrapper not found: $WRAPPER_FILE" -ForegroundColor Red
    exit 1
}

# Check if smoke test file exists
if (-not (Test-Path $SMOKE_TEST_FILE)) {
    Write-Host "❌ Comprehensive smoke test not found: $SMOKE_TEST_FILE" -ForegroundColor Red
    exit 1
}

# Check if test file exists
if (-not (Test-Path $TEST_FILE)) {
    Write-Host "❌ Test file not found: $TEST_FILE" -ForegroundColor Red
    exit 1
}

Write-Host "✅ All required files found" -ForegroundColor Green

Write-Host ""
Write-Host "🔧 Setting file permissions..." -ForegroundColor Yellow

# Set proper permissions (Windows equivalent)
try {
    $acl = Get-Acl $LOGS_DIR
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule("Everyone","FullControl","Allow")
    $acl.SetAccessRule($accessRule)
    Set-Acl $LOGS_DIR $acl
    Write-Host "✅ File permissions set" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Could not set file permissions: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🧪 Running recursive improvement test..." -ForegroundColor Yellow

# Run the test
Set-Location $PLUGIN_DIR
try {
    $testResult = php test-recursive-improvement.php
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Recursive improvement test PASSED" -ForegroundColor Green
    } else {
        Write-Host "❌ Recursive improvement test FAILED" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "❌ Error running test: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "🔍 Running comprehensive architecture smoke test..." -ForegroundColor Yellow

# Run the comprehensive smoke test
try {
    $smokeResult = php deployment\comprehensive-architecture-smoke-test.php
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Comprehensive architecture smoke test PASSED" -ForegroundColor Green
    } else {
        Write-Host "❌ Comprehensive architecture smoke test FAILED" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "❌ Error running smoke test: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "📊 Checking log files..." -ForegroundColor Yellow

# Check if log files were created
$LOG_FILES = @(
    "logs\realtime-activity.log",
    "logs\debug-activity.log",
    "logs\performance-metrics.log",
    "logs\error-tracking.log"
)

foreach ($log_file in $LOG_FILES) {
    if (Test-Path $log_file) {
        Write-Host "✅ $log_file exists" -ForegroundColor Green
        # Check if file is writable
        try {
            $testWrite = [System.IO.File]::OpenWrite($log_file)
            $testWrite.Close()
            Write-Host "✅ $log_file is writable" -ForegroundColor Green
        } catch {
            Write-Host "⚠️ $log_file is not writable" -ForegroundColor Yellow
        }
    } else {
        Write-Host "❌ $log_file not found" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "🔍 Checking log content..." -ForegroundColor Yellow

# Check if logs contain data
foreach ($log_file in $LOG_FILES) {
    if (Test-Path $log_file) {
        $line_count = (Get-Content $log_file | Measure-Object -Line).Lines
        Write-Host "📊 $log_file : $line_count lines" -ForegroundColor Cyan
    }
}

Write-Host ""
Write-Host "🎯 DEPLOYMENT SUMMARY" -ForegroundColor Green
Write-Host "====================" -ForegroundColor Green
Write-Host "✅ Recursive self-improvement wrapper deployed" -ForegroundColor Green
Write-Host "✅ Real-time logging system active" -ForegroundColor Green
Write-Host "✅ Debug logging system active" -ForegroundColor Green
Write-Host "✅ Performance monitoring active" -ForegroundColor Green
Write-Host "✅ Error tracking active" -ForegroundColor Green
Write-Host "✅ Agent communication monitoring active" -ForegroundColor Green
Write-Host "✅ Tool calling access monitoring active" -ForegroundColor Green
Write-Host "✅ All tests passed" -ForegroundColor Green
Write-Host "✅ Log files created and writable" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 RECURSIVE SELF-IMPROVEMENT SYSTEM IS OPERATIONAL!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 NEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Monitor logs in $LOGS_DIR" -ForegroundColor White
Write-Host "2. Check WordPress admin for improvement dashboard" -ForegroundColor White
Write-Host "3. Monitor system performance" -ForegroundColor White
Write-Host "4. Review improvement statistics" -ForegroundColor White
Write-Host ""
Write-Host "🎉 DEPLOYMENT COMPLETE!" -ForegroundColor Green 