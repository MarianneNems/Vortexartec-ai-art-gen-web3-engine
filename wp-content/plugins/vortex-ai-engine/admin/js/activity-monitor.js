/**
 * VORTEX AI Engine - Real-Time Activity Monitor JavaScript
 * 
 * Handles real-time updates, filtering, and interaction for the activity monitor
 */

var VortexActivityMonitor = {
    
    refreshInterval: null,
    currentFilters: {
        type: '',
        level: ''
    },
    
    /**
     * Initialize the activity monitor
     */
    init: function() {
        this.bindEvents();
        this.startAutoRefresh();
        this.loadActivities();
    },
    
    /**
     * Bind event handlers
     */
    bindEvents: function() {
        jQuery(document).ready(function($) {
            // Filter change events
            $('#activity-type-filter').on('change', function() {
                VortexActivityMonitor.currentFilters.type = $(this).val();
                VortexActivityMonitor.loadActivities();
            });
            
            $('#activity-level-filter').on('change', function() {
                VortexActivityMonitor.currentFilters.level = $(this).val();
                VortexActivityMonitor.loadActivities();
            });
            
            // Button events
            $('#clear-activity-btn').on('click', function() {
                VortexActivityMonitor.clearActivity();
            });
            
            $('#refresh-activity-btn').on('click', function() {
                VortexActivityMonitor.loadActivities();
            });
            
            // Activity entry click events
            $(document).on('click', '.activity-entry', function() {
                VortexActivityMonitor.toggleActivityDetails($(this));
            });
        });
    },
    
    /**
     * Start auto-refresh
     */
    startAutoRefresh: function() {
        this.refreshInterval = setInterval(function() {
            VortexActivityMonitor.loadActivities();
        }, vortex_activity_ajax.refresh_interval);
    },
    
    /**
     * Stop auto-refresh
     */
    stopAutoRefresh: function() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    },
    
    /**
     * Load activities via AJAX
     */
    loadActivities: function() {
        jQuery.ajax({
            url: vortex_activity_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_activity',
                nonce: vortex_activity_ajax.nonce,
                type_filter: this.currentFilters.type,
                level_filter: this.currentFilters.level,
                limit: 100
            },
            success: function(response) {
                if (response.success) {
                    VortexActivityMonitor.renderActivities(response.data.activities);
                    VortexActivityMonitor.updateTimestamp(response.data.timestamp);
                } else {
                    console.error('Failed to load activities:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    },
    
    /**
     * Render activities in the feed
     */
    renderActivities: function(activities) {
        var $feed = jQuery('#activity-feed');
        var html = '';
        
        if (activities.length === 0) {
            html = '<div class="no-activities">No activities found with current filters.</div>';
        } else {
            activities.forEach(function(activity) {
                html += VortexActivityMonitor.renderActivityEntry(activity);
            });
        }
        
        $feed.html(html);
    },
    
    /**
     * Render individual activity entry
     */
    renderActivityEntry: function(activity) {
        var levelClass = 'level-' + activity.level.toLowerCase();
        var typeIcon = VortexActivityMonitor.getTypeIcon(activity.type);
        var timeAgo = VortexActivityMonitor.getTimeAgo(activity.timestamp);
        
        var html = '<div class="activity-entry ' + levelClass + '" data-activity-id="' + activity.request_id + '">';
        html += '<div class="activity-header">';
        html += '<span class="activity-icon">' + typeIcon + '</span>';
        html += '<span class="activity-type">' + activity.type + '</span>';
        html += '<span class="activity-level">' + activity.level + '</span>';
        html += '<span class="activity-time">' + timeAgo + '</span>';
        html += '</div>';
        html += '<div class="activity-message">' + VortexActivityMonitor.escapeHtml(activity.message) + '</div>';
        html += '<div class="activity-details" style="display: none;">';
        html += '<pre>' + VortexActivityMonitor.escapeHtml(JSON.stringify(activity.data, null, 2)) + '</pre>';
        html += '</div>';
        html += '</div>';
        
        return html;
    },
    
    /**
     * Get icon for activity type
     */
    getTypeIcon: function(type) {
        var icons = {
            'AI_AGENT': '🤖',
            'SERVER': '🌐',
            'ALGORITHM': '🧠',
            'DATABASE': '💾',
            'BLOCKCHAIN': '⛓️',
            'CLOUD': '☁️',
            'USER': '👤',
            'SYSTEM': '⚙️'
        };
        
        return icons[type] || '📝';
    },
    
    /**
     * Get time ago string
     */
    getTimeAgo: function(timestamp) {
        var now = new Date();
        var activityTime = new Date(timestamp);
        var diffMs = now - activityTime;
        var diffSec = Math.floor(diffMs / 1000);
        var diffMin = Math.floor(diffSec / 60);
        var diffHour = Math.floor(diffMin / 60);
        
        if (diffSec < 60) {
            return 'Just now';
        } else if (diffMin < 60) {
            return diffMin + ' min ago';
        } else if (diffHour < 24) {
            return diffHour + ' hour' + (diffHour > 1 ? 's' : '') + ' ago';
        } else {
            return activityTime.toLocaleDateString() + ' ' + activityTime.toLocaleTimeString();
        }
    },
    
    /**
     * Toggle activity details
     */
    toggleActivityDetails: function($entry) {
        var $details = $entry.find('.activity-details');
        $details.slideToggle();
    },
    
    /**
     * Clear activity buffer
     */
    clearActivity: function() {
        if (!confirm('Are you sure you want to clear the activity buffer? This action cannot be undone.')) {
            return;
        }
        
        jQuery.ajax({
            url: vortex_activity_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_clear_activity',
                nonce: vortex_activity_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    VortexActivityMonitor.loadActivities();
                    alert('Activity buffer cleared successfully!');
                } else {
                    alert('Failed to clear activity buffer.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error clearing activity buffer: ' + error);
            }
        });
    },
    
    /**
     * Update timestamp display
     */
    updateTimestamp: function(timestamp) {
        var $timestamp = jQuery('.activity-feed h2');
        if ($timestamp.length) {
            $timestamp.html('🔄 Real-Time Activity Feed <small>(Last updated: ' + timestamp + ')</small>');
        }
    },
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
};

// Auto-refresh indicator
jQuery(document).ready(function($) {
    // Add refresh indicator
    $('.vortex-activity-feed h2').append('<span class="refresh-indicator">🔄 Auto-refreshing</span>');
    
    // Pause auto-refresh when tab is not visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            VortexActivityMonitor.stopAutoRefresh();
            $('.refresh-indicator').text('⏸️ Paused');
        } else {
            VortexActivityMonitor.startAutoRefresh();
            $('.refresh-indicator').text('🔄 Auto-refreshing');
        }
    });
}); 