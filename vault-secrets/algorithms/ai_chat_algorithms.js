/**
 * VORTEX AI Chat Interface JavaScript
 * Handles AI agent interactions, chat interface, and API calls
 */

(function($) {
    'use strict';

    // Global variables
    let sessionCost = 0;
    let isProcessing = false;
    let chatHistory = [];

    // Initialize when document is ready
    $(document).ready(function() {
        initializeChat();
        setupEventListeners();
        displayWelcomeMessage();
    });

    /**
     * Initialize chat interface
     */
    function initializeChat() {
        // Set up agent status indicators
        $('.agent-status').each(function() {
            $(this).addClass('active');
        });

        // Auto-focus input
        $('#vortex-ai-query').focus();

        // Update cost display
        updateCostDisplay();
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Form submission
        $('#vortex-ai-chat-form').on('submit', function(e) {
            e.preventDefault();
            handleUserMessage();
        });

        // Enter key support
        $('#vortex-ai-query').on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                handleUserMessage();
            }
        });

        // Agent status clicks
        $('.agent-status').on('click', function() {
            const agentId = $(this).hasClass('huraii') ? 'huraii' : 
                          $(this).hasClass('cloe') ? 'cloe' : 'horace';
            showAgentInfo(agentId);
        });

        // Auto-resize text area
        $('#vortex-ai-query').on('input', function() {
            autoResizeTextarea(this);
        });
    }

    /**
     * Display welcome message
     */
    function displayWelcomeMessage() {
        const welcomeMessage = {
            type: 'system',
            content: 'Hello! I\'m your VORTEX AI assistant. I work with three specialized agents to provide you with comprehensive responses. Ask me anything!',
            timestamp: new Date()
        };
        
        // The welcome message is already in the HTML, so we don't need to add it again
        chatHistory.push(welcomeMessage);
    }

    /**
     * Handle user message submission
     */
    function handleUserMessage() {
        if (isProcessing) return;

        const query = $('#vortex-ai-query').val().trim();
        if (!query) return;

        // Add user message to chat
        addMessage({
            type: 'user',
            content: query,
            timestamp: new Date()
        });

        // Clear input
        $('#vortex-ai-query').val('');

        // Show processing state
        setProcessingState(true);

        // Send to AI orchestrator
        sendToAIOrchestrator(query);
    }

    /**
     * Add message to chat interface
     */
    function addMessage(message) {
        const messagesContainer = $('#vortex-ai-messages');
        const messageHTML = createMessageHTML(message);
        
        messagesContainer.append(messageHTML);
        scrollToBottom();
        
        // Add to history
        chatHistory.push(message);
    }

    /**
     * Create HTML for a message
     */
    function createMessageHTML(message) {
        const isUser = message.type === 'user';
        const messageClass = isUser ? 'user-message' : 'ai-message';
        const avatar = isUser ? '👤' : '🤖';
        
        return `
            <div class="ai-message ${messageClass}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    ${formatMessageContent(message.content)}
                    ${message.agents ? createAgentBadges(message.agents) : ''}
                    ${message.cost ? `<small class="message-cost">Cost: $${message.cost.toFixed(3)}</small>` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Format message content (support for markdown-like formatting)
     */
    function formatMessageContent(content) {
        // Convert basic markdown to HTML
        let formatted = content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');

        // Convert lists
        formatted = formatted.replace(/^- (.*$)/gm, '<li>$1</li>');
        formatted = formatted.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');

        return `<p>${formatted}</p>`;
    }

    /**
     * Create agent badges
     */
    function createAgentBadges(agents) {
        if (!agents || agents.length === 0) return '';
        
        const badges = agents.map(agent => {
            const agentNames = {
                'huraii': 'HURAII',
                'cloe': 'CLOE',
                'horace': 'HORACE'
            };
            return `<span class="agent-badge ${agent}">${agentNames[agent] || agent}</span>`;
        }).join('');
        
        return `<div class="agents-used">${badges}</div>`;
    }

    /**
     * Send query to AI orchestrator
     */
    function sendToAIOrchestrator(query) {
        const data = {
            action: 'vortex_ai_query',
            query: query,
            nonce: vortexAIConfig.nonce,
            context: getContextData()
        };

        $.ajax({
            url: vortexAIConfig.ajaxUrl,
            type: 'POST',
            data: data,
            timeout: 60000, // 60 seconds timeout
            success: function(response) {
                handleAIResponse(response);
            },
            error: function(xhr, status, error) {
                handleAIError(error, status);
            },
            complete: function() {
                setProcessingState(false);
            }
        });
    }

    /**
     * Handle AI response
     */
    function handleAIResponse(response) {
        if (response.success) {
            const aiMessage = {
                type: 'ai',
                content: response.data.response || 'I received your message but had trouble generating a response.',
                agents: response.data.agents_used || [],
                cost: response.data.cost || 0,
                timestamp: new Date()
            };

            addMessage(aiMessage);
            
            // Update session cost
            if (response.data.cost) {
                sessionCost += response.data.cost;
                updateCostDisplay();
            }

            // Update agent status if provided
            if (response.data.agent_status) {
                updateAgentStatus(response.data.agent_status);
            }
        } else {
            // Handle error response
            const errorMessage = {
                type: 'system',
                content: response.data?.message || 'Sorry, I encountered an error processing your request. Please try again.',
                timestamp: new Date()
            };
            addMessage(errorMessage);
        }
    }

    /**
     * Handle AI error
     */
    function handleAIError(error, status) {
        let errorMessage = 'Sorry, I\'m having trouble connecting to the AI services. ';
        
        if (status === 'timeout') {
            errorMessage += 'The request timed out. Please try again.';
        } else if (status === 'error') {
            errorMessage += 'There was a network error. Please check your connection.';
        } else {
            errorMessage += 'Please try again in a moment.';
        }

        const systemMessage = {
            type: 'system',
            content: errorMessage,
            timestamp: new Date()
        };
        
        addMessage(systemMessage);
    }

    /**
     * Set processing state
     */
    function setProcessingState(processing) {
        isProcessing = processing;
        
        const submitButton = $('#vortex-ai-submit');
        const queryInput = $('#vortex-ai-query');
        
        if (processing) {
            submitButton.addClass('loading').prop('disabled', true);
            queryInput.prop('disabled', true);
            
            // Add processing indicator
            addProcessingIndicator();
        } else {
            submitButton.removeClass('loading').prop('disabled', false);
            queryInput.prop('disabled', false);
            
            // Remove processing indicator
            removeProcessingIndicator();
        }
    }

    /**
     * Add processing indicator
     */
    function addProcessingIndicator() {
        const processingHTML = `
            <div class="ai-message processing-message">
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="processing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <small>AI agents are processing your request...</small>
                </div>
            </div>
        `;
        
        $('#vortex-ai-messages').append(processingHTML);
        scrollToBottom();
    }

    /**
     * Remove processing indicator
     */
    function removeProcessingIndicator() {
        $('.processing-message').remove();
    }

    /**
     * Update cost display
     */
    function updateCostDisplay() {
        $('#session-cost').text(sessionCost.toFixed(3));
    }

    /**
     * Update agent status
     */
    function updateAgentStatus(statusData) {
        Object.keys(statusData).forEach(agentId => {
            const $agent = $(`.agent-status.${agentId}`);
            if (statusData[agentId].active) {
                $agent.addClass('active');
            } else {
                $agent.removeClass('active');
            }
        });
    }

    /**
     * Show agent information
     */
    function showAgentInfo(agentId) {
        const agentInfo = {
            huraii: {
                name: 'HURAII',
                description: 'Artistic Creation Agent',
                specialization: 'Creative content generation, artistic innovation, and design concepts.',
                cost: '$0.01 per call'
            },
            cloe: {
                name: 'CLOE',
                description: 'Analysis & Optimization Agent',
                specialization: 'Data analysis, performance optimization, and strategic insights.',
                cost: '$0.008 per call'
            },
            horace: {
                name: 'HORACE',
                description: 'Data Synthesis Agent',
                specialization: 'Information synthesis, pattern recognition, and comprehensive analysis.',
                cost: '$0.012 per call'
            }
        };

        const agent = agentInfo[agentId];
        if (agent) {
            const infoMessage = {
                type: 'system',
                content: `**${agent.name}** - ${agent.description}\n\n*Specialization:* ${agent.specialization}\n*Cost:* ${agent.cost}`,
                timestamp: new Date()
            };
            addMessage(infoMessage);
        }
    }

    /**
     * Get context data for AI request
     */
    function getContextData() {
        return {
            page_url: window.location.href,
            page_title: document.title,
            user_agent: navigator.userAgent,
            timestamp: new Date().toISOString(),
            chat_history: chatHistory.slice(-5) // Last 5 messages for context
        };
    }

    /**
     * Auto-resize textarea
     */
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    /**
     * Scroll to bottom of messages
     */
    function scrollToBottom() {
        const messagesContainer = $('#vortex-ai-messages');
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    /**
     * Export chat history
     */
    function exportChatHistory() {
        const exportData = {
            session_cost: sessionCost,
            messages: chatHistory,
            timestamp: new Date().toISOString()
        };
        
        const dataStr = JSON.stringify(exportData, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        const exportFileDefaultName = `vortex-ai-chat-${new Date().toISOString().split('T')[0]}.json`;
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();
    }

    /**
     * Clear chat history
     */
    function clearChatHistory() {
        if (confirm('Are you sure you want to clear the chat history?')) {
            chatHistory = [];
            $('#vortex-ai-messages').empty();
            sessionCost = 0;
            updateCostDisplay();
            displayWelcomeMessage();
        }
    }

    // Add processing dots animation CSS
    const processingCSS = `
        <style>
        .processing-dots {
            display: inline-flex;
            gap: 4px;
            margin: 5px 0;
        }
        .processing-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #667eea;
            animation: processingDots 1.4s infinite ease-in-out both;
        }
        .processing-dots span:nth-child(1) { animation-delay: -0.32s; }
        .processing-dots span:nth-child(2) { animation-delay: -0.16s; }
        .processing-dots span:nth-child(3) { animation-delay: 0s; }
        @keyframes processingDots {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        .agent-badge {
            display: inline-block;
            padding: 2px 6px;
            margin: 2px 2px 0 0;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }
        .agent-badge.huraii { background: #ff6b6b; }
        .agent-badge.cloe { background: #4ecdc4; }
        .agent-badge.horace { background: #45b7d1; }
        .agents-used {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .message-cost {
            display: block;
            margin-top: 8px;
            color: #666;
            font-size: 11px;
        }
        </style>
    `;
    
    $('head').append(processingCSS);

    // Expose functions globally if needed
    window.VortexAIChat = {
        exportChatHistory: exportChatHistory,
        clearChatHistory: clearChatHistory,
        addMessage: addMessage,
        sessionCost: function() { return sessionCost; }
    };

})(jQuery); 