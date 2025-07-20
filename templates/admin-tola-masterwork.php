<?php
/**
 * Admin Template: TOLA Masterwork
 * Full GPU access interface for admin users
 *
 * @package VortexAIEngine
 * @version 3.0.0 Enhanced
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check admin permissions
if (!current_user_can('administrator')) {
    wp_die('Access denied');
}

$user_id = get_current_user_id();
// Use WordPress database instead of non-existent VortexAIEngine_EnhancedDatabase
global $wpdb;

// Check admin daily limit using WordPress database
$today_limit_check = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_admin_usage 
     WHERE user_id = %d AND DATE(created_at) = %s",
    $user_id, date('Y-m-d')
));
$can_use_today = $today_limit_check < 3; // Limit to 3 uses per day

// Get TOLA masterpiece participants
$participants = $wpdb->get_results(
    "SELECT DISTINCT user_id FROM {$wpdb->prefix}vortex_admin_usage 
     WHERE action = 'tola_masterpiece' 
     ORDER BY created_at DESC"
);
$total_participants = count($participants);

// Get marketplace artworks
global $wpdb;
$marketplace_artworks = $wpdb->get_results(
    "SELECT p.ID, p.post_title, p.post_author, ms.quality_score, ms.usage_count, ms.tola_eligible
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->prefix}vortex_marketplace_sources ms ON p.ID = ms.post_id
     WHERE p.post_type = 'marketplace_artwork' AND p.post_status = 'publish'
     ORDER BY ms.quality_score DESC, ms.usage_count ASC
     LIMIT 50"
);

// Get today's admin usage
$today_usage = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}vortex_admin_usage 
     WHERE user_id = %d AND DATE(created_at) = %s 
     ORDER BY created_at DESC",
    $user_id, date('Y-m-d')
));

?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-art"></span>
        TOLA Masterwork - Admin GPU Access
    </h1>
    
    <div class="tola-masterwork-dashboard">
        <!-- Status Overview -->
        <div class="tola-status-grid">
            <div class="status-card <?php echo $can_use_today ? 'available' : 'used'; ?>">
                <h3>Daily Usage Status</h3>
                <div class="status-indicator">
                    <?php if ($can_use_today): ?>
                        <span class="status-available">✓ Available</span>
                        <p>You can use TOLA Masterwork today</p>
                    <?php else: ?>
                        <span class="status-used">✗ Used</span>
                        <p>Daily limit reached. Reset at midnight UTC.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="status-card">
                <h3>GPU Configuration</h3>
                <div class="gpu-specs">
                    <strong>16x H200 GPUs</strong>
                    <p>131,072 CPU units</p>
                    <p>262,144 MB memory</p>
                    <p>Ultra priority access</p>
                </div>
            </div>
            
            <div class="status-card">
                <h3>TOLA Participants</h3>
                <div class="participants-count">
                    <strong><?php echo $total_participants; ?> Active</strong>
                    <p>Users contributing to masterpiece</p>
                </div>
            </div>
        </div>
        
        <!-- Main Interface -->
        <div class="tola-main-interface">
            <div class="interface-left">
                <h2>Generate TOLA Masterwork</h2>
                
                <?php if ($can_use_today): ?>
                    <form id="tola-masterwork-form" class="tola-generation-form">
                        <div class="form-group">
                            <label for="marketplace-source">Marketplace Artwork Source *</label>
                            <select id="marketplace-source" name="marketplace_source" required>
                                <option value="">Select artwork source...</option>
                                <?php foreach ($marketplace_artworks as $artwork): ?>
                                    <option value="<?php echo $artwork->ID; ?>" 
                                            data-quality="<?php echo $artwork->quality_score; ?>"
                                            data-usage="<?php echo $artwork->usage_count; ?>"
                                            data-eligible="<?php echo $artwork->tola_eligible; ?>">
                                        <?php echo esc_html($artwork->post_title); ?>
                                        (Quality: <?php echo number_format($artwork->quality_score, 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="generation-query">Generation Query *</label>
                            <textarea id="generation-query" name="query" required 
                                      placeholder="Describe the masterwork you want to create using participant artworks..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="art-style">Art Style</label>
                            <select id="art-style" name="style">
                                <option value="">Auto-detect from source</option>
                                <option value="realistic">Realistic</option>
                                <option value="abstract">Abstract</option>
                                <option value="surreal">Surreal</option>
                                <option value="impressionist">Impressionist</option>
                                <option value="pop-art">Pop Art</option>
                                <option value="minimalist">Minimalist</option>
                                <option value="cyberpunk">Cyberpunk</option>
                                <option value="fantasy">Fantasy</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quality-level">Quality Level</label>
                            <select id="quality-level" name="quality">
                                <option value="ultra">Ultra (Maximum GPU usage)</option>
                                <option value="high">High</option>
                                <option value="standard">Standard</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="include_all_participants" value="1" checked>
                                Include all TOLA Masterpiece participants
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary button-large">
                                Generate TOLA Masterwork
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="daily-limit-message">
                        <p><strong>Daily limit reached.</strong></p>
                        <p>TOLA Masterwork generation is limited to once daily to ensure quality and resource optimization.</p>
                        <p>Next available: <strong><?php echo date('Y-m-d H:i:s', strtotime('tomorrow')); ?> UTC</strong></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="interface-right">
                <div class="participants-panel">
                    <h3>Active Participants</h3>
                    <div class="participants-list">
                        <?php if (empty($participants)): ?>
                            <p>No participants yet. Users need to agree to TOLA Masterpiece participation.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($participants, 0, 10) as $participant): ?>
                                <div class="participant-card">
                                    <div class="participant-info">
                                        <strong><?php echo esc_html($participant->display_name); ?></strong>
                                        <span class="contribution-score">
                                            Score: <?php echo number_format($participant->contribution_score, 1); ?>
                                        </span>
                                    </div>
                                    <div class="participant-stats">
                                        <small>
                                            Artworks: <?php echo $participant->artworks_contributed; ?> | 
                                            Quality: <?php echo number_format($participant->quality_rating, 2); ?> | 
                                            Level: <?php echo ucfirst($participant->participation_level); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($participants) > 10): ?>
                                <div class="more-participants">
                                    <small>+ <?php echo count($participants) - 10; ?> more participants</small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="marketplace-panel">
                    <h3>Available Marketplace Sources</h3>
                    <div class="marketplace-sources">
                        <?php foreach (array_slice($marketplace_artworks, 0, 10) as $artwork): ?>
                            <div class="source-item">
                                <strong><?php echo esc_html($artwork->post_title); ?></strong>
                                <div class="source-stats">
                                    <span class="quality-badge quality-<?php echo $artwork->quality_score >= 0.8 ? 'high' : ($artwork->quality_score >= 0.6 ? 'medium' : 'low'); ?>">
                                        Quality: <?php echo number_format($artwork->quality_score, 2); ?>
                                    </span>
                                    <span class="usage-count">Used: <?php echo $artwork->usage_count; ?>x</span>
                                    <?php if ($artwork->tola_eligible): ?>
                                        <span class="tola-eligible">✓ TOLA Eligible</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Today's Usage History -->
        <?php if (!empty($today_usage)): ?>
            <div class="usage-history">
                <h3>Today's Usage</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Marketplace Source</th>
                            <th>Participants</th>
                            <th>GPU Duration</th>
                            <th>Cost Savings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_usage as $usage): ?>
                            <tr>
                                <td><?php echo date('H:i:s', strtotime($usage->created_at)); ?></td>
                                <td><?php echo esc_html($usage->action); ?></td>
                                <td><?php echo $usage->marketplace_source ? get_the_title($usage->marketplace_source) : 'N/A'; ?></td>
                                <td><?php echo $usage->participants_count; ?></td>
                                <td><?php echo $usage->gpu_usage_duration; ?>s</td>
                                <td>$<?php echo number_format($usage->cost_savings, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="tola-result-modal" class="tola-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>TOLA Masterwork Result</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="generation-result"></div>
        </div>
    </div>
</div>

<style>
.tola-masterwork-dashboard {
    max-width: 1200px;
    margin: 20px 0;
}

.tola-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.status-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-card.available {
    border-left: 4px solid #28a745;
}

.status-card.used {
    border-left: 4px solid #dc3545;
}

.status-available {
    color: #28a745;
    font-weight: bold;
}

.status-used {
    color: #dc3545;
    font-weight: bold;
}

.tola-main-interface {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.tola-generation-form {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-group textarea {
    height: 100px;
    resize: vertical;
}

.participants-panel,
.marketplace-panel {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.participant-card {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.participant-card:last-child {
    border-bottom: none;
}

.contribution-score {
    float: right;
    font-size: 0.9em;
    color: #666;
}

.participant-stats {
    margin-top: 5px;
}

.quality-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.8em;
    font-weight: bold;
}

.quality-high {
    background: #28a745;
    color: white;
}

.quality-medium {
    background: #ffc107;
    color: black;
}

.quality-low {
    background: #dc3545;
    color: white;
}

.tola-eligible {
    color: #28a745;
    font-weight: bold;
}

.daily-limit-message {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.tola-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 8px;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle form submission
    $('#tola-masterwork-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'tola_masterwork_generate',
            nonce: '<?php echo wp_create_nonce('tola_masterwork_nonce'); ?>',
            marketplace_source: $('#marketplace-source').val(),
            query: $('#generation-query').val(),
            style: $('#art-style').val(),
            quality: $('#quality-level').val(),
            include_all_participants: $('input[name="include_all_participants"]:checked').val() || 0
        };
        
        // Show loading state
        const $button = $(this).find('button[type="submit"]');
        const originalText = $button.text();
        $button.text('Generating...').prop('disabled', true);
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                $('#generation-result').html(response.data.html);
                $('#tola-result-modal').show();
                
                // Refresh page after successful generation
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('Network error. Please try again.');
        }).always(function() {
            $button.text(originalText).prop('disabled', false);
        });
    });
    
    // Handle modal close
    $('.close, #tola-result-modal').on('click', function(e) {
        if (e.target === this) {
            $('#tola-result-modal').hide();
        }
    });
    
    // Real-time source validation
    $('#marketplace-source').on('change', function() {
        const $option = $(this).find('option:selected');
        const quality = $option.data('quality');
        const usage = $option.data('usage');
        const eligible = $option.data('eligible');
        
        if (quality < 0.5) {
            alert('Warning: This artwork has low quality score. Consider selecting a higher quality source.');
        }
    });
});
</script>
<?php 