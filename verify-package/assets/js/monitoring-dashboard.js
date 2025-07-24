/**
 * Vortex AI Engine - Monitoring Dashboard JavaScript
 * 
 * Real-time monitoring dashboard with charts, auto-refresh, and interactive features
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

(function($) {
    'use strict';
    
    // Dashboard state
    let dashboardState = {
        autoRefresh: true,
        refreshInterval: null,
        charts: {},
        lastUpdate: null
    };
    
    // Initialize dashboard when DOM is ready
    $(document).ready(function() {
        initializeDashboard();
        setupEventListeners();
        startAutoRefresh();
    });
    
    /**
     * Initialize dashboard components
     */
    function initializeDashboard() {
        console.log('🤖 Initializing Vortex AI Engine Monitoring Dashboard...');
        
        // Load initial data
        loadDashboardMetrics();
        loadAuditStatus();
        loadModelPerformance();
        loadRecentAlerts();
        
        // Initialize charts
        initializeCharts();
        
        // Update timestamp
        updateLastUpdatedTime();
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Refresh button
        $('#refresh-dashboard').on('click', function() {
            refreshAllData();
        });
        
        // Auto-refresh toggle
        $('#auto-refresh').on('change', function() {
            dashboardState.autoRefresh = this.checked;
            if (dashboardState.autoRefresh) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
        
        // Quick action buttons
        $('.quick-actions button').on('click', function() {
            const action = $(this).data('action');
            if (action) {
                executeQuickAction(action);
            }
        });
    }
    
    /**
     * Start auto-refresh
     */
    function startAutoRefresh() {
        if (dashboardState.refreshInterval) {
            clearInterval(dashboardState.refreshInterval);
        }
        
        dashboardState.refreshInterval = setInterval(function() {
            if (dashboardState.autoRefresh) {
                loadDashboardMetrics();
            }
        }, vortexMonitoring.refreshInterval);
        
        console.log('🔄 Auto-refresh started');
    }
    
    /**
     * Stop auto-refresh
     */
    function stopAutoRefresh() {
        if (dashboardState.refreshInterval) {
            clearInterval(dashboardState.refreshInterval);
            dashboardState.refreshInterval = null;
        }
        
        console.log('⏸️ Auto-refresh stopped');
    }
    
    /**
     * Load dashboard metrics
     */
    function loadDashboardMetrics() {
        $.ajax({
            url: vortexMonitoring.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_get_dashboard_metrics',
                nonce: vortexMonitoring.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardMetrics(response.data);
                    updateLastUpdatedTime();
                } else {
                    console.error('❌ Failed to load dashboard metrics:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error loading metrics:', error);
            }
        });
    }
    
    /**
     * Load audit status
     */
    function loadAuditStatus() {
        $.ajax({
            url: vortexMonitoring.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_get_audit_status',
                nonce: vortexMonitoring.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateAuditStatus(response.data);
                }
            }
        });
    }
    
    /**
     * Load model performance
     */
    function loadModelPerformance() {
        $.ajax({
            url: vortexMonitoring.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_get_model_performance',
                nonce: vortexMonitoring.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateModelPerformance(response.data);
                }
            }
        });
    }
    
    /**
     * Load recent alerts
     */
    function loadRecentAlerts() {
        // This would load recent alerts from the system
        // For now, show placeholder data
        const alerts = [
            {
                type: 'warning',
                message: 'Model performance slightly degraded',
                timestamp: new Date().toISOString(),
                severity: 'medium'
            },
            {
                type: 'info',
                message: 'A/B test completed successfully',
                timestamp: new Date(Date.now() - 3600000).toISOString(),
                severity: 'low'
            }
        ];
        
        updateRecentAlerts(alerts);
    }
    
    /**
     * Update dashboard metrics
     */
    function updateDashboardMetrics(data) {
        // Update health metrics
        updateMetricCard('overall-health', data.overall_health + '%', getHealthStatus(data.overall_health));
        updateMetricCard('error-rate', data.error_rate + '%', getErrorStatus(data.error_rate));
        updateMetricCard('response-time', data.avg_response_time + 'ms', getResponseTimeStatus(data.avg_response_time));
        updateMetricCard('satisfaction', data.user_satisfaction + '%', getSatisfactionStatus(data.user_satisfaction));
        
        // Update feedback stats
        $('#total-feedback').text(data.total_feedback);
        $('#positive-ratio').text(data.positive_ratio + '%');
        $('#avg-rating').text(data.avg_rating + '/5');
        
        // Update charts if they exist
        if (dashboardState.charts.feedback) {
            updateFeedbackChart(data);
        }
    }
    
    /**
     * Update metric card
     */
    function updateMetricCard(cardId, value, status) {
        const card = $('#' + cardId);
        card.find('.metric-value').text(value);
        card.find('.metric-status').removeClass().addClass('metric-status ' + status);
    }
    
    /**
     * Get health status
     */
    function getHealthStatus(health) {
        if (health >= 90) return 'status-healthy';
        if (health >= 70) return 'status-warning';
        return 'status-critical';
    }
    
    /**
     * Get error status
     */
    function getErrorStatus(errorRate) {
        if (errorRate <= 1) return 'status-healthy';
        if (errorRate <= 5) return 'status-warning';
        return 'status-critical';
    }
    
    /**
     * Get response time status
     */
    function getResponseTimeStatus(responseTime) {
        if (responseTime <= 1000) return 'status-healthy';
        if (responseTime <= 3000) return 'status-warning';
        return 'status-critical';
    }
    
    /**
     * Get satisfaction status
     */
    function getSatisfactionStatus(satisfaction) {
        if (satisfaction >= 85) return 'status-healthy';
        if (satisfaction >= 70) return 'status-warning';
        return 'status-critical';
    }
    
    /**
     * Update audit status
     */
    function updateAuditStatus(data) {
        const auditStatus = $('#audit-status');
        let html = '';
        
        if (data.last_audit) {
            html += `
                <div class="audit-info">
                    <h3>Last Audit</h3>
                    <p><strong>Time:</strong> ${formatTimestamp(data.last_audit.timestamp)}</p>
                    <p><strong>Type:</strong> ${data.last_audit.type}</p>
                    <p><strong>Status:</strong> <span class="status-${data.audit_health}">${data.audit_health}</span></p>
                </div>
            `;
        }
        
        if (data.recent_issues && data.recent_issues.length > 0) {
            html += `
                <div class="recent-issues">
                    <h3>Recent Issues</h3>
                    <ul>
                        ${data.recent_issues.map(issue => `
                            <li>
                                <a href="${issue.issue_url}" target="_blank">
                                    Issue #${issue.issue_url.split('/').pop()}
                                </a>
                                <span class="issue-time">${formatTimestamp(issue.timestamp)}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        auditStatus.html(html);
    }
    
    /**
     * Update model performance
     */
    function updateModelPerformance(data) {
        const trainingStatus = $('#training-status');
        let html = '';
        
        // Current model info
        if (data.current_model) {
            html += `
                <div class="current-model">
                    <h3>Current Production Model</h3>
                    <p><strong>Version:</strong> ${data.current_model.version_id}</p>
                    <p><strong>Status:</strong> <span class="status-${data.current_model.status}">${data.current_model.status}</span></p>
                    <p><strong>Created:</strong> ${formatTimestamp(data.current_model.created_at)}</p>
                </div>
            `;
        }
        
        // Training jobs
        if (data.training_jobs && data.training_jobs.length > 0) {
            html += `
                <div class="training-jobs">
                    <h3>Recent Training Jobs</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.training_jobs.map(job => `
                                <tr>
                                    <td>${job.job_id}</td>
                                    <td><span class="status-${job.status}">${job.status}</span></td>
                                    <td>${formatTimestamp(job.started_at)}</td>
                                    <td>${job.duration_ms ? Math.round(job.duration_ms / 1000) + 's' : 'N/A'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        // A/B tests
        if (data.ab_tests && Object.keys(data.ab_tests).length > 0) {
            html += `
                <div class="ab-tests">
                    <h3>Active A/B Tests</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Model Version</th>
                                <th>Traffic %</th>
                                <th>Status</th>
                                <th>Started</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Object.entries(data.ab_tests).map(([version, test]) => `
                                <tr>
                                    <td>${version}</td>
                                    <td>${(test.traffic_percentage * 100).toFixed(1)}%</td>
                                    <td><span class="status-${test.status}">${test.status}</span></td>
                                    <td>${formatTimestamp(test.started_at)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        trainingStatus.html(html);
    }
    
    /**
     * Update recent alerts
     */
    function updateRecentAlerts(alerts) {
        const recentAlerts = $('#recent-alerts');
        let html = '';
        
        if (alerts.length === 0) {
            html = '<p class="no-alerts">No recent alerts</p>';
        } else {
            html = `
                <div class="alerts-list">
                    ${alerts.map(alert => `
                        <div class="alert-item alert-${alert.type}">
                            <div class="alert-icon">${getAlertIcon(alert.type)}</div>
                            <div class="alert-content">
                                <div class="alert-message">${alert.message}</div>
                                <div class="alert-time">${formatTimestamp(new Date(alert.timestamp).getTime() / 1000)}</div>
                            </div>
                            <div class="alert-severity severity-${alert.severity}">${alert.severity}</div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        recentAlerts.html(html);
    }
    
    /**
     * Get alert icon
     */
    function getAlertIcon(type) {
        const icons = {
            'error': '🚨',
            'warning': '⚠️',
            'info': 'ℹ️',
            'success': '✅'
        };
        
        return icons[type] || 'ℹ️';
    }
    
    /**
     * Initialize charts
     */
    function initializeCharts() {
        // Initialize feedback chart
        const feedbackCtx = document.getElementById('feedback-chart');
        if (feedbackCtx) {
            dashboardState.charts.feedback = new Chart(feedbackCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Positive Feedback',
                        data: [],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Negative Feedback',
                        data: [],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
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
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Update feedback chart
     */
    function updateFeedbackChart(data) {
        // This would update the chart with real data
        // For now, add sample data
        const chart = dashboardState.charts.feedback;
        const now = new Date();
        
        chart.data.labels.push(now.toLocaleTimeString());
        chart.data.datasets[0].data.push(Math.random() * 100);
        chart.data.datasets[1].data.push(Math.random() * 20);
        
        // Keep only last 20 data points
        if (chart.data.labels.length > 20) {
            chart.data.labels.shift();
            chart.data.datasets[0].data.shift();
            chart.data.datasets[1].data.shift();
        }
        
        chart.update();
    }
    
    /**
     * Refresh all data
     */
    function refreshAllData() {
        loadDashboardMetrics();
        loadAuditStatus();
        loadModelPerformance();
        loadRecentAlerts();
        
        // Show refresh indicator
        $('#refresh-dashboard').text('🔄 Refreshing...').prop('disabled', true);
        setTimeout(function() {
            $('#refresh-dashboard').text('🔄 Refresh').prop('disabled', false);
        }, 2000);
    }
    
    /**
     * Execute quick action
     */
    function executeQuickAction(action) {
        switch (action) {
            case 'manual-audit':
                runManualAudit();
                break;
            case 'model-retraining':
                triggerModelRetraining();
                break;
            case 'view-report':
                viewLatestReport();
                break;
            case 'view-issues':
                viewGitHubIssues();
                break;
        }
    }
    
    /**
     * Run manual audit
     */
    function runManualAudit() {
        if (confirm('Run a manual comprehensive audit?')) {
            $.ajax({
                url: vortexMonitoring.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_run_manual_audit',
                    nonce: vortexMonitoring.nonce,
                    audit_type: 'full'
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ Audit started successfully! Check the audit status for results.');
                        loadAuditStatus();
                    } else {
                        alert('❌ Failed to start audit: ' + response.data.message);
                    }
                }
            });
        }
    }
    
    /**
     * Trigger model retraining
     */
    function triggerModelRetraining() {
        if (confirm('Trigger model retraining?')) {
            $.ajax({
                url: vortexMonitoring.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_manual_retrain',
                    nonce: vortexMonitoring.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ Model retraining started! Job ID: ' + response.data.job_id);
                        loadModelPerformance();
                    } else {
                        alert('❌ Failed to start retraining: ' + response.data.message);
                    }
                }
            });
        }
    }
    
    /**
     * View latest report
     */
    function viewLatestReport() {
        window.open(vortexMonitoring.ajaxUrl + '?action=vortex_get_latest_report', '_blank');
    }
    
    /**
     * View GitHub issues
     */
    function viewGitHubIssues() {
        window.open('https://github.com/YOUR_USERNAME/vortex-ai-engine/issues?q=label%3Aaudit-regression', '_blank');
    }
    
    /**
     * Update last updated time
     */
    function updateLastUpdatedTime() {
        dashboardState.lastUpdate = new Date();
        $('#last-updated-time').text(dashboardState.lastUpdate.toLocaleString());
    }
    
    /**
     * Format timestamp
     */
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleString();
    }
    
    // Expose functions globally for inline script access
    window.runManualAudit = runManualAudit;
    window.triggerModelRetraining = triggerModelRetraining;
    window.viewLatestReport = viewLatestReport;
    window.viewGitHubIssues = viewGitHubIssues;
    
})(jQuery); 