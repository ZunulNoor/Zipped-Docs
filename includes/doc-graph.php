<?php

defined( 'ABSPATH' ) || exit;

add_action( 'save_post_zipped_docs_doc', 'zipped_docs_build_graph', 10, 2 );
add_action( 'delete_post', 'zipped_docs_on_delete_graph' );
add_action( 'trashed_post', 'zipped_docs_on_trash_untrash_graph' );
add_action( 'untrashed_post', 'zipped_docs_on_trash_untrash_graph' );
add_action( 'edited_zipped_docs_category', 'zipped_docs_build_graph' );
add_action( 'created_zipped_docs_category', 'zipped_docs_build_graph' );
add_action( 'delete_zipped_docs_category', 'zipped_docs_build_graph' );
add_action( 'edited_zipped_docs_product', 'zipped_docs_build_graph' );
add_action( 'created_zipped_docs_product', 'zipped_docs_build_graph' );
add_action( 'delete_zipped_docs_product', 'zipped_docs_build_graph' );

function zipped_docs_get_graph() {
    global $zipped_docs_graph_cache;
    if ( null !== $zipped_docs_graph_cache ) {
        return $zipped_docs_graph_cache;
    }
    $graph = get_option( 'zipped_docs_graph', false );
    if ( ! is_array( $graph ) || empty( $graph ) ) {
        zipped_docs_build_graph();
        $graph = get_option( 'zipped_docs_graph', array() );
    }
    $zipped_docs_graph_cache = $graph;
    return $zipped_docs_graph_cache;
}

function zipped_docs_clear_graph_cache() {
    global $zipped_docs_graph_cache;
    $zipped_docs_graph_cache = null;
}

function zipped_docs_get_product_graph( $category_slug ) {
    $graph = zipped_docs_get_graph();
    if ( is_array( $graph ) && isset( $graph['doc_tree'][ $category_slug ] ) ) {
        return $graph['doc_tree'][ $category_slug ];
    }
    return array(
        'category_slug' => $category_slug,
        'category_name' => '',
        'flat_list'     => array(),
    );
}

function zipped_docs_build_graph() {
    zipped_docs_clear_graph_cache();

    $categories = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
    ) );

    $doc_tree      = array();
    $search_index  = array();
    $category_map  = array();

    foreach ( $categories as $cat ) {
        $category_map[ $cat->term_id ] = array(
            'id'       => $cat->term_id,
            'name'     => $cat->name,
            'slug'     => $cat->slug,
            'parent'   => $cat->parent,
            'count'    => $cat->count,
            'children' => array(),
        );
    }
    foreach ( $category_map as $id => &$cat ) {
        if ( $cat['parent'] && isset( $category_map[ $cat['parent'] ] ) ) {
            $category_map[ $cat['parent'] ]['children'][] = $id;
        }
    }
    unset( $cat );

    $all_docs = array();
    $page = 1;
    do {
        $batch = get_posts( array(
            'post_type'      => 'zipped_docs_doc',
            'post_status'    => 'publish',
            'posts_per_page' => 500,
            'paged'          => $page,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
        if ( empty( $batch ) ) {
            break;
        }
        update_meta_cache( 'post', wp_list_pluck( $batch, 'ID' ) );
        $all_docs = array_merge( $all_docs, $batch );
        $page++;
    } while ( count( $batch ) === 500 );

    $docs_by_category = array();
    foreach ( $categories as $cat ) {
        $docs_by_category[ $cat->slug ] = array();
    }
    foreach ( $all_docs as $doc ) {
        $doc_cats = wp_get_post_terms( $doc->ID, 'zipped_docs_category', array( 'fields' => 'slugs' ) );
        foreach ( $doc_cats as $slug ) {
            if ( isset( $docs_by_category[ $slug ] ) ) {
                $docs_by_category[ $slug ][] = $doc;
            }
        }
    }

    foreach ( $categories as $cat ) {
        $docs = $docs_by_category[ $cat->slug ];
        $flat_list   = array();

        foreach ( $docs as $doc ) {
            $order     = (int) get_post_meta( $doc->ID, '_zipped_docs_order', true );
            $headings  = zipped_docs_extract_headings( $doc->post_content );
            $excerpt   = wp_trim_words( zipped_docs_strip_tags_preserve_space( $doc->post_content ), 30 );

            $entry = array(
                'id'        => $doc->ID,
                'title'     => $doc->post_title,
                'excerpt'   => $excerpt,
                'slug'      => $doc->post_name,
                'order'     => $order,
                'category'  => $cat->term_id,
                'category_slug' => $cat->slug,
                'category_name' => $cat->name,
                'headings'  => $headings,
                'url'       => add_query_arg( 'zipped_docs', $doc->ID, home_url() ),
            );

            $flat_list[ $doc->ID ] = $entry;

            $text = mb_strtolower( $doc->post_title . ' ' . $doc->post_title );
            foreach ( $headings as $h ) {
                $text .= ' ' . mb_strtolower( $h['text'] );
            }
            $text .= ' ' . mb_strtolower( zipped_docs_strip_tags_preserve_space( $doc->post_content ) );
            $tokens = zipped_docs_tokenize( $text );

            foreach ( $tokens as $token => $weight_mult ) {
                if ( ! isset( $search_index[ $token ] ) ) {
                    $search_index[ $token ] = array();
                }
                $weight = 1;
                $title_lower = mb_strtolower( $doc->post_title );
                if ( false !== mb_strpos( $title_lower, $token ) ) {
                    $weight = 5;
                } else {
                    foreach ( $headings as $h ) {
                        if ( false !== mb_strpos( mb_strtolower( $h['text'] ), $token ) ) {
                            $weight = 3;
                            break;
                        }
                    }
                }
                $search_index[ $token ][] = array( 'id' => $doc->ID, 'weight' => $weight );
            }
        }

        $doc_tree[ $cat->slug ] = array(
            'category_slug'  => $cat->slug,
            'category_name'  => $cat->name,
            'category_id'    => $cat->term_id,
            'flat_list'      => $flat_list,
        );
    }

    $graph = array(
        'doc_tree'      => $doc_tree,
        'search_index'  => $search_index,
        'category_map'  => $category_map,
        'built'         => time(),
    );

    update_option( 'zipped_docs_graph', $graph );

    zipped_docs_purge_page_cache();
}

function zipped_docs_on_delete_graph( $post_id ) {
    if ( 'zipped_docs_doc' !== get_post_type( $post_id ) ) {
        return;
    }
    zipped_docs_build_graph();
}

function zipped_docs_on_trash_untrash_graph( $post_id ) {
    if ( 'zipped_docs_doc' !== get_post_type( $post_id ) ) {
        return;
    }
    zipped_docs_build_graph();
}

function zipped_docs_is_ascii( $str ) {
    return (bool) preg_match( '/^[\x00-\x7f]*$/', $str );
}

/**
 * Strip HTML tags but preserve word boundaries by replacing each tag with a
 * space. Unlike wp_strip_all_tags(), this keeps text from separate elements
 * distinct so search tokens are not concatenated across tag boundaries.
 */
function zipped_docs_strip_tags_preserve_space( $content ) {
    $content = preg_replace( '/<[^>]+>/', ' ', $content );
    $content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
    $content = preg_replace( '/\s+/u', ' ', $content );
    return trim( $content );
}

function zipped_docs_tokenize( $text ) {
    $text = preg_replace( '/[^\p{L}\p{N}\s-]/u', ' ', $text );
    $text = preg_replace( '/\s+/', ' ', $text );
    $text = trim( $text );

    $words = explode( ' ', $text );
    $words = array_filter( $words, function( $w ) {
        return mb_strlen( $w ) >= 2;
    } );
    $words = array_slice( $words, 0, 500 );

    $tokens = array();
    foreach ( $words as $word ) {
        $word = mb_substr( $word, 0, 50 );
        $tokens[ $word ] = isset( $tokens[ $word ] ) ? $tokens[ $word ] + 1 : 1;

        $len = mb_strlen( $word );
        if ( $len >= 5 ) {
            $prefix = mb_substr( $word, 0, $len - 2 );
            $tokens[ $prefix ] = isset( $tokens[ $prefix ] ) ? $tokens[ $prefix ] + 1 : 1;
        }
    }

    return $tokens;
}

function zipped_docs_search( $query, $product_slug = '' ) {
    $graph = zipped_docs_get_graph();
    if ( empty( $graph['search_index'] ) ) {
        return array();
    }

    $query  = mb_strtolower( trim( $query ) );
    $tokens = explode( ' ', $query );
    $tokens = array_filter( $tokens, function( $t ) {
        return mb_strlen( $t ) >= 2;
    } );

    if ( empty( $tokens ) ) {
        return array();
    }

    $index   = $graph['search_index'];
    $results = array();

    foreach ( $tokens as $token ) {
        $token = mb_substr( $token, 0, 50 );

        if ( isset( $index[ $token ] ) ) {
            foreach ( $index[ $token ] as $entry ) {
                if ( ! isset( $results[ $entry['id'] ] ) ) {
                    $results[ $entry['id'] ] = 0;
                }
                $results[ $entry['id'] ] += $entry['weight'];
            }
        }

        foreach ( $index as $key => $entries ) {
            if ( $key === $token ) {
                continue;
            }
            if ( ! zipped_docs_is_ascii( $token ) || ! zipped_docs_is_ascii( $key ) ) {
                continue;
            }
            if ( false !== mb_strpos( $key, $token ) || false !== mb_strpos( $token, $key ) ) {
                $lev = levenshtein( $token, mb_substr( $key, 0, mb_strlen( $token ) ) );
                if ( $lev <= 2 ) {
                    foreach ( $entries as $entry ) {
                        if ( ! isset( $results[ $entry['id'] ] ) ) {
                            $results[ $entry['id'] ] = 0;
                        }
                        $results[ $entry['id'] ] += max( 1, $entry['weight'] - $lev );
                    }
                }
            }
        }
    }

    arsort( $results );

    $final = array();
    foreach ( $results as $doc_id => $score ) {
        $info = zipped_docs_get_doc_info( $doc_id, $graph );
        if ( $info ) {
            $info['score'] = $score;
            $final[] = $info;
        }
        if ( count( $final ) >= 20 ) {
            break;
        }
    }

    if ( $product_slug && ! empty( $final ) ) {
        $final = array_filter( $final, function( $d ) use ( $product_slug ) {
            return isset( $d['product_slug'] ) && $d['product_slug'] === $product_slug;
        } );
        $final = array_values( $final );
    }

    return $final;
}

function zipped_docs_get_doc_info( $doc_id, $graph = null ) {
    if ( null === $graph ) {
        $graph = zipped_docs_get_graph();
    }

    if ( empty( $graph['doc_tree'] ) || ! is_array( $graph['doc_tree'] ) ) {
        return null;
    }

    foreach ( $graph['doc_tree'] as $slug => $tree ) {
        if ( isset( $tree['flat_list'][ $doc_id ] ) ) {
            $info = $tree['flat_list'][ $doc_id ];
            $info['product_slug'] = $slug;
            $info['product_name'] = $tree['category_name'];
            return $info;
        }
    }

    return null;
}

function zipped_docs_extract_headings( $content ) {
    if ( ! $content ) {
        return array();
    }

    $headings = array();
    $pattern  = '/<h([1-6])([^>]*)>(.*?)<\/h\1>/si';

    if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $m ) {
            $level = (int) $m[1];
            $text  = wp_strip_all_tags( $m[3] );
            $text  = trim( $text );
            if ( $text ) {
                $headings[] = array(
                    'level' => $level,
                    'text'  => $text,
                );
            }
        }
    }

    return $headings;
}

function zipped_docs_get_breadcrumbs( $doc_id, $graph = null ) {
    if ( null === $graph ) {
        $graph = zipped_docs_get_graph();
    }

    $info = zipped_docs_get_doc_info( $doc_id, $graph );
    if ( ! $info ) {
        return array();
    }

    $crumbs = array(
        array(
            'label' => isset( $info['product_name'] ) ? $info['product_name'] : __( 'Documentation', 'zipped-docs' ),
            'slug'  => isset( $info['product_slug'] ) ? $info['product_slug'] : '',
        ),
    );

    if ( ! empty( $info['category'] ) && isset( $graph['category_map'][ $info['category'] ] ) ) {
        $cat_info = $graph['category_map'][ $info['category'] ];
        $crumbs[] = array(
            'label' => $cat_info['name'],
            'slug'  => $cat_info['slug'],
        );
    }

    $crumbs[] = array(
        'label' => $info['title'],
        'slug'  => $info['slug'],
    );

    return $crumbs;
}

function zipped_docs_get_adjacent( $doc_id, $category_slug ) {
    $tree = zipped_docs_get_product_graph( $category_slug );
    $list = isset( $tree['flat_list'] ) ? $tree['flat_list'] : array();

    if ( empty( $list ) ) {
        return array( 'prev' => null, 'next' => null );
    }

    $ids = array_keys( $list );
    $pos = array_search( $doc_id, $ids, true );

    if ( false === $pos ) {
        return array( 'prev' => null, 'next' => null );
    }

    return array(
        'prev' => $pos > 0 ? $list[ $ids[ $pos - 1 ] ] : null,
        'next' => $pos < count( $ids ) - 1 ? $list[ $ids[ $pos + 1 ] ] : null,
    );
}

function zipped_docs_get_related( $doc_id, $category_slug, $max = 3 ) {
    $tree   = zipped_docs_get_product_graph( $category_slug );
    $flat   = isset( $tree['flat_list'] ) ? $tree['flat_list'] : array();
    $info   = isset( $flat[ $doc_id ] ) ? $flat[ $doc_id ] : null;

    if ( ! $info ) {
        return array();
    }

    $cat_id = $info['category'] ?? 0;
    $related = array();

    foreach ( $flat as $id => $entry ) {
        if ( $id === $doc_id ) {
            continue;
        }
        if ( $cat_id && isset( $entry['category'] ) && (int) $entry['category'] === (int) $cat_id ) {
            $related[] = $entry;
            if ( count( $related ) >= $max ) {
                break;
            }
        }
    }

    return $related;
}

function zipped_docs_rebuild_graph() {
    delete_option( 'zipped_docs_graph' );
    zipped_docs_build_graph();
}
