/**
 * VORTEX AI Engine - Agreements JavaScript
 * Handles agreement modal functionality and form processing
 */

(function($) {
    'use strict';

    const VortexAgreements = {
        config: {
            ajaxUrl: vortexAgreementsConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: vortexAgreementsConfig.nonce || '',
            restUrl: vortexAgreementsConfig.restUrl || '/wp-json/vortex/v1/',
            restNonce: vortexAgreementsConfig.restNonce || ''
        },

        init: function() {
            this.bindEvents();
            this.initializeModals();
        },

        bindEvents: function() {
            // Agreement modal triggers
            $(document).on('click', '.vortex-agreement-trigger', this.openAgreementModal);
            $(document).on('click', '.vortex-modal-close', this.closeModal);
            $(document).on('click', '.vortex-modal-overlay', this.handleOverlayClick);
            
            // Agreement form submission
            $(document).on('submit', '.vortex-agreement-form', this.handleAgreementSubmit);
            
            // Agreement actions
            $(document).on('click', '.vortex-sign-agreement', this.signAgreement);
            $(document).on('click', '.vortex-view-agreement', this.viewAgreement);
            $(document).on('click', '.vortex-download-agreement', this.downloadAgreement);
            
            // Keyboard events
            $(document).on('keydown', this.handleKeydown);
        },

        initializeModals: function() {
            // Create modal overlay if it doesn't exist
            if (!$('.vortex-modal-overlay').length) {
                $('body').append('<div class="vortex-modal-overlay"></div>');
            }
        },

        openAgreementModal: function(e) {
            e.preventDefault();
            const $trigger = $(this);
            const agreementType = $trigger.data('agreement-type');
            const agreementId = $trigger.data('agreement-id');
            
            VortexAgreements.loadAgreementModal(agreementType, agreementId);
        },

        loadAgreementModal: function(agreementType, agreementId) {
            const $overlay = $('.vortex-modal-overlay');
            
            // Show loading state
            $overlay.addClass('active').html(`
                <div class="vortex-modal">
                    <div class="vortex-modal-header">
                        <h3 class="vortex-modal-title">Loading Agreement...</h3>
                        <button class="vortex-modal-close">&times;</button>
                    </div>
                    <div class="vortex-modal-body">
                        <div style="text-align: center; padding: 40px;">
                            <div class="vortex-loading"></div>
                        </div>
                    </div>
                </div>
            `);
            
            // Load agreement content
            $.ajax({
                url: VortexAgreements.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_agreement',
                    agreement_type: agreementType,
                    agreement_id: agreementId,
                    nonce: VortexAgreements.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VortexAgreements.renderAgreementModal(response.data);
                    } else {
                        VortexAgreements.showError('Failed to load agreement: ' + response.data.message);
                    }
                },
                error: function() {
                    VortexAgreements.showError('Network error occurred while loading agreement');
                }
            });
        },

        renderAgreementModal: function(data) {
            const $overlay = $('.vortex-modal-overlay');
            const modalHtml = `
                <div class="vortex-modal">
                    <div class="vortex-modal-header">
                        <h3 class="vortex-modal-title">${data.title}</h3>
                        <p class="vortex-modal-subtitle">${data.subtitle || ''}</p>
                        <button class="vortex-modal-close">&times;</button>
                    </div>
                    <div class="vortex-modal-body">
                        <div class="vortex-agreement-content">
                            ${data.content}
                        </div>
                        
                        <form class="vortex-agreement-form" data-agreement-id="${data.id}">
                            <div class="vortex-form-group">
                                <label class="vortex-form-label">Full Name</label>
                                <input type="text" class="vortex-form-input vortex-signature-input" 
                                       name="signature_name" placeholder="Enter your full name" required>
                            </div>
                            
                            <div class="vortex-form-group">
                                <div class="vortex-form-checkbox">
                                    <input type="checkbox" id="agree-terms" name="agree_terms" required>
                                    <label for="agree-terms" class="vortex-form-checkbox-label">
                                        I have read and agree to the terms and conditions above
                                    </label>
                                </div>
                            </div>
                            
                            <div class="vortex-form-group">
                                <div class="vortex-form-checkbox">
                                    <input type="checkbox" id="agree-privacy" name="agree_privacy" required>
                                    <label for="agree-privacy" class="vortex-form-checkbox-label">
                                        I agree to the <a href="/privacy-policy" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="vortex-modal-footer">
                        <button type="button" class="vortex-btn vortex-btn-secondary" onclick="VortexAgreements.closeModal()">
                            Cancel
                        </button>
                        <button type="button" class="vortex-btn vortex-btn-primary vortex-sign-agreement">
                            Sign Agreement
                        </button>
                    </div>
                </div>
            `;
            
            $overlay.html(modalHtml);
        },

        handleAgreementSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = new FormData($form[0]);
            
            formData.append('action', 'vortex_submit_agreement');
            formData.append('nonce', VortexAgreements.config.nonce);
            
            const $submitBtn = $form.find('.vortex-sign-agreement');
            $submitBtn.prop('disabled', true).text('Signing...');
            
            $.ajax({
                url: VortexAgreements.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        VortexAgreements.showSuccess('Agreement signed successfully!');
                        VortexAgreements.closeModal();
                        
                        // Trigger agreement signed event
                        $(document).trigger('vortex:agreement-signed', [response.data]);
                        
                        // Reload page if needed
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        VortexAgreements.showError('Failed to sign agreement: ' + response.data.message);
                    }
                },
                error: function() {
                    VortexAgreements.showError('Network error occurred while signing agreement');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Sign Agreement');
                }
            });
        },

        signAgreement: function(e) {
            e.preventDefault();
            const $modal = $(this).closest('.vortex-modal');
            const $form = $modal.find('.vortex-agreement-form');
            
            // Trigger form submission
            $form.submit();
        },

        viewAgreement: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const agreementId = $btn.data('agreement-id');
            
            // Open agreement in new tab
            window.open(`/agreement/${agreementId}`, '_blank');
        },

        downloadAgreement: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const agreementId = $btn.data('agreement-id');
            
            $btn.prop('disabled', true).text('Downloading...');
            
            $.ajax({
                url: VortexAgreements.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_download_agreement',
                    agreement_id: agreementId,
                    nonce: VortexAgreements.config.nonce
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
                        
                        VortexAgreements.showSuccess('Agreement downloaded successfully');
                    } else {
                        VortexAgreements.showError('Failed to download agreement: ' + response.data.message);
                    }
                },
                error: function() {
                    VortexAgreements.showError('Network error occurred while downloading agreement');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Download');
                }
            });
        },

        closeModal: function(e) {
            if (e) e.preventDefault();
            $('.vortex-modal-overlay').removeClass('active');
        },

        handleOverlayClick: function(e) {
            if (e.target === this) {
                VortexAgreements.closeModal();
            }
        },

        handleKeydown: function(e) {
            if (e.key === 'Escape') {
                VortexAgreements.closeModal();
            }
        },

        showSuccess: function(message) {
            VortexAgreements.showNotification(message, 'success');
        },

        showError: function(message) {
            VortexAgreements.showNotification(message, 'error');
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
        },

        // Utility functions
        utils: {
            formatDate: function(date) {
                return new Date(date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            },

            validateSignature: function(signature) {
                return signature.trim().length >= 2;
            },

            debounce: function(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        VortexAgreements.init();
    });

    // Make available globally
    window.VortexAgreements = VortexAgreements;

})(jQuery); 