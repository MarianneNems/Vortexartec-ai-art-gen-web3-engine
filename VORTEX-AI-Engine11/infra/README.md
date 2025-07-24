# VORTEX AI Engine - Infrastructure as Code

This Terraform configuration provisions the complete production infrastructure for the VORTEX AI Engine, including all services needed for the 7-step orchestration pipeline.

## 🏗️ Infrastructure Overview

### **Core Services**
- **S3 Data Lake**: `vortex-ai-data-lake-1752530342` (existing bucket)
- **Redis ElastiCache**: Caching and session management
- **DynamoDB**: User memory storage with streams
- **Vault on ECS Fargate**: Secure secrets management
- **AI Orchestrator on ECS Fargate**: Main AI processing service
- **Application Load Balancer**: SSL termination and routing
- **Route53**: DNS management for vortexartec.com

### **Security & Monitoring**
- **ACM SSL Certificate**: Wildcard certificate for all subdomains
- **CloudWatch Logs**: Centralized logging
- **IAM Roles**: Least privilege access
- **Security Groups**: Network-level security
- **Auto Scaling**: Dynamic scaling based on CPU/memory

## 📋 Prerequisites

1. **AWS CLI** configured with appropriate credentials
2. **Terraform** >= 1.0 installed
3. **Route53 Hosted Zone** for your domain
4. **Docker images** pushed to registry (for AI Orchestrator)

## 🚀 Quick Start

### 1. Configure Variables
```bash
# Copy example configuration
cp terraform.tfvars.example terraform.tfvars

# Edit with your values
nano terraform.tfvars
```

### 2. Initialize Terraform
```bash
cd infra/
terraform init
```

### 3. Plan Deployment
```bash
terraform plan
```

### 4. Deploy Infrastructure
```bash
terraform apply
```

### 5. Get Outputs
```bash
terraform output
```

## 🔧 Configuration

### **Required Variables**
```hcl
aws_region      = "us-east-1"
environment     = "prod"
domain_name     = "vortexartec.com"
s3_bucket_name  = "vortex-ai-data-lake-1752530342"
```

### **Optional Variables**
See `terraform.tfvars.example` for all available options.

## 🌐 Endpoints

After deployment, the following endpoints will be available:

- **Main Website**: `https://vortexartec.com`
- **Vault Service**: `https://vault.vortexartec.com`
- **AI Orchestrator API**: `https://api.vortexartec.com`

## 📊 Outputs

The configuration provides comprehensive outputs including:

### **Resource ARNs**
- S3 bucket ARN
- DynamoDB table ARN
- Redis cluster ARN
- ECS services ARNs
- Load balancer ARN
- SSL certificate ARN

### **Connection Endpoints**
- Vault HTTPS endpoint
- API HTTPS endpoint
- Redis endpoint
- S3 bucket name
- DynamoDB table name

### **Environment Variables**
Ready-to-use environment variables for container configuration.

## 🔒 Security Features

### **Network Security**
- VPC with public/private subnets
- Security groups with minimal access
- Application Load Balancer with SSL

### **Data Encryption**
- S3 bucket encryption at rest
- Redis encryption in transit and at rest
- DynamoDB encryption at rest

### **Access Control**
- IAM roles with least privilege
- ECS task execution roles
- Service-specific permissions

## 📈 Monitoring & Logging

### **CloudWatch Integration**
- ECS service logs
- Vault service logs
- Application Load Balancer logs

### **Auto Scaling**
- CPU-based scaling (70% target)
- Memory-based scaling (80% target)
- Min: 2 tasks, Max: 10 tasks

## 🎯 Integration with VORTEX AI Engine

### **7-Step Orchestration Pipeline**
1. **Vault Secret Fetch**: `https://vault.vortexartec.com`
2. **Colossal GPU Call**: Via AI Orchestrator
3. **Memory Store**: DynamoDB table
4. **EventBus Emit**: CloudWatch Events
5. **S3 Data-Lake Write**: `vortex-ai-data-lake-1752530342`
6. **Batch Training Trigger**: Lambda/SQS integration
7. **Return Response**: Via API endpoint

### **Environment Variables**
```bash
AWS_REGION=us-east-1
S3_BUCKET_NAME=vortex-ai-data-lake-1752530342
DYNAMODB_TABLE_NAME=vortex-user-memory-prod
REDIS_ENDPOINT=vortex-redis-prod.xxxxx.cache.amazonaws.com:6379
VAULT_ENDPOINT=https://vault.vortexartec.com
ENVIRONMENT=prod
```

## 🔐 Vault Integration

### **Vault Secrets Management**

The infrastructure automatically populates HashiCorp Vault with your proprietary algorithms and configurations:

#### **Algorithm Secrets**
- **AI Orchestration**: Main 7-step pipeline algorithm
- **Base AI Orchestrator**: Core orchestration classes
- **Individual Agent Algorithms**: CLOE, Horace, Archer agents
- **Cost Optimization**: 80% profit margin algorithms
- **Tier Subscription**: Basic/Premium/Enterprise logic
- **HURAII Frontend**: 13-tab dashboard algorithms
- **Individual Shortcodes**: Frontend shortcode handlers
- **AI Chat**: Real-time chat algorithms

#### **Configuration Secrets**
- **S3 Configuration**: Bucket name, region, endpoints
- **DynamoDB Configuration**: Table name, region, endpoints
- **Redis Configuration**: Primary/reader endpoints, port
- **API Configuration**: All service endpoints
- **WordPress Configuration**: Plugin settings and versions

### **Vault Authentication**

#### **AWS Auth Method**
- **Enabled**: AWS IAM authentication for ECS tasks
- **Role**: `ai-orchestrator-role` bound to ECS task IAM role
- **Token TTL**: 600 seconds (10 minutes)
- **Max TTL**: 1200 seconds (20 minutes)

#### **Access Policies**
- **AI Orchestrator Policy**: Read-only access to algorithm secrets
- **Admin Policy**: Full access to all VORTEX secrets

### **Vault Endpoints**

- **Main Vault**: `https://vault.vortexartec.com`
- **KV v2 Engine**: `vortex-secrets/`
- **Algorithm Path**: `vortex-secrets/algorithms/`
- **Config Path**: `vortex-secrets/config/`

### **Using Vault in Your Application**

#### **Authentication Flow**
1. ECS task uses IAM role to authenticate with Vault
2. Vault validates IAM role against AWS
3. Returns JWT token with appropriate policies
4. Application uses token to read secrets

#### **Example Usage**
```bash
# Authenticate with Vault using AWS IAM
vault write auth/aws/login \
  role=ai-orchestrator-role \
  iam_http_request_method=POST \
  iam_request_url=https://sts.amazonaws.com/ \
  iam_request_body=... \
  iam_request_headers=...

# Read algorithm secret
vault kv get vortex-secrets/algorithms/ai_orchestration

# Read configuration
vault kv get vortex-secrets/config/s3
```

## 🛠️ Maintenance

### **Updating Infrastructure**
```bash
# Plan changes
terraform plan

# Apply changes
terraform apply
```

### **Scaling Services**
Edit variables in `terraform.tfvars`:
```hcl
orchestrator_min_capacity = 3
orchestrator_max_capacity = 20
```

### **Monitoring Health**
Check ECS service health:
```bash
aws ecs describe-services --cluster vortex-ai-prod --services vortex-ai-orchestrator-prod
```

## 📝 Cost Optimization

### **Development Environment**
```hcl
redis_node_type = "cache.t3.micro"
orchestrator_cpu = 512
orchestrator_memory = 1024
```

### **Production Environment**
```hcl
redis_node_type = "cache.r6g.large"
orchestrator_cpu = 2048
orchestrator_memory = 4096
```

## 🚨 Troubleshooting

### **Common Issues**

#### **Route53 Zone Not Found**
Ensure your domain has a Route53 hosted zone:
```bash
aws route53 list-hosted-zones
```

#### **SSL Certificate Validation**
DNS validation requires Route53 access:
```bash
aws route53 list-resource-record-sets --hosted-zone-id Z123456789
```

#### **ECS Service Failed to Start**
Check CloudWatch logs:
```bash
aws logs describe-log-groups --log-group-name-prefix /aws/ecs/vortex
```

### **Service Health Checks**
- **Vault**: `https://vault.vortexartec.com/v1/sys/health`
- **AI Orchestrator**: `https://api.vortexartec.com/health`

## 🗂️ File Structure

```
infra/
├── main.tf              # Main infrastructure configuration
├── variables.tf         # Variable definitions
├── outputs.tf           # Output definitions
├── terraform.tfvars.example  # Example configuration
└── README.md           # This file
```

## 📞 Support

For infrastructure issues:
1. Check AWS CloudWatch logs
2. Review Terraform plan/apply output
3. Verify Route53 DNS settings
4. Check ECS service status

## 🔄 Backup & Recovery

### **Automated Backups**
- **DynamoDB**: Point-in-time recovery enabled
- **S3**: Versioning enabled
- **Redis**: Automatic snapshots

### **Disaster Recovery**
```bash
# Recreate infrastructure
terraform destroy
terraform apply
```

## 🚀 Production Deployment

1. **Pre-deployment**: Run `terraform plan`
2. **Deployment**: Run `terraform apply`
3. **Post-deployment**: Verify all endpoints
4. **Monitoring**: Check CloudWatch dashboards

**Your VORTEX AI Engine infrastructure is now ready for production!** 