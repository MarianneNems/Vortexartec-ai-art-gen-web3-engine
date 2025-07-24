# VORTEX AI Engine - Publish to GitHub Script
# This script helps you publish the plugin to GitHub with proper security measures

Write-Host "🚀 VORTEX AI Engine - Publishing to GitHub" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""

# Check current Git status
Write-Host "📋 Current Git Status:" -ForegroundColor Cyan
git status --porcelain

Write-Host ""
Write-Host "📊 Repository Statistics:" -ForegroundColor Cyan
$fileCount = (git ls-files | Measure-Object).Count
$lineCount = (git ls-files | ForEach-Object { (Get-Content $_ | Measure-Object -Line).Lines } | Measure-Object -Sum).Sum
Write-Host "Files: $fileCount" -ForegroundColor White
Write-Host "Lines of Code: $lineCount" -ForegroundColor White

Write-Host ""
Write-Host "🔒 Security Check:" -ForegroundColor Cyan
$sensitiveFiles = @("wp-config.php", "vendor/", "node_modules/", "*.log")
$foundSensitive = $false

foreach ($file in $sensitiveFiles) {
    if (Test-Path $file) {
        Write-Host "⚠️ Found sensitive file: $file" -ForegroundColor Yellow
        $foundSensitive = $true
    }
}

if (-not $foundSensitive) {
    Write-Host "✅ No sensitive files found in repository" -ForegroundColor Green
}

Write-Host ""
Write-Host "🎯 Next Steps:" -ForegroundColor Yellow
Write-Host "1. Go to https://github.com/new" -ForegroundColor White
Write-Host "2. Create repository named vortex-ai-engine" -ForegroundColor White
Write-Host "3. DO NOT initialize with README, .gitignore, or license" -ForegroundColor White
Write-Host "4. Copy the repository URL" -ForegroundColor White
Write-Host ""

# Get GitHub username
$githubUsername = Read-Host "Enter your GitHub username"
if (-not $githubUsername) {
    Write-Host "❌ GitHub username required" -ForegroundColor Red
    exit 1
}

$repoUrl = "git@github.com:$githubUsername/vortex-ai-engine.git"

Write-Host ""
Write-Host "🔗 Repository URL: $repoUrl" -ForegroundColor Green
Write-Host ""

# Ask for confirmation
$confirm = Read-Host "Ready to publish to GitHub? (y/N)"
if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "❌ Publication cancelled" -ForegroundColor Red
    exit 0
}

Write-Host ""
Write-Host "🔧 Setting up GitHub repository..." -ForegroundColor Cyan

# Add remote origin
git remote add origin $repoUrl
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Remote origin added" -ForegroundColor Green
} else {
    Write-Host "⚠️ Remote origin already exists or failed to add" -ForegroundColor Yellow
}

# Set main branch
git branch -M main
Write-Host "✅ Main branch set" -ForegroundColor Green

# Push to GitHub
Write-Host ""
Write-Host "📤 Pushing to GitHub..." -ForegroundColor Cyan
git push -u origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "🎉 SUCCESS! Repository published to GitHub!" -ForegroundColor Green
    Write-Host "=============================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "🔗 View your repository at: https://github.com/$githubUsername/vortex-ai-engine" -ForegroundColor Cyan
    Write-Host ""
    
    # Create and push tag
    Write-Host "🏷️ Creating version tag..." -ForegroundColor Cyan
    git tag v2.2.0-audit
    git push origin v2.2.0-audit
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Version tag v2.2.0-audit created and pushed" -ForegroundColor Green
    } else {
        Write-Host "⚠️ Tag creation failed" -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "📋 Next steps:" -ForegroundColor Yellow
    Write-Host "1. Configure repository settings on GitHub" -ForegroundColor White
    Write-Host "2. Add topics/tags for better discoverability" -ForegroundColor White
    Write-Host "3. Set up branch protection rules" -ForegroundColor White
    Write-Host "4. Enable GitHub Actions for CI/CD" -ForegroundColor White
    Write-Host "5. Create your first release" -ForegroundColor White
    Write-Host ""
    Write-Host "📚 See GITHUB-SETUP-COMPLETE.md for detailed instructions" -ForegroundColor Cyan
    
} else {
    Write-Host ""
    Write-Host "❌ Failed to push to GitHub" -ForegroundColor Red
    Write-Host "Please check:" -ForegroundColor Yellow
    Write-Host "1. Repository exists on GitHub" -ForegroundColor White
    Write-Host "2. You have proper permissions" -ForegroundColor White
    Write-Host "3. Git credentials are configured" -ForegroundColor White
    Write-Host "4. SSH key is set up for GitHub" -ForegroundColor White
}

Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 