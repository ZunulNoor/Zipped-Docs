<?php

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Post_Page_Export_Adapter implements Zipped_Docs_Import_Adapter {

    public function supports( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        if ( isset( $data['zipped_docs_version'] ) || ( isset( $data['source'] ) && 'zipped-docs' === $data['source'] ) ) {
            return false;
        }

        if ( isset( $data['type'] ) && in_array( $data['type'], array( 'post', 'page', 'wp_block' ), true ) ) {
            if ( Zipped_Docs_Field_Mapper::has_any_field( $data, 'title' ) || Zipped_Docs_Field_Mapper::has_any_field( $data, 'content' ) ) {
                return true;
            }
        }

        if ( isset( $data['post_meta'] ) && is_array( $data['post_meta'] ) && isset( $data['post_title'] ) && isset( $data['post_content'] ) ) {
            return true;
        }

        if ( isset( $data['tax_input'] ) && is_array( $data['tax_input'] ) && isset( $data['post_title'] ) && isset( $data['post_content'] ) ) {
            return true;
        }

        if ( isset( $data['post_meta'] ) && is_array( $data['post_meta'] ) ) {
            foreach ( array_keys( $data['post_meta'] ) as $key ) {
                if ( strpos( $key, '_' ) === 0 || strpos( $key, 'wp_' ) === 0 ) {
                    if ( isset( $data['post_title'] ) ) {
                        return true;
                    }
                }
            }
        }

        if ( isset( $data['post_title'] ) && isset( $data['post_content'] ) && isset( $data['post_type'] ) ) {
            $valid = array( 'page', 'post', 'wp_block', 'nav_menu_item', 'product', 'attachment' );
            if ( in_array( $data['post_type'], $valid, true ) ) {
                return true;
            }
        }

        if ( isset( $data['title'] ) && isset( $data['content'] ) && isset( $data['post_meta'] ) ) {
            return true;
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

        if ( isset( $data['tax_input'] ) && is_array( $data['tax_input'] ) ) {
            foreach ( $data['tax_input'] as $taxonomy => $terms ) {
                if ( in_array( $taxonomy, array( 'category', 'post_tag', 'zipped_docs_category' ), true ) ) {
                    continue;
                }
                if ( is_array( $terms ) ) {
                    foreach ( $terms as $term ) {
                        if ( is_string( $term ) ) {
                            $doc['custom_fields'][ 'taxonomy_' . $taxonomy ][] = $term;
                        }
                    }
                }
            }
        }

        $doc['custom_fields'] = Zipped_Docs_Field_Mapper::extract_custom_fields( $data );

        if ( isset( $data['post_meta'] ) && is_array( $data['post_meta'] ) ) {
            foreach ( $data['post_meta'] as $key => $value ) {
                if ( strpos( $key, '_' ) === 0 ) {
                    if ( in_array( $key, array( '_thumbnail_id', '_wp_page_template', '_wp_attached_file', '_wp_attachment_metadata' ), true ) ) {
                        continue;
                    }
                }
                if ( ! isset( $doc['custom_fields'][ $key ] ) ) {
                    $val = is_array( $value ) && count( $value ) === 1 ? maybe_unserialize( $value[0] ) : $value;
                    $doc['custom_fields'][ $key ] = $val;
                }
            }
        }

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
        } elseif ( isset( $data['post_meta']['_thumbnail_id'] ) ) {
            $tid = (int) ( is_array( $data['post_meta']['_thumbnail_id'] ) ? $data['post_meta']['_thumbnail_id'][0] : $data['post_meta']['_thumbnail_id'] );
            $attachment = get_post( $tid );
            if ( $attachment && 'attachment' === $attachment->post_type ) {
                $url = wp_get_attachment_url( $tid );
                $doc['featured_image'] = $url ? $url : $tid;
            }
        }

        if ( isset( $data['post_meta']['_wp_attached_file'] ) ) {
            $file = is_array( $data['post_meta']['_wp_attached_file'] ) ? $data['post_meta']['_wp_attached_file'][0] : $data['post_meta']['_wp_attached_file'];
            $upload_dir = wp_upload_dir();
            $doc['attachments'][] = $upload_dir['baseurl'] . '/' . $file;
        }

        $template = Zipped_Docs_Field_Mapper::get( $data, 'template' );
        if ( ! $template && isset( $data['post_meta']['_wp_page_template'] ) ) {
            $template = is_array( $data['post_meta']['_wp_page_template'] ) ? $data['post_meta']['_wp_page_template'][0] : $data['post_meta']['_wp_page_template'];
        }
        if ( $template ) {
            $doc['template'] = $template;
            $doc['custom_fields']['_wp_page_template'] = $template;
        }

        $doc['menu_order'] = (int) Zipped_Docs_Field_Mapper::get( $data, 'menu_order', 0 );

        if ( preg_match( '/<!--\s+wp:/', $doc['content'] ) ) {
            $doc['gutenberg_blocks'] = array( 'detected' => true );
        }

        $doc['source'] = 'post-page-export';
        $doc['original_data'] = $data;

        return $doc;
    }
}
