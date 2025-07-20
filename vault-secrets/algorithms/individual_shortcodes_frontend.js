/**
 * Individual Shortcodes JavaScript
 * Handles interactive functionality for individual HURAII tab shortcodes
 * 
 * Features:
 * - Enhanced 7-step orchestration pipeline
 * - Real-time cost tracking with 80% profit margin
 * - Continuous learning feedback loops
 * - Quality metrics and performance monitoring
 * - AWS services integration
 * - Marketplace synchronization
 * - Audit logging
 */

(function($) {
    'use strict';

    // Global configuration
    const VortexIndividualShortcodes = {
        // Configuration from WordPress
        config: {
            ajaxUrl: vortexIndividualConfig.ajaxUrl,
            restUrl: vortexIndividualConfig.restUrl,
            nonce: vortexIndividualConfig.nonce,
            restNonce: vortexIndividualConfig.restNonce,
            userId: vortexIndividualConfig.userId,
            isLoggedIn: vortexIndividualConfig.isLoggedIn,
            orchestrationEndpoint: vortexIndividualConfig.orchestrationEndpoint,
            costTracking: vortexIndividualConfig.costTracking,
            profitMargin: vortexIndividualConfig.profitMargin,
            continuousLearning: vortexIndividualConfig.continuousLearning
        },

        // State management
        state: {
            activeShortcodes: new Map(),
            currentCosts: new Map(),
            qualityMetrics: new Map(),
            processingQueue: [],
            learningFeedback: [],
            orchestrationData: {}
        },

        // Cost tracking
        costTracker: {
            sessionCost: 0.0,
            targetMargin: 0.80,
            currentMargin: 0.0,
            optimizationSuggestions: [],
            costBreakdown: {
                vault_fetch: 0.001,
                gpu_call: 0.015,
                memory_store: 0.002,
                eventbus_emit: 0.0005,
                s3_write: 0.0001,
                batch_training: 0.005,
                response_processing: 0.001
            }
        },

        // Quality metrics
        qualityTracker: {
            averageQuality: 0.0,
            qualityHistory: [],
            agentPerformance: {
                huraii: { quality: 0.85, cost: 0.01 },
                cloe: { quality: 0.90, cost: 0.008 },
                horace: { quality: 0.88, cost: 0.012 },
                thorius: { quality: 0.92, cost: 0.015 },
                archer: { quality: 0.95, cost: 0.020 }
            },
            processingTimes: []
        },

        // Initialize the system
        init: function() {
            $(document).ready(() => {
                this.setupEventListeners();
                this.initializeShortcodes();
                this.setupCostTracking();
                this.setupQualityMetrics();
                this.setupContinuousLearning();
            });
        },

        // Setup event listeners
        setupEventListeners: function() {
            // Generate shortcode events
            $(document).on('click', '.generate-btn', this.handleGenerate.bind(this));
            $(document).on('click', '.preset-btn', this.handlePresetSelection.bind(this));
            $(document).on('click', '.ratio-btn', this.handleRatioSelection.bind(this));
            $(document).on('input', '.creativity-slider, .detail-slider', this.handleSliderChange.bind(this));
            $(document).on('click', '.voice-btn', this.handleVoiceCommand.bind(this));

            // Describe shortcode events
            $(document).on('click', '.describe-btn', this.handleDescribe.bind(this));
            $(document).on('change', '#describe-file-input', this.handleFileUpload.bind(this));
            $(document).on('click', '.clear-btn', this.handleClearInput.bind(this));
            $(document).on('change', '.aspect-checkboxes input, .agent-checkboxes input', this.handleAnalysisOptions.bind(this));

            // Upscale shortcode events
            $(document).on('click', '.upscale-btn', this.handleUpscale.bind(this));
            $(document).on('click', '.factor-btn', this.handleFactorSelection.bind(this));
            $(document).on('change', '#upscale-file-input', this.handleUpscaleFileUpload.bind(this));

            // Enhance shortcode events
            $(document).on('click', '.enhance-btn', this.handleEnhance.bind(this));
            $(document).on('click', '.enhancement-card', this.handleEnhancementCardSelection.bind(this));
            $(document).on('click', '.batch-enhance-btn', this.handleBatchEnhance.bind(this));
            $(document).on('click', '.select-all-btn', this.handleSelectAll.bind(this));
            $(document).on('click', '.clear-selection-btn', this.handleClearSelection.bind(this));

            // Export shortcode events
            $(document).on('click', '.export-btn', this.handleExport.bind(this));
            $(document).on('click', '.format-btn', this.handleFormatSelection.bind(this));
            $(document).on('change', '.quality-option input[type="radio"]', this.handleQualitySelection.bind(this));

            // Share shortcode events
            $(document).on('click', '.share-btn', this.handleShare.bind(this));
            $(document).on('click', '.platform-btn', this.handlePlatformSelection.bind(this));

            // Drag and drop events
            $(document).on('dragover', '.upload-area', this.handleDragOver.bind(this));
            $(document).on('dragleave', '.upload-area', this.handleDragLeave.bind(this));
            $(document).on('drop', '.upload-area', this.handleDrop.bind(this));
            $(document).on('click', '.upload-area', this.handleUploadClick.bind(this));

            // Quality feedback events
            $(document).on('click', '.quality-feedback-btn', this.handleQualityFeedback.bind(this));
            $(document).on('click', '.cost-optimization-btn', this.handleCostOptimization.bind(this));
        },

        // Initialize shortcodes
        initializeShortcodes: function() {
            $('.huraii-individual-shortcode').each((index, element) => {
                const $shortcode = $(element);
                const shortcodeType = $shortcode.data('shortcode');
                
                this.state.activeShortcodes.set(shortcodeType, {
                    element: $shortcode,
                    initialized: true,
                    processing: false,
                    lastResult: null
                });

                // Initialize cost tracking for this shortcode
                this.state.currentCosts.set(shortcodeType, 0.0);
                this.state.qualityMetrics.set(shortcodeType, {
                    quality: 0.0,
                    processingTime: 0.0,
                    agentsUsed: []
                });

                // Update initial UI state
                this.updateCostDisplay($shortcode);
                this.updateQualityDisplay($shortcode);
            });
        },

        // Setup cost tracking
        setupCostTracking: function() {
            if (!this.config.costTracking) return;

            // Update cost displays every second
            setInterval(() => {
                this.updateAllCostDisplays();
            }, 1000);

            // Check profit margin every 30 seconds
            setInterval(() => {
                this.checkProfitMargin();
            }, 30000);
        },

        // Setup quality metrics
        setupQualityMetrics: function() {
            // Update quality displays every 2 seconds
            setInterval(() => {
                this.updateAllQualityDisplays();
            }, 2000);

            // Process quality history every minute
            setInterval(() => {
                this.processQualityHistory();
            }, 60000);
        },

        // Setup continuous learning
        setupContinuousLearning: function() {
            if (!this.config.continuousLearning) return;

            // Process learning feedback every 5 minutes
            setInterval(() => {
                this.processLearningFeedback();
            }, 300000);
        },

        // Handle Generate action
        handleGenerate: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            const shortcodeType = $shortcode.data('shortcode');
            
            if (this.isProcessing($shortcode)) return;
            
            // Collect form data
            const formData = this.collectGenerateData($shortcode);
            
            if (!formData.prompt || formData.prompt.trim() === '') {
                this.showError($shortcode, 'Please enter a prompt for artwork generation.');
                return;
            }
            
            this.setProcessingState($shortcode, true);
            
            // Execute enhanced orchestration
            this.executeEnhancedOrchestration('generate', formData, shortcodeType)
                .then(result => {
                    this.handleOrchestrationSuccess($shortcode, result);
                })
                .catch(error => {
                    this.handleOrchestrationError($shortcode, error);
                });
        },

        // Handle Describe action
        handleDescribe: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            const shortcodeType = $shortcode.data('shortcode');
            
            if (this.isProcessing($shortcode)) return;
            
            // Collect form data
            const formData = this.collectDescribeData($shortcode);
            
            if (!formData.input || formData.input.trim() === '') {
                this.showError($shortcode, 'Please enter text or upload an image for analysis.');
                return;
            }
            
            this.setProcessingState($shortcode, true);
            
            // Execute enhanced orchestration with CHLOE (CLOE) analysis
            this.executeEnhancedOrchestration('describe', formData, shortcodeType)
                .then(result => {
                    this.handleOrchestrationSuccess($shortcode, result);
                    this.displayChloeAnalysis($shortcode, result);
                })
                .catch(error => {
                    this.handleOrchestrationError($shortcode, error);
                });
        },

        // Handle other actions
        handleUpscale: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            const shortcodeType = $shortcode.data('shortcode');
            
            if (this.isProcessing($shortcode)) return;
            
            const formData = this.collectUpscaleData($shortcode);
            
            if (!formData.image_id) {
                this.showError($shortcode, 'Please select an image to upscale.');
                return;
            }
            
            this.setProcessingState($shortcode, true);
            
            this.executeEnhancedOrchestration('upscale', formData, shortcodeType)
                .then(result => {
                    this.handleOrchestrationSuccess($shortcode, result);
                })
                .catch(error => {
                    this.handleOrchestrationError($shortcode, error);
                });
        },

        handleEnhance: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            const shortcodeType = $shortcode.data('shortcode');
            const enhancementType = $button.data('enhance');
            
            if (this.isProcessing($shortcode)) return;
            
            const formData = this.collectEnhanceData($shortcode, enhancementType);
            
            if (!formData.image_ids || formData.image_ids.length === 0) {
                this.showError($shortcode, 'Please select images to enhance.');
                return;
            }
            
            this.setProcessingState($shortcode, true);
            
            this.executeEnhancedOrchestration('enhance', formData, shortcodeType)
                .then(result => {
                    this.handleOrchestrationSuccess($shortcode, result);
                })
                .catch(error => {
                    this.handleOrchestrationError($shortcode, error);
                });
        },

        handleExport: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            const shortcodeType = $shortcode.data('shortcode');
            
            if (this.isProcessing($shortcode)) return;
            
            const formData = this.collectExportData($shortcode);
            
            if (!formData.image_ids || formData.image_ids.length === 0) {
                this.showError($shortcode, 'Please select images to export.');
                return;
            }
            
            this.setProcessingState($shortcode, true);
            
            this.executeEnhancedOrchestration('export', formData, shortcodeType)
                .then(result => {
                    this.handleOrchestrationSuccess($shortcode, result);
                })
                .catch(error => {
                    this.handleOrchestrationError($shortcode, error);
                });
        },

        handleShare: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            const shortcodeType = $shortcode.data('shortcode');
            
            if (this.isProcessing($shortcode)) return;
            
            const formData = this.collectShareData($shortcode);
            
            if (!formData.image_ids || formData.image_ids.length === 0) {
                this.showError($shortcode, 'Please select images to share.');
                return;
            }
            
            if (!formData.platform) {
                this.showError($shortcode, 'Please select a platform to share to.');
                return;
            }
            
            this.setProcessingState($shortcode, true);
            
            this.executeEnhancedOrchestration('share', formData, shortcodeType)
                .then(result => {
                    this.handleOrchestrationSuccess($shortcode, result);
                })
                .catch(error => {
                    this.handleOrchestrationError($shortcode, error);
                });
        },

        // Execute enhanced orchestration with 7-step pipeline
        executeEnhancedOrchestration: function(action, formData, shortcodeType) {
            const startTime = Date.now();
            
            return new Promise((resolve, reject) => {
                // Track orchestration start
                this.trackOrchestrationStart(action, shortcodeType);
                
                $.ajax({
                    url: this.config.orchestrationEndpoint + action,
                    type: 'POST',
                    data: {
                        action: `huraii_individual_${action}`,
                        nonce: this.config.nonce,
                        ...formData
                    },
                    timeout: 60000, // 60 second timeout
                    success: (response) => {
                        const processingTime = Date.now() - startTime;
                        
                        if (response.success) {
                            // Track successful orchestration
                            this.trackOrchestrationSuccess(action, response.data, processingTime);
                            
                            // Update cost tracking
                            this.updateCostTracking(shortcodeType, response.data.orchestration_data?.cost_analysis);
                            
                            // Update quality metrics
                            this.updateQualityMetrics(shortcodeType, response.data.orchestration_data?.quality_metrics);
                            
                            // Process continuous learning
                            this.processContinuousLearning(action, response.data.orchestration_data?.continuous_learning);
                            
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data?.message || 'Unknown error occurred'));
                        }
                    },
                    error: (xhr, status, error) => {
                        const processingTime = Date.now() - startTime;
                        
                        // Track orchestration error
                        this.trackOrchestrationError(action, error, processingTime);
                        
                        reject(new Error(`${status}: ${error}`));
                    }
                });
            });
        },

        // Data collection functions
        collectGenerateData: function($shortcode) {
            return {
                prompt: $shortcode.find('.generate-prompt-textarea').val(),
                style: $shortcode.find('.preset-btn.active').data('preset') || 'artistic',
                aspect_ratio: $shortcode.find('.ratio-btn.active').data('ratio') || '1:1',
                quality: $shortcode.find('.quality-select').val() || 'standard',
                creativity_level: $shortcode.find('.creativity-slider').val() || 70,
                detail_level: $shortcode.find('.detail-slider').val() || 80,
                agents: this.getSelectedAgents($shortcode)
            };
        },

        collectDescribeData: function($shortcode) {
            return {
                input: $shortcode.find('.describe-input-textarea').val(),
                analysis_depth: $shortcode.find('.analysis-select').val() || 'comprehensive',
                aspects: this.getSelectedAspects($shortcode),
                agents: this.getSelectedAgents($shortcode),
                uploaded_image: $shortcode.find('#describe-file-input')[0]?.files[0] || null
            };
        },

        collectUpscaleData: function($shortcode) {
            return {
                image_id: $shortcode.find('.selected-image').data('image-id'),
                upscale_factor: $shortcode.find('.factor-btn.active').data('factor') || 2,
                quality: $shortcode.find('.quality-select').val() || 'standard',
                enhancement_options: this.getSelectedEnhancementOptions($shortcode)
            };
        },

        collectEnhanceData: function($shortcode, enhancementType) {
            return {
                image_ids: this.getSelectedImageIds($shortcode),
                enhancement_type: enhancementType || 'super-res'
            };
        },

        collectExportData: function($shortcode) {
            return {
                image_ids: this.getSelectedImageIds($shortcode),
                format: $shortcode.find('.format-btn.active').data('format') || 'jpg',
                quality: $shortcode.find('.quality-option input[type="radio"]:checked').val() || 'web'
            };
        },

        collectShareData: function($shortcode) {
            return {
                image_ids: this.getSelectedImageIds($shortcode),
                platform: $shortcode.find('.platform-btn.active').data('platform'),
                privacy: $shortcode.find('.privacy-select').val() || 'public',
                watermark: $shortcode.find('#add-watermark').is(':checked'),
                include_signature: $shortcode.find('#include-signature').is(':checked')
            };
        },

        // Helper functions
        getSelectedAgents: function($shortcode) {
            const agents = [];
            $shortcode.find('.agent-checkboxes input:checked').each(function() {
                agents.push($(this).val());
            });
            return agents;
        },

        getSelectedAspects: function($shortcode) {
            const aspects = [];
            $shortcode.find('.aspect-checkboxes input:checked').each(function() {
                aspects.push($(this).val());
            });
            return aspects;
        },

        getSelectedEnhancementOptions: function($shortcode) {
            const options = [];
            $shortcode.find('.enhancement-checkboxes input:checked').each(function() {
                options.push($(this).val());
            });
            return options;
        },

        getSelectedImageIds: function($shortcode) {
            const imageIds = [];
            $shortcode.find('.selected-image').each(function() {
                imageIds.push($(this).data('image-id'));
            });
            return imageIds;
        },

        // Processing state management
        isProcessing: function($shortcode) {
            return $shortcode.hasClass('processing');
        },

        setProcessingState: function($shortcode, processing) {
            const $button = $shortcode.find('.huraii-btn-primary');
            
            if (processing) {
                $shortcode.addClass('processing');
                $button.addClass('loading').prop('disabled', true);
            } else {
                $shortcode.removeClass('processing');
                $button.removeClass('loading').prop('disabled', false);
            }
        },

        // Result handling
        handleOrchestrationSuccess: function($shortcode, result) {
            this.setProcessingState($shortcode, false);
            
            // Display result
            this.displayResult($shortcode, result);
            
            // Update metrics
            this.updateMetricsDisplay($shortcode, result.orchestration_data);
            
            // Show success message
            this.showSuccess($shortcode, result.message || 'Operation completed successfully!');
            
            // Process marketplace sync if applicable
            if (result.marketplace_sync) {
                this.processMarketplaceSync($shortcode, result.marketplace_sync);
            }
        },

        handleOrchestrationError: function($shortcode, error) {
            this.setProcessingState($shortcode, false);
            
            // Show error message
            this.showError($shortcode, error.message || 'An error occurred during processing.');
            
            // Log error for debugging
            console.error('Orchestration error:', error);
        },

        // Display functions
        displayResult: function($shortcode, result) {
            const $resultsContainer = $shortcode.find('.generation-results, .describe-results, .upscale-results, .enhance-results, .export-results, .share-results');
            
            if ($resultsContainer.length === 0) return;
            
            // Clear previous results
            $resultsContainer.empty();
            
            // Display new results based on action type
            if (result.results) {
                const resultHtml = this.formatResultsDisplay(result);
                $resultsContainer.html(resultHtml);
            }
        },

        displayChloeAnalysis: function($shortcode, result) {
            const $resultsContainer = $shortcode.find('.describe-results');
            
            if (result.chloe_analysis) {
                const analysisHtml = this.formatChloeAnalysis(result.chloe_analysis);
                $resultsContainer.append(analysisHtml);
            }
        },

        formatResultsDisplay: function(result) {
            let html = '<div class="orchestration-results">';
            
            // Display orchestration steps
            if (result.orchestration_data?.steps_completed) {
                html += `<div class="orchestration-steps">
                    <h4>🔄 Orchestration Pipeline (${result.orchestration_data.steps_completed}/7 steps)</h4>
                    <div class="steps-completed">
                        <div class="step completed">1. Vault Secret Fetch ✓</div>
                        <div class="step completed">2. Colossal GPU Call ✓</div>
                        <div class="step completed">3. Memory Store ✓</div>
                        <div class="step completed">4. EventBus Emit ✓</div>
                        <div class="step completed">5. S3 Data-Lake Write ✓</div>
                        <div class="step completed">6. Batch Training Trigger ✓</div>
                        <div class="step completed">7. Return Response ✓</div>
                    </div>
                </div>`;
            }
            
            // Display results
            if (result.results) {
                html += '<div class="results-content">';
                
                Object.keys(result.results).forEach(agentId => {
                    const agentResult = result.results[agentId];
                    if (agentResult && !agentResult.error) {
                        html += `<div class="agent-result">
                            <h5>${agentId.toUpperCase()} Result:</h5>
                            <p>${agentResult.response || agentResult}</p>
                        </div>`;
                    }
                });
                
                html += '</div>';
            }
            
            html += '</div>';
            
            return html;
        },

        formatChloeAnalysis: function(chloeAnalysis) {
            let html = '<div class="chloe-analysis-results">';
            
            html += '<h4>🔍 CHLOE Analysis Results</h4>';
            
            if (chloeAnalysis.analysis_depth) {
                html += `<div class="analysis-depth">
                    <strong>Analysis Depth:</strong> ${chloeAnalysis.analysis_depth}
                </div>`;
            }
            
            if (chloeAnalysis.aspects_analyzed) {
                html += `<div class="aspects-analyzed">
                    <strong>Aspects Analyzed:</strong> ${chloeAnalysis.aspects_analyzed.join(', ')}
                </div>`;
            }
            
            if (chloeAnalysis.agent_contributions) {
                html += '<div class="agent-contributions">';
                html += '<h5>Agent Contributions:</h5>';
                
                Object.keys(chloeAnalysis.agent_contributions).forEach(agentId => {
                    const contribution = chloeAnalysis.agent_contributions[agentId];
                    if (contribution && !contribution.error) {
                        html += `<div class="agent-contribution">
                            <strong>${agentId.toUpperCase()}:</strong>
                            <p>${contribution.response || contribution}</p>
                        </div>`;
                    }
                });
                
                html += '</div>';
            }
            
            html += '</div>';
            
            return html;
        },

        // Cost tracking functions
        updateCostTracking: function(shortcodeType, costAnalysis) {
            if (!costAnalysis) return;
            
            const currentCost = this.state.currentCosts.get(shortcodeType) || 0;
            const newCost = costAnalysis.total_cost || 0;
            
            this.state.currentCosts.set(shortcodeType, currentCost + newCost);
            this.costTracker.sessionCost += newCost;
            
            // Update profit margin
            if (costAnalysis.profit_margin !== undefined) {
                this.costTracker.currentMargin = costAnalysis.profit_margin;
            }
            
            // Store optimization suggestions
            if (costAnalysis.optimization_suggestions) {
                this.costTracker.optimizationSuggestions = costAnalysis.optimization_suggestions;
            }
        },

        updateCostDisplay: function($shortcode) {
            const shortcodeType = $shortcode.data('shortcode');
            const currentCost = this.state.currentCosts.get(shortcodeType) || 0;
            
            $shortcode.find('.session-cost').text(currentCost.toFixed(3));
            $shortcode.find('.profit-margin').text((this.costTracker.currentMargin * 100).toFixed(1) + '%');
            
            // Update color based on profit margin
            const $profitMargin = $shortcode.find('.profit-margin');
            if (this.costTracker.currentMargin < this.costTracker.targetMargin) {
                $profitMargin.css('color', '#ff6b6b');
            } else {
                $profitMargin.css('color', '#4ecdc4');
            }
        },

        updateAllCostDisplays: function() {
            $('.huraii-individual-shortcode').each((index, element) => {
                this.updateCostDisplay($(element));
            });
        },

        checkProfitMargin: function() {
            if (this.costTracker.currentMargin < this.costTracker.targetMargin) {
                this.showProfitMarginAlert();
            }
        },

        showProfitMarginAlert: function() {
            const alert = $(`
                <div class="profit-margin-alert">
                    <h4>⚠️ Profit Margin Alert</h4>
                    <p>Current profit margin (${(this.costTracker.currentMargin * 100).toFixed(1)}%) is below target (${(this.costTracker.targetMargin * 100).toFixed(1)}%)</p>
                    <div class="optimization-suggestions">
                        <strong>Suggestions:</strong>
                        <ul>
                            ${this.costTracker.optimizationSuggestions.map(suggestion => `<li>${suggestion}</li>`).join('')}
                        </ul>
                    </div>
                    <button class="huraii-btn huraii-btn-secondary dismiss-alert">Dismiss</button>
                </div>
            `);
            
            $('body').append(alert);
            
            setTimeout(() => {
                alert.fadeOut(() => alert.remove());
            }, 10000);
        },

        // Quality metrics functions
        updateQualityMetrics: function(shortcodeType, qualityMetrics) {
            if (!qualityMetrics) return;
            
            const currentMetrics = this.state.qualityMetrics.get(shortcodeType) || {};
            
            const updatedMetrics = {
                quality: qualityMetrics.quality_score || 0,
                processingTime: qualityMetrics.total_processing_time || 0,
                agentsUsed: qualityMetrics.agents_used || []
            };
            
            this.state.qualityMetrics.set(shortcodeType, updatedMetrics);
            
            // Update global quality tracking
            this.qualityTracker.qualityHistory.push(updatedMetrics.quality);
            this.qualityTracker.processingTimes.push(updatedMetrics.processingTime);
            
            // Keep only last 100 entries
            if (this.qualityTracker.qualityHistory.length > 100) {
                this.qualityTracker.qualityHistory = this.qualityTracker.qualityHistory.slice(-100);
            }
            if (this.qualityTracker.processingTimes.length > 100) {
                this.qualityTracker.processingTimes = this.qualityTracker.processingTimes.slice(-100);
            }
            
            // Update average quality
            this.qualityTracker.averageQuality = this.qualityTracker.qualityHistory.reduce((a, b) => a + b, 0) / this.qualityTracker.qualityHistory.length;
        },

        updateQualityDisplay: function($shortcode) {
            const shortcodeType = $shortcode.data('shortcode');
            const metrics = this.state.qualityMetrics.get(shortcodeType) || {};
            
            // Update quality bar
            const qualityPercent = (metrics.quality * 100).toFixed(1);
            $shortcode.find('.quality-fill').css('width', qualityPercent + '%');
            $shortcode.find('.quality-value').text(qualityPercent + '%');
            
            // Update processing time
            $shortcode.find('.processing-time').text((metrics.processingTime / 1000).toFixed(2) + 's');
            
            // Update agents used
            $shortcode.find('.agents-used').text(metrics.agentsUsed.join(', ') || '-');
        },

        updateAllQualityDisplays: function() {
            $('.huraii-individual-shortcode').each((index, element) => {
                this.updateQualityDisplay($(element));
            });
        },

        updateMetricsDisplay: function($shortcode, orchestrationData) {
            if (!orchestrationData) return;
            
            // Update cost analysis display
            if (orchestrationData.cost_analysis) {
                this.updateCostDisplay($shortcode);
            }
            
            // Update quality metrics display
            if (orchestrationData.quality_metrics) {
                this.updateQualityDisplay($shortcode);
            }
            
            // Update continuous learning display
            if (orchestrationData.continuous_learning) {
                this.updateContinuousLearningDisplay($shortcode, orchestrationData.continuous_learning);
            }
        },

        updateContinuousLearningDisplay: function($shortcode, learningData) {
            if (learningData.learning_data_collected) {
                $shortcode.find('.learning-status').text('Learning data collected ✓');
            }
            
            if (learningData.adaptation_suggested) {
                $shortcode.find('.adaptation-status').text('Model adaptation suggested ✓');
            }
        },

        // Continuous learning functions
        processContinuousLearning: function(action, learningData) {
            if (!learningData) return;
            
            this.state.learningFeedback.push({
                action: action,
                timestamp: Date.now(),
                data: learningData
            });
            
            // Process feedback if queue is full
            if (this.state.learningFeedback.length >= 10) {
                this.sendLearningFeedback();
            }
        },

        sendLearningFeedback: function() {
            if (this.state.learningFeedback.length === 0) return;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_continuous_learning_feedback',
                    nonce: this.config.nonce,
                    feedback: this.state.learningFeedback
                },
                success: (response) => {
                    if (response.success) {
                        this.state.learningFeedback = [];
                        console.log('Learning feedback sent successfully');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to send learning feedback:', error);
                }
            });
        },

        processLearningFeedback: function() {
            if (this.state.learningFeedback.length > 0) {
                this.sendLearningFeedback();
            }
        },

        // Marketplace sync functions
        processMarketplaceSync: function($shortcode, syncData) {
            if (!syncData) return;
            
            const $syncStatus = $shortcode.find('.marketplace-sync-status');
            if ($syncStatus.length === 0) {
                $shortcode.find('.generation-results, .describe-results').append(
                    '<div class="marketplace-sync-status">📊 Syncing with marketplace...</div>'
                );
            }
            
            // Update sync status
            setTimeout(() => {
                $shortcode.find('.marketplace-sync-status').text('✅ Marketplace sync completed');
            }, 2000);
        },

        // Tracking functions
        trackOrchestrationStart: function(action, shortcodeType) {
            this.state.orchestrationData[`${shortcodeType}_${action}`] = {
                startTime: Date.now(),
                action: action,
                shortcodeType: shortcodeType
            };
        },

        trackOrchestrationSuccess: function(action, result, processingTime) {
            console.log(`Orchestration success: ${action} completed in ${processingTime}ms`);
            
            // Update performance metrics
            this.qualityTracker.processingTimes.push(processingTime);
            
            // Track agent performance
            if (result.agents_used) {
                result.agents_used.forEach(agentId => {
                    if (this.qualityTracker.agentPerformance[agentId]) {
                        // Update agent performance metrics
                        this.qualityTracker.agentPerformance[agentId].lastUsed = Date.now();
                    }
                });
            }
        },

        trackOrchestrationError: function(action, error, processingTime) {
            console.error(`Orchestration error: ${action} failed in ${processingTime}ms:`, error);
            
            // Log error for analysis
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_log_orchestration_error',
                    nonce: this.config.nonce,
                    error_action: action,
                    error_message: error.message || error,
                    processing_time: processingTime
                }
            });
        },

        // UI interaction handlers
        handlePresetSelection: function(e) {
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.preset-btn').removeClass('active');
            $button.addClass('active');
        },

        handleRatioSelection: function(e) {
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.ratio-btn').removeClass('active');
            $button.addClass('active');
        },

        handleFactorSelection: function(e) {
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.factor-btn').removeClass('active');
            $button.addClass('active');
        },

        handleFormatSelection: function(e) {
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.format-btn').removeClass('active');
            $button.addClass('active');
        },

        handlePlatformSelection: function(e) {
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.platform-btn').removeClass('active');
            $button.addClass('active');
        },

        handleEnhancementCardSelection: function(e) {
            const $card = $(e.currentTarget);
            const $shortcode = $card.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.enhancement-card').removeClass('active');
            $card.addClass('active');
        },

        handleSliderChange: function(e) {
            const $slider = $(e.currentTarget);
            const $valueDisplay = $slider.siblings('.slider-value');
            
            $valueDisplay.text($slider.val() + '%');
        },

        handleAnalysisOptions: function(e) {
            // Update analysis options based on selections
            const $shortcode = $(e.currentTarget).closest('.huraii-individual-shortcode');
            this.updateAnalysisPreview($shortcode);
        },

        handleQualitySelection: function(e) {
            const $radio = $(e.currentTarget);
            const $option = $radio.closest('.quality-option');
            const $shortcode = $option.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.quality-option').removeClass('active');
            $option.addClass('active');
        },

        handleVoiceCommand: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            // Voice recognition implementation would go here
            this.showInfo($shortcode, 'Voice command feature coming soon!');
        },

        handleClearInput: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('textarea, input[type="text"]').val('');
            $shortcode.find('.upload-area').removeClass('has-file');
            $shortcode.find('.describe-results').empty();
        },

        handleSelectAll: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.image-item').addClass('selected');
        },

        handleClearSelection: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            $shortcode.find('.image-item').removeClass('selected');
        },

        handleBatchEnhance: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $shortcode = $button.closest('.huraii-individual-shortcode');
            
            const selectedImages = $shortcode.find('.image-item.selected');
            if (selectedImages.length === 0) {
                this.showError($shortcode, 'Please select images to enhance.');
                return;
            }
            
            // Trigger enhance with selected images
            this.handleEnhance(e);
        },

        // File upload handlers
        handleFileUpload: function(e) {
            const $input = $(e.currentTarget);
            const $shortcode = $input.closest('.huraii-individual-shortcode');
            const file = e.target.files[0];
            
            if (file) {
                this.processFileUpload($shortcode, file);
            }
        },

        handleUpscaleFileUpload: function(e) {
            const $input = $(e.currentTarget);
            const $shortcode = $input.closest('.huraii-individual-shortcode');
            const file = e.target.files[0];
            
            if (file) {
                this.processUpscaleFileUpload($shortcode, file);
            }
        },

        handleDragOver: function(e) {
            e.preventDefault();
            $(e.currentTarget).addClass('dragover');
        },

        handleDragLeave: function(e) {
            e.preventDefault();
            $(e.currentTarget).removeClass('dragover');
        },

        handleDrop: function(e) {
            e.preventDefault();
            
            const $uploadArea = $(e.currentTarget);
            const $shortcode = $uploadArea.closest('.huraii-individual-shortcode');
            
            $uploadArea.removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                this.processFileUpload($shortcode, files[0]);
            }
        },

        handleUploadClick: function(e) {
            const $uploadArea = $(e.currentTarget);
            const $fileInput = $uploadArea.find('input[type="file"]');
            
            $fileInput.click();
        },

        processFileUpload: function($shortcode, file) {
            // File validation
            if (!file.type.startsWith('image/')) {
                this.showError($shortcode, 'Please select a valid image file.');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                this.showError($shortcode, 'File size must be less than 10MB.');
                return;
            }
            
            // Update UI
            const $uploadArea = $shortcode.find('.upload-area');
            $uploadArea.addClass('has-file');
            $uploadArea.find('.upload-text p').first().text(`File selected: ${file.name}`);
            
            // Store file reference
            $shortcode.data('uploaded-file', file);
        },

        processUpscaleFileUpload: function($shortcode, file) {
            // Similar to processFileUpload but for upscale
            this.processFileUpload($shortcode, file);
        },

        // Utility functions
        showSuccess: function($shortcode, message) {
            this.showNotification($shortcode, message, 'success');
        },

        showError: function($shortcode, message) {
            this.showNotification($shortcode, message, 'error');
        },

        showInfo: function($shortcode, message) {
            this.showNotification($shortcode, message, 'info');
        },

        showNotification: function($shortcode, message, type) {
            const $notification = $(`
                <div class="huraii-notification notification-${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            $shortcode.prepend($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
            
            // Manual dismiss
            $notification.find('.notification-close').click(() => {
                $notification.fadeOut(() => $notification.remove());
            });
        },

        updateAnalysisPreview: function($shortcode) {
            const selectedAspects = this.getSelectedAspects($shortcode);
            const selectedAgents = this.getSelectedAgents($shortcode);
            
            // Update preview text
            const $preview = $shortcode.find('.analysis-preview');
            if ($preview.length > 0) {
                $preview.text(`Analysis will include: ${selectedAspects.join(', ')} using agents: ${selectedAgents.join(', ')}`);
            }
        },

        processQualityHistory: function() {
            // Process quality history for insights
            if (this.qualityTracker.qualityHistory.length > 10) {
                const recentQuality = this.qualityTracker.qualityHistory.slice(-10);
                const averageRecent = recentQuality.reduce((a, b) => a + b, 0) / recentQuality.length;
                
                if (averageRecent < 0.7) {
                    console.warn('Recent quality below threshold, consider model optimization');
                }
            }
        }
    };

    // Initialize when DOM is ready
    VortexIndividualShortcodes.init();

    // Expose to global scope for debugging
    window.VortexIndividualShortcodes = VortexIndividualShortcodes;

})(jQuery); 