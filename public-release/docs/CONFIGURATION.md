# Configuration Guide

## Environment Setup

1. Copy `.env-template` to `.env`
2. Update all values with your actual credentials
3. Ensure file permissions are secure (600)

## WordPress Configuration

1. Copy `wp-config-template.php` to `wp-config.php`
2. Update database credentials
3. Generate authentication keys
4. Configure security settings

## AI Agent Configuration

Each AI agent can be configured independently:

- ARCHER: Orchestration settings
- HURAII: Image generation parameters
- CLOE: Market analysis settings
- HORACE: Content optimization
- THORIUS: Security monitoring

## Security Checklist

- [ ] Environment variables secured
- [ ] Database credentials updated
- [ ] API keys configured
- [ ] Encryption keys set
- [ ] File permissions correct
- [ ] SSL enabled
- [ ] Debug mode disabled