<?php

defined( 'ABSPATH' ) || exit;

function doc_vista_admin_categories_page() {
    if ( ! current_user_can( 'doc_vista_read' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions.', 'doc-vista' ) );
    }

    $can_manage = current_user_can( 'doc_vista_manage_categories' );
    $taxonomy   = 'doc_vista_category';

    $message = '';

    if ( $can_manage && isset( $_POST['doc_vista_add_cat_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['doc_vista_add_cat_nonce'] ) ), 'doc_vista_add_cat' ) ) {
        $name = sanitize_text_field( wp_unslash( $_POST['doc_vista_cat_name'] ?? '' ) );
        $slug = sanitize_title( wp_unslash( $_POST['doc_vista_cat_slug'] ?? '' ) );
        $parent = isset( $_POST['doc_vista_cat_parent'] ) ? (int) wp_unslash( $_POST['doc_vista_cat_parent'] ) : 0;

        if ( $name ) {
            $args = array( 'slug' => $slug ?: sanitize_title( $name ) );
            if ( $parent && term_exists( $parent, $taxonomy ) ) {
                $args['parent'] = $parent;
            }
            $result = wp_insert_term( $name, $taxonomy, $args );
            if ( is_wp_error( $result ) ) {
                $message = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
            } else {
                $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Category added.', 'doc-vista' ) . '</p></div>';
            }
        }
    }

    if ( $can_manage && isset( $_POST['doc_vista_edit_cat_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['doc_vista_edit_cat_nonce'] ) ), 'doc_vista_edit_cat' ) ) {
        $cat_id = isset( $_POST['doc_vista_cat_id'] ) ? (int) wp_unslash( $_POST['doc_vista_cat_id'] ) : 0;
        $name   = sanitize_text_field( wp_unslash( $_POST['doc_vista_cat_name'] ?? '' ) );
        $slug   = sanitize_title( wp_unslash( $_POST['doc_vista_cat_slug'] ?? '' ) );

        if ( $cat_id && $name ) {
            $args = array( 'name' => $name );
            if ( $slug ) {
                $args['slug'] = $slug;
            }
            $result = wp_update_term( $cat_id, $taxonomy, $args );
            if ( is_wp_error( $result ) ) {
                $message = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
            } else {
                $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Category updated.', 'doc-vista' ) . '</p></div>';
            }
        }
    }

    if ( $can_manage && isset( $_GET['action'], $_GET['cat_id'] ) && 'delete' === $_GET['action'] ) {
        $cat_id = (int) $_GET['cat_id'];
        if ( $cat_id && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'delete_cat_' . $cat_id ) ) {
            $result = wp_delete_term( $cat_id, $taxonomy );
            if ( is_wp_error( $result ) ) {
                $message = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
            } else {
                $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Category deleted.', 'doc-vista' ) . '</p></div>';
            }
        }
    }

    $categories = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ) );

    $edit_cat = null;
    if ( $can_manage && isset( $_GET['action'], $_GET['cat_id'] ) && 'edit' === $_GET['action'] ) {
        $edit_cat = get_term( (int) $_GET['cat_id'], $taxonomy );
    }
    ?>
    <div class="wrap doc-vista-categories">
        <h1><?php esc_html_e( 'Doc Categories', 'doc-vista' ); ?></h1>
        <?php echo wp_kses_post( $message ); ?>

        <div class="doc-vista-cats-layout">
            <?php if ( $can_manage ) : ?>
            <div class="doc-vista-cats-form">
                <?php if ( $edit_cat && ! is_wp_error( $edit_cat ) ) : ?>
                    <h2><?php esc_html_e( 'Edit Category', 'doc-vista' ); ?></h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=doc-vista-categories' ) ); ?>">
                        <?php wp_nonce_field( 'doc_vista_edit_cat', 'doc_vista_edit_cat_nonce' ); ?>
                        <input type="hidden" name="doc_vista_cat_id" value="<?php echo esc_attr( $edit_cat->term_id ); ?>" />
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="doc_vista_cat_name"><?php esc_html_e( 'Name', 'doc-vista' ); ?></label></th>
                                <td><input type="text" id="doc_vista_cat_name" name="doc_vista_cat_name" value="<?php echo esc_attr( $edit_cat->name ); ?>" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="doc_vista_cat_slug"><?php esc_html_e( 'Slug', 'doc-vista' ); ?></label></th>
                                <td><input type="text" id="doc_vista_cat_slug" name="doc_vista_cat_slug" value="<?php echo esc_attr( $edit_cat->slug ); ?>" class="regular-text" /></td>
                            </tr>
                        </table>
                        <?php submit_button( __( 'Update Category', 'doc-vista' ) ); ?>
                    </form>
                <?php else : ?>
                    <h2><?php esc_html_e( 'Add New Category', 'doc-vista' ); ?></h2>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'doc_vista_add_cat', 'doc_vista_add_cat_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="doc_vista_cat_name"><?php esc_html_e( 'Name', 'doc-vista' ); ?></label></th>
                                <td><input type="text" id="doc_vista_cat_name" name="doc_vista_cat_name" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="doc_vista_cat_slug"><?php esc_html_e( 'Slug', 'doc-vista' ); ?></label></th>
                                <td><input type="text" id="doc_vista_cat_slug" name="doc_vista_cat_slug" class="regular-text" placeholder="<?php esc_attr_e( 'Auto-generated', 'doc-vista' ); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="doc_vista_cat_parent"><?php esc_html_e( 'Parent', 'doc-vista' ); ?></label></th>
                                <td>
                                    <select id="doc_vista_cat_parent" name="doc_vista_cat_parent">
                                        <option value=""><?php esc_html_e( '— None —', 'doc-vista' ); ?></option>
                                        <?php foreach ( $categories as $cat ) : ?>
                                            <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button( __( 'Add Category', 'doc-vista' ) ); ?>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="doc-vista-cats-list">
                <h2><?php esc_html_e( 'All Categories', 'doc-vista' ); ?></h2>
                <?php if ( empty( $categories ) ) : ?>
                    <p><?php esc_html_e( 'No categories yet.', 'doc-vista' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Name', 'doc-vista' ); ?></th>
                                <th><?php esc_html_e( 'Slug', 'doc-vista' ); ?></th>
                                <th><?php esc_html_e( 'Docs Count', 'doc-vista' ); ?></th>
                                <?php if ( $can_manage ) : ?>
                                <th><?php esc_html_e( 'Actions', 'doc-vista' ); ?></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_cats = count( $categories ); ?>
                            <?php foreach ( $categories as $index => $cat ) :
                                $edit_link   = admin_url( 'admin.php?page=doc-vista-categories&action=edit&cat_id=' . $cat->term_id );
                                $delete_link = wp_nonce_url(
                                    admin_url( 'admin.php?page=doc-vista-categories&action=delete&cat_id=' . $cat->term_id ),
                                    'delete_cat_' . $cat->term_id
                                );
                                $is_last = $total_cats <= 1;
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $cat->name ); ?></strong></td>
                                <td><code><?php echo esc_html( $cat->slug ); ?></code></td>
                                <td><?php echo esc_html( $cat->count ); ?></td>
                                <?php if ( $can_manage ) : ?>
                                <td class="doc-vista-actions">
                                    <a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'doc-vista' ); ?></a>
                                    <?php if ( $is_last ) : ?>
                                        <span class="button button-small button-link-delete doc-vista-btn-disabled" title="<?php esc_attr_e( 'At least one documentation category is required.', 'doc-vista' ); ?>"><?php esc_html_e( 'Delete', 'doc-vista' ); ?></span>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( $delete_link ); ?>" class="button button-small button-link-delete doc-vista-delete-cat" data-confirm="<?php esc_attr_e( 'Delete this category? This action cannot be undone.', 'doc-vista' ); ?>"><?php esc_html_e( 'Delete', 'doc-vista' ); ?></a>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
