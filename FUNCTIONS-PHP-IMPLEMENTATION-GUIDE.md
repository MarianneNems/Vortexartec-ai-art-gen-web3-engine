# Functions.php Implementation Guide
**Theme:** Hawat  
**File:** functions.php  
**Priority:** High  

## Overview

This guide provides step-by-step instructions to implement the security, performance, and code quality improvements identified in the audit.

## Pre-Implementation Checklist

- [ ] Backup current `functions.php` file
- [ ] Test theme in staging environment
- [ ] Verify all modules are working
- [ ] Check for any custom modifications

## Implementation Steps

### Step 1: Create Backup

```bash
# Create backup of current functions.php
cp functions.php functions.php.backup.$(date +%Y%m%d)
```

### Step 2: Add Security Constants

Add these constants at the top of `functions.php` (after the opening PHP tag):

```php
// Define constants for configuration
define( 'HAWAT_DEFAULT_CONTENT_WIDTH', 1300 );
define( 'HAWAT_MIN_PHP_VERSION', '7.4' );
define( 'HAWAT_MIN_WP_VERSION', '5.0' );
```

### Step 3: Add System Requirements Check

Add this method to the `Hawat_Handler` class:

```php
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
```

### Step 4: Improve File Inclusion Security

Replace the current `include_modules()` method with:

```php
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
```

### Step 5: Add Performance Optimizations

Add this method to the `Hawat_Handler` class:

```php
/**
 * Add resource hints for performance
 * 
 * @since 1.0.0
 */
public function add_resource_hints() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
```

### Step 6: Improve Input Validation

Update the `add_inline_style()` method:

```php
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
```

### Step 7: Enhance Security in Localized Data

Update the `localize_js_scripts()` method:

```php
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
```

### Step 8: Improve Google Fonts Security

Update the `include_google_fonts()` method:

```php
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
```

### Step 9: Update Constructor

Update the constructor to call the new methods:

```php
public function __construct() {
    // Check system requirements
    $this->check_requirements();

    // Include required files
    $this->include_required_files();

    // Initialize theme
    $this->init_theme();
}
```

### Step 10: Add Required Files Method

Add this method to the `Hawat_Handler` class:

```php
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
```

### Step 11: Add Init Theme Method

Add this method to the `Hawat_Handler` class:

```php
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
```

## Testing Checklist

After implementing the changes, test the following:

### Functionality Tests
- [ ] Theme activates without errors
- [ ] All modules load correctly
- [ ] Google Fonts display properly
- [ ] CSS and JS files load
- [ ] Gutenberg editor works
- [ ] Child theme compatibility

### Security Tests
- [ ] No PHP errors in error log
- [ ] File inclusion security works
- [ ] Input validation prevents XSS
- [ ] Nonce verification works (if AJAX)

### Performance Tests
- [ ] Page load times improved
- [ ] Google Fonts load faster
- [ ] No duplicate resource loading
- [ ] Resource hints work

### Compatibility Tests
- [ ] Works with PHP 7.4+
- [ ] Works with WordPress 5.0+
- [ ] Compatible with popular plugins
- [ ] Mobile responsive

## Rollback Plan

If issues occur, restore the backup:

```bash
# Restore backup
cp functions.php.backup.$(date +%Y%m%d) functions.php
```

## Monitoring

After deployment, monitor:

1. **Error Logs:** Check for any new errors
2. **Performance:** Monitor page load times
3. **User Feedback:** Watch for user-reported issues
4. **Analytics:** Check for any drop in traffic

## Post-Implementation

1. **Document Changes:** Update theme documentation
2. **Version Update:** Increment theme version
3. **Changelog:** Document improvements
4. **User Communication:** Inform users of improvements

## Support

If you encounter issues:

1. Check the error logs
2. Verify all files are in place
3. Test in a clean environment
4. Contact theme support if needed

## Conclusion

These improvements will significantly enhance the security, performance, and maintainability of your Hawat theme. The changes are backward compatible and follow WordPress coding standards. 