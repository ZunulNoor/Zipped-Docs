<?php

defined( 'ABSPATH' ) || exit;

class Doc_Vista_Field_Mapper {

    private static $field_map = array(
        'title' => array( 'post_title', 'document_title', 'name', 'heading', 'headline' ),
        'content' => array( 'post_content', 'content_html', 'post_body', 'body', 'description', 'document_content' ),
        'slug' => array( 'post_name', 'post_slug', 'document_slug', 'url', 'uri' ),
        'excerpt' => array( 'post_excerpt', 'summary', 'abstract', 'intro' ),
        'status' => array( 'post_status', 'document_status', 'publish_status', 'visibility' ),
        'author' => array( 'post_author', 'author_id', 'user_id', 'creator', 'created_by' ),
        'created_date' => array( 'post_date', 'date', 'created', 'publish_date', 'published', 'post_date_gmt' ),
        'modified_date' => array( 'post_modified', 'modified', 'updated', 'last_modified', 'post_modified_gmt', 'changed' ),
        'featured_image' => array( 'post_thumbnail', '_thumbnail_id', 'thumbnail', 'featured_media', 'image', 'post_image' ),
        'categories' => array( 'category', 'category_name', 'cat', 'doc_vista_category', 'terms', 'taxonomy', 'taxonomies' ),
        'tags' => array( 'tags_input', 'post_tags', 'tag', 'doc_vista_tags' ),
        'custom_fields' => array( 'post_meta', 'meta', 'meta_data', 'fields', 'custom_meta', 'post_custom' ),
        'gutenberg_blocks' => array( 'blocks', 'block_data', 'inner_blocks' ),
        'attachments' => array( 'media', 'images', 'files', 'uploads', 'enclosure' ),
        'menu_order' => array( 'order', 'doc_vista_order', 'sort_order', 'position', 'menu_order' ),
        'template' => array( 'page_template', 'wp_page_template', '_wp_page_template', 'template_name' ),
        'post_type' => array( 'type', 'document_type', 'content_type' ),
    );

    public static function get( $data, $canonical, $default = '' ) {
        if ( ! isset( self::$field_map[ $canonical ] ) ) {
            return isset( $data[ $canonical ] ) ? $data[ $canonical ] : $default;
        }

        if ( isset( $data[ $canonical ] ) ) {
            return $data[ $canonical ];
        }

        $aliases = self::$field_map[ $canonical ];
        foreach ( $aliases as $alias ) {
            if ( isset( $data[ $alias ] ) ) {
                return $data[ $alias ];
            }
        }

        return $default;
    }

    public static function has_any_field( $data, $canonical ) {
        if ( ! isset( self::$field_map[ $canonical ] ) ) {
            return isset( $data[ $canonical ] );
        }

        if ( isset( $data[ $canonical ] ) ) {
            return true;
        }

        $aliases = self::$field_map[ $canonical ];
        foreach ( $aliases as $alias ) {
            if ( isset( $data[ $alias ] ) ) {
                return true;
            }
        }

        return false;
    }

    public static function get_rendered( $data, $field ) {
        $value = self::get( $data, $field );
        if ( is_array( $value ) && isset( $value['rendered'] ) ) {
            return $value['rendered'];
        }
        return $value;
    }

    public static function extract_category_names( $data ) {
        $cats = array();

        $raw = self::get( $data, 'categories', array() );
        if ( is_array( $raw ) ) {
            foreach ( $raw as $item ) {
                if ( is_string( $item ) ) {
                    $cats[] = $item;
                } elseif ( is_array( $item ) ) {
                    if ( isset( $item['slug'] ) ) {
                        $cats[] = $item['slug'];
                    } elseif ( isset( $item['name'] ) ) {
                        $cats[] = $item['name'];
                    } elseif ( isset( $item['term_id'] ) ) {
                        $t = get_term( (int) $item['term_id'] );
                        if ( $t && ! is_wp_error( $t ) ) {
                            $cats[] = $t->slug;
                        }
                    }
                } elseif ( is_numeric( $item ) ) {
                    $t = get_term( (int) $item );
                    if ( $t && ! is_wp_error( $t ) ) {
                        $cats[] = $t->slug;
                    } else {
                        $cats[] = (string) $item;
                    }
                }
            }
        } elseif ( is_string( $raw ) ) {
            $cats = array_map( 'trim', explode( ',', $raw ) );
        }

        if ( empty( $cats ) && isset( $data['tax_input'] ) && is_array( $data['tax_input'] ) ) {
            foreach ( $data['tax_input'] as $tax => $terms ) {
                if ( in_array( $tax, array( 'category', 'doc_vista_category' ), true ) ) {
                    if ( is_array( $terms ) ) {
                        foreach ( $terms as $t ) {
                            if ( is_string( $t ) ) {
                                $cats[] = $t;
                            }
                        }
                    }
                }
            }
        }

        return array_unique( $cats );
    }

    public static function extract_tag_names( $data ) {
        $tags = array();

        $raw = self::get( $data, 'tags', array() );
        if ( is_array( $raw ) ) {
            foreach ( $raw as $item ) {
                if ( is_string( $item ) ) {
                    $tags[] = $item;
                } elseif ( is_array( $item ) && isset( $item['name'] ) ) {
                    $tags[] = $item['name'];
                }
            }
        } elseif ( is_string( $raw ) ) {
            $tags = array_map( 'trim', explode( ',', $raw ) );
        }

        if ( empty( $tags ) && isset( $data['tax_input'] ) && is_array( $data['tax_input'] ) ) {
            if ( isset( $data['tax_input']['post_tag'] ) && is_array( $data['tax_input']['post_tag'] ) ) {
                foreach ( $data['tax_input']['post_tag'] as $t ) {
                    if ( is_string( $t ) ) {
                        $tags[] = $t;
                    }
                }
            }
        }

        return array_unique( $tags );
    }

    public static function extract_custom_fields( $data ) {
        $fields = array();

        $raw = self::get( $data, 'custom_fields', array() );
        if ( is_array( $raw ) ) {
            foreach ( $raw as $key => $value ) {
                if ( strpos( $key, '_' ) === 0 ) {
                    continue;
                }
                if ( in_array( $key, array( 'post_title', 'post_content', 'post_status', 'post_type', 'post_excerpt', 'post_name', 'post_date', 'post_modified', 'post_author', 'post_password' ), true ) ) {
                    continue;
                }
                if ( is_array( $value ) && count( $value ) === 1 ) {
                    $fields[ $key ] = maybe_unserialize( $value[0] );
                } else {
                    $fields[ $key ] = $value;
                }
            }
        }

        if ( isset( $data['meta_input'] ) && is_array( $data['meta_input'] ) ) {
            foreach ( $data['meta_input'] as $key => $value ) {
                $fields[ $key ] = $value;
            }
        }

        return $fields;
    }

    public static function detect_post_type( $data ) {
        $raw = self::get( $data, 'post_type', '' );
        if ( $raw ) {
            return $raw;
        }
        return '';
    }

    public static function is_wordpress_export( $data ) {
        $content_keys = array( 'post_content', 'content', 'content_html', 'post_body' );
        $title_keys   = array( 'post_title', 'title' );

        $has_content = false;
        $has_title   = false;

        foreach ( $content_keys as $k ) {
            if ( isset( $data[ $k ] ) ) {
                $has_content = true;
                break;
            }
        }

        foreach ( $title_keys as $k ) {
            if ( isset( $data[ $k ] ) ) {
                $has_title = true;
                break;
            }
        }

        if ( $has_content && $has_title ) {
            if ( isset( $data['post_type'] ) || isset( $data['type'] ) || isset( $data['post_status'] ) || isset( $data['post_meta'] ) || isset( $data['meta_input'] ) || isset( $data['post_date'] ) ) {
                return true;
            }
            if ( isset( $data['post_title'] ) || isset( $data['post_name'] ) ) {
                return true;
            }
        }

        return $has_content && $has_title;
    }
}
