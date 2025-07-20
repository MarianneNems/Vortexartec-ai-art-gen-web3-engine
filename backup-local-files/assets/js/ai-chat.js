/**
 * VORTEX AI Engine - AI Chat JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeAIChat();
    });

    function initializeAIChat() {
        // Handle form submission
        $('.vortex-ai-chat-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $input = $form.find('.vortex-ai-chat-input');
            const $submit = $form.find('.vortex-ai-chat-submit');
            const $messages = $('.vortex-ai-chat-messages');
            const message = $input.val().trim();
            
            if (!message) return;
            
            // Disable form
            $input.prop('disabled', true);
            $submit.prop('disabled', true);
            $submit.find('.submit-text').hide();
            $submit.find('.submit-loading').show();
            
            // Add user message
            addMessage('user', message);
            $input.val('');
            
            // Send to AI
            $.ajax({
                url: vortexAIConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_ai_chat',
                    message: message,
                    nonce: vortexAIConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        addMessage('assistant', response.data.message, response.data.agent);
                        updateCost(response.data.cost);
                    } else {
                        addMessage('assistant', 'Sorry, I encountered an error. Please try again.', 'System');
                    }
                },
                error: function() {
                    addMessage('assistant', 'Connection error. Please check your internet and try again.', 'System');
                },
                complete: function() {
                    // Re-enable form
                    $input.prop('disabled', false);
                    $submit.prop('disabled', false);
                    $submit.find('.submit-text').show();
                    $submit.find('.submit-loading').hide();
                    $input.focus();
                }
            });
        });
        
        // Agent status click handlers
        $('.agent-status').on('click', function() {
            const agent = $(this).attr('title');
            showAgentInfo(agent);
        });
    }
    
    function addMessage(type, content, agent) {
        const $messages = $('.vortex-ai-chat-messages');
        const messageHtml = `
            <div class="ai-message ${type}">
                ${agent ? `<div class="message-agent">${agent}</div>` : ''}
                <div class="message-content">${escapeHtml(content)}</div>
            </div>
        `;
        
        $messages.append(messageHtml);
        $messages.scrollTop($messages[0].scrollHeight);
    }
    
    function updateCost(cost) {
        const $cost = $('#session-cost');
        const currentCost = parseFloat($cost.text()) || 0;
        const newCost = currentCost + (parseFloat(cost) || 0);
        $cost.text(newCost.toFixed(2));
    }
    
    function showAgentInfo(agent) {
        const agentInfo = {
            'HURAII - Artistic Creation': 'HURAII specializes in creative image generation and artistic concepts.',
            'CLOE - Analysis & Optimization': 'CLOE analyzes and optimizes your creations for best results.',
            'HORACE - Data Synthesis': 'HORACE synthesizes data and provides insights from your creative history.'
        };
        
        if (agentInfo[agent]) {
            alert(agentInfo[agent]);
        }
    }
    
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

})(jQuery); 