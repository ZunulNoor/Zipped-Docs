<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( defined( 'ZIPPED_DOCS_PRESERVE_DATA' ) && ZIPPED_DOCS_PRESERVE_DATA ) {
    return;
}

$zipped_docs_settings = get_option( 'zipped_docs_settings', array() );
if ( ! empty( $zipped_docs_settings['zipped_docs_preserve_data'] ) && 'yes' === $zipped_docs_settings['zipped_docs_preserve_data'] ) {
    return;
}

$zipped_docs_caps = array(
    'zipped_docs_read',
    'zipped_docs_create',
    'zipped_docs_edit',
    'zipped_docs_publish',
    'zipped_docs_delete',
    'zipped_docs_manage_categories',
    'zipped_docs_manage_settings',
    'zipped_docs_import',
    'zipped_docs_export',
    'zipped_docs_manage_plugin',
);

global $wp_roles;
if ( ! isset( $wp_roles ) ) {
    $wp_roles = new WP_Roles();
}
foreach ( $wp_roles->roles as $zipped_docs_role_name => $zipped_docs_role_info ) {
    $role = get_role( $zipped_docs_role_name );
    if ( $role ) {
        foreach ( $zipped_docs_caps as $zipped_docs_cap ) {
            $role->remove_cap( $zipped_docs_cap );
        }
    }
}

remove_role( 'zipped_docs_editor' );

delete_option( 'zipped_docs_graph' );
delete_option( 'zipped_docs_settings' );
delete_option( 'zipped_docs_version' );
delete_option( 'zipped_docs_migrated_product_cats' );

$zipped_docs_post_types = array( 'zipped_docs_doc' );

foreach ( $zipped_docs_post_types as $zipped_docs_post_type ) {
    $posts = get_posts( array(
        'post_type'      => $zipped_docs_post_type,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );

    if ( ! empty( $posts ) ) {
        foreach ( $posts as $post_id ) {
            wp_delete_post( $post_id, true );
        }
    }
}

$zipped_docs_taxonomies = array(
    'zipped_docs_category',
    'zipped_docs_product',
);

foreach ( $zipped_docs_taxonomies as $zipped_docs_taxonomy ) {
    $zipped_docs_terms = get_terms( array(
        'taxonomy'   => $zipped_docs_taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( ! empty( $zipped_docs_terms ) && ! is_wp_error( $zipped_docs_terms ) ) {
        foreach ( $zipped_docs_terms as $zipped_docs_term_id ) {
            wp_delete_term( $zipped_docs_term_id, $zipped_docs_taxonomy );
        }
    }
}
