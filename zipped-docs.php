<?php
/**
 * Plugin Name:  Zipped Docs
 * Plugin URI:   https://zunulnoor.vercel.app
 * Description:  Full documentation CMS with custom post types, categories, TOC,
 *               client-side search, and multi-product support.
 * Version:      3.0.0
 * Author:       Zun Ul Noor
 * Author URI:   https://zunulnoor.vercel.app
 * Text Domain:  zipped-docs
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

/* -----------------------------------------------------------------------
 * Constants
 * --------------------------------------------------------------------- */
define( 'ZIPPED_DOCS_VERSION',     '3.0.0' );
define( 'ZIPPED_DOCS_DIR',         plugin_dir_path( __FILE__ ) );
define( 'ZIPPED_DOCS_URL',         plugin_dir_url( __FILE__ ) );
define( 'ZIPPED_DOCS_ASSETS',      ZIPPED_DOCS_URL . 'assets/' );
define( 'ZIPPED_DOCS_TEMPLATES',   ZIPPED_DOCS_DIR . 'templates/' );
define( 'ZIPPED_DOCS_INCLUDES',    ZIPPED_DOCS_DIR . 'includes/' );

/* -----------------------------------------------------------------------
 * Safely load includes
 * --------------------------------------------------------------------- */
$zipped_docs_includes = array(
    'class-settings.php',
    'class-capabilities.php',
    'post-type.php',
    'doc-graph.php',
    'shortcode.php',
    'admin-dashboard.php',
    'admin-new-doc.php',
    'admin-categories.php',
    'admin-settings.php',
    'admin-meta-box.php',
    'import-export/interface-adapter.php',
    'import-export/class-field-mapper.php',
    'import-export/class-normalizer.php',
    'import-export/class-format-detector.php',
    'import-export/class-import-engine.php',
    'import-export/class-export-engine.php',
    'import-export/class-admin-ui.php',
);

foreach ( $zipped_docs_includes as $zipped_docs_file ) {
    $path = ZIPPED_DOCS_INCLUDES . $zipped_docs_file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

new Zipped_Docs_Import_Export_Admin();

/* -----------------------------------------------------------------------
 * Register Custom Post Type + Taxonomy
 * --------------------------------------------------------------------- */
add_action( 'init', 'zipped_docs_register_post_type' );
add_action( 'init', 'zipped_docs_register_taxonomy' );
add_action( 'init', 'zipped_docs_register_product_taxonomy' );

/* -----------------------------------------------------------------------
 * Flush rewrite rules on activation so CPT slugs work immediately
 * --------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'zipped_docs_activate' );
function zipped_docs_activate() {
    zipped_docs_register_post_type();
    zipped_docs_register_taxonomy();
    zipped_docs_register_product_taxonomy();
    zipped_docs_seed_default_terms();
    zipped_docs_seed_settings();
    zipped_docs_register_capabilities();
    update_option( 'zipped_docs_version', ZIPPED_DOCS_VERSION );
    flush_rewrite_rules();

    if ( zipped_docs_has_previous_data() ) {
        set_transient( 'zipped_docs_show_reinstall_notice', true, 300 );
    }

    set_transient( 'zipped_docs_activation_redirect', true, 30 );
}

add_action( 'admin_init', 'zipped_docs_activation_redirect_handler' );
function zipped_docs_activation_redirect_handler() {
    if ( ! get_transient( 'zipped_docs_activation_redirect' ) ) {
        return;
    }

    delete_transient( 'zipped_docs_activation_redirect' );

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core WordPress activation redirect check.
    if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
        return;
    }

    wp_safe_redirect( admin_url( 'admin.php?page=zipped-docs' ) );
    exit;
}

function zipped_docs_has_previous_data() {
    $settings = get_option( 'zipped_docs_settings', array() );
    if ( is_array( $settings ) && ! empty( $settings ) ) {
        $defaults = Zipped_Docs_Settings::get_defaults();
        foreach ( $defaults as $key => $default_val ) {
            if ( isset( $settings[ $key ] ) && $settings[ $key ] !== $default_val ) {
                return true;
            }
        }
    }
    $doc_count = wp_count_posts( 'zipped_docs_doc' );
    if ( $doc_count && ( ( $doc_count->publish ?? 0 ) > 0 || ( $doc_count->draft ?? 0 ) > 0 ) ) {
        return true;
    }
    return false;
}

register_deactivation_hook( __FILE__, 'zipped_docs_deactivate' );
function zipped_docs_deactivate() {
    flush_rewrite_rules();
}

function zipped_docs_seed_settings() {
    $existing = get_option( 'zipped_docs_settings', null );
    if ( null === $existing || false === $existing ) {
        update_option( 'zipped_docs_settings', Zipped_Docs_Settings::get_defaults() );
    } else {
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }
        $defaults = Zipped_Docs_Settings::get_defaults();
        foreach ( $defaults as $key => $value ) {
            if ( ! array_key_exists( $key, $existing ) ) {
                $existing[ $key ] = $value;
            }
        }
        update_option( 'zipped_docs_settings', $existing );
    }
    Zipped_Docs_Settings::get_instance()->reload();
}

function zipped_docs_purge_page_cache() {
    if ( ! function_exists( 'wpo_cache_flush' ) ) {
        $wpo_cache_funcs = WP_PLUGIN_DIR . '/wp-optimize/cache/file-based-page-cache-functions.php';
        if ( file_exists( $wpo_cache_funcs ) ) {
            require_once $wpo_cache_funcs;
        }
    }

    if ( function_exists( 'wpo_cache_flush' ) ) {
        wpo_cache_flush();
    }

    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        wp_cache_clear_cache();
    }
}

add_action( 'zipped_docs_settings_saved', 'zipped_docs_clear_settings_cache' );
function zipped_docs_clear_settings_cache() {
    wp_cache_delete( Zipped_Docs_Settings::OPTION_NAME, 'options' );
    zipped_docs_purge_page_cache();
}

add_action( 'admin_init', 'zipped_docs_version_upgrade' );
function zipped_docs_version_upgrade() {
    $stored = get_option( 'zipped_docs_version', '' );
    if ( $stored === ZIPPED_DOCS_VERSION ) {
        return;
    }

    if ( ! post_type_exists( 'zipped_docs_doc' ) ) {
        zipped_docs_register_post_type();
    }
    if ( ! taxonomy_exists( 'zipped_docs_category' ) ) {
        zipped_docs_register_taxonomy();
    }
    if ( ! taxonomy_exists( 'zipped_docs_product' ) ) {
        zipped_docs_register_product_taxonomy();
    }

    zipped_docs_seed_default_terms();
    zipped_docs_seed_settings();
    zipped_docs_register_capabilities();

    $version_map = array(
        '1.0.0' => 'zipped_docs_upgrade_100_to_200',
    );

    foreach ( $version_map as $version => $callback ) {
        if ( version_compare( $stored, $version, '<' ) && function_exists( $callback ) ) {
            call_user_func( $callback );
        }
    }

    zipped_docs_migrate_products_to_categories();

    update_option( 'zipped_docs_version', ZIPPED_DOCS_VERSION );
    flush_rewrite_rules();
}

function zipped_docs_upgrade_100_to_200() {
    $settings = get_option( 'zipped_docs_settings', array() );
    if ( ! is_array( $settings ) ) {
        $settings = array();
    }
    $defaults = Zipped_Docs_Settings::get_defaults();
    $settings = array_merge( $defaults, $settings );
    update_option( 'zipped_docs_settings', $settings );
}

add_action( 'admin_menu', 'zipped_docs_register_admin_menu' );
function zipped_docs_register_admin_menu() {

    add_menu_page(
        'Zipped Docs',
        'Zipped Docs',
        'zipped_docs_read',
        'zipped-docs',
        'zipped_docs_admin_dashboard',
        'dashicons-book',
        25
    );

    add_submenu_page(
        'zipped-docs',
        'All Docs',
        'All Docs',
        'zipped_docs_read',
        'zipped-docs',
        'zipped_docs_admin_dashboard'
    );

    add_submenu_page(
        'zipped-docs',
        'Add New Doc',
        'Add New',
        'zipped_docs_create',
        'zipped-docs-new',
        'zipped_docs_admin_new_doc_page'
    );

    add_submenu_page(
        'zipped-docs',
        'Categories',
        'Categories',
        'zipped_docs_read',
        'zipped-docs-categories',
        'zipped_docs_admin_categories_page'
    );

    add_submenu_page(
        'zipped-docs',
        'Settings',
        'Settings',
        'zipped_docs_manage_settings',
        'zipped-docs-settings',
        'zipped_docs_admin_settings_page'
    );
}

add_action( 'admin_notices', 'zipped_docs_reinstall_notice' );
function zipped_docs_reinstall_notice() {
    if ( ! current_user_can( 'zipped_docs_read' ) ) {
        return;
    }

    if ( ! get_transient( 'zipped_docs_show_reinstall_notice' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    $is_zipped_docs_page = $screen && strpos( $screen->id, 'zipped-docs' ) !== false;
    $is_plugins   = $screen && 'plugins' === $screen->base;
    if ( ! $is_zipped_docs_page && ! $is_plugins ) {
        return;
    }

    if ( ! zipped_docs_has_previous_data() ) {
        delete_transient( 'zipped_docs_show_reinstall_notice' );
        return;
    }

    $action = isset( $_GET['zipped_docs_reinstall_action'] ) ? sanitize_key( $_GET['zipped_docs_reinstall_action'] ) : '';
    if ( $action ) {
        check_admin_referer( 'zipped_docs_reinstall_action' );

        if ( 'fresh' === $action ) {
            update_option( 'zipped_docs_settings', Zipped_Docs_Settings::get_defaults() );
            Zipped_Docs_Settings::get_instance()->reload();
            delete_transient( 'zipped_docs_show_reinstall_notice' );
            return;
        }

        if ( 'restore' === $action ) {
            delete_transient( 'zipped_docs_show_reinstall_notice' );
            zipped_docs_rebuild_graph();
            return;
        }
    }

    $restore_url = wp_nonce_url(
        add_query_arg( 'zipped_docs_reinstall_action', 'restore' ),
        'zipped_docs_reinstall_action'
    );
    $fresh_url = wp_nonce_url(
        add_query_arg( 'zipped_docs_reinstall_action', 'fresh' ),
        'zipped_docs_reinstall_action'
    );
    ?>
    <div class="notice notice-info is-dismissible zipped-docs-reinstall-notice">
        <p><strong><?php esc_html_e( 'Zipped Docs', 'zipped-docs' ); ?>:</strong>
        <?php esc_html_e( 'We found existing Zipped Docs data from a previous installation.', 'zipped-docs' ); ?></p>
        <p>
            <a href="<?php echo esc_url( $restore_url ); ?>" class="button button-primary">
                <?php esc_html_e( 'Restore Previous Data', 'zipped-docs' ); ?>
            </a>
            <a href="<?php echo esc_url( $fresh_url ); ?>" class="button">
                <?php esc_html_e( 'Start Fresh', 'zipped-docs' ); ?>
            </a>
        </p>
    </div>
    <?php
}

function zipped_docs_migrate_products_to_categories() {
    $done = get_option( 'zipped_docs_migrated_product_cats', false );
    if ( $done ) {
        return;
    }

    if ( ! taxonomy_exists( 'zipped_docs_product' ) || ! taxonomy_exists( 'zipped_docs_category' ) ) {
        return;
    }

    $docs = get_posts( array(
        'post_type'      => 'zipped_docs_doc',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- One-time migration query.
        'tax_query'      => array(
            array(
                'taxonomy' => 'zipped_docs_product',
                'operator' => 'EXISTS',
            ),
        ),
    ) );

    foreach ( $docs as $doc_id ) {
        $existing_cats = wp_get_post_terms( $doc_id, 'zipped_docs_category', array( 'fields' => 'ids' ) );
        if ( ! empty( $existing_cats ) ) {
            continue;
        }

        $products = wp_get_post_terms( $doc_id, 'zipped_docs_product', array( 'fields' => 'id=>slug' ) );
        if ( empty( $products ) || is_wp_error( $products ) ) {
            continue;
        }

        $term_id = key( $products );
        $slug    = reset( $products );

        $existing_cat = get_term_by( 'slug', $slug, 'zipped_docs_category' );
        if ( $existing_cat ) {
            wp_set_object_terms( $doc_id, array( (int) $existing_cat->term_id ), 'zipped_docs_category' );
        } else {
            $product_term = get_term( $term_id, 'zipped_docs_product' );
            if ( $product_term && ! is_wp_error( $product_term ) ) {
                $new_cat = wp_insert_term(
                    $product_term->name,
                    'zipped_docs_category',
                    array( 'slug' => $slug )
                );
                if ( ! is_wp_error( $new_cat ) ) {
                    wp_set_object_terms( $doc_id, array( (int) $new_cat['term_id'] ), 'zipped_docs_category' );
                }
            }
        }
    }

    update_option( 'zipped_docs_migrated_product_cats', true );
    zipped_docs_rebuild_graph();
}

add_action( 'wp_enqueue_scripts', 'zipped_docs_register_assets' );
function zipped_docs_register_assets() {
    wp_register_style(
        'zipped-docs',
        ZIPPED_DOCS_ASSETS . 'zipped-docs.css',
        array(),
        ZIPPED_DOCS_VERSION
    );

    wp_register_script(
        'zipped-docs',
        ZIPPED_DOCS_ASSETS . 'zipped-docs.js',
        array(),
        ZIPPED_DOCS_VERSION,
        true
    );
}

add_action( 'admin_enqueue_scripts', 'zipped_docs_admin_enqueue' );
function zipped_docs_admin_enqueue( $hook ) {
    if ( strpos( $hook, 'zipped-docs' ) === false ) {
        return;
    }

    wp_enqueue_style(
        'zipped-docs-admin',
        ZIPPED_DOCS_ASSETS . 'zipped-docs-admin.css',
        array(),
        ZIPPED_DOCS_VERSION
    );

    wp_enqueue_script(
        'zipped-docs-admin',
        ZIPPED_DOCS_ASSETS . 'zipped-docs-admin.js',
        array(),
        ZIPPED_DOCS_VERSION,
        true
    );

    wp_localize_script( 'zipped-docs-admin', 'ZIPPED_DOCS_ADMIN', array(
        'themeColor' => zipped_docs_get_settings()['zipped_docs_theme_color'] ?? '#2563EB',
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'i18n'       => array(
            'deactivationTitle' => __( 'Leaving Zipped Docs?', 'zipped-docs' ),
            'deactivationDesc'  => __( 'Would you like to keep your documentation and settings for future use?', 'zipped-docs' ),
            'keepData'          => __( 'Keep my documentation and settings', 'zipped-docs' ),
            'keepDataDesc'      => __( 'Database will remain intact for future use.', 'zipped-docs' ),
            'removeData'        => __( 'Remove all plugin data', 'zipped-docs' ),
            'removeDataDesc'    => __( 'All documentation, categories, and settings will be deleted on uninstall.', 'zipped-docs' ),
            'cancel'            => __( 'Cancel', 'zipped-docs' ),
            'deactivate'        => __( 'Deactivate Plugin', 'zipped-docs' ),
        ),
    ) );
}

add_action( 'admin_enqueue_scripts', 'zipped_docs_plugins_page_enqueue' );
function zipped_docs_plugins_page_enqueue( $hook ) {
    if ( 'plugins.php' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'zipped-docs-admin',
        ZIPPED_DOCS_ASSETS . 'zipped-docs-admin.js',
        array(),
        ZIPPED_DOCS_VERSION,
        true
    );

    wp_localize_script( 'zipped-docs-admin', 'ZIPPED_DOCS_ADMIN', array(
        'themeColor'       => zipped_docs_get_settings()['zipped_docs_theme_color'] ?? '#2563EB',
        'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
        'deactivationNonce' => wp_create_nonce( 'zipped_docs_deactivation_nonce' ),
        'i18n'             => array(
            'deactivationTitle' => __( 'Leaving Zipped Docs?', 'zipped-docs' ),
            'deactivationDesc'  => __( 'Would you like to keep your documentation and settings for future use?', 'zipped-docs' ),
            'keepData'          => __( 'Keep my documentation and settings', 'zipped-docs' ),
            'keepDataDesc'      => __( 'Database will remain intact for future use.', 'zipped-docs' ),
            'removeData'        => __( 'Remove all plugin data', 'zipped-docs' ),
            'removeDataDesc'    => __( 'All documentation, categories, and settings will be deleted on uninstall.', 'zipped-docs' ),
            'cancel'            => __( 'Cancel', 'zipped-docs' ),
            'deactivate'        => __( 'Deactivate Plugin', 'zipped-docs' ),
        ),
    ) );
}

add_action( 'wp_ajax_zipped_docs_set_deactivation_pref', 'zipped_docs_ajax_set_deactivation_pref' );
function zipped_docs_ajax_set_deactivation_pref() {
    if ( ! current_user_can( 'deactivate_plugins' ) ) {
        wp_die( '0' );
    }

    check_ajax_referer( 'zipped_docs_deactivation_nonce', '_wpnonce' );

    $action = isset( $_POST['deactivate_action'] ) ? sanitize_text_field( wp_unslash( $_POST['deactivate_action'] ) ) : 'keep';

    if ( 'remove' === $action ) {
        update_option( 'zipped_docs_preserve_data', 'no' );
        $settings = get_option( 'zipped_docs_settings', array() );
        if ( is_array( $settings ) ) {
            $settings['zipped_docs_preserve_data'] = 'no';
            update_option( 'zipped_docs_settings', $settings );
        }
    } else {
        update_option( 'zipped_docs_preserve_data', 'yes' );
        $settings = get_option( 'zipped_docs_settings', array() );
        if ( is_array( $settings ) ) {
            $settings['zipped_docs_preserve_data'] = 'yes';
            update_option( 'zipped_docs_settings', $settings );
        }
    }

    wp_die( '1' );
}

function zipped_docs_product_label( $slug ) {
    $term = get_term_by( 'slug', $slug, 'zipped_docs_category' );
    if ( $term && ! is_wp_error( $term ) ) {
        return $term->name;
    }
    return ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );
}

function zipped_docs_get_dynamic_css( $settings = null ) {
    if ( null === $settings ) {
        $settings = zipped_docs_get_settings();
    }

    $sidebar_w = (int) $settings['sidebar_width'];
    $content_w = 100 - $sidebar_w;
    $direction = 'left' === $settings['toc_position'] ? 'row' : 'row-reverse';
    $active_bg = 'yes' === $settings['enable_active_bg'] ? $settings['toc_active_bg'] : 'transparent';
    $heading_bg_rule = 'yes' === $settings['enable_heading_bg']
        ? '.zipped-docs .zipped-docs-toc li[data-depth="1"] > .zipped-docs-toc-link { background: ' . esc_attr( $settings['toc_heading_bg'] ) . '; border-radius: 6px; }'
        : '';

    $theme_color = $settings['zipped_docs_theme_color'];
    $theme_rgb = zipped_docs_hex_to_rgb( $theme_color );
    $theme_hover = zipped_docs_darken_color( $theme_color, 0.85 );

    $font_family = 'inherit';
    if ( 'google' === $settings['zipped_docs_font_family'] && ! empty( $settings['zipped_docs_google_font'] ) ) {
        $font_family = "'" . esc_attr( $settings['zipped_docs_google_font'] ) . "', sans-serif";
    }

    $css = '
.zipped-docs {
    --zipped-docs-primary: ' . esc_attr( $theme_color ) . ';
    --zipped-docs-primary-rgb: ' . esc_attr( $theme_rgb ) . ';
    --zipped-docs-theme-color: ' . esc_attr( $theme_color ) . ';
    --zipped-docs-theme-color-rgb: ' . esc_attr( $theme_rgb ) . ';
    --zipped-docs-text: #000000;
    --zipped-docs-text-link: ' . esc_attr( $theme_color ) . ';
    --zipped-docs-docs-primary-hover: ' . esc_attr( $theme_hover ) . ';
    --zipped-docs-docs-h1-size: ' . (int) $settings['h1_size'] . 'px;
    --zipped-docs-docs-h2-size: ' . (int) $settings['h2_size'] . 'px;
    --zipped-docs-docs-h3-size: ' . (int) $settings['h3_size'] . 'px;
    --zipped-docs-docs-h4-size: ' . (int) $settings['h4_size'] . 'px;
    --zipped-docs-docs-h5-size: ' . (int) $settings['h5_size'] . 'px;
    --zipped-docs-docs-h6-size: ' . (int) $settings['h6_size'] . 'px;
    --zipped-docs-docs-p-size: ' . (int) $settings['p_size'] . 'px;
    --zipped-docs-docs-line-height: ' . esc_attr( $settings['line_height'] ) . ';
    --zipped-docs-docs-toc-bg: ' . esc_attr( $settings['toc_bg'] ) . ';
    --zipped-docs-docs-toc-text: ' . esc_attr( $settings['toc_text'] ) . ';
    --zipped-docs-docs-toc-hover: ' . esc_attr( $settings['toc_hover'] ) . ';
    --zipped-docs-docs-toc-active-text: ' . esc_attr( $settings['toc_active_text'] ) . ';
    --zipped-docs-docs-toc-active-bg: ' . esc_attr( $active_bg ) . ';
    --zipped-docs-docs-toc-active-bar: ' . esc_attr( $settings['toc_active_bar'] ) . ';
    --zipped-docs-docs-highlight-bg: ' . esc_attr( $settings['highlight_bg'] ) . ';
    --zipped-docs-docs-highlight-text: ' . esc_attr( $settings['highlight_text'] ) . ';
    --zipped-docs-docs-sidebar-w: ' . $sidebar_w . '%;
    --zipped-docs-font: ' . $font_family . ';
    flex-direction: ' . esc_attr( $direction ) . ';
}
.zipped-docs-content-wrap {
    width: ' . $content_w . '%;
}
' . $heading_bg_rule . '
.zipped-docs.zipped-docs-has-admin-bar .zipped-docs-sidebar {
    top: calc(var(--zipped-docs-offset, 0px) + 24px);
    height: calc(100vh - var(--zipped-docs-offset, 0px));
}';

    return $css;
}

function zipped_docs_hex_to_rgb( $hex ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) !== 6 ) {
        return '37, 99, 235';
    }
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );
    return "{$r}, {$g}, {$b}";
}

function zipped_docs_darken_color( $hex, $factor = 0.8 ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) !== 6 ) {
        return '#1d4ed8';
    }
    $r = round( hexdec( substr( $hex, 0, 2 ) ) * $factor );
    $g = round( hexdec( substr( $hex, 2, 2 ) ) * $factor );
    $b = round( hexdec( substr( $hex, 4, 2 ) ) * $factor );
    return '#' . sprintf( '%02x%02x%02x', $r, $g, $b );
}

add_action( 'rest_api_init', 'zipped_docs_register_search_route' );
function zipped_docs_register_search_route() {
    register_rest_route( 'zipped-docs/v1', '/search', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'zipped_docs_rest_search',
        'permission_callback' => function () {
            return current_user_can( 'read' );
        },
        'args'                => array(
            'q' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ( $value ) {
                    return is_string( $value ) && mb_strlen( trim( $value ) ) >= 2;
                },
            ),
            'product' => array(
                'sanitize_callback' => 'sanitize_key',
            ),
        ),
    ) );
}

function zipped_docs_rest_search( $request ) {
    $query   = $request->get_param( 'q' );
    $product = $request->get_param( 'product' );

    if ( ! is_string( $query ) || mb_strlen( trim( $query ) ) < 2 ) {
        return new WP_Error( 'invalid_query', __( 'Search query must be at least 2 characters.', 'zipped-docs' ), array( 'status' => 400 ) );
    }

    $remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
    $throttle_key = 'zipped_docs_search_' . md5( $remote_ip );
    $throttled = get_transient( $throttle_key );
    if ( $throttled ) {
        return new WP_Error( 'rate_limited', __( 'Too many requests. Please wait before searching again.', 'zipped-docs' ), array( 'status' => 429 ) );
    }
    set_transient( $throttle_key, 1, 5 );

    $results = zipped_docs_search( $query, $product );

    return new WP_REST_Response( array(
        'query'   => $query,
        'results' => array_values( $results ),
        'total'   => count( $results ),
    ), 200 );
}

function zipped_docs_error( $message ) {
    if ( ! current_user_can( 'zipped_docs_edit' ) ) {
        return '';
    }
    return sprintf(
        '<div class="zipped-docs-error" style="border:1px solid #c00;padding:1rem;color:#c00;font-family:monospace;">'
        . '<strong>[Zipped Docs]</strong> %s</div>',
        esc_html( $message )
    );
}
