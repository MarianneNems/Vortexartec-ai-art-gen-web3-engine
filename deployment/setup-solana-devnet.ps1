# VORTEX AI Engine - Solana Devnet Setup Script
# PowerShell script to configure Solana devnet for testing

param(
    [string]$Network = "devnet",
    [string]$KeypairPath = "validator-keypair.json",
    [string]$VoteAccountPath = "vote-account-keypair.json",
    [switch]$SetupValidator = $false,
    [switch]$InstallSolana = $false
)

Write-Host "🚀 VORTEX AI Engine - Solana Devnet Setup" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# Configuration
$SolanaConfig = @{
    Devnet = @{
        RpcUrl = "https://api.devnet.solana.com"
        WsUrl = "wss://api.devnet.solana.com"
        GenesisHash = "EtWTRABZaYq6iMfeYKouRu166VU2xqa1wcaWoxPkrZBG"
        Entrypoints = @(
            "entrypoint.devnet.solana.com:8001",
            "entrypoint2.devnet.solana.com:8001",
            "entrypoint3.devnet.solana.com:8001",
            "entrypoint4.devnet.solana.com:8001",
            "entrypoint5.devnet.solana.com:8001"
        )
        KnownValidators = @(
            "dv1ZAGvdsz5hHLwWXsVnM94hWf1pjbKVau1QVkaMJ92",
            "dv2eQHeP4RFrJZ6UeiZWoc3XTtmtZCUKxxCApCDcRNV",
            "dv4ACNkpYPcE3aKmYDqZm9G5EB3J4MRoeE7WNDRBVJB",
            "dv3qDFk1DTF36Z62bNvrCXe9sKATA6xvVy6A798xxAS"
        )
    }
    Testnet = @{
        RpcUrl = "https://api.testnet.solana.com"
        WsUrl = "wss://api.testnet.solana.com"
        GenesisHash = "4uhcVNiUZhFDVUTM1vp3AjT1xq7X7zpnjZdf6JRCuCjz"
        Entrypoints = @(
            "entrypoint.testnet.solana.com:8001",
            "entrypoint2.testnet.solana.com:8001",
            "entrypoint3.testnet.solana.com:8001",
            "entrypoint4.testnet.solana.com:8001",
            "entrypoint5.testnet.solana.com:8001"
        )
        KnownValidators = @()
    }
}

# Metrics configuration
$MetricsConfig = @{
    Host = "https://metrics.solana.com:8086"
    Database = "devnet"
    Username = "scratch_writer"
    Password = "topsecret"
}

function Test-SolanaInstallation {
    Write-Host "🔍 Checking Solana installation..." -ForegroundColor Yellow
    
    try {
        $solanaVersion = solana --version 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Solana CLI is installed: $solanaVersion" -ForegroundColor Green
            return $true
        }
    }
    catch {
        Write-Host "❌ Solana CLI not found" -ForegroundColor Red
        return $false
    }
    
    return $false
}

function Install-Solana {
    Write-Host "📦 Installing Solana CLI..." -ForegroundColor Yellow
    
    # Download and install Solana
    $installScript = "https://release.solana.com/v1.17.0/install"
    
    try {
        # For Windows, download the installer
        $installerUrl = "https://release.solana.com/v1.17.0/solana-install-init-x86_64-pc-windows-msvc.exe"
        $installerPath = "$env:TEMP\solana-installer.exe"
        
        Write-Host "Downloading Solana installer..." -ForegroundColor Yellow
        Invoke-WebRequest -Uri $installerUrl -OutFile $installerPath
        
        Write-Host "Installing Solana..." -ForegroundColor Yellow
        Start-Process -FilePath $installerPath -ArgumentList "--data-dir", "$env:USERPROFILE\.local\share\solana\install\active_release\bin" -Wait
        
        # Add to PATH
        $solanaPath = "$env:USERPROFILE\.local\share\solana\install\active_release\bin"
        $currentPath = [Environment]::GetEnvironmentVariable("PATH", "User")
        if ($currentPath -notlike "*$solanaPath*") {
            [Environment]::SetEnvironmentVariable("PATH", "$currentPath;$solanaPath", "User")
            Write-Host "✅ Added Solana to PATH" -ForegroundColor Green
        }
        
        Write-Host "✅ Solana CLI installed successfully" -ForegroundColor Green
    }
    catch {
        Write-Host "❌ Failed to install Solana CLI: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    
    return $true
}

function Set-SolanaConfig {
    param([string]$Network)
    
    Write-Host "⚙️ Configuring Solana for $Network..." -ForegroundColor Yellow
    
    $config = $SolanaConfig[$Network]
    
    try {
        # Set RPC URL
        Write-Host "Setting RPC URL: $($config.RpcUrl)" -ForegroundColor Yellow
        solana config set --url $config.RpcUrl
        
        # Set commitment
        Write-Host "Setting commitment: confirmed" -ForegroundColor Yellow
        solana config set --commitment confirmed
        
        # Set genesis hash
        Write-Host "Setting genesis hash: $($config.GenesisHash)" -ForegroundColor Yellow
        solana config set --expected-genesis-hash $config.GenesisHash
        
        Write-Host "✅ Solana configuration set for $Network" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "❌ Failed to configure Solana: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function New-SolanaKeypair {
    param([string]$Path)
    
    Write-Host "🔑 Generating Solana keypair: $Path" -ForegroundColor Yellow
    
    try {
        solana-keygen new --outfile $Path --no-bip39-passphrase
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Keypair generated: $Path" -ForegroundColor Green
            return $true
        }
    }
    catch {
        Write-Host "❌ Failed to generate keypair: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    
    return $false
}

function New-VoteAccount {
    param([string]$IdentityPath, [string]$VoteAccountPath)
    
    Write-Host "🗳️ Creating vote account..." -ForegroundColor Yellow
    
    try {
        solana create-vote-account $VoteAccountPath $IdentityPath --commission 10
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Vote account created" -ForegroundColor Green
            return $true
        }
    }
    catch {
        Write-Host "❌ Failed to create vote account: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    
    return $false
}

function Start-Validator {
    param([string]$Network, [string]$IdentityPath, [string]$VoteAccountPath)
    
    Write-Host "🏗️ Starting Solana validator for $Network..." -ForegroundColor Yellow
    
    $config = $SolanaConfig[$Network]
    
    # Build validator command
    $validatorArgs = @(
        "--identity", $IdentityPath,
        "--vote-account", $VoteAccountPath,
        "--rpc-port", "8899",
        "--dynamic-port-range", "8000-8020",
        "--wal-recovery-mode", "skip_any_corrupted_record",
        "--limit-ledger-size"
    )
    
    # Add known validators
    foreach ($validator in $config.KnownValidators) {
        $validatorArgs += "--known-validator"
        $validatorArgs += $validator
    }
    
    # Add entrypoints
    foreach ($entrypoint in $config.Entrypoints) {
        $validatorArgs += "--entrypoint"
        $validatorArgs += $entrypoint
    }
    
    # Add genesis hash
    $validatorArgs += "--expected-genesis-hash"
    $validatorArgs += $config.GenesisHash
    
    try {
        Write-Host "Starting validator with arguments: $($validatorArgs -join ' ')" -ForegroundColor Yellow
        Start-Process -FilePath "agave-validator" -ArgumentList $validatorArgs -NoNewWindow
        Write-Host "✅ Validator started successfully" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "❌ Failed to start validator: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Set-EnvironmentVariables {
    Write-Host "🌍 Setting environment variables..." -ForegroundColor Yellow
    
    # Set Solana metrics configuration
    $metricsConfig = "host=$($MetricsConfig.Host),db=$($MetricsConfig.Database),u=$($MetricsConfig.Username),p=$($MetricsConfig.Password)"
    
    try {
        [Environment]::SetEnvironmentVariable("SOLANA_METRICS_CONFIG", $metricsConfig, "User")
        Write-Host "✅ Set SOLANA_METRICS_CONFIG" -ForegroundColor Green
        
        # Set Solana network
        [Environment]::SetEnvironmentVariable("SOLANA_NETWORK", $Network, "User")
        Write-Host "✅ Set SOLANA_NETWORK=$Network" -ForegroundColor Green
        
        # Set Solana RPC URL
        $rpcUrl = $SolanaConfig[$Network].RpcUrl
        [Environment]::SetEnvironmentVariable("SOLANA_RPC_URL", $rpcUrl, "User")
        Write-Host "✅ Set SOLANA_RPC_URL=$rpcUrl" -ForegroundColor Green
        
        return $true
    }
    catch {
        Write-Host "❌ Failed to set environment variables: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Test-SolanaConnection {
    param([string]$Network)
    
    Write-Host "🔗 Testing Solana connection..." -ForegroundColor Yellow
    
    $config = $SolanaConfig[$Network]
    
    try {
        # Test RPC connection
        $response = Invoke-RestMethod -Uri $config.RpcUrl -Method Post -Body '{"jsonrpc":"2.0","id":1,"method":"getHealth"}' -ContentType "application/json"
        
        if ($response.result -eq "ok") {
            Write-Host "✅ RPC connection successful" -ForegroundColor Green
            return $true
        }
        else {
            Write-Host "❌ RPC connection failed" -ForegroundColor Red
            return $false
        }
    }
    catch {
        Write-Host "❌ Failed to connect to Solana: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Get-SolanaBalance {
    param([string]$PublicKey)
    
    Write-Host "💰 Getting balance for $PublicKey..." -ForegroundColor Yellow
    
    try {
        $balance = solana balance $PublicKey
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Balance: $balance" -ForegroundColor Green
            return $balance
        }
    }
    catch {
        Write-Host "❌ Failed to get balance: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

function Request-Airdrop {
    param([string]$PublicKey, [decimal]$Amount = 2)
    
    Write-Host "🎁 Requesting airdrop of $Amount SOL to $PublicKey..." -ForegroundColor Yellow
    
    try {
        solana airdrop $Amount $PublicKey
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Airdrop successful" -ForegroundColor Green
            return $true
        }
    }
    catch {
        Write-Host "❌ Failed to request airdrop: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    
    return $false
}

# Main execution
Write-Host "Starting Solana devnet setup..." -ForegroundColor Cyan

# Check if Solana is installed
if (-not (Test-SolanaInstallation)) {
    if ($InstallSolana) {
        if (-not (Install-Solana)) {
            Write-Host "❌ Failed to install Solana CLI. Please install manually." -ForegroundColor Red
            exit 1
        }
    }
    else {
        Write-Host "❌ Solana CLI not found. Use -InstallSolana to install automatically." -ForegroundColor Red
        exit 1
    }
}

# Set Solana configuration
if (-not (Set-SolanaConfig -Network $Network)) {
    Write-Host "❌ Failed to configure Solana" -ForegroundColor Red
    exit 1
}

# Test connection
if (-not (Test-SolanaConnection -Network $Network)) {
    Write-Host "❌ Failed to connect to Solana $Network" -ForegroundColor Red
    exit 1
}

# Generate keypairs if they don't exist
if (-not (Test-Path $KeypairPath)) {
    if (-not (New-SolanaKeypair -Path $KeypairPath)) {
        Write-Host "❌ Failed to generate identity keypair" -ForegroundColor Red
        exit 1
    }
}

if (-not (Test-Path $VoteAccountPath)) {
    if (-not (New-SolanaKeypair -Path $VoteAccountPath)) {
        Write-Host "❌ Failed to generate vote account keypair" -ForegroundColor Red
        exit 1
    }
}

# Set environment variables
if (-not (Set-EnvironmentVariables)) {
    Write-Host "❌ Failed to set environment variables" -ForegroundColor Red
    exit 1
}

# Get public key
$identityPubkey = solana-keygen pubkey $KeypairPath
Write-Host "🔑 Identity public key: $identityPubkey" -ForegroundColor Cyan

# Request airdrop for testing
if ($Network -eq "devnet") {
    if (Request-Airdrop -PublicKey $identityPubkey) {
        Start-Sleep -Seconds 2
        Get-SolanaBalance -PublicKey $identityPubkey
    }
}

# Setup validator if requested
if ($SetupValidator) {
    Write-Host "🏗️ Setting up validator..." -ForegroundColor Yellow
    
    # Create vote account
    if (-not (New-VoteAccount -IdentityPath $KeypairPath -VoteAccountPath $VoteAccountPath)) {
        Write-Host "❌ Failed to create vote account" -ForegroundColor Red
        exit 1
    }
    
    # Start validator
    if (-not (Start-Validator -Network $Network -IdentityPath $KeypairPath -VoteAccountPath $VoteAccountPath)) {
        Write-Host "❌ Failed to start validator" -ForegroundColor Red
        exit 1
    }
}

Write-Host "🎉 Solana $Network setup completed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Configuration Summary:" -ForegroundColor Cyan
Write-Host "   Network: $Network" -ForegroundColor White
Write-Host "   RPC URL: $($SolanaConfig[$Network].RpcUrl)" -ForegroundColor White
Write-Host "   Identity: $identityPubkey" -ForegroundColor White
Write-Host "   Keypair: $KeypairPath" -ForegroundColor White
Write-Host "   Vote Account: $VoteAccountPath" -ForegroundColor White
Write-Host ""
Write-Host "🔧 Next Steps:" -ForegroundColor Cyan
Write-Host "   1. Test your connection: solana cluster-version" -ForegroundColor White
Write-Host "   2. Check your balance: solana balance $identityPubkey" -ForegroundColor White
Write-Host "   3. Deploy a program: solana program deploy <program.so>" -ForegroundColor White
Write-Host "   4. View metrics: Visit $($MetricsConfig.Host)" -ForegroundColor White
Write-Host ""
Write-Host "📚 Documentation: https://docs.solana.com/developing/clients/devnet" -ForegroundColor Blue 