<?php
/**
 * Doc Vista Import/Export Comprehensive QA Test Suite
 *
 * Run via: WP-CLI: wp eval-file tests/qa-test.php
 * Or access via web: /wp-content/plugins/doc-vista/tests/qa-test.php
 *
 * Requires WordPress to be loaded.
 */

// If accessed via web, bootstrap WordPress
if ( ! defined( 'ABSPATH' ) ) {
    $wp_load = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php';
    if ( file_exists( $wp_load ) ) {
        require_once $wp_load;
    } else {
        die( 'WordPress not found.' );
    }
}

// Ensure plugin is loaded
if ( ! defined( 'DOC_VISTA_VERSION' ) ) {
    $plugin_file = WP_PLUGIN_DIR . '/doc-vista/doc-vista.php';
    if ( file_exists( $plugin_file ) ) {
        require_once $plugin_file;
    } else {
        die( 'Doc Vista plugin not found.' );
    }
}

class Doc_Vista_QA_Test {
    private $results = array();
    private $passed = 0;
    private $failed = 0;
    private $test_data_dir = '';
    private $import_engine = null;

    public function __construct() {
        $this->test_data_dir = __DIR__ . '/test-data';
    }

    public function run() {
        $this->header( 'Doc Vista Import/Export QA Test Suite' );
        $this->header( 'Phase 1: Unit Tests', 1 );

        $this->test_field_mapper();
        $this->test_normalizer();
        $this->test_format_detector();
        $this->test_docvista_adapter();
        $this->test_wordpress_adapter();
        $this->test_post_page_export_adapter();
        $this->test_gutenberg_adapter();

        $this->header( 'Phase 2: Import Engine Tests', 1 );
        $this->test_normalize_input();
        $this->test_error_handling();

        $this->header( 'Phase 3: Export Engine Tests', 1 );
        $this->test_export_engine();

        $this->header( 'Phase 4: Import from File Tests', 1 );
        $this->test_import_docvista_format();
        $this->test_import_wordpress_format();
        $this->test_import_gutenberg_format();
        $this->test_import_post_page_export_format();

        $this->header( 'Phase 5: Error Handling Tests', 1 );
        $this->test_invalid_json();
        $this->test_empty_json();
        $this->test_unsupported_format();

        $this->header( 'Phase 6: Edge Cases', 1 );
        $this->test_empty_title();
        $this->test_missing_content();
        $this->test_empty_document();

        $this->header( 'Phase 7: Round-Trip Test', 1 );
        $this->test_round_trip();

        $this->results_summary();
    }

    private function test_field_mapper() {
        $this->subheader( 'Doc_Vista_Field_Mapper' );

        $data = array(
            'post_title' => 'Test Title',
            'post_content' => 'Test Content',
            'post_name' => 'test-slug',
            'post_excerpt' => 'Test excerpt',
            'post_status' => 'publish',
            'post_author' => '1',
            'post_date' => '2026-01-01 00:00:00',
            'post_modified' => '2026-07-01 00:00:00',
        );

        // Test get()
        $this->assert_eq( 'post_title -> title', Doc_Vista_Field_Mapper::get( $data, 'title' ), 'Test Title' );
        $this->assert_eq( 'post_content -> content', Doc_Vista_Field_Mapper::get( $data, 'content' ), 'Test Content' );
        $this->assert_eq( 'post_name -> slug', Doc_Vista_Field_Mapper::get( $data, 'slug' ), 'test-slug' );

        // Test has_any_field()
        $this->assert_true( 'has title', Doc_Vista_Field_Mapper::has_any_field( $data, 'title' ) );
        $this->assert_true( 'has content', Doc_Vista_Field_Mapper::has_any_field( $data, 'content' ) );
        $this->assert_false( 'no gutenberg', Doc_Vista_Field_Mapper::has_any_field( $data, 'gutenberg_blocks' ) );

        // Test get_rendered()
        $rendered_data = array( 'title' => array( 'rendered' => 'Rendered Title' ) );
        $this->assert_eq( 'rendered title', Doc_Vista_Field_Mapper::get_rendered( $rendered_data, 'title' ), 'Rendered Title' );

        // Test extract_category_names()
        $cat_data = array( 'categories' => array( 'Cat1', 'Cat2' ) );
        $cats = Doc_Vista_Field_Mapper::extract_category_names( $cat_data );
        $this->assert_eq( 'categories extracted', count( $cats ), 2 );
        $this->assert_true( 'cat1 exists', in_array( 'Cat1', $cats ) );

        // Test with taxonomies alias (new)
        $tax_data = array( 'taxonomies' => array( 'Tax1', 'Tax2' ) );
        $tax_cats = Doc_Vista_Field_Mapper::extract_category_names( $tax_data );
        $this->assert_eq( 'taxonomies extracted', count( $tax_cats ), 2 );

        // Test extract_tag_names()
        $tag_data = array( 'tags' => array( 'Tag1', 'Tag2' ) );
        $tags = Doc_Vista_Field_Mapper::extract_tag_names( $tag_data );
        $this->assert_eq( 'tags extracted', count( $tags ), 2 );

        // Test extract_custom_fields()
        $cf_data = array( 'post_meta' => array( 'my_key' => array( 'my_value' ), '_hidden' => array( 'secret' ) ) );
        $fields = Doc_Vista_Field_Mapper::extract_custom_fields( $cf_data );
        $this->assert_true( 'custom field extracted', isset( $fields['my_key'] ) );
        $this->assert_false( 'hidden key skipped', isset( $fields['_hidden'] ) );
    }

    private function test_normalizer() {
        $this->subheader( 'Doc_Vista_Normalizer' );

        $empty = Doc_Vista_Normalizer::empty_doc();
        $this->assert_true( 'empty doc has title key', isset( $empty['title'] ) );
        $this->assert_true( 'empty doc has content key', isset( $empty['content'] ) );
        $this->assert_eq( 'empty doc default status', $empty['status'], 'draft' );

        // Validate
        $valid_doc = $empty;
        $valid_doc['title'] = 'Test';
        $valid_doc['content'] = '<p>Content</p>';
        $errors = Doc_Vista_Normalizer::validate( $valid_doc );
        $this->assert_eq( 'valid doc no errors', count( $errors ), 0 );

        $invalid_doc = $empty;
        $errors2 = Doc_Vista_Normalizer::validate( $invalid_doc );
        $this->assert_true( 'empty title has error', count( $errors2 ) > 0 );

        // get_error_reason
        $reason = Doc_Vista_Normalizer::get_error_reason( array(), array() );
        $this->assert_true( 'empty error reason', ! empty( $reason ) );
    }

    private function test_format_detector() {
        $this->subheader( 'Doc_Vista_Format_Detector' );

        $detector = new Doc_Vista_Format_Detector();

        // Test DocVista detection
        $docvista_data = array( '_doc_vista_export' => true, 'documents' => array( array( 'title' => 'Doc' ) ) );
        $detected = $detector->detect( $docvista_data );
        $this->assert_true( 'docvista detected', $detected instanceof Doc_Vista_Import_Adapter );
        $this->assert_eq( 'docvista label', $detector->get_format_label( $docvista_data, $detected ), 'Doc Vista Export' );

        // Test WordPress detection
        $wp_data = array( 'post_title' => 'Test', 'post_content' => 'Content', 'post_type' => 'page' );
        $detected2 = $detector->detect( $wp_data );
        $this->assert_true( 'wordpress detected', $detected2 instanceof Doc_Vista_Import_Adapter );

        // Test Gutenberg detection
        $guten_data = array( 'content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->', 'title' => 'Test' );
        $detected3 = $detector->detect( $guten_data );
        $this->assert_true( 'gutenberg detected', $detected3 instanceof Doc_Vista_Import_Adapter );

        // Test Post/Page Export detection
        $ppe_data = array( 'post_title' => 'Test', 'post_content' => 'Content', 'post_meta' => array( '_key' => array( 'val' ) ) );
        $detected4 = $detector->detect( $ppe_data );
        $this->assert_true( 'post-page detected', $detected4 instanceof Doc_Vista_Import_Adapter );

        // Test analyze_structure
        $diag = $detector->analyze_structure( $docvista_data );
        $this->assert_eq( 'docvista structure type', $diag['structure_type'], 'doc_vista_wrapper' );

        // Test Gutenberg does NOT match WordPress data with Gutenberg content
        $mixed_data = array( 'post_title' => 'Test', 'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->', 'post_type' => 'page', 'post_meta' => array( 'key' => 'val' ) );
        $detected5 = $detector->detect( $mixed_data );
        // Post/Page Export adapter matches first (has post_meta + post_title + post_content)
        // Then falls through to WordPress adapter if no match
        $class = get_class( $detected5 );
        $this->assert_true( 'mixed data not gutenberg', $class !== 'Doc_Vista_Gutenberg_Adapter' );

        // Test Gutenberg with only Gutenberg data (no WP keys)
        $pure_guten = array( 'content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->', 'title' => 'Test' );
        $detected6 = $detector->detect( $pure_guten );
        $this->assert_eq( 'pure gutenberg matches gutenberg adapter', get_class( $detected6 ), 'Doc_Vista_Gutenberg_Adapter' );
    }

    private function test_docvista_adapter() {
        $this->subheader( 'Doc_Vista_Docvista_Adapter' );

        $adapter = new Doc_Vista_Docvista_Adapter();

        // Test supports with version marker
        $with_version = array( 'doc_vista_version' => '2.2.0', 'title' => 'Test' );
        $this->assert_true( 'supports docvista with version', $adapter->supports( $with_version ) );

        // Test supports with _doc_vista_export marker
        $with_export_flag = array( '_doc_vista_export' => true, 'title' => 'Test' );
        $this->assert_true( 'supports docvista with flag', $adapter->supports( $with_export_flag ) );

        // Test supports with source marker
        $with_source = array( 'source' => 'doc-vista', 'title' => 'Test' );
        $this->assert_true( 'supports docvista with source', $adapter->supports( $with_source ) );

        $data = array(
            'title' => 'Test Document',
            'slug' => 'test-doc',
            'content' => '<p>Content</p>',
            'status' => 'publish',
            'categories' => array( 'Cat1' ),
            'tags' => array( 'Tag1' ),
            'author' => 1,
        );

        $doc = $adapter->normalize( $data );
        $this->assert_eq( 'docvista title', $doc['title'], 'Test Document' );
        $this->assert_eq( 'docvista slug', $doc['slug'], 'test-doc' );
        $this->assert_eq( 'docvista status', $doc['status'], 'publish' );
        $this->assert_eq( 'docvista source', $doc['source'], 'doc-vista' );
    }

    private function test_wordpress_adapter() {
        $this->subheader( 'Doc_Vista_Wordpress_Adapter' );

        $adapter = new Doc_Vista_Wordpress_Adapter();
        $data = array(
            'post_title' => 'WP Page',
            'post_content' => '<p>Content</p>',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'wp-page',
            'post_author' => '1',
            'post_date' => '2026-01-01 00:00:00',
            'post_modified' => '2026-07-01 00:00:00',
        );

        $this->assert_true( 'supports wp', $adapter->supports( $data ) );

        $doc = $adapter->normalize( $data );
        $this->assert_eq( 'wp title', $doc['title'], 'WP Page' );
        $this->assert_eq( 'wp slug', $doc['slug'], 'wp-page' );
        $this->assert_eq( 'wp status', $doc['status'], 'publish' );
        $this->assert_eq( 'wp source', $doc['source'], 'wordpress' );

        // Test meta_input is NOT duplicated
        $meta_data = array(
            'post_title' => 'With Meta',
            'post_content' => '<p>Content</p>',
            'post_type' => 'page',
            'meta_input' => array( 'custom_key' => 'custom_value' ),
        );
        $doc2 = $adapter->normalize( $meta_data );
        $matches = 0;
        foreach ( $doc2['custom_fields'] as $k => $v ) {
            if ( $k === 'custom_key' && $v === 'custom_value' ) {
                $matches++;
            }
        }
        $this->assert_eq( 'meta_input not duplicated', $matches, 1 );
    }

    private function test_post_page_export_adapter() {
        $this->subheader( 'Doc_Vista_Post_Page_Export_Adapter' );

        $adapter = new Doc_Vista_Post_Page_Export_Adapter();
        $data = array(
            'post_title' => 'PPE Test',
            'post_content' => '<p>Content</p>',
            'post_meta' => array( '_some_key' => array( 'val' ) ),
        );

        $this->assert_true( 'supports ppe', $adapter->supports( $data ) );

        $doc = $adapter->normalize( $data );
        $this->assert_eq( 'ppe title', $doc['title'], 'PPE Test' );
        $this->assert_eq( 'ppe source', $doc['source'], 'post-page-export' );

        // Test with taxonomies field
        $tax_data = array(
            'post_title' => 'Tax Test',
            'post_content' => '<p>Content</p>',
            'taxonomies' => array( 'Cat A', 'Cat B' ),
            'post_meta' => array( '_some_key' => array( 'val' ) ),
        );
        $doc2 = $adapter->normalize( $tax_data );
        $this->assert_true( 'taxonomies extracted', in_array( 'Cat A', $doc2['categories'] ) );
    }

    private function test_gutenberg_adapter() {
        $this->subheader( 'Doc_Vista_Gutenberg_Adapter' );

        $adapter = new Doc_Vista_Gutenberg_Adapter();
        $data = array(
            'title' => 'Guten Test',
            'content' => '<!-- wp:paragraph --><p>Blocks!</p><!-- /wp:paragraph -->',
        );

        $this->assert_true( 'supports gutenberg', $adapter->supports( $data ) );

        $doc = $adapter->normalize( $data );
        $this->assert_eq( 'guten title', $doc['title'], 'Guten Test' );
        $this->assert_true( 'guten blocks detected', ! empty( $doc['gutenberg_blocks'] ) );
        $this->assert_eq( 'guten source', $doc['source'], 'gutenberg' );
    }

    private function test_normalize_input() {
        $this->subheader( 'normalize_input()' );

        $engine = $this->get_import_engine();

        // Use reflection to access private method
        $ref = new ReflectionMethod( $engine, 'normalize_input' );
        $ref->setAccessible( true );

        // Test _doc_vista_export wrapper
        $result = $ref->invoke( $engine, array(
            '_doc_vista_export' => true,
            'documents' => array(
                array( 'title' => 'Doc1', 'content' => '<p>C1</p>' ),
                array( 'title' => 'Doc2', 'content' => '<p>C2</p>' ),
            ),
        ) );
        $this->assert_eq( 'docvista wrapper count', count( $result ), 2 );

        // Test array of posts
        $result2 = $ref->invoke( $engine, array(
            array( 'post_title' => 'P1', 'post_content' => '<p>C1</p>', 'post_type' => 'page' ),
            array( 'post_title' => 'P2', 'post_content' => '<p>C2</p>', 'post_type' => 'page' ),
        ) );
        $this->assert_eq( 'array of posts count', count( $result2 ), 2 );

        // Test posts wrapper
        $result3 = $ref->invoke( $engine, array(
            'posts' => array(
                array( 'post_title' => 'P1', 'post_content' => '<p>C1</p>', 'post_type' => 'post' ),
            ),
        ) );
        $this->assert_eq( 'posts wrapper count', count( $result3 ), 1 );

        // Test pages wrapper
        $result4 = $ref->invoke( $engine, array(
            'pages' => array(
                array( 'post_title' => 'Pg1', 'post_content' => '<p>C1</p>', 'post_type' => 'page' ),
            ),
        ) );
        $this->assert_eq( 'pages wrapper count', count( $result4 ), 1 );

        // Test items wrapper
        $result5 = $ref->invoke( $engine, array(
            'items' => array(
                array( 'post_title' => 'I1', 'post_content' => '<p>C1</p>', 'post_type' => 'page' ),
            ),
        ) );
        $this->assert_eq( 'items wrapper count', count( $result5 ), 1 );

        // Test data wrapper
        $result6 = $ref->invoke( $engine, array(
            'data' => array(
                array( 'post_title' => 'D1', 'post_content' => '<p>C1</p>', 'post_type' => 'page' ),
            ),
        ) );
        $this->assert_eq( 'data wrapper count', count( $result6 ), 1 );

        // Test post_data wrapper (the fix for page_ID_5382_data.json)
        $result7 = $ref->invoke( $engine, array(
            'post_data' => array(
                'post_title' => 'Shipox User Guide Test',
                'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
                'post_name' => 'shipox-user-guide-test',
                'post_author' => '2',
                'post_date' => '2026-07-07 07:23:07',
                'post_modified' => '2026-07-29 07:14:34',
            ),
            'post_meta' => array(
                '_edit_lock' => array( '1785317450:3' ),
                'custom_key' => array( 'custom_val' ),
            ),
            'post_type' => 'page',
        ) );
        if ( empty( $result7 ) ) {
            $this->fail( 'post_data wrapper should return documents' );
        } else {
            $this->assert_eq( 'post_data wrapper count', count( $result7 ), 1 );
            $this->assert_eq( 'post_data title', $result7[0]['title'], 'Shipox User Guide Test' );
        }

        // Test single doc (fallback)
        $result8 = $ref->invoke( $engine, array(
            'post_title' => 'Single',
            'post_content' => '<p>Content</p>',
            'post_type' => 'page',
        ) );
        $this->assert_eq( 'single doc count', count( $result8 ), 1 );
    }

    private function test_error_handling() {
        $this->subheader( 'Error Handling' );

        $engine = $this->get_import_engine();

        // Empty array
        $result = $engine->process_data( array() );
        $this->assert_true( 'empty array returns wp_error', is_wp_error( $result ) );

        // Invalid structure
        $result2 = $engine->process_data( array( 'random' => 'data' ) );
        $this->assert_true( 'invalid returns wp_error', is_wp_error( $result2 ) );

        // Test process_upload with no file
        $result3 = $engine->process_upload();
        $this->assert_true( 'no file returns wp_error', is_wp_error( $result3 ) );

        // Test preview_upload with no file
        $result4 = $engine->preview_upload();
        $this->assert_true( 'preview no file returns wp_error', is_wp_error( $result4 ) );
    }

    private function test_export_engine() {
        $this->subheader( 'Export Engine' );

        $exporter = new Doc_Vista_Export_Engine();

        // Export with no IDs
        $result = $exporter->export_selected( array() );
        $this->assert_true( 'empty ids returns error', is_wp_error( $result ) );

        // Export with invalid post type
        $result2 = $exporter->export_single( 999999 );
        $this->assert_true( 'invalid post returns error', is_wp_error( $result2 ) );
    }

    private function test_import_docvista_format() {
        $this->subheader( 'Import Doc Vista JSON' );

        $file = $this->test_data_dir . '/doc-vista-export.json';
        if ( ! file_exists( $file ) ) {
            $this->fail( 'Test file not found: ' . $file );
            return;
        }

        $engine = $this->get_import_engine();
        $preview = $engine->generate_preview( json_decode( file_get_contents( $file ), true ) );

        $this->assert_true( 'docvista preview can_import', $preview['can_import'] );
        $this->assert_eq( 'docvista doc count', $preview['document_count'], 2 );
        $this->assert_true( 'docvista has_blocks', $preview['has_blocks'] );
        $this->assert_true( 'docvista has_meta', $preview['has_meta'] );
        $this->assert_true( 'docvista has_tax', $preview['has_tax'] );
    }

    private function test_import_wordpress_format() {
        $this->subheader( 'Import WordPress JSON' );

        $file = $this->test_data_dir . '/wordpress-page-export.json';
        if ( ! file_exists( $file ) ) {
            $this->fail( 'Test file not found' );
            return;
        }

        $engine = $this->get_import_engine();
        $preview = $engine->generate_preview( json_decode( file_get_contents( $file ), true ) );

        $this->assert_true( 'wp preview can_import', $preview['can_import'] );
        $this->assert_eq( 'wp doc count', $preview['document_count'], 1 );
        $this->assert_true( 'wp has_blocks', $preview['has_blocks'] );
        $this->assert_true( 'wp has_meta', $preview['has_meta'] );
    }

    private function test_import_gutenberg_format() {
        $this->subheader( 'Import Gutenberg JSON' );

        $file = $this->test_data_dir . '/gutenberg-export.json';
        if ( ! file_exists( $file ) ) {
            $this->fail( 'Test file not found' );
            return;
        }

        $engine = $this->get_import_engine();
        $preview = $engine->generate_preview( json_decode( file_get_contents( $file ), true ) );

        $this->assert_true( 'guten preview can_import', $preview['can_import'] );
        $this->assert_eq( 'guten doc count', $preview['document_count'], 1 );
        $this->assert_true( 'guten has_blocks', $preview['has_blocks'] );
    }

    private function test_import_post_page_export_format() {
        $this->subheader( 'Import Post/Page Export JSON' );

        $file = $this->test_data_dir . '/post-page-export.json';
        if ( ! file_exists( $file ) ) {
            $this->fail( 'Test file not found' );
            return;
        }

        $engine = $this->get_import_engine();
        $preview = $engine->generate_preview( json_decode( file_get_contents( $file ), true ) );

        $this->assert_true( 'ppe preview can_import', $preview['can_import'] );
        $this->assert_eq( 'ppe doc count', $preview['document_count'], 1 );
        $this->assert_true( 'ppe has_meta', $preview['has_meta'] );
    }

    private function test_invalid_json() {
        $this->subheader( 'Invalid JSON' );

        $engine = $this->get_import_engine();
        $ref = new ReflectionMethod( $engine, 'read_json_file' );
        $ref->setAccessible( true );

        // Create temp file with invalid JSON
        $tmp = tempnam( sys_get_temp_dir(), 'qa-test-' );
        file_put_contents( $tmp, '{invalid json}' );
        $result = $ref->invoke( $engine, $tmp );
        unlink( $tmp );
        $this->assert_true( 'invalid json returns error', is_wp_error( $result ) );
        $this->assert_true( 'invalid json error code', strpos( $result->get_error_code(), 'invalid_json' ) === 0 );
    }

    private function test_empty_json() {
        $this->subheader( 'Empty JSON' );

        $engine = $this->get_import_engine();
        $preview = $engine->generate_preview( array() );
        $this->assert_false( 'empty preview cannot import', $preview['can_import'] );
        $this->assert_true( 'empty preview has error', ! empty( $preview['error_message'] ) );
    }

    private function test_unsupported_format() {
        $this->subheader( 'Unsupported Format' );

        $engine = $this->get_import_engine();
        $preview = $engine->generate_preview( array( 'some_random_key' => 'value' ) );
        $this->assert_false( 'unsupported cannot import', $preview['can_import'] );
    }

    private function test_empty_title() {
        $this->subheader( 'Empty Title' );

        $engine = $this->get_import_engine();
        $ref = new ReflectionMethod( $engine, 'normalize_input' );
        $ref->setAccessible( true );

        // Test data used by the Gutenberg adapter with no title
        $data = array(
            'content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
        );
        $result = $ref->invoke( $engine, $data );
        if ( ! empty( $result ) ) {
            $validation = Doc_Vista_Normalizer::validate( $result[0] );
            $has_title_error = false;
            foreach ( $validation as $e ) {
                if ( strpos( $e, 'title' ) !== false ) {
                    $has_title_error = true;
                }
            }
            $this->assert_true( 'empty title should fail validation', $has_title_error );
        }
    }

    private function test_missing_content() {
        $this->subheader( 'Missing Content' );

        $engine = $this->get_import_engine();
        $ref = new ReflectionMethod( $engine, 'normalize_input' );
        $ref->setAccessible( true );

        $data = array(
            'post_title' => 'No Content',
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_content' => '',
        );
        $result = $ref->invoke( $engine, $data );
        if ( ! empty( $result ) ) {
            $validation = Doc_Vista_Normalizer::validate( $result[0] );
            $this->assert_true( 'no content imported as empty string', is_string( $result[0]['content'] ) );
            $this->assert_eq( 'empty content equals empty string', $result[0]['content'], '' );
        } else {
            $this->pass( 'no content: normalize returned empty (adapter did not match)' );
        }
    }

    private function test_empty_document() {
        $this->subheader( 'Empty Document' );

        $engine = $this->get_import_engine();
        $ref = new ReflectionMethod( $engine, 'normalize_input' );
        $ref->setAccessible( true );

        $data = array(
            'title' => '',
            'content' => '',
        );
        $result = $ref->invoke( $engine, $data );
        if ( ! empty( $result ) ) {
            $validation = Doc_Vista_Normalizer::validate( $result[0] );
            $this->assert_true( 'empty doc should have validation errors', ! empty( $validation ) );
        } else {
            $this->pass( 'empty doc: normalize returned empty (adapter did not match)' );
        }
    }

    private function test_round_trip() {
        $this->subheader( 'Round-Trip Test' );

        // Create a test doc
        $post_data = array(
            'post_title'   => 'QA Round-Trip Test ' . uniqid(),
            'post_content' => '<!-- wp:heading --><h2>Round Trip</h2><!-- /wp:heading -->\n\n<!-- wp:paragraph --><p>Testing round trip export/import.</p><!-- /wp:paragraph -->\n\n<!-- wp:list --><ul><li>Item 1</li><li>Item 2</li></ul><!-- /wp:list -->',
            'post_status'  => 'publish',
            'post_type'    => 'doc_vista_doc',
            'post_author'  => get_current_user_id(),
            'post_excerpt' => 'Round trip excerpt',
            'post_name'    => 'qa-round-trip-' . uniqid(),
            'menu_order'   => 5,
        );

        $post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            $this->fail( 'Could not create test post: ' . $post_id->get_error_message() );
            return;
        }

        // Set categories
        wp_set_object_terms( $post_id, array( 'QA Test Cat' ), 'doc_vista_category' );
        wp_set_post_tags( $post_id, array( 'qa-tag' ) );
        update_post_meta( $post_id, '_doc_vista_order', 5 );
        update_post_meta( $post_id, '_wp_page_template', 'default' );
        update_post_meta( $post_id, 'custom_field_key', 'custom_field_value' );
        update_post_meta( $post_id, '_doc_vista_gutenberg_blocks', array(
            array( 'blockName' => 'core/heading', 'attrs' => array( 'level' => 2 ), 'innerBlocks' => array() ),
            array( 'blockName' => 'core/paragraph', 'attrs' => array(), 'innerBlocks' => array() ),
            array( 'blockName' => 'core/list', 'attrs' => array(), 'innerBlocks' => array() ),
        ) );

        // Export
        $exporter = new Doc_Vista_Export_Engine();
        $export = $exporter->export_single( $post_id );
        if ( is_wp_error( $export ) ) {
            $this->fail( 'Export failed: ' . $export->get_error_message() );
            return;
        }

        // Verify export content
        $this->assert_eq( 'export title matches', $export['title'], $post_data['post_title'] );
        $this->assert_true( 'export content is non-empty', strlen( $export['content'] ) > 0 );
        $this->assert_true( 'export content has Gutenberg markers', strpos( $export['content'], '<!-- wp:' ) !== false );
        $this->assert_true( 'export content has heading', strpos( $export['content'], 'Round Trip' ) !== false );
        $this->assert_eq( 'export status matches', $export['status'], $post_data['post_status'] );
        $this->assert_eq( 'export slug matches', $export['slug'], $post_data['post_name'] );
        $this->assert_true( 'export categories exist', ! empty( $export['categories'] ) );
        $this->assert_true( 'export tags exist', ! empty( $export['tags'] ) );
        $this->assert_eq( 'export menu_order', $export['menu_order'], 5 );
        $this->assert_true( 'export has gutenberg blocks', ! empty( $export['gutenberg_blocks'] ) );
        $this->assert_true( 'export has source', $export['source'] === 'doc-vista' );

        // Delete original
        $deleted = wp_delete_post( $post_id, true );
        if ( ! $deleted ) {
            $this->fail( 'Could not delete test post for round-trip' );
            return;
        }
        $this->assert_true( 'original post deleted', true );

        // Import the exported data
        $import_engine = $this->get_import_engine();
        $result = $import_engine->process_data( array(
            '_doc_vista_export' => true,
            'documents' => array( $export ),
        ) );

        if ( is_wp_error( $result ) ) {
            $this->fail( 'Import failed: ' . $result->get_error_message() );
            return;
        }

        // Check import result
        $imported_ids = $result['imported'] ?? array();
        if ( empty( $imported_ids ) ) {
            $this->fail( 'No documents imported' );
            return;
        }

        $new_post_id = $imported_ids[0];
        $imported_post = get_post( $new_post_id );

        if ( ! $imported_post ) {
            $this->fail( 'Imported post not found' );
            return;
        }

        // Compare
        $this->assert_eq( 'round-trip title', $imported_post->post_title, $post_data['post_title'] );
        $this->assert_true( 'round-trip content non-empty', strlen( $imported_post->post_content ) > 0 );
        $this->assert_true( 'round-trip content has Gutenberg', strpos( $imported_post->post_content, '<!-- wp:' ) !== false );
        $this->assert_true( 'round-trip content preserved (heading)', strpos( $imported_post->post_content, 'Round Trip' ) !== false );
        $this->assert_true( 'round-trip content preserved (paragraph)', strpos( $imported_post->post_content, 'Testing round trip' ) !== false );
        $this->assert_true( 'round-trip content preserved (list)', strpos( $imported_post->post_content, 'Item 1' ) !== false );
        $this->assert_true( 'round-trip content preserved (list item 2)', strpos( $imported_post->post_content, 'Item 2' ) !== false );
        $this->assert_eq( 'round-trip status', $imported_post->post_status, $post_data['post_status'] );
        $this->assert_eq( 'round-trip slug', $imported_post->post_name, $post_data['post_name'] );

        // Verify imported categories
        $imported_cats = wp_get_post_terms( $new_post_id, 'doc_vista_category', array( 'fields' => 'names' ) );
        $this->assert_true( 'round-trip has category', in_array( 'QA Test Cat', $imported_cats ) );

        // Clean up imported post
        wp_delete_post( $new_post_id, true );
    }

    private function get_import_engine() {
        if ( ! $this->import_engine ) {
            $this->import_engine = new Doc_Vista_Import_Engine();
        }
        return $this->import_engine;
    }

    private function assert_eq( $label, $actual, $expected ) {
        if ( $actual === $expected ) {
            $this->pass( $label );
        } else {
            $this->fail( $label . ' - expected: ' . $this->val( $expected ) . ', got: ' . $this->val( $actual ) );
        }
    }

    private function assert_true( $label, $condition ) {
        if ( $condition ) {
            $this->pass( $label );
        } else {
            $this->fail( $label . ' - expected true, got false' );
        }
    }

    private function assert_false( $label, $condition ) {
        if ( ! $condition ) {
            $this->pass( $label );
        } else {
            $this->fail( $label . ' - expected false, got true' );
        }
    }

    private function pass( $label ) {
        $this->passed++;
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::log( "  PASS: {$label}" );
        } else {
            echo "  <span style='color:green'>PASS</span>: {$label}\n";
        }
    }

    private function fail( $label ) {
        $this->failed++;
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::warning( "  FAIL: {$label}" );
        } else {
            echo "  <span style='color:red'>FAIL</span>: {$label}\n";
        }
    }

    private function header( $text, $level = 0 ) {
        echo "\n";
        if ( $level === 0 ) {
            echo str_repeat( '=', 60 ) . "\n{$text}\n" . str_repeat( '=', 60 ) . "\n";
        } else {
            echo str_repeat( '-', 40 ) . "\n{$text}\n" . str_repeat( '-', 40 ) . "\n";
        }
    }

    private function subheader( $text ) {
        echo "\n  {$text}:\n";
    }

    private function val( $v ) {
        if ( is_bool( $v ) ) return $v ? 'true' : 'false';
        if ( is_null( $v ) ) return 'null';
        if ( is_array( $v ) ) return 'array(' . count( $v ) . ')';
        if ( is_object( $v ) ) return get_class( $v );
        return (string) $v;
    }

    private function results_summary() {
        $this->header( 'RESULTS SUMMARY' );
        $total = $this->passed + $this->failed;
        echo "Total tests: {$total}\n";
        echo "Passed: {$this->passed}\n";
        if ( $this->failed > 0 ) {
            echo "Failed: {$this->failed}\n";
        } else {
            echo "Failed: 0 - ALL TESTS PASSED!\n";
        }
        echo "\n";
    }
}

// Run the tests
$tester = new Doc_Vista_QA_Test();
$tester->run();
