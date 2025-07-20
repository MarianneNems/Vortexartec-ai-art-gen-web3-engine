// Fix for utils.min.js modal error
// Add this to your theme's functions.php or create a custom plugin

jQuery(document).ready(function($) {
    // Fix for rbm_tracking_firstgo modal error
    if (typeof window.rbm_tracking_firstgo !== 'undefined') {
        try {
            // Initialize tracking properly
            window.rbm_tracking_firstgo.init();
        } catch (error) {
            console.log('RBM tracking initialization failed:', error);
        }
    }
    
    // Alternative: Disable the problematic modal if it's causing issues
    if (typeof window.disableRbmModal === 'undefined') {
        window.disableRbmModal = function() {
            // Disable problematic modal functionality
            console.log('RBM modal disabled to prevent errors');
        };
    }
}); 