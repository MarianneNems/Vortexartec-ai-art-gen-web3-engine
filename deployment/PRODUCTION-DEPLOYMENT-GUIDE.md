# Vortex AI Engine - Production Deployment Guide

## 🚀 Complete Staging → Production Pipeline

This guide provides step-by-step instructions for deploying the Vortex AI Engine from development to production with full CI/CD integration.

---

## 📋 Pre-Deployment Checklist

### 1. Environment Verification

Before deploying, run the environment verification script:

```bash
# From the plugin directory
php deployment/verify-environment.php
```

**Expected Output:**
```
✅ Environment verification PASSED!
   Ready for staging deployment.
```

**If verification fails, fix the issues before proceeding.**

### 2. Configuration Requirements

Ensure these are properly configured:

- ✅ **wp-config.php** - Database credentials and salts
- ✅ **AWS Credentials** - Access keys and region
- ✅ **Redis Configuration** - If using Redis caching
- ✅ **File Permissions** - Plugin directories writable
- ✅ **SSL Certificate** - HTTPS enabled for production

---

## 🔧 Staging Deployment

### Step 1: Prepare Staging Environment

1. **Upload Plugin to Staging:**
   ```bash
   # Option A: Git deployment
   git clone https://github.com/YOUR_USERNAME/vortex-ai-engine.git
   cd vortex-ai-engine
   git checkout main
   
   # Option B: Direct upload
   # Upload vortex-ai-engine folder to wp-content/plugins/
   ```

2. **Activate Plugin:**
   - Go to WordPress Admin → Plugins
   - Activate "Vortex AI Engine"
   - Accept the agreement modal

3. **Configure Environment Variables:**
   ```php
   // Add to wp-config.php or .env file
   define('AWS_ACCESS_KEY_ID', 'your_staging_key');
   define('AWS_SECRET_ACCESS_KEY', 'your_staging_secret');
   define('AWS_DEFAULT_REGION', 'us-east-1');
   ```

### Step 2: Run Smoke Tests

Execute the smoke test script:

```bash
php deployment/smoke-test.php
```

**Expected Output:**
```
🎉 Smoke tests PASSED!
   Plugin is ready for production deployment.
```

### Step 3: Test All Features

Manually test these features:

1. **Shortcodes:**
   - `[vortex_swap]` - Swap interface
   - `[vortex_wallet]` - Wallet management
   - `[vortex_metric]` - Metrics display
   - `[vortex_chat]` - AI chat interface
   - `[vortex_feedback]` - Feedback collection
   - `[huraii_generate]` - AI image generation

2. **REST Endpoints:**
   ```bash
   curl https://staging.yoursite.com/wp-json/vortex/v1/health-check
   curl https://staging.yoursite.com/wp-json/vortex/v1/feedback
   curl https://staging.yoursite.com/wp-json/vortex/v1/generate
   ```

3. **Admin Dashboard:**
   - Vortex AI Engine main dashboard
   - Artist Journey tracking
   - Activity monitoring
   - Agreement management
   - Health check status

4. **Agreement Modal:**
   - Verify modal appears for new users
   - Test terms acceptance flow
   - Check admin agreement tracking

### Step 4: User Acceptance Testing

1. **Invite Test Users:**
   - Create test accounts
   - Walk through Artist Journey
   - Test feedback collection
   - Verify agreement acceptance

2. **Collect Feedback:**
   - Monitor user interactions
   - Check error logs
   - Gather performance metrics

---

## 🏭 Production Deployment

### Step 1: Production Preparation

1. **Backup Production:**
   ```bash
   # Database backup
   mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
   
   # Plugin backup
   cp -r wp-content/plugins/vortex-ai-engine wp-content/plugins/vortex-ai-engine.backup
   ```

2. **Enable Maintenance Mode:**
   ```php
   // Add to wp-config.php temporarily
   define('WP_MAINTENANCE_MODE', true);
   ```

### Step 2: Deploy to Production

**Option A: Automated Deployment (Recommended)**

```bash
# Run the PowerShell deployment script
.\deployment\deploy-to-production.ps1 -Environment "production" -BackupDatabase $true -RunSmokeTests $true
```

**Option B: Manual Deployment**

1. **Upload Plugin:**
   ```bash
   # Upload vortex-ai-engine folder to production
   rsync -avz vortex-ai-engine/ user@server:/path/to/wp-content/plugins/vortex-ai-engine/
   ```

2. **Set Permissions:**
   ```bash
   chmod -R 755 wp-content/plugins/vortex-ai-engine/
   chown -R www-data:www-data wp-content/plugins/vortex-ai-engine/
   ```

3. **Activate Plugin:**
   - Go to WordPress Admin → Plugins
   - Activate "Vortex AI Engine"

### Step 3: Post-Deployment Verification

1. **Run Health Check:**
   ```bash
   curl https://yoursite.com/wp-json/vortex/v1/health-check
   ```

2. **Execute Smoke Tests:**
   ```bash
   php deployment/smoke-test.php
   ```

3. **Verify Admin Dashboard:**
   - Check all admin pages load
   - Verify agreement management
   - Test health check widget

4. **Monitor Error Logs:**
   ```bash
   tail -f wp-content/debug.log
   ```

### Step 4: Disable Maintenance Mode

```php
// Remove from wp-config.php
// define('WP_MAINTENANCE_MODE', true);
```

---

## 🔄 CI/CD Pipeline Setup

### GitHub Actions Configuration

1. **Enable GitHub Actions:**
   - Go to your GitHub repository
   - Navigate to Actions tab
   - Enable workflows

2. **Configure Secrets:**
   ```
   STAGING_URL=https://staging.yoursite.com
   PRODUCTION_URL=https://yoursite.com
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   ```

3. **Workflow Triggers:**
   - **Push to main** → Deploy to staging
   - **Pull Request** → Run tests
   - **Tag release** → Deploy to production

### Automated Testing

The CI/CD pipeline includes:

- ✅ **PHP Linting** - Syntax and code style
- ✅ **Unit Tests** - Automated test suite
- ✅ **Security Audit** - Vulnerability scanning
- ✅ **Recursive Audit** - Comprehensive plugin audit
- ✅ **Environment Verification** - Configuration validation
- ✅ **Smoke Tests** - End-to-end functionality tests

---

## 📊 Monitoring & Maintenance

### Health Monitoring

1. **Automated Health Checks:**
   ```bash
   # Daily health check (configured in GitHub Actions)
   curl https://yoursite.com/wp-json/vortex/v1/health-check?detailed=true
   ```

2. **CloudWatch Monitoring:**
   - Lambda function errors
   - SQS queue depth
   - SNS alert delivery
   - API Gateway metrics

3. **WordPress Admin Dashboard:**
   - Real-time health status
   - Performance metrics
   - Error log monitoring

### Performance Optimization

1. **Caching:**
   ```php
   // Enable Redis caching
   define('WP_REDIS_HOST', 'localhost');
   define('WP_REDIS_PORT', 6379);
   ```

2. **Database Optimization:**
   ```sql
   -- Optimize Vortex tables
   OPTIMIZE TABLE wp_vortex_activity_logs;
   OPTIMIZE TABLE wp_vortex_artist_journey;
   ```

3. **CDN Configuration:**
   - Configure CDN for static assets
   - Enable gzip compression
   - Set proper cache headers

### Backup Strategy

1. **Automated Backups:**
   ```bash
   # Daily database backup
   0 2 * * * mysqldump -u user -p database > /backups/daily_$(date +\%Y\%m\%d).sql
   
   # Weekly full backup
   0 3 * * 0 tar -czf /backups/weekly_$(date +\%Y\%m\%d).tar.gz /var/www/
   ```

2. **Backup Verification:**
   - Test backup restoration monthly
   - Verify backup integrity
   - Store backups in multiple locations

---

## 🚨 Troubleshooting

### Common Issues

1. **Plugin Activation Fails:**
   ```bash
   # Check PHP error log
   tail -f /var/log/php_errors.log
   
   # Verify file permissions
   ls -la wp-content/plugins/vortex-ai-engine/
   ```

2. **Database Connection Issues:**
   ```bash
   # Test database connectivity
   mysql -u username -p -h hostname database_name
   
   # Check wp-config.php credentials
   grep -E "DB_|WP_" wp-config.php
   ```

3. **AWS Integration Problems:**
   ```bash
   # Verify AWS credentials
   aws sts get-caller-identity
   
   # Test SQS connection
   aws sqs list-queues
   ```

4. **Agreement Modal Not Showing:**
   ```bash
   # Check JavaScript console
   # Verify agreement assets are loaded
   curl -I https://yoursite.com/wp-content/plugins/vortex-ai-engine/assets/js/agreement.js
   ```

### Rollback Procedure

If deployment fails:

1. **Immediate Rollback:**
   ```bash
   # Restore plugin backup
   rm -rf wp-content/plugins/vortex-ai-engine
   cp -r wp-content/plugins/vortex-ai-engine.backup wp-content/plugins/vortex-ai-engine
   
   # Restore database backup
   mysql -u user -p database < backup_YYYYMMDD_HHMMSS.sql
   ```

2. **Verify Rollback:**
   ```bash
   # Run health check
   curl https://yoursite.com/wp-json/vortex/v1/health-check
   
   # Test critical functionality
   php deployment/smoke-test.php
   ```

---

## 📈 Post-Deployment Checklist

### 24 Hours After Deployment

- [ ] Monitor error logs for new issues
- [ ] Check performance metrics
- [ ] Verify all shortcodes work
- [ ] Test agreement flow
- [ ] Monitor AWS costs and usage
- [ ] Check user feedback and ratings

### 1 Week After Deployment

- [ ] Review performance analytics
- [ ] Analyze user engagement metrics
- [ ] Check agreement acceptance rates
- [ ] Monitor AI agent performance
- [ ] Review security audit results
- [ ] Plan next iteration

---

## 🎯 Success Metrics

Track these metrics to ensure successful deployment:

- **Performance:** Page load times < 3 seconds
- **Reliability:** 99.9% uptime
- **User Experience:** Agreement acceptance rate > 95%
- **Security:** Zero critical vulnerabilities
- **Monitoring:** All health checks passing

---

## 📞 Support & Maintenance

For ongoing support:

1. **Monitor GitHub Issues** for bug reports
2. **Check CloudWatch Logs** for AWS issues
3. **Review WordPress Debug Log** for PHP errors
4. **Monitor User Feedback** for UX improvements

---

**🎉 Congratulations! Your Vortex AI Engine is now deployed and ready for production use.**

The system will continuously learn, self-audit, and self-optimize through the feedback loop you've implemented. Monitor the health dashboard and stay updated with the latest releases for optimal performance. 