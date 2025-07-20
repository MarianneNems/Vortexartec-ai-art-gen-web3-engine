/**
 * TOLA NFT JavaScript Functions
 * 
 * Handles interactions for TOLA NFT shortcodes
 */

// Global variables
let tolaWalletConnected = false;
let tolaCurrentWallet = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if wallet is already connected
    checkWalletConnection();
    
    // Initialize shortcode interactions
    initializeTolaShortcodes();
});

/**
 * Check if wallet is already connected
 */
function checkWalletConnection() {
    const walletElement = document.querySelector('.tola-wallet-connected');
    if (walletElement) {
        tolaWalletConnected = true;
        const addressElement = walletElement.querySelector('.tola-wallet-address');
        if (addressElement) {
            tolaCurrentWallet = addressElement.textContent.trim();
        }
    }
}

/**
 * Initialize TOLA shortcode interactions
 */
function initializeTolaShortcodes() {
    // Auto-refresh mint status for pending NFTs
    const pendingMints = document.querySelectorAll('.tola-mint-status .tola-status-badge.pending');
    pendingMints.forEach(badge => {
        const container = badge.closest('.tola-mint-status');
        if (container) {
            const artworkId = container.getAttribute('data-artwork-id');
            if (artworkId) {
                // Check status every 10 seconds
                setInterval(() => tolaRefreshStatus(artworkId), 10000);
            }
        }
    });
    
    // Initialize royalty sliders
    const royaltySliders = document.querySelectorAll('input[name="royalty_fee"]');
    royaltySliders.forEach(slider => {
        slider.addEventListener('input', function() {
            tolaUpdateRoyaltyDisplay(this.value);
        });
    });
}

/**
 * Refresh NFT mint status
 */
function tolaRefreshStatus(artworkId) {
    const statusContainer = document.querySelector(`[data-artwork-id="${artworkId}"]`);
    if (!statusContainer) return;
    
    // Show loading state
    const statusBadge = statusContainer.querySelector('.tola-status-badge');
    if (statusBadge) {
        statusBadge.textContent = 'Checking...';
        statusBadge.className = 'tola-status-badge loading';
    }
    
    // Make AJAX request
    fetch(tola_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'tola_refresh_status',
            artwork_id: artworkId,
            nonce: tola_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatusDisplay(statusContainer, data.data);
        } else {
            showTolaError('Failed to refresh status: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error refreshing status:', error);
        showTolaError('Network error while refreshing status');
    });
}

/**
 * Update status display
 */
function updateStatusDisplay(container, data) {
    const statusBadge = container.querySelector('.tola-status-badge');
    if (statusBadge) {
        statusBadge.textContent = data.minted ? 'Minted' : 'Processing';
        statusBadge.className = 'tola-status-badge ' + (data.minted ? 'success' : 'pending');
    }
    
    // Update transaction link if available
    if (data.signature) {
        const transactionLink = container.querySelector('.tola-detail-item a');
        if (transactionLink) {
            transactionLink.href = data.explorer_url;
            transactionLink.textContent = data.signature.substring(0, 20) + '...';
        }
    }
    
    // Show marketplace link if minted
    if (data.minted && data.marketplace_url) {
        const actionsContainer = container.querySelector('.tola-mint-actions');
        if (actionsContainer) {
            let marketplaceLink = actionsContainer.querySelector('.tola-btn-primary');
            if (!marketplaceLink) {
                marketplaceLink = document.createElement('a');
                marketplaceLink.className = 'tola-btn tola-btn-primary';
                marketplaceLink.target = '_blank';
                marketplaceLink.textContent = 'View on Marketplace';
                actionsContainer.insertBefore(marketplaceLink, actionsContainer.firstChild);
            }
            marketplaceLink.href = data.marketplace_url;
        }
    }
}

/**
 * Update royalty display
 */
function tolaUpdateRoyaltyDisplay(value) {
    const display = document.getElementById('royalty_display');
    if (display) {
        display.textContent = value + '%';
    }
}

/**
 * Update royalty settings
 */
function tolaUpdateRoyalty(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const container = form.closest('.tola-royalty-manager');
    const artworkId = container.getAttribute('data-artwork-id');
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Updating...';
    submitBtn.disabled = true;
    
    // Make AJAX request
    fetch(tola_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'tola_update_royalty',
            artwork_id: artworkId,
            royalty_fee: formData.get('royalty_fee'),
            royalty_recipient: formData.get('royalty_recipient'),
            nonce: tola_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTolaSuccess('Royalty updated successfully! Transaction: ' + data.data.signature);
            
            // Update current royalty display
            const currentRoyalty = container.querySelector('.tola-royalty-info strong');
            if (currentRoyalty) {
                currentRoyalty.textContent = formData.get('royalty_fee') + '%';
            }
        } else {
            showTolaError('Failed to update royalty: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error updating royalty:', error);
        showTolaError('Network error while updating royalty');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Reset royalty to default
 */
function tolaResetRoyalty() {
    const slider = document.getElementById('royalty_fee');
    if (slider) {
        slider.value = '5';
        tolaUpdateRoyaltyDisplay('5');
    }
}

/**
 * Connect Phantom wallet
 */
function tolaConnectPhantom() {
    if (typeof window.solana !== 'undefined' && window.solana.isPhantom) {
        window.solana.connect()
            .then(response => {
                tolaHandleWalletConnection(response.publicKey.toString(), 'phantom');
            })
            .catch(error => {
                console.error('Phantom connection error:', error);
                showTolaError('Failed to connect Phantom wallet');
            });
    } else {
        showTolaError('Phantom wallet not found. Please install Phantom extension.');
    }
}

/**
 * Connect Solflare wallet
 */
function tolaConnectSolflare() {
    if (typeof window.solflare !== 'undefined') {
        window.solflare.connect()
            .then(response => {
                tolaHandleWalletConnection(response.publicKey.toString(), 'solflare');
            })
            .catch(error => {
                console.error('Solflare connection error:', error);
                showTolaError('Failed to connect Solflare wallet');
            });
    } else {
        showTolaError('Solflare wallet not found. Please install Solflare extension.');
    }
}

/**
 * Handle manual wallet connection
 */
function tolaManualConnect(event) {
    event.preventDefault();
    
    const walletInput = document.getElementById('manual_wallet');
    const walletAddress = walletInput.value.trim();
    
    if (!walletAddress) {
        showTolaError('Please enter a wallet address');
        return;
    }
    
    // Basic validation
    if (!/^[1-9A-HJ-NP-Za-km-z]{32,44}$/.test(walletAddress)) {
        showTolaError('Invalid Solana wallet address format');
        return;
    }
    
    tolaHandleWalletConnection(walletAddress, 'manual');
}

/**
 * Handle wallet connection
 */
function tolaHandleWalletConnection(walletAddress, walletType) {
    // Make AJAX request to save wallet
    fetch(tola_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'tola_connect_wallet',
            wallet_address: walletAddress,
            wallet_type: walletType,
            nonce: tola_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTolaSuccess('Wallet connected successfully!');
            
            // Update wallet connection status
            tolaWalletConnected = true;
            tolaCurrentWallet = walletAddress;
            
            // Refresh the page to update wallet display
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showTolaError('Failed to connect wallet: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error connecting wallet:', error);
        showTolaError('Network error while connecting wallet');
    });
}

/**
 * Disconnect wallet
 */
function tolaDisconnectWallet() {
    if (confirm('Are you sure you want to disconnect your wallet?')) {
        fetch(tola_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tola_disconnect_wallet',
                nonce: tola_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTolaSuccess('Wallet disconnected successfully!');
                
                // Update wallet connection status
                tolaWalletConnected = false;
                tolaCurrentWallet = null;
                
                // Refresh the page to update wallet display
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showTolaError('Failed to disconnect wallet: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Error disconnecting wallet:', error);
            showTolaError('Network error while disconnecting wallet');
        });
    }
}

/**
 * View wallet NFTs
 */
function tolaViewWalletNFTs() {
    // Redirect to NFT gallery page or show modal
    const galleryUrl = tola_ajax.gallery_url || '/nft-gallery/';
    window.location.href = galleryUrl;
}

/**
 * Manage NFT royalty
 */
function tolaManageRoyalty(artworkId) {
    // Redirect to royalty management page or show modal
    const royaltyUrl = tola_ajax.royalty_url || '/nft-royalty/';
    window.location.href = royaltyUrl + '?artwork_id=' + artworkId;
}

/**
 * Show success message
 */
function showTolaSuccess(message) {
    showTolaMessage(message, 'success');
}

/**
 * Show error message
 */
function showTolaError(message) {
    showTolaMessage(message, 'error');
}

/**
 * Show message
 */
function showTolaMessage(message, type = 'info') {
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `tola-message tola-message-${type}`;
    messageDiv.textContent = message;
    
    // Add close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'tola-message-close';
    closeBtn.textContent = '×';
    closeBtn.onclick = () => messageDiv.remove();
    messageDiv.appendChild(closeBtn);
    
    // Add to page
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

/**
 * Copy to clipboard
 */
function tolaCopyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showTolaSuccess('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        showTolaError('Failed to copy to clipboard');
    });
}

/**
 * Format Solana address for display
 */
function tolaFormatAddress(address, length = 8) {
    if (!address || address.length < length * 2) {
        return address;
    }
    
    return address.substring(0, length) + '...' + address.substring(address.length - length);
}

/**
 * Validate Solana address
 */
function tolaValidateAddress(address) {
    return /^[1-9A-HJ-NP-Za-km-z]{32,44}$/.test(address);
}

/**
 * Get network status
 */
function tolaGetNetworkStatus() {
    return {
        connected: tolaWalletConnected,
        wallet: tolaCurrentWallet,
        network: 'TOLA'
    };
} 