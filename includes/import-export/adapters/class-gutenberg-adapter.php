<?php

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Gutenberg_Adapter implements Zipped_Docs_Import_Adapter {

    public function supports( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        if ( isset( $data['zipped_docs_version'] ) || ( isset( $data['source'] ) && 'zipped-docs' === $data['source'] ) ) {
            return false;
        }

        if ( isset( $data['__file'] ) && 'wp_export' === $data['__file'] ) {
            return true;
        }

        if ( isset( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
            return true;
        }

        $content = '';
        if ( isset( $data['content'] ) && is_string( $data['content'] ) ) {
            $content = $data['content'];
        } elseif ( isset( $data['post_content'] ) && is_string( $data['post_content'] ) ) {
            $content = $data['post_content'];
        }

        if ( $content && preg_match( '/<!--\s+wp:/', $content ) ) {
            if ( ! isset( $data['post_type'] ) && ! isset( $data['post_meta'] ) && ! isset( $data['tax_input'] ) ) {
                return true;
            }
        }

        return false;
    }

    public function normalize( $data ) {
        $doc = Zipped_Docs_Normalizer::empty_doc();

        $doc['title']   = Zipped_Docs_Field_Mapper::get_rendered( $data, 'title' );
        $doc['slug']    = Zipped_Docs_Field_Mapper::get( $data, 'slug' );
        $doc['excerpt'] = Zipped_Docs_Field_Mapper::get_rendered( $data, 'excerpt' );
        $doc['status']  = Zipped_Docs_Field_Mapper::get( $data, 'status', 'draft' );

        $content = Zipped_Docs_Field_Mapper::get_rendered( $data, 'content' );
        $doc['content'] = $content;

        if ( isset( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
            $doc['gutenberg_blocks'] = $data['blocks'];
        }

        if ( ! empty( $doc['content'] ) && preg_match( '/<!--\s+wp:/', $doc['content'] ) ) {
            $doc['gutenberg_blocks'] = array( 'detected' => true );
        }

        $author_raw = Zipped_Docs_Field_Mapper::get( $data, 'author' );
        if ( $author_raw ) {
            if ( is_numeric( $author_raw ) ) {
                $user = get_user_by( 'ID', (int) $author_raw );
                if ( $user ) {
                    $doc['author'] = (int) $author_raw;
                }
            } elseif ( is_string( $author_raw ) ) {
                $user = get_user_by( 'login', $author_raw );
                if ( $user ) {
                    $doc['author'] = $user->ID;
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
        }

        $doc['menu_order'] = (int) Zipped_Docs_Field_Mapper::get( $data, 'menu_order', 0 );

        $template = Zipped_Docs_Field_Mapper::get( $data, 'template' );
        if ( $template ) {
            $doc['template'] = $template;
            $doc['custom_fields']['_wp_page_template'] = $template;
        }

        $doc['source'] = 'gutenberg';
        $doc['original_data'] = $data;

        return $doc;
    }
}
