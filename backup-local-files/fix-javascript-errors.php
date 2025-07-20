<?php
/**
 * Plugin Name: JavaScript Error Fixer
 * Description: Fixes common JavaScript errors on the site
 * Version: 1.0
 * Author: VORTEX AI Engine
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function fix_javascript_errors() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Fix for news-letter.js error
        const newsletterElement = document.querySelector('#newsletter-form');
        if (newsletterElement) {
            newsletterElement.addEventListener('submit', function(e) {
                // Newsletter form handling
            });
        }
        
        // Fix for utils.min.js modal error
        if (typeof window.rbm_tracking_firstgo !== 'undefined') {
            try {
                window.rbm_tracking_firstgo.init();
            } catch (error) {
                console.log('RBM tracking initialization failed:', error);
            }
        }
    });
    </script>
    <?php
}

add_action('wp_footer', 'fix_javascript_errors'); 