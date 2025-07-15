# =============================================================================
# VORTEX AI Engine - Terraform Providers Configuration
# =============================================================================

terraform {
  required_version = ">= 1.6.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    vault = {
      source  = "hashicorp/vault"
      version = "~> 3.20"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.4"
    }
    time = {
      source  = "hashicorp/time"
      version = "~> 0.9"
    }
  }
}

# =============================================================================
# AWS Provider Configuration
# =============================================================================

provider "aws" {
  region = var.aws_region
  
  default_tags {
    tags = {
      Project     = "VORTEX-AI-Engine"
      Environment = var.environment
      ManagedBy   = "Terraform"
      Owner       = "VortexArtec"
      Repository  = "vortex-ai-engine"
      Version     = "3.0"
    }
  }
}

# =============================================================================
# Vault Provider Configuration
# =============================================================================

provider "vault" {
  address = var.vault_addr
  token   = var.vault_token
  
  # Skip TLS verification for development (enable for production)
  skip_tls_verify = var.vault_skip_tls_verify
}

# =============================================================================
# Random Provider Configuration
# =============================================================================

provider "random" {
  # No additional configuration needed
}

# =============================================================================
# Time Provider Configuration
# =============================================================================

provider "time" {
  # No additional configuration needed
} 