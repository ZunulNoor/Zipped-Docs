<?php

defined( 'ABSPATH' ) || exit;

function doc_vista_register_post_type() {

    $labels = array(
        'name'                  => _x( 'Docs', 'Post Type General Name', 'doc-vista' ),
        'singular_name'         => _x( 'Doc', 'Post Type Singular Name', 'doc-vista' ),
        'menu_name'             => __( 'Docs', 'doc-vista' ),
        'name_admin_bar'        => __( 'Doc', 'doc-vista' ),
        'archives'              => __( 'Doc Archives', 'doc-vista' ),
        'attributes'            => __( 'Doc Attributes', 'doc-vista' ),
        'all_items'             => __( 'All Docs', 'doc-vista' ),
        'add_new_item'          => __( 'Add New Doc', 'doc-vista' ),
        'add_new'               => __( 'Add New', 'doc-vista' ),
        'new_item'              => __( 'New Doc', 'doc-vista' ),
        'edit_item'             => __( 'Edit Doc', 'doc-vista' ),
        'update_item'           => __( 'Update Doc', 'doc-vista' ),
        'view_item'             => __( 'View Doc', 'doc-vista' ),
        'view_items'            => __( 'View Docs', 'doc-vista' ),
        'search_items'          => __( 'Search Doc', 'doc-vista' ),
        'not_found'             => __( 'Not found', 'doc-vista' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'doc-vista' ),
        'insert_into_item'      => __( 'Insert into doc', 'doc-vista' ),
        'uploaded_to_this_item' => __( 'Uploaded to this doc', 'doc-vista' ),
        'items_list'            => __( 'Docs list', 'doc-vista' ),
        'item_published'        => __( 'Doc published.', 'doc-vista' ),
        'item_updated'          => __( 'Doc updated.', 'doc-vista' ),
    );

    $args = array(
        'label'               => __( 'Docs', 'doc-vista' ),
        'description'         => __( 'Documentation articles', 'doc-vista' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'hierarchical'        => false,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => false,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => true,
        'menu_position'       => null,
        'menu_icon'           => 'dashicons-book',
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'capability_type'     => 'doc_vista_doc',
        'capabilities'        => array(
            'edit_post'              => 'doc_vista_edit',
            'read_post'              => 'doc_vista_read',
            'delete_post'            => 'doc_vista_delete',
            'edit_posts'             => 'doc_vista_edit',
            'edit_others_posts'      => 'doc_vista_edit',
            'publish_posts'          => 'doc_vista_publish',
            'read_private_posts'     => 'doc_vista_read',
            'create_posts'           => 'doc_vista_create',
            'delete_posts'           => 'doc_vista_delete',
            'delete_private_posts'   => 'doc_vista_delete',
            'delete_published_posts' => 'doc_vista_delete',
            'delete_others_posts'    => 'doc_vista_delete',
            'edit_private_posts'     => 'doc_vista_edit',
            'edit_published_posts'   => 'doc_vista_edit',
        ),
        'map_meta_cap'        => false,
        'show_in_rest'        => true,
        'rest_base'           => 'doc-vista-docs',
    );

    register_post_type( 'doc_vista_doc', $args );
}

function doc_vista_register_taxonomy() {

    $labels = array(
        'name'                       => _x( 'Doc Categories', 'Taxonomy General Name', 'doc-vista' ),
        'singular_name'              => _x( 'Doc Category', 'Taxonomy Singular Name', 'doc-vista' ),
        'menu_name'                  => __( 'Categories', 'doc-vista' ),
        'all_items'                  => __( 'All Categories', 'doc-vista' ),
        'parent_item'                => __( 'Parent Category', 'doc-vista' ),
        'parent_item_colon'          => __( 'Parent Category:', 'doc-vista' ),
        'new_item_name'              => __( 'New Category Name', 'doc-vista' ),
        'add_new_item'               => __( 'Add New Category', 'doc-vista' ),
        'edit_item'                  => __( 'Edit Category', 'doc-vista' ),
        'update_item'                => __( 'Update Category', 'doc-vista' ),
        'view_item'                  => __( 'View Category', 'doc-vista' ),
        'separate_items_with_commas' => __( 'Separate categories with commas', 'doc-vista' ),
        'add_or_remove_items'        => __( 'Add or remove categories', 'doc-vista' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'doc-vista' ),
        'popular_items'              => __( 'Popular Categories', 'doc-vista' ),
        'search_items'               => __( 'Search Categories', 'doc-vista' ),
        'not_found'                  => __( 'Not Found', 'doc-vista' ),
        'no_terms'                   => __( 'No categories', 'doc-vista' ),
        'items_list'                 => __( 'Categories list', 'doc-vista' ),
        'items_list_navigation'      => __( 'Categories list navigation', 'doc-vista' ),
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rest_base'         => 'doc-vista-categories',
        'meta_box_cb'       => false,
    );

    register_taxonomy( 'doc_vista_category', array( 'doc_vista_doc' ), $args );
}

function doc_vista_register_product_taxonomy() {

    $labels = array(
        'name'                       => _x( 'Products', 'Taxonomy General Name', 'doc-vista' ),
        'singular_name'              => _x( 'Product', 'Taxonomy Singular Name', 'doc-vista' ),
        'menu_name'                  => __( 'Products', 'doc-vista' ),
        'all_items'                  => __( 'All Products', 'doc-vista' ),
        'new_item_name'              => __( 'New Product', 'doc-vista' ),
        'add_new_item'               => __( 'Add New Product', 'doc-vista' ),
        'edit_item'                  => __( 'Edit Product', 'doc-vista' ),
        'update_item'                => __( 'Update Product', 'doc-vista' ),
        'view_item'                  => __( 'View Product', 'doc-vista' ),
        'separate_items_with_commas' => __( 'Separate products with commas', 'doc-vista' ),
        'add_or_remove_items'        => __( 'Add or remove products', 'doc-vista' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'doc-vista' ),
        'popular_items'              => __( 'Popular Products', 'doc-vista' ),
        'search_items'               => __( 'Search Products', 'doc-vista' ),
        'not_found'                  => __( 'Not Found', 'doc-vista' ),
        'no_terms'                   => __( 'No products', 'doc-vista' ),
        'items_list'                 => __( 'Products list', 'doc-vista' ),
        'items_list_navigation'      => __( 'Products list navigation', 'doc-vista' ),
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rest_base'         => 'doc-vista-products',
        'meta_box_cb'       => false,
    );

    register_taxonomy( 'doc_vista_product', array( 'doc_vista_doc' ), $args );
}

function doc_vista_seed_default_terms() {

    $existing_cats = get_terms( array(
        'taxonomy'   => 'doc_vista_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( empty( $existing_cats ) || is_wp_error( $existing_cats ) ) {
        if ( ! term_exists( 'general', 'doc_vista_category' ) ) {
            wp_insert_term(
                'General',
                'doc_vista_category',
                array( 'slug' => 'general' )
            );
        }
    }

    $existing_products = get_terms( array(
        'taxonomy'   => 'doc_vista_product',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( empty( $existing_products ) || is_wp_error( $existing_products ) ) {
        $products = array(
            'default'  => 'Default',
        );

        foreach ( $products as $slug => $name ) {
            if ( ! term_exists( $slug, 'doc_vista_product' ) ) {
                wp_insert_term( $name, 'doc_vista_product', array( 'slug' => $slug ) );
            }
        }
    }
}

add_action( 'pre_delete_term', 'doc_vista_prevent_delete_last_category', 10, 2 );
function doc_vista_prevent_delete_last_category( $term, $taxonomy ) {
    if ( 'doc_vista_category' !== $taxonomy ) {
        return;
    }
    if ( ! taxonomy_exists( 'doc_vista_category' ) ) {
        return;
    }
    $existing = get_terms( array(
        'taxonomy'   => 'doc_vista_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    if ( is_array( $existing ) && count( $existing ) <= 1 ) {
        wp_die( esc_html__( 'At least one documentation category is required. Create a new category before deleting the last one.', 'doc-vista' ) );
    }
}

add_filter( 'doc_vista_category_row_actions', 'doc_vista_hide_delete_last_category_action', 10, 2 );
function doc_vista_hide_delete_last_category_action( $actions, $term ) {
    if ( ! taxonomy_exists( 'doc_vista_category' ) ) {
        return $actions;
    }
    $existing = get_terms( array(
        'taxonomy'   => 'doc_vista_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    if ( is_array( $existing ) && count( $existing ) <= 1 && isset( $actions['delete'] ) ) {
        unset( $actions['delete'] );
    }
    return $actions;
}

add_action( 'admin_init', 'doc_vista_ensure_default_category_exists' );
function doc_vista_ensure_default_category_exists() {
    if ( ! taxonomy_exists( 'doc_vista_category' ) ) {
        return;
    }
    $existing = get_terms( array(
        'taxonomy'   => 'doc_vista_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    if ( empty( $existing ) || is_wp_error( $existing ) ) {
        if ( ! term_exists( 'general', 'doc_vista_category' ) ) {
            wp_insert_term(
                'General',
                'doc_vista_category',
                array( 'slug' => 'general' )
            );
        }
    }
}
