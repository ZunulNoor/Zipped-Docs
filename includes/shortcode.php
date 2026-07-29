<?php

defined( 'ABSPATH' ) || exit;

add_shortcode( 'doc_vista', 'doc_vista_render_shortcode' );

function doc_vista_render_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'product'   => '',
            'doc_id'    => '',
            'toc_depth' => '6',
        ),
        (array) $atts,
        'doc_vista'
    );

    $product   = sanitize_key( $atts['product'] );
    $doc_id    = (int) $atts['doc_id'];
    $toc_depth = max( 2, min( 6, (int) $atts['toc_depth'] ) );

    $settings = doc_vista_get_settings();

    if ( $product && ! $doc_id ) {
        $cat_exists = term_exists( $product, 'doc_vista_category' );
        if ( ! $cat_exists ) {
            if ( current_user_can( 'doc_vista_edit' ) ) {
                return doc_vista_error(
                    sprintf(
                        __( 'The category "%s" does not exist. Please create it under Doc Vista → Categories or use a valid category slug.', 'doc-vista' ),
                        esc_html( $product )
                    )
                );
            }
            return '<p>' . esc_html__( 'Documentation is not available.', 'doc-vista' ) . '</p>';
        }
    }

    $page_content = '';
    $page_title   = __( 'Documentation', 'doc-vista' );

    if ( $doc_id ) {
        $doc_obj = get_post( $doc_id );
        if ( $doc_obj && 'publish' === $doc_obj->post_status && 'doc_vista_doc' === $doc_obj->post_type ) {
            $page_content = apply_filters( 'the_content', $doc_obj->post_content );
            $page_title   = get_the_title( $doc_obj );
            if ( ! $product ) {
                $terms = wp_get_post_terms( $doc_id, 'doc_vista_category', array( 'fields' => 'slugs' ) );
                $product = $terms[0] ?? '';
            }
        }
    } elseif ( $product ) {
        $tree   = doc_vista_get_product_graph( $product );
        $list   = ! empty( $tree['flat_list'] ) ? $tree['flat_list'] : array();
        if ( ! empty( $list ) ) {
            $first = reset( $list );
            $doc_obj = get_post( $first['id'] );
            if ( $doc_obj && 'publish' === $doc_obj->post_status ) {
                $page_content = apply_filters( 'the_content', $doc_obj->post_content );
                $page_title   = get_the_title( $doc_obj );
                $doc_id       = $first['id'];
            }
        }
    }

    $breadcrumbs = array();
    $adjacent    = array( 'prev' => null, 'next' => null );
    $related     = array();

    if ( $doc_id ) {
        $graph       = doc_vista_get_graph();
        $breadcrumbs = doc_vista_get_breadcrumbs( $doc_id, $graph );
        if ( $product ) {
            $adjacent = doc_vista_get_adjacent( $doc_id, $product );
            $related  = doc_vista_get_related( $doc_id, $product );
        }
    }

    wp_enqueue_style( 'doc-vista' );
    wp_enqueue_script( 'doc-vista' );

    if ( 'google' === $settings['doc_vista_font_family'] && ! empty( $settings['doc_vista_google_font'] ) ) {
        $gf = trim( $settings['doc_vista_google_font'] );
        $gf_slug = sanitize_title( $gf );
        $gf_url = 'https://fonts.googleapis.com/css2?family=' . str_replace( ' ', '+', $gf ) . ':wght@400;500;600;700&display=swap';
        wp_enqueue_style( 'doc-vista-font-' . $gf_slug, $gf_url, array(), DOC_VISTA_VERSION );
    }

    $graph = doc_vista_get_graph();
    $search_data = array();
    if ( $product && isset( $graph['search_index'] ) ) {
        $search_data = doc_vista_get_product_search_data( $product );
    }

    $display_settings = Doc_Vista_Settings::get_display_settings();

    $config = array(
        'product'         => $product,
        'docId'           => $doc_id,
        'tocDepth'        => $toc_depth,
        'restUrl'         => esc_url_raw( rest_url( 'doc-vista/v1/search' ) ),
        'restNonce'       => wp_create_nonce( 'wp_rest' ),
        'searchIndex'     => $search_data,
        'breadcrumbs'     => $breadcrumbs,
        'adjacent'        => $adjacent,
        'related'         => $related,
        'display'         => $display_settings,
        'settings'        => $settings,
        'i18n'            => array(
            'searchPlaceholder' => __( 'Search documentation…', 'doc-vista' ),
            'noResults'         => __( 'No results found.', 'doc-vista' ),
            'tocLabel'          => __( 'On this page', 'doc-vista' ),
            'prev'              => __( 'Previous', 'doc-vista' ),
            'next'              => __( 'Next', 'doc-vista' ),
            'related'           => __( 'Related articles', 'doc-vista' ),
            'tocNoResults'      => __( 'No matching sections found for "{query}"', 'doc-vista' ),
        ),
    );

    wp_localize_script( 'doc-vista', 'DocVistaConfig', $config );

    $css_vars = doc_vista_get_dynamic_css( $settings );
    wp_add_inline_style( 'doc-vista', $css_vars );

    ob_start();
    include DOC_VISTA_TEMPLATES . 'layout.php';

    return ob_get_clean();
}

function doc_vista_get_product_search_data( $category_slug ) {
    $graph = doc_vista_get_graph();
    if ( ! isset( $graph['doc_tree'][ $category_slug ] ) ) {
        return array(
            'docs'   => array(),
            'tokens' => array(),
        );
    }

    $tree = $graph['doc_tree'][ $category_slug ];
    $docs = array();

    foreach ( $tree['flat_list'] as $id => $info ) {
        $docs[ $id ] = array(
            'id'      => $id,
            'title'   => $info['title'],
            'excerpt' => $info['excerpt'],
        );
    }

    return array(
        'docs'   => $docs,
        'tokens' => array(),
    );
}
