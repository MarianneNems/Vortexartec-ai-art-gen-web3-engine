# =============================================================================
# VORTEX AI Engine - Infrastructure Variables
# =============================================================================

variable "aws_region" {
  description = "AWS region for resources"
  type        = string
  default     = "us-east-1"
}

variable "environment" {
  description = "Environment name (dev, staging, prod)"
  type        = string
  default     = "prod"
}

variable "domain_name" {
  description = "Domain name for the application"
  type        = string
  default     = "vortexartec.com"
}

variable "s3_bucket_name" {
  description = "Name of the existing S3 bucket for data lake"
  type        = string
  default     = "vortex-ai-data-lake-1752530342"
}

variable "redis_node_type" {
  description = "Node type for Redis cluster"
  type        = string
  default     = "cache.t3.micro"
}

# =============================================================================
# ECS Configuration
# =============================================================================

variable "vault_cpu" {
  description = "CPU units for Vault service"
  type        = number
  default     = 512
}

variable "vault_memory" {
  description = "Memory for Vault service"
  type        = number
  default     = 1024
}

variable "orchestrator_cpu" {
  description = "CPU units for AI Orchestrator service"
  type        = number
  default     = 1024
}

variable "orchestrator_memory" {
  description = "Memory for AI Orchestrator service"
  type        = number
  default     = 2048
}

variable "orchestrator_min_capacity" {
  description = "Minimum number of AI Orchestrator tasks"
  type        = number
  default     = 2
}

variable "orchestrator_max_capacity" {
  description = "Maximum number of AI Orchestrator tasks"
  type        = number
  default     = 10
}

# =============================================================================
# Auto Scaling Configuration
# =============================================================================

variable "cpu_target_value" {
  description = "Target CPU utilization for auto scaling"
  type        = number
  default     = 70.0
}

variable "memory_target_value" {
  description = "Target memory utilization for auto scaling"
  type        = number
  default     = 80.0
}

# =============================================================================
# Tags Configuration
# =============================================================================

variable "common_tags" {
  description = "Common tags for all resources"
  type        = map(string)
  default = {
    Project     = "VORTEX-AI-Engine"
    Owner       = "VortexArtec"
    ManagedBy   = "Terraform"
    Cost-Center = "AI-Operations"
  }
}

# =============================================================================
# Security Configuration
# =============================================================================

variable "allowed_cidr_blocks" {
  description = "CIDR blocks allowed to access the application"
  type        = list(string)
  default     = ["0.0.0.0/0"]
}

variable "vault_root_token" {
  description = "Root token for Vault (use AWS Secrets Manager in production)"
  type        = string
  default     = "myroot"
  sensitive   = true
}

variable "vault_addr" {
  description = "Vault server address"
  type        = string
  default     = ""
}

variable "vault_token" {
  description = "Vault authentication token"
  type        = string
  default     = ""
  sensitive   = true
}

variable "vault_skip_tls_verify" {
  description = "Skip TLS verification for Vault (development only)"
  type        = bool
  default     = false
}

variable "vault_algorithm_secrets" {
  description = "List of algorithm files to store in Vault"
  type        = list(object({
    name        = string
    path        = string
    type        = string
    description = string
  }))
  default = [
    {
      name        = "ai_orchestration"
      path        = "ai_orchestration.php"
      type        = "php"
      description = "Main AI orchestration algorithm for 7-step pipeline"
    },
    {
      name        = "base_ai_orchestrator"
      path        = "base_ai_orchestrator.php"
      type        = "php"
      description = "Base orchestrator class for AI operations"
    },
    {
      name        = "individual_agent_algorithms"
      path        = "individual_agent_algorithms.php"
      type        = "php"
      description = "Individual agent algorithms for CLOE, Horace, and Archer"
    },
    {
      name        = "cost_optimization_algorithms"
      path        = "cost_optimization_algorithms.php"
      type        = "php"
      description = "Cost optimization algorithms for 80% profit margin enforcement"
    },
    {
      name        = "tier_subscription_algorithms"
      path        = "tier_subscription_algorithms.php"
      type        = "php"
      description = "Tier-based subscription algorithms for Basic/Premium/Enterprise"
    },
    {
      name        = "huraii_frontend_algorithms"
      path        = "huraii_frontend_algorithms.js"
      type        = "javascript"
      description = "Frontend algorithms for HURAII dashboard with 13 tabs"
    },
    {
      name        = "individual_shortcodes_frontend"
      path        = "individual_shortcodes_frontend.js"
      type        = "javascript"
      description = "Frontend algorithms for individual shortcode handlers"
    },
    {
      name        = "ai_chat_algorithms"
      path        = "ai_chat_algorithms.js"
      type        = "javascript"
      description = "AI chat algorithms for real-time user interaction"
    }
  ]
}

# =============================================================================
# Database Configuration
# =============================================================================

variable "dynamodb_billing_mode" {
  description = "Billing mode for DynamoDB table"
  type        = string
  default     = "PAY_PER_REQUEST"
}

variable "enable_dynamodb_point_in_time_recovery" {
  description = "Enable point in time recovery for DynamoDB"
  type        = bool
  default     = true
}

# =============================================================================
# Redis Configuration
# =============================================================================

variable "redis_num_cache_clusters" {
  description = "Number of cache clusters for Redis"
  type        = number
  default     = 2
}

variable "redis_automatic_failover_enabled" {
  description = "Enable automatic failover for Redis"
  type        = bool
  default     = true
}

variable "redis_multi_az_enabled" {
  description = "Enable Multi-AZ for Redis"
  type        = bool
  default     = true
}

# =============================================================================
# Monitoring Configuration
# =============================================================================

variable "cloudwatch_log_retention_days" {
  description = "CloudWatch log retention in days"
  type        = number
  default     = 7
}

variable "vault_log_retention_days" {
  description = "Vault log retention in days"
  type        = number
  default     = 14
}

# =============================================================================
# Load Balancer Configuration
# =============================================================================

variable "enable_deletion_protection" {
  description = "Enable deletion protection for load balancer"
  type        = bool
  default     = false
}

variable "ssl_policy" {
  description = "SSL policy for HTTPS listeners"
  type        = string
  default     = "ELBSecurityPolicy-TLS-1-2-2017-01"
}

# =============================================================================
# Container Images
# =============================================================================

variable "vault_image" {
  description = "Docker image for Vault"
  type        = string
  default     = "vault:1.15.4"
}

variable "ai_orchestrator_image" {
  description = "Docker image for AI Orchestrator"
  type        = string
  default     = "vortexartec/ai-orchestrator:latest"
}

# =============================================================================
# Health Check Configuration
# =============================================================================

variable "health_check_interval" {
  description = "Health check interval in seconds"
  type        = number
  default     = 30
}

variable "health_check_timeout" {
  description = "Health check timeout in seconds"
  type        = number
  default     = 5
}

variable "health_check_healthy_threshold" {
  description = "Healthy threshold for health checks"
  type        = number
  default     = 2
}

variable "health_check_unhealthy_threshold" {
  description = "Unhealthy threshold for health checks"
  type        = number
  default     = 2
} 