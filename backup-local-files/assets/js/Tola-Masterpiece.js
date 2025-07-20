/**
 * TOLA Masterpiece JavaScript
 * 
 * Handles daily TOLA masterpiece generation, participation, and management
 */

(function($) {
    'use strict';

    const TolaMasterpiece = {
        config: {
            ajaxUrl: tolaMasterpieceConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: tolaMasterpieceConfig.nonce || '',
            restUrl: tolaMasterpieceConfig.restUrl || '/wp-json/vortex/v1/',
            restNonce: tolaMasterpieceConfig.restNonce || '',
            userId: tolaMasterpieceConfig.userId || 0,
            isLoggedIn: tolaMasterpieceConfig.isLoggedIn || false
        },

        init: function() {
            this.bindEvents();
            this.initializeComponents();
            this.checkDailyMasterpiece();
        },

        bindEvents: function() {
            // Masterpiece participation
            $(document).on('click', '.tola-masterpiece-participate', this.handleParticipation);
            $(document).on('click', '.tola-masterpiece-opt-out', this.handleOptOut);
            
            // Masterpiece viewing
            $(document).on('click', '.tola-view-masterpiece', this.viewMasterpiece);
            $(document).on('click', '.tola-download-masterpiece', this.downloadMasterpiece);
            $(document).on('click', '.tola-share-masterpiece', this.shareMasterpiece);
            
            // Masterpiece generation
            $(document).on('click', '.tola-generate-masterpiece', this.generateMasterpiece);
            $(document).on('click', '.tola-force-generate', this.forceGenerateMasterpiece);
            
            // Masterpiece gallery
            $(document).on('click', '.tola-masterpiece-item', this.selectMasterpiece);
            $(document).on('click', '.tola-masterpiece-prev', this.previousMasterpiece);
            $(document).on('click', '.tola-masterpiece-next', this.nextMasterpiece);
            
            // Form submissions
            $(document).on('submit', '.tola-masterpiece-form', this.handleFormSubmit);
        },

        initializeComponents: function() {
            // Initialize masterpiece countdown
            this.initializeCountdown();
            
            // Initialize masterpiece gallery
            this.initializeGallery();
            
            // Initialize participation stats
            this.updateParticipationStats();
        },

        initializeCountdown: function() {
            const $countdown = $('.tola-masterpiece-countdown');
            if ($countdown.length) {
                this.updateCountdown();
                // Update countdown every second
                setInterval(() => this.updateCountdown(), 1000);
            }
        },

        updateCountdown: function() {
            const $countdown = $('.tola-masterpiece-countdown');
            if (!$countdown.length) return;

            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 0, 0, 0);
            
            const timeLeft = tomorrow - now;
            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            $countdown.text(`${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`);
        },

        initializeGallery: function() {
            const $gallery = $('.tola-masterpiece-gallery');
            if ($gallery.length) {
                const $items = $gallery.find('.tola-masterpiece-item');
                const totalItems = $items.length;
                
                if (totalItems > 0) {
                    $gallery.data('current-index', 0);
                    $gallery.data('total-items', totalItems);
                    this.showMasterpiece(0);
                }
            }
        },

        showMasterpiece: function(index) {
            const $gallery = $('.tola-masterpiece-gallery');
            const totalItems = $gallery.data('total-items');
            
            if (index < 0) index = totalItems - 1;
            if (index >= totalItems) index = 0;
            
            $gallery.data('current-index', index);
            
            $gallery.find('.tola-masterpiece-item').removeClass('active');
            $gallery.find('.tola-masterpiece-item').eq(index).addClass('active');
            
            // Update navigation buttons
            $gallery.find('.tola-masterpiece-prev').prop('disabled', totalItems <= 1);
            $gallery.find('.tola-masterpiece-next').prop('disabled', totalItems <= 1);
            
            // Update counter
            $gallery.find('.tola-masterpiece-counter').text(`${index + 1} of ${totalItems}`);
        },

        checkDailyMasterpiece: function() {
            // Check if today's masterpiece has been generated
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_check_daily_masterpiece',
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.generated) {
                            TolaMasterpiece.showTodayMasterpiece(response.data.masterpiece);
                        } else {
                            TolaMasterpiece.showGenerationPending();
                        }
                    }
                },
                error: function() {
                    console.error('Failed to check daily masterpiece status');
                }
            });
        },

        handleParticipation: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const artworkId = $btn.data('artwork-id');
            
            if (!confirm('Would you like this artwork to participate in the daily TOLA Masterpiece generation?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Opting In...');
            
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_opt_in_masterpiece',
                    artwork_id: artworkId,
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TolaMasterpiece.showSuccess('Artwork opted in for masterpiece generation!');
                        $btn.removeClass('tola-masterpiece-participate')
                            .addClass('tola-masterpiece-opt-out')
                            .text('Opt Out')
                            .data('opted-in', true);
                        
                        TolaMasterpiece.updateParticipationStats();
                    } else {
                        TolaMasterpiece.showError('Failed to opt in: ' + response.data.message);
                    }
                },
                error: function() {
                    TolaMasterpiece.showError('Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        handleOptOut: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const artworkId = $btn.data('artwork-id');
            
            if (!confirm('Are you sure you want to opt out of masterpiece generation for this artwork?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Opting Out...');
            
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_opt_out_masterpiece',
                    artwork_id: artworkId,
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TolaMasterpiece.showSuccess('Artwork opted out of masterpiece generation');
                        $btn.removeClass('tola-masterpiece-opt-out')
                            .addClass('tola-masterpiece-participate')
                            .text('Participate')
                            .data('opted-in', false);
                        
                        TolaMasterpiece.updateParticipationStats();
                    } else {
                        TolaMasterpiece.showError('Failed to opt out: ' + response.data.message);
                    }
                },
                error: function() {
                    TolaMasterpiece.showError('Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        viewMasterpiece: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const masterpieceId = $btn.data('masterpiece-id');
            
            // Open masterpiece in modal or new tab
            window.open(`/tola-masterpiece/${masterpieceId}`, '_blank');
        },

        downloadMasterpiece: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const masterpieceId = $btn.data('masterpiece-id');
            
            $btn.prop('disabled', true).text('Downloading...');
            
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_download_masterpiece',
                    masterpiece_id: masterpieceId,
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        TolaMasterpiece.showSuccess('Masterpiece downloaded successfully');
                    } else {
                        TolaMasterpiece.showError('Failed to download: ' + response.data.message);
                    }
                },
                error: function() {
                    TolaMasterpiece.showError('Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Download');
                }
            });
        },

        shareMasterpiece: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const masterpieceId = $btn.data('masterpiece-id');
            const platform = $btn.data('platform') || 'twitter';
            
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_share_masterpiece',
                    masterpiece_id: masterpieceId,
                    platform: platform,
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Open share URL
                        window.open(response.data.share_url, '_blank');
                        TolaMasterpiece.showSuccess('Masterpiece shared successfully');
                    } else {
                        TolaMasterpiece.showError('Failed to share: ' + response.data.message);
                    }
                },
                error: function() {
                    TolaMasterpiece.showError('Network error occurred');
                }
            });
        },

        generateMasterpiece: function(e) {
            e.preventDefault();
            const $btn = $(this);
            
            if (!confirm('Generate today\'s TOLA Masterpiece? This will combine all opted-in artworks.')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_generate_masterpiece',
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TolaMasterpiece.showSuccess('Masterpiece generated successfully!');
                        TolaMasterpiece.showTodayMasterpiece(response.data.masterpiece);
                    } else {
                        TolaMasterpiece.showError('Failed to generate: ' + response.data.message);
                    }
                },
                error: function() {
                    TolaMasterpiece.showError('Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Generate Masterpiece');
                }
            });
        },

        forceGenerateMasterpiece: function(e) {
            e.preventDefault();
            const $btn = $(this);
            
            if (!confirm('Force generate masterpiece? This will override any existing masterpiece for today.')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Force Generating...');
            
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_force_generate_masterpiece',
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TolaMasterpiece.showSuccess('Masterpiece force generated successfully!');
                        TolaMasterpiece.showTodayMasterpiece(response.data.masterpiece);
                    } else {
                        TolaMasterpiece.showError('Failed to force generate: ' + response.data.message);
                    }
                },
                error: function() {
                    TolaMasterpiece.showError('Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Force Generate');
                }
            });
        },

        selectMasterpiece: function(e) {
            e.preventDefault();
            const $item = $(this);
            const index = $item.index();
            
            this.showMasterpiece(index);
        },

        previousMasterpiece: function(e) {
            e.preventDefault();
            const $gallery = $('.tola-masterpiece-gallery');
            const currentIndex = $gallery.data('current-index');
            
            this.showMasterpiece(currentIndex - 1);
        },

        nextMasterpiece: function(e) {
            e.preventDefault();
            const $gallery = $('.tola-masterpiece-gallery');
            const currentIndex = $gallery.data('current-index');
            
            this.showMasterpiece(currentIndex + 1);
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = new FormData($form[0]);
            
            formData.append('action', 'tola_masterpiece_form');
            formData.append('nonce', TolaMasterpiece.config.nonce);
            
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        TolaMasterpiece.showSuccess('Form submitted successfully!');
                        $form[0].reset();
                    } else {
                        TolaMasterpiece.showError('Failed to submit: ' + response.data.message);
                    }
                },
                error: function() {
                    TolaMasterpiece.showError('Network error occurred');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Submit');
                }
            });
        },

        showTodayMasterpiece: function(masterpiece) {
            const $container = $('.tola-today-masterpiece');
            if (!$container.length) return;
            
            const html = `
                <div class="tola-masterpiece-display">
                    <h3>Today's TOLA Masterpiece</h3>
                    <div class="tola-masterpiece-image">
                        <img src="${masterpiece.image_url}" alt="${masterpiece.title}">
                    </div>
                    <div class="tola-masterpiece-info">
                        <h4>${masterpiece.title}</h4>
                        <p>${masterpiece.description}</p>
                        <div class="tola-masterpiece-stats">
                            <span>${masterpiece.participant_count} participants</span>
                            <span>Generated: ${masterpiece.created_date}</span>
                        </div>
                        <div class="tola-masterpiece-actions">
                            <button class="tola-btn tola-btn-primary tola-view-masterpiece" 
                                    data-masterpiece-id="${masterpiece.id}">
                                View Details
                            </button>
                            <button class="tola-btn tola-btn-secondary tola-download-masterpiece" 
                                    data-masterpiece-id="${masterpiece.id}">
                                Download
                            </button>
                            <button class="tola-btn tola-btn-secondary tola-share-masterpiece" 
                                    data-masterpiece-id="${masterpiece.id}" data-platform="twitter">
                                Share
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $container.html(html);
        },

        showGenerationPending: function() {
            const $container = $('.tola-today-masterpiece');
            if (!$container.length) return;
            
            const html = `
                <div class="tola-masterpiece-pending">
                    <h3>Today's Masterpiece</h3>
                    <p>Today's TOLA Masterpiece will be generated at midnight UTC.</p>
                    <div class="tola-countdown-info">
                        <span>Time remaining:</span>
                        <div class="tola-masterpiece-countdown">--:--:--</div>
                    </div>
                    <button class="tola-btn tola-btn-primary tola-generate-masterpiece">
                        Generate Now
                    </button>
                </div>
            `;
            
            $container.html(html);
            this.updateCountdown();
        },

        updateParticipationStats: function() {
            $.ajax({
                url: TolaMasterpiece.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tola_get_participation_stats',
                    nonce: TolaMasterpiece.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const stats = response.data;
                        
                        $('.tola-participant-count').text(stats.participant_count);
                        $('.tola-artwork-count').text(stats.artwork_count);
                        $('.tola-masterpiece-count').text(stats.masterpiece_count);
                        
                        // Update progress bar
                        const $progress = $('.tola-participation-progress');
                        if ($progress.length) {
                            const percentage = (stats.participant_count / stats.total_users) * 100;
                            $progress.find('.tola-progress-fill').css('width', percentage + '%');
                            $progress.find('.tola-progress-text').text(percentage.toFixed(1) + '%');
                        }
                    }
                }
            });
        },

        showSuccess: function(message) {
            TolaMasterpiece.showNotification(message, 'success');
        },

        showError: function(message) {
            TolaMasterpiece.showNotification(message, 'error');
        },

        showNotification: function(message, type) {
            const $notification = $(`
                <div class="tola-notification tola-notification-${type}">
                    <span class="tola-notification-message">${message}</span>
                    <button class="tola-notification-close">&times;</button>
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
            $notification.find('.tola-notification-close').on('click', function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        TolaMasterpiece.init();
    });

    // Make available globally
    window.TolaMasterpiece = TolaMasterpiece;

})(jQuery); 