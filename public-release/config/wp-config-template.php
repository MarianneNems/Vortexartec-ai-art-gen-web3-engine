<?php
// WordPress Configuration Template
// Copy this file to wp-config.php and update with your values

// Database Configuration
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASSWORD', 'your_database_password');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Authentication Keys and Salts
// Generate your own keys at: https://api.wordpress.org/secret-key/1.1/salt/
require_once('wp-salt.php');

// WordPress Settings
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_MEMORY_LIMIT', '256M');

// Security Settings
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);

// VORTEX AI Engine Settings
define('VORTEX_AI_ENGINE_VERSION', '2.2.0');
define('VORTEX_AI_ENGINE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_AI_ENGINE_PLUGIN_PATH', plugin_dir_path(__FILE__));

// That's all, stop editing!
require_once(ABSPATH . 'wp-settings.php'); 