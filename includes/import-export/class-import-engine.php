<?php

defined( 'ABSPATH' ) || exit;

class Doc_Vista_Import_Engine {

    private $detector;
    private $imported_ids = array();
    private $errors       = array();
    private $warnings     = array();
    private $chunk_size   = 50;

    public function __construct() {
        $this->detector = new Doc_Vista_Format_Detector();
    }

    public function process_upload() {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in the AJAX handler (class-admin-ui.php).
        if ( ! isset( $_FILES['doc_vista_import_file'] ) ) {
            return new WP_Error( 'no_file', __( 'No file was uploaded.', 'doc-vista' ) );
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Upload array handled by wp_handle_upload below; nonce verified in AJAX handler.
        $file = $_FILES['doc_vista_import_file'];

        if ( UPLOAD_ERR_OK !== $file['error'] ) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the server\'s upload_max_filesize directive.', 'doc-vista' ),
                UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive.', 'doc-vista' ),
                UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.', 'doc-vista' ),
                UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'doc-vista' ),
                UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.', 'doc-vista' ),
                UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'doc-vista' ),
            );
            $msg = isset( $error_messages[ $file['error'] ] ) ? $error_messages[ $file['error'] ] : __( 'Unknown upload error.', 'doc-vista' );
            return new WP_Error( 'upload_error', $msg );
        }

        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( 'json' !== $file_ext ) {
            return new WP_Error( 'invalid_format', __( 'Only .json files are supported.', 'doc-vista' ) );
        }

        $file_size = filesize( $file['tmp_name'] );
        $max_size  = wp_max_upload_size();
        if ( false !== $max_size && $file_size > $max_size ) {
            return new WP_Error( 'file_too_large', sprintf(
                /* translators: 1: uploaded file size, 2: maximum allowed upload size. */
                __( 'The uploaded file (%1$s) exceeds the maximum allowed upload size (%2$s).', 'doc-vista' ),
                size_format( $file_size ),
                size_format( $max_size )
            ) );
        }

        $data = $this->read_json_file( $file['tmp_name'] );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return $this->process_data( $data );
    }

    private function read_json_file( $path ) {
        global $wp_filesystem;

        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $buffer = $wp_filesystem->get_contents( $path );

        if ( false === $buffer ) {
            return new WP_Error( 'read_error', __( 'Could not open the uploaded file for reading.', 'doc-vista' ) );
        }

        $memory_limit = $this->get_php_memory_limit();
        if ( strlen( $buffer ) > $memory_limit * 0.5 ) {
            return new WP_Error( 'file_too_large', __( 'The file is too large to process. Try increasing PHP memory_limit.', 'doc-vista' ) );
        }

        $data = json_decode( $buffer, true );
        if ( JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_Error( 'invalid_json', sprintf(
                /* translators: %s: JSON parser error message. */
                __( 'Invalid JSON: %s', 'doc-vista' ),
                json_last_error_msg()
            ) );
        }

        return $data;
    }

    private function get_php_memory_limit() {
        $limit = ini_get( 'memory_limit' );
        if ( '-1' === $limit ) {
            return PHP_INT_MAX;
        }
        $unit = strtolower( substr( $limit, -1 ) );
        $value = (int) $limit;
        switch ( $unit ) {
            case 'g': $value *= 1073741824; break;
            case 'm': $value *= 1048576; break;
            case 'k': $value *= 1024; break;
        }
        return $value;
    }

    public function preview_upload() {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in the AJAX handler (class-admin-ui.php).
        if ( ! isset( $_FILES['doc_vista_import_file'] ) ) {
            return new WP_Error( 'no_file', __( 'No file was uploaded.', 'doc-vista' ) );
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Upload array parsed directly; nonce verified in AJAX handler.
        $file = $_FILES['doc_vista_import_file'];
        if ( UPLOAD_ERR_OK !== $file['error'] ) {
            return new WP_Error( 'upload_error', __( 'File upload error.', 'doc-vista' ) );
        }

        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( 'json' !== $file_ext ) {
            return new WP_Error( 'invalid_format', __( 'Only .json files are supported.', 'doc-vista' ) );
        }

        $file_size = filesize( $file['tmp_name'] );
        $max_size  = wp_max_upload_size();
        if ( false !== $max_size && $file_size > $max_size ) {
            return new WP_Error( 'file_too_large', sprintf(
                /* translators: 1: uploaded file size, 2: maximum allowed upload size. */
                __( 'The uploaded file (%1$s) exceeds the maximum allowed upload size (%2$s).', 'doc-vista' ),
                size_format( $file_size ),
                size_format( $max_size )
            ) );
        }

        $data = $this->read_json_file( $file['tmp_name'] );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return $this->generate_preview( $data );
    }

    public function generate_preview( $data ) {
        $diagnostics = $this->detector->analyze_structure( $data );

        $preview = array(
            'structure_type'  => $diagnostics['structure_type'],
            'is_collection'   => $diagnostics['is_collection'],
            'document_count'  => $diagnostics['count'],
            'detected_keys'   => $diagnostics['detected_keys'],
            'has_meta'        => $diagnostics['has_meta'],
            'has_tax'         => $diagnostics['has_tax'],
            'has_blocks'      => $diagnostics['has_blocks'],
            'has_media'       => $diagnostics['has_media'],
            'has_dates'       => $diagnostics['has_dates'],
            'has_author'      => $diagnostics['has_author'],
            'format_label'    => '',
            'has_content'     => false,
            'total_categories' => 0,
            'total_images'    => 0,
            'blocks_supported' => true,
            'can_import'      => false,
            'error_message'   => '',
            'sample_docs'     => array(),
        );

        $documents = $this->normalize_input( $data );

        if ( empty( $documents ) ) {
            $preview['error_message'] = Doc_Vista_Normalizer::get_error_reason( $data, $diagnostics );
            return $preview;
        }

        $preview['can_import'] = true;
        $preview['document_count'] = count( $documents );
        $preview['has_content'] = true;

        if ( isset( $data[0] ) && is_array( $data[0] ) ) {
            $preview['format_label'] = $this->detector->get_format_label( $data );
        } else {
            $detected = $this->detector->detect( $data );
            $preview['format_label'] = $this->detector->get_format_label( $data, $detected );
        }

        $all_cats = array();
        $image_count = 0;
        foreach ( $documents as $doc ) {
            $all_cats = array_merge( $all_cats, $doc['categories'] );
            if ( ! empty( $doc['featured_image'] ) ) {
                $image_count++;
            }
            if ( ! empty( $doc['attachments'] ) ) {
                $image_count += count( $doc['attachments'] );
            }
        }
        $preview['total_categories'] = count( array_unique( $all_cats ) );
        $preview['total_images'] = $image_count;

        $sample_count = min( 3, count( $documents ) );
        for ( $i = 0; $i < $sample_count; $i++ ) {
            $preview['sample_docs'][] = array(
                'title' => $documents[ $i ]['title'],
                'slug'  => $documents[ $i ]['slug'],
            );
        }

        $conflicts = array();
        foreach ( $documents as $index => $doc ) {
            $existing = $this->find_existing( $doc );
            if ( $existing ) {
                $conflicts[] = array(
                    'index'          => $index,
                    'doc'            => $doc,
                    'existing_id'    => $existing['id'],
                    'existing_title' => $existing['title'],
                    'existing_slug'  => $existing['slug'],
                );
            }
        }
        $preview['conflict_count'] = count( $conflicts );

        return $preview;
    }

    public function process_data( $data ) {
        $this->imported_ids = array();
        $this->errors       = array();
        $this->warnings     = array();

        $documents = $this->normalize_input( $data );
        if ( empty( $documents ) ) {
            $diagnostics = $this->detector->analyze_structure( $data );
            return new WP_Error( 'no_documents', Doc_Vista_Normalizer::get_error_reason( $data, $diagnostics ) );
        }

        $results = array(
            'imported'  => array(),
            'skipped'   => array(),
            'replaced'  => array(),
            'updated'   => array(),
            'errors'    => array(),
            'warnings'  => array(),
            'conflicts' => array(),
        );

        $chunks = array_chunk( $documents, $this->chunk_size );
        foreach ( $chunks as $chunk ) {
            foreach ( $chunk as $index => $doc ) {
                $validation_errors = Doc_Vista_Normalizer::validate( $doc );
                if ( ! empty( $validation_errors ) ) {
                    /* translators: %d: numeric index of the document being imported. */
                    $title = ! empty( $doc['title'] ) ? $doc['title'] : sprintf( __( 'Document #%d', 'doc-vista' ), $index + 1 );
                    $results['errors'][] = sprintf(
                        /* translators: 1: document title, 2: validation error message. */
                        __( '%1$s: %2$s', 'doc-vista' ),
                        esc_html( $title ),
                        implode( ' ', $validation_errors )
                    );
                    continue;
                }

                $existing = $this->find_existing( $doc );

                if ( $existing ) {
                    $results['conflicts'][] = array(
                        'index'          => $index,
                        'doc'            => $doc,
                        'existing_id'    => $existing['id'],
                        'existing_title' => $existing['title'],
                        'existing_slug'  => $existing['slug'],
                    );
                } else {
                    $result = $this->create_document( $doc );
                    if ( is_wp_error( $result ) ) {
                        $results['errors'][] = sprintf(
                            /* translators: 1: document title, 2: error message. */
                            __( '%1$s: %2$s', 'doc-vista' ),
                            esc_html( $doc['title'] ),
                            $result->get_error_message()
                        );
                    } else {
                        $results['imported'][] = $result;
                        $this->imported_ids[]  = $result;
                    }
                }
            }

            if ( ! empty( $this->imported_ids ) ) {
                wp_cache_flush();
            }
        }

        if ( ! empty( $results['conflicts'] ) ) {
            $results['has_conflicts'] = true;
            return $results;
        }

        $this->finalize();
        return $results;
    }

    public function process_with_decisions( $decisions ) {
        $this->imported_ids = array();
        $this->errors       = array();
        $this->warnings     = array();

        $results = array(
            'imported'  => array(),
            'skipped'   => array(),
            'replaced'  => array(),
            'updated'   => array(),
            'errors'    => array(),
            'warnings'  => array(),
        );

        if ( ! isset( $decisions['documents'] ) || ! is_array( $decisions['documents'] ) ) {
            return new WP_Error( 'invalid_decisions', __( 'No import decisions provided.', 'doc-vista' ) );
        }

        $chunks = array_chunk( $decisions['documents'], $this->chunk_size );
        foreach ( $chunks as $chunk ) {
            foreach ( $chunk as $item ) {
                $doc      = isset( $item['doc'] ) ? $item['doc'] : array();
                $decision = isset( $item['decision'] ) ? $item['decision'] : 'skip';
                $existing_id = isset( $item['existing_id'] ) ? (int) $item['existing_id'] : 0;

                if ( 'skip' === $decision ) {
                    $title = ! empty( $doc['title'] ) ? $doc['title'] : __( 'Untitled', 'doc-vista' );
                    $results['skipped'][] = $title;
                    continue;
                }

                if ( 'replace' === $decision && $existing_id ) {
                    $deleted = wp_delete_post( $existing_id, true );
                    if ( ! $deleted ) {
                        $results['errors'][] = sprintf(
                            /* translators: %d: ID of the existing document. */
                            __( 'Could not delete existing document %d.', 'doc-vista' ),
                            $existing_id
                        );
                        continue;
                    }
                    $result = $this->create_document( $doc );
                    if ( is_wp_error( $result ) ) {
                        $results['errors'][] = sprintf(
                            /* translators: 1: document title, 2: error message. */
                            __( '%1$s: %2$s', 'doc-vista' ),
                            esc_html( $doc['title'] ),
                            $result->get_error_message()
                        );
                    } else {
                        $results['replaced'][] = $result;
                        $this->imported_ids[]  = $result;
                    }
                } elseif ( 'update' === $decision && $existing_id ) {
                    $result = $this->update_document( $existing_id, $doc );
                    if ( is_wp_error( $result ) ) {
                        $results['errors'][] = sprintf(
                            /* translators: 1: document title, 2: error message. */
                            __( '%1$s: %2$s', 'doc-vista' ),
                            esc_html( $doc['title'] ),
                            $result->get_error_message()
                        );
                    } else {
                        $results['updated'][] = $result;
                        $this->imported_ids[]  = $result;
                    }
                } elseif ( 'create' === $decision ) {
                    $result = $this->create_document( $doc );
                    if ( is_wp_error( $result ) ) {
                        $results['errors'][] = sprintf(
                            /* translators: 1: document title, 2: error message. */
                            __( '%1$s: %2$s', 'doc-vista' ),
                            esc_html( $doc['title'] ),
                            $result->get_error_message()
                        );
                    } else {
                        $results['imported'][] = $result;
                        $this->imported_ids[]  = $result;
                    }
                }
            }

            if ( ! empty( $this->imported_ids ) ) {
                wp_cache_flush();
            }
        }

        $this->finalize();
        return $results;
    }

    private function normalize_input( $data ) {
        if ( isset( $data['_doc_vista_export'] ) && true === $data['_doc_vista_export'] ) {
            if ( isset( $data['documents'] ) && is_array( $data['documents'] ) ) {
                $documents = array();
                $doc_vista_fallback = null;
                foreach ( $data['documents'] as $raw ) {
                    $detected = $this->detector->detect( $raw );
                    if ( ! $detected ) {
                        if ( null === $doc_vista_fallback ) {
                            $doc_vista_fallback = new Doc_Vista_Docvista_Adapter();
                        }
                        $detected = $doc_vista_fallback;
                    }
                    if ( $detected ) {
                        $documents[] = $detected->normalize( $raw );
                    }
                }
                return $documents;
            }
            return array();
        }

        if ( isset( $data[0] ) && is_array( $data[0] ) ) {
            $documents = array();
            foreach ( $data as $raw ) {
                $detected = $this->detector->detect( $raw );
                if ( $detected ) {
                    $documents[] = $detected->normalize( $raw );
                }
            }
            if ( ! empty( $documents ) ) {
                return $documents;
            }
        }

        if ( isset( $data['posts'] ) && is_array( $data['posts'] ) ) {
            $documents = array();
            foreach ( $data['posts'] as $raw ) {
                $detected = $this->detector->detect( $raw );
                if ( $detected ) {
                    $documents[] = $detected->normalize( $raw );
                }
            }
            if ( ! empty( $documents ) ) {
                return $documents;
            }
        }

        if ( isset( $data['pages'] ) && is_array( $data['pages'] ) ) {
            $documents = array();
            foreach ( $data['pages'] as $raw ) {
                $detected = $this->detector->detect( $raw );
                if ( $detected ) {
                    $documents[] = $detected->normalize( $raw );
                }
            }
            if ( ! empty( $documents ) ) {
                return $documents;
            }
        }

        if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
            $documents = array();
            foreach ( $data['items'] as $raw ) {
                $detected = $this->detector->detect( $raw );
                if ( $detected ) {
                    $documents[] = $detected->normalize( $raw );
                }
            }
            if ( ! empty( $documents ) ) {
                return $documents;
            }
        }

        if ( isset( $data['data'] ) && is_array( $data['data'] ) && isset( $data['data'][0] ) ) {
            $documents = array();
            foreach ( $data['data'] as $raw ) {
                $detected = $this->detector->detect( $raw );
                if ( $detected ) {
                    $documents[] = $detected->normalize( $raw );
                }
            }
            if ( ! empty( $documents ) ) {
                return $documents;
            }
        }

        if ( isset( $data['post_data'] ) && is_array( $data['post_data'] ) ) {
            $inner = $data['post_data'];
            if ( isset( $data['post_meta'] ) ) {
                $inner['post_meta'] = $data['post_meta'];
            }
            if ( isset( $data['taxonomies'] ) ) {
                $inner['taxonomies'] = $data['taxonomies'];
            }
            if ( isset( $data['feature_img'] ) ) {
                $inner['feature_img'] = $data['feature_img'];
            }
            if ( isset( $data['acf_fields'] ) ) {
                $inner['acf_fields'] = $data['acf_fields'];
            }
            if ( ! isset( $inner['post_type'] ) && isset( $data['post_type'] ) ) {
                $inner['post_type'] = $data['post_type'];
            }
            $detected = $this->detector->detect( $inner );
            if ( $detected ) {
                return array( $detected->normalize( $inner ) );
            }
        }

        $detected = $this->detector->detect( $data );
        if ( $detected ) {
            return array( $detected->normalize( $data ) );
        }

        return array();
    }

    private function find_existing( $doc ) {
        if ( ! empty( $doc['slug'] ) ) {
            $existing = get_posts( array(
                'post_type'      => 'doc_vista_doc',
                'name'           => $doc['slug'],
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ) );
            if ( ! empty( $existing ) ) {
                $post = get_post( $existing[0] );
                return array(
                    'id'    => $post->ID,
                    'title' => $post->post_title,
                    'slug'  => $post->post_name,
                );
            }
        }

        if ( ! empty( $doc['title'] ) ) {
            $existing = get_posts( array(
                'post_type'      => 'doc_vista_doc',
                'title'          => $doc['title'],
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'exact'          => true,
            ) );
            if ( ! empty( $existing ) ) {
                $post = get_post( $existing[0] );
                return array(
                    'id'    => $post->ID,
                    'title' => $post->post_title,
                    'slug'  => $post->post_name,
                );
            }
        }

        return null;
    }

    private function create_document( $doc ) {
        $post_data = array(
            'post_title'   => $doc['title'],
            'post_content' => $doc['content'],
            'post_status'  => $doc['status'] ?: 'draft',
            'post_type'    => 'doc_vista_doc',
            'post_excerpt' => $doc['excerpt'],
            'post_author'  => $doc['author'] ?: get_current_user_id(),
        );

        if ( ! empty( $doc['slug'] ) ) {
            $post_data['post_name'] = $doc['slug'];
        }

        if ( ! empty( $doc['created_date'] ) ) {
            $post_data['post_date'] = $doc['created_date'];
        }

        if ( ! empty( $doc['modified_date'] ) ) {
            $post_data['post_modified'] = $doc['modified_date'];
        }

        if ( ! empty( $doc['menu_order'] ) ) {
            $post_data['menu_order'] = (int) $doc['menu_order'];
        }

        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $this->set_categories( $post_id, $doc );
        $this->set_tags( $post_id, $doc );
        $this->set_custom_fields( $post_id, $doc );
        $this->handle_featured_image( $post_id, $doc );
        $this->handle_missing_media( $post_id, $doc );

        if ( ! empty( $doc['template'] ) ) {
            update_post_meta( $post_id, '_wp_page_template', sanitize_text_field( $doc['template'] ) );
        }

        if ( isset( $doc['gutenberg_blocks'] ) && is_array( $doc['gutenberg_blocks'] ) && ! isset( $doc['gutenberg_blocks']['detected'] ) ) {
            update_post_meta( $post_id, '_doc_vista_gutenberg_blocks', $doc['gutenberg_blocks'] );
        }

        update_post_meta( $post_id, '_doc_vista_order', (int) $doc['menu_order'] );

        return $post_id;
    }

    private function update_document( $post_id, $doc ) {
        $post_data = array(
            'ID'            => $post_id,
            'post_title'    => $doc['title'],
            'post_content'  => $doc['content'],
            'post_excerpt'  => $doc['excerpt'],
        );

        if ( ! empty( $doc['status'] ) ) {
            $post_data['post_status'] = $doc['status'];
        }

        if ( ! empty( $doc['slug'] ) ) {
            $post_data['post_name'] = $doc['slug'];
        }

        if ( ! empty( $doc['menu_order'] ) ) {
            $post_data['menu_order'] = (int) $doc['menu_order'];
        }

        $updated = wp_update_post( $post_data, true );

        if ( is_wp_error( $updated ) ) {
            return $updated;
        }

        $this->set_categories( $post_id, $doc );
        $this->set_tags( $post_id, $doc );
        $this->set_custom_fields( $post_id, $doc );
        $this->handle_featured_image( $post_id, $doc );
        $this->handle_missing_media( $post_id, $doc );

        if ( ! empty( $doc['template'] ) ) {
            update_post_meta( $post_id, '_wp_page_template', sanitize_text_field( $doc['template'] ) );
        }

        update_post_meta( $post_id, '_doc_vista_order', (int) $doc['menu_order'] );

        return $post_id;
    }

    private function set_categories( $post_id, $doc ) {
        if ( empty( $doc['categories'] ) ) {
            return;
        }

        $term_ids = array();
        foreach ( $doc['categories'] as $cat ) {
            if ( is_numeric( $cat ) ) {
                $term = get_term( (int) $cat, 'doc_vista_category' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $term_ids[] = (int) $cat;
                    continue;
                }
            }

            if ( is_string( $cat ) ) {
                $term = term_exists( $cat, 'doc_vista_category' );
                if ( $term ) {
                    $term_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
                } else {
                    $new_term = wp_insert_term( ucfirst( str_replace( array( '-', '_' ), ' ', $cat ) ), 'doc_vista_category', array( 'slug' => sanitize_title( $cat ) ) );
                    if ( ! is_wp_error( $new_term ) ) {
                        $term_ids[] = (int) $new_term['term_id'];
                    }
                }
            }
        }

        if ( ! empty( $term_ids ) ) {
            wp_set_object_terms( $post_id, $term_ids, 'doc_vista_category' );
        }
    }

    private function set_tags( $post_id, $doc ) {
        if ( empty( $doc['tags'] ) ) {
            return;
        }

        $tag_names = array();
        foreach ( $doc['tags'] as $tag ) {
            if ( is_string( $tag ) ) {
                $tag_names[] = $tag;
            }
        }

        if ( ! empty( $tag_names ) ) {
            wp_set_post_tags( $post_id, $tag_names, true );
        }
    }

    private function set_custom_fields( $post_id, $doc ) {
        $fields = $doc['custom_fields'];
        if ( ! is_array( $fields ) ) {
            return;
        }

        foreach ( $fields as $key => $value ) {
            if ( strpos( $key, '_' ) === 0 ) {
                continue;
            }
            if ( in_array( $key, array( 'post_title', 'post_content', 'post_status', 'post_type', 'post_excerpt', 'post_name', 'post_date', 'post_modified', 'post_author', 'post_password' ), true ) ) {
                continue;
            }

            if ( is_array( $value ) ) {
                $value = maybe_serialize( $value );
            }

            if ( is_serialized( $value ) ) {
                update_post_meta( $post_id, sanitize_key( $key ), $value );
            } else {
                update_post_meta( $post_id, sanitize_key( $key ), sanitize_text_field( $value ) );
            }
        }
    }

    private function handle_featured_image( $post_id, $doc ) {
        $image = $doc['featured_image'];
        if ( empty( $image ) ) {
            return;
        }

        if ( is_numeric( $image ) ) {
            $attachment = get_post( (int) $image );
            if ( $attachment && 'attachment' === $attachment->post_type ) {
                set_post_thumbnail( $post_id, (int) $image );
                return;
            }
            return;
        }

        if ( is_string( $image ) && filter_var( $image, FILTER_VALIDATE_URL ) ) {
            $local_id = $this->get_attachment_by_url( $image );
            if ( $local_id ) {
                set_post_thumbnail( $post_id, $local_id );
                return;
            }
            $this->import_media_url( $post_id, $image, true );
        }
    }

    private function get_attachment_by_url( $url ) {
        $attachment = attachment_url_to_postid( $url );
        if ( $attachment ) {
            return $attachment;
        }

        $filename = basename( $url );
        $attachment = get_posts( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'title'          => $filename,
            'exact'          => true,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
        if ( ! empty( $attachment ) ) {
            return (int) $attachment[0];
        }

        return 0;
    }

    private function import_media_url( $post_id, $url, $set_as_thumbnail = false ) {
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attachment_id = media_sideload_image( $url, $post_id, null, 'id' );

        if ( is_wp_error( $attachment_id ) ) {
            $this->warnings[] = sprintf(
                /* translators: 1: media URL, 2: error message. */
                __( 'Could not import media from %1$s: %2$s', 'doc-vista' ),
                esc_url( $url ),
                $attachment_id->get_error_message()
            );
            return false;
        }

        if ( $set_as_thumbnail ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }

        return $attachment_id;
    }

    public function handle_missing_media( $post_id, $doc ) {
        if ( empty( $doc['attachments'] ) ) {
            return;
        }

        foreach ( $doc['attachments'] as $attachment ) {
            if ( is_numeric( $attachment ) ) {
                $existing = get_post( (int) $attachment );
                if ( $existing && 'attachment' === $existing->post_type ) {
                    continue;
                }
                $this->warnings[] = sprintf(
                    /* translators: %d: ID of a missing attachment. */
                    __( 'Attachment ID %d not found. Document imported without it.', 'doc-vista' ),
                    (int) $attachment
                );
            } elseif ( is_string( $attachment ) && filter_var( $attachment, FILTER_VALIDATE_URL ) ) {
                $local_id = $this->get_attachment_by_url( $attachment );
                if ( $local_id ) {
                    continue;
                }
                $result = $this->import_media_url( $post_id, $attachment, false );
                if ( ! $result ) {
                    $this->warnings[] = sprintf(
                        /* translators: %s: media URL. */
                        __( 'Could not import media from URL: %s. External URL kept as-is.', 'doc-vista' ),
                        esc_url( $attachment )
                    );
                }
            }
        }
    }

    private function finalize() {
        if ( ! empty( $this->imported_ids ) ) {
            doc_vista_rebuild_graph();
        }

        if ( ! empty( $this->warnings ) ) {
            $existing_warnings = get_transient( 'doc_vista_import_warnings' );
            if ( false === $existing_warnings ) {
                $existing_warnings = array();
            }
            $existing_warnings = array_merge( $existing_warnings, $this->warnings );
            set_transient( 'doc_vista_import_warnings', $existing_warnings, HOUR_IN_SECONDS );
        }
    }

    public function get_detector() {
        return $this->detector;
    }
}
