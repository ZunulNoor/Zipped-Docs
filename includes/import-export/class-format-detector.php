<?php

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Format_Detector {

    private $adapters = array();

    public function __construct() {
        $this->register_builtin_adapters();
    }

    private function register_builtin_adapters() {
        $adapter_dir = __DIR__ . '/adapters';
        $files = glob( $adapter_dir . '/class-*-adapter.php' );
        if ( ! $files ) {
            return;
        }
        foreach ( $files as $file ) {
            require_once $file;
            $class_name = $this->get_class_name_from_file( $file );
            if ( $class_name && class_exists( $class_name ) ) {
                $adapter = new $class_name();
                if ( $adapter instanceof Zipped_Docs_Import_Adapter ) {
                    $this->adapters[] = $adapter;
                }
            }
        }
    }

    private function get_class_name_from_file( $file ) {
        $basename = basename( $file, '.php' );
        $parts = explode( '-', $basename );
        array_shift( $parts );
        array_pop( $parts );
        $name_parts = array();
        foreach ( $parts as $part ) {
            $name_parts[] = ucfirst( $part );
        }
        return 'Zipped_Docs_' . implode( '_', $name_parts ) . '_Adapter';
    }

    public function register_adapter( Zipped_Docs_Import_Adapter $adapter ) {
        $this->adapters[] = $adapter;
    }

    public function detect( $data ) {
        foreach ( $this->adapters as $adapter ) {
            if ( $adapter->supports( $data ) ) {
                return $adapter;
            }
        }
        return null;
    }

    public function analyze_structure( $data ) {
        $diagnostics = array(
            'structure_type' => 'unknown',
            'is_array'       => false,
            'is_collection'  => false,
            'count'          => 0,
            'sample_keys'    => array(),
            'detected_keys'  => array(),
            'has_meta'       => false,
            'has_tax'        => false,
            'has_blocks'     => false,
            'has_media'      => false,
            'has_dates'      => false,
            'has_author'     => false,
        );

        if ( ! is_array( $data ) ) {
            return $diagnostics;
        }

        $diagnostics['is_array'] = true;

        if ( isset( $data['_zipped_docs_export'] ) && isset( $data['documents'] ) ) {
            $diagnostics['structure_type'] = 'zipped_docs_wrapper';
            $diagnostics['count'] = count( $data['documents'] );
            if ( isset( $data['documents'][0] ) && is_array( $data['documents'][0] ) ) {
                $diagnostics['sample_keys'] = array_keys( $data['documents'][0] );
                $this->analyze_keys( $diagnostics, $data['documents'][0] );
            }
            return $diagnostics;
        }

        if ( isset( $data[0] ) && is_array( $data[0] ) ) {
            $diagnostics['structure_type'] = 'collection';
            $diagnostics['is_collection'] = true;
            $diagnostics['count'] = count( $data );
            $diagnostics['sample_keys'] = array_keys( $data[0] );
            $this->analyze_keys( $diagnostics, $data[0] );
            return $diagnostics;
        }

        $diagnostics['structure_type'] = 'single';
        $diagnostics['count'] = 1;
        $diagnostics['sample_keys'] = array_keys( $data );
        $this->analyze_keys( $diagnostics, $data );
        return $diagnostics;
    }

    private function analyze_keys( &$diagnostics, $sample ) {
        $detected = array();

        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'title' ) ) {
            $detected[] = 'title';
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'content' ) ) {
            $detected[] = 'content';
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'slug' ) ) {
            $detected[] = 'slug';
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'excerpt' ) ) {
            $detected[] = 'excerpt';
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'status' ) ) {
            $detected[] = 'status';
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'author' ) ) {
            $detected[] = 'author';
            $diagnostics['has_author'] = true;
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'created_date' ) ) {
            $detected[] = 'date';
            $diagnostics['has_dates'] = true;
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'featured_image' ) ) {
            $detected[] = 'image';
            $diagnostics['has_media'] = true;
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'custom_fields' ) || isset( $sample['meta_input'] ) ) {
            $detected[] = 'meta';
            $diagnostics['has_meta'] = true;
        }
        if ( isset( $sample['tax_input'] ) || Zipped_Docs_Field_Mapper::has_any_field( $sample, 'categories' ) ) {
            $detected[] = 'tax';
            $diagnostics['has_tax'] = true;
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'tags' ) ) {
            $detected[] = 'tags';
        }
        $has_gutenberg_content = false;
        if ( isset( $sample['content'] ) && is_string( $sample['content'] ) && preg_match( '/<!--\s+wp:/', $sample['content'] ) ) {
            $has_gutenberg_content = true;
        } elseif ( isset( $sample['post_content'] ) && is_string( $sample['post_content'] ) && preg_match( '/<!--\s+wp:/', $sample['post_content'] ) ) {
            $has_gutenberg_content = true;
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'gutenberg_blocks' ) || $has_gutenberg_content ) {
            $detected[] = 'blocks';
            $diagnostics['has_blocks'] = true;
        }
        if ( Zipped_Docs_Field_Mapper::has_any_field( $sample, 'attachments' ) ) {
            $detected[] = 'attachments';
            $diagnostics['has_media'] = true;
        }
        if ( isset( $sample['_thumbnail_id'] ) ) {
            $detected[] = 'thumbnail_id';
            $diagnostics['has_media'] = true;
        }

        $diagnostics['detected_keys'] = $detected;
    }

    public function get_format_label( $data, $detected_adapter = null ) {
        if ( $detected_adapter ) {
            $class = get_class( $detected_adapter );
            $labels = array(
                'Zipped_Docs_Native_Adapter' => __( 'Zipped Docs Export', 'zipped-docs' ),
                'Zipped_Docs_Wordpress_Adapter' => __( 'WordPress Export', 'zipped-docs' ),
                'Zipped_Docs_Post_Page_Export_Adapter' => __( 'Post/Page Export (with Custom Fields)', 'zipped-docs' ),
                'Zipped_Docs_Gutenberg_Adapter' => __( 'Gutenberg Block Export', 'zipped-docs' ),
            );
            return isset( $labels[ $class ] ) ? $labels[ $class ] : __( 'Unknown Format', 'zipped-docs' );
        }

        if ( isset( $data['_zipped_docs_export'] ) ) {
            return __( 'Zipped Docs Export', 'zipped-docs' );
        }

        if ( isset( $data[0] ) && is_array( $data[0] ) ) {
            $sample = $data[0];
            if ( isset( $sample['post_type'] ) ) {
                if ( 'post' === $sample['post_type'] ) {
                    return __( 'WordPress Post Export', 'zipped-docs' );
                }
                if ( 'page' === $sample['post_type'] ) {
                    return __( 'WordPress Page Export', 'zipped-docs' );
                }
            }
            if ( isset( $sample['type'] ) ) {
                if ( 'post' === $sample['type'] ) {
                    return __( 'WordPress Post Export', 'zipped-docs' );
                }
                if ( 'page' === $sample['type'] ) {
                    return __( 'WordPress Page Export', 'zipped-docs' );
                }
            }
            if ( isset( $sample['post_title'] ) ) {
                $post_type = isset( $sample['post_type'] ) ? $sample['post_type'] : ( isset( $sample['type'] ) ? $sample['type'] : '' );
                if ( $post_type ) {
                    /* translators: %s: post type label, e.g. "Post" or "Page". */
                    return sprintf( __( 'WordPress %s Export', 'zipped-docs' ), ucfirst( $post_type ) );
                }
                if ( isset( $sample['post_meta'] ) || isset( $sample['tax_input'] ) ) {
                    return __( 'WordPress Export', 'zipped-docs' );
                }
                return __( 'WordPress Export', 'zipped-docs' );
            }
            if ( isset( $sample['title'] ) && isset( $sample['content'] ) ) {
                if ( preg_match( '/<!--\s+wp:/', $sample['content'] ) ) {
                    return __( 'Gutenberg Export', 'zipped-docs' );
                }
                return __( 'Content Export', 'zipped-docs' );
            }
        }

        if ( isset( $data['post_title'] ) || isset( $data['title'] ) ) {
            return __( 'WordPress Export', 'zipped-docs' );
        }

        return __( 'Content Export', 'zipped-docs' );
    }

    public function get_supported_labels() {
        return array(
            __( 'Zipped Docs JSON', 'zipped-docs' ),
            __( 'WordPress Page JSON', 'zipped-docs' ),
            __( 'WordPress Post JSON', 'zipped-docs' ),
            __( 'Gutenberg Block JSON', 'zipped-docs' ),
            __( 'Post/Page Import Export JSON', 'zipped-docs' ),
        );
    }
}
