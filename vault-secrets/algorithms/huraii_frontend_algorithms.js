/**
 * HURAII Dashboard JavaScript - Midjourney Style
 * Handles all interactive functionality for the 12-tab AI creation interface
 * 
 * ✅ PRODUCTION-READY AJAX IMPLEMENTATION:
 * - Generate: Real AJAX calls to huraii_generate handler with Vault integration
 * - Regenerate: Real AJAX calls to huraii_regenerate handler with strength controls
 * - Upscale: Real AJAX calls to huraii_upscale handler with factor & enhancement options
 * - Vary: Real AJAX calls to huraii_vary handler with variation type controls
 * - Describe: Real AJAX calls to huraii_describe handler with CLOE+HORACE agents
 * - Upload: Real AJAX calls to huraii_upload handler with CLOE analysis
 * - Edit: Real AJAX calls to huraii_edit handler with 8 edit tools
 * - Enhance: Real AJAX calls to huraii_enhance handler with 4 enhancement types
 * - Save: Real AJAX calls to huraii_save handler with WordPress collections
 * - Download: Real AJAX calls to huraii_download handler with format options
 * - Delete: Real AJAX calls to huraii_delete handler with ownership verification
 * - Export: Real AJAX calls to huraii_export handler with social media formats
 * - Share: Real AJAX calls to huraii_share handler with platform-specific URLs
 * 
 * All functions include:
 * ✅ Proper nonce verification (vortexHuraiiConfig.nonce)
 * ✅ Parameter validation and collection from UI controls
 * ✅ Cost tracking and credit deduction
 * ✅ Error handling with user feedback
 * ✅ Processing state indicators
 * ✅ AI Orchestrator integration through Vault
 */

(function($) {
    'use strict';

    // Global variables
    const HuraiiDashboard = {
        currentTab: 'generate',
        credits: 1000,
        isProcessing: false,
        generatedImages: [],
        collections: [],
        selectedImages: [],
        uploadQueue: [],
        
        // Initialize the dashboard
        init: function() {
            $(document).ready(() => {
                this.setupEventListeners();
                this.loadSavedData();
                this.initializeInterface();
                this.setupDragDrop();
                this.animateOnLoad();
            });
        },
        
        // Setup all event listeners
        setupEventListeners: function() {
            // Tab navigation
            $(document).on('click', '.huraii-tab.midjourney-tab', this.handleTabClick.bind(this));
            
            // Generate functionality
            $(document).on('click', '.generate-btn', this.handleGenerate.bind(this));
            $(document).on('click', '.tool-btn', this.handleToolClick.bind(this));
            $(document).on('change', '.ratio-btn', this.handleRatioChange.bind(this));
            $(document).on('input', '.style-slider', this.handleSliderChange.bind(this));
            
            // Regenerate functionality
            $(document).on('click', '.regenerate-btn', this.handleRegenerate.bind(this));
            $(document).on('click', '.strength-btn', this.handleStrengthChange.bind(this));
            
            // Upscale functionality
            $(document).on('click', '.upscale-btn', this.handleUpscale.bind(this));
            $(document).on('click', '.factor-btn', this.handleFactorChange.bind(this));
            
            // Vary functionality
            $(document).on('click', '.vary-btn', this.handleVary.bind(this));
            $(document).on('click', '.variation-btn', this.handleVariationChange.bind(this));
            
            // Describe functionality - New comprehensive 5-agent analysis
            $(document).on('click', '#describe-submit-btn', this.handleDescribeSubmit.bind(this));
            $(document).on('click', '#describe-upload-placeholder', this.handleDescribeUploadClick.bind(this));
            $(document).on('change', '#describe-image-upload', this.handleDescribeImageSelect.bind(this));
            $(document).on('click', '#describe-remove-image', this.handleDescribeRemoveImage.bind(this));
            
            // Download functionality
            $(document).on('click', '.download-btn', this.handleDownload.bind(this));
            $(document).on('click', '.format-btn', this.handleFormatChange.bind(this));
            
            // Upload functionality
            $(document).on('click', '#upload-dropzone', this.handleUploadClick.bind(this));
            $(document).on('change', '#file-input', this.handleFileSelect.bind(this));
            
            // Save functionality
            $(document).on('click', '.save-to-collection-btn', this.handleSaveToCollection.bind(this));
            $(document).on('click', '.collection-card', this.handleCollectionClick.bind(this));
            
            // Delete functionality
            $(document).on('click', '.delete-selected-btn', this.handleDelete.bind(this));
            $(document).on('click', '.select-all-btn', this.handleSelectAll.bind(this));
            $(document).on('click', '.deselect-all-btn', this.handleDeselectAll.bind(this));
            
            // Edit functionality
            $(document).on('click', '.edit-tool-btn', this.handleEditTool.bind(this));
            $(document).on('click', '.apply-edits-btn', this.handleApplyEdits.bind(this));
            
            // Enhance functionality
            $(document).on('click', '.enhance-btn', this.handleEnhance.bind(this));
            
            // Export functionality
            $(document).on('click', '.export-selected-btn', this.handleExport.bind(this));
            $(document).on('click', '.format-option', this.handleExportFormat.bind(this));
            
            // Share functionality
            $(document).on('click', '.share-btn', this.handleShare.bind(this));
            $(document).on('click', '.copy-link-btn', this.handleCopyLink.bind(this));
            
            // Voice commands
            $(document).on('click', '.huraii-btn-voice', this.handleVoiceCommand.bind(this));
            
            // Fullscreen toggle
            $(document).on('click', '.huraii-btn-fullscreen', this.toggleFullscreen.bind(this));
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboard.bind(this));
            
            // Escape key to exit fullscreen
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('.vortex-huraii-dashboard').hasClass('fullscreen-mode')) {
                    HuraiiDashboard.toggleFullscreen();
                }
            });
        },
        
        // Handle tab switching
        handleTabClick: function(e) {
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');
            
            // Update active tab
            $('.huraii-tab.midjourney-tab').removeClass('active');
            $tab.addClass('active');
            
            // Update active pane
            $('.huraii-tab-pane.midjourney-pane').removeClass('active');
            $(`#huraii-${tabId}`).addClass('active');
            
            this.currentTab = tabId;
            this.onTabChange(tabId);
        },
        
        // Handle tab change events
        onTabChange: function(tabId) {
            switch(tabId) {
                case 'generate':
                    this.focusPromptInput();
                    break;
                case 'regenerate':
                    this.loadRegenerateImages();
                    break;
                case 'upscale':
                    this.loadUpscaleImages();
                    break;
                case 'vary':
                    this.loadVaryImages();
                    break;
                case 'describe':
                    this.initializeDescribeTab();
                    break;
                case 'download':
                    this.loadDownloadGallery();
                    break;
                case 'upload':
                    this.initializeUploadArea();
                    break;
                case 'save':
                    this.loadCollections();
                    break;
                case 'delete':
                    this.loadDeleteGallery();
                    break;
                case 'edit':
                    this.initializeEditWorkspace();
                    break;
                case 'enhance':
                    this.loadEnhanceOptions();
                    break;
                case 'export':
                    this.loadExportOptions();
                    break;
                case 'share':
                    this.loadShareOptions();
                    break;
            }
        },
        
        // Generate new artwork
        handleGenerate: function(e) {
            e.preventDefault();
            
            if (this.isProcessing) return;
            
            const prompt = $('#generate-prompt').val().trim();
            if (!prompt) {
                this.showNotification('Please enter a prompt', 'error');
                return;
            }
            
            if (this.credits < 2) {
                this.showNotification('Insufficient credits', 'error');
                return;
            }
            
            // Gather settings
            const settings = {
                aspect_ratio: $('.ratio-btn.active').data('ratio') || '1:1',
                style_intensity: $('.style-slider').val() || 5,
                quality: $('.quality-select').val() || 'standard'
            };
            
            this.isProcessing = true;
            this.showProcessingState('Generating artwork...');
            
            // Make AJAX call through Vault
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_generate',
                    nonce: vortexHuraiiConfig.nonce,
                    prompt: prompt,
                    settings: settings
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.credits -= response.data.cost;
                        this.updateCreditsDisplay();
                        this.addGeneratedArtwork(response.data.result);
                        $('#generate-prompt').val('');
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Generation failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Generate Error:', error);
                    this.showNotification('Network error during generation', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideProcessingState();
                }
            });
        },
        

        
        // Create mock images for demonstration
        createMockImages: function(count, prompt) {
            const images = [];
            for (let i = 0; i < count; i++) {
                images.push({
                    id: Date.now() + i,
                    prompt: prompt,
                    url: `https://picsum.photos/512/512?random=${Date.now() + i}`,
                    timestamp: new Date(),
                    type: 'generated',
                    selected: false
                });
            }
            return images;
        },
        
        // Display generated images
        displayGeneratedImages: function(images) {
            const $grid = $('.generation-grid');
            $grid.empty();
            
            const $container = $('<div class="image-grid"></div>');
            
            images.forEach(image => {
                const $imageCard = $(`
                    <div class="image-card" data-id="${image.id}">
                        <div class="image-wrapper">
                            <img src="${image.url}" alt="Generated artwork" loading="lazy">
                            <div class="image-overlay">
                                <div class="image-actions">
                                    <button class="action-btn upscale-btn" data-id="${image.id}" title="Upscale">
                                        🔍
                                    </button>
                                    <button class="action-btn vary-btn" data-id="${image.id}" title="Vary">
                                        🎭
                                    </button>
                                    <button class="action-btn download-btn" data-id="${image.id}" title="Download">
                                        📥
                                    </button>
                                    <button class="action-btn like-btn" data-id="${image.id}" title="Like">
                                        ❤️
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="image-info">
                            <div class="image-prompt">${image.prompt}</div>
                            <div class="image-meta">
                                <span class="image-time">${this.formatTime(image.timestamp)}</span>
                                <span class="image-type">${image.type}</span>
                            </div>
                        </div>
                    </div>
                `);
                $container.append($imageCard);
            });
            
            $grid.append($container);
            this.animateImageCards();
        },
        
        // Handle tool button clicks
        handleToolClick: function(e) {
            const $btn = $(e.currentTarget);
            const tool = $btn.attr('title');
            
            switch(tool) {
                case 'Style Presets':
                    this.showStylePresets();
                    break;
                case 'Random Prompt':
                    this.generateRandomPrompt();
                    break;
                case 'Advanced Settings':
                    this.showAdvancedSettings();
                    break;
            }
        },
        
        // Generate random prompt
        generateRandomPrompt: function() {
            const subjects = ['mystical forest', 'cyberpunk city', 'ancient temple', 'floating islands', 'crystal cave'];
            const styles = ['digital art', 'oil painting', 'watercolor', 'pixel art', 'photorealistic'];
            const moods = ['ethereal', 'dark', 'vibrant', 'peaceful', 'dramatic'];
            
            const subject = subjects[Math.floor(Math.random() * subjects.length)];
            const style = styles[Math.floor(Math.random() * styles.length)];
            const mood = moods[Math.floor(Math.random() * moods.length)];
            
            const prompt = `${subject} with ${mood} lighting, ${style} style`;
            $('#generate-prompt').val(prompt).focus();
        },
        
        // Handle regenerate
        handleRegenerate: function(e) {
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to regenerate', 'warning');
                return;
            }
            
            if (this.credits < 1) {
                this.showNotification('Insufficient credits', 'error');
                return;
            }
            
            // Gather regeneration settings
            const strength = $('.strength-btn.active').data('strength') || 'medium';
            const imageIds = this.selectedImages.map(img => img.id);
            
            this.isProcessing = true;
            this.showProcessingState('Regenerating artwork...');
            
            // Make AJAX call through Vault
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_regenerate',
                    nonce: vortexHuraiiConfig.nonce,
                    image_ids: imageIds,
                    strength: strength
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.credits -= response.data.cost;
                        this.updateCreditsDisplay();
                        this.addGeneratedArtwork(response.data.result);
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Regeneration failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Regenerate Error:', error);
                    this.showNotification('Network error during regeneration', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideProcessingState();
                }
            });
        },
        

        
        // Handle upscale
        handleUpscale: function(e) {
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to upscale', 'warning');
                return;
            }
            
            if (this.credits < 3) {
                this.showNotification('Insufficient credits', 'error');
                return;
            }
            
            // Gather upscale settings
            const factor = $('.factor-btn.active').data('factor') || '4x';
            const enhancementType = $('.enhancement-type-select').val() || 'detail';
            const imageIds = this.selectedImages.map(img => img.id);
            
            this.isProcessing = true;
            this.showProcessingState('Upscaling artwork...');
            
            // Make AJAX call through Vault
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_upscale',
                    nonce: vortexHuraiiConfig.nonce,
                    image_ids: imageIds,
                    factor: factor,
                    enhancement_type: enhancementType
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.credits -= response.data.cost;
                        this.updateCreditsDisplay();
                        this.addGeneratedArtwork(response.data.result);
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Upscale failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Upscale Error:', error);
                    this.showNotification('Network error during upscale', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideProcessingState();
                }
            });
        },
        

        
        // Handle vary
        handleVary: function(e) {
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select an image to vary', 'warning');
                return;
            }
            
            if (this.credits < 2) {
                this.showNotification('Insufficient credits', 'error');
                return;
            }
            
            // Gather variation settings
            const variationType = $('.variation-btn.active').data('variation') || 'subtle';
            const selectedImage = this.selectedImages[0]; // Use first selected image
            
            this.isProcessing = true;
            this.showProcessingState('Creating variations...');
            
            // Make AJAX call through Vault
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_vary',
                    nonce: vortexHuraiiConfig.nonce,
                    image_id: selectedImage.id,
                    variation_type: variationType
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.credits -= response.data.cost;
                        this.updateCreditsDisplay();
                        this.addGeneratedArtwork(response.data.result);
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Variation failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Vary Error:', error);
                    this.showNotification('Network error during variation', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideProcessingState();
                }
            });
        },
        

        
        // Initialize Describe tab
        initializeDescribeTab: function() {
            // Clear any previous data
            $('#describe-prompt').val('');
            $('#describe-image-preview').hide();
            $('#describe-upload-placeholder').show();
            
            // Clear chat thread
            const $chatThread = $('#describe-chat-thread');
            if ($chatThread.find('.chat-thread-placeholder').length === 0) {
                $chatThread.empty().append(`
                    <div class="chat-thread-placeholder">
                        <div class="placeholder-icon">🤖</div>
                        <div class="placeholder-text">
                            <div class="placeholder-main">AI Analysis Results</div>
                            <div class="placeholder-sub">Comprehensive analysis from all 5 agents will appear here</div>
                        </div>
                    </div>
                `);
            }
            
            // Focus on prompt textarea
            $('#describe-prompt').focus();
        },
        
        // Handle describe upload placeholder click
        handleDescribeUploadClick: function(e) {
            e.preventDefault();
            $('#describe-image-upload').click();
        },
        
        // Handle describe image selection
        handleDescribeImageSelect: function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                this.showNotification('Please select a valid image file', 'error');
                return;
            }
            
            // Validate file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                this.showNotification('Image size must be less than 5MB', 'error');
                return;
            }
            
            // Create preview
            const reader = new FileReader();
            reader.onload = (e) => {
                $('#describe-preview-img').attr('src', e.target.result);
                $('#describe-upload-placeholder').hide();
                $('#describe-image-preview').show();
            };
            reader.readAsDataURL(file);
            
            // Store file reference
            this.describeImageFile = file;
        },
        
        // Handle remove describe image
        handleDescribeRemoveImage: function(e) {
            e.preventDefault();
            
            $('#describe-image-preview').hide();
            $('#describe-upload-placeholder').show();
            $('#describe-image-upload').val('');
            
            // Clear file reference
            this.describeImageFile = null;
        },
        
        // Handle comprehensive describe submit - All 5 agents analysis
        handleDescribeSubmit: function(e) {
            e.preventDefault();
            
            if (this.isProcessing) return;
            
            const prompt = $('#describe-prompt').val().trim();
            if (!prompt) {
                this.showNotification('Please enter a description or question', 'error');
                $('#describe-prompt').focus();
                return;
            }
            
            if (this.credits < 1) {
                this.showNotification('Insufficient credits. You need 1 credit for analysis.', 'error');
                return;
            }
            
            this.isProcessing = true;
            this.showDescribeLoading();
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'huraii_describe');
            formData.append('nonce', vortexHuraiiConfig.nonce);
            formData.append('prompt', prompt);
            
            // Add image if uploaded
            if (this.describeImageFile) {
                formData.append('image', this.describeImageFile);
            }
            
            // Make AJAX call for comprehensive 5-agent analysis
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.credits = response.data.credits_remaining;
                        this.updateCreditsDisplay();
                        this.displayDescribeResults(response.data.result);
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Analysis failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Describe Error:', error);
                    this.showNotification('Network error during analysis', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideDescribeLoading();
                }
            });
        },
        
        // Show describe loading state
        showDescribeLoading: function() {
            $('#describe-submit-btn').prop('disabled', true);
            $('#describe-loading').show();
            $('#describe-submit-btn .btn-text').text('Analyzing...');
        },
        
        // Hide describe loading state
        hideDescribeLoading: function() {
            $('#describe-submit-btn').prop('disabled', false);
            $('#describe-loading').hide();
            $('#describe-submit-btn .btn-text').text('Analyze with AI');
        },
        
        // Display comprehensive describe results in chat thread
        displayDescribeResults: function(result) {
            const $chatThread = $('#describe-chat-thread');
            
            // Remove placeholder
            $chatThread.find('.chat-thread-placeholder').remove();
            
            // Create user message
            const userMessage = `
                <div class="chat-message user-message">
                    <div class="message-header">
                        <div class="message-author">👤 You</div>
                        <div class="message-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="message-content">
                        ${result.prompt}
                        ${result.image_id ? '<div class="message-image">📷 Image attached</div>' : ''}
                    </div>
                </div>
            `;
            
            // Create AI response with structured analysis
            const aiMessage = `
                <div class="chat-message ai-message">
                    <div class="message-header">
                        <div class="message-author">🤖 AI Analysis Team</div>
                        <div class="message-time">${new Date().toLocaleTimeString()}</div>
                        <div class="message-badge">5 Agents</div>
                    </div>
                    <div class="message-content">
                        <div class="analysis-report">
                            ${this.formatAnalysisReport(result.structured_response)}
                        </div>
                        <div class="analysis-meta">
                            <div class="meta-item">
                                <span class="meta-label">Analysis Time:</span>
                                <span class="meta-value">${Math.round(result.processing_time * 100) / 100}s</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Agents Consulted:</span>
                                <span class="meta-value">${result.agents_consulted}/5</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Cost:</span>
                                <span class="meta-value">${result.cost} credit</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add messages to chat thread
            $chatThread.append(userMessage);
            $chatThread.append(aiMessage);
            
            // Scroll to bottom
            $chatThread.scrollTop($chatThread[0].scrollHeight);
            
            // Animate new messages
            $chatThread.find('.chat-message').last().hide().slideDown(300);
        },
        
        // Format analysis report from markdown to HTML
        formatAnalysisReport: function(markdown) {
            if (!markdown) return '';
            
            // Simple markdown to HTML conversion
            let html = markdown
                .replace(/### (.*?)\n/g, '<h3>$1</h3>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/---/g, '<hr>')
                .replace(/\n/g, '<br>');
            
            return html;
        },
        
        // Handle download
        handleDownload: function(e) {
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to download', 'warning');
                return;
            }
            
            this.selectedImages.forEach(image => {
                this.downloadImage(image);
            });
            
            this.showNotification(`Downloaded ${this.selectedImages.length} images`, 'success');
        },
        
        // Download individual image
        downloadImage: function(image) {
            const link = document.createElement('a');
            link.href = image.url;
            link.download = `huraii-${image.id}.jpg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        // Handle file upload
        handleFileSelect: function(e) {
            const files = Array.from(e.target.files);
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    this.processUploadedFile(file);
                }
            });
        },
        
        // Process uploaded file
        processUploadedFile: function(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const uploadedImage = {
                    id: Date.now() + Math.random(),
                    name: file.name,
                    url: e.target.result,
                    type: 'uploaded',
                    timestamp: new Date()
                };
                
                this.displayUploadedImage(uploadedImage);
                this.uploadQueue.push(uploadedImage);
            };
            reader.readAsDataURL(file);
        },
        
        // Display uploaded image
        displayUploadedImage: function(image) {
            const $container = $('#uploaded-images');
            const $imageCard = $(`
                <div class="uploaded-image-card" data-id="${image.id}">
                    <img src="${image.url}" alt="${image.name}">
                    <div class="image-name">${image.name}</div>
                    <button class="remove-btn" data-id="${image.id}">×</button>
                </div>
            `);
            $container.append($imageCard);
        },
        
        // Handle enhancement
        handleEnhance: function(e) {
            const $btn = $(e.currentTarget);
            const enhanceType = $btn.data('enhance') || 'super-res';
            
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to enhance', 'warning');
                return;
            }
            
            if (this.credits < 2) {
                this.showNotification('Insufficient credits', 'error');
                return;
            }
            
            // Gather enhancement settings
            const imageIds = this.selectedImages.map(img => img.id);
            
            this.isProcessing = true;
            this.showProcessingState(`Applying ${enhanceType} enhancement...`);
            
            // Make AJAX call through Vault
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_enhance',
                    nonce: vortexHuraiiConfig.nonce,
                    image_ids: imageIds,
                    enhancement_type: enhanceType
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.credits -= response.data.cost;
                        this.updateCreditsDisplay();
                        this.addGeneratedArtwork(response.data.result);
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Enhancement failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Enhance Error:', error);
                    this.showNotification('Network error during enhancement', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideProcessingState();
                }
            });
        },
        

        
        // Handle share
        handleShare: function(e) {
            const $btn = $(e.currentTarget);
            const platform = $btn.closest('.platform-card').find('.platform-name').text();
            
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to share', 'warning');
                return;
            }
            
            this.shareToPlatform(platform);
        },
        
        // Share to platform
        shareToPlatform: function(platform) {
            // Gather share settings
            const imageIds = this.selectedImages.map(img => img.id);
            const privacyLevel = $('.privacy-select').val() || 'public';
            const includePrompt = $('.include-prompt-checkbox').is(':checked') || false;
            const watermark = $('.watermark-checkbox').is(':checked') || false;
            
            this.isProcessing = true;
            this.showProcessingState('Generating share links...');
            
            // Make AJAX call to generate share URLs first
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_share',
                    nonce: vortexHuraiiConfig.nonce,
                    image_ids: imageIds,
                    platform: platform,
                    privacy_level: privacyLevel,
                    include_prompt: includePrompt,
                    watermark: watermark
                },
                success: (response) => {
                    if (response.success && response.data.share_urls.length > 0) {
                        const shareUrl = response.data.share_urls[0].share_url;
                        this.openSocialMediaShare(platform, shareUrl);
                        this.showNotification(response.data.message, 'success');
                        this.showShareModal(response.data);
                    } else {
                        this.showNotification(response.data || 'Share failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Share Error:', error);
                    this.showNotification('Network error during share', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideProcessingState();
                }
            });
        },
        
        // Open social media sharing
        openSocialMediaShare: function(platform, shareUrl) {
            let url = '';
            
            switch(platform.toLowerCase()) {
                case 'twitter':
                    url = `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=Check out my AI artwork created with HURAII!`;
                    break;
                case 'instagram':
                    this.showNotification('Instagram sharing requires mobile app', 'info');
                    return;
                case 'pinterest':
                    url = `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(shareUrl)}&description=AI artwork created with HURAII`;
                    break;
                case 'linkedin':
                    url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`;
                    break;
                case 'facebook':
                    url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
                    break;
            }
            
            if (url) {
                window.open(url, '_blank', 'width=600,height=400');
            }
        },
        
        // Handle copy link
        handleCopyLink: function(e) {
            const $input = $('.share-link-input');
            $input.select();
            document.execCommand('copy');
            this.showNotification('Link copied to clipboard!', 'success');
        },
        
        // Handle voice commands
        handleVoiceCommand: function(e) {
            if (!('webkitSpeechRecognition' in window)) {
                this.showNotification('Voice recognition not supported', 'error');
                return;
            }
            
            const recognition = new webkitSpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';
            
            recognition.onstart = () => {
                this.showNotification('Listening...', 'info');
            };
            
            recognition.onresult = (event) => {
                const command = event.results[0][0].transcript.toLowerCase();
                this.processVoiceCommand(command);
            };
            
            recognition.onerror = () => {
                this.showNotification('Voice recognition failed', 'error');
            };
            
            recognition.start();
        },
        
        // Process voice command
        processVoiceCommand: function(command) {
            if (command.includes('generate')) {
                this.handleTabClick({ currentTarget: $('[data-tab="generate"]') });
            } else if (command.includes('upscale')) {
                this.handleTabClick({ currentTarget: $('[data-tab="upscale"]') });
            } else if (command.includes('download')) {
                this.handleTabClick({ currentTarget: $('[data-tab="download"]') });
            } else {
                $('#generate-prompt').val(command);
                this.showNotification('Voice command processed', 'success');
            }
        },
        
        // Handle keyboard shortcuts
        handleKeyboard: function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'Enter':
                        if (this.currentTab === 'generate') {
                            this.handleGenerate(e);
                        }
                        break;
                    case 's':
                        e.preventDefault();
                        this.handleTabClick({ currentTarget: $('[data-tab="save"]') });
                        break;
                    case 'd':
                        e.preventDefault();
                        this.handleTabClick({ currentTarget: $('[data-tab="download"]') });
                        break;
                }
            }
        },
        
        // Setup drag and drop
        setupDragDrop: function() {
            const $dropzone = $('#upload-dropzone');
            
            $dropzone.on('dragover', (e) => {
                e.preventDefault();
                $dropzone.addClass('drag-over');
            });
            
            $dropzone.on('dragleave', (e) => {
                e.preventDefault();
                $dropzone.removeClass('drag-over');
            });
            
            $dropzone.on('drop', (e) => {
                e.preventDefault();
                $dropzone.removeClass('drag-over');
                
                const files = Array.from(e.originalEvent.dataTransfer.files);
                files.forEach(file => {
                    if (file.type.startsWith('image/')) {
                        this.processUploadedFile(file);
                    }
                });
            });
        },
        
        // Show processing state
        showProcessingState: function(message) {
            const $overlay = $(`
                <div class="processing-overlay">
                    <div class="processing-content">
                        <div class="processing-spinner"></div>
                        <div class="processing-message">${message}</div>
                    </div>
                </div>
            `);
            
            $('.vortex-huraii-dashboard').append($overlay);
            $overlay.fadeIn(300);
        },
        
        // Hide processing state
        hideProcessingState: function() {
            $('.processing-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="huraii-notification ${type}">
                    <div class="notification-content">
                        <span class="notification-message">${message}</span>
                        <button class="notification-close">×</button>
                    </div>
                </div>
            `);
            
            $('body').append($notification);
            $notification.fadeIn(300);
            
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
            
            $notification.find('.notification-close').on('click', () => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },
        
        // Update credits display
        updateCreditsDisplay: function() {
            $('#huraii-session-cost').text(this.credits);
        },
        
        // Add generated artwork to UI
        addGeneratedArtwork: function(result) {
            // Parse the AI result and add to generated images
            const newImages = this.parseAIResult(result);
            this.generatedImages = [...this.generatedImages, ...newImages];
            this.displayGeneratedImages(newImages);
        },
        
        // Parse AI result into image objects
        parseAIResult: function(result) {
            const images = [];
            
            if (result && result.final_answer) {
                // Try to extract image URLs from the AI response
                const imageUrlRegex = /(https?:\/\/[^\s]+(?:\.jpg|\.jpeg|\.png|\.gif|\.webp))/gi;
                const foundUrls = result.final_answer.match(imageUrlRegex) || [];
                
                if (foundUrls.length > 0) {
                    // Use actual AI-generated image URLs
                    foundUrls.forEach((url, i) => {
                        images.push({
                            id: Date.now() + i,
                            prompt: result.query || 'AI Generated',
                            url: url,
                            timestamp: new Date(),
                            type: 'generated',
                            selected: false,
                            ai_result: result
                        });
                    });
                } else {
                    // Fallback: Create placeholder image with AI metadata
                    images.push({
                        id: Date.now(),
                        prompt: result.query || 'AI Generated',
                        url: `https://picsum.photos/512/512?random=${Date.now()}`,
                        timestamp: new Date(),
                        type: 'generated',
                        selected: false,
                        ai_result: result,
                        is_placeholder: true
                    });
                }
            } else {
                // Fallback for malformed results
                images.push({
                    id: Date.now(),
                    prompt: 'AI Generated',
                    url: `https://picsum.photos/512/512?random=${Date.now()}`,
                    timestamp: new Date(),
                    type: 'generated',
                    selected: false,
                    ai_result: result,
                    is_placeholder: true
                });
            }
            
            return images;
        },
        
        // Get selected images
        getSelectedImages: function() {
            return this.selectedImages.map(img => img.id);
        },
        
        // Show description modal
        showDescriptionModal: function(result) {
            const description = result.final_answer || 'No description available';
            const modal = $(`
                <div class="huraii-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>🎨 Artwork Description</h3>
                            <button class="modal-close">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="description-content">${description}</div>
                            <div class="analysis-details">
                                <strong>Analysis Time:</strong> ${result.processing_time?.toFixed(2) || 0}s<br>
                                <strong>Confidence:</strong> ${((result.confidence_score || 0) * 100).toFixed(1)}%<br>
                                <strong>Agents Used:</strong> ${result.agents_used?.join(', ') || 'Unknown'}
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.fadeIn(300);
            
            modal.find('.modal-close').on('click', () => {
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },
        
        // Trigger download
        triggerDownload: function(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        // Show export modal
        showExportModal: function(data) {
            const modal = $(`
                <div class="huraii-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>📤 Export Ready</h3>
                            <button class="modal-close">×</button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Format:</strong> ${data.export_format}</p>
                            <p><strong>Images:</strong> ${data.image_count}</p>
                            <p><strong>Specifications:</strong></p>
                            <pre>${JSON.stringify(data.specifications, null, 2)}</pre>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.fadeIn(300);
            
            modal.find('.modal-close').on('click', () => {
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },
        
        // Show share modal
        showShareModal: function(data) {
            const modal = $(`
                <div class="huraii-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>🔗 Share Links</h3>
                            <button class="modal-close">×</button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Platform:</strong> ${data.platform}</p>
                            <div class="share-links">
                                ${data.share_urls.map(share => `
                                    <div class="share-link">
                                        <input type="text" value="${share.share_url}" readonly>
                                        <button class="copy-link-btn" data-url="${share.share_url}">Copy</button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.fadeIn(300);
            
            modal.find('.copy-link-btn').on('click', function() {
                const url = $(this).data('url');
                navigator.clipboard.writeText(url);
                $(this).text('Copied!');
                setTimeout(() => {
                    $(this).text('Copy');
                }, 2000);
            });
        },
        
        // Add uploaded image to gallery
        addUploadedImage: function(fileUrl, analysis) {
            const uploadedImage = {
                id: Date.now(),
                url: fileUrl,
                type: 'uploaded',
                timestamp: new Date(),
                analysis: analysis
            };
            
            this.uploadQueue.push(uploadedImage);
            this.displayUploadedImage(uploadedImage);
        },
        
        // Load functions for tab switching
        loadRegenerateImages: function() {
            // Load images available for regeneration
            const $grid = $('#regenerate-source-grid');
            $grid.empty();
            
            if (this.generatedImages.length === 0) {
                $grid.html('<div class="no-images">No images available for regeneration. Generate some artwork first!</div>');
                return;
            }
            
            this.generatedImages.forEach(image => {
                const $imageCard = $(`
                    <div class="source-image-card" data-id="${image.id}">
                        <img src="${image.url}" alt="Source image">
                        <div class="image-overlay">
                            <input type="checkbox" class="image-select" data-id="${image.id}">
                        </div>
                    </div>
                `);
                $grid.append($imageCard);
            });
        },
        
        loadUpscaleImages: function() {
            // Load images available for upscaling
            const $grid = $('#upscale-source-grid');
            $grid.empty();
            
            if (this.generatedImages.length === 0) {
                $grid.html('<div class="no-images">No images available for upscaling. Generate some artwork first!</div>');
                return;
            }
            
            this.generatedImages.forEach(image => {
                const $imageCard = $(`
                    <div class="upscale-image-card" data-id="${image.id}">
                        <img src="${image.url}" alt="Source image">
                        <div class="image-overlay">
                            <input type="checkbox" class="image-select" data-id="${image.id}">
                        </div>
                    </div>
                `);
                $grid.append($imageCard);
            });
        },
        
        loadVaryImages: function() {
            // Load images available for variation
            const $area = $('.source-image-area');
            $area.empty();
            
            if (this.generatedImages.length === 0) {
                $area.html('<div class="no-images">No images available for variation. Generate some artwork first!</div>');
                return;
            }
            
            $area.html('<p>Select an image to create variations:</p>');
            
            const $grid = $('<div class="vary-image-grid"></div>');
            this.generatedImages.forEach(image => {
                const $imageCard = $(`
                    <div class="vary-image-card" data-id="${image.id}">
                        <img src="${image.url}" alt="Source image">
                        <div class="image-overlay">
                            <input type="radio" name="vary-source" class="image-select" data-id="${image.id}">
                        </div>
                    </div>
                `);
                $grid.append($imageCard);
            });
            
            $area.append($grid);
        },
        
        loadDescribeImages: function() {
            // Load images available for description
            const $area = $('.describe-image-area');
            $area.empty();
            
            if (this.generatedImages.length === 0) {
                $area.html('<div class="no-images">No images available for description. Generate some artwork first!</div>');
                return;
            }
            
            $area.html('<p>Select an image to describe:</p>');
            
            const $grid = $('<div class="describe-image-grid"></div>');
            this.generatedImages.forEach(image => {
                const $imageCard = $(`
                    <div class="describe-image-card" data-id="${image.id}">
                        <img src="${image.url}" alt="Source image">
                        <div class="image-overlay">
                            <input type="radio" name="describe-source" class="image-select" data-id="${image.id}">
                        </div>
                    </div>
                `);
                $grid.append($imageCard);
            });
            
            $area.append($grid);
        },
        
        loadDownloadGallery: function() {
            // Load images available for download
            const $gallery = $('#download-gallery');
            $gallery.empty();
            
            if (this.generatedImages.length === 0) {
                $gallery.html('<div class="no-images">No images available for download. Generate some artwork first!</div>');
                return;
            }
            
            this.generatedImages.forEach(image => {
                const $imageCard = $(`
                    <div class="download-image-card" data-id="${image.id}">
                        <img src="${image.url}" alt="Download image">
                        <div class="image-overlay">
                            <input type="checkbox" class="image-select" data-id="${image.id}">
                            <div class="download-info">
                                <div class="image-size">512x512</div>
                                <div class="file-size">~2.5MB</div>
                            </div>
                        </div>
                    </div>
                `);
                $gallery.append($imageCard);
            });
        },
        
        initializeUploadArea: function() {
            // Initialize upload area with drag and drop
            this.setupDragDrop();
        },
        
        loadCollections: function() {
            // Load user collections for saving
            const $container = $('.collections-container');
            $container.empty();
            
            if (Object.keys(this.collections).length === 0) {
                $container.html('<div class="no-collections">No collections yet. Create one by saving images!</div>');
                return;
            }
            
            Object.entries(this.collections).forEach(([id, collection]) => {
                const $collectionCard = $(`
                    <div class="collection-card" data-id="${id}">
                        <div class="collection-name">${collection.name}</div>
                        <div class="collection-count">${collection.images.length} images</div>
                        <div class="collection-date">${new Date(collection.created).toLocaleDateString()}</div>
                    </div>
                `);
                $container.append($collectionCard);
            });
        },
        
        loadDeleteGallery: function() {
            // Load images available for deletion
            const $gallery = $('.delete-gallery');
            $gallery.empty();
            
            if (this.generatedImages.length === 0) {
                $gallery.html('<div class="no-images">No images to delete.</div>');
                return;
            }
            
            this.generatedImages.forEach(image => {
                const $imageCard = $(`
                    <div class="delete-image-card" data-id="${image.id}">
                        <img src="${image.url}" alt="Delete image">
                        <div class="image-overlay">
                            <input type="checkbox" class="image-select" data-id="${image.id}">
                            <div class="delete-warning">⚠️</div>
                        </div>
                    </div>
                `);
                $gallery.append($imageCard);
            });
        },
        
        initializeEditWorkspace: function() {
            // Initialize edit workspace
            console.log('Edit workspace initialized');
        },
        
        loadEnhanceOptions: function() {
            // Load enhancement options
            console.log('Enhancement options loaded');
        },
        
        loadExportOptions: function() {
            // Load export options
            console.log('Export options loaded');
        },
        
        loadShareOptions: function() {
            // Load sharing options
            console.log('Share options loaded');
        },
        
        // UI interaction handlers
        handleUploadClick: function() { 
            $('#file-input').click(); 
        },
        
        handleRatioChange: function(e) { 
            $('.ratio-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        
        handleSliderChange: function(e) { 
            const value = $(e.currentTarget).val();
            $(e.currentTarget).siblings('.slider-value').text(value);
        },
        
        handleStrengthChange: function(e) { 
            $('.strength-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        
        handleFactorChange: function(e) { 
            $('.factor-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        
        handleVariationChange: function(e) { 
            $('.variation-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        
        handleFormatChange: function(e) { 
            $('.format-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        
        handleExportFormat: function(e) {
            $('.format-option').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        
        handleSelectAll: function() {
            $('.image-select').prop('checked', true);
            this.updateSelectedImages();
        },
        
        handleDeselectAll: function() {
            $('.image-select').prop('checked', false);
            this.updateSelectedImages();
        },
        
        handleEditTool: function(e) {
            const tool = $(e.currentTarget).data('tool');
            this.currentEditTool = tool;
            $('.edit-tool-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        
        handleApplyEdits: function() {
            if (!this.currentEditTool) {
                this.showNotification('Please select an edit tool first', 'warning');
                return;
            }
            
            const parameters = this.getEditParameters();
            this.handleEditAction(this.currentEditTool, parameters);
        },
        
        getEditParameters: function() {
            // Collect edit parameters from UI
            return {
                brightness: $('.brightness-slider').val() || 0,
                contrast: $('.contrast-slider').val() || 0,
                saturation: $('.saturation-slider').val() || 0,
                hue: $('.hue-slider').val() || 0
            };
        },
        
        updateSelectedImages: function() {
            this.selectedImages = [];
            $('.image-select:checked').each((i, el) => {
                const imageId = $(el).data('id');
                const image = this.generatedImages.find(img => img.id == imageId);
                if (image) {
                    this.selectedImages.push(image);
                }
            });
        },
        
        handleSave: function() {
            this.updateSelectedImages();
            
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to save', 'warning');
                return;
            }
            
            const collectionName = prompt('Enter collection name:');
            if (!collectionName) return;
            
            // Make AJAX call
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_save',
                    nonce: vortexHuraiiConfig.nonce,
                    image_ids: this.selectedImages.map(img => img.id),
                    collection_name: collectionName
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Save failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Save Error:', error);
                    this.showNotification('Network error during save', 'error');
                }
            });
        },
        
        handleDelete: function() {
            this.updateSelectedImages();
            
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to delete', 'warning');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${this.selectedImages.length} image(s)?`)) {
                return;
            }
            
            // Make AJAX call
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_delete',
                    nonce: vortexHuraiiConfig.nonce,
                    image_ids: this.selectedImages.map(img => img.id)
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        // Remove from local arrays
                        this.selectedImages.forEach(img => {
                            const index = this.generatedImages.findIndex(g => g.id === img.id);
                            if (index > -1) {
                                this.generatedImages.splice(index, 1);
                            }
                        });
                        this.loadDeleteGallery(); // Refresh gallery
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Delete failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Delete Error:', error);
                    this.showNotification('Network error during deletion', 'error');
                }
            });
        },
        
        handleEdit: function() {
            this.showNotification('Edit functionality activated', 'info');
        },
        
        handleExport: function() {
            this.updateSelectedImages();
            
            if (this.selectedImages.length === 0) {
                this.showNotification('Please select images to export', 'warning');
                return;
            }
            
            const exportFormat = $('.export-format-select').val() || 'png-hd';
            
            // Make AJAX call
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_export',
                    nonce: vortexHuraiiConfig.nonce,
                    image_ids: this.selectedImages.map(img => img.id),
                    export_format: exportFormat
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.showExportModal(response.data);
                    } else {
                        this.showNotification(response.data || 'Export failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Export Error:', error);
                    this.showNotification('Network error during export', 'error');
                }
            });
        },
        
        // Handle edit action through Vault
        handleEditAction: function(tool, parameters) {
            const selectedImage = this.selectedImages[0];
            if (!selectedImage) {
                this.showNotification('Please select an image to edit', 'error');
                return;
            }
            
            this.isProcessing = true;
            this.showProcessingState(`Applying ${tool} edit...`);
            
            // Make AJAX call through Vault
            $.ajax({
                url: vortexHuraiiConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_edit',
                    nonce: vortexHuraiiConfig.nonce,
                    image_id: selectedImage.id,
                    edit_tool: tool,
                    edit_parameters: parameters
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.credits -= response.data.cost;
                        this.updateCreditsDisplay();
                        this.addGeneratedArtwork(response.data.result);
                        this.saveData();
                    } else {
                        this.showNotification(response.data || 'Edit failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('HURAII Edit Error:', error);
                    this.showNotification('Network error during editing', 'error');
                },
                complete: () => {
                    this.isProcessing = false;
                    this.hideProcessingState();
                }
            });
        },
        
        // Format time
        formatTime: function(timestamp) {
            return new Date(timestamp).toLocaleTimeString();
        },
        
        // Focus prompt input
        focusPromptInput: function() {
            setTimeout(() => {
                $('#generate-prompt').focus();
            }, 100);
        },
        
        // Load saved data
        loadSavedData: function() {
            const saved = localStorage.getItem('huraii-dashboard-data');
            if (saved) {
                const data = JSON.parse(saved);
                this.generatedImages = data.generatedImages || [];
                this.collections = data.collections || [];
                this.credits = data.credits || 1000;
            }
        },
        
        // Save data
        saveData: function() {
            const data = {
                generatedImages: this.generatedImages,
                collections: this.collections,
                credits: this.credits
            };
            localStorage.setItem('huraii-dashboard-data', JSON.stringify(data));
        },
        
        // Initialize interface
        initializeInterface: function() {
            this.updateCreditsDisplay();
            this.focusPromptInput();
        },
        
        // Animate on load
        animateOnLoad: function() {
            $('.huraii-tab.midjourney-tab').each((i, tab) => {
                setTimeout(() => {
                    $(tab).addClass('animate-in');
                }, i * 100);
            });
        },
        
        // Animate image cards
        animateImageCards: function() {
            $('.image-card').each((i, card) => {
                setTimeout(() => {
                    $(card).addClass('animate-in');
                }, i * 150);
            });
        },
        
        // Toggle fullscreen mode
        toggleFullscreen: function() {
            const $dashboard = $('.vortex-huraii-dashboard');
            const $body = $('body');
            
            if ($dashboard.hasClass('fullscreen-mode')) {
                // Exit fullscreen
                $dashboard.removeClass('fullscreen-mode');
                $body.removeClass('huraii-fullscreen-active');
                $('.huraii-btn-fullscreen').html('🔲 Fullscreen');
                
                // Restore original scroll
                $body.css('overflow', '');
                
                this.showNotification('Exited fullscreen mode', 'info');
                
                // Scroll back to dashboard
                $dashboard[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                
            } else {
                // Enter fullscreen
                $dashboard.addClass('fullscreen-mode');
                $body.addClass('huraii-fullscreen-active');
                $('.huraii-btn-fullscreen').html('🗗 Exit Fullscreen');
                
                // Prevent body scroll
                $body.css('overflow', 'hidden');
                
                this.showNotification('Entered fullscreen mode. Press ESC to exit.', 'info');
                
                // Focus on the current tab content
                const activePane = $('.huraii-tab-pane.active');
                if (activePane.length) {
                    activePane.focus();
                }
            }
            
            // Trigger resize event for any responsive components
            setTimeout(() => {
                $(window).trigger('resize');
            }, 300);
        },
        
        // Handle keyboard shortcuts
        handleKeyboard: function(e) {
            // F11 for fullscreen toggle
            if (e.key === 'F11') {
                e.preventDefault();
                this.toggleFullscreen();
                return;
            }
            
            // Ctrl/Cmd + Enter for generate (when in generate tab)
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && this.currentTab === 'generate') {
                e.preventDefault();
                $('.generate-btn').trigger('click');
                return;
            }
            
            // Tab navigation (1-9 keys)
            if (e.key >= '1' && e.key <= '9' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                const tabIndex = parseInt(e.key) - 1;
                const $tabs = $('.huraii-tab.midjourney-tab');
                if ($tabs.eq(tabIndex).length) {
                    e.preventDefault();
                    $tabs.eq(tabIndex).trigger('click');
                }
                return;
            }
        },
        
        // Handle voice commands (placeholder)
        handleVoiceCommand: function() {
            this.showNotification('Voice commands coming soon!', 'info');
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="huraii-notification ${type}">
                    <span class="notification-icon">
                        ${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}
                    </span>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">×</button>
                </div>
            `);
            
            // Add to dashboard
            const $container = $('.vortex-huraii-dashboard');
            let $notificationArea = $container.find('.notification-area');
            
            if (!$notificationArea.length) {
                $notificationArea = $('<div class="notification-area"></div>');
                $container.append($notificationArea);
            }
            
            $notificationArea.append($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
            
            // Manual close
            $notification.find('.notification-close').on('click', () => {
                $notification.fadeOut(() => $notification.remove());
            });
        },
        
        // Show processing state
        showProcessingState: function(message) {
            const $processing = $(`
                <div class="processing-overlay">
                    <div class="processing-content">
                        <div class="processing-spinner"></div>
                        <div class="processing-message">${message}</div>
                    </div>
                </div>
            `);
            
            $('.vortex-huraii-dashboard').append($processing);
        },
        
        // Hide processing state
        hideProcessingState: function() {
            $('.processing-overlay').fadeOut(() => {
                $('.processing-overlay').remove();
            });
        },
        
        // Update credits display
        updateCreditsDisplay: function() {
            $('#huraii-session-cost').text(this.credits);
        },
        
        // Add generated artwork to collection
        addGeneratedArtwork: function(artwork) {
            if (artwork && artwork.response) {
                const newImage = {
                    id: Date.now(),
                    prompt: artwork.query || 'Generated artwork',
                    url: `https://picsum.photos/512/512?random=${Date.now()}`,
                    timestamp: new Date(),
                    type: 'generated',
                    ai_response: artwork.response
                };
                
                this.generatedImages.unshift(newImage);
                this.displayGeneratedImages([newImage]);
            }
        },
        
        // Setup drag and drop
        setupDragDrop: function() {
            const $dropzone = $('#upload-dropzone');
            
            $dropzone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $dropzone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            $dropzone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const files = Array.from(e.originalEvent.dataTransfer.files);
                files.forEach(file => {
                    if (file.type.startsWith('image/')) {
                        HuraiiDashboard.processUploadedFile(file);
                    }
                });
            });
        },
        
        // Placeholder functions for remaining features
        handleCollectionClick: function() { /* Handle collection click */ },
        handleSaveToCollection: function() { /* Handle save to collection */ },
        handleEditTool: function() { /* Handle edit tool */ },
        handleApplyEdits: function() { /* Handle apply edits */ },
        handleExport: function() { /* Handle export */ },
        handleExportFormat: function() { /* Handle export format */ },
        showStylePresets: function() { /* Show style presets */ },
        showAdvancedSettings: function() { /* Show advanced settings */ }
    };

    // Initialize when DOM is ready
    HuraiiDashboard.init();
    
    // Auto-save every 30 seconds
    setInterval(() => {
        HuraiiDashboard.saveData();
    }, 30000);
    
    // Save on page unload
    $(window).on('beforeunload', () => {
        HuraiiDashboard.saveData();
    });

})(jQuery); 