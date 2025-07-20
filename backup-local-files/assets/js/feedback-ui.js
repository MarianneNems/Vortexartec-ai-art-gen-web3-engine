jQuery(document).ready(function($) {
    $(document).on('click', '.vortex-feedback-btn', function() {
        const liked = $(this).data('like');
        const requestId = $(this).closest('.vortex-response').data('request-id');
        const currentAction = vortex_globals.current_action;
        
        $.ajax({
            url: vortex_globals.rest_url + 'vortex/v1/feedback',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: currentAction,
                request_id: requestId,
                liked: liked
            }),
            success: function(response) {
                console.log('Feedback submitted successfully');
                // Optional: Show toast message
            },
            error: function(error) {
                console.error('Feedback submission failed', error);
            }
        });
    });
}); 