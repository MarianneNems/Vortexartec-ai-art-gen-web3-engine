# =============================================================================
# VORTEX AI Engine - Production Infrastructure
# =============================================================================

terraform {
  required_version = ">= 1.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.1"
    }
    vault = {
      source  = "hashicorp/vault"
      version = "~> 3.0"
    }
    time = {
      source  = "hashicorp/time"
      version = "~> 0.9"
    }
  }
}

# =============================================================================
# Provider Configuration
# =============================================================================

provider "aws" {
  region = var.aws_region
  
  default_tags {
    tags = {
      Project     = "VORTEX-AI-Engine"
      Environment = var.environment
      ManagedBy   = "Terraform"
      Owner       = "VortexArtec"
    }
  }
}

# Vault provider configuration
provider "vault" {
  address = "https://vault.${var.domain_name}"
  token   = var.vault_root_token
  
  # Skip TLS verification for development (enable for production)
  skip_tls_verify = var.vault_skip_tls_verify || var.environment != "prod"
}

# =============================================================================
# Data Sources
# =============================================================================

data "aws_caller_identity" "current" {}
data "aws_region" "current" {}

data "aws_route53_zone" "main" {
  name = var.domain_name
}

data "aws_vpc" "default" {
  default = true
}

data "aws_subnets" "default" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }
}

# =============================================================================
# S3 Bucket (Existing - Import or Reference)
# =============================================================================

resource "aws_s3_bucket" "vortex_data_lake" {
  bucket = var.s3_bucket_name
  
  tags = {
    Name        = "VORTEX AI Data Lake"
    Purpose     = "AI Orchestration Data Storage"
    Integration = "7-Step-Pipeline"
  }
}

resource "aws_s3_bucket_versioning" "vortex_data_lake" {
  bucket = aws_s3_bucket.vortex_data_lake.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_encryption" "vortex_data_lake" {
  bucket = aws_s3_bucket.vortex_data_lake.id
  
  server_side_encryption_configuration {
    rule {
      apply_server_side_encryption_by_default {
        sse_algorithm = "AES256"
      }
    }
  }
}

resource "aws_s3_bucket_public_access_block" "vortex_data_lake" {
  bucket = aws_s3_bucket.vortex_data_lake.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# =============================================================================
# DynamoDB Table for User Memory
# =============================================================================

resource "aws_dynamodb_table" "vortex_user_memory" {
  name           = "vortex_user_memory"
  billing_mode   = "PAY_PER_REQUEST"
  hash_key       = "user_id"
  range_key      = "timestamp"
  stream_enabled = true
  stream_view_type = "NEW_AND_OLD_IMAGES"

  attribute {
    name = "user_id"
    type = "S"
  }

  attribute {
    name = "timestamp"
    type = "S"
  }

  attribute {
    name = "memory_key"
    type = "S"
  }

  global_secondary_index {
    name     = "MemoryKeyIndex"
    hash_key = "user_id"
    range_key = "memory_key"
  }

  point_in_time_recovery {
    enabled = true
  }

  tags = {
    Name    = "VORTEX User Memory"
    Purpose = "Real-time Learning Storage"
  }
}

# =============================================================================
# ElastiCache Redis Cluster
# =============================================================================

resource "aws_elasticache_subnet_group" "vortex_redis" {
  name       = "vortex-redis-subnet-group-${var.environment}"
  subnet_ids = data.aws_subnets.default.ids
}

resource "aws_security_group" "redis" {
  name        = "vortex-redis-${var.environment}"
  description = "Security group for VORTEX Redis cluster"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port   = 6379
    to_port     = 6379
    protocol    = "tcp"
    cidr_blocks = [data.aws_vpc.default.cidr_block]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "VORTEX Redis Security Group"
  }
}

resource "aws_elasticache_replication_group" "vortex_redis" {
  replication_group_id       = "vortex-redis-${var.environment}"
  description                = "VORTEX AI Engine Redis cluster for caching and session management"
  
  node_type                  = "cache.t3.medium"
  port                       = 6379
  parameter_group_name       = "default.redis7"
  
  num_cache_clusters         = 1
  automatic_failover_enabled = false
  multi_az_enabled          = false
  
  subnet_group_name = aws_elasticache_subnet_group.vortex_redis.name
  security_group_ids = [aws_security_group.redis.id]
  
  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  
  tags = {
    Name    = "VORTEX Redis Cluster"
    Purpose = "AI Orchestration Caching"
  }
}

# =============================================================================
# ECR Repository for VORTEX AI Orchestrator
# =============================================================================

resource "aws_ecr_repository" "vortex_ai_orchestrator" {
  name                 = "vortex-ai-orchestrator"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  encryption_configuration {
    encryption_type = "AES256"
  }

  tags = {
    Name = "VORTEX AI Orchestrator Repository"
  }
}

resource "aws_ecr_lifecycle_policy" "vortex_ai_orchestrator" {
  repository = aws_ecr_repository.vortex_ai_orchestrator.name

  policy = jsonencode({
    rules = [
      {
        rulePriority = 1
        selection = {
          tagStatus     = "tagged"
          tagPrefixList = ["v"]
          countType     = "imageCountMoreThan"
          countNumber   = 5
        }
        action = {
          type = "expire"
        }
      },
      {
        rulePriority = 2
        selection = {
          tagStatus   = "untagged"
          countType   = "sinceImagePushed"
          countUnit   = "days"
          countNumber = 7
        }
        action = {
          type = "expire"
        }
      }
    ]
  })
}

# =============================================================================
# Secrets Manager for Vault Token
# =============================================================================

resource "aws_secretsmanager_secret" "vault_token" {
  name                    = "vortex-ai-vault-token-${var.environment}"
  description             = "Vault token for VORTEX AI Orchestrator"
  recovery_window_in_days = 7

  tags = {
    Name = "VORTEX AI Vault Token"
  }
}

resource "aws_secretsmanager_secret_version" "vault_token" {
  secret_id     = aws_secretsmanager_secret.vault_token.id
  secret_string = var.vault_root_token
}

# =============================================================================
# ECS Cluster
# =============================================================================

resource "aws_ecs_cluster" "vortex" {
  name = "vortex-ai-${var.environment}"
  
  configuration {
    execute_command_configuration {
      logging = "OVERRIDE"
      log_configuration {
        cloud_watch_log_group_name = aws_cloudwatch_log_group.ecs.name
      }
    }
  }
  
  tags = {
    Name = "VORTEX AI Cluster"
  }
}

resource "aws_ecs_cluster_capacity_providers" "vortex" {
  cluster_name = aws_ecs_cluster.vortex.name
  capacity_providers = ["FARGATE", "FARGATE_SPOT"]
  
  default_capacity_provider_strategy {
    base              = 1
    weight            = 100
    capacity_provider = "FARGATE"
  }
}

# =============================================================================
# CloudWatch Log Groups
# =============================================================================

resource "aws_cloudwatch_log_group" "ecs" {
  name              = "/aws/ecs/vortex-ai-${var.environment}"
  retention_in_days = 7
  
  tags = {
    Name = "VORTEX ECS Logs"
  }
}

resource "aws_cloudwatch_log_group" "vault" {
  name              = "/aws/ecs/vortex-vault-${var.environment}"
  retention_in_days = 14
  
  tags = {
    Name = "VORTEX Vault Logs"
  }
}

# =============================================================================
# IAM Roles and Policies
# =============================================================================

resource "aws_iam_role" "ecs_task_execution" {
  name = "vortex-ecs-task-execution-${var.environment}"
  
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "ecs_task_execution" {
  role       = aws_iam_role.ecs_task_execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

resource "aws_iam_role" "ecs_task" {
  name = "vortex-ecs-task-${var.environment}"
  
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
      }
    ]
  })
}

resource "aws_iam_role_policy" "ecs_task" {
  name = "vortex-ecs-task-policy-${var.environment}"
  role = aws_iam_role.ecs_task.id
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "s3:GetObject",
          "s3:PutObject",
          "s3:DeleteObject",
          "s3:ListBucket"
        ]
        Resource = [
          aws_s3_bucket.vortex_data_lake.arn,
          "${aws_s3_bucket.vortex_data_lake.arn}/*"
        ]
      },
      {
        Effect = "Allow"
        Action = [
          "dynamodb:GetItem",
          "dynamodb:PutItem",
          "dynamodb:UpdateItem",
          "dynamodb:DeleteItem",
          "dynamodb:Query",
          "dynamodb:Scan"
        ]
        Resource = [
          aws_dynamodb_table.vortex_user_memory.arn,
          "${aws_dynamodb_table.vortex_user_memory.arn}/index/*"
        ]
      },
      {
        Effect = "Allow"
        Action = [
          "elasticache:DescribeReplicationGroups",
          "elasticache:DescribeCacheClusters"
        ]
        Resource = "*"
      },
      {
        Effect = "Allow"
        Action = [
          "secretsmanager:GetSecretValue"
        ]
        Resource = [
          aws_secretsmanager_secret.vault_token.arn
        ]
      }
    ]
  })
}

# =============================================================================
# Application Load Balancer
# =============================================================================

resource "aws_security_group" "alb" {
  name        = "vortex-alb-${var.environment}"
  description = "Security group for VORTEX ALB"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "VORTEX ALB Security Group"
  }
}

resource "aws_lb" "vortex" {
  name               = "vortex-alb-${var.environment}"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = data.aws_subnets.default.ids

  enable_deletion_protection = false

  tags = {
    Name = "VORTEX AI Load Balancer"
  }
}

# =============================================================================
# ACM Certificate
# =============================================================================

resource "aws_acm_certificate" "vortex" {
  domain_name       = var.domain_name
  validation_method = "DNS"

  subject_alternative_names = [
    "*.${var.domain_name}",
    "vault.${var.domain_name}",
    "api.${var.domain_name}"
  ]

  lifecycle {
    create_before_destroy = true
  }

  tags = {
    Name = "VORTEX AI Certificate"
  }
}

resource "aws_route53_record" "cert_validation" {
  for_each = {
    for dvo in aws_acm_certificate.vortex.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.main.zone_id
}

resource "aws_acm_certificate_validation" "vortex" {
  certificate_arn         = aws_acm_certificate.vortex.arn
  validation_record_fqdns = [for record in aws_route53_record.cert_validation : record.fqdn]
}

# =============================================================================
# Route53 Records
# =============================================================================

resource "aws_route53_record" "vortex_main" {
  zone_id = data.aws_route53_zone.main.zone_id
  name    = var.domain_name
  type    = "A"

  alias {
    name                   = aws_lb.vortex.dns_name
    zone_id                = aws_lb.vortex.zone_id
    evaluate_target_health = true
  }
}

resource "aws_route53_record" "vortex_vault" {
  zone_id = data.aws_route53_zone.main.zone_id
  name    = "vault.${var.domain_name}"
  type    = "A"

  alias {
    name                   = aws_lb.vortex.dns_name
    zone_id                = aws_lb.vortex.zone_id
    evaluate_target_health = true
  }
}

resource "aws_route53_record" "vortex_api" {
  zone_id = data.aws_route53_zone.main.zone_id
  name    = "api.${var.domain_name}"
  type    = "A"

  alias {
    name                   = aws_lb.vortex.dns_name
    zone_id                = aws_lb.vortex.zone_id
    evaluate_target_health = true
  }
}

# =============================================================================
# CloudFront Distribution for WWW Subdomain
# =============================================================================

resource "aws_cloudfront_origin_access_control" "vortex_www" {
  name                              = "vortex-www-${var.environment}"
  description                       = "Origin access control for VORTEX www subdomain"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_s3_bucket" "vortex_www_static" {
  bucket = "vortex-www-static-${var.environment}-${random_string.bucket_suffix.result}"
  
  tags = {
    Name        = "VORTEX WWW Static Content"
    Purpose     = "Static website hosting"
    Environment = var.environment
  }
}

resource "random_string" "bucket_suffix" {
  length  = 8
  special = false
  upper   = false
}

resource "aws_s3_bucket_versioning" "vortex_www_static" {
  bucket = aws_s3_bucket.vortex_www_static.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_encryption" "vortex_www_static" {
  bucket = aws_s3_bucket.vortex_www_static.id
  
  server_side_encryption_configuration {
    rule {
      apply_server_side_encryption_by_default {
        sse_algorithm = "AES256"
      }
    }
  }
}

resource "aws_s3_bucket_public_access_block" "vortex_www_static" {
  bucket = aws_s3_bucket.vortex_www_static.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_policy" "vortex_www_static" {
  bucket = aws_s3_bucket.vortex_www_static.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid       = "AllowCloudFrontServicePrincipal"
        Effect    = "Allow"
        Principal = {
          Service = "cloudfront.amazonaws.com"
        }
        Action   = "s3:GetObject"
        Resource = "${aws_s3_bucket.vortex_www_static.arn}/*"
        Condition = {
          StringEquals = {
            "AWS:SourceArn" = aws_cloudfront_distribution.vortex_www.arn
          }
        }
      }
    ]
  })
}

# Default index.html for www subdomain
resource "aws_s3_object" "www_index" {
  bucket       = aws_s3_bucket.vortex_www_static.id
  key          = "index.html"
  content_type = "text/html"
  
  content = <<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VORTEX AI Engine - Welcome</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            max-width: 800px;
            padding: 40px;
        }
        h1 {
            font-size: 3em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        p {
            font-size: 1.2em;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .cta-button {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 15px 30px;
            font-size: 1.1em;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 10px;
        }
        .cta-button:hover {
            background: white;
            color: #667eea;
            transform: translateY(-2px);
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        .feature {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .feature h3 {
            margin-bottom: 10px;
            color: #ffd700;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 VORTEX AI Engine</h1>
        <p>Advanced AI orchestration platform with 7-step pipeline processing, real-time learning, and enterprise-grade scalability.</p>
        
        <a href="https://vortexartec.com" class="cta-button">🏠 Main Platform</a>
        <a href="https://api.vortexartec.com" class="cta-button">🔗 API Access</a>
        <a href="https://vault.vortexartec.com" class="cta-button">🔐 Vault Portal</a>
        
        <div class="features">
            <div class="feature">
                <h3>🎯 AI Orchestration</h3>
                <p>7-step pipeline with ColossalAI integration and real-time processing</p>
            </div>
            <div class="feature">
                <h3>📊 Cost Optimization</h3>
                <p>80% profit margin enforcement with intelligent resource allocation</p>
            </div>
            <div class="feature">
                <h3>🔄 Continuous Learning</h3>
                <p>Real-time model updates and performance optimization</p>
            </div>
            <div class="feature">
                <h3>🛡️ Enterprise Security</h3>
                <p>Vault-based secrets management and encrypted data flows</p>
            </div>
        </div>
    </div>
</body>
</html>
EOF
  
  tags = {
    Name = "VORTEX WWW Index Page"
  }
}

resource "aws_cloudfront_distribution" "vortex_www" {
  origin {
    domain_name              = aws_s3_bucket.vortex_www_static.bucket_regional_domain_name
    origin_access_control_id = aws_cloudfront_origin_access_control.vortex_www.id
    origin_id                = "S3-${aws_s3_bucket.vortex_www_static.bucket}"
  }

  enabled             = true
  is_ipv6_enabled     = true
  default_root_object = "index.html"
  
  aliases = ["www.${var.domain_name}"]

  default_cache_behavior {
    allowed_methods  = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cached_methods   = ["GET", "HEAD"]
    target_origin_id = "S3-${aws_s3_bucket.vortex_www_static.bucket}"

    forwarded_values {
      query_string = false
      cookies {
        forward = "none"
      }
    }

    viewer_protocol_policy = "redirect-to-https"
    min_ttl                = 0
    default_ttl            = 3600
    max_ttl                = 86400
    compress               = true
  }

  custom_error_response {
    error_code         = 404
    response_code      = 200
    response_page_path = "/index.html"
  }

  custom_error_response {
    error_code         = 403
    response_code      = 200
    response_page_path = "/index.html"
  }

  price_class = "PriceClass_100"

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    acm_certificate_arn      = aws_acm_certificate.vortex.arn
    ssl_support_method       = "sni-only"
    minimum_protocol_version = "TLSv1.2_2021"
  }

  tags = {
    Name        = "VORTEX WWW CloudFront"
    Environment = var.environment
  }
  
  depends_on = [aws_acm_certificate_validation.vortex]
}

resource "aws_route53_record" "vortex_www" {
  zone_id = data.aws_route53_zone.main.zone_id
  name    = "www.${var.domain_name}"
  type    = "A"

  alias {
    name                   = aws_cloudfront_distribution.vortex_www.domain_name
    zone_id                = aws_cloudfront_distribution.vortex_www.hosted_zone_id
    evaluate_target_health = false
  }
}

# =============================================================================
# ECS Security Group
# =============================================================================

resource "aws_security_group" "ecs_tasks" {
  name        = "vortex-ecs-tasks-${var.environment}"
  description = "Security group for VORTEX ECS tasks"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port       = 8200
    to_port         = 8200
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  ingress {
    from_port       = 3000
    to_port         = 3000
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "VORTEX ECS Tasks Security Group"
  }
}

# =============================================================================
# Vault ECS Service
# =============================================================================

resource "aws_ecs_task_definition" "vault" {
  family                   = "vortex-vault-${var.environment}"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = 512
  memory                   = 1024
  execution_role_arn       = aws_iam_role.ecs_task_execution.arn
  task_role_arn           = aws_iam_role.ecs_task.arn

  container_definitions = jsonencode([
    {
      name  = "vault"
      image = "vault:1.15.4"
      
      portMappings = [
        {
          containerPort = 8200
          protocol      = "tcp"
        }
      ]
      
      environment = [
        {
          name  = "VAULT_DEV_ROOT_TOKEN_ID"
          value = "myroot"
        },
        {
          name  = "VAULT_DEV_LISTEN_ADDRESS"
          value = "0.0.0.0:8200"
        }
      ]
      
      command = ["vault", "server", "-dev", "-dev-listen-address=0.0.0.0:8200"]
      
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.vault.name
          awslogs-region        = data.aws_region.current.name
          awslogs-stream-prefix = "ecs"
        }
      }
      
      healthCheck = {
        command = ["CMD-SHELL", "wget --no-verbose --tries=1 --spider http://localhost:8200/v1/sys/health || exit 1"]
        interval = 30
        timeout = 5
        retries = 3
      }
    }
  ])
}

resource "aws_ecs_service" "vault" {
  name            = "vortex-vault-${var.environment}"
  cluster         = aws_ecs_cluster.vortex.id
  task_definition = aws_ecs_task_definition.vault.arn
  desired_count   = 1
  
  capacity_provider_strategy {
    capacity_provider = "FARGATE"
    weight           = 100
  }
  
  network_configuration {
    subnets          = data.aws_subnets.default.ids
    security_groups  = [aws_security_group.ecs_tasks.id]
    assign_public_ip = true
  }
  
  load_balancer {
    target_group_arn = aws_lb_target_group.vault.arn
    container_name   = "vault"
    container_port   = 8200
  }
  
  depends_on = [aws_lb_listener.vault]
}

# =============================================================================
# Vault KV v2 Secrets Engine and Proprietary Algorithm Storage
# =============================================================================

# Wait for Vault service to be ready before configuring secrets
resource "time_sleep" "wait_for_vault" {
  depends_on = [aws_ecs_service.vault]
  create_duration = "60s"
}

# Enable KV v2 secrets engine
resource "vault_mount" "vortex_secrets" {
  depends_on = [time_sleep.wait_for_vault]
  
  path        = "vortex-secrets"
  type        = "kv"
  options     = { version = "2" }
  description = "VORTEX AI Engine proprietary algorithms and secrets"
}

# =============================================================================
# Proprietary Algorithm Secrets
# =============================================================================

# AI Orchestration Algorithm
resource "vault_generic_secret" "ai_orchestration" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/ai_orchestration"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/ai_orchestration.php")
    filename = "ai_orchestration.php"
    type = "php"
    description = "Main AI orchestration algorithm for 7-step pipeline"
    version = "3.0"
    created_at = timestamp()
  })
}

# Base AI Orchestrator
resource "vault_generic_secret" "base_ai_orchestrator" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/base_ai_orchestrator"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/base_ai_orchestrator.php")
    filename = "base_ai_orchestrator.php"
    type = "php"
    description = "Base orchestrator class for AI operations"
    version = "3.0"
    created_at = timestamp()
  })
}

# Individual Agent Algorithms
resource "vault_generic_secret" "individual_agent_algorithms" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/individual_agent_algorithms"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/individual_agent_algorithms.php")
    filename = "individual_agent_algorithms.php"
    type = "php"
    description = "Individual agent algorithms for CLOE, Horace, and Archer"
    version = "3.0"
    created_at = timestamp()
  })
}

# Cost Optimization Algorithms
resource "vault_generic_secret" "cost_optimization_algorithms" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/cost_optimization_algorithms"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/cost_optimization_algorithms.php")
    filename = "cost_optimization_algorithms.php"
    type = "php"
    description = "Cost optimization algorithms for 80% profit margin enforcement"
    version = "3.0"
    created_at = timestamp()
  })
}

# Tier Subscription Algorithms
resource "vault_generic_secret" "tier_subscription_algorithms" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/tier_subscription_algorithms"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/tier_subscription_algorithms.php")
    filename = "tier_subscription_algorithms.php"
    type = "php"
    description = "Tier-based subscription algorithms for Basic/Premium/Enterprise"
    version = "3.0"
    created_at = timestamp()
  })
}

# =============================================================================
# Frontend Algorithm Secrets
# =============================================================================

# HURAII Frontend Algorithms
resource "vault_generic_secret" "huraii_frontend_algorithms" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/huraii_frontend_algorithms"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/huraii_frontend_algorithms.js")
    filename = "huraii_frontend_algorithms.js"
    type = "javascript"
    description = "Frontend algorithms for HURAII dashboard with 13 tabs"
    version = "3.0"
    created_at = timestamp()
  })
}

# Individual Shortcodes Frontend
resource "vault_generic_secret" "individual_shortcodes_frontend" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/individual_shortcodes_frontend"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/individual_shortcodes_frontend.js")
    filename = "individual_shortcodes_frontend.js"
    type = "javascript"
    description = "Frontend algorithms for individual shortcode handlers"
    version = "3.0"
    created_at = timestamp()
  })
}

# AI Chat Algorithms
resource "vault_generic_secret" "ai_chat_algorithms" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/algorithms/ai_chat_algorithms"
  
  data_json = jsonencode({
    content = file("${path.module}/../vault-secrets/algorithms/ai_chat_algorithms.js")
    filename = "ai_chat_algorithms.js"
    type = "javascript"
    description = "AI chat algorithms for real-time user interaction"
    version = "3.0"
    created_at = timestamp()
  })
}

# =============================================================================
# Vault Secret Policies
# =============================================================================

# Policy for AI Orchestrator to read algorithms
resource "vault_policy" "ai_orchestrator_policy" {
  depends_on = [vault_mount.vortex_secrets]
  
  name = "ai-orchestrator-policy"
  
  policy = <<EOT
# Allow reading all algorithm secrets
path "vortex-secrets/data/algorithms/*" {
  capabilities = ["read"]
}

# Allow listing algorithm secrets
path "vortex-secrets/metadata/algorithms/*" {
  capabilities = ["list", "read"]
}

# Allow reading metadata
path "vortex-secrets/metadata" {
  capabilities = ["list"]
}
EOT
}

# Policy for admin access to all secrets
resource "vault_policy" "admin_policy" {
  depends_on = [vault_mount.vortex_secrets]
  
  name = "vortex-admin-policy"
  
  policy = <<EOT
# Full access to all VORTEX secrets
path "vortex-secrets/*" {
  capabilities = ["create", "read", "update", "delete", "list"]
}

# System policy access
path "sys/*" {
  capabilities = ["read", "list"]
}
EOT
}

# =============================================================================
# Vault Authentication Methods
# =============================================================================

# Enable AWS auth method for ECS tasks
resource "vault_auth_backend" "aws" {
  depends_on = [vault_mount.vortex_secrets]
  
  type = "aws"
  path = "aws"
  
  description = "AWS auth method for ECS tasks"
}

# Configure AWS auth method
resource "vault_aws_auth_backend_config" "aws" {
  depends_on = [vault_auth_backend.aws]
  
  backend    = vault_auth_backend.aws.path
  access_key = null  # Will use IAM role
  secret_key = null  # Will use IAM role
  region     = var.aws_region
  
  # Use IAM role for authentication
  iam_server_id_header_value = "vault.${var.domain_name}"
}

# Create AWS auth role for AI Orchestrator
resource "vault_aws_auth_backend_role" "ai_orchestrator" {
  depends_on = [vault_aws_auth_backend_config.aws]
  
  backend                         = vault_auth_backend.aws.path
  role                           = "ai-orchestrator-role"
  auth_type                      = "iam"
  bound_iam_principal_arns       = [aws_iam_role.ecs_task.arn]
  token_ttl                      = 600
  token_max_ttl                  = 1200
  token_policies                 = [vault_policy.ai_orchestrator_policy.name]
  resolve_aws_unique_ids         = true
}

# =============================================================================
# AI Orchestrator ECS Service
# =============================================================================

resource "aws_ecs_task_definition" "ai_orchestrator" {
  family                   = "vortex-ai-orchestrator-${var.environment}"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = 1024
  memory                   = 2048
  execution_role_arn       = aws_iam_role.ecs_task_execution.arn
  task_role_arn           = aws_iam_role.ecs_task.arn

  container_definitions = jsonencode([
    {
      name  = "ai-orchestrator"
      image = "${aws_ecr_repository.vortex_ai_orchestrator.repository_url}:latest"
      
      portMappings = [
        {
          containerPort = 3000
          protocol      = "tcp"
        }
      ]
      
      environment = [
        {
          name  = "AWS_REGION"
          value = data.aws_region.current.name
        },
        {
          name  = "S3_BUCKET_NAME"
          value = aws_s3_bucket.vortex_data_lake.bucket
        },
        {
          name  = "DYNAMODB_TABLE_NAME"
          value = aws_dynamodb_table.vortex_user_memory.name
        },
        {
          name  = "REDIS_ENDPOINT"
          value = aws_elasticache_replication_group.vortex_redis.primary_endpoint_address
        },
        {
          name  = "VAULT_ADDR"
          value = "https://vault.${var.domain_name}"
        },
        {
          name  = "VAULT_ENDPOINT"
          value = "https://vault.${var.domain_name}"
        },
        {
          name  = "VAULT_AUTH_PATH"
          value = "aws"
        },
        {
          name  = "VAULT_AUTH_ROLE"
          value = "ai-orchestrator-role"
        },
        {
          name  = "VAULT_KV_PATH"
          value = "vortex-secrets"
        },
        {
          name  = "ENVIRONMENT"
          value = var.environment
        },
        {
          name  = "ORCHESTRATION_STEPS"
          value = "7"
        },
        {
          name  = "PROFIT_MARGIN"
          value = "0.80"
        }
      ]
      
      secrets = [
        {
          name      = "VAULT_TOKEN"
          valueFrom = aws_secretsmanager_secret.vault_token.arn
        }
      ]
      
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.ecs.name
          awslogs-region        = data.aws_region.current.name
          awslogs-stream-prefix = "ecs"
        }
      }
      
      healthCheck = {
        command = ["CMD-SHELL", "curl -f http://localhost:3000/health || exit 1"]
        interval = 30
        timeout = 5
        retries = 3
      }
    }
  ])
}

resource "aws_ecs_service" "ai_orchestrator" {
  name            = "vortex-ai-orchestrator-${var.environment}"
  cluster         = aws_ecs_cluster.vortex.id
  task_definition = aws_ecs_task_definition.ai_orchestrator.arn
  desired_count   = 2
  
  capacity_provider_strategy {
    capacity_provider = "FARGATE"
    weight           = 100
  }
  
  network_configuration {
    subnets          = data.aws_subnets.default.ids
    security_groups  = [aws_security_group.ecs_tasks.id]
    assign_public_ip = true
  }
  
  load_balancer {
    target_group_arn = aws_lb_target_group.ai_orchestrator.arn
    container_name   = "ai-orchestrator"
    container_port   = 3000
  }
  
  depends_on = [aws_lb_listener.ai_orchestrator]
}

# =============================================================================
# Auto Scaling for AI Orchestrator
# =============================================================================

resource "aws_appautoscaling_target" "ai_orchestrator" {
  max_capacity       = 10
  min_capacity       = 2
  resource_id        = "service/${aws_ecs_cluster.vortex.name}/${aws_ecs_service.ai_orchestrator.name}"
  scalable_dimension = "ecs:service:DesiredCount"
  service_namespace  = "ecs"
}

resource "aws_appautoscaling_policy" "ai_orchestrator_cpu" {
  name               = "vortex-ai-orchestrator-cpu-${var.environment}"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.ai_orchestrator.resource_id
  scalable_dimension = aws_appautoscaling_target.ai_orchestrator.scalable_dimension
  service_namespace  = aws_appautoscaling_target.ai_orchestrator.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageCPUUtilization"
    }
    target_value = 60.0
  }
}

resource "aws_appautoscaling_policy" "ai_orchestrator_memory" {
  name               = "vortex-ai-orchestrator-memory-${var.environment}"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.ai_orchestrator.resource_id
  scalable_dimension = aws_appautoscaling_target.ai_orchestrator.scalable_dimension
  service_namespace  = aws_appautoscaling_target.ai_orchestrator.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageMemoryUtilization"
    }
    target_value = 80.0
  }
}

# =============================================================================
# Load Balancer Target Groups
# =============================================================================

resource "aws_lb_target_group" "vault" {
  name     = "vortex-vault-${var.environment}"
  port     = 8200
  protocol = "HTTP"
  vpc_id   = data.aws_vpc.default.id
  target_type = "ip"
  
  health_check {
    enabled             = true
    healthy_threshold   = 2
    unhealthy_threshold = 2
    timeout             = 5
    interval            = 30
    path                = "/v1/sys/health"
    matcher             = "200"
    protocol            = "HTTP"
  }
  
  tags = {
    Name = "VORTEX Vault Target Group"
  }
}

resource "aws_lb_target_group" "ai_orchestrator" {
  name     = "vortex-ai-orchestrator-${var.environment}"
  port     = 3000
  protocol = "HTTP"
  vpc_id   = data.aws_vpc.default.id
  target_type = "ip"
  
  health_check {
    enabled             = true
    healthy_threshold   = 2
    unhealthy_threshold = 2
    timeout             = 5
    interval            = 30
    path                = "/health"
    matcher             = "200"
    protocol            = "HTTP"
  }
  
  tags = {
    Name = "VORTEX AI Orchestrator Target Group"
  }
}

# =============================================================================
# Load Balancer Listeners
# =============================================================================

resource "aws_lb_listener" "vault" {
  load_balancer_arn = aws_lb.vortex.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-2017-01"
  certificate_arn   = aws_acm_certificate_validation.vortex.certificate_arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.vault.arn
  }
  
  condition {
    host_header {
      values = ["vault.${var.domain_name}"]
    }
  }
}

resource "aws_lb_listener" "ai_orchestrator" {
  load_balancer_arn = aws_lb.vortex.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-2017-01"
  certificate_arn   = aws_acm_certificate_validation.vortex.certificate_arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ai_orchestrator.arn
  }
  
  condition {
    host_header {
      values = ["api.${var.domain_name}"]
    }
  }
}

# ALB Listener Rule for /orchestrator path
resource "aws_lb_listener_rule" "orchestrator_path" {
  listener_arn = aws_lb_listener.ai_orchestrator.arn
  priority     = 100

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ai_orchestrator.arn
  }

  condition {
    path_pattern {
      values = ["/orchestrator*"]
    }
  }

  condition {
    host_header {
      values = ["api.${var.domain_name}"]
    }
  }
}

# ALB Listener Rule for main domain /orchestrator path
resource "aws_lb_listener_rule" "orchestrator_main_path" {
  listener_arn = aws_lb_listener.vault.arn
  priority     = 100

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ai_orchestrator.arn
  }

  condition {
    path_pattern {
      values = ["/orchestrator*"]
    }
  }

  condition {
    host_header {
      values = ["${var.domain_name}"]
    }
  }
}

resource "aws_lb_listener" "redirect_http" {
  load_balancer_arn = aws_lb.vortex.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type = "redirect"
    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
} 

# =============================================================================
# Additional Vault Secrets for Configuration
# =============================================================================

# S3 Configuration Secret
resource "vault_generic_secret" "s3_config" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/config/s3"
  
  data_json = jsonencode({
    bucket_name    = var.s3_bucket_name
    region         = var.aws_region
    endpoint       = "https://s3.${var.aws_region}.amazonaws.com"
    created_at     = timestamp()
    environment    = var.environment
  })
}

# DynamoDB Configuration Secret
resource "vault_generic_secret" "dynamodb_config" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/config/dynamodb"
  
  data_json = jsonencode({
    table_name     = aws_dynamodb_table.vortex_user_memory.name
    region         = var.aws_region
    endpoint       = "https://dynamodb.${var.aws_region}.amazonaws.com"
    created_at     = timestamp()
    environment    = var.environment
  })
}

# Redis Configuration Secret
resource "vault_generic_secret" "redis_config" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/config/redis"
  
  data_json = jsonencode({
    primary_endpoint = aws_elasticache_replication_group.vortex_redis.primary_endpoint_address
    reader_endpoint  = aws_elasticache_replication_group.vortex_redis.reader_endpoint_address
    port            = aws_elasticache_replication_group.vortex_redis.port
    created_at      = timestamp()
    environment     = var.environment
  })
}

# API Configuration Secret
resource "vault_generic_secret" "api_config" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/config/api"
  
  data_json = jsonencode({
    vault_endpoint    = "https://vault.${var.domain_name}"
    api_endpoint      = "https://api.${var.domain_name}"
    main_endpoint     = "https://${var.domain_name}"
    environment       = var.environment
    version           = "3.0"
    created_at        = timestamp()
  })
}

# WordPress Integration Secret
resource "vault_generic_secret" "wordpress_config" {
  depends_on = [vault_mount.vortex_secrets]
  
  path = "vortex-secrets/config/wordpress"
  
  data_json = jsonencode({
    plugin_version    = "3.0"
    shortcodes_count  = 13
    orchestration_steps = 7
    profit_margin     = 0.80
    environment       = var.environment
    created_at        = timestamp()
  })
} 