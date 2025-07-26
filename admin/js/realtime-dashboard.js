/**
 * VORTEX AI ENGINE - REALTIME DASHBOARD JAVASCRIPT
 * 
 * Real-time dashboard functionality with:
 * - Live data updates
 * - WebSocket-like polling
 * - Interactive controls
 * - Real-time log streaming
 * - Performance monitoring
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

(function($) {
    'use strict';
    
    var VortexRealtimeDashboard = {
        
        // Configuration
        config: {
            updateInterval: 5000, // 5 seconds
            logUpdateInterval: 2000, // 2 seconds
            maxLogEntries: 1000,
            autoScroll: true
        },
        
        // State
        state: {
            isUpdating: false,
            lastUpdate: 0,
            updateTimer: null,
            logTimer: null,
            logContainer: null,
            improvementProgress: 0
        },
        
        // Initialize the dashboard
        init: function() {
            this.bindEvents();
            this.startUpdates();
            this.startLogUpdates();
            this.updateImprovementProgress();
            
            console.log('🚀 VORTEX Realtime Dashboard initialized');
        },
        
        // Bind event handlers
        bindEvents: function() {
            var self = this;
            
            // Manual sync trigger
            $('#trigger-sync').on('click', function() {
                self.triggerSync();
            });
            
            // Manual improvement trigger
            $('#trigger-improvement').on('click', function() {
                self.triggerImprovement();
            });
            
            // Manual learning trigger
            $('#trigger-learning').on('click', function() {
                self.triggerLearning();
            });
            
            // Clear logs
            $('#clear-logs').on('click', function() {
                self.clearLogs();
            });
            
            // Auto-scroll toggle
            $('#auto-scroll').on('change', function() {
                self.config.autoScroll = $(this).is(':checked');
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                self.handleKeyboardShortcuts(e);
            });
        },
        
        // Start real-time updates
        startUpdates: function() {
            var self = this;
            
            self.state.updateTimer = setInterval(function() {
                self.updateDashboard();
            }, self.config.updateInterval);
            
            // Initial update
            self.updateDashboard();
        },
        
        // Start log updates
        startLogUpdates: function() {
            var self = this;
            
            self.state.logTimer = setInterval(function() {
                self.updateLogs();
            }, self.config.logUpdateInterval);
        },
        
        // Update dashboard data
        updateDashboard: function() {
            var self = this;
            
            if (self.state.isUpdating) {
                return;
            }
            
            self.state.isUpdating = true;
            
            $.ajax({
                url: vortexRealtime.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_dashboard_data',
                    nonce: vortexRealtime.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboardUI(response.data);
                        self.state.lastUpdate = Date.now();
                    } else {
                        console.error('Failed to update dashboard:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Dashboard update error:', error);
                },
                complete: function() {
                    self.state.isUpdating = false;
                }
            });
        },
        
        // Update dashboard UI
        updateDashboardUI: function(data) {
            this.updateSystemStatus(data.system_status);
            this.updateGitHubStatus(data.system_status);
            this.updatePerformanceMetrics(data.performance_metrics);
            this.updateLearningProgress(data.learning_data);
            this.updateImprovementCycle(data.system_status);
        },
        
        // Update system status
        updateSystemStatus: function(status) {
            $('#system-status').html(this.renderSystemStatus(status));
        },
        
        // Update GitHub status
        updateGitHubStatus: function(status) {
            $('#github-status').html(this.renderGitHubStatus(status));
        },
        
        // Update performance metrics
        updatePerformanceMetrics: function(metrics) {
            $('#performance-metrics').html(this.renderPerformanceMetrics(metrics));
        },
        
        // Update learning progress
        updateLearningProgress: function(learningData) {
            $('#learning-progress').html(this.renderLearningProgress(learningData));
        },
        
        // Update improvement cycle
        updateImprovementCycle: function(status) {
            $('#improvement-cycle').html(this.renderImprovementCycle(status));
        },
        
        // Update logs
        updateLogs: function() {
            var self = this;
            
            $.ajax({
                url: vortexRealtime.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_dashboard_data',
                    nonce: vortexRealtime.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateLogUI(response.data.debug_log);
                    }
                }
            });
        },
        
        // Update log UI
        updateLogUI: function(logs) {
            var logContainer = $('#debug-log .log-container');
            var currentLogs = logContainer.find('.log-entry').length;
            
            // Only update if there are new logs
            if (logs.length > currentLogs) {
                var newLogs = logs.slice(currentLogs);
                var logHtml = '';
                
                newLogs.forEach(function(log) {
                    logHtml += this.renderLogEntry(log);
                }.bind(this));
                
                logContainer.append(logHtml);
                
                // Auto-scroll if enabled
                if (this.config.autoScroll) {
                    logContainer.scrollTop(logContainer[0].scrollHeight);
                }
                
                // Limit log entries
                if (logContainer.find('.log-entry').length > this.config.maxLogEntries) {
                    logContainer.find('.log-entry').slice(0, -this.config.maxLogEntries).remove();
                }
            }
        },
        
        // Trigger sync
        triggerSync: function() {
            var self = this;
            var button = $('#trigger-sync');
            
            button.prop('disabled', true).text('🔄 Syncing...');
            
            $.ajax({
                url: vortexRealtime.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_trigger_sync',
                    nonce: vortexRealtime.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('Sync triggered successfully', 'success');
                        self.updateDashboard();
                    } else {
                        self.showNotification('Sync failed: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Sync request failed', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('🔄 Trigger Sync');
                }
            });
        },
        
        // Trigger improvement
        triggerImprovement: function() {
            var self = this;
            var button = $('#trigger-improvement');
            
            button.prop('disabled', true).text('⚡ Improving...');
            
            $.ajax({
                url: vortexRealtime.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_trigger_improvement',
                    nonce: vortexRealtime.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('Improvement cycle triggered successfully', 'success');
                        self.updateDashboard();
                    } else {
                        self.showNotification('Improvement failed: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Improvement request failed', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('⚡ Trigger Improvement');
                }
            });
        },
        
        // Trigger learning
        triggerLearning: function() {
            var self = this;
            var button = $('#trigger-learning');
            
            button.prop('disabled', true).text('🧠 Learning...');
            
            $.ajax({
                url: vortexRealtime.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_trigger_learning',
                    nonce: vortexRealtime.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('Learning cycle triggered successfully', 'success');
                        self.updateDashboard();
                    } else {
                        self.showNotification('Learning failed: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Learning request failed', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('🧠 Trigger Learning');
                }
            });
        },
        
        // Clear logs
        clearLogs: function() {
            var self = this;
            
            if (!confirm('Are you sure you want to clear all logs?')) {
                return;
            }
            
            $.ajax({
                url: vortexRealtime.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_clear_logs',
                    nonce: vortexRealtime.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('Logs cleared successfully', 'success');
                        $('#debug-log .log-container').empty();
                    } else {
                        self.showNotification('Failed to clear logs', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Clear logs request failed', 'error');
                }
            });
        },
        
        // Update improvement progress
        updateImprovementProgress: function() {
            var self = this;
            
            setInterval(function() {
                self.state.improvementProgress = (self.state.improvementProgress + 1) % 100;
                $('#improvement-progress').css('width', self.state.improvementProgress + '%');
            }, 30000); // Update every 30 seconds
        },
        
        // Handle keyboard shortcuts
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + R: Refresh dashboard
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
                e.preventDefault();
                this.updateDashboard();
            }
            
            // Ctrl/Cmd + S: Trigger sync
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                e.preventDefault();
                this.triggerSync();
            }
            
            // Ctrl/Cmd + I: Trigger improvement
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 73) {
                e.preventDefault();
                this.triggerImprovement();
            }
            
            // Ctrl/Cmd + L: Trigger learning
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 76) {
                e.preventDefault();
                this.triggerLearning();
            }
        },
        
        // Show notification
        showNotification: function(message, type) {
            var notification = $('<div class="vortex-notification notification-' + type + '">' + message + '</div>');
            
            $('body').append(notification);
            
            notification.fadeIn().delay(3000).fadeOut(function() {
                $(this).remove();
            });
        },
        
        // Render system status HTML
        renderSystemStatus: function(status) {
            return `
                <div class="status-item">
                    <span class="status-label">Monitoring:</span>
                    <span class="status-value ${status.monitoring_active ? 'active' : 'inactive'}">
                        ${status.monitoring_active ? '🟢 Active' : '🔴 Inactive'}
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Recursive Loop:</span>
                    <span class="status-value ${status.recursive_loop_active ? 'active' : 'inactive'}">
                        ${status.recursive_loop_active ? '🟢 Active' : '🔴 Inactive'}
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Deep Learning:</span>
                    <span class="status-value ${status.deep_learning_active ? 'active' : 'inactive'}">
                        ${status.deep_learning_active ? '🟢 Active' : '🔴 Inactive'}
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Memory Usage:</span>
                    <span class="status-value">${this.formatBytes(status.memory_usage)}</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Peak Memory:</span>
                    <span class="status-value">${this.formatBytes(status.peak_memory)}</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Uptime:</span>
                    <span class="status-value">${this.formatUptime(status.uptime)}</span>
                </div>
            `;
        },
        
        // Render GitHub status HTML
        renderGitHubStatus: function(status) {
            var lastSync = status.last_sync_time ? new Date(status.last_sync_time * 1000).toLocaleString() : 'Never';
            
            return `
                <div class="github-status-item">
                    <span class="status-label">Last Sync:</span>
                    <span class="status-value">${lastSync}</span>
                </div>
                <div class="github-status-item">
                    <span class="status-label">Improvement Cycle:</span>
                    <span class="status-value">#${status.improvement_cycle}</span>
                </div>
                <div class="github-status-item">
                    <span class="status-label">Repository:</span>
                    <span class="status-value">mariannenems/vortexartec-ai-marketplace</span>
                </div>
                <div class="github-status-item">
                    <span class="status-label">Branch:</span>
                    <span class="status-value">main</span>
                </div>
            `;
        },
        
        // Render performance metrics HTML
        renderPerformanceMetrics: function(metrics) {
            var html = '<div class="metrics-grid">';
            
            for (var operation in metrics) {
                if (metrics[operation] && metrics[operation].length > 0) {
                    var data = metrics[operation];
                    var latest = data[data.length - 1];
                    var avgTime = data.reduce(function(sum, item) {
                        return sum + item.execution_time;
                    }, 0) / data.length;
                    
                    html += `
                        <div class="metric-item">
                            <span class="metric-label">${operation.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); })}:</span>
                            <span class="metric-value">${latest.execution_time.toFixed(3)}s</span>
                            <span class="metric-avg">(avg: ${avgTime.toFixed(3)}s)</span>
                        </div>
                    `;
                }
            }
            
            html += '</div>';
            return html;
        },
        
        // Render learning progress HTML
        renderLearningProgress: function(learningData) {
            return `
                <div class="learning-progress-grid">
                    <div class="progress-item">
                        <span class="progress-label">Patterns Learned:</span>
                        <span class="progress-value">${(learningData.patterns || []).length}</span>
                    </div>
                    <div class="progress-item">
                        <span class="progress-label">Optimizations Applied:</span>
                        <span class="progress-value">${(learningData.optimizations || []).length}</span>
                    </div>
                    <div class="progress-item">
                        <span class="progress-label">Error Patterns:</span>
                        <span class="progress-value">${(learningData.error_patterns || []).length}</span>
                    </div>
                    <div class="progress-item">
                        <span class="progress-label">Success Metrics:</span>
                        <span class="progress-value">${(learningData.success_metrics || []).length}</span>
                    </div>
                </div>
            `;
        },
        
        // Render improvement cycle HTML
        renderImprovementCycle: function(status) {
            return `
                <div class="improvement-cycle-info">
                    <div class="cycle-item">
                        <span class="cycle-label">Current Cycle:</span>
                        <span class="cycle-value">#${status.improvement_cycle}</span>
                    </div>
                    <div class="cycle-item">
                        <span class="cycle-label">Status:</span>
                        <span class="cycle-value ${status.recursive_loop_active ? 'active' : 'inactive'}">
                            ${status.recursive_loop_active ? '🔄 Running' : '⏸️ Paused'}
                        </span>
                    </div>
                    <div class="cycle-item">
                        <span class="cycle-label">Next Improvement:</span>
                        <span class="cycle-value" id="next-improvement">Calculating...</span>
                    </div>
                </div>
                <div class="improvement-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="improvement-progress" style="width: ${this.state.improvementProgress}%"></div>
                    </div>
                </div>
            `;
        },
        
        // Render log entry HTML
        renderLogEntry: function(log) {
            return `
                <div class="log-entry log-level-${log.level.toLowerCase()}">
                    <span class="log-timestamp">${log.timestamp}</span>
                    <span class="log-level">[${log.level}]</span>
                    <span class="log-message">${this.escapeHtml(log.message)}</span>
                    <span class="log-memory">${this.formatBytes(log.memory_usage)}</span>
                </div>
            `;
        },
        
        // Format bytes
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 B';
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        // Format uptime
        formatUptime: function(seconds) {
            var hours = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var secs = Math.floor(seconds % 60);
            return (hours < 10 ? '0' : '') + hours + ':' + 
                   (minutes < 10 ? '0' : '') + minutes + ':' + 
                   (secs < 10 ? '0' : '') + secs;
        },
        
        // Escape HTML
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
    
    // Expose to global scope
    window.vortexRealtimeDashboard = VortexRealtimeDashboard;
    
})(jQuery); 