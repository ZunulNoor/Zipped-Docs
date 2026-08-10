<?php

defined( 'ABSPATH' ) || exit;

function zipped_docs_register_post_type() {

    $labels = array(
        'name'                  => _x( 'Docs', 'Post Type General Name', 'zipped-docs' ),
        'singular_name'         => _x( 'Doc', 'Post Type Singular Name', 'zipped-docs' ),
        'menu_name'             => __( 'Docs', 'zipped-docs' ),
        'name_admin_bar'        => __( 'Doc', 'zipped-docs' ),
        'archives'              => __( 'Doc Archives', 'zipped-docs' ),
        'attributes'            => __( 'Doc Attributes', 'zipped-docs' ),
        'all_items'             => __( 'All Docs', 'zipped-docs' ),
        'add_new_item'          => __( 'Add New Doc', 'zipped-docs' ),
        'add_new'               => __( 'Add New', 'zipped-docs' ),
        'new_item'              => __( 'New Doc', 'zipped-docs' ),
        'edit_item'             => __( 'Edit Doc', 'zipped-docs' ),
        'update_item'           => __( 'Update Doc', 'zipped-docs' ),
        'view_item'             => __( 'View Doc', 'zipped-docs' ),
        'view_items'            => __( 'View Docs', 'zipped-docs' ),
        'search_items'          => __( 'Search Doc', 'zipped-docs' ),
        'not_found'             => __( 'Not found', 'zipped-docs' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'zipped-docs' ),
        'insert_into_item'      => __( 'Insert into doc', 'zipped-docs' ),
        'uploaded_to_this_item' => __( 'Uploaded to this doc', 'zipped-docs' ),
        'items_list'            => __( 'Docs list', 'zipped-docs' ),
        'item_published'        => __( 'Doc published.', 'zipped-docs' ),
        'item_updated'          => __( 'Doc updated.', 'zipped-docs' ),
    );

    $args = array(
        'label'               => __( 'Docs', 'zipped-docs' ),
        'description'         => __( 'Documentation articles', 'zipped-docs' ),
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
        'capability_type'     => 'zipped_docs_doc',
        'capabilities'        => array(
            'edit_post'              => 'zipped_docs_edit',
            'read_post'              => 'zipped_docs_read',
            'delete_post'            => 'zipped_docs_delete',
            'edit_posts'             => 'zipped_docs_edit',
            'edit_others_posts'      => 'zipped_docs_edit',
            'publish_posts'          => 'zipped_docs_publish',
            'read_private_posts'     => 'zipped_docs_read',
            'create_posts'           => 'zipped_docs_create',
            'delete_posts'           => 'zipped_docs_delete',
            'delete_private_posts'   => 'zipped_docs_delete',
            'delete_published_posts' => 'zipped_docs_delete',
            'delete_others_posts'    => 'zipped_docs_delete',
            'edit_private_posts'     => 'zipped_docs_edit',
            'edit_published_posts'   => 'zipped_docs_edit',
        ),
        'map_meta_cap'        => false,
        'show_in_rest'        => true,
        'rest_base'           => 'zipped-docs-docs',
    );

    register_post_type( 'zipped_docs_doc', $args );
}

function zipped_docs_register_taxonomy() {

    $labels = array(
        'name'                       => _x( 'Doc Categories', 'Taxonomy General Name', 'zipped-docs' ),
        'singular_name'              => _x( 'Doc Category', 'Taxonomy Singular Name', 'zipped-docs' ),
        'menu_name'                  => __( 'Categories', 'zipped-docs' ),
        'all_items'                  => __( 'All Categories', 'zipped-docs' ),
        'parent_item'                => __( 'Parent Category', 'zipped-docs' ),
        'parent_item_colon'          => __( 'Parent Category:', 'zipped-docs' ),
        'new_item_name'              => __( 'New Category Name', 'zipped-docs' ),
        'add_new_item'               => __( 'Add New Category', 'zipped-docs' ),
        'edit_item'                  => __( 'Edit Category', 'zipped-docs' ),
        'update_item'                => __( 'Update Category', 'zipped-docs' ),
        'view_item'                  => __( 'View Category', 'zipped-docs' ),
        'separate_items_with_commas' => __( 'Separate categories with commas', 'zipped-docs' ),
        'add_or_remove_items'        => __( 'Add or remove categories', 'zipped-docs' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'zipped-docs' ),
        'popular_items'              => __( 'Popular Categories', 'zipped-docs' ),
        'search_items'               => __( 'Search Categories', 'zipped-docs' ),
        'not_found'                  => __( 'Not Found', 'zipped-docs' ),
        'no_terms'                   => __( 'No categories', 'zipped-docs' ),
        'items_list'                 => __( 'Categories list', 'zipped-docs' ),
        'items_list_navigation'      => __( 'Categories list navigation', 'zipped-docs' ),
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
        'rest_base'         => 'zipped-docs-categories',
        'meta_box_cb'       => false,
    );

    register_taxonomy( 'zipped_docs_category', array( 'zipped_docs_doc' ), $args );
}

function zipped_docs_register_product_taxonomy() {

    $labels = array(
        'name'                       => _x( 'Products', 'Taxonomy General Name', 'zipped-docs' ),
        'singular_name'              => _x( 'Product', 'Taxonomy Singular Name', 'zipped-docs' ),
        'menu_name'                  => __( 'Products', 'zipped-docs' ),
        'all_items'                  => __( 'All Products', 'zipped-docs' ),
        'new_item_name'              => __( 'New Product', 'zipped-docs' ),
        'add_new_item'               => __( 'Add New Product', 'zipped-docs' ),
        'edit_item'                  => __( 'Edit Product', 'zipped-docs' ),
        'update_item'                => __( 'Update Product', 'zipped-docs' ),
        'view_item'                  => __( 'View Product', 'zipped-docs' ),
        'separate_items_with_commas' => __( 'Separate products with commas', 'zipped-docs' ),
        'add_or_remove_items'        => __( 'Add or remove products', 'zipped-docs' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'zipped-docs' ),
        'popular_items'              => __( 'Popular Products', 'zipped-docs' ),
        'search_items'               => __( 'Search Products', 'zipped-docs' ),
        'not_found'                  => __( 'Not Found', 'zipped-docs' ),
        'no_terms'                   => __( 'No products', 'zipped-docs' ),
        'items_list'                 => __( 'Products list', 'zipped-docs' ),
        'items_list_navigation'      => __( 'Products list navigation', 'zipped-docs' ),
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
        'rest_base'         => 'zipped-docs-products',
        'meta_box_cb'       => false,
    );

    register_taxonomy( 'zipped_docs_product', array( 'zipped_docs_doc' ), $args );
}

function zipped_docs_seed_default_terms() {

    $existing_cats = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( empty( $existing_cats ) || is_wp_error( $existing_cats ) ) {
        if ( ! term_exists( 'general', 'zipped_docs_category' ) ) {
            wp_insert_term(
                'General',
                'zipped_docs_category',
                array( 'slug' => 'general' )
            );
        }
    }

    $existing_products = get_terms( array(
        'taxonomy'   => 'zipped_docs_product',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( empty( $existing_products ) || is_wp_error( $existing_products ) ) {
        $products = array(
            'default'  => 'Default',
        );

        foreach ( $products as $slug => $name ) {
            if ( ! term_exists( $slug, 'zipped_docs_product' ) ) {
                wp_insert_term( $name, 'zipped_docs_product', array( 'slug' => $slug ) );
            }
        }
    }
}

add_action( 'pre_delete_term', 'zipped_docs_prevent_delete_last_category', 10, 2 );
function zipped_docs_prevent_delete_last_category( $term, $taxonomy ) {
    if ( 'zipped_docs_category' !== $taxonomy ) {
        return;
    }
    if ( ! taxonomy_exists( 'zipped_docs_category' ) ) {
        return;
    }
    $existing = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    if ( is_array( $existing ) && count( $existing ) <= 1 ) {
        wp_die( esc_html__( 'At least one documentation category is required. Create a new category before deleting the last one.', 'zipped-docs' ) );
    }
}

add_filter( 'zipped_docs_category_row_actions', 'zipped_docs_hide_delete_last_category_action', 10, 2 );
function zipped_docs_hide_delete_last_category_action( $actions, $term ) {
    if ( ! taxonomy_exists( 'zipped_docs_category' ) ) {
        return $actions;
    }
    $existing = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    if ( is_array( $existing ) && count( $existing ) <= 1 && isset( $actions['delete'] ) ) {
        unset( $actions['delete'] );
    }
    return $actions;
}

add_action( 'admin_init', 'zipped_docs_ensure_default_category_exists' );
function zipped_docs_ensure_default_category_exists() {
    if ( ! taxonomy_exists( 'zipped_docs_category' ) ) {
        return;
    }
    $existing = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    if ( empty( $existing ) || is_wp_error( $existing ) ) {
        if ( ! term_exists( 'general', 'zipped_docs_category' ) ) {
            wp_insert_term(
                'General',
                'zipped_docs_category',
                array( 'slug' => 'general' )
            );
        }
    }
}
