<?php
/**
 * Vortex AI Engine - Agreement Policy
 * 
 * Handles user agreement to Vortex Artec, co terms of service and privacy policy
 * Required for plugin activation and usage
 * 
 * @package VortexAIEngine
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vortex_Agreement_Policy {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Agreement options
     */
    private $agreement_options = [
        'terms_version' => '2.2.0',
        'privacy_version' => '2.2.0',
        'required_agreement' => true,
        'agreement_expiry_days' => 365
    ];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Check agreement on plugin activation
        add_action('vortex_plugin_activated', [$this, 'check_user_agreement']);
        
        // Add agreement check to admin pages
        add_action('admin_init', [$this, 'check_admin_agreement']);
        
        // Add agreement modal to frontend
        add_action('wp_footer', [$this, 'add_agreement_modal']);
        
        // Handle agreement submission
        add_action('wp_ajax_vortex_accept_agreement', [$this, 'handle_agreement_acceptance']);
        add_action('wp_ajax_nopriv_vortex_accept_agreement', [$this, 'handle_agreement_acceptance']);
        
        // Add agreement status to user profile
        add_action('show_user_profile', [$this, 'add_agreement_status_to_profile']);
        add_action('edit_user_profile', [$this, 'add_agreement_status_to_profile']);
        
        // Add admin menu for agreement management
        add_action('admin_menu', [$this, 'add_agreement_admin_menu']);
        
        // Enqueue agreement assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_agreement_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_agreement_assets']);
    }
    
    /**
     * Check user agreement on plugin activation
     */
    public function check_user_agreement() {
        if (!$this->has_user_agreed()) {
            $this->show_agreement_notice();
        }
    }
    
    /**
     * Check admin agreement
     */
    public function check_admin_agreement() {
        if (is_admin() && !$this->has_user_agreed()) {
            add_action('admin_notices', [$this, 'show_admin_agreement_notice']);
        }
    }
    
    /**
     * Check if user has agreed to terms
     */
    public function has_user_agreed() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $agreement_data = get_user_meta($user_id, 'vortex_agreement_data', true);
        
        if (!$agreement_data) {
            return false;
        }
        
        // Check if agreement is still valid
        $agreement_time = $agreement_data['agreed_at'] ?? 0;
        $expiry_time = $agreement_time + ($this->agreement_options['agreement_expiry_days'] * 24 * 60 * 60);
        
        if (time() > $expiry_time) {
            return false;
        }
        
        // Check if terms version is current
        if (($agreement_data['terms_version'] ?? '') !== $this->agreement_options['terms_version']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Show agreement notice
     */
    public function show_agreement_notice() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>Vortex AI Engine:</strong> You must agree to the <a href="#" onclick="showVortexAgreement()">Terms of Service and Privacy Policy</a> to use this plugin.</p>
        </div>
        <?php
    }
    
    /**
     * Show admin agreement notice
     */
    public function show_admin_agreement_notice() {
        ?>
        <div class="notice notice-error">
            <h3>🤖 Vortex AI Engine - Agreement Required</h3>
            <p>You must agree to the Vortex Artec, co Terms of Service and Privacy Policy to use this plugin.</p>
            <p><a href="#" class="button button-primary" onclick="showVortexAgreement()">Review & Accept Terms</a></p>
        </div>
        <?php
    }
    
    /**
     * Add agreement modal to frontend
     */
    public function add_agreement_modal() {
        if (!$this->has_user_agreed()) {
            ?>
            <div id="vortex-agreement-modal" class="vortex-modal" style="display: none;">
                <div class="vortex-modal-content">
                    <div class="vortex-modal-header">
                        <h2>🤖 Vortex AI Engine - Terms of Service & Privacy Policy</h2>
                        <span class="vortex-modal-close" onclick="closeVortexAgreement()">&times;</span>
                    </div>
                    <div class="vortex-modal-body">
                        <div class="agreement-tabs">
                            <button class="tab-button active" onclick="showAgreementTab('terms')">Terms of Service</button>
                            <button class="tab-button" onclick="showAgreementTab('privacy')">Privacy Policy</button>
                        </div>
                        
                        <div id="terms-content" class="tab-content active">
                            <?php echo $this->get_terms_of_service(); ?>
                        </div>
                        
                        <div id="privacy-content" class="tab-content">
                            <?php echo $this->get_privacy_policy(); ?>
                        </div>
                        
                        <div class="agreement-checkbox">
                            <label>
                                <input type="checkbox" id="vortex-agreement-checkbox">
                                I have read and agree to the Terms of Service and Privacy Policy
                            </label>
                        </div>
                    </div>
                    <div class="vortex-modal-footer">
                        <button class="button button-primary" onclick="acceptVortexAgreement()" disabled id="accept-agreement-btn">Accept & Continue</button>
                        <button class="button" onclick="closeVortexAgreement()">Cancel</button>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Handle agreement acceptance
     */
    public function handle_agreement_acceptance() {
        check_ajax_referer('vortex_agreement_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'User must be logged in to accept agreement']);
        }
        
        $user_id = get_current_user_id();
        $agreement_data = [
            'agreed_at' => current_time('timestamp'),
            'terms_version' => $this->agreement_options['terms_version'],
            'privacy_version' => $this->agreement_options['privacy_version'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        update_user_meta($user_id, 'vortex_agreement_data', $agreement_data);
        
        // Log agreement acceptance
        do_action('vortex_agreement_accepted', $user_id, $agreement_data);
        
        wp_send_json_success([
            'message' => 'Agreement accepted successfully',
            'redirect_url' => admin_url('admin.php?page=vortex-ai-engine')
        ]);
    }
    
    /**
     * Get Terms of Service content
     */
    private function get_terms_of_service() {
        return '
        <div class="terms-content">
            <h3>Vortex Artec, co - Terms of Service</h3>
            <p><strong>Effective Date:</strong> ' . date('F j, Y') . '</p>
            
            <h4>1. Acceptance of Terms</h4>
            <p>By using the Vortex AI Engine plugin ("the Service"), you agree to be bound by these Terms of Service ("Terms") and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this service.</p>
            
            <h4>2. Service Description</h4>
            <p>The Vortex AI Engine is an advanced artificial intelligence system that provides:</p>
            <ul>
                <li>AI-powered content generation and analysis</li>
                <li>Blockchain integration and smart contract automation</li>
                <li>Artist journey tracking and management</li>
                <li>Real-time monitoring and analytics</li>
                <li>Continuous learning and self-improvement capabilities</li>
            </ul>
            
            <h4>3. User Responsibilities</h4>
            <p>You agree to:</p>
            <ul>
                <li>Use the Service only for lawful purposes</li>
                <li>Not attempt to reverse engineer or compromise the system</li>
                <li>Respect intellectual property rights</li>
                <li>Maintain the security of your account credentials</li>
                <li>Report any security vulnerabilities or issues</li>
            </ul>
            
            <h4>4. Intellectual Property</h4>
            <p>The Service and its original content, features, and functionality are owned by Vortex Artec, co and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>
            
            <h4>5. Privacy and Data</h4>
            <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the Service, to understand our practices.</p>
            
            <h4>6. Service Availability</h4>
            <p>We strive to maintain high availability but do not guarantee uninterrupted access. The Service may be temporarily unavailable for maintenance or updates.</p>
            
            <h4>7. Limitation of Liability</h4>
            <p>Vortex Artec, co shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the Service.</p>
            
            <h4>8. Termination</h4>
            <p>We may terminate or suspend your access immediately, without prior notice, for any reason, including breach of these Terms.</p>
            
            <h4>9. Changes to Terms</h4>
            <p>We reserve the right to modify these terms at any time. We will notify users of any material changes.</p>
            
            <h4>10. Contact Information</h4>
            <p>For questions about these Terms, please contact us at legal@vortexartec.com</p>
        </div>';
    }
    
    /**
     * Get Privacy Policy content
     */
    private function get_privacy_policy() {
        return '
        <div class="privacy-content">
            <h3>Vortex Artec, co - Privacy Policy</h3>
            <p><strong>Effective Date:</strong> ' . date('F j, Y') . '</p>
            
            <h4>1. Information We Collect</h4>
            <p>We collect information you provide directly to us, including:</p>
            <ul>
                <li>Account information (name, email, preferences)</li>
                <li>Usage data and interaction patterns</li>
                <li>Feedback and ratings</li>
                <li>Performance metrics and analytics</li>
                <li>Technical information (IP address, browser type, device info)</li>
            </ul>
            
            <h4>2. How We Use Your Information</h4>
            <p>We use the collected information to:</p>
            <ul>
                <li>Provide and improve our services</li>
                <li>Personalize your experience</li>
                <li>Train and optimize AI models</li>
                <li>Monitor system performance and security</li>
                <li>Communicate with you about updates and features</li>
                <li>Comply with legal obligations</li>
            </ul>
            
            <h4>3. AI Model Training</h4>
            <p>Your interactions with the AI system may be used to improve our models through:</p>
            <ul>
                <li>Feedback analysis and sentiment tracking</li>
                <li>Performance optimization</li>
                <li>Quality improvement</li>
                <li>Feature development</li>
            </ul>
            <p>All data used for training is anonymized and aggregated to protect your privacy.</p>
            
            <h4>4. Data Sharing</h4>
            <p>We do not sell, trade, or rent your personal information. We may share data with:</p>
            <ul>
                <li>Service providers who assist in our operations</li>
                <li>Legal authorities when required by law</li>
                <li>Partners with your explicit consent</li>
            </ul>
            
            <h4>5. Data Security</h4>
            <p>We implement appropriate security measures to protect your information, including:</p>
            <ul>
                <li>Encryption of data in transit and at rest</li>
                <li>Regular security audits and updates</li>
                <li>Access controls and authentication</li>
                <li>Monitoring and threat detection</li>
            </ul>
            
            <h4>6. Data Retention</h4>
            <p>We retain your information for as long as necessary to provide our services and comply with legal obligations. You may request deletion of your data at any time.</p>
            
            <h4>7. Your Rights</h4>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal information</li>
                <li>Correct inaccurate data</li>
                <li>Request deletion of your data</li>
                <li>Opt-out of certain communications</li>
                <li>Lodge a complaint with supervisory authorities</li>
            </ul>
            
            <h4>8. Cookies and Tracking</h4>
            <p>We use cookies and similar technologies to enhance your experience and analyze usage patterns.</p>
            
            <h4>9. International Transfers</h4>
            <p>Your information may be transferred to and processed in countries other than your own, where privacy laws may be different.</p>
            
            <h4>10. Children\'s Privacy</h4>
            <p>Our service is not intended for children under 13. We do not knowingly collect information from children under 13.</p>
            
            <h4>11. Changes to Privacy Policy</h4>
            <p>We may update this Privacy Policy from time to time. We will notify you of any material changes.</p>
            
            <h4>12. Contact Us</h4>
            <p>For privacy-related questions, please contact us at privacy@vortexartec.com</p>
        </div>';
    }
    
    /**
     * Add agreement status to user profile
     */
    public function add_agreement_status_to_profile($user) {
        $agreement_data = get_user_meta($user->ID, 'vortex_agreement_data', true);
        
        ?>
        <h3>Vortex AI Engine Agreement Status</h3>
        <table class="form-table">
            <tr>
                <th>Agreement Status</th>
                <td>
                    <?php if ($this->has_user_agreed()): ?>
                        <span style="color: green;">✓ Agreed</span>
                        <p><small>Agreed on: <?php echo date('F j, Y g:i a', $agreement_data['agreed_at']); ?></small></p>
                    <?php else: ?>
                        <span style="color: red;">✗ Not Agreed</span>
                        <p><a href="#" onclick="showVortexAgreement()">Review and Accept Terms</a></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Add agreement admin menu
     */
    public function add_agreement_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            'Agreement Management',
            'Agreements',
            'manage_options',
            'vortex-agreements',
            [$this, 'render_agreement_admin_page']
        );
    }
    
    /**
     * Render agreement admin page
     */
    public function render_agreement_admin_page() {
        $users_without_agreement = $this->get_users_without_agreement();
        $agreement_stats = $this->get_agreement_statistics();
        
        ?>
        <div class="wrap">
            <h1>🤖 Vortex AI Engine - Agreement Management</h1>
            
            <div class="agreement-stats">
                <h2>Agreement Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $agreement_stats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $agreement_stats['agreed_users']; ?></h3>
                        <p>Agreed Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $agreement_stats['pending_users']; ?></h3>
                        <p>Pending Agreement</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo round($agreement_stats['agreement_rate'] * 100, 1); ?>%</h3>
                        <p>Agreement Rate</p>
                    </div>
                </div>
            </div>
            
            <div class="users-without-agreement">
                <h2>Users Without Agreement</h2>
                <?php if (empty($users_without_agreement)): ?>
                    <p>All users have agreed to the terms!</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_without_agreement as $user): ?>
                                <tr>
                                    <td><?php echo esc_html($user->display_name); ?></td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($user->user_registered)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button">Edit User</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="agreement-settings">
                <h2>Agreement Settings</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('vortex_agreement_options'); ?>
                    <table class="form-table">
                        <tr>
                            <th>Require Agreement</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="vortex_require_agreement" value="1" <?php checked(get_option('vortex_require_agreement', true)); ?>>
                                    Users must agree to terms before using the plugin
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Agreement Expiry (days)</th>
                            <td>
                                <input type="number" name="vortex_agreement_expiry_days" value="<?php echo get_option('vortex_agreement_expiry_days', 365); ?>" min="1" max="3650">
                                <p class="description">How long agreements remain valid (1-3650 days)</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get users without agreement
     */
    private function get_users_without_agreement() {
        $users = get_users();
        $users_without_agreement = [];
        
        foreach ($users as $user) {
            if (!$this->has_user_agreed($user->ID)) {
                $users_without_agreement[] = $user;
            }
        }
        
        return $users_without_agreement;
    }
    
    /**
     * Get agreement statistics
     */
    private function get_agreement_statistics() {
        $users = get_users();
        $total_users = count($users);
        $agreed_users = 0;
        
        foreach ($users as $user) {
            if ($this->has_user_agreed($user->ID)) {
                $agreed_users++;
            }
        }
        
        return [
            'total_users' => $total_users,
            'agreed_users' => $agreed_users,
            'pending_users' => $total_users - $agreed_users,
            'agreement_rate' => $total_users > 0 ? $agreed_users / $total_users : 0
        ];
    }
    
    /**
     * Enqueue agreement assets
     */
    public function enqueue_agreement_assets() {
        wp_enqueue_script(
            'vortex-agreement',
            VORTEX_PLUGIN_URL . 'assets/js/agreement.js',
            ['jquery'],
            VORTEX_VERSION,
            true
        );
        
        wp_enqueue_style(
            'vortex-agreement',
            VORTEX_PLUGIN_URL . 'assets/css/agreement.css',
            [],
            VORTEX_VERSION
        );
        
        wp_localize_script('vortex-agreement', 'vortexAgreement', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_agreement_nonce')
        ]);
    }
}

// Initialize the agreement policy
Vortex_Agreement_Policy::get_instance(); 