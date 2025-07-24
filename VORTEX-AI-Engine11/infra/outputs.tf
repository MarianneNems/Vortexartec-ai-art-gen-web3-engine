# =============================================================================
# VORTEX AI Engine - Infrastructure Outputs
# =============================================================================

# =============================================================================
# S3 Bucket Outputs
# =============================================================================

output "s3_bucket_name" {
  description = "Name of the S3 data lake bucket"
  value       = aws_s3_bucket.vortex_data_lake.bucket
}

output "s3_bucket_arn" {
  description = "ARN of the S3 data lake bucket"
  value       = aws_s3_bucket.vortex_data_lake.arn
}

output "s3_bucket_domain_name" {
  description = "Domain name of the S3 bucket"
  value       = aws_s3_bucket.vortex_data_lake.bucket_domain_name
}

output "s3_bucket_regional_domain_name" {
  description = "Regional domain name of the S3 bucket"
  value       = aws_s3_bucket.vortex_data_lake.bucket_regional_domain_name
}

# =============================================================================
# DynamoDB Outputs
# =============================================================================

output "dynamodb_table_name" {
  description = "Name of the DynamoDB user memory table"
  value       = aws_dynamodb_table.vortex_user_memory.name
}

output "dynamodb_table_arn" {
  description = "ARN of the DynamoDB user memory table"
  value       = aws_dynamodb_table.vortex_user_memory.arn
}

output "dynamodb_table_stream_arn" {
  description = "ARN of the DynamoDB table stream"
  value       = aws_dynamodb_table.vortex_user_memory.stream_arn
}

# =============================================================================
# Redis ElastiCache Outputs
# =============================================================================

output "redis_cluster_id" {
  description = "ID of the Redis cluster"
  value       = aws_elasticache_replication_group.vortex_redis.id
}

output "redis_cluster_arn" {
  description = "ARN of the Redis cluster"
  value       = aws_elasticache_replication_group.vortex_redis.arn
}

output "redis_primary_endpoint" {
  description = "Primary endpoint of the Redis cluster"
  value       = aws_elasticache_replication_group.vortex_redis.primary_endpoint_address
}

output "redis_reader_endpoint" {
  description = "Reader endpoint of the Redis cluster"
  value       = aws_elasticache_replication_group.vortex_redis.reader_endpoint_address
}

output "redis_port" {
  description = "Port of the Redis cluster"
  value       = aws_elasticache_replication_group.vortex_redis.port
}

# =============================================================================
# ECS Cluster Outputs
# =============================================================================

output "ecs_cluster_name" {
  description = "Name of the ECS cluster"
  value       = aws_ecs_cluster.vortex.name
}

output "ecs_cluster_arn" {
  description = "ARN of the ECS cluster"
  value       = aws_ecs_cluster.vortex.arn
}

# =============================================================================
# Vault Service Outputs
# =============================================================================

output "vault_service_name" {
  description = "Name of the Vault ECS service"
  value       = aws_ecs_service.vault.name
}

output "vault_service_arn" {
  description = "ARN of the Vault ECS service"
  value       = aws_ecs_service.vault.id
}

output "vault_task_definition_arn" {
  description = "ARN of the Vault task definition"
  value       = aws_ecs_task_definition.vault.arn
}

output "vault_endpoint" {
  description = "HTTPS endpoint for Vault service"
  value       = "https://vault.${var.domain_name}"
}

output "vault_internal_endpoint" {
  description = "Internal endpoint for Vault service"
  value       = "http://${aws_lb.vortex.dns_name}:8200"
}

output "vault_kv_engine_path" {
  description = "Path to the Vault KV v2 secrets engine"
  value       = vault_mount.vortex_secrets.path
}

output "vault_secrets_paths" {
  description = "Paths to all stored algorithm secrets in Vault"
  value = {
    ai_orchestration             = vault_generic_secret.ai_orchestration.path
    base_ai_orchestrator         = vault_generic_secret.base_ai_orchestrator.path
    individual_agent_algorithms  = vault_generic_secret.individual_agent_algorithms.path
    cost_optimization_algorithms = vault_generic_secret.cost_optimization_algorithms.path
    tier_subscription_algorithms = vault_generic_secret.tier_subscription_algorithms.path
    huraii_frontend_algorithms   = vault_generic_secret.huraii_frontend_algorithms.path
    individual_shortcodes_frontend = vault_generic_secret.individual_shortcodes_frontend.path
    ai_chat_algorithms           = vault_generic_secret.ai_chat_algorithms.path
    s3_config                    = vault_generic_secret.s3_config.path
    dynamodb_config              = vault_generic_secret.dynamodb_config.path
    redis_config                 = vault_generic_secret.redis_config.path
    api_config                   = vault_generic_secret.api_config.path
    wordpress_config             = vault_generic_secret.wordpress_config.path
  }
}

output "vault_policies" {
  description = "Names of created Vault policies"
  value = {
    ai_orchestrator_policy = vault_policy.ai_orchestrator_policy.name
    admin_policy          = vault_policy.admin_policy.name
  }
}

output "vault_auth_methods" {
  description = "Vault authentication methods and roles"
  value = {
    aws_auth_path     = vault_auth_backend.aws.path
    aws_auth_role     = vault_aws_auth_backend_role.ai_orchestrator.role
    iam_role_arn      = aws_iam_role.ecs_task.arn
  }
}

# =============================================================================
# AI Orchestrator Service Outputs
# =============================================================================

output "ai_orchestrator_service_name" {
  description = "Name of the AI Orchestrator ECS service"
  value       = aws_ecs_service.ai_orchestrator.name
}

output "ai_orchestrator_service_arn" {
  description = "ARN of the AI Orchestrator ECS service"
  value       = aws_ecs_service.ai_orchestrator.id
}

output "ai_orchestrator_task_definition_arn" {
  description = "ARN of the AI Orchestrator task definition"
  value       = aws_ecs_task_definition.ai_orchestrator.arn
}

output "ai_orchestrator_endpoint" {
  description = "HTTPS endpoint for AI Orchestrator service"
  value       = "https://api.${var.domain_name}"
}

# =============================================================================
# Load Balancer Outputs
# =============================================================================

output "alb_name" {
  description = "Name of the Application Load Balancer"
  value       = aws_lb.vortex.name
}

output "alb_arn" {
  description = "ARN of the Application Load Balancer"
  value       = aws_lb.vortex.arn
}

output "alb_dns_name" {
  description = "DNS name of the Application Load Balancer"
  value       = aws_lb.vortex.dns_name
}

output "alb_zone_id" {
  description = "Zone ID of the Application Load Balancer"
  value       = aws_lb.vortex.zone_id
}

# =============================================================================
# Target Group Outputs
# =============================================================================

output "vault_target_group_arn" {
  description = "ARN of the Vault target group"
  value       = aws_lb_target_group.vault.arn
}

output "ai_orchestrator_target_group_arn" {
  description = "ARN of the AI Orchestrator target group"
  value       = aws_lb_target_group.ai_orchestrator.arn
}

# =============================================================================
# SSL Certificate Outputs
# =============================================================================

output "ssl_certificate_arn" {
  description = "ARN of the SSL certificate"
  value       = aws_acm_certificate.vortex.arn
}

output "ssl_certificate_domain_name" {
  description = "Domain name of the SSL certificate"
  value       = aws_acm_certificate.vortex.domain_name
}

# =============================================================================
# Route53 Outputs
# =============================================================================

output "route53_zone_id" {
  description = "Zone ID of the Route53 hosted zone"
  value       = data.aws_route53_zone.main.zone_id
}

output "main_domain_record" {
  description = "Main domain A record"
  value       = aws_route53_record.vortex_main.name
}

output "vault_domain_record" {
  description = "Vault subdomain A record"
  value       = aws_route53_record.vortex_vault.name
}

output "api_domain_record" {
  description = "API subdomain A record"
  value       = aws_route53_record.vortex_api.name
}

output "www_domain_record" {
  description = "WWW subdomain A record"
  value       = aws_route53_record.vortex_www.name
}

# =============================================================================
# CloudFront Distribution Outputs
# =============================================================================

output "cloudfront_distribution_id" {
  description = "ID of the CloudFront distribution"
  value       = aws_cloudfront_distribution.vortex_www.id
}

output "cloudfront_distribution_arn" {
  description = "ARN of the CloudFront distribution"
  value       = aws_cloudfront_distribution.vortex_www.arn
}

output "cloudfront_distribution_domain_name" {
  description = "Domain name of the CloudFront distribution"
  value       = aws_cloudfront_distribution.vortex_www.domain_name
}

output "cloudfront_distribution_hosted_zone_id" {
  description = "Hosted zone ID of the CloudFront distribution"
  value       = aws_cloudfront_distribution.vortex_www.hosted_zone_id
}

output "www_s3_bucket_name" {
  description = "Name of the S3 bucket for www static content"
  value       = aws_s3_bucket.vortex_www_static.bucket
}

output "www_s3_bucket_arn" {
  description = "ARN of the S3 bucket for www static content"
  value       = aws_s3_bucket.vortex_www_static.arn
}

output "www_endpoint" {
  description = "HTTPS endpoint for www subdomain"
  value       = "https://www.${var.domain_name}"
}

# =============================================================================
# Security Group Outputs
# =============================================================================

output "alb_security_group_id" {
  description = "ID of the ALB security group"
  value       = aws_security_group.alb.id
}

output "ecs_security_group_id" {
  description = "ID of the ECS tasks security group"
  value       = aws_security_group.ecs_tasks.id
}

output "redis_security_group_id" {
  description = "ID of the Redis security group"
  value       = aws_security_group.redis.id
}

# =============================================================================
# IAM Role Outputs
# =============================================================================

output "ecs_task_execution_role_arn" {
  description = "ARN of the ECS task execution role"
  value       = aws_iam_role.ecs_task_execution.arn
}

output "ecs_task_role_arn" {
  description = "ARN of the ECS task role"
  value       = aws_iam_role.ecs_task.arn
}

# =============================================================================
# CloudWatch Outputs
# =============================================================================

output "ecs_log_group_name" {
  description = "Name of the ECS CloudWatch log group"
  value       = aws_cloudwatch_log_group.ecs.name
}

output "vault_log_group_name" {
  description = "Name of the Vault CloudWatch log group"
  value       = aws_cloudwatch_log_group.vault.name
}

output "ecs_log_group_arn" {
  description = "ARN of the ECS CloudWatch log group"
  value       = aws_cloudwatch_log_group.ecs.arn
}

output "vault_log_group_arn" {
  description = "ARN of the Vault CloudWatch log group"
  value       = aws_cloudwatch_log_group.vault.arn
}

# =============================================================================
# Auto Scaling Outputs
# =============================================================================

output "auto_scaling_target_arn" {
  description = "ARN of the auto scaling target"
  value       = aws_appautoscaling_target.ai_orchestrator.arn
}

output "cpu_scaling_policy_arn" {
  description = "ARN of the CPU scaling policy"
  value       = aws_appautoscaling_policy.ai_orchestrator_cpu.arn
}

output "memory_scaling_policy_arn" {
  description = "ARN of the memory scaling policy"
  value       = aws_appautoscaling_policy.ai_orchestrator_memory.arn
}

# =============================================================================
# Environment Configuration Outputs
# =============================================================================

output "environment_variables" {
  description = "Environment variables for the AI Orchestrator"
  value = {
    AWS_REGION         = data.aws_region.current.name
    S3_BUCKET_NAME     = aws_s3_bucket.vortex_data_lake.bucket
    DYNAMODB_TABLE_NAME = aws_dynamodb_table.vortex_user_memory.name
    REDIS_ENDPOINT     = aws_elasticache_replication_group.vortex_redis.primary_endpoint_address
    VAULT_ENDPOINT     = "https://vault.${var.domain_name}"
    ENVIRONMENT        = var.environment
  }
}

# =============================================================================
# Network Configuration Outputs
# =============================================================================

output "vpc_id" {
  description = "ID of the VPC"
  value       = data.aws_vpc.default.id
}

output "subnet_ids" {
  description = "List of subnet IDs"
  value       = data.aws_subnets.default.ids
}

output "vpc_cidr_block" {
  description = "CIDR block of the VPC"
  value       = data.aws_vpc.default.cidr_block
}

# =============================================================================
# Connection Strings and Endpoints Summary
# =============================================================================

output "connection_summary" {
  description = "Summary of all connection endpoints"
  value = {
    vault_https_endpoint    = "https://vault.${var.domain_name}"
    api_https_endpoint      = "https://api.${var.domain_name}"
    main_website_endpoint   = "https://${var.domain_name}"
    www_website_endpoint    = "https://www.${var.domain_name}"
    orchestrator_path       = "https://${var.domain_name}/orchestrator"
    orchestrator_api_path   = "https://api.${var.domain_name}/orchestrator"
    redis_endpoint          = "${aws_elasticache_replication_group.vortex_redis.primary_endpoint_address}:${aws_elasticache_replication_group.vortex_redis.port}"
    s3_bucket_name          = aws_s3_bucket.vortex_data_lake.bucket
    dynamodb_table_name     = aws_dynamodb_table.vortex_user_memory.name
    load_balancer_dns       = aws_lb.vortex.dns_name
    cloudfront_domain       = aws_cloudfront_distribution.vortex_www.domain_name
    vault_kv_engine_path    = vault_mount.vortex_secrets.path
    vault_secrets_count     = 13
    ecr_repository_url      = aws_ecr_repository.vortex_ai_orchestrator.repository_url
  }
}

# =============================================================================
# Resource ARNs Summary
# =============================================================================

output "resource_arns" {
  description = "Summary of all resource ARNs"
  value = {
    s3_bucket_arn                   = aws_s3_bucket.vortex_data_lake.arn
    www_s3_bucket_arn               = aws_s3_bucket.vortex_www_static.arn
    dynamodb_table_arn              = aws_dynamodb_table.vortex_user_memory.arn
    redis_cluster_arn               = aws_elasticache_replication_group.vortex_redis.arn
    cloudfront_distribution_arn     = aws_cloudfront_distribution.vortex_www.arn
    ecs_cluster_arn                 = aws_ecs_cluster.vortex.arn
    vault_service_arn               = aws_ecs_service.vault.id
    ai_orchestrator_service_arn     = aws_ecs_service.ai_orchestrator.id
    vault_task_definition_arn       = aws_ecs_task_definition.vault.arn
    ai_orchestrator_task_definition_arn = aws_ecs_task_definition.ai_orchestrator.arn
    load_balancer_arn               = aws_lb.vortex.arn
    ssl_certificate_arn             = aws_acm_certificate.vortex.arn
    ecs_task_execution_role_arn     = aws_iam_role.ecs_task_execution.arn
    ecs_task_role_arn               = aws_iam_role.ecs_task.arn
    vault_kv_engine_path            = vault_mount.vortex_secrets.path
    vault_ai_orchestrator_policy    = vault_policy.ai_orchestrator_policy.name
    vault_admin_policy              = vault_policy.admin_policy.name
    ecr_repository_arn              = aws_ecr_repository.vortex_ai_orchestrator.arn
    vault_token_secret_arn          = aws_secretsmanager_secret.vault_token.arn
  }
} 