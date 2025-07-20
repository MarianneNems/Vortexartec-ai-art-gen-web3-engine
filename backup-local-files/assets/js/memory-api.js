/**
 * Memory API JavaScript
 * Handles live memory updates and interactive timeline
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 */

(function($) {
    'use strict';
    
    class HuraiiMemoryAPI {
        constructor() {
            this.containers = [];
            this.updateInterval = 5000; // 5 seconds
            this.lastUpdate = 0;
            this.isLive = true;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initializeContainers();
            this.startLiveUpdates();
        }
        
        bindEvents() {
            $(document).on('click', '.huraii-memory-refresh', this.refreshMemory.bind(this));
            $(document).on('click', '.huraii-memory-clear', this.clearMemory.bind(this));
            $(document).on('click', '.huraii-memory-toggle-live', this.toggleLive.bind(this));
            $(document).on('click', '.huraii-memory-item', this.expandItem.bind(this));
            
            // Handle window focus/blur for efficient polling
            $(window).on('focus', () => {
                this.isLive = true;
                this.startLiveUpdates();
            });
            
            $(window).on('blur', () => {
                this.isLive = false;
                this.stopLiveUpdates();
            });
        }
        
        initializeContainers() {
            $('.huraii-memory-container').each((index, container) => {
                const $container = $(container);
                const config = {
                    element: $container,
                    userId: $container.data('user-id'),
                    limit: $container.data('limit'),
                    liveUpdate: $container.data('live-update'),
                    theme: $container.data('theme'),
                    lastTimestamp: 0
                };
                
                this.containers.push(config);
                this.loadMemory(config);
            });
        }
        
        loadMemory(config) {
            const $container = config.element;
            const $loading = $container.find('.huraii-memory-loading');
            const $timeline = $container.find('.huraii-memory-timeline');
            
            $loading.show();
            
            // Determine endpoint based on user ID
            const endpoint = config.userId == huraii_memory.current_user_id ? 
                'current' : config.userId;
            
            const params = {
                limit: config.limit,
                since: config.lastTimestamp
            };
            
            $.ajax({
                url: `${huraii_memory.rest_url}${endpoint}`,
                method: 'GET',
                data: params,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', huraii_memory.nonce);
                },
                success: (response) => {
                    $loading.hide();
                    
                    if (response.success) {
                        this.renderMemoryItems(config, response.data);
                        
                        // Update last timestamp
                        if (response.data.length > 0) {
                            config.lastTimestamp = response.data[0].timestamp;
                        }
                        
                        // Update status
                        $container.find('.huraii-memory-status').text('Live');
                    } else {
                        this.showError($container, 'Failed to load memory');
                    }
                },
                error: (xhr, status, error) => {
                    $loading.hide();
                    this.showError($container, `Error: ${error}`);
                }
            });
        }
        
        renderMemoryItems(config, items) {
            const $timeline = config.element.find('.huraii-memory-timeline');
            
            if (items.length === 0 && config.lastTimestamp === 0) {
                $timeline.html('<div class="huraii-memory-empty">No memory items yet</div>');
                return;
            }
            
            // If this is a live update, prepend new items
            if (config.lastTimestamp > 0) {
                items.forEach(item => {
                    const $item = this.createMemoryItem(item, config.theme);
                    $timeline.prepend($item);
                });
            } else {
                // Full refresh
                $timeline.empty();
                items.forEach(item => {
                    const $item = this.createMemoryItem(item, config.theme);
                    $timeline.append($item);
                });
            }
            
            // Limit items to prevent memory bloat
            const $items = $timeline.find('.huraii-memory-item');
            if ($items.length > config.limit * 2) {
                $items.slice(config.limit * 2).remove();
            }
        }
        
        createMemoryItem(item, theme) {
            const qualityClass = this.getQualityClass(item.quality_score);
            const actionIcon = this.getActionIcon(item.action);
            
            return $(`
                <div class="huraii-memory-item ${theme}" data-timestamp="${item.timestamp}">
                    <div class="memory-item-header">
                        <span class="memory-item-icon">${actionIcon}</span>
                        <span class="memory-item-action">${item.action}</span>
                        <span class="memory-item-time">${item.relative_time}</span>
                        <span class="memory-item-quality ${qualityClass}">${(item.quality_score * 100).toFixed(0)}%</span>
                    </div>
                    <div class="memory-item-content">
                        <div class="memory-item-summary">
                            <span class="memory-cost">$${item.cost.toFixed(4)}</span>
                            <span class="memory-time">${item.processing_time.toFixed(2)}s</span>
                        </div>
                        <div class="memory-item-details" style="display: none;">
                            <div class="memory-details-grid">
                                <div class="memory-detail">
                                    <strong>Quality Score:</strong> ${item.quality_score.toFixed(3)}
                                </div>
                                <div class="memory-detail">
                                    <strong>Processing Time:</strong> ${item.processing_time.toFixed(2)}s
                                </div>
                                <div class="memory-detail">
                                    <strong>Cost:</strong> $${item.cost.toFixed(4)}
                                </div>
                                <div class="memory-detail">
                                    <strong>Timestamp:</strong> ${item.formatted_timestamp}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        getQualityClass(score) {
            if (score >= 0.8) return 'quality-high';
            if (score >= 0.6) return 'quality-medium';
            return 'quality-low';
        }
        
        getActionIcon(action) {
            const icons = {
                'generate': '🎨',
                'describe': '📝',
                'upscale': '🔍',
                'enhance': '✨',
                'edit': '✏️',
                'vary': '🔄',
                'tola_masterwork': '👑',
                'batch_generate': '📦',
                'custom_model': '🧠'
            };
            
            return icons[action] || '⚡';
        }
        
        refreshMemory(e) {
            e.preventDefault();
            const $container = $(e.target).closest('.huraii-memory-container');
            const config = this.containers.find(c => c.element.is($container));
            
            if (config) {
                config.lastTimestamp = 0; // Force full refresh
                this.loadMemory(config);
            }
        }
        
        clearMemory(e) {
            e.preventDefault();
            const $container = $(e.target).closest('.huraii-memory-container');
            const config = this.containers.find(c => c.element.is($container));
            
            if (config && confirm('Are you sure you want to clear all memory? This action cannot be undone.')) {
                this.performClearMemory(config);
            }
        }
        
        performClearMemory(config) {
            $.ajax({
                url: `${huraii_memory.rest_url}${config.userId}/clear`,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', huraii_memory.nonce);
                },
                success: (response) => {
                    if (response.success) {
                        config.element.find('.huraii-memory-timeline').empty();
                        config.lastTimestamp = 0;
                        this.showSuccess(config.element, 'Memory cleared successfully');
                    } else {
                        this.showError(config.element, 'Failed to clear memory');
                    }
                },
                error: (xhr, status, error) => {
                    this.showError(config.element, `Error: ${error}`);
                }
            });
        }
        
        expandItem(e) {
            e.preventDefault();
            const $item = $(e.target).closest('.huraii-memory-item');
            const $details = $item.find('.memory-item-details');
            
            if ($details.is(':visible')) {
                $details.slideUp();
                $item.removeClass('expanded');
            } else {
                $details.slideDown();
                $item.addClass('expanded');
            }
        }
        
        toggleLive(e) {
            e.preventDefault();
            this.isLive = !this.isLive;
            
            if (this.isLive) {
                this.startLiveUpdates();
                $(e.target).text('Pause');
            } else {
                this.stopLiveUpdates();
                $(e.target).text('Resume');
            }
        }
        
        startLiveUpdates() {
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
            }
            
            this.updateTimer = setInterval(() => {
                if (this.isLive) {
                    this.containers.forEach(config => {
                        if (config.liveUpdate) {
                            this.loadMemory(config);
                        }
                    });
                }
            }, this.updateInterval);
        }
        
        stopLiveUpdates() {
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
                this.updateTimer = null;
            }
        }
        
        showError($container, message) {
            const $error = $('<div class="huraii-memory-error"></div>').text(message);
            $container.find('.huraii-memory-content').prepend($error);
            
            setTimeout(() => {
                $error.fadeOut(() => $error.remove());
            }, 5000);
        }
        
        showSuccess($container, message) {
            const $success = $('<div class="huraii-memory-success"></div>').text(message);
            $container.find('.huraii-memory-content').prepend($success);
            
            setTimeout(() => {
                $success.fadeOut(() => $success.remove());
            }, 3000);
        }
    }
    
    // Initialize when document is ready
    $(document).ready(() => {
        window.huraiiMemoryAPI = new HuraiiMemoryAPI();
    });
    
})(jQuery); 