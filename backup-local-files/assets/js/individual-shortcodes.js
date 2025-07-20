/**
 * VORTEX AI Engine - Individual Shortcodes JavaScript
 * Handles individual agent shortcode functionality
 */

(function($) {
    'use strict';

    const VortexIndividualShortcodes = {
        config: {
            ajaxUrl: vortexIndividualConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: vortexIndividualConfig.nonce || '',
            restUrl: vortexIndividualConfig.restUrl || '/wp-json/vortex/v1/',
            restNonce: vortexIndividualConfig.restNonce || '',
            userId: vortexIndividualConfig.userId || 0,
            isLoggedIn: vortexIndividualConfig.isLoggedIn || false
        },

        init: function() {
            this.bindEvents();
            this.initializeComponents();
        },

        bindEvents: function() {
            // HURAII Generation events
            $(document).on('click', '.huraii-generate-btn', this.handleGenerate);
            $(document).on('click', '.huraii-regenerate-btn', this.handleRegenerate);
            $(document).on('click', '.huraii-upscale-btn', this.handleUpscale);
            $(document).on('click', '.huraii-vary-btn', this.handleVary);
            $(document).on('click', '.huraii-describe-btn', this.handleDescribe);
            $(document).on('click', '.huraii-enhance-btn', this.handleEnhance);
            
            // File operations
            $(document).on('click', '.huraii-upload-btn', this.handleUpload);
            $(document).on('click', '.huraii-save-btn', this.handleSave);
            $(document).on('click', '.huraii-download-btn', this.handleDownload);
            $(document).on('click', '.huraii-delete-btn', this.handleDelete);
            $(document).on('click', '.huraii-export-btn', this.handleExport);
            $(document).on('click', '.huraii-share-btn', this.handleShare);
            
            // Image selection
            $(document).on('click', '.huraii-image-item', this.handleImageSelect);
            $(document).on('click', '.huraii-select-all', this.handleSelectAll);
            $(document).on('click', '.huraii-clear-selection', this.handleClearSelection);
            
            // Form submissions
            $(document).on('submit', '.huraii-generation-form', this.handleFormSubmit);
            $(document).on('input', '.huraii-prompt-input', this.handlePromptInput);
            
            // Tab switching
            $(document).on('click', '.huraii-tab-btn', this.handleTabSwitch);
        },

        initializeComponents: function() {
            // Initialize tooltips
            $('[data-tooltip]').tooltip();
            
            // Initialize image galleries
            this.initializeImageGalleries();
            
            // Initialize progress bars
            this.initializeProgressBars();
            
            // Initialize drag and drop
            this.initializeDragAndDrop();
        },

        initializeImageGalleries: function() {
            $('.huraii-image-gallery').each(function() {
                const $gallery = $(this);
                const $container = $gallery.find('.huraii-images-container');
                const $prevBtn = $gallery.find('.huraii-prev-btn');
                const $nextBtn = $gallery.find('.huraii-next-btn');
                
                let currentIndex = 0;
                const itemsPerPage = 6;
                const totalItems = $container.find('.huraii-image-item').length;
                const totalPages = Math.ceil(totalItems / itemsPerPage);
                
                function updateGallery() {
                    const startIndex = currentIndex * itemsPerPage;
                    const endIndex = startIndex + itemsPerPage;
                    
                    $container.find('.huraii-image-item').hide();
                    $container.find('.huraii-image-item').slice(startIndex, endIndex).show();
                    
                    $prevBtn.prop('disabled', currentIndex === 0);
                    $nextBtn.prop('disabled', currentIndex >= totalPages - 1);
                }
                
                $prevBtn.on('click', function() {
                    if (currentIndex > 0) {
                        currentIndex--;
                        updateGallery();
                    }
                });
                
                $nextBtn.on('click', function() {
                    if (currentIndex < totalPages - 1) {
                        currentIndex++;
                        updateGallery();
                    }
                });
                
                updateGallery();
            });
        },

        initializeProgressBars: function() {
            $('.huraii-progress-bar').each(function() {
                const $progress = $(this);
                const $bar = $progress.find('.huraii-progress-fill');
                const $text = $progress.find('.huraii-progress-text');
                
                function updateProgress(percentage) {
                    $bar.css('width', percentage + '%');
                    $text.text(percentage + '%');
                }
                
                $progress.data('updateProgress', updateProgress);
            });
        },

        initializeDragAndDrop: function() {
            $('.huraii-drop-zone').each(function() {
                const $dropZone = $(this);
                const $input = $dropZone.find('input[type="file"]');
                
                $dropZone.on('dragover', function(e) {
                    e.preventDefault();
                    $dropZone.addClass('huraii-drag-over');
                });
                
                $dropZone.on('dragleave', function(e) {
                    e.preventDefault();
                    $dropZone.removeClass('huraii-drag-over');
                });
                
                $dropZone.on('drop', function(e) {
                    e.preventDefault();
                    $dropZone.removeClass('huraii-drag-over');
                    
                    const files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0) {
                        $input[0].files = files;
                        $input.trigger('change');
                    }
                });
                
                $input.on('change', function() {
                    const files = this.files;
                    if (files.length > 0) {
                        VortexIndividualShortcodes.handleFileSelect(files, $dropZone);
                    }
                });
            });
        },

        handleGenerate: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $form = $btn.closest('.huraii-generation-form');
            
            if (!VortexIndividualShortcodes.validateForm($form)) {
                return;
            }
            
            VortexIndividualShortcodes.submitGeneration($form, 'generate');
        },

        handleRegenerate: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $form = $btn.closest('.huraii-generation-form');
            
            VortexIndividualShortcodes.submitGeneration($form, 'regenerate');
        },

        handleUpscale: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select an image to upscale');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('upscale', { image_ids: imageIds });
        },

        handleVary: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select an image to vary');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('vary', { image_ids: imageIds });
        },

        handleDescribe: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select an image to describe');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('describe', { image_ids: imageIds });
        },

        handleEnhance: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select an image to enhance');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('enhance', { image_ids: imageIds });
        },

        handleUpload: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $dropZone = $btn.closest('.huraii-upload-section').find('.huraii-drop-zone');
            $dropZone.find('input[type="file"]').click();
        },

        handleSave: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select images to save');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('save', { image_ids: imageIds });
        },

        handleDownload: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select images to download');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('download', { image_ids: imageIds });
        },

        handleDelete: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select images to delete');
                return;
            }
            
            if (!confirm('Are you sure you want to delete the selected images?')) {
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('delete', { image_ids: imageIds });
        },

        handleExport: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select images to export');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('export', { image_ids: imageIds });
        },

        handleShare: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const selectedImages = $('.huraii-image-item.selected');
            
            if (selectedImages.length === 0) {
                VortexIndividualShortcodes.showError('Please select images to share');
                return;
            }
            
            const imageIds = selectedImages.map(function() {
                return $(this).data('image-id');
            }).get();
            
            VortexIndividualShortcodes.submitAction('share', { image_ids: imageIds });
        },

        handleImageSelect: function(e) {
            e.preventDefault();
            const $item = $(this);
            $item.toggleClass('selected');
            
            VortexIndividualShortcodes.updateSelectionCount();
        },

        handleSelectAll: function(e) {
            e.preventDefault();
            $('.huraii-image-item').addClass('selected');
            VortexIndividualShortcodes.updateSelectionCount();
        },

        handleClearSelection: function(e) {
            e.preventDefault();
            $('.huraii-image-item').removeClass('selected');
            VortexIndividualShortcodes.updateSelectionCount();
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            
            if (!VortexIndividualShortcodes.validateForm($form)) {
                return;
            }
            
            VortexIndividualShortcodes.submitGeneration($form, 'generate');
        },

        handlePromptInput: function(e) {
            const $input = $(this);
            const $counter = $input.siblings('.huraii-char-counter');
            const maxLength = $input.attr('maxlength') || 1000;
            const currentLength = $input.val().length;
            
            if ($counter.length) {
                $counter.text(currentLength + '/' + maxLength);
                
                if (currentLength > maxLength * 0.9) {
                    $counter.addClass('huraii-char-warning');
                } else {
                    $counter.removeClass('huraii-char-warning');
                }
            }
        },

        handleTabSwitch: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const targetTab = $btn.data('tab');
            
            // Update active tab
            $('.huraii-tab-btn').removeClass('active');
            $btn.addClass('active');
            
            // Show target content
            $('.huraii-tab-content').removeClass('active');
            $('.huraii-tab-content[data-tab="' + targetTab + '"]').addClass('active');
        },

        validateForm: function($form) {
            const prompt = $form.find('.huraii-prompt-input').val().trim();
            
            if (!prompt) {
                VortexIndividualShortcodes.showError('Please enter a prompt');
                return false;
            }
            
            if (prompt.length < 10) {
                VortexIndividualShortcodes.showError('Prompt must be at least 10 characters long');
                return false;
            }
            
            return true;
        },

        submitGeneration: function($form, action) {
            const $submitBtn = $form.find('.huraii-generate-btn, .huraii-regenerate-btn');
            const formData = new FormData($form[0]);
            
            formData.append('action', 'vortex_huraii_' + action);
            formData.append('nonce', VortexIndividualShortcodes.config.nonce);
            
            $submitBtn.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: VortexIndividualShortcodes.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        VortexIndividualShortcodes.showSuccess('Generation completed successfully!');
                        VortexIndividualShortcodes.updateImageGallery(response.data.images);
                    } else {
                        VortexIndividualShortcodes.showError('Generation failed: ' + response.data.message);
                    }
                },
                error: function() {
                    VortexIndividualShortcodes.showError('Network error occurred during generation');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Generate');
                }
            });
        },

        submitAction: function(action, data) {
            const $btn = $('.huraii-' + action + '-btn');
            
            data.action = 'vortex_huraii_' + action;
            data.nonce = VortexIndividualShortcodes.config.nonce;
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: VortexIndividualShortcodes.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        VortexIndividualShortcodes.showSuccess(response.data.message || 'Action completed successfully!');
                        
                        if (action === 'delete') {
                            VortexIndividualShortcodes.removeSelectedImages();
                        }
                    } else {
                        VortexIndividualShortcodes.showError('Action failed: ' + response.data.message);
                    }
                },
                error: function() {
                    VortexIndividualShortcodes.showError('Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        handleFileSelect: function(files, $dropZone) {
            const $preview = $dropZone.find('.huraii-file-preview');
            const $input = $dropZone.find('input[type="file"]');
            
            $preview.empty();
            
            Array.from(files).forEach(function(file) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $preview.append(`
                            <div class="huraii-file-item">
                                <img src="${e.target.result}" alt="${file.name}">
                                <span class="huraii-file-name">${file.name}</span>
                            </div>
                        `);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Auto-upload if configured
            if ($dropZone.data('auto-upload')) {
                VortexIndividualShortcodes.uploadFiles(files);
            }
        },

        uploadFiles: function(files) {
            const formData = new FormData();
            
            Array.from(files).forEach(function(file) {
                formData.append('files[]', file);
            });
            
            formData.append('action', 'vortex_huraii_upload');
            formData.append('nonce', VortexIndividualShortcodes.config.nonce);
            
            $.ajax({
                url: VortexIndividualShortcodes.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        VortexIndividualShortcodes.showSuccess('Files uploaded successfully!');
                        VortexIndividualShortcodes.updateImageGallery(response.data.images);
                    } else {
                        VortexIndividualShortcodes.showError('Upload failed: ' + response.data.message);
                    }
                },
                error: function() {
                    VortexIndividualShortcodes.showError('Network error occurred during upload');
                }
            });
        },

        updateImageGallery: function(images) {
            const $gallery = $('.huraii-image-gallery');
            const $container = $gallery.find('.huraii-images-container');
            
            if (images && images.length > 0) {
                images.forEach(function(image) {
                    const imageHtml = `
                        <div class="huraii-image-item" data-image-id="${image.id}">
                            <img src="${image.url}" alt="${image.title}">
                            <div class="huraii-image-overlay">
                                <button class="huraii-image-action huraii-upscale-btn" title="Upscale">⬆</button>
                                <button class="huraii-image-action huraii-vary-btn" title="Vary">🔄</button>
                                <button class="huraii-image-action huraii-download-btn" title="Download">⬇</button>
                            </div>
                        </div>
                    `;
                    $container.prepend(imageHtml);
                });
            }
        },

        removeSelectedImages: function() {
            $('.huraii-image-item.selected').fadeOut(function() {
                $(this).remove();
                VortexIndividualShortcodes.updateSelectionCount();
            });
        },

        updateSelectionCount: function() {
            const selectedCount = $('.huraii-image-item.selected').length;
            const $counter = $('.huraii-selection-counter');
            
            if ($counter.length) {
                $counter.text(selectedCount + ' selected');
            }
            
            // Update action buttons state
            $('.huraii-action-btn').prop('disabled', selectedCount === 0);
        },

        showSuccess: function(message) {
            VortexIndividualShortcodes.showNotification(message, 'success');
        },

        showError: function(message) {
            VortexIndividualShortcodes.showNotification(message, 'error');
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
        VortexIndividualShortcodes.init();
    });

    // Make available globally
    window.VortexIndividualShortcodes = VortexIndividualShortcodes;

})(jQuery); 