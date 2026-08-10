<?php

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Export_Engine {

    public function export_single( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || 'zipped_docs_doc' !== $post->post_type ) {
            return new WP_Error( 'invalid_post', __( 'Invalid document.', 'zipped-docs' ) );
        }

        return $this->build_export_doc( $post );
    }

    public function export_selected( $post_ids ) {
        if ( empty( $post_ids ) ) {
            return new WP_Error( 'no_docs', __( 'No documents selected.', 'zipped-docs' ) );
        }

        $posts = get_posts( array(
            'post_type'      => 'zipped_docs_doc',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'post__in'       => $post_ids,
            'orderby'        => 'post__in',
        ) );

        if ( empty( $posts ) ) {
            return new WP_Error( 'no_docs', __( 'No documents found.', 'zipped-docs' ) );
        }

        $documents = array();
        foreach ( $posts as $post ) {
            $documents[] = $this->build_export_doc( $post );
        }

        return $this->build_export_package( $documents );
    }

    public function export_category( $category_slug ) {
        $term = get_term_by( 'slug', $category_slug, 'zipped_docs_category' );
        if ( ! $term ) {
            return new WP_Error( 'invalid_category', __( 'Category not found.', 'zipped-docs' ) );
        }

        $posts = get_posts( array(
            'post_type'      => 'zipped_docs_doc',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category export.
            'tax_query'      => array(
                array(
                    'taxonomy' => 'zipped_docs_category',
                    'field'    => 'slug',
                    'terms'    => $category_slug,
                ),
            ),
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ) );

        if ( empty( $posts ) ) {
            return new WP_Error( 'no_docs', __( 'No documents found in this category.', 'zipped-docs' ) );
        }

        $documents = array();
        foreach ( $posts as $post ) {
            $documents[] = $this->build_export_doc( $post );
        }

        return $this->build_export_package( $documents );
    }

    public function export_all() {
        $posts = get_posts( array(
            'post_type'      => 'zipped_docs_doc',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ) );

        if ( empty( $posts ) ) {
            return new WP_Error( 'no_docs', __( 'No documents found.', 'zipped-docs' ) );
        }

        $documents = array();
        foreach ( $posts as $post ) {
            $documents[] = $this->build_export_doc( $post );
        }

        return $this->build_export_package( $documents );
    }

    public function export_by_ids_batched( $post_ids, $batch_size = 50 ) {
        $all_documents = array();
        $batches = array_chunk( $post_ids, $batch_size );

        foreach ( $batches as $batch ) {
            $posts = get_posts( array(
                'post_type'      => 'zipped_docs_doc',
                'post_status'    => 'any',
                'posts_per_page' => count( $batch ),
                'post__in'       => $batch,
                'orderby'        => 'post__in',
            ) );

            foreach ( $posts as $post ) {
                $all_documents[] = $this->build_export_doc( $post );
            }
        }

        if ( empty( $all_documents ) ) {
            return new WP_Error( 'no_docs', __( 'No documents found.', 'zipped-docs' ) );
        }

        return $this->build_export_package( $all_documents );
    }

    private function build_export_doc( $post ) {
        $categories = wp_get_post_terms( $post->ID, 'zipped_docs_category', array( 'fields' => 'slugs' ) );
        $tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

        $custom_fields = array();
        $meta = get_post_meta( $post->ID );
        if ( is_array( $meta ) ) {
            foreach ( $meta as $key => $values ) {
                if ( strpos( $key, '_' ) === 0 ) {
                    if ( in_array( $key, array( '_wp_page_template', '_zipped_docs_order', '_zipped_docs_gutenberg_blocks' ), true ) ) {
                        $v = maybe_unserialize( $values[0] );
                        $custom_fields[ $key ] = $v;
                    }
                    continue;
                }
                $custom_fields[ $key ] = maybe_unserialize( $values[0] );
            }
        }

        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        $featured_image = '';
        $featured_image_id = 0;
        if ( $thumbnail_id ) {
            $featured_image_url = wp_get_attachment_url( $thumbnail_id );
            if ( $featured_image_url ) {
                $featured_image = $featured_image_url;
                $featured_image_id = $thumbnail_id;
            }
        }

        $attachments = array();
        $media = get_attached_media( '', $post->ID );
        foreach ( $media as $att ) {
            $attachments[] = array(
                'id'   => $att->ID,
                'url'  => wp_get_attachment_url( $att->ID ),
                'name' => $att->post_title,
            );
        }

        $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
        $doc_order     = get_post_meta( $post->ID, '_zipped_docs_order', true );
        $guten_blocks  = get_post_meta( $post->ID, '_zipped_docs_gutenberg_blocks', true );

        $doc = array(
            'title'             => $post->post_title,
            'slug'              => $post->post_name,
            'status'            => $post->post_status,
            'excerpt'           => $post->post_excerpt,
            'content'           => $post->post_content,
            'gutenberg_blocks'  => is_array( $guten_blocks ) ? $guten_blocks : array(),
            'categories'        => $categories,
            'tags'              => $tags,
            'featured_image'    => $featured_image,
            'featured_image_id' => $featured_image_id,
            'attachments'       => $attachments,
            'custom_fields'     => $custom_fields,
            'meta'              => array(
                'zipped_docs_order'   => $doc_order ? (int) $doc_order : 0,
                'page_template'     => $page_template ? $page_template : '',
                'zipped_docs_version' => ZIPPED_DOCS_VERSION,
            ),
            'menu_order'        => $post->menu_order,
            'template'          => $page_template ? $page_template : '',
            'author'            => $post->post_author,
            'created_date'      => $post->post_date,
            'modified_date'     => $post->post_modified,
            'source'            => 'zipped-docs',
        );

        return $doc;
    }

    private function build_export_package( $documents ) {
        return array(
            '_zipped_docs_export'   => true,
            'zipped_docs_version'   => ZIPPED_DOCS_VERSION,
            'export_date'         => current_time( 'mysql' ),
            'total_documents'     => count( $documents ),
            'source'              => 'zipped-docs',
            'generator'           => 'Zipped Docs ' . ZIPPED_DOCS_VERSION,
            'documents'           => $documents,
        );
    }
}
