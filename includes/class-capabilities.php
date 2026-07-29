<?php

defined( 'ABSPATH' ) || exit;

function doc_vista_get_all_capabilities() {
    return array(
        'doc_vista_read'              => __( 'Read Documentation', 'doc-vista' ),
        'doc_vista_create'            => __( 'Create Documentation', 'doc-vista' ),
        'doc_vista_edit'              => __( 'Edit Documentation', 'doc-vista' ),
        'doc_vista_publish'           => __( 'Publish Documentation', 'doc-vista' ),
        'doc_vista_delete'            => __( 'Delete Documentation', 'doc-vista' ),
        'doc_vista_manage_categories' => __( 'Manage Categories', 'doc-vista' ),
        'doc_vista_manage_settings'   => __( 'Manage Settings', 'doc-vista' ),
        'doc_vista_import'            => __( 'Import Documentation', 'doc-vista' ),
        'doc_vista_export'            => __( 'Export Documentation', 'doc-vista' ),
        'doc_vista_manage_plugin'     => __( 'Manage Plugin', 'doc-vista' ),
    );
}

function doc_vista_get_capability_keys() {
    return array_keys( doc_vista_get_all_capabilities() );
}

function doc_vista_get_editor_capabilities() {
    return array(
        'doc_vista_read',
        'doc_vista_create',
        'doc_vista_edit',
        'doc_vista_publish',
    );
}

function doc_vista_add_caps_to_role( $role_name, $caps = null ) {
    $role = get_role( $role_name );
    if ( ! $role ) {
        return;
    }
    if ( null === $caps ) {
        $caps = doc_vista_get_capability_keys();
    }
    foreach ( $caps as $cap ) {
        $role->add_cap( $cap );
    }
}

function doc_vista_remove_caps_from_role( $role_name, $caps = null ) {
    $role = get_role( $role_name );
    if ( ! $role ) {
        return;
    }
    if ( null === $caps ) {
        $caps = doc_vista_get_capability_keys();
    }
    foreach ( $caps as $cap ) {
        $role->remove_cap( $cap );
    }
}

function doc_vista_create_editor_role() {
    $role = get_role( 'doc_vista_editor' );
    if ( $role ) {
        doc_vista_remove_caps_from_role( 'doc_vista_editor', doc_vista_get_capability_keys() );
    } else {
        $role = add_role(
            'doc_vista_editor',
            __( 'Doc Vista Editor', 'doc-vista' ),
            array(
                'read'         => true,
                'upload_files' => true,
            )
        );
    }

    if ( ! $role ) {
        return;
    }

    foreach ( doc_vista_get_editor_capabilities() as $cap ) {
        $role->add_cap( $cap );
    }
}

function doc_vista_register_capabilities() {
    doc_vista_add_caps_to_role( 'administrator' );
    doc_vista_create_editor_role();
    doc_vista_sync_editor_role_caps();
}

function doc_vista_sync_editor_role_caps() {
    $settings      = Doc_Vista_Settings::get_instance();
    $allow_editors = 'yes' === $settings->get( 'doc_vista_allow_editors', 'no' );

    if ( $allow_editors ) {
        doc_vista_add_caps_to_role( 'editor', doc_vista_get_editor_capabilities() );
    } else {
        doc_vista_remove_caps_from_role( 'editor', doc_vista_get_editor_capabilities() );
    }
}

add_action( 'doc_vista_settings_saved', 'doc_vista_sync_editor_role_caps' );
