<?php

defined( 'ABSPATH' ) || exit;

class Doc_Vista_Normalizer {

    const VALID_STATUSES = array( 'publish', 'draft', 'pending', 'private', 'future' );

    public static function empty_doc() {
        return array(
            'title'            => '',
            'slug'             => '',
            'status'           => 'draft',
            'excerpt'          => '',
            'author'           => 0,
            'content'          => '',
            'gutenberg_blocks' => array(),
            'categories'       => array(),
            'tags'             => array(),
            'featured_image'   => '',
            'attachments'      => array(),
            'custom_fields'    => array(),
            'meta'             => array(),
            'menu_order'       => 0,
            'template'         => '',
            'created_date'     => '',
            'modified_date'    => '',
            'source'           => '',
            'original_data'    => array(),
        );
    }

    public static function validate( $doc ) {
        $errors = array();
        if ( empty( $doc['title'] ) ) {
            $errors[] = __( 'Document title is required.', 'doc-vista' );
        }
        if ( ! is_string( $doc['content'] ) ) {
            $errors[] = __( 'Document content must be a string.', 'doc-vista' );
        }
        if ( ! empty( $doc['status'] ) && ! in_array( $doc['status'], self::VALID_STATUSES, true ) ) {
            $doc['status'] = 'draft';
        }
        return $errors;
    }

    public static function get_error_reason( $data, $diagnostics ) {
        if ( empty( $data ) ) {
            return __( 'Empty export. The file contains no data.', 'doc-vista' );
        }

        if ( ! is_array( $data ) ) {
            return __( 'Invalid JSON structure. Expected an object or array, got a different type.', 'doc-vista' );
        }

        $detected = $diagnostics['detected_keys'] ?? array();
        if ( empty( $detected ) ) {
            return __( 'Unknown export format. The JSON structure does not match any supported format.', 'doc-vista' );
        }

        if ( ! in_array( 'title', $detected, true ) ) {
            return __( 'Missing document title. No title field found in the JSON data.', 'doc-vista' );
        }

        if ( ! in_array( 'content', $detected, true ) ) {
            return __( 'Missing document content. No content field found in the JSON data.', 'doc-vista' );
        }

        if ( $diagnostics['is_collection'] && 0 === $diagnostics['count'] ) {
            return __( 'Empty export. The file contains an empty array.', 'doc-vista' );
        }

        return __( 'No supported posts/pages found. The data format is recognized but contains no importable documents.', 'doc-vista' );
    }
}
