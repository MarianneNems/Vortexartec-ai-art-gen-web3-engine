<?php
/**
 * Database setup & cleanup for VortexAIEngine agreements
 *
 * @package VortexAIEngine
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_Database {
    /** @var self|null */
    private static $instance = null;

    /** Singleton */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if database connection is working
     * @return bool
     */
    public function is_database_available() {
        global $wpdb;
        
        // Suppress errors temporarily
        $wpdb->suppress_errors();
        
        // Test with a simple query
        $result = $wpdb->get_var( "SELECT 1" );
        
        // Restore error reporting
        $wpdb->suppress_errors( false );
        
        return $result === '1';
    }

    /**
     * Create or update tables on activation with proper error handling
     */
    public function create_tables() {
        global $wpdb;
        
        // Check if database is available before attempting to create tables
        if ( ! $this->is_database_available() ) {
            // Log the error
            error_log( '[VortexAI Database] Database connection not available during plugin activation' );
            
            // Add admin notice for later display
            add_option( 'vortex_db_activation_error', 'Database connection failed during plugin activation. Please check your wp-config.php settings.' );
            
            return false;
        }

        try {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $charset_collate = $wpdb->get_charset_collate();

            // User agreements table
            $user_table = $wpdb->prefix . 'vortex_user_agreements';
            $sql1 = "CREATE TABLE {$user_table} (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              user_id BIGINT UNSIGNED NOT NULL,
              agreement_type VARCHAR(50) NOT NULL,
              agreed TINYINT(1) NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY user_id (user_id),
              KEY agreement_type (agreement_type)
            ) {$charset_collate};";

            // Product agreements table
            $prod_table = $wpdb->prefix . 'vortex_product_agreements';
            $sql2 = "CREATE TABLE {$prod_table} (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              post_id BIGINT UNSIGNED NOT NULL,
              agreement_type VARCHAR(50) NOT NULL,
              agreed TINYINT(1) NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY post_id (post_id),
              KEY agreement_type (agreement_type)
            ) {$charset_collate};";

            // Create tables
            $result1 = dbDelta( $sql1 );
            $result2 = dbDelta( $sql2 );

            // Verify tables were created successfully
            if ( $this->verify_tables_exist() ) {
                // Log success
                error_log( '[VortexAI Database] Tables created successfully during plugin activation' );
                
                // Clean up any previous error notices
                delete_option( 'vortex_db_activation_error' );
                
                return true;
            } else {
                // Log failure
                error_log( '[VortexAI Database] Table creation failed during plugin activation' );
                
                // Add admin notice
                add_option( 'vortex_db_activation_error', 'Database tables could not be created. Please check database permissions.' );
                
                return false;
            }

        } catch ( Exception $e ) {
            // Log the specific error
            error_log( '[VortexAI Database] Exception during table creation: ' . $e->getMessage() );
            
            // Add admin notice
            add_option( 'vortex_db_activation_error', 'Database error during plugin activation: ' . $e->getMessage() );
            
            return false;
        }
    }

    /**
     * Verify that our tables exist
     * @return bool
     */
    public function verify_tables_exist() {
        global $wpdb;
        
        $user_table = $wpdb->prefix . 'vortex_user_agreements';
        $prod_table = $wpdb->prefix . 'vortex_product_agreements';
        
        // Check if tables exist
        $user_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $user_table ) ) === $user_table;
        $prod_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $prod_table ) ) === $prod_table;
        
        return $user_exists && $prod_exists;
    }

    /**
     * Get database status for admin dashboard
     * @return array
     */
    public function get_status() {
        $status = [
            'connection' => $this->is_database_available(),
            'tables_exist' => false,
            'tables_accessible' => false,
            'error_message' => ''
        ];

        if ( $status['connection'] ) {
            $status['tables_exist'] = $this->verify_tables_exist();
            
            if ( $status['tables_exist'] ) {
                // Test if we can actually query the tables
                global $wpdb;
                $user_table = $wpdb->prefix . 'vortex_user_agreements';
                $test_query = $wpdb->get_var( "SELECT COUNT(*) FROM {$user_table}" );
                $status['tables_accessible'] = ( $test_query !== null );
            }
        } else {
            $status['error_message'] = 'Database connection failed';
        }

        return $status;
    }

    /**
     * Display admin notice if there were database errors during activation
     */
    public static function maybe_show_activation_error() {
        $error = get_option( 'vortex_db_activation_error' );
        if ( $error ) {
            ?>
            <div class="notice notice-error">
                <p><strong>VORTEX AI Engine:</strong> <?php echo esc_html( $error ); ?></p>
                <p>Please check your WordPress database configuration and try reactivating the plugin.</p>
            </div>
            <?php
        }
    }

    /**
     * Safe method to record user agreement (with error handling)
     * @param int $user_id
     * @param string $agreement_type
     * @param bool $agreed
     * @return bool
     */
    public function record_user_agreement( $user_id, $agreement_type, $agreed ) {
        global $wpdb;
        
        if ( ! $this->is_database_available() ) {
            error_log( '[VortexAI Database] Cannot record user agreement - database unavailable' );
            return false;
        }

        $table = $wpdb->prefix . 'vortex_user_agreements';
        
        $result = $wpdb->insert(
            $table,
            [
                'user_id' => absint( $user_id ),
                'agreement_type' => sanitize_text_field( $agreement_type ),
                'agreed' => $agreed ? 1 : 0,
                'created_at' => current_time( 'mysql' )
            ],
            [ '%d', '%s', '%d', '%s' ]
        );

        if ( false === $result ) {
            error_log( '[VortexAI Database] Failed to record user agreement: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Safe method to record product agreement (with error handling)
     * @param int $post_id
     * @param string $agreement_type
     * @param bool $agreed
     * @return bool
     */
    public function record_product_agreement( $post_id, $agreement_type, $agreed ) {
        global $wpdb;
        
        if ( ! $this->is_database_available() ) {
            error_log( '[VortexAI Database] Cannot record product agreement - database unavailable' );
            return false;
        }

        $table = $wpdb->prefix . 'vortex_product_agreements';
        
        $result = $wpdb->insert(
            $table,
            [
                'post_id' => absint( $post_id ),
                'agreement_type' => sanitize_text_field( $agreement_type ),
                'agreed' => $agreed ? 1 : 0,
                'created_at' => current_time( 'mysql' )
            ],
            [ '%d', '%s', '%d', '%s' ]
        );

        if ( false === $result ) {
            error_log( '[VortexAI Database] Failed to record product agreement: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }
} 