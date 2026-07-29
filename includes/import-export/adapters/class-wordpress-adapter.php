<?php

defined( 'ABSPATH' ) || exit;

class Doc_Vista_Wordpress_Adapter implements Doc_Vista_Import_Adapter {

    public function supports( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        if ( isset( $data['doc_vista_version'] ) || ( isset( $data['source'] ) && 'doc-vista' === $data['source'] ) ) {
            return false;
        }

        if ( isset( $data['post_type'] ) && in_array( $data['post_type'], array( 'page', 'post' ), true ) ) {
            if ( Doc_Vista_Field_Mapper::has_any_field( $data, 'title' ) && Doc_Vista_Field_Mapper::has_any_field( $data, 'content' ) ) {
                return true;
            }
        }

        $has_title = Doc_Vista_Field_Mapper::has_any_field( $data, 'title' );
        $has_content = Doc_Vista_Field_Mapper::has_any_field( $data, 'content' );

        if ( $has_title && $has_content && ! isset( $data['type'] ) ) {
            if ( isset( $data['post_title'] ) || isset( $data['post_name'] ) || isset( $data['post_date'] ) || isset( $data['post_status'] ) ) {
                return true;
            }
        }

        if ( isset( $data['post_title'] ) && isset( $data['post_content'] ) ) {
            $pt = isset( $data['post_type'] ) ? $data['post_type'] : '';
            if ( ! $pt || in_array( $pt, array( 'page', 'post', '' ), true ) ) {
                return true;
            }
        }

        return false;
    }

    public function normalize( $data ) {
        $doc = Doc_Vista_Normalizer::empty_doc();

        $doc['title']   = Doc_Vista_Field_Mapper::get_rendered( $data, 'title' );
        $doc['slug']    = Doc_Vista_Field_Mapper::get( $data, 'slug' );
        $doc['content'] = Doc_Vista_Field_Mapper::get_rendered( $data, 'content' );
        $doc['excerpt'] = Doc_Vista_Field_Mapper::get_rendered( $data, 'excerpt' );
        $doc['status']  = Doc_Vista_Field_Mapper::get( $data, 'status', 'draft' );

        $author_raw = Doc_Vista_Field_Mapper::get( $data, 'author' );
        if ( $author_raw ) {
            if ( is_numeric( $author_raw ) ) {
                $user = get_user_by( 'ID', (int) $author_raw );
                if ( $user ) {
                    $doc['author'] = (int) $author_raw;
                }
            }
        }
        if ( ! $doc['author'] ) {
            $doc['author'] = get_current_user_id();
        }

        $doc['created_date']  = Doc_Vista_Field_Mapper::get_rendered( $data, 'created_date' );
        $doc['modified_date'] = Doc_Vista_Field_Mapper::get_rendered( $data, 'modified_date' );

        $doc['categories'] = Doc_Vista_Field_Mapper::extract_category_names( $data );
        $doc['tags']       = Doc_Vista_Field_Mapper::extract_tag_names( $data );
        $doc['custom_fields'] = Doc_Vista_Field_Mapper::extract_custom_fields( $data );

        $featured = Doc_Vista_Field_Mapper::get( $data, 'featured_image' );
        if ( $featured ) {
            if ( is_numeric( $featured ) ) {
                $attachment = get_post( (int) $featured );
                if ( $attachment && 'attachment' === $attachment->post_type ) {
                    $url = wp_get_attachment_url( (int) $featured );
                    $doc['featured_image'] = $url ? $url : $featured;
                } else {
                    $doc['featured_image'] = $featured;
                }
            } else {
                $doc['featured_image'] = $featured;
            }
        } elseif ( isset( $data['_thumbnail_id'] ) ) {
            $tid = (int) $data['_thumbnail_id'];
            $attachment = get_post( $tid );
            if ( $attachment && 'attachment' === $attachment->post_type ) {
                $url = wp_get_attachment_url( $tid );
                $doc['featured_image'] = $url ? $url : $tid;
            }
        }

        $doc['menu_order'] = (int) Doc_Vista_Field_Mapper::get( $data, 'menu_order', 0 );

        $template = Doc_Vista_Field_Mapper::get( $data, 'template' );
        if ( $template ) {
            $doc['template'] = $template;
            $doc['custom_fields']['_wp_page_template'] = $template;
        }

        if ( ! empty( $doc['content'] ) && preg_match( '/<!--\s+wp:/', $doc['content'] ) ) {
            $doc['gutenberg_blocks'] = array( 'detected' => true );
        }

        $doc['source'] = 'wordpress';
        $doc['original_data'] = $data;

        return $doc;
    }
}
