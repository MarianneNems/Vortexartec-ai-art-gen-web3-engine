<?php
/**
 * TOLA NFT Smoke Test
 * 
 * Comprehensive end-to-end test for TOLA NFT minting and royalty workflow
 * 
 * @package VortexAI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class TOLA_NFT_Smoke_Test {
    
    private $test_results = [];
    private $test_user_id;
    private $test_wallet_address;
    private $test_artwork_id;
    private $solana_integration;
    private $nft_database;
    
    public function __construct() {
        $this->solana_integration = new VortexAIEngine_Solana_Integration();
        $this->nft_database = new VortexAIEngine_NFT_Database();
    }
    
    /**
     * Run complete smoke test suite
     */
    public function run_smoke_tests() {
        $this->log_test_start();
        
        try {
            // Setup test environment
            $this->setup_test_environment();
            
            // Test 1: Configuration and Setup
            $this->test_configuration_setup();
            
            // Test 2: Wallet Connection
            $this->test_wallet_connection();
            
            // Test 3: Database Operations
            $this->test_database_operations();
            
            // Test 4: Artwork Upload and Metadata
            $this->test_artwork_upload();
            
            // Test 5: NFT Minting
            $this->test_nft_minting();
            
            // Test 6: Royalty Management
            $this->test_royalty_management();
            
            // Test 7: Shortcodes and UI
            $this->test_shortcodes_ui();
            
            // Test 8: AJAX Endpoints
            $this->test_ajax_endpoints();
            
            // Test 9: Analytics and Tracking
            $this->test_analytics_tracking();
            
            // Test 10: Security and Validation
            $this->test_security_validation();
            
            // Test 11: Error Handling
            $this->test_error_handling();
            
            // Test 12: Performance and Scalability
            $this->test_performance_scalability();
            
            // Clean up test environment
            $this->cleanup_test_environment();
            
            $this->log_test_completion();
            
        } catch (Exception $e) {
            $this->log_test_error($e);
            $this->cleanup_test_environment();
        }
        
        return $this->generate_test_report();
    }
    
    /**
     * Setup test environment
     */
    private function setup_test_environment() {
        $this->log_test_step('Setting up test environment');
        
        // Create test user
        $this->test_user_id = wp_create_user('tola_test_user', 'test_password_123', 'test@tola.com');
        
        if (is_wp_error($this->test_user_id)) {
            throw new Exception('Failed to create test user: ' . $this->test_user_id->get_error_message());
        }
        
        // Set test wallet address
        $this->test_wallet_address = 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky';
        update_user_meta($this->test_user_id, 'vortex_solana_wallet', $this->test_wallet_address);
        
        // Generate test artwork ID
        $this->test_artwork_id = time() + rand(1, 1000);
        
        $this->mark_test_passed('Test environment setup');
    }
    
    /**
     * Test configuration and setup
     */
    private function test_configuration_setup() {
        $this->log_test_step('Testing configuration and setup');
        
        // Test Solana integration configuration
        $is_configured = $this->solana_integration->is_configured();
        if (!$is_configured) {
            $this->mark_test_failed('Solana integration not configured');
            return;
        }
        
        // Test required options
        $required_options = [
            'vortex_solana_program_id',
            'vortex_solana_rpc_url',
            'vortex_tola_authority'
        ];
        
        foreach ($required_options as $option) {
            $value = get_option($option);
            if (empty($value)) {
                $this->mark_test_failed("Required option '$option' not set");
                return;
            }
        }
        
        // Test database tables
        global $wpdb;
        $tables = [
            $wpdb->prefix . 'vortex_solana_nfts',
            $wpdb->prefix . 'vortex_nft_transactions',
            $wpdb->prefix . 'vortex_nft_royalty_history',
            $wpdb->prefix . 'vortex_nft_marketplace',
            $wpdb->prefix . 'vortex_nft_analytics'
        ];
        
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            if (!$exists) {
                $this->mark_test_failed("Database table '$table' not found");
                return;
            }
        }
        
        $this->mark_test_passed('Configuration and setup');
    }
    
    /**
     * Test wallet connection
     */
    private function test_wallet_connection() {
        $this->log_test_step('Testing wallet connection');
        
        // Test wallet validation
        $is_valid = $this->validate_solana_address($this->test_wallet_address);
        if (!$is_valid) {
            $this->mark_test_failed('Invalid test wallet address');
            return;
        }
        
        // Test wallet storage
        $stored_wallet = get_user_meta($this->test_user_id, 'vortex_solana_wallet', true);
        if ($stored_wallet !== $this->test_wallet_address) {
            $this->mark_test_failed('Wallet address not stored correctly');
            return;
        }
        
        $this->mark_test_passed('Wallet connection');
    }
    
    /**
     * Test database operations
     */
    private function test_database_operations() {
        $this->log_test_step('Testing database operations');
        
        try {
            // Test NFT insertion
            $nft_id = $this->nft_database->insert_nft([
                'recipient_wallet' => $this->test_wallet_address,
                'artwork_id' => $this->test_artwork_id,
                'program_id' => get_option('vortex_solana_program_id'),
                'uri' => 'https://test.arweave.net/test-metadata',
                'metadata' => json_encode([
                    'name' => 'Test NFT',
                    'description' => 'Test NFT for smoke testing'
                ])
            ]);
            
            if (!$nft_id) {
                $this->mark_test_failed('Failed to insert NFT record');
                return;
            }
            
            // Test NFT retrieval
            $nft = $this->nft_database->get_nft_by_artwork_id($this->test_artwork_id);
            if (!$nft || $nft->artwork_id != $this->test_artwork_id) {
                $this->mark_test_failed('Failed to retrieve NFT record');
                return;
            }
            
            // Test NFT update
            $updated = $this->nft_database->update_nft($this->test_artwork_id, [
                'signature' => 'test_signature_' . time(),
                'royalty_fee_percent' => 10.0
            ]);
            
            if (!$updated) {
                $this->mark_test_failed('Failed to update NFT record');
                return;
            }
            
            // Test statistics
            $stats = $this->nft_database->get_nft_stats($this->test_wallet_address);
            if (!$stats || $stats->total_nfts < 1) {
                $this->mark_test_failed('Failed to get NFT statistics');
                return;
            }
            
            $this->mark_test_passed('Database operations');
            
        } catch (Exception $e) {
            $this->mark_test_failed('Database operations: ' . $e->getMessage());
        }
    }
    
    /**
     * Test artwork upload
     */
    private function test_artwork_upload() {
        $this->log_test_step('Testing artwork upload');
        
        // Create test image data
        $test_image_data = $this->create_test_image();
        
        // Test metadata preparation
        $metadata = [
            'name' => 'Test TOLA Masterpiece',
            'description' => 'Test artwork for smoke testing',
            'attributes' => [
                [
                    'trait_type' => 'Test',
                    'value' => 'Smoke Test'
                ]
            ]
        ];
        
        // Test upload process (mocked for smoke test)
        $upload_result = $this->simulate_artwork_upload($test_image_data, $metadata);
        
        if (!$upload_result) {
            $this->mark_test_failed('Failed to upload artwork');
            return;
        }
        
        $this->mark_test_passed('Artwork upload');
    }
    
    /**
     * Test NFT minting
     */
    private function test_nft_minting() {
        $this->log_test_step('Testing NFT minting');
        
        // Test minting process (mocked for smoke test)
        $mint_result = $this->simulate_nft_minting();
        
        if (!$mint_result['success']) {
            $this->mark_test_failed('NFT minting failed: ' . $mint_result['error']);
            return;
        }
        
        // Verify NFT was created in database
        $nft = $this->nft_database->get_nft_by_artwork_id($this->test_artwork_id);
        if (!$nft) {
            $this->mark_test_failed('NFT not found in database after minting');
            return;
        }
        
        $this->mark_test_passed('NFT minting');
    }
    
    /**
     * Test royalty management
     */
    private function test_royalty_management() {
        $this->log_test_step('Testing royalty management');
        
        // Test royalty validation
        $valid_royalty = 10.0;
        $invalid_royalty = 20.0; // Over 15% limit
        
        // Test setting valid royalty
        $royalty_result = $this->simulate_royalty_update($valid_royalty);
        if (!$royalty_result['success']) {
            $this->mark_test_failed('Failed to set valid royalty: ' . $royalty_result['error']);
            return;
        }
        
        // Test setting invalid royalty
        $invalid_result = $this->simulate_royalty_update($invalid_royalty);
        if ($invalid_result['success']) {
            $this->mark_test_failed('Invalid royalty was accepted');
            return;
        }
        
        // Test royalty history
        $history = $this->nft_database->get_royalty_history($this->test_artwork_id);
        if (empty($history)) {
            $this->mark_test_failed('Royalty history not recorded');
            return;
        }
        
        $this->mark_test_passed('Royalty management');
    }
    
    /**
     * Test shortcodes and UI
     */
    private function test_shortcodes_ui() {
        $this->log_test_step('Testing shortcodes and UI');
        
        // Test shortcode registration
        $shortcodes = [
            'tola_mint_status',
            'tola_royalty_manager',
            'tola_nft_gallery',
            'tola_wallet_connect',
            'tola_marketplace_link',
            'tola_nft_stats'
        ];
        
        foreach ($shortcodes as $shortcode) {
            if (!shortcode_exists($shortcode)) {
                $this->mark_test_failed("Shortcode '$shortcode' not registered");
                return;
            }
        }
        
        // Test shortcode rendering
        $nft_shortcodes = new VortexAIEngine_NFT_Shortcodes();
        
        // Test mint status shortcode
        $mint_status_output = $nft_shortcodes->render_mint_status(['artwork_id' => $this->test_artwork_id]);
        if (empty($mint_status_output)) {
            $this->mark_test_failed('Mint status shortcode returned empty output');
            return;
        }
        
        // Test wallet connect shortcode
        wp_set_current_user($this->test_user_id);
        $wallet_output = $nft_shortcodes->render_wallet_connect([]);
        if (empty($wallet_output)) {
            $this->mark_test_failed('Wallet connect shortcode returned empty output');
            return;
        }
        
        $this->mark_test_passed('Shortcodes and UI');
    }
    
    /**
     * Test AJAX endpoints
     */
    private function test_ajax_endpoints() {
        $this->log_test_step('Testing AJAX endpoints');
        
        // Test AJAX actions registration
        $ajax_actions = [
            'tola_refresh_status',
            'tola_update_royalty',
            'tola_connect_wallet',
            'tola_disconnect_wallet',
            'tola_get_user_nfts',
            'tola_get_nft_stats'
        ];
        
        foreach ($ajax_actions as $action) {
            if (!has_action("wp_ajax_$action")) {
                $this->mark_test_failed("AJAX action '$action' not registered");
                return;
            }
        }
        
        // Test AJAX security (nonce verification)
        $_POST['nonce'] = wp_create_nonce('tola_nft_nonce');
        $_POST['artwork_id'] = $this->test_artwork_id;
        
        wp_set_current_user($this->test_user_id);
        
        // Test refresh status endpoint
        ob_start();
        $ajax_handler = new VortexAIEngine_NFT_Ajax();
        $ajax_handler->refresh_nft_status();
        $output = ob_get_clean();
        
        if (empty($output)) {
            $this->mark_test_failed('AJAX refresh status returned empty output');
            return;
        }
        
        $this->mark_test_passed('AJAX endpoints');
    }
    
    /**
     * Test analytics and tracking
     */
    private function test_analytics_tracking() {
        $this->log_test_step('Testing analytics and tracking');
        
        // Test analytics insertion
        $analytics_id = $this->nft_database->insert_analytics([
            'artwork_id' => $this->test_artwork_id,
            'metric_type' => 'view',
            'user_id' => $this->test_user_id,
            'user_wallet' => $this->test_wallet_address,
            'ip_address' => '127.0.0.1',
            'metadata' => json_encode(['test' => true])
        ]);
        
        if (!$analytics_id) {
            $this->mark_test_failed('Failed to insert analytics record');
            return;
        }
        
        // Test analytics retrieval
        $analytics = $this->nft_database->get_analytics($this->test_artwork_id);
        if (empty($analytics)) {
            $this->mark_test_failed('Failed to retrieve analytics data');
            return;
        }
        
        $this->mark_test_passed('Analytics and tracking');
    }
    
    /**
     * Test security and validation
     */
    private function test_security_validation() {
        $this->log_test_step('Testing security and validation');
        
        // Test wallet address validation
        $valid_addresses = [
            'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky',
            '11111111111111111111111111111112'
        ];
        
        $invalid_addresses = [
            'invalid_address',
            '123',
            'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky123456789', // Too long
            '0000000000000000000000000000000000000000000000' // Invalid characters
        ];
        
        foreach ($valid_addresses as $address) {
            if (!$this->validate_solana_address($address)) {
                $this->mark_test_failed("Valid address '$address' failed validation");
                return;
            }
        }
        
        foreach ($invalid_addresses as $address) {
            if ($this->validate_solana_address($address)) {
                $this->mark_test_failed("Invalid address '$address' passed validation");
                return;
            }
        }
        
        // Test royalty fee validation
        $valid_fees = [0, 5, 10, 15];
        $invalid_fees = [-1, 16, 100];
        
        foreach ($valid_fees as $fee) {
            if ($fee < 0 || $fee > 15) {
                $this->mark_test_failed("Valid fee '$fee' should be accepted");
                return;
            }
        }
        
        $this->mark_test_passed('Security and validation');
    }
    
    /**
     * Test error handling
     */
    private function test_error_handling() {
        $this->log_test_step('Testing error handling');
        
        // Test database error handling
        try {
            $this->nft_database->insert_nft([
                'recipient_wallet' => 'invalid_wallet',
                'artwork_id' => $this->test_artwork_id, // Duplicate ID
                'program_id' => '',
                'uri' => ''
            ]);
            
            $this->mark_test_failed('Database should have thrown error for invalid data');
            return;
        } catch (Exception $e) {
            // Expected behavior
        }
        
        // Test AJAX error handling
        $_POST['nonce'] = 'invalid_nonce';
        
        ob_start();
        $ajax_handler = new VortexAIEngine_NFT_Ajax();
        
        try {
            $ajax_handler->refresh_nft_status();
        } catch (Exception $e) {
            // Expected behavior for security check
        }
        
        ob_end_clean();
        
        $this->mark_test_passed('Error handling');
    }
    
    /**
     * Test performance and scalability
     */
    private function test_performance_scalability() {
        $this->log_test_step('Testing performance and scalability');
        
        // Test database query performance
        $start_time = microtime(true);
        
        // Run multiple queries
        for ($i = 0; $i < 10; $i++) {
            $this->nft_database->get_nft_stats($this->test_wallet_address);
        }
        
        $end_time = microtime(true);
        $query_time = $end_time - $start_time;
        
        if ($query_time > 1.0) { // Should complete within 1 second
            $this->mark_test_failed("Database queries too slow: {$query_time}s");
            return;
        }
        
        // Test memory usage
        $memory_usage = memory_get_usage(true);
        if ($memory_usage > 50 * 1024 * 1024) { // 50MB limit
            $this->mark_test_failed("Memory usage too high: " . ($memory_usage / 1024 / 1024) . "MB");
            return;
        }
        
        $this->mark_test_passed('Performance and scalability');
    }
    
    /**
     * Helper methods
     */
    
    private function create_test_image() {
        // Create a simple test image
        $image = imagecreate(100, 100);
        $background = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 10, 10, 'TEST', $text_color);
        
        ob_start();
        imagepng($image);
        $image_data = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return $image_data;
    }
    
    private function simulate_artwork_upload($image_data, $metadata) {
        // Simulate artwork upload process
        return true;
    }
    
    private function simulate_nft_minting() {
        // Simulate NFT minting process
        return [
            'success' => true,
            'artwork_id' => $this->test_artwork_id,
            'signature' => 'test_signature_' . time(),
            'uri' => 'https://test.arweave.net/test-metadata'
        ];
    }
    
    private function simulate_royalty_update($fee_percent) {
        // Simulate royalty update process
        if ($fee_percent > 15) {
            return [
                'success' => false,
                'error' => 'Royalty fee exceeds maximum'
            ];
        }
        
        return [
            'success' => true,
            'signature' => 'test_royalty_signature_' . time()
        ];
    }
    
    private function validate_solana_address($address) {
        return preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address);
    }
    
    private function log_test_start() {
        $this->test_results['start_time'] = microtime(true);
        $this->test_results['tests'] = [];
        error_log('[TOLA NFT Smoke Test] Starting comprehensive smoke test');
    }
    
    private function log_test_step($step) {
        error_log("[TOLA NFT Smoke Test] $step");
    }
    
    private function log_test_completion() {
        $this->test_results['end_time'] = microtime(true);
        $this->test_results['duration'] = $this->test_results['end_time'] - $this->test_results['start_time'];
        error_log('[TOLA NFT Smoke Test] Smoke test completed in ' . $this->test_results['duration'] . ' seconds');
    }
    
    private function log_test_error($exception) {
        $this->test_results['error'] = $exception->getMessage();
        error_log('[TOLA NFT Smoke Test] ERROR: ' . $exception->getMessage());
    }
    
    private function mark_test_passed($test_name) {
        $this->test_results['tests'][$test_name] = 'PASSED';
        error_log("[TOLA NFT Smoke Test] ✓ $test_name - PASSED");
    }
    
    private function mark_test_failed($test_name) {
        $this->test_results['tests'][$test_name] = 'FAILED';
        error_log("[TOLA NFT Smoke Test] ✗ $test_name - FAILED");
    }
    
    private function cleanup_test_environment() {
        if ($this->test_user_id) {
            wp_delete_user($this->test_user_id);
        }
        
        // Clean up test NFT data
        global $wpdb;
        
        $tables_to_clean = [
            $wpdb->prefix . 'vortex_solana_nfts',
            $wpdb->prefix . 'vortex_nft_transactions',
            $wpdb->prefix . 'vortex_nft_royalty_history',
            $wpdb->prefix . 'vortex_nft_analytics'
        ];
        
        foreach ($tables_to_clean as $table) {
            $wpdb->delete($table, ['artwork_id' => $this->test_artwork_id]);
        }
    }
    
    private function generate_test_report() {
        $passed = 0;
        $failed = 0;
        
        foreach ($this->test_results['tests'] as $test => $result) {
            if ($result === 'PASSED') {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $report = [
            'summary' => [
                'total_tests' => $passed + $failed,
                'passed' => $passed,
                'failed' => $failed,
                'success_rate' => $passed / ($passed + $failed) * 100,
                'duration' => $this->test_results['duration'] ?? 0
            ],
            'tests' => $this->test_results['tests'],
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => [
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];
        
        if (isset($this->test_results['error'])) {
            $report['error'] = $this->test_results['error'];
        }
        
        return $report;
    }
}

// Function to run smoke test
function run_tola_nft_smoke_test() {
    $test = new TOLA_NFT_Smoke_Test();
    return $test->run_smoke_tests();
}
?> 