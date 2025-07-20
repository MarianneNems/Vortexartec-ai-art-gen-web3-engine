# VORTEX AI Engine - API Keys Directory

This directory contains the encrypted API keys for all AI providers used by the VORTEX AI Engine.

## 🔐 Security Notice

**CRITICAL**: This directory contains sensitive API keys. Never commit these files to version control.

## 📁 Key Files Structure

```
keys/
├── openai.env              # OpenAI API key and configuration
├── claude.env              # Claude API key and configuration  
├── gemini.env              # Google Gemini API key and configuration
├── grok.env                # xAI Grok API key and configuration
├── aws-credentials.env     # AWS access keys for S3/DynamoDB/Redis
├── vault-token.env         # HashiCorp Vault authentication token
└── encryption.key          # Master encryption key for AES-256-CBC
```

## 🔑 API Key Format

Each `.env` file should contain:

```bash
# OpenAI Configuration (openai.env)
OPENAI_API_KEY=sk-proj-your-actual-key-here
OPENAI_ORG_ID=org-your-org-id
OPENAI_PROJECT_ID=proj-your-project-id

# Claude Configuration (claude.env)
CLAUDE_API_KEY=sk-ant-api03-your-actual-key-here

# Gemini Configuration (gemini.env)
GEMINI_API_KEY=AIzaSyC-your-actual-key-here
GOOGLE_PROJECT_ID=your-google-project-id

# Grok Configuration (grok.env)
GROK_API_KEY=xai-your-actual-key-here

# AWS Configuration (aws-credentials.env)
AWS_ACCESS_KEY_ID=AKIA-your-actual-access-key
AWS_SECRET_ACCESS_KEY=your-actual-secret-key
AWS_REGION=us-east-1
S3_BUCKET=vortex-ai-data-lake
DYNAMODB_TABLE=vortex-user-memory
REDIS_ENDPOINT=vortex-redis-cluster.cache.amazonaws.com

# Vault Configuration (vault-token.env)
VAULT_ADDR=https://vault.vortexartec.com:8200
VAULT_TOKEN=hvs.your-actual-vault-token
VAULT_NAMESPACE=vortex-ai
```

## 🛡️ Security Features

### Encryption
- **AES-256-CBC**: All keys encrypted before storage
- **PBKDF2**: Key derivation function with 10,000 iterations
- **Salt**: 32-byte random salt for each key
- **IV**: Unique initialization vector per encryption

### Access Control
- **File Permissions**: 600 (owner read/write only)
- **Directory Permissions**: 700 (owner access only)
- **WordPress Integration**: Admin-only access
- **Audit Logging**: All key access logged

### Key Rotation
- **Automatic**: Keys rotated every 90 days
- **Manual**: Force rotation via admin interface
- **Backup**: Previous keys backed up securely
- **Validation**: Keys tested before rotation

## 📋 Setup Instructions

### 1. Create Key Files
```bash
# Create individual key files
touch openai.env claude.env gemini.env grok.env
touch aws-credentials.env vault-token.env

# Set secure permissions
chmod 600 *.env
chmod 700 ../keys/
```

### 2. Populate Keys
Edit each file with your actual API keys:

```bash
# Example for openai.env
echo "OPENAI_API_KEY=sk-proj-your-actual-key" > openai.env
echo "OPENAI_ORG_ID=org-your-org-id" >> openai.env
```

### 3. Test Configuration
```bash
# Run from plugin directory
php verify-secure-keys.php
```

### 4. Verify Encryption
```bash
# Check that keys are encrypted in WordPress database
wp option get vortex_encrypted_openai_api_key
```

## 🚨 Emergency Procedures

### Key Compromise
1. **Immediately revoke** compromised key from provider dashboard
2. **Generate new key** from provider
3. **Update key file** with new key
4. **Test all providers** to ensure functionality
5. **Audit logs** for unauthorized usage

### System Recovery
1. **Backup restoration**: Restore from encrypted backup
2. **Key regeneration**: Generate all new keys if needed
3. **Provider testing**: Verify all providers work
4. **Audit trail**: Review all access logs

## 📊 Monitoring

### Key Usage Metrics
- **Request counts** per provider
- **Token consumption** tracking
- **Error rates** monitoring
- **Cost analysis** per key

### Security Monitoring
- **Access attempts** logging
- **Failed decryption** alerts
- **Unusual usage** patterns
- **Rate limit** violations

## 🔄 Maintenance

### Weekly Tasks
- [ ] Review usage metrics
- [ ] Check error logs
- [ ] Verify key health
- [ ] Monitor costs

### Monthly Tasks
- [ ] Audit access logs
- [ ] Review rate limits
- [ ] Update documentation
- [ ] Test backup procedures

### Quarterly Tasks
- [ ] Rotate all API keys
- [ ] Security audit
- [ ] Update encryption keys
- [ ] Review permissions

## 📞 Support

For key management issues:
- **Security**: security@vortexartec.com
- **Technical**: support@vortexartec.com
- **Emergency**: Use WordPress admin interface

---

**Remember**: These keys are the lifeblood of your AI system. Treat them with the highest security standards. 