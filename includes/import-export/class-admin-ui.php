<?php

defined( 'ABSPATH' ) || exit;

class Doc_Vista_Import_Export_Admin {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_doc_vista_import_preview', array( $this, 'ajax_import_preview' ) );
        add_action( 'wp_ajax_doc_vista_import_upload', array( $this, 'ajax_import_upload' ) );
        add_action( 'wp_ajax_doc_vista_import_process', array( $this, 'ajax_import_process' ) );
        add_action( 'wp_ajax_doc_vista_export', array( $this, 'ajax_export' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'doc-vista' ) === false ) {
            return;
        }

        wp_enqueue_script(
            'doc-vista-import-export',
            DOC_VISTA_ASSETS . 'doc-vista-import-export.js',
            array( 'doc-vista-admin' ),
            DOC_VISTA_VERSION,
            true
        );

        $max_upload = wp_max_upload_size();

        wp_localize_script( 'doc-vista-import-export', 'DOC_VISTA_IE', array(
            'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
            'importNonce'           => wp_create_nonce( 'doc_vista_import_nonce' ),
            'exportNonce'           => wp_create_nonce( 'doc_vista_export_nonce' ),
            'maxUploadSize'         => $max_upload,
            'maxUploadSizeFormatted' => size_format( $max_upload ),
            'maxUploadSizeIni'      => ini_get( 'upload_max_filesize' ),
            'postMaxSize'           => ini_get( 'post_max_size' ),
            'themeColor'            => doc_vista_get_settings()['doc_vista_theme_color'] ?? '#2563EB',
            'i18n'                  => array(
                'importTitle'            => __( 'Import Documentation', 'doc-vista' ),
                'exportTitle'            => __( 'Export Documentation', 'doc-vista' ),
                'dragDrop'               => __( 'Drag & drop your JSON file here', 'doc-vista' ),
                'browseFile'             => __( 'Browse Files', 'doc-vista' ),
                'supportedFormats'       => __( 'Supported Formats', 'doc-vista' ),
                'processing'             => __( 'Processing...', 'doc-vista' ),
                'importComplete'         => __( 'Import Complete', 'doc-vista' ),
                'exportComplete'         => __( 'Export Complete', 'doc-vista' ),
                'imported'               => __( 'Imported', 'doc-vista' ),
                'replaced'               => __( 'Replaced', 'doc-vista' ),
                'updated'                => __( 'Updated', 'doc-vista' ),
                'skipped'                => __( 'Skipped', 'doc-vista' ),
                'errors'                 => __( 'Errors', 'doc-vista' ),
                'conflictsFound'         => __( 'Some documents already exist', 'doc-vista' ),
                'conflictMessage'        => __( 'How would you like to handle each conflict?', 'doc-vista' ),
                'existing'               => __( 'Existing', 'doc-vista' ),
                'incoming'               => __( 'Incoming', 'doc-vista' ),
                'createNew'              => __( 'Create New', 'doc-vista' ),
                'replaceExisting'        => __( 'Replace Existing', 'doc-vista' ),
                'updateExisting'         => __( 'Update Existing', 'doc-vista' ),
                'skip'                   => __( 'Skip', 'doc-vista' ),
                'applyAll'               => __( 'Apply to all', 'doc-vista' ),
                'close'                  => __( 'Close', 'doc-vista' ),
                'uploadError'            => __( 'Upload Error', 'doc-vista' ),
                'invalidFile'            => __( 'Invalid file type. Only .json files are supported.', 'doc-vista' ),
                'fileTooLarge'           => __( 'File exceeds maximum upload size.', 'doc-vista' ),
                'noFileSelected'         => __( 'No file selected.', 'doc-vista' ),
                'noDocsSelected'         => __( 'Please select at least one document to export.', 'doc-vista' ),
                'exporting'              => __( 'Exporting...', 'doc-vista' ),
                'documents'              => __( 'documents', 'doc-vista' ),
                'importAnother'          => __( 'Import Another File', 'doc-vista' ),
                'viewDocs'               => __( 'View Docs', 'doc-vista' ),
                'missingMedia'           => __( 'Media Warnings', 'doc-vista' ),
                'unknownFormat'          => __( 'Unknown Format', 'doc-vista' ),
                'unknownFormatDesc'      => __( 'The uploaded file does not match any supported format.', 'doc-vista' ),
                'fileInfo'               => __( 'File', 'doc-vista' ),
                'detectedFormat'         => __( 'Detected Format', 'doc-vista' ),
                'previewTitle'           => __( 'Import Preview', 'doc-vista' ),
                'readyToImport'          => __( 'Ready to import', 'doc-vista' ),
                'documentsFound'         => __( 'documents found', 'doc-vista' ),
                'categoriesFound'        => __( 'Categories', 'doc-vista' ),
                'imagesFound'            => __( 'Images', 'doc-vista' ),
                'gutenbergSupported'     => __( 'Blocks', 'doc-vista' ),
                'supported'              => __( 'Supported', 'doc-vista' ),
                'metadata'               => __( 'Metadata', 'doc-vista' ),
                'dates'                  => __( 'Dates', 'doc-vista' ),
                'author'                 => __( 'Author', 'doc-vista' ),
                'yes'                    => __( 'Yes', 'doc-vista' ),
                'no'                     => __( 'No', 'doc-vista' ),
                'proceedImport'          => __( 'Proceed Import', 'doc-vista' ),
                'cancel'                 => __( 'Cancel', 'doc-vista' ),
                'maxUploadSizeMsg'       => sprintf(
                    /* translators: %s: formatted maximum allowed upload size, e.g. "64 MB". */
                    __( 'Maximum upload size: %s', 'doc-vista' ),
                    size_format( $max_upload )
                ),
            ),
        ) );
    }

    public function ajax_import_preview() {
        if ( ! current_user_can( 'doc_vista_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to import.', 'doc-vista' ) ) );
        }

        check_ajax_referer( 'doc_vista_import_nonce', '_wpnonce' );

        $engine = new Doc_Vista_Import_Engine();
        $result = $engine->preview_upload();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    public function ajax_import_upload() {
        if ( ! current_user_can( 'doc_vista_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to import.', 'doc-vista' ) ) );
        }

        check_ajax_referer( 'doc_vista_import_nonce', '_wpnonce' );

        $engine = new Doc_Vista_Import_Engine();
        $result = $engine->process_upload();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    public function ajax_import_process() {
        if ( ! current_user_can( 'doc_vista_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to import.', 'doc-vista' ) ) );
        }

        check_ajax_referer( 'doc_vista_import_nonce', '_wpnonce' );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON payload, decoded and validated below.
        $raw = isset( $_POST['decisions'] ) ? wp_unslash( $_POST['decisions'] ) : '';
        $decisions = json_decode( $raw, true );

        if ( ! $decisions || ! is_array( $decisions ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid import decisions.', 'doc-vista' ) ) );
        }

        $engine = new Doc_Vista_Import_Engine();
        $result = $engine->process_with_decisions( $decisions );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $warnings = get_transient( 'doc_vista_import_warnings' );
        if ( ! empty( $warnings ) ) {
            $result['warnings'] = $warnings;
            delete_transient( 'doc_vista_import_warnings' );
        }

        wp_send_json_success( $result );
    }

    public function ajax_export() {
        if ( ! current_user_can( 'doc_vista_export' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to export.', 'doc-vista' ) ) );
        }

        check_ajax_referer( 'doc_vista_export_nonce', '_wpnonce' );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON payload, decoded and intval'd below.
        $doc_ids = isset( $_POST['doc_ids'] ) ? wp_unslash( $_POST['doc_ids'] ) : '';
        $doc_ids = json_decode( $doc_ids, true );

        if ( ! is_array( $doc_ids ) || empty( $doc_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No documents selected for export.', 'doc-vista' ) ) );
        }

        $doc_ids = array_map( 'intval', $doc_ids );

        $engine = new Doc_Vista_Export_Engine();
        $result = $engine->export_selected( $doc_ids );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }
}
