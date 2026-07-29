<?php
/**
 * Plugin Name:  Doc Vista
 * Plugin URI:   https://github.com/ZunulNoor/Doc-Vista-WP-Plugin
 * Description:  Full documentation CMS with custom post types, categories, TOC,
 *               client-side search, and multi-product support.
 * Version:      2.2.0
 * Author:       Zun Ul Noor
 * Author URI:   https://zunulnoor.vercel.app
 * Text Domain:  doc-vista
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

/* -----------------------------------------------------------------------
 * Constants
 * --------------------------------------------------------------------- */
define( 'DOC_VISTA_VERSION',     '2.2.0' );
define( 'DOC_VISTA_DIR',         plugin_dir_path( __FILE__ ) );
define( 'DOC_VISTA_URL',         plugin_dir_url( __FILE__ ) );
define( 'DOC_VISTA_ASSETS',      DOC_VISTA_URL . 'assets/' );
define( 'DOC_VISTA_TEMPLATES',   DOC_VISTA_DIR . 'templates/' );
define( 'DOC_VISTA_INCLUDES',    DOC_VISTA_DIR . 'includes/' );

/* -----------------------------------------------------------------------
 * Safely load includes
 * --------------------------------------------------------------------- */
$doc_vista_includes = array(
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

foreach ( $doc_vista_includes as $doc_vista_file ) {
    $path = DOC_VISTA_INCLUDES . $doc_vista_file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

new Doc_Vista_Import_Export_Admin();

/* -----------------------------------------------------------------------
 * Register Custom Post Type + Taxonomy
 * --------------------------------------------------------------------- */
add_action( 'init', 'doc_vista_register_post_type' );
add_action( 'init', 'doc_vista_register_taxonomy' );
add_action( 'init', 'doc_vista_register_product_taxonomy' );

/* -----------------------------------------------------------------------
 * Flush rewrite rules on activation so CPT slugs work immediately
 * --------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'doc_vista_activate' );
function doc_vista_activate() {
    doc_vista_register_post_type();
    doc_vista_register_taxonomy();
    doc_vista_register_product_taxonomy();
    doc_vista_seed_default_terms();
    doc_vista_seed_settings();
    doc_vista_register_capabilities();
    update_option( 'doc_vista_version', DOC_VISTA_VERSION );
    flush_rewrite_rules();

    if ( doc_vista_has_previous_data() ) {
        set_transient( 'doc_vista_show_reinstall_notice', true, 300 );
    }

    set_transient( 'doc_vista_activation_redirect', true, 30 );
}

add_action( 'admin_init', 'doc_vista_activation_redirect_handler' );
function doc_vista_activation_redirect_handler() {
    if ( ! get_transient( 'doc_vista_activation_redirect' ) ) {
        return;
    }

    delete_transient( 'doc_vista_activation_redirect' );

    if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
        return;
    }

    wp_safe_redirect( admin_url( 'admin.php?page=doc-vista' ) );
    exit;
}

function doc_vista_has_previous_data() {
    $settings = get_option( 'doc_vista_settings', array() );
    if ( is_array( $settings ) && ! empty( $settings ) ) {
        $defaults = Doc_Vista_Settings::get_defaults();
        foreach ( $defaults as $key => $default_val ) {
            if ( isset( $settings[ $key ] ) && $settings[ $key ] !== $default_val ) {
                return true;
            }
        }
    }
    $doc_count = wp_count_posts( 'doc_vista_doc' );
    if ( $doc_count && ( ( $doc_count->publish ?? 0 ) > 0 || ( $doc_count->draft ?? 0 ) > 0 ) ) {
        return true;
    }
    return false;
}

register_deactivation_hook( __FILE__, 'doc_vista_deactivate' );
function doc_vista_deactivate() {
    flush_rewrite_rules();
}

function doc_vista_seed_settings() {
    $existing = get_option( 'doc_vista_settings', null );
    if ( null === $existing || false === $existing ) {
        update_option( 'doc_vista_settings', Doc_Vista_Settings::get_defaults() );
    } else {
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }
        $defaults = Doc_Vista_Settings::get_defaults();
        foreach ( $defaults as $key => $value ) {
            if ( ! array_key_exists( $key, $existing ) ) {
                $existing[ $key ] = $value;
            }
        }
        update_option( 'doc_vista_settings', $existing );
    }
    Doc_Vista_Settings::get_instance()->reload();
}

function doc_vista_purge_page_cache() {
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

add_action( 'doc_vista_settings_saved', 'doc_vista_clear_settings_cache' );
function doc_vista_clear_settings_cache() {
    wp_cache_delete( Doc_Vista_Settings::OPTION_NAME, 'options' );
    doc_vista_purge_page_cache();
}

add_action( 'admin_init', 'doc_vista_version_upgrade' );
function doc_vista_version_upgrade() {
    $stored = get_option( 'doc_vista_version', '' );
    if ( $stored === DOC_VISTA_VERSION ) {
        return;
    }

    if ( ! post_type_exists( 'doc_vista_doc' ) ) {
        doc_vista_register_post_type();
    }
    if ( ! taxonomy_exists( 'doc_vista_category' ) ) {
        doc_vista_register_taxonomy();
    }
    if ( ! taxonomy_exists( 'doc_vista_product' ) ) {
        doc_vista_register_product_taxonomy();
    }

    doc_vista_seed_default_terms();
    doc_vista_seed_settings();
    doc_vista_register_capabilities();

    $version_map = array(
        '1.0.0' => 'doc_vista_upgrade_100_to_200',
    );

    foreach ( $version_map as $version => $callback ) {
        if ( version_compare( $stored, $version, '<' ) && function_exists( $callback ) ) {
            call_user_func( $callback );
        }
    }

    doc_vista_migrate_products_to_categories();

    update_option( 'doc_vista_version', DOC_VISTA_VERSION );
    flush_rewrite_rules();
}

function doc_vista_upgrade_100_to_200() {
    $settings = get_option( 'doc_vista_settings', array() );
    if ( ! is_array( $settings ) ) {
        $settings = array();
    }
    $defaults = Doc_Vista_Settings::get_defaults();
    $settings = array_merge( $defaults, $settings );
    update_option( 'doc_vista_settings', $settings );
}

add_action( 'admin_menu', 'doc_vista_register_admin_menu' );
function doc_vista_register_admin_menu() {

    add_menu_page(
        'Doc Vista',
        'Doc Vista',
        'doc_vista_read',
        'doc-vista',
        'doc_vista_admin_dashboard',
        'dashicons-book',
        25
    );

    add_submenu_page(
        'doc-vista',
        'All Docs',
        'All Docs',
        'doc_vista_read',
        'doc-vista',
        'doc_vista_admin_dashboard'
    );

    add_submenu_page(
        'doc-vista',
        'Add New Doc',
        'Add New',
        'doc_vista_create',
        'doc-vista-new',
        'doc_vista_admin_new_doc_page'
    );

    add_submenu_page(
        'doc-vista',
        'Categories',
        'Categories',
        'doc_vista_read',
        'doc-vista-categories',
        'doc_vista_admin_categories_page'
    );

    add_submenu_page(
        'doc-vista',
        'Settings',
        'Settings',
        'doc_vista_manage_settings',
        'doc-vista-settings',
        'doc_vista_admin_settings_page'
    );
}

add_action( 'admin_notices', 'doc_vista_reinstall_notice' );
function doc_vista_reinstall_notice() {
    if ( ! current_user_can( 'doc_vista_read' ) ) {
        return;
    }

    if ( ! get_transient( 'doc_vista_show_reinstall_notice' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    $is_doc_vista_page = $screen && strpos( $screen->id, 'doc-vista' ) !== false;
    $is_plugins   = $screen && 'plugins' === $screen->base;
    if ( ! $is_doc_vista_page && ! $is_plugins ) {
        return;
    }

    if ( ! doc_vista_has_previous_data() ) {
        delete_transient( 'doc_vista_show_reinstall_notice' );
        return;
    }

    $action = isset( $_GET['doc_vista_reinstall_action'] ) ? sanitize_key( $_GET['doc_vista_reinstall_action'] ) : '';
    if ( $action ) {
        check_admin_referer( 'doc_vista_reinstall_action' );

        if ( 'fresh' === $action ) {
            update_option( 'doc_vista_settings', Doc_Vista_Settings::get_defaults() );
            Doc_Vista_Settings::get_instance()->reload();
            delete_transient( 'doc_vista_show_reinstall_notice' );
            return;
        }

        if ( 'restore' === $action ) {
            delete_transient( 'doc_vista_show_reinstall_notice' );
            doc_vista_rebuild_graph();
            return;
        }
    }

    $restore_url = wp_nonce_url(
        add_query_arg( 'doc_vista_reinstall_action', 'restore' ),
        'doc_vista_reinstall_action'
    );
    $fresh_url = wp_nonce_url(
        add_query_arg( 'doc_vista_reinstall_action', 'fresh' ),
        'doc_vista_reinstall_action'
    );
    ?>
    <div class="notice notice-info is-dismissible doc-vista-reinstall-notice">
        <p><strong><?php esc_html_e( 'Doc Vista', 'doc-vista' ); ?>:</strong>
        <?php esc_html_e( 'We found existing Doc Vista data from a previous installation.', 'doc-vista' ); ?></p>
        <p>
            <a href="<?php echo esc_url( $restore_url ); ?>" class="button button-primary">
                <?php esc_html_e( 'Restore Previous Data', 'doc-vista' ); ?>
            </a>
            <a href="<?php echo esc_url( $fresh_url ); ?>" class="button">
                <?php esc_html_e( 'Start Fresh', 'doc-vista' ); ?>
            </a>
        </p>
    </div>
    <?php
}

function doc_vista_migrate_products_to_categories() {
    $done = get_option( 'doc_vista_migrated_product_cats', false );
    if ( $done ) {
        return;
    }

    if ( ! taxonomy_exists( 'doc_vista_product' ) || ! taxonomy_exists( 'doc_vista_category' ) ) {
        return;
    }

    $docs = get_posts( array(
        'post_type'      => 'doc_vista_doc',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'tax_query'      => array(
            array(
                'taxonomy' => 'doc_vista_product',
                'operator' => 'EXISTS',
            ),
        ),
    ) );

    foreach ( $docs as $doc_id ) {
        $existing_cats = wp_get_post_terms( $doc_id, 'doc_vista_category', array( 'fields' => 'ids' ) );
        if ( ! empty( $existing_cats ) ) {
            continue;
        }

        $products = wp_get_post_terms( $doc_id, 'doc_vista_product', array( 'fields' => 'id=>slug' ) );
        if ( empty( $products ) || is_wp_error( $products ) ) {
            continue;
        }

        $term_id = key( $products );
        $slug    = reset( $products );

        $existing_cat = get_term_by( 'slug', $slug, 'doc_vista_category' );
        if ( $existing_cat ) {
            wp_set_object_terms( $doc_id, array( (int) $existing_cat->term_id ), 'doc_vista_category' );
        } else {
            $product_term = get_term( $term_id, 'doc_vista_product' );
            if ( $product_term && ! is_wp_error( $product_term ) ) {
                $new_cat = wp_insert_term(
                    $product_term->name,
                    'doc_vista_category',
                    array( 'slug' => $slug )
                );
                if ( ! is_wp_error( $new_cat ) ) {
                    wp_set_object_terms( $doc_id, array( (int) $new_cat['term_id'] ), 'doc_vista_category' );
                }
            }
        }
    }

    update_option( 'doc_vista_migrated_product_cats', true );
    doc_vista_rebuild_graph();
}

add_action( 'wp_enqueue_scripts', 'doc_vista_register_assets' );
function doc_vista_register_assets() {
    wp_register_style(
        'doc-vista',
        DOC_VISTA_ASSETS . 'doc-vista.css',
        array(),
        DOC_VISTA_VERSION
    );

    wp_register_script(
        'doc-vista',
        DOC_VISTA_ASSETS . 'doc-vista.js',
        array(),
        DOC_VISTA_VERSION,
        true
    );
}

add_action( 'admin_enqueue_scripts', 'doc_vista_admin_enqueue' );
function doc_vista_admin_enqueue( $hook ) {
    if ( strpos( $hook, 'doc-vista' ) === false ) {
        return;
    }

    wp_enqueue_style(
        'doc-vista-admin',
        DOC_VISTA_ASSETS . 'doc-vista-admin.css',
        array(),
        DOC_VISTA_VERSION
    );

    wp_enqueue_script(
        'doc-vista-admin',
        DOC_VISTA_ASSETS . 'doc-vista-admin.js',
        array(),
        DOC_VISTA_VERSION,
        true
    );

    wp_localize_script( 'doc-vista-admin', 'DOC_VISTA_ADMIN', array(
        'themeColor' => doc_vista_get_settings()['doc_vista_theme_color'] ?? '#2563EB',
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'i18n'       => array(
            'deactivationTitle' => __( 'Leaving Doc Vista?', 'doc-vista' ),
            'deactivationDesc'  => __( 'Would you like to keep your documentation and settings for future use?', 'doc-vista' ),
            'keepData'          => __( 'Keep my documentation and settings', 'doc-vista' ),
            'keepDataDesc'      => __( 'Database will remain intact for future use.', 'doc-vista' ),
            'removeData'        => __( 'Remove all plugin data', 'doc-vista' ),
            'removeDataDesc'    => __( 'All documentation, categories, and settings will be deleted on uninstall.', 'doc-vista' ),
            'cancel'            => __( 'Cancel', 'doc-vista' ),
            'deactivate'        => __( 'Deactivate Plugin', 'doc-vista' ),
        ),
    ) );
}

add_action( 'admin_enqueue_scripts', 'doc_vista_plugins_page_enqueue' );
function doc_vista_plugins_page_enqueue( $hook ) {
    if ( 'plugins.php' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'doc-vista-admin',
        DOC_VISTA_ASSETS . 'doc-vista-admin.js',
        array(),
        DOC_VISTA_VERSION,
        true
    );

    wp_localize_script( 'doc-vista-admin', 'DOC_VISTA_ADMIN', array(
        'themeColor'       => doc_vista_get_settings()['doc_vista_theme_color'] ?? '#2563EB',
        'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
        'deactivationNonce' => wp_create_nonce( 'doc_vista_deactivation_nonce' ),
        'i18n'             => array(
            'deactivationTitle' => __( 'Leaving Doc Vista?', 'doc-vista' ),
            'deactivationDesc'  => __( 'Would you like to keep your documentation and settings for future use?', 'doc-vista' ),
            'keepData'          => __( 'Keep my documentation and settings', 'doc-vista' ),
            'keepDataDesc'      => __( 'Database will remain intact for future use.', 'doc-vista' ),
            'removeData'        => __( 'Remove all plugin data', 'doc-vista' ),
            'removeDataDesc'    => __( 'All documentation, categories, and settings will be deleted on uninstall.', 'doc-vista' ),
            'cancel'            => __( 'Cancel', 'doc-vista' ),
            'deactivate'        => __( 'Deactivate Plugin', 'doc-vista' ),
        ),
    ) );
}

add_action( 'wp_ajax_doc_vista_set_deactivation_pref', 'doc_vista_ajax_set_deactivation_pref' );
function doc_vista_ajax_set_deactivation_pref() {
    if ( ! current_user_can( 'deactivate_plugins' ) ) {
        wp_die( '0' );
    }

    check_ajax_referer( 'doc_vista_deactivation_nonce', '_wpnonce' );

    $action = isset( $_POST['deactivate_action'] ) ? sanitize_text_field( wp_unslash( $_POST['deactivate_action'] ) ) : 'keep';

    if ( 'remove' === $action ) {
        update_option( 'doc_vista_preserve_data', 'no' );
        $settings = get_option( 'doc_vista_settings', array() );
        if ( is_array( $settings ) ) {
            $settings['doc_vista_preserve_data'] = 'no';
            update_option( 'doc_vista_settings', $settings );
        }
    } else {
        update_option( 'doc_vista_preserve_data', 'yes' );
        $settings = get_option( 'doc_vista_settings', array() );
        if ( is_array( $settings ) ) {
            $settings['doc_vista_preserve_data'] = 'yes';
            update_option( 'doc_vista_settings', $settings );
        }
    }

    wp_die( '1' );
}

function doc_vista_product_label( $slug ) {
    $term = get_term_by( 'slug', $slug, 'doc_vista_category' );
    if ( $term && ! is_wp_error( $term ) ) {
        return $term->name;
    }
    return ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );
}

function doc_vista_get_dynamic_css( $settings = null ) {
    if ( null === $settings ) {
        $settings = doc_vista_get_settings();
    }

    $sidebar_w = (int) $settings['sidebar_width'];
    $content_w = 100 - $sidebar_w;
    $direction = 'left' === $settings['toc_position'] ? 'row' : 'row-reverse';
    $active_bg = 'yes' === $settings['enable_active_bg'] ? $settings['toc_active_bg'] : 'transparent';
    $heading_bg_rule = 'yes' === $settings['enable_heading_bg']
        ? '.doc-vista .doc-vista-toc li[data-depth="1"] > .doc-vista-toc-link { background: ' . esc_attr( $settings['toc_heading_bg'] ) . '; border-radius: 6px; }'
        : '';

    $theme_color = $settings['doc_vista_theme_color'];
    $theme_rgb = doc_vista_hex_to_rgb( $theme_color );
    $theme_hover = doc_vista_darken_color( $theme_color, 0.85 );

    $font_family = 'inherit';
    if ( 'google' === $settings['doc_vista_font_family'] && ! empty( $settings['doc_vista_google_font'] ) ) {
        $font_family = "'" . esc_attr( $settings['doc_vista_google_font'] ) . "', sans-serif";
    }

    $css = '
.doc-vista {
    --doc-vista-primary: ' . esc_attr( $theme_color ) . ';
    --doc-vista-primary-rgb: ' . esc_attr( $theme_rgb ) . ';
    --doc-vista-theme-color: ' . esc_attr( $theme_color ) . ';
    --doc-vista-theme-color-rgb: ' . esc_attr( $theme_rgb ) . ';
    --doc-vista-text: #000000;
    --doc-vista-text-link: ' . esc_attr( $theme_color ) . ';
    --doc-vista-docs-primary-hover: ' . esc_attr( $theme_hover ) . ';
    --doc-vista-docs-h1-size: ' . (int) $settings['h1_size'] . 'px;
    --doc-vista-docs-h2-size: ' . (int) $settings['h2_size'] . 'px;
    --doc-vista-docs-h3-size: ' . (int) $settings['h3_size'] . 'px;
    --doc-vista-docs-h4-size: ' . (int) $settings['h4_size'] . 'px;
    --doc-vista-docs-h5-size: ' . (int) $settings['h5_size'] . 'px;
    --doc-vista-docs-h6-size: ' . (int) $settings['h6_size'] . 'px;
    --doc-vista-docs-p-size: ' . (int) $settings['p_size'] . 'px;
    --doc-vista-docs-line-height: ' . esc_attr( $settings['line_height'] ) . ';
    --doc-vista-docs-toc-bg: ' . esc_attr( $settings['toc_bg'] ) . ';
    --doc-vista-docs-toc-text: ' . esc_attr( $settings['toc_text'] ) . ';
    --doc-vista-docs-toc-hover: ' . esc_attr( $settings['toc_hover'] ) . ';
    --doc-vista-docs-toc-active-text: ' . esc_attr( $settings['toc_active_text'] ) . ';
    --doc-vista-docs-toc-active-bg: ' . esc_attr( $active_bg ) . ';
    --doc-vista-docs-toc-active-bar: ' . esc_attr( $settings['toc_active_bar'] ) . ';
    --doc-vista-docs-highlight-bg: ' . esc_attr( $settings['highlight_bg'] ) . ';
    --doc-vista-docs-highlight-text: ' . esc_attr( $settings['highlight_text'] ) . ';
    --doc-vista-docs-sidebar-w: ' . $sidebar_w . '%;
    --doc-vista-font: ' . $font_family . ';
    flex-direction: ' . esc_attr( $direction ) . ';
}
.doc-vista-content-wrap {
    width: ' . $content_w . '%;
}
' . $heading_bg_rule . '
.doc-vista.doc-vista-has-admin-bar .doc-vista-sidebar {
    top: calc(var(--doc-vista-offset, 0px) + 24px);
    height: calc(100vh - var(--doc-vista-offset, 0px));
}';

    return $css;
}

function doc_vista_hex_to_rgb( $hex ) {
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

function doc_vista_darken_color( $hex, $factor = 0.8 ) {
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

add_action( 'rest_api_init', 'doc_vista_register_search_route' );
function doc_vista_register_search_route() {
    register_rest_route( 'doc-vista/v1', '/search', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'doc_vista_rest_search',
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

function doc_vista_rest_search( $request ) {
    $query   = $request->get_param( 'q' );
    $product = $request->get_param( 'product' );

    if ( ! is_string( $query ) || mb_strlen( trim( $query ) ) < 2 ) {
        return new WP_Error( 'invalid_query', __( 'Search query must be at least 2 characters.', 'doc-vista' ), array( 'status' => 400 ) );
    }

    $remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
    $throttle_key = 'doc_vista_search_' . md5( $remote_ip );
    $throttled = get_transient( $throttle_key );
    if ( $throttled ) {
        return new WP_Error( 'rate_limited', __( 'Too many requests. Please wait before searching again.', 'doc-vista' ), array( 'status' => 429 ) );
    }
    set_transient( $throttle_key, 1, 5 );

    $results = doc_vista_search( $query, $product );

    return new WP_REST_Response( array(
        'query'   => $query,
        'results' => array_values( $results ),
        'total'   => count( $results ),
    ), 200 );
}

function doc_vista_error( $message ) {
    if ( ! current_user_can( 'doc_vista_edit' ) ) {
        return '';
    }
    return sprintf(
        '<div class="doc-vista-error" style="border:1px solid #c00;padding:1rem;color:#c00;font-family:monospace;">'
        . '<strong>[Doc Vista]</strong> %s</div>',
        esc_html( $message )
    );
}
