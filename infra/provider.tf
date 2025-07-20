provider "aws" {
  region     = var.aws_region
  profile    = var.aws_profile  # Optional: Use if not using env vars
}

terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
} 