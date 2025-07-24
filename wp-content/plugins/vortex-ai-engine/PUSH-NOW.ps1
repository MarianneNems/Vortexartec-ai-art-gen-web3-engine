# VORTEX AI Engine - Push to GitHub Now
Write-Host "🚀 VORTEX AI Engine - Pushing to GitHub" -ForegroundColor Green
Write-Host "=======================================" -ForegroundColor Green
Write-Host ""

# Check current status
Write-Host "📋 Current Status:" -ForegroundColor Cyan
git status --porcelain
Write-Host ""

# Show commit history
Write-Host "📊 Commit History:" -ForegroundColor Cyan
git log --oneline -4
Write-Host ""

# Get GitHub username
$githubUsername = Read-Host "Enter your GitHub username"
if (-not $githubUsername) {
    Write-Host "❌ GitHub username required" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "🔗 Repository will be created at: https://github.com/$githubUsername/vortex-ai-engine" -ForegroundColor Green
Write-Host ""

# Ask for confirmation
$confirm = Read-Host "Ready to create repository and push? (y/N)"
if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "❌ Push cancelled" -ForegroundColor Red
    exit 0
}

Write-Host ""
Write-Host "🔧 Setting up GitHub repository..." -ForegroundColor Cyan

# Add remote origin
$repoUrl = "git@github.com:$githubUsername/vortex-ai-engine.git"
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
    Write-Host "=========================================" -ForegroundColor Green
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
    Write-Host "📋 Repository Details:" -ForegroundColor Yellow
    Write-Host "• Repository URL: https://github.com/$githubUsername/vortex-ai-engine" -ForegroundColor White
    Write-Host "• Branch: main" -ForegroundColor White
    Write-Host "• Version Tag: v2.2.0-audit" -ForegroundColor White
    Write-Host "• Files: 78+ files with 27,000+ lines of code" -ForegroundColor White
    Write-Host "• Status: Ready for development" -ForegroundColor White
    Write-Host ""
    Write-Host "🎯 Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Configure repository settings on GitHub" -ForegroundColor White
    Write-Host "2. Add topics/tags for better discoverability" -ForegroundColor White
    Write-Host "3. Set up branch protection rules" -ForegroundColor White
    Write-Host "4. Enable GitHub Actions for CI/CD" -ForegroundColor White
    Write-Host "5. Create your first release" -ForegroundColor White
    
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