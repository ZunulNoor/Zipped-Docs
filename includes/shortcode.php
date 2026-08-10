<?php

defined( 'ABSPATH' ) || exit;

add_shortcode( 'zippeddocs', 'zipped_docs_render_shortcode' );
add_shortcode( 'zipped_docs', 'zipped_docs_render_shortcode' );

function zipped_docs_render_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'product'   => '',
            'doc_id'    => '',
            'toc_depth' => '6',
        ),
        (array) $atts,
        'zippeddocs'
    );

    $product   = sanitize_key( $atts['product'] );
    $doc_id    = (int) $atts['doc_id'];
    $toc_depth = max( 2, min( 6, (int) $atts['toc_depth'] ) );

    $settings = zipped_docs_get_settings();

    if ( $product && ! $doc_id ) {
        $cat_exists = term_exists( $product, 'zipped_docs_category' );
        if ( ! $cat_exists ) {
            if ( current_user_can( 'zipped_docs_edit' ) ) {
                return zipped_docs_error(
                    sprintf(
                        /* translators: %s: product category slug. */
                        __( 'The category "%s" does not exist. Please create it under Zipped Docs → Categories or use a valid category slug.', 'zipped-docs' ),
                        esc_html( $product )
                    )
                );
            }
            return '<p>' . esc_html__( 'Documentation is not available.', 'zipped-docs' ) . '</p>';
        }
    }

    $page_content = '';
    $page_title   = __( 'Documentation', 'zipped-docs' );

    if ( $doc_id ) {
        $doc_obj = get_post( $doc_id );
        if ( $doc_obj && 'publish' === $doc_obj->post_status && 'zipped_docs_doc' === $doc_obj->post_type ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WordPress filter.
            $page_content = apply_filters( 'the_content', $doc_obj->post_content );
            $page_title   = get_the_title( $doc_obj );
            if ( ! $product ) {
                $terms = wp_get_post_terms( $doc_id, 'zipped_docs_category', array( 'fields' => 'slugs' ) );
                $product = $terms[0] ?? '';
            }
        }
    } elseif ( $product ) {
        $tree   = zipped_docs_get_product_graph( $product );
        $list   = ! empty( $tree['flat_list'] ) ? $tree['flat_list'] : array();
        if ( ! empty( $list ) ) {
            $first = reset( $list );
            $doc_obj = get_post( $first['id'] );
            if ( $doc_obj && 'publish' === $doc_obj->post_status ) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WordPress filter.
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
        $graph       = zipped_docs_get_graph();
        $breadcrumbs = zipped_docs_get_breadcrumbs( $doc_id, $graph );
        if ( $product ) {
            $adjacent = zipped_docs_get_adjacent( $doc_id, $product );
            $related  = zipped_docs_get_related( $doc_id, $product );
        }
    }

    wp_enqueue_style( 'zipped-docs' );
    wp_enqueue_script( 'zipped-docs' );

    if ( 'google' === $settings['zipped_docs_font_family'] && ! empty( $settings['zipped_docs_google_font'] ) ) {
        $gf = trim( $settings['zipped_docs_google_font'] );
        $gf_slug = sanitize_title( $gf );
        $gf_url = 'https://fonts.googleapis.com/css2?family=' . str_replace( ' ', '+', $gf ) . ':wght@400;500;600;700&display=swap';
        wp_enqueue_style( 'zipped-docs-font-' . $gf_slug, $gf_url, array(), ZIPPED_DOCS_VERSION );
    }

    $graph = zipped_docs_get_graph();
    $search_data = array();
    if ( $product && isset( $graph['search_index'] ) ) {
        $search_data = zipped_docs_get_product_search_data( $product );
    }

    $display_settings = Zipped_Docs_Settings::get_display_settings();

    $config = array(
        'product'         => $product,
        'docId'           => $doc_id,
        'tocDepth'        => $toc_depth,
        'restUrl'         => esc_url_raw( rest_url( 'zipped-docs/v1/search' ) ),
        'restNonce'       => wp_create_nonce( 'wp_rest' ),
        'searchIndex'     => $search_data,
        'breadcrumbs'     => $breadcrumbs,
        'adjacent'        => $adjacent,
        'related'         => $related,
        'display'         => $display_settings,
        'settings'        => $settings,
        'i18n'            => array(
            'searchPlaceholder' => __( 'Search documentation…', 'zipped-docs' ),
            'noResults'         => __( 'No results found.', 'zipped-docs' ),
            'tocLabel'          => __( 'On this page', 'zipped-docs' ),
            'prev'              => __( 'Previous', 'zipped-docs' ),
            'next'              => __( 'Next', 'zipped-docs' ),
            'related'           => __( 'Related articles', 'zipped-docs' ),
            'tocNoResults'      => __( 'No matching sections found for "{query}"', 'zipped-docs' ),
        ),
    );

    wp_localize_script( 'zipped-docs', 'ZippedDocsConfig', $config );

    $css_vars = zipped_docs_get_dynamic_css( $settings );
    wp_add_inline_style( 'zipped-docs', $css_vars );

    ob_start();
    include ZIPPED_DOCS_TEMPLATES . 'layout.php';

    return ob_get_clean();
}

function zipped_docs_get_product_search_data( $category_slug ) {
    $graph = zipped_docs_get_graph();
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
