# SNS Topic for feedback events
resource "aws_sns_topic" "feedback_topic" {
  name = "${var.project_name}-feedback-topic"
}

# SQS Queue for event queuing
resource "aws_sqs_queue" "feedback_queue" {
  name                       = "${var.project_name}-feedback-queue"
  delay_seconds              = 0
  message_retention_seconds  = 86400
  receive_wait_time_seconds  = 10
}

# SQS Subscription to SNS
resource "aws_sns_topic_subscription" "feedback_subscription" {
  topic_arn = aws_sns_topic.feedback_topic.arn
  protocol  = "sqs"
  endpoint  = aws_sqs_queue.feedback_queue.arn
}

# IAM Policy for SQS to SNS
resource "aws_sqs_queue_policy" "feedback_queue_policy" {
  queue_url = aws_sqs_queue.feedback_queue.id
  policy    = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = "*"
      Action = "sqs:SendMessage"
      Resource = aws_sqs_queue.feedback_queue.arn
      Condition = {
        ArnEquals = { "aws:SourceArn" = aws_sns_topic.feedback_topic.arn }
      }
    }]
  })
}

# DynamoDB Table for user memory and feedback
resource "aws_dynamodb_table" "user_memory" {
  name           = "${var.project_name}-user-memory"
  billing_mode   = "PAY_PER_REQUEST"
  hash_key       = "user_id"
  
  attribute {
    name = "user_id"
    type = "S"
  }
  
  # Enable point-in-time recovery
  point_in_time_recovery {
    enabled = true
  }
  
  # Enable server-side encryption
  server_side_encryption {
    enabled = true
  }
  
  # Add tags for resource management
  tags = {
    Name        = "${var.project_name}-user-memory"
    Environment = var.environment
    Project     = var.project_name
  }
}

# S3 Bucket for sanitized outputs
resource "aws_s3_bucket" "public_outputs" {
  bucket = "${var.project_name}-public-outputs"
}

# S3 Bucket versioning
resource "aws_s3_bucket_versioning" "public_outputs_versioning" {
  bucket = aws_s3_bucket.public_outputs.id
  versioning_configuration {
    status = "Enabled"
  }
}

# S3 Bucket encryption
resource "aws_s3_bucket_server_side_encryption_configuration" "public_outputs_encryption" {
  bucket = aws_s3_bucket.public_outputs.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

# S3 Bucket public access block
resource "aws_s3_bucket_public_access_block" "public_outputs_pab" {
  bucket = aws_s3_bucket.public_outputs.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# S3 Bucket lifecycle configuration
resource "aws_s3_bucket_lifecycle_configuration" "public_outputs_lifecycle" {
  bucket = aws_s3_bucket.public_outputs.id

  rule {
    id     = "delete_old_versions"
    status = "Enabled"

    noncurrent_version_expiration {
      noncurrent_days = 30
    }
  }
}

# IAM Role for Lambda
resource "aws_iam_role" "lambda_role" {
  name = "${var.project_name}-lambda-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = { Service = "lambda.amazonaws.com" }
    }]
  })
  
  tags = {
    Name        = "${var.project_name}-lambda-role"
    Environment = var.environment
    Project     = var.project_name
  }
}

# IAM Role for Batch Instance
resource "aws_iam_role" "batch_instance_role" {
  name = "${var.project_name}-batch-instance-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
    }]
  })
  
  tags = {
    Name        = "${var.project_name}-batch-instance-role"
    Environment = var.environment
    Project     = var.project_name
  }
}

# IAM Instance Profile for Batch
resource "aws_iam_instance_profile" "batch_instance_profile" {
  name = "${var.project_name}-batch-instance-profile"
  role = aws_iam_role.batch_instance_role.name
}

# Attach required policies to batch instance role
resource "aws_iam_role_policy_attachment" "batch_instance_role_policy" {
  role       = aws_iam_role.batch_instance_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceforEC2Role"
}

# Attach policies to Lambda role (e.g., for SNS, DynamoDB, Batch)
resource "aws_iam_role_policy_attachment" "lambda_basic" {
  role       = aws_iam_role.lambda_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}

resource "aws_iam_role_policy" "lambda_policy" {
  role = aws_iam_role.lambda_role.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "dynamodb:PutItem",
          "dynamodb:UpdateItem",
          "dynamodb:Query",
          "dynamodb:GetItem"
        ]
        Resource = [
          aws_dynamodb_table.user_memory.arn,
          "${aws_dynamodb_table.user_memory.arn}/*"
        ]
      },
      {
        Effect = "Allow"
        Action = [
          "batch:SubmitJob",
          "batch:DescribeJobs"
        ]
        Resource = [
          aws_batch_job_queue.trainer_queue.arn,
          aws_batch_job_definition.trainer_job.arn
        ]
      },
      {
        Effect = "Allow"
        Action = [
          "sns:Publish"
        ]
        Resource = aws_sns_topic.feedback_topic.arn
      },
      {
        Effect = "Allow"
        Action = [
          "logs:CreateLogGroup",
          "logs:CreateLogStream",
          "logs:PutLogEvents"
        ]
        Resource = "arn:aws:logs:*:*:*"
      },
      {
        Effect = "Allow"
        Action = [
          "s3:GetObject",
          "s3:PutObject"
        ]
        Resource = [
          "${aws_s3_bucket.public_outputs.arn}/*"
        ]
      }
    ]
  })
}

# Lambda Function for continuous trainer
resource "aws_lambda_function" "continuous_trainer" {
  filename         = "lambda/continuous_trainer.zip"  # Assume zipped code
  function_name    = "${var.project_name}-continuous-trainer"
  role             = aws_iam_role.lambda_role.arn
  handler          = "continuous_trainer.handler"
  runtime          = "python3.9"
  source_code_hash = filebase64sha256("lambda/continuous_trainer.zip")
}

# Security Group for Batch Compute Environment
resource "aws_security_group" "batch_compute_sg" {
  count       = length(var.security_group_ids) == 0 ? 1 : 0
  name        = "${var.project_name}-batch-compute-sg"
  description = "Security group for batch compute environment"
  vpc_id      = var.vpc_id

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = var.allowed_cidr_blocks
  }

  tags = {
    Name        = "${var.project_name}-batch-compute-sg"
    Environment = var.environment
    Project     = var.project_name
  }
}

# Batch Compute Environment
resource "aws_batch_compute_environment" "trainer_env" {
  compute_environment_name = "${var.project_name}-trainer-env"
  type                     = "MANAGED"
  state                    = "ENABLED"
  
  compute_resources {
    max_vcpus      = 16
    min_vcpus      = 0
    desired_vcpus  = 2
    instance_type  = ["m5.large", "m5.xlarge"]
    subnets        = length(var.subnet_ids) > 0 ? var.subnet_ids : []
    security_group_ids = length(var.security_group_ids) > 0 ? var.security_group_ids : [aws_security_group.batch_compute_sg[0].id]
    type           = "EC2"
    
    # Use spot instances for cost optimization
    allocation_strategy = "SPOT_CAPACITY_OPTIMIZED"
    bid_percentage      = 50
    
    # Add instance role for security
    instance_role = aws_iam_instance_profile.batch_instance_profile.arn
    
    tags = {
      Name        = "${var.project_name}-batch-compute"
      Environment = var.environment
      Project     = var.project_name
    }
  }

  depends_on = [
    aws_iam_role_policy_attachment.batch_instance_role_policy
  ]
}

# Batch Job Queue
resource "aws_batch_job_queue" "trainer_queue" {
  name     = "${var.project_name}-trainer-queue"
  state    = "ENABLED"
  priority = 1
  compute_environments = [aws_batch_compute_environment.trainer_env.arn]
}

# Batch Job Definition
resource "aws_batch_job_definition" "trainer_job" {
  name = "${var.project_name}-trainer-job"
  type = "container"
  container_properties = jsonencode({
    image  = "${var.account_id}.dkr.ecr.${var.aws_region}.amazonaws.com/vortex-trainer:latest"  // Replace with your ECR repo
    vcpus  = 2
    memory = 4096
  })
}

# CloudWatch Dashboard for metrics
resource "aws_cloudwatch_dashboard" "vortex_dashboard" {
  dashboard_name = "${var.project_name}-metrics"
  dashboard_body = jsonencode({
    widgets = [{
      type   = "metric"
      x      = 0
      y      = 0
      width  = 12
      height = 6
      properties = {
        metrics = [ [ "AWS/Lambda", "Invocations", "FunctionName", aws_lambda_function.continuous_trainer.function_name ] ]
        view    = "timeSeries"
        stacked = false
        region  = var.aws_region
        title   = "Lambda Invocations"
      }
    }]
  })
} 