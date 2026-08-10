<?php

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Import_Export_Admin {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_zipped_docs_import_preview', array( $this, 'ajax_import_preview' ) );
        add_action( 'wp_ajax_zipped_docs_import_upload', array( $this, 'ajax_import_upload' ) );
        add_action( 'wp_ajax_zipped_docs_import_process', array( $this, 'ajax_import_process' ) );
        add_action( 'wp_ajax_zipped_docs_export', array( $this, 'ajax_export' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'zipped-docs' ) === false ) {
            return;
        }

        wp_enqueue_script(
            'zipped-docs-import-export',
            ZIPPED_DOCS_ASSETS . 'zipped-docs-import-export.js',
            array( 'zipped-docs-admin' ),
            ZIPPED_DOCS_VERSION,
            true
        );

        $max_upload = wp_max_upload_size();

        wp_localize_script( 'zipped-docs-import-export', 'ZIPPED_DOCS_IE', array(
            'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
            'importNonce'           => wp_create_nonce( 'zipped_docs_import_nonce' ),
            'exportNonce'           => wp_create_nonce( 'zipped_docs_export_nonce' ),
            'maxUploadSize'         => $max_upload,
            'maxUploadSizeFormatted' => size_format( $max_upload ),
            'maxUploadSizeIni'      => ini_get( 'upload_max_filesize' ),
            'postMaxSize'           => ini_get( 'post_max_size' ),
            'themeColor'            => zipped_docs_get_settings()['zipped_docs_theme_color'] ?? '#2563EB',
            'i18n'                  => array(
                'importTitle'            => __( 'Import Documentation', 'zipped-docs' ),
                'exportTitle'            => __( 'Export Documentation', 'zipped-docs' ),
                'dragDrop'               => __( 'Drag & drop your JSON file here', 'zipped-docs' ),
                'browseFile'             => __( 'Browse Files', 'zipped-docs' ),
                'supportedFormats'       => __( 'Supported Formats', 'zipped-docs' ),
                'processing'             => __( 'Processing...', 'zipped-docs' ),
                'importComplete'         => __( 'Import Complete', 'zipped-docs' ),
                'exportComplete'         => __( 'Export Complete', 'zipped-docs' ),
                'imported'               => __( 'Imported', 'zipped-docs' ),
                'replaced'               => __( 'Replaced', 'zipped-docs' ),
                'updated'                => __( 'Updated', 'zipped-docs' ),
                'skipped'                => __( 'Skipped', 'zipped-docs' ),
                'errors'                 => __( 'Errors', 'zipped-docs' ),
                'conflictsFound'         => __( 'Some documents already exist', 'zipped-docs' ),
                'conflictMessage'        => __( 'How would you like to handle each conflict?', 'zipped-docs' ),
                'existing'               => __( 'Existing', 'zipped-docs' ),
                'incoming'               => __( 'Incoming', 'zipped-docs' ),
                'createNew'              => __( 'Create New', 'zipped-docs' ),
                'replaceExisting'        => __( 'Replace Existing', 'zipped-docs' ),
                'updateExisting'         => __( 'Update Existing', 'zipped-docs' ),
                'skip'                   => __( 'Skip', 'zipped-docs' ),
                'applyAll'               => __( 'Apply to all', 'zipped-docs' ),
                'close'                  => __( 'Close', 'zipped-docs' ),
                'uploadError'            => __( 'Upload Error', 'zipped-docs' ),
                'invalidFile'            => __( 'Invalid file type. Only .json files are supported.', 'zipped-docs' ),
                'fileTooLarge'           => __( 'File exceeds maximum upload size.', 'zipped-docs' ),
                'noFileSelected'         => __( 'No file selected.', 'zipped-docs' ),
                'noDocsSelected'         => __( 'Please select at least one document to export.', 'zipped-docs' ),
                'exporting'              => __( 'Exporting...', 'zipped-docs' ),
                'documents'              => __( 'documents', 'zipped-docs' ),
                'importAnother'          => __( 'Import Another File', 'zipped-docs' ),
                'viewDocs'               => __( 'View Docs', 'zipped-docs' ),
                'missingMedia'           => __( 'Media Warnings', 'zipped-docs' ),
                'unknownFormat'          => __( 'Unknown Format', 'zipped-docs' ),
                'unknownFormatDesc'      => __( 'The uploaded file does not match any supported format.', 'zipped-docs' ),
                'fileInfo'               => __( 'File', 'zipped-docs' ),
                'detectedFormat'         => __( 'Detected Format', 'zipped-docs' ),
                'previewTitle'           => __( 'Import Preview', 'zipped-docs' ),
                'readyToImport'          => __( 'Ready to import', 'zipped-docs' ),
                'documentsFound'         => __( 'documents found', 'zipped-docs' ),
                'categoriesFound'        => __( 'Categories', 'zipped-docs' ),
                'imagesFound'            => __( 'Images', 'zipped-docs' ),
                'gutenbergSupported'     => __( 'Blocks', 'zipped-docs' ),
                'supported'              => __( 'Supported', 'zipped-docs' ),
                'metadata'               => __( 'Metadata', 'zipped-docs' ),
                'dates'                  => __( 'Dates', 'zipped-docs' ),
                'author'                 => __( 'Author', 'zipped-docs' ),
                'yes'                    => __( 'Yes', 'zipped-docs' ),
                'no'                     => __( 'No', 'zipped-docs' ),
                'proceedImport'          => __( 'Proceed Import', 'zipped-docs' ),
                'cancel'                 => __( 'Cancel', 'zipped-docs' ),
                'maxUploadSizeMsg'       => sprintf(
                    /* translators: %s: formatted maximum allowed upload size, e.g. "64 MB". */
                    __( 'Maximum upload size: %s', 'zipped-docs' ),
                    size_format( $max_upload )
                ),
            ),
        ) );
    }

    public function ajax_import_preview() {
        if ( ! current_user_can( 'zipped_docs_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to import.', 'zipped-docs' ) ) );
        }

        check_ajax_referer( 'zipped_docs_import_nonce', '_wpnonce' );

        $engine = new Zipped_Docs_Import_Engine();
        $result = $engine->preview_upload();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    public function ajax_import_upload() {
        if ( ! current_user_can( 'zipped_docs_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to import.', 'zipped-docs' ) ) );
        }

        check_ajax_referer( 'zipped_docs_import_nonce', '_wpnonce' );

        $engine = new Zipped_Docs_Import_Engine();
        $result = $engine->process_upload();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    public function ajax_import_process() {
        if ( ! current_user_can( 'zipped_docs_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to import.', 'zipped-docs' ) ) );
        }

        check_ajax_referer( 'zipped_docs_import_nonce', '_wpnonce' );

        // JSON payload must be decoded before its individual fields can be validated.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decoded then validated per-field below and in the import engine.
        $raw = isset( $_POST['decisions'] ) ? (string) wp_unslash( $_POST['decisions'] ) : '';
        $decisions = json_decode( $raw, true );

        if ( ! is_array( $decisions ) || JSON_ERROR_NONE !== json_last_error() || ! isset( $decisions['documents'] ) || ! is_array( $decisions['documents'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid import decisions.', 'zipped-docs' ) ) );
        }

        $allowed_choices = array( 'create', 'replace', 'update', 'skip' );
        foreach ( $decisions['documents'] as $key => $item ) {
            if ( ! is_array( $item ) ) {
                unset( $decisions['documents'][ $key ] );
                continue;
            }
            $decisions['documents'][ $key ]['existing_id'] = isset( $item['existing_id'] ) ? (int) $item['existing_id'] : 0;
            if ( isset( $item['decision'] ) && in_array( $item['decision'], $allowed_choices, true ) ) {
                $decisions['documents'][ $key ]['decision'] = $item['decision'];
            } else {
                $decisions['documents'][ $key ]['decision'] = 'skip';
            }
        }

        $engine = new Zipped_Docs_Import_Engine();
        $result = $engine->process_with_decisions( $decisions );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $warnings = get_transient( 'zipped_docs_import_warnings' );
        if ( ! empty( $warnings ) ) {
            $result['warnings'] = $warnings;
            delete_transient( 'zipped_docs_import_warnings' );
        }

        wp_send_json_success( $result );
    }

    public function ajax_export() {
        if ( ! current_user_can( 'zipped_docs_export' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to export.', 'zipped-docs' ) ) );
        }

        check_ajax_referer( 'zipped_docs_export_nonce', '_wpnonce' );

        // JSON payload must be decoded before its individual fields can be validated.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decoded then intval'd below.
        $raw = isset( $_POST['doc_ids'] ) ? (string) wp_unslash( $_POST['doc_ids'] ) : '';
        $doc_ids = json_decode( $raw, true );

        if ( ! is_array( $doc_ids ) || JSON_ERROR_NONE !== json_last_error() || empty( $doc_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No documents selected for export.', 'zipped-docs' ) ) );
        }

        $doc_ids = array_values( array_filter( array_map( 'intval', $doc_ids ), function ( $id ) {
            return $id > 0;
        } ) );

        if ( empty( $doc_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No documents selected for export.', 'zipped-docs' ) ) );
        }

        $engine = new Zipped_Docs_Export_Engine();
        $result = $engine->export_selected( $doc_ids );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }
}
