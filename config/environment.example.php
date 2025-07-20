<?php
/**
 * VORTEX AI Engine - Environment Configuration Example
 * Copy this file to environment.php and fill in your actual values
 * 
 * This file should be placed outside the web root or protected by server configuration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// AI Provider API Keys
// =============================================================================
define('OPENAI_API_KEY', 'your-openai-api-key-here');
define('OPENAI_ORG_ID', 'your-openai-organization-id');
define('OPENAI_PROJECT_ID', 'your-openai-project-id');

define('CLAUDE_API_KEY', 'your-claude-api-key-here');

define('GEMINI_API_KEY', 'your-gemini-api-key-here');
define('GOOGLE_PROJECT_ID', 'your-google-project-id');

define('GROK_API_KEY', 'your-grok-api-key-here');

// =============================================================================
// AWS Configuration
// =============================================================================
define('AWS_ACCESS_KEY_ID', 'your-aws-access-key-id');
define('AWS_SECRET_ACCESS_KEY', 'your-aws-secret-access-key');
define('AWS_REGION', 'us-east-1');

// S3 Configuration
define('S3_BUCKET_NAME', 'vortex-ai-data-lake');
define('S3_REGION', 'us-east-1');

// DynamoDB Configuration
define('DYNAMODB_TABLE_NAME', 'vortex-user-memory');
define('DYNAMODB_REGION', 'us-east-1');

// Redis Configuration
define('REDIS_ENDPOINT', 'your-redis-endpoint');
define('REDIS_PORT', '6379');

// =============================================================================
// Vault Configuration
// =============================================================================
define('VAULT_ADDRESS', 'https://vault.yourdomain.com:8200');
define('VAULT_TOKEN', 'your-vault-token');
define('VAULT_NAMESPACE', 'vortex-ai');

// =============================================================================
// Security Configuration
// =============================================================================
define('VORTEX_ENABLE_RATE_LIMITING', true);
define('VORTEX_MAX_REQUESTS_PER_MINUTE', 60);
define('VORTEX_ENABLE_SECURITY_HEADERS', true);
define('VORTEX_ENABLE_AUDIT_LOGGING', true);

// =============================================================================
// Application Configuration
// =============================================================================
define('VORTEX_DEBUG', false);
define('VORTEX_LOG_LEVEL', 'info');

// =============================================================================
// WordPress Security Enhancements
// =============================================================================
define('VORTEX_DISABLE_FILE_EDIT', true);
define('VORTEX_FORCE_SSL_ADMIN', true);
define('VORTEX_HIDE_WP_VERSION', true);

// =============================================================================
// API Rate Limits (per tier)
// =============================================================================
define('VORTEX_BASIC_TIER_LIMIT', 100);      // 100 requests per month
define('VORTEX_PREMIUM_TIER_LIMIT', 1000);   // 1000 requests per month
define('VORTEX_ENTERPRISE_TIER_LIMIT', -1);  // Unlimited

// =============================================================================
// Cost Management
// =============================================================================
define('VORTEX_TARGET_PROFIT_MARGIN', 0.8);  // 80% profit margin
define('VORTEX_MAX_COST_PER_REQUEST', 0.50); // $0.50 max cost per request
define('VORTEX_ENABLE_COST_ALERTS', true);

// =============================================================================
// Monitoring and Logging
// =============================================================================
define('VORTEX_ENABLE_MONITORING', true);
define('VORTEX_LOG_RETENTION_DAYS', 30);
define('VORTEX_ENABLE_PERFORMANCE_MONITORING', true);

// =============================================================================
// Backup Configuration
// =============================================================================
define('VORTEX_ENABLE_AUTO_BACKUP', true);
define('VORTEX_BACKUP_RETENTION_DAYS', 30);
define('VORTEX_BACKUP_ENCRYPTION', true);

// =============================================================================
// Load configuration (if environment.php exists)
// =============================================================================
$config_file = __DIR__ . '/environment.php';
if (file_exists($config_file)) {
    require_once $config_file;
} 