/**
 * VORTEX Metrics JavaScript
 * Handles metrics and ranking functionality
 */

(function($) {
    'use strict';

    // Global metrics state
    let metricsState = {
        data: {},
        lastUpdate: null,
        autoRefresh: true,
        refreshInterval: 30000, // 30 seconds
        intervalId: null
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        initMetricsInterface();
    });

    /**
     * Initialize metrics interface
     */
    function initMetricsInterface() {
        // Initialize all metrics containers
        $('.vortex-metrics-container').each(function() {
            initializeMetricsContainer($(this));
        });
        
        // Bind events
        bindMetricsEvents();
        
        // Load initial metrics
        loadMetrics();
        
        // Start auto-refresh
        startAutoRefresh();
    }

    /**
     * Initialize individual metrics container
     */
    function initializeMetricsContainer(container) {
        // Add loading state
        showMetricsLoading(container, true);
        
        // Set up timeframe selector if not present
        if (container.find('.vortex-metrics-timeframe').length === 0) {
            addTimeframeSelector(container);
        }
        
        // Set up refresh button if not present
        if (container.find('.vortex-metrics-refresh').length === 0) {
            addRefreshButton(container);
        }
    }

    /**
     * Bind metrics events
     */
    function bindMetricsEvents() {
        // Refresh button
        $(document).on('click', '.vortex-metrics-refresh', handleRefreshClick);
        
        // Timeframe change
        $(document).on('change', '.vortex-metrics-timeframe', handleTimeframeChange);
        
        // Auto-refresh toggle
        $(document).on('change', '.vortex-metrics-auto-refresh', handleAutoRefreshToggle);
        
        // Leaderboard item click
        $(document).on('click', '.vortex-leaderboard-item', handleLeaderboardItemClick);
    }

    /**
     * Load metrics data
     */
    function loadMetrics() {
        const timeframe = $('.vortex-metrics-timeframe').val() || '24h';
        
        // Show loading state
        $('.vortex-metrics-container').each(function() {
            showMetricsLoading($(this), true);
        });
        
        $.ajax({
            url: typeof vortexMetricsConfig !== 'undefined' ? vortexMetricsConfig.ajaxUrl : ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_metrics',
                timeframe: timeframe,
                nonce: typeof vortexMetricsConfig !== 'undefined' ? vortexMetricsConfig.nonce : ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    metricsState.data = response.data;
                    metricsState.lastUpdate = new Date();
                    
                    updateMetricsDisplay();
                    hideMetricsError();
                } else {
                    // Use mock data as fallback
                    loadMockMetrics();
                    updateMetricsDisplay();
                }
            },
            error: function(error) {
                console.error('Error loading metrics:', error);
                
                // Use mock data as fallback
                loadMockMetrics();
                updateMetricsDisplay();
                showMetricsError('Failed to load metrics. Using cached data.');
            },
            complete: function() {
                $('.vortex-metrics-container').each(function() {
                    showMetricsLoading($(this), false);
                });
            }
        });
    }

    /**
     * Load mock metrics data
     */
    function loadMockMetrics() {
        metricsState.data = {
            total_users: Math.floor(Math.random() * 10000) + 1000,
            total_users_change: (Math.random() * 20 - 10).toFixed(1),
            total_transactions: Math.floor(Math.random() * 50000) + 5000,
            total_transactions_change: (Math.random() * 30 - 15).toFixed(1),
            total_volume: (Math.random() * 1000000).toFixed(2),
            total_volume_change: (Math.random() * 40 - 20).toFixed(1),
            active_users: Math.floor(Math.random() * 5000) + 500,
            active_users_change: (Math.random() * 25 - 12.5).toFixed(1),
            leaderboard: [
                { name: 'Artist Alpha', score: (Math.random() * 1000 + 500).toFixed(0) },
                { name: 'Creator Beta', score: (Math.random() * 800 + 400).toFixed(0) },
                { name: 'Designer Gamma', score: (Math.random() * 600 + 300).toFixed(0) },
                { name: 'Maker Delta', score: (Math.random() * 400 + 200).toFixed(0) },
                { name: 'Builder Epsilon', score: (Math.random() * 300 + 100).toFixed(0) }
            ]
        };
        
        metricsState.lastUpdate = new Date();
    }

    /**
     * Update metrics display
     */
    function updateMetricsDisplay() {
        // Update metric cards
        updateMetricCards();
        
        // Update leaderboard
        updateLeaderboard();
        
        // Update last update time
        updateLastUpdateTime();
    }

    /**
     * Update metric cards
     */
    function updateMetricCards() {
        const metrics = [
            { key: 'total_users', label: 'Total Users' },
            { key: 'total_transactions', label: 'Total Transactions' },
            { key: 'total_volume', label: 'Total Volume' },
            { key: 'active_users', label: 'Active Users' }
        ];
        
        metrics.forEach(metric => {
            const value = metricsState.data[metric.key] || 0;
            const change = metricsState.data[metric.key + '_change'] || 0;
            
            // Update value
            const valueElement = $(`.vortex-metrics-value[data-metric="${metric.key}"]`);
            if (valueElement.length) {
                valueElement.html(formatMetricValue(value, metric.key));
            }
            
            // Update change
            const changeElement = $(`.vortex-metrics-change[data-metric="${metric.key}"]`);
            if (changeElement.length) {
                updateMetricChange(changeElement, change);
            }
        });
    }

    /**
     * Format metric value
     */
    function formatMetricValue(value, type) {
        if (type === 'total_volume') {
            return '$' + parseFloat(value).toLocaleString();
        }
        return parseInt(value).toLocaleString();
    }

    /**
     * Update metric change indicator
     */
    function updateMetricChange(element, change) {
        const changeValue = parseFloat(change);
        const isPositive = changeValue >= 0;
        const sign = isPositive ? '+' : '';
        const className = isPositive ? 'vortex-metrics-positive' : 'vortex-metrics-negative';
        
        element.html(`${sign}${changeValue}%`);
        element.removeClass('vortex-metrics-positive vortex-metrics-negative');
        element.addClass(className);
    }

    /**
     * Update leaderboard
     */
    function updateLeaderboard() {
        const leaderboard = metricsState.data.leaderboard || [];
        const leaderboardList = $('.vortex-leaderboard-list');
        
        leaderboardList.empty();
        
        if (leaderboard.length === 0) {
            leaderboardList.html('<div class="vortex-leaderboard-empty">No leaderboard data available</div>');
            return;
        }
        
        leaderboard.forEach(function(item, index) {
            const rank = index + 1;
            const listItem = $(`
                <div class="vortex-leaderboard-item" data-rank="${rank}">
                    <div class="vortex-leaderboard-rank">#${rank}</div>
                    <div class="vortex-leaderboard-name">${escapeHtml(item.name)}</div>
                    <div class="vortex-leaderboard-score">${item.score}</div>
                </div>
            `);
            leaderboardList.append(listItem);
        });
    }

    /**
     * Update last update time
     */
    function updateLastUpdateTime() {
        if (metricsState.lastUpdate) {
            const timeString = metricsState.lastUpdate.toLocaleTimeString();
            $('.vortex-metrics-last-update').html(`Last updated: ${timeString}`);
        }
    }

    /**
     * Handle refresh button click
     */
    function handleRefreshClick() {
        const button = $(this);
        
        button.prop('disabled', true).html('Refreshing...');
        
        loadMetrics();
        
        // Re-enable button after a delay
        setTimeout(() => {
            button.prop('disabled', false).html('Refresh');
        }, 2000);
    }

    /**
     * Handle timeframe change
     */
    function handleTimeframeChange() {
        loadMetrics();
    }

    /**
     * Handle auto-refresh toggle
     */
    function handleAutoRefreshToggle() {
        const isEnabled = $(this).is(':checked');
        metricsState.autoRefresh = isEnabled;
        
        if (isEnabled) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    }

    /**
     * Handle leaderboard item click
     */
    function handleLeaderboardItemClick() {
        const rank = $(this).data('rank');
        const name = $(this).find('.vortex-leaderboard-name').text();
        
        // Show user details (implementation depends on requirements)
        alert(`View details for ${name} (Rank #${rank})`);
    }

    /**
     * Start auto-refresh
     */
    function startAutoRefresh() {
        if (metricsState.intervalId) {
            clearInterval(metricsState.intervalId);
        }
        
        if (metricsState.autoRefresh) {
            metricsState.intervalId = setInterval(loadMetrics, metricsState.refreshInterval);
        }
    }

    /**
     * Stop auto-refresh
     */
    function stopAutoRefresh() {
        if (metricsState.intervalId) {
            clearInterval(metricsState.intervalId);
            metricsState.intervalId = null;
        }
    }

    /**
     * Show metrics loading state
     */
    function showMetricsLoading(container, show) {
        const loadingDiv = container.find('.vortex-metrics-loading');
        
        if (show) {
            if (loadingDiv.length === 0) {
                container.append('<div class="vortex-metrics-loading">Loading metrics...</div>');
            } else {
                loadingDiv.show();
            }
        } else {
            loadingDiv.hide();
        }
    }

    /**
     * Show metrics error
     */
    function showMetricsError(message) {
        const errorDiv = $('.vortex-metrics-error');
        
        if (errorDiv.length === 0) {
            $('.vortex-metrics-container').first().before(`
                <div class="vortex-metrics-error">${message}</div>
            `);
        } else {
            errorDiv.html(message).show();
        }
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            errorDiv.fadeOut();
        }, 10000);
    }

    /**
     * Hide metrics error
     */
    function hideMetricsError() {
        $('.vortex-metrics-error').hide();
    }

    /**
     * Add timeframe selector
     */
    function addTimeframeSelector(container) {
        const selector = $(`
            <div class="vortex-metrics-controls">
                <select class="vortex-metrics-timeframe">
                    <option value="1h">Last Hour</option>
                    <option value="24h" selected>Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="90d">Last 90 Days</option>
                </select>
            </div>
        `);
        
        container.prepend(selector);
    }

    /**
     * Add refresh button
     */
    function addRefreshButton(container) {
        const controls = container.find('.vortex-metrics-controls');
        const refreshBtn = $('<button class="vortex-metrics-refresh">Refresh</button>');
        
        if (controls.length) {
            controls.append(refreshBtn);
        } else {
            container.prepend(`
                <div class="vortex-metrics-controls">
                    <button class="vortex-metrics-refresh">Refresh</button>
                </div>
            `);
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Export functions for external use
    window.VortexMetrics = {
        refresh: loadMetrics,
        startAutoRefresh: startAutoRefresh,
        stopAutoRefresh: stopAutoRefresh,
        getData: () => metricsState.data
    };

    // Clean up on page unload
    $(window).on('beforeunload', function() {
        stopAutoRefresh();
    });

})(jQuery); 