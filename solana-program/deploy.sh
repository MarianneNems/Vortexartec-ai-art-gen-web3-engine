#!/bin/bash

# TOLA NFT Solana Program Deployment Script
# This script deploys the TOLA Masterpiece program to the TOLA network

set -e

# Configuration
NETWORK="${NETWORK:-tola-mainnet}"
PROGRAM_NAME="tola_masterpiece"
KEYPAIR_PATH="${KEYPAIR_PATH:-~/.config/solana/id.json}"
RPC_URL="${RPC_URL:-https://api.tola.solana.com}"
AUTHORITY_WALLET="${AUTHORITY_WALLET:-H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_prerequisites() {
    print_info "Checking prerequisites..."
    
    # Check if Solana CLI is installed
    if ! command -v solana &> /dev/null; then
        print_error "Solana CLI is not installed. Please install it first."
        exit 1
    fi
    
    # Check if Anchor CLI is installed
    if ! command -v anchor &> /dev/null; then
        print_error "Anchor CLI is not installed. Please install it first."
        exit 1
    fi
    
    # Check if Rust is installed
    if ! command -v rustc &> /dev/null; then
        print_error "Rust is not installed. Please install it first."
        exit 1
    fi
    
    # Check if Node.js is installed
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed. Please install it first."
        exit 1
    fi
    
    # Check if Yarn is installed
    if ! command -v yarn &> /dev/null; then
        print_error "Yarn is not installed. Please install it first."
        exit 1
    fi
    
    print_success "All prerequisites are installed"
}

setup_solana_config() {
    print_info "Setting up Solana configuration..."
    
    # Set the cluster
    solana config set --url $RPC_URL
    
    # Set the keypair if provided
    if [ -f "$KEYPAIR_PATH" ]; then
        solana config set --keypair $KEYPAIR_PATH
        print_success "Keypair set to $KEYPAIR_PATH"
    else
        print_warning "Keypair not found at $KEYPAIR_PATH"
        print_info "Please ensure you have a valid keypair"
    fi
    
    # Show current configuration
    print_info "Current Solana configuration:"
    solana config get
    
    # Check wallet balance
    print_info "Checking wallet balance..."
    BALANCE=$(solana balance --output json | jq -r '.lamports')
    SOL_BALANCE=$(echo "scale=9; $BALANCE / 1000000000" | bc)
    
    if (( $(echo "$SOL_BALANCE < 1" | bc -l) )); then
        print_warning "Wallet balance is low: $SOL_BALANCE SOL"
        print_info "You may need more SOL for deployment"
    else
        print_success "Wallet balance: $SOL_BALANCE SOL"
    fi
}

install_dependencies() {
    print_info "Installing dependencies..."
    
    # Install Node.js dependencies
    yarn install
    
    # Build Anchor dependencies
    anchor build
    
    print_success "Dependencies installed successfully"
}

run_tests() {
    print_info "Running tests..."
    
    # Run Anchor tests
    anchor test --skip-deploy
    
    if [ $? -eq 0 ]; then
        print_success "All tests passed"
    else
        print_error "Tests failed"
        exit 1
    fi
}

deploy_program() {
    print_info "Deploying TOLA Masterpiece program..."
    
    # Deploy the program
    anchor deploy --provider.cluster $RPC_URL --provider.wallet $KEYPAIR_PATH
    
    if [ $? -eq 0 ]; then
        print_success "Program deployed successfully"
        
        # Get program ID
        PROGRAM_ID=$(solana-keygen pubkey target/deploy/tola_masterpiece-keypair.json)
        print_success "Program ID: $PROGRAM_ID"
        
        # Save program ID to file
        echo $PROGRAM_ID > program_id.txt
        
        # Update Anchor.toml with deployed program ID
        sed -i "s/tola_masterpiece = \".*\"/tola_masterpiece = \"$PROGRAM_ID\"/" Anchor.toml
        
    else
        print_error "Program deployment failed"
        exit 1
    fi
}

initialize_program() {
    print_info "Initializing program state..."
    
    # Create initialize transaction
    anchor run initialize --provider.cluster $RPC_URL --provider.wallet $KEYPAIR_PATH
    
    if [ $? -eq 0 ]; then
        print_success "Program initialized successfully"
    else
        print_error "Program initialization failed"
        exit 1
    fi
}

verify_deployment() {
    print_info "Verifying deployment..."
    
    # Get program info
    PROGRAM_ID=$(cat program_id.txt)
    
    # Check if program exists on blockchain
    PROGRAM_INFO=$(solana program show $PROGRAM_ID --output json 2>/dev/null)
    
    if [ $? -eq 0 ]; then
        print_success "Program verified on blockchain"
        
        # Extract program size
        PROGRAM_SIZE=$(echo $PROGRAM_INFO | jq -r '.programdataAddress')
        print_info "Program data address: $PROGRAM_SIZE"
        
        # Check program authority
        PROGRAM_AUTHORITY=$(echo $PROGRAM_INFO | jq -r '.authority')
        print_info "Program authority: $PROGRAM_AUTHORITY"
        
    else
        print_error "Program verification failed"
        exit 1
    fi
}

create_deployment_report() {
    print_info "Creating deployment report..."
    
    PROGRAM_ID=$(cat program_id.txt)
    TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Create deployment report
    cat > deployment_report.json << EOF
{
  "program_name": "$PROGRAM_NAME",
  "program_id": "$PROGRAM_ID",
  "network": "$NETWORK",
  "rpc_url": "$RPC_URL",
  "authority_wallet": "$AUTHORITY_WALLET",
  "deployed_at": "$TIMESTAMP",
  "deployer_wallet": "$(solana-keygen pubkey $KEYPAIR_PATH)",
  "anchor_version": "$(anchor --version)",
  "solana_version": "$(solana --version)"
}
EOF
    
    print_success "Deployment report created: deployment_report.json"
}

update_wordpress_config() {
    print_info "Updating WordPress configuration..."
    
    PROGRAM_ID=$(cat program_id.txt)
    
    # Create WordPress configuration file
    cat > wordpress_config.php << EOF
<?php
// TOLA NFT WordPress Configuration
// Generated automatically by deployment script

// Solana Program Configuration
define('VORTEX_SOLANA_PROGRAM_ID', '$PROGRAM_ID');
define('VORTEX_SOLANA_RPC_URL', '$RPC_URL');
define('VORTEX_SOLANA_NETWORK', '$NETWORK');
define('VORTEX_TOLA_AUTHORITY', '$AUTHORITY_WALLET');

// WordPress Options to Update
update_option('vortex_solana_program_id', '$PROGRAM_ID');
update_option('vortex_solana_rpc_url', '$RPC_URL');
update_option('vortex_solana_network', '$NETWORK');
update_option('vortex_tola_authority', '$AUTHORITY_WALLET');
update_option('vortex_nft_minting_enabled', true);

// Explorer and Marketplace URLs
update_option('vortex_solana_explorer_url', 'https://explorer.solana.com');
update_option('vortex_nft_marketplace_url', 'https://marketplace.tola.com');

// Default NFT Settings
update_option('vortex_nft_default_royalty', 5.0);
update_option('vortex_nft_max_secondary_royalty', 15.0);
update_option('vortex_nft_auto_mint', true);
EOF
    
    print_success "WordPress configuration created: wordpress_config.php"
    print_info "Please run this configuration in your WordPress admin panel"
}

cleanup() {
    print_info "Cleaning up temporary files..."
    
    # Clean up build artifacts if needed
    # anchor clean
    
    print_success "Cleanup completed"
}

main() {
    print_info "Starting TOLA NFT Solana Program Deployment"
    print_info "Network: $NETWORK"
    print_info "RPC URL: $RPC_URL"
    print_info "Authority: $AUTHORITY_WALLET"
    
    # Check prerequisites
    check_prerequisites
    
    # Setup Solana configuration
    setup_solana_config
    
    # Install dependencies
    install_dependencies
    
    # Run tests
    if [ "${SKIP_TESTS:-false}" != "true" ]; then
        run_tests
    else
        print_warning "Skipping tests as requested"
    fi
    
    # Deploy program
    deploy_program
    
    # Initialize program
    initialize_program
    
    # Verify deployment
    verify_deployment
    
    # Create deployment report
    create_deployment_report
    
    # Update WordPress configuration
    update_wordpress_config
    
    # Cleanup
    cleanup
    
    print_success "TOLA NFT Solana Program deployment completed successfully!"
    print_info "Program ID: $(cat program_id.txt)"
    print_info "Network: $NETWORK"
    print_info "Please update your WordPress configuration with the generated settings"
}

# Handle script arguments
case "${1:-}" in
    --help|-h)
        echo "TOLA NFT Solana Program Deployment Script"
        echo ""
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Environment Variables:"
        echo "  NETWORK           - Target network (default: tola-mainnet)"
        echo "  KEYPAIR_PATH      - Path to keypair file (default: ~/.config/solana/id.json)"
        echo "  RPC_URL           - RPC URL (default: https://api.tola.solana.com)"
        echo "  AUTHORITY_WALLET  - Authority wallet address"
        echo "  SKIP_TESTS        - Skip tests (default: false)"
        echo ""
        echo "Examples:"
        echo "  $0                              # Deploy to tola-mainnet"
        echo "  NETWORK=devnet $0               # Deploy to devnet"
        echo "  SKIP_TESTS=true $0              # Deploy without running tests"
        exit 0
        ;;
    --verify)
        verify_deployment
        exit 0
        ;;
    --test)
        run_tests
        exit 0
        ;;
    *)
        main
        ;;
esac 