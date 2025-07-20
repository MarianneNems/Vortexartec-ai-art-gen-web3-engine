/**
 * VORTEX AI Engine - Tier Interface JavaScript
 * Handles frontend interactions for subscription-based API endpoints
 */

(function($) {
    'use strict';

    // Main tier interface class
    window.VortexTierInterface = class VortexTierInterface {
        constructor(element) {
            this.element = element;
            this.tier = element.dataset.tier;
            this.apiKey = null;
            this.usage = { used: 0, limit: 0 };
            
            this.init();
        }

        init() {
            this.render();
            this.bindEvents();
            this.loadStatus();
        }

        render() {
            // Clear existing content
            this.element.innerHTML = '';
            
            // Create elements safely to prevent XSS
            const tierTitle = this.escapeHtml(this.tier.charAt(0).toUpperCase() + this.tier.slice(1));
            const tierAttribute = this.escapeHtml(this.tier);
            
            // Create header
            const header = document.createElement('div');
            header.className = 'tier-interface-header';
            
            const title = document.createElement('h3');
            title.className = 'tier-title';
            title.textContent = tierTitle + ' Tier';
            
            const usage = document.createElement('div');
            usage.className = 'tier-usage';
            
            const usageBar = document.createElement('div');
            usageBar.className = 'usage-bar';
            
            const usageFill = document.createElement('div');
            usageFill.className = 'usage-fill';
            usageFill.style.width = '0%';
            
            const usageText = document.createElement('span');
            usageText.className = 'usage-text';
            usageText.textContent = '0 / 0 requests';
            
            usageBar.appendChild(usageFill);
            usage.appendChild(usageBar);
            usage.appendChild(usageText);
            header.appendChild(title);
            header.appendChild(usage);
            
            // Create content
            const content = document.createElement('div');
            content.className = 'tier-interface-content';
            
            // Form
            const form = document.createElement('div');
            form.className = 'tier-form';
            
            // Query group
            const queryGroup = document.createElement('div');
            queryGroup.className = 'form-group';
            
            const queryLabel = document.createElement('label');
            queryLabel.setAttribute('for', 'tier-query-' + tierAttribute);
            queryLabel.textContent = 'Prompt:';
            
            const queryTextarea = document.createElement('textarea');
            queryTextarea.id = 'tier-query-' + tierAttribute;
            queryTextarea.className = 'tier-query';
            queryTextarea.placeholder = 'Enter your generation prompt...';
            queryTextarea.rows = 4;
            
            queryGroup.appendChild(queryLabel);
            queryGroup.appendChild(queryTextarea);
            
            // Form row
            const formRow = document.createElement('div');
            formRow.className = 'form-row';
            
            // Style group
            const styleGroup = document.createElement('div');
            styleGroup.className = 'form-group';
            
            const styleLabel = document.createElement('label');
            styleLabel.setAttribute('for', 'tier-style-' + tierAttribute);
            styleLabel.textContent = 'Style:';
            
            const styleSelect = document.createElement('select');
            styleSelect.id = 'tier-style-' + tierAttribute;
            styleSelect.className = 'tier-style';
            
            const styleOptions = [
                { value: '', text: 'Default' },
                { value: 'photorealistic', text: 'Photorealistic' },
                { value: 'artistic', text: 'Artistic' },
                { value: 'abstract', text: 'Abstract' },
                { value: 'minimalist', text: 'Minimalist' }
            ];
            
            styleOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                styleSelect.appendChild(option);
            });
            
            styleGroup.appendChild(styleLabel);
            styleGroup.appendChild(styleSelect);
            
            // Quality group
            const qualityGroup = document.createElement('div');
            qualityGroup.className = 'form-group';
            
            const qualityLabel = document.createElement('label');
            qualityLabel.setAttribute('for', 'tier-quality-' + tierAttribute);
            qualityLabel.textContent = 'Quality:';
            
            const qualitySelect = document.createElement('select');
            qualitySelect.id = 'tier-quality-' + tierAttribute;
            qualitySelect.className = 'tier-quality';
            
            const qualityOptions = [
                { value: '', text: 'Standard' },
                { value: 'hd', text: 'HD' },
                { value: 'ultra', text: 'Ultra' }
            ];
            
            qualityOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                qualitySelect.appendChild(option);
            });
            
            qualityGroup.appendChild(qualityLabel);
            qualityGroup.appendChild(qualitySelect);
            
            formRow.appendChild(styleGroup);
            formRow.appendChild(qualityGroup);
            
            // Form actions
            const formActions = document.createElement('div');
            formActions.className = 'form-actions';
            
            const generateBtn = document.createElement('button');
            generateBtn.type = 'button';
            generateBtn.className = 'tier-generate-btn';
            generateBtn.setAttribute('data-tier', tierAttribute);
            generateBtn.textContent = 'Generate';
            
            const refreshBtn = document.createElement('button');
            refreshBtn.type = 'button';
            refreshBtn.className = 'tier-refresh-btn';
            refreshBtn.setAttribute('data-tier', tierAttribute);
            refreshBtn.textContent = 'Refresh Status';
            
            formActions.appendChild(generateBtn);
            formActions.appendChild(refreshBtn);
            
            form.appendChild(queryGroup);
            form.appendChild(formRow);
            form.appendChild(formActions);
            
            // Results
            const results = document.createElement('div');
            results.className = 'tier-results';
            
            const resultsHeader = document.createElement('div');
            resultsHeader.className = 'results-header';
            
            const resultsTitle = document.createElement('h4');
            resultsTitle.textContent = 'Generated Results';
            
            const resultsContent = document.createElement('div');
            resultsContent.className = 'results-content';
            
            const noResults = document.createElement('p');
            noResults.className = 'no-results';
            noResults.textContent = 'No results yet. Click "Generate" to create your first result.';
            
            resultsHeader.appendChild(resultsTitle);
            resultsContent.appendChild(noResults);
            results.appendChild(resultsHeader);
            results.appendChild(resultsContent);
            
            content.appendChild(form);
            content.appendChild(results);
            
            // Footer
            const footer = document.createElement('div');
            footer.className = 'tier-interface-footer';
            
            const tierInfo = document.createElement('div');
            tierInfo.className = 'tier-info';
            
            const tierNode = document.createElement('span');
            tierNode.className = 'tier-node';
            tierNode.setAttribute('data-tier', tierAttribute);
            tierNode.textContent = 'Loading...';
            
            tierInfo.appendChild(tierNode);
            footer.appendChild(tierInfo);
            
            // Append all to element
            this.element.appendChild(header);
            this.element.appendChild(content);
            this.element.appendChild(footer);
        }
        
        // Helper method to escape HTML
        escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        bindEvents() {
            // Generate button
            $(this.element).on('click', '.tier-generate-btn', (e) => {
                e.preventDefault();
                this.handleGenerate();
            });

            // Refresh button
            $(this.element).on('click', '.tier-refresh-btn', (e) => {
                e.preventDefault();
                this.loadStatus();
            });

            // Auto-refresh usage every 30 seconds
            setInterval(() => {
                this.loadStatus();
            }, 30000);
        }

        async loadStatus() {
            try {
                const response = await fetch(`/wp-json/vortex/v3/tier/${this.tier}/status`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': vortexAjax.nonce
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load status');
                }

                const data = await response.json();
                this.usage = data;
                this.updateUsageDisplay();
                this.updateNodeDisplay(data.node);

            } catch (error) {
                console.error('Error loading tier status:', error);
                this.showError('Failed to load tier status');
            }
        }

        updateUsageDisplay() {
            const percentage = (this.usage.used / this.usage.limit) * 100;
            const usageFill = this.element.querySelector('.usage-fill');
            const usageText = this.element.querySelector('.usage-text');

            if (usageFill) {
                usageFill.style.width = `${Math.min(percentage, 100)}%`;
                
                // Color coding based on usage
                if (percentage >= 90) {
                    usageFill.className = 'usage-fill usage-critical';
                } else if (percentage >= 75) {
                    usageFill.className = 'usage-fill usage-warning';
                } else {
                    usageFill.className = 'usage-fill usage-normal';
                }
            }

            if (usageText) {
                usageText.textContent = `${this.usage.used} / ${this.usage.limit} requests`;
            }
        }

        updateNodeDisplay(node) {
            const nodeElement = this.element.querySelector('.tier-node');
            if (nodeElement) {
                nodeElement.textContent = `Node: ${node}`;
            }
        }

        async handleGenerate() {
            const queryElement = this.element.querySelector('.tier-query');
            const styleElement = this.element.querySelector('.tier-style');
            const qualityElement = this.element.querySelector('.tier-quality');
            const generateBtn = this.element.querySelector('.tier-generate-btn');

            const query = queryElement.value.trim();
            if (!query) {
                this.showError('Please enter a prompt');
                return;
            }

            // Check usage limit
            if (this.usage.used >= this.usage.limit) {
                this.showError('Monthly usage limit reached');
                return;
            }

            // Disable button and show loading
            generateBtn.disabled = true;
            generateBtn.textContent = 'Generating...';
            
            try {
                const response = await fetch(`/wp-json/vortex/v3/tier/${this.tier}/generate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': vortexAjax.nonce
                    },
                    body: JSON.stringify({
                        query: query,
                        style: styleElement.value,
                        quality: qualityElement.value
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Generation failed');
                }

                const data = await response.json();
                this.displayResult(data);
                
                // Clear form
                queryElement.value = '';
                styleElement.value = '';
                qualityElement.value = '';
                
                // Refresh usage
                this.loadStatus();

            } catch (error) {
                console.error('Error generating:', error);
                this.showError(error.message || 'Generation failed');
            } finally {
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate';
            }
        }

        displayResult(data) {
            const resultsContent = this.element.querySelector('.results-content');
            const noResults = resultsContent.querySelector('.no-results');
            
            if (noResults) {
                noResults.remove();
            }

            // Create result element safely
            const resultElement = document.createElement('div');
            resultElement.className = 'result-item';
            
            // Create header
            const resultHeader = document.createElement('div');
            resultHeader.className = 'result-header';
            
            const resultTime = document.createElement('span');
            resultTime.className = 'result-time';
            resultTime.textContent = new Date().toLocaleTimeString();
            
            const resultCost = document.createElement('span');
            resultCost.className = 'result-cost';
            resultCost.textContent = 'Cost: $' + (data.cost || 0).toFixed(4);
            
            resultHeader.appendChild(resultTime);
            resultHeader.appendChild(resultCost);
            
            // Create content
            const resultContent = document.createElement('div');
            resultContent.className = 'result-content';
            
            // Add image if present
            if (data.image_url) {
                const img = document.createElement('img');
                img.src = this.escapeHtml(data.image_url);
                img.alt = 'Generated image';
                img.className = 'result-image';
                resultContent.appendChild(img);
            }
            
            // Add text response if present
            if (data.text_response) {
                const textPara = document.createElement('p');
                textPara.className = 'result-text';
                textPara.textContent = data.text_response;
                resultContent.appendChild(textPara);
            }
            
            resultElement.appendChild(resultHeader);
            resultElement.appendChild(resultContent);
            
            resultsContent.insertBefore(resultElement, resultsContent.firstChild);

            // Limit to 5 results
            const results = resultsContent.querySelectorAll('.result-item');
            if (results.length > 5) {
                results[results.length - 1].remove();
            }
        }

        showError(message) {
            const errorElement = document.createElement('div');
            errorElement.className = 'tier-error';
            errorElement.textContent = message;
            
            this.element.querySelector('.tier-interface-content').appendChild(errorElement);
            
            setTimeout(() => {
                errorElement.remove();
            }, 5000);
        }
    };

    // Initialize all tier interfaces when DOM is ready
    $(document).ready(function() {
        $('.vortex-tier-interface').each(function() {
            new VortexTierInterface(this);
        });
    });

})(jQuery); 