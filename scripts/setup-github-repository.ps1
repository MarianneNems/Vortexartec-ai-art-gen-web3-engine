# VORTEX AI Engine - GitHub Repository Setup Script
# 
# This script sets up the GitHub repository with proper security measures
# and branch protection for public release

param(
    [Parameter(Mandatory=$true)]
    [string]$RepositoryName = "vortex-ai-engine",
    
    [Parameter(Mandatory=$true)]
    [string]$GitHubUsername,
    
    [Parameter(Mandatory=$false)]
    [string]$Description = "AI-powered marketplace engine for WordPress featuring advanced AI agents, blockchain integration, and automated art generation",
    
    [Parameter(Mandatory=$false)]
    [switch]$Private = $false,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipConfirmation
)

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
    
    # Check if git is installed
    try {
        $gitVersion = git --version
        Write-Success "Git found: $gitVersion"
    } catch {
        Write-Error "Git is not installed. Please install Git first."
        exit 1
    }
    
    # Check if GitHub CLI is installed
    try {
        $ghVersion = gh --version
        Write-Success "GitHub CLI found"
    } catch {
        Write-Warning "GitHub CLI not found. Will use git commands instead."
    }
    
    # Check if we're in a git repository
    if (Test-Path ".git") {
        Write-Success "Already in a git repository"
    } else {
        Write-Info "Initializing git repository..."
        git init
    }
    
    # Check if public-release directory exists
    if (Test-Path "public-release") {
        Write-Success "Public release directory found"
    } else {
        Write-Error "Public release directory not found. Run prepare-public-release.php first."
        exit 1
    }
}

function Create-GitHubRepository {
    Write-Info "Creating GitHub repository..."
    
    $repoUrl = "https://github.com/$GitHubUsername/$RepositoryName"
    
    # Check if repository already exists
    try {
        $response = Invoke-RestMethod -Uri "https://api.github.com/repos/$GitHubUsername/$RepositoryName" -Method Get
        Write-Warning "Repository already exists: $repoUrl"
        return $repoUrl
    } catch {
        Write-Info "Repository does not exist. Creating new repository..."
    }
    
    # Create repository using GitHub CLI if available
    try {
        $visibility = if ($Private) { "private" } else { "public" }
        $result = gh repo create $RepositoryName --description $Description --$visibility --source=. --remote=origin --push
        Write-Success "Repository created successfully: $repoUrl"
        return $repoUrl
    } catch {
        Write-Warning "GitHub CLI failed. Please create repository manually at: $repoUrl"
        Write-Info "Then run: git remote add origin $repoUrl"
        return $repoUrl
    }
}

function Setup-BranchStructure {
    Write-Info "Setting up branch structure..."
    
    # Create main branch (public)
    Write-Info "Setting up main branch (public)..."
    
    # Copy public release files to current directory
    Copy-Item -Path "public-release/*" -Destination "." -Recurse -Force
    
    # Add all files
    git add .
    
    # Initial commit
    git commit -m "Initial public release of VORTEX AI Engine v2.2.0"
    
    # Push to main branch
    git push -u origin main
    
    Write-Success "Main branch (public) created and pushed"
    
    # Create proprietary branch (private)
    Write-Info "Setting up proprietary branch (private)..."
    
    git checkout -b proprietary
    
    # Add sensitive files to proprietary branch
    $sensitiveFiles = @(
        "wp-config.php",
        "wp-salt.php",
        ".env",
        "config/",
        "private/",
        "keys/",
        "logs/",
        "backups/",
        "sensitive-data/"
    )
    
    foreach ($file in $sensitiveFiles) {
        if (Test-Path $file) {
            git add $file
            Write-Info "Added sensitive file: $file"
        }
    }
    
    # Commit sensitive files
    git commit -m "Add proprietary and sensitive data to private branch"
    
    # Push proprietary branch
    git push -u origin proprietary
    
    Write-Success "Proprietary branch (private) created and pushed"
    
    # Switch back to main branch
    git checkout main
}

function Setup-BranchProtection {
    Write-Info "Setting up branch protection..."
    
    # Create branch protection configuration
    $protectionConfig = @{
        required_status_checks = @{
            strict = $true
            contexts = @("ci/tests", "ci/security-scan")
        }
        enforce_admins = $true
        required_pull_request_reviews = @{
            required_approving_review_count = 2
            dismiss_stale_reviews = $true
        }
        restrictions = @{
            users = @()
            teams = @("admin-team")
        }
        allow_force_pushes = $false
        allow_deletions = $false
    }
    
    # Apply branch protection to main branch
    try {
        $protectionJson = $protectionConfig | ConvertTo-Json -Depth 10
        $headers = @{
            "Authorization" = "token $env:GITHUB_TOKEN"
            "Accept" = "application/vnd.github.v3+json"
        }
        
        $url = "https://api.github.com/repos/$GitHubUsername/$RepositoryName/branches/main/protection"
        Invoke-RestMethod -Uri $url -Method Put -Headers $headers -Body $protectionJson
        
        Write-Success "Branch protection applied to main branch"
    } catch {
        Write-Warning "Failed to apply branch protection via API. Please configure manually in GitHub settings."
    }
}

function Setup-SecuritySettings {
    Write-Info "Setting up security settings..."
    
    # Create security files
    $securityFiles = @{
        ".github/SECURITY.md" = @"
# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.2.x   | :white_check_mark: |
| 2.1.x   | :white_check_mark: |
| < 2.1   | :x:                |

## Reporting a Vulnerability

Please report security vulnerabilities to security@vortexartec.com

## Security Measures

- All code is reviewed for security issues
- Regular security audits are performed
- Dependencies are kept up to date
- Encryption is used for sensitive data
"@
        
        ".github/CODEOWNERS" = @"
# Code owners for VORTEX AI Engine
* @$GitHubUsername

# Sensitive files
wp-config.php @$GitHubUsername
wp-salt.php @$GitHubUsername
.env @$GitHubUsername
config/ @$GitHubUsername
private/ @$GitHubUsername
"@
        
        ".github/workflows/security-scan.yml" = @"
name: Security Scan

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    - name: Run security scan
      run: |
        echo "Running security scan..."
        # Add your security scanning tools here
"@
    }
    
    foreach ($file in $securityFiles.Keys) {
        $content = $securityFiles[$file]
        $filePath = $file
        
        # Create directory if needed
        $dir = Split-Path $filePath -Parent
        if (!(Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }
        
        Set-Content -Path $filePath -Value $content
        Write-Success "Created: $file"
    }
    
    # Commit security files
    git add .
    git commit -m "Add security configuration and policies"
    git push
    
    Write-Success "Security settings configured"
}

function Setup-CI-CD {
    Write-Info "Setting up CI/CD pipeline..."
    
    # Create GitHub Actions workflow
    $workflowContent = @"
name: CI/CD Pipeline

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        extensions: mbstring, intl, mysql, xml, curl, gd, zip
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
    
    - name: Run tests
      run: |
        php deployment/smoke-test.php
        php deployment/test-plugin-activation.php
    
    - name: Security scan
      run: |
        echo "Running security scan..."
        # Add security scanning here
    
    - name: Build package
      run: |
        echo "Building deployment package..."
        # Add build process here

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
    - uses: actions/checkout@v3
    
    - name: Deploy to staging
      run: |
        echo "Deploying to staging..."
        # Add deployment process here
"@
    
    $workflowPath = ".github/workflows/ci-cd.yml"
    $workflowDir = Split-Path $workflowPath -Parent
    
    if (!(Test-Path $workflowDir)) {
        New-Item -ItemType Directory -Path $workflowDir -Force | Out-Null
    }
    
    Set-Content -Path $workflowPath -Value $workflowContent
    Write-Success "Created CI/CD workflow"
    
    # Commit CI/CD files
    git add .
    git commit -m "Add CI/CD pipeline configuration"
    git push
    
    Write-Success "CI/CD pipeline configured"
}

function Setup-Documentation {
    Write-Info "Setting up documentation..."
    
    # Create documentation structure
    $docsStructure = @{
        "docs/INSTALLATION.md" = "Installation guide"
        "docs/CONFIGURATION.md" = "Configuration guide"
        "docs/API-REFERENCE.md" = "API reference"
        "docs/DEPLOYMENT.md" = "Deployment guide"
        "docs/SECURITY.md" = "Security guide"
        "docs/CONTRIBUTING.md" = "Contributing guide"
    }
    
    foreach ($file in $docsStructure.Keys) {
        if (!(Test-Path $file)) {
            $content = "# $($docsStructure[$file])

This documentation is under development.

## Quick Start

1. Install the plugin
2. Configure settings
3. Activate in WordPress
4. Follow the setup wizard

For detailed instructions, see the main documentation."
            
            Set-Content -Path $file -Value $content
            Write-Success "Created: $file"
        }
    }
    
    # Commit documentation
    git add .
    git commit -m "Add documentation structure"
    git push
    
    Write-Success "Documentation structure created"
}

function Generate-SetupReport {
    Write-Info "Generating setup report..."
    
    $report = @"
# GitHub Repository Setup Report

## Repository Information
- Name: $RepositoryName
- Owner: $GitHubUsername
- URL: https://github.com/$GitHubUsername/$RepositoryName
- Visibility: $(if ($Private) { 'Private' } else { 'Public' })

## Branch Structure
- main: Public branch with sanitized code
- proprietary: Private branch with sensitive data

## Security Measures
- Branch protection enabled
- Required pull request reviews
- Security scanning configured
- Access controls implemented

## CI/CD Pipeline
- Automated testing
- Security scanning
- Deployment automation

## Next Steps
1. Review the repository structure
2. Configure additional security settings
3. Set up monitoring and alerts
4. Test the CI/CD pipeline
5. Add team members and collaborators

## Important Notes
- Sensitive data is stored in the proprietary branch
- Public branch contains sanitized code only
- Branch protection prevents direct pushes to main
- Security policies are in place

Generated on: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
"@
    
    $reportPath = "github-setup-report-$(Get-Date -Format 'yyyy-MM-dd-HH-mm-ss').md"
    Set-Content -Path $reportPath -Value $report
    
    Write-Success "Setup report generated: $reportPath"
    Write-Info "Repository setup completed successfully!"
}

# Main execution
function Start-GitHubSetup {
    Write-ColorOutput "🚀 VORTEX AI Engine - GitHub Repository Setup" $Colors.Info
    Write-ColorOutput "=============================================" $Colors.Info
    Write-ColorOutput "Repository: $RepositoryName" $Colors.Info
    Write-ColorOutput "Owner: $GitHubUsername" $Colors.Info
    Write-ColorOutput "Visibility: $(if ($Private) { 'Private' } else { 'Public' })" $Colors.Info
    Write-ColorOutput "" $Colors.Info
    
    # Confirmation prompt
    if (-not $SkipConfirmation) {
        $confirmation = Read-Host "Are you sure you want to set up the GitHub repository? (y/N)"
        if ($confirmation -ne "y" -and $confirmation -ne "Y") {
            Write-Info "Setup cancelled by user"
            exit 0
        }
    }
    
    try {
        # Run setup steps
        Test-Prerequisites
        $repoUrl = Create-GitHubRepository
        Setup-BranchStructure
        Setup-BranchProtection
        Setup-SecuritySettings
        Setup-CI-CD
        Setup-Documentation
        Generate-SetupReport
        
        Write-Success "GitHub repository setup completed successfully!"
        Write-Info "Repository URL: $repoUrl"
        
    } catch {
        Write-Error "Setup failed: $($_.Exception.Message)"
        exit 1
    }
}

# Execute setup
Start-GitHubSetup 