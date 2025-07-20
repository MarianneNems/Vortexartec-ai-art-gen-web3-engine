<?php
/**
 * VORTEX AI Engine - Admin Interface
 * Main admin dashboard and configuration interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check admin permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Include admin class if it exists
if (file_exists(__DIR__ . '/class-vortex-admin.php')) {
    require_once __DIR__ . '/class-vortex-admin.php';
    
    // Initialize admin interface
    if (class_exists('VortexAIEngine_Admin')) {
        new VortexAIEngine_Admin();
    }
}

// Admin dashboard content
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="vortex-admin-dashboard">
        <div class="notice notice-info">
            <p><strong>VORTEX AI Engine</strong> - Advanced AI orchestration system is active.</p>
        </div>
        
        <div class="card">
            <h2>System Status</h2>
            <p>Plugin is properly configured and ready for use.</p>
        </div>
        
        <div class="card">
            <h2>Configuration</h2>
            <p>Configure your API keys and settings in the WordPress admin under Settings > VORTEX AI.</p>
        </div>
        
        <div class="card">
            <h2>Documentation</h2>
            <p>For detailed setup instructions, see the DEPLOYMENT-INSTRUCTIONS.md file.</p>
        </div>
    </div>
</div>

<style>
.vortex-admin-dashboard {
    margin-top: 20px;
}

.vortex-admin-dashboard .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.vortex-admin-dashboard .card h2 {
    margin-top: 0;
    color: #23282d;
}
</style> 