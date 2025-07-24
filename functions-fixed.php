<?php
/**
 * Hawat Theme Functions
 * 
 * @package Hawat
 * @version 1.0.0
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants for configuration
define( 'HAWAT_DEFAULT_CONTENT_WIDTH', 1300 );
define( 'HAWAT_MIN_PHP_VERSION', '7.4' );
define( 'HAWAT_MIN_WP_VERSION', '5.0' );

if ( ! class_exists( 'Hawat_Handler' ) ) {
    /**
     * Main theme class with configuration
     * 
     * @since 1.0.0
     */
    class Hawat_Handler {
        private static $instance;
        private $modules_loaded = false;

        public function __construct() {
            // Check system requirements
            $this->check_requirements();

            // Include required files
            $this->include_required_files();

            // Initialize theme
            $this->init_theme();
        }

        /**
         * Check system requirements
         * 
         * @since 1.0.0
         */
        private function check_requirements() {
            // Check PHP version
            if ( version_compare( PHP_VERSION, HAWAT_MIN_PHP_VERSION, '<' ) ) {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-error"><p>';
                    printf(
                        esc_html__( 'Hawat theme requires PHP version %s or higher. Current version: %s', 'hawat' ),
                        HAWAT_MIN_PHP_VERSION,
                        PHP_VERSION
                    );
                    echo '</p></div>';
                });
                return;
            }

            // Check WordPress version
            if ( version_compare( get_bloginfo( 'version' ), HAWAT_MIN_WP_VERSION, '<' ) ) {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-error"><p>';
                    printf(
                        esc_html__( 'Hawat theme requires WordPress version %s or higher. Current version: %s', 'hawat' ),
                        HAWAT_MIN_WP_VERSION,
                        get_bloginfo( 'version' )
                    );
                    echo '</p></div>';
                });
                return;
            }
        }

        /**
         * Include required files
         * 
         * @since 1.0.0
         */
        private function include_required_files() {
            $required_files = array(
                get_template_directory() . '/constants.php',
                HAWAT_ROOT_DIR . '/helpers/helper.php'
            );

            foreach ( $required_files as $file ) {
                if ( file_exists( $file ) && is_readable( $file ) ) {
                    require_once $file;
                } else {
                    error_log( 'Hawat theme: Required file not found or not readable: ' . $file );
                }
            }
        }

        /**
         * Initialize theme
         * 
         * @since 1.0.0
         */
        private function init_theme() {
            // Include theme's style and inline style
            add_action( 'wp_enqueue_scripts', array( $this, 'include_css_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_style' ) );

            // Include theme's script and localize theme's main js script
            add_action( 'wp_enqueue_scripts', array( $this, 'include_js_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'localize_js_scripts' ) );

            // Include theme's 3rd party plugins styles
            add_action( 'hawat_action_before_main_css', array( $this, 'include_plugins_styles' ) );

            // Include theme's 3rd party plugins scripts
            add_action( 'hawat_action_before_main_js', array( $this, 'include_plugins_scripts' ) );

            // Add pingback header
            add_action( 'wp_head', array( $this, 'add_pingback_header' ), 1 );

            // Add resource hints for performance
            add_action( 'wp_head', array( $this, 'add_resource_hints' ), 1 );

            // Include theme's skip link
            add_action( 'hawat_action_after_body_tag_open', array( $this, 'add_skip_link' ), 5 );

            // Include theme's Google fonts
            add_action( 'hawat_action_before_main_css', array( $this, 'include_google_fonts' ) );

            // Add theme's supports feature
            add_action( 'after_setup_theme', array( $this, 'set_theme_support' ) );

            // Enqueue supplemental block editor styles
            add_action( 'enqueue_block_editor_assets', array( $this, 'editor_customizer_styles' ) );

            // Add theme's body classes
            add_filter( 'body_class', array( $this, 'add_body_classes' ) );

            // Include modules (lazy loading)
            add_action( 'after_setup_theme', array( $this, 'include_modules' ) );
        }

        /**
         * Get singleton instance
         * 
         * @return Hawat_Handler
         * @since 1.0.0
         */
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Include CSS scripts
         * 
         * @since 1.0.0
         */
        public function include_css_scripts() {
            // CSS dependency variable
            $main_css_dependency = apply_filters( 'hawat_filter_main_css_dependency', array( 'swiper' ) );

            // Hook to include additional scripts before theme's main style
            do_action( 'hawat_action_before_main_css' );

            // Enqueue theme's main style
            wp_enqueue_style( 'hawat-grid', HAWAT_ASSETS_CSS_ROOT . '/grid.min.css' );

            // Enqueue theme's main style
            wp_enqueue_style( 'hawat-main', HAWAT_ASSETS_CSS_ROOT . '/main.min.css', $main_css_dependency );

            // Enqueue theme's style
            wp_enqueue_style( 'hawat-style', HAWAT_ROOT . '/style.css' );

            // Hook to include additional scripts after theme's main style
            do_action( 'hawat_action_after_main_css' );
        }

        /**
         * Add inline style
         * 
         * @since 1.0.0
         */
        public function add_inline_style() {
            $style = apply_filters( 'hawat_filter_add_inline_style', '' );

            if ( ! empty( $style ) && is_string( $style ) ) {
                wp_add_inline_style( 'hawat-style', wp_strip_all_tags( $style ) );
            }
        }

        /**
         * Include JS scripts
         * 
         * @since 1.0.0
         */
        public function include_js_scripts() {
            // JS dependency variable
            $main_js_dependency = apply_filters( 'hawat_filter_main_js_dependency', array( 'jquery' ) );

            // Hook to include additional scripts before theme's main script
            do_action( 'hawat_action_before_main_js', $main_js_dependency );

            // Enqueue theme's main script
            wp_enqueue_script( 'hawat-main-js', HAWAT_ASSETS_JS_ROOT . '/main.min.js', $main_js_dependency, false, true );

            // Hook to include additional scripts after theme's main script
            do_action( 'hawat_action_after_main_js' );

            // Include comment reply script
            if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
                wp_enqueue_script( 'comment-reply' );
            }
        }

        /**
         * Localize JS scripts
         * 
         * @since 1.0.0
         */
        public function localize_js_scripts() {
            $global = apply_filters(
                'hawat_filter_localize_main_js',
                array(
                    'adminBarHeight' => is_admin_bar_showing() ? 32 : 0,
                    'iconArrowLeft'  => wp_kses_post( hawat_get_svg_icon( 'slider-arrow-left' ) ),
                    'iconArrowRight' => wp_kses_post( hawat_get_svg_icon( 'slider-arrow-right' ) ),
                    'iconClose'      => wp_kses_post( hawat_get_svg_icon( 'close' ) ),
                    'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                    'nonce'          => wp_create_nonce( 'hawat_ajax_nonce' ),
                )
            );

            wp_localize_script(
                'hawat-main-js',
                'qodefGlobal',
                array(
                    'vars' => $global,
                )
            );
        }

        /**
         * Include plugins styles
         * 
         * @since 1.0.0
         */
        public function include_plugins_styles() {
            // Enqueue 3rd party plugins style
            wp_enqueue_style( 'swiper', HAWAT_ASSETS_ROOT . '/plugins/swiper/swiper.min.css' );
        }

        /**
         * Include plugins scripts
         * 
         * @since 1.0.0
         */
        public function include_plugins_scripts() {
            // JS dependency variables
            $js_3rd_party_dependency = apply_filters( 'hawat_filter_js_3rd_party_dependency', 'jquery' );

            // Enqueue 3rd party plugins script
            wp_enqueue_script( 'swiper', HAWAT_ASSETS_ROOT . '/plugins/swiper/swiper.min.js', array( $js_3rd_party_dependency ), false, true );
            wp_enqueue_script( 'glitch', HAWAT_ASSETS_ROOT . '/plugins/glitch/glitch.js', array( $js_3rd_party_dependency ), false, true );
        }

        /**
         * Add pingback header
         * 
         * @since 1.0.0
         */
        public function add_pingback_header() {
            if ( is_singular() && pings_open( get_queried_object() ) ) { ?>
                <link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>">
                <?php
            }
        }

        /**
         * Add resource hints for performance
         * 
         * @since 1.0.0
         */
        public function add_resource_hints() {
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        }

        /**
         * Add skip link
         * 
         * @since 1.0.0
         */
        public function add_skip_link() {
            echo '<a class="skip-link screen-reader-text" href="#qodef-page-content">' . esc_html__( 'Skip to the content', 'hawat' ) . '</a>';
        }

        /**
         * Include Google fonts
         * 
         * @since 1.0.0
         */
        public function include_google_fonts() {
            $font_subset_array = array(
                'latin-ext',
            );

            $font_weight_array = array(
                '400',
                '500',
            );

            $default_font_family = array(
                'Orbitron',
                'Poppins',
            );

            $font_weight_str = implode( ',', array_unique( apply_filters( 'hawat_filter_google_fonts_weight_list', $font_weight_array ) ) );
            $font_subset_str = implode( ',', array_unique( apply_filters( 'hawat_filter_google_fonts_subset_list', $font_subset_array ) ) );
            $fonts_array     = apply_filters( 'hawat_filter_google_fonts_list', $default_font_family );

            if ( ! empty( $fonts_array ) && is_array( $fonts_array ) ) {
                $modified_default_font_family = array();

                foreach ( $fonts_array as $font ) {
                    if ( is_string( $font ) && ! empty( $font ) ) {
                        $modified_default_font_family[] = sanitize_text_field( $font ) . ':' . $font_weight_str;
                    }
                }

                if ( ! empty( $modified_default_font_family ) ) {
                    $default_font_string = implode( '|', $modified_default_font_family );

                    $fonts_full_list_args = array(
                        'family'  => urlencode( $default_font_string ),
                        'subset'  => urlencode( $font_subset_str ),
                        'display' => 'swap',
                    );

                    $google_fonts_url = add_query_arg( $fonts_full_list_args, 'https://fonts.googleapis.com/css' );
                    wp_enqueue_style( 'hawat-google-fonts', esc_url_raw( $google_fonts_url ), array(), '1.0.0' );
                }
            }
        }

        /**
         * Set theme support
         * 
         * @since 1.0.0
         */
        public function set_theme_support() {
            // Make theme available for translation
            load_theme_textdomain( 'hawat', HAWAT_ROOT_DIR . '/languages' );

            // Add support for feed links
            add_theme_support( 'automatic-feed-links' );

            // Add support for title tag
            add_theme_support( 'title-tag' );

            // Add support for post thumbnails
            add_theme_support( 'post-thumbnails' );

            // Add theme support for Custom Logo
            add_theme_support( 'custom-logo' );

            // Add support for full and wide align images.
            add_theme_support( 'align-wide' );

            // Set the default content width
            global $content_width;
            if ( ! isset( $content_width ) ) {
                $content_width = apply_filters( 'hawat_filter_set_content_width', HAWAT_DEFAULT_CONTENT_WIDTH );
            }

            // Add support for post formats
            add_theme_support( 'post-formats', array( 'gallery', 'video', 'audio', 'link', 'quote' ) );

            // Add theme support for editor style
            add_editor_style( HAWAT_ASSETS_CSS_ROOT . '/editor-style.min.css' );
        }

        /**
         * Editor customizer styles
         * 
         * @since 1.0.0
         */
        public function editor_customizer_styles() {
            // Include theme's Google fonts for Gutenberg editor
            $this->include_google_fonts();

            // Add editor customizer style
            wp_enqueue_style( 'hawat-editor-customizer-styles', HAWAT_ASSETS_CSS_ROOT . '/editor-customizer-style.css' );

            // Add Gutenberg blocks style
            wp_enqueue_style( 'hawat-gutenberg-blocks-style', HAWAT_INC_ROOT . '/gutenberg/assets/admin/css/gutenberg-blocks.css' );
        }

        /**
         * Add body classes
         * 
         * @param array $classes Body classes
         * @return array Modified body classes
         * @since 1.0.0
         */
        public function add_body_classes( $classes ) {
            if ( ! is_array( $classes ) ) {
                $classes = array();
            }

            $current_theme = wp_get_theme();
            $theme_name    = esc_attr( str_replace( ' ', '-', strtolower( $current_theme->get( 'Name' ) ) ) );
            $theme_version = esc_attr( $current_theme->get( 'Version' ) );

            // Check is child theme activated
            if ( $current_theme->parent() ) {
                // Add child theme version
                $child_theme_suffix = strpos( $theme_name, 'child' ) === false ? '-child' : '';

                $classes[] = $theme_name . $child_theme_suffix . '-' . $theme_version;

                // Get main theme variables
                $current_theme = $current_theme->parent();
                $theme_name    = esc_attr( str_replace( ' ', '-', strtolower( $current_theme->get( 'Name' ) ) ) );
                $theme_version = esc_attr( $current_theme->get( 'Version' ) );
            }

            if ( $current_theme->exists() ) {
                $classes[] = $theme_name . '-' . $theme_version;
            }

            // Set default grid size value
            $classes['grid_size'] = 'qodef-content-grid-1100';

            return apply_filters( 'hawat_filter_add_body_classes', $classes );
        }

        /**
         * Include modules
         * 
         * @since 1.0.0
         */
        public function include_modules() {
            if ( $this->modules_loaded ) {
                return;
            }

            // Hook to include additional files before modules inclusion
            do_action( 'hawat_action_before_include_modules' );

            $modules_path = HAWAT_INC_ROOT_DIR . '/*/include.php';
            $modules = glob( $modules_path );

            if ( $modules && is_array( $modules ) ) {
                foreach ( $modules as $module ) {
                    if ( file_exists( $module ) && is_readable( $module ) && is_file( $module ) ) {
                        try {
                            include_once $module;
                        } catch ( Exception $e ) {
                            error_log( 'Hawat theme module loading error: ' . $e->getMessage() . ' in file: ' . $module );
                        }
                    } else {
                        error_log( 'Hawat theme: Module file not found or not readable: ' . $module );
                    }
                }
            }

            // Hook to include additional files after modules inclusion
            do_action( 'hawat_action_after_include_modules' );

            $this->modules_loaded = true;
        }
    }

    // Initialize the theme
    Hawat_Handler::get_instance();
} 