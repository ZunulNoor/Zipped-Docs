<?php

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Wordpress_Adapter implements Zipped_Docs_Import_Adapter {

    public function supports( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        if ( isset( $data['zipped_docs_version'] ) || ( isset( $data['source'] ) && 'zipped-docs' === $data['source'] ) ) {
            return false;
        }

        if ( isset( $data['post_type'] ) && in_array( $data['post_type'], array( 'page', 'post' ), true ) ) {
            if ( Zipped_Docs_Field_Mapper::has_any_field( $data, 'title' ) && Zipped_Docs_Field_Mapper::has_any_field( $data, 'content' ) ) {
                return true;
            }
        }

        $has_title = Zipped_Docs_Field_Mapper::has_any_field( $data, 'title' );
        $has_content = Zipped_Docs_Field_Mapper::has_any_field( $data, 'content' );

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
        $doc = Zipped_Docs_Normalizer::empty_doc();

        $doc['title']   = Zipped_Docs_Field_Mapper::get_rendered( $data, 'title' );
        $doc['slug']    = Zipped_Docs_Field_Mapper::get( $data, 'slug' );
        $doc['content'] = Zipped_Docs_Field_Mapper::get_rendered( $data, 'content' );
        $doc['excerpt'] = Zipped_Docs_Field_Mapper::get_rendered( $data, 'excerpt' );
        $doc['status']  = Zipped_Docs_Field_Mapper::get( $data, 'status', 'draft' );

        $author_raw = Zipped_Docs_Field_Mapper::get( $data, 'author' );
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

        $doc['created_date']  = Zipped_Docs_Field_Mapper::get_rendered( $data, 'created_date' );
        $doc['modified_date'] = Zipped_Docs_Field_Mapper::get_rendered( $data, 'modified_date' );

        $doc['categories'] = Zipped_Docs_Field_Mapper::extract_category_names( $data );
        $doc['tags']       = Zipped_Docs_Field_Mapper::extract_tag_names( $data );
        $doc['custom_fields'] = Zipped_Docs_Field_Mapper::extract_custom_fields( $data );

        $featured = Zipped_Docs_Field_Mapper::get( $data, 'featured_image' );
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

        $doc['menu_order'] = (int) Zipped_Docs_Field_Mapper::get( $data, 'menu_order', 0 );

        $template = Zipped_Docs_Field_Mapper::get( $data, 'template' );
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
