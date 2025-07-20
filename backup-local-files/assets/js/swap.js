/**
 * VORTEX Swap JavaScript
 * Handles token swap functionality
 */

(function($) {
    'use strict';

    // Global variables
    let swapConfig = {
        isConnected: false,
        currentWallet: null,
        supportedTokens: ['VORTEX', 'ETH', 'USDC', 'TOLA'],
        exchangeRates: {
            'VORTEX': 1,
            'ETH': 0.0001,
            'USDC': 0.5,
            'TOLA': 2
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        initSwapInterface();
    });

    /**
     * Initialize swap interface
     */
    function initSwapInterface() {
        // Bind form submission
        $(document).on('submit', '.vortex-swap-form', handleSwapSubmit);
        
        // Bind wallet connection
        $(document).on('click', '.vortex-connect-wallet', connectWallet);
        
        // Bind wallet disconnection
        $(document).on('click', '.vortex-disconnect-wallet', disconnectWallet);
        
        // Update swap preview
        $(document).on('input', '.vortex-swap-amount', updateSwapPreview);
        
        // Token selection change
        $(document).on('change', '.vortex-from-token, .vortex-to-token', updateSwapPreview);
        
        // Refresh rates
        $(document).on('click', '.vortex-refresh-rates', refreshExchangeRates);
        
        // Initialize existing containers
        $('.vortex-swap-container').each(function() {
            initializeSwapContainer($(this));
        });
    }

    /**
     * Initialize individual swap container
     */
    function initializeSwapContainer(container) {
        // Check if wallet is already connected
        checkWalletConnection(container);
        
        // Load initial exchange rates
        loadExchangeRates(container);
        
        // Set up form validation
        setupFormValidation(container);
    }

    /**
     * Handle swap form submission
     */
    function handleSwapSubmit(e) {
        e.preventDefault();
        
        const form = $(this);
        const container = form.closest('.vortex-swap-container');
        const amount = parseFloat(form.find('.vortex-swap-amount').val());
        const fromToken = form.find('.vortex-from-token').val();
        const toToken = form.find('.vortex-to-token').val();
        
        // Validation
        if (!amount || amount <= 0) {
            showSwapStatus(container, 'Please enter a valid amount', 'error');
            return;
        }
        
        if (!fromToken || !toToken) {
            showSwapStatus(container, 'Please select both tokens', 'error');
            return;
        }
        
        if (fromToken === toToken) {
            showSwapStatus(container, 'Cannot swap same token', 'error');
            return;
        }
        
        if (!swapConfig.isConnected) {
            showSwapStatus(container, 'Please connect your wallet first', 'error');
            return;
        }
        
        // Show loading state
        showSwapStatus(container, 'Processing swap...', 'info');
        form.find('.vortex-swap-button').prop('disabled', true);
        
        // AJAX request to backend
        $.ajax({
            url: typeof vortexSwapConfig !== 'undefined' ? vortexSwapConfig.ajaxUrl : ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_process_swap',
                amount: amount,
                from_token: fromToken,
                to_token: toToken,
                nonce: typeof vortexSwapConfig !== 'undefined' ? vortexSwapConfig.nonce : ''
            },
            success: function(response) {
                if (response.success) {
                    const expectedAmount = calculateExpectedAmount(amount, fromToken, toToken);
                    showSwapStatus(container, `Swap completed! Received ${expectedAmount.toFixed(4)} ${toToken}`, 'success');
                    updateWalletBalance(container);
                    resetForm(form);
                } else {
                    showSwapStatus(container, response.data?.message || 'Swap failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Swap error:', error);
                showSwapStatus(container, 'Network error. Please try again.', 'error');
            },
            complete: function() {
                form.find('.vortex-swap-button').prop('disabled', false);
            }
        });
    }

    /**
     * Connect wallet
     */
    function connectWallet() {
        const button = $(this);
        const container = button.closest('.vortex-swap-container');
        
        button.prop('disabled', true).html('Connecting...');
        showSwapStatus(container, 'Connecting to wallet...', 'info');
        
        // Simulate wallet connection (replace with actual wallet integration)
        setTimeout(() => {
            swapConfig.isConnected = true;
            swapConfig.currentWallet = '0x' + Math.random().toString(16).substr(2, 8);
            
            updateWalletStatus(container);
            showSwapStatus(container, 'Wallet connected successfully!', 'success');
            
            // Load wallet balance
            loadWalletBalance(container);
            
        }, 2000);
    }

    /**
     * Disconnect wallet
     */
    function disconnectWallet() {
        const button = $(this);
        const container = button.closest('.vortex-swap-container');
        
        swapConfig.isConnected = false;
        swapConfig.currentWallet = null;
        
        updateWalletStatus(container);
        showSwapStatus(container, 'Wallet disconnected', 'info');
    }

    /**
     * Update swap preview
     */
    function updateSwapPreview() {
        const input = $(this);
        const container = input.closest('.vortex-swap-container');
        const form = input.closest('.vortex-swap-form');
        
        const amount = parseFloat(input.val());
        const fromToken = form.find('.vortex-from-token').val();
        const toToken = form.find('.vortex-to-token').val();
        
        if (amount && fromToken && toToken && fromToken !== toToken) {
            const expectedAmount = calculateExpectedAmount(amount, fromToken, toToken);
            const preview = container.find('.vortex-swap-preview');
            
            preview.html(`
                <strong>Preview:</strong><br>
                ${amount} ${fromToken} → ${expectedAmount.toFixed(4)} ${toToken}<br>
                <small>Rate: 1 ${fromToken} = ${(swapConfig.exchangeRates[toToken] / swapConfig.exchangeRates[fromToken]).toFixed(6)} ${toToken}</small>
            `).show();
        } else {
            container.find('.vortex-swap-preview').hide();
        }
    }

    /**
     * Calculate expected amount
     */
    function calculateExpectedAmount(amount, fromToken, toToken) {
        const fromRate = swapConfig.exchangeRates[fromToken] || 1;
        const toRate = swapConfig.exchangeRates[toToken] || 1;
        const fee = 0.003; // 0.3% fee
        
        return (amount * fromRate / toRate) * (1 - fee);
    }

    /**
     * Check wallet connection
     */
    function checkWalletConnection(container) {
        // Check if wallet was previously connected
        const savedWallet = localStorage.getItem('vortex_wallet_address');
        if (savedWallet) {
            swapConfig.isConnected = true;
            swapConfig.currentWallet = savedWallet;
            updateWalletStatus(container);
            loadWalletBalance(container);
        }
    }

    /**
     * Update wallet status display
     */
    function updateWalletStatus(container) {
        const statusDiv = container.find('.vortex-wallet-status');
        const connectBtn = container.find('.vortex-connect-wallet');
        const disconnectBtn = container.find('.vortex-disconnect-wallet');
        
        if (swapConfig.isConnected) {
            statusDiv.removeClass('vortex-wallet-disconnected').addClass('vortex-wallet-connected');
            statusDiv.html(`Connected: ${swapConfig.currentWallet.substring(0, 8)}...`);
            connectBtn.hide();
            disconnectBtn.show();
            
            // Save to localStorage
            localStorage.setItem('vortex_wallet_address', swapConfig.currentWallet);
        } else {
            statusDiv.removeClass('vortex-wallet-connected').addClass('vortex-wallet-disconnected');
            statusDiv.html('Wallet not connected');
            connectBtn.show();
            disconnectBtn.hide();
            
            // Remove from localStorage
            localStorage.removeItem('vortex_wallet_address');
        }
    }

    /**
     * Load wallet balance
     */
    function loadWalletBalance(container) {
        if (!swapConfig.isConnected) return;
        
        $.ajax({
            url: typeof vortexSwapConfig !== 'undefined' ? vortexSwapConfig.ajaxUrl : ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_wallet_balance',
                wallet_address: swapConfig.currentWallet,
                nonce: typeof vortexSwapConfig !== 'undefined' ? vortexSwapConfig.nonce : ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateBalanceDisplay(container, response.data);
                }
            },
            error: function(error) {
                console.error('Error loading wallet balance:', error);
            }
        });
    }

    /**
     * Update balance display
     */
    function updateBalanceDisplay(container, balances) {
        // Update balance information if elements exist
        const balanceDiv = container.find('.vortex-wallet-balance');
        if (balanceDiv.length && balances.VORTEX) {
            balanceDiv.html(`Balance: ${balances.VORTEX} VORTEX`);
        }
    }

    /**
     * Load exchange rates
     */
    function loadExchangeRates(container) {
        $.ajax({
            url: typeof vortexSwapConfig !== 'undefined' ? vortexSwapConfig.ajaxUrl : ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_exchange_rates',
                nonce: typeof vortexSwapConfig !== 'undefined' ? vortexSwapConfig.nonce : ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    swapConfig.exchangeRates = response.data;
                    updateSwapPreview.call(container.find('.vortex-swap-amount'));
                }
            },
            error: function(error) {
                console.error('Error loading exchange rates:', error);
            }
        });
    }

    /**
     * Refresh exchange rates
     */
    function refreshExchangeRates() {
        const button = $(this);
        const container = button.closest('.vortex-swap-container');
        
        button.prop('disabled', true).html('Refreshing...');
        
        setTimeout(() => {
            loadExchangeRates(container);
            button.prop('disabled', false).html('Refresh Rates');
            showSwapStatus(container, 'Exchange rates updated', 'success');
        }, 1000);
    }

    /**
     * Setup form validation
     */
    function setupFormValidation(container) {
        const form = container.find('.vortex-swap-form');
        const amountInput = form.find('.vortex-swap-amount');
        
        amountInput.on('input', function() {
            const value = parseFloat($(this).val());
            const submitBtn = form.find('.vortex-swap-button');
            
            if (value <= 0 || isNaN(value)) {
                submitBtn.prop('disabled', true);
            } else {
                submitBtn.prop('disabled', false);
            }
        });
    }

    /**
     * Show swap status message
     */
    function showSwapStatus(container, message, type) {
        const statusDiv = container.find('.vortex-swap-status');
        statusDiv.removeClass('vortex-swap-success vortex-swap-error vortex-swap-info');
        statusDiv.addClass(`vortex-swap-${type}`);
        statusDiv.html(message).show();
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                statusDiv.fadeOut();
            }, 5000);
        }
    }

    /**
     * Reset form
     */
    function resetForm(form) {
        form.find('.vortex-swap-amount').val('');
        form.find('.vortex-swap-preview').hide();
    }

    /**
     * Update wallet balance after swap
     */
    function updateWalletBalance(container) {
        if (swapConfig.isConnected) {
            loadWalletBalance(container);
        }
    }

    // Export functions for external use
    window.VortexSwap = {
        connect: connectWallet,
        disconnect: disconnectWallet,
        getBalance: loadWalletBalance,
        refreshRates: refreshExchangeRates
    };

})(jQuery); 