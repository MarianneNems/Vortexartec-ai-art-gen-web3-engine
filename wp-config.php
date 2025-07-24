<?php
// Disable WordPress caching to prevent Redis connection issues
define( 'WP_CACHE', false );

/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ============================================================================
// VORTEX AI ENGINE - ENHANCED CONFIGURATION
// ============================================================================
// Optimized settings for Vortex AI Engine with Solana blockchain integration
// and comprehensive artist journey management

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'xjrhkufwbn');
/** MySQL database username */
define('DB_USER', 'xjrhkufwbn');
/** MySQL database password */
define('DB_PASSWORD', '5W2X93Dx3z');
/** MySQL hostname */
define('DB_HOST', 'localhost');
/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');
/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 */
require_once('wp-salt.php');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpww_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('FS_METHOD', 'direct');

define('FS_CHMOD_DIR', (0775 & ~umask()));
define('FS_CHMOD_FILE', (0664 & ~umask()));

// ============================================================================
// VORTEX AI ENGINE - DEBUGGING CONFIGURATION
// ============================================================================
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', true); // Enabled for Vortex AI Engine debugging

// Enhanced debugging for Vortex AI Engine
define('WP_DEBUG_LOG', true); // Enable debug logging
define('WP_DEBUG_DISPLAY', false); // Disable display for production
define('WP_DEBUG_DISPLAY_FOR_ADMINS', false); // Disabled for security

// WooCommerce Debug Settings (optimized for Vortex AI Engine)
if (!defined('WC_DEBUG')) define('WC_DEBUG', true);
if (!defined('WC_DEBUG_LOG')) define('WC_DEBUG_LOG', true);
if (!defined('WC_DEBUG_DISPLAY')) define('WC_DEBUG_DISPLAY', false);

// ============================================================================
// VORTEX AI ENGINE - PERFORMANCE OPTIMIZATION
// ============================================================================
// Enhanced memory and performance settings for blockchain operations
define('WP_MEMORY_LIMIT', '512M'); // Increased for Solana operations
define('WP_MAX_MEMORY_LIMIT', '1024M'); // Maximum memory limit

// Disable WordPress cron for better performance (use system cron instead)
define('DISABLE_WP_CRON', true);

// ============================================================================
// VORTEX AI ENGINE - SECURITY CONFIGURATION
// ============================================================================
// Enhanced security settings
define('DISALLOW_FILE_EDIT', true); // Disable file editing in admin
define('DISALLOW_FILE_MODS', false); // Allow plugin/theme updates
define('FORCE_SSL_ADMIN', true); // Force SSL for admin
define('AUTOMATIC_UPDATER_DISABLED', false); // Allow automatic updates

// ============================================================================
// VORTEX AI ENGINE - SOLANA BLOCKCHAIN CONFIGURATION
// ============================================================================
// Solana blockchain integration settings
if (!defined('VORTEX_SOLANA_DEVNET_RPC')) {
    define('VORTEX_SOLANA_DEVNET_RPC', 'https://api.devnet.solana.com');
}
if (!defined('VORTEX_SOLANA_TESTNET_RPC')) {
    define('VORTEX_SOLANA_TESTNET_RPC', 'https://api.testnet.solana.com');
}
if (!defined('VORTEX_SOLANA_MAINNET_RPC')) {
    define('VORTEX_SOLANA_MAINNET_RPC', 'https://api.mainnet-beta.solana.com');
}

// Solana metrics configuration
if (!defined('VORTEX_SOLANA_METRICS_CONFIG')) {
    define('VORTEX_SOLANA_METRICS_CONFIG', 'host=https://metrics.solana.com:8086,db=devnet,u=scratch_writer,p=topsecret');
}

// ============================================================================
// VORTEX AI ENGINE - TOLA TOKEN CONFIGURATION
// ============================================================================
// TOLA token settings
if (!defined('VORTEX_TOLA_TOKEN_ADDRESS')) {
    define('VORTEX_TOLA_TOKEN_ADDRESS', 'TOLA1234567890123456789012345678901234567890');
}
if (!defined('VORTEX_TOLA_TOTAL_SUPPLY')) {
    define('VORTEX_TOLA_TOTAL_SUPPLY', '1000000000'); // 1 billion TOLA
}

// ============================================================================
// VORTEX AI ENGINE - ARTIST JOURNEY CONFIGURATION
// ============================================================================
// Artist journey tracking settings
if (!defined('VORTEX_ARTIST_JOURNEY_ENABLED')) {
    define('VORTEX_ARTIST_JOURNEY_ENABLED', true);
}
if (!defined('VORTEX_ACTIVITY_LOGGING_ENABLED')) {
    define('VORTEX_ACTIVITY_LOGGING_ENABLED', true);
}

// ============================================================================
// VORTEX AI ENGINE - CACHE AND OPTIMIZATION
// ============================================================================
// Cache settings for better performance
if (!defined('VORTEX_CACHE_ENABLED')) {
    define('VORTEX_CACHE_ENABLED', true);
}
if (!defined('VORTEX_CACHE_DURATION')) {
    define('VORTEX_CACHE_DURATION', 3600); // 1 hour
}

// ============================================================================
// VORTEX AI ENGINE - ERROR HANDLING
// ============================================================================
// Enhanced error handling for blockchain operations
if (!defined('VORTEX_ERROR_REPORTING')) {
    define('VORTEX_ERROR_REPORTING', E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
if (!defined('VORTEX_LOG_ERRORS')) {
    define('VORTEX_LOG_ERRORS', true);
}

// ============================================================================
// VORTEX AI ENGINE - DEVELOPMENT MODE
// ============================================================================
// Development mode settings
if (!defined('VORTEX_DEV_MODE')) {
    define('VORTEX_DEV_MODE', false); // Set to true for development
}

// ============================================================================
// VORTEX AI ENGINE - API RATE LIMITING
// ============================================================================
// Rate limiting for Solana API calls
if (!defined('VORTEX_API_RATE_LIMIT')) {
    define('VORTEX_API_RATE_LIMIT', 100); // Requests per minute
}

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
        define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php'); 