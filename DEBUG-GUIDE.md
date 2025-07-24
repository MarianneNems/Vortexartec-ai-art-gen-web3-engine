# WooCommerce Blocks IntegrationRegistry Debug Guide

## 🚨 Issue: IntegrationRegistry::register Conflicts

**Error Message:**
```
Function Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::register was called incorrectly. "" is already registered.
```

## 🔍 Quick Diagnosis

### Step 1: Run Quick Debug Script
```bash
php quick-debug.php
```

This will immediately show:
- System information
- Integration count and empty names
- Error log analysis
- Plugin conflicts
- Recommendations

### Step 2: Check Admin Debug Page
Visit: `Tools > Integration Debug` in WordPress admin

## 📊 Detailed Debugging Process

### 1. **System Information Check**

**Required Versions:**
- PHP: 7.4+
- WordPress: 5.0+
- WooCommerce: 5.0+
- WooCommerce Blocks: 8.0+

**Check Command:**
```php
echo "PHP: " . PHP_VERSION;
echo "WP: " . get_bloginfo('version');
echo "WC: " . WC()->version;
echo "WC Blocks: " . WC_BLOCKS_VERSION;
```

### 2. **Integration Registry Analysis**

**Check Current State:**
```php
$registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
$integrations = $registry->get_all_registered();

foreach ($integrations as $name => $integration) {
    if (empty($name)) {
        echo "EMPTY NAME DETECTED!";
    }
}
```

**Expected Results:**
- ✅ All integrations have non-empty names
- ❌ Empty names indicate the problem

### 3. **Error Log Analysis**

**Check Recent Errors:**
```bash
tail -50 wp-content/debug.log | grep "IntegrationRegistry"
```

**Common Error Patterns:**
- `"" is already registered` - Empty name conflicts
- `Function called incorrectly` - Registration issues
- `Duplicate registration` - Multiple registrations

### 4. **Plugin Conflict Detection**

**Check Active Plugins:**
```php
$active_plugins = get_option('active_plugins');
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'woocommerce') !== false) {
        echo "WooCommerce Plugin: $plugin";
    }
}
```

**Potential Conflict Plugins:**
- WooCommerce Blocks
- WooCommerce Extensions
- Theme Customizations
- Third-party Integrations

## 🛠️ Debug Tools

### 1. **Quick Debug Script** (`quick-debug.php`)
- Immediate system analysis
- Integration count check
- Error log review
- Plugin conflict detection

### 2. **Comprehensive Debug Tool** (`debug-integration-registry.php`)
- Detailed logging
- Registration tracking
- HTML report generation
- Admin interface

### 3. **Admin Debug Page**
- Real-time monitoring
- Integration status
- Error tracking
- Quick actions

## 🔧 Debug Commands

### Check Integration Registry
```bash
# Run quick debug
php quick-debug.php

# Check error logs
tail -f wp-content/debug.log | grep "IntegrationRegistry"

# Monitor in real-time
watch -n 5 "tail -10 wp-content/debug.log | grep IntegrationRegistry"
```

### Clear Debug Data
```bash
# Clear debug logs
rm wp-content/integration-registry-debug.log
rm wp-content/integration-registry-debug-report.html

# Clear WordPress cache
wp cache flush
```

### Test Integration Fix
```bash
# Deploy fix
./deploy-woocommerce-fix.sh

# Test fix
php test-integration-fix.php

# Monitor results
tail -f wp-content/debug.log
```

## 📋 Debug Checklist

### Pre-Debug Setup
- [ ] Enable debug logging in wp-config.php
- [ ] Clear all caches
- [ ] Note current error count
- [ ] Backup current state

### Debug Process
- [ ] Run quick debug script
- [ ] Check admin debug page
- [ ] Review error logs
- [ ] Identify empty names
- [ ] Check plugin conflicts
- [ ] Test fix implementation

### Post-Debug Verification
- [ ] Confirm no empty names
- [ ] Verify no new errors
- [ ] Test WooCommerce functionality
- [ ] Monitor for 24 hours
- [ ] Document results

## 🚨 Emergency Debugging

### Critical Issues
If you're experiencing severe conflicts:

1. **Enable Emergency Mode:**
```php
// Add to wp-config.php
define('VORTEX_EMERGENCY_FIX', true);
```

2. **Clear All Integrations:**
```php
$registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
$integrations = $registry->get_all_registered();
foreach ($integrations as $name => $integration) {
    $registry->unregister($name);
}
```

3. **Disable Problem Plugins:**
```bash
# Temporarily disable plugins
wp plugin deactivate woocommerce-blocks
wp plugin deactivate [problem-plugin]
```

### Recovery Steps
1. **Restore from backup**
2. **Re-enable plugins one by one**
3. **Test after each activation**
4. **Implement fix before re-enabling**

## 📊 Debug Reports

### HTML Report
Location: `wp-content/integration-registry-debug-report.html`
- Complete system analysis
- Integration details
- Error summaries
- Recommendations

### Log File
Location: `wp-content/integration-registry-debug.log`
- Detailed debug information
- Registration attempts
- Error tracking
- Timestamp data

### Admin Interface
Location: `Tools > Integration Debug`
- Real-time monitoring
- Quick actions
- Status overview
- Manual testing

## 🔍 Common Debug Scenarios

### Scenario 1: Empty Integration Names
**Symptoms:**
- `"" is already registered` errors
- Multiple identical errors
- WooCommerce Blocks issues

**Debug Steps:**
1. Run quick debug script
2. Check for empty names
3. Deploy integration fix
4. Monitor error logs

### Scenario 2: Plugin Conflicts
**Symptoms:**
- Errors after plugin activation
- Specific plugin-related issues
- Inconsistent behavior

**Debug Steps:**
1. Identify conflict plugins
2. Test with plugins disabled
3. Re-enable one by one
4. Implement fix before conflicts

### Scenario 3: Version Compatibility
**Symptoms:**
- Errors after updates
- Version-specific issues
- Breaking changes

**Debug Steps:**
1. Check version compatibility
2. Review changelogs
3. Test in staging
4. Update fix if needed

## 📈 Performance Monitoring

### Monitor Error Frequency
```bash
# Count errors per hour
grep "IntegrationRegistry" wp-content/debug.log | wc -l

# Monitor real-time
tail -f wp-content/debug.log | grep -c "IntegrationRegistry"
```

### Track Integration Count
```php
$registry = \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::get_instance();
$count = count($registry->get_all_registered());
echo "Integration count: $count";
```

### Performance Impact
- Monitor page load times
- Check memory usage
- Track CPU usage
- Review error frequency

## 🎯 Debug Best Practices

### 1. **Systematic Approach**
- Start with quick debug
- Follow systematic checklist
- Document all findings
- Test solutions thoroughly

### 2. **Isolation Testing**
- Test one change at a time
- Use staging environment
- Backup before changes
- Monitor after changes

### 3. **Documentation**
- Record all debug steps
- Note error patterns
- Document solutions
- Share findings with team

### 4. **Prevention**
- Regular monitoring
- Proactive testing
- Version compatibility checks
- Automated alerts

## 🆘 Debug Support

### When to Seek Help
- Debug script shows unknown errors
- Fix doesn't resolve issues
- New errors appear after fix
- Performance severely impacted

### Information to Provide
- Debug script output
- Error log excerpts
- Plugin list
- System information
- Steps already taken

### Support Resources
- Debug documentation
- Implementation guide
- Troubleshooting procedures
- Emergency procedures

---

**This debug guide provides comprehensive tools and procedures to identify and resolve WooCommerce Blocks IntegrationRegistry conflicts.** 