<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( defined( 'DOC_VISTA_PRESERVE_DATA' ) && DOC_VISTA_PRESERVE_DATA ) {
    return;
}

$doc_vista_settings = get_option( 'doc_vista_settings', array() );
if ( ! empty( $doc_vista_settings['doc_vista_preserve_data'] ) && 'yes' === $doc_vista_settings['doc_vista_preserve_data'] ) {
    return;
}

$doc_vista_caps = array(
    'doc_vista_read',
    'doc_vista_create',
    'doc_vista_edit',
    'doc_vista_publish',
    'doc_vista_delete',
    'doc_vista_manage_categories',
    'doc_vista_manage_settings',
    'doc_vista_import',
    'doc_vista_export',
    'doc_vista_manage_plugin',
);

global $wp_roles;
if ( ! isset( $wp_roles ) ) {
    $wp_roles = new WP_Roles();
}
foreach ( $wp_roles->roles as $doc_vista_role_name => $doc_vista_role_info ) {
    $role = get_role( $doc_vista_role_name );
    if ( $role ) {
        foreach ( $doc_vista_caps as $doc_vista_cap ) {
            $role->remove_cap( $doc_vista_cap );
        }
    }
}

remove_role( 'doc_vista_editor' );

delete_option( 'doc_vista_graph' );
delete_option( 'doc_vista_settings' );
delete_option( 'doc_vista_version' );

$posts = get_posts( array(
    'post_type'      => 'doc_vista_doc',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
) );

if ( ! empty( $posts ) ) {
    foreach ( $posts as $post_id ) {
        wp_delete_post( $post_id, true );
    }
}

$doc_vista_taxonomies = array( 'doc_vista_category', 'doc_vista_product' );

foreach ( $doc_vista_taxonomies as $doc_vista_taxonomy ) {
    $doc_vista_terms = get_terms( array(
        'taxonomy'   => $doc_vista_taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( ! empty( $doc_vista_terms ) && ! is_wp_error( $doc_vista_terms ) ) {
        foreach ( $doc_vista_terms as $doc_vista_term_id ) {
            wp_delete_term( $doc_vista_term_id, $doc_vista_taxonomy );
        }
    }
}
