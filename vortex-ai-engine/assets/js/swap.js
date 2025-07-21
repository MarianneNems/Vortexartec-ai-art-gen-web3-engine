/**
 * VORTEX AI Engine - Swap Interface JavaScript
 * 
 * Handles the swap interface functionality for the marketplace
 * 
 * @package VORTEX_AI_Engine
 * @version 2.2.0
 */

(function($) {
    'use strict';
    
    var VORTEXSwap = {
        
        init: function() {
            this.bindEvents();
            this.initializeInterface();
        },
        
        bindEvents: function() {
            $(document).on('click', '.vortex-swap-button', this.handleSwap);
            $(document).on('change', '.vortex-token-select', this.updateSwapRate);
            $(document).on('input', '.vortex-amount-input', this.calculateSwap);
            $(document).on('click', '.vortex-connect-wallet', this.connectWallet);
        },
        
        initializeInterface: function() {
            this.updateSwapRate();
            this.loadSwapHistory();
        },
        
        handleSwap: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('.vortex-swap-form');
            
            if (!VORTEXSwap.validateSwap($form)) {
                return;
            }
            
            $button.prop('disabled', true).text('Processing...');
            
            var swapData = {
                action: 'vortex_ai_action',
                action_type: 'process_swap',
                nonce: vortex_ajax.nonce,
                from_token: $form.find('.vortex-from-token').val(),
                to_token: $form.find('.vortex-to-token').val(),
                amount: $form.find('.vortex-amount-input').val(),
                slippage: $form.find('.vortex-slippage').val()
            };
            
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: swapData,
                success: function(response) {
                    if (response.success) {
                        VORTEXSwap.showSuccess('Swap completed successfully!');
                        VORTEXSwap.loadSwapHistory();
                    } else {
                        VORTEXSwap.showError(response.message || 'Swap failed');
                    }
                },
                error: function() {
                    VORTEXSwap.showError('Network error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Swap');
                }
            });
        },
        
        validateSwap: function($form) {
            var amount = parseFloat($form.find('.vortex-amount-input').val());
            var balance = parseFloat($form.find('.vortex-balance').text());
            
            if (isNaN(amount) || amount <= 0) {
                this.showError('Please enter a valid amount');
                return false;
            }
            
            if (amount > balance) {
                this.showError('Insufficient balance');
                return false;
            }
            
            return true;
        },
        
        updateSwapRate: function() {
            var fromToken = $('.vortex-from-token').val();
            var toToken = $('.vortex-to-token').val();
            
            if (fromToken && toToken && fromToken !== toToken) {
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_ai_action',
                        action_type: 'get_swap_rate',
                        nonce: vortex_ajax.nonce,
                        from_token: fromToken,
                        to_token: toToken
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.vortex-swap-rate').text(response.data.rate);
                            $('.vortex-swap-fee').text(response.data.fee);
                        }
                    }
                });
            }
        },
        
        calculateSwap: function() {
            var amount = parseFloat($(this).val()) || 0;
            var rate = parseFloat($('.vortex-swap-rate').text()) || 0;
            var fee = parseFloat($('.vortex-swap-fee').text()) || 0;
            
            var output = (amount * rate) - fee;
            $('.vortex-output-amount').text(output.toFixed(6));
        },
        
        connectWallet: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).text('Connecting...');
            
            // Simulate wallet connection
            setTimeout(function() {
                $button.prop('disabled', false).text('Connected');
                $('.vortex-wallet-status').text('Connected');
                VORTEXSwap.loadWalletBalance();
            }, 2000);
        },
        
        loadWalletBalance: function() {
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_ai_action',
                    action_type: 'get_wallet_balance',
                    nonce: vortex_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.vortex-balance').text(response.data.balance);
                    }
                }
            });
        },
        
        loadSwapHistory: function() {
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_ai_action',
                    action_type: 'get_swap_history',
                    nonce: vortex_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VORTEXSwap.renderSwapHistory(response.data.history);
                    }
                }
            });
        },
        
        renderSwapHistory: function(history) {
            var $container = $('.vortex-swap-history');
            $container.empty();
            
            if (history.length === 0) {
                $container.append('<p>No swap history found</p>');
                return;
            }
            
            var html = '<table class="vortex-history-table">';
            html += '<thead><tr><th>Date</th><th>From</th><th>To</th><th>Amount</th><th>Status</th></tr></thead>';
            html += '<tbody>';
            
            history.forEach(function(swap) {
                html += '<tr>';
                html += '<td>' + swap.date + '</td>';
                html += '<td>' + swap.from_token + '</td>';
                html += '<td>' + swap.to_token + '</td>';
                html += '<td>' + swap.amount + '</td>';
                html += '<td><span class="vortex-status-' + swap.status + '">' + swap.status + '</span></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $container.html(html);
        },
        
        showSuccess: function(message) {
            $('.vortex-notifications').append(
                '<div class="vortex-notification success">' + message + '</div>'
            );
            
            setTimeout(function() {
                $('.vortex-notification.success').fadeOut();
            }, 5000);
        },
        
        showError: function(message) {
            $('.vortex-notifications').append(
                '<div class="vortex-notification error">' + message + '</div>'
            );
            
            setTimeout(function() {
                $('.vortex-notification.error').fadeOut();
            }, 5000);
        }
    };
    
    $(document).ready(function() {
        VORTEXSwap.init();
    });
    
})(jQuery); 