#!/bin/bash

# =============================================================================
# VORTEX AI Engine - Infrastructure Deployment Script
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}==============================================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}==============================================================================${NC}"
}

# Check if running in the correct directory
if [[ ! -f "main.tf" ]]; then
    print_error "This script must be run from the infra/ directory"
    exit 1
fi

print_header "VORTEX AI Engine - Infrastructure Deployment"

# =============================================================================
# Pre-deployment Checks
# =============================================================================

print_status "Checking prerequisites..."

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    print_error "AWS CLI is not installed. Please install it first."
    exit 1
fi

# Check if Terraform is installed
if ! command -v terraform &> /dev/null; then
    print_error "Terraform is not installed. Please install it first."
    exit 1
fi

# Check if AWS credentials are configured
if ! aws sts get-caller-identity &> /dev/null; then
    print_error "AWS credentials not configured. Please run 'aws configure' first."
    exit 1
fi

# Check if terraform.tfvars exists
if [[ ! -f "terraform.tfvars" ]]; then
    print_warning "terraform.tfvars not found. Creating from example..."
    cp terraform.tfvars.example terraform.tfvars
    print_warning "Please edit terraform.tfvars with your configuration and run this script again."
    exit 1
fi

print_status "Prerequisites check passed ✓"

# =============================================================================
# Get Configuration
# =============================================================================

# Extract domain from terraform.tfvars
DOMAIN=$(grep 'domain_name' terraform.tfvars | cut -d'"' -f2)
if [[ -z "$DOMAIN" ]]; then
    print_error "Could not find domain_name in terraform.tfvars"
    exit 1
fi

# Extract S3 bucket name
S3_BUCKET=$(grep 's3_bucket_name' terraform.tfvars | cut -d'"' -f2)
if [[ -z "$S3_BUCKET" ]]; then
    print_error "Could not find s3_bucket_name in terraform.tfvars"
    exit 1
fi

print_status "Configuration:"
print_status "  Domain: $DOMAIN"
print_status "  S3 Bucket: $S3_BUCKET"

# =============================================================================
# Validate Route53 Hosted Zone
# =============================================================================

print_status "Validating Route53 hosted zone for $DOMAIN..."

if ! aws route53 list-hosted-zones --query "HostedZones[?Name=='$DOMAIN.']" --output text | grep -q "$DOMAIN"; then
    print_error "Route53 hosted zone for $DOMAIN not found."
    print_error "Please create a hosted zone first:"
    print_error "  aws route53 create-hosted-zone --name $DOMAIN --caller-reference $(date +%s)"
    exit 1
fi

print_status "Route53 hosted zone validated ✓"

# =============================================================================
# Validate S3 Bucket
# =============================================================================

print_status "Validating S3 bucket $S3_BUCKET..."

if ! aws s3api head-bucket --bucket "$S3_BUCKET" 2>/dev/null; then
    print_warning "S3 bucket $S3_BUCKET not found. Creating..."
    aws s3 mb "s3://$S3_BUCKET" --region us-east-1
    print_status "S3 bucket created ✓"
else
    print_status "S3 bucket exists ✓"
fi

# =============================================================================
# Terraform Operations
# =============================================================================

print_header "Terraform Initialization"

print_status "Initializing Terraform..."
terraform init

print_status "Validating Terraform configuration..."
terraform validate

print_header "Terraform Plan"

print_status "Generating Terraform plan..."
terraform plan -out=tfplan

print_status "Plan generated successfully ✓"

# =============================================================================
# Deployment Confirmation
# =============================================================================

print_header "Deployment Confirmation"

echo -e "${YELLOW}The following infrastructure will be deployed:${NC}"
echo "  • S3 Data Lake: $S3_BUCKET"
echo "  • Redis ElastiCache cluster"
echo "  • DynamoDB table for user memory"
echo "  • Vault server on ECS Fargate"
echo "  • AI Orchestrator on ECS Fargate with auto-scaling"
echo "  • Application Load Balancer with SSL"
echo "  • Route53 A records for:"
echo "    - $DOMAIN"
echo "    - vault.$DOMAIN"
echo "    - api.$DOMAIN"
echo ""

read -p "Do you want to proceed with the deployment? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_status "Deployment cancelled."
    exit 0
fi

# =============================================================================
# Deploy Infrastructure
# =============================================================================

print_header "Deploying Infrastructure"

print_status "Applying Terraform configuration..."
terraform apply tfplan

# =============================================================================
# Post-deployment Verification
# =============================================================================

print_header "Post-deployment Verification"

print_status "Waiting for services to be ready..."
sleep 30

# Get outputs
print_status "Retrieving deployment outputs..."
terraform output -json > outputs.json

# Extract key endpoints
VAULT_ENDPOINT=$(terraform output -raw vault_endpoint)
API_ENDPOINT=$(terraform output -raw ai_orchestrator_endpoint)
MAIN_ENDPOINT="https://$DOMAIN"

print_status "Deployment completed successfully! ✓"

print_header "Deployment Summary"

echo -e "${GREEN}✓ Infrastructure deployed successfully${NC}"
echo ""
echo -e "${BLUE}Endpoints:${NC}"
echo "  • Main Website: $MAIN_ENDPOINT"
echo "  • Vault Service: $VAULT_ENDPOINT"
echo "  • AI Orchestrator API: $API_ENDPOINT"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "  1. Verify SSL certificates are issued (may take 5-10 minutes)"
echo "  2. Check ECS services are running:"
echo "     aws ecs describe-services --cluster vortex-ai-prod --services vortex-vault-prod vortex-ai-orchestrator-prod"
echo "  3. Test endpoints:"
echo "     curl -k $VAULT_ENDPOINT/v1/sys/health"
echo "     curl -k $API_ENDPOINT/health"
echo "  4. Monitor CloudWatch logs for any issues"
echo ""

print_status "Deployment script completed successfully!"

# =============================================================================
# Cleanup
# =============================================================================

print_status "Cleaning up temporary files..."
rm -f tfplan outputs.json

print_status "All done! Your VORTEX AI Engine infrastructure is ready. 🚀" 