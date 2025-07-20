// Fix for news-letter.js error
// Add this to your theme's functions.php or create a custom plugin

jQuery(document).ready(function($) {
    // Fix for news-letter.js error - check if element exists before adding event listener
    const newsletterElement = document.querySelector('#newsletter-form'); // Replace with actual selector
    
    if (newsletterElement) {
        newsletterElement.addEventListener('submit', function(e) {
            // Newsletter form handling
        });
    }
    
    // Alternative fix - use jQuery which handles null elements gracefully
    $('#newsletter-form').on('submit', function(e) {
        // Newsletter form handling
    });
}); 