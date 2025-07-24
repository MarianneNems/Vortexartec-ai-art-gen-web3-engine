/**
 * VORTEX AI Engine - Solana Dashboard JavaScript
 * 
 * Interactive functionality for Solana blockchain dashboard
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

(function($) {
    'use strict';
    
    // Global variables
    let metricsRefreshInterval;
    let connectionTestInterval;
    
    // Initialize dashboard
    $(document).ready(function() {
        initializeDashboard();
        setupEventListeners();
        startAutoRefresh();
    });
    
    /**
     * Initialize dashboard
     */
    function initializeDashboard() {
        console.log('VORTEX AI Engine: Initializing Solana Dashboard');
        
        // Add loading states
        addLoadingStates();
        
        // Initialize tooltips
        initializeTooltips();
        
        // Set up real-time updates
        setupRealTimeUpdates();
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Refresh metrics button
        $(document).on('click', '.refresh-metrics', function(e) {
            e.preventDefault();
            refreshMetrics();
        });
        
        // Test connection button
        $(document).on('click', '.test-connection', function(e) {
            e.preventDefault();
            testConnection();
        });
        
        // Deploy program form
        $(document).on('submit', '#vortex-solana-deploy-form', function(e) {
            e.preventDefault();
            deployProgram($(this));
        });
        
        // Network selector
        $(document).on('change', '.network-selector', function() {
            const network = $(this).val();
            switchNetwork(network);
        });
        
        // Modal close
        $(document).on('click', '.vortex-solana-modal-close', function() {
            closeModal();
        });
        
        // Close modal on outside click
        $(document).on('click', '.vortex-solana-modal', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
    
    /**
     * Add loading states
     */
    function addLoadingStates() {
        $('.vortex-solana-card').each(function() {
            $(this).append('<div class="loading-overlay" style="display: none;"></div>');
        });
    }
    
    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        $('[data-tooltip]').each(function() {
            const tooltip = $(this).attr('data-tooltip');
            $(this).append(`<span class="tooltiptext">${tooltip}</span>`);
        });
    }
    
    /**
     * Setup real-time updates
     */
    function setupRealTimeUpdates() {
        // Update timestamps every minute
        setInterval(function() {
            updateTimestamps();
        }, 60000);
        
        // Update performance indicators
        setInterval(function() {
            updatePerformanceIndicators();
        }, 30000);
    }
    
    /**
     * Start auto refresh
     */
    function startAutoRefresh() {
        // Refresh metrics every 5 minutes
        metricsRefreshInterval = setInterval(function() {
            refreshMetrics();
        }, 300000);
        
        // Test connection every 10 minutes
        connectionTestInterval = setInterval(function() {
            testConnection();
        }, 600000);
    }
    
    /**
     * Refresh metrics
     */
    function refreshMetrics() {
        showLoading('.vortex-solana-metrics-grid');
        
        $.ajax({
            url: vortexSolana.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_solana_refresh_metrics',
                nonce: vortexSolana.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateMetricsDisplay(response.data);
                    showNotification('Metrics refreshed successfully', 'success');
                } else {
                    showNotification('Failed to refresh metrics: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error refreshing metrics: ' + error, 'error');
            },
            complete: function() {
                hideLoading('.vortex-solana-metrics-grid');
            }
        });
    }
    
    /**
     * Test connection
     */
    function testConnection() {
        showLoading('.vortex-solana-section:first');
        
        $.ajax({
            url: vortexSolana.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_solana_test_connection',
                nonce: vortexSolana.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateConnectionStatus(response.data);
                    showNotification('Connection test successful', 'success');
                } else {
                    showNotification('Connection test failed: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error testing connection: ' + error, 'error');
            },
            complete: function() {
                hideLoading('.vortex-solana-section:first');
            }
        });
    }
    
    /**
     * Deploy program
     */
    function deployProgram(form) {
        const formData = form.serialize();
        
        showLoading('#vortex-solana-deploy-modal');
        
        $.ajax({
            url: vortexSolana.ajaxUrl,
            type: 'POST',
            data: formData + '&action=vortex_solana_deploy_program&nonce=' + vortexSolana.nonce,
            success: function(response) {
                if (response.success) {
                    showNotification('Program deployed successfully! Program ID: ' + response.data.program_id, 'success');
                    closeModal();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Deployment failed: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error deploying program: ' + error, 'error');
            },
            complete: function() {
                hideLoading('#vortex-solana-deploy-modal');
            }
        });
    }
    
    /**
     * Switch network
     */
    function switchNetwork(network) {
        showLoading('.vortex-solana-dashboard');
        
        $.ajax({
            url: vortexSolana.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_solana_switch_network',
                network: network,
                nonce: vortexSolana.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showNotification('Failed to switch network: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error switching network: ' + error, 'error');
            },
            complete: function() {
                hideLoading('.vortex-solana-dashboard');
            }
        });
    }
    
    /**
     * Update metrics display
     */
    function updateMetricsDisplay(data) {
        if (data.slot) {
            $('.vortex-solana-card:contains("Current Slot") .vortex-solana-value').text(formatNumber(data.slot));
        }
        
        if (data.block_height) {
            $('.vortex-solana-card:contains("Block Height") .vortex-solana-value').text(formatNumber(data.block_height));
        }
        
        if (data.transaction_count) {
            $('.vortex-solana-card:contains("Transactions") .vortex-solana-value').text(formatNumber(data.transaction_count));
        }
        
        if (data.validator_count) {
            $('.vortex-solana-card:contains("Validators") .vortex-solana-value').text(formatNumber(data.validator_count));
        }
        
        if (data.supply) {
            $('.vortex-solana-card:contains("Supply") .vortex-solana-value').text(formatNumber(data.supply / 1000000000, 2));
        }
        
        if (data.cluster_nodes) {
            $('.vortex-solana-card:contains("Cluster Nodes") .vortex-solana-value').text(formatNumber(data.cluster_nodes));
        }
    }
    
    /**
     * Update connection status
     */
    function updateConnectionStatus(data) {
        const statusElements = $('.vortex-solana-card .vortex-solana-value');
        
        statusElements.each(function() {
            const text = $(this).text();
            
            if (text.includes('RPC')) {
                $(this).removeClass('status-ok status-error status-warning')
                       .addClass(data.rpc_status ? 'status-ok' : 'status-error')
                       .text(data.rpc_status ? 'Connected' : 'Disconnected');
            }
            
            if (text.includes('Metrics')) {
                $(this).removeClass('status-ok status-error status-warning')
                       .addClass(data.metrics_status ? 'status-ok' : 'status-error')
                       .text(data.metrics_status ? 'Connected' : 'Disconnected');
            }
            
            if (text.includes('Validator')) {
                $(this).removeClass('status-ok status-error status-warning')
                       .addClass(data.validator_status ? 'status-ok' : 'status-warning')
                       .text(data.validator_status ? 'Connected' : 'Warning');
            }
        });
    }
    
    /**
     * Update timestamps
     */
    function updateTimestamps() {
        $('.vortex-solana-dashboard .timestamp').each(function() {
            const timestamp = $(this).data('timestamp');
            if (timestamp) {
                $(this).text(formatTimestamp(timestamp));
            }
        });
    }
    
    /**
     * Update performance indicators
     */
    function updatePerformanceIndicators() {
        $('.performance-bar').each(function() {
            const currentValue = parseInt($(this).data('value'));
            const maxValue = parseInt($(this).data('max'));
            const percentage = (currentValue / maxValue) * 100;
            
            $(this).find('.performance-fill').css('width', percentage + '%');
        });
    }
    
    /**
     * Show modal
     */
    function showModal(modalId) {
        $('#' + modalId).fadeIn(300);
        $('body').addClass('modal-open');
    }
    
    /**
     * Close modal
     */
    function closeModal() {
        $('.vortex-solana-modal').fadeOut(300);
        $('body').removeClass('modal-open');
    }
    
    /**
     * Show loading state
     */
    function showLoading(selector) {
        $(selector).addClass('vortex-solana-loading');
        $(selector).find('.loading-overlay').show();
    }
    
    /**
     * Hide loading state
     */
    function hideLoading(selector) {
        $(selector).removeClass('vortex-solana-loading');
        $(selector).find('.loading-overlay').hide();
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="vortex-solana-notification notification-${type}">
                <span class="notification-message">${message}</span>
                <span class="notification-close">&times;</span>
            </div>
        `);
        
        $('body').append(notification);
        
        notification.fadeIn(300);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Close button
        notification.find('.notification-close').on('click', function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Format number
     */
    function formatNumber(num, decimals = 0) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(decimals) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(decimals) + 'K';
        } else {
            return num.toFixed(decimals);
        }
    }
    
    /**
     * Format timestamp
     */
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) {
            return 'Just now';
        } else if (diff < 3600) {
            return Math.floor(diff / 60) + ' minutes ago';
        } else if (diff < 86400) {
            return Math.floor(diff / 3600) + ' hours ago';
        } else {
            return date.toLocaleDateString();
        }
    }
    
    /**
     * Create performance chart
     */
    function createPerformanceChart(containerId, data) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        // Simple bar chart implementation
        const chart = document.createElement('div');
        chart.className = 'performance-chart';
        
        data.forEach(function(item, index) {
            const bar = document.createElement('div');
            bar.className = 'chart-bar';
            bar.style.height = (item.value / Math.max(...data.map(d => d.value))) * 100 + '%';
            bar.style.backgroundColor = getColorForIndex(index);
            bar.title = item.label + ': ' + item.value;
            chart.appendChild(bar);
        });
        
        container.appendChild(chart);
    }
    
    /**
     * Get color for chart index
     */
    function getColorForIndex(index) {
        const colors = ['#9945FF', '#14F195', '#FF6B6B', '#FFD93D', '#6BCF7F'];
        return colors[index % colors.length];
    }
    
    /**
     * Export data
     */
    function exportData(type) {
        $.ajax({
            url: vortexSolana.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_solana_export_data',
                type: type,
                nonce: vortexSolana.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([JSON.stringify(response.data, null, 2)], {type: 'application/json'});
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `vortex-solana-${type}-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showNotification('Data exported successfully', 'success');
                } else {
                    showNotification('Export failed: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error exporting data: ' + error, 'error');
            }
        });
    }
    
    /**
     * Search and filter
     */
    function setupSearchFilter() {
        $('.vortex-solana-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('.vortex-solana-card').each(function() {
                const text = $(this).text().toLowerCase();
                if (text.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }
    
    // Global functions for external access
    window.vortexSolanaRefreshMetrics = refreshMetrics;
    window.vortexSolanaTestConnection = testConnection;
    window.vortexSolanaShowDeployForm = function() {
        showModal('vortex-solana-deploy-modal');
    };
    window.vortexSolanaHideDeployForm = closeModal;
    window.vortexSolanaExportData = exportData;
    
})(jQuery); 