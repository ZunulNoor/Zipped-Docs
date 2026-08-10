<?php

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Native_Adapter implements Zipped_Docs_Import_Adapter {

    public function supports( $data ) {
        if ( isset( $data['zipped_docs_version'] ) ) {
            return true;
        }
        if ( isset( $data['_zipped_docs_export'] ) && true === $data['_zipped_docs_export'] ) {
            return true;
        }
        if ( isset( $data['source'] ) && 'zipped-docs' === $data['source'] ) {
            return true;
        }
        return false;
    }

    public function normalize( $data ) {
        $doc = Zipped_Docs_Normalizer::empty_doc();

        $doc['title'] = Zipped_Docs_Field_Mapper::get( $data, 'title' );
        $doc['slug']  = Zipped_Docs_Field_Mapper::get( $data, 'slug' );
        $doc['status'] = Zipped_Docs_Field_Mapper::get( $data, 'status', 'draft' );
        $doc['excerpt'] = Zipped_Docs_Field_Mapper::get( $data, 'excerpt' );

        $content = Zipped_Docs_Field_Mapper::get_rendered( $data, 'content' );
        $doc['content'] = $content;

        if ( isset( $data['gutenberg_blocks'] ) && is_array( $data['gutenberg_blocks'] ) ) {
            $doc['gutenberg_blocks'] = $data['gutenberg_blocks'];
        } elseif ( isset( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
            $doc['gutenberg_blocks'] = $data['blocks'];
        }

        $doc['categories'] = Zipped_Docs_Field_Mapper::extract_category_names( $data );
        $doc['tags'] = Zipped_Docs_Field_Mapper::extract_tag_names( $data );
        $doc['custom_fields'] = Zipped_Docs_Field_Mapper::extract_custom_fields( $data );

        if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
            $doc['meta'] = $data['meta'];
        }

        $featured = Zipped_Docs_Field_Mapper::get( $data, 'featured_image' );
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

        $doc['created_date'] = Zipped_Docs_Field_Mapper::get_rendered( $data, 'created_date' );
        $doc['modified_date'] = Zipped_Docs_Field_Mapper::get_rendered( $data, 'modified_date' );

        $doc['menu_order'] = (int) Zipped_Docs_Field_Mapper::get( $data, 'menu_order', 0 );
        $doc['template'] = Zipped_Docs_Field_Mapper::get( $data, 'template' );

        $doc['source'] = 'zipped-docs';
        $doc['original_data'] = $data;

        return $doc;
    }
}
