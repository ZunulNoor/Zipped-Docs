<?php

defined( 'ABSPATH' ) || exit;

class Doc_Vista_Docvista_Adapter implements Doc_Vista_Import_Adapter {

    public function supports( $data ) {
        if ( isset( $data['doc_vista_version'] ) ) {
            return true;
        }
        if ( isset( $data['_doc_vista_export'] ) && true === $data['_doc_vista_export'] ) {
            return true;
        }
        if ( isset( $data['source'] ) && 'doc-vista' === $data['source'] ) {
            return true;
        }
        return false;
    }

    public function normalize( $data ) {
        $doc = Doc_Vista_Normalizer::empty_doc();

        $doc['title'] = Doc_Vista_Field_Mapper::get( $data, 'title' );
        $doc['slug']  = Doc_Vista_Field_Mapper::get( $data, 'slug' );
        $doc['status'] = Doc_Vista_Field_Mapper::get( $data, 'status', 'draft' );
        $doc['excerpt'] = Doc_Vista_Field_Mapper::get( $data, 'excerpt' );

        $content = Doc_Vista_Field_Mapper::get_rendered( $data, 'content' );
        $doc['content'] = $content;

        if ( isset( $data['gutenberg_blocks'] ) && is_array( $data['gutenberg_blocks'] ) ) {
            $doc['gutenberg_blocks'] = $data['gutenberg_blocks'];
        } elseif ( isset( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
            $doc['gutenberg_blocks'] = $data['blocks'];
        }

        $doc['categories'] = Doc_Vista_Field_Mapper::extract_category_names( $data );
        $doc['tags'] = Doc_Vista_Field_Mapper::extract_tag_names( $data );
        $doc['custom_fields'] = Doc_Vista_Field_Mapper::extract_custom_fields( $data );

        if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
            $doc['meta'] = $data['meta'];
        }

        $featured = Doc_Vista_Field_Mapper::get( $data, 'featured_image' );
        if ( $featured ) {
            if ( is_numeric( $featured ) ) {
                $attachment = get_post( (int) $featured );
                $doc['featured_image'] = $featured;
                if ( $attachment && 'attachment' === $attachment->post_type ) {
                    $url = wp_get_attachment_url( (int) $featured );
                    if ( $url ) {
                        $doc['featured_image'] = $url;
                    }
                }
            } else {
                $doc['featured_image'] = $featured;
            }
        }

        if ( isset( $data['attachments'] ) && is_array( $data['attachments'] ) ) {
            $doc['attachments'] = $data['attachments'];
        }

        $author_raw = Doc_Vista_Field_Mapper::get( $data, 'author' );
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

        $doc['created_date'] = Doc_Vista_Field_Mapper::get_rendered( $data, 'created_date' );
        $doc['modified_date'] = Doc_Vista_Field_Mapper::get_rendered( $data, 'modified_date' );

        $doc['menu_order'] = (int) Doc_Vista_Field_Mapper::get( $data, 'menu_order', 0 );
        $doc['template'] = Doc_Vista_Field_Mapper::get( $data, 'template' );

        $doc['source'] = 'doc-vista';
        $doc['original_data'] = $data;

        return $doc;
    }
}
