/**
 * VORTEX AI Engine - Admin JavaScript
 * Handles admin interface functionality
 */

(function($) {
    'use strict';

    // Admin configuration
    const VortexAdmin = {
        config: {
            ajaxUrl: vortexAdminConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: vortexAdminConfig.nonce || '',
            restUrl: vortexAdminConfig.restUrl || '/wp-json/vortex/v1/',
            restNonce: vortexAdminConfig.restNonce || ''
        },

        init: function() {
            this.bindEvents();
            this.initializeComponents();
        },

        bindEvents: function() {
            // Admin dashboard events
            $(document).on('click', '.vortex-admin-btn', this.handleAdminAction);
            $(document).on('submit', '.vortex-admin-form', this.handleFormSubmit);
            
            // Settings panel events
            $(document).on('change', '.vortex-setting-toggle', this.handleSettingToggle);
            $(document).on('click', '.vortex-save-settings', this.saveSettings);
            
            // Analytics events
            $(document).on('click', '.vortex-refresh-stats', this.refreshStats);
            $(document).on('change', '.vortex-date-range', this.updateDateRange);
            
            // User management events
            $(document).on('click', '.vortex-user-action', this.handleUserAction);
            $(document).on('click', '.vortex-bulk-action', this.handleBulkAction);
        },

        initializeComponents: function() {
            // Initialize tooltips
            $('[data-tooltip]').tooltip();
            
            // Initialize date pickers
            $('.vortex-date-picker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
            
            // Initialize charts if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.initializeCharts();
            }
            
            // Initialize data tables
            $('.vortex-data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']]
            });
        },

        handleAdminAction: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const action = $btn.data('action');
            const target = $btn.data('target');
            
            $btn.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: VortexAdmin.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_admin_action',
                    admin_action: action,
                    target: target,
                    nonce: VortexAdmin.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.showNotification('Success: ' + response.data.message, 'success');
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        VortexAdmin.showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    VortexAdmin.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = new FormData($form[0]);
            
            formData.append('action', 'vortex_admin_form');
            formData.append('nonce', VortexAdmin.config.nonce);
            
            $.ajax({
                url: VortexAdmin.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.showNotification('Settings saved successfully', 'success');
                    } else {
                        VortexAdmin.showNotification('Error saving settings: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    VortexAdmin.showNotification('Network error occurred', 'error');
                }
            });
        },

        handleSettingToggle: function() {
            const $toggle = $(this);
            const setting = $toggle.data('setting');
            const value = $toggle.is(':checked');
            
            $.ajax({
                url: VortexAdmin.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_toggle_setting',
                    setting: setting,
                    value: value,
                    nonce: VortexAdmin.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.showNotification('Setting updated', 'success');
                    } else {
                        $toggle.prop('checked', !value); // Revert on error
                        VortexAdmin.showNotification('Error updating setting', 'error');
                    }
                },
                error: function() {
                    $toggle.prop('checked', !value); // Revert on error
                    VortexAdmin.showNotification('Network error occurred', 'error');
                }
            });
        },

        saveSettings: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $form = $btn.closest('form');
            
            $btn.prop('disabled', true).text('Saving...');
            
            const formData = new FormData($form[0]);
            formData.append('action', 'vortex_save_settings');
            formData.append('nonce', VortexAdmin.config.nonce);
            
            $.ajax({
                url: VortexAdmin.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.showNotification('Settings saved successfully', 'success');
                    } else {
                        VortexAdmin.showNotification('Error saving settings: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    VortexAdmin.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Save Settings');
                }
            });
        },

        refreshStats: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const dateRange = $('.vortex-date-range').val();
            
            $btn.prop('disabled', true).text('Refreshing...');
            
            $.ajax({
                url: VortexAdmin.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_refresh_stats',
                    date_range: dateRange,
                    nonce: VortexAdmin.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.updateStatsDisplay(response.data);
                        VortexAdmin.showNotification('Stats refreshed', 'success');
                    } else {
                        VortexAdmin.showNotification('Error refreshing stats', 'error');
                    }
                },
                error: function() {
                    VortexAdmin.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Refresh Stats');
                }
            });
        },

        updateDateRange: function() {
            VortexAdmin.refreshStats({ preventDefault: function() {} });
        },

        handleUserAction: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const action = $btn.data('action');
            const userId = $btn.data('user-id');
            
            if (!confirm('Are you sure you want to perform this action?')) {
                return;
            }
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: VortexAdmin.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_user_action',
                    user_action: action,
                    user_id: userId,
                    nonce: VortexAdmin.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.showNotification('User action completed', 'success');
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        VortexAdmin.showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    VortexAdmin.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        handleBulkAction: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const action = $btn.data('action');
            const selectedUsers = $('.vortex-user-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedUsers.length === 0) {
                VortexAdmin.showNotification('Please select users first', 'warning');
                return;
            }
            
            if (!confirm('Are you sure you want to perform this bulk action?')) {
                return;
            }
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: VortexAdmin.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_bulk_action',
                    bulk_action: action,
                    user_ids: selectedUsers,
                    nonce: VortexAdmin.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.showNotification('Bulk action completed', 'success');
                        location.reload();
                    } else {
                        VortexAdmin.showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    VortexAdmin.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        initializeCharts: function() {
            // Initialize usage chart
            const usageCtx = document.getElementById('vortex-usage-chart');
            if (usageCtx) {
                new Chart(usageCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'API Usage',
                            data: [12, 19, 3, 5, 2, 3],
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Initialize revenue chart
            const revenueCtx = document.getElementById('vortex-revenue-chart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Basic', 'Essential', 'Premium'],
                        datasets: [{
                            label: 'Revenue',
                            data: [300, 500, 1200],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 205, 86, 0.2)'
                            ],
                            borderColor: [
                                'rgb(255, 99, 132)',
                                'rgb(54, 162, 235)',
                                'rgb(255, 205, 86)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        },

        updateStatsDisplay: function(data) {
            // Update statistics display
            $('.vortex-stat-users').text(data.total_users || 0);
            $('.vortex-stat-revenue').text('$' + (data.total_revenue || 0));
            $('.vortex-stat-generations').text(data.total_generations || 0);
            $('.vortex-stat-active').text(data.active_users || 0);
        },

        showNotification: function(message, type) {
            const $notification = $(`
                <div class="vortex-notification vortex-notification-${type}">
                    <span class="vortex-notification-message">${message}</span>
                    <button class="vortex-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $notification.find('.vortex-notification-close').on('click', function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        VortexAdmin.init();
    });

})(jQuery); 