# 🚀 VORTEX AI Engine - Deployment Workflow

## Overview
This guide explains how to deploy the VORTEX AI Engine plugin from GitHub to WordPress sites using automated workflows and manual deployment options.

## 📋 Prerequisites

### For Automated Deployment (GitHub Actions)
- GitHub repository access
- WordPress site with SFTP/SSH access
- GitHub Secrets configured (see setup below)

### For Manual Deployment
- Git installed locally
- Composer (optional, for dependency management)
- ZIP utility

## 🔧 Setup Options

### Option 1: Automated GitHub Actions Deployment

#### 1. Configure GitHub Secrets
Go to your GitHub repository → Settings → Secrets and variables → Actions, and add:

```
WP_SFTP_USERNAME=your_sftp_username
WP_SFTP_SERVER=your_server.com
WP_SFTP_PRIVATE_KEY=your_private_key_content
WP_SSH_HOST=your_server.com
WP_SSH_USERNAME=your_ssh_username
WP_SSH_PRIVATE_KEY=your_private_key_content
```

#### 2. Trigger Deployment
- **Automatic**: Push to `main` branch or create a release
- **Manual**: Go to Actions tab → "Deploy VORTEX AI Engine to WordPress" → Run workflow

#### 3. Monitor Deployment
- Check the Actions tab for deployment status
- Download the generated ZIP file from artifacts
- Verify plugin installation on your WordPress site

### Option 2: Manual Deployment

#### Windows (PowerShell)
```powershell
# Run the deployment script
.\deploy-plugin.ps1

# The script will create a ZIP file in the build/ directory
# Upload this ZIP to your WordPress site
```

#### Linux/Mac (Bash)
```bash
# Make script executable
chmod +x deploy-plugin.sh

# Run the deployment script
./deploy-plugin.sh

# The script will create a ZIP file in the build/ directory
# Upload this ZIP to your WordPress site
```

### Option 3: Direct GitHub Download

1. Go to your GitHub repository
2. Click "Code" → "Download ZIP"
3. Extract the ZIP file
4. Run the deployment script on the extracted files
5. Upload the generated plugin ZIP to WordPress

## 📦 WordPress Installation

### Method 1: WordPress Admin Upload
1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Choose the generated ZIP file
4. Click "Install Now"
5. Click "Activate Plugin"

### Method 2: FTP/SFTP Upload
1. Extract the plugin ZIP file
2. Upload the `vortex-ai-engine` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "VORTEX AI Engine For the ARTS" and click "Activate"

### Method 3: WP-CLI (Advanced)
```bash
# Install plugin via WP-CLI
wp plugin install /path/to/vortex-ai-engine.zip --activate

# Or install from directory
wp plugin install /path/to/vortex-ai-engine --activate
```

## 🔄 Workflow Steps

### Development Workflow
1. **Make Changes**: Edit plugin files locally
2. **Test Locally**: Test changes in local WordPress environment
3. **Commit**: `git add . && git commit -m "Description"`
4. **Push**: `git push origin development`
5. **Deploy**: Either automatic (GitHub Actions) or manual

### Production Workflow
1. **Merge to Main**: `git checkout main && git merge development`
2. **Push to Main**: `git push origin main`
3. **Automatic Deployment**: GitHub Actions will deploy to production
4. **Verify**: Check WordPress site for successful deployment

## 📁 File Structure

The deployment includes these essential files:
```
vortex-ai-engine/
├── vortex-ai-engine.php          # Main plugin file
├── readme.txt                    # Plugin readme
├── composer.json                 # Dependencies
├── admin/                        # Admin interface
├── includes/                     # Core functionality
├── vault-secrets/                # AI algorithms
├── assets/                       # CSS/JS files
├── templates/                    # Template files
└── languages/                    # Translations
```

## 🛠️ Troubleshooting

### Common Issues

#### Plugin Won't Activate
- Check PHP version (requires 7.4+)
- Verify all required files are present
- Check WordPress error logs
- Ensure database connection is working

#### Missing Dependencies
```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader
```

#### File Permission Issues
```bash
# Set correct permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
```

#### Database Connection Issues
- Verify WordPress database credentials
- Check if mysqli extension is enabled
- Test database connectivity

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 🔐 Security Considerations

### Before Deployment
- Remove any sensitive data (API keys, passwords)
- Ensure `.gitignore` excludes sensitive files
- Test in staging environment first
- Backup existing plugin installation

### After Deployment
- Verify plugin functionality
- Check for any error messages
- Monitor site performance
- Test all plugin features

## 📊 Monitoring

### Health Checks
- Run the health check script: `vortex-health-check.php`
- Monitor WordPress error logs
- Check plugin admin interface
- Verify all shortcodes work

### Performance Monitoring
- Monitor site load times
- Check memory usage
- Verify database queries
- Monitor API calls

## 🚀 Advanced Features

### Continuous Integration
The GitHub Actions workflow includes:
- Automated testing
- Dependency installation
- ZIP file creation
- Automatic deployment
- Release management

### Version Management
- Semantic versioning (2.1.0)
- Automatic release creation
- Changelog generation
- Rollback capabilities

## 📞 Support

### Getting Help
1. Check the plugin documentation
2. Review WordPress error logs
3. Test in a clean WordPress installation
4. Contact support with error details

### Useful Commands
```bash
# Check plugin status
wp plugin list

# Deactivate plugin
wp plugin deactivate vortex-ai-engine

# Remove plugin
wp plugin delete vortex-ai-engine

# Check WordPress version
wp core version

# Check PHP version
wp cli info
```

---

## 🎉 Success!

Once deployed, your VORTEX AI Engine plugin should be fully functional with:
- ✅ Admin interface accessible
- ✅ All shortcodes working
- ✅ AI algorithms operational
- ✅ Database tables created
- ✅ Assets loading correctly

**Happy deploying! 🚀** 