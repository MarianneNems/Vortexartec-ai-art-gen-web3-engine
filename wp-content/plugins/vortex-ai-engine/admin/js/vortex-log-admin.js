/**
 * VORTEX AI Engine - Real-Time Log Admin JavaScript
 * 
 * Handles AJAX loading, filtering, and interactive features for log management
 * Provides real-time updates and secure data handling
 */

(function($) {
    'use strict';
    
    // Global variables
    let currentPage = 1;
    let currentFilters = {};
    let refreshInterval = null;
    let isAutoRefresh = false;
    
    // Initialize when document is ready
    $(document).ready(function() {
        initializeLogAdmin();
    });
    
    /**
     * Initialize log admin interface
     */
    function initializeLogAdmin() {
        // Load initial logs
        loadLogs();
        
        // Setup event handlers
        setupEventHandlers();
        
        // Setup auto-refresh
        setupAutoRefresh();
        
        // Load statistics
        loadStatistics();
    }
    
    /**
     * Setup event handlers
     */
    function setupEventHandlers() {
        // Filter form submission
        $('#vortex-log-filter-form').on('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadLogs();
        });
        
        // Clear filters
        $('#vortex-clear-filters').on('click', function() {
            $('#vortex-log-filter-form')[0].reset();
            currentFilters = {};
            currentPage = 1;
            loadLogs();
        });
        
        // Export logs
        $('#vortex-export-logs').on('click', function() {
            exportLogs();
        });
        
        // Clear logs
        $('#vortex-clear-logs').on('click', function() {
            clearLogs();
        });
        
        // Optimize database
        $('#vortex-optimize-db').on('click', function() {
            optimizeDatabase();
        });
        
        // Refresh logs
        $('#vortex-refresh-logs').on('click', function() {
            loadLogs();
            loadStatistics();
        });
        
        // Toggle auto-refresh
        $('#vortex-toggle-refresh').on('click', function() {
            toggleAutoRefresh();
        });
        
        // Modal close
        $('.vortex-modal-close').on('click', function() {
            closeModal();
        });
        
        // Close modal on outside click
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('vortex-modal')) {
                closeModal();
            }
        });
        
        // View context button
        $(document).on('click', '.vortex-view-context', function() {
            const logId = $(this).data('log-id');
            viewLogContext(logId);
        });
        
        // View details button
        $(document).on('click', '.vortex-view-details', function() {
            const logId = $(this).data('log-id');
            viewLogDetails(logId);
        });
        
        // Pagination
        $(document).on('click', '.vortex-pagination .page-numbers', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                currentPage = page;
                loadLogs();
            }
        });
    }
    
    /**
     * Load logs via AJAX
     */
    function loadLogs() {
        showLoading();
        
        // Collect filters
        const filters = {
            level: $('#log-level').val(),
            user_id: $('#log-user').val(),
            date_from: $('#log-date-from').val(),
            date_to: $('#log-date-to').val(),
            page: currentPage
        };
        
        // Remove empty filters
        Object.keys(filters).forEach(key => {
            if (!filters[key]) {
                delete filters[key];
            }
        });
        
        currentFilters = filters;
        
        $.ajax({
            url: vortexLogAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_logs',
                nonce: vortexLogAjax.nonce,
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    displayLogs(response.data.logs);
                    updatePagination(response.data.total);
                } else {
                    showError('Failed to load logs: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                showError('AJAX error: ' + error);
            },
            complete: function() {
                hideLoading();
            }
        });
    }
    
    /**
     * Display logs in table
     */
    function displayLogs(logs) {
        const tbody = $('#vortex-log-tbody');
        tbody.empty();
        
        if (logs.length === 0) {
            tbody.append('<tr><td colspan="7" style="text-align: center; padding: 40px; color: #666;">No logs found matching the current filters.</td></tr>');
            return;
        }
        
        logs.forEach(function(log) {
            const row = createLogRow(log);
            tbody.append(row);
        });
    }
    
    /**
     * Create log row HTML
     */
    function createLogRow(log) {
        const template = $('#vortex-log-row-template').html();
        
        return template
            .replace(/\{\{id\}\}/g, log.id)
            .replace(/\{\{timestamp\}\}/g, log.timestamp)
            .replace(/\{\{level\}\}/g, log.level)
            .replace(/\{\{message\}\}/g, escapeHtml(log.message))
            .replace(/\{\{user_info\}\}/g, escapeHtml(log.user_info))
            .replace(/\{\{ip_address\}\}/g, log.ip_address)
            .replace(/\{\{has_context\}\}/g, log.has_context)
            .replace(/\{\{encrypted\}\}/g, log.encrypted);
    }
    
    /**
     * Update pagination
     */
    function updatePagination(total) {
        const itemsPerPage = 50;
        const totalPages = Math.ceil(total / itemsPerPage);
        
        if (totalPages <= 1) {
            $('#vortex-log-pagination').hide();
            return;
        }
        
        $('#vortex-log-pagination').show();
        
        let paginationHtml = '<div class="vortex-pagination-info">';
        paginationHtml += `Showing page ${currentPage} of ${totalPages} (${total} total entries)`;
        paginationHtml += '</div>';
        
        paginationHtml += '<div class="vortex-pagination-links">';
        
        // Previous page
        if (currentPage > 1) {
            paginationHtml += `<a href="#" class="page-numbers" data-page="${currentPage - 1}">← Previous</a>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const currentClass = i === currentPage ? 'current' : '';
            paginationHtml += `<a href="#" class="page-numbers ${currentClass}" data-page="${i}">${i}</a>`;
        }
        
        // Next page
        if (currentPage < totalPages) {
            paginationHtml += `<a href="#" class="page-numbers" data-page="${currentPage + 1}">Next →</a>`;
        }
        
        paginationHtml += '</div>';
        
        $('#vortex-log-pagination').html(paginationHtml);
    }
    
    /**
     * Export logs
     */
    function exportLogs() {
        if (!confirm(vortexLogAjax.strings.confirm_export)) {
            return;
        }
        
        // Create form for download
        const form = $('<form>', {
            method: 'POST',
            action: vortexLogAjax.ajaxurl,
            target: '_blank'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'vortex_export_logs'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: vortexLogAjax.nonce
        }));
        
        // Add current filters
        Object.keys(currentFilters).forEach(key => {
            if (key !== 'page') {
                form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: currentFilters[key]
                }));
            }
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    /**
     * Clear logs
     */
    function clearLogs() {
        const days = prompt('Enter number of days of logs to clear (default: 30):', '30');
        
        if (!days || isNaN(days)) {
            return;
        }
        
        if (!confirm(vortexLogAjax.strings.confirm_clear)) {
            return;
        }
        
        $.ajax({
            url: vortexLogAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_clear_logs',
                nonce: vortexLogAjax.nonce,
                days: parseInt(days)
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    loadLogs();
                    loadStatistics();
                } else {
                    showError('Failed to clear logs: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                showError('AJAX error: ' + error);
            }
        });
    }
    
    /**
     * Optimize database
     */
    function optimizeDatabase() {
        $.ajax({
            url: vortexLogAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_optimize_database',
                nonce: vortexLogAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Database optimized successfully');
                } else {
                    showError('Failed to optimize database: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                showError('AJAX error: ' + error);
            }
        });
    }
    
    /**
     * View log context
     */
    function viewLogContext(logId) {
        // Find the log entry and display its context
        const logRow = $(`[data-log-id="${logId}"]`).closest('tr');
        const context = logRow.find('.vortex-log-context').data('context');
        
        if (context) {
            showModal('Log Context', `<pre>${JSON.stringify(JSON.parse(context), null, 2)}</pre>`);
        } else {
            showError('No context available for this log entry');
        }
    }
    
    /**
     * View log details
     */
    function viewLogDetails(logId) {
        // This would typically load full log details via AJAX
        // For now, we'll show a placeholder
        showModal('Log Details', `<p>Detailed information for log ID: ${logId}</p><p>This would show decrypted data, full context, and additional metadata.</p>`);
    }
    
    /**
     * Load statistics
     */
    function loadStatistics() {
        $.ajax({
            url: vortexLogAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_statistics',
                nonce: vortexLogAjax.nonce,
                days: 7
            },
            success: function(response) {
                if (response.success) {
                    updateStatistics(response.data);
                }
            }
        });
    }
    
    /**
     * Update statistics display
     */
    function updateStatistics(statistics) {
        // Update statistics display
        // This would update the statistics dashboard with new data
    }
    
    /**
     * Setup auto-refresh
     */
    function setupAutoRefresh() {
        // Auto-refresh every 30 seconds
        refreshInterval = setInterval(function() {
            if (isAutoRefresh) {
                loadLogs();
                loadStatistics();
            }
        }, 30000);
    }
    
    /**
     * Toggle auto-refresh
     */
    function toggleAutoRefresh() {
        isAutoRefresh = !isAutoRefresh;
        const button = $('#vortex-toggle-refresh');
        
        if (isAutoRefresh) {
            button.text('Disable Auto-Refresh').removeClass('button-primary').addClass('button-secondary');
            showSuccess('Auto-refresh enabled');
        } else {
            button.text('Enable Auto-Refresh').removeClass('button-secondary').addClass('button-primary');
            showSuccess('Auto-refresh disabled');
        }
    }
    
    /**
     * Show modal
     */
    function showModal(title, content) {
        $('#vortex-log-modal h2').text(title);
        $('#vortex-log-details').html(content);
        $('#vortex-log-modal').show();
    }
    
    /**
     * Close modal
     */
    function closeModal() {
        $('#vortex-log-modal').hide();
    }
    
    /**
     * Show loading indicator
     */
    function showLoading() {
        $('#vortex-log-loading').show();
        $('#vortex-log-table-container').hide();
    }
    
    /**
     * Hide loading indicator
     */
    function hideLoading() {
        $('#vortex-log-loading').hide();
        $('#vortex-log-table-container').show();
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        showNotification(message, 'success');
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        showNotification(message, 'error');
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const notification = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        
        // Insert after the page title
        $('.wrap h1').after(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        notification.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        notification.find('.notice-dismiss').on('click', function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Format timestamp
     */
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString();
    }
    
    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Debounce function
     */
    function debounce(func, wait, immediate) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    // Export functions for global access
    window.VortexLogAdmin = {
        loadLogs: loadLogs,
        exportLogs: exportLogs,
        clearLogs: clearLogs,
        showModal: showModal,
        closeModal: closeModal
    };
    
})(jQuery); 