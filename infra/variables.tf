variable "project_name" {
  description = "The name of the project"
  type        = string
  default     = "vortex-ai-engine"
}

variable "environment" {
  description = "The deployment environment"
  type        = string
  default     = "production"
}

variable "aws_region" {
  description = "AWS region for resources"
  type        = string
  default     = "us-east-1"
}

variable "aws_profile" {
  description = "AWS profile to use"
  type        = string
  default     = "default"
}

variable "account_id" {
  description = "AWS account ID"
  type        = string
  default     = ""
}

variable "subnet_ids" {
  description = "List of subnet IDs for batch compute environment"
  type        = list(string)
  default     = []
}

variable "security_group_ids" {
  description = "List of security group IDs for batch compute environment"
  type        = list(string)
  default     = []
}

variable "vpc_id" {
  description = "VPC ID for resources"
  type        = string
  default     = ""
}

variable "allowed_cidr_blocks" {
  description = "CIDR blocks allowed to access resources"
  type        = list(string)
  default     = ["10.0.0.0/8", "172.16.0.0/12", "192.168.0.0/16"]
}

variable "enable_logging" {
  description = "Enable detailed logging"
  type        = bool
  default     = true
}

variable "backup_retention_days" {
  description = "Number of days to retain backups"
  type        = number
  default     = 30
} 