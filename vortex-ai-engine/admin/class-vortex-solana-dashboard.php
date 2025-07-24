<?php
/**
 * VORTEX AI Engine - Solana Dashboard
 * 
 * Admin dashboard for Solana blockchain metrics and management
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Solana Dashboard Class
 * 
 * Handles the Solana blockchain dashboard in WordPress admin
 */
class Vortex_Solana_Dashboard {
    
    /**
     * Dashboard configuration
     */
    private $config = [
        'name' => 'VORTEX Solana Dashboard',
        'version' => '3.0.0',
        'page_title' => 'Solana Blockchain',
        'menu_title' => 'Solana',
        'capability' => 'manage_options',
        'menu_slug' => 'vortex-solana-dashboard',
        'icon' => 'dashicons-networking'
    ];
    
    /**
     * Solana integration instance
     */
    private $solana_integration;
    
    /**
     * Initialize the dashboard
     */
    public function init() {
        $this->solana_integration = new Vortex_Solana_Integration();
        $this->register_hooks();
        
        error_log('VORTEX AI Engine: Solana Dashboard initialized');
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_vortex_solana_refresh_metrics', [$this, 'handle_refresh_metrics']);
        add_action('wp_ajax_vortex_solana_deploy_program', [$this, 'handle_deploy_program']);
        add_action('wp_ajax_vortex_solana_test_connection', [$this, 'handle_test_connection']);
        add_action('wp_ajax_vortex_solana_tola_operation', [$this, 'handle_tola_operation']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai-engine',
            $this->config['page_title'],
            $this->config['menu_title'],
            $this->config['capability'],
            $this->config['menu_slug'],
            [$this, 'render_dashboard']
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'vortex-ai-engine_page_' . $this->config['menu_slug']) {
            return;
        }
        
        wp_enqueue_script(
            'vortex-solana-dashboard',
            plugin_dir_url(__FILE__) . '../assets/js/solana-dashboard.js',
            ['jquery', 'wp-util'],
            $this->config['version'],
            true
        );
        
        wp_enqueue_style(
            'vortex-solana-dashboard',
            plugin_dir_url(__FILE__) . '../assets/css/solana-dashboard.css',
            [],
            $this->config['version']
        );
        
        wp_localize_script('vortex-solana-dashboard', 'vortexSolana', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_solana_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'vortex-ai-engine'),
                'error' => __('Error occurred', 'vortex-ai-engine'),
                'success' => __('Operation completed successfully', 'vortex-ai-engine')
            ]
        ]);
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $solana_status = $this->solana_integration->get_status();
        $metrics = Vortex_Solana_Database_Manager::get_metrics(null, 50);
        $programs = Vortex_Solana_Database_Manager::get_programs();
        $health_checks = Vortex_Solana_Database_Manager::get_health_checks(null, 10);
        $statistics = Vortex_Solana_Database_Manager::get_statistics();
        
        // Get TOLA token data
        $tola_data = $this->get_tola_metrics();
        $tola_transactions = $this->get_tola_transactions();
        $tola_holders = $this->get_tola_holders();
        $tola_rewards = $this->get_tola_rewards();
        
        ?>
        <div class="wrap vortex-solana-dashboard">
            <h1>⛓️ <?php echo esc_html($this->config['page_title']); ?> & 🎨 TOLA Token Metrics</h1>
            
            <!-- Network Status -->
            <div class="vortex-solana-section">
                <h2>🌐 Network Status</h2>
                <div class="vortex-solana-grid">
                    <div class="vortex-solana-card">
                        <h3>Active Network</h3>
                        <div class="vortex-solana-value"><?php echo esc_html($solana_status['active_network']); ?></div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>RPC URL</h3>
                        <div class="vortex-solana-value"><?php echo esc_html($solana_status['rpc_url']); ?></div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Metrics</h3>
                        <div class="vortex-solana-value <?php echo $solana_status['metrics_configured'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $solana_status['metrics_configured'] ? 'Configured' : 'Not Configured'; ?>
                        </div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Validator</h3>
                        <div class="vortex-solana-value <?php echo $solana_status['validator_configured'] ? 'status-ok' : 'status-warning'; ?>">
                            <?php echo $solana_status['validator_configured'] ? 'Configured' : 'Not Configured'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TOLA Token Overview -->
            <div class="vortex-solana-section">
                <h2>🎨 TOLA Token Overview</h2>
                <div class="vortex-solana-grid">
                    <div class="vortex-solana-card tola-card">
                        <h3>Total Supply</h3>
                        <div class="vortex-solana-value large"><?php echo number_format($tola_data['total_supply']); ?> TOLA</div>
                        <div class="tola-subtitle">Circulating: <?php echo number_format($tola_data['circulating_supply']); ?></div>
                    </div>
                    <div class="vortex-solana-card tola-card">
                        <h3>Market Cap</h3>
                        <div class="vortex-solana-value large">$<?php echo number_format($tola_data['market_cap'], 2); ?></div>
                        <div class="tola-subtitle">Price: $<?php echo number_format($tola_data['price'], 6); ?></div>
                    </div>
                    <div class="vortex-solana-card tola-card">
                        <h3>Token Holders</h3>
                        <div class="vortex-solana-value large"><?php echo number_format($tola_data['holder_count']); ?></div>
                        <div class="tola-subtitle">Active: <?php echo number_format($tola_data['active_holders']); ?></div>
                    </div>
                    <div class="vortex-solana-card tola-card">
                        <h3>24h Volume</h3>
                        <div class="vortex-solana-value large"><?php echo number_format($tola_data['volume_24h']); ?> TOLA</div>
                        <div class="tola-subtitle">$<?php echo number_format($tola_data['volume_usd_24h'], 2); ?></div>
                    </div>
                    <div class="vortex-solana-card tola-card">
                        <h3>Staked Amount</h3>
                        <div class="vortex-solana-value large"><?php echo number_format($tola_data['staked_amount']); ?> TOLA</div>
                        <div class="tola-subtitle"><?php echo number_format($tola_data['staking_apy'], 2); ?>% APY</div>
                    </div>
                    <div class="vortex-solana-card tola-card">
                        <h3>Rewards Distributed</h3>
                        <div class="vortex-solana-value large"><?php echo number_format($tola_data['rewards_distributed']); ?> TOLA</div>
                        <div class="tola-subtitle">Today: <?php echo number_format($tola_data['rewards_today']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- TOLA Token Distribution -->
            <div class="vortex-solana-section">
                <h2>📊 TOLA Token Distribution</h2>
                <div class="vortex-solana-grid">
                    <div class="vortex-solana-card">
                        <h3>Community Rewards</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_data['distribution']['community_rewards']); ?> TOLA</div>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: 40%; background: #9945FF;"></div>
                        </div>
                        <div class="tola-subtitle">40% of Total Supply</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Development Fund</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_data['distribution']['development_fund']); ?> TOLA</div>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: 25%; background: #14F195;"></div>
                        </div>
                        <div class="tola-subtitle">25% of Total Supply</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Liquidity Pool</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_data['distribution']['liquidity_pool']); ?> TOLA</div>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: 20%; background: #FF6B6B;"></div>
                        </div>
                        <div class="tola-subtitle">20% of Total Supply</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Team Tokens</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_data['distribution']['team_tokens']); ?> TOLA</div>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: 10%; background: #FFD93D;"></div>
                        </div>
                        <div class="tola-subtitle">10% of Total Supply</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Reserve Fund</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_data['distribution']['reserve_fund']); ?> TOLA</div>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: 5%; background: #6BCF7F;"></div>
                        </div>
                        <div class="tola-subtitle">5% of Total Supply</div>
                    </div>
                </div>
            </div>
            
            <!-- TOLA Rewards System -->
            <div class="vortex-solana-section">
                <h2>🏆 TOLA Rewards System</h2>
                <div class="vortex-solana-grid">
                    <div class="vortex-solana-card">
                        <h3>Artwork Creation</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_rewards['artwork_creation']); ?> TOLA</div>
                        <div class="tola-subtitle">10 TOLA per artwork</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Community Engagement</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_rewards['community_engagement']); ?> TOLA</div>
                        <div class="tola-subtitle">5 TOLA per engagement</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Exhibition Participation</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_rewards['exhibition_participation']); ?> TOLA</div>
                        <div class="tola-subtitle">15 TOLA per exhibition</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Sales Achievement</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_rewards['sales_achievement']); ?> TOLA</div>
                        <div class="tola-subtitle">20 TOLA per sale</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Mentorship Contribution</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_rewards['mentorship_contribution']); ?> TOLA</div>
                        <div class="tola-subtitle">25 TOLA per mentorship</div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Governance Participation</h3>
                        <div class="vortex-solana-value"><?php echo number_format($tola_rewards['governance_participation']); ?> TOLA</div>
                        <div class="tola-subtitle">25 TOLA per vote</div>
                    </div>
                </div>
            </div>
            
            <!-- Real-time Metrics -->
            <div class="vortex-solana-section">
                <h2>📊 Real-time Metrics</h2>
                <div class="vortex-solana-metrics-grid" id="vortex-solana-metrics">
                    <?php if (!empty($metrics)): ?>
                        <?php $latest_metrics = $metrics[0]; ?>
                        <div class="vortex-solana-card">
                            <h3>Current Slot</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($latest_metrics['slot']); ?></div>
                        </div>
                        <div class="vortex-solana-card">
                            <h3>Block Height</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($latest_metrics['block_height']); ?></div>
                        </div>
                        <div class="vortex-solana-card">
                            <h3>Transactions</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($latest_metrics['transaction_count']); ?></div>
                        </div>
                        <div class="vortex-solana-card">
                            <h3>Validators</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($latest_metrics['validator_count']); ?></div>
                        </div>
                        <div class="vortex-solana-card">
                            <h3>Supply (SOL)</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($latest_metrics['supply'] / 1000000000, 2); ?></div>
                        </div>
                        <div class="vortex-solana-card">
                            <h3>Cluster Nodes</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($latest_metrics['cluster_nodes']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="vortex-solana-card full-width">
                            <p>No metrics data available. <button class="button button-primary" onclick="vortexSolanaRefreshMetrics()">Refresh Metrics</button></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="vortex-solana-section">
                <h2>⚡ Performance Metrics</h2>
                <div class="vortex-solana-grid">
                    <?php if (!empty($metrics) && isset($metrics[0]['performance_data'])): ?>
                        <?php $performance = json_decode($metrics[0]['performance_data'], true); ?>
                        <div class="vortex-solana-card">
                            <h3>TPS (Transactions/sec)</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($performance['tps']); ?></div>
                        </div>
                        <div class="vortex-solana-card">
                            <h3>Block Time (ms)</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($performance['block_time']); ?></div>
                        </div>
                        <div class="vortex-solana-card">
                            <h3>Confirmation Time (ms)</h3>
                            <div class="vortex-solana-value large"><?php echo number_format($performance['confirmation_time']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="vortex-solana-card full-width">
                            <p>Performance data not available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- TOLA Transactions -->
            <div class="vortex-solana-section">
                <h2>💸 Recent TOLA Transactions</h2>
                <?php if (!empty($tola_transactions)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Transaction Hash</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Amount (TOLA)</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($tola_transactions, 0, 10) as $tx): ?>
                                <tr>
                                    <td>
                                        <code><?php echo esc_html(substr($tx['transaction_hash'], 0, 16)) . '...'; ?></code>
                                    </td>
                                    <td><?php echo esc_html(substr($tx['from_address'], 0, 8)) . '...'; ?></td>
                                    <td><?php echo esc_html(substr($tx['to_address'], 0, 8)) . '...'; ?></td>
                                    <td><?php echo number_format($tx['amount'], 2); ?> TOLA</td>
                                    <td>
                                        <span class="transaction-type transaction-<?php echo esc_attr($tx['type']); ?>">
                                            <?php echo esc_html(ucfirst($tx['type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($tx['status']); ?>">
                                            <?php echo esc_html(ucfirst($tx['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($tx['created_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="vortex-solana-card">
                        <p>No TOLA transactions found.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Top TOLA Holders -->
            <div class="vortex-solana-section">
                <h2>👥 Top TOLA Holders</h2>
                <?php if (!empty($tola_holders)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Address</th>
                                <th>Balance (TOLA)</th>
                                <th>Percentage</th>
                                <th>Staked Amount</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($tola_holders, 0, 10) as $index => $holder): ?>
                                <tr>
                                    <td>#<?php echo $index + 1; ?></td>
                                    <td>
                                        <code><?php echo esc_html(substr($holder['address'], 0, 8)) . '...' . substr($holder['address'], -8); ?></code>
                                    </td>
                                    <td><?php echo number_format($holder['balance'], 2); ?> TOLA</td>
                                    <td><?php echo number_format($holder['percentage'], 2); ?>%</td>
                                    <td><?php echo number_format($holder['staked_amount'], 2); ?> TOLA</td>
                                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($holder['last_activity']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="vortex-solana-card">
                        <p>No TOLA holders data available.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Deployed Programs -->
            <div class="vortex-solana-section">
                <h2>📦 Deployed Programs</h2>
                <?php if (!empty($programs)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Program ID</th>
                                <th>Network</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Deployed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program): ?>
                                <tr>
                                    <td>
                                        <code><?php echo esc_html($program['program_id']); ?></code>
                                    </td>
                                    <td><?php echo esc_html($program['network']); ?></td>
                                    <td><?php echo esc_html($program['program_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo esc_html($program['program_type'] ?? 'Custom'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($program['status']); ?>">
                                            <?php echo esc_html(ucfirst($program['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($program['deployed_at']))); ?></td>
                                    <td>
                                        <a href="https://explorer.solana.com/address/<?php echo esc_attr($program['program_id']); ?>?cluster=<?php echo esc_attr($program['network']); ?>" target="_blank" class="button button-small">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="vortex-solana-card">
                        <p>No programs deployed yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Health Checks -->
            <div class="vortex-solana-section">
                <h2>🏥 Health Checks</h2>
                <?php if (!empty($health_checks)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Network</th>
                                <th>RPC Status</th>
                                <th>Metrics Status</th>
                                <th>Validator Status</th>
                                <th>TOLA Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($health_checks as $check): ?>
                                <tr>
                                    <td><?php echo esc_html($check['network']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $check['rpc_status'] ? 'ok' : 'error'; ?>">
                                            <?php echo $check['rpc_status'] ? 'OK' : 'Error'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $check['metrics_status'] ? 'ok' : 'error'; ?>">
                                            <?php echo $check['metrics_status'] ? 'OK' : 'Error'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $check['validator_status'] ? 'ok' : 'warning'; ?>">
                                            <?php echo $check['validator_status'] ? 'OK' : 'Warning'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $check['tola_status'] ? 'ok' : 'warning'; ?>">
                                            <?php echo $check['tola_status'] ? 'OK' : 'Warning'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($check['timestamp']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="vortex-solana-card">
                        <p>No health check data available.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="vortex-solana-section">
                <h2>🔧 Actions</h2>
                <div class="vortex-solana-actions">
                    <button class="button button-primary" onclick="vortexSolanaRefreshMetrics()">
                        🔄 Refresh Metrics
                    </button>
                    <button class="button button-secondary" onclick="vortexSolanaTestConnection()">
                        🔗 Test Connection
                    </button>
                    <button class="button button-secondary" onclick="vortexSolanaShowDeployForm()">
                        📦 Deploy Program
                    </button>
                    <button class="button button-secondary" onclick="vortexSolanaShowTolaForm()">
                        🎨 TOLA Operations
                    </button>
                    <a href="https://explorer.solana.com/?cluster=<?php echo esc_attr($solana_status['active_network']); ?>" target="_blank" class="button button-secondary">
                        🌐 Open Explorer
                    </a>
                </div>
            </div>
            
            <!-- Deploy Program Modal -->
            <div id="vortex-solana-deploy-modal" class="vortex-solana-modal" style="display: none;">
                <div class="vortex-solana-modal-content">
                    <span class="vortex-solana-modal-close">&times;</span>
                    <h2>Deploy Solana Program</h2>
                    <form id="vortex-solana-deploy-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Program Path</th>
                                <td>
                                    <input type="text" name="program_path" class="regular-text" placeholder="/path/to/your/program" required>
                                    <p class="description">Path to your Solana program directory</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Network</th>
                                <td>
                                    <select name="network">
                                        <option value="devnet">Devnet</option>
                                        <option value="testnet">Testnet</option>
                                        <option value="mainnet-beta">Mainnet Beta</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary">Deploy Program</button>
                            <button type="button" class="button button-secondary" onclick="vortexSolanaHideDeployForm()">Cancel</button>
                        </p>
                    </form>
                </div>
            </div>
            
            <!-- TOLA Operations Modal -->
            <div id="vortex-solana-tola-modal" class="vortex-solana-modal" style="display: none;">
                <div class="vortex-solana-modal-content">
                    <span class="vortex-solana-modal-close">&times;</span>
                    <h2>🎨 TOLA Token Operations</h2>
                    <form id="vortex-solana-tola-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Operation Type</th>
                                <td>
                                    <select name="operation_type" id="tola-operation-type">
                                        <option value="transfer">Transfer TOLA</option>
                                        <option value="stake">Stake TOLA</option>
                                        <option value="unstake">Unstake TOLA</option>
                                        <option value="claim_rewards">Claim Rewards</option>
                                        <option value="mint">Mint TOLA</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="tola-amount-row">
                                <th scope="row">Amount (TOLA)</th>
                                <td>
                                    <input type="number" name="amount" class="regular-text" step="0.01" min="0">
                                </td>
                            </tr>
                            <tr id="tola-recipient-row">
                                <th scope="row">Recipient Address</th>
                                <td>
                                    <input type="text" name="recipient_address" class="regular-text" placeholder="Enter Solana address">
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary">Execute Operation</button>
                            <button type="button" class="button button-secondary" onclick="vortexSolanaHideTolaForm()">Cancel</button>
                        </p>
                    </form>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="vortex-solana-section">
                <h2>📈 Statistics</h2>
                <div class="vortex-solana-grid">
                    <div class="vortex-solana-card">
                        <h3>Total Metrics</h3>
                        <div class="vortex-solana-value"><?php echo number_format($statistics['metrics_count']); ?></div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Deployed Programs</h3>
                        <div class="vortex-solana-value"><?php echo number_format($statistics['programs_count']); ?></div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Health Checks</h3>
                        <div class="vortex-solana-value"><?php echo number_format($statistics['health_checks_count']); ?></div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Transactions</h3>
                        <div class="vortex-solana-value"><?php echo number_format($statistics['transactions_count']); ?></div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Accounts</h3>
                        <div class="vortex-solana-value"><?php echo number_format($statistics['accounts_count']); ?></div>
                    </div>
                    <div class="vortex-solana-card">
                        <h3>Recent Activity (24h)</h3>
                        <div class="vortex-solana-value"><?php echo number_format($statistics['recent_transactions']); ?> tx</div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Global functions for AJAX calls
        function vortexSolanaRefreshMetrics() {
            jQuery.post(vortexSolana.ajaxUrl, {
                action: 'vortex_solana_refresh_metrics',
                nonce: vortexSolana.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to refresh metrics: ' + response.data);
                }
            });
        }
        
        function vortexSolanaTestConnection() {
            jQuery.post(vortexSolana.ajaxUrl, {
                action: 'vortex_solana_test_connection',
                nonce: vortexSolana.nonce
            }, function(response) {
                if (response.success) {
                    alert('Connection test successful!');
                } else {
                    alert('Connection test failed: ' + response.data);
                }
            });
        }
        
        function vortexSolanaShowDeployForm() {
            document.getElementById('vortex-solana-deploy-modal').style.display = 'block';
        }
        
        function vortexSolanaHideDeployForm() {
            document.getElementById('vortex-solana-deploy-modal').style.display = 'none';
        }
        
        function vortexSolanaShowTolaForm() {
            document.getElementById('vortex-solana-tola-modal').style.display = 'block';
        }
        
        function vortexSolanaHideTolaForm() {
            document.getElementById('vortex-solana-tola-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var deployModal = document.getElementById('vortex-solana-deploy-modal');
            var tolaModal = document.getElementById('vortex-solana-tola-modal');
            if (event.target == deployModal) {
                deployModal.style.display = 'none';
            }
            if (event.target == tolaModal) {
                tolaModal.style.display = 'none';
            }
        }
        
        // Close modal when clicking X
        document.querySelectorAll('.vortex-solana-modal-close').forEach(function(element) {
            element.onclick = function() {
                this.closest('.vortex-solana-modal').style.display = 'none';
            }
        });
        
        // Handle deploy form submission
        jQuery('#vortex-solana-deploy-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = jQuery(this).serialize();
            formData += '&action=vortex_solana_deploy_program&nonce=' + vortexSolana.nonce;
            
            jQuery.post(vortexSolana.ajaxUrl, formData, function(response) {
                if (response.success) {
                    alert('Program deployed successfully! Program ID: ' + response.data.program_id);
                    location.reload();
                } else {
                    alert('Deployment failed: ' + response.data);
                }
            });
        });
        
        // Handle TOLA form submission
        jQuery('#vortex-solana-tola-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = jQuery(this).serialize();
            formData += '&action=vortex_solana_tola_operation&nonce=' + vortexSolana.nonce;
            
            jQuery.post(vortexSolana.ajaxUrl, formData, function(response) {
                if (response.success) {
                    alert('TOLA operation completed successfully!');
                    location.reload();
                } else {
                    alert('TOLA operation failed: ' + response.data);
                }
            });
        });
        
        // Toggle form fields based on operation type
        jQuery('#tola-operation-type').on('change', function() {
            var operationType = jQuery(this).val();
            
            if (operationType === 'transfer') {
                jQuery('#tola-amount-row, #tola-recipient-row').show();
            } else if (operationType === 'stake' || operationType === 'unstake') {
                jQuery('#tola-amount-row').show();
                jQuery('#tola-recipient-row').hide();
            } else {
                jQuery('#tola-amount-row, #tola-recipient-row').hide();
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handlers
     */
    public function handle_refresh_metrics() {
        check_ajax_referer('vortex_solana_nonce', 'nonce');
        
        if (!current_user_can($this->config['capability'])) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $this->solana_integration->collect_metrics();
        
        wp_send_json_success('Metrics refreshed successfully');
    }
    
    public function handle_deploy_program() {
        check_ajax_referer('vortex_solana_nonce', 'nonce');
        
        if (!current_user_can($this->config['capability'])) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $program_path = sanitize_text_field($_POST['program_path']);
        $network = sanitize_text_field($_POST['network'] ?? 'devnet');
        
        $result = $this->solana_integration->deploy_program($program_path, $network);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    public function handle_test_connection() {
        check_ajax_referer('vortex_solana_nonce', 'nonce');
        
        if (!current_user_can($this->config['capability'])) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $network = sanitize_text_field($_POST['network'] ?? 'devnet');
        
        $result = $this->solana_integration->test_connection($network);
        
        wp_send_json_success($result);
    }
    
    public function handle_tola_operation() {
        check_ajax_referer('vortex_solana_nonce', 'nonce');
        
        if (!current_user_can($this->config['capability'])) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $operation_type = sanitize_text_field($_POST['operation_type']);
        $amount = floatval($_POST['amount'] ?? 0);
        $recipient_address = sanitize_text_field($_POST['recipient_address'] ?? '');
        $user_id = get_current_user_id();
        
        $result = $this->execute_tola_operation($operation_type, $amount, $recipient_address, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Execute TOLA operation
     */
    private function execute_tola_operation($operation_type, $amount, $recipient_address, $user_id) {
        global $wpdb;
        
        switch ($operation_type) {
            case 'transfer':
                if (empty($recipient_address) || $amount <= 0) {
                    return ['success' => false, 'error' => 'Invalid recipient address or amount'];
                }
                
                // Check user balance
                $user_balance = $this->get_user_tola_balance($user_id);
                if ($user_balance < $amount) {
                    return ['success' => false, 'error' => 'Insufficient TOLA balance'];
                }
                
                // Execute transfer
                $transfer_result = $this->transfer_tola($user_id, $recipient_address, $amount);
                return $transfer_result;
                
            case 'stake':
                if ($amount <= 0) {
                    return ['success' => false, 'error' => 'Invalid staking amount'];
                }
                
                // Check user balance
                $user_balance = $this->get_user_tola_balance($user_id);
                if ($user_balance < $amount) {
                    return ['success' => false, 'error' => 'Insufficient TOLA balance'];
                }
                
                // Execute staking
                $stake_result = $this->stake_tola($user_id, $amount);
                return $stake_result;
                
            case 'unstake':
                if ($amount <= 0) {
                    return ['success' => false, 'error' => 'Invalid unstaking amount'];
                }
                
                // Check staked balance
                $staked_balance = $this->get_user_staked_balance($user_id);
                if ($staked_balance < $amount) {
                    return ['success' => false, 'error' => 'Insufficient staked TOLA balance'];
                }
                
                // Execute unstaking
                $unstake_result = $this->unstake_tola($user_id, $amount);
                return $unstake_result;
                
            case 'claim_rewards':
                // Check available rewards
                $available_rewards = $this->get_user_available_rewards($user_id);
                if ($available_rewards <= 0) {
                    return ['success' => false, 'error' => 'No rewards available to claim'];
                }
                
                // Execute reward claim
                $claim_result = $this->claim_tola_rewards($user_id);
                return $claim_result;
                
            case 'mint':
                // Only allow minting for authorized users
                if (!current_user_can('manage_options')) {
                    return ['success' => false, 'error' => 'Insufficient permissions for minting'];
                }
                
                if ($amount <= 0) {
                    return ['success' => false, 'error' => 'Invalid minting amount'];
                }
                
                // Execute minting
                $mint_result = $this->mint_tola($user_id, $amount);
                return $mint_result;
                
            default:
                return ['success' => false, 'error' => 'Invalid operation type'];
        }
    }
    
    /**
     * Get user TOLA balance
     */
    private function get_user_tola_balance($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d",
            $user_id
        )) ?? 0;
    }
    
    /**
     * Get user staked balance
     */
    private function get_user_staked_balance($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_staking WHERE user_id = %d AND status = 'staked'",
            $user_id
        )) ?? 0;
    }
    
    /**
     * Get user available rewards
     */
    private function get_user_available_rewards($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE user_id = %d AND claimed = 0",
            $user_id
        )) ?? 0;
    }
    
    /**
     * Transfer TOLA
     */
    private function transfer_tola($from_user_id, $to_address, $amount) {
        global $wpdb;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Deduct from sender
            $wpdb->update(
                $wpdb->prefix . 'vortex_tola_balances',
                ['balance' => $wpdb->get_var($wpdb->prepare("SELECT balance FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d", $from_user_id)) - $amount],
                ['user_id' => $from_user_id]
            );
            
            // Add to recipient (if user exists)
            $to_user_id = $this->get_user_id_by_address($to_address);
            if ($to_user_id) {
                $wpdb->update(
                    $wpdb->prefix . 'vortex_tola_balances',
                    ['balance' => $wpdb->get_var($wpdb->prepare("SELECT balance FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d", $to_user_id)) + $amount],
                    ['user_id' => $to_user_id]
                );
            }
            
            // Record transaction
            $transaction_hash = '0x' . substr(md5($from_user_id . $to_address . $amount . time()), 0, 64);
            $wpdb->insert(
                $wpdb->prefix . 'vortex_tola_transactions',
                [
                    'from_user_id' => $from_user_id,
                    'to_address' => $to_address,
                    'amount' => $amount,
                    'transaction_hash' => $transaction_hash,
                    'type' => 'transfer',
                    'status' => 'confirmed',
                    'created_at' => current_time('mysql')
                ]
            );
            
            $wpdb->query('COMMIT');
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash,
                'message' => 'TOLA transfer completed successfully'
            ];
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return ['success' => false, 'error' => 'Transfer failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Stake TOLA
     */
    private function stake_tola($user_id, $amount) {
        global $wpdb;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Deduct from balance
            $wpdb->update(
                $wpdb->prefix . 'vortex_tola_balances',
                ['balance' => $wpdb->get_var($wpdb->prepare("SELECT balance FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d", $user_id)) - $amount],
                ['user_id' => $user_id]
            );
            
            // Add to staking
            $transaction_hash = '0x' . substr(md5($user_id . $amount . time()), 0, 64);
            $wpdb->insert(
                $wpdb->prefix . 'vortex_token_staking',
                [
                    'user_id' => $user_id,
                    'amount' => $amount,
                    'transaction_hash' => $transaction_hash,
                    'status' => 'staked',
                    'created_at' => current_time('mysql')
                ]
            );
            
            $wpdb->query('COMMIT');
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash,
                'message' => 'TOLA staking completed successfully'
            ];
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return ['success' => false, 'error' => 'Staking failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Unstake TOLA
     */
    private function unstake_tola($user_id, $amount) {
        global $wpdb;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Remove from staking
            $wpdb->update(
                $wpdb->prefix . 'vortex_token_staking',
                ['status' => 'unstaked'],
                [
                    'user_id' => $user_id,
                    'status' => 'staked',
                    'amount' => $amount
                ]
            );
            
            // Add back to balance
            $wpdb->update(
                $wpdb->prefix . 'vortex_tola_balances',
                ['balance' => $wpdb->get_var($wpdb->prepare("SELECT balance FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d", $user_id)) + $amount],
                ['user_id' => $user_id]
            );
            
            $transaction_hash = '0x' . substr(md5($user_id . $amount . time()), 0, 64);
            
            $wpdb->query('COMMIT');
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash,
                'message' => 'TOLA unstaking completed successfully'
            ];
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return ['success' => false, 'error' => 'Unstaking failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Claim TOLA rewards
     */
    private function claim_tola_rewards($user_id) {
        global $wpdb;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Get available rewards
            $available_rewards = $this->get_user_available_rewards($user_id);
            
            if ($available_rewards <= 0) {
                return ['success' => false, 'error' => 'No rewards available to claim'];
            }
            
            // Mark rewards as claimed
            $wpdb->update(
                $wpdb->prefix . 'vortex_token_rewards',
                ['claimed' => 1, 'claimed_at' => current_time('mysql')],
                ['user_id' => $user_id, 'claimed' => 0]
            );
            
            // Add to balance
            $wpdb->update(
                $wpdb->prefix . 'vortex_tola_balances',
                ['balance' => $wpdb->get_var($wpdb->prepare("SELECT balance FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d", $user_id)) + $available_rewards],
                ['user_id' => $user_id]
            );
            
            $transaction_hash = '0x' . substr(md5($user_id . $available_rewards . time()), 0, 64);
            
            $wpdb->query('COMMIT');
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash,
                'amount' => $available_rewards,
                'message' => 'TOLA rewards claimed successfully'
            ];
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return ['success' => false, 'error' => 'Reward claim failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mint TOLA
     */
    private function mint_tola($user_id, $amount) {
        global $wpdb;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Add to balance
            $wpdb->update(
                $wpdb->prefix . 'vortex_tola_balances',
                ['balance' => $wpdb->get_var($wpdb->prepare("SELECT balance FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d", $user_id)) + $amount],
                ['user_id' => $user_id]
            );
            
            $transaction_hash = '0x' . substr(md5($user_id . $amount . time()), 0, 64);
            
            // Record mint transaction
            $wpdb->insert(
                $wpdb->prefix . 'vortex_tola_transactions',
                [
                    'from_user_id' => 0, // System mint
                    'to_address' => $this->get_user_address($user_id),
                    'amount' => $amount,
                    'transaction_hash' => $transaction_hash,
                    'type' => 'mint',
                    'status' => 'confirmed',
                    'created_at' => current_time('mysql')
                ]
            );
            
            $wpdb->query('COMMIT');
            
            return [
                'success' => true,
                'transaction_hash' => $transaction_hash,
                'message' => 'TOLA minting completed successfully'
            ];
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return ['success' => false, 'error' => 'Minting failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user ID by address
     */
    private function get_user_id_by_address($address) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}vortex_tola_balances WHERE address = %s",
            $address
        ));
    }
    
    /**
     * Get user address
     */
    private function get_user_address($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT address FROM {$wpdb->prefix}vortex_tola_balances WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Get TOLA metrics
     */
    private function get_tola_metrics() {
        global $wpdb;
        
        // Get basic token data
        $total_supply = 1000000000; // 1 billion TOLA
        $circulating_supply = $wpdb->get_var("SELECT SUM(balance) FROM {$wpdb->prefix}vortex_tola_balances") ?? 0;
        $holder_count = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}vortex_tola_balances") ?? 0;
        $active_holders = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}vortex_tola_balances WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?? 0;
        
        // Get staking data
        $staked_amount = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_staking WHERE status = 'staked'") ?? 0;
        $staking_apy = 12.5; // 12.5% APY
        
        // Get rewards data
        $rewards_distributed = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards") ?? 0;
        $rewards_today = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE DATE(created_at) = CURDATE()") ?? 0;
        
        // Get transaction data
        $volume_24h = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_tola_transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?? 0;
        
        // Calculate price (simulated)
        $price = 0.000125; // $0.000125 per TOLA
        $market_cap = $circulating_supply * $price;
        $volume_usd_24h = $volume_24h * $price;
        
        // Token distribution
        $distribution = [
            'community_rewards' => $total_supply * 0.40,
            'development_fund' => $total_supply * 0.25,
            'liquidity_pool' => $total_supply * 0.20,
            'team_tokens' => $total_supply * 0.10,
            'reserve_fund' => $total_supply * 0.05
        ];
        
        return [
            'total_supply' => $total_supply,
            'circulating_supply' => $circulating_supply,
            'holder_count' => $holder_count,
            'active_holders' => $active_holders,
            'staked_amount' => $staked_amount,
            'staking_apy' => $staking_apy,
            'rewards_distributed' => $rewards_distributed,
            'rewards_today' => $rewards_today,
            'volume_24h' => $volume_24h,
            'price' => $price,
            'market_cap' => $market_cap,
            'volume_usd_24h' => $volume_usd_24h,
            'distribution' => $distribution
        ];
    }
    
    /**
     * Get TOLA transactions
     */
    private function get_tola_transactions() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_tola_transactions ORDER BY created_at DESC LIMIT 50",
            ARRAY_A
        ) ?? [];
    }
    
    /**
     * Get TOLA holders
     */
    private function get_tola_holders() {
        global $wpdb;
        
        $total_supply = 1000000000;
        
        return $wpdb->get_results(
            "SELECT 
                user_id,
                address,
                balance,
                staked_amount,
                last_activity,
                (balance / $total_supply) * 100 as percentage
            FROM {$wpdb->prefix}vortex_tola_balances 
            ORDER BY balance DESC 
            LIMIT 20",
            ARRAY_A
        ) ?? [];
    }
    
    /**
     * Get TOLA rewards
     */
    private function get_tola_rewards() {
        global $wpdb;
        
        return [
            'artwork_creation' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE activity_type = 'artwork_creation'") ?? 0,
            'community_engagement' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE activity_type = 'community_engagement'") ?? 0,
            'exhibition_participation' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE activity_type = 'exhibition_participation'") ?? 0,
            'sales_achievement' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE activity_type = 'sales_achievement'") ?? 0,
            'mentorship_contribution' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE activity_type = 'mentorship_contribution'") ?? 0,
            'governance_participation' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_rewards WHERE activity_type = 'governance_participation'") ?? 0
        ];
    }
} 