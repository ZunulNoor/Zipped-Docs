<?php

defined( 'ABSPATH' ) || exit;

function zipped_docs_get_all_capabilities() {
    return array(
        'zipped_docs_read'              => __( 'Read Documentation', 'zipped-docs' ),
        'zipped_docs_create'            => __( 'Create Documentation', 'zipped-docs' ),
        'zipped_docs_edit'              => __( 'Edit Documentation', 'zipped-docs' ),
        'zipped_docs_publish'           => __( 'Publish Documentation', 'zipped-docs' ),
        'zipped_docs_delete'            => __( 'Delete Documentation', 'zipped-docs' ),
        'zipped_docs_manage_categories' => __( 'Manage Categories', 'zipped-docs' ),
        'zipped_docs_manage_settings'   => __( 'Manage Settings', 'zipped-docs' ),
        'zipped_docs_import'            => __( 'Import Documentation', 'zipped-docs' ),
        'zipped_docs_export'            => __( 'Export Documentation', 'zipped-docs' ),
        'zipped_docs_manage_plugin'     => __( 'Manage Plugin', 'zipped-docs' ),
    );
}

function zipped_docs_get_capability_keys() {
    return array_keys( zipped_docs_get_all_capabilities() );
}

function zipped_docs_get_editor_capabilities() {
    return array(
        'zipped_docs_read',
        'zipped_docs_create',
        'zipped_docs_edit',
        'zipped_docs_publish',
    );
}

function zipped_docs_add_caps_to_role( $role_name, $caps = null ) {
    $role = get_role( $role_name );
    if ( ! $role ) {
        return;
    }
    if ( null === $caps ) {
        $caps = zipped_docs_get_capability_keys();
    }
    foreach ( $caps as $cap ) {
        $role->add_cap( $cap );
    }
}

function zipped_docs_remove_caps_from_role( $role_name, $caps = null ) {
    $role = get_role( $role_name );
    if ( ! $role ) {
        return;
    }
    if ( null === $caps ) {
        $caps = zipped_docs_get_capability_keys();
    }
    foreach ( $caps as $cap ) {
        $role->remove_cap( $cap );
    }
}

function zipped_docs_create_editor_role() {
    $role = get_role( 'zipped_docs_editor' );
    if ( $role ) {
        zipped_docs_remove_caps_from_role( 'zipped_docs_editor', zipped_docs_get_capability_keys() );
    } else {
        $role = add_role(
            'zipped_docs_editor',
            __( 'Zipped Docs Editor', 'zipped-docs' ),
            array(
                'read'         => true,
                'upload_files' => true,
            )
        );
    }

    if ( ! $role ) {
        return;
    }

    foreach ( zipped_docs_get_editor_capabilities() as $cap ) {
        $role->add_cap( $cap );
    }
}

function zipped_docs_register_capabilities() {
    zipped_docs_add_caps_to_role( 'administrator' );
    zipped_docs_create_editor_role();
    zipped_docs_sync_editor_role_caps();
}

function zipped_docs_sync_editor_role_caps() {
    $settings      = Zipped_Docs_Settings::get_instance();
    $allow_editors = 'yes' === $settings->get( 'zipped_docs_allow_editors', 'no' );

    if ( $allow_editors ) {
        zipped_docs_add_caps_to_role( 'editor', zipped_docs_get_editor_capabilities() );
    } else {
        zipped_docs_remove_caps_from_role( 'editor', zipped_docs_get_editor_capabilities() );
    }
}

add_action( 'zipped_docs_settings_saved', 'zipped_docs_sync_editor_role_caps' );
