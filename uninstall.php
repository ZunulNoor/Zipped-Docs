<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( defined( 'DOC_VISTA_PRESERVE_DATA' ) && DOC_VISTA_PRESERVE_DATA ) {
    return;
}

$settings = get_option( 'doc_vista_settings', array() );
if ( ! empty( $settings['doc_vista_preserve_data'] ) && 'yes' === $settings['doc_vista_preserve_data'] ) {
    return;
}

$caps = array(
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
foreach ( $wp_roles->roles as $role_name => $role_info ) {
    $role = get_role( $role_name );
    if ( $role ) {
        foreach ( $caps as $cap ) {
            $role->remove_cap( $cap );
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

$taxonomies = array( 'doc_vista_category', 'doc_vista_product' );

foreach ( $taxonomies as $taxonomy ) {
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }
    }
}
