# VORTEX AI Engine - Push to GitHub Script
# This script helps you push the plugin to a new GitHub repository

Write-Host "🚀 VORTEX AI Engine - GitHub Repository Setup" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""

# Check Git status
Write-Host "📋 Current Git Status:" -ForegroundColor Cyan
git status --porcelain

Write-Host ""
Write-Host "📊 Repository Statistics:" -ForegroundColor Cyan
$fileCount = (git ls-files | Measure-Object).Count
$lineCount = (git ls-files | ForEach-Object { (Get-Content $_ | Measure-Object -Line).Lines } | Measure-Object -Sum).Sum
Write-Host "Files: $fileCount" -ForegroundColor White
Write-Host "Lines of Code: $lineCount" -ForegroundColor White

Write-Host ""
Write-Host "🎯 Next Steps:" -ForegroundColor Yellow
Write-Host "1. Go to https://github.com/new" -ForegroundColor White
Write-Host "2. Create repository named 'vortex-ai-engine'" -ForegroundColor White
Write-Host "3. DO NOT initialize with README, .gitignore, or license" -ForegroundColor White
Write-Host "4. Copy the repository URL" -ForegroundColor White
Write-Host ""

# Get GitHub username
$githubUsername = Read-Host "Enter your GitHub username"
if (-not $githubUsername) {
    Write-Host "❌ GitHub username required" -ForegroundColor Red
    exit 1
}

$repoUrl = "https://github.com/$githubUsername/vortex-ai-engine.git"

Write-Host ""
Write-Host "🔗 Repository URL: $repoUrl" -ForegroundColor Green
Write-Host ""

# Ask for confirmation
$confirm = Read-Host "Ready to push to GitHub? (y/N)"
if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "❌ Push cancelled" -ForegroundColor Red
    exit 0
}

Write-Host ""
Write-Host "🔧 Setting up remote repository..." -ForegroundColor Cyan

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
    Write-Host "🎉 SUCCESS! Repository pushed to GitHub!" -ForegroundColor Green
    Write-Host "================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "🔗 View your repository at: $repoUrl" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📋 Next steps:" -ForegroundColor Yellow
    Write-Host "1. Configure repository settings on GitHub" -ForegroundColor White
    Write-Host "2. Add topics/tags for better discoverability" -ForegroundColor White
    Write-Host "3. Set up branch protection rules" -ForegroundColor White
    Write-Host "4. Create your first release tag" -ForegroundColor White
    Write-Host "5. Set up GitHub Actions for CI/CD" -ForegroundColor White
    Write-Host ""
    Write-Host "📚 See GITHUB-SETUP-GUIDE.md for detailed instructions" -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "❌ Failed to push to GitHub" -ForegroundColor Red
    Write-Host "Please check:" -ForegroundColor Yellow
    Write-Host "1. Repository exists on GitHub" -ForegroundColor White
    Write-Host "2. You have proper permissions" -ForegroundColor White
    Write-Host "3. Git credentials are configured" -ForegroundColor White
}

Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 