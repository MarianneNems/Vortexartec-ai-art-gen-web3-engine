/**
 * Vortex AI Engine - Agreement Modal JavaScript
 * 
 * Handles the agreement modal functionality including tab switching,
 * checkbox validation, and AJAX submission
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

(function($) {
    'use strict';
    
    // Agreement modal state
    let agreementState = {
        currentTab: 'terms',
        checkboxChecked: false,
        modalVisible: false
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeAgreementModal();
        setupEventListeners();
    });
    
    /**
     * Initialize agreement modal
     */
    function initializeAgreementModal() {
        // Check if user needs to agree
        if (typeof vortexAgreement !== 'undefined' && !vortexAgreement.userAgreed) {
            showVortexAgreement();
        }
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Checkbox change event
        $(document).on('change', '#vortex-agreement-checkbox', function() {
            agreementState.checkboxChecked = this.checked;
            updateAcceptButton();
        });
        
        // Modal close events
        $(document).on('click', '.vortex-modal-close', function() {
            closeVortexAgreement();
        });
        
        $(document).on('click', '.vortex-modal', function(e) {
            if (e.target === this) {
                closeVortexAgreement();
            }
        });
        
        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && agreementState.modalVisible) {
                closeVortexAgreement();
            }
        });
    }
    
    /**
     * Show agreement modal
     */
    window.showVortexAgreement = function() {
        const modal = document.getElementById('vortex-agreement-modal');
        if (modal) {
            modal.style.display = 'block';
            agreementState.modalVisible = true;
            document.body.style.overflow = 'hidden';
            
            // Reset state
            agreementState.checkboxChecked = false;
            agreementState.currentTab = 'terms';
            
            // Reset UI
            $('#vortex-agreement-checkbox').prop('checked', false);
            updateAcceptButton();
            showAgreementTab('terms');
            
            // Focus on modal
            modal.focus();
        }
    };
    
    /**
     * Close agreement modal
     */
    window.closeVortexAgreement = function() {
        const modal = document.getElementById('vortex-agreement-modal');
        if (modal) {
            modal.style.display = 'none';
            agreementState.modalVisible = false;
            document.body.style.overflow = '';
        }
    };
    
    /**
     * Show specific agreement tab
     */
    window.showAgreementTab = function(tabName) {
        // Update tab buttons
        $('.tab-button').removeClass('active');
        $('.tab-button[onclick*="' + tabName + '"]').addClass('active');
        
        // Update tab content
        $('.tab-content').removeClass('active');
        $('#' + tabName + '-content').addClass('active');
        
        agreementState.currentTab = tabName;
    };
    
    /**
     * Update accept button state
     */
    function updateAcceptButton() {
        const acceptBtn = document.getElementById('accept-agreement-btn');
        if (acceptBtn) {
            acceptBtn.disabled = !agreementState.checkboxChecked;
        }
    }
    
    /**
     * Accept agreement
     */
    window.acceptVortexAgreement = function() {
        if (!agreementState.checkboxChecked) {
            alert('Please check the agreement checkbox to continue.');
            return;
        }
        
        // Show loading state
        const acceptBtn = document.getElementById('accept-agreement-btn');
        const originalText = acceptBtn.textContent;
        acceptBtn.textContent = 'Processing...';
        acceptBtn.disabled = true;
        
        // Submit agreement via AJAX
        $.ajax({
            url: vortexAgreement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_accept_agreement',
                nonce: vortexAgreement.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showSuccessMessage('Agreement accepted successfully!');
                    
                    // Close modal
                    closeVortexAgreement();
                    
                    // Redirect if provided
                    if (response.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                    } else {
                        // Reload page to update UI
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showErrorMessage(response.data.message || 'Failed to accept agreement');
                    resetAcceptButton(acceptBtn, originalText);
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage('Network error. Please try again.');
                resetAcceptButton(acceptBtn, originalText);
            }
        });
    };
    
    /**
     * Reset accept button
     */
    function resetAcceptButton(button, originalText) {
        button.textContent = originalText;
        button.disabled = false;
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        const messageDiv = $('<div class="vortex-message vortex-success">' + message + '</div>');
        $('body').append(messageDiv);
        
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Show error message
     */
    function showErrorMessage(message) {
        const messageDiv = $('<div class="vortex-message vortex-error">' + message + '</div>');
        $('body').append(messageDiv);
        
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Check if user has agreed (for external use)
     */
    window.hasUserAgreedToVortexTerms = function() {
        return !agreementState.modalVisible && typeof vortexAgreement !== 'undefined' && vortexAgreement.userAgreed;
    };
    
    /**
     * Force show agreement modal (for admin use)
     */
    window.forceShowVortexAgreement = function() {
        showVortexAgreement();
    };
    
    /**
     * Get agreement status (for external use)
     */
    window.getVortexAgreementStatus = function() {
        return {
            agreed: typeof vortexAgreement !== 'undefined' && vortexAgreement.userAgreed,
            modalVisible: agreementState.modalVisible,
            currentTab: agreementState.currentTab
        };
    };
    
    // Add global functions for inline script access
    window.showVortexAgreement = showVortexAgreement;
    window.closeVortexAgreement = closeVortexAgreement;
    window.showAgreementTab = showAgreementTab;
    window.acceptVortexAgreement = acceptVortexAgreement;
    
})(jQuery); 