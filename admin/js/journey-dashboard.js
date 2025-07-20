/**
 * VORTEX AI Engine - Artist Journey Dashboard JavaScript
 * 
 * Real-time dashboard functionality for monitoring artist journey,
 * reinforcement learning loops, and recursive self-improvement.
 */

var VortexJourneyDashboard = {
    
    refreshInterval: null,
    charts: {},
    
    /**
     * Initialize the journey dashboard
     */
    init: function() {
        this.bindEvents();
        this.startAutoRefresh();
        this.loadDashboardData();
        this.initializeCharts();
    },
    
    /**
     * Bind event handlers
     */
    bindEvents: function() {
        jQuery(document).ready(function($) {
            // Filter change events
            $('#activity-type-filter').on('change', function() {
                VortexJourneyDashboard.updateActivityChart();
            });
            
            $('#time-range-filter').on('change', function() {
                VortexJourneyDashboard.updateActivityChart();
            });
            
            // Artist search
            $('#search-artist-btn').on('click', function() {
                VortexJourneyDashboard.searchArtist();
            });
            
            $('#artist-search').on('keypress', function(e) {
                if (e.which === 13) {
                    VortexJourneyDashboard.searchArtist();
                }
            });
        });
    },
    
    /**
     * Start auto-refresh
     */
    startAutoRefresh: function() {
        this.refreshInterval = setInterval(function() {
            VortexJourneyDashboard.loadDashboardData();
        }, vortex_journey_ajax.refresh_interval);
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
     * Load dashboard data
     */
    loadDashboardData: function() {
        this.loadJourneyStats();
        this.loadRLMetrics();
        this.loadSelfImprovement();
    },
    
    /**
     * Load journey statistics
     */
    loadJourneyStats: function() {
        jQuery.ajax({
            url: vortex_journey_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_journey_stats',
                nonce: vortex_journey_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    VortexJourneyDashboard.updateStats(response.data.stats);
                    VortexJourneyDashboard.updateRegistrationFeed(response.data.recent_registrations);
                    VortexJourneyDashboard.updateActivityChart(response.data.activity_data);
                }
            }
        });
    },
    
    /**
     * Load RL metrics
     */
    loadRLMetrics: function() {
        jQuery.ajax({
            url: vortex_journey_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_rl_metrics',
                nonce: vortex_journey_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    VortexJourneyDashboard.updateRLMetrics(response.data);
                }
            }
        });
    },
    
    /**
     * Load self-improvement data
     */
    loadSelfImprovement: function() {
        jQuery.ajax({
            url: vortex_journey_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_self_improvement',
                nonce: vortex_journey_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    VortexJourneyDashboard.updateSelfImprovement(response.data);
                }
            }
        });
    },
    
    /**
     * Update statistics display
     */
    updateStats: function(stats) {
        jQuery('.vortex-journey-overview .stat-value').each(function(index) {
            var value = Object.values(stats)[index];
            if (typeof value === 'number') {
                jQuery(this).text(value.toLocaleString());
            } else {
                jQuery(this).text(value);
            }
        });
    },
    
    /**
     * Update registration feed
     */
    updateRegistrationFeed: function(registrations) {
        var $feed = jQuery('#registration-feed');
        var html = '';
        
        if (registrations.length === 0) {
            html = '<div class="no-registrations">No recent registrations</div>';
        } else {
            registrations.forEach(function(registration) {
                html += VortexJourneyDashboard.renderRegistrationEntry(registration);
            });
        }
        
        $feed.html(html);
    },
    
    /**
     * Render registration entry
     */
    renderRegistrationEntry: function(registration) {
        var timeAgo = VortexJourneyDashboard.getTimeAgo(registration.journey_start_date);
        var skillLevelClass = 'skill-' + registration.skill_level;
        
        var html = '<div class="registration-entry">';
        html += '<div class="registration-header">';
        html += '<span class="registration-icon">👤</span>';
        html += '<span class="registration-username">' + registration.username + '</span>';
        html += '<span class="registration-skill ' + skillLevelClass + '">' + registration.skill_level + '</span>';
        html += '<span class="registration-time">' + timeAgo + '</span>';
        html += '</div>';
        html += '<div class="registration-details">';
        html += '<span class="registration-stage">Stage: ' + registration.current_stage + '</span>';
        html += '<span class="registration-engagement">Engagement: ' + registration.engagement_score + '</span>';
        html += '</div>';
        html += '</div>';
        
        return html;
    },
    
    /**
     * Initialize charts
     */
    initializeCharts: function() {
        // Activity Chart
        var activityCtx = document.getElementById('activityChart').getContext('2d');
        this.charts.activity = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Activities',
                    data: [],
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // RL Chart
        var rlCtx = document.getElementById('rlChart').getContext('2d');
        this.charts.rl = new Chart(rlCtx, {
            type: 'bar',
            data: {
                labels: ['Patterns', 'Rewards', 'Updates', 'Exploration'],
                datasets: [{
                    label: 'RL Metrics',
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Improvement Chart
        var improvementCtx = document.getElementById('improvementChart').getContext('2d');
        this.charts.improvement = new Chart(improvementCtx, {
            type: 'doughnut',
            data: {
                labels: ['Applied', 'Pending', 'Failed'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    },
    
    /**
     * Update activity chart
     */
    updateActivityChart: function(data) {
        if (!data) return;
        
        var chartData = this.processActivityData(data);
        
        this.charts.activity.data.labels = chartData.labels;
        this.charts.activity.data.datasets[0].data = chartData.values;
        this.charts.activity.update();
    },
    
    /**
     * Process activity data for chart
     */
    processActivityData: function(data) {
        var processed = {
            labels: [],
            values: []
        };
        
        // Group by date and activity type
        var grouped = {};
        data.forEach(function(item) {
            if (!grouped[item.date]) {
                grouped[item.date] = {};
            }
            grouped[item.date][item.activity_type] = item.count;
        });
        
        // Convert to chart format
        Object.keys(grouped).forEach(function(date) {
            processed.labels.push(date);
            var total = Object.values(grouped[date]).reduce(function(sum, count) {
                return sum + count;
            }, 0);
            processed.values.push(total);
        });
        
        return processed;
    },
    
    /**
     * Update RL metrics
     */
    updateRLMetrics: function(metrics) {
        // Update stat displays
        jQuery('#active-rl-count').text(metrics.active_rl_count);
        jQuery('#total-patterns').text(metrics.total_patterns);
        jQuery('#avg-reward').text(parseFloat(metrics.avg_reward).toFixed(2));
        jQuery('#policy-updates').text(metrics.policy_updates);
        
        // Update RL chart
        this.charts.rl.data.datasets[0].data = [
            metrics.total_patterns,
            parseFloat(metrics.avg_reward) * 100,
            metrics.policy_updates,
            metrics.active_rl_count
        ];
        this.charts.rl.update();
    },
    
    /**
     * Update self-improvement data
     */
    updateSelfImprovement: function(data) {
        // Update stat displays
        jQuery('#improvements-applied').text(data.improvements_applied);
        jQuery('#effectiveness-score').text(parseFloat(data.effectiveness_score).toFixed(2));
        jQuery('#users-affected').text(data.users_affected);
        jQuery('#last-improvement').text(data.last_improvement ? VortexJourneyDashboard.getTimeAgo(data.last_improvement) : 'Never');
        
        // Update improvement chart (mock data for now)
        this.charts.improvement.data.datasets[0].data = [
            data.improvements_applied,
            Math.max(0, 10 - data.improvements_applied),
            0
        ];
        this.charts.improvement.update();
    },
    
    /**
     * Search for artist
     */
    searchArtist: function() {
        var searchTerm = jQuery('#artist-search').val().trim();
        
        if (!searchTerm) {
            alert('Please enter a username or email');
            return;
        }
        
        jQuery.ajax({
            url: vortex_journey_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_user_journey',
                nonce: vortex_journey_ajax.nonce,
                search_term: searchTerm
            },
            success: function(response) {
                if (response.success) {
                    VortexJourneyDashboard.displayArtistJourney(response.data);
                } else {
                    alert('Artist not found');
                }
            },
            error: function() {
                alert('Error searching for artist');
            }
        });
    },
    
    /**
     * Display artist journey
     */
    displayArtistJourney: function(journeyData) {
        var profile = journeyData.profile;
        var activities = journeyData.activities;
        var achievements = journeyData.achievements;
        
        // Update profile information
        jQuery('#artist-name').text(profile.username || 'Unknown Artist');
        jQuery('#journey-stage').text(profile.current_stage);
        jQuery('#skill-level').text(profile.skill_level);
        jQuery('#engagement-score').text(profile.engagement_score);
        jQuery('#total-activities').text(profile.total_activities);
        
        // Update activities list
        var activitiesHtml = '';
        if (activities.length === 0) {
            activitiesHtml = '<div class="no-activities">No activities found</div>';
        } else {
            activities.forEach(function(activity) {
                activitiesHtml += VortexJourneyDashboard.renderActivityEntry(activity);
            });
        }
        jQuery('#artist-activities-list').html(activitiesHtml);
        
        // Update achievements list
        var achievementsHtml = '';
        if (achievements.length === 0) {
            achievementsHtml = '<div class="no-achievements">No achievements yet</div>';
        } else {
            achievements.forEach(function(achievement) {
                achievementsHtml += VortexJourneyDashboard.renderAchievementEntry(achievement);
            });
        }
        jQuery('#artist-achievements-list').html(achievementsHtml);
        
        // Show the details section
        jQuery('#artist-journey-details').show();
    },
    
    /**
     * Render activity entry
     */
    renderActivityEntry: function(activity) {
        var timeAgo = VortexJourneyDashboard.getTimeAgo(activity.timestamp);
        var activityData = JSON.parse(activity.activity_data || '{}');
        
        var html = '<div class="activity-entry">';
        html += '<div class="activity-header">';
        html += '<span class="activity-type">' + activity.activity_type.replace(/_/g, ' ').toUpperCase() + '</span>';
        html += '<span class="activity-time">' + timeAgo + '</span>';
        html += '</div>';
        if (activityData.message) {
            html += '<div class="activity-message">' + activityData.message + '</div>';
        }
        html += '</div>';
        
        return html;
    },
    
    /**
     * Render achievement entry
     */
    renderAchievementEntry: function(achievement) {
        var timeAgo = VortexJourneyDashboard.getTimeAgo(achievement.unlocked_at);
        
        var html = '<div class="achievement-entry">';
        html += '<div class="achievement-header">';
        html += '<span class="achievement-name">' + achievement.achievement_name + '</span>';
        html += '<span class="achievement-points">+' + achievement.points_awarded + ' pts</span>';
        html += '</div>';
        html += '<div class="achievement-description">' + achievement.achievement_description + '</div>';
        html += '<div class="achievement-time">Unlocked ' + timeAgo + '</div>';
        html += '</div>';
        
        return html;
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
        var diffDay = Math.floor(diffHour / 24);
        
        if (diffSec < 60) {
            return 'Just now';
        } else if (diffMin < 60) {
            return diffMin + ' min ago';
        } else if (diffHour < 24) {
            return diffHour + ' hour' + (diffHour > 1 ? 's' : '') + ' ago';
        } else if (diffDay < 7) {
            return diffDay + ' day' + (diffDay > 1 ? 's' : '') + ' ago';
        } else {
            return activityTime.toLocaleDateString();
        }
    }
};

// Auto-refresh indicator
jQuery(document).ready(function($) {
    // Add refresh indicator
    $('.vortex-journey-section h2').each(function() {
        $(this).append('<span class="refresh-indicator">🔄 Live</span>');
    });
    
    // Pause auto-refresh when tab is not visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            VortexJourneyDashboard.stopAutoRefresh();
            $('.refresh-indicator').text('⏸️ Paused');
        } else {
            VortexJourneyDashboard.startAutoRefresh();
            $('.refresh-indicator').text('🔄 Live');
        }
    });
}); 